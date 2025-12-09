#!/usr/bin/env python3
"""
E3F-DS100C4 NPN Sensor Monitor
Detects bottles via proximity sensor
"""

import time
import requests
import logging
import json

# -------------------------
# Logging Configuration
# -------------------------
logging.basicConfig(
    level=logging.INFO,
    format='[%(asctime)s] %(levelname)s: %(message)s',
    handlers=[
        logging.FileHandler('/var/log/bottle-sensor.log'),
        logging.StreamHandler()
    ]
)

# -------------------------
# GPIO Handling (Mock mode if not Raspberry Pi)
# -------------------------
try:
    import RPi.GPIO as GPIO
    IS_RASPBERRY_PI = True
except (ImportError, RuntimeError):
    logging.warning("RPi.GPIO not available - running in MOCK mode")
    IS_RASPBERRY_PI = False

    class MockGPIO:
        BCM = 1
        IN = 1
        PUD_UP = 1

        @staticmethod
        def setmode(mode): pass

        @staticmethod
        def setup(pin, mode, pull_up_down=None): pass

        @staticmethod
        def input(pin):
            return 1  # Always HIGH → No bottle

        @staticmethod
        def cleanup(): pass

    GPIO = MockGPIO()

# -------------------------
# Configuration
# -------------------------
SENSOR_PIN = 17
SENSOR_URL = 'http://localhost/sensor.php'

DEBOUNCE_TIME = 0.15  # seconds
CHECK_INTERVAL = 0.05  # seconds
MIN_DELAY_BETWEEN_TRIGGERS = 1.0  # Prevent duplicates


# -------------------------
# Bottle Detection Function
# -------------------------
def detect_bottles():
    """
    Monitor E3F-DS100C4 sensor for bottle insertions.
    Sensor Logic:
        - NO bottle = HIGH (1)
        - Bottle detected = LOW (0)
    """

    GPIO.setmode(GPIO.BCM)
    GPIO.setup(SENSOR_PIN, GPIO.IN, pull_up_down=GPIO.PUD_UP)

    logging.info("Sensor initialized on GPIO %d with pull-up enabled", SENSOR_PIN)

    bottle_state = False
    last_trigger_time = 0

    try:
        while True:
            state = GPIO.input(SENSOR_PIN)

            # LOW = bottle detected
            if state == 0 and not bottle_state:
                current_time = time.time()

                # Ignore duplicates
                if current_time - last_trigger_time < MIN_DELAY_BETWEEN_TRIGGERS:
                    time.sleep(CHECK_INTERVAL)
                    continue

                # Start debounce
                start = current_time
                bottle_state = True
                logging.debug("Bottle signal detected, debouncing...")

                # Wait until stable LOW
                while GPIO.input(SENSOR_PIN) == 0:
                    if time.time() - start >= DEBOUNCE_TIME:
                        break
                    time.sleep(0.01)

                # Confirm detection
                if GPIO.input(SENSOR_PIN) == 0:
                    logging.info("✓ Bottle confirmed!")

                    # Send to PHP API
                    try:
                        response = requests.post(
                            SENSOR_URL,
                            timeout=5,
                            data={"source": "e3f-ds100c4"}
                        )

                        if response.status_code == 200:
                            try:
                                result = response.json()
                            except json.JSONDecodeError:
                                result = response.text

                            logging.info("Bottle logged: %s", result)

                        else:
                            logging.warning("API returned HTTP %d", response.status_code)

                    except requests.RequestException as e:
                        logging.error("API request failed: %s", e)

                    last_trigger_time = time.time()

            # Reset when sensor goes back HIGH
            if state == 1:
                bottle_state = False

            time.sleep(CHECK_INTERVAL)

    except KeyboardInterrupt:
        logging.info("Stopped by user")

    except Exception as e:
        logging.error("Runtime error: %s", e)

    finally:
        GPIO.cleanup()
        logging.info("GPIO cleaned up")


# -------------------------
# Main Entry
# -------------------------
if __name__ == '__main__':
    if IS_RASPBERRY_PI:
        logging.info("Running on Raspberry Pi")
    else:
        logging.warning("Running in MOCK mode (no real sensor)")

    detect_bottles()
