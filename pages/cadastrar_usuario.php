<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';

require_login();
if (!tem_permissao('usuarios.gerenciar')) {
    header('Location: /dashboard');
    exit;
}

check_csrf();
$message = null;
$error = null;
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'create';

    try {
        $pdo->beginTransaction();

        if ($action === 'create') {
            $nome = trim($_POST['nome'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $senha = $_POST['senha'] ?? '';
            $nivel_acesso_id = $_POST['nivel_acesso_id'] ?? null;
            $orgao_id = empty($_POST['orgao_id']) ? null : $_POST['orgao_id'];

            if (empty($nome) || empty($email) || empty($senha) || empty($nivel_acesso_id)) {
                throw new Exception('Nome, e-mail, senha e nível de acesso são obrigatórios.');
            }

            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = :email");
            $stmt->execute([':email' => $email]);
            if ($stmt->fetch()) {
                throw new Exception('Este e-mail já está cadastrado.');
            }

            $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
            $insert_stmt = $pdo->prepare(
                "INSERT INTO usuarios (nome, email, senha_hash, orgao_id, created_at) VALUES (:nome, :email, :senha_hash, :orgao_id, NOW())"
            );
            $insert_stmt->execute([':nome' => $nome, ':email' => $email, ':senha_hash' => $senha_hash, ':orgao_id' => $orgao_id]);
            
            $user_id = $pdo->lastInsertId();

            $nivel_stmt = $pdo->prepare("INSERT INTO usuarios_niveis (usuario_id, nivel_acesso_id) VALUES (:usuario_id, :nivel_acesso_id)");
            $nivel_stmt->execute([':usuario_id' => $user_id, ':nivel_acesso_id' => $nivel_acesso_id]);
            
            $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Usuário cadastrado com sucesso!'];

        } elseif ($action === 'update') {
            $id = $_POST['id'] ?? null;
            $nome = trim($_POST['nome'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $senha = $_POST['senha'] ?? '';
            $nivel_acesso_id = $_POST['nivel_acesso_id'] ?? null;
            $orgao_id = empty($_POST['orgao_id']) ? null : $_POST['orgao_id'];

            if (empty($id) || empty($nome) || empty($email) || empty($nivel_acesso_id)) {
                throw new Exception('Nome, e-mail e nível de acesso são obrigatórios.');
            }

            $params = [':id' => $id, ':nome' => $nome, ':email' => $email, ':orgao_id' => $orgao_id];
            $sql = "UPDATE usuarios SET nome = :nome, email = :email, orgao_id = :orgao_id";
            if (!empty($senha)) {
                $sql .= ", senha_hash = :senha_hash";
                $params[':senha_hash'] = password_hash($senha, PASSWORD_DEFAULT);
            }
            $sql .= " WHERE id = :id";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            $delete_nivel_stmt = $pdo->prepare("DELETE FROM usuarios_niveis WHERE usuario_id = :usuario_id");
            $delete_nivel_stmt->execute([':usuario_id' => $id]);
            $insert_nivel_stmt = $pdo->prepare("INSERT INTO usuarios_niveis (usuario_id, nivel_acesso_id) VALUES (:usuario_id, :nivel_acesso_id)");
            $insert_nivel_stmt->execute([':usuario_id' => $id, ':nivel_acesso_id' => $nivel_acesso_id]);
            
            $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Usuário atualizado com sucesso!'];

        } elseif ($action === 'delete') {
            $id = $_POST['id'] ?? null;
            if ($id) {
                $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = :id");
                $stmt->execute([':id' => $id]);
                $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Usuário excluído com sucesso!'];
            }
        }

        $pdo->commit();
        header('Location: /cadastrar_usuario');
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        $error = $e->getMessage();
    }
}

$usuarios_stmt = $pdo->query("
    SELECT u.id, u.nome, u.email, o.nome AS orgao_nome, na.nome AS nivel_acesso_nome, un.nivel_acesso_id
    FROM usuarios u
    LEFT JOIN orgaos o ON u.orgao_id = o.id
    LEFT JOIN usuarios_niveis un ON u.id = un.usuario_id
    LEFT JOIN niveis_acesso na ON un.nivel_acesso_id = na.id
    ORDER BY u.nome
");
$usuarios = $usuarios_stmt->fetchAll();

$niveis_acesso = $pdo->query("SELECT id, nome FROM niveis_acesso ORDER BY nome")->fetchAll();
$orgaos = $pdo->query("SELECT id, nome FROM orgaos ORDER BY nome")->fetchAll();

render_header('Gerenciar Usuários - LicitAções');
?>

<div class="card">
    <h2>Cadastrar Novo Usuário</h2>
    
    <?php display_flash_message(); ?>
    <?php if ($error): ?>
        <div class="chip error" style="margin-bottom: 1rem;"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
        <input type="hidden" name="action" value="create">
        
        <div class="grid grid-3">
            <div>
                <label for="nome">Nome</label>
                <input type="text" id="nome" name="nome" required>
            </div>
            <div>
                <label for="email">E-mail</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div>
                <label for="senha">Senha</label>
                <input type="password" id="senha" name="senha" required>
            </div>
            <div>
                <label for="nivel_acesso_id">Nível de Acesso</label>
                <select id="nivel_acesso_id" name="nivel_acesso_id" required>
                    <option value="">-- Selecione --</option>
                    <?php foreach ($niveis_acesso as $nivel): ?>
                        <option value="<?= $nivel['id'] ?>"><?= htmlspecialchars($nivel['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="grid-span-2">
                <label for="orgao_id">Órgão (Opcional)</label>
                <select id="orgao_id" name="orgao_id">
                    <option value="">-- Nenhum / Todos --</option>
                    <?php foreach ($orgaos as $orgao): ?>
                        <option value="<?= $orgao['id'] ?>"><?= htmlspecialchars($orgao['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-actions" style="margin-top: 1.5rem;">
            <button class="btn good" type="submit">Cadastrar Usuário</button>
        </div>
    </form>
</div>

<div class="card" style="margin-top: 2rem;">
    <h2>Usuários Cadastrados</h2>
    <div class="table-scroll-container">
        <table>
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>E-mail</th>
                    <th>Nível de Acesso</th>
                    <th>Órgão</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($usuarios as $usuario): ?>
                    <tr>
                        <td><?= htmlspecialchars($usuario['nome']) ?></td>
                        <td><?= htmlspecialchars($usuario['email']) ?></td>
                        <td><span class="chip"><?= htmlspecialchars($usuario['nivel_acesso_nome'] ?? 'N/D') ?></span></td>
                        <td><?= htmlspecialchars($usuario['orgao_nome'] ?? 'Todos') ?></td>
                        <td>
                            <a href="#editar-usuario-popup-<?= $usuario['id'] ?>" class="btn btn-sm">Editar</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php foreach ($usuarios as $usuario): ?>
<div id="editar-usuario-popup-<?= $usuario['id'] ?>" class="popup-overlay">
  <div class="popup-card card">
    <a href="#" class="popup-close">&times;</a>
    <h2>Editar Usuário: <?= htmlspecialchars($usuario['nome']) ?></h2>
    <form method="post" class="form-popup">
        <div class="popup-content">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
            <input type="hidden" name="id" value="<?= $usuario['id'] ?>">
            <input type="hidden" name="action" value="update">
            
            <div class="grid grid-2">
                <div>
                    <label>Nome</label>
                    <input type="text" name="nome" value="<?= htmlspecialchars($usuario['nome']) ?>" required>
                </div>
                <div>
                    <label>E-mail</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($usuario['email']) ?>" required>
                </div>
                <div>
                    <label>Nível de Acesso</label>
                    <select name="nivel_acesso_id" required>
                        <option value="">-- Selecione --</option>
                        <?php foreach ($niveis_acesso as $nivel): ?>
                            <option value="<?= $nivel['id'] ?>" <?= ($usuario['nivel_acesso_id'] == $nivel['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($nivel['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label>Órgão</label>
                    <select name="orgao_id">
                        <option value="">-- Nenhum / Todos --</option>
                        <?php 
                            $stmt_user_orgao = $pdo->prepare("SELECT orgao_id FROM usuarios WHERE id = ?");
                            $stmt_user_orgao->execute([$usuario['id']]);
                            $current_orgao_id = $stmt_user_orgao->fetchColumn();

                            foreach ($orgaos as $orgao): 
                        ?>
                            <option value="<?= $orgao['id'] ?>" <?= ($current_orgao_id == $orgao['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($orgao['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div style="margin-top: 1rem;">
                <label>Nova Senha</label>
                <input type="password" name="senha" placeholder="Deixe em branco para não alterar">
            </div>
        </div>
        <div class="form-actions">
            <?php if ($usuario['id'] !== 1):?>
                <button class="btn warn btn-confirm-delete" type="submit" name="action" value="delete" data-confirm-message="Excluir este usuário?">Excluir</button>
            <?php endif; ?>
            <button class="btn good" type="submit" name="action" value="update">Atualizar Usuário</button>
        </div>
    </form>
  </div>
</div>
<?php endforeach; ?>

<?php
render_footer();
?>