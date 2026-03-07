import pytest
import time
from datetime import datetime, timedelta
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait, Select
from selenium.webdriver.support import expected_conditions as EC

def test_offer_ride(driver, base_url):
    driver.get(f"{base_url}/offer_ride.php")
    
    wait = WebDriverWait(driver, 10)
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
    
    # Wait for success message
    wait.until(EC.visibility_of_element_located((By.ID, "rideFormMessage")))
    msg = driver.find_element(By.ID, "rideFormMessage").text
    assert "success" in msg.lower() or "published" in msg.lower()
