<?php
header("Access-Control-Allow-Origin: *");
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
session_start();
include('db.php');

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Extract the data from the POST request
    $encodedData = file_get_contents('php://input');
    $decodedData = json_decode($encodedData, true);
    $requiredFields = array('name', 'message', 'emailid', 'departmentid', 'approver','leaveid');
    $missingFields = array();

    $name = $decodedData['name'];
    $message = $decodedData['message'];
    $emailid = $decodedData['emailid'];
    $departmentid = $decodedData['departmentid'];
    $approver = $decodedData['approver'];
    $leaveid = $decodedData['leaveid'];

    if (empty($missingFields)) {
        // Load composer's autoloader
        require 'vendor/autoload.php';

        $mail = new PHPMailer(true);

        try {
            // Query to get the email addresses of Ap1 and Ap2
            if($userrole === "Staff"){
            $query =   "SELECT t2.EmailId
                        FROM tbldepartments t1
                        JOIN tblemployees t2 ON (t1.Ap1 = t2.emp_id OR t1.Ap2 = t2.emp_id)
                        WHERE t1.id = '$departmentid'"; 

            } else if ($userrole === "HOD") {
                $query =   "SELECT t2.EmailId
                            FROM tblleaves t1
                            JOIN tblemployees t2 ON (t1.empid = t2.emp_id )
                            WHERE t1.id = '$leaveid'"; 
            }

            $result = mysqli_query($conn, $query);

            if ($result && mysqli_num_rows($result) > 0) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $emails[] = $row['EmailId'];
                }

                // SMTP server configuration
                $mail->isSMTP();                                 // Send using SMTP
                $mail->Host       = 'smtp.gmail.com';            // Set the SMTP server to send through
                $mail->SMTPAuth   = true;                         // Enable SMTP authentication
                $mail->Username   = 'sawitlms@gmail.com';        // SMTP username
                $mail->Password   = 'xxpzfvvljgesmorr';          // SMTP password
                $mail->SMTPSecure = "tls";
                $mail->Port       = 587;

                // Recipients
                $mail->setFrom($emailid, $name);
                foreach ($emails as $recipientEmail) {
                    $mail->addAddress($recipientEmail);           // Add recipients
                }
                $mail->addReplyTo($emailid, $name);

                // Content
                $mail->isHTML(true);      // Set email format to HTML
                $mail->Subject = 'Leave Application';
                $mail->Body    = $message;  // Use the message from the frontend

                if ($mail->send()) {
                    echo "Message has been sent to Approvers!";
                }
            }
        } catch (Exception $e) {
            echo "Message couldn't be sent. Error: " . $mail->ErrorInfo;
        }
    } else {
        echo "All the fields are required!";
    }
} else {
    http_response_code(405); // Method Not Allowed
    echo json_encode(["error" => "Method not allowed"]);
}
?>
