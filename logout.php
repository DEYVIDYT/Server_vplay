<?php
include 'db_config.php';

header('Content-Type: application/json');

$user_id = $_POST['user_id'] ?? '';
$session_token = $_POST['session_token'] ?? '';

if (empty($user_id) || empty($session_token)) {
    echo json_encode(['status' => 'error', 'message' => 'ID do usuário e token de sessão são obrigatórios.']);
    exit;
}

// Excluir sessão
$sql = "DELETE FROM sessions WHERE user_id = ? AND session_token = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("is", $user_id, $session_token);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Logout bem-sucedido.']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Erro ao fazer logout.']);
}

$stmt->close();
$conn->close();
?>
