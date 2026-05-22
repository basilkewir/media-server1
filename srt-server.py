#!/usr/bin/env python3

"""
SRT Server for Media Server (Stable Implementation)
Uses a two-stage relay:
1. srt-live-transmit receives SRT and outputs to local UDP
2. FFmpeg reads UDP and forwards to Flussonic RTMP
"""

import os
import sys
import subprocess
import logging
import signal
import time
import threading

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

# Configuration
SRT_LISTEN_PORT = 9000
RTMP_RELAY_HOST = '127.0.0.1'
RTMP_RELAY_PORT = 1935

# Multiple streams configuration
STREAMS = {
    'compassiontv': {
        'udp_port': 5000,
        'rtmp_stream': 'compassiontv'
    },
    'sudfmtv': {
        'udp_port': 5001,
        'rtmp_stream': 'sudfmtv'
    }
}

def signal_handler(sig, frame):
    """Handle SIGTERM and SIGINT gracefully"""
    logger.info('Received signal - shutting down...')
    sys.exit(0)

def main():
    """Main entry point"""
    signal.signal(signal.SIGTERM, signal_handler)
    signal.signal(signal.SIGINT, signal_handler)
    
    srt_listen = f'srt://:{SRT_LISTEN_PORT}?mode=listener&latency=1000'
    
    logger.info('='*70)
    logger.info('SRT Server Starting (Multi-Stream Relay)')
    logger.info('='*70)
    logger.info(f'SRT Listen: srt://0.0.0.0:{SRT_LISTEN_PORT}')
    logger.info(f'Streams configured:')
    for stream_name, config in STREAMS.items():
        logger.info(f'  - {stream_name}: UDP :{config["udp_port"]} → RTMP /{config["rtmp_stream"]}')
    logger.info('='*70)
    
    # Start SRT receiver in background thread
    srt_thread = threading.Thread(
        target=start_srt_receiver,
        args=(srt_listen,),
        daemon=False
    )
    srt_thread.start()
    
    # Wait a moment for SRT to start
    time.sleep(2)
    
    # Start FFmpeg relays for each stream
    relay_threads = []
    for stream_name, config in STREAMS.items():
        udp_relay = f'udp://127.0.0.1:{config["udp_port"]}'
        rtmp_output = f'rtmp://{RTMP_RELAY_HOST}:{RTMP_RELAY_PORT}/{config["rtmp_stream"]}'
        
        relay_thread = threading.Thread(
            target=start_ffmpeg_relay,
            args=(stream_name, udp_relay, rtmp_output),
            daemon=False
        )
        relay_threads.append(relay_thread)
        relay_thread.start()
        time.sleep(1)  # Stagger starts
    
    # Keep main thread alive
    try:
        while True:
            time.sleep(1)
    except KeyboardInterrupt:
        logger.info('Keyboard interrupt - shutting down')
        sys.exit(0)

def start_srt_receiver(srt_listen):
    """Start srt-live-transmit to receive SRT and output UDP to multiple relays"""
    try:
        # Build list of UDP outputs for all streams
        udp_outputs = [
            f'udp://127.0.0.1:{config["udp_port"]}'
            for config in STREAMS.values()
        ]
        
        # srt-live-transmit receives SRT and outputs to multiple UDP ports
        cmd = [
            'srt-live-transmit',
            srt_listen,
        ]
        cmd.extend(udp_outputs)
        
        logger.info(f'Starting SRT receiver with srt-live-transmit')
        logger.info(f'Command: {" ".join(cmd)}')
        
        process = subprocess.Popen(
            cmd,
            stdout=subprocess.PIPE,
            stderr=subprocess.STDOUT,
            text=True,
            bufsize=1
        )
        
        # Monitor output
        for line in process.stdout:
            line = line.strip()
            if line:
                logger.info(f'[SRT-RX] {line}')
        
        process.wait()
        logger.error(f'srt-live-transmit exited with code {process.returncode}')
        
        # Restart
        logger.info('Restarting SRT receiver in 3 seconds...')
        time.sleep(3)
        start_srt_receiver(srt_listen)
        
    except FileNotFoundError:
        logger.error('srt-live-transmit not found')
        logger.error('Install with: sudo apt-get install -y srt-tools')
        sys.exit(1)
    except Exception as e:
        logger.error(f'SRT receiver error: {e}')
        time.sleep(3)
        start_srt_receiver(srt_listen)

def start_ffmpeg_relay(stream_name, udp_input, rtmp_output):
    """Start FFmpeg to relay UDP to RTMP"""
    try:
        # FFmpeg reads UDP MPEG-TS and outputs to RTMP
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
        
        logger.info(f'Starting FFmpeg relay for {stream_name} (UDP → RTMP)')
        
        process = subprocess.Popen(
            cmd,
            stdout=subprocess.PIPE,
            stderr=subprocess.STDOUT,
            text=True,
            bufsize=1
        )
        
        # Monitor output
        for line in process.stdout:
            line = line.strip()
            if line:
                logger.info(f'[FFmpeg-{stream_name}] {line}')
        
        process.wait()
        logger.error(f'FFmpeg ({stream_name}) exited with code {process.returncode}')
        
        # Wait before restart
        logger.info(f'Waiting 3 seconds before {stream_name} FFmpeg restart...')
        time.sleep(3)
        # Restart FFmpeg relay
        start_ffmpeg_relay(stream_name, udp_input, rtmp_output)
        
    except FileNotFoundError:
        logger.error('FFmpeg not found')
        sys.exit(1)
    except Exception as e:
        logger.error(f'FFmpeg relay error ({stream_name}): {e}')
        time.sleep(3)
        # Restart on error
        start_ffmpeg_relay(stream_name, udp_input, rtmp_output)

if __name__ == '__main__':
    main()

