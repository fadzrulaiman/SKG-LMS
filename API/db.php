<?php
// Try the first connection attempt
$conn = mysqli_connect('localhost', 'skglms', 'Sawit2024');

if (!$conn) {
    // If the first attempt fails, try the second connection
    error_log("Connection failed with root user. Trying skglms user...");
    $conn = mysqli_connect('localhost', 'skglms', 'Sawit2024');

    // If the second attempt also fails, terminate with an error message
    if (!$conn) {
        die("Connection failed: " . mysqli_connect_error());
    }
}

// Select the database
$database = mysqli_select_db($conn, 'lms');
if (!$database) {
    die("Database selection failed: " . mysqli_error($conn));
}

// Handle incoming data
$encodedData = file_get_contents('php://input');
$decodedData = json_decode($encodedData, true);
?>
