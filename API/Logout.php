<?php
session_start();
include 'db.php'; // Ensure this file establishes a connection to your database

// Get the posted user ID
$encodedData = file_get_contents('php://input');  // take data from react native fetch API
$decodedData = json_decode($encodedData, true);
$userId = $decodedData['user_id'] ?? null;

if (!$userId) {
    echo json_encode(['success' => false, 'message' => 'No user ID provided']);
    exit();
}

// Check if session exists before trying to destroy it
if (session_status() == PHP_SESSION_ACTIVE) {
    // Clear all session variables
    $_SESSION = array();

    // Destroy the session
    session_destroy();

    // Remove the access token for the current user from the oauth_access_tokens table
    $stmt = $conn->prepare("DELETE FROM oauth_access_tokens WHERE user_id = ?");
    $stmt->bind_param("i", $userId);

    if ($stmt->execute()) {
        // Clear the FCM token from the users table
        $stmt = $conn->prepare("UPDATE users SET fcm_token = NULL WHERE id = ?");
        $stmt->bind_param("i", $userId);

        if ($stmt->execute()) {
            $response = ['success' => true, 'message' => 'Logout successful, access token and FCM token removed'];
        } else {
            $response = ['success' => false, 'message' => 'Logout successful, access token removed but failed to remove FCM token'];
        }

        $stmt->close();
    } else {
        $response = ['success' => false, 'message' => 'Logout successful but failed to remove access token'];
    }

    // Respond with a JSON message
    echo json_encode($response);
} else {
    echo json_encode(['success' => false, 'message' => 'No active session']);
}
exit();
?>
