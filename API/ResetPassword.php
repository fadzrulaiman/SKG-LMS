<?php
// Include database connection
include 'db.php'; // Replace with your database connection file

// Include email library (PHPMailer or CodeIgniter email)
include '../application/config/email.php'; // Path to your email config

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // Make sure to load the PHPMailer autoloader

// Function to load the email template and replace placeholders
function loadEmailTemplate($templatePath, $data) {
    ob_start();
    include($templatePath);
    $templateContent = ob_get_clean();

    // Replace placeholders with actual values
    foreach ($data as $key => $value) {
        $templateContent = str_replace("{" . $key . "}", $value, $templateContent);
    }

    return $templateContent;
}

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the raw POST data
    $input = file_get_contents("php://input");
    $decodedData = json_decode($input, true);

    // Case 1: Reset password by user_id
    if (isset($decodedData['user_id']) && isset($decodedData['new_password'])) {
        $userId = mysqli_real_escape_string($conn, $decodedData['user_id']);
        $newPassword = $decodedData['new_password'];

        // Hash the new password
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        // Prepare SQL statement to update the password
        $sql = "UPDATE users SET password = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);

        if ($stmt) {
            // Bind parameters (s for string, i for integer)
            $stmt->bind_param("si", $hashedPassword, $userId);

            // Execute the statement
            if ($stmt->execute()) {
                // Retrieve user's first name, last name, and email for the email
                $sqlUser = "SELECT firstname, lastname, email FROM users WHERE id = ?";
                $stmtUser = $conn->prepare($sqlUser);
                $stmtUser->bind_param("i", $userId);
                $stmtUser->execute();
                $result = $stmtUser->get_result();

                if ($result->num_rows > 0) {
                    $user = $result->fetch_assoc();
                    $firstname = $user['firstname'];
                    $lastname = $user['lastname'];
                    $email = $user['email'];

                    // Prepare the data for email template placeholders
                    $templateData = [
                        'Title' => 'Your password has been reset',
                        'Firstname' => $firstname,
                        'Lastname' => $lastname
                    ];

                    // Load email template
                    $emailTemplate = loadEmailTemplate('../application/views/emails/en/password_reset.php', $templateData);

                    // Send email notification to the user
                    $mail = new PHPMailer(true);
                    try {
                        // Server settings
                        $mail->isSMTP();
                        $mail->Host = $config['smtp_host'];
                        $mail->SMTPAuth = $config['smtp_auth'];
                        $mail->Username = $config['smtp_user'];
                        $mail->Password = $config['smtp_pass'];
                        $mail->SMTPSecure = $config['smtp_crypto'];
                        $mail->Port = $config['smtp_port'];
                        $mail->CharSet = $config['charset'];
                        $mail->isHTML($config['mailtype'] === 'html');

                        // Recipients
                        $mail->setFrom($config['smtp_user'], 'Your Application Name');
                        $mail->addAddress($email, $firstname . ' ' . $lastname);

                        // Content
                        $mail->Subject = '[SKG-LMS] Your password has been reset';
                        $mail->Body = $emailTemplate;

                        // Send the email
                        $mail->send();

                        // Return success response
                        echo json_encode([
                            'success' => true,
                            'message' => 'Password reset successfully. Email sent.'
                        ]);
                    } catch (Exception $e) {
                        // Return error response for email sending failure
                        echo json_encode([
                            'success' => false,
                            'message' => 'Password reset successfully, but failed to send email. Mailer Error: ' . $mail->ErrorInfo
                        ]);
                    }
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => 'User not found'
                    ]);
                }

                $stmtUser->close();
            } else {
                // Return error response if the query failed
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to reset password'
                ]);
            }

            // Close the statement
            $stmt->close();
        } else {
            // Return error response if the statement preparation failed
            echo json_encode([
                'success' => false,
                'message' => 'Failed to prepare the statement'
            ]);
        }

    // Case 2: Reset password by user_login
    } elseif (isset($decodedData['user_login']) && isset($decodedData['new_password'])) {
        $userLogin = mysqli_real_escape_string($conn, $decodedData['user_login']);
        $newPassword = $decodedData['new_password'];

        // Hash the new password
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        // Prepare SQL statement to update the password
        $sql = "UPDATE users SET password = ? WHERE login = ?";
        $stmt = $conn->prepare($sql);

        if ($stmt) {
            // Bind parameters (s for string, i for string)
            $stmt->bind_param("ss", $hashedPassword, $userLogin);

            // Execute the statement
            if ($stmt->execute()) {
                // Retrieve user's first name, last name, and email for the email
                $sqlUser = "SELECT firstname, lastname, email FROM users WHERE login = ?";
                $stmtUser = $conn->prepare($sqlUser);
                $stmtUser->bind_param("s", $userLogin);
                $stmtUser->execute();
                $result = $stmtUser->get_result();

                if ($result->num_rows > 0) {
                    $user = $result->fetch_assoc();
                    $firstname = $user['firstname'];
                    $lastname = $user['lastname'];
                    $email = $user['email'];

                    // Prepare the data for email template placeholders
                    $templateData = [
                        'Title' => '[SKG-LMS] Your password has been reset',
                        'Firstname' => $firstname,
                        'Lastname' => $lastname
                    ];

                    // Load email template
                    $emailTemplate = loadEmailTemplate('../application/views/emails/en/password_reset.php', $templateData);

                    // Send email notification to the user
                    $mail = new PHPMailer(true);
                    try {
                        // Server settings
                        $mail->isSMTP();
                        $mail->Host = $config['smtp_host'];
                        $mail->SMTPAuth = $config['smtp_auth'];
                        $mail->Username = $config['smtp_user'];
                        $mail->Password = $config['smtp_pass'];
                        $mail->SMTPSecure = $config['smtp_crypto'];
                        $mail->Port = $config['smtp_port'];
                        $mail->CharSet = $config['charset'];
                        $mail->isHTML($config['mailtype'] === 'html');

                        // Recipients
                        $mail->setFrom($config['smtp_user'], 'Your Application Name');
                        $mail->addAddress($email, $firstname . ' ' . $lastname);

                        // Content
                        $mail->Subject = 'Password Reset Successful';
                        $mail->Body = $emailTemplate;

                        // Send the email
                        $mail->send();

                        // Return success response
                        echo json_encode([
                            'success' => true,
                            'message' => 'Password reset successfully. Email sent.'
                        ]);
                    } catch (Exception $e) {
                        // Return error response for email sending failure
                        echo json_encode([
                            'success' => false,
                            'message' => 'Password reset successfully, but failed to send email. Mailer Error: ' . $mail->ErrorInfo
                        ]);
                    }
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => 'User not found'
                    ]);
                }

                $stmtUser->close();
            } else {
                // Return error response if the query failed
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to reset password'
                ]);
            }

            // Close the statement
            $stmt->close();
        } else {
            // Return error response if the statement preparation failed
            echo json_encode([
                'success' => false,
                'message' => 'Failed to prepare the statement'
            ]);
        }
    } else {
        // Return error response if required fields are missing
        echo json_encode([
            'success' => false,
            'message' => 'Required fields missing'
        ]);
    }

    // Close the database connection
    $conn->close();
} else {
    // Return error response for invalid request method
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
}
