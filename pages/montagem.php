<?php
// 1. CARREGA O LAYOUT E AUTENTICAÇÃO
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

// 2. VERIFICA SE OS DADOS FORAM ENVIADOS
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /gerar_edital.php');
    exit;
}

// 3. CAPTURA OS DADOS DO FORMULÁRIO `gerar_edital.php`
$objeto = htmlspecialchars($_POST['objeto'] ?? '', ENT_QUOTES, 'UTF-8');
$pregao_num = htmlspecialchars($_POST['pregao'] ?? '', ENT_QUOTES, 'UTF-8');
$processo_num = htmlspecialchars($_POST['processoadm'] ?? '', ENT_QUOTES, 'UTF-8');
$requisicao_num = htmlspecialchars($_POST['requisicao'] ?? '', ENT_QUOTES, 'UTF-8');
$pregoeiro_nome = htmlspecialchars($_POST['pregoeiro'] ?? '', ENT_QUOTES, 'UTF-8');
$valor_estimado = htmlspecialchars($_POST['valor'] ?? 'R$ 0,00', ENT_QUOTES, 'UTF-8');
$modalidade_val = htmlspecialchars($_POST['modalidade'] ?? '1', ENT_QUOTES, 'UTF-8');
$cj_val = htmlspecialchars($_POST['cj'] ?? '1', ENT_QUOTES, 'UTF-8');

// --- PREPARA VARIÁVEIS DE TEXTO INICIAIS ---
$modalidade_texto = ($modalidade_val == '1') ? "Pregão Eletrônico" : "Concorrência Eletrônica";
// Usa o critério do documento ou o do formulário
$criterio_texto = ($cj_val == '1') ? "Menor Preço Unitário" : "Maior Desconto";
$data_hoje = date('d/m/Y');


// --- 4. CONFIGURA HEADER COM CSS E JS SEPARADOS ---
$page_styles = ['/css/montagem.css']; 
$page_scripts = ['/js/montagem.js'];
render_header('Montagem do Edital', ['scripts' => $page_scripts, 'styles' => $page_styles]);
?>

<div id="edital-data-store"
     data-modalidade-texto="<?php echo $modalidade_texto; ?>"
     data-cj-texto="<?php echo $criterio_texto; ?>"
     data-data-hoje="<?php echo $data_hoje; ?>"
></div>


<div class="editor-container">

    <div class="editor-sidebar">
        <div class="card">
            <div class="card-header">
                <h3>Opções do Edital</h3>
            </div>
            <div class="card-content" style="padding: 0 1.5rem 1.5rem 1.5rem;">
                
                <div class="br-accordion" id="accordion-principal" single="single">
                    
                    <div class="item" id='item0'>
                        <button class="header" type="button" aria-controls="content-0">
                            <span class="icon"><i class="fas fa-angle-down" aria-hidden="true"></i></span>
                            <span class="title">DATA E HORA DA SESSÃO</span>
                        </button>
                    </div>
                    <div class="content" id="content-0" style='margin:0;'>
                        <div class="row">
                            <div class="col-lg-6 mb-3">
                                <div class="br-datetimepicker" data-mode="single" data-type="text">
                                    <div class="br-input has-icon">
                                        <label for="input-data-sessao">Data da Sessão</label>
                                        <input id="input-data-sessao" type="text" placeholder="ex: 00/00/0000" data-input="data-input" />
                                        <button class="br-button circle small" type="button" data-toggle="data-toggle"><i class="fas fa-calendar-alt" aria-hidden="true"></i></button>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="br-datetimepicker" data-mode="single" data-type="time">
                                    <div class="br-input has-icon">
                                        <label for="hInicial">Hora de Início (Brasília)</label>
                                        <input type="time" placeholder="00:00" id="hInicial" data-input="data-input" value="09:00"/>
                                        <button class="br-button circle small" type="button" data-toggle="data-toggle"><i class="fas fa-clock" aria-hidden="true"></i></button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="item" id='item1'>
                        <button class="header" type="button" aria-controls="content-1">
                            <span class="icon"><i class="fas fa-angle-down" aria-hidden="true"></i></span>
                            <span class="title">1. OBJETO</span>
                        </button>
                    </div>
                    <div class="content" id="content-1" style='margin:0;'>
                        <div class="col mt-3 mb-3 ">
                            <div class="br-textarea">
                                <label for="control_objeto_accordion">Texto do Objeto</label>
                                <textarea id="control_objeto_accordion" placeholder="Insira o objeto da licitação"><?php echo $objeto; ?></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <div class="item" id='item17'>
                        <button class="header" type="button" aria-controls="content-17">
                            <span class="icon"><i class="fas fa-angle-down" aria-hidden="true"></i></span>
                            <span class="title">17. INFRAÇÕES E SANÇÕES</span>
                        </button>
                    </div>
                    <div class="content" id="content-17" style='margin:0;'>
                        <div class="col mt-3 mb-3 ">
                            <div class="row">  
                                <div class="col d-inline-block ">
                                    <div class="br-input small input-highlight ">
                                        <label for="infracoes-prazo-multa"><small>Prazo p/ recolhimento de multa (dias)</small></label>
                                        <input id="infracoes-prazo-multa" name="infracoes-prazo-multa" type="number" placeholder='Ex: 30' value="30" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="item" id='item18'>
                        <button class="header" type="button" aria-controls="content-18">
                            <span class="icon"><i class="fas fa-angle-down" aria-hidden="true"></i></span>
                            <span class="title">ASSINATURA E DATA</span>
                        </button>
                    </div>
                    <div class="content" id="content-18" style='margin:0;'>
                        <div class="col mt-3 mb-3 ">
                            <div class="row">
                                <div class="col-6 d-inline-block ">
                                    <div class="br-input small input-highlight ">
                                        <label for="gerais-cidade"><small>Cidade</small></label>
                                        <input id="gerais-cidade" name="gerais-cidade" type="text" placeholder='Sapucaia do Sul' value="Sapucaia do Sul" />
                                    </div>
                                </div>
                                <div class="col-6 d-inline-block ">
                                    <div class="br-datetimepicker" data-mode="single" data-type="text">
                                        <div class="br-input has-icon">
                                            <label for="gerais-data"><small>Data</small></label>
                                            <input id="gerais-data" type="text" placeholder="ex: 02/02/2024" data-input="data-input" value="<?php echo $data_hoje; ?>" />
                                            <button class="br-button circle small" type="button" data-toggle="data-toggle"><i class="fas fa-calendar-alt" aria-hidden="true"></i></button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-3"> 
                                <div class="col d-inline-block ">
                                    <div class="br-input small input-highlight ">
                                        <label for="gerais-cargo-agente"><small>Cargo do Agente</small></label>
                                        <input id="gerais-cargo-agente" name="gerais-cargo-agente" type="text" placeholder="Ex: Pregoeiro Municipal" value="Pregoeira" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div> </div>
        </div> <form id="pdf-form" method="POST" action="finalizar_pdf.php" target="_blank" style="margin-top: 1.5rem;">
            <input type="hidden" name="edital_html" id="hidden_html_content">
            <div class="card">
                <div class="card-footer form-actions" style="display: flex; justify-content: flex-end; gap: 1rem;">
                    <button class="br-button secondary" type="button" id="btn-salvar-html"><i class="fas fa-file-code" aria-hidden="true"></i> Salvar HTML</button>
                    <button class="br-button primary" type="submit" id="btn-gerar-pdf">Gerar PDF</button>
                </div>
            </div>
        </form>

    </div> <div class="editor-preview">
        <div class="right" id="right">
            
            <div style='background-color:#ffffff; padding: 2cm;' id="topoPagina">
                
                [cite_start]<p><b>EDITAL DE PREGÃO ELETRÔNICO N°. <mark><?php echo $pregao_num; ?></mark></b></p> [cite: 1]
                <p><b>PROCESSO DIGITAL N°. <mark><?php echo $processo_num; [cite_start]?></mark></b></p> [cite: 2]
                <p><b>REQUISIÇÃO N°. <mark><?php echo $requisicao_num; [cite_start]?></mark></b></p> [cite: 3]
                <p><b>OBJETO: <mark id="preview-objeto"><?php echo $objeto; [cite_start]?></mark></b></p> [cite: 4]
                <p><b>VALOR TOTAL ESTIMADO: R$ <mark><?php echo $valor_estimado; [cite_start]?></mark></b></p> [cite: 5]
                
                [cite_start]<p><b>LIMITE PARA RECEBIMENTO DAS PROPOSTAS:</b></p> [cite: 6]
                [cite_start]<p>Às 00h00min do dia 00/00/0000 até as <mark><span id="preview-hora-sessao">09:00</span></mark>h do dia <mark><span id="preview-data-sessao">[DATA]</span></mark></p> [cite: 7]
                [cite_start]<p><b>INÍCIO DA SESSÃO DE DISPUTA:</b></p> [cite: 8]
                [cite_start]<p>Às <mark><span id="preview-hora-sessao-inicio">09:00</span></mark>h do dia <mark><span id="preview-data-sessao-inicio">[DATA]</span></mark></p> [cite: 9]
                
                [cite_start]<p><b>MODO DE DISPUTA:</b></p> [cite: 10]
                <p><?php echo $criterio_texto; [cite_start]?></p> [cite: 11]
                [cite_start]<p><b>CRITÉRIO DE JULGAMENTO:</b></p> [cite: 12]
                <p><mark><span id="preview-criterio-julgamento"><?php echo $criterio_texto; [cite_start]?></span></mark></p> [cite: 13]
                
                [cite_start]<p><b>PREGOEIRA RESPONSÁVEL:</b></p> [cite: 14]
                <p><mark><?php echo $pregoeiro_nome; [cite_start]?></mark></p> [cite: 15]
                
                [cite_start]<p><b>REFÊRENCIA DE TEMPO:</b></p> [cite: 16]
                [cite_start]<p>Será observado o horário de Brasília (DF).</p> [cite: 17]
                [cite_start]<p>Os documentos que integram o Edital serão disponibilizados nos seguintes locais:</p> [cite: 18]
                [cite_start]<p>a) Portal Nacional de Contratações Públicas (PNCP) - https://www.gov.br/pncp/pt-br</p> [cite: 19]
                [cite_start]<p>b) Portal de Compras Públicas (PCP) - https://www.portaldecompraspublicas.com.br/</p> [cite: 20]
                
                <p>O <b>MUNICÍPIO DE SAPUCAIA DO SUL</b>, inscrito no CNPJ/MF sob o n° 88.185.020/0001-25 por meio da <b>Diretoria de Compras e Licitações da Secretaria Municipal de Administração - SMA</b>, com sede no Endereço: Av. Leônidas de Souza, 1289 - Santa Catarina, Sapucaia do Sul - RS, 93210-140, torna público que realizará licitação na modalidade, <b><?php echo $modalidade_texto; ?></b>, tipo por <span id="preview-criterio-julgamento-texto-2"><?php echo $criterio_texto; [cite_start]?></span>, que será regido pela Lei Federal nº 14.133 de 1º de abril de 2021, pela Lei Complementar 123/2006, pela Lei Federal n° 8.078/1990 e demais legislações aplicáveis e, ainda, de acordo com as condições estabelecidas neste Edital.</p> [cite: 21, 22]
                
                [cite_start]<p>Conforme especificações descritas no Termo de Referência (Anexo II), o qual passa a ser parte integrante do presente edital.</p> [cite: 23]
                
                [cite_start]<p>Fazem parte integrante deste edital:</p> [cite: 24]
                [cite_start]<p>Anexo I – Estudo Técnico Preliminar (ETP);</p> [cite: 25]
                [cite_start]<p>Anexo II – Termo de Referência (TR);</p> [cite: 26]
                [cite_start]<p>Anexo III – Modelo de Proposta;</p> [cite: 27]
                [cite_start]<p>Anexo IV – Modelo de Declaração;</p> [cite: 28]
                [cite_start]<p>Anexo V – Modelo Contratual;</p> [cite: 29]

                [cite_start]<p><b>DETALHAMENTO DO OBJETO:</b></p> [cite: 30]
                [cite_start]<p>Especificações e Quantidades:</p> [cite: 31]
                [cite_start]<p>Constitui objeto da presente licitação a contratação para o fornecimento dos seguintes ITENS, cujas descrições e condições de entrega estão detalhadas no Termo de Referência (Anexo II):</p> [cite: 32]
                
                [cite_start]<p><b>CREDENCIAMENTO E PARTICIPAÇÃO DO CERTAME</b></p> [cite: 34]
                [cite_start]<p>Para participar do certame, o licitante deve providenciar o seu credenciamento, com atribuição de chave e senha, diretamente junto ao provedor do sistema, onde deverá informar-se a respeito do seu funcionamento, regulamento e instruções para a sua correta utilização.</p> [cite: 35]
                [cite_start]<p>As instruções para o credenciamento podem ser acessadas no seguinte sítio eletrônico, qualquer dúvida, em relação ao acesso no sistema operacional, poderá ser esclarecida pelo número 3003-5455 (atendimento nacional), junto à Central de Atendimento do Portal de Compras Públicas.</p> [cite: 36]
                [cite_start]<p>É de responsabilidade do licitante, além de credenciar-se previamente no sistema eletrônico utilizado no certame e de cumprir as regras do presente edital:</p> [cite: 37]
                [cite_start]<p>Responsabilizar-se formalmente pelas transações efetuadas em seu nome, assumir como firmes e verdadeiras suas propostas e seus lances, inclusive os atos praticados diretamente ou por seu representante, excluída a responsabilidade do provedor do sistema ou do órgão ou entidade promotora da licitação por eventuais danos decorrentes de uso indevido da senha, ainda que por terceiros;</p> [cite: 38]
                [cite_start]<p>Acompanhar as operações no sistema eletrônico durante o processo licitatório e responsabilizar-se pelo ônus decorrente da perda de negócios diante da inobservância de mensagens emitidas pelo sistema ou de sua desconexão;</p> [cite: 39]
                [cite_start]<p>Comunicar imediatamente ao provedor do sistema qualquer acontecimento que possa comprometer o sigilo ou a inviabilidade do uso da senha, para imediato bloqueio de acesso;</p> [cite: 40]
                [cite_start]<p>Utilizar a chave de identificação e a senha de acesso para participar do pregão na forma eletrônica; e</p> [cite: 41]
                [cite_start]<p>Solicitar o cancelamento da chave de identificação ou da senha de acesso por interesse próprio.</p> [cite: 42]
                [cite_start]<p>O licitante é totalmente responsável por todas as ações (transações, propostas, lances) feitas em seu nome, incluindo as de seu representante.</p> [cite: 43]
                [cite_start]<p>O órgão licitante e o provedor do sistema não se responsabilizam por danos causados pelo mau uso das credenciais de acesso, mesmo que utilizadas por terceiros.</p> [cite: 44]
                [cite_start]<p>É de responsabilidade do cadastrado conferir a exatidão dos seus dados cadastrais nos Sistemas relacionados no item anterior e mantê-los atualizados junto aos órgãos responsáveis pela informação, devendo proceder, imediatamente, à correção ou à alteração dos registros tão logo identifique incorreção ou aqueles se tornem desatualizados.</p> [cite: 45]
                [cite_start]<p>A não observância do disposto no item anterior poderá ensejar desclassificação no momento da habilitação.</p> [cite: 46]
                
                [cite_start]<p><b>VEDAÇÕES</b></p> [cite: 47]
                [cite_start]<p>Não poderão disputar licitação ou participar da execução de contrato, direta ou indiretamente:</p> [cite: 48]
                [cite_start]<p>aquele que não atenda às condições deste Edital e seu(s) anexo(s);</p> [cite: 49]
                [cite_start]<p>sociedade que desempenhe atividade incompatível com o objeto da licitação;</p> [cite: 50]
                [cite_start]<p>pessoa física ou jurídica que se encontre, ao tempo da licitação, impossibilitada de participar da licitação em decorrência de sanção que lhe foi imposta;</p> [cite: 51]
                [cite_start]<p>aquele que mantenha vínculo de natureza técnica, comercial, econômica, financeira, trabalhista ou civil com dirigente do órgão ou entidade contratante ou com agente público que desempenhe função na licitação ou atue na fiscalização ou na gestão do contrato, ou que deles seja cônjuge, companheiro ou parente em linha reta, colateral ou por afinidade, até o terceiro grau;</p> [cite: 52]
                [cite_start]<p>empresas controladoras, controladas ou coligadas, nos termos da Lei nº 6.404, de 15 de dezembro de 1976, concorrendo entre si;</p> [cite: 53]
                [cite_start]<p>pessoa física ou jurídica que, nos 5 (cinco) anos anteriores à divulgação do edital, tenha sido condenada judicialmente, com trânsito em julgado, por exploração de trabalho infantil, por submissão de trabalhadores a condições análogas às de escravo ou por contratação de adolescentes nos casos vedados pela legislação trabalhista;</p> [cite: 54]
                <p>agente público do órgão ou entidade contratante, devendo ser observadas as situações que possam configurar conflito de interesses no exercício ou após o exercício do cargo ou emprego, nos termos da legislação que disciplina a matéria, conforme § 1º do art. [cite_start]9º da Lei nº 14.133, de 2021.</p> [cite: 55, 56]
                [cite_start]<p>O impedimento de que trata a alínea “a” do item 3.1, supra, será também aplicado ao licitante que atue em substituição a outra pessoa, física ou jurídica, com o intuito de burlar a efetividade da sanção a ela aplicada, inclusive a sua controladora, controlada ou coligada, desde que devidamente comprovado o ilícito ou a utilização fraudulenta da personalidade jurídica do licitante.</p> [cite: 57]
                [cite_start]<p>O impedimento de que trata a alínea “c” será também aplicado ao licitante que atue em substituição a outra pessoa, física ou jurídica, com o intuito de burlar a efetividade da sanção a ela aplicada, inclusive a sua controladora, controlada ou coligada, desde que devidamente comprovado o ilícito ou a utilização fraudulenta da personalidade jurídica do licitante.</p> [cite: 58]
                [cite_start]<p>A vedação de que trata a alínea “g” estende-se a terceiro que auxilie a condução da contratação na qualidade de integrante de equipe de apoio, profissional especializado ou funcionário ou representante de empresa que preste assessoria técnica.</p> [cite: 59]
                [cite_start]<p>Durante a vigência do contrato, é vedado ao contratado contratar cônjuge, companheiro ou parente em linha reta, colateral ou por afinidade, até o terceiro grau, de dirigente do órgão contratante ou de agente público que desempenhe função na licitação ou atue na fiscalização ou na gestão do contrato.</p> [cite: 60]

                [cite_start]<p><b>ABERTURA DA SESSÃO PÚBLICA</b></p> [cite: 61]
                [cite_start]<p>No dia e hora indicados no preâmbulo, o pregoeiro abrirá a sessão pública, mediante a utilização de sua chave e senha.</p> [cite: 62]
                [cite_start]<p>O licitante poderá participar da sessão pública na internet, mediante a utilização de sua chave de acesso e senha, e deverá acompanhar o andamento do certame e as operações realizadas no sistema eletrônico durante toda a sessão pública do pregão, ficando responsável pela perda de negócios diante da inobservância de mensagens emitidas pelo sistema ou de sua desconexão, conforme item 2.3.2 deste Edital.</p> [cite: 63]
                [cite_start]<p>A comunicação entre o pregoeiro e os licitantes ocorrerá mediante troca de mensagens em campo próprio do sistema eletrônico.</p> [cite: 64]
                [cite_start]<p>Iniciada a sessão, as propostas de preços contendo a descrição do objeto e do valor estarão disponíveis na internet .</p> [cite: 65]
                
                [cite_start]<p><b>MODO DE DISPUTA</b></p> [cite: 66]
                [cite_start]<p>Será adotado o modo de disputa aberto, em que os licitantes apresentarão lances públicos e sucessivos, observando as regras constantes no item 7.</p> [cite: 67]
                [cite_start]<p>A etapa competitiva, de envio de lances na sessão pública, durará 10 (dez) minutos e, após isso, será prorrogada automaticamente pelo sistema quando houver lance ofertado nos últimos dois minutos do período de duração da sessão pública.</p> [cite: 68]
                [cite_start]<p>A prorrogação automática da etapa de envio de lances será de dois minutos e ocorrerá sucessivamente sempre que houver lances enviados nesse período de prorrogação, inclusive quando se tratar de lances intermediários.</p> [cite: 69]
                [cite_start]<p>Na hipótese de não haver novos lances, a sessão pública será encerrada automaticamente.</p> [cite: 70]
                [cite_start]<p>Encerrada a sessão pública sem prorrogação automática pelo sistema, o pregoeiro poderá, assessorado pela equipe de apoio, admitir o reinício da etapa de envio de lances, em prol da consecução do melhor preço, mediante justificativa.</p> [cite: 71]
                [cite_start]<p>Na hipótese de o sistema eletrônico desconectar para o pregoeiro no decorrer da etapa de envio de lances da sessão pública e permanecer acessível aos licitantes, os lances continuarão sendo recebidos, sem prejuízo dos atos realizados.</p> [cite: 72]
                [cite_start]<p>Quando a desconexão do sistema eletrônico para o pregoeiro persistir por tempo superior a 10 (dez) minutos, a sessão pública será suspensa e reiniciada somente decorridas 24 (vinte e quatro horas) após a comunicação do fato aos participantes, no sítio eletrônico www.portaldecompraspublicas.com.br.</p> [cite: 73]
                
                [cite_start]<p><b>ENVIO DAS PROPOSTAS</b></p> [cite: 74]
                [cite_start]<p>O prazo de validade da proposta será de 60 dias, a contar da data de abertura da sessão do pregão, estabelecida no preâmbulo desse edital.</p> [cite: 75]
                [cite_start]<p>Os licitantes deverão registrar suas propostas no sistema eletrônico, observando as diretrizes do Modelo de Proposta Comercial, com a indicação completa do produto ofertado, incluindo marca, modelo, referências e demais dados técnicos, bem como com a indicação dos valores unitários e totais dos itens, englobando a tributação, os custos de entrega e quaisquer outras despesas incidentes para o cumprimento das obrigações assumidas.</p> [cite: 76]
                [cite_start]<p>Qualquer elemento que possa identificar o licitante importará na desclassificação da proposta, razão pela qual os licitantes não poderão encaminhar documentos com timbre ou logomarca da empresa, assinatura ou carimbo de sócios ou outra informação que possa levar a sua identificação, até que se encerre a etapa de lances.</p> [cite: 77]
                [cite_start]<p>As propostas e os documentos de habilitação deverão ser enviados exclusivamente por meio do sistema eletrônico:</p> [cite: 78]
                [cite_start]<p>As propostas deverão ser anexadas ao sistema até a data e horário estabelecidos no preâmbulo deste edital, observando os itens 2 e 6, e poderão ser retiradas ou substituídas até a abertura da sessão pública;</p> [cite: 79]
                [cite_start]<p>Os documentos de habilitação do arrematante de cada item poderão ser enviados após a fase de lances ou quando o Agente de Contratação/Pregoeiro os solicitar em campo próprio do sistema do Portal de Compras Públicas, na fase de habilitação.</p> [cite: 80]
                [cite_start]<p>O licitante deverá declarar, em campo próprio do sistema, sendo que a falsidade da declaração sujeitará o licitante às sanções legais:</p> [cite: 81]
                [cite_start]<p>O cumprimento dos requisitos para a habilitação e a conformidade de sua proposta com as exigências do edital, respondendo o declarante pela veracidade das suas informações, na forma da lei;</p> [cite: 82]
                [cite_start]<p>Que cumpre as exigências de reserva de cargos para pessoa com deficiência e para reabilitado da Previdência Social, previstas em lei e em outras normas específicas.</p> [cite: 83]
                <p>O cumprimento dos requisitos legais para a qualificação como microempresa ou empresa de pequeno porte, microempreendedor individual, produtor rural pessoa física, agricultor familiar ou sociedade cooperativa de consumo, se for o caso , estando apto a usufruir do tratamento favorecido estabelecido nos arts. [cite_start]42 ao 49 da Lei Complementar nº 123 de 14 de dezembro de 2006, como condição para aplicação do disposto neste edital.</p> [cite: 84, 85]
                [cite_start]<p>Declaração de observância do limite de R$ 4.800.000,00 na licitação, limitada às microempresas e às empresas de pequeno porte que, no ano-calendário de realização da licitação, ainda não tenham celebrado contratos com a Administração Pública cujos valores somados extrapolem a receita bruta máxima admitida para fins de enquadramento como empresa de pequeno porte.</p> [cite: 86]
                [cite_start]<p>Que suas propostas econômicas compreendem a integralidade dos custos para atendimento dos direitos trabalhistas assegurados na Constituição Federal, nas leis trabalhistas, nas normas infralegais, nas convenções coletivas de trabalho e nos termos de ajustamento de conduta vigentes na data de entrega das propostas.</p> [cite: 87]
                [cite_start]<p>Que não possui em seu quadro cônjuge, companheiro ou parente em linha reta, colateral ou por afinidade, até o terceiro grau, de dirigente do órgão contratante ou de agente público que desempenhe função na licitação ou atue na fiscalização ou na gestão do contrato.</p> [cite: 88]
                [cite_start]<p>Em conformidade com o Inciso IV do artigo 14 da Lei Federal 14.133/2021.</p> [cite: 89]
                [cite_start]<p>Outros eventuais documentos complementares à proposta e à habilitação, que venham a ser solicitados pelo pregoeiro, deverão ser encaminhados no prazo máximo de 2 (dois) dias.</p> [cite: 90]

                [cite_start]<p><b>DAS PROPOSTAS, FORMULAÇÃO DE LANCES E HABILITAÇÃO</b></p> [cite: 91]
                [cite_start]<p>O pregoeiro verificará as propostas apresentadas e desclassificará fundamentadamente aquelas que não estejam em conformidade com os requisitos estabelecidos no edital.</p> [cite: 92]
                [cite_start]<p>Serão desclassificadas as propostas que:</p> [cite: 93]
                [cite_start]<p>contiverem vícios insanáveis;</p> [cite: 94]
                [cite_start]<p>não obedecerem às especificações técnicas pormenorizadas no edital;</p> [cite: 95]
                [cite_start]<p>apresentarem preços inexequíveis ou permanecerem acima do orçamento estimado para a contratação;</p> [cite: 96]
                [cite_start]<p>não tiverem sua exequibilidade demonstrada, quando exigido pela Administração;</p> [cite: 97]
                [cite_start]<p>apresentarem desconformidade com quaisquer outras exigências do edital, desde que insanável.</p> [cite: 98]
                [cite_start]<p>A verificação da conformidade das propostas poderá ser feita exclusivamente em relação à proposta mais bem classificada.</p> [cite: 99]
                [cite_start]<p>Quaisquer inserções na proposta que visem modificar, extinguir ou criar direitos, sem previsão no edital, serão tidas como inexistentes, aproveitando-se a proposta no que não for conflitante com o instrumento convocatório.</p> [cite: 100]
                [cite_start]<p>As propostas classificadas serão ordenadas pelo sistema e o pregoeiro dará início à fase competitiva, oportunidade em que os licitantes poderão encaminhar lances exclusivamente por meio do sistema eletrônico.</p> [cite: 101]
                [cite_start]<p>Somente poderão participar da fase competitiva os autores das propostas classificadas.</p> [cite: 102]
                [cite_start]<p>Os licitantes poderão oferecer lances sucessivos e serão informados, em tempo real, do valor do menor lance registrado, vedada a identificação do seu autor, observando o horário fixado para duração da etapa competitiva, e as seguintes regras:</p> [cite: 103]
                [cite_start]<p>O licitante será imediatamente informado do recebimento do lance e do valor consignado no registro.</p> [cite: 104]
                [cite_start]<p>O licitante somente poderá oferecer valor inferior ao último lance por ele ofertado e registrado pelo sistema.</p> [cite: 105]
                [cite_start]<p>Não serão aceitos dois ou mais lances iguais e prevalecerá aquele que for recebido e registrado primeiro.</p> [cite: 106]
                [cite_start]<p>O intervalo mínimo de diferença de valores entre os lances será de R$0,01, que incidirá tanto em relação aos lances intermediários, quanto em relação do lance que cobrir a melhor oferta.</p> [cite: 107]
                [cite_start]<p>Serão considerados intermediários os lances iguais ou superiores ao menor já ofertado;</p> [cite: 108]
                [cite_start]<p>Após a definição da melhor proposta, se a diferença em relação à proposta classificada em segundo lugar for de pelo menos 5% (cinco por cento), a Administração poderá admitir o reinício da disputa aberta, para a definição das demais colocações.</p> [cite: 109]
                [cite_start]<p>A Administração poderá realizar diligências para aferir a exequibilidade das propostas ou exigir dos licitantes que ela seja demonstrada.</p> [cite: 110]
                [cite_start]<p>Os licitantes poderão retirar ou substituir a proposta ou, na hipótese de a fase de habilitação anteceder as fases de apresentação de propostas e lances e de julgamento, os documentos de habilitação anteriormente inseridos no sistema, até a abertura da sessão pública.</p> [cite: 111]
                [cite_start]<p>Caberá ao licitante interessado em participar da licitação acompanhar as operações no sistema eletrônico durante o processo licitatório e se responsabilizar pelo ônus decorrente da perda de negócios diante da inobservância de mensagens emitidas pela Administração ou de sua desconexão.</p> [cite: 112]
                [cite_start]<p>O licitante deverá comunicar imediatamente ao provedor do sistema qualquer acontecimento que possa comprometer o sigilo ou a segurança, para imediato bloqueio de acesso.</p> [cite: 113]
                [cite_start]<p>Não haverá ordem de classificação na etapa de apresentação da proposta e dos documentos de habilitação pelo licitante, o que ocorrerá somente após os procedimentos de abertura da sessão pública e da fase de envio de lances.</p> [cite: 114]
                [cite_start]<p>Para fins de habilitação neste pregão, a licitante deverá enviar os seguintes documentos, observando o procedimento disposto no item 5 deste Edital:</p> [cite: 115]
                [cite_start]<p>A documentação exigida para fins de habilitação jurídica, fiscal, social e trabalhista e econômico-financeira, poderá ser substituída pelo registro cadastral no SICAF.</p> [cite: 116]
                [cite_start]<p><b>HABILITAÇÃO JURÍDICA</b></p> [cite: 117]
                [cite_start]<p>cópia do registro comercial, no caso de empresa individual;</p> [cite: 118]
                [cite_start]<p>cópia do ato constitutivo, estatuto ou contrato social em vigor, devidamente registrado, em se tratando de sociedades comerciais, e, no caso de sociedade por ações, acompanhado de documentos de eleição de seus administradores;</p> [cite: 119]
                [cite_start]<p>cópia do decreto de autorização, em se tratando de empresa ou sociedade estrangeira em funcionamento no País, e ato de registro ou autorização para funcionamento expedido pelo órgão competente, quando a atividade assim o exigir.</p> [cite: 120]
                <p>As empresas estrangeiras que não funcionem no País deverão apresentar documentos equivalentes, na forma de regulamento previsto no art. [cite_start]70, parágrafo único, da Lei Federal nº 14.133/2021.</p> [cite: 121, 122]
                [cite_start]<p><b>HABILITAÇÃO FISCAL, SOCIAL E TRABALHISTA</b></p> [cite: 123]
                [cite_start]<p>comprovante de inscrição no Cadastro Nacional de Pessoa Jurídica (CNPJ);</p> [cite: 124]
                [cite_start]<p>comprovante de inscrição no cadastro de contribuintes estadual e/ou municipal, se houver, relativo ao domicílio ou sede do licitante, pertinente ao seu ramo de atividade e compatível com o objeto contratual;</p> [cite: 125]
                [cite_start]<p>prova de regularidade perante a Fazenda federal, estadual e/ou municipal do domicílio ou sede do licitante, ou outra equivalente, na forma da lei;</p> [cite: 126]
                [cite_start]<p>prova de regularidade relativa à Seguridade Social e ao FGTS, que demonstre cumprimento dos encargos sociais instituídos por lei;</p> [cite: 127]
                [cite_start]<p>prova de regularidade perante a Justiça do Trabalho;</p> [cite: 128]
                <p>declaração de cumprimento do disposto no inciso XXXIII do art. [cite_start]7º da Constituição Federal.</p> [cite: 129]
                <p>não possui empregados executando trabalho degradante ou forçado, observando o disposto nos incisos III e IV do art. 1º e no inciso III do art. [cite_start]5º da Constituição Federal;</p> [cite: 130, 131]
                [cite_start]<p><b>HABILITAÇÃO ECONÔMICO-FINANCEIRA</b></p> [cite: 132]
                [cite_start]<p>certidão negativa de falência expedida pelo distribuidor da sede da pessoa jurídica.</p> [cite: 133]
                [cite_start]<p><b>HABILITAÇÃO TÉCNICA</b></p> [cite: 134]
                [cite_start]<p><b>DAS AMOSTRAS</b></p> [cite: 135]
                
                [cite_start]<p><b>NEGOCIAÇÃO E JULGAMENTO</b></p> [cite: 136]
                [cite_start]<p>Encerrada a etapa de envio de lances da sessão pública, inclusive com a realização do desempate, se for o caso, o pregoeiro deverá encaminhar, pelo sistema eletrônico, contraproposta ao licitante que tenha apresentado o melhor preço, para que seja obtida melhor proposta.</p> [cite: 137]
                [cite_start]<p>A resposta à contraproposta e o envio de documentos complementares, necessários ao julgamento da aceitabilidade da proposta, inclusive a sua adequação ao último lance ofertado, que sejam solicitados pelo pregoeiro, deverão ser encaminhados no prazo fixado no item 6.4.4 deste Edital.</p> [cite: 138]
                [cite_start]<p>Encerrada a etapa de negociação, será examinada a proposta classificada em primeiro lugar quanto à adequação ao objeto e à compatibilidade do preço em relação valor de referência da Administração.</p> [cite: 139]
                [cite_start]<p>Não serão consideradas, para julgamento das propostas, vantagens não previstas no edital.</p> [cite: 140]

                [cite_start]<p><b>CRITÉRIOS DE DESEMPATE</b></p> [cite: 141]
                <p>Encerrada etapa de envio de lances, será apurada a ocorrência de empate, nos termos dos Arts. [cite_start]44 e 45 da Lei Complementar nº 123/2006, sendo assegurada, como critério do desempate, preferência de contratação para as beneficiárias que tiverem apresentado as declarações de que tratam os itens 6.4.3.3 e 6.3.3.4 deste Edital;</p> [cite: 142, 143]
                [cite_start]<p>Entende-se como empate, para fins da Lei Complementar nº 123/2006, aquelas situações em que as propostas apresentadas pelas beneficiárias sejam iguais ou superiores em até 5% (cinco por cento) à proposta de menor valor.</p> [cite: 144]
                [cite_start]<p>Ocorrendo o empate, na forma do subitem anterior, proceder-se-á da seguinte forma:</p> [cite: 145]
                [cite_start]<p>A beneficiária detentora da proposta de menor valor será convocada via sistema para apresentar, no prazo de 5 (cinco) minutos, nova proposta, inferior àquela considerada, até então, de menor preço, situação em que será declarada vencedora do certame.</p> [cite: 146]
                [cite_start]<p>Se a beneficiária, convocada na forma da alínea anterior, não apresentar nova proposta, inferior à de menor preço, será facultada, pela ordem de classificação, às demais microempresas, empresas de pequeno porte ou cooperativas remanescentes, que se enquadrarem na hipótese do item 9.1. deste edital, a apresentação de nova proposta, no prazo previsto na alínea a deste item.</p> [cite: 147, 148]
                [cite_start]<p>O disposto no item 9.1.1. não se aplica às hipóteses em que a proposta de menor valor inicial tiver sido apresentado por beneficiária da Lei Complementar nº 123/2006.</p> [cite: 149]
                [cite_start]<p>Se não houver licitante que atenda ao item 9.1 e seus subitens, serão utilizados os seguintes critérios de desempate, nesta ordem:</p> [cite: 150]
                [cite_start]<p>disputa final, hipótese em que os licitantes empatados poderão apresentar nova proposta em ato contínuo à classificação;</p> [cite: 151]
                [cite_start]<p>avaliação do desempenho contratual prévio dos licitantes, para a qual serão ser utilizados registros cadastrais para efeito de atesto de cumprimento de obrigações decorrentes de outras contratações;</p> [cite: 152]
                [cite_start]<p>desenvolvimento pelo licitante de programa de integridade, conforme orientações dos órgãos de controle.</p> [cite: 153]
                [cite_start]<p>Em igualdade de condições, se não houver desempate, será assegurada preferência, sucessivamente, aos bens e serviços produzidos ou prestados por:</p> [cite: 154]
                [cite_start]<p>empresas estabelecidas no território do Estado do Rio Grande do Sul;</p> [cite: 155]
                [cite_start]<p>empresas brasileiras;</p> [cite: 156]
                [cite_start]<p>empresas que invistam em pesquisa e no desenvolvimento de tecnologia no País;</p> [cite: 157]
                [cite_start]<p>empresas que comprovem a prática de mitigação, nos termos da Lei nº 12.187, de 29 de dezembro de 2009.</p> [cite: 158]

                [cite_start]<p><b>PEDIDOS DE ESCLARECIMENTOS E IMPUGNAÇÕES</b></p> [cite: 159]
                [cite_start]<p>Os pedidos de esclarecimentos referentes ao processo licitatório e os pedidos de impugnações poderão ser enviados ao pregoeiro, até três dias úteis anteriores à data fixada para abertura da sessão pública, por meio do seguinte endereço eletrônico: www.portaldecompraspublicas.com.br.</p> [cite: 160]
                [cite_start]<p>As respostas aos pedidos de esclarecimentos e às impugnações serão divulgadas no seguinte sítio eletrônico da Administração: www.portaldecompraspublicas.com.br.</p> [cite: 161]
                
                [cite_start]<p><b>VERIFICAÇÃO DA HABILITAÇÃO</b></p> [cite: 162]
                [cite_start]<p>Os documentos de habilitação, de que tratam os itens 7.13.2, 7.13.3, 7.13.4, 7.13.5 e 7.13.6, enviados nos termos do item 6.4, todos deste edital, serão examinados pelo pregoeiro, que verificará a autenticidade das certidões junto aos sítios eletrônicos oficiais de órgãos e entidades emissores.</p> [cite: 163]
                [cite_start]<p>As certidões apresentadas na habilitação, que tenham sido expedidas em meio eletrônico, serão tidas como originais após terem a autenticidade de seus dados e certificação digital conferidos pela Administração, dispensando nova apresentação, exceto se vencido o prazo de validade.</p> [cite: 164]
                [cite_start]<p>A prova de autenticidade de cópia de documento público ou particular poderá ser feita perante agente da Administração, mediante apresentação de original ou de declaração de autenticidade por advogado, sob sua responsabilidade pessoal.</p> [cite: 165]
                [cite_start]<p>A beneficiária da Lei Complementar nº 123/2006, que tenha apresentado a declaração exigida no item 6.4.3.3 e 6.4.3.4 deste Edital e que possua alguma restrição na comprovação de regularidade fiscal e/ou trabalhista, terá sua habilitação condicionada ao envio de nova documentação, que comprove a sua regularidade, em 5 (cinco) dias úteis, prazo que poderá ser prorrogado uma única vez, por igual período, a critério da Administração, desde que seja requerido pelo interessado, de forma motivada e durante o transcurso do respectivo prazo.</p> [cite: 166]
                [cite_start]<p>Na hipótese de a proposta vencedora não for aceitável ou o licitante não atender às exigências para habilitação, o pregoeiro examinará a proposta subsequente e assim sucessivamente, na ordem de classificação, até a apuração de uma proposta que atenda ao edital.</p> [cite: 167]
                [cite_start]<p>Constatado o atendimento às exigências estabelecidas no Edital, o licitante será declarado vencedor, oportunizando-se a manifestação da intenção de recurso.</p> [cite: 168]

                [cite_start]<p><b>RECURSO</b></p> [cite: 169]
                [cite_start]<p>Caberá recurso, no prazo de 3 (três) dias úteis, contado da data de intimação ou de lavratura da ata, em face de:</p> [cite: 170]
                [cite_start]<p>ato que defira ou indefira pedido de pré-qualificação de interessado ou de inscrição em registro cadastral, sua alteração ou cancelamento;</p> [cite: 171]
                [cite_start]<p>julgamento das propostas;</p> [cite: 172]
                [cite_start]<p>ato de habilitação ou inabilitação de licitante;</p> [cite: 173]
                [cite_start]<p>anulação ou revogação da licitação.</p> [cite: 174]
                [cite_start]<p>O prazo para apresentação de contrarrazões será o mesmo do recurso e terá início na data de intimação pessoal ou de divulgação da interposição do recurso.</p> [cite: 175]
                [cite_start]<p>Quanto ao recurso apresentado em virtude do disposto nas alíneas “b” e “c” do item 13.1 do presente Edital, serão observadas as seguintes disposições:</p> [cite: 176]
                [cite_start]<p>a intenção de recorrer deverá ser manifestada imediatamente, sob pena de preclusão, e o prazo para apresentação das razões recursais será iniciado na data de intimação ou de lavratura da ata de habilitação ou inabilitação;</p> [cite: 177]
                [cite_start]<p>a apreciação dar-se-á em fase única.</p> [cite: 178]
                [cite_start]<p>O recurso será dirigido à autoridade que tiver editado o ato ou proferido a decisão recorrida, que, se não reconsiderar o ato ou a decisão no prazo de 3 (três) dias úteis, encaminhará o recurso com a sua motivação à autoridade superior, a qual deverá proferir sua decisão no prazo máximo de 10 (dez) dias úteis, contado do recebimento dos autos.</p> [cite: 179]
                [cite_start]<p>O acolhimento do recurso implicará invalidação apenas de ato insuscetível de aproveitamento.</p> [cite: 180]
                [cite_start]<p>O recurso interposto dará efeito suspensivo ao ato ou à decisão recorrida, até que sobrevenha decisão final da autoridade competente.</p> [cite: 181]

                [cite_start]<p><b>ENCERRAMENTO DA LICITAÇÃO</b></p> [cite: 182]
                [cite_start]<p>Encerradas as fases de julgamento e habilitação, e exauridos os recursos administrativos, o processo licitatório será encaminhado à autoridade superior, que poderá:</p> [cite: 183]
                [cite_start]<p>determinar o retorno dos autos para saneamento de irregularidades;</p> [cite: 184]
                [cite_start]<p>revogar a licitação por motivo de conveniência e oportunidade;</p> [cite: 185]
                [cite_start]<p>proceder à anulação da licitação, de ofício ou mediante provocação de terceiros, sempre que presente ilegalidade insanável;</p> [cite: 186]
                [cite_start]<p>adjudicar o objeto e homologar a licitação.</p> [cite: 187]

                [cite_start]<p><b>DA ADJUDICAÇÃO E HOMOLOGAÇÃO</b></p> [cite: 188]
                <p>Encerradas as fases de julgamento e habilitação, e exauridos os recursos administrativos, o processo licitatório será encaminhado à autoridade superior para adjudicar o objeto e homologar o procedimento, observado o disposto no art. [cite_start]71 da Lei nº 14.133, de 2021.</p> [cite: 189, 190]
                
                [cite_start]<p><b>DOTAÇÃO ORÇAMENTÁRIA:</b></p> [cite: 191]
                [cite_start]<p>O dispêndio financeiro decorrente da contratação ora pretendido decorrerá da(s) dotação(ões) orçamentária(s):</p> [cite: 192]

                [cite_start]<p><b>DA CONTRATAÇÃO</b></p> [cite: 193]
                [cite_start]<p>O prazo de vigência da contratação é o estabelecido em Minuta Contratual.</p> [cite: 194]
                [cite_start]<p>O licitante vencedor será convocado para assinar o termo de contrato ou para aceitar ou retirar o instrumento equivalente, dentro do prazo de 05 (cinco) dias, sob pena de decair o direito à contratação, sem prejuízo das sanções previstas neste Edital.</p> [cite: 195]
                [cite_start]<p>O prazo de convocação poderá ser prorrogado 1 (uma) vez, por igual período, mediante solicitação da parte, durante seu transcurso, devidamente justificada, e desde que o motivo apresentado seja aceito pela Administração.</p> [cite: 196]
                [cite_start]<p>Será facultado à Administração, quando o convocado não assinar o termo de contrato ou não aceitar ou não retirar o instrumento equivalente no prazo e nas condições estabelecidas neste Edital, convocar os licitantes remanescentes, na ordem de classificação, para a celebração do contrato nas condições propostas pelo licitante vencedor.</p> [cite: 197]
                [cite_start]<p>Decorrido o prazo de validade da proposta indicado no item 6.1 deste Edital, sem convocação para a contratação, ficarão os licitantes liberados dos compromissos assumidos.</p> [cite: 198]
                [cite_start]<p>Na hipótese de nenhum dos licitantes aceitar a contratação, nos termos do 16 deste Edital, a Administração, observados o valor estimado e sua eventual atualização nos termos do edital, poderá:</p> [cite: 199]
                [cite_start]<p>convocar os licitantes remanescentes para negociação, na ordem de classificação, com vistas à obtenção de preço melhor, mesmo que acima do preço do adjudicatário;</p> [cite: 200]
                [cite_start]<p>adjudicar e celebrar o contrato nas condições ofertadas pelos licitantes remanescentes, atendida a ordem classificatória, quando frustrada a negociação de melhor condição.</p> [cite: 201]
                [cite_start]<p>A recusa injustificada do adjudicatário em assinar o contrato ou em aceitar ou retirar o instrumento equivalente no prazo estabelecido pela Administração caracterizará o descumprimento total da obrigação assumida e o sujeitará às penalidades legalmente estabelecidas, previstas neste edital, e à imediata perda da garantia de proposta em favor do órgão licitante.</p> [cite: 202]
                [cite_start]<p>O Aceite da Nota de Empenho ou do instrumento equivalente, emitida ao fornecedor adjudicado, implica o reconhecimento de que:</p> [cite: 203]
                [cite_start]<p>referida Nota está substituindo o contrato, aplicando-se à relação de negócios ali estabelecida as disposições da Lei nº 14.133, de 2021;</p> [cite: 204]
                [cite_start]<p>a contratada se vincula à sua proposta e às previsões contidas neste Edital;</p> [cite: 205]
                [cite_start]<p>a contratada reconhece que as hipóteses de rescisão são aquelas previstas nos artigos 137 e 138 da Lei nº 14.133, de 2021 e reconhece os direitos da Administração previstos nos artigos 137 a 139 da mesma Lei.</p> [cite: 206]

                [cite_start]<p><b>DAS INFRAÇÕES ADMINISTRATIVAS E SANÇÕES</b></p> [cite: 207]
                [cite_start]<p>Comete infração administrativa, nos termos da lei, o licitante que, com dolo ou culpa:</p> [cite: 208]
                [cite_start]<p>deixar de entregar a documentação exigida para o certame ou não entregar qualquer documento que tenha sido solicitado pelo/a Pregoeiro/a durante o certame;</p> [cite: 209]
                [cite_start]<p>Salvo em decorrência de fato superveniente devidamente justificado, não mantiver a proposta em especial quando:</p> [cite: 210]
                [cite_start]<p>não enviar a proposta adequada ao último lance ofertado ou após a negociação;</p> [cite: 211]
                [cite_start]<p>recusar-se a enviar o detalhamento da proposta quando exigível;</p> [cite: 212]
                [cite_start]<p>pedir para ser desclassificado quando encerrada a etapa competitiva;</p> [cite: 213]
                [cite_start]<p>deixar de apresentar amostra; ou</p> [cite: 214]
                [cite_start]<p>apresentar proposta ou amostra em desacordo com as especificações do edital;</p> [cite: 215]
                [cite_start]<p>não celebrar o contrato ou não entregar a documentação exigida para a contratação, quando convocado dentro do prazo de validade de sua proposta;</p> [cite: 216]
                [cite_start]<p>recusar-se, sem justificativa, a assinar o contrato ou a ata de registro de preço, ou a aceitar ou retirar o instrumento equivalente no prazo estabelecido pela Administração;</p> [cite: 217]
                [cite_start]<p>apresentar declaração ou documentação falsa exigida para o certame ou prestar declaração falsa durante a licitação</p> [cite: 218]
                [cite_start]<p>fraudar a licitação;</p> [cite: 219]
                [cite_start]<p>comportar-se de modo inidôneo ou cometer fraude de qualquer natureza, em especial quando:</p> [cite: 220]
                [cite_start]<p>agir em conluio ou em desconformidade com a lei;</p> [cite: 221]
                [cite_start]<p>induzir deliberadamente a erro no julgamento;</p> [cite: 222]
                [cite_start]<p>apresentar amostra falsificada ou deteriorada;</p> [cite: 223]
                [cite_start]<p>praticar atos ilícitos com vistas a frustrar os objetivos da licitação</p> [cite: 224]
                <p>praticar ato lesivo previsto no art. [cite_start]5º da Lei n.º 12.846, de 2013.</p> [cite: 225]
                [cite_start]<p>Com fulcro na Lei nº 14.133, de 2021, a Administração poderá, garantida a prévia defesa, aplicar aos licitantes e/ou adjudicatários as seguintes sanções, sem prejuízo das responsabilidades civil e criminal:</p> [cite: 226]
                [cite_start]<p>advertência;</p> [cite: 227]
                [cite_start]<p>multa;</p> [cite: 228]
                [cite_start]<p>impedimento de licitar e contratar e</p> [cite: 229]
                [cite_start]<p>declaração de inidoneidade para licitar ou contratar, enquanto perdurarem os motivos determinantes da punição ou até que seja promovida sua reabilitação perante a própria autoridade que aplicou a penalidade.</p> [cite: 230]
                [cite_start]<p>Na aplicação das sanções serão considerados:</p> [cite: 231]
                [cite_start]<p>a natureza e a gravidade da infração cometida.</p> [cite: 232]
                [cite_start]<p>as peculiaridades do caso concreto</p> [cite: 233]
                [cite_start]<p>as circunstâncias agravantes ou atenuantes</p> [cite: 234]
                [cite_start]<p>os danos que dela provierem para a Administração Pública</p> [cite: 235]
                [cite_start]<p>a implantação ou o aperfeiçoamento de programa de integridade, conforme normas e orientações dos órgãos de controle.</p> [cite: 236]
                [cite_start]<p>A multa será recolhida no prazo máximo de <mark><span id="preview-infracoes-prazo-multa">30 (trinta)</span></mark> dias úteis, a contar da comunicação oficial.</p> [cite: 237]
                [cite_start]<p>Para as infrações previstas nos itens 17.1.1, 17.1.2 e 17.1.3, a multa será de 0.5% a 15% do valor do contrato licitado.</p> [cite: 238]
                [cite_start]<p>Para as infrações previstas nos itens 17.1.4, 17.1.5, 17.1.6, 17.1.7, 17.1.8 e 17.1.9, a multa será de 15% a 30% do valor do contrato licitado.</p> [cite: 239]
                [cite_start]<p>As sanções de advertência, impedimento de licitar e contratar e declaração de inidoneidade para licitar ou contratar poderão ser aplicadas, cumulativamente ou não, à penalidade de multa.</p> [cite: 240]
                [cite_start]<p>Na aplicação da sanção de multa será facultada a defesa do interessado no prazo de 15 (quinze) dias úteis, contado da data de sua intimação.</p> [cite: 241]
                [cite_start]<p>A sanção de impedimento de licitar e contratar será aplicada ao responsável em decorrência das infrações administrativas relacionadas nos itens 17.1.1, 17.1.2 e 17.1.3, quando não se justificar a imposição de penalidade mais grave, e impedirá o responsável de licitar e contratar no âmbito da Administração Pública direta e indireta do ente federativo o qual pertencer o órgão ou entidade, pelo prazo máximo de 3 (três) anos.</p> [cite: 242]
                <p>Poderá ser aplicada ao responsável a sanção de declaração de inidoneidade para licitar ou contratar, em decorrência da prática das infrações dispostas nos itens 17.1.5, 17.1.6, 17.1.7, 17.1.8 e 17.1.9, bem como pelas infrações administrativas previstas nos itens 17.1.1, 17.1.2, 17.1.3 e 17.1.4, que justifiquem a imposição de penalidade mais grave que a sanção de impedimento de licitar e contratar, cuja duração observará o prazo previsto no art. [cite_start]156, §5º, da Lei n.º 14.133, de 2021.</p> [cite: 243, 244]
                <p>A recusa injustificada do adjudicatário em assinar o contrato ou a ata de registro de preço, ou em aceitar ou retirar o instrumento equivalente no prazo estabelecido pela Administração, descrita no item 17.1.4, caracterizará o descumprimento total da obrigação assumida e o sujeitará às penalidades e à imediata perda da garantia de proposta em favor do órgão ou entidade promotora da licitação, nos termos do art. [cite_start]45, §4º da IN SEGES/ME n.º 73, de 2022.</p> [cite: 245, 246]
                [cite_start]<p>A apuração de responsabilidade relacionadas às sanções de impedimento de licitar e contratar e de declaração de inidoneidade para licitar ou contratar demandará a instauração de processo de responsabilização a ser conduzido por comissão composta por 2 (dois) ou mais servidores estáveis, que avaliará fatos e circunstâncias conhecidos e intimará o licitante ou o adjudicatário para, no prazo de 15 (quinze) dias úteis, contado da data de sua intimação, apresentar defesa escrita e especificar as provas que pretenda produzir.</p> [cite: 247]
                [cite_start]<p>Caberá recurso no prazo de 15 (quinze) dias úteis da aplicação das sanções de advertência, multa e impedimento de licitar e contratar, contado da data da intimação, o qual será dirigido à autoridade que tiver proferido a decisão recorrida, que, se não a reconsiderar no prazo de 5 (cinco) dias úteis, encaminhará o recurso com sua motivação à autoridade superior, que deverá proferir sua decisão no prazo máximo de 20 (vinte) dias úteis, contado do recebimento dos autos.</p> [cite: 248]
                [cite_start]<p>Caberá a apresentação de pedido de reconsideração da aplicação da sanção de declaração de inidoneidade para licitar ou contratar no prazo de 15 (quinze) dias úteis, contado da data da intimação, e decidido no prazo máximo de 20 (vinte) dias úteis, contado do seu recebimento.</p> [cite: 249]
                [cite_start]<p>O recurso e o pedido de reconsideração terão efeito suspensivo do ato ou da decisão recorrida até que sobrevenha decisão final da autoridade competente.</p> [cite: 250]
                [cite_start]<p>A aplicação das sanções previstas neste edital não exclui, em hipótese alguma, a obrigação de reparação integral dos danos causados.</p> [cite: 251]
                [cite_start]<p>Para a garantia da ampla defesa e contraditório dos licitantes, as notificações serão enviadas eletronicamente para os endereços de e-mail informados na proposta comercial, bem como os cadastrados pela empresa no SICAF.</p> [cite: 252]
                [cite_start]<p>Os endereços de e-mail informados na proposta comercial e/ou cadastrados no Sicaf serão considerados de uso contínuo da empresa, não cabendo alegação de desconhecimento das comunicações a eles comprovadamente enviadas.</p> [cite: 253]

                [cite_start]<p><b>DAS DISPOSIÇÕES GERAIS:</b></p> [cite: 254]
                <p>A proponente que vier a ser contratada ficará obrigada a aceitar, nas mesmas condições contratuais, os acréscimos ou supressões que se fizerem necessários, por conveniência da Administração, dentro do limite permitido pelo art. [cite_start]125 da Lei nº 14.133/2021, sobre o valor inicial atualizado do contratado.</p> [cite: 255, 256]
                [cite_start]<p>Após a apresentação da proposta, não caberá desistência, salvo por motivo justo decorrente de fato superveniente e aceito pelo pregoeiro.</p> [cite: 257]
                [cite_start]<p>A Administração tem a prerrogativa de fiscalizar o cumprimento satisfatório do objeto da presente licitação, por meio de agente designado para tal função, conforme o disposto na Lei nº 14.133/2021.</p> [cite: 258]
                [cite_start]<p>Fica eleito e convencionado, para fins legais e para dirimir questões oriundas desta licitação, o Foro da Comarca de Sapucaia do Sul, com renúncia expressa a qualquer outro, com expressa renúncia a outro qualquer, por mais privilegiado que seja.</p> [cite: 259]
                [cite_start]<p>Não havendo expediente ou ocorrendo qualquer fato superveniente que impeça a realização do certame na data marcada, a sessão será automaticamente transferida para o primeiro dia útil subsequente, no mesmo horário anteriormente estabelecido, desde que não haja comunicação em contrário, pelo Pregoeiro.</p> [cite: 260]
                [cite_start]<p>A homologação do resultado desta licitação não implicará direito à contratação.</p> [cite: 261]
                [cite_start]<p>As normas disciplinadoras da licitação serão sempre interpretadas em favor da ampliação da disputa entre os interessados, desde que não comprometam o interesse da Administração, o princípio da isonomia, a finalidade e a segurança da contratação.</p> [cite: 262]
                [cite_start]<p>Os licitantes assumem todos os custos de preparação e apresentação de suas propostas e a Administração não será, em nenhum caso, responsável por esses custos, independentemente da condução ou do resultado do processo licitatório.</p> [cite: 263]
                [cite_start]<p>O desatendimento de exigências formais não essenciais não importará o afastamento do licitante, desde que seja possível o aproveitamento do ato, observados os princípios da isonomia e do interesse público.</p> [cite: 264]
                [cite_start]<p>Em caso de divergência entre disposições deste Edital e de seus anexos ou demais peças que compõem o processo, prevalecerá as deste Edital.</p> [cite: 265]
                
                <p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p>
                <p class=""> <mark><span id="preview-gerais-cidade">Sapucaia do Sul</span></mark>, <mark><span id="preview-gerais-data"><?php echo $data_hoje; ?></span></mark></p>
                <p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p>
                <p class="centralizado"> ............................................................................ </p>
                <p class="centralizado"> <mark id="preview-gerais-nome-agente"><?php echo $pregoeiro_nome; ?></mark> </p>
                <p class="centralizado"> <mark id="preview-gerais-cargo-agente">Pregoeira</mark> </p>
            
            </div> </div> </div> </div> <?php
render_footer();
?>