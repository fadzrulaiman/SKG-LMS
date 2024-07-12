<?php
$conn = mysqli_connect('localhost', 'skglms', 'Sawit2024');
$database = mysqli_select_db($conn, 'lms');

$encodedData = file_get_contents('php://input');  // take data from react native fetch API
$decodedData = json_decode($encodedData, true);
