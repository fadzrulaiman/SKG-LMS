import smtplib
import logging
import sys

# Set up logging to file and console with extremely detailed information
logging.basicConfig(level=logging.DEBUG, 
                    format='%(asctime)s %(levelname)s %(message)s',
                    handlers=[
                        logging.FileHandler("smtp_debug.log"),  # Log to file
                        logging.StreamHandler(sys.stdout)       # Also log to console
                    ])

def log_and_print(msg):
    logging.debug(msg)
    print(msg)

try:
    log_and_print("Starting SMTP connection (Port 587 for TLS)...")
    
    # Create SMTP session for TLS with a timeout
    server = smtplib.SMTP('mail.sawitkinabalu.com.my', 587, timeout=10)  # Adding timeout
    server.set_debuglevel(2)  # Enable EXTREMELY verbose debug output for the SMTP connection

    # Say EHLO to the server (initial handshake)
    log_and_print("Sending EHLO...")
    server.ehlo()  # or server.helo() if EHLO doesn't work
    
    # Start TLS for security
    log_and_print("Starting TLS encryption...")
    server.starttls()

    # Send EHLO again after starting TLS
    log_and_print("Sending EHLO again after TLS...")
    server.ehlo()

    # Login to the SMTP server
    log_and_print("Attempting to log in...")
    server.login('fadzrulaiman@sawitkinabalu.com.my', 'Aiman182024')

    log_and_print("Login successful! Connection established.")

    # Quit the server
    server.quit()
    log_and_print("SMTP connection closed successfully.")

except smtplib.SMTPException as e:
    log_and_print(f"SMTPException occurred: {str(e)}")
except Exception as e:
    log_and_print(f"An error occurred: {str(e)}")

# Test with SSL on port 465 for additional troubleshooting
try:
    log_and_print("Starting SMTP connection (Port 465 for SSL)...")
    
    # Create SMTP session for SSL
    server_ssl = smtplib.SMTP_SSL('mail.sawitkinabalu.com.my', 465, timeout=10)  # Adding timeout
    server_ssl.set_debuglevel(2)  # Enable EXTREMELY verbose debug output for the SMTP connection

    # Say EHLO to the server (initial handshake)
    log_and_print("Sending EHLO...")
    server_ssl.ehlo()  # or server_ssl.helo() if EHLO doesn't work
    
    # Login to the SMTP server
    log_and_print("Attempting to log in (SSL)...")
    server_ssl.login('fadzrulaiman@sawitkinabalu.com.my', 'Aiman182024')

    log_and_print("SSL Login successful! Connection established (Port 465).")

    # Quit the server
    server_ssl.quit()
    log_and_print("SMTP connection (SSL) closed successfully.")

except smtplib.SMTPException as e:
    log_and_print(f"SMTPException occurred (SSL): {str(e)}")
except Exception as e:
    log_and_print(f"An error occurred (SSL): {str(e)}")
