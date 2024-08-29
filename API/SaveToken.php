<?php
require_once 'db.php';  // Include the database connection file

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $token = $data['token'];
    $userId = $data['userId'];

    if (!empty($token) && !empty($userId)) {
        // Check if the token already exists
        $checkTokenQuery = "SELECT id FROM user_fcm_tokens WHERE fcm_token = ?";
        $stmt = $conn->prepare($checkTokenQuery);
        if ($stmt) {
            $stmt->bind_param("s", $token);
            $stmt->execute();
            $stmt->store_result();
            
            if ($stmt->num_rows > 0) {
                // Token already exists, skip insertion
                echo json_encode(['status' => 'success', 'message' => 'Token already exists, skipping insertion.']);
            } else {
                // Token does not exist, proceed with insertion

                // Check the current maximum device_id for the user
                $checkDeviceIdQuery = "SELECT MAX(device_id) AS max_device_id FROM user_fcm_tokens WHERE user_id = ?";
                $stmt = $conn->prepare($checkDeviceIdQuery);
                if ($stmt) {
                    $stmt->bind_param("i", $userId);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $row = $result->fetch_assoc();
                    $maxDeviceId = $row['max_device_id'] ? $row['max_device_id'] : 0;

                    // Increment device_id or reset to 1 for the first device
                    $newDeviceId = $maxDeviceId + 1;

                    // Insert the new token with the incremented device_id
                    $insertQuery = "INSERT INTO user_fcm_tokens (user_id, fcm_token, device_id) VALUES (?, ?, ?)";
                    $insertStmt = $conn->prepare($insertQuery);
                    if ($insertStmt) {
                        $insertStmt->bind_param("isi", $userId, $token, $newDeviceId);
                        if ($insertStmt->execute()) {
                            echo json_encode(['status' => 'success', 'message' => 'Token saved successfully.']);
                        } else {
                            echo json_encode(['status' => 'error', 'message' => 'Failed to save token.']);
                        }
                    } else {
                        echo json_encode(['status' => 'error', 'message' => 'Failed to prepare insert statement.']);
                    }
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Failed to prepare device ID check statement.']);
                }
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to prepare token check statement.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid input.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
}
?>
