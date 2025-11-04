<?php
// Carrega o layout.php, que por sua vez já carrega o auth.php
require_once __DIR__ . '/../includes/layout.php';

// Esta é a forma correta de verificar o login, baseada no seu layout.php
$user = current_user();
if (!$user) {
    header('Location: /login'); // Redireciona para o login se não houver usuário
    exit;
}

// Verifica a permissão
if (!tem_permissao('licitacoes.ver')) { 
    header('Location: /sem_permissao');
    exit;
}

// Define o script JS específico que esta página irá carregar
$page_scripts = ['/js/gerar_edital.js'];

// Renderiza o cabeçalho da página
render_header('Módulo Gerador de Edital', ['scripts' => $page_scripts]);
?>

<div class="card" style="margin-top: 2rem; max-width: 1000px; margin-left: auto; margin-right: auto;">
    <div class="card-header">
        <h2>Gerador de Capa de Edital</h2>
    </div>
    
    <form method="POST" action="montagem/index.php">
        <div class="card-content">
            <div class="param-grid">
            <div class="radio-box" style="max-width: 400px; margin-bottom: 1.5rem;">
                <p>Modalidade</p>
                <div class="radio-group">
                    <label class="radio-label">
                        <input id="mdpregao" type="radio" name="modalidade" value="1" checked="checked"/>
                        Pregão
                    </label>
                    <label class="radio-label">
                        <input id="mdconcorrencia" type="radio" name="modalidade" value="0" />
                        Concorrência
                    </label>
                </div>
            </div>
            
            <div class="radio-box" style="max-width: 400px; margin-bottom: 1.5rem;">
                <p>SRP</p>
                <div class="radio-group">
					<label class="radio-label"><input id="com_srp" type="radio" name="srp-radio" value="1" checked="checked"/> Sim</label>
					<label class="radio-label"><input id="sem_srp" type="radio" name="srp-radio" value="0" /> Não</label>
                </div>
            </div>
                
</div>
            <div class="form-group">
                <label for="objeto">Objeto da licitação</label>
                <textarea id="objeto" rows="4" name="objeto" placeholder="Insira o objeto da licitação" required ></textarea>
            </div>
            
            <div class="row">
                <div class="col">
                    <div class="form-group">
                        <label for="pregao">Nº do Edital (Formato 00000/AAAA)</label>
                        <input id="pregao" name="pregao" type="text" pattern="\d{1,5}/\d{4}"  placeholder="00000/2025" required/>
                    </div>
                </div>
                <div class="col">
                    <div class="form-group">
                        <label for="processoadm">Processo Administrativo</label>
                        <input id="processoadm" name="processoadm" type="text"  placeholder="Processo Administrativo nº..."  value="" required/>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col">
                    <div class="form-group">
                        <label for="requisicao">Nº da Requisição</label>
                        <input id="requisicao" name="requisicao" type="text" placeholder="00000/2025" required />
                    </div>
                </div>
                <div class="col">
                    <div class="form-group">
                        <label for="pregoeiro">Pregoeiro(a) Responsável</label>
                        <input id="pregoeiro" name="pregoeiro" type="text" placeholder="Nome do Pregoeiro(a)" required />
                    </div>
                </div>
            </div>

            <hr style="margin: 1.5rem 0;">
            
            <p style="font-weight: bold; font-size: 1.1em;">Parâmetros da Licitação</p>
            
            <div class="param-grid">
                
                <div class="radio-box">
                    <p>Critério de Julgamento</p>
                    <div class="radio-group">
                        <label class="radio-label"><input id="menor_preco" type="radio" name="cj" value="1" checked="checked"/> Menor Preço</label>
                        <label class="radio-label"><input id="maior_desconto" type="radio" name="cj" value="0" /> Maior Desconto</label>
                    </div>
                </div>
                
                <div class="radio-box">
                    <p>Tratamento ME/EPP</p>
                    <div class="radio-group">
                        <label class="radio-label"><input id="com_pref" type="radio" name="pref" value="1" checked="checked"/> Sim</label>
                        <label class="radio-label"><input id="sem_pref" type="radio" name="pref" value="0" /> Não</label>
                    </div>
                </div>

                <div class="radio-box">
                    <p>Valor Estimado</p>
                    <div class="radio-group">
                        <label class="radio-label">
                            <input id="ck_valor2" type="radio" name="ck_valor" value="0" checked="checked" />
                            Divulgado
                        </label>
                    </div>
                    <div id="isigiloso" class="form-group" style="margin-top: 10px; margin-bottom: 0;">
                        <label for="valor" style="display: none;">Valor</label>
                        <input id="valor" name="valor" type="text" placeholder="R$ 0,00" required />
                    </div>
                </div>
            </div> </div> <div class="card-footer form-actions" style="text-align: right;">
            <button class="btn" type="submit">Avançar</button>
        </div>
    </form>
</div>

<style>
    /* Estilos gerais do formulário */
    .card-content .row {
        display: flex;
        flex-wrap: wrap;
        gap: 1rem;
    }
    .card-content .col {
        flex: 1;
        min-width: 250px;
    }

    /* * A GRELHA (GRID) PARA OS PARÂMETROS
     * Esta é a alteração principal para forçar 3 colunas.
    */
    .card-content .param-grid {
        display: grid;
        /* Força 3 colunas de largura igual */
        grid-template-columns: repeat(3, 1fr); 
        gap: 1rem; /* Espaçamento entre os "quadradinhos" */
        margin-top: 1rem;
    }

    /* O "quadradinho" (caixa) */
    .card-content .radio-box {
        border: 1px solid var(--border-color, #ddd); 
        background-color: var(--card-bg, #fff);
        border-radius: 8px;
        padding: 1rem;
        box-sizing: border-box;
        display: flex; /* Usado para garantir altura igual */
        flex-direction: column;
    }

    /* Título dentro do quadradinho */
    .card-content .radio-box p {
        font-weight: 500;
        margin-top: 0;
        margin-bottom: 0.75rem;
    }

    /* O contêiner flex para os botões de rádio */
    .card-content .radio-box .radio-group {
        display: flex;
        flex-direction: row; /* Alinha lado a lado */
        flex-wrap: wrap;     /* PERMITE quebra de linha (ex: Modo de Disputa) */
        gap: 0.75rem 1rem;  /* Espaçamento vertical e horizontal */
    }

    /* * O RÓTULO (LABEL) DO RÁDIO
     * Esta é a regra mais importante para combater o 'display: block'
     * do seu 'base.css'.
    */
    .card-content .radio-box .radio-group .radio-label {
        display: inline-flex !important;  /* Força a ficar na mesma linha */
        width: auto !important;           /* Força a largura a ser automática */
        align-items: center !important;   /* Alinha bolinha e texto */
        margin: 0 !important;             /* Remove margens globais */
        padding: 0 !important;            /* Remove paddings globais */
        gap: 0.5rem;                      /* Espaço entre a bolinha e o texto */
        cursor: pointer;
    }
    
    /* A "bolinha" do rádio */
    .card-content .radio-box .radio-label input[type="radio"] {
        width: auto !important;
        height: auto !important;
        margin: 0 !important;
        display: inline-block !important;
        flex-shrink: 0; /* Impede que a bolinha seja espremida */
    }

    /* Ajuste para o campo de valor */
    .card-content .radio-box #isigiloso {
        margin-bottom: 0;
        margin-top: auto; /* Empurra para o final da caixa */
        padding-top: 10px;
    }

    /* * RESPONSIVIDADE
     * Em ecrãs pequenos (ex: telemóveis), muda a grelha de 3 colunas para 1 coluna.
    */
    @media (max-width: 768px) {
        .card-content .param-grid {
            grid-template-columns: 1fr; /* 1 coluna */
        }
    }
</style>
<?php
// Renderiza o rodapé
render_footer();
?>