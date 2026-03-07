# Selenium Testing Guide for ShareMyRide

I have set up a Selenium testing suite for your project. You can find the test files in the `tests/selenium/` directory.

## Prerequisite: Install Python
It appears Python is not currently installed or not in your system path. To run these tests, please:
1.  Download and install Python from [python.org](https://www.python.org/downloads/).
2.  During installation, make sure to check **"Add Python to PATH"**.

## Setup Instructions

1.  **Open a Terminal** in the `tests/selenium/` directory.
2.  **Install Dependencies**:
    ```bash
    pip install -r requirements.txt
    ```

## Running the Tests

Make sure your XAMPP server (Apache & MySQL) is running.

1.  **Run All Tests**:
    ```bash
    pytest
    ```

2.  **Run Specific Tests**:
    - To test Registration and Login:
      ```bash
      pytest test_auth.py
      ```
    - To test Offering and Finding Rides:
      ```bash
      pytest test_rides.py
      ```

## Test Files Overview

- **`requirements.txt`**: Lists necessary libraries (`selenium`, `webdriver-manager`, `pytest`).
- **`conftest.py`**: Contains shared fixtures, like the WebDriver setup.
- **`test_auth.py`**: Automates user registration and login workflows.
- **`test_rides.py`**: Automates the "Offer a Ride" and "Find a Ride" processes.

> [!NOTE]
> The tests are configured to use Google Chrome. If you use a different browser, you may need to adjust the `conftest.py` file.
