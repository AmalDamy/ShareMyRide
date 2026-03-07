import pytest
import time
import random
import string
import json
import os
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC

def get_random_string(length=8):
    return ''.join(random.choices(string.ascii_lowercase + string.digits, k=length))

def test_registration(driver, base_url):
    driver.get(f"{base_url}/login.php?mode=signup")
    
    # Wait for the switch if not already on signup
    signup_name = driver.find_element(By.ID, "signupName")
    
    name = f"Test User {get_random_string(4)}"
    email = f"test_{get_random_string(6)}@example.com"
    password = "Password123!"
    
    signup_name.send_keys(name)
    driver.find_element(By.ID, "signupEmail").send_keys(email)
    driver.find_element(By.ID, "signupPass").send_keys(password)
    
    # Wait for animation
    time.sleep(2)
    
    # Click Sign Up - Use JS click to avoid interception by overlay
    signup_btn = driver.find_element(By.CSS_SELECTOR, "#signupForm button")
    driver.execute_script("arguments[0].click();", signup_btn)
    
    # Wait for success message or redirect to login
    wait = WebDriverWait(driver, 10)
    alert = wait.until(EC.visibility_of_element_located((By.ID, "signup-alert")))
    
    assert "successful" in alert.text.lower() or "created" in alert.text.lower()
    
    # Save credentials for other test files
    creds = {"email": email, "password": password}
    with open("creds.json", "w") as f:
        json.dump(creds, f)
