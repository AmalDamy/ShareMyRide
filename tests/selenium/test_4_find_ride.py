import pytest
import time
import json
from datetime import datetime, timedelta
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
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

def test_find_ride(driver, base_url):
    ensure_logged_in(driver, base_url)
    # Use show_self=1 to bypass self-filtering during tests
    driver.get(f"{base_url}/find_ride.php?show_self=1")
    
    # Search for the ride we just posted
    driver.find_element(By.ID, "searchFrom").send_keys("Kottayam")
    driver.find_element(By.ID, "searchTo").send_keys("Kochi")
    
    # Set date to tomorrow to match the posted ride using JS
    tomorrow = (datetime.now() + timedelta(days=1)).strftime("%Y-%m-%d")
    driver.execute_script(f"document.getElementById('searchDate').value = '{tomorrow}';")
    
    # The search might be automatic on input, but let's click the button to be sure
    try:
        driver.find_element(By.CSS_SELECTOR, "button[type='submit']").click()
    except:
        pass 
        
    time.sleep(2) # Wait for results to load via JS
    
    # Verify results exist
    rides_grid = driver.find_element(By.ID, "ridesGrid")
    assert "Kottayam" in rides_grid.text
    assert "Kochi" in rides_grid.text
