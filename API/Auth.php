<?php
session_start();
include('db.php');

$decodedData = json_decode(file_get_contents('php://input'), true);

// Log the received data for debugging
error_log("Received Data: " . print_r($decodedData, true));

// Define the current app version
$AppVersion = '1.0.0';

//* Maintenance mode off
$maintenance_mode = 'off'; //off, on, warn 
$alert = '';
$link = ''; // */

/*/ Schedule maintenance mode 
$maintenance_mode = 'warn'; //off, on, warn
$alert = '[SKG-LMS] Upcoming schedule maintenance on [YYYY-MM-DD, HH:MM(AM/PM)]';
$link = ''; //'www.google.com.my' or '' */

/*/ Maintenance mode on
$maintenance_mode = 'on'; //off, on, warn
$alert = '[SKG-LMS] Maintenance complete, please update the app by downloading and install the latest version of this app'; //A new version of this app is available. Please update the app by downloading and install the latest version of this app
$link = 'www.google.com.my'; //'www.google.com.my' or '' */

try {
    // Check if the action is 'checkVersion'
    if (isset($decodedData['action']) && $decodedData['action'] === 'checkVersion') {
        // Return the version in the response
        $response = array(
            "Message" => "Version check",
            "Version" => $AppVersion,
            "Maintenance" => $maintenance_mode,
            "alert" => $alert,
            "link" => $link
        );
        echo json_encode($response);
        exit(); // Exit after sending the response
    }

    $userExists = 0; // Initialize userExists to ensure it's always defined

    if (isset($decodedData['token'])) {
        // Strip surrounding quotes if they exist
        $token = mysqli_real_escape_string($conn, trim($decodedData['token'], '"'));
    
        // Log the token after trimming
        error_log("Processed Token: " . $token);
    
        // Case-insensitive token comparison
        $SQL = "SELECT users.*, 
                       CONCAT(manager_user.firstname, ' ', manager_user.lastname) AS manager_name,
                       positions.name AS position_name,
                       organization.name AS organization_name,
                       contracts.name AS contract_name,
                       users.employmentdate, 
                       users.identifier 
                FROM users 
                LEFT JOIN users AS manager_user ON users.manager = manager_user.id 
                LEFT JOIN positions ON users.position = positions.id
                LEFT JOIN organization ON users.organization = organization.id
                LEFT JOIN contracts ON users.contract = contracts.id
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
    
                // Assign values
                $Message = "Success";
                $UserID = (string) $row['id'];
                $UserRole = $row['role'];
                $firstname = $row['firstname'];
                $lastname = $row['lastname'];
                $UserName = $row['firstname'] . ' ' . $row['lastname'];
                $email = $row['email'];
                $contract = $row['contract'];
                $contractName = $row['contract_name'];
                $role = $row['role'];
                $manager = $row['manager'];
                $managerName = $row['manager_name'];
                $positionName = $row['position_name'];
                $organizationName = $row['organization_name'];
                $employmentDate = $row['employmentdate'];
                $identifier = $row['identifier'];
    
                // Update expires column in oauth_access_tokens table
                $datetime = new DateTime('now', new DateTimeZone('UTC'));
                $datetime->setTimezone(new DateTimeZone('Asia/Kuala_Lumpur')); // GMT+8
                $access_token_expires = $datetime->modify('+5 minutes')->format("Y-m-d H:i:s");
    
                // Update the expiry time of the token
                $updateSQL = "UPDATE oauth_access_tokens SET expires = ? WHERE LOWER(access_token) = LOWER(?)";
                $updateStmt = $conn->prepare($updateSQL);
                $updateStmt->bind_param("ss", $access_token_expires, $token);
    
                if ($updateStmt->execute()) {
                    error_log("Token expiry updated successfully.");
                } else {
                    error_log("Failed to update token expiry: " . $updateStmt->error);
                }
    
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
                           FROM users t1 
                           JOIN delegations t2 ON t1.id = t2.delegate_id 
                           WHERE t1.id = '$UserID'";
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
                $_SESSION['firstname'] = $firstname;
                $_SESSION['lastname'] = $lastname;
                $_SESSION['user_name'] = $UserName;
                $_SESSION['user_email'] = $email;
                $_SESSION['user_exists'] = $userExists;
                $_SESSION['contract'] = $contract;
                $_SESSION['contract_name'] = $contractName;
                $_SESSION['role'] = $role;
                $_SESSION['delegate_id'] = $delegate;
                $_SESSION['manager'] = $manager;
                $_SESSION['manager_name'] = $managerName;
                $_SESSION['position_name'] = $positionName;
                $_SESSION['organization_name'] = $organizationName;
                $_SESSION['employmentdate'] = $employmentDate;
                $_SESSION['identifier'] = $identifier;
                $_SESSION['access_token_expires'] = $access_token_expires;
            } else {
                $Message = "Invalid token";
                error_log("No matching token found in the database.");
            }
        } else {
            $Message = "Query execution failed";
            error_log("SQL execution failed: " . $stmt->error);
        }
    }
     elseif (isset($decodedData['Login']) && isset($decodedData['Password'])) {
        $UserLogin = mysqli_real_escape_string($conn, $decodedData['Login']);
        $UserPassword = $decodedData['Password'];

        $SQL = "SELECT users.*, 
                       CONCAT(manager_user.firstname, ' ', manager_user.lastname) AS manager_name,
                       positions.name AS position_name,
                       organization.name AS organization_name,
                       contracts.name AS contract_name
                FROM users 
                LEFT JOIN users AS manager_user ON users.manager = manager_user.id 
                LEFT JOIN positions ON users.position = positions.id 
                LEFT JOIN organization ON users.organization = organization.id
                LEFT JOIN contracts ON users.contract = contracts.id
                WHERE users.login = ?";
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
                $firstname = $row['firstname'];
                $lastname = $row['lastname'];
                $email = $row['email'];
                $contract = $row['contract'];
                $contractName = $row['contract_name']; // Get the contract name from the contracts table
                $role = $row['role'];
                $manager = $row['manager'];
                $managerName = $row['manager_name'];
                $positionName = $row['position_name'];
                $organizationName = $row['organization_name'];
                $employmentDate = $row['employmentdate'];
                $identifier = $row['identifier'];

                $VerificationToken = bin2hex(random_bytes(16));
                //$refresh_token = bin2hex(random_bytes(16));
                // Create DateTime object for current time in UTC
                $datetime = new DateTime('now', new DateTimeZone('UTC'));

                // Set the timezone to GMT+8
                $datetime->setTimezone(new DateTimeZone('Asia/Kuala_Lumpur')); // GMT+8

                // Format the timestamp to the desired format and assign to variables
                $access_token_expires = $datetime->modify('+5 minute')->format("Y-m-d H:i:s");  
                //$refresh_token_expires = $datetime->modify('+5 minutes')->format("Y-m-d H:i:s");

                $expires = $datetime->modify('+5 minute')->format("Y-m-d H:i:s"); 

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
                $insertSQL = "INSERT INTO oauth_access_tokens (user_id, client_id, access_token, expires) VALUES (?, ?, ?, ?)";
                $insertStmt = $conn->prepare($insertSQL);
                $insertStmt->bind_param("isss", $UserID, $clientID, $VerificationToken, $expires);
                $insertStmt->execute();

                // Create a new entry with the incremented client_id
                $clientID = "device " . $tokenCount;
                $insertSQL = "INSERT INTO oauth_refresh_tokens (user_id, client_id, refresh_token, expires) VALUES (?, ?, ?, ?)";
                $insertStmt = $conn->prepare($insertSQL);
                $insertStmt->bind_param("isss", $UserID, $clientID, $refresh_token, $expires);
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
                           FROM users t1 
                           JOIN delegations t2 ON t1.id = t2.delegate_id 
                           WHERE t1.id = '$UserID'";

                $result3 = mysqli_query($conn, $query3);
                $delegate = null;
                if ($result3) {
                    $row3 = mysqli_fetch_assoc($result3);
                    if ($row3) {
                        $delegate = $row3['delegate_id'];
                    }
                }

                $UserName = $firstname . ' ' . $lastname;

                // Set session variables
                $_SESSION['user_id'] = $UserID;
                $_SESSION['user_role'] = $UserRole;
                $_SESSION['firstname'] = $firstname;
                $_SESSION['lastname'] = $lastname;
                $_SESSION['user_name'] = $UserName;
                $_SESSION['user_email'] = $email;
                $_SESSION['user_exists'] = $userExists;
                $_SESSION['contract'] = $contract;
                $_SESSION['contract_name'] = $contractName;
                $_SESSION['role'] = $role;
                $_SESSION['delegate_id'] = $delegate;
                $_SESSION['manager'] = $manager;
                $_SESSION['manager_name'] = $managerName;
                $_SESSION['position_name'] = $positionName;
                $_SESSION['organization_name'] = $organizationName;
                $_SESSION['employmentdate'] = $employmentDate;
                $_SESSION['identifier'] = $identifier;
                $_SESSION['verification_token'] = $VerificationToken;
                //$_SESSION['refresh_token'] = $refresh_token;
                $_SESSION['access_token_expires'] = $access_token_expires;
                //$_SESSION['refresh_token_expires'] = $refresh_token_expires;
            } else {
                $Message = "Wrong Email or Password";
            }
        } else {
            $Message = "Wrong Email or Password";
        }
    } else {
        $Message = "Invalid request: Email or password missing.";
        return [];
    }

    // Log the session data for debugging
    error_log("Session Data: " . print_r($_SESSION, true));

    // Prepare the response
    $response = array(
        "Message" => $Message,
        "UserID" => isset($_SESSION['user_id']) ? (string) $_SESSION['user_id'] : null,
        "UserRole" => isset($_SESSION['user_role']) ? $_SESSION['user_role'] : null,
        "firstname" => isset($_SESSION['firstname']) ? $_SESSION['firstname'] : null,
        "lastname" => isset($_SESSION['lastname']) ? $_SESSION['lastname'] : null,
        "UserName" => isset($_SESSION['user_name']) ? $_SESSION['user_name'] : null,
        "Email" => isset($_SESSION['user_email']) ? $_SESSION['user_email'] : null,
        "userExists" => (string) $userExists, // Ensure userExists is always defined

        "VerificationToken" => isset($_SESSION['verification_token']) ? $_SESSION['verification_token'] : null,
        "refresh_token" => isset($_SESSION['refresh_token']) ? $_SESSION['refresh_token'] : null,
        "access_token_expires" => isset($_SESSION['access_token_expires']) ? $_SESSION['access_token_expires'] : null,
        "refresh_token_expires" => isset($_SESSION['refresh_token_expires']) ? $_SESSION['refresh_token_expires'] : null,
   
        "contract" => isset($_SESSION['contract']) ? $_SESSION['contract'] : null,
        "contractName" => isset($_SESSION['contract_name']) ? $_SESSION['contract_name'] : null,
        "role" => isset($_SESSION['role']) ? $_SESSION['role'] : null,
        "delegate" => isset($_SESSION['delegate_id']) ? $_SESSION['delegate_id'] : null,
        "manager" => isset($_SESSION['manager']) ? $_SESSION['manager'] : null,
        "managerName" => isset($_SESSION['manager_name']) ? $_SESSION['manager_name'] : null,
        "positionName" => isset($_SESSION['position_name']) ? $_SESSION['position_name'] : null,
        "organizationName" => isset($_SESSION['organization_name']) ? $_SESSION['organization_name'] : null,
        "employmentdate" => isset($_SESSION['employmentdate']) ? $_SESSION['employmentdate'] : null,
        "identifier" => isset($_SESSION['identifier']) ? $_SESSION['identifier'] : null,
        "Version" => $AppVersion,
    );

    echo json_encode($response);
} catch (Exception $e) {
    $response = array(
        "Message" => "Error: " . $e->getMessage(),
    );
    echo json_encode($response);
}
?>
