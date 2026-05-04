<?php
header('Content-Type: application/json');
ini_set('display_errors', 1);
error_reporting(E_ALL);

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "atm";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die(json_encode(["error" => "Connection failed: " . $conn->connect_error]));
}

$name = $_POST['name'] ?? '';
$account = $_POST['account'] ?? '';
$phone = $_POST['phone'] ?? '';
$email = $_POST['email'] ?? '';
$pin = $_POST['password'] ?? '';

if (!$name || !$account || !$phone || !$email || !$pin) {
    echo json_encode(["error" => "All fields are required."]);
    exit;
}

if (!preg_match("/^[a-zA-Z\s]+$/", $name)) {
    echo json_encode(["error" => "Invalid name format."]);
    exit;
}
if (!preg_match("/^\d{4}$/", $account)) {
    echo json_encode(["error" => "Account must be 4 digits."]);
    exit;
}
if (!preg_match("/^\d{10}$/", $phone)) {
    echo json_encode(["error" => "Phone must be 10 digits."]);
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(["error" => "Invalid email format."]);
    exit;
}
if (!preg_match("/^\d{4}$/", $pin)) {
    echo json_encode(["error" => "PIN must be 4 digits."]);
    exit;
}

$check = $conn->prepare("SELECT * FROM customers WHERE email = ? OR account = ? OR phone = ?");
$check->bind_param("sss", $email, $account, $phone);
$check->execute();
$result = $check->get_result();

if ($result->num_rows > 0) {
    $existing = $result->fetch_assoc();
    if ($existing['email'] === $email) echo json_encode(["error" => "Email already registered."]);
    elseif ($existing['account'] === $account) echo json_encode(["error" => "Account number already exists."]);
    elseif ($existing['phone'] === $phone) echo json_encode(["error" => "Phone number already exists."]);
    exit;
}

$insert = $conn->prepare("INSERT INTO customers (name, account, phone, email, password, balance) VALUES (?, ?, ?, ?, ?, 0)");
$insert->bind_param("sssss", $name, $account, $phone, $email, $pin);

if ($insert->execute()) {
    echo json_encode(["success" => true, "message" => "Sign-up successful! Please log in."]);
} else {
    echo json_encode(["error" => "Error saving user: " . $insert->error]);
}

$conn->close();
?>