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
UDP_RELAY_PORT = 5000  # Internal UDP relay port
RTMP_RELAY_HOST = '127.0.0.1'
RTMP_RELAY_PORT = 1935
STREAM_ID = 'compassiontv'

def signal_handler(sig, frame):
    """Handle SIGTERM and SIGINT gracefully"""
    logger.info('Received signal - shutting down...')
    sys.exit(0)

def main():
    """Main entry point"""
    signal.signal(signal.SIGTERM, signal_handler)
    signal.signal(signal.SIGINT, signal_handler)
    
    srt_listen = f'srt://:{SRT_LISTEN_PORT}?mode=listener&latency=1000'
    udp_relay = f'udp://127.0.0.1:{UDP_RELAY_PORT}'
    rtmp_output = f'rtmp://{RTMP_RELAY_HOST}:{RTMP_RELAY_PORT}/live/{STREAM_ID}'
    
    logger.info('='*70)
    logger.info('SRT Server Starting (Two-Stage Relay)')
    logger.info('='*70)
    logger.info(f'SRT Listen: srt://0.0.0.0:{SRT_LISTEN_PORT}')
    logger.info(f'Internal Relay: UDP {udp_relay}')
    logger.info(f'RTMP Output: {rtmp_output}')
    logger.info('='*70)
    
    # Start SRT receiver in background thread
    srt_thread = threading.Thread(
        target=start_srt_receiver,
        args=(srt_listen, udp_relay),
        daemon=False
    )
    srt_thread.start()
    
    # Wait a moment for SRT to start
    time.sleep(2)
    
    # Start FFmpeg relay in main thread (will restart on exit)
    while True:
        try:
            start_ffmpeg_relay(udp_relay, rtmp_output)
        except KeyboardInterrupt:
            logger.info('Keyboard interrupt')
            sys.exit(0)
        except Exception as e:
            logger.error(f'Error in main loop: {e}')
            time.sleep(3)

def start_srt_receiver(srt_listen, udp_output):
    """Start srt-live-transmit to receive SRT and output UDP"""
    try:
        # srt-live-transmit receives SRT and outputs to UDP
        cmd = [
            'srt-live-transmit',
            srt_listen,
            udp_output
        ]
        
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
        start_srt_receiver(srt_listen, udp_output)
        
    except FileNotFoundError:
        logger.error('srt-live-transmit not found')
        logger.error('Install with: sudo apt-get install -y srt-tools')
        sys.exit(1)
    except Exception as e:
        logger.error(f'SRT receiver error: {e}')
        time.sleep(3)
        start_srt_receiver(srt_listen, udp_output)

def start_ffmpeg_relay(udp_input, rtmp_output):
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
        
        logger.info(f'Starting FFmpeg relay (UDP → RTMP)')
        
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
                logger.info(f'[FFmpeg] {line}')
        
        process.wait()
        logger.error(f'FFmpeg exited with code {process.returncode}')
        
        # Wait before restart
        logger.info('Waiting 3 seconds before FFmpeg restart...')
        time.sleep(3)
        
    except FileNotFoundError:
        logger.error('FFmpeg not found')
        sys.exit(1)
    except Exception as e:
        logger.error(f'FFmpeg relay error: {e}')
        time.sleep(3)

if __name__ == '__main__':
    main()

