<?php
$conn = mysqli_connect('localhost', 'root', '');
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
$database = mysqli_select_db($conn, 'lms');
if (!$database) {
    die("Database selection failed: " . mysqli_error($conn));
}

$encodedData = file_get_contents('php://input');
$decodedData = json_decode($encodedData, true);
?>
