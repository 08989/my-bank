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

$email = $_POST['email'] ?? '';
$pin = $_POST['password'] ?? '';

if (empty($email) || empty($pin)) {
    echo json_encode(["error" => "Please fill all fields."]);
    exit;
}

$sql = "SELECT id, name, account, phone, email, password, balance FROM customers WHERE email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($user = $result->fetch_assoc()) {
    if ($user['password'] === $pin) {
        unset($user['password']);
        echo json_encode(["success" => true, "user" => $user]);
    } else {
        echo json_encode(["error" => "Wrong PIN."]);
    }
} else {
    echo json_encode(["error" => "User not found."]);
}

$stmt->close();
$conn->close();
?>