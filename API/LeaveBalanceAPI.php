<?php

require_once 'db.php'; // Include the database connection file
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

// Create a new leave balance record
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'create') {
    $encodedData = file_get_contents('php://input');
    $decodedData = json_decode($encodedData, true);

    $empid = $decodedData['empid'];
    $leavetypeid = $decodedData['leavetypeid'];
    $Leave_Balance = $decodedData['Leave_Balance'];

    $query = "INSERT INTO tbl_leavebalance (empid, leavetypeid, Leave_Balance) VALUES ('$empid', '$leavetypeid', '$Leave_Balance')";

    if ($conn->query($query) === TRUE) {
        echo json_encode(['message' => 'Leave balance record created successfully.']);
    } else {
        echo json_encode(['error' => 'Error: ' . $conn->error]);
    }
}

// Read all leave balance records
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $query = "SELECT * FROM tbl_leavebalance";
    $result = $conn->query($query);

    if ($result->num_rows > 0) {
        $leaveBalances = [];
        while ($row = $result->fetch_assoc()) {
            $leaveBalances[] = $row;
        }
        echo json_encode($leaveBalances);
    } else {
        echo json_encode([]);
    }
}

// Update a leave balance record
if ($_SERVER['REQUEST_METHOD'] === 'PUT' && isset($_GET['action']) && $_GET['action'] === 'update') {
    $encodedData = file_get_contents('php://input');
    $decodedData = json_decode($encodedData, true);

    $leavebalance_id = $decodedData['leavebalance_id'];
    $empid = $decodedData['empid'];
    $leavetypeid = $decodedData['leavetypeid'];
    $Leave_Balance = $decodedData['Leave_Balance'];

    $query = "UPDATE tbl_leavebalance SET empid='$empid', leavetypeid='$leavetypeid', Leave_Balance='$Leave_Balance' WHERE leavebalance_id='$leavebalance_id'";

    if ($conn->query($query) === TRUE) {
        echo json_encode(['message' => 'Leave balance record updated successfully.']);
    } else {
        echo json_encode(['error' => 'Error: ' . $conn->error]);
    }
}

// Delete a leave balance record
if ($_SERVER['REQUEST_METHOD'] === 'DELETE' && isset($_GET['action']) && $_GET['action'] === 'delete') {
    $encodedData = file_get_contents('php://input');
    $decodedData = json_decode($encodedData, true);
    $leavebalance_id = $decodedData['leavebalance_id'];

    $query = "DELETE FROM tbl_leavebalance WHERE leavebalance_id='$leavebalance_id'";

    if ($conn->query($query) === TRUE) {
        echo json_encode(['message' => 'Leave balance record deleted successfully.']);
    } else {
        echo json_encode(['error' => 'Error: ' . $conn->error]);
    }
}

$conn->close();

?>
