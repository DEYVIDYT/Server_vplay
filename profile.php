<?php
include 'db_config.php';

header('Content-Type: application/json');

$user_id = $_POST['user_id'] ?? '';
$session_token = $_POST['session_token'] ?? '';

if (empty($user_id) || empty($session_token)) {
    echo json_encode(['status' => 'error', 'message' => 'ID do usuário e token de sessão são obrigatórios.']);
    exit;
}

// Verificar sessão
$sql = "SELECT id FROM sessions WHERE user_id = ? AND session_token = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("is", $user_id, $session_token);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows == 0) {
    echo json_encode(['status' => 'error', 'message' => 'Sessão inválida.']);
    $stmt->close();
    $conn->close();
    exit;
}

$stmt->close();

// Buscar dados do perfil
$sql = "SELECT email, plan_expiration FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($email, $plan_expiration);
$stmt->fetch();

function obfuscate_email($email) {
    $parts = explode('@', $email);
    $name = $parts[0];
    $domain = $parts[1];

    if (strlen($name) > 3) {
        $name = substr($name, 0, 3) . str_repeat('*', strlen($name) - 3);
    }

    return $name . '@' . $domain;
}

echo json_encode([
    'status' => 'success',
    'email' => obfuscate_email($email),
    'plan_expiration' => $plan_expiration
]);

$stmt->close();
$conn->close();
?>
