<?php
require_once 'db.php';  // Include the database connection file
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

// Get all leave types
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
  $query = "SELECT * FROM tblleavetype";
  $result = mysqli_query($conn, $query);
  $leaveTypes = array();

  while ($row = mysqli_fetch_assoc($result)) {
    $leaveTypes[] = $row;
  }

  echo json_encode($leaveTypes);
}

// Create a new leave type
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'create') {
  $encodedData = file_get_contents('php://input');
  $decodedData = json_decode($encodedData, true);

  if (isset($decodedData['LeaveType']) && isset($decodedData['Description'])) {
    $leaveType = $decodedData['LeaveType'];
    $description = $decodedData['Description'];

    // Prepare and execute the query
    $query = "INSERT INTO tblleavetype (LeaveType, Description) VALUES ('$leaveType', '$description')";
    $result = mysqli_query($conn, $query);

    if ($result) {
      echo "Success";
    } else {
      echo "Error: " . mysqli_error($conn);
    }
  } else {
    echo "Error: Required fields are missing.";
  }
}

// Update a leave type
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'update') {
  $encodedData = file_get_contents('php://input');
  $decodedData = json_decode($encodedData, true);

  if (isset($decodedData['id2']) && isset($decodedData['LeaveType']) && isset($decodedData['Description'])) {
    $id2 = $decodedData['id2'];
    $leaveType = $decodedData['LeaveType'];
    $description = $decodedData['Description'];

    $query = "UPDATE tblleavetype SET LeaveType='$leaveType', Description='$description' WHERE id2='$id2'";
    $result = mysqli_query($conn, $query);

    if ($result) {
      echo "Success";
    } else {
      echo "Error: " . mysqli_error($conn);
    }
  } else {
    echo "Error: Required fields are missing.";
  }
}

// Delete a leave type
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'delete') {
  $encodedData = file_get_contents('php://input');
  $decodedData = json_decode($encodedData, true);

  if (isset($decodedData['id2'])) {
    $id2 = $decodedData['id2'];

    $query = "DELETE FROM tblleavetype WHERE id2='$id2'";
    $result = mysqli_query($conn, $query);

    if ($result) {
      echo "Success";
    } else {
      echo "Error: " . mysqli_error($conn);
    }
  } else {
    echo "Error: Required fields are missing.";
  }
}
?>
