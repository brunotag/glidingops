#!/usr/bin/env python3
import smtpd
import asyncore
import os
import signal
import sys

LOGFILE = os.path.expanduser('~/emails.log')

class LoggingSMTPD(smtpd.SMTPServer):
    def process_message(self, peer, mailfrom, rcptos, data, mail_options=None, rcpt_options=None):
        with open(LOGFILE, 'a') as f:
            f.write(f"=== Email Received ===\n")
            f.write(f"From: {mailfrom}\n")
            f.write(f"To: {', '.join(rcptos)}\n")
            f.write(f"==========================\n")
            if isinstance(data, bytes):
                data = data.decode('utf-8', errors='replace')
            f.write(data)
            f.write(f"\n{'-'*50}\n\n")

        peer_ip = peer[0] if peer else 'unknown'
        print(f"[SMTP] {mailfrom} -> {', '.join(rcptos)} ({len(data)} bytes)", flush=True)

def signal_handler(sig, frame):
    print("\nStopping SMTP logger...")
    sys.exit(0)

signal.signal(signal.SIGINT, signal_handler)
signal.signal(signal.SIGTERM, signal_handler)

if __name__ == "__main__":
    server = LoggingSMTPD(("127.0.0.1", 1025), None)
    print(f"SMTP logger running on 127.0.0.1:1025")
    print(f"Logging to: {LOGFILE}")
    print("Press Ctrl+C to stop")
    asyncore.loop()