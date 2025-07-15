<?php
include 'db_config.php';

header('Content-Type: application/json');

$user_id = $_POST['user_id'] ?? '';
$device_id = $_POST['device_id'] ?? '';

if (empty($user_id) || empty($device_id)) {
    echo json_encode(['status' => 'error', 'message' => 'ID do usuário e ID do dispositivo são obrigatórios.']);
    exit;
}

// Verificar se o usuário está banido
$sql = "SELECT is_banned FROM users WHERE id = ?";
$stmt_user = $conn->prepare($sql);
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$stmt_user->bind_result($is_banned);
$stmt_user->fetch();
$stmt_user->close();

if ($is_banned) {
    echo json_encode(['status' => 'banned', 'message' => 'Este usuário está banido.']);
    exit;
}

// Verificar se o dispositivo está banido
$sql = "SELECT id FROM users WHERE device_id = ? AND is_banned = 1";
$stmt_device = $conn->prepare($sql);
$stmt_device->bind_param("s", $device_id);
$stmt_device->execute();
$stmt_device->store_result();

if ($stmt_device->num_rows > 0) {
    echo json_encode(['status' => 'banned', 'message' => 'Este dispositivo está banido.']);
    $stmt_device->close();
    exit;
}

$stmt_device->close();

echo json_encode(['status' => 'ok']);

$conn->close();
?>
