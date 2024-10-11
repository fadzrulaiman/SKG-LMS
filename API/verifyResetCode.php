<?php
require 'db.php';  // Include your database connection settings

header('Content-Type: application/json');

// Function to check if the reset code is expired
function isResetCodeExpired($createdAt) {
    $currentTime = new DateTime();
    $createdTime = new DateTime($createdAt);

    // Calculate the difference in seconds
    $intervalInSeconds = $currentTime->getTimestamp() - $createdTime->getTimestamp();
    
    // Convert the difference into minutes
    $minutes = $intervalInSeconds / 60;

    // Check if it's been more than 10 minutes (you can adjust the time limit as needed)
    return $minutes > 10;
}

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    // Check if the required fields are set
    if (isset($input['login']) && isset($input['resetCode'])) {
        $username = $input['login'];
        $resetCode = $input['resetCode'];

        // Sanitize the inputs
        $username = $conn->real_escape_string($username);
        $resetCode = $conn->real_escape_string($resetCode);

        // Query to check if the reset code matches and retrieve created_at time
        $query = "SELECT code, created_at FROM reset_codes WHERE username = '$username' AND code = '$resetCode'";
        $result = $conn->query($query);

        if ($result->num_rows > 0) {
            // Fetch the result
            $resetData = $result->fetch_assoc();
            $createdAt = $resetData['created_at'];

            // Check if the reset code has expired
            if (isResetCodeExpired($createdAt)) {
                // If expired, delete the reset code
                $deleteQuery = "DELETE FROM reset_codes WHERE username = '$username' AND code = '$resetCode'";
                if ($conn->query($deleteQuery) === TRUE) {
                    echo json_encode(['success' => false, 'message' => 'Reset code has expired and has been removed. Please request a new one.']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to delete expired reset code']);
                }
            } else {
                // The code matches and is still valid
                echo json_encode(['success' => true, 'message' => 'Reset code verified successfully']);
            }
        } else {
            // The code does not match, delete the reset code if it's invalid
            $deleteInvalidQuery = "DELETE FROM reset_codes WHERE username = '$username' AND code = '$resetCode'";
            if ($conn->query($deleteInvalidQuery) === TRUE) {
                echo json_encode(['success' => false, 'message' => 'Invalid reset code and has been removed. Please request a new one.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid reset code, failed to remove from database.']);
            }
        }

        $conn->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Username and reset code are required']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
