<?php
require_once 'db.php';  // Include the database connection file
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT');
header('Access-Control-Allow-Headers: Content-Type');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Kreait\Firebase\Factory;
use Kreait\Firebase\ServiceAccount;

require 'vendor/autoload.php'; // Make sure to load the PHPMailer and Firebase autoloaders

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

function getManagerFcmToken($userId, $conn) {
    $query = "SELECT fcm_token FROM users WHERE id = (SELECT manager FROM users WHERE id = ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['fcm_token'];
    }
    return null;
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
        return $result->fetch_assoc();
    }
    return null;
}

function sendEmail($recipient, $subject, $body) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'sawitlms@gmail.com';
        $mail->Password   = 'xxpzfvvljgesmorr';
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        $mail->setFrom('sawitlms@gmail.com', 'Leave Management System');
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
/*
function sendEmail($recipient, $subject, $body) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'sandbox.smtp.mailtrap.io';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'dfa7082498f4da';
        $mail->Password   = '2f235b6942faf3';
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        $mail->setFrom('sawitlms@mail.com', 'Leave Management System');
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
*/
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

function sendPushNotification($fcmToken, $title, $body, $data) {
    $factory = (new Factory)
    ->withServiceAccount(__DIR__.'/push-notification-5ce03-firebase-adminsdk-6mh0o-3eeaa64ee4.json')
    ->withDatabaseUri('https://172.20.10.5.firebaseio.com');

    $messaging = $factory->createMessaging();

    $message = [
        'token' => $fcmToken,
        'notification' => [
            'title' => $title,
            'body' => $body,
        ],
        'data' => $data,
    ];

    try {
        $messaging->send($message);
        return true;
    } catch (\Kreait\Firebase\Exception\MessagingException $e) {
        error_log('Failed to send push notification: ' . $e->getMessage());
        return false;
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

    $watermark = imagecreatefrompng('../assets/images/watermark.png'); // Path to your watermark image
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

// Path to the email template files
$templatePaths = [
    'default' => '../application/views/emails/en/request.php',
    'leave_bank' => '../application/views/emails/en/bankrequest.php',
    'approve' => '../application/views/emails/en/request_accepted.php',
    'reject' => '../application/views/emails/en/request_rejected.php',
    'approve_leave_bank' => '../application/views/emails/en/manager_approved.php',
    'cancel' => '../application/views/emails/en/cancelled.php',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'create') {
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

    $query = "INSERT INTO leaves (employee, type, cause, startdate, enddate, duration, status, attachment) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        error_log("Prepare statement failed: " . $conn->error);
        sendJsonResponse('error', 'Failed to prepare statement: ' . $conn->error);
    }
    $stmt->bind_param("iisssiss", $userId, $leaveType, $leaveDesc, $startDate, $endDate, $leaveDuration, $leaveStatus, $imagePath);

    if ($stmt->execute()) {
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
                'Title' => 'New Leave Application',
                'Firstname' => $userDetails['firstname'],
                'Lastname' => $userDetails['lastname'],
                'BaseUrl' => 'https://60.51.59.113/SKG-LMS/home',
                'LeaveId' => $stmt->insert_id,
                'StartDate' => $startDate,
                'EndDate' => $endDate,
                'Type' => getLeaveTypeNameById($leaveType, $conn),
                'Duration' => $leaveDuration,
                'Balance' => $newBalance,
                'Reason' => $leaveDesc,
                'Comments' => '',
                'Status' => 'Requested'
            ];

            $emailBody = buildEmailBody($templatePath, $emailData);
            if ($emailBody) {
                $emailSent = sendEmail($managerEmail, 'New Leave Application', $emailBody);

                // Send push notification to the manager
                $managerFcmToken = getManagerFcmToken($userId, $conn);
                if ($managerFcmToken) {
                    $pushSent = sendPushNotification($managerFcmToken, 'New Leave Application', 'You have a new leave application from ' . $userDetails['firstname'] . ' ' . $userDetails['lastname'], $emailData);
                    if (!$pushSent) {
                        error_log("Failed to send push notification to manager with token: $managerFcmToken");
                    } else {
                        error_log("Push notification sent to manager with token: $managerFcmToken");
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

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['user_id']) && isset($_GET['type'])) {
        $userId = intval($_GET['user_id']);
        $type = $_GET['type'];

        if ($type === 'individual') {
            // Get all leave for a specific user
            $query = "SELECT l.*, s.name AS status_name, t.name AS type_name, u.firstname, u.fcm_token
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
        } elseif ($type === 'organization') {
            // Get all leave for users in the same organization
            $organizationId = getUserOrganization($userId, $conn);
            if ($organizationId !== null) {
                $query = "SELECT l.*, u.firstname, s.name AS status_name, t.name AS type_name, u.fcm_token
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
        } elseif ($type === 'manager') {
            // Get all leave for users managed by the current user
            $query = "SELECT l.*, u.firstname, s.name AS status_name, t.name AS type_name, u.fcm_token
                      FROM leaves l
                      JOIN users u ON l.employee = u.id
                      JOIN status s ON l.status = s.id
                      JOIN types t ON l.type = t.id
                      JOIN delegations d 
                      WHERE (u.manager = $userId OR d.delegate_id = $userId) AND l.status = 2"; // Assuming status 2 is 'Pending'
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
    } elseif (isset($_GET['leave_id'])) {
        $leaveId = intval($_GET['leave_id']);

        // Get detailed information for a specific leave
        $query = "SELECT l.*, s.name AS status_name, t.name AS type_name, u.firstname, u.lastname, u.email, u.id
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
    } elseif (isset($_GET['role']) && isset($_GET['type'])) {
        
        $role = intval($_GET['role']);
        $type = $_GET['type'];

        if ($type === 'hr') {
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

function getUserFcmToken($userId, $conn) {
    error_log("Getting FCM token for user ID: " . $userId);
    $query = "SELECT fcm_token FROM users WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        error_log("FCM token found: " . $row['fcm_token']);
        return $row['fcm_token'];
    }
    error_log("No FCM token found for user ID: " . $userId);
    return null;
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

if ($_SERVER['REQUEST_METHOD'] === 'PUT' && isset($_GET['action']) && ($_GET['action'] === 'approve' || $_GET['action'] === 'reject')) {
    $encodedData = file_get_contents('php://input');
    if (!empty($encodedData)) {
        $decodedData = json_decode($encodedData, true);
        if (isset($decodedData['leave_id'])) {
            $leaveId = intval($decodedData['leave_id']);
            $leaveType = getLeaveTypeByLeaveId($leaveId, $conn);
            if (!$leaveType) {
                echo "Error: Invalid leave ID.";
                exit();
            }
            $statusValue = ($_GET['action'] === 'approve') ? ($leaveType == '3' ? '7' : '3') : '4';
            $query = "UPDATE leaves SET status = ? WHERE id = ?";
            $stmt = $conn->prepare($query);

            if ($stmt) {
                $stmt->bind_param("si", $statusValue, $leaveId);
                $stmt->execute();
                if ($stmt->affected_rows > 0) {
                    $userEmail = getUserEmailByLeaveId($leaveId, $conn);
                    $userId = getUserIdByLeaveId($leaveId, $conn);
                    $userDetails = getUserDetailsById($userId, $conn);

                    // Get leave details
                    $leaveDetailsQuery = "SELECT startdate, enddate, type, cause, status FROM leaves WHERE id = ?";
                    $leaveDetailsStmt = $conn->prepare($leaveDetailsQuery);
                    $leaveDetailsStmt->bind_param("i", $leaveId);
                    $leaveDetailsStmt->execute();
                    $leaveDetailsResult = $leaveDetailsStmt->get_result();
                    $leaveDetails = $leaveDetailsResult->fetch_assoc();

                    if ($userEmail && $leaveDetails) {
                        // Determine the template path based on the leave type and action
                        if ($leaveType == '3' && $_GET['action'] === 'approve') {
                            $templatePath = $templatePaths['approve_leave_bank'];
                        } elseif ($_GET['action'] === 'approve') {
                            $templatePath = $templatePaths['approve'];
                        } else {
                            $templatePath = $templatePaths['reject'];
                        }

                        // Prepare email data
                        $emailData = [
                            'Title' => ($_GET['action'] === 'approve') ? 'Leave Approved' : 'Leave Rejected',
                            'Firstname' => $userDetails['firstname'],
                            'Lastname' => $userDetails['lastname'],
                            'BaseUrl' => 'https://60.51.59.113/SKG-LMS/home',
                            'LeaveId' => $leaveId,
                            'StartDate' => $leaveDetails['startdate'],
                            'EndDate' => $leaveDetails['enddate'],
                            'Type' => getLeaveTypeNameById($leaveDetails['type'], $conn),
                            'Duration' => '',  // You can calculate and fill this if needed
                            'Balance' => '',   // You can calculate and fill this if needed
                            'Cause' => $leaveDetails['cause'],
                            'Comments' => ($_GET['action'] === 'approve') ? 'Your leave request has been approved.' : 'Your leave request has been rejected.',
                            'Status' => 'Waiting HR Approval'
                        ];

                        $emailBody = buildEmailBody($templatePath, $emailData);
                        if ($emailBody) {
                            $emailSent = sendEmail($userEmail, $emailData['Title'], $emailBody);

                            // Send push notification to the user
                            $userFcmToken = getUserFcmToken($userId, $conn);
                            if ($userFcmToken) {
                                $pushSent = sendPushNotification($userFcmToken, $emailData['Title'], $emailData['Comments'], ['leave_id' => $leaveId]);
                                if (!$pushSent) {
                                    error_log("Failed to send push notification to user with token: $userFcmToken");
                                } else {
                                    error_log("Push notification sent to user with token: $userFcmToken");
                                }
                            } else {
                                error_log("No FCM token found for user");
                            }

                            if ($emailSent === true) {
                                echo "Success";
                            } else {
                                echo "Leave status updated but email failed: " . $emailSent;
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'update') {
    $encodedData = file_get_contents('php://input');
    $decodedData = json_decode($encodedData, true);
    $requiredFields = array('id', 'leave_Type', 'leave_desc', 'start_date', 'end_date', 'leave_duration');
    $missingFields = array();

    foreach ($requiredFields as $field) {
        if (!isset($decodedData[$field])) {
            $missingFields[] = $field;
        }
    }

    if (empty($missingFields)) {
        $leaveId = $decodedData['id'];
        $leaveType = $decodedData['leave_Type'];
        $leaveDesc = $decodedData['leave_desc'];
        $startDate = $decodedData['start_date'];
        $endDate = $decodedData['end_date'];
        $leaveDuration = $decodedData['leave_duration'];

        $query = "UPDATE leaves SET type = ?, cause = ?, startdate = ?, enddate = ?, duration = ? WHERE id = ?";
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            error_log("Prepare statement failed: " . $conn->error);
            sendJsonResponse('error', 'Failed to prepare statement: ' . $conn->error);
        }
        $stmt->bind_param("issssi", $leaveType, $leaveDesc, $startDate, $endDate, $leaveDuration, $leaveId);

        if ($stmt->execute()) {
            sendJsonResponse('success', 'Leave updated successfully.');
        } else {
            error_log("Failed to update leave: " . $stmt->error);
            sendJsonResponse('error', 'Failed to update leave: ' . $stmt->error);
        }
    } else {
        sendJsonResponse('error', 'Required fields are missing. Missing fields: ' . implode(', ', $missingFields));
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'delete') {
    $encodedData = file_get_contents('php://input');
    $decodedData = json_decode($encodedData, true);
    $leaveId = $decodedData['id'];

    if (isset($leaveId)) {
        $query = "DELETE FROM leaves WHERE id = ?";
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            error_log("Prepare statement failed: " . $conn->error);
            sendJsonResponse('error', 'Failed to prepare statement: ' . $conn->error);
        }
        $stmt->bind_param("i", $leaveId);

        if ($stmt->execute()) {
            sendJsonResponse('success', 'Leave deleted successfully.');
        } else {
            error_log("Failed to delete leave: " . $stmt->error);
            sendJsonResponse('error', 'Failed to delete leave: ' . $stmt->error);
        }
    } else {
        sendJsonResponse('error', 'Leave ID is missing.');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'PUT' && isset($_GET['action']) && $_GET['action'] === 'cancel') {
    $encodedData = file_get_contents('php://input');
    if (!empty($encodedData)) {
        $decodedData = json_decode($encodedData, true);
        if (isset($decodedData['leave_id']) && isset($decodedData['leave_balance'])) {
            $leaveId = intval($decodedData['leave_id']);
            $leaveBalance = $decodedData['leave_balance'];
            $leaveType = getLeaveTypeByLeaveId($leaveId, $conn);
            if (!$leaveType) {
                sendJsonResponse('error', 'Invalid leave ID.');
            }
            $statusValue = '6'; // Status 6 means the leave is cancelled
            $query = "UPDATE leaves SET status = ? WHERE id = ?";
            $stmt = $conn->prepare($query);

            if ($stmt) {
                $stmt->bind_param("si", $statusValue, $leaveId);
                $stmt->execute();
                if ($stmt->affected_rows > 0) {
                    $userEmail = getUserEmailByLeaveId($leaveId, $conn);
                    $userId = getUserIdByLeaveId($leaveId, $conn);
                    $userDetails = getUserDetailsById($userId, $conn);
                    $managerEmail = getManagerEmail($userId, $conn);

                    // Get leave details
                    $leaveDetailsQuery = "SELECT startdate, enddate, type, cause, status, duration FROM leaves WHERE id = ?";
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
                            'BaseUrl' => 'https://60.51.59.113/SKG-LMS/home',
                            'LeaveId' => $leaveId,
                            'StartDate' => $leaveDetails['startdate'],
                            'EndDate' => $leaveDetails['enddate'],
                            'Type' => getLeaveTypeNameById($leaveDetails['type'], $conn),
                            'Duration' => $leaveDetails['duration'],
                            'Balance' => $newBalance,
                            'Reason' => $leaveDetails['cause'],
                            'Comments' => 'The leave request has been cancelled by the user.',
                            'Status' => 'Cancelled'
                        ];

                        $templatePath = $templatePaths['cancel']; // Assuming 'cancelled.php' is under templatePaths with key 'cancel'
                        $emailBody = buildEmailBody($templatePath, $emailData);
                        if ($emailBody) {
                            $emailSent = sendEmail($managerEmail, 'Leave Cancelled', $emailBody);

                            // Send push notification to the manager
                            $managerFcmToken = getManagerFcmToken($userId, $conn);
                            if ($managerFcmToken) {
                                $pushSent = sendPushNotification($managerFcmToken, 'Leave Cancelled', 'The leave request has been cancelled by the user.', $emailData);
                                if (!$pushSent) {
                                    error_log("Failed to send push notification to manager with token: $managerFcmToken");
                                } else {
                                    error_log("Push notification sent to manager with token: $managerFcmToken");
                                }
                            } else {
                                error_log("No FCM token found for manager");
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

if ($_SERVER['REQUEST_METHOD'] === 'PUT' && isset($_GET['action']) && ($_GET['action'] === 'approveBank' || $_GET['action'] === 'reject')) {
    $encodedData = file_get_contents('php://input');
    if (!empty($encodedData)) {
        $decodedData = json_decode($encodedData, true);
        if (isset($decodedData['leave_id'])) {
            $leaveId = intval($decodedData['leave_id']);
            $leaveType = getLeaveTypeByLeaveId($leaveId, $conn);
            if (!$leaveType) {
                echo "Error: Invalid leave ID.";
                exit();
            }
            $statusValue = ($_GET['action'] === 'approveBank') ? '3' : '4';
            $query = "UPDATE leaves SET status = ? WHERE id = ?";
            $stmt = $conn->prepare($query);

            if ($stmt) {
                $stmt->bind_param("si", $statusValue, $leaveId);
                $stmt->execute();
                if ($stmt->affected_rows > 0) {
                    $userEmail = getUserEmailByLeaveId($leaveId, $conn);
                    $userId = getUserIdByLeaveId($leaveId, $conn);
                    $userDetails = getUserDetailsById($userId, $conn);

                    // Get leave details
                    $leaveDetailsQuery = "SELECT startdate, enddate, type, cause, status FROM leaves WHERE id = ?";
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
                            'Title' => ($_GET['action'] === 'approveBank') ? 'Leave Approved' : 'Leave Rejected',
                            'Firstname' => $userDetails['firstname'],
                            'Lastname' => $userDetails['lastname'],
                            'BaseUrl' => 'https://60.51.59.113/SKG-LMS/home',
                            'LeaveId' => $leaveId,
                            'StartDate' => $leaveDetails['startdate'],
                            'EndDate' => $leaveDetails['enddate'],
                            'Type' => getLeaveTypeNameById($leaveDetails['type'], $conn),
                            'Duration' => '',  // You can calculate and fill this if needed
                            'Balance' => '',   // You can calculate and fill this if needed
                            'Cause' => $leaveDetails['cause'],
                            'Comments' => ($_GET['action'] === 'approveBank') ? 'Your leave request has been approved.' : 'Your leave request has been rejected.',
                            'Status' => 'Waiting HR Approval'
                        ];

                        $emailBody = buildEmailBody($templatePath, $emailData);
                        if ($emailBody) {
                            $emailSent = sendEmail($userEmail, $emailData['Title'], $emailBody);

                            // Send push notification to the user
                            $userFcmToken = getUserFcmToken($userId, $conn);
                            if ($userFcmToken) {
                                $pushSent = sendPushNotification($userFcmToken, $emailData['Title'], $emailData['Comments'], ['leave_id' => $leaveId]);
                                if (!$pushSent) {
                                    error_log("Failed to send push notification to user with token: $userFcmToken");
                                } else {
                                    error_log("Push notification sent to user with token: $userFcmToken");
                                }
                            } else {
                                error_log("No FCM token found for user");
                            }

                            if ($emailSent === true) {
                                echo "Success";
                            } else {
                                echo "Leave status updated but email failed: " . $emailSent;
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





?>
