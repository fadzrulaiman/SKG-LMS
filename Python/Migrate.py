import pandas as pd
import re
from datetime import datetime

# Function to split first and last names
def split_name(full_name):
    parts = full_name.split()
    firstname = parts[0]
    lastname = ' '.join(parts[1:]) if len(parts) > 1 else ''
    return firstname, lastname

# Function to generate the login name
def generate_login(full_name, email, existing_logins):
    if email and isinstance(email, str) and "@" in email:
        base_login = email.split('@')[0].replace('.', '').replace('_', '').lower()
    else:
        parts = full_name.split()
        firstname = parts[0]
        if len(parts) > 1:
            lastname_initial = parts[1][0]
        else:
            lastname_initial = ''
        base_login = firstname + lastname_initial.lower()
    
    login = base_login
    counter = 1
    while login in existing_logins:
        login = f"{base_login}{counter}"
        counter += 1
    existing_logins.add(login)
    return login

# Function to determine the contract type
def determine_contract(employmentdate, level_code):
    date_threshold = datetime(2006, 3, 13)
    if isinstance(employmentdate, str):
        hired_date = datetime.strptime(employmentdate, '%Y-%m-%d')
    elif isinstance(employmentdate, pd.Timestamp):
        hired_date = employmentdate.to_pydatetime()
    
    if hired_date < date_threshold and level_code in ["E1", "E2", "M1", "M2", "M3", "M4", "M5", "M6"]:
        return 1
    elif hired_date >= date_threshold and level_code in ["E1", "E2", "M1", "M2", "M3", "M4", "M5", "M6"]:
        return 3
    elif hired_date < date_threshold and level_code in ["NE1", "NE2", "NE3", "NE4"]:
        return 2
    elif hired_date >= date_threshold and level_code in ["NE1", "NE2", "NE3", "NE4"]:
        return 4
    else:
        return None

# Function to determine the position
def determine_position(level_code):
    positions = {
        "E1": 1, "E2": 2, "M1": 3, "M2": 4, "M3": 5, "M4": 6, "M5": 7, "M6": 8,
        "NE1": 9, "NE2": 10, "NE3": 11, "NE4": 12
    }
    return positions.get(level_code, None)

# Function to determine the location
def determine_location(locations_code):
    locations = {
        "Head Office": 1, "Lahad Datu Region": 2, "Sandakan Region": 3, "Tawau Region": 4, "West Coast Region": 5
    }
    return locations.get(locations_code, None)

# Function to format values for SQL
def format_sql_value(value):
    if pd.isna(value) or value is None:
        return 'NULL'
    elif isinstance(value, str):
        # Escape single quotes in the string
        value = value.replace("'", "''")
        return f"'{value}'"
    else:
        return str(value)

# Load data from Excel file
excel_file_path = r"C:\Users\User\Downloads\Book2.xlsx"
df_old = pd.read_excel(excel_file_path)

# Keep track of existing logins
existing_logins = set()

# Prepare the new DataFrame
data = {
    'id': df_old['No_'],
    'firstname': df_old['First Name'].apply(lambda x: split_name(x)[0]),
    'lastname': df_old['First Name'].apply(lambda x: split_name(x)[1]),
    'login': df_old.apply(lambda row: generate_login(row['First Name'], row['Sawit Email Address'], existing_logins), axis=1),
    'email': df_old['Sawit Email Address'],
    'password': '$2a$08$UcJPtjOftib3DLMN/zlkf.73c/VdYZ.0ZqirUetcrDOE.dqv3uUAe',
    'role': 2,
    'manager': 2,
    'country': None,
    'organization': 61,
    'contract': df_old.apply(lambda row: determine_contract(row['Employment Date'], row['Level Code']), axis=1),
    'position': df_old['Level Code'].apply(determine_position),
    'location': df_old['Staff Location'].apply(determine_location),   
    'employmentdate': pd.to_datetime(df_old['Employment Date']).dt.strftime('%Y-%m-%d'),
    'identifier': '',
    'language': 'en',
    'ldap_path': None,
    'active': 1,
    'timezone': 'Asia/Kuala_Lumpur',
    'calendar': None,
    'random_hash': None,
    'user_properties': None,
    'picture': None
}

df_new = pd.DataFrame(data)

# Display the transformed data
print(df_new.head())

# Save the new DataFrame to an Excel file
output_excel_file_path = r"C:\Users\User\Downloads\Transformed_Data.xlsx"
df_new.to_excel(output_excel_file_path, index=False)

print(f"Transformed data has been saved to {output_excel_file_path}")

# Generate SQL insert statements
sql_statements = []
for _, row in df_new.iterrows():
    sql = f"""
    INSERT INTO `users` (`id`, `firstname`, `lastname`, `login`, `email`, `password`, `role`, `manager`, `country`, `organization`, `contract`, `position`, `location`, `employmentdate`, `identifier`, `language`, `ldap_path`, `active`, `timezone`, `calendar`, `random_hash`, `user_properties`, `picture`) VALUES
    ({format_sql_value(row['id'])}, {format_sql_value(row['firstname'])}, {format_sql_value(row['lastname'])}, {format_sql_value(row['login'])}, {format_sql_value(row['email'])}, {format_sql_value(row['password'])}, {format_sql_value(row['role'])}, {format_sql_value(row['manager'])}, {format_sql_value(row['country'])}, {format_sql_value(row['organization'])}, {format_sql_value(row['contract'])}, {format_sql_value(row['position'])}, {format_sql_value(row['location'])}, {format_sql_value(row['employmentdate'])}, {format_sql_value(row['identifier'])}, {format_sql_value(row['language'])}, {format_sql_value(row['ldap_path'])}, {format_sql_value(row['active'])}, {format_sql_value(row['timezone'])}, {format_sql_value(row['calendar'])}, {format_sql_value(row['random_hash'])}, {format_sql_value(row['user_properties'])}, {format_sql_value(row['picture'])});
    """
    sql_statements.append(sql.strip())

# Join all SQL statements into one string
all_sql_statements = "\n".join(sql_statements)

# Save SQL statements to a file
output_sql_file_path = r"C:\Users\User\Downloads\insert_users.sql"
with open(output_sql_file_path, "w") as file:
    file.write(all_sql_statements)

print(f"SQL insert statements have been saved to {output_sql_file_path}")
