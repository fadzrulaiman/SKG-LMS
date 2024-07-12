<?php
require_once 'db.php';  // Include the database connection file
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

// Get public holidays based on contract
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Get the contract from the POST request
  $input = json_decode(file_get_contents('php://input'), true);
  $contract = isset($input['contract']) ? $input['contract'] : null;

  if ($contract) {
    // Adjust the query based on the contract
    $query = "SELECT * FROM dayoffs WHERE contract = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('s', $contract);
    $stmt->execute();
    $result = $stmt->get_result();
    $publicHolidays = array();

    while ($row = $result->fetch_assoc()) {
      $publicHolidays[] = $row;
    }

    echo json_encode($publicHolidays);
  } else {
    echo json_encode(["error" => "No contract provided."]);
  }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
  $query = "SELECT * FROM dayoffs";
  $result = mysqli_query($conn, $query);
  $publicHolidays = array();

  while ($row = mysqli_fetch_assoc($result)) {
    $publicHolidays[] = $row;
  }

  echo json_encode($publicHolidays);
}
?>
