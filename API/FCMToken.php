<?php
header('Content-Type: application/json');

require_once 'db.php'; // Database connection

$data = json_decode(file_get_contents('php://input'), true);

$userID = $data['userID'];
$fcmToken = isset($data['fcmToken']) ? $data['fcmToken'] : null;

if (empty($userID)) {
    echo json_encode(['Message' => 'User ID is required.']);
    exit;
}

if ($fcmToken) {
    // Update the FCM token
    $query = "UPDATE users SET fcm_token = ? WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('si', $fcmToken, $userID);

    if ($stmt->execute()) {
        echo json_encode(['Message' => 'Success']);
    } else {
        echo json_encode(['Message' => 'Failed to update token']);
    }

    $stmt->close();
} else {
    // Fetch the FCM token
    $query = "SELECT fcm_token FROM users WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $userID);
    $stmt->execute();
    $stmt->bind_result($fcmToken);
    $stmt->fetch();

    if ($fcmToken) {
        echo json_encode(['fcmToken' => $fcmToken]);
    } else {
        echo json_encode(['Message' => 'Token not found']);
    }

    $stmt->close();
}

$conn->close();
?>
