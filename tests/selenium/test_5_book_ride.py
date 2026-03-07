import pytest
import time
import os
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC

def test_book_ride(driver, base_url):
    driver.get(f"{base_url}/find_ride.php?show_self=1")
    
    # Search for the ride
    driver.find_element(By.ID, "searchFrom").send_keys("Kottayam")
    driver.find_element(By.ID, "searchTo").send_keys("Kochi")
    
    # Set date to tomorrow to match the posted ride
    from datetime import datetime, timedelta
    tomorrow = (datetime.now() + timedelta(days=1)).strftime("%Y-%m-%d")
    driver.execute_script(f"document.getElementById('searchDate').value = '{tomorrow}';")
    
    # Click search button
    try:
        driver.find_element(By.CSS_SELECTOR, "button[type='submit']").click()
    except:
        pass
        
    # Wait for search results
    wait = WebDriverWait(driver, 10)
    time.sleep(2)
    
    # Click Request Ride button on the first card
    request_btn = wait.until(EC.element_to_be_clickable((By.XPATH, "//button[contains(text(), 'Request Ride')]")))
    driver.execute_script("arguments[0].click();", request_btn)
    
    # Fill Request Modal
    wait.until(EC.visibility_of_element_located((By.ID, "modalContent")))
    
    # Wait for the confirm button to be ready
    confirm_btn = wait.until(EC.element_to_be_clickable((By.ID, "btnConfirm")))
    
    # Handle file upload
    file_input = driver.find_element(By.ID, "mProof")
    driver.execute_script("arguments[0].style.display = 'block';", file_input)
    
    dummy_file_path = os.path.abspath("c:/xampp6/htdocs/sharemyride/tests/selenium/dummy_id.png")
    file_input.send_keys(dummy_file_path)
    
    # Fill phone
    try:
        phone_input = driver.find_element(By.ID, "mPhone")
        driver.execute_script("arguments[0].style.display = 'block'; arguments[0].type = 'text';", phone_input)
        phone_input.clear()
        phone_input.send_keys("9876543210")
    except:
        pass
        
    # Choose "Pay Later"
    pay_later_btn = driver.find_element(By.XPATH, "//span[contains(text(), 'Pay Later')]/parent::div")
    driver.execute_script("arguments[0].click();", pay_later_btn)
    
    # Submit Request
    confirm_btn = wait.until(EC.element_to_be_clickable((By.ID, "btnConfirm")))
    driver.execute_script("arguments[0].click();", confirm_btn)
    
    # Wait for success UI
    time.sleep(4) 
    try:
        # Check for any success indicators
        res_text = driver.execute_script("return document.body.innerText;")
        assert "Success" in res_text or "Sent" in res_text or "Already" in res_text
    except Exception as e:
        print(f"Final URL: {driver.current_url}")
        raise e
