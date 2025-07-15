<?php
include 'db_config.php';
session_start();

header('Content-Type: application/json');

$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';
$device_id = $_POST['device_id'] ?? '';

if (empty($email) || empty($password) || empty($device_id)) {
    echo json_encode(['status' => 'error', 'message' => 'Email, senha e ID do dispositivo são obrigatórios.']);
    exit;
}

// Buscar usuário
$sql = "SELECT id, password, plan_expiration, is_banned FROM users WHERE email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();
$stmt->bind_result($user_id, $hashed_password, $plan_expiration, $is_banned);

if ($stmt->num_rows > 0) {
    $stmt->fetch();
    if (password_verify($password, $hashed_password)) {
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

        // Verificar se já existe uma sessão para este dispositivo
        $sql = "SELECT id FROM sessions WHERE user_id = ? AND device_id = ?";
        $session_stmt = $conn->prepare($sql);
        $session_stmt->bind_param("is", $user_id, $device_id);
        $session_stmt->execute();
        $session_stmt->store_result();

        if ($session_stmt->num_rows > 0) {
            // A sessão para este dispositivo já existe, não é necessário criar uma nova
        } else {
            // Excluir sessões antigas para outros dispositivos
            $sql = "DELETE FROM sessions WHERE user_id = ?";
            $delete_stmt = $conn->prepare($sql);
            $delete_stmt->bind_param("i", $user_id);
            $delete_stmt->execute();
            $delete_stmt->close();
        }

        // Gerar token de sessão
        $session_token = bin2hex(random_bytes(32));

        // Inserir nova sessão
        $sql = "INSERT INTO sessions (user_id, session_token, device_id) VALUES (?, ?, ?)";
        $insert_stmt = $conn->prepare($sql);
        $insert_stmt->bind_param("iss", $user_id, $session_token, $device_id);
        $insert_stmt->execute();
        $insert_stmt->close();

        // Registrar atividade
        $sql = "INSERT INTO activity_logs (user_id) VALUES (?)";
        $activity_stmt = $conn->prepare($sql);
        $activity_stmt->bind_param("i", $user_id);
        $activity_stmt->execute();
        $activity_stmt->close();

        $response = [
            'status' => 'success',
            'message' => 'Login bem-sucedido.',
            'user_id' => $user_id,
            'session_token' => $session_token,
            'plan_expiration' => $plan_expiration,
        ];

        // Se o plano não estiver expirado, fornecer credenciais do Xtream
        if (strtotime($plan_expiration) >= time()) {
            $xtream_logins_file = 'xtream_logins.json';
            if (file_exists($xtream_logins_file)) {
                $xtream_logins = json_decode(file_get_contents($xtream_logins_file), true);
                if (!empty($xtream_logins)) {
                    $random_login = $xtream_logins[array_rand($xtream_logins)];
                    $response['xtream_server'] = $random_login['server'];
                    $response['xtream_username'] = $random_login['username'];
                    $response['xtream_password'] = $random_login['password'];
                }
            }
        }

        echo json_encode($response);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Senha incorreta.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Usuário não encontrado.']);
}

$stmt->close();
$conn->close();
?>
