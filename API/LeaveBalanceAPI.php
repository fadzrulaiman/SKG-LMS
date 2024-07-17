<?php

require_once 'db.php'; // Include the database connection file
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

// Get the posted user ID
$encodedData = file_get_contents('php://input');  // take data from react native fetch API
$decodedData = json_decode($encodedData, true);
$user_id = $decodedData['user_id'] ?? null;

if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'No user ID provided']);
    exit();
}

// Step 1: Get user's contract value from users table
$sql = "SELECT contract FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo json_encode(["error" => "User not found"]);
    exit;
}

$user = $result->fetch_assoc();
$user_contract = $user['contract'];

// Step 2: Compare with contract value from entitleddays table and get leave type and days
$sql = "SELECT type, days FROM entitleddays WHERE contract = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $user_contract);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo json_encode(["error" => "No entitled days found for the user's contract"]);
    exit;
}

$entitled_days = [];
$max_leave_days = [];
while ($row = $result->fetch_assoc()) {
    $entitled_days[$row['type']] = (int)$row['days'];
    $max_leave_days[$row['type']] = (int)$row['days']; // Store max days for each leave type in whole number format
}

// Ensure that the maximum leave days for leave bank and annual leave are the same
if (isset($max_leave_days[1])) {
    $max_leave_days[3] = $max_leave_days[1];
}

// Step 3: Get the sum of leave durations from leaves table for the same user and leave types with status 2 or 3
$sql = "SELECT type, SUM(duration) as total_duration FROM leaves WHERE employee = ? AND (status = 2 OR status = 3) GROUP BY type";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$used_days = [];
while ($row = $result->fetch_assoc()) {
    $used_days[$row['type']] = (int)$row['total_duration'];
}

// Step 4: Get the initial leave bank days based on the user ID from entitleddays table
$sql = "SELECT days FROM entitleddays WHERE type = '3' AND employee = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$leave_bank_initial = 0;
if ($result->num_rows > 0) {
    $leave_bank = $result->fetch_assoc();
    $leave_bank_initial = (int)$leave_bank['days'];
}

// Step 5: Get the used leave bank days from the leaves table with status 2 or 3
$sql = "SELECT SUM(duration) as total_duration FROM leaves WHERE employee = ? AND type = '3' AND (status = 2 OR status = 3)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$leave_bank_used = 0;
if ($result->num_rows > 0) {
    $leave_bank = $result->fetch_assoc();
    $leave_bank_used = (int)$leave_bank['total_duration'];
}

// Calculate the leave bank balance
$leave_balance['3'] = $leave_bank_initial - $leave_bank_used;

// Step 6: Get the sick leave balance from the contracts table where type = 2 and id = user_id
$sql = "SELECT days FROM entitleddays WHERE type = '2' AND employee = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$sick_leave_balance = 0;
if ($result->num_rows > 0) {
    $contract = $result->fetch_assoc();
    $sick_leave_balance = (int)$contract['days'];
}

// Step 7: Get the used sick leave days from the leaves table with status 2 or 3
$sql = "SELECT SUM(duration) as total_duration FROM leaves WHERE employee = ? AND type = '2' AND (status = 2 OR status = 3)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$sick_leave_used = 0;
if ($result->num_rows > 0) {
    $leave = $result->fetch_assoc();
    $sick_leave_used = (int)$leave['total_duration'];
}

// Include the sick leave balance in the leave balance
$leave_balance['2'] = $sick_leave_balance - $sick_leave_used;
$max_leave_days['2'] = $sick_leave_balance;

// Step 8: Calculate the leave balance for other leave types
foreach ($entitled_days as $type => $entitled_days_count) {
    if ($type != '3' && $type != '2') { // Skip leave bank and sick leave as they're already calculated
        $used_days_count = $used_days[$type] ?? 0;
        $leave_balance[$type] = $entitled_days_count - $used_days_count;
    }
}

// Get leave type names from the types table
$sql = "SELECT id, name FROM types";
$result = $conn->query($sql);

$leave_types = [];
while ($row = $result->fetch_assoc()) {
    $leave_types[$row['id']] = $row['name'];
}

// Map leave type IDs to names
$leave_balance_named = [];
$max_leave_days_named = [];

foreach ($leave_balance as $type_id => $balance) {
    $leave_balance_named[$leave_types[$type_id]] = $balance;
}

foreach ($max_leave_days as $type_id => $days) {
    $max_leave_days_named[$leave_types[$type_id]] = $days;
}

// Log the response for debugging
error_log(json_encode([
    "leave_balance" => $leave_balance_named,
    "max_leave_days" => $max_leave_days_named
]));

// Return the leave balance and max leave days as JSON
echo json_encode([
    "leave_balance" => $leave_balance_named,
    "max_leave_days" => $max_leave_days_named
]);

$conn->close();
?>
