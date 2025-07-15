<?php
include 'db_config.php';

header('Content-Type: application/json');

$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';
$device_id = $_POST['device_id'] ?? '';

if (empty($email) || empty($password) || empty($device_id)) {
    echo json_encode(['status' => 'error', 'message' => 'Todos os campos são obrigatórios.']);
    exit;
}

// Verificar se o ID do dispositivo já existe
$sql = "SELECT id FROM users WHERE device_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $device_id);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    echo json_encode(['status' => 'error', 'message' => 'Este dispositivo já está registrado.']);
    $stmt->close();
    $conn->close();
    exit;
}

$stmt->close();

// Verificar se o e-mail já existe
$sql = "SELECT id FROM users WHERE email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    echo json_encode(['status' => 'error', 'message' => 'Este e-mail já está cadastrado.']);
    $stmt->close();
    $conn->close();
    exit;
}

$stmt->close();

// Hash da senha
$hashed_password = password_hash($password, PASSWORD_DEFAULT);
$plan_expiration = date('Y-m-d', strtotime('+2 days'));

// Inserir novo usuário
$sql = "INSERT INTO users (email, password, device_id, plan_expiration) VALUES (?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssss", $email, $hashed_password, $device_id, $plan_expiration);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Usuário registrado com sucesso.']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Erro ao registrar o usuário.']);
}

$stmt->close();
$conn->close();
?>
