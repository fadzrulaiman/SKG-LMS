<?php
header('Content-Type: application/json');
require_once 'db.php'; // Include your database connection here

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the raw POST data
    $input = json_decode(file_get_contents('php://input'), true);
    $login = $input['login'] ?? null;

    if ($login) {
        try {
            // Prepare SQL statement to fetch email based on login
            $query = "SELECT email FROM users WHERE login = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("s", $login);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $email = $row['email'];
                echo json_encode(['success' => true, 'email' => $email]);
            } else {
                echo json_encode(['success' => false, 'message' => 'User not found']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error retrieving email: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Login is required']);
    }
}
?>
