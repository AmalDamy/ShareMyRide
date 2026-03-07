import pytest
import json
import os
import time
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC

def test_login(driver, base_url):
    driver.get(f"{base_url}/logout.php") # Ensure logged out
    driver.get(f"{base_url}/login.php")
    
    # Load credentials from file
    if os.path.exists("creds.json"):
        with open("creds.json", "r") as f:
            creds = json.load(f)
            email = creds["email"]
            password = creds["password"]
    else:
        email = "test_user@example.com"
        password = "Password123!"
        
    driver.find_element(By.ID, "loginEmail").send_keys(email)
    driver.find_element(By.ID, "loginPass").send_keys(password)
    
    login_btn = driver.find_element(By.CSS_SELECTOR, "#loginForm button")
    driver.execute_script("arguments[0].click();", login_btn)
    
    # Wait for dashboard redirect
    wait = WebDriverWait(driver, 10)
    wait.until(EC.url_contains("dashboard.php"))
    
    assert "dashboard.php" in driver.current_url
