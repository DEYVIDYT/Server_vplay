<!DOCTYPE html>
<html>
<head>
    <title>Painel de Administração</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; line-height: 1.6; color: #333; background-color: #f4f4f4; margin: 0; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1, h2 { color: #333; }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 20px; }
        .stat-card { background: #f9f9f9; padding: 20px; border-radius: 8px; text-align: center; }
        .stat-card h3 { margin: 0 0 10px; font-size: 18px; }
        .stat-card p { font-size: 24px; font-weight: bold; margin: 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; border-bottom: 1px solid #ddd; text-align: left; }
        th { background-color: #f2f2f2; }
        tr:hover { background-color: #f5f5f5; }
        .actions button { padding: 8px 12px; border: none; border-radius: 4px; cursor: pointer; margin-right: 5px; }
        .ban-btn { background-color: #f44336; color: white; }
        .unban-btn { background-color: #4CAF50; color: white; }
        .delete-btn { background-color: #757575; color: white; }
        .remove-days-btn { background-color: #ff9800; color: white; }
        .form-container { margin-bottom: 20px; padding: 20px; background: #f9f9f9; border-radius: 8px; }
        .form-container input { width: calc(100% - 22px); padding: 10px; margin-bottom: 10px; border: 1px solid #ddd; border-radius: 4px; }
        .form-container button { width: 100%; padding: 10px; border: none; border-radius: 4px; background-color: #4CAF50; color: white; cursor: pointer; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Painel de Administração</h1>

        <div class="stats">
            <div class="stat-card">
                <h3>Usuários Online</h3>
                <p id="online-users">0</p>
            </div>
            <div class="stat-card">
                <h3>Usuários Hoje</h3>
                <p id="today-users">0</p>
            </div>
        </div>

        <div class="form-container">
            <h2>Adicionar/Alterar Plano</h2>
            <form id="add-plan-form">
                <input type="email" id="email" placeholder="Email do usuário" required>
                <input type="number" id="days" placeholder="Duração (dias)" required>
                <button type="submit">Salvar Plano</button>
            </form>
        </div>

        <h2>Usuários</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Email</th>
                    <th>Expiração do Plano</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody id="users-table-body">
                <!-- Os dados dos usuários serão inseridos aqui -->
            </tbody>
        </table>
    </div>

    <script>
        const correctToken = "f3a8e0d8a5f2b7c9f9ad6c2eb37dd28cb3fa6ff2390b0a6129739e2c5a891d43";
        let apiToken = '';

        document.addEventListener('DOMContentLoaded', function() {
            const enteredToken = prompt("Por favor, insira o token de autenticação:");
            if (enteredToken === correctToken) {
                apiToken = enteredToken;
                loadStats();
                loadUsers();
            } else {
                alert("Token inválido. Acesso negado.");
                document.body.innerHTML = '<h1>Acesso Negado</h1>';
            }

            document.getElementById('add-plan-form').addEventListener('submit', function(e) {
                e.preventDefault();
                const email = document.getElementById('email').value;
                const days = document.getElementById('days').value;
                addPlan(email, days);
            });
        });

        function apiFetch(action, options = {}) {
            const url = `api.php?token=${apiToken}&action=${action}`;
            return fetch(url, options).then(response => response.json());
        }

        function loadStats() {
            apiFetch('get_stats').then(data => {
                document.getElementById('online-users').textContent = data.online_users;
                document.getElementById('today-users').textContent = data.today_users;
            });
        }

        function removeDays(userId) {
            if (!confirm('Tem certeza que deseja remover os dias deste usuário?')) return;
            apiFetch('remove_days', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ user_id: userId })
            }).then(data => {
                if (data.status === 'success') {
                    loadUsers();
                } else {
                    alert('Erro: ' + data.message);
                }
            });
        }

        function loadUsers() {
            apiFetch('get_users').then(data => {
                if (data.status === 'error') {
                    alert('Erro ao carregar usuários: ' + data.message);
                    return;
                }
                const tbody = document.getElementById('users-table-body');
                tbody.innerHTML = '';
                data.forEach(user => {
                    const banButton = user.is_banned == 1
                        ? `<button class="unban-btn" onclick="toggleBan(${user.id})">Desbanir</button>`
                        : `<button class="ban-btn" onclick="toggleBan(${user.id})">Banir</button>`;
                    const row = `<tr>
                        <td>${user.id}</td>
                        <td>${user.email}</td>
                        <td>${user.plan_expiration || 'N/A'}</td>
                        <td>${user.is_banned == 1 ? 'Banido' : 'Ativo'}</td>
                        <td class="actions">
                            ${banButton}
                            <button class="delete-btn" onclick="deleteUser(${user.id})">Remover</button>
                            <button class="remove-days-btn" onclick="removeDays(${user.id})">Remover Dias</button>
                        </td>
                    </tr>`;
                    tbody.innerHTML += row;
                });
            }).catch(error => {
                alert('Erro ao carregar usuários. Verifique o console para mais detalhes.');
                console.error(error);
            });
        }

        function addPlan(email, days) {
            apiFetch('add_plan', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ email, days })
            }).then(data => {
                if (data.status === 'success') {
                    alert('Plano salvo com sucesso!');
                    loadUsers();
                } else {
                    alert('Erro: ' + data.message);
                }
            });
        }

        function toggleBan(userId) {
            if (!confirm('Tem certeza que deseja alterar o status de banido deste usuário?')) return;
            apiFetch('toggle_ban', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ user_id: userId })
            }).then(data => {
                if (data.status === 'success') {
                    loadUsers();
                } else {
                    alert('Erro: ' + data.message);
                }
            });
        }

        function deleteUser(userId) {
            if (!confirm('Tem certeza que deseja remover este usuário? Esta ação não pode ser desfeita.')) return;
            apiFetch('delete_user', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ user_id: userId })
            }).then(data => {
                if (data.status === 'success') {
                    loadUsers();
                } else {
                    alert('Erro: ' + data.message);
                }
            });
        }
    </script>
</body>
</html>
