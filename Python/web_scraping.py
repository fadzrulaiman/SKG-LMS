import subprocess
import sys

# Function to install missing packages
def install(package):
    subprocess.check_call([sys.executable, "-m", "pip", "install", package])

# Try to import necessary modules and install them if missing
try:
    import requests
    from selenium import webdriver
    from selenium.webdriver.chrome.service import Service
    from webdriver_manager.chrome import ChromeDriverManager
    from bs4 import BeautifulSoup
    from datetime import datetime
except ImportError as e:
    missing_module = str(e).split()[-1]
    print(f"{missing_module} is missing. Installing...")
    if missing_module == "'webdriver_manager'":
        install('webdriver-manager')
    elif missing_module == "'bs4'":
        install('beautifulsoup4')
    elif missing_module == "'selenium'":
        install('selenium')
    else:
        print(f"Module {missing_module} is not recognized.")
    # Re-run the script after installing the missing package
    import requests
    from selenium import webdriver
    from selenium.webdriver.chrome.service import Service
    from webdriver_manager.chrome import ChromeDriverManager
    from bs4 import BeautifulSoup
    from datetime import datetime

# Get the current year dynamically
current_year = datetime.now().year

# Create a URL with the current year
url = f'https://publicholidays.com.my/sabah/{current_year}-dates/'

# Set up WebDriver using ChromeDriverManager to automatically manage the driver version
service = Service(ChromeDriverManager().install())
driver = webdriver.Chrome(service=service)

# Open the page with Selenium
driver.get(url)

# Get the page source and pass it to BeautifulSoup
soup = BeautifulSoup(driver.page_source, 'html.parser')

# Find the table containing public holidays (class='publicholidays phgtable')
table = soup.find('table', class_='publicholidays phgtable')

# Prepare lists to store the data
dates = []
holidays = []

# Extract data rows from the table
if table:
    rows = table.find('tbody').find_all('tr')
    
    # Loop through rows to extract the Date and Holiday columns
    for row in rows:
        columns = row.find_all('td')
        if len(columns) > 2:
            # Extract date and holiday
            date_str = columns[0].text.strip()  # Date in format like '24 Dec'
            holiday = columns[2].text.strip()  # Holiday title like 'Christmas Eve'
            
            # Remove any single quotes from the holiday name
            holiday = holiday.replace("'", "")  # Removes all single quotes
            
            # Convert the date to 'YYYY-MM-DD' format
            date_obj = datetime.strptime(f"{date_str} {current_year}", "%d %b %Y")
            formatted_date = date_obj.strftime("%Y-%m-%d")
            
            # Append the formatted date and holiday to lists
            dates.append(formatted_date)
            holidays.append(holiday)

else:
    print("Table not found on the webpage.")

# Close the browser
driver.quit()

# Open a file to write the SQL statements
sql_file_path = f'dayoffs_{current_year}.sql'
with open(sql_file_path, 'w') as sql_file:
    # Generate and write the SQL insert statements
    for date, holiday in zip(dates, holidays):
        insert_statement = f"INSERT INTO dayoffs  (contract, date, type, title) VALUES (1, '{date}', 1, '{holiday}');\n"
        sql_file.write(insert_statement)

print(f"SQL file successfully generated at {sql_file_path}")
