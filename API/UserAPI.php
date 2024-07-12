<?php
require_once 'db.php';  // Include the database connection file
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

// Get all employees
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
  $query = "SELECT * FROM users";
  $result = mysqli_query($conn, $query);
  $employees = array();

  while ($row = mysqli_fetch_assoc($result)) {
    $employees[] = $row;
  }

  echo json_encode($employees);
}

// Create a new employee
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'create') {
  $encodedData = file_get_contents('php://input');
  $decodedData = json_decode($encodedData, true);

  if (isset($decodedData['FirstName']) && isset($decodedData['LastName']) && isset($decodedData['EmailId']) && isset($decodedData['Password']) && isset($decodedData['Gender']) && isset($decodedData['Dob']) && isset($decodedData['Department']) && isset($decodedData['Address']) && isset($decodedData['leave_days']) && isset($decodedData['Phonenumber']) && isset($decodedData['Status']) && isset($decodedData['RegDate']) && isset($decodedData['role']) && isset($decodedData['location']) && isset($decodedData['verification_token']) && isset($decodedData['employment_Date']) && isset($decodedData['maternity_leave']) && isset($decodedData['paternity_leave']) && isset($decodedData['sick_leave']) && isset($decodedData['leave_bank'])) {
    $FirstName = $decodedData['FirstName'];
    $LastName = $decodedData['LastName'];
    $EmailId = $decodedData['EmailId'];
    $Password = $decodedData['Password'];
    $Gender = $decodedData['Gender'];
    $Dob = $decodedData['Dob'];
    $Department = $decodedData['Department'];
    $Address = $decodedData['Address'];
    $leave_days = $decodedData['leave_days'];
    $Phonenumber = $decodedData['Phonenumber'];
    $Status = $decodedData['Status'];
    $RegDate = $decodedData['RegDate'];
    $role = $decodedData['role'];
    $location = $decodedData['location'];
    $verification_token = $decodedData['verification_token'];
    $employment_Date = $decodedData['employment_Date'];
    $maternity_leave = $decodedData['maternity_leave'];
    $paternity_leave = $decodedData['paternity_leave'];
    $sick_leave = $decodedData['sick_leave'];
    $leave_bank = $decodedData['leave_bank'];

    // Hash the password for security
    $hashedPassword = password_hash($Password, PASSWORD_DEFAULT);

    // Prepare and execute the query
    $query = "INSERT INTO tblemployees (FirstName, LastName, EmailId, Password, Gender, Dob, Department, Address, leave_days, Phonenumber, Status, RegDate, role, location, verification_token, employment_Date, maternity_leave, paternity_leave, sick_leave, leave_bank) VALUES ('$FirstName', '$LastName', '$EmailId', '$hashedPassword', '$Gender', '$Dob', '$Department', '$Address', '$leave_days', '$Phonenumber', '$Status', '$RegDate', '$role', '$location', '$verification_token', '$employment_Date', '$maternity_leave', '$paternity_leave', '$sick_leave', '$leave_bank')";
    $result = mysqli_query($conn, $query);

    if ($result) {
      $emp_id = mysqli_insert_id($conn); // Get the auto-generated employee ID
      echo json_encode(array("emp_id" => $emp_id, "message" => "Employee created successfully."));
    } else {
      http_response_code(500); // Set the appropriate response status code (500 for internal server error)
      echo json_encode(array("message" => "Error: " . mysqli_error($conn)));
    }
  } else {
    http_response_code(400); // Set the appropriate response status code (400 for bad request)
    echo json_encode(array("message" => "Error: Required fields are missing."));
  }
}

// Update an employee
if ($_SERVER['REQUEST_METHOD'] === 'PUT' && isset($_GET['action']) && $_GET['action'] === 'update') {
  $encodedData = file_get_contents('php://input');
  $decodedData = json_decode($encodedData, true);

  if (isset($decodedData['emp_id']) && isset($decodedData['FirstName']) && isset($decodedData['LastName']) && isset($decodedData['EmailId']) && isset($decodedData['Gender']) && isset($decodedData['Dob']) && isset($decodedData['Department']) && isset($decodedData['Address']) && isset($decodedData['leave_days']) && isset($decodedData['Phonenumber']) && isset($decodedData['Status']) && isset($decodedData['RegDate']) && isset($decodedData['role']) && isset($decodedData['location']) && isset($decodedData['verification_token']) && isset($decodedData['employment_Date']) && isset($decodedData['maternity_leave']) && isset($decodedData['paternity_leave']) && isset($decodedData['sick_leave']) && isset($decodedData['leave_bank'])) {
    $emp_id = $decodedData['emp_id'];
    $FirstName = $decodedData['FirstName'];
    $LastName = $decodedData['LastName'];
    $EmailId = $decodedData['EmailId'];
    $Gender = $decodedData['Gender'];
    $Dob = $decodedData['Dob'];
    $Department = $decodedData['Department'];
    $Address = $decodedData['Address'];
    $leave_days = $decodedData['leave_days'];
    $Phonenumber = $decodedData['Phonenumber'];
    $Status = $decodedData['Status'];
    $RegDate = $decodedData['RegDate'];
    $role = $decodedData['role'];
    $location = $decodedData['location'];
    $verification_token = $decodedData['verification_token'];
    $employment_Date = $decodedData['employment_Date'];
    $maternity_leave = $decodedData['maternity_leave'];
    $paternity_leave = $decodedData['paternity_leave'];
    $sick_leave = $decodedData['sick_leave'];
    $leave_bank = $decodedData['leave_bank'];

    // Prepare and execute the query
    $query = "UPDATE tblemployees SET FirstName='$FirstName', LastName='$LastName', EmailId='$EmailId', Gender='$Gender', Dob='$Dob', Department='$Department', Address='$Address', leave_days='$leave_days', Phonenumber='$Phonenumber', Status='$Status', RegDate='$RegDate', role='$role', location='$location', verification_token='$verification_token', employment_Date='$employment_Date', maternity_leave='$maternity_leave', paternity_leave='$paternity_leave', sick_leave='$sick_leave', leave_bank='$leave_bank' WHERE emp_id='$emp_id'";
    $result = mysqli_query($conn, $query);

    if ($result) {
      echo json_encode(array("message" => "Employee updated successfully."));
    } else {
      http_response_code(500); // Set the appropriate response status code (500 for internal server error)
      echo json_encode(array("message" => "Error: " . mysqli_error($conn)));
    }
  } else {
    http_response_code(400); // Set the appropriate response status code (400 for bad request)
    echo json_encode(array("message" => "Error: Required fields are missing."));
  }
}

// Delete an employee
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
  $emp_id = $_GET['emp_id'];

  if (!empty($emp_id)) {
    $query = "DELETE FROM tblemployees WHERE emp_id='$emp_id'";
    $result = mysqli_query($conn, $query);

    if ($result) {
      http_response_code(200); // Set the appropriate response status code (200 for success)
      echo json_encode(array("message" => "Employee deleted successfully."));
    } else {
      http_response_code(500); // Set the appropriate response status code (500 for internal server error)
      echo json_encode(array("message" => "Error: " . mysqli_error($conn)));
    }
  } else {
    http_response_code(400); // Set the appropriate response status code (400 for bad request)
    echo json_encode(array("message" => "Error: Required fields are missing."));
  }
}
?>
