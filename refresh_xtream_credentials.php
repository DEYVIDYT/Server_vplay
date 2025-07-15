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

// Buscar dados do usuário
$sql = "SELECT plan_expiration, is_banned FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($plan_expiration, $is_banned);
$stmt->fetch();
$stmt->close();

if ($is_banned) {
    echo json_encode(['status' => 'banned', 'message' => 'Este usuário está banido.']);
    exit;
}

if (strtotime($plan_expiration) < time()) {
    echo json_encode(['status' => 'expired', 'message' => 'Seu plano expirou.']);
    exit;
}

// Ler logins do Xtream
$xtream_logins_file = 'xtream_logins.json';
if (file_exists($xtream_logins_file)) {
    $xtream_logins = json_decode(file_get_contents($xtream_logins_file), true);
    if (!empty($xtream_logins)) {
        $random_login = $xtream_logins[array_rand($xtream_logins)];
        $xtream_server = $random_login['server'];
        $xtream_username = $random_login['username'];
        $xtream_password = $random_login['password'];
    }
}

if (isset($xtream_server)) {
    echo json_encode([
        'status' => 'success',
        'xtream_server' => $xtream_server,
        'xtream_username' => $xtream_username,
        'xtream_password' => $xtream_password
    ]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Nenhum login do Xtream disponível.']);
}

$conn->close();
?>
