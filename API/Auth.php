<?php
session_start();
include('db.php');

$decodedData = json_decode(file_get_contents('php://input'), true);

// Log the received data for debugging
error_log("Received Data: " . print_r($decodedData, true));

try {
    $userExists = 0; // Initialize userExists to ensure it's always defined

    if (isset($decodedData['token'])) {
        // Strip surrounding quotes if they exist
        $token = mysqli_real_escape_string($conn, trim($decodedData['token'], '"'));

        // Log the token after trimming
        error_log("Processed Token: " . $token);

        // Case-insensitive token comparison
        $SQL = "SELECT users.* FROM users 
                JOIN oauth_access_tokens ON users.id = oauth_access_tokens.user_id 
                WHERE LOWER(oauth_access_tokens.access_token) = LOWER(?)";
        $stmt = $conn->prepare($SQL);
        $stmt->bind_param("s", $token);

        // Execute the query and log the result
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            error_log("Query executed. Rows returned: " . $result->num_rows);

            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                error_log("User found: " . print_r($row, true));

                $Message = "Success";
                $UserID = (string) $row['id']; // Cast to string
                $UserRole = $row['role'];
                $UserName = $row['firstname'] . ' ' . $row['lastname'];
                $email = $row['email'];
                $contract = $row['contract'];
                $role = $row['role'];

                // Check if the user is a manager
                $query2 = "SELECT EXISTS (SELECT 1 FROM users WHERE manager = '$UserID') AS user_exists;";
                $result2 = mysqli_query($conn, $query2);
                if ($result2) {
                    $row2 = mysqli_fetch_assoc($result2);
                    if ($row2) {
                        $userExists = $row2['user_exists'];
                    }
                }

                // Check if the user has a delegation
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

                // Set session variables
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
                error_log("No matching token found in database.");
            }
        } else {
            $Message = "Query execution failed";
            error_log("SQL execution failed: " . $stmt->error);
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
    
                // Get the count of existing tokens for the user
                $checkSQL = "SELECT COUNT(*) as token_count FROM oauth_access_tokens WHERE user_id = ?";
                $checkStmt = $conn->prepare($checkSQL);
                $checkStmt->bind_param("i", $UserID);
                $checkStmt->execute();
                $checkResult = $checkStmt->get_result();
                $rowCount = $checkResult->fetch_assoc();
                $tokenCount = $rowCount['token_count'] + 1; // Increment by 1 for the new token
    
                // Create a new entry with the incremented client_id
                $clientID = "device " . $tokenCount;
                $insertSQL = "INSERT INTO oauth_access_tokens (user_id, client_id, access_token) VALUES (?, ?, ?)";
                $insertStmt = $conn->prepare($insertSQL);
                $insertStmt->bind_param("iss", $UserID, $clientID, $VerificationToken);
                $insertStmt->execute();
    
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
    
                // Set session variables
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
    }
    
    else {
        $Message = "Invalid request: Email or password missing.";
    }

    // Log the session data for debugging
    error_log("Session Data: " . print_r($_SESSION, true));

    // Prepare the response
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
