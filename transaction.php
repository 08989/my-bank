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
$amount = $_POST['amount'] ?? 0;
$type = $_POST['type'] ?? '';

if (!$user_id || !$amount || !$type) {
    echo json_encode(["error" => "Invalid transaction data."]);
    exit;
}

$amount = floatval($amount);
if ($amount <= 0) {
    echo json_encode(["error" => "Amount must be greater than 0."]);
    exit;
}

$conn->begin_transaction();

try {
    $balance_query = "SELECT balance FROM customers WHERE id = ? FOR UPDATE";
    $stmt = $conn->prepare($balance_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    if (!$user) {
        throw new Exception("User not found.");
    }
    
    $current_balance = floatval($user['balance']);
    
    if ($type === 'withdraw') {
        if ($current_balance < $amount) {
            throw new Exception("Insufficient funds.");
        }
        $new_balance = $current_balance - $amount;
    } elseif ($type === 'deposit') {
        $new_balance = $current_balance + $amount;
    } else {
        throw new Exception("Invalid transaction type.");
    }
    
    $update = $conn->prepare("UPDATE customers SET balance = ? WHERE id = ?");
    $update->bind_param("di", $new_balance, $user_id);
    if (!$update->execute()) {
        throw new Exception("Failed to update balance.");
    }
    
    $insert = $conn->prepare("INSERT INTO transactions (user_id, type, amount) VALUES (?, ?, ?)");
    $insert->bind_param("isd", $user_id, $type, $amount);
    if (!$insert->execute()) {
        throw new Exception("Failed to record transaction.");
    }
    
    $conn->commit();
    echo json_encode(["success" => true, "new_balance" => $new_balance, "message" => ucfirst($type) . " successful!"]);
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(["error" => $e->getMessage()]);
}

$conn->close();
?>