<?php
require_once 'db.php';  // Include the database connection file
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['type'])) {
      $type = $_GET['type'];

      if ($type === 'types') {
        $query = "SELECT * FROM types";
        $result = mysqli_query($conn, $query);
        $leaveTypes = array();
      
        while ($row = mysqli_fetch_assoc($result)) {
          $leaveTypes[] = $row;
        }
      
        echo json_encode($leaveTypes);
      } elseif ($type === 'status') {
        $query = "SELECT * FROM status";
        $result = mysqli_query($conn, $query);
        $leaveStatus = array();
      
        while ($row = mysqli_fetch_assoc($result)) {
          $leaveStatus[] = $row;
        }
      
        echo json_encode($leaveStatus);
      } else {
        echo json_encode(array("error" => "Not found."));
      }
    } else {
    echo json_encode(array("error" => "Invalid type provided."));
  }
  
?>
