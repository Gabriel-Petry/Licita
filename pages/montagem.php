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
$valor_estimado = htmlspecialchars($_POST['valor'] ?? 'R$ 0,00', ENT_QUOTES, 'UTF-8');

$modalidade_val = htmlspecialchars($_POST['modalidade'] ?? '1', ENT_QUOTES, 'UTF-8');
$srp_val = htmlspecialchars($_POST['srp-radio'] ?? '0', ENT_QUOTES, 'UTF-8');
$cj_val = htmlspecialchars($_POST['cj'] ?? '1', ENT_QUOTES, 'UTF-8');
$pref_val = htmlspecialchars($_POST['pref'] ?? '0', ENT_QUOTES, 'UTF-8');
$impref_val = '0';
$sigilo_val = '0';

$modalidade_texto = ($modalidade_val == '1') ? "Pregão Eletrônico" : "Concorrência Eletrônica";
$criterio_texto = ($cj_val == '1') ? "Menor Preço" : "Maior Desconto";
$data_hoje = date('d/m/Y');

$page_styles = [
    '/css/montagem.css',
    'https://cdn.jsdelivr.net/npm/@govbr-ds/core@3.6.2/dist/core.min.css'
];

$page_scripts = [
    'https://cdn.jsdelivr.net/npm/@govbr-ds/core@3.6.2/dist/core.min.js',
    '/js/montagem.js'
];

render_header('Montagem do Edital', ['scripts' => $page_scripts, 'styles' => $page_styles]);
?>

<div id="edital-data-store"
     data-modalidade="<?php echo $modalidade_val; ?>"
     data-modalidade-texto="<?php echo $modalidade_texto; ?>"
     data-srp="<?php echo $srp_val; ?>"
     data-objeto="<?php echo $objeto; ?>"
     data-pregao="<?php echo $pregao_num; ?>"
     data-processo="<?php echo $processo_num; ?>"
     data-requisicao="<?php echo $requisicao_num; ?>"
     data-pregoeiro="<?php echo $pregoeiro_nome; ?>"
     data-cj="<?php echo $cj_val; ?>"
     data-cj-texto="<?php echo $criterio_texto; ?>"
     data-pref="<?php echo $pref_val; ?>"
     data-impref="<?php echo $impref_val; ?>"
     data-sigilo="<?php echo $sigilo_val; ?>"
     data-valor="<?php echo $valor_estimado; ?>"
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
                            <span class="title">DATA E HORA</span>
                        </button>
                        <div class="content" id="content-0" style='margin:0;'>
                            <div class="row">
                                <div class="col-lg-6 mb-3">
                                    <div class="br-datetimepicker" data-mode="single" data-type="text">
                                        <div class="br-input has-icon">
                                            <label for="input-data-sessao">Data da Sessão Pública</label>
                                            <input id="input-data-sessao" type="text" placeholder="ex: 02/02/2024" data-input="data-input" />
                                            <button class="br-button circle small" type="button" aria-label="Abrir Timepicker" data-toggle="data-toggle" tabindex="-1">
                                                <i class="fas fa-calendar-alt" aria-hidden="true"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="br-datetimepicker" data-mode="single" data-type="time">
                                        <div class="br-input has-icon">
                                            <label for="hInicial">Hora de Início</label>
                                            <input type="time" placeholder="exemplo: 02:40" id="hInicial" data-input="data-input" value="00:00"/>
                                            <button class="br-button circle small" type="button" aria-label="Abrir Timepicker" data-toggle="data-toggle" tabindex="-1">
                                                <i class="fas fa-clock" aria-hidden="true"></i>
                                            </button>
                                        </div>
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
                        <div class="content" id="content-1" style='margin:0;'>
                            <div class="col mt-3 mb-3 ">
                                <div class="br-textarea">
                                    <label for="control_objeto_accordion">Texto do Objeto</label>
                                    <textarea id="control_objeto_accordion" placeholder="Insira o objeto da licitação"><?php echo $objeto; ?></textarea>
                                </div>

                                <span class="br-divider my-3"></span>
                                
                                <div class="row">
                                    <div class="col-10">
                                        <span>Nota Explicativa sobre o Objeto</span>
                                    </div>
                                    <div class="col-2">
                                        <button class="br-button circle small" type="button" aria-label="Nota Explicativa" onclick="ExibirNota(49)">
                                            <i class="fas fa-chalkboard-teacher" aria-hidden="true"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="br-radio" id="objeto-tipo-group">
                                    <div class="row my-2">
                                        <div class="col-12">
                                            <input id="obj-tipo-item" type="radio" name="objeto-tipo" value="item" />
                                            <label for="obj-tipo-item">A licitação será dividida em itens</label>
                                        </div>
                                    </div>
                                    <div class="row my-2">
                                        <div class="col-12">
                                            <input id="obj-tipo-grupo" type="radio" name="objeto-tipo" value="grupo" />
                                            <label for="obj-tipo-grupo">A licitação será dividida em Grupo</label>
                                        </div>
                                    </div>
                                    <div class="row my-2">
                                        <div class="col-10">
                                            <input id="obj-tipo-item-grupo" type="radio" name="objeto-tipo" value="item-grupo" />
                                            <label for="obj-tipo-item-grupo">A licitação será dividida em Itens e Grupos</label>
                                        </div>
                                        <div class="col-2">
                                            <button class="br-button circle small" type="button" aria-label="Nota Explicativa" onclick="ExibirNota(50)">
                                                <i class="fas fa-chalkboard-teacher" aria-hidden="true"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <div id="objeto-sub-options" class="mt-3 hidden">
                                    
                                    <div id="objeto-item-options" class="br-radio hidden">
                                        <div class="row my-2">
                                            <div class="col-10">
                                                <input id="obj-item-varios" type="radio" name="objeto-item-sub" value="varios-itens"/>
                                                <label for="obj-item-varios">Vários itens</label>
                                            </div>
                                            <div class="col-2"><button class="br-button circle small" type="button" aria-label="Nota Explicativa" onclick="ExibirNota(14)"><i class="fas fa-chalkboard-teacher" aria-hidden="true"></i></button></div>
                                        </div>
                                        <div class="row my-2">
                                            <div class="col-12">
                                                <input id="obj-item-unico" type="radio" name="objeto-item-sub" value="item-unico"/>
                                                <label for="obj-item-unico">Item único</label>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div id="objeto-grupo-options" class="br-radio hidden">
                                        <div class="row my-2">
                                            <div class="col-10">
                                                <input id="obj-grupo-varios" type="radio" name="objeto-grupo-sub" value="varios-grupos"/>
                                                <label for="obj-grupo-varios">Vários grupos</label>
                                            </div>
                                            <div class="col-2"><button class="br-button circle small" type="button" aria-label="Nota Explicativa" onclick="ExibirNota(15)"><i class="fas fa-chalkboard-teacher" aria-hidden="true"></i></button></div>
                                        </div>
                                        <div class="row my-2">
                                            <div class="col-10">
                                                <input id="obj-grupo-unico" type="radio" name="objeto-grupo-sub" value="grupo-unico"/>
                                                <label for="obj-grupo-unico">Grupo único</label>
                                            </div>
                                            <div class="col-2"><button class="br-button circle small" type="button" aria-label="Nota Explicativa" onclick="ExibirNota(15)"><i class="fas fa-chalkboard-teacher" aria-hidden="true"></i></button></div>
                                        </div>
                                    </div>

                                    <div id="objeto-num-itens-container" class="br-input small input-highlight p-1 hidden">
                                        <label for="objeto-num-itens">Número de Itens</label>
                                        <input id="objeto-num-itens" name="objeto-num-itens" type="number" title="Número de Itens"/>
                                    </div>
                                    <div id="objeto-num-grupos-container" class="br-input small input-highlight p-1 hidden">
                                        <label for="objeto-num-grupos">Número de Grupos</label>
                                        <input id="objeto-num-grupos" name="objeto-num-grupos" type="number" title="Número de Grupos"/>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="item" id='item3'>
                        <button class="header" type="button" aria-controls="content-3">
                            <span class="icon"><i class="fas fa-angle-down" aria-hidden="true"></i></span>
                            <span class="title"><span class="numeraparticipacao negrito"></span>. PARTICIPAÇÃO NA LICITAÇÃO</span>
                        </button>
                        <div class="content" id="content-3" style='margin:0;'>
                            <div class="col mt-3 mb-3 ">
                                
                                <div id="participacao-me-epp-container"></div>

                                <span class="br-divider sm p-2"></span>

                                <div class="row mb-3">
                                    <div class="col-10 d-inline-block ">
                                        <div class="br-checkbox small">
                                            <input id="participa-scom" name="participa-scom" type="checkbox"/>
                                            <label for="participa-scom">Serão serviços com dedicação exclusiva de mão de obra?</label>
                                        </div>
                                    </div>
                                </div>
                                
                                <span id="participa-coop-divider" class="br-divider sm p-2"></span>
                                
                                <div id="participa-coop-div" class="row">
                                    <div class="col-10 d-inline-block ">
                                        <div class="br-checkbox small">
                                            <input id="participa-coop" name="participa-coop" type="checkbox"/>
                                            <label for="participa-coop">Vedar participação de sociedades cooperativas?</label>
                                        </div>
                                    </div>
                                    <div class="col-2">
                                        <div class="ml-auto">
                                            <button class="br-button circle small" type="button" aria-label="Nota Explicativa" onclick="ExibirNota(45)">
                                                <i class="fas fa-chalkboard-teacher" aria-hidden="true"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <span class="br-divider sm p-2"></span>
                                
                                <div class="row">
                                    <div class="col-10 d-inline-block ">
                                        <div class="br-checkbox small">
                                            <input id="participa-consorcio" name="participa-consorcio" type="checkbox"/>
                                            <label for="participa-consorcio">Vedar pessoas jurídicas reunidas em consórcio</label>
                                        </div>
                                    </div>
                                    <div class="col-2">
                                        <div class="ml-auto">
                                            <button class="br-button circle small" type="button" aria-label="Nota Explicativa" onclick="ExibirNota(3)">
                                                <i class="fas fa-chalkboard-teacher" aria-hidden="true"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>

                    <div class="item" id='item5'>
                        <button class="header" type="button" aria-controls="content-5">
                            <span class="icon"><i class="fas fa-angle-down" aria-hidden="true"></i></span>
                            <span class="title"><span class="numeraapresentacao negrito"></span>. DA APRESENTAÇÃO DA PROPOSTA E DOS DOCUMENTOS DE HABILITAÇÃO</span>
                        </button>
                        <div class="content" id="content-5" style='margin:0;'>
                            <div class="col mt-3 mb-3 ">
                                <div class="row">
                                    <div class="col-10 d-inline-block ">
                                        <div class="br-checkbox small">
                                            <input id="apresenta-inversao" name="apresenta-inversao" type="checkbox"/>
                                            <label for="apresenta-inversao">Haverá inversão de fase? </label>
                                        </div>
                                    </div>
                                    <div class="col-2">
                                        <div class="ml-auto">
                                            <button class="br-button circle small" type="button" aria-label="Nota Explicativa" onclick="ExibirNota(5)">
                                                <i class="fas fa-chalkboard-teacher" aria-hidden="true"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="item" id='item6'>
                        <button class="header" type="button" aria-controls="content-6">
                            <span class="icon"><i class="fas fa-angle-down" aria-hidden="true"></i></span>
                            <span class="title"><span class="numeraproposta negrito"></span>. DO PREENCHIMENTO DA PROPOSTA</span>
                        </button>
                        <div class="content" id="content-6" style='margin:0;'>
                            <div class="col mt-3 mb-3 ">
                                
                                <div id="proposta-criterio-container">
                                </div>

                                <div id="proposta-scom-container" class="hidden">
                                    <span class="br-divider sm p-2"></span>
                                    <div class="row">
                                        <div class="col-10 d-inline-block ">
                                            <div class="br-checkbox small">
                                                <input id="proposta-veda-simples" name="proposta-veda-simples" type="checkbox"/>
                                                <label for="proposta-veda-simples">Vedação de tributação pelo Simples Nacional</label>
                                            </div>
                                        </div>
                                        <div class="col-2">
                                            <div class="ml-auto">
                                                <button class="br-button circle small" type="button" aria-label="Nota Explicativa" onclick="ExibirNota(7)">
                                                    <i class="fas fa-chalkboard-teacher" aria-hidden="true"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div id="proposta-srp-container" class="hidden">
                                    <span class="br-divider sm p-2"></span>
                                    <div class="row">
                                        <div class="col-10 d-inline-block ">
                                            <div class="br-checkbox small">
                                                <input id="proposta-qtde-minima" name="proposta-qtde-minima" type="checkbox"/>
                                                <label for="proposta-qtde-minima">Informar quantidade mínima?</label>
                                            </div>
                                        </div>
                                        <div class="col-2">
                                            <div class="ml-auto">
                                                <button class="br-button circle small" type="button" aria-label="Nota Explicativa" onclick="ExibirNota(17)">
                                                    <i class="fas fa-chalkboard-teacher" aria-hidden="true"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <span class="br-divider sm p-2"></span>

                                <span class="br-divider sm p-2"></span>
                                <div class="row mb-2">
                                    <div class="col-10 d-inline-block">
                                        <div class="br-checkbox small ">
                                            <input id="proposta-marca" name="proposta-marca" type="checkbox"/>
                                            <label for="proposta-marca">Informar Marca (opcional)</label>
                                        </div>
                                    </div>
                                </div>
                                
                                <span class="br-divider sm p-2"></span>
                                <div class="row mb-2">
                                    <div class="col br-checkbox small ">
                                        <input id="proposta-fabricante" name="proposta-fabricante" type="checkbox"/>
                                        <label for="proposta-fabricante">Informar Fabricante (opcional)</label>
                                    </div>
                                </div>

                                <span class="br-divider sm p-2"></span>
                                <div class="row">
                                    <div class="col-10 d-inline-block ">
                                        <div class="br-input small input-highlight">
                                            <label for="proposta-validade"><small>Prazo de validade (mín. 60 dias)</small></label>
                                            <input id="proposta-validade" name="proposta-validade" type="number" title='Prazo de validade' placeholder='60' value="60" />
                                        </div>
                                    </div>
                                    <div class="col-2">
                                        <div class="ml-auto">
                                            <button class="br-button circle small" type="button" aria-label="Nota Explicativa" onclick="ExibirNota(20)">
                                                <i class="fas fa-chalkboard-teacher" aria-hidden="true"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <span class="br-divider sm p-2"></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="item" id='item7'>
                        <button class="header" type="button" aria-controls="content-7">
                            <span class="icon"><i class="fas fa-angle-down" aria-hidden="true"></i></span>
                            <span class="title"><span class="numeraabertura negrito"></span>. DA ABERTURA DA SESSÃO, CLASSIFICAÇÃO DAS PROPOSTAS E FORMULAÇÃO DE LANCES</span>
                        </button>
                        <div class="content" id="content-7" style='margin:0;'>
                            <div class="col mt-3 mb-3 ">
                                <div class="row">
                                    <div class="col-10 d-inline-block "><span > Nota sobre lances</span></div>
                                    <div class="col-2"><div class="ml-auto">
                                        <button class="br-button circle small" type="button" aria-label="Nota Explicativa" onclick="ExibirNota(18)"><i class="fas fa-chalkboard-teacher" aria-hidden="true"></i></button>
                                    </div></div>
                                </div>
                                <span class="br-divider sm p-2 mt-2"></span>

                                <div id="intervalo-minimo-container"></div>

                                <div id="nota-beneficio-mp-container"></div>
                                
                                <span class="br-divider sm p-2 mt-2"></span>
                                <div class="row">
                                    <div class="col d-inline-block ">
                                        <span class="p-2"><small>Prazo de adequação da proposta ao último lance (min 2 horas)</small></span>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-10 d-inline-block ">
                                        <div class="br-input small input-highlight ">
                                            <label for="abertura-prazo-adequacao">Prazo (em horas)</label>
                                            <input id="abertura-prazo-adequacao" name="abertura-prazo-adequacao" type="number" placeholder='2' value="2" min="2" />
                                        </div>
                                    </div>
                                    <div class="col-2"><div class="ml-auto">
                                        <button class="br-button circle small" type="button" aria-label="Nota Explicativa" onclick="ExibirNota(25)"><i class="fas fa-chalkboard-teacher" aria-hidden="true"></i></button>
                                    </div></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="item" id='item8'>
                        <button class="header" type="button" aria-controls="content-8">
                            <span class="icon"><i class="fas fa-angle-down" aria-hidden="true"></i></span>
                            <span class="title"><span class="numerajulgamento negrito"></span>. DA FASE DE JULGAMENTO</span>
                        </button>
                        <div class="content" id="content-8" style='margin:0;'>
                            <div class="col mt-3 mb-3 ">
                                
                                <div id="julgamento-scom-container" class="hidden">
                                    <div id="julgamento-dissidio-builder"></div>

                                    <span class="br-divider sm p-2 mt-2"></span>
                                    <div class="row">
                                        <div class="col-10 d-inline-block ">
                                            <div class="br-input small input-highlight ">
                                                <label for="julgamento-prazo-readequacao"><small>Prazo p/ readequação da proposta (min 2h)</small></label>
                                                <input id="julgamento-prazo-readequacao" name="julgamento-prazo-readequacao" type="number" placeholder="2" value="2" min="2" />
                                            </div>
                                        </div>
                                    </div>
                                    <span class="br-divider sm p-2 mt-2"></span>
                                </div>

                                <div class="row mb-2">
                                    <div class="col-10 d-inline-block ">
                                        <div class="br-checkbox small">
                                            <input id="julgamento-custos-relevantes" name="julgamento-custos-relevantes" type="checkbox"/>
                                            <label for="julgamento-custos-relevantes">Mencionar custos unitários tidos como relevantes.</label>
                                        </div>
                                    </div>
                                </div>
                                
                                <span class="br-divider sm p-2 mt-2"></span>
                                <div class="row">
                                    <div class="col-10 d-inline-block "><span> Nota sobre apresentação de amostra</span></div>
                                    <div class="col-2"><div class="ml-auto">
                                        <button class="br-button circle small" type="button" aria-label="Nota Explicativa" onclick="ExibirNota(28)"><i class="fas fa-chalkboard-teacher" aria-hidden="true"></i></button>
                                    </div></div>
                                </div>

                            </div>
                        </div>
                    </div>
                    
                    <div class="item" id='item9'>
                        <button class="header" type="button" aria-controls="content-9">
                            <span class="icon"><i class="fas fa-angle-down" aria-hidden="true"></i></span>
                            <span class="title"><span class="numerahabilitacao negrito"></span>. DA FASE DE HABILITAÇÃO</span>
                        </button>
                        <div class="content" id="content-9" style='margin:0;'>
                            <div class="col mt-3 mb-3 ">
                                
                                <div id="habilitacao-me-epp-container"></div>

                                <div id="habilitacao-consorcio-container" class="hidden"></div>
                                
                                <span class="br-divider sm p-2 mt-2"></span>
                                
                                <div class="row">
                                    <div class="col-10 d-inline-block ">
                                        <div class="br-checkbox small">
                                            <input id="habilitacao-outros-meios" name="habilitacao-outros-meios" type="checkbox"/>
                                            <label for="habilitacao-outros-meios">Documentos de Habilitação - definir outros meios admitidos.</label>
                                        </div>
                                    </div>
                                </div>
                                <div id="habilitacao-outros-meios-input-container" class="row hidden mt-2">  
                                    <div class="col mb-3">
                                        <div class="br-input small input-highlight">
                                            <label for="habilitacao-outros-meios-texto">Meio admitido</label>
                                            <input id="habilitacao-outros-meios-texto" name="habilitacao-outros-meios-texto" type="text" placeholder="Indicar meio admitido" />
                                        </div>
                                    </div>
                                </div>
                                
                                <span class="br-divider sm p-2 mt-2"></span>

                                <div class="row">
                                    <div class="col-10 d-inline-block ">
                                        <div class="br-checkbox small">
                                            <input id="habilitacao-vistoria" name="habilitacao-vistoria" type="checkbox"/>
                                            <label for="habilitacao-vistoria">Aquisições ou serviços que dependam de conhecimento do local (Vistoria).</label>
                                        </div>
                                    </div>
                                    <div class="col-2"><div class="ml-auto">
                                        <button class="br-button circle small" type="button" aria-label="Nota Explicativa" onclick="ExibirNota(30)"><i class="fas fa-chalkboard-teacher" aria-hidden="true"></i></button>
                                    </div></div>
                                </div>
                                <div id="habilitacao-vistoria-input-container" class="row hidden mt-2">  
                                    <div class="col mb-3">
                                        <div class="br-input small input-highlight">
                                            <label for="habilitacao-vistoria-texto">Forma de agendamento</label>
                                            <input id="habilitacao-vistoria-texto" name="habilitacao-vistoria-texto" type="text" placeholder="Indicar forma de agendamento" />
                                        </div>
                                    </div>
                                </div>
                                
                                <span class="br-divider sm p-2 mt-2"></span>

                                <div class="row">  
                                    <div class="col">
                                        <span class="p-2"><small>Prazo docs. não contemplados no Sicaf (Mínimo 2 horas)</small></span>
                                        <div class="br-input small input-highlight ">
                                            <input id="habilitacao-prazo-sicaf" name="habilitacao-prazo-sicaf" type="number" min='2' placeholder='2' value="2" />
                                        </div>
                                    </div>
                                </div>
                                <span class="br-divider sm p-2 mt-2"></span>
                                <div class="row">  
                                    <div class="col">
                                        <span class="p-2"><small>Prazo para apresentação de novos documentos (Mínimo 2 horas)</small></span>
                                        <div class="br-input small input-highlight ">
                                            <input id="habilitacao-prazo-novos-docs" name="habilitacao-prazo-novos-docs" type="number" min='2' placeholder='2' value="2" />
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                    
                    <div class="item" id='item10'>
                        <button class="header" type="button" aria-controls="content-10">
                            <span class="icon"><i class="fas fa-angle-down" aria-hidden="true"></i></span>
                            <span class="title"><span class="numeratermo negrito"></span>. DO TERMO DE CONTRATO</span>
                        </button>
                        <div class="content" id="content-10" style='margin:0;'>
                            <div class="col mt-3 mb-3 ">
                                
                                <div class="row"> 
                                    <div class="col d-inline-block ">
                                        <div class="br-input small input-highlight ">
                                            <label for="termo-prazo-assinatura"><small>Prazo para assinatura do contrato (dias)</small></label>
                                            <input id="termo-prazo-assinatura" name="termo-prazo-assinatura" type="number" min='1' placeholder='Ex: 5' />
                                        </div>
                                    </div>
                                </div>
                                <span class="br-divider sm p-2 mt-2"></span>
                                <p class="text-center" style="font-weight: bold;"><small>Meios Alternativos para assinatura</small></p>
                                <div class="row"> 
                                    <div class="col d-inline-block ">
                                        <div class="br-input small input-highlight ">
                                            <label for="termo-prazo-ar"><small>Prazo para envio do AR (dias)</small></label>
                                            <input id="termo-prazo-ar" name="termo-prazo-ar" type="number" min='1' placeholder='Ex: 5' />
                                        </div>
                                    </div>
                                </div>
                                <div class="row mt-3"> 
                                    <div class="col d-inline-block ">
                                        <div class="br-input small input-highlight ">
                                            <label for="termo-prazo-digital"><small>Prazo para assinatura digital (dias)</small></label>
                                            <input id="termo-prazo-digital" name="termo-prazo-digital" type="number" min='1' placeholder='Ex: 5' />
                                        </div>
                                    </div>
                                </div>
                                <div class="row mt-3"> 
                                    <div class="col d-inline-block ">
                                        <div class="br-input small input-highlight ">
                                            <label for="termo-prazo-outro"><small>Prazo outro meio (dias)</small></label>
                                            <input id="termo-prazo-outro" name="termo-prazo-outro" type="number" min='1' placeholder='Ex: 5' />
                                        </div>
                                    </div>
                                </div>
                                
                                <span class="br-divider sm p-2 mt-2"></span>

                                <div class="row">
                                    <div class="col-10 d-inline-block ">
                                        <div class="br-checkbox small mb-3">
                                            <input id="termo-nota-empenho" name="termo-nota-empenho" type="checkbox"/>
                                            <label for="termo-nota-empenho">Aceite da Nota de Empenho ou do instrumento equivalente.</label>
                                        </div>
                                    </div>
                                </div>

                                <div id="termo-scom-container" class="hidden">
                                    <span class="br-divider sm p-2"></span>
                                    <div class="row mb-3">
                                        <div class="col d-inline-block ">
                                            <div class="br-checkbox small">
                                                <input id="termo-scom-mais-25" name="termo-scom-mais-25" type="checkbox"/>
                                                <label for="termo-scom-mais-25">Contratos com 25 ou mais colaboradores?</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div id="termo-scom-input-container" class="row hidden"> 
                                        <div class="col d-inline-block ">
                                            <div class="br-input small input-highlight ">
                                                <label for="termo-scom-percentual"><small>% de vagas para mulheres vítimas de violência</small></label>
                                                <input id="termo-scom-percentual" name="termo-scom-percentual" type="number" min='2' max='8' placeholder='Ex: 2' />
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>

                    <div class="item" id='item16'>
                        <button class="header" type="button" aria-controls="content-16">
                            <span class="icon"><i class="fas fa-angle-down" aria-hidden="true"></i></span>
                            <span class="title"><span class="numeragerais"></span>. DISPOSIÇÕES GERAIS</span>
                        </button>
                        <div class="content" id="content-16">
                            <div class="row"> 
                                <div class="col d-inline-block ">
                                    <div class="br-input small input-highlight ">
                                        <label for="gerais-1"><small>Endereço Eletrônico para consulta</small></label>
                                        <input id="gerais-1" name="gerais-1" type="text" placeholder="Endereço Eletrônico" required />
                                    </div>
                                </div>
                            </div>
                            <span class="br-divider sm p-2 mt-2"></span>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <form id="pdf-form" method="POST" action="finalizar_pdf.php" target="_blank" style="margin-top: 1.5rem;">
            <input type="hidden" name="edital_html" id="hidden_html_content">
            <div class="card">
                <div class="card-footer form-actions" style="display: flex; justify-content: flex-end; gap: 1rem;">
                    <button class="br-button secondary" type="button" id="btn-salvar-html"><i class="fas fa-file-code" aria-hidden="true"></i> Salvar HTML</button>
                    <button class="br-button primary" type="submit" id="btn-gerar-pdf">Gerar PDF</button>
                </div>
            </div>
        </form>

    </div>

    <div class="editor-preview">
        <div class="right" id="right">
            
            <div style='background-color:#ffffff; padding-top:100px;margin-right: 2cm; margin-left: 2cm;' id="topoPagina">
                
                <p style='text-align: center;'><b>MODELO DE EDITAL</b></p><BR>
                <p style='text-align: center;'><b><span class="modalidade" aria-label="M"><?php echo strtoupper($modalidade_texto); ?></span></b></p>
                <p style='text-align: center;'><b><mark><span id="orgao1">NOME DO ÓRGÃO (Ajustar no JS)</span></mark></b></p>
                <p>&nbsp;</p>
                <p style='text-align: center;'><b><span class="modalidade" aria-label="M"><?php echo strtoupper($modalidade_texto); ?></span> Nº <mark><span id="pregao"><?php echo $pregao_num; ?></span></mark></b></p>
                <p style='text-align: center;'><b><mark><span id="processoadm"><?php echo $processo_num; ?></span></mark></b></p>
                
                <p class="beforesub"> Data da Sessão Pública: <mark><span id="data_evento"></span></mark></p>
                <p class="beforesub"> Hora Inicial : <mark><span id="hora_inicial"></span></mark></p>
                <p>&nbsp;</p>
                
                <p><b>1. DO OBJETO</b></p>
                <p> 1.1. O objeto da presente licitação é <mark><span id="objeto"><?php echo $objeto; ?></span></mark> conforme condições, quantidades e exigências estabelecidas neste Edital e seus anexos.</p>
                <div id="preview-objeto-detalhe"></div>

                <div id="dynamic-content-container">

                    <p>&nbsp;</p>
                    <p><b><span class="numeraparticipacao negrito"></span>. DA PARTICIPAÇÃO NA LICITAÇÃO</b></p>
                    <p>&nbsp;</p>
                    <p class=""><span class="numeraparticipacao"></span>.<span class="layer1numeraparticipacao"></span>. Poderão participar deste certame...</p>
                    <p class=""><span class="numeraparticipacao"></span>.<span class="layer1numeraparticipacao"></span>. ...É de responsabilidade do cadastrado...</p>
                    <div id="preview-participacao-exclusiva"></div>
                    <div id="preview-participacao-tratamento"></div> 
                    <p class="beforesub"><span class="numeraparticipacao ref10"></span>.<span class="layer1numeraparticipacao ref10"></span>. Não poderão disputar esta licitação:</p>
                    <p class="sub1"><span class="numeraparticipacao"></span>.<span class="layer1numeraparticipacao"></span>.<span class="layer2numeraparticipacao"></span>. aquele que não atenda...</p>
                    <div id="preview-participacao-cooperativa"></div>
                    <div id="preview-participacao-consorcio"></div>
                    <p class="sub1 aftersub"><span class="numeraparticipacao"></span>.<span class="layer1numeraparticipacao"></span>.<span class="layer2numeraparticipacao last"></span>. Organizações da Sociedade Civil...</p>
                    <p class=""><span class="numeraparticipacao ref6"></span>.<span class="layer1numeraparticipacao ref6"></span>. Não poderá participar, direta ou indiretamente...</p>
                    
                    <p>&nbsp;</p>
                    <p><b><span class="numeraapresentacao negrito"></span>. DA APRESENTAÇÃO DA PROPOSTA E DOS DOCUMENTOS DE HABILITAÇÃO</b></p>
                    <p>&nbsp;</p>
                    <div id="preview-apresentacao-inversao"></div>
                    <p class=""><span class="numeraapresentacao"></span>.<span class="layer1numeraapresentacao"></span>. Os licitantes encaminharão... proposta com o <span id="apresentacao21">preço</span>...</p>
                    <div id="preview-apresentacao-inversao-docs"></div>
                    <p class="beforesub"><span class="numeraapresentacao ref15"></span>.<span class="layer1numeraapresentacao ref15"></span>. No cadastramento da proposta inicial...</p>
                    <p class="sub1"><span class="numeraapresentacao"></span>.<span class="layer1numeraapresentacao"></span>.<span class="layer2numeraapresentacao"></span>. está ciente e concorda...</p>
                    <p class=""><span class="numeraapresentacao"></span>.<span class="layer1numeraapresentacao"></span>. O licitante deverá comunicar imediatamente...</p>
                    
                    <p>&nbsp;</p>
                    <p><b><span class="numeraproposta negrito"></span>. DO PREENCHIMENTO DA PROPOSTA</b></p>
                    <p>&nbsp;</p>
                    <p class="beforesub"><span class="numeraproposta"></span>.<span class="layer1numeraproposta"></span>. O licitante deverá enviar sua proposta...</p>
                    <div id="preview-proposta-criterio"></div>
                    <div id="preview-proposta-marca"></div>
                    <div id="preview-proposta-fabricante"></div>
                    <div id="preview-proposta-tabela"></div>
                    <p class="beforesub"><span class="numeraproposta"></span>.<span class="layer1numeraproposta"></span>. Todas as especificações do objeto...</p>
                    <div id="preview-proposta-scom"></div>
                    <div id="preview-proposta-simples"></div>
                    <div id="preview-proposta-srp"></div>
                    <p class=""><span class="numeraproposta"></span>.<span class="layer1numeraproposta"></span>. A apresentação das propostas implica...</p>
                    <p class=""><span class="numeraproposta"></span>.<span class="layer1numeraproposta"></span>. O prazo de validade da proposta... <mark><span id="preview-proposta-validade">60 (sessenta)</span></mark> dias...</p>
                    
                    <p>&nbsp;</p>
                    <p><b><span class="numeraabertura negrito"></span>. DA ABERTURA DA SESSÃO, CLASSIFICAÇÃO DAS PROPOSTAS E FORMULAÇÃO DE LANCES</b></p>
                    <p>&nbsp;</p>
                    <p class=""><span class="numeraabertura"></span>.<span class="layer1numeraabertura"></span>. A abertura da presente licitação...</p>
                    <p class=""><span class="numeraabertura"></span>.<span class="layer1numeraabertura"></span>. ...O sistema disponibilizará campo próprio...</p>
                    <p class=""><span class="numeraabertura"></span>.<span class="layer1numeraabertura"></span>. Iniciada a etapa competitiva...</p>
                    <p class=""><span class="numeraabertura"></span>.<span class="layer1numeraabertura"></span>. O lance deverá ser ofertado pelo <mark><span id="preview-abertura-lance-tipo">...</span></mark></p>
                    <p class=""><span class="numeraabertura"></span>.<span class="layer1numeraabertura"></span>. ...lance de <mark><span id="preview-abertura-lance-valor">...</span></mark> ao último...</p>
                    <p class=""><span class="numeraabertura"></span>.<span class="layer1numeraabertura"></span>. O intervalo mínimo de... <mark><span id="preview-abertura-intervalo-tipo">...</span></mark> ...será de <mark><span id="preview-abertura-intervalo-valor">...</span></mark></p>
                    <div id="preview-abertura-tabela-intervalo"></div>
                    <p class=""><span class="numeraabertura"></span>.<span class="layer1numeraabertura"></span>. O procedimento seguirá... modo de disputa <mark><span id="preview-abertura-modo-disputa">...</span></mark>.</p>
                    <div id="preview-abertura-modo-disputa-regras"></div>
                    <div id="preview-abertura-margem-pref"></div>
                    <div id="preview-abertura-empate-me"></div>
                    <p class="beforesub"><span class="numeraabertura"></span>.<span class="layer1numeraabertura"></span>. Havendo eventual empate...</p>
                    <p class="sub1"><span class="numeraabertura"></span>.<span class="layer1numeraabertura"></span>.<span class="layer2numeraabertura"></span>. disputa final...</p>
                    <p class="beforesub"><span class="numeraabertura"></span>.<span class="layer1numeraabertura"></span>. Encerrada a etapa de envio de lances...</p>
                    <p class="sub1"><span class="numeraabertura"></span>.<span class="layer1numeraabertura"></span>.<span class="layer2numeraabertura"></span>. ...A negociação poderá ser feita...</p>
                    <p class="sub1"><span class="numeraabertura"></span>.<span class="layer1numeraabertura"></span>.<span class="layer2numeraabertura"></span>. O <span class="agente" aria-label="M"></span> solicitará... prazo de <mark><span id="preview-abertura-prazo-adequacao">2 (duas)</span></mark> horas...</p>
                    
                    <p>&nbsp;</p>
                    <p><b><span class="numerajulgamento negrito"></span>. DA FASE DE JULGAMENTO</b></p>
                    <p>&nbsp;</p>
                    <p class="beforesub"><span class="numerajulgamento"></span>.<span class="layer1numerajulgamento"></span>. Encerrada a etapa de negociação...</p>
                    <p class=""><span class="numerajulgamento"></span>.<span class="layer1numerajulgamento"></span>. Verificadas as condições de participação...</p>
                    <div id="preview-julgamento-scom-dissidios"></div>
                    <p class="beforesub"><span class="numerajulgamento"></span>.<span class="layer1numerajulgamento"></span>. Será desclassificada a proposta...</p>
                    <p class="sub1 aftersub"><span class="numerajulgamento"></span>.<span class="layer1numerajulgamento"></span>.<span class="layer2numerajulgamento last"></span>. apresentar desconformidade...</p>
                    <p class="beforesub"><span class="numerajulgamento"></span>.<span class="layer1numerajulgamento"></span>. Em contratação de obras e serviços de engenharia...</p>
                    <p class="sub1"><span class="numerajulgamento"></span>.<span class="layer1numerajulgamento"></span>.<span class="layer2numerajulgamento"></span>. ...sobrepreço se dará pela superação do valor global...</p>
                    <p class="sub1"><span class="numerajulgamento"></span>.<span class="layer1numerajulgamento"></span>.<span class="layer2numerajulgamento"></span>. No regime de empreitada por preço unitário...<mark><span id="preview-julgamento-custos">.</span></mark></p>
                    <div id="preview-julgamento-scom-produtividade"></div>
                    <p class=""><span class="numerajulgamento"></span><span class="indproposta10"></span>.<span class="layer1numerajulgamento"></span>. Para fins de análise da proposta...</p>
                    <div id="preview-julgamento-scom-declaracoes"></div>

                    <p>&nbsp;</p>
                    <p><b><span class="numerahabilitacao negrito"></span>. DA FASE DE HABILITAÇÃO</b></p>
                    <p>&nbsp;</p>
                    <p class="beforesub"><span class="numerahabilitacao"></span>.<span class="layer1numerahabilitacao"></span>. Os documentos previstos no Termo de Referência...</p>
                    <p class="sub1 aftersub"><span class="numerahabilitacao ref7"></span>.<span class="layer1numerahabilitacao ref7"></span>.<span class="layer2numerahabilitacao last ref7"></span>. A documentação... poderá ser substituída pelo... SICAF.</p>
                    <div id="preview-habilitacao-consorcio"></div>
                    <div id="preview-habilitacao-me-epp"></div>
                    <p class=""><span class="numerahabilitacao"></span>.<span class="layer1numerahabilitacao"></span>. Os documentos... poderão ser apresentados em original<mark><span id="preview-habilitacao-outros-meios"></span></mark>.</p>
                    <p class=""><span class="numerahabilitacao"></span>.<span class="layer1numerahabilitacao"></span>. O licitante deverá apresentar... declaração de que sua proposta...</p>
                    <div id="preview-habilitacao-vistoria"></div>
                    <p class="beforesub"><span class="numerahabilitacao"></span>.<span class="layer1numerahabilitacao"></span>. A habilitação será verificada por meio do Sicaf...</p>
                    <p class="beforesub"><span class="numerahabilitacao"></span>.<span class="layer1numerahabilitacao"></span>. A verificação pelo <span class="agente" aria-label="M"></span>...</p>
                    <div id="preview-habilitacao-inversao-fases-sicaf"></div>
                    <p class="sub1 aftersub"><span class="numerahabilitacao ref13"></span>.<span class="layer1numerahabilitacao ref13"></span>.<span class="layer2numerajulgamento last ref13"></span>. Os documentos... não contemplados no Sicaf... no prazo de <mark><span id="preview-habilitacao-prazo-sicaf">2 (duas)</span> horas</mark>...</p>
                    <p class="beforesub"><span class="numerahabilitacao"></span>.<span class="layer1numerahabilitacao"></span>. Encerrado o prazo... poderá ser admitida... novos documentos... em até <mark><span id="preview-habilitacao-prazo-novos-docs">2 (duas)</span></mark> horas...</p>
                    <p class=""><span class="numerahabilitacao"></span>.<span class="layer1numerahabilitacao"></span>. Na análise dos documentos de habilitação...</p>
                    <div id="preview-habilitacao-inversao-fases-final"></div>
                    
                    <p>&nbsp;</p>
                    <p><b><span class="numeratermo negrito"></span>. DO TERMO DE CONTRATO</b></p>
                    <p>&nbsp;</p>

                    <p class=""><span class="numeratermo"></span>.<span class="layer1numeratermo"></span>. Após a homologação e adjudicação... será firmado termo de contrato...</p>
                    <p class=""><span class="numeratermo ref11"></span>.<span class="layer1numeratermo ref11"></span>. O adjudicatário terá o prazo de <mark><span id="preview-termo-prazo-assinatura">[PRAZO]</span></mark> dias úteis... para assinar o termo de contrato...</p>
                    <p class="beforesub"><span class="numeratermo ref12"></span>.<span class="layer1numeratermo ref12"></span>. Alternativamente à convocação... a Administração poderá:</p>
                    <p class="sub1">a) encaminhá-lo para assinatura, mediante... AR, ...no prazo de <mark><span id="preview-termo-prazo-ar">[PRAZO]</span></mark> dias úteis...</p>
                    <p class="sub1">b) disponibilizar acesso a sistema... para que seja assinado digitalmente em até <mark><span id="preview-termo-prazo-digital">[PRAZO]</span></mark> dias úteis...</p>
                    <p class="sub1 aftersub">c) outro meio eletrônico, assegurado o prazo de <mark><span id="preview-termo-prazo-outro">[PRAZO]</span></mark> dias úteis...</p>
                    
                    <div id="preview-termo-nota-empenho"></div>
                    <div id="preview-termo-scom"></div>

                    <p class=""><span class="numeratermo"></span>.<span class="layer1numeratermo"></span>. O prazo de vigência da contratação é o estabelecido no Termo de Referência.</p>
                    <p class="beforesub"><span class="numeratermo"></span>.<span class="layer1numeratermo"></span>. Na assinatura do contrato... será exigido o... Cadin...</p>

                <p>&nbsp;</p>
                <p><b><span class="numeragerais"></span>. DAS DISPOSIÇÕES GERAIS</b></p>
                <p><span class="numeragerais"></span>.<span class="layer1numeragerais"></span>. O Edital e seus anexos estão disponíveis... em <mark><span id="gerais1">................</span></mark></p>
                <p>&nbsp;</p>
                <p class=""> <span id="cidade_data">Sapucaia do Sul, </span> <span id="dataPorExtenso"><?php echo $data_hoje; ?></span></p>
                
                <p class="centralizado"> ............................................................................ </p>
                <p class="centralizado" id="nome_preg"> <mark><?php echo $pregoeiro_nome; ?></mark> </p>
                <p class="centralizado" id="cargo_preg"> <mark>Pregoeiro/Agente (Ajustar no JS)</mark> </p>

			</div>
        </div> 
    </div>

</div> 
<?php
render_footer();
?>