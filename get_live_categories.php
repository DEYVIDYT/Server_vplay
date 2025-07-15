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
$conn->close();

// Credenciais do Xtream - PREENCHA COM SEUS DADOS
$xtream_server = 'http://xtream.example.com';
$xtream_username = 'your_username';
$xtream_password = 'your_password';

$url = "$xtream_server/player_api.php?username=$xtream_username&password=$xtream_password&action=get_live_categories";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
$output = curl_exec($ch);
curl_close($ch);

echo $output;
?>
