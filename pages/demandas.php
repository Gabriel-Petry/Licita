<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';

require_login();
$user = current_user();
$pdo = db();

$pca_id = (int)($_GET['pca_id'] ?? 0);
if (empty($pca_id)) {
    header('Location: /pca');
    exit;
}
$pca_stmt = $pdo->prepare("SELECT * FROM plano_contratacoes_anual WHERE id = ?");
$pca_stmt->execute([$pca_id]);
$plano = $pca_stmt->fetch();
if (!$plano) {
    header('Location: /pca');
    exit;
}

check_csrf();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $demanda_id = (int)($_POST['demanda_id'] ?? 0);

    try {
        if ($action === 'create_demanda') {
            if (!tem_permissao('demandas.criar')) throw new Exception('Acesso negado.');
            
            $pdo->beginTransaction();

            $data_previsao = (empty($_POST['mes_previsao']) || empty($_POST['ano_previsao'])) ? null : $_POST['ano_previsao'] . '-' . $_POST['mes_previsao'] . '-01';

            $sql_demanda = "INSERT INTO demandas (pca_id, orgao_id, usuario_id, descricao_necessidade, justificativa_contratacao, beneficios_esperados, objeto_contratacao, tipo_objeto, data_previsao_licitacao, grau_prioridade, justificativa_prioridade, vinculacao_dependencia, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Em Análise')";
            $stmt_demanda = $pdo->prepare($sql_demanda);
            $stmt_demanda->execute([
                $pca_id, $user['orgao_id'], $user['id'],
                $_POST['descricao_necessidade'], $_POST['justificativa_contratacao'], $_POST['beneficios_esperados'],
                $_POST['objeto_contratacao'], $_POST['tipo_objeto'], $data_previsao,
                $_POST['grau_prioridade'], $_POST['justificativa_prioridade'], $_POST['vinculacao_dependencia']
            ]);
            $new_demanda_id = $pdo->lastInsertId();
            
            $valor_total_estimado = 0;
            if (isset($_POST['items']) && is_array($_POST['items'])) {
                $sql_item = "INSERT INTO demanda_itens (demanda_id, descricao_item, quantidade, unidade_medida, valor_unitario_estimado) VALUES (?, ?, ?, ?, ?)";
                $stmt_item = $pdo->prepare($sql_item);
                foreach ($_POST['items'] as $item) {
                    if (empty($item['descricao']) || empty($item['quantidade']) || empty($item['valor_unitario'])) continue;
                    $quantidade = (float) str_replace(['.', ','], ['', '.'], $item['quantidade']);
                    $valor_unitario = (float) str_replace(['.', ','], ['', '.'], $item['valor_unitario']);
                    $valor_total_estimado += $quantidade * $valor_unitario;
                    $stmt_item->execute([$new_demanda_id, $item['descricao'], $quantidade, $item['unidade'], $valor_unitario]);
                }
            }
            
            $stmt_update_valor = $pdo->prepare("UPDATE demandas SET valor_total_estimado = ? WHERE id = ?");
            $stmt_update_valor->execute([$valor_total_estimado, $new_demanda_id]);
            $pdo->commit();
            $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Demanda enviada com sucesso!'];

        } elseif ($action === 'update_demanda') {
            if ($demanda_id <= 0) throw new Exception('ID da demanda inválido.');

            $pdo->beginTransaction();
            $stmt_check = $pdo->prepare("SELECT status, usuario_id FROM demandas WHERE id = ?");
            $stmt_check->execute([$demanda_id]);
            $demanda_db = $stmt_check->fetch();

            if (!$demanda_db) throw new Exception('Demanda não encontrada.');
            
            $podeEditar = ($demanda_db['usuario_id'] == $user['id'] || tem_permissao('pca.gerenciar'));
            if (!$podeEditar) {
                 throw new Exception('Acesso negado. Você não tem permissão para editar esta demanda.');
            }
            
            if ($demanda_db['status'] !== 'Em Análise') {
                 throw new Exception('Esta demanda não pode mais ser editada (Status: ' . $demanda_db['status'] . ').');
            }

            $data_previsao = (empty($_POST['mes_previsao']) || empty($_POST['ano_previsao'])) ? null : $_POST['ano_previsao'] . '-' . $_POST['mes_previsao'] . '-01';
            
            $sql_update_demanda = "UPDATE demandas SET 
                descricao_necessidade = ?, justificativa_contratacao = ?, beneficios_esperados = ?, 
                objeto_contratacao = ?, tipo_objeto = ?, data_previsao_licitacao = ?, 
                grau_prioridade = ?, justificativa_prioridade = ?, vinculacao_dependencia = ?
                WHERE id = ?";
            $stmt_update = $pdo->prepare($sql_update_demanda);
            $stmt_update->execute([
                $_POST['descricao_necessidade'], $_POST['justificativa_contratacao'], $_POST['beneficios_esperados'],
                $_POST['objeto_contratacao'], $_POST['tipo_objeto'], $data_previsao,
                $_POST['grau_prioridade'], $_POST['justificativa_prioridade'], $_POST['vinculacao_dependencia'],
                $demanda_id
            ]);

            $stmt_delete_itens = $pdo->prepare("DELETE FROM demanda_itens WHERE demanda_id = ?");
            $stmt_delete_itens->execute([$demanda_id]);

            $valor_total_estimado = 0;
            if (isset($_POST['items']) && is_array($_POST['items'])) {
                $sql_item = "INSERT INTO demanda_itens (demanda_id, descricao_item, quantidade, unidade_medida, valor_unitario_estimado) VALUES (?, ?, ?, ?, ?)";
                $stmt_item = $pdo->prepare($sql_item);
                foreach ($_POST['items'] as $item) {
                    if (empty($item['descricao']) || empty($item['quantidade']) || empty($item['valor_unitario'])) continue;
                    $quantidade = (float) str_replace(['.', ','], ['', '.'], $item['quantidade']);
                    $valor_unitario = (float) str_replace(['.', ','], ['', '.'], $item['valor_unitario']);
                    $valor_total_estimado += $quantidade * $valor_unitario;
                    $stmt_item->execute([$demanda_id, $item['descricao'], $quantidade, $item['unidade'], $valor_unitario]);
                }
            }

            $stmt_update_valor = $pdo->prepare("UPDATE demandas SET valor_total_estimado = ? WHERE id = ?");
            $stmt_update_valor->execute([$valor_total_estimado, $demanda_id]);
            
            $pdo->commit();
            $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Demanda atualizada com sucesso!'];


        } elseif ($action === 'change_status') {
            if (!tem_permissao('demandas.aprovar')) throw new Exception('Acesso negado.');
            
            $novo_status = $_POST['novo_status'] ?? '';
            if ($demanda_id > 0 && in_array($novo_status, ['Aprovada', 'Reprovada'])) {
                $stmt = $pdo->prepare("UPDATE demandas SET status = :status WHERE id = :id");
                $stmt->execute([':status' => $novo_status, ':id' => $demanda_id]);
                $_SESSION['flash_message'] = ['type' => 'success', 'text' => "Demanda {$novo_status} com sucesso!"];
            } else {
                throw new Exception('Ação inválida.');
            }
        } elseif ($action === 'delete_demanda') {
            if (!tem_permissao('pca.gerenciar')) throw new Exception('Acesso negado.');

            if ($demanda_id > 0) {
                $pdo->beginTransaction();
                $stmt_delete_itens = $pdo->prepare("DELETE FROM demanda_itens WHERE demanda_id = ?");
                $stmt_delete_itens->execute([$demanda_id]);
                $stmt_delete_demanda = $pdo->prepare("DELETE FROM demandas WHERE id = ?");
                $stmt_delete_demanda->execute([$demanda_id]);
                $pdo->commit();
                $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Demanda excluída com sucesso!'];
            } else {
                throw new Exception('ID da demanda inválido.');
            }
        }

    } catch (Exception $e) {
        if($pdo->inTransaction()) $pdo->rollBack();
        $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Erro: ' . $e->getMessage()];
    }
    header("Location: /demandas?pca_id=$pca_id");
    exit;
}

$where = ["d.pca_id = :pca_id"];
$params = [':pca_id' => $pca_id];
if ($user['orgao_id'] && !tem_permissao('demandas.ver_todas')) {
    $where[] = "d.orgao_id = :user_orgao_id";
    $params[':user_orgao_id'] = $user['orgao_id'];
}
$demandas_sql = "SELECT d.*, o.nome as orgao_nome, u.nome as usuario_nome FROM demandas d JOIN orgaos o ON d.orgao_id = o.id JOIN usuarios u ON d.usuario_id = u.id WHERE " . implode(' AND ', $where) . " ORDER BY d.data_criacao DESC";
$demandas_stmt = $pdo->prepare($demandas_sql);
$demandas_stmt->execute($params);
$demandas = $demandas_stmt->fetchAll();

render_header('Demandas do PCA ' . $plano['ano_vigencia'], ['scripts' => ['/js/demandas.js']]);
?>

<div class="card">
    <?php display_flash_message(); ?>
    <div class="toolbar">
        <div>
            <a href="/pca" class="btn btn-sm" style="margin-right: 1rem;">&larr; Voltar para Planos</a>
            <h2>Demandas do PCA <?= htmlspecialchars($plano['ano_vigencia']) ?></h2>
        </div>
        <div class="right" style="display: flex; gap: 1rem; align-items: center;">
            <?php if (tem_permissao('relatorios.gerar')): ?>
            <div class="dropdown-relatorio">
                <button type="button" class="btn">Gerar Relatório &#9662;</button>
                <div class="dropdown-relatorio-content">
                    <?php if (tem_permissao('relatorios.pca_calendario')): ?><a href="/gerar_relatorio?tipo=pca_calendario&pca_id=<?= $pca_id ?>&formato=pdf" target="_blank">Calendário (PDF)</a><a href="/gerar_relatorio?tipo=pca_calendario&pca_id=<?= $pca_id ?>&formato=excel" target="_blank">Calendário (Excel)</a><?php endif; ?>
                    <?php if (tem_permissao('relatorios.pca_dfd')): ?><a href="/gerar_relatorio?tipo=pca_dfd&pca_id=<?= $pca_id ?>&formato=pdf" target="_blank">DFDs (PDF)</a><a href="/gerar_relatorio?tipo=pca_dfd&pca_id=<?= $pca_id ?>&formato=excel" target="_blank">DFDs (Excel)</a><?php endif; ?>
                    <?php if (tem_permissao('relatorios.pca_itens')): ?><a href="/gerar_relatorio?tipo=pca_itens&pca_id=<?= $pca_id ?>&formato=pdf" target="_blank">Itens (PDF)</a><a href="/gerar_relatorio?tipo=pca_itens&pca_id=<?= $pca_id ?>&formato=excel" target="_blank">Itens (Excel)</a><?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
            <?php if (tem_permissao('demandas.criar') && $plano['status'] === 'Aberto'): ?>
                <a href="#nova-demanda-popup" class="btn primary">Nova Demanda (DFD)</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="table-scroll-container">
        <table>
            <thead><tr><th>Órgão</th><th>Objeto</th><th>Valor Estimado</th><th>Data Prevista</th><th>Status</th><th>Solicitante</th><th>Ações</th></tr></thead>
            <tbody>
                <?php if (count($demandas) > 0): foreach ($demandas as $demanda): ?>
                        <tr>
                            <td><?= htmlspecialchars($demanda['orgao_nome']) ?></td>
                            <td><?= htmlspecialchars($demanda['objeto_contratacao']) ?></td>
                            <td>R$ <?= number_format($demanda['valor_total_estimado'], 2, ',', '.') ?></td>
                            <td><?= $demanda['data_previsao_licitacao'] ? date('m/Y', strtotime($demanda['data_previsao_licitacao'])) : '--' ?></td>
                            <td><span class="chip"><?= htmlspecialchars($demanda['status']) ?></span></td>
                            <td><?= htmlspecialchars($demanda['usuario_nome']) ?></td>
                            <td>
                                <a href="#ver-demanda-<?= $demanda['id'] ?>" class="btn btn-sm">Ver Detalhes</a>
                                <?php 
                                $podeEditar = $demanda['status'] === 'Em Análise' && 
                                              ($demanda['usuario_id'] == $user['id'] || tem_permissao('pca.gerenciar'));
                                if ($podeEditar): 
                                ?>
                                    <a href="#editar-demanda-<?= $demanda['id'] ?>" class="btn btn-sm warn">Editar</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="7" class="text-center" style="padding: 2rem;">Nenhuma demanda cadastrada para este plano.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php 
$meses = [
    '01' => 'Janeiro', '02' => 'Fevereiro', '03' => 'Março', '04' => 'Abril', 
    '05' => 'Maio', '06' => 'Junho', '07' => 'Julho', '08' => 'Agosto', 
    '09' => 'Setembro', '10' => 'Outubro', '11' => 'Novembro', '12' => 'Dezembro'
];
?>

<?php if (tem_permissao('demandas.criar') && $plano['status'] === 'Aberto'): ?>
<div id="nova-demanda-popup" class="popup-overlay">
    <div class="popup-card card">
        <a href="#" class="popup-close">&times;</a>
        <h2>Nova Demanda (DFD) para o PCA <?= htmlspecialchars($plano['ano_vigencia']) ?></h2>
        
        <form method="post" class="form-popup" id="form-nova-demanda" action="/demandas?pca_id=<?= $pca_id ?>">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>"><input type="hidden" name="action" value="create_demanda">
            <div class="popup-content">
                <div class="grid grid-3">
                    <div><label>Objeto da Contratação (Resumido)</label><input name="objeto_contratacao" required></div>
                    <div><label>Tipo de Objeto</label><select name="tipo_objeto" required><option value="Bem">Bem</option><option value="Serviço">Serviço</option><option value="Obra">Obra</option><option value="Solução de TI">Solução de TI</option></select></div>
                    <div>
                        <label>Mês Previsto para Contratação</label>
                        <select name="mes_previsao">
                            <option value="">-- Selecione --</option>
                            <?php foreach ($meses as $num => $nome): ?>
                            <option value="<?= $num ?>"><?= $nome ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input type="hidden" name="ano_previsao" value="<?= htmlspecialchars($plano['ano_vigencia']) ?>">
                    </div>
                </div>

                <div class="grid grid-2" style="margin-top: 1rem;">
                    <div>
                        <label>Grau de Prioridade</label>
                        <select name="grau_prioridade" required>
                            <option value="Baixa">Baixa</option>
                            <option value="Média" selected>Média</option>
                            <option value="Alta">Alta</option>
                        </select>
                    </div>
                    <div>
                        <label>Justificativa da Prioridade</label>
                        <textarea name="justificativa_prioridade" rows="2" required></textarea>
                    </div>
                </div>
                <div><label>Descrição Detalhada do Objeto</label><textarea name="descricao_necessidade" rows="3" required></textarea></div>
                <div class="grid grid-2">
                    <div><label>Justificativa da Necessidade</label><textarea name="justificativa_contratacao" rows="3" required></textarea></div>
                    <div><label>Benefícios Esperados</label><textarea name="beneficios_esperados" rows="3" required></textarea></div>
                </div>

                <div>
                    <label>Indicação de vinculação/dependência com outra contratação</label>
                    <textarea name="vinculacao_dependencia" rows="2" required></textarea>
                </div>
                <hr style="margin: 2rem 0;">
                <h4>Itens da Demanda</h4>
                
                <div class="itens-container" style="display: flex; flex-direction: column; gap: 0.5rem;">
                    </div>
                <button type="button" class="add-item-btn btn btn-sm" style="margin-top: 1rem;">+ Adicionar Item</button>
                
            </div>
            <div class="form-actions"><button class="btn good" type="submit">Enviar Demanda</button></div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php foreach ($demandas as $demanda): 
    $itens_stmt = $pdo->prepare("SELECT * FROM demanda_itens WHERE demanda_id = ? ORDER BY id");
    $itens_stmt->execute([$demanda['id']]);
    $itens = $itens_stmt->fetchAll();
?>
<div id="ver-demanda-<?= $demanda['id'] ?>" class="popup-overlay">
    <div class="popup-card card">
        <a href="#" class="popup-close">&times;</a>
        <h2>Detalhes do DFD: <?= htmlspecialchars($demanda['objeto_contratacao']) ?></h2>
        <div class="popup-content">
            <div class="grid grid-3" style="gap: 1.5rem; margin-bottom: 1.5rem;">
                <div><strong>Órgão:</strong><br><?= htmlspecialchars($demanda['orgao_nome']) ?></div>
                <div><strong>Solicitante:</strong><br><?= htmlspecialchars($demanda['usuario_nome']) ?></div>
                <div><strong>Data da Solicitação:</strong><br><?= htmlspecialchars(date('d/m/Y', strtotime($demanda['data_criacao']))) ?></div>
            </div>
            <hr>
            
            <p><strong>Grau de Prioridade:</strong><br><?= htmlspecialchars($demanda['grau_prioridade']) ?></p>
            <p><strong>Justificativa da Prioridade:</strong><br><?= nl2br(htmlspecialchars($demanda['justificativa_prioridade'])) ?></p>
            <p><strong>Vinculação/Dependência:</strong><br><?= nl2br(htmlspecialchars($demanda['vinculacao_dependencia'] ?? 'Nenhuma')) ?></p>
            <hr>
            
            <p><strong>Descrição da Necessidade:</strong><br><?= nl2br(htmlspecialchars($demanda['descricao_necessidade'])) ?></p>
            <p><strong>Justificativa:</strong><br><?= nl2br(htmlspecialchars($demanda['justificativa_contratacao'])) ?></p>
            <p><strong>Benefícios Esperados:</strong><br><?= nl2br(htmlspecialchars($demanda['beneficios_esperados'])) ?></p>
            <hr>
            <h4>Itens Solicitados</h4>
            <div class="table-scroll-container">
                <table>
                    <thead><tr><th>Item</th><th>Qtd.</th><th>Un.</th><th>Valor Unit. (R$)</th><th>Valor Total (R$)</th></tr></thead>
                    <tbody>
                    <?php if (count($itens) > 0): foreach ($itens as $item): ?>
                        <tr>
                            <td><?= htmlspecialchars($item['descricao_item']) ?></td>
                            <td><?= number_format($item['quantidade'], 2, ',', '.') ?></td>
                            <td><?= htmlspecialchars($item['unidade_medida']) ?></td>
                            <td><?= number_format($item['valor_unitario_estimado'], 2, ',', '.') ?></td>
                            <td><?= number_format($item['quantidade'] * $item['valor_unitario_estimado'], 2, ',', '.') ?></td>
                        </tr>
                    <?php endforeach; else: ?>
                        <tr><td colspan="5" class="text-center">Nenhum item cadastrado.</td></tr>
                    <?php endif; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="4" style="text-align: right; font-weight: bold;">TOTAL:</td>
                            <td style="font-weight: bold;">R$ <?= number_format($demanda['valor_total_estimado'], 2, ',', '.') ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        
        <div class="form-actions">
            <?php if (tem_permissao('demandas.aprovar') && $demanda['status'] === 'Em Análise'): ?>
                <form method="post" class="form-confirm-submit" action="/demandas?pca_id=<?= $pca_id ?>">
                    <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
                    <input type="hidden" name="action" value="change_status">
                    <input type="hidden" name="demanda_id" value="<?= $demanda['id'] ?>">
                    <input type="hidden" name="novo_status" value="Reprovada">
                    <button type="submit" class="btn warn">Reprovar Demanda</button>
                </form>
                <form method="post" class="form-confirm-submit" action="/demandas?pca_id=<?= $pca_id ?>">
                    <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
                    <input type="hidden" name="action" value="change_status">
                    <input type="hidden" name="demanda_id" value="<?= $demanda['id'] ?>">
                    <input type="hidden" name="novo_status" value="Aprovada">
                    <button type="submit" class="btn good">Aprovar Demanda</button>
                </form>
            <?php endif; ?>

            <?php if ($demanda['status'] === 'Aprovada'): ?>
                <a href="/pages/gerar_dfd.php?demanda_id=<?= $demanda['id'] ?>" target="_blank" class="btn primary">Emitir DFD (PDF)</a>
            <?php endif; ?>
            
            <?php if (tem_permissao('pca.gerenciar')): ?>
                 <form method="post" class="form-confirm-submit" data-confirm-message="Tem a certeza que deseja excluir esta demanda? Esta ação não pode ser desfeita." action="/demandas?pca_id=<?= $pca_id ?>">
                    <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
                    <input type="hidden" name="action" value="delete_demanda">
                    <input type="hidden" name="demanda_id" value="<?= $demanda['id'] ?>">
                    <button type="submit" class="btn warn">Excluir Demanda</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php 
$podeEditar = $demanda['status'] === 'Em Análise' && 
              ($demanda['usuario_id'] == $user['id'] || tem_permissao('pca.gerenciar'));

if ($podeEditar): 
    $mes_previsao = $demanda['data_previsao_licitacao'] ? date('m', strtotime($demanda['data_previsao_licitacao'])) : '';
?>
<div id="editar-demanda-<?= $demanda['id'] ?>" class="popup-overlay">
    <div class="popup-card card">
        <a href="#" class="popup-close">&times;</a>
        <h2>Editar Demanda (DFD): <?= htmlspecialchars($demanda['objeto_contratacao']) ?></h2>
        
        <form method="post" class="form-popup" action="/demandas?pca_id=<?= $pca_id ?>">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
            <input type="hidden" name="action" value="update_demanda">
            <input type="hidden" name="demanda_id" value="<?= $demanda['id'] ?>">
            
            <div class="popup-content">
                <div class="grid grid-3">
                    <div>
                        <label>Objeto da Contratação (Resumido)</label>
                        <input name="objeto_contratacao" required value="<?= htmlspecialchars($demanda['objeto_contratacao']) ?>">
                    </div>
                    <div>
                        <label>Tipo de Objeto</label>
                        <select name="tipo_objeto" required>
                            <option value="Bem" <?= $demanda['tipo_objeto'] == 'Bem' ? 'selected' : '' ?>>Bem</option>
                            <option value="Serviço" <?= $demanda['tipo_objeto'] == 'Serviço' ? 'selected' : '' ?>>Serviço</option>
                            <option value="Obra" <?= $demanda['tipo_objeto'] == 'Obra' ? 'selected' : '' ?>>Obra</option>
                            <option value="Solução de TI" <?= $demanda['tipo_objeto'] == 'Solução de TI' ? 'selected' : '' ?>>Solução de TI</option>
                        </select>
                    </div>
                    <div>
                        <label>Mês Previsto para Contratação</label>
                        <select name="mes_previsao">
                            <option value="">-- Selecione --</option>
                            <?php foreach ($meses as $num => $nome): ?>
                            <option value="<?= $num ?>" <?= $num == $mes_previsao ? 'selected' : '' ?>><?= $nome ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input type="hidden" name="ano_previsao" value="<?= htmlspecialchars($plano['ano_vigencia']) ?>">
                    </div>
                </div>

                <div class="grid grid-2" style="margin-top: 1rem;">
                    <div>
                        <label>Grau de Prioridade</label>
                        <select name="grau_prioridade" required>
                            <option value="Baixa" <?= $demanda['grau_prioridade'] == 'Baixa' ? 'selected' : '' ?>>Baixa</option>
                            <option value="Média" <?= $demanda['grau_prioridade'] == 'Média' ? 'selected' : '' ?>>Média</option>
                            <option value="Alta" <?= $demanda['grau_prioridade'] == 'Alta' ? 'selected' : '' ?>>Alta</option>
                        </select>
                    </div>
                    <div>
                        <label>Justificativa da Prioridade</label>
                        <textarea name="justificativa_prioridade" rows="2" required><?= htmlspecialchars($demanda['justificativa_prioridade']) ?></textarea>
                    </div>
                </div>
                <div>
                    <label>Descrição Detalhada do Objeto</label>
                    <textarea name="descricao_necessidade" rows="3" required><?= htmlspecialchars($demanda['descricao_necessidade']) ?></textarea>
                </div>
                <div class="grid grid-2">
                    <div>
                        <label>Justificativa da Necessidade</label>
                        <textarea name="justificativa_contratacao" rows="3" required><?= htmlspecialchars($demanda['justificativa_contratacao']) ?></textarea>
                    </div>
                    <div>
                        <label>Benefícios Esperados</label>
                        <textarea name="beneficios_esperados" rows="3" required><?= htmlspecialchars($demanda['beneficios_esperados']) ?></textarea>
                    </div>
                </div>

                <div>
                    <label>Indicação de vinculação/dependência com outra contratação (Opcional)</label>
                    <textarea name="vinculacao_dependencia" rows="2"><?= htmlspecialchars($demanda['vinculacao_dependencia']) ?></textarea>
                </div>
                <hr style="margin: 2rem 0;">
                <h4>Itens da Demanda</h4>
                
                <div class="itens-container" style="display: flex; flex-direction: column; gap: 0.5rem;">
                    <?php
                    foreach ($itens as $index => $item): 
                    ?>
                    <div class="demanda-item" style="padding: 1rem; border: 1px solid var(--cor-borda); border-radius: var(--raio-borda); margin-bottom: 1rem;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                            <strong>Item <?= $index + 1 ?></strong>
                            <button type="button" class="btn warn btn-sm remove-item-btn">Remover</button>
                        </div>
                        <label>Descrição do Item</label>
                        <textarea name="items[<?= $index ?>][descricao]" rows="2" required><?= htmlspecialchars($item['descricao_item']) ?></textarea>
                        <div class="grid grid-3" style="margin-top: 1rem;">
                            <div>
                                <label>Quantidade</label>
                                <input type="text" name="items[<?= $index ?>][quantidade]" class="numeric-mask" value="<?= number_format($item['quantidade'], 2, ',', '.') ?>" required>
                            </div>
                            <div>
                                <label>Unidade de Medida</label>
                                <input name="items[<?= $index ?>][unidade]" value="<?= htmlspecialchars($item['unidade_medida']) ?>" required placeholder="Ex: Un, Cx, Kg...">
                            </div>
                            <div>
                                <label>Valor Unitário Estimado (R$)</label>
                                <input type="text" name="items[<?= $index ?>][valor_unitario]" class="numeric-mask" value="<?= number_format($item['valor_unitario_estimado'], 2, ',', '.') ?>" required>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <button type="button" class="add-item-btn btn btn-sm" style="margin-top: 1rem;">+ Adicionar Item</button>
            </div>
            <div class="form-actions">
                <button class="btn good" type="submit">Salvar Alterações</button>
            </div>
        </form>
    </div>
</div>
<?php 
endif;
endforeach;
?>

<?php 
render_footer(); 
?>