#!/usr/bin/env python3

import signal
import sys
import time

import RPi.GPIO as GPIO
from mfrc522 import SimpleMFRC522


running = True
reader = SimpleMFRC522()
current_uid = None


def stop_listener(signum, frame):
    del signum
    del frame

    global running
    running = False


signal.signal(signal.SIGINT, stop_listener)
signal.signal(signal.SIGTERM, stop_listener)


def read_uid_once():
    status, _ = reader.READER.MFRC522_Request(reader.READER.PICC_REQIDL)

    if status != reader.READER.MI_OK:
        return None

    status, uid = reader.READER.MFRC522_Anticoll()

    if status != reader.READER.MI_OK or not uid:
        return None

    return "".join(f"{part:02X}" for part in uid)


try:
    while running:
        seen_uid = read_uid_once()

        if seen_uid is not None:
            if seen_uid != current_uid:
                current_uid = seen_uid
                print(f"PRESENT:{current_uid}", flush=True)
        elif current_uid is not None:
            print(f"REMOVED:{current_uid}", flush=True)
            current_uid = None

        time.sleep(0.1)
except KeyboardInterrupt:
    pass
finally:
    GPIO.cleanup()
    sys.exit(0)
