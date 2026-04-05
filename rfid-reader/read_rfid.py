#!/usr/bin/env python3

import argparse
import signal
import sqlite3
import sys
import time

import RPi.GPIO as GPIO
from mfrc522 import SimpleMFRC522


running = True
reader = SimpleMFRC522()
current_uid = None
db_path = "database/database.sqlite"


def stop_listener(signum, frame):
    del signum
    del frame

    global running
    running = False


signal.signal(signal.SIGINT, stop_listener)
signal.signal(signal.SIGTERM, stop_listener)


def update_rfid_status_in_db(uid):
    """Update RFID status in database"""
    try:
        conn = sqlite3.connect(db_path, timeout=1.0)
        cursor = conn.cursor()

        # Create table if it doesn't exist
        cursor.execute('''
            CREATE TABLE IF NOT EXISTS rfid_status (
                id INTEGER PRIMARY KEY,
                current_uid TEXT,
                last_seen TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ''')

        # Update or insert status
        cursor.execute('''
            INSERT OR REPLACE INTO rfid_status (id, current_uid, last_seen)
            VALUES (1, ?, CURRENT_TIMESTAMP)
        ''', (uid,))

        conn.commit()
        conn.close()
    except Exception as e:
        print(f"DB Error: {e}", file=sys.stderr)


def read_uid_continuous():
    """Continuously read RFID tags and update database"""
    global current_uid

    while running:
        try:
            status, _ = reader.READER.MFRC522_Request(reader.READER.PICC_REQIDL)

            if status == reader.READER.MI_OK:
                status, uid = reader.READER.MFRC522_Anticoll()

                if status == reader.READER.MI_OK and uid:
                    uid_str = "".join(f"{part:02X}" for part in uid)

                    if uid_str != current_uid:
                        current_uid = uid_str
                        print(f"PRESENT:{uid_str}", flush=True)
                        update_rfid_status_in_db(uid_str)
                else:
                    # No tag present
                    if current_uid is not None:
                        current_uid = None
                        print("ABSENT", flush=True)
                        update_rfid_status_in_db(None)
            else:
                # No tag present
                if current_uid is not None:
                    current_uid = None
                    print("ABSENT", flush=True)
                    update_rfid_status_in_db(None)

            time.sleep(0.1)  # Small delay to prevent busy waiting

        except Exception as e:
            print(f"RFID Error: {e}", file=sys.stderr)
            time.sleep(1)  # Wait before retrying


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
            update_rfid_status_in_db(seen_uid)
            break

        time.sleep(0.1)


def main():
    parser = argparse.ArgumentParser(description='RFID Reader for TonPI')
    parser.add_argument('--once', action='store_true', help='Read once and exit')
    parser.add_argument('--timeout', type=int, default=10, help='Timeout for once mode in seconds')

    args = parser.parse_args()

    try:
        if args.once:
            read_once_mode(args.timeout)
        else:
            print("Starting continuous RFID monitoring...", flush=True)
            read_uid_continuous()
    except KeyboardInterrupt:
        pass
    finally:
        print("Cleaning up RFID reader...", flush=True)
        GPIO.cleanup()

    return 0


if __name__ == "__main__":
    exit(main())
