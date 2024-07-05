<?php
session_start();
include('db.php');

$decodedData = json_decode(file_get_contents('php://input'), true);

function getLeaveBalances($conn, $UserID) {
    $SQL = "SELECT * FROM tbl_leavebalance WHERE empid = '$UserID'";
    $result = mysqli_query($conn, $SQL);

    if (!$result) {
        throw new Exception("Unable to get leave balances: " . mysqli_error($conn));
    }

    $leaveBalances = array(
        'Balance' => null,
        'Bank' => null,
    );

    while ($row = mysqli_fetch_assoc($result)) {
        error_log("Fetched Row: " . print_r($row, true)); // Log each row
        $leavetypeid = $row['leavetypeid'];
        $leaveBalance = isset($row['leave_balance']) ? $row['leave_balance'] : 0;

        if ($leavetypeid == 10) {
            $leaveBalances['Balance'] = $leaveBalance;
        } elseif ($leavetypeid == 18) {
            $leaveBalances['Bank'] = $leaveBalance;
        }
    }

    error_log("Leave Balances: " . print_r($leaveBalances, true));

    return $leaveBalances;
}

try {
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

            $leaveBalances = getLeaveBalances($conn, $UserID);

            $query2 = "SELECT t2.id, t2.name, t2.supervisor
                       FROM users t1
                       JOIN organization t2 ON t1.organization = t2.id
                       WHERE t1.id = '$UserID'";

            $result2 = mysqli_query($conn, $query2);

            if ($result2 && mysqli_num_rows($result2) > 0) {
                $row = mysqli_fetch_assoc($result2);
                $DepartmentID = (string) $row['id']; // Cast to string
                $DepartmentShortName = $row['name'];
                $Supervisor = $row['supervisor'];
            }

            $_SESSION['user_id'] = $UserID;
            $_SESSION['user_role'] = $UserRole;
            $_SESSION['user_name'] = $UserName;
            $_SESSION['user_email'] = $email;
            $_SESSION['user_department'] = $DepartmentID;
            $_SESSION['user_balance'] = $leaveBalances['Balance'];
            $_SESSION['user_bank'] = $leaveBalances['Bank'];
            $_SESSION['department_short_name'] = $DepartmentShortName;
            $_SESSION['user_supervisor'] = $Supervisor;
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
                $DepartmentID = (string) $row['organization']; // Cast to string
                $email = $row['email'];

                // Generate verification token
                $VerificationToken = bin2hex(random_bytes(16));
                $currentDate = date('Y-m-d H:i:s');

                // Check if user_id already exists in the verification_tokens table
                $checkSQL = "SELECT * FROM oauth_access_tokens WHERE user_id = ?";
                $checkStmt = $conn->prepare($checkSQL);
                $checkStmt->bind_param("i", $UserID);
                $checkStmt->execute();
                $checkResult = $checkStmt->get_result();

                if ($checkResult->num_rows > 0) {
                    // Update the existing token
                    $updateSQL = "UPDATE oauth_access_tokens SET access_token = ?, scope = ? WHERE user_id = ?";
                    $updateStmt = $conn->prepare($updateSQL);
                    $updateStmt->bind_param("ssi", $VerificationToken, $currentDate, $UserID);
                    $updateStmt->execute();
                } else {
                    // Insert a new token
                    $insertSQL = "INSERT INTO oauth_access_tokens (user_id, scope, access_token) VALUES (?, ?, ?)";
                    $insertStmt = $conn->prepare($insertSQL);
                    $insertStmt->bind_param("iss", $UserID, $currentDate, $VerificationToken);
                    $insertStmt->execute();
                }

                $leaveBalances = getLeaveBalances($conn, $UserID);

                $query2 = "SELECT t2.id, t2.name, t2.supervisor
                           FROM users t1
                           JOIN organization t2 ON t1.organization = t2.id
                           WHERE t1.id = '$UserID'";

                $result2 = mysqli_query($conn, $query2);

                if ($result2 && mysqli_num_rows($result2) > 0) {
                    $row = mysqli_fetch_assoc($result2);
                    $DepartmentID = (string) $row['id']; // Cast to string
                    $DepartmentShortName = $row['name'];
                    $Supervisor = $row['supervisor'];
                }

                $UserName = $FirstName . ' ' . $LastName;

                $_SESSION['user_id'] = $UserID;
                $_SESSION['user_role'] = $UserRole;
                $_SESSION['user_name'] = $UserName;
                $_SESSION['user_email'] = $email;
                $_SESSION['user_department'] = $DepartmentID;
                $_SESSION['user_balance'] = $leaveBalances['Balance'];
                $_SESSION['user_bank'] = $leaveBalances['Bank'];
                $_SESSION['department_short_name'] = $DepartmentShortName;
                $_SESSION['user_supervisor'] = $Supervisor;
            } else {
                $Message = "Wrong Email or Password";
            }
        } else {
            $Message = "Wrong Email or Password";
        }
    } else {
        $Message = "Invalid request: Email or password missing.";
    }

    $response = array(
        "Message" => $Message,
        "UserID" => isset($_SESSION['user_id']) ? (string) $_SESSION['user_id'] : null,
        "UserRole" => isset($_SESSION['user_role']) ? $_SESSION['user_role'] : null,
        "UserName" => isset($_SESSION['user_name']) ? $_SESSION['user_name'] : null,
        "Email" => isset($_SESSION['user_email']) ? $_SESSION['user_email'] : null,
        "DepartmentID" => isset($_SESSION['user_department']) ? (string) $_SESSION['user_department'] : null,
        "Balance" => isset($_SESSION['user_balance']) ? $_SESSION['user_balance'] : null,
        "Bank" => isset($_SESSION['user_bank']) ? $_SESSION['user_bank'] : null,
        "DepartmentShortName" => isset($_SESSION['department_short_name']) ? $_SESSION['department_short_name'] : null,
        "VerificationToken" => isset($VerificationToken) ? $VerificationToken : null,
        "Supervisor" => isset($_SESSION['user_supervisor']) ? (string) $_SESSION['user_supervisor'] : null,
    );

    echo json_encode($response);
} catch (Exception $e) {
    $response = array(
        "Message" => "Error: " . $e->getMessage(),
    );
    echo json_encode($response);
}
?>
