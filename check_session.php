<?php
include 'db_config.php';

header('Content-Type: application/json');

$user_id = $_POST['user_id'] ?? '';
$device_id = $_POST['device_id'] ?? '';

if (empty($user_id) || empty($device_id)) {
    echo json_encode(['status' => 'error', 'message' => 'ID do usuário e ID do dispositivo são obrigatórios.']);
    exit;
}

// Verificar se existe uma sessão para este usuário em um dispositivo diferente
$sql = "SELECT id FROM sessions WHERE user_id = ? AND device_id != ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("is", $user_id, $device_id);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    echo json_encode(['status' => 'error', 'message' => 'Duplo login detectado.']);
} else {
    echo json_encode(['status' => 'success', 'message' => 'Sessão válida.']);
}

$stmt->close();
$conn->close();
?>
