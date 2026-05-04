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

$user_id = $_POST['user_id'] ?? 0;

if (!$user_id) {
    echo json_encode(["error" => "User ID required."]);
    exit;
}

$sql = "SELECT type, amount, date FROM transactions WHERE user_id = ? ORDER BY date DESC LIMIT 10";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$transactions = [];
while ($row = $result->fetch_assoc()) {
    $transactions[] = $row;
}

echo json_encode(["success" => true, "transactions" => $transactions]);

$conn->close();
?>