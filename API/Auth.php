<?php
session_start();
include('db.php');

$decodedData = json_decode(file_get_contents('php://input'), true);

// Log the decoded data for debugging
error_log("Received Data: " . print_r($decodedData, true));

try {
    $userExists = 0; // Initialize userExists to ensure it's always defined

    if (isset($decodedData['token'])) {
        $token = mysqli_real_escape_string($conn, $decodedData['token']);

        $SQL = "SELECT users.* FROM users 
                JOIN oauth_access_tokens ON users.id = oauth_access_tokens.user_id 
                WHERE oauth_access_tokens.access_token = ?";
        $stmt = $conn->prepare($SQL);
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $Message = "Success";
            $UserID = (string) $row['id']; // Cast to string
            $UserRole = $row['role'];
            $UserName = $row['firstname'] . ' ' . $row['lastname'];
            $email = $row['email'];
            $contract = $row['contract'];
            $role = $row['role'];

            $query2 = "SELECT EXISTS (SELECT 1 FROM users WHERE manager = '$UserID') AS user_exists;";
            $result2 = mysqli_query($conn, $query2);
            if ($result2) {
                $row2 = mysqli_fetch_assoc($result2);
                if ($row2) {
                    $userExists = $row2['user_exists'];
                }
            }

            $query3 = "SELECT t2.delegate_id
                       FROM users t1 JOIN delegations t2 
                       ON t1.id = t2.delegate_id WHERE t1.id = '$UserID'";

            $result3 = mysqli_query($conn, $query3);
            $delegate = null;
            if ($result3) {
                $row3 = mysqli_fetch_assoc($result3);
                if ($row3) {
                    $delegate = $row3['delegate_id'];
                }
            }

            $_SESSION['user_id'] = $UserID;
            $_SESSION['user_role'] = $UserRole;
            $_SESSION['user_name'] = $UserName;
            $_SESSION['user_email'] = $email;
            $_SESSION['user_exists'] = $userExists;
            $_SESSION['contract'] = $contract;
            $_SESSION['role'] = $role;
            $_SESSION['delegate_id'] = $delegate;
        } else {
            $Message = "Invalid token";
        }
    } elseif (isset($decodedData['Login']) && isset($decodedData['Password'])) {
        $UserLogin = mysqli_real_escape_string($conn, $decodedData['Login']);
        $UserPassword = $decodedData['Password'];

        $SQL = "SELECT * FROM users WHERE login = ?";
        $stmt = $conn->prepare($SQL);
        $stmt->bind_param("s", $UserLogin);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $storedPassword = $row['password'];

            if (password_verify($UserPassword, $storedPassword) || $UserPassword == $storedPassword) {
                $Message = "Success";
                $UserID = (string) $row['id']; // Cast to string
                $UserRole = $row['role'];
                $FirstName = $row['firstname'];
                $LastName = $row['lastname'];
                $email = $row['email'];
                $contract = $row['contract'];
                $role = $row['role'];

                $VerificationToken = bin2hex(random_bytes(16));
                $currentDate = date('Y-m-d H:i:s');

                $checkSQL = "SELECT * FROM oauth_access_tokens WHERE user_id = ?";
                $checkStmt = $conn->prepare($checkSQL);
                $checkStmt->bind_param("i", $UserID);
                $checkStmt->execute();
                $checkResult = $checkStmt->get_result();

                if ($checkResult->num_rows > 0) {
                    $updateSQL = "UPDATE oauth_access_tokens SET access_token = ?, scope = ? WHERE user_id = ?";
                    $updateStmt = $conn->prepare($updateSQL);
                    $updateStmt->bind_param("ssi", $VerificationToken, $currentDate, $UserID);
                    $updateStmt->execute();
                } else {
                    $insertSQL = "INSERT INTO oauth_access_tokens (user_id, scope, access_token) VALUES (?, ?, ?)";
                    $insertStmt = $conn->prepare($insertSQL);
                    $insertStmt->bind_param("iss", $UserID, $currentDate, $VerificationToken);
                    $insertStmt->execute();
                }

                $query2 = "SELECT EXISTS (SELECT 1 FROM users WHERE manager = '$UserID') AS user_exists;";
                $result2 = mysqli_query($conn, $query2);
                if ($result2) {
                    $row2 = mysqli_fetch_assoc($result2);
                    if ($row2) {
                        $userExists = $row2['user_exists'];
                    }
                }

                $query3 = "SELECT t2.delegate_id
                           FROM users t1 JOIN delegations t2 
                           ON t1.id = t2.delegate_id WHERE t1.id = '$UserID'";

                $result3 = mysqli_query($conn, $query3);
                $delegate = null;
                if ($result3) {
                    $row3 = mysqli_fetch_assoc($result3);
                    if ($row3) {
                        $delegate = $row3['delegate_id'];
                    }
                }

                $UserName = $FirstName . ' ' . $LastName;

                $_SESSION['user_id'] = $UserID;
                $_SESSION['user_role'] = $UserRole;
                $_SESSION['user_name'] = $UserName;
                $_SESSION['user_email'] = $email;
                $_SESSION['user_exists'] = $userExists;
                $_SESSION['contract'] = $contract;
                $_SESSION['role'] = $role;
                $_SESSION['delegate_id'] = $delegate;
            } else {
                $Message = "Wrong Email or Password";
            }
        } else {
            $Message = "Wrong Email or Password";
        }
    } else {
        $Message = "Invalid request: Email or password missing.";
    }

    // Log the session data for debugging
    error_log("Session Data: " . print_r($_SESSION, true));

    $response = array(
        "Message" => $Message,
        "UserID" => isset($_SESSION['user_id']) ? (string) $_SESSION['user_id'] : null,
        "UserRole" => isset($_SESSION['user_role']) ? $_SESSION['user_role'] : null,
        "UserName" => isset($_SESSION['user_name']) ? $_SESSION['user_name'] : null,
        "Email" => isset($_SESSION['user_email']) ? $_SESSION['user_email'] : null,
        "userExists" => (string) $userExists, // Ensure userExists is always defined
        "VerificationToken" => isset($VerificationToken) ? $VerificationToken : null,
        "contract" => isset($_SESSION['contract']) ? $_SESSION['contract'] : null,
        "role" => isset($_SESSION['role']) ? $_SESSION['role'] : null,
        "delegate" => isset($_SESSION['delegate_id']) ? $_SESSION['delegate_id'] : null,
    );

    echo json_encode($response);
} catch (Exception $e) {
    $response = array(
        "Message" => "Error: " . $e->getMessage(),
    );
    echo json_encode($response);
}
?>
