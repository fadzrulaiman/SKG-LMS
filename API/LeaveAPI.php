<?php
require_once 'db.php';  // Include the database connection file
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

// Create a new leave
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'create') {
  $encodedData = file_get_contents('php://input');
  $decodedData = json_decode($encodedData, true);
  $requiredFields = array('user_id', 'leave_Type', 'leave_desc', 'start_date', 'end_date', 'leave_duration','department_id','posting_date');
  $missingFields = array();

  foreach ($requiredFields as $field) {
    if (!isset($decodedData[$field])) {
      $missingFields[] = $field;
    }
  }

  if (empty($missingFields)) {
    $userId = $decodedData['user_id'];
    $leaveType = $decodedData['leave_Type'];
    $leaveDesc = $decodedData['leave_desc'];
    $startDate = $decodedData['start_date'];
    $endDate = $decodedData['end_date'];
    $leaveDuration = $decodedData['leave_duration'];
    $departmentId = $decodedData['department_id'];
    $leaveStatus = $decodedData['leave_status'];
    //$leaveStatus2 = $decodedData['leave_status_2'];
    $postingDate = $decodedData['posting_date'];
    $departmentid = $decodedData['department_id'];

    $query = "INSERT INTO tblleaves (empid, LeaveType, Description, FromDate, ToDate, RequestedDays, Status, PostingDate, DepartmentID) VALUES ('$userId', '$leaveType', '$leaveDesc', '$startDate', '$endDate', '$leaveDuration', '$leaveStatus', '$postingDate', $departmentid)";
    $result = mysqli_query($conn, $query);

    if ($result) {
      $leaveId = mysqli_insert_id($conn); // Get the auto-generated leave ID
      echo "Success";
    } else {
      echo "Error: " . mysqli_error($conn);
    }
  } else {
    echo "Error: Required fields are missing. Missing fields: " . implode(', ', $missingFields);
  }
}

// Get all leave
if ($_SERVER['REQUEST_METHOD'] === 'GET') { 
  $query = "SELECT * FROM tblleaves";
  $result = mysqli_query($conn, $query);
  $leave = array();

  while ($row = mysqli_fetch_assoc($result)) {
    $leave[] = $row;
  }

  echo json_encode($leave);
}

// Approve a leave (both cases)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && ($_GET['action'] === 'approve' || $_GET['action'] === 'approve2')) {
  // Retrieve the raw request body
  $encodedData = file_get_contents('php://input');

  // Check if the request body is not empty
  if (!empty($encodedData)) {
    // Decode the JSON data
    $decodedData = json_decode($encodedData, true);

    // Check if the required fields are present
    if (isset($decodedData['id'])) {
      // Sanitize the input
      $leaveId = intval($decodedData['id']);

      // Determine the appropriate status field based on the action
      $statusField = ($_GET['action'] === 'approve') ? 'Status' : 'Status2';
      $statusValue = ($_GET['action'] === 'approve') ? '1' : '1';

      // Prepare the SQL query to update the leave record
      $query = "UPDATE tblleaves SET $statusField = ? WHERE id = ?";

      // Prepare the statement
      $stmt = $conn->prepare($query);

      if ($stmt) {
        // Bind parameters and execute the statement
        $stmt->bind_param("si", $statusValue, $leaveId);
        $stmt->execute();

        // Check if the update was successful
        if ($stmt->affected_rows > 0) {
          echo "Success";
        } else {
          echo "Error: Failed to update leave status.";
        }

        // Close the statement
        $stmt->close();
      } else {
        echo "Error: Failed to prepare statement.";
      }
    } else {
      echo "Error: Leave ID is missing.";
    }
  } else {
    echo "Error: Empty request body.";
  }
}


// Update a leave
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'update') {
  $encodedData = file_get_contents('php://input');
  $decodedData = json_decode($encodedData, true);
  $requiredFields = array('id', 'leave_Type', 'leave_desc', 'start_date', 'end_date', 'leave_duration', 'posting_date');
  $missingFields = array();

  foreach ($requiredFields as $field) {
    if (!isset($decodedData[$field])) {
      $missingFields[] = $field;
    }
  }

  if (empty($missingFields)) {
    $leaveId = $decodedData['id'];
    $leaveType = $decodedData['leave_Type'];
    $leaveDesc = $decodedData['leave_desc'];
    $startDate = $decodedData['start_date'];
    $endDate = $decodedData['end_date'];
    $leaveDuration = $decodedData['leave_duration'];
    $postingDate = $decodedData['posting_date'];

    $query = "UPDATE tblleaves SET LeaveType = '$leaveType', Description = '$leaveDesc', FromDate = '$startDate', ToDate = '$endDate', RequestedDays = '$leaveDuration', PostingDate = '$postingDate' WHERE id = $leaveId";
    $result = mysqli_query($conn, $query);

    if ($result) {
      echo "Success";
    } else {
      echo "Error1: " . mysqli_error($conn);
    }
  } else {
    echo "Error: Required fields are missing. Missing fields: " . implode(', ', $missingFields);
  }
}


// Delete a leave
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'delete') {
  $encodedData = file_get_contents('php://input');
  $decodedData = json_decode($encodedData, true);
  $leaveId = $decodedData['id'];

  // Check if the leave ID is provided
  if (isset($leaveId)) {
    // Construct the SQL query to delete the leave record
    $query = "DELETE FROM tblleaves WHERE id = $leaveId";
    $result = mysqli_query($conn, $query);

    if ($result) {
      echo "Success";
    } else {
      echo "Error: " . mysqli_error($conn);
    }
  } else {
    echo "Error: Leave ID is missing.";
  }
}
