#!/usr/bin/env python3

import argparse
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


def read_once_mode(timeout_seconds):
    deadline = time.time() + timeout_seconds

    while running and time.time() < deadline:
        seen_uid = read_uid_once()

        if seen_uid is not None:
            print(f"PRESENT:{seen_uid}", flush=True)
            return 0

        time.sleep(0.1)

    return 1


def read_continuous_mode():
    global current_uid

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


def parse_args():
    parser = argparse.ArgumentParser()
    parser.add_argument("--once", action="store_true", help="Exit after first detected RFID chip")
    parser.add_argument("--timeout", type=int, default=10, help="Timeout in seconds for --once mode")

    return parser.parse_args()


args = parse_args()

try:
    if args.once:
        sys.exit(read_once_mode(max(1, args.timeout)))

    read_continuous_mode()
except KeyboardInterrupt:
    pass
finally:
    GPIO.cleanup()
    sys.exit(0)
