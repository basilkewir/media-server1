#!/usr/bin/env python3

"""
SRT Server with Dynamic Stream Management
Reads from JSON config file and reloads on SIGUSR1 signal
Supports unlimited streams without service restart
"""

import os
import sys
import json
import subprocess
import logging
import signal
import time
import threading
from pathlib import Path

# Setup logging
log_file = '/var/www/mediaserver/storage/logs/srt-server.log'
os.makedirs(os.path.dirname(log_file), exist_ok=True)

logging.basicConfig(
    level=logging.INFO,
    format='[%(asctime)s] %(levelname)s: %(message)s',
    handlers=[
        logging.FileHandler(log_file),
        logging.StreamHandler()
    ]
)
logger = logging.getLogger(__name__)

# Global configuration
config = {}
srt_processes = {}
ffmpeg_processes = {}
reload_requested = False

def signal_handler(sig, frame):
    """Handle signals gracefully"""
    global reload_requested
    if sig == signal.SIGUSR1:
        logger.info('Reload signal received - reloading configuration')
        reload_requested = True
    elif sig == signal.SIGTERM or sig == signal.SIGINT:
        logger.info('Shutdown signal received - shutting down')
        cleanup_all()
        sys.exit(0)

def load_config():
    """Load configuration from JSON file"""
    global config
    config_file = '/var/www/mediaserver/srt-server-config.json'

    if not os.path.exists(config_file):
        logger.error(f'Config file not found: {config_file}')
        return False

    try:
        with open(config_file, 'r') as f:
            config = json.load(f)
        logger.info(f'Configuration loaded: {len(config.get("streams", {}))} streams configured')
        return True
    except Exception as e:
        logger.error(f'Error loading config: {e}')
        return False

def cleanup_all():
    """Stop all running processes"""
    logger.info('Cleaning up all processes...')

    for stream_id, process in srt_processes.items():
        try:
            process.terminate()
            process.wait(timeout=5)
            logger.info(f'Stopped SRT receiver for {stream_id}')
        except Exception as e:
            logger.error(f'Error stopping SRT receiver {stream_id}: {e}')
            try:
                process.kill()
            except:
                pass

    for stream_id, process in ffmpeg_processes.items():
        try:
            process.terminate()
            process.wait(timeout=5)
            logger.info(f'Stopped FFmpeg relay for {stream_id}')
        except Exception as e:
            logger.error(f'Error stopping FFmpeg {stream_id}: {e}')
            try:
                process.kill()
            except:
                pass

    srt_processes.clear()
    ffmpeg_processes.clear()

def start_srt_receiver(stream_id, config_stream):
    """Start SRT receiver for a stream"""
    try:
        srt_port = config_stream['srt_port']
        streamid = config_stream['streamid']
        udp_port = config_stream['udp_port']

        srt_listen = f'srt://:{srt_port}?mode=listener&latency=1000&streamid={streamid}'
        udp_output = f'udp://127.0.0.1:{udp_port}'

        cmd = [
            'srt-live-transmit',
            srt_listen,
            udp_output
        ]

        logger.info(f'Starting SRT receiver for {stream_id} on port {srt_port}')

        process = subprocess.Popen(
            cmd,
            stdout=subprocess.PIPE,
            stderr=subprocess.STDOUT,
            text=True,
            bufsize=1
        )

        # Monitor output in thread
        def monitor_srt():
            for line in process.stdout:
                line = line.strip()
                if line:
                    logger.info(f'[SRT-RX-{stream_id}] {line}')
            logger.error(f'srt-live-transmit ({stream_id}) exited with code {process.returncode}')

        monitor_thread = threading.Thread(target=monitor_srt, daemon=True)
        monitor_thread.start()

        return process

    except FileNotFoundError:
        logger.error('srt-live-transmit not found. Install with: sudo apt-get install -y srt-tools')
        sys.exit(1)
    except Exception as e:
        logger.error(f'Error starting SRT receiver for {stream_id}: {e}')
        return None

def start_ffmpeg_relay(stream_id, config_stream):
    """Start FFmpeg relay for a stream"""
    try:
        udp_port = config_stream['udp_port']
        rtmp_host = config['rtmp_host']
        rtmp_port = config['rtmp_port']
        rtmp_stream = config_stream['rtmp_stream']

        udp_input = f'udp://127.0.0.1:{udp_port}'
        rtmp_output = f'rtmp://{rtmp_host}:{rtmp_port}/{rtmp_stream}'

        cmd = [
            'ffmpeg',
            '-hide_banner',
            '-loglevel', 'info',
            '-protocol_whitelist', 'file,http,https,tcp,tls,srt,crypto,rtp,udp',
            '-i', udp_input,
            '-c:v', 'copy',
            '-c:a', 'copy',
            '-f', 'flv',
            '-flvflags', 'no_duration_filesize',
            rtmp_output
        ]

        logger.info(f'Starting FFmpeg relay for {stream_id} (UDP :{udp_port} → RTMP /{rtmp_stream})')

        process = subprocess.Popen(
            cmd,
            stdout=subprocess.PIPE,
            stderr=subprocess.STDOUT,
            text=True,
            bufsize=1
        )

        # Monitor output in thread
        def monitor_ffmpeg():
            for line in process.stdout:
                line = line.strip()
                if line:
                    logger.info(f'[FFmpeg-{stream_id}] {line}')
            logger.error(f'FFmpeg ({stream_id}) exited with code {process.returncode}')

        monitor_thread = threading.Thread(target=monitor_ffmpeg, daemon=True)
        monitor_thread.start()

        return process

    except FileNotFoundError:
        logger.error('FFmpeg not found')
        sys.exit(1)
    except Exception as e:
        logger.error(f'Error starting FFmpeg relay for {stream_id}: {e}')
        return None

def reconcile_streams():
    """
    Reconcile configured streams with running processes
    - Start new streams
    - Stop removed streams
    - Restart failed streams
    """
    global srt_processes, ffmpeg_processes, reload_requested

    configured_streams = config.get('streams', {})
    configured_ids = set(configured_streams.keys())
    running_ids = set(srt_processes.keys())

    # Start new streams
    for stream_id in configured_ids - running_ids:
        stream_config = configured_streams[stream_id]
        logger.info(f'Starting new stream: {stream_id}')

        srt_process = start_srt_receiver(stream_id, stream_config)
        if srt_process:
            srt_processes[stream_id] = srt_process

        time.sleep(1)

        ffmpeg_process = start_ffmpeg_relay(stream_id, stream_config)
        if ffmpeg_process:
            ffmpeg_processes[stream_id] = ffmpeg_process

        time.sleep(1)

    # Stop removed streams
    for stream_id in running_ids - configured_ids:
        logger.info(f'Stopping removed stream: {stream_id}')

        if stream_id in srt_processes:
            try:
                srt_processes[stream_id].terminate()
                srt_processes[stream_id].wait(timeout=5)
            except:
                try:
                    srt_processes[stream_id].kill()
                except:
                    pass
            del srt_processes[stream_id]

        if stream_id in ffmpeg_processes:
            try:
                ffmpeg_processes[stream_id].terminate()
                ffmpeg_processes[stream_id].wait(timeout=5)
            except:
                try:
                    ffmpeg_processes[stream_id].kill()
                except:
                    pass
            del ffmpeg_processes[stream_id]

    # Check for crashed processes and restart
    for stream_id in configured_ids:
        if stream_id in srt_processes:
            if srt_processes[stream_id].poll() is not None:
                logger.warning(f'SRT receiver for {stream_id} crashed, restarting...')
                stream_config = configured_streams[stream_id]
                srt_process = start_srt_receiver(stream_id, stream_config)
                if srt_process:
                    srt_processes[stream_id] = srt_process
                time.sleep(1)

        if stream_id in ffmpeg_processes:
            if ffmpeg_processes[stream_id].poll() is not None:
                logger.warning(f'FFmpeg relay for {stream_id} crashed, restarting...')
                stream_config = configured_streams[stream_id]
                ffmpeg_process = start_ffmpeg_relay(stream_id, stream_config)
                if ffmpeg_process:
                    ffmpeg_processes[stream_id] = ffmpeg_process
                time.sleep(1)

    reload_requested = False

def main():
    """Main entry point"""
    global reload_requested

    signal.signal(signal.SIGUSR1, signal_handler)
    signal.signal(signal.SIGTERM, signal_handler)
    signal.signal(signal.SIGINT, signal_handler)

    logger.info('='*70)
    logger.info('SRT Server Starting (Dynamic Multi-Stream Management)')
    logger.info('='*70)

    if not load_config():
        logger.error('Failed to load configuration')
        sys.exit(1)

    # Initial startup
    reconcile_streams()

    logger.info('='*70)
    logger.info(f'SRT Server Ready - {len(srt_processes)} streams active')
    logger.info('='*70)

    # Main loop
    try:
        while True:
            time.sleep(5)

            # Reload if signal received
            if reload_requested:
                if load_config():
                    logger.info('Reconciling streams after configuration reload...')
                    reconcile_streams()

            # Health check
            for stream_id in list(srt_processes.keys()):
                if srt_processes[stream_id].poll() is not None:
                    logger.warning(f'SRT process crashed for {stream_id}, will restart next cycle')

            for stream_id in list(ffmpeg_processes.keys()):
                if ffmpeg_processes[stream_id].poll() is not None:
                    logger.warning(f'FFmpeg process crashed for {stream_id}, will restart next cycle')

    except KeyboardInterrupt:
        logger.info('Keyboard interrupt')
        cleanup_all()
        sys.exit(0)
    except Exception as e:
        logger.error(f'Unexpected error: {e}')
        cleanup_all()
        sys.exit(1)

if __name__ == '__main__':
    main()
