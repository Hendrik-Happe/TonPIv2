#!/usr/bin/env python3

import os
import signal
import sqlite3
import sys
import time

import RPi.GPIO as GPIO


running = True


BUTTON_PREVIOUS_PIN = int(os.getenv("GPIO_BTN_PREVIOUS_PIN", "17"))
BUTTON_NEXT_PIN = int(os.getenv("GPIO_BTN_NEXT_PIN", "27"))
BUTTON_VOLUME_DOWN_PIN = int(os.getenv("GPIO_BTN_VOL_DOWN_PIN", "22"))
BUTTON_VOLUME_UP_PIN = int(os.getenv("GPIO_BTN_VOL_UP_PIN", "23"))
LED_READY_PIN = int(os.getenv("GPIO_LED_READY_PIN", "24"))
LED_PLAYING_PIN = int(os.getenv("GPIO_LED_PLAYING_PIN", "25"))
BUTTON_DEBOUNCE_MS = int(os.getenv("GPIO_BUTTON_DEBOUNCE_MS", "180"))
LED_POLL_INTERVAL_MS = int(os.getenv("GPIO_LED_POLL_INTERVAL_MS", "500"))
PLAYER_STATE_DB_PATH = os.getenv("GPIO_PLAYER_STATE_DB_PATH", "database/database.sqlite")


def stop_listener(signum, frame):
    del signum
    del frame

    global running
    running = False


signal.signal(signal.SIGINT, stop_listener)
signal.signal(signal.SIGTERM, stop_listener)


def emit(event_name):
    print(f"EVENT:{event_name}", flush=True)


def read_player_status():
    if not os.path.exists(PLAYER_STATE_DB_PATH):
        return None

    try:
        connection = sqlite3.connect(PLAYER_STATE_DB_PATH, timeout=0.5)
        cursor = connection.cursor()
        cursor.execute("SELECT status FROM player_state WHERE id = 1 LIMIT 1")
        row = cursor.fetchone()
        connection.close()

        if row is None:
            return None

        return row[0]
    except sqlite3.Error:
        return None


def setup_gpio():
    GPIO.setwarnings(False)
    GPIO.setmode(GPIO.BCM)

    GPIO.setup(LED_READY_PIN, GPIO.OUT)
    GPIO.setup(LED_PLAYING_PIN, GPIO.OUT)

    for pin in [
        BUTTON_PREVIOUS_PIN,
        BUTTON_NEXT_PIN,
        BUTTON_VOLUME_DOWN_PIN,
        BUTTON_VOLUME_UP_PIN,
    ]:
        GPIO.setup(pin, GPIO.IN, pull_up_down=GPIO.PUD_UP)

    GPIO.output(LED_READY_PIN, GPIO.HIGH)
    GPIO.output(LED_PLAYING_PIN, GPIO.LOW)


setup_gpio()

GPIO.add_event_detect(
    BUTTON_PREVIOUS_PIN,
    GPIO.FALLING,
    callback=lambda channel: emit("PREVIOUS"),
    bouncetime=BUTTON_DEBOUNCE_MS,
)
GPIO.add_event_detect(
    BUTTON_NEXT_PIN,
    GPIO.FALLING,
    callback=lambda channel: emit("NEXT"),
    bouncetime=BUTTON_DEBOUNCE_MS,
)
GPIO.add_event_detect(
    BUTTON_VOLUME_DOWN_PIN,
    GPIO.FALLING,
    callback=lambda channel: emit("VOLUME_DOWN"),
    bouncetime=BUTTON_DEBOUNCE_MS,
)
GPIO.add_event_detect(
    BUTTON_VOLUME_UP_PIN,
    GPIO.FALLING,
    callback=lambda channel: emit("VOLUME_UP"),
    bouncetime=BUTTON_DEBOUNCE_MS,
)

try:
    while running:
        status = read_player_status()

        if status == "playing":
            GPIO.output(LED_PLAYING_PIN, GPIO.HIGH)
        else:
            GPIO.output(LED_PLAYING_PIN, GPIO.LOW)

        time.sleep(max(0.05, LED_POLL_INTERVAL_MS / 1000.0))
finally:
    GPIO.output(LED_READY_PIN, GPIO.LOW)
    GPIO.output(LED_PLAYING_PIN, GPIO.LOW)
    GPIO.cleanup()
    sys.exit(0)
