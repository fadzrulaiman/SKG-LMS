<?php
require_once 'db.php';  // Include the database connection file
require_once 'notification_helper.php'; // Include the notification helper file

// Include email library (PHPMailer or CodeIgniter email)
require './config/email.php'; // Path to your email config

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT');
header('Access-Control-Allow-Headers: Content-Type');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Kreait\Firebase\Factory;
use Kreait\Firebase\ServiceAccount;

require 'vendor/autoload.php'; // Make sure to load the PHPMailer and Firebase autoloaders

function getUserManagerId($userId, $conn) {
    // Prepare the query to fetch the manager ID for the given user
    $query = "SELECT manager FROM users WHERE id = ?";
    
    // Prepare the statement
    if ($stmt = $conn->prepare($query)) {
        // Bind the user ID as an integer parameter
        $stmt->bind_param("i", $userId);
        
        // Execute the query
        $stmt->execute();
        
        // Bind the result to a variable
        $stmt->bind_result($managerId);
        
        // Fetch the result
        if ($stmt->fetch()) {
            // Return the manager ID
            return $managerId;
        } else {
            // Return null if no manager is found
            return null;
        }
        
        // Close the statement
        $stmt->close();
    } else {
        // Handle any errors in preparing the statement
        return null;
    }
}

function sendJsonResponse($status, $message) {
    echo json_encode(['status' => $status, 'message' => $message]);
    exit();
}

function executeQuery($query, $conn) {
    $result = mysqli_query($conn, $query);
    if (!$result) {
        error_log("Database query failed: " . mysqli_error($conn));
        sendJsonResponse('error', 'Database query failed: ' . mysqli_error($conn));
    }
    return $result;
}

function getUserOrganization($userId, $conn) {
    $query = "SELECT organization FROM users WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['organization'];
    }
    return null;
}

function getHrEmails($conn) {
    $query = "SELECT email FROM users WHERE role = 3";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $result = $stmt->get_result();

    $emails = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $emails[] = $row['email'];
        }
    }

    return $emails;
}

function getHrFcmTokens($conn) {
    $query = "
        SELECT uft.fcm_token 
        FROM user_fcm_tokens uft 
        INNER JOIN users u ON uft.user_id = u.id 
        WHERE u.role = 3";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        // Handle the error if the statement couldn't be prepared
        return null;
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    $tokens = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $tokens[] = $row['fcm_token'];
        }
    }
    
    return !empty($tokens) ? $tokens : null;
}


function getManagerEmail($userId, $conn) {
    $query = "SELECT manager FROM users WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $managerId = $row['manager'];

        $query = "SELECT email FROM users WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $managerId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return $row['email'];
        }
    }
    return null;
}

function getManagerFcmTokens($userId, $conn) {
    $query = "
        SELECT uft.fcm_token 
        FROM user_fcm_tokens uft 
        INNER JOIN users u ON u.manager = uft.user_id
        WHERE u.id = ?";
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        // Handle the error if the statement couldn't be prepared
        return null;
    }
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $tokens = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $tokens[] = $row['fcm_token'];
        }
    }
    
    return !empty($tokens) ? $tokens : null;
}


function getUserEmailByLeaveId($leaveId, $conn) {
    $query = "SELECT u.email 
              FROM users u 
              JOIN leaves l ON u.id = l.employee 
              WHERE l.id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $leaveId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['email'];
    }
    return null;
}

function getLeaveTypeNameById($leaveTypeId, $conn) {
    $query = "SELECT name FROM types WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $leaveTypeId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['name'];
    }
    return null;
}

function getLeaveTypeByLeaveId($leaveId, $conn) {
    $query = "SELECT type FROM leaves WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $leaveId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['type'];
    }
    return null;
}

function getUserDetailsById($userId, $conn) {
    $query = "SELECT firstname, lastname FROM users WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        $userDetails = $result->fetch_assoc();
    }

    // Query to check if other users exist with the same manager
    $query2 = "SELECT EXISTS (SELECT 1 FROM users WHERE manager = ?) AS user_exists";
    $stmt2 = $conn->prepare($query2);
    $stmt2->bind_param("i", $userId);
    $stmt2->execute();
    $result2 = $stmt2->get_result();
    if ($result2 && $result2->num_rows > 0) {
        $row2 = $result2->fetch_assoc();
        $userDetails['userExists'] = $row2['user_exists'];
    }

    // Query to get the delegate ID
    $query3 = "SELECT t2.delegate_id
               FROM users t1
               JOIN delegations t2 ON t1.id = t2.delegate_id
               WHERE t1.id = ?";
    $stmt3 = $conn->prepare($query3);
    $stmt3->bind_param("i", $userId);
    $stmt3->execute();
    $result3 = $stmt3->get_result();
    if ($result3 && $result3->num_rows > 0) {
        $row3 = $result3->fetch_assoc();
        $userDetails['delegate'] = $row3['delegate_id'];
    }

    return $userDetails;
}

function getManagerDetailsById($userId, $conn) {
    $managerDetails = [];

    // Query to get the manager details
    $query = "SELECT firstname, lastname, role FROM users WHERE id = (SELECT manager FROM users WHERE id = ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        $managerDetails = $result->fetch_assoc();
    }

    // Query to check if other users exist with the same manager
    $query2 = "SELECT EXISTS (SELECT 1 FROM users WHERE manager = (SELECT manager FROM users WHERE id = ?)) AS user_exists";
    $stmt2 = $conn->prepare($query2);
    $stmt2->bind_param("i", $userId);
    $stmt2->execute();
    $result2 = $stmt2->get_result();
    if ($result2 && $result2->num_rows > 0) {
        $row2 = $result2->fetch_assoc();
        $managerDetails['userExists'] = $row2['user_exists'];
    }

    // Query to get the delegate ID
    $query3 = "SELECT t2.delegate_id
               FROM users t1
               JOIN delegations t2 ON t1.id = t2.delegate_id
               WHERE t1.id = (SELECT manager FROM users WHERE id = ?)";
    $stmt3 = $conn->prepare($query3);
    $stmt3->bind_param("i", $userId);
    $stmt3->execute();
    $result3 = $stmt3->get_result();
    if ($result3 && $result3->num_rows > 0) {
        $row3 = $result3->fetch_assoc();
        $managerDetails['delegate'] = $row3['delegate_id'];
    }

    return $managerDetails;
}

function getLeaveBalanceByType($leaveBalance, $leaveType) {
    $balanceArray = json_decode($leaveBalance, true);
    switch ($leaveType) {
        case "Annual Leave":
            return isset($balanceArray["Annual Leave"]) ? $balanceArray["Annual Leave"] . " Days" : "0 Days";
        case "Leave Bank":
            return isset($balanceArray["Leave Bank"]) ? $balanceArray["Leave Bank"] . " Days" : "0 Days";
        case "Sick Leave":
            return isset($balanceArray["Sick Leave"]) ? $balanceArray["Sick Leave"] . " Days" : "0 Days";
        default:
            return "0 Days";
    }
}

function getUserFcmTokens($userId, $conn) {
    error_log("Getting FCM tokens for user ID: " . $userId);
    $query = "SELECT fcm_token FROM user_fcm_tokens WHERE user_id = ?";
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        error_log("Failed to prepare statement for user ID: " . $userId);
        return null;
    }
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $tokens = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $tokens[] = $row['fcm_token'];
            error_log("FCM token found: " . $row['fcm_token']);
        }
    }
    
    if (!empty($tokens)) {
        return $tokens;
    } else {
        error_log("No FCM tokens found for user ID: " . $userId);
        return null;
    }
}

function getUserIdByLeaveId($leaveId, $conn) {
    error_log("Retrieving user ID for leave ID: " . $leaveId);
    $query = "SELECT employee FROM leaves WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $leaveId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        error_log("User ID found: " . $row['employee']);
        return $row['employee'];
    }
    error_log("No user ID found for leave ID: " . $leaveId);
    return null;
}

// Path to the email template files
$templatePaths = [
    'default' => '../application/views/emails/en/request.php',
    'leave_bank' => '../application/views/emails/en/bankrequest.php',
    'approve' => '../application/views/emails/en/request_accepted.php',
    'reject' => '../application/views/emails/en/request_rejected.php',
    'approve_leave_bank' => '../application/views/emails/en/manager_approved.php',
    'cancel' => '../application/views/emails/en/cancelled.php',
];

function sendEmail($recipient, $subject, $body) {
    global $config; // Use the global config array from email.php
    
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = $config['smtp_host'];
        $mail->SMTPAuth = $config['smtp_auth'];
        $mail->Username = $config['smtp_user'];
        $mail->Password = $config['smtp_pass'];
        $mail->SMTPSecure = $config['smtp_crypto'];
        $mail->Port = $config['smtp_port'];

        $mail->setFrom($config['smtp_user'], 'SKG-LMS');
        $mail->addAddress($recipient);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;

        if ($mail->send()) {
            return true;
        } else {
            return $mail->ErrorInfo;
        }
    } catch (Exception $e) {
        return $mail->ErrorInfo;
    }
}

function buildEmailBody($templateFilePath, $data) {
    if (file_exists($templateFilePath)) {
        $template = file_get_contents($templateFilePath);
        foreach ($data as $key => $value) {
            $template = str_replace('{' . $key . '}', htmlspecialchars((string)$value), $template);
        }
        return $template;
    } else {
        error_log("Email template file not found: " . $templateFilePath);
        return null;
    }
}

// Add watermark to image
function addWatermark($filePath) {
    $imageType = exif_imagetype($filePath);
    switch ($imageType) {
        case IMAGETYPE_JPEG:
            $image = imagecreatefromjpeg($filePath);
            break;
        case IMAGETYPE_PNG:
            $image = imagecreatefrompng($filePath);
            break;
        case IMAGETYPE_GIF:
            $image = imagecreatefromgif($filePath);
            break;
        default:
            error_log('Unsupported image type for watermark: ' . $filePath);
            return; // Unsupported image type
    }

    $watermark = imagecreatefrompng('../assets/uploads/watermark.png'); // Path to your watermark image
    $imageWidth = imagesx($image);
    $imageHeight = imagesy($image);
    $watermarkWidth = imagesx($watermark);
    $watermarkHeight = imagesy($watermark);

    $tempWatermark = imagecreatetruecolor($watermarkWidth, $watermarkHeight);
    imagealphablending($tempWatermark, false);
    imagesavealpha($tempWatermark, true);
    $transparent = imagecolorallocatealpha($tempWatermark, 0, 0, 0, 127);
    imagefilledrectangle($tempWatermark, 0, 0, $watermarkWidth, $watermarkHeight, $transparent);

    imagecopy($tempWatermark, $watermark, 0, 0, 0, 0, $watermarkWidth, $watermarkHeight);
    imagefilter($tempWatermark, IMG_FILTER_COLORIZE, 0, 0, 0, 63); // 50% transparency

    for ($y = 0; $y < $imageHeight; $y += $watermarkHeight) {
        for ($x = 0; $x < $imageWidth; $x += $watermarkWidth) {
            imagecopy($image, $tempWatermark, $x, $y, 0, 0, $watermarkWidth, $watermarkHeight);
        }
    }

    switch ($imageType) {
        case IMAGETYPE_JPEG:
            imagejpeg($image, $filePath);
            break;
        case IMAGETYPE_PNG:
            imagepng($image, $filePath);
            break;
        case IMAGETYPE_GIF:
            imagegif($image, $filePath);
            break;
    }

    imagedestroy($image);
    imagedestroy($watermark);
    imagedestroy($tempWatermark);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') { //get leave data
    if (isset($_GET['user_id']) && isset($_GET['type'])) {
        $userId = intval($_GET['user_id']);
        $type = $_GET['type'];

        if ($type === 'individual') {
            // Get all leave for a specific user
            $query = "SELECT l.*, s.name AS status_name, t.name AS type_name, u.firstname, u.lastname, u.fcm_token
                      FROM leaves l
                      JOIN status s ON l.status = s.id
                      JOIN types t ON l.type = t.id
                      JOIN users u ON l.employee = u.id
                      WHERE l.employee = $userId";
            $result = mysqli_query($conn, $query);
            $leave = array();

            while ($row = mysqli_fetch_assoc($result)) {
                $row['duration'] = round($row['duration']);  // Round to whole number

                $leave[] = $row;
            }

            echo json_encode($leave);
        } elseif ($type === 'individualfilter') {
            // Get the current date
            $currentDate = date('Y-m-d');
            
            // Get all leave for a specific user with a start date on or after the current date
            // or the current date is within the range of startdate and enddate
            $query = "SELECT l.*, s.name AS status_name, t.name AS type_name, u.firstname, u.lastname, u.fcm_token
                      FROM leaves l
                      JOIN status s ON l.status = s.id
                      JOIN types t ON l.type = t.id
                      JOIN users u ON l.employee = u.id
                      WHERE l.employee = $userId 
                      AND ('$currentDate' BETWEEN l.startdate AND l.enddate OR l.startdate >= '$currentDate' OR l.status = 2 OR l.status = 7)";
        
            $result = mysqli_query($conn, $query);
            $leave = array();
        
            while ($row = mysqli_fetch_assoc($result)) {
                $row['duration'] = round($row['duration']);  // Round to whole number
                $leave[] = $row;
            }
        
            echo json_encode($leave);
        }
         elseif ($type === 'organization') {
            // Get all leave for users in the same organization
            $organizationId = getUserOrganization($userId, $conn);
            if ($organizationId !== null) {
                $query = "SELECT l.*, u.firstname, u.lastname, s.name AS status_name, t.name AS type_name, u.fcm_token
                          FROM leaves l
                          JOIN users u ON l.employee = u.id
                          JOIN status s ON l.status = s.id
                          JOIN types t ON l.type = t.id
                          WHERE u.organization = $organizationId AND l.status = 3";
                $result = mysqli_query($conn, $query);
                $leave = array();

                while ($row = mysqli_fetch_assoc($result)) {
                    $row['duration'] = round($row['duration']);  // Round to whole number

                    $leave[] = $row;
                }

                echo json_encode($leave);
            } else {
                echo json_encode(array("error" => "Organization not found for user."));
            }
        } elseif ($type === 'team') {
            // Get the manager ID for the current user
            $userManagerId = getUserManagerId($userId, $conn);
            
            if ($userManagerId !== null) {
                $query = "SELECT l.*, u.firstname, u.lastname, s.name AS status_name, t.name AS type_name, u.fcm_token
                          FROM leaves l
                          JOIN users u ON l.employee = u.id
                          JOIN status s ON l.status = s.id
                          JOIN types t ON l.type = t.id
                          WHERE u.manager = $userManagerId AND l.status = 3";
                $result = mysqli_query($conn, $query);
                $leave = array();
        
                while ($row = mysqli_fetch_assoc($result)) {
                    $row['duration'] = round($row['duration']);  // Round to whole number
        
                    $leave[] = $row;
                }
        
                echo json_encode($leave);
            } else {
                echo json_encode(array("error" => "Manager not found for user."));
            }
        }
        elseif ($type === 'manager') {
            // Get all leave for users managed by the current user
            $query =   "SELECT l.*, u.firstname, u.lastname, s.name AS status_name, t.name AS type_name, u.fcm_token
                        FROM leaves l
                        JOIN users u ON l.employee = u.id
                        JOIN status s ON l.status = s.id
                        JOIN types t ON l.type = t.id
                        LEFT JOIN delegations d ON u.manager = d.manager_id AND d.delegate_id = $userId
                        WHERE (u.manager = $userId OR d.delegate_id = $userId) AND l.status = 2";// Assuming status 2 is 'Pending'
            $result = mysqli_query($conn, $query);
            $leave = array();

            while ($row = mysqli_fetch_assoc($result)) {
                $row['duration'] = round($row['duration']);  // Round to whole number

                $leave[] = $row;
            }

            echo json_encode($leave);
        } 
        elseif ($type === 'subordinate') {
            // Get all leave for users managed by the current user
            $query =   "SELECT l.*, u.firstname, u.lastname, s.name AS status_name, t.name AS type_name, u.fcm_token
                        FROM leaves l
                        JOIN users u ON l.employee = u.id
                        JOIN status s ON l.status = s.id
                        JOIN types t ON l.type = t.id
                        LEFT JOIN delegations d ON u.manager = d.manager_id AND d.delegate_id = $userId
                        WHERE (u.manager = $userId OR d.delegate_id = $userId) ";// Assuming status 2 is 'Pending'
            $result = mysqli_query($conn, $query);
            $leave = array();

            while ($row = mysqli_fetch_assoc($result)) {
                $row['duration'] = round($row['duration']);  // Round to whole number

                $leave[] = $row;
            }

            echo json_encode($leave);
        }else {
            echo json_encode(array("error" => "Invalid type provided."));
        }
    } elseif (isset($_GET['leave_id'])) {
        $leaveId = intval($_GET['leave_id']);
    
        // Get detailed information for a specific leave
        $query = "SELECT l.*, s.name AS status_name, t.name AS type_name, u.firstname, u.lastname, u.email, u.id, l.attachment, l.id AS leave_id, l.status
                  FROM leaves l
                  JOIN status s ON l.status = s.id
                  JOIN types t ON l.type = t.id
                  JOIN users u ON l.employee = u.id
                  WHERE l.id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $leaveId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            $leaveDetails = $result->fetch_assoc();
            echo json_encode($leaveDetails);
        } else {
            echo json_encode(array("error" => "Leave not found."));
        }
    }
     elseif (isset($_GET['role']) && isset($_GET['type'])) {
        
        $role = intval($_GET['role']);
        $type = $_GET['type'];

        if ($type === 'hr' && $role === 3) {
            // Get all leave for users managed by the current user
            $query = "SELECT l.*, u.firstname, s.name AS status_name, t.name AS type_name, u.fcm_token
                      FROM leaves l
                      JOIN users u ON l.employee = u.id
                      JOIN status s ON l.status = s.id
                      JOIN types t ON l.type = t.id
                      WHERE l.status = 7"; // Assuming status 2 is 'Pending'
            $result = mysqli_query($conn, $query);
            $leave = array();

            while ($row = mysqli_fetch_assoc($result)) {
                $row['duration'] = round($row['duration']);  // Round to whole number

                $leave[] = $row;
            }

            echo json_encode($leave);
        } else {
            echo json_encode(array("error" => "Invalid type provided."));
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'create') { //Apply leave
    $userId = $_POST['user_id'];
    $leaveType = $_POST['leave_Type'];
    $leaveDesc = $_POST['leave_desc'];
    $startDate = $_POST['start_date'];
    $endDate = $_POST['end_date'];
    $leaveDuration = $_POST['leave_duration'];
    $leaveStatus = $_POST['leave_status'];
    $leaveBalance = isset($_POST['leave_balance']) ? $_POST['leave_balance'] : null;
    $imagePath = '';

    // Handle file upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../assets/uploads/'; // Folder outside the API folder
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $fullImagePath = $uploadDir . basename($_FILES['image']['name']);
        if (!move_uploaded_file($_FILES['image']['tmp_name'], $fullImagePath)) {
            error_log('Error: Failed to upload image');
            sendJsonResponse('error', 'Failed to upload image');
        }
        // Add watermark to the uploaded image
        addWatermark($fullImagePath);
        $imagePath = 'assets/uploads/' . basename($_FILES['image']['name']); // Store the full path in the database
    }

    $startDateType = 'morning'; // Set start date type
    $endDateType = 'afternoon'; // Set end date type

    $query = "INSERT INTO leaves (employee, type, cause, startdate, enddate, duration, status, attachment, startdatetype, enddatetype) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        error_log("Prepare statement failed: " . $conn->error);
        sendJsonResponse('error', 'Failed to prepare statement: ' . $conn->error);
    }
    $stmt->bind_param("iisssissss", $userId, $leaveType, $leaveDesc, $startDate, $endDate, $leaveDuration, $leaveStatus, $imagePath, $startDateType, $endDateType);

    if ($stmt->execute()) {
        //echo "Success";
        $leaveId = $stmt->insert_id;

        // Log the created leave in leave_history table
        $queryHistory = "INSERT INTO leaves_history (id, startdate, enddate, status, employee, cause, attachment, startdatetype, enddatetype, duration, type, comments, document, changed_by, change_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmtHistory = $conn->prepare($queryHistory);
        if (!$stmtHistory) {
            error_log("Prepare statement failed: " . $conn->error);
            sendJsonResponse('error', 'Failed to prepare statement: ' . $conn->error);
        }
        $comments = null; // Define or retrieve comments
        $document = null; // Define or retrieve the document
        $changedBy = $userId; // Or use another identifier for the user who made the change
        $changeType = 1; // Set change type to 1

        $stmtHistory->bind_param("isssisssssissii", $leaveId, $startDate, $endDate, $leaveStatus, $userId, $leaveDesc, $imagePath, $startDateType, $endDateType, $leaveDuration, $leaveType, $comments, $document, $changedBy, $changeType);

        if (!$stmtHistory->execute()) {
            error_log("Failed to log leave history: " . $stmtHistory->error);
        }

        $managerEmail = getManagerEmail($userId, $conn);
        if ($managerEmail) {
            $userDetails = getUserDetailsById($userId, $conn);
            $specificLeaveBalance = getLeaveBalanceByType($leaveBalance, getLeaveTypeNameById($leaveType, $conn));

            // Extract numeric value from the balance string
            $numericBalance = intval($specificLeaveBalance);

            // Calculate the new balance
            $newBalance = $numericBalance - intval($leaveDuration);

            // Determine the template path based on the leave type
            $templatePath = ($leaveType == '3') ? $templatePaths['leave_bank'] : $templatePaths['default'];

            // Prepare email data
            $emailData = [
                'Title' => 'Leave Request',
                'Firstname' => $userDetails['firstname'],
                'Lastname' => $userDetails['lastname'],
                'BaseUrl' => 'https://lms.sawitkinabalu.com.my/SKG-LMS/home',
                'LeaveId' => $leaveId,
                'StartDate' => $startDate,
                'EndDate' => $endDate,
                'Type' => getLeaveTypeNameById($leaveType, $conn),
                'Duration' => $leaveDuration,
                'Balance' => $numericBalance,
                'Reason' => $leaveDesc,
                'Comments' => '',
                'Status' => 'Requested',
                'Email Title' => '[SKG-LMS] Leave Request from ' . $userDetails['firstname'] . ' ' . $userDetails['lastname'],
                'Notify' => "[ Leave ID: $leaveId ] You have a $leaveType request from {$userDetails['firstname']} {$userDetails['lastname']}, from $startDate until $endDate.",
            ];

            $emailBody = buildEmailBody($templatePath, $emailData);
            if ($emailBody) {
                $emailSent = sendEmail($managerEmail, $emailData['Email Title'], $emailBody);

                // Send push notification to the manager
                $managerFcmTokens = getManagerFcmTokens($userId, $conn);
                if ($managerFcmTokens) {
                    $data = [
                        'screen' => 'Leave Approval',
                        'leaveId' => $emailData['LeaveId']
                    ];
                    
                    foreach ($managerFcmTokens as $managerFcmToken) {
                        $pushSent = sendPushNotification($managerFcmToken,  $emailData['Title'], $emailData['Notify'], $data);
                        if (!$pushSent) {
                            error_log("Failed to send push notification to manager with token: $managerFcmToken");
                        } else {
                            error_log("Push notification sent to manager with token: $managerFcmToken");
                        }
                    }
                } else {
                    error_log("No FCM token found for manager");
                }


                if ($emailSent === true) {
                    sendJsonResponse('success', 'Leave submitted and email sent.');
                } else {
                    error_log("Leave submitted but email failed: " . $emailSent);
                    sendJsonResponse('error', 'Leave submitted but email failed: ' . $emailSent);
                }
            } else {
                sendJsonResponse('error', 'Failed to read email template.');
            }
        } else {
            sendJsonResponse('success', 'Leave submitted but no manager email found.');
        }
    } else {
        error_log("Failed to submit leave: " . $stmt->error);
        sendJsonResponse('error', 'Failed to submit leave: ' . $stmt->error);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'PUT' && isset($_GET['action']) && ($_GET['action'] === 'approve' || $_GET['action'] === 'reject')) { //Approve or Reject leave
    $encodedData = file_get_contents('php://input');
    if (!empty($encodedData)) {
        $decodedData = json_decode($encodedData, true);
        if (isset($decodedData['leave_id'])) {
            $leaveId = intval($decodedData['leave_id']);
            $comments = isset($decodedData['comments']) ? trim($decodedData['comments']) : '';  // Handle comments as a string
            $leaveType = getLeaveTypeByLeaveId($leaveId, $conn);
            if (!$leaveType) {
                echo "Error: Invalid leave ID.";
                exit();
            }
            $statusValue = ($_GET['action'] === 'approve') ? ($leaveType == 3 ? 7 : 3) : 4;
            $query = "UPDATE leaves SET status = ?, comments = ? WHERE id = ?";
            $stmt = $conn->prepare($query);

            if ($stmt) {
                $stmt->bind_param("ssi", $statusValue, $comments, $leaveId);
                $stmt->execute();
                if ($stmt->affected_rows > 0) {
                    echo "Success";
                    $userEmail = getUserEmailByLeaveId($leaveId, $conn);
                    $userId = getUserIdByLeaveId($leaveId, $conn);
                    $userDetails = getUserDetailsById($userId, $conn);

                    // Get leave details
                    $leaveDetailsQuery = "SELECT startdate, enddate, type, cause, status, attachment, duration, startdatetype, enddatetype, comments FROM leaves WHERE id = ?";
                    $leaveDetailsStmt = $conn->prepare($leaveDetailsQuery);
                    $leaveDetailsStmt->bind_param("i", $leaveId);
                    $leaveDetailsStmt->execute();
                    $leaveDetailsResult = $leaveDetailsStmt->get_result();
                    $leaveDetails = $leaveDetailsResult->fetch_assoc();

                    if ($userEmail && $leaveDetails) {
                        // Determine the template path based on the leave type and action
                        if ($leaveType == '3' && $_GET['action'] === 'approve') {  //Approve leave bank

                            //Send Email and notification to Hr when manager approve leave bank
                            $HrEmails = getHrEmails($conn);
                            if ($HrEmails) {
                                $userDetails = getUserDetailsById($userId, $conn);
                                
                                // Determine the template path based on the leave type
                                $templatePath = $templatePaths['leave_bank'];

                                // Prepare email data
                                $emailData = [
                                    'Title' => 'Leave Request',
                                    'Firstname' => $userDetails['firstname'],
                                    'Lastname' => $userDetails['lastname'],
                                    'BaseUrl' => 'https://lms.sawitkinabalu.com.my/SKG-LMS/home',
                                    'LeaveId' => $leaveId,
                                    'StartDate' => $leaveDetails['startdate'],
                                    'EndDate' => $leaveDetails['enddate'],
                                    'Type' => getLeaveTypeNameById($leaveDetails['type'], $conn),
                                    'Duration' => $leaveDetails['duration'],
                                    'Balance' => '',   // You can calculate and fill this if needed
                                    'Reason' => $leaveDetails['cause'],
                                    'Comments' => $leaveDetails['comments'],
                                    'Status' => 'Waiting HR Approval',
                                    'Email Title' => '[SKG-LMS] Leave Bank Request from ' . $userDetails['firstname'] . ' ' . $userDetails['lastname'],
                                    'Notify' => "[ Leave ID: $leaveId ] You have a Leave Bank request from {$userDetails['firstname']} {$userDetails['lastname']}, from {$leaveDetails['startdate']} until {$leaveDetails['enddate']}.",
                                ];

                                $emailBody = buildEmailBody($templatePath, $emailData);
                                if ($emailBody) {
                                    $emailSent = true;
                                    foreach ($HrEmails as $HrEmail) {
                                        $emailSent = $emailSent && sendEmail($HrEmail, $emailData['Email Title'], $emailBody);
                                    }

                                    // Send push notification to the manager
                                    $HrFcmTokens = getHrFcmTokens($conn);
                                    if ($HrFcmTokens) {
                                        $data = [
                                            'screen' => 'Leave Approval',
                                            'leaveId' => $emailData['LeaveId'],
                                            'leaveStatus' => $statusValue,
                                        ];

                                        foreach ($HrFcmTokens as $HrFcmToken) {
                                            $pushSent = sendPushNotification($HrFcmToken,  $emailData['Title'], $emailData['Notify'], $data);
                                            if (!$pushSent) {
                                                error_log("Failed to send push notification to HR with token: $HrFcmToken");
                                            } else {
                                                error_log("Push notification sent to HR with token: $HrFcmToken");
                                            }
                                        }
                                    } else {
                                        error_log("No FCM token found for manager");
                                    }

                                    // Parameter to send email and notification to user
                                    $templatePath = $templatePaths['approve_leave_bank'];
                                    $fcmtitle = 'Leave Request, [SKG-LMS] Waiting for HR approval';

                                    if ($emailSent === true) {
                                        //echo "Success";
                                    } else {
                                        error_log("Leave submitted but email failed");
                                        sendJsonResponse('error', 'Leave submitted but email failed');
                                    }
                                } else {
                                    sendJsonResponse('error', 'Failed to read email template.');
                                }
                            }
                            else {
                                sendJsonResponse('success', 'Leave submitted but no manager email found.');
                            }

                        } elseif ($_GET['action'] === 'approve') { //Approve annual or sick leave
                            $templatePath = $templatePaths['approve'];
                            $fcmtitle = 'Leave Request, [SKG-LMS] Your leave request has been approved';
                        } else {  //Reject annual or sick leave
                            $templatePath = $templatePaths['reject'];
                            $fcmtitle = 'Leave Request, [SKG-LMS] Your leave request has been rejected';
                        }

                        // Prepare email data
                        $emailData = [
                            'Title' => 'Leave Request',
                            'Firstname' => $userDetails['firstname'],
                            'Lastname' => $userDetails['lastname'],
                            'BaseUrl' => 'https://lms.sawitkinabalu.com.my/SKG-LMS/home',
                            'LeaveId' => $leaveId,
                            'StartDate' => $leaveDetails['startdate'],
                            'EndDate' => $leaveDetails['enddate'],
                            'Type' => getLeaveTypeNameById($leaveDetails['type'], $conn),
                            'Duration' => $leaveDetails['duration'],
                            'Balance' => '',   // You can calculate and fill this if needed
                            'Cause' => $leaveDetails['cause'],
                            'Comments' => $leaveDetails['comments'],
                            'Status' => 'Waiting HR Approval',
                            'Email Title' => ($_GET['action'] === 'reject') ? '[SKG-LMS] Your leave request has been rejected' : '[SKG-LMS] Your leave request has been approved',
                            'Notify' => "[ Leave ID: $leaveId ] Your {$leaveType} request on {$leaveDetails['startdate']} until {$leaveDetails['enddate']} is {$_GET['action']}",
                        ];

                        $emailBody = buildEmailBody($templatePath, $emailData);
                        if ($emailBody) {
                            $emailSent = sendEmail($userEmail, $emailData['Email Title'], $emailBody);

                            // Send push notification to the user
                            $userFcmTokens = getUserFcmTokens($userId, $conn);
                            if ($userFcmTokens) {
                                $data = [
                                    'screen' => 'Leave History',
                                    'leaveId' => $emailData['LeaveId'],
                                    'leaveStatus' => $statusValue,
                                ];

                                foreach ($userFcmTokens as $userFcmToken) {
                                    $pushSent = sendPushNotification($userFcmToken, $emailData['Title'], $emailData['Notify'], $data);
                                    if (!$pushSent) {
                                        error_log("Failed to send push notification to user with token: $userFcmToken");
                                    } else {
                                        error_log("Push notification sent to user with token: $userFcmToken");
                                    }
                                }
                            } else {
                                error_log("No FCM tokens found for user");
                            }

                            // Log the action in leave_history table
                            $queryHistory = "INSERT INTO leaves_history (id, startdate, enddate, status, employee, cause, attachment, startdatetype, enddatetype, duration, type, comments, document, changed_by, change_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                            $stmtHistory = $conn->prepare($queryHistory);
                            if ($stmtHistory) {
                                $currentDate = date('Y-m-d'); // Current date
                                $existingComments = json_decode($leaveDetails['comments'], true);
                                if (!is_array($existingComments) || !isset($existingComments['comments'])) {
                                    $existingComments = ['comments' => []];
                                }
                                $newComment = [
                                    'type' => 'change',
                                    'status_number' => $statusValue,
                                    'date' => $currentDate,
                                    'comment' => $comments  // Include the new comment
                                ];
                                $existingComments['comments'][] = $newComment;
                                $comments = json_encode($existingComments);
                                $document = null; // Define or retrieve the document if necessary
                                $changedBy = $userId; // Or use another identifier for the user who made the change
                                $changeType = 1; // Set change type to 1 for approved/rejected actions

                                $stmtHistory->bind_param("isssisssssissii", $leaveId, $leaveDetails['startdate'], $leaveDetails['enddate'], $statusValue, $userId, $leaveDetails['cause'], $leaveDetails['attachment'], $leaveDetails['startdatetype'], $leaveDetails['enddatetype'], $leaveDetails['duration'], $leaveDetails['type'], $comments, $document, $changedBy, $changeType);
                                $stmtHistory->execute();
                                $stmtHistory->close();
                            } else {
                                error_log("Failed to prepare leave history statement: " . $conn->error);
                            }

                            if ($emailSent === true) {
                                //echo "Success";
                            } else {
                                error_log ("Leave status updated but email failed: " . $emailSent);
                                sendJsonResponse('error', 'Leave submitted but email failed');
                            }
                        } else {
                            echo "Leave status updated but failed to read email template.";
                        }
                    } else {
                        echo "Success but no user email found or leave details missing.";
                    }
                } else {
                    echo "Error: Failed to update leave status 1.";
                }
                $stmt->close();
            } else {
                echo "Error: Failed to prepare statement.";
            }
        } else {
            echo "Error: Leave ID is missing.";
        }
    } else {
        echo "Error: Empty request body.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'PUT' && isset($_GET['action']) && ($_GET['action'] === 'approveBank' || $_GET['action'] === 'rejectBank')) { //Hr Approve or Reject leave bank
    $encodedData = file_get_contents('php://input');
    if (!empty($encodedData)) {
        $decodedData = json_decode($encodedData, true);
        if (isset($decodedData['leave_id'])) {
            $leaveId = intval($decodedData['leave_id']);
            $comments = intval($decodedData['comments']);
            $leaveType = getLeaveTypeByLeaveId($leaveId, $conn);
            if (!$leaveType) {
                echo "Error: Invalid leave ID.";
                exit();
            }
            $statusValue = ($_GET['action'] === 'approveBank') ? 3 : 4;
            $query = "UPDATE leaves SET status = ? WHERE id = ?";
            $stmt = $conn->prepare($query);

            if ($stmt) {
                $stmt->bind_param("si", $statusValue, $leaveId);
                $stmt->execute();
                if ($stmt->affected_rows > 0) {
                    echo "Success";
                    $userEmail = getUserEmailByLeaveId($leaveId, $conn);
                    $userId = getUserIdByLeaveId($leaveId, $conn);
                    $userDetails = getUserDetailsById($userId, $conn);

                    // Get leave details
                    $leaveDetailsQuery = "SELECT startdate, enddate, type, cause, status, attachment, duration, startdatetype, enddatetype, comments FROM leaves WHERE id = ?";
                    $leaveDetailsStmt = $conn->prepare($leaveDetailsQuery);
                    $leaveDetailsStmt->bind_param("i", $leaveId);
                    $leaveDetailsStmt->execute();
                    $leaveDetailsResult = $leaveDetailsStmt->get_result();
                    $leaveDetails = $leaveDetailsResult->fetch_assoc();

                    if ($userEmail && $leaveDetails) {
                        // Determine the template path based on the leave type and action
                        if ($_GET['action'] === 'approveBank') {
                            $templatePath = $templatePaths['approve'];
                        } else {
                            $templatePath = $templatePaths['reject'];
                        }

                        // Prepare email data
                        $emailData = [
                            'Title' => 'Leave Approved',
                            'Firstname' => $userDetails['firstname'],
                            'Lastname' => $userDetails['lastname'],
                            'BaseUrl' => 'https://lms.sawitkinabalu.com.my/SKG-LMS/home',
                            'LeaveId' => $leaveId,
                            'StartDate' => $leaveDetails['startdate'],
                            'EndDate' => $leaveDetails['enddate'],
                            'Type' => getLeaveTypeNameById($leaveDetails['type'], $conn),
                            'Duration' => $leaveDetails['duration'],  // You can calculate and fill this if needed
                            'Balance' => '',   // You can calculate and fill this if needed
                            'Cause' => $leaveDetails['cause'],
                            'Comments' => $leaveDetails['comments'],
                            'Email Title' => ($_GET['action'] === 'rejectBank') ? '[SKG-LMS] Your leave bank request has been rejected' : '[SKG-LMS] Your leave bank request has been approved',
                            'Notify' => ($_GET['action'] === 'rejectBank') ? "[ Leave ID: $leaveId ] Your Leave Bank request on {$leaveDetails['startdate']} until {$leaveDetails['enddate']} is Approved" : "[ Leave ID: $leaveId ] Your Leave Bank request on {$leaveDetails['startdate']} until {$leaveDetails['enddate']} is Rejected",
                        ];

                        $emailBody = buildEmailBody($templatePath, $emailData);
                        if ($emailBody) {
                            $emailSent = sendEmail($userEmail,  $emailData['Title'], $emailData['Email Title'], $emailBody);

                            // Send push notification to the user
                            $userFcmTokens = getUserFcmTokens($userId, $conn);
                            if ($userFcmTokens) {
                                $data = [
                                    'screen' => 'Leave History',
                                    'leaveId' => $emailData['LeaveId'],
                                    'leaveStatus' => $statusValue,
                                ];

                                foreach ($userFcmTokens as $userFcmToken) {
                                    $pushSent = sendPushNotification($userFcmToken,  $emailData['Title'], $emailData['Notify'], $data);
                                    if (!$pushSent) {
                                        error_log("Failed to send push notification to user with token: $userFcmToken");
                                    } else {
                                        error_log("Push notification sent to user with token: $userFcmToken");
                                    }
                                }
                            } else {
                                error_log("No FCM tokens found for user");
                            }

                            // Log the action in leave_history table
                            $queryHistory = "INSERT INTO leaves_history (id, startdate, enddate, status, employee, cause, attachment, startdatetype, enddatetype, duration, type, comments, document, changed_by, change_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                            $stmtHistory = $conn->prepare($queryHistory);
                            if ($stmtHistory) {
                                $currentDate = date('Y-m-d'); // Current date
                                $existingComments = json_decode($leaveDetails['comments'], true);
                                if (!is_array($existingComments) || !isset($existingComments['comments'])) {
                                    $existingComments = ['comments' => []];
                                }
                                $newComment = [
                                    'type' => 'change',
                                    'status_number' => $statusValue,
                                    'date' => $currentDate
                                ];
                                $existingComments['comments'][] = $newComment;
                                $comments = json_encode($existingComments);
                                $document = null; // Define or retrieve the document if necessary
                                $changedBy = $userId; // Or use another identifier for the user who made the change
                                $changeType = 1; // Set change type to 1 for approved/rejected actions

                                $stmtHistory->bind_param("isssisssssissii", $leaveId, $leaveDetails['startdate'], $leaveDetails['enddate'], $statusValue, $userId, $leaveDetails['cause'], $leaveDetails['attachment'], $leaveDetails['startdatetype'], $leaveDetails['enddatetype'], $leaveDetails['duration'], $leaveDetails['type'], $comments, $document, $changedBy, $changeType);
                                $stmtHistory->execute();
                                $stmtHistory->close();
                            } else {
                                error_log("Failed to prepare leave history statement: " . $conn->error);
                            }

                            if ($emailSent === true) {
                                //echo "Success";
                            } else {
                                error_log ("Leave status updated but email failed: " . $emailSent);
                                sendJsonResponse('error', 'Leave submitted but email failed');
                            }
                        } else {
                            echo "Leave status updated but failed to read email template.";
                        }
                    } else {
                        echo "Success but no user email found or leave details missing.";
                    }
                } else {
                    echo "Error: Failed to update leave status.";
                }
                $stmt->close();
            } else {
                echo "Error: Failed to prepare statement.";
            }
        } else {
            echo "Error: Leave ID is missing.";
        }
    } else {
        echo "Error: Empty request body.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'PUT' && isset($_GET['action']) && $_GET['action'] === 'cancel') { //cancel leave
    $encodedData = file_get_contents('php://input');
    if (!empty($encodedData)) {
        $decodedData = json_decode($encodedData, true);
        if (isset($decodedData['leave_id']) && isset($decodedData['leave_balance'])) {
            $leaveId = intval($decodedData['leave_id']);
            $leaveBalance = $decodedData['leave_balance'];
            $leaveType = getLeaveTypeByLeaveId($leaveId, $conn);
            if (!$leaveType) {
                sendJsonResponse('error', 'Invalid leave ID.');
                exit();
            }
            $statusValue = 6; // Status 6 means the leave is cancelled
            $query = "UPDATE leaves SET status = ? WHERE id = ?";
            $stmt = $conn->prepare($query);

            if ($stmt) {
                $stmt->bind_param("si", $statusValue, $leaveId);
                $stmt->execute();
                if ($stmt->affected_rows > 0) {
                    //echo "Success";
                    $userEmail = getUserEmailByLeaveId($leaveId, $conn);
                    $userId = getUserIdByLeaveId($leaveId, $conn);
                    $userDetails = getUserDetailsById($userId, $conn);
                    $managerEmail = getManagerEmail($userId, $conn);

                    // Get leave details
                    $leaveDetailsQuery = "SELECT startdate, enddate, type, cause, status, duration, attachment, startdatetype, enddatetype, comments FROM leaves WHERE id = ?";
                    $leaveDetailsStmt = $conn->prepare($leaveDetailsQuery);
                    $leaveDetailsStmt->bind_param("i", $leaveId);
                    $leaveDetailsStmt->execute();
                    $leaveDetailsResult = $leaveDetailsStmt->get_result();
                    $leaveDetails = $leaveDetailsResult->fetch_assoc();

                    if ($managerEmail && $leaveDetails) {
                        // Prepare email data
                        $specificLeaveBalance = getLeaveBalanceByType($leaveBalance, getLeaveTypeNameById($leaveType, $conn));

                        // Extract numeric value from the balance string
                        $numericBalance = intval($specificLeaveBalance);

                        // Calculate the new balance
                        $newBalance = $numericBalance + intval($leaveDetails['duration']);

                        $emailData = [
                            'Title' => 'Leave Cancelled',
                            'Firstname' => $userDetails['firstname'],
                            'Lastname' => $userDetails['lastname'],
                            'BaseUrl' => 'https://lms.sawitkinabalu.com.my/SKG-LMS/home',
                            'LeaveId' => $leaveId,
                            'StartDate' => $leaveDetails['startdate'],
                            'EndDate' => $leaveDetails['enddate'],
                            'Type' => getLeaveTypeNameById($leaveDetails['type'], $conn),
                            'Duration' => $leaveDetails['duration'],
                            'Balance' => $numericBalance,
                            'Reason' => $leaveDetails['cause'],
                            'Comments' => 'The leave request has been cancelled by the user.',
                            'Status' => 'Cancelled'
                        ];

                        $templatePath = $templatePaths['cancel']; // Assuming 'cancel' is the key for the cancel template
                        $emailBody = buildEmailBody($templatePath, $emailData);
                        if ($emailBody) {
                            $emailSent = sendEmail($managerEmail, 'Leave Cancelled', $emailBody);

                            // Log the action in leave_history table
                            $queryHistory = "INSERT INTO leaves_history (id, startdate, enddate, status, employee, cause, attachment, startdatetype, enddatetype, duration, type, comments, document, changed_by, change_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                            $stmtHistory = $conn->prepare($queryHistory);
                            if ($stmtHistory) {
                                $currentDate = date('Y-m-d'); // Current date
                                $existingComments = json_decode($leaveDetails['comments'], true);
                                if (!is_array($existingComments) || !isset($existingComments['comments'])) {
                                    $existingComments = ['comments' => []];
                                }
                                $newComment = [
                                    'type' => 'change',
                                    'status_number' => $statusValue,
                                    'date' => $currentDate
                                ];
                                $existingComments['comments'][] = $newComment;
                                $comments = json_encode($existingComments);
                                $document = null; // Define or retrieve the document if necessary
                                $changedBy = $userId; // Or use another identifier for the user who made the change
                                $changeType = 1; // Set change type to 1 for cancel actions

                                $stmtHistory->bind_param("isssisssssissii", $leaveId, $leaveDetails['startdate'], $leaveDetails['enddate'], $statusValue, $userId, $leaveDetails['cause'], $leaveDetails['attachment'], $leaveDetails['startdatetype'], $leaveDetails['enddatetype'], $leaveDetails['duration'], $leaveDetails['type'], $comments, $document, $changedBy, $changeType);
                                $stmtHistory->execute();
                                $stmtHistory->close();
                            } else {
                                error_log("Failed to prepare leave history statement: " . $conn->error);
                            }

                            if ($emailSent === true) {
                                sendJsonResponse('success', 'Leave cancelled and email sent.');
                            } else {
                                sendJsonResponse('error', 'Leave cancelled but email failed: ' . $emailSent);
                            }
                        } else {
                            sendJsonResponse('error', 'Failed to read email template.');
                        }
                    } else {
                        sendJsonResponse('success', 'Leave cancelled but no manager email found.');
                    }
                } else {
                    sendJsonResponse('error', 'Failed to cancel leave.');
                }
                $stmt->close();
            } else {
                sendJsonResponse('error', 'Failed to prepare statement.');
            }
        } else {
            sendJsonResponse('error', 'Leave ID or leave balance is missing.');
        }
    } else {
        sendJsonResponse('error', 'Empty request body.');
    }
}

?>
