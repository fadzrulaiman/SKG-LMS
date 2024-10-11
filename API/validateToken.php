<?php
header('Content-Type: application/json');

// Include your database connection file
include('db.php');

// Get the input data
$data = json_decode(file_get_contents('php://input'), true);
$token = isset($data['token']) ? $data['token'] : '';

// Response array
$response = [];

// Check if token is provided
if (empty($token)) {
    $response['valid'] = false;
    $response['message'] = 'No token provided';
    echo json_encode($response);
    exit;
}

// Check if the token exists in the database
$query = "SELECT * FROM oauth_access_tokens WHERE access_token = ?";
$stmt = mysqli_prepare($conn, $query);

if ($stmt) {
    // Bind the token parameter and execute the statement
    mysqli_stmt_bind_param($stmt, 's', $token);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($tokenData = mysqli_fetch_assoc($result)) {
        $expires = $tokenData['expires'];
        
        // Get the current time in GMT +8
        $timezone = new DateTimeZone('Asia/Singapore');
        $currentDateTime = new DateTime('now', $timezone);
        $currentTime = $currentDateTime->format('Y-m-d H:i:s');

        // Compare current time with expiration time
        if ($currentTime > $expires) {
            // Token expired, delete the entry
            $deleteQuery = "DELETE FROM oauth_access_tokens WHERE access_token = ?";
            $deleteStmt = mysqli_prepare($conn, $deleteQuery);
            mysqli_stmt_bind_param($deleteStmt, 's', $token);
            mysqli_stmt_execute($deleteStmt);

            $response['valid'] = false;
            $response['message'] = 'Token expired and deleted';
        } else {
            // Token is valid
            $response['valid'] = true;
            $response['message'] = 'Token is valid';
        }
    } else {
        // Token not found or invalid
        $response['valid'] = false;
        $response['message'] = 'Invalid token';
    }

    // Close the statement
    mysqli_stmt_close($stmt);
} else {
    // SQL error
    $response['valid'] = false;
    $response['message'] = 'Error preparing statement';
}

// Close the database connection
mysqli_close($conn);

// Return the response as JSON
echo json_encode($response);
?>
