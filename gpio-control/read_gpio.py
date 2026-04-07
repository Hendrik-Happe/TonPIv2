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


BUTTON_PINS = [
    BUTTON_PREVIOUS_PIN,
    BUTTON_NEXT_PIN,
    BUTTON_VOLUME_DOWN_PIN,
    BUTTON_VOLUME_UP_PIN,
]


def cleanup_gpio():
    """Release any stale GPIO state from a previous (crashed) run.

    GPIO.cleanup() only frees state in the current process.
    Writing to /sys/class/gpio/unexport releases the kernel sysfs entries
    from any previous process, which is required to re-register edge detection.
    """
    ALL_PINS = BUTTON_PINS + [LED_READY_PIN, LED_PLAYING_PIN]

    # Kernel-level cleanup – works across processes
    for pin in ALL_PINS:
        try:
            with open("/sys/class/gpio/unexport", "w") as f:
                f.write(str(pin))
        except (OSError, IOError):
            pass  # Not currently exported – that's fine

    # In-process cleanup as a secondary measure
    try:
        for pin in BUTTON_PINS:
            try:
                GPIO.remove_event_detect(pin)
            except Exception:
                pass
        GPIO.cleanup()
    except Exception:
        pass


def setup_gpio():
    GPIO.setwarnings(False)
    GPIO.setmode(GPIO.BCM)

    GPIO.setup(LED_READY_PIN, GPIO.OUT)
    GPIO.setup(LED_PLAYING_PIN, GPIO.OUT)

    for pin in BUTTON_PINS:
        GPIO.setup(pin, GPIO.IN, pull_up_down=GPIO.PUD_UP)

    GPIO.output(LED_READY_PIN, GPIO.HIGH)
    GPIO.output(LED_PLAYING_PIN, GPIO.LOW)


BUTTON_EVENTS = [
    (BUTTON_PREVIOUS_PIN, "PREVIOUS"),
    (BUTTON_NEXT_PIN, "NEXT"),
    (BUTTON_VOLUME_DOWN_PIN, "VOLUME_DOWN"),
    (BUTTON_VOLUME_UP_PIN, "VOLUME_UP"),
]

# Polling interval for button reads (20 ms is responsive enough and CPU-friendly)
BUTTON_POLL_INTERVAL = 0.02


# Clean up any stale state from a previous run before initialising
cleanup_gpio()
time.sleep(0.2)
setup_gpio()

# Track previous pin states for edge (HIGH→LOW) detection and debounce
prev_states: dict[int, int] = {pin: GPIO.HIGH for pin, _ in BUTTON_EVENTS}
last_trigger: dict[int, float] = {pin: 0.0 for pin, _ in BUTTON_EVENTS}

led_poll_interval = max(0.05, LED_POLL_INTERVAL_MS / 1000.0)
debounce_seconds = BUTTON_DEBOUNCE_MS / 1000.0
last_led_poll = 0.0

try:
    while running:
        now = time.monotonic()

        # Button polling (replaces edge detection, works on all kernel versions)
        for pin, event_name in BUTTON_EVENTS:
            current = GPIO.input(pin)
            # Detect falling edge (HIGH → LOW = button pressed) with debounce
            if prev_states[pin] == GPIO.HIGH and current == GPIO.LOW:
                if now - last_trigger[pin] >= debounce_seconds:
                    emit(event_name)
                    last_trigger[pin] = now
            prev_states[pin] = current

        # LED update at a lower frequency to avoid hammering the DB
        if now - last_led_poll >= led_poll_interval:
            status = read_player_status()
            GPIO.output(LED_PLAYING_PIN, GPIO.HIGH if status == "playing" else GPIO.LOW)
            last_led_poll = now

        time.sleep(BUTTON_POLL_INTERVAL)
finally:
    GPIO.output(LED_READY_PIN, GPIO.LOW)
    GPIO.output(LED_PLAYING_PIN, GPIO.LOW)
    GPIO.cleanup()
    sys.exit(0)
