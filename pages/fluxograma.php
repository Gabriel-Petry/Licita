<?php

require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/auth.php';

$page_scripts = [
    'https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js',
    'https://cdnjs.cloudflare.com/ajax/libs/lodash.js/4.17.21/lodash.min.js',
    'https://cdnjs.cloudflare.com/ajax/libs/backbone.js/1.4.0/backbone-min.js',
    'https://cdnjs.cloudflare.com/ajax/libs/jointjs/3.4.1/joint.min.js',
    '/js/fluxograma.js' 
];

render_header('Editor de Fluxograma', ['scripts' => $page_scripts]);

?>

<div class="card">
    <div class="card-header">
        <h2>Editor de Fluxograma</h2>
        <p>Arraste os componentes para a área de desenho. Use CTRL+Roda do Mouse para zoom e ALT+Arrastar para mover a tela.</p>

        <div class="save-form">
            <input type="text" id="fluxograma-nome" placeholder="Dê um nome ao seu fluxograma">
            <button class="btn primary" id="btn-salvar-fluxograma">Salvar</button>
        </div>
    </div>

    <div id="app-container">
        <div id="sidebar">
            <h3>Componentes</h3>
            <div class="shape-item" data-type="processo">Processo</div>
            <div class="shape-item" data-type="decisao">Decisão</div>
            <div class="shape-item" data-type="inicio_fim">Início / Fim</div>
            <div class="shape-item" data-type="document">Documento</div>
            <div class="shape-item" data-type="module">Módulo</div>
            <div class="shape-item" data-type="annotation">Anotação</div>
        </div>

        <div id="paper-container"></div>

        <div id="properties-panel">
            <h3>Propriedades</h3>
            <label for="prop-title">Título:</label>
            <input type="text" id="prop-title">

            <label for="prop-desc">Descrição:</label>
            <textarea id="prop-desc" rows="3"></textarea>

            <div class="size-inputs">
                <div>
                    <label for="prop-width">Largura:</label>
                    <input type="number" id="prop-width" min="10">
                </div>
                <div>
                    <label for="prop-height">Altura:</label>
                    <input type="number" id="prop-height" min="10">
                </div>
            </div>

            <label for="prop-bg-color">Cor de Fundo:</label>
            <input type="color" id="prop-bg-color" value="#ffffff">

            <label for="prop-border-color">Cor da Borda:</label>
            <input type="color" id="prop-border-color" value="#000000">

            <label for="prop-text-color">Cor do Texto:</label>
            <input type="color" id="prop-text-color" value="#000000">
            
            <button id="btn-remove">Remover Bloco</button>
        </div>
    </div>
</div>

<?php
render_footer();
?>
