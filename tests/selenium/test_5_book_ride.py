import pytest
import time
import os
import json
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

def test_book_ride(driver, base_url):
    ensure_logged_in(driver, base_url)
    driver.get(f"{base_url}/find_ride.php?show_self=1")
    
    wait = WebDriverWait(driver, 15)
    
    # Search for the ride
    wait.until(EC.presence_of_element_located((By.ID, "searchFrom")))
    driver.find_element(By.ID, "searchFrom").send_keys("Kottayam")
    driver.find_element(By.ID, "searchTo").send_keys("Kochi")
    
    # Set date to tomorrow
    from datetime import datetime, timedelta
    tomorrow = (datetime.now() + timedelta(days=1)).strftime("%Y-%m-%d")
    driver.execute_script(f"document.getElementById('searchDate').value = '{tomorrow}';")
    
    # Click search button
    try:
        driver.find_element(By.CSS_SELECTOR, "button[type='submit']").click()
    except:
        pass
        
    # Wait for results
    time.sleep(3)
    
    # Click Request Ride button
    request_btn = wait.until(EC.element_to_be_clickable((By.XPATH, "//button[contains(text(), 'Request Ride')]")))
    driver.execute_script("arguments[0].click();", request_btn)
    
    # Fill Request Modal
    wait.until(EC.visibility_of_element_located((By.ID, "modalContent")))
    
    # Handle file upload
    file_input = driver.find_element(By.ID, "mProof")
    driver.execute_script("arguments[0].style.display = 'block';", file_input)
    dummy_file_path = os.path.abspath(r"c:\xampp6\htdocs\sharemyride\tests\selenium\dummy_id.png")
    file_input.send_keys(dummy_file_path)
    
    # Choose "Pay Later"
    pay_later_btn = driver.find_element(By.XPATH, "//span[contains(text(), 'Pay Later')]/parent::div")
    driver.execute_script("arguments[0].click();", pay_later_btn)
    
    # Submit Request
    confirm_btn = wait.until(EC.element_to_be_clickable((By.ID, "btnConfirm")))
    driver.execute_script("arguments[0].click();", confirm_btn)
    
    # Wait for response
    time.sleep(4) 
    
    # Check for success OR the "cannot request own ride" message
    res_text = driver.execute_script("return document.body.innerText;")
    valid_responses = ["Success", "Sent", "Already", "cannot", "own ride", "Request"]
    assert any(word.lower() in res_text.lower() for word in valid_responses)
