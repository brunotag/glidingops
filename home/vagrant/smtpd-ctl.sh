#!/bin/bash
# Start Python SMTP logger for local development email capture
# Requires: Python 3 smtpd module

PIDFILE=/home/vagrant/smtpd.pid
LOGFILE=/home/vagrant/emails.log

start_smtpd() {
    if [ -f "$PIDFILE" ] && kill -0 $(cat "$PIDFILE") 2>/dev/null; then
        echo "SMTP logger already running (PID $(cat $PIDFILE))"
        return 0
    fi
    python3 -c "
import smtpd
import asyncore
import os

class LoggingSMTPD(smtpd.SMTPServer):
    def process_message(self, peer, mailfrom, rcptos, data, mail_options=None, rcpt_options=None):
        with open(os.path.expanduser('~/emails.log'), 'a') as f:
            f.write(f'From: {mailfrom}\n')
            f.write(f'To: {rcptos}\n')
            f.write(f'Data:\n{data}\n')
            f.write('-' * 50 + '\n')
        print(f'Email logged: {mailfrom} -> {rcptos}', flush=True)

server = LoggingSMTPD(('127.0.0.1', 1025), None)
asyncore.loop()
" &
    echo $! > "$PIDFILE"
    echo "SMTP logger started (PID $!)"
}

stop_smtpd() {
    if [ -f "$PIDFILE" ]; then
        kill $(cat "$PIDFILE") 2>/dev/null && echo "Stopped" || echo "Not running"
        rm -f "$PIDFILE"
    else
        echo "PID file not found"
    fi
}

status_smtpd() {
    if [ -f "$PIDFILE" ] && kill -0 $(cat "$PIDFILE") 2>/dev/null; then
        echo "Running (PID $(cat $PIDFILE))"
        echo "Log: $LOGFILE"
        tail -5 "$LOGFILE" 2>/dev/null
    else
        echo "Not running"
    fi
}

case "$1" in
    start) start_smtpd ;;
    stop) stop_smtpd ;;
    status) status_smtpd ;;
    log) tail -f "$LOGFILE" ;;
    *) echo "Usage: $0 {start|stop|status|log}" ;;
esac