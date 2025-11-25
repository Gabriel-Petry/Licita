<?php

require_once __DIR__ . '/../includes/layout.php';

$user = current_user();

if (!$user) {
    header('Location: /login');
    exit;
}

if (!tem_permissao('licitacoes.ver')) {
    header('Location: /sem_permissao');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /gerar_edital.php');
    exit;
}

$objeto = htmlspecialchars($_POST['objeto'] ?? '', ENT_QUOTES, 'UTF-8');
$pregao_num = htmlspecialchars($_POST['pregao'] ?? '', ENT_QUOTES, 'UTF-8');
$processo_num = htmlspecialchars($_POST['processoadm'] ?? '', ENT_QUOTES, 'UTF-8');
$requisicao_num = htmlspecialchars($_POST['requisicao'] ?? '', ENT_QUOTES, 'UTF-8');
$pregoeiro_nome = htmlspecialchars($_POST['pregoeiro'] ?? '', ENT_QUOTES, 'UTF-8');
$valor_estimado = htmlspecialchars($_POST['valor'] ?? '0,00', ENT_QUOTES, 'UTF-8');

$modalidade_val = htmlspecialchars($_POST['modalidade'] ?? '1', ENT_QUOTES, 'UTF-8');
$srp_val = htmlspecialchars($_POST['srp-radio'] ?? '0', ENT_QUOTES, 'UTF-8');
$cj_val = htmlspecialchars($_POST['cj'] ?? '1', ENT_QUOTES, 'UTF-8');
$pref_val = htmlspecialchars($_POST['pref'] ?? '0', ENT_QUOTES, 'UTF-8');
$impref_val = '0';
$sigilo_val = '0';

$modalidade_texto = ($modalidade_val == '1') ? "Pregão Eletrônico" : "Concorrência Eletrônica";
$criterio_texto = ($cj_val == '1') ? "Menor Preço" : "Maior Desconto";
$data_hoje = date('d/m/Y');

$page_styles = ['/css/montagem.css'];
$page_scripts = ['/js/montagem.js?v=' . time()];

render_header('Montagem do Edital', ['scripts' => $page_scripts, 'styles' => $page_styles]);
?>

<div id="edital-data-store"
    data-modalidade="<?php echo $modalidade_val; ?>"
    data-srp="<?php echo $srp_val; ?>"
    data-cj="<?php echo $cj_val; ?>">
</div>

<div class="editor-container">
    <div class="editor-sidebar">
        <div class="card">
            <div class="card-header">
                <h3>Navegação</h3>
            </div>
            <div class="card-content" style="padding: 0;">
                <div class="nav-accordion">
                    <div class="nav-group">
                        <div class="nav-group-header">
                            <span class="titulo-grupo">CABEÇALHO / OBJETO</span>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="nav-group-content" style="padding: 15px; background-color: #fff;">
                            <div class="br-input small mb-2">
                                <label>Nº do Edital:</label>
                                <input type="text" value="<?php echo $pregao_num; ?>" data-live-target="view-pregao">
                            </div>
                            <div class="br-input small mb-2">
                                <label>Processo Adm.:</label>
                                <input type="text" value="<?php echo $processo_num; ?>" data-live-target="view-processo">
                            </div>
                            <div class="br-input small mb-2">
                                <label>Requisição:</label>
                                <input type="text" value="<?php echo $requisicao_num; ?>" data-live-target="view-requisicao">
                            </div>
                            <div class="br-textarea small mb-2">
                                <label>Objeto:</label>
                                <textarea rows="4" data-live-target="view-objeto"><?php echo $objeto; ?></textarea>
                            </div>
                            <div class="br-input small mb-2">
                                <label>Valor (R$):</label>
                                <input type="text" value="<?php echo $valor_estimado; ?>" data-live-target="view-valor">
                            </div>
                            <div class="br-input small mb-2">
                                <label>Pregoeiro(a):</label>
                                <input type="text" value="<?php echo $pregoeiro_nome; ?>" data-live-target="view-pregoeiro">
                            </div>
                            <div style="text-align: center; margin-top: 10px;">
                                <a href="#edital-cabecalho" class="br-button secondary small" style="width: 100%; justify-content: center;">Ir para o Texto</a>
                            </div>
                        </div>
                    </div>

                    <div class="nav-group">
                        <div class="nav-group-header">
                            <span class="titulo-grupo" data-target="edital-detalhamento">DETALHAMENTO DO OBJETO</span>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="nav-group-content" style="padding: 15px; background-color: #fff;">
                            <div style="background: #f8f9fa; padding: 10px; border: 1px solid #e9ecef; border-radius: 5px; margin-bottom: 15px;">
                                <label style="font-weight: bold; font-size: 0.9em; display: block; margin-bottom: 5px;">Modo de Execução:</label>
                                <div class="toggle-options-wrapper">
                                    <input type="radio" id="opt-item" name="input-tipo-jul" value="item" checked onchange="atualizarModoDisputa()">
                                    <label for="opt-item">Item</label>

                                    <input type="radio" id="opt-lote" name="input-tipo-jul" value="lote" onchange="atualizarModoDisputa()">
                                    <label for="opt-lote">Lote</label>

                                    <input type="radio" id="opt-global" name="input-tipo-jul" value="global" onchange="atualizarModoDisputa()">
                                    <label for="opt-global">Global</label>
                                </div>
                                <div id="div-qtd-lotes" style="display: none; border-top: 1px solid #ddd; padding-top: 10px;">
                                    <label style="font-size: 0.85em; font-weight: bold;">Qtd. de Lotes:</label>
                                    <input type="number" id="input-qtd-lotes" class="br-input small" value="1" min="1" style="width: 100%; margin-top: 5px;" oninput="gerarEstruturaLotes()">
                                </div>
                            </div>

                            <label style="font-weight: bold; color: #1351b4; display: block; margin-bottom: 5px;">Importar Planilha:</label>
                            <div class="br-input small mb-3" style="border: 1px dashed #1351b4; padding: 10px; background: #f8faff; border-radius: 4px;">
                                <input type="file" id="input-importar-planilha" accept=".xlsx, .xls, .csv" style="border: none; padding: 0; width: 100%;">
                                <div style="font-size: 0.8em; color: #666; margin-top: 5px;">
                                    Aceita Excel/CSV (Cols: Item, Descrição, Unid, Qtd)
                                </div>
                            </div>

                            <hr style="margin: 10px 0; border-color: #eee;">

                            <label style="font-weight: bold; color: #555; display: block; margin-bottom: 10px;">Adicionar Manualmente:</label>

                            <div id="div-select-lote-destino" class="mb-2" style="display: none;">
                                <label style="font-size: 0.85em;">Adicionar ao:</label>
                                <select id="select-lote-destino" class="br-input small" style="width: 100%;"></select>
                            </div>

                            <div class="row" style="gap: 5px;">
                                <div class="col" style="flex: 1;">
                                    <div class="br-input small">
                                        <input type="text" id="add-item-nr" placeholder="Nº">
                                    </div>
                                </div>
                                <div class="col" style="flex: 1;">
                                    <div class="br-input small">
                                        <input type="text" id="add-item-un" placeholder="Unid.">
                                    </div>
                                </div>
                                <div class="col" style="flex: 1;">
                                    <div class="br-input small">
                                        <input type="text" id="add-item-qtd" placeholder="Qtd.">
                                    </div>
                                </div>
                            </div>
                            <div class="br-textarea small mt-2 mb-2">
                                <textarea id="add-item-desc" rows="2" placeholder="Descrição do Item..."></textarea>
                            </div>

                            <div style="display: flex; gap: 5px;">
                                <button type="button" id="btn-add-item" class="br-button primary small" style="flex: 1;">
                                    <i class="fas fa-plus"></i> Add
                                </button>
                                <button type="button" id="btn-limpar-tabela" class="br-button secondary small">
                                    <i class="fas fa-trash"></i> Limpar
                                </button>
                            </div>

                            <div style="text-align: center; margin-top: 10px;">
                                <a href="#edital-detalhamento" class="br-button secondary small" style="width: 100%; justify-content: center;">Ir para o Texto</a>
                            </div>
                        </div>
                    </div>

                    <div class="nav-group">
                        <div class="nav-group-header">
                            <span class="titulo-grupo" data-target="edital-modo-disputa">MODO DE DISPUTA</span>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="nav-group-content" style="padding: 15px; background-color: #fff;">
                            <label style="font-weight: bold; font-size: 0.9em; display: block; margin-bottom: 10px; color: #555;">Selecione o Modo:</label>

                            <div class="toggle-options-wrapper">
                                <input type="radio" id="opt-disp-aberto" name="input-modo-disputa" value="aberto" checked onchange="atualizarTextoDisputa()">
                                <label for="opt-disp-aberto">Aberto</label>

                                <input type="radio" id="opt-disp-aberto-fechado" name="input-modo-disputa" value="aberto_fechado" onchange="atualizarTextoDisputa()">
                                <label for="opt-disp-aberto-fechado">Aberto/Fechado</label>

                                <input type="radio" id="opt-disp-fechado" name="input-modo-disputa" value="fechado" onchange="atualizarTextoDisputa()">
                                <label for="opt-disp-fechado">Fechado</label>
                            </div>

                            <div style="text-align: center; margin-top: 15px;">
                                <a href="#edital-modo-disputa" class="br-button secondary small" style="width: 100%; justify-content: center;">Ir para o Texto</a>
                            </div>
                        </div>
                    </div>

					<div class="nav-group">
                        <div class="nav-group-header">
                            <span class="titulo-grupo" data-target="edital-propostas-lances-habilitacao">PROPOSTAS E HABILITAÇÃO</span>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="nav-group-content" style="padding: 15px; background-color: #fff;">
                            <p style="font-size: 0.85em; color: #666; margin-bottom: 10px; font-style: italic;">
                                * Habilitação Jurídica e Fiscal são obrigatórias.
                            </p>

                            <details class="nested-accordion">
                                <summary>Habilitação Econômica</summary>
                                <div class="accordion-body">
                                    <label style="font-size: 0.85em; display: block; margin-bottom: 5px;">Tipo de Exigência:</label>
                                    <div class="toggle-options-wrapper">
                                        <input type="radio" id="eco-simples" name="opt-hab-eco" value="simples" onchange="atualizarHabilitacao(this)">
                                        <label for="eco-simples">Simples</label>

                                        <input type="radio" id="eco-complexa" name="opt-hab-eco" value="complexa" checked onchange="atualizarHabilitacao(this)">
                                        <label for="eco-complexa">Complexa</label>
                                    </div>
                                </div>
                            </details>

                            <details class="nested-accordion">
                                <summary>Habilitação Técnica</summary>
                                <div class="accordion-body">
                                    <label style="font-size: 0.85em; display: block; margin-bottom: 5px;">Será Exigida?</label>
                                    <div class="toggle-options-wrapper">
                                        <input type="radio" id="tec-sim" name="opt-hab-tec" value="sim" onchange="atualizarHabilitacao(this)">
                                        <label for="tec-sim">Sim</label>

                                        <input type="radio" id="tec-nao" name="opt-hab-tec" value="nao" checked onchange="atualizarHabilitacao(this)">
                                        <label for="tec-nao">Não</label>
                                    </div>

                                    <div id="tools-hab-tec" class="tools-box" style="display:none;">
                                        <label>Adicionar Cláusula:</label>
                                        <textarea id="input-txt-tec" rows="3" placeholder="Ex: A empresa deverá apresentar atestado de..."></textarea>
                                        
                                        <div class="radio-toggle-group">
                                            <label>
                                                <input type="radio" name="nivel-tec" value="paragrafo" checked> Parágrafo
                                            </label>
                                            <label>
                                                <input type="radio" name="nivel-tec" value="sub"> Subparágrafo
                                            </label>
                                        </div>

                                        <div class="btn-row">
                                            <button type="button" onclick="adicionarItemDinamico('tec', this)" class="br-button primary small" style="flex: 2;">
                                                <i class="fas fa-plus-circle"></i> Adicionar
                                            </button>
                                            <button type="button" onclick="limparItensDinamicos('tec', this)" class="br-button secondary small" style="flex: 1;" title="Limpar tudo">
                                                <i class="fas fa-eraser"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </details>

                            <details class="nested-accordion">
                                <summary>Amostras</summary>
                                <div class="accordion-body">
                                    <label style="font-size: 0.85em; display: block; margin-bottom: 5px;">Serão Exigidas?</label>
                                    <div class="toggle-options-wrapper">
                                        <input type="radio" id="amostra-sim" name="opt-hab-amostra" value="sim" onchange="atualizarHabilitacao(this)">
                                        <label for="amostra-sim">Sim</label>

                                        <input type="radio" id="amostra-nao" name="opt-hab-amostra" value="nao" checked onchange="atualizarHabilitacao(this)">
                                        <label for="amostra-nao">Não</label>
                                    </div>

                                    <div id="tools-hab-amostra" class="tools-box" style="display:none;">
                                        <label>Adicionar Cláusula:</label>
                                        <textarea id="input-txt-amostra" rows="3" placeholder="Ex: O prazo para entrega da amostra será de..."></textarea>
                                        
                                        <div class="radio-toggle-group">
                                            <label>
                                                <input type="radio" name="nivel-amostra" value="paragrafo" checked> Parágrafo
                                            </label>
                                            <label>
                                                <input type="radio" name="nivel-amostra" value="sub"> Subparágrafo
                                            </label>
                                        </div>

                                        <div class="btn-row">
                                            <button type="button" onclick="adicionarItemDinamico('amostra', this)" class="br-button primary small" style="flex: 2;">
                                                <i class="fas fa-plus-circle"></i> Adicionar
                                            </button>
                                            <button type="button" onclick="limparItensDinamicos('amostra', this)" class="br-button secondary small" style="flex: 1;" title="Limpar tudo">
                                                <i class="fas fa-eraser"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </details>

                            <div style="text-align: center; margin-top: 15px;">
                                <a href="#edital-propostas-lances-habilitacao" class="br-button secondary small" style="width: 100%; justify-content: center;">Ir para o Texto</a>
                            </div>
                        </div>
                    </div>

                    <a href="#edital-dotacao" class="nav-item">DOTAÇÃO ORÇAMENTÁRIA</a>
                    <a href="#edital-infracoes-sancoes" class="nav-item">INFRAÇÕES E SANÇÕES</a>
                    <a href="#edital-disposicoes-gerais" class="nav-item">DISPOSIÇÕES GERAIS</a>

                </div>
            </div>
        </div>

        <form id="pdf-form" method="POST" action="finalizar_pdf.php" target="_blank" style="margin-top: 1.5rem;">
            <input type="hidden" name="edital_html" id="hidden_html_content">
            <div class="card">
                <div class="card-footer form-actions" style="display: flex; justify-content: flex-end; gap: 1rem;">
                    <button class="br-button secondary" type="button" id="btn-salvar-html">Salvar HTML</button>
                    <button class="br-button primary" type="submit" id="btn-gerar-pdf">Gerar PDF</button>
                </div>
            </div>
        </form>
    </div>

    <div class="editor-preview" id="right">
        <div class="document-paper">
            <div id="edital-cabecalho">
                <p class="center bold">EDITAL DE <?php echo strtoupper($modalidade_texto); ?> N° <span id="view-pregao"><?php echo $pregao_num; ?></span></p>
                <p class="center bold">PROCESSO DIGITAL N° <span id="view-processo"><?php echo $processo_num; ?></span></p>
                <p class="center bold">REQUISIÇÃO N° <span id="view-requisicao"><?php echo $requisicao_num; ?></span></p>
                <br>
                <p><b>OBJETO:</b> <span id="view-objeto"><?php echo $objeto; ?></span></p>
                <p><b>VALOR TOTAL ESTIMADO:</b> R$ <span id="view-valor"><?php echo $valor_estimado; ?></span></p>
                <p><b>LIMITE PARA RECEBIMENTO DAS PROPOSTAS:</b><br>
                    Às 00h00min do dia 00/00/0000 até as 00h00min do dia 00/00/0000</p>
                <p><b>INÍCIO DA SESSÃO DE DISPUTA:</b><br>
                    Às 00h00min do dia 00/00/0000</p>
                <p><b>MODO DE DISPUTA:</b> Aberto</p>
                <p><b>CRITÉRIO DE JULGAMENTO:</b> <?php echo $criterio_texto; ?></p>
                <p><b>PREGOEIRA RESPONSÁVEL:</b> <span id="view-pregoeiro"><?php echo $pregoeiro_nome; ?></span></p>
                <p><b>REFERÊNCIA DE TEMPO:</b> Será observado o horário de Brasília (DF).</p>
                <p>Os documentos que integram o Edital serão disponibilizados nos seguintes locais:</p>
                <p>a) Portal Nacional de Contratações Públicas (PNCP) - https://www.gov.br/pncp/pt-br</p>
                <p>b) Portal de Compras Públicas (PCP) - https://www.portaldecompraspublicas.com.br/</p>
                <br>
                <p>O <b>MUNICÍPIO DE SAPUCAIA DO SUL</b>, inscrito no CNPJ/MF sob o n° 88.185.020/0001-25 por meio da <b>Diretoria de Compras e Licitações da Secretaria Municipal de Administração - SMA</b>, com sede no Endereço: Av. Leônidas de Souza, 1289 - Santa Catarina, Sapucaia do Sul - RS, 93210-140, torna público que realizará licitação na modalidade <b><?php echo $modalidade_texto; ?></b>, tipo por <b id="texto-tipo-julgamento"></b>, que será regido pela Lei Federal nº 14.133 de 1º de abril de 2021, pela Lei Complementar 123/2006, pela Lei Federal n° 8.078/1990 e demais legislações aplicáveis e, ainda, de acordo com as condições estabelecidas neste Edital.</p>
                <p>Conforme especificações descritas no Termo de Referência (Anexo II), o qual passa a ser parte integrante do presente edital.</p>
                <p>Fazem parte integrante deste edital:</p>
                <p>Anexo I – Estudo Técnico Preliminar (ETP);</p>
                <p>Anexo II – Termo de Referência (TR);</p>
                <p>Anexo III – Modelo de Proposta;</p>
                <p>Anexo IV – Modelo Contratual;</p>
            </div>

            <div id="edital-detalhamento" class="secao-numerada">
                <p class="bold"><span class="nr-titulo"></span>. DETALHAMENTO DO OBJETO:</p>
                <p class="subitem">Especificações e Quantidades:</p>
                <p class="subitem-3" id="view-detalhe-intro">Constitui objeto da presente licitação a contratação para o fornecimento dos seguintes ITENS, cujas descrições e condições de entrega estão detalhadas no Termo de Referência (Anexo II):</p>
                <div id="container-tabelas-itens"></div>
            </div>

            <div id="edital-credenciamento" class="secao-numerada">
                <p class="bold"><span class="nr-titulo"></span>. CREDENCIAMENTO E PARTICIPAÇÃO DO CERTAME</p>
                <p class="subitem">Para participar do certame, o licitante deve providenciar o seu credenciamento, com atribuição de chave e senha, diretamente junto ao provedor do sistema, onde deverá informar-se a respeito do seu funcionamento, regulamento e instruções para a sua correta utilização.</p>
                <p class="subitem">As instruções para o credenciamento podem ser acessadas no seguinte sítio eletrônico, qualquer dúvida, em relação ao acesso no sistema operacional, poderá ser esclarecida pelo número 3003-5455 (atendimento nacional), junto à Central de Atendimento do Portal de Compras Públicas.</p>
                <p class="subitem">É de responsabilidade do licitante, além de credenciar-se previamente no sistema eletrônico utilizado no certame e de cumprir as regras do presente edital:</p>
                <p class="subitem-3">Responsabilizar-se formalmente pelas transações efetuadas em seu nome, assumir como firmes e verdadeiras suas propostas e seus lances, inclusive os atos praticados diretamente ou por seu representante, excluída a responsabilidade do provedor do sistema ou do órgão ou entidade promotora da licitação por eventuais danos decorrentes de uso indevido da senha, ainda que por terceiros;</p>
                <p class="subitem-3">Acompanhar as operações no sistema eletrônico durante o processo licitatório e responsabilizar-se pelo ônus decorrente da perda de negócios diante da inobservância de mensagens emitidas pelo sistema ou de sua desconexão;</p>
                <p class="subitem-3">Comunicar imediatamente ao provedor do sistema qualquer acontecimento que possa comprometer o sigilo ou a inviabilidade do uso da senha, para imediato bloqueio de acesso;</p>
                <p class="subitem-3">Utilizar a chave de identificação e a senha de acesso para participar do pregão na forma eletrônica; e</p>
                <p class="subitem-3">Solicitar o cancelamento da chave de identificação ou da senha de acesso por interesse próprio.</p>
                <p class="subitem">O licitante é totalmente responsável por todas as ações (transações, propostas, lances) feitas em seu nome, incluindo as de seu representante. O órgão licitante e o provedor do sistema não se responsabilizam por danos causados pelo mau uso das credenciais de acesso, mesmo que utilizadas por terceiros.</p>
                <p class="subitem">É de responsabilidade do cadastrado conferir a exatidão dos seus dados cadastrais nos Sistemas relacionados no item anterior e mantê-los atualizados junto aos órgãos responsáveis pela informação, devendo proceder, imediatamente, à correção ou à alteração dos registros tão logo identifique incorreção ou aqueles se tornem desatualizados.</p>
                <p class="subitem">A não observância do disposto no item anterior poderá ensejar desclassificação no momento da habilitação.</p>
            </div>

            <div id="edital-vedacoes" class="secao-numerada">
                <p class="bold"><span class="nr-titulo"></span>. VEDAÇÕES</p>
                <p class="subitem">Não poderão disputar licitação ou participar da execução de contrato, direta ou indiretamente:</p>
                <p class="subitem-3">aquele que não atenda às condições deste Edital e seu(s) anexo(s);</p>
                <p class="subitem-3">sociedade que desempenhe atividade incompatível com o objeto da licitação;</p>
                <p class="subitem-3">pessoa física ou jurídica que se encontre, ao tempo da licitação, impossibilitada de participar da licitação em decorrência de sanção que lhe foi imposta;</p>
                <p class="subitem-3">aquele que mantenha vínculo de natureza técnica, comercial, econômica, financeira, trabalhista ou civil com dirigente do órgão ou entidade contratante ou com agente público que desempenhe função na licitação ou atue na fiscalização ou na gestão do contrato, ou que deles seja cônjuge, companheiro ou parente em linha reta, colateral ou por afinidade, até o terceiro grau;</p>
                <p class="subitem-3">empresas controladoras, controladas ou coligadas, nos termos da Lei nº 6.404, de 15 de dezembro de 1976, concorrendo entre si;</p>
                <p class="subitem-3">pessoa física ou jurídica que, nos 5 (cinco) anos anteriores à divulgação do edital, tenha sido condenada judicialmente, com trânsito em julgado, por exploração de trabalho infantil, por submissão de trabalhadores a condições análogas às de escravo ou por contratação de adolescentes nos casos vedados pela legislação trabalhista;</p>
                <p class="subitem"-3>agente público do órgão ou entidade contratante, devendo ser observadas as situações que possam configurar conflito de interesses no exercício ou após o exercício do cargo ou emprego, nos termos da legislação que disciplina a matéria, conforme § 1º do art. 9º da Lei nº 14.133, de 2021.</p>
                <p class="subitem">O impedimento de que trata a alínea “a” do item 3.1, supra, será também aplicado ao licitante que atue em substituição a outra pessoa, física ou jurídica, com o intuito de burlar a efetividade da sanção a ela aplicada, inclusive a sua controladora, controlada ou coligada, desde que devidamente comprovado o ilícito ou a utilização fraudulenta da personalidade jurídica do licitante.</p>
                <p class="subitem">O impedimento de que trata a alínea “c” será também aplicado ao licitante que atue em substituição a outra pessoa, física ou jurídica, com o intuito de burlar a efetividade da sanção a ela aplicada, inclusive a sua controladora, controlada ou coligada, desde que devidamente comprovado o ilícito ou a utilização fraudulenta da personalidade jurídica do licitante.</p>
                <p class="subitem">A vedação de que trata a alínea “g” estende-se a terceiro que auxilie a condução da contratação na qualidade de integrante de equipe de apoio, profissional especializado ou funcionário ou representante de empresa que preste assessoria técnica.</p>
                <p class="subitem">Durante a vigência do contrato, é vedado ao contratado contratar cônjuge, companheiro ou parente em linha reta, colateral ou por afinidade, até o terceiro grau, de dirigente do órgão contratante ou de agente público que desempenhe função na licitação ou atue na fiscalização ou na gestão do contrato.</p>
            </div>

            <div id="edital-abertura" class="secao-numerada">
                <p class="bold"><span class="nr-titulo"></span>. ABERTURA DA SESSÃO PÚBLICA</p>
                <p class="subitem">No dia e hora indicados no preâmbulo, o pregoeiro abrirá a sessão pública, mediante a utilização de sua chave e senha.</p>
                <p class="subitem">O licitante poderá participar da sessão pública na internet, mediante a utilização de sua chave de acesso e senha, e deverá acompanhar o andamento do certame e as operações realizadas no sistema eletrônico durante toda a sessão pública do pregão, ficando responsável pela perda de negócios diante da inobservância de mensagens emitidas pelo sistema ou de sua desconexão, conforme item 2.3.2 deste Edital.</p>
                <p class="subitem">A comunicação entre o pregoeiro e os licitantes ocorrerá mediante troca de mensagens em campo próprio do sistema eletrônico.</p>
                <p class="subitem">Iniciada a sessão, as propostas de preços contendo a descrição do objeto e do valor estarão disponíveis na internet.</p>
            </div>

            <div id="edital-modo-disputa" class="secao-numerada">
                <p class="bold"><span class="nr-titulo"></span>. MODO DE DISPUTA</p>

                <p class="subitem" id="md-intro">Será adotado o modo de disputa aberto, em que os licitantes apresentarão lances públicos e sucessivos, observando as regras constantes no item 7.</p>
                <p class="subitem" id="md-p1">A etapa competitiva, de envio de lances na sessão pública, durará 10 (dez) minutos e, após isso, será prorrogada automaticamente pelo sistema quando houver lance ofertado nos últimos dois minutos do período de duração da sessão pública.</p>
                <p class="subitem" id="md-p2">A prorrogação automática da etapa de envio de lances será de dois minutos e ocorrerá sucessivamente sempre que houver lances enviados nesse período de prorrogação, inclusive quando se tratar de lances intermediários.</p>
                <p class="subitem" id="md-p3">Na hipótese de não haver novos lances, a sessão pública será encerrada automaticamente.</p>
                <p class="subitem" id="md-p4">Encerrada a sessão pública sem prorrogação automática pelo sistema, o pregoeiro poderá, assessorado pela equipe de apoio, admitir o reinício da etapa de envio de lances, em prol da consecução do melhor preço, mediante justificativa.</p>
                <p class="subitem" id="md-p5"></p>
                <p class="subitem" id="md-p6"></p>
                <p class="subitem">Na hipótese de o sistema eletrônico desconectar para o pregoeiro no decorrer da etapa de envio de lances da sessão pública e permanecer acessível aos licitantes, os lances continuarão sendo recebidos, sem prejuízo dos atos realizados.</p>
                <p class="subitem">Quando a desconexão do sistema eletrônico para o pregoeiro persistir por tempo superior a 10 (dez) minutos, a sessão pública será suspensa e reiniciada somente decorridas 24 (vinte e quatro horas) após a comunicação do fato aos participantes, no sítio eletrônico www.portaldecompraspublicas.com.br.</p>
            </div>

            <div id="edital-envio-propostas" class="secao-numerada">
                <p class="bold"><span class="nr-titulo"></span>. ENVIO DAS PROPOSTAS</p>
                <p class="subitem">O prazo de validade da proposta será de 60 dias, a contar da data de abertura da sessão do pregão, estabelecida no preâmbulo desse edital.</p>
                <p class="subitem">Os licitantes deverão registrar suas propostas no sistema eletrônico, observando as diretrizes do Modelo de Proposta Comercial, com a indicação completa do produto ofertado, incluindo marca, modelo, referências e demais dados técnicos, bem como com a indicação dos valores unitários e totais dos itens, englobando a tributação, os custos de entrega e quaisquer outras despesas incidentes para o cumprimento das obrigações assumidas.</p>
                <p class="subitem">Qualquer elemento que possa identificar o licitante importará na desclassificação da proposta, razão pela qual os licitantes não poderão encaminhar documentos com timbre ou logomarca da empresa, assinatura ou carimbo de sócios ou outra informação que possa levar a sua identificação, até que se encerre a etapa de lances.</p>
                <p class="subitem">As propostas e os documentos de habilitação deverão ser enviados exclusivamente por meio do sistema eletrônico:</p>
                <p class="subitem-3">As propostas deverão ser anexadas ao sistema até a data e horário estabelecidos no preâmbulo deste edital, observando os itens 2 e 6, e poderão ser retiradas ou substituídas até a abertura da sessão pública;</p>
                <p class="subitem-3">Os documentos de habilitação do arrematante de cada item poderão ser enviados após a fase de lances ou quando o Agente de Contratação/Pregoeiro os solicitar em campo próprio do sistema do Portal de Compras Públicas, na fase de habilitação.</p>
                <p class="subitem-3">O licitante deverá declarar, em campo próprio do sistema, sendo que a falsidade da declaração sujeitará o licitante às sanções legais:</p>
                <p class="subitem-4">O cumprimento dos requisitos para a habilitação e a conformidade de sua proposta com as exigências do edital, respondendo o declarante pela veracidade das suas informações, na forma da lei;</p>
                <p class="subitem-4">Que cumpre as exigências de reserva de cargos para pessoa com deficiência e para reabilitado da Previdência Social, previstas em lei e em outras normas específicas.</p>
                <p class="subitem-4">O cumprimento dos requisitos legais para a qualificação como microempresa ou empresa de pequeno porte, microempreendedor individual, produtor rural pessoa física, agricultor familiar ou sociedade cooperativa de consumo, se for o caso, estando apto a usufruir do tratamento favorecido estabelecido nos arts. 42 ao 49 da Lei Complementar nº 123 de 14 de dezembro de 2006, como condição para aplicação do disposto neste edital.</p>
                <p class="subitem-4">Declaração de observância do limite de R$ 4.800.000,00 na licitação, limitada às microempresas e às empresas de pequeno porte que, no ano-calendário de realização da licitação, ainda não tenham celebrado contratos com a Administração Pública cujos valores somados extrapolem a receita bruta máxima admitida para fins de enquadramento como empresa de pequeno porte.</p>
                <p class="subitem-4">Que suas propostas econômicas compreendem a integralidade dos custos para atendimento dos direitos trabalhistas assegurados na Constituição Federal, nas leis trabalhistas, nas normas infralegais, nas convenções coletivas de trabalho e nos termos de ajustamento de conduta vigentes na data de entrega das propostas.</p>
                <p class="subitem-4">Que não possui em seu quadro cônjuge, companheiro ou parente em linha reta, colateral ou por afinidade, até o terceiro grau, de dirigente do órgão contratante ou de agente público que desempenhe função na licitação ou atue na fiscalização ou na gestão do contrato. Em conformidade com o Inciso IV do artigo 14 da Lei Federal 14.133/2021.</p>
                <p class="subitem-3">Outros eventuais documentos complementares à proposta e à habilitação, que venham a ser solicitados pelo pregoeiro, deverão ser encaminhados no prazo máximo de 2 (dois) dias.</p>
            </div>

            <div id="edital-propostas-lances-habilitacao" class="secao-numerada">
                <p class="bold"><span class="nr-titulo"></span>. DAS PROPOSTAS, FORMULAÇÃO DE LANCES E HABILITAÇÃO</p>
                <p class="subitem">O pregoeiro verificará as propostas apresentadas e desclassificará fundamentadamente aquelas que não estejam em conformidade com os requisitos estabelecidos no edital.</p>
                <p class="subitem">Serão desclassificadas as propostas que:</p>
                <p class="subitem-3">contiverem vícios insanáveis;</p>
                <p class="subitem-3">não obedecerem às especificações técnicas pormenorizadas no edital;</p>
                <p class="subitem-3">apresentarem preços inexequíveis ou permanecerem acima do orçamento estimado para a contratação;</p>
                <p class="subitem-3">não tiverem sua exequibilidade demonstrada, quando exigido pela Administração;</p>
                <p class="subitem-3">apresentarem desconformidade com quaisquer outras exigências do edital, desde que insanável.</p>
                <p class="subitem">A verificação da conformidade das propostas poderá ser feita exclusivamente em relação à proposta mais bem classificada.</p>
                <p class="subitem">Quaisquer inserções na proposta que visem modificar, extinguir ou criar direitos, sem previsão no edital, serão tidas como inexistentes, aproveitando-se a proposta no que não for conflitante com o instrumento convocatório.</p>
                <p class="subitem">As propostas classificadas serão ordenadas pelo sistema e o pregoeiro dará início à fase competitiva, oportunidade em que os licitantes poderão encaminhar lances exclusivamente por meio do sistema eletrônico.</p>
                <p class="subitem">Somente poderão participar da fase competitiva os autores das propostas classificadas.</p>
                <p class="subitem">Os licitantes poderão oferecer lances sucessivos e serão informados, em tempo real, do valor do menor lance registrado, vedada a identificação do seu autor, observando o horário fixado para duração da etapa competitiva, e as seguintes regras:</p>
                <p class="subitem-3">O licitante será imediatamente informado do recebimento do lance e do valor consignado no registro.</p>
                <p class="subitem-3">O licitante somente poderá oferecer valor inferior ao último lance por ele ofertado e registrado pelo sistema.</p>
                <p class="subitem-3">Não serão aceitos dois ou mais lances iguais e prevalecerá aquele que for recebido e registrado primeiro.</p>
                <p class="subitem-3">O intervalo mínimo de diferença de valores entre os lances será de R$0,01, que incidirá tanto em relação aos lances intermediários, quanto em relação do lance que cobrir a melhor oferta.</p>
                <p class="subitem-3">Serão considerados intermediários os lances iguais ou superiores ao menor já ofertado;</p>
                <p class="subitem-3">Após a definição da melhor proposta, se a diferença em relação à proposta classificada em segundo lugar for de pelo menos 5% (cinco por cento), a Administração poderá admitir o reinício da disputa aberta, para a definição das demais colocações.</p>
                <p class="subitem">A Administração poderá realizar diligências para aferir a exequibilidade das propostas ou exigir dos licitantes que ela seja demonstrada.</p>
                <p class="subitem">Os licitantes poderão retirar ou substituir a proposta ou, na hipótese de a fase de habilitação anteceder as fases de apresentação de propostas e lances e de julgamento, os documentos de habilitação anteriormente inseridos no sistema, até a abertura da sessão pública.</p>
                <p class="subitem">Caberá ao licitante interessado em participar da licitação acompanhar as operações no sistema eletrônico durante o processo licitatório e se responsabilizar pelo ônus decorrente da perda de negócios diante da inobservância de mensagens emitidas pela Administração ou de sua desconexão.</p>
                <p class="subitem">O licitante deverá comunicar imediatamente ao provedor do sistema qualquer acontecimento que possa comprometer o sigilo ou a segurança, para imediato bloqueio de acesso.</p>
                <p class="subitem">Não haverá ordem de classificação na etapa de apresentação da proposta e dos documentos de habilitação pelo licitante, o que ocorrerá somente após os procedimentos de abertura da sessão pública e da fase de envio de lances.</p>
                <p class="subitem">Para fins de habilitação neste pregão, a licitante deverá enviar os seguintes documentos, observando o procedimento disposto no item 5 deste Edital:</p>
                <p class="subitem-3">A documentação exigida para fins de habilitação jurídica, fiscal, social e trabalhista e econômico-financeira, poderá ser substituída pelo registro cadastral no SICAF.</p>

                <p class="bold subitem-3" id="hab-juridica">HABILITAÇÃO JURÍDICA</p>
                <p class="subitem-4">cópia do registro comercial, no caso de empresa individual;</p>
                <p class="subitem-4">cópia do ato constitutivo, estatuto ou contrato social em vigor, devidamente registrado, em se tratando de sociedades comerciais, e, no caso de sociedade por ações, acompanhado de documentos de eleição de seus administradores;</p>
                <p class="subitem-4">cópia do decreto de autorização, em se tratando de empresa ou sociedade estrangeira em funcionamento no País, e ato de registro ou autorização para funcionamento expedido pelo órgão competente, quando a atividade assim o exigir.</p>
                <p class="subitem-4">As empresas estrangeiras que não funcionem no País deverão apresentar documentos equivalentes, na forma de regulamento previsto no art. 70, parágrafo único, da Lei Federal nº 14.133/2021.</p>

                <p class="bold subitem-3" id="hab-fiscal">HABILITAÇÃO FISCAL, SOCIAL E TRABALHISTA</p>
                <p class="subitem-4">comprovante de inscrição no Cadastro Nacional de Pessoa Jurídica (CNPJ);</p>
                <p class="subitem-4">comprovante de inscrição no cadastro de contribuintes estadual e/ou municipal, se houver, relativo ao domicílio ou sede do licitante, pertinente ao seu ramo de atividade e compatível com o objeto contratual;</p>
                <p class="subitem-4">prova de regularidade perante a Fazenda federal, estadual e/ou municipal do domicílio ou sede do licitante, ou outra equivalente, na forma da lei;</p>
                <p class="subitem-4">prova de regularidade relativa à Seguridade Social e ao FGTS, que demonstre cumprimento dos encargos sociais instituídos por lei;</p>
                <p class="subitem-4">prova de regularidade perante a Justiça do Trabalho;</p>
                <p class="subitem-4">declaração de cumprimento do disposto no inciso XXXIII do art. 7º da Constituição Federal.</p>
                <p class="subitem-4">não possui empregados executando trabalho degradante ou forçado, observando o disposto nos incisos III e IV do art. 1º e no inciso III do art. 5º da Constituição Federal;</p>

                <p class="bold subitem-3">HABILITAÇÃO ECONÔMICO-FINANCEIRA</p>
                
                <div id="texto-eco-complexa">
                    <p class="subitem-4">balanço patrimonial, demonstração de resultado de exercício e demais demonstrações contábeis dos 2 (dois) últimos exercícios sociais;</p>
                    <p class="subitem-4">certidão negativa de falência expedida pelo distribuidor da sede da pessoa jurídica, em prazo não superior a 30 (trinta) dias da data designada para a apresentação do documento;</p>
                    <p class="subitem-4">para comprovação da boa situação financeira da empresa, serão apurados índices mínimos aceitáveis, pela aplicação das seguintes formulas:</p>

                    <div style="margin-left: 40px; margin-top: 15px; margin-bottom: 15px;">
                        <table style="border-collapse: collapse;">

                            <tr>
                                <td style="font-weight: bold; padding-right: 10px; white-space: nowrap;">LIQUIDEZ CORRENTE:</td>
                                <td style="text-align: center; vertical-align: middle;">
                                    <div style="display: flex; align-items: center; justify-content: center;">
                                        <div style="display: flex; flex-direction: column; align-items: center;">
                                            <div style="border-bottom: 1px solid #000; padding: 1px 5px;">AC</div>
                                            <div style="padding: 1px 5px;">PC</div>
                                        </div>
                                        <div style="padding-left: 10px;">
                                            = índice mínimo: (1)
                                        </div>
                                    </div>
                                </td>
                            </tr>

                            <tr>
                                <td style="font-weight: bold; padding-right: 10px; white-space: nowrap;">LIQUIDEZ GERAL:</td>
                                <td style="text-align: center; vertical-align: middle;">
                                    <div style="display: flex; align-items: center; justify-content: center;">
                                        <div style="display: flex; flex-direction: column; align-items: center;">
                                            <div style="border-bottom: 1px solid #000; padding: 1px 5px;">AC + ARLP</div>
                                            <div style="padding: 1px 5px;">PC + PELP</div>
                                        </div>
                                        <div style="padding-left: 10px;">
                                            = índice mínimo: (1)
                                        </div>
                                    </div>
                                </td>
                            </tr>

                            <tr>
                                <td style="font-weight: bold; padding-right: 10px; white-space: nowrap;">GRAU DE ENDIVIDAMENTO:</td>
                                <td style="text-align: center; vertical-align: middle;">
                                    <div style="display: flex; align-items: center; justify-content: center;">
                                        <div style="display: flex; flex-direction: column; align-items: center;">
                                            <div style="border-bottom: 1px solid #000; padding: 1px 5px;">PC + PELP</div>
                                            <div style="padding: 1px 5px;">AT</div>
                                        </div>
                                        <div style="padding-left: 10px;">
                                            = índice máximo: (1)
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <p class="subitem-4" style="margin-top: 10px;">Onde: AC = Ativo Circulante; AD = Ativo Disponível; ARLP = Ativo Realizável a Longo Prazo; AP = Ativo Permanente; AT = Ativo Total; PC = Passivo Circulante; PELP = Passivo Exigível a Longo Prazo; PL = Patrimônio Líquido.</p>
                </div>
                
                <div id="texto-eco-simples" style="display: none;">
                    <p class="subitem-4">certidão negativa de falência expedida pelo distribuidor da sede da pessoa jurídica.</p>
                </div>


				<p class="bold subitem-3">HABILITAÇÃO TÉCNICA</p>
                
                <div id="texto-tec-sim" style="display: none;">
                     <div id="lista-tec-dinamica"></div>
                </div>
                
                <div id="texto-tec-nao">
                    <p class="subitem-4">Não será exigida comprovação de qualificação técnica para este certame.</p>
                </div>


                <p class="bold subitem-3">DAS AMOSTRAS</p>
                
                <div id="texto-amostra-sim" style="display: none;">
                    <div id="lista-amostra-dinamica"></div>
                </div>
                
                <div id="texto-amostra-nao">
                    <p class="subitem-4">Não será exigida a apresentação de amostras.</p>
                </div>

            <div id="edital-negociacao-julgamento" class="secao-numerada">
                <p class="bold"><span class="nr-titulo"></span>. NEGOCIAÇÃO E JULGAMENTO</p>
                <p class="subitem">Encerrada a etapa de envio de lances da sessão pública, inclusive com a realização do desempate, se for o caso, o pregoeiro deverá encaminhar, pelo sistema eletrônico, contraproposta ao licitante que tenha apresentado o melhor preço, para que seja obtida melhor proposta.</p>
                <p class="subitem">A resposta à contraproposta e o envio de documentos complementares, necessários ao julgamento da aceitabilidade da proposta, inclusive a sua adequação ao último lance ofertado, que sejam solicitados pelo pregoeiro, deverão ser encaminhados no prazo fixado no item 6.4.4 deste Edital.</p>
                <p class="subitem">Encerrada a etapa de negociação, será examinada a proposta classificada em primeiro lugar quanto à adequação ao objeto e à compatibilidade do preço em relação valor de referência da Administração.</p>
                <p class="subitem">Não serão consideradas, para julgamento das propostas, vantagens não previstas no edital.</p>
            </div>

            <div id="edital-criterios-desempate" class="secao-numerada">
                <p class="bold"><span class="nr-titulo"></span>. CRITÉRIOS DE DESEMPATE</p>
                <p class="subitem">Encerrada etapa de envio de lances, será apurada a ocorrência de empate, nos termos dos Arts. 44 e 45 da Lei Complementar nº 123/2006, sendo assegurada, como critério do desempate, preferência de contratação para as beneficiárias que tiverem apresentado as declarações de que tratam os itens 6.4.3.3 e 6.3.3.4 deste Edital;</p>
                <p class="subitem-3">Entende-se como empate, para fins da Lei Complementar nº 123/2006, aquelas situações em que as propostas apresentadas pelas beneficiárias sejam iguais ou superiores em até 5% (cinco por cento) à proposta de menor valor.</p>
                <p class="subitem-3">Ocorrendo o empate, na forma do subitem anterior, proceder-se-á da seguinte forma:</p>
                <p class="subitem-4">A beneficiária detentora da proposta de menor valor será convocada via sistema para apresentar, no prazo de 5 (cinco) minutos, nova proposta, inferior àquela considerada, até então, de menor preço, situação em que será declarada vencedora do certame.</p>
                <p class="subitem-4">Se a beneficiária, convocada na forma da alínea anterior, não apresentar nova proposta, inferior à de menor preço, será facultada, pela ordem de classificação, às demais microempresas, empresas de pequeno porte ou cooperativas remanescentes, que se enquadrarem na hipótese do item 9.1. deste edital, a apresentação de nova proposta, no prazo previsto na alínea a deste item.</p>
                <p class="subitem-3">O disposto no item 9.1.1. não se aplica às hipóteses em que a proposta de menor valor inicial tiver sido apresentado por beneficiária da Lei Complementar nº 123/2006.</p>
                <p class="subitem">Se não houver licitante que atenda ao item 9.1 e seus subitens, serão utilizados os seguintes critérios de desempate, nesta ordem:</p>
                <p class="subitem-3">disputa final, hipótese em que os licitantes empatados poderão apresentar nova proposta em ato contínuo à classificação;</p>
                <p class="subitem-3">avaliação do desempenho contratual prévio dos licitantes, para a qual serão ser utilizados registros cadastrais para efeito de atesto de cumprimento de obrigações decorrentes de outras contratações;</p>
                <p class="subitem-3">desenvolvimento pelo licitante de programa de integridade, conforme orientações dos órgãos de controle.</p>
                <p class="subitem">Em igualdade de condições, se não houver desempate, será assegurada preferência, sucessivamente, aos bens e serviços produzidos ou prestados por:</p>
                <p class="subitem-3">empresas estabelecidas no território do Estado do Rio Grande do Sul;</p>
                <p class="subitem-3">empresas brasileiras;</p>
                <p class="subitem-3">empresas que invistam em pesquisa e no desenvolvimento de tecnologia no País;</p>
                <p class="subitem-3">empresas que comprovem a prática de mitigação, nos termos da Lei nº 12.187, de 29 de dezembro de 2009.</p>
            </div>

            <div id="edital-esclarecimentos-impugnacoes" class="secao-numerada">
                <p class="bold"><span class="nr-titulo"></span>. PEDIDOS DE ESCLARECIMENTOS E IMPUGNAÇÕES</p>
                <p class="subitem">Os pedidos de esclarecimentos referentes ao processo licitatório e os pedidos de impugnações poderão ser enviados ao pregoeiro, até três dias úteis anteriores à data fixada para abertura da sessão pública, por meio do seguinte endereço eletrônico: www.portaldecompraspublicas.com.br.</p>
                <p class="subitem">As respostas aos pedidos de esclarecimentos e às impugnações serão divulgadas no seguinte sítio eletrônico da Administração: www.portaldecompraspublicas.com.br.</p>
            </div>

            <div id="edital-verificacao-habilitacao" class="secao-numerada">
                <p class="bold"><span class="nr-titulo"></span>. VERIFICAÇÃO DA HABILITAÇÃO</p>
                <p class="subitem">Os documentos de habilitação, de que tratam os itens 7.13.2, 7.13.3, 7.13.4, 7.13.5 e 7.13.6, enviados nos termos do item 6.4, todos deste edital, serão examinados pelo pregoeiro, que verificará a autenticidade das certidões junto aos sítios eletrônicos oficiais de órgãos e entidades emissores.</p>
                <p class="subitem">As certidões apresentadas na habilitação, que tenham sido expedidas em meio eletrônico, serão tidas como originais após terem a autenticidade de seus dados e certificação digital conferidos pela Administração, dispensando nova apresentação, exceto se vencido o prazo de validade.</p>
                <p class="subitem">A prova de autenticidade de cópia de documento público ou particular poderá ser feita perante agente da Administração, mediante apresentação de original ou de declaração de autenticidade por advogado, sob sua responsabilidade pessoal.</p>
                <p class="subitem">A beneficiária da Lei Complementar nº 123/2006, que tenha apresentado a declaração exigida no item 6.4.3.3 e 6.4.3.4 deste Edital e que possua alguma restrição na comprovação de regularidade fiscal e/ou trabalhista, terá sua habilitação condicionada ao envio de nova documentação, que comprove a sua regularidade, em 5 (cinco) dias úteis, prazo que poderá ser prorrogado uma única vez, por igual período, a critério da Administração, desde que seja requerido pelo interessado, de forma motivada e durante o transcurso do respectivo prazo.</p>
                <p class="subitem">Na hipótese de a proposta vencedora não for aceitável ou o licitante não atender às exigências para habilitação, o pregoeiro examinará a proposta subsequente e assim sucessivamente, na ordem de classificação, até a apuração de uma proposta que atenda ao edital.</p>
                <p class="subitem">Constatado o atendimento às exigências estabelecidas no Edital, o licitante será declarado vencedor, oportunizando-se a manifestação da intenção de recurso.</p>
            </div>

            <div id="edital-recurso" class="secao-numerada">
                <p class="bold"><span class="nr-titulo"></span>. RECURSO</p>
                <p class="subitem">Caberá recurso, no prazo de 3 (três) dias úteis, contado da data de intimação ou de lavratura da ata, em face de:</p>
                <p class="subitem-3">ato que defira ou indefira pedido de pré-qualificação de interessado ou de inscrição em registro cadastral, sua alteração ou cancelamento;</p>
                <p class="subitem-3">julgamento das propostas;</p>
                <p class="subitem-3">ato de habilitação ou inabilitação de licitante;</p>
                <p class="subitem-3">anulação ou revogação da licitação.</p>
                <p class="subitem">O prazo para apresentação de contrarrazões será o mesmo do recurso e terá início na data de intimação pessoal ou de divulgação da interposição do recurso.</p>
                <p class="subitem">Quanto ao recurso apresentado em virtude do disposto nas alíneas “b” e “c” do item 13.1 do presente Edital, serão observadas as seguintes disposições:</p>
                <p class="subitem-3">a intenção de recorrer deverá ser manifestada imediatamente, sob pena de preclusão, e o prazo para apresentação das razões recursais será iniciado na data de intimação ou de lavratura da ata de habilitação ou inabilitação;</p>
                <p class="subitem-3">a apreciação dar-se-á em fase única.</p>
                <p class="subitem">O recurso será dirigido à autoridade que tiver editado o ato ou proferido a decisão recorrida, que, se não reconsiderar o ato ou a decisão no prazo de 3 (três) dias úteis, encaminhará o recurso com a sua motivação à autoridade superior, a qual deverá proferir sua decisão no prazo máximo de 10 (dez) dias úteis, contado do recebimento dos autos.</p>
                <p class="subitem">O acolhimento do recurso implicará invalidação apenas de ato insuscetível de aproveitamento.</p>
                <p class="subitem">O recurso interposto dará efeito suspensivo ao ato ou à decisão recorrida, até que sobrevenha decisão final da autoridade competente.</p>
            </div>

            <div id="edital-encerramento" class="secao-numerada">
                <p class="bold"><span class="nr-titulo"></span>. ENCERRAMENTO DA LICITAÇÃO</p>
                <p class="subitem">Encerradas as fases de julgamento e habilitação, e exauridos os recursos administrativos, o processo licitatório será encaminhado à autoridade superior, que poderá:</p>
                <p class="subitem-3">determinar o retorno dos autos para saneamento de irregularidades;</p>
                <p class="subitem-3">revogar a licitação por motivo de conveniência e oportunidade;</p>
                <p class="subitem-3">proceder à anulação da licitação, de ofício ou mediante provocação de terceiros, sempre que presente ilegalidade insanável;</p>
                <p class="subitem-3">adjudicar o objeto e homologar a licitação.</p>
            </div>

            <div id="edital-adjudicacao-homologacao" class="secao-numerada">
                <p class="bold"><span class="nr-titulo"></span>. DA ADJUDICAÇÃO E HOMOLOGAÇÃO</p>
                <p class="subitem">Encerradas as fases de julgamento e habilitação, e exauridos os recursos administrativos, o processo licitatório será encaminhado à autoridade superior para adjudicar o objeto e homologar o procedimento, observado o disposto no art. 71 da Lei nº 14.133, de 2021.</p>
            </div>

            <div id="edital-dotacao" class="secao-numerada">
                <p class="bold"><span class="nr-titulo"></span>. DOTAÇÃO ORÇAMENTÁRIA:</p>
                <p class="subitem">O dispêndio financeiro decorrente da contratação ora pretendido decorrerá da(s) dotação(ões) orçamentária(s):</p>
            </div>

            <div id="edital-contratacao" class="secao-numerada">
                <p class="bold"><span class="nr-titulo"></span>. DA CONTRATAÇÃO</p>
                <p class="subitem">O prazo de vigência da contratação é o estabelecido em Minuta Contratual.</p>
                <p class="subitem">O licitante vencedor será convocado para assinar o termo de contrato ou para aceitar ou retirar o instrumento equivalente, dentro do prazo de 05 (cinco) dias, sob pena de decair o direito à contratação, sem prejuízo das sanções previstas neste Edital.</p>
                <p class="subitem">O prazo de convocação poderá ser prorrogado 1 (uma) vez, por igual período, mediante solicitação da parte, durante seu transcurso, devidamente justificada, e desde que o motivo apresentado seja aceito pela Administração.</p>
                <p class="subitem">Será facultado à Administração, quando o convocado não assinar o termo de contrato ou não aceitar ou não retirar o instrumento equivalente no prazo e nas condições estabelecidas neste Edital, convocar os licitantes remanescentes, na ordem de classificação, para a celebração do contrato nas condições propostas pelo licitante vencedor.</p>
                <p class="subitem">Decorrido o prazo de validade da proposta indicado no item 6.1 deste Edital, sem convocação para a contratação, ficarão os licitantes liberados dos compromissos assumidos.</p>
                <p class="subitem">Na hipótese de nenhum dos licitantes aceitar a contratação, nos termos do 16 deste Edital, a Administração, observados o valor estimado e sua eventual atualização nos termos do edital, poderá:</p>
                <p class="subitem-3">convocar os licitantes remanescentes para negociação, na ordem de classificação, com vistas à obtenção de preço melhor, mesmo que acima do preço do adjudicatário;</p>
                <p class="subitem-3">adjudicar e celebrar o contrato nas condições ofertadas pelos licitantes remanescentes, atendida a ordem classificatória, quando frustrada a negociação de melhor condição.</p>
                <p class="subitem">A recusa injustificada do adjudicatário em assinar o contrato ou em aceitar ou retirar o instrumento equivalente no prazo estabelecido pela Administração caracterizará o descumprimento total da obrigação assumida e o sujeitará às penalidades legalmente estabelecidas, previstas neste edital, e à imediata perda da garantia de proposta em favor do órgão licitante.</p>
                <p class="subitem">O Aceite da Nota de Empenho ou do instrumento equivalente, emitida ao fornecedor adjudicado, implica o reconhecimento de que:</p>
                <p class="subitem-3">referida Nota está substituindo o contrato, aplicando-se à relação de negócios ali estabelecida as disposições da Lei nº 14.133, de 2021;</p>
                <p class="subitem-3">a contratada se vincula à sua proposta e às previsões contidas neste Edital;</p>
                <p class="subitem-3">a contratada reconhece que as hipóteses de rescisão são aquelas previstas nos artigos 137 e 138 da Lei nº 14.133, de 2021 e reconhece os direitos da Administração previstos nos artigos 137 a 139 da mesma Lei.</p>
            </div>

            <div id="edital-infracoes-sancoes" class="secao-numerada">
                <p class="bold"><span class="nr-titulo"></span>. DAS INFRAÇÕES ADMINISTRATIVAS E SANÇÕES</p>
                <p class="subitem">Comete infração administrativa, nos termos da lei, o licitante que, com dolo ou culpa:</p>
                <p class="subitem-3">deixar de entregar a documentação exigida para o certame ou não entregar qualquer documento que tenha sido solicitado pelo/a Pregoeiro/a durante o certame;</p>
                <p class="subitem-3">Salvo em decorrência de fato superveniente devidamente justificado, não mantiver a proposta em especial quando:</p>
                <p class="subitem-4">não enviar a proposta adequada ao último lance ofertado ou após a negociação;</p>
                <p class="subitem-4">recusar-se a enviar o detalhamento da proposta quando exigível;</p>
                <p class="subitem-4">pedir para ser desclassificado quando encerrada a etapa competitiva;</p>
                <p class="subitem-4">deixar de apresentar amostra; ou</p>
                <p class="subitem-4">apresentar proposta ou amostra em desacordo com as especificações do edital;</p>
                <p class="subitem-3">não celebrar o contrato ou não entregar a documentação exigida para a contratação, quando convocado dentro do prazo de validade de sua proposta;</p>
                <p class="subitem-3">recusar-se, sem justificativa, a assinar o contrato ou a ata de registro de preço, ou a aceitar ou retirar o instrumento equivalente no prazo estabelecido pela Administração;</p>
                <p class="subitem-3">apresentar declaração ou documentação falsa exigida para o certame ou prestar declaração falsa durante a licitação</p>
                <p class="subitem-3">fraudar a licitação;</p>
                <p class="subitem-3">comportar-se de modo inidôneo ou cometer fraude de qualquer natureza, em especial quando:</p>
                <p class="subitem-4">agir em conluio ou em desconformidade com a lei;</p>
                <p class="subitem-4">induzir deliberadamente a erro no julgamento;</p>
                <p class="subitem-4">apresentar amostra falsificada ou deteriorada;</p>
                <p class="subitem-3">praticar atos ilícitos com vistas a frustrar os objetivos da licitação</p>
                <p class="subitem-3">praticar ato lesivo previsto no art. 5º da Lei n.º 12.846, de 2013.</p>
                <p class="subitem">Com fulcro na Lei nº 14.133, de 2021, a Administração poderá, garantida a prévia defesa, aplicar aos licitantes e/ou adjudicatários as seguintes sanções, sem prejuízo das responsabilidades civil e criminal:</p>
                <p class="subitem-3">advertência;</p>
                <p class="subitem-3">multa;</p>
                <p class="subitem-3">impedimento de licitar e contratar e</p>
                <p class="subitem-3">declaração de inidoneidade para licitar ou contratar, enquanto perdurarem os motivos determinantes da punição ou até que seja promovida sua reabilitação perante a própria autoridade que aplicou a penalidade.</p>
                <p class="subitem">Na aplicação das sanções serão considerados:</p>
                <p class="subitem-3">a natureza e a gravidade da infração cometida.</p>
                <p class="subitem-3">as peculiaridades do caso concreto</p>
                <p class="subitem-3">as circunstâncias agravantes ou atenuantes</p>
                <p class="subitem-3">os danos que dela provierem para a Administração Pública</p>
                <p class="subitem-3">a implantação ou o aperfeiçoamento de programa de integridade, conforme normas e orientações dos órgãos de controle.</p>
                <p class="subitem">A multa será recolhida no prazo máximo de dias úteis, a contar da comunicação oficial.</p>
                <p class="subitem-3">Para as infrações previstas nos itens 17.1.1, 17.1.2 e 17.1.3, a multa será de 0.5% a 15% do valor do contrato licitado.</p>
                <p class="subitem-3">Para as infrações previstas nos itens 17.1.4, 17.1.5, 17.1.6, 17.1.7, 17.1.8 e 17.1.9, a multa será de 15% a 30% do valor do contrato licitado.</p>
                <p class="subitem">As sanções de advertência, impedimento de licitar e contratar e declaração de inidoneidade para licitar ou contratar poderão ser aplicadas, cumulativamente ou não, à penalidade de multa.</p>
                <p class="subitem">Na aplicação da sanção de multa será facultada a defesa do interessado no prazo de 15 (quinze) dias úteis, contado da data de sua intimação.</p>
                <p class="subitem">A sanção de impedimento de licitar e contratar será aplicada ao responsável em decorrência das infrações administrativas relacionadas nos itens 17.1.1, 17.1.2 e 17.1.3, quando não se justificar a imposição de penalidade mais grave, e impedirá o responsável de licitar e contratar no âmbito da Administração Pública direta e indireta do ente federativo o qual pertencer o órgão ou entidade, pelo prazo máximo de 3 (três) anos.</p>
                <p class="subitem">Poderá ser aplicada ao responsável a sanção de declaração de inidoneidade para licitar ou contratar, em decorrência da prática das infrações dispostas nos itens 17.1.5, 17.1.6, 17.1.7, 17.1.8 e 17.1.9, bem como pelas infrações administrativas previstas nos itens 17.1.1, 17.1.2, 17.1.3 e 17.1.4, que justifiquem a imposição de penalidade mais grave que a sanção de impedimento de licitar e contratar, cuja duração observará o prazo previsto no art. 156, §5º, da Lei n.º 14.133, de 2021.</p>
                <p class="subitem">A recusa injustificada do adjudicatário em assinar o contrato ou a ata de registro de preço, ou em aceitar ou retirar o instrumento equivalente no prazo estabelecido pela Administração, descrita no item 17.1.4, caracterizará o descumprimento total da obrigação assumida e o sujeitará às penalidades e à imediata perda da garantia de proposta em favor do órgão ou entidade promotora da licitação, nos termos do art. 45, §4º da IN SEGES/ME n.º 73, de 2022.</p>
                <p class="subitem">A apuração de responsabilidade relacionadas às sanções de impedimento de licitar e contratar e de declaração de inidoneidade para licitar ou contratar demandará a instauração de processo de responsabilização a ser conduzido por comissão composta por 2 (dois) ou mais servidores estáveis, que avaliará fatos e circunstâncias conhecidos e intimará o licitante ou o adjudicatário para, no prazo de 15 (quinze) dias úteis, contado da data de sua intimação, apresentar defesa escrita e especificar as provas que pretenda produzir.</p>
                <p class="subitem">Caberá recurso no prazo de 15 (quinze) dias úteis da aplicação das sanções de advertência, multa e impedimento de licitar e contratar, contado da data da intimação, o qual será dirigido à autoridade que tiver proferido a decisão recorrida, que, se não a reconsiderar no prazo de 5 (cinco) dias úteis, encaminhará o recurso com sua motivação à autoridade superior, que deverá proferir sua decisão no prazo máximo de 20 (vinte) dias úteis, contado do recebimento dos autos.</p>
                <p class="subitem">Caberá a apresentação de pedido de reconsideração da aplicação da sanção de declaração de inidoneidade para licitar ou contratar no prazo de 15 (quinze) dias úteis, contado da data da intimação, e decidido no prazo máximo de 20 (vinte) dias úteis, contado do seu recebimento.</p>
                <p class="subitem">O recurso e o pedido de reconsideração terão efeito suspensivo do ato ou da decisão recorrida até que sobrevenha decisão final da autoridade competente.</p>
                <p class="subitem">A aplicação das sanções previstas neste edital não exclui, em hipótese alguma, a obrigação de reparação integral dos danos causados.</p>
                <p class="subitem">Para a garantia da ampla defesa e contraditório dos licitantes, as notificações serão enviadas eletronicamente para os endereços de e-mail informados na proposta comercial, bem como os cadastrados pela empresa no SICAF.</p>
                <p class="subitem">Os endereços de e-mail informados na proposta comercial e/ou cadastrados no Sicaf serão considerados de uso contínuo da empresa, não cabendo alegação de desconhecimento das comunicações a eles comprovadamente enviadas.</p>
            </div>

            <div id="edital-disposicoes-gerais" class="secao-numerada">
                <p class="bold"><span class="nr-titulo"></span>. DAS DISPOSIÇÕES GERAIS:</p>
                <p class="subitem">A proponente que vier a ser contratada ficará obrigada a aceitar, nas mesmas condições contratuais, os acréscimos ou supressões que se fizerem necessários, por conveniência da Administração, dentro do limite permitido pelo art. 125 da Lei nº 14.133/2021, sobre o valor inicial atualizado do contratado.</p>
                <p class="subitem">Após a apresentação da proposta, não caberá desistência, salvo por motivo justo decorrente de fato superveniente e aceito pelo pregoeiro.</p>
                <p class="subitem">A Administração tem a prerrogativa de fiscalizar o cumprimento satisfatório do objeto da presente licitação, por meio de agente designado para tal função, conforme o disposto na Lei nº 14.133/2021.</p>
                <p class="subitem">Fica eleito e convencionado, para fins legais e para dirimir questões oriundas desta licitação, o Foro da Comarca de Sapucaia do Sul, com renúncia expressa a qualquer outro, por mais privilegiado que seja.</p>
                <p class="subitem">Não havendo expediente ou ocorrendo qualquer fato superveniente que impeça a realização do certame na data marcada, a sessão será automaticamente transferida para o primeiro dia útil subsequente, no mesmo horário anteriormente estabelecido, desde que não haja comunicação em contrário, pelo Pregoeiro.</p>
                <p class="subitem">A homologação do resultado desta licitação não implicará direito à contratação.</p>
                <p class="subitem">As normas disciplinadoras da licitação serão sempre interpretadas em favor da ampliação da disputa entre os interessados, desde que não comprometam o interesse da Administração, o princípio da isonomia, a finalidade e a segurança da contratação.</p>
                <p class="subitem">Os licitantes assumem todos os custos de preparação e apresentação de suas propostas e a Administração não será, em nenhum caso, responsável por esses custos, independentemente da condução ou do resultado do processo licitatório.</p>
                <p class="subitem">O desatendimento de exigências formais não essenciais não importará o afastamento do licitante, desde que seja possível o aproveitamento do ato, observados os princípios da isonomia e do interesse público.</p>
                <p class="subitem">Em caso de divergência entre disposições deste Edital e de seus anexos ou demais peças que compõem o processo, prevalecerá as deste Edital.</p>
            </div>

            <br><br>
            <div id="edital-assinatura">
                <p class="center">Sapucaia do Sul, <?php echo $data_hoje; ?></p>
                <br><br>
                <p class="center">............................................................................</p>
                <p class="center bold"><?php echo $pregoeiro_nome; ?></p>
                <p class="center">Pregoeiro(a) Responsável</p>
            </div>

        </div>
    </div>
</div>

<?php
render_footer();
?>