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
        $query = "UPDATE users SET fcm_token = ? WHERE id = ?";
        $stmt = $conn->prepare($query);
        if ($stmt) {
            $stmt->bind_param("si", $token, $userId);
            if ($stmt->execute()) {
                echo json_encode(['status' => 'success', 'message' => 'Token saved successfully.']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to save token.']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to prepare statement.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid input.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
}
?>
