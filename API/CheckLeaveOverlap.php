<?php
include 'db.php'; // Include your database connection configuration
header('Content-Type: application/json');

// Retrieve JSON input
$input = file_get_contents('php://input');
$decodedData = json_decode($input, true);

// Check if the required keys exist in the JSON input
if (!isset($decodedData['user_id']) || !isset($decodedData['start_date']) || !isset($decodedData['end_date'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid input data']);
    exit();
}

// Assign variables from JSON input
$user_id = $decodedData['user_id'];
$start_date = $decodedData['start_date'];
$end_date = $decodedData['end_date'];

// Prepare the SQL query
$sql = "SELECT * FROM leaves WHERE employee = ? AND status NOT IN (4, 6)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$overlap = false;

while ($row = $result->fetch_assoc()) {
    $existing_start = $row['startdate'];
    $existing_end = $row['enddate'];

    if (
        (strtotime($start_date) >= strtotime($existing_start) && strtotime($start_date) <= strtotime($existing_end)) ||
        (strtotime($end_date) >= strtotime($existing_start) && strtotime($end_date) <= strtotime($existing_end)) ||
        (strtotime($start_date) <= strtotime($existing_start) && strtotime($end_date) >= strtotime($existing_end))
    ) {
        $overlap = true;
        break;
    }
}

$response = array('success' => !$overlap);
echo json_encode($response);

$stmt->close();
$conn->close();
?>
