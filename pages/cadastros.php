<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';

require_login();
if (!tem_permissao('parametros.gerenciar')) {
    header('Location: /dashboard');
    exit;
}

check_csrf();
$pdo = db();

$tabs = [
    'orgaos' => 'Órgãos', 
    'modalidades' => 'Modalidades', 
    'status' => 'Status',
    'agentes_contratacao' => 'Agentes de Contratação',
    'responsaveis_elaboracao' => 'Responsáveis p/ Edital',
];
$tab = $_GET['t'] ?? 'orgaos';
if (!array_key_exists($tab, $tabs)) $tab = 'orgaos';

$foreign_key_map = [
    'orgaos' => 'orgao_id',
    'modalidades' => 'modalidade_id',
    'status' => 'status_id',
    'agentes_contratacao' => 'agente_contratacao_id',
    'responsaveis_elaboracao' => 'responsavel_elaboracao_id'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $tbl = $_POST['tbl'] ?? 'orgaos';

    if (!tem_permissao('parametros.gerenciar')) {
        $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Acesso negado.'];
        header('Location: /dashboard');
        exit;
    }

    if ($action === 'create') {
        $stmt = $pdo->prepare("INSERT INTO {$tbl} (nome) VALUES (:nome)");
        $stmt->execute([':nome' => $_POST['nome'] ?? '']);
    } elseif ($action === 'update') {
        $stmt = $pdo->prepare("UPDATE {$tbl} SET nome=:nome WHERE id=:id");
        $stmt->execute([':id' => $_POST['id'], ':nome' => $_POST['nome'] ?? '']);
    } elseif ($action === 'delete') {
        if (isset($foreign_key_map[$tbl])) {
            $fk_column = $foreign_key_map[$tbl];
            $update_stmt = $pdo->prepare("UPDATE licitacoes SET {$fk_column} = NULL WHERE {$fk_column} = :id");
            $update_stmt->execute([':id' => $_POST['id']]);
        }
        $stmt = $pdo->prepare("DELETE FROM {$tbl} WHERE id=:id");
        $stmt->execute([':id' => $_POST['id']]);
    }
    
    header('Location: /cadastros?t=' . urlencode($tbl));
    exit;
}
$rows = $pdo->query("SELECT id, nome FROM {$tab} ORDER BY id ASC")->fetchAll();
render_header('Cadastros - LicitAções', ['bodyClass' => 'page-cadastros']);
?>
<div class="card">
  <?php display_flash_message(); ?>
  <div class="toolbar">
    <?php foreach ($tabs as $k => $v): ?>
      <a class="btn <?= $k === $tab ? 'primary' : '' ?>" href="/cadastros?t=<?= urlencode($k) ?>"><?= htmlspecialchars($v) ?></a>
    <?php endforeach; ?>
    <a href="#novo-cadastro-popup" class="btn primary right">Novo</a>
  </div>
  <div class="table-scroll-container">
    <table>
      <thead><tr><th># (ID)</th><th>Nome</th><th style="text-align: right;">Ações</th></tr></thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td><?= $r['id'] ?></td><td><?= htmlspecialchars($r['nome']) ?></td>
            <td class="actions-cell">
              <a href="#editar-cadastro-popup-<?= $r['id'] ?>" class="btn">Editar</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<div id="novo-cadastro-popup" class="popup-overlay">
  <div class="popup-card card popup-small">
    <a href="#" class="popup-close">&times;</a>
    <h2>Novo Cadastro em "<?= htmlspecialchars($tabs[$tab]) ?>"</h2>
    <form method="post">
      <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
      <input type="hidden" name="action" value="create">
      <input type="hidden" name="tbl" value="<?= htmlspecialchars($tab) ?>">
      <label>Nome</label>
      <input name="nome" required>
      <button class="btn good" type="submit" style="width:100%; margin-top:1rem;">Salvar</button>
    </form>
  </div>
</div>

<?php foreach ($rows as $r): ?>
<div id="editar-cadastro-popup-<?= $r['id'] ?>" class="popup-overlay">
  <div class="popup-card card popup-small">
    <a href="#" class="popup-close">&times;</a>
    <h2>Editando #<?= $r['id'] ?> em "<?= htmlspecialchars($tabs[$tab]) ?>"</h2>
    <form method="post">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
        <input type="hidden" name="action" value="update">
        <input type="hidden" name="tbl" value="<?= htmlspecialchars($tab) ?>">
        <input type="hidden" name="id" value="<?= $r['id'] ?>">
        <label>Nome</label>
        <input name="nome" value="<?= htmlspecialchars($r['nome']) ?>" required>
        <div class="form-actions" style="margin-top: 1rem;">
             <button class="btn warn btn-confirm-delete" type="submit" name="action" value="delete" data-confirm-message="Excluir este registro?">Excluir</button>
             <button class="btn good" type="submit" name="action" value="update">Atualizar</button>
        </div>
    </form>
  </div>
</div>
<?php endforeach; ?>

<?php render_footer(); ?>