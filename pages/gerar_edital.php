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

$page_scripts = ['/js/gerar_edital.js'];

render_header('Módulo Gerador de Edital', ['scripts' => $page_scripts]);
?>

<div class="card" style="margin-top: 2rem; max-width: 1000px; margin-left: auto; margin-right: auto;">
    <div class="card-header">
        <h2>Gerador de Capa de Edital</h2>
    </div>
    
    <form method="POST" action="montagem">
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
                        <label class="radio-label"><input id="sem_srp" type="radio" name="srp-radio" value="0"/> Não</label>
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
                        <label for="pregoeiro">Pregoeiro(a)/Agente Responsável</label>
                        <input id="pregoeiro" name="pregoeiro" type="text" placeholder="Nome" required />
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
                        <label class="radio-label"><input id="com_pref" type="radio" name="pref" value="1"/> Sim</label>
                        <label class="radio-label"><input id="sem_pref" type="radio" name="pref" value="0" checked="checked"/> Não</label>
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
                        <input id="valor" name="valor" type="text" placeholder="0,00" required />
                    </div>
                </div>
            </div> 
        </div> 
        <div class="card-footer form-actions" style="text-align: right;">
            <button class="btn" type="submit">Avançar</button>
        </div>
    </form>
</div>

<style>
    .card-content .row {
        display: flex;
        flex-wrap: wrap;
        gap: 1rem;
    }
    .card-content .col {
        flex: 1;
        min-width: 250px;
    }
    .card-content .param-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr); 
        gap: 1rem;
        margin-top: 1rem;
    }
    .card-content .radio-box {
        border: 1px solid var(--border-color, #ddd); 
        background-color: var(--card-bg, #fff);
        border-radius: 8px;
        padding: 1rem;
        box-sizing: border-box;
        display: flex;
        flex-direction: column;
    }
    .card-content .radio-box p {
        font-weight: 500;
        margin-top: 0;
        margin-bottom: 0.75rem;
    }
    .card-content .radio-box .radio-group {
        display: flex;
        flex-direction: row;
        flex-wrap: wrap; 
        gap: 0.75rem 1rem; 
    }
    .card-content .radio-box .radio-group .radio-label {
        display: inline-flex !important; 
        width: auto !important; 
        align-items: center !important; 
        margin: 0 !important; 
        padding: 0 !important; 
        gap: 0.5rem; 
        cursor: pointer;
    }
    
    .card-content .radio-box .radio-label input[type="radio"] {
        width: auto !important;
        height: auto !important;
        margin: 0 !important;
        display: inline-block !important;
        flex-shrink: 0;
    }
    .card-content .radio-box #isigiloso {
        margin-bottom: 0;
        margin-top: auto;
        padding-top: 10px;
    }
    @media (max-width: 768px) {
        .card-content .param-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
<?php
render_footer();
?>