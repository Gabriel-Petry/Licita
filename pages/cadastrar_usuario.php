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
    $action = $_POST['action'] ?? null;

    try {
        $pdo->beginTransaction();

        if ($action === 'create_user') {
            $nome = trim($_POST['nome'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $senha = $_POST['senha'] ?? '';
            $nivel_acesso_id = $_POST['nivel_acesso_id'] ?? null;
            $orgao_id = empty($_POST['orgao_id']) ? null : $_POST['orgao_id'];
            
            $cpf = $_POST['cpf'] ?? null;
            $cargo = $_POST['cargo'] ?? null;
            $setor = $_POST['setor'] ?? null;

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
                "INSERT INTO usuarios (nome, email, senha_hash, orgao_id, cpf, cargo, setor, created_at) VALUES (:nome, :email, :senha_hash, :orgao_id, :cpf, :cargo, :setor, NOW())"
            );
            $insert_stmt->execute([
                ':nome' => $nome, 
                ':email' => $email, 
                ':senha_hash' => $senha_hash, 
                ':orgao_id' => $orgao_id,
                ':cpf' => $cpf,
                ':cargo' => $cargo,
                ':setor' => $setor
            ]);
            
            $user_id = $pdo->lastInsertId();

            $nivel_stmt = $pdo->prepare("INSERT INTO usuarios_niveis (usuario_id, nivel_acesso_id) VALUES (:usuario_id, :nivel_acesso_id)");
            $nivel_stmt->execute([':usuario_id' => $user_id, ':nivel_acesso_id' => $nivel_acesso_id]);
            
            $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Usuário cadastrado com sucesso!'];

        } elseif ($action === 'update_user') {
            $id = $_POST['id'] ?? null;
            $nome = trim($_POST['nome'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $senha = $_POST['senha'] ?? '';
            $nivel_acesso_id = $_POST['nivel_acesso_id'] ?? null;
            $orgao_id = empty($_POST['orgao_id']) ? null : $_POST['orgao_id'];
            
            $cpf = $_POST['cpf'] ?? null;
            $cargo = $_POST['cargo'] ?? null;
            $setor = $_POST['setor'] ?? null;

            if (empty($id) || empty($nome) || empty($email) || empty($nivel_acesso_id)) {
                throw new Exception('Nome, e-mail e nível de acesso são obrigatórios.');
            }

            $params = [
                ':id' => $id, 
                ':nome' => $nome, 
                ':email' => $email, 
                ':orgao_id' => $orgao_id,
                ':cpf' => $cpf,
                ':cargo' => $cargo,
                ':setor' => $setor
            ];
            $sql = "UPDATE usuarios SET nome = :nome, email = :email, orgao_id = :orgao_id, cpf = :cpf, cargo = :cargo, setor = :setor";
            
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

        } elseif ($action === 'delete_user') {
            $id = $_POST['id'] ?? null;
            if ($id) {
                if ($id == current_user()['id']) throw new Exception('Você não pode excluir seu próprio usuário.');
                $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = :id");
                $stmt->execute([':id' => $id]);
                $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Usuário excluído com sucesso!'];
            }
        
        } elseif ($action === 'create_nivel') {
            $nome_nivel = trim($_POST['nome_nivel'] ?? '');
            if (empty($nome_nivel)) {
                throw new Exception('O nome do nível de acesso é obrigatório.');
            }
            $stmt = $pdo->prepare("INSERT INTO niveis_acesso (nome) VALUES (:nome)");
            $stmt->execute([':nome' => $nome_nivel]);
            $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Nível de acesso criado com sucesso!'];
        
        } elseif ($action === 'update_nivel') {
            $nivel_id = $_POST['nivel_id'] ?? null;
            $permissoes_ids = $_POST['permissoes'] ?? [];

            if (empty($nivel_id)) {
                throw new Exception('ID do nível de acesso inválido.');
            }

            $delete_stmt = $pdo->prepare("DELETE FROM nivel_acesso_permissoes WHERE nivel_acesso_id = :nivel_id");
            $delete_stmt->execute([':nivel_id' => $nivel_id]);
            
            if (!empty($permissoes_ids)) {
                $insert_stmt = $pdo->prepare("INSERT INTO nivel_acesso_permissoes (nivel_acesso_id, permissao_id) VALUES (:nivel_id, :permissao_id)");
                foreach ($permissoes_ids as $permissao_id) {
                    $insert_stmt->execute([':nivel_id' => $nivel_id, ':permissao_id' => $permissao_id]);
                }
            }
            $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Permissões do nível de acesso atualizadas com sucesso!'];
        
        } elseif ($action === 'delete_nivel') {
            $nivel_id = $_POST['nivel_id'] ?? null;
            if (empty($nivel_id)) {
                throw new Exception('ID do nível de acesso inválido.');
            }
            $stmt = $pdo->prepare("DELETE FROM niveis_acesso WHERE id = :id");
            $stmt->execute([':id' => $nivel_id]);
            $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Nível de acesso excluído com sucesso!'];
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
    SELECT u.id, u.nome, u.email, u.cpf, u.cargo, u.setor, o.nome AS orgao_nome, na.nome AS nivel_acesso_nome, un.nivel_acesso_id, u.orgao_id
    FROM usuarios u
    LEFT JOIN orgaos o ON u.orgao_id = o.id
    LEFT JOIN usuarios_niveis un ON u.id = un.usuario_id
    LEFT JOIN niveis_acesso na ON un.nivel_acesso_id = na.id
    ORDER BY u.nome
");
$usuarios = $usuarios_stmt->fetchAll();

$niveis_acesso = $pdo->query("SELECT id, nome FROM niveis_acesso ORDER BY nome")->fetchAll();
$orgaos = $pdo->query("SELECT id, nome FROM orgaos ORDER BY nome")->fetchAll();
$permissoes = $pdo->query("SELECT id, codigo, descricao FROM permissoes ORDER BY codigo")->fetchAll();

$permissoes_por_nivel = [];
$perm_nivel_stmt = $pdo->query("SELECT nivel_acesso_id, permissao_id FROM nivel_acesso_permissoes");
while ($row = $perm_nivel_stmt->fetch()) {
    $permissoes_por_nivel[$row['nivel_acesso_id']][] = $row['permissao_id'];
}

render_header('Gerenciar Usuários e Perfis - LicitAções');
?>

<div class="tabs">
    <input type="radio" id="tab-usuarios" name="tab-control" checked>
    <input type="radio" id="tab-niveis" name="tab-control">

    <div class="tab-container">
        <label for="tab-usuarios" class="tab-link">Gerenciar Usuários</label>
        <label for="tab-niveis" class="tab-link">Gerenciar Níveis de Acesso</label>
    </div>

    <?php display_flash_message(); ?>
    <?php if ($error): ?>
        <div class="chip error" style="margin-bottom: 1rem;"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="tab-contents">
        <div id="usuarios-content" class="tab-content">
            <div class="card">
                <h2>Cadastrar Novo Usuário</h2>
                <form method="post">
                    <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
                    <input type="hidden" name="action" value="create_user">
                    
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
                    
                    <hr style="margin: 1.5rem 0;">
                    <h4 style="margin-bottom: 1rem;">Informações Adicionais (Opcional)</h4>
                    <div class="grid grid-3">
                        <div>
                            <label for="cpf">CPF</label>
                            <input type="text" id="cpf" name="cpf">
                        </div>
                        <div>
                            <label for="cargo">Cargo/Função</label>
                            <input type="text" id="cargo" name="cargo">
                        </div>
                        <div>
                            <label for="setor">Setor</label>
                            <input type="text" id="setor" name="setor">
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
        </div>

        <div id="niveis-content" class="tab-content">
            <div class="card">
                <h2>Cadastrar Novo Nível de Acesso</h2>
                <form method="post">
                    <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
                    <input type="hidden" name="action" value="create_nivel">
                    <div class="grid grid-3">
                         <div>
                            <label for="nome_nivel">Nome do Nível</label>
                            <input type="text" id="nome_nivel" name="nome_nivel" required>
                        </div>
                        <div style="align-self: flex-end;">
                            <button class="btn good" type="submit">Cadastrar Nível</button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="card" style="margin-top: 2rem;">
                <h2>Níveis de Acesso Cadastrados</h2>
                <div class="table-scroll-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($niveis_acesso as $nivel): ?>
                                <tr>
                                    <td><?= htmlspecialchars($nivel['nome']) ?></td>
                                    <td>
                                        <a href="#editar-nivel-popup-<?= $nivel['id'] ?>" class="btn btn-sm">Editar Permissões</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
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
                <input type="hidden" name="action" value="update_user">
                
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
                            <?php foreach ($orgaos as $orgao): ?>
                                <option value="<?= $orgao['id'] ?>" <?= ($usuario['orgao_id'] == $orgao['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($orgao['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <hr style="margin: 1.5rem 0;">
                <h4 style="margin-bottom: 1rem;">Informações Adicionais (Opcional)</h4>
                <div class="grid grid-3">
                     <div>
                        <label for="cpf_edit_<?= $usuario['id'] ?>">CPF</label>
                        <input type="text" id="cpf_edit_<?= $usuario['id'] ?>" name="cpf" value="<?= htmlspecialchars($usuario['cpf'] ?? '') ?>">
                    </div>
                    <div>
                        <label for="cargo_edit_<?= $usuario['id'] ?>">Cargo/Função</label>
                        <input type="text" id="cargo_edit_<?= $usuario['id'] ?>" name="cargo" value="<?= htmlspecialchars($usuario['cargo'] ?? '') ?>">
                    </div>
                    <div>
                        <label for="setor_edit_<?= $usuario['id'] ?>">Setor</label>
                        <input type="text" id="setor_edit_<?= $usuario['id'] ?>" name="setor" value="<?= htmlspecialchars($usuario['setor'] ?? '') ?>">
                    </div>
                </div>

                <div style="margin-top: 1.5rem;">
                    <label>Nova Senha</label>
                    <input type="password" name="senha" placeholder="Deixe em branco para não alterar">
                </div>
            </div>
            <div class="form-actions">
                <?php if ($usuario['id'] !== current_user()['id']):?>
                    <button class="btn warn btn-confirm-delete" type="submit" name="action" value="delete_user" data-confirm-message="Excluir este usuário?">Excluir</button>
                <?php endif; ?>
                <button class="btn good" type="submit" name="action" value="update_user">Atualizar Usuário</button>
            </div>
        </form>
    </div>
</div>
<?php endforeach; ?>

<?php foreach ($niveis_acesso as $nivel): ?>
<div id="editar-nivel-popup-<?= $nivel['id'] ?>" class="popup-overlay">
    <div class="popup-card card">
        <a href="#" class="popup-close">&times;</a>
        <h2>Editar Permissões: <?= htmlspecialchars($nivel['nome']) ?></h2>
        <form method="post" class="form-popup">
            <div class="popup-content">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
                <input type="hidden" name="nivel_id" value="<?= $nivel['id'] ?>">
                <input type="hidden" name="action" value="update_nivel">
                
                <div class="grid grid-3">
                    <?php
                    $grouped_permissions = [];
                    foreach ($permissoes as $permissao) {
                        $group = explode('.', $permissao['codigo'])[0];
                        $grouped_permissions[$group][] = $permissao;
                    }

                    $nivel_permissoes = $permissoes_por_nivel[$nivel['id']] ?? [];

                    foreach ($grouped_permissions as $group_name => $group_items):
                    ?>
                    <div class="permission-group">
                        <h4 class="permission-group-title"><?= htmlspecialchars(ucfirst($group_name)) ?></h4>
                        <?php foreach ($group_items as $permissao): ?>
                            <label class="permission-item">
                                <input type="checkbox" name="permissoes[]" value="<?= $permissao['id'] ?>" <?= in_array($permissao['id'], $nivel_permissoes) ? 'checked' : '' ?>>
                                <?= htmlspecialchars($permissao['descricao']) ?> (<code><?= htmlspecialchars($permissao['codigo']) ?></code>)
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="form-actions">
                <?php if (!in_array($nivel['id'], [1,2,3,4,5])):?>
                     <button class="btn warn btn-confirm-delete" type="submit" name="action" value="delete_nivel" data-confirm-message="Excluir este nível de acesso?">Excluir</button>
                <?php endif; ?>
                <button class="btn good" type="submit" name="action" value="update_nivel">Salvar Permissões</button>
            </div>
        </form>
    </div>
</div>
<?php endforeach; ?>

<?php
render_footer();
?>