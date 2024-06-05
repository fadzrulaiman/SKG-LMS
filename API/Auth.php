<?php
session_start();
include('db.php');

$decodedData = json_decode(file_get_contents('php://input'), true);

if (isset($decodedData['Email']) && isset($decodedData['Password'])) {
    $UserEmail = mysqli_real_escape_string($conn, $decodedData['Email']);
    $UserPassword = $decodedData['Password'];

    $SQL = "SELECT * FROM tblemployees WHERE EmailId = '$UserEmail'";
    $exeSQL = mysqli_query($conn, $SQL);

    if ($exeSQL) {
        $checkEmail = mysqli_num_rows($exeSQL);

        if ($checkEmail > 0) {
            $arrayu = mysqli_fetch_assoc($exeSQL);
            $storedPassword = $arrayu['Password'];

            if (password_verify($UserPassword, $storedPassword) || $UserPassword == $storedPassword) {
                $Message = "Success";
                $UserID = $arrayu['emp_id'];
                $UserRole = $arrayu['role'];
                $FirstName = $arrayu['FirstName'];
                $LastName = $arrayu['LastName'];
                $DepartmentID = $arrayu['Department'];

                $SQL2 = "SELECT * FROM tbl_leavebalance WHERE empid = '$UserID'";
                $exeSQL2 = mysqli_query($conn, $SQL2);

                $leaveBalances = array(); // Initialize the array

                while ($row = mysqli_fetch_assoc($exeSQL2)) {
                    $leavetypeid = $row['leavetypeid'];
                    $leaveBalance = $row['leave_balance'];

                    if ($leavetypeid == 10) {
                        $Balance = $row['Leave_Balance'];
                    } elseif ($leavetypeid == 18) {
                        $Bank = $row['Leave_Balance'];
                    }
                }

                $query2 = "SELECT t2.id, t2.DepartmentShortName
                           FROM tblemployees t1
                           JOIN tbldepartments t2 ON t1.Department = t2.id
                           WHERE t1.EmailId = '$UserEmail'";

                $result2 = mysqli_query($conn, $query2);

                if ($result2 && mysqli_num_rows($result2) > 0) {
                    $row = mysqli_fetch_assoc($result2);
                    $DepartmentID = $row['id'];
                    $DepartmentShortName = $row['DepartmentShortName'];
                }

                $UserName = $FirstName . ' ' . $LastName;

                $_SESSION['user_id'] = $UserID;
                $_SESSION['user_role'] = $UserRole;
                $_SESSION['user_name'] = $UserName;
                $_SESSION['user_department'] = $DepartmentID;
                $_SESSION['user_balance'] = $Balance;
                $_SESSION['user_bank'] = $Bank;
                $_SESSION['department_short_name'] = $DepartmentShortName;

            } else {
                $Message = "Wrong Email or Password";
            }
        } else {
            $Message = "Wrong Email or Password";
        }
    } else {
        $Message = "Database error: " . mysqli_error($conn);
    }
} else {
    $Message = "Invalid request: Email or password missing.";
}

$response = array(
    "Message" => $Message,
    "UserID" => isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null,
    "UserRole" => isset($_SESSION['user_role']) ? $_SESSION['user_role'] : null,
    "UserName" => isset($_SESSION['user_name']) ? $_SESSION['user_name'] : null,
    "DepartmentID" => isset($_SESSION['user_department']) ? $_SESSION['user_department'] : null,
    "Balance" => isset($_SESSION['user_balance']) ? $_SESSION['user_balance'] : null,
    "Bank" => isset($_SESSION['user_bank']) ? $_SESSION['user_bank'] : null,
    "DepartmentShortName" => isset($_SESSION['department_short_name']) ? $_SESSION['department_short_name'] : null,
);

echo json_encode($response);
?>
