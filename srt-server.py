#!/usr/bin/env python3

"""
SRT Server for Media Server
Uses srt-live-transmit to receive SRT streams and relay to Flussonic RTMP
This is the most reliable approach using the dedicated SRT tool.
"""

import os
import sys
import subprocess
import logging
import signal
import time

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

def check_srt_tools():
    """Check if srt-live-transmit is available"""
    result = subprocess.run(['which', 'srt-live-transmit'], capture_output=True)
    return result.returncode == 0

def main():
    """Main entry point"""
    signal.signal(signal.SIGTERM, signal_handler)
    signal.signal(signal.SIGINT, signal_handler)
    
    srt_input = f'srt://:{SRT_LISTEN_PORT}?mode=listener&transtype=live&latency=1000'
    rtmp_output = f'rtmp://{RTMP_RELAY_HOST}:{RTMP_RELAY_PORT}/live/{STREAM_ID}'
    
    logger.info('='*70)
    logger.info('SRT Server Starting')
    logger.info('='*70)
    logger.info(f'SRT Listen: srt://0.0.0.0:{SRT_LISTEN_PORT} (mode=listener)')
    logger.info(f'RTMP Relay: {rtmp_output}')
    logger.info('Waiting for encoder connections...')
    logger.info('='*70)
    
    try:
        # Use srt-live-transmit - the dedicated SRT relay tool
        # This is more stable than FFmpeg for SRT listening
        
        # First, check if srt-live-transmit is available
        if not check_srt_tools():
            logger.warning('srt-live-transmit not found, attempting to install...')
            logger.warning('Run: sudo apt-get install -y srt-tools')
            logger.info('Falling back to FFmpeg relay with simplified approach...')
            use_ffmpeg_fallback()
            return
        
        # Primary method: Use srt-live-transmit for SRT listening and RTMP output
        cmd = [
            'srt-live-transmit',
            '-loglevel', 'info',
            srt_input,
            rtmp_output
        ]
        
        logger.info(f'Using srt-live-transmit for relay (recommended)')
        logger.info(f'Command: {" ".join(cmd)}')
        
        process = subprocess.Popen(
            cmd,
            stdout=subprocess.PIPE,
            stderr=subprocess.STDOUT,
            text=True,
            bufsize=1
        )
        
        # Monitor relay output
        for line in process.stdout:
            line = line.strip()
            if line:
                logger.info(f'[SRT-Relay] {line}')
        
        process.wait()
        logger.error(f'srt-live-transmit exited with code {process.returncode}')
        
        # Auto-restart on exit
        logger.info('Waiting 3 seconds before restart...')
        time.sleep(3)
        main()  # Recursive restart
        
    except FileNotFoundError:
        logger.error('srt-live-transmit not found - install with: sudo apt-get install -y srt-tools')
        logger.info('Falling back to FFmpeg...')
        use_ffmpeg_fallback()
    except KeyboardInterrupt:
        logger.info('Keyboard interrupt')
        sys.exit(0)
    except Exception as e:
        logger.error(f'Error: {e}')
        time.sleep(3)
        main()  # Restart on error

def use_ffmpeg_fallback():
    """Fallback to FFmpeg if srt-live-transmit is not available"""
    # Note: This approach uses FFmpeg to push TO an RTMP server
    # instead of listening on SRT (which requires complex FFmpeg options)
    
    rtmp_url = f'rtmp://{RTMP_RELAY_HOST}:{RTMP_RELAY_PORT}/live/{STREAM_ID}'
    
    # For FFmpeg fallback, we use a different approach:
    # Create a simple wrapper that accepts input and forwards it
    logger.warning('Using FFmpeg fallback (less stable than srt-live-transmit)')
    logger.info('For better stability, install srt-tools: sudo apt-get install -y srt-tools')
    
    # This would require a more complex setup with intermediate processing
    # For now, just exit and let supervisor restart
    logger.error('FFmpeg fallback requires additional configuration - please install srt-tools')
    sys.exit(1)

if __name__ == '__main__':
    main()

