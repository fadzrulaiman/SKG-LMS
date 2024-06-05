<?php
require_once 'db.php';  // Include the database connection file
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

// Get all departments
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
  $query = "SELECT * FROM tbldepartments";
  $result = mysqli_query($conn, $query);
  $departments = array();

  while ($row = mysqli_fetch_assoc($result)) {
    $departments[] = $row;
  }

  echo json_encode($departments);
}

// Create a new department
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'create') {
  $encodedData = file_get_contents('php://input');
  $decodedData = json_decode($encodedData, true);

  if (isset($decodedData['DepartmentName']) && isset($decodedData['DepartmentShortName']) && isset($decodedData['CreationDate'])) {
    $DepartmentName = $decodedData['DepartmentName'];
    $DepartmentShortName = $decodedData['DepartmentShortName'];
    $CreationDate = $decodedData['CreationDate'];

    // Prepare and execute the query
    $query = "INSERT INTO tbldepartments (DepartmentName, DepartmentShortName, CreationDate) VALUES ('$DepartmentName', '$DepartmentShortName', '$CreationDate')";
    $result = mysqli_query($conn, $query);

    if ($result) {
      $id = mysqli_insert_id($conn); // Get the auto-generated department ID
      echo json_encode(array("id" => $id, "message" => "Department created successfully."));
    } else {
      http_response_code(500); // Set the appropriate response status code (500 for internal server error)
      echo json_encode(array("message" => "Error: " . mysqli_error($conn)));
    }
  } else {
    http_response_code(400); // Set the appropriate response status code (400 for bad request)
    echo json_encode(array("message" => "Error: Required fields are missing."));
  }
}

// Update a department
if ($_SERVER['REQUEST_METHOD'] === 'PUT' && isset($_GET['action']) && $_GET['action'] === 'update') {
  $encodedData = file_get_contents('php://input');
  $decodedData = json_decode($encodedData, true);

  if (isset($decodedData['id']) && isset($decodedData['DepartmentName']) && isset($decodedData['DepartmentShortName']) && isset($decodedData['CreationDate'])) {
    $id = $decodedData['id'];
    $DepartmentName = $decodedData['DepartmentName'];
    $DepartmentShortName = $decodedData['DepartmentShortName'];
    $CreationDate = $decodedData['CreationDate'];

    // Prepare and execute the query
    $query = "UPDATE tbldepartments SET DepartmentName='$DepartmentName', DepartmentShortName='$DepartmentShortName', CreationDate='$CreationDate' WHERE id='$id'";
    $result = mysqli_query($conn, $query);

    if ($result) {
      echo json_encode(array("message" => "Department updated successfully."));
    } else {
      http_response_code(500); // Set the appropriate response status code (500 for internal server error)
      echo json_encode(array("message" => "Error: " . mysqli_error($conn)));
    }
  } else {
    http_response_code(400); // Set the appropriate response status code (400 for bad request)
    echo json_encode(array("message" => "Error: Required fields are missing."));
  }
}

// Delete a department
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
  $id = $_GET['id'];

  if (!empty($id)) {
    $query = "DELETE FROM tbldepartments WHERE id='$id'";
    $result = mysqli_query($conn, $query);

    if ($result) {
      http_response_code(200); // Set the appropriate response status code (200 for success)
      echo json_encode(array("message" => "Department deleted successfully."));
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
