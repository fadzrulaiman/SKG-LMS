<?php
require 'db.php';  // Include your database connection settings
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT');
header('Access-Control-Allow-Headers: Content-Type');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // Make sure to load the PHPMailer autoloader
header('Content-Type: application/json');

// Include email configuration file
require '../application/config/email.php'; // Adjust path if needed

function sendEmail($recipient, $subject, $body) {
    global $config; // Use the global config array from email.php

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = $config['smtp_host'];
        $mail->SMTPAuth   = $config['smtp_auth'];
        $mail->Username   = $config['smtp_user'];
        $mail->Password   = $config['smtp_pass'];
        $mail->SMTPSecure = $config['smtp_crypto'];
        $mail->Port       = $config['smtp_port'];

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

// Function to generate a random 6-digit reset code
function generateResetCode() {
    return rand(100000, 999999);
}

// Function to format the reset code with bold and evenly spaced digits
function formatResetCode($resetCode) {
    $codeArray = str_split($resetCode);
    return implode(' ', array_map(function($digit) {
        return "<strong>$digit</strong>";
    }, $codeArray));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    if (isset($input['login'])) {
        $username = $input['login'];

        // Sanitize input
        $username = $conn->real_escape_string($username);

        // Query to find the user's email
        $query = "SELECT email FROM users WHERE login = '$username'";
        $result = $conn->query($query);

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $email = $user['email'];

            // Generate reset code
            $resetCode = generateResetCode();
            $formattedResetCode = formatResetCode($resetCode);

            // Save the reset code to the database (assuming a reset_codes table exists)
            $resetQuery = "INSERT INTO reset_codes (username, code, created_at) VALUES ('$username', '$resetCode', NOW())
                           ON DUPLICATE KEY UPDATE code = '$resetCode', created_at = NOW()";
            if ($conn->query($resetQuery) === TRUE) {
                // Send email
                $subject = "[SKG-LMS] Password Reset Code";
                $body = "
                    <html lang='en'>
                    <head>
                        <meta content='text/html; charset=utf-8' http-equiv='Content-Type'>
                        <meta charset='UTF-8'>
                    </head>
                    <body style='font-family: Arial, sans-serif; color: #333; background-color: #f4f4f4; margin: 0; padding: 20px;'>
                        <!-- Main Container -->
                        <table width='600' align='center' cellpadding='0' cellspacing='0' style='background-color: #ffffff; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); border-collapse: collapse;'>
                            <!-- Header -->
                            <tr>
                                <td style='padding: 20px; background-color: #007bff; color: #ffffff; text-align: center; font-size: 24px; font-weight: bold;'>
                                    Your password reset code
                                </td>
                            </tr>
                            <!-- Content -->
                            <tr>
                                <td style='padding: 20px; font-size: 14px;'>
                                    <p>Your password reset code is:</p>
                                    <p style='font-size: 20px; text-align: center; letter-spacing: 2px;'>{$formattedResetCode}</p>
                                    <p>Please use this code to reset your password. This code will expire in 10 minutes.</p>
                                </td>
                            </tr>
                            <!-- Footer -->
                            <tr>
                                <td style='padding: 20px; background-color: #f4f4f4; color: #777; text-align: center; font-size: 12px;'>
                                    <hr style='border-top: 1px solid #e0e0e0;'>
                                    <h5 style='color: #ff4444;'>*** This is an automatically generated message, please do not reply to this message ***</h5>
                                </td>
                            </tr>
                        </table>
                    </body>
                    </html>";

                if (sendEmail($email, $subject, $body)) {
                    echo json_encode(['success' => true, 'message' => 'Reset code sent to your email']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to send email']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to save reset code']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'User not found']);
        }

        $conn->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Username is required']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
