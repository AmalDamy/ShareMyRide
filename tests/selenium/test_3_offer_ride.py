import pytest
import time
import os
import json
from datetime import datetime, timedelta
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait, Select
from selenium.webdriver.support import expected_conditions as EC

def ensure_logged_in(driver, base_url):
    driver.get(f"{base_url}/dashboard.php")
    if "login.php" in driver.current_url:
        driver.get(f"{base_url}/login.php")
        try:
            with open("creds.json", "r") as f:
                creds = json.load(f)
                email = creds["email"]
                password = creds["password"]
        except:
            email = "test_user@example.com"
            password = "Password123!"
            
        driver.find_element(By.ID, "loginEmail").send_keys(email)
        driver.find_element(By.ID, "loginPass").send_keys(password)
        driver.execute_script("arguments[0].click();", driver.find_element(By.CSS_SELECTOR, "#loginForm button"))
        WebDriverWait(driver, 10).until(EC.url_contains("dashboard.php"))

def test_offer_ride(driver, base_url):
    ensure_logged_in(driver, base_url)
    driver.get(f"{base_url}/offer_ride.php")
    
    wait = WebDriverWait(driver, 15)
    wait.until(EC.presence_of_element_located((By.ID, "offerFrom")))
    
    driver.find_element(By.ID, "offerFrom").send_keys("Kottayam")
    driver.find_element(By.ID, "offerTo").send_keys("Kochi")
    
    # Set date to tomorrow using JS for reliability
    tomorrow = (datetime.now() + timedelta(days=1)).strftime("%Y-%m-%d")
    driver.execute_script(f"document.getElementById('offerDate').value = '{tomorrow}';")
    
    # Set time
    driver.execute_script("document.getElementById('offerTime').value = '09:00';")
    
    # Select vehicle
    vehicle_select = Select(driver.find_element(By.ID, "vehicleType"))
    vehicle_select.select_by_value("car")
    
    time.sleep(1) # Wait for JS to update seats
    
    seats_select = Select(driver.find_element(By.ID, "offerSeats"))
    seats_select.select_by_value("4")
    
    driver.find_element(By.ID, "offerPrice").send_keys("200")
    
    # Use JS click for submit
    submit_btn = driver.find_element(By.CSS_SELECTOR, "button[type='submit']")
    driver.execute_script("arguments[0].click();", submit_btn)
    
    # Wait for success message text to appear
    wait.until(lambda d: "success" in d.find_element(By.ID, "rideFormMessage").text.lower() or 
                        "published" in d.find_element(By.ID, "rideFormMessage").text.lower())
    
    msg = driver.find_element(By.ID, "rideFormMessage").text
    assert "success" in msg.lower() or "published" in msg.lower()
