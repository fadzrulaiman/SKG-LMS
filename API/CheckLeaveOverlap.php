<?php
header('Content-Type: application/json');

include 'db.php'; // Include your database connection configuration

$user_id = $_POST['user_id'];
$start_date = $_POST['start_date'];
$end_date = $_POST['end_date'];

$sql = "SELECT * FROM leaves WHERE employee = ? AND status NOT IN (4, 6)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$overlap = false;

while ($row = $result->fetch_assoc()) {
    $existing_start = $row['startdate'];
    $existing_end = $row['enddate'];

    if (($start_date >= $existing_start && $start_date <= $existing_end) ||
        ($end_date >= $existing_start && $end_date <= $existing_end) ||
        ($start_date <= $existing_start && $end_date >= $existing_end)) {
        $overlap = true;
        break;
    }
}

$response = array('success' => !$overlap);
echo json_encode($response);
?>
