#!/usr/bin/env python3

"""
Simple SRT Server for Media Server
Receives SRT streams and relays them via FFmpeg to Flussonic RTMP
"""

import os
import sys
import subprocess
import logging
import signal

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
STREAM_ID = 'compassiontv'

def signal_handler(sig, frame):
    """Handle SIGTERM and SIGINT gracefully"""
    logger.info('Received signal - shutting down...')
    sys.exit(0)

def main():
    """Main entry point"""
    signal.signal(signal.SIGTERM, signal_handler)
    signal.signal(signal.SIGINT, signal_handler)
    
    rtmp_url = f'rtmp://{RTMP_RELAY_HOST}:{RTMP_RELAY_PORT}/live/{STREAM_ID}'
    srt_listen = f'srt://:{SRT_LISTEN_PORT}?mode=listener'
    
    logger.info('='*70)
    logger.info('SRT Server Starting')
    logger.info('='*70)
    logger.info(f'SRT Listen: {srt_listen}')
    logger.info(f'RTMP Relay: {rtmp_url}')
    logger.info('Waiting for encoder connections...')
    logger.info('='*70)
    
    try:
        # Use FFmpeg with -listen 1 flag to receive SRT connections
        # FFmpeg will listen on port 9000 for SRT input from encoder
        cmd = [
            'ffmpeg',
            '-hide_banner',
            '-loglevel', 'info',
            '-protocol_whitelist', 'file,http,https,tcp,tls,srt,crypto,rtp,udp',
            '-f', 'srt',
            '-listen', '1',
            '-i', srt_listen,
            '-c:v', 'copy',
            '-c:a', 'copy',
            '-f', 'flv',
            '-flvflags', 'no_duration_filesize',
            rtmp_url
        ]
        
        logger.info(f'Executing: ffmpeg (SRT listener on port {SRT_LISTEN_PORT})')
        
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
        
        process.wait()
        logger.error(f'FFmpeg exited with code {process.returncode}')
        
    except FileNotFoundError:
        logger.error('FFmpeg not found')
        sys.exit(1)
    except KeyboardInterrupt:
        logger.info('Keyboard interrupt')
        sys.exit(0)
    except Exception as e:
        logger.error(f'Error: {e}')
        sys.exit(1)

if __name__ == '__main__':
    main()

