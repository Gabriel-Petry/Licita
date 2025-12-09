<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';

require_login();
if (!tem_permissao('demandas.criar')) {
    header('Location: /sem_permissao');
    exit;
}

$user = current_user();
$pdo = db();

$demanda_id = (int)($_GET['id'] ?? 0);
if (empty($demanda_id)) {
    header('Location: /pca');
    exit;
}

$stmt_demanda = $pdo->prepare("SELECT * FROM demandas WHERE id = ?");
$stmt_demanda->execute([$demanda_id]);
$demanda = $stmt_demanda->fetch();

if (!$demanda || $demanda['status'] !== 'Em Análise') {
    $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Esta demanda não pode ser editada.'];
    header('Location: /demandas?pca_id=' . ($demanda['pca_id'] ?? 0));
    exit;
}

$stmt_itens = $pdo->prepare("SELECT * FROM demanda_itens WHERE demanda_id = ? ORDER BY id");
$stmt_itens->execute([$demanda_id]);
$itens = $stmt_itens->fetchAll();

render_header('Editar Demanda (DFD)');
?>

<div class="card">
    <div class="toolbar">
        <a href="/demandas?pca_id=<?= $demanda['pca_id'] ?>" class="btn btn-sm">&larr; Voltar para Demandas</a>
        <h2>Editar Demanda (DFD)</h2>
    </div>

    <form method="post" class="form-popup" id="form-nova-demanda" action="/demandas?pca_id=<?= $demanda['pca_id'] ?>">
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
                    <?php 
                        $mes_previsao = $demanda['data_previsao_licitacao'] ? date('m', strtotime($demanda['data_previsao_licitacao'])) : ''; 
                    ?>
                    <select name="mes_previsao">
                        <option value="">-- Selecione --</option>
                        <?php for ($i = 1; $i <= 12; $i++): $mes = str_pad($i, 2, '0', STR_PAD_LEFT); ?>
                            <option value="<?= $mes ?>" <?= $mes_previsao == $mes ? 'selected' : '' ?>>
                                <?= date('F', mktime(0, 0, 0, $i, 10)) ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                    <input type="hidden" name="ano_previsao" value="<?= htmlspecialchars($demanda['data_previsao_licitacao'] ? date('Y', strtotime($demanda['data_previsao_licitacao'])) : date('Y')) ?>">
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
            <div id="itens-container"></div>
            
            <button type="button" id="add-item-btn" class="btn btn-sm" style="margin-top: 1rem;">+ Adicionar Item</button>
        </div>

        <div class="form-actions">
            <button class="btn good" type="submit">Salvar Alterações</button>
        </div>
    </form>
</div>

<script>
    const existingItems = <?= json_encode($itens) ?>;
</script>

<template id="item-template">
    <div class="item-row grid" style="gap: 0.5rem; align-items: center; margin-bottom: 0.5rem; padding: 0.5rem; border: 1px solid #ddd; border-radius: 5px;">
        <input type="hidden" name="items[{i}][id]" value="0">
        
        <div style="grid-column: 1 / -1;">
             <label>Descrição do Item</label>
             <input type="text" name="items[{i}][descricao]" placeholder="Descrição do Item" required>
        </div>

        <div>
            <label>Quantidade</label>
            <input type="text" class="mask-valor" name="items[{i}][quantidade]" placeholder="Quantidade" required>
        </div>
        <div>
            <label>Unidade</label>
            <input type="text" name="items[{i}][unidade]" placeholder="Un, Kg, Cx" style="width: 100px;">
        </div>
         <div>
            <label>Valor Unitário (R$)</label>
            <input type="text" class="mask-valor" name="items[{i}][valor_unitario]" placeholder="Valor Unitário (R$)" required>
        </div>
        
        <button type="button" class="btn btn-sm warn remove-item-btn" style="align-self: end; margin-bottom: 5px;">&times;</button>
    </div>
</template>

<?php
render_footer(['scripts' => ['/js/demandas.js']]); 
?>