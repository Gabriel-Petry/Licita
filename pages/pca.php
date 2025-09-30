<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';

require_login();
if (!tem_permissao('pca.gerenciar') && !tem_permissao('demandas.criar')) {
    header('Location: /dashboard');
    exit;
}

check_csrf();
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!tem_permissao('pca.gerenciar')) {
        $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Acesso negado.'];
        header('Location: /pca');
        exit;
    }

    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'create') {
            $ano = $_POST['ano_vigencia'] ?? '';
            if (empty($ano) || !is_numeric($ano) || $ano < date('Y')) {
                throw new Exception('Ano de vigência inválido.');
            }
            $stmt = $pdo->prepare("INSERT INTO plano_contratacoes_anual (ano_vigencia) VALUES (:ano)");
            $stmt->execute([':ano' => $ano]);
            $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'PCA para o ano de ' . $ano . ' criado com sucesso!'];
        } elseif ($action === 'toggle_status') {
            $id = $_POST['id'] ?? 0;
            $stmt = $pdo->prepare("UPDATE plano_contratacoes_anual SET status = IF(status='Aberto', 'Fechado', 'Aberto') WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Status do PCA alterado com sucesso!'];
        }
    } catch (Exception $e) {
        $_SESSION['flash_message'] = ['type' => 'error', 'text' => $e->getMessage()];
    }

    header('Location: /pca');
    exit;
}

$planos = $pdo->query("SELECT * FROM plano_contratacoes_anual ORDER BY ano_vigencia DESC")->fetchAll();

render_header('Plano de Contratações Anual (PCA) - LicitAções');
?>

<div class="card">
    <?php display_flash_message(); ?>
    <div class="toolbar">
        <h2>Gestão do Plano de Contratações Anual</h2>
        <?php if (tem_permissao('pca.gerenciar')): ?>
            <a href="#novo-pca-popup" class="btn primary right">Novo PCA</a>
        <?php endif; ?>
    </div>

    <div class="table-scroll-container">
        <table>
            <thead>
                <tr>
                    <th>Ano de Vigência</th>

                    <th>Status</th>

                    <th>Data de Criação</th>

                    <th>Ações</th>
                </tr>
            </thead>

            <tbody>
                <?php if (count($planos) > 0): ?>
                    <?php foreach ($planos as $plano): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($plano['ano_vigencia']) ?></strong></td>
                            <td>
                                <span class="chip <?= $plano['status'] === 'Aberto' ? 'good' : 'warn' ?>">
                                    <?= htmlspecialchars($plano['status']) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($plano['data_criacao']))) ?></td>
                            <td>
                                <div class="pca-actions-container">
                                    <a href="/demandas?pca_id=<?= $plano['id'] ?>" class="btn btn-sm">Ver Demandas</a>
                                    <?php if (tem_permissao('pca.gerenciar')): ?>
                                        <form action="/pca" method="post">
                                            <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="id" value="<?= $plano['id'] ?>">
                                            <button type="submit" class="btn btn-sm">
                                                <?= $plano['status'] === 'Aberto' ? 'Fechar' : 'Abrir' ?>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="text-center" style="padding: 2rem;">Nenhum Plano de Contratação Anual encontrado.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if (tem_permissao('pca.gerenciar')): ?>
    <div id="novo-pca-popup" class="popup-overlay">

        <div class="popup-card card popup-small">
            <a href="#" class="popup-close">&times;</a>
            <h2>Criar Novo PCA</h2>

            <form method="post">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
                <input type="hidden" name="action" value="create">
                <label for="ano_vigencia">Ano de Vigência</label>
                <input type="number" name="ano_vigencia" min="<?= date('Y') ?>" value="<?= date('Y') + 1 ?>" required>
                <button class="btn good" type="submit" style="width: 100%; margin-top: 1rem;">Criar Plano Anual</button>
            </form>

        </div>

    </div>

<?php endif; ?>

<?php
render_footer();
?>