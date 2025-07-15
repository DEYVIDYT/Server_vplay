<?php
include '../db_config.php';

header('Content-Type: application/json');

$auth_token = "f3a8e0d8a5f2b7c9f9ad6c2eb37dd28cb3fa6ff2390b0a6129739e2c5a891d43";
$provided_token = $_GET['token'] ?? '';

if ($provided_token !== $auth_token) {
    echo json_encode(['status' => 'error', 'message' => 'Token de autenticação inválido.']);
    exit;
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'get_stats':
        getStats($conn);
        break;
    case 'get_users':
        getUsers($conn);
        break;
    case 'get_enhanced_stats':
        getEnhancedStats($conn);
        break;
    case 'search_users':
        searchUsers($conn);
        break;
    case 'add_plan':
        addPlan($conn);
        break;
    case 'toggle_ban':
        toggleBan($conn);
        break;
    case 'delete_user':
        deleteUser($conn);
        break;
    case 'remove_days':
        removeDays($conn);
        break;
    default:
        echo json_encode(['status' => 'error', 'message' => 'Ação inválida.']);
}

function getStats($conn) {
    // Usuários online (sessões ativas nos últimos 5 minutos)
    $sql = "SELECT COUNT(DISTINCT user_id) as online_users FROM sessions WHERE created_at >= NOW() - INTERVAL 5 MINUTE";
    $result = $conn->query($sql);
    $online_users = $result->fetch_assoc()['online_users'] ?? 0;

    // Usuários hoje
    $sql = "SELECT COUNT(DISTINCT user_id) as today_users FROM activity_logs WHERE login_time >= CURDATE()";
    $result = $conn->query($sql);
    $today_users = $result->fetch_assoc()['today_users'] ?? 0;
    
    // Total de usuários
    $sql = "SELECT COUNT(*) as total_users FROM users";
    $result = $conn->query($sql);
    $total_users = $result->fetch_assoc()['total_users'] ?? 0;
    
    // Usuários com plano ativo
    $sql = "SELECT COUNT(*) as active_plans FROM users WHERE plan_expiration >= CURDATE() AND plan_expiration != '1970-01-01'";
    $result = $conn->query($sql);
    $active_plans = $result->fetch_assoc()['active_plans'] ?? 0;
    
    // Usuários banidos
    $sql = "SELECT COUNT(*) as banned_users FROM users WHERE is_banned = 1";
    $result = $conn->query($sql);
    $banned_users = $result->fetch_assoc()['banned_users'] ?? 0;

    echo json_encode([
        'online_users' => $online_users,
        'today_users' => $today_users,
        'total_users' => $total_users,
        'active_plans' => $active_plans,
        'banned_users' => $banned_users
    ]);
}

function getUsers($conn) {
    $search = $_GET['search'] ?? '';
    $status = $_GET['status'] ?? 'all';
    $plan_status = $_GET['plan_status'] ?? 'all';
    
    $sql = "SELECT id, email, plan_expiration, is_banned FROM users WHERE 1=1";
    $params = [];
    $types = '';
    
    // Filtro por email
    if (!empty($search)) {
        $sql .= " AND email LIKE ?";
        $params[] = "%$search%";
        $types .= 's';
    }
    
    // Filtro por status de ban
    if ($status === 'banned') {
        $sql .= " AND is_banned = 1";
    } elseif ($status === 'active') {
        $sql .= " AND is_banned = 0";
    }
    
    // Filtro por status do plano
    if ($plan_status === 'active') {
        $sql .= " AND plan_expiration >= CURDATE()";
    } elseif ($plan_status === 'expired') {
        $sql .= " AND (plan_expiration < CURDATE() OR plan_expiration IS NULL OR plan_expiration = '1970-01-01')";
    }
    
    $sql .= " ORDER BY id DESC";
    
    if (!empty($params)) {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $conn->query($sql);
    }
    
    $users = [];
    while ($row = $result->fetch_assoc()) {
        // Adicionar informação se o plano está ativo
        $row['plan_active'] = ($row['plan_expiration'] && 
                              $row['plan_expiration'] !== '1970-01-01' && 
                              strtotime($row['plan_expiration']) >= time());
        $users[] = $row;
    }
    
    echo json_encode($users);
}

function toggleBan($conn) {
    $data = json_decode(file_get_contents('php://input'), true);
    $userId = $data['user_id'] ?? 0;

    if ($userId <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'ID de usuário inválido.']);
        return;
    }

    $sql = "UPDATE users SET is_banned = 1 - is_banned WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Erro ao alterar o status de banido.']);
    }

    $stmt->close();
}

function removeDays($conn) {
    $data = json_decode(file_get_contents('php://input'), true);
    $userId = $data['user_id'] ?? 0;

    if ($userId <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'ID de usuário inválido.']);
        return;
    }

    $sql = "UPDATE users SET plan_expiration = '1970-01-01' WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Erro ao remover os dias.']);
    }

    $stmt->close();
}

function deleteUser($conn) {
    $data = json_decode(file_get_contents('php://input'), true);
    $userId = $data['user_id'] ?? 0;

    if ($userId <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'ID de usuário inválido.']);
        return;
    }

    $sql = "DELETE FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Erro ao remover o usuário.']);
    }

    $stmt->close();
}

function addPlan($conn) {
    $data = json_decode(file_get_contents('php://input'), true);
    $email = $data['email'] ?? '';
    $days = $data['days'] ?? 0;

    if (empty($email) || $days <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Email e duração são obrigatórios.']);
        return;
    }

    // Obter data de expiração atual
    $sql = "SELECT plan_expiration FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->bind_result($current_expiration);
    $stmt->fetch();
    $stmt->close();

    $new_expiration = date('Y-m-d', strtotime("+$days days"));
    if ($current_expiration && strtotime($current_expiration) > time()) {
        // Se o plano atual ainda for válido, adicione dias à data de expiração existente
        $new_expiration = date('Y-m-d', strtotime("$current_expiration +$days days"));
    }

    // Atualizar plano do usuário
    $sql = "UPDATE users SET plan_expiration = ? WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $new_expiration, $email);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Erro ao adicionar o plano.']);
    }

    $stmt->close();
}

$conn->close();
?>
