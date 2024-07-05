<?php
require_once 'db.php';  // Include the database connection file
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

// Get all departments
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
  $query = "SELECT * FROM dayoffs";
  $result = mysqli_query($conn, $query);
  $departments = array();

  while ($row = mysqli_fetch_assoc($result)) {
    $departments[] = $row;
  }

  echo json_encode($departments);
}

