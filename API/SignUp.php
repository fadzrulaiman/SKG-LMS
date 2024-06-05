<?php
include('db.php');

$decodedData = json_decode(file_get_contents('php://input'), true);

$UserEmail = $decodedData['Email'];
$UserPassword = $decodedData['Password'];
$UserRole = $decodedData['Role'];

$checkEmailQuery = "SELECT * FROM tbl_user WHERE UserEmail = '$UserEmail'";
$checkEmailResult = mysqli_query($conn, $checkEmailQuery);
$checkEmail = mysqli_num_rows($checkEmailResult);

if ($checkEmail != 0) {
    $Message = "Email already exists";
} else {
    $insertQuery = "INSERT INTO tbl_user (UserEmail, UserPassword, UserRole) VALUES ('$UserEmail', '$UserPassword', '$UserRole')";
    $insertResult = mysqli_query($conn, $insertQuery);

    if ($insertResult) {
        $Message = "Success";
    } else {
        $Message = "Error";
    }
}

$response = array("Message" => $Message);
echo json_encode($response);
?>
