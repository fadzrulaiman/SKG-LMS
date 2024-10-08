import pandas as pd
from datetime import datetime

# Function to format values for SQL
def format_sql_value(value):
    if pd.isna(value) or value is None:
        return 'NULL'
    elif isinstance(value, str):
        value = value.replace("'", "''")
        return f"'{value}'"
    elif isinstance(value, (datetime, pd.Timestamp)):
        return f"'{value.date()}'"  # Format as date only
    else:
        return str(value)

# Load data from Excel file
excel_file_path = r"C:\Users\SKG-USER-DTU\Downloads\FINIT_Leave_2023.xlsx"
df_old = pd.read_excel(excel_file_path)

# Determine the 'type' based on 'Cause of Absence Code'
def determine_leave_type(cause_code):
    if cause_code == 'AL':
        return 1  # Type 1 for Annual Leave
    elif cause_code == 'SICK':
        return 2  # Type 2 for Sick Leave
    else:
        return 'NULL'  # Default case if neither

# Prepare the new DataFrame based on your transformed data
data = {
    'startdate': df_old['From Date'],
    'enddate': df_old['To Date'],
    'Status': 3,  # Assuming this is a constant value
    'employee': df_old['Employee No_'],
    'startdatetype': 'Morning',  # Assuming this is a constant value
    'enddatetype': 'Afternoon',  # Assuming this is a constant value
    'duration': df_old['Quantity'],
    'type': df_old['Cause of Absence Code'].apply(determine_leave_type),  # Dynamic leave type based on cause code
}

df_new = pd.DataFrame(data)

# Display the transformed data
print(df_new.head())

# Save the new DataFrame to an Excel file
output_excel_file_path = r"C:\Users\SKG-USER-DTU\Downloads\FINIT_Leave_2023.xlsx"
df_new.to_excel(output_excel_file_path, index=False)

print(f"Transformed data has been saved to {output_excel_file_path}")

# Generate SQL insert statements
sql_statements = []
for _, row in df_new.iterrows():
    sql = f"""
    INSERT INTO `leaves` (`startdate`, `enddate`, `Status`, `employee`, `startdatetype`, `enddatetype`, `duration`, `type`) VALUES
    ({format_sql_value(row['startdate'])}, {format_sql_value(row['enddate'])}, {format_sql_value(row['Status'])}, {format_sql_value(row['employee'])}, {format_sql_value(row['startdatetype'])}, {format_sql_value(row['enddatetype'])}, {format_sql_value(row['duration'])}, {format_sql_value(row['type'])});
    """
    sql_statements.append(sql.strip())

# Join all SQL statements into one string
all_sql_statements = "\n".join(sql_statements)

# Save SQL statements to a file
output_sql_file_path = r"C:\Users\SKG-USER-DTU\Downloads\FINIT_Leave_2023.sql"
with open(output_sql_file_path, "w") as file:
    file.write(all_sql_statements)

print(f"SQL insert statements have been saved to {output_sql_file_path}")
