#!/usr/bin/env python3

"""
Simple SRT Server for Media Server
Receives SRT streams and relays them via FFmpeg to Flussonic RTMP
"""

import os
import sys
import socket
import subprocess
import threading
import logging
import time
import signal
import requests
from pathlib import Path

# Setup logging
log_file = Path('/var/www/mediaserver/storage/logs/srt-server.log')
log_file.parent.mkdir(parents=True, exist_ok=True)

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
SRT_LISTEN_IP = '0.0.0.0'
SRT_LISTEN_PORT = 9000
RTMP_RELAY_HOST = '127.0.0.1'
RTMP_RELAY_PORT = 1935
WEBHOOK_URL = 'http://127.0.0.1:8000/api/srt/connect'
STREAM_ID = 'compassiontv'
FFMPEG_TIMEOUT = 30

def signal_handler(sig, frame):
    """Handle SIGTERM and SIGINT gracefully"""
    logger.info('Received signal - shutting down...')
    sys.exit(0)

def check_port_available(port):
    """Check if UDP port is available"""
    sock = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
    try:
        sock.bind(('0.0.0.0', port))
        sock.close()
        return True
    except OSError:
        return False
    finally:
        sock.close()

def start_ffmpeg_relay(stream_id):
    """Start FFmpeg relay from SRT to RTMP"""
    rtmp_url = f'rtmp://{RTMP_RELAY_HOST}:{RTMP_RELAY_PORT}/live/{stream_id}'
    srt_input = f'srt://127.0.0.1:{SRT_LISTEN_PORT}?streamid={stream_id}'
    
    logger.info(f'Starting FFmpeg relay: {srt_input} → {rtmp_url}')
    
    try:
        # Use FFmpeg to relay SRT to RTMP
        cmd = [
            'ffmpeg',
            '-hide_banner',
            '-protocol_whitelist', 'file,http,https,tcp,tls,srt,crypto',
            '-i', srt_input,
            '-c:v', 'copy',
            '-c:a', 'copy',
            '-f', 'flv',
            '-flvflags', 'no_duration_filesize',
            rtmp_url
        ]
        
        logger.info(f'Executing: {" ".join(cmd)}')
        
        process = subprocess.Popen(
            cmd,
            stdout=subprocess.PIPE,
            stderr=subprocess.STDOUT,
            text=True,
            bufsize=1
        )
        
        # Monitor FFmpeg output
        for line in process.stdout:
            line = line.strip()
            if line:
                logger.info(f'[FFmpeg] {line}')
                
                # Notify Laravel when stream connects
                if 'Connection' in line or 'connected' in line:
                    try:
                        requests.get(
                            f'{WEBHOOK_URL}?streamid={stream_id}',
                            timeout=2
                        )
                    except Exception as e:
                        logger.warning(f'Webhook error: {e}')
        
        process.wait()
        logger.error(f'FFmpeg process exited with code {process.returncode}')
        
    except FileNotFoundError:
        logger.error('FFmpeg not found - install with: apt install ffmpeg')
        sys.exit(1)
    except Exception as e:
        logger.error(f'FFmpeg error: {e}')
        sys.exit(1)

def main():
    """Main entry point"""
    signal.signal(signal.SIGTERM, signal_handler)
    signal.signal(signal.SIGINT, signal_handler)
    
    logger.info('='*60)
    logger.info('SRT Server Starting')
    logger.info('='*60)
    logger.info(f'SRT Listen: {SRT_LISTEN_IP}:{SRT_LISTEN_PORT}')
    logger.info(f'RTMP Relay: rtmp://{RTMP_RELAY_HOST}:{RTMP_RELAY_PORT}/live/{STREAM_ID}')
    logger.info(f'Webhook: {WEBHOOK_URL}?streamid={STREAM_ID}')
    
    # Check if port is available
    logger.info(f'Checking if port {SRT_LISTEN_PORT}/UDP is available...')
    if not check_port_available(SRT_LISTEN_PORT):
        logger.error(f'Port {SRT_LISTEN_PORT} is already in use')
        sys.exit(1)
    logger.info(f'Port {SRT_LISTEN_PORT} is available')
    
    # Start FFmpeg relay thread
    logger.info('Starting FFmpeg relay...')
    ffmpeg_thread = threading.Thread(target=start_ffmpeg_relay, args=(STREAM_ID,), daemon=False)
    ffmpeg_thread.start()
    
    logger.info('SRT Server is running. Press Ctrl+C to stop.')
    
    try:
        ffmpeg_thread.join()
    except KeyboardInterrupt:
        logger.info('Keyboard interrupt received')
        sys.exit(0)

if __name__ == '__main__':
    main()
