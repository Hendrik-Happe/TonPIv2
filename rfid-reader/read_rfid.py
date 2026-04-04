#!/usr/bin/env python3

import signal
import sys

import RPi.GPIO as GPIO
from mfrc522 import SimpleMFRC522


running = True
reader = SimpleMFRC522()


def stop_listener(signum, frame):
    del signum
    del frame

    global running
    running = False


signal.signal(signal.SIGINT, stop_listener)
signal.signal(signal.SIGTERM, stop_listener)

try:
    while running:
        uid, _ = reader.read()
        print(f"{uid:X}", flush=True)
except KeyboardInterrupt:
    pass
finally:
    GPIO.cleanup()
    sys.exit(0)
