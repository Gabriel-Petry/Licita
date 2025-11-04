const Edital = {
    dados: {},
    elementos: {},
    estado: {
        // Objeto
        objetoTipo: null, objetoSubTipo: null, objetoNumItens: '', objetoNumGrupos: '',
        // Participação
        participacaoExclusiva: false, participacaoExclusivaItens: '',
        participacaoExcluirTratamento: false, participacaoExcluirTratamentoItens: '',
        isSCOM: false, vedarCooperativas: false, vedarConsorcio: false,
        // Apresentação
        inversaoFases: false,
        // Seção 6 Proposta
        propostaTabelaItens: [], propostaMarca: false, propostaFabricante: false,
        propostaValidade: 60, propostaVedaSimples: false, propostaQtdeMinima: false,
        // Seção 7 Abertura
        aberturaPrazoAdequacao: 2, aberturaTabelaIntervalo: [],
        aberturaIntervaloUnico: false, aberturaIntervaloValorUnico: 'R$ 0,01',
        // Seção 8 Julgamento
        julgamentoCustosRelevantes: false,
        julgamentoPrazoReadequacao: 2,
        julgamentoTabelaDissidios: [],
        // Seção 9 Habilitação
        habilitacaoConsorcioPercentual: '',
        habilitacaoOutrosMeios: false, habilitacaoOutrosMeiosTexto: '',
        habilitacaoVistoria: false, habilitacaoVistoriaTexto: '',
        habilitacaoPrazoSicaf: 2, habilitacaoPrazoNovosDocs: 2,
        // --- NOVO: Seção 10 Termo de Contrato ---
        termoPrazoAssinatura: '',
        termoPrazoAR: '',
        termoPrazoDigital: '',
        termoPrazoOutro: '',
        termoNotaEmpenho: false,
        termoScomMais25: false,
        termoScomMenos25Percentual: '',
    },
};

/**
 * Ponto de entrada principal do script.
 */
document.addEventListener('DOMContentLoaded', () => {
    
    const dataStore = document.getElementById('edital-data-store');
    if (dataStore) Edital.dados = { ...dataStore.dataset };
    else return console.error('ERRO: Div #edital-data-store não encontrada.');
    Edital.dados.modoDisputa = '1'; // 1 = Aberto (Fixo)

    // 2. MAPEAMENTO DOS ELEMENTOS DO DOM
    Edital.elementos = {
        // ... (elementos seções 1-5 omitidos) ...
        inputDataSessao: document.getElementById('input-data-sessao'),
        inputHoraInicial: document.getElementById('hInicial'),
        textareaObjeto: document.getElementById('control_objeto_accordion'),
        previewDataSessao: document.getElementById('data_evento'),
        previewHoraInicial: document.getElementById('hora_inicial'),
        previewObjeto: document.getElementById('objeto'),
        previewPregoeiro: document.getElementById('nome_preg'),
        previewObjetoDetalhe: document.getElementById('preview-objeto-detalhe'),
        previewSpansModalidade: document.querySelectorAll('.modalidade'),
        objetoSubOptions: document.getElementById('objeto-sub-options'),
        objetoItemOptions: document.getElementById('objeto-item-options'),
        objetoGrupoOptions: document.getElementById('objeto-grupo-options'),
        objetoNumItensContainer: document.getElementById('objeto-num-itens-container'),
        objetoNumGruposContainer: document.getElementById('objeto-num-grupos-container'),
        objetoTipoGroup: document.getElementById('objeto-tipo-group'),
        objetoItemSubGroup: document.getElementById('objeto-item-options'),
        objetoGrupoSubGroup: document.getElementById('objeto-grupo-options'),
        inputNumItens: document.getElementById('objeto-num-itens'),
        inputNumGrupos: document.getElementById('objeto-num-grupos'),
        participacaoMeEppContainer: document.getElementById('participacao-me-epp-container'),
        participaCoopDiv: document.getElementById('participa-coop-div'),
        participaCoopDivider: document.getElementById('participa-coop-divider'),
        checkSCOM: document.getElementById('participa-scom'),
        checkVedarCoop: document.getElementById('participa-coop'),
        checkVedarConsorcio: document.getElementById('participa-consorcio'),
        previewParticipacaoExclusiva: document.getElementById('preview-participacao-exclusiva'),
        previewParticipacaoTratamento: document.getElementById('preview-participacao-tratamento'),
        previewParticipacaoCooperativa: document.getElementById('preview-participacao-cooperativa'),
        previewParticipacaoConsorcio: document.getElementById('preview-participacao-consorcio'),
        checkInversaoFases: document.getElementById('apresenta-inversao'),
        previewInversaoFases: document.getElementById('preview-apresentacao-inversao'),
        previewInversaoFasesDocs: document.getElementById('preview-apresentacao-inversao-docs'),
        // Seção 6
        propostaCriterioContainer: document.getElementById('proposta-criterio-container'),
        checkPropostaMarca: document.getElementById('proposta-marca'),
        checkPropostaFabricante: document.getElementById('proposta-fabricante'),
        inputPropostaValidade: document.getElementById('proposta-validade'),
        previewPropostaCriterio: document.getElementById('preview-proposta-criterio'),
        previewPropostaMarca: document.getElementById('preview-proposta-marca'),
        previewPropostaFabricante: document.getElementById('preview-proposta-fabricante'),
        previewPropostaTabela: document.getElementById('preview-proposta-tabela'),
        previewPropostaValidade: document.getElementById('preview-proposta-validade'),
        propostaScomContainer: document.getElementById('proposta-scom-container'),
        checkPropostaVedaSimples: document.getElementById('proposta-veda-simples'),
        propostaSrpContainer: document.getElementById('proposta-srp-container'),
        checkPropostaQtdeMinima: document.getElementById('proposta-qtde-minima'),
        previewPropostaScom: document.getElementById('preview-proposta-scom'),
        previewPropostaSimples: document.getElementById('preview-proposta-simples'),
        previewPropostaSrp: document.getElementById('preview-proposta-srp'),
        // Seção 7
        intervaloMinimoContainer: document.getElementById('intervalo-minimo-container'),
        notaBeneficioMpContainer: document.getElementById('nota-beneficio-mp-container'),
        inputAberturaPrazoAdequacao: document.getElementById('abertura-prazo-adequacao'),
        previewAberturaLanceTipo: document.getElementById('preview-abertura-lance-tipo'),
        previewAberturaLanceValor: document.getElementById('preview-abertura-lance-valor'),
        previewAberturaIntervaloTipo: document.getElementById('preview-abertura-intervalo-tipo'),
        previewAberturaIntervaloValor: document.getElementById('preview-abertura-intervalo-valor'),
        previewAberturaTabelaIntervalo: document.getElementById('preview-abertura-tabela-intervalo'),
        previewAberturaModoDisputa: document.getElementById('preview-abertura-modo-disputa'),
        previewAberturaModoDisputaRegras: document.getElementById('preview-abertura-modo-disputa-regras'),
        previewAberturaMargemPref: document.getElementById('preview-abertura-margem-pref'),
        previewAberturaEmpateMe: document.getElementById('preview-abertura-empate-me'),
        previewAberturaPrazoAdequacao: document.getElementById('preview-abertura-prazo-adequacao'),
        // Seção 8
        julgamentoScomContainer: document.getElementById('julgamento-scom-container'),
        julgamentoDissidioBuilder: document.getElementById('julgamento-dissidio-builder'),
        inputJulgamentoPrazoReadequacao: document.getElementById('julgamento-prazo-readequacao'),
        checkJulgamentoCustosRelevantes: document.getElementById('julgamento-custos-relevantes'),
        previewJulgamentoScomDissidios: document.getElementById('preview-julgamento-scom-dissidios'),
        previewJulgamentoCustos: document.getElementById('preview-julgamento-custos'),
        previewJulgamentoScomProdutividade: document.getElementById('preview-julgamento-scom-produtividade'),
        previewJulgamentoScomDeclaracoes: document.getElementById('preview-julgamento-scom-declaracoes'),
        // Seção 9
        habilitacaoMeEppContainer: document.getElementById('habilitacao-me-epp-container'),
        habilitacaoConsorcioContainer: document.getElementById('habilitacao-consorcio-container'),
        checkHabilitacaoOutrosMeios: document.getElementById('habilitacao-outros-meios'),
        habilitacaoOutrosMeiosInputContainer: document.getElementById('habilitacao-outros-meios-input-container'),
        inputHabilitacaoOutrosMeiosTexto: document.getElementById('habilitacao-outros-meios-texto'),
        checkHabilitacaoVistoria: document.getElementById('habilitacao-vistoria'),
        habilitacaoVistoriaInputContainer: document.getElementById('habilitacao-vistoria-input-container'),
        inputHabilitacaoVistoriaTexto: document.getElementById('habilitacao-vistoria-texto'),
        inputHabilitacaoPrazoSicaf: document.getElementById('habilitacao-prazo-sicaf'),
        inputHabilitacaoPrazoNovosDocs: document.getElementById('habilitacao-prazo-novos-docs'),
        previewHabilitacaoConsorcio: document.getElementById('preview-habilitacao-consorcio'),
        previewHabilitacaoMeEpp: document.getElementById('preview-habilitacao-me-epp'),
        previewHabilitacaoOutrosMeios: document.getElementById('preview-habilitacao-outros-meios'),
        previewHabilitacaoVistoria: document.getElementById('preview-habilitacao-vistoria'),
        previewHabilitacaoInversaoFasesSicaf: document.getElementById('preview-habilitacao-inversao-fases-sicaf'),
        previewHabilitacaoPrazoSicaf: document.getElementById('preview-habilitacao-prazo-sicaf'),
        previewHabilitacaoPrazoNovosDocs: document.getElementById('preview-habilitacao-prazo-novos-docs'),
        previewHabilitacaoInversaoFasesFinal: document.getElementById('preview-habilitacao-inversao-fases-final'),

        // --- NOVO: SEÇÃO 10 TERMO DE CONTRATO ---
        inputTermoPrazoAssinatura: document.getElementById('termo-prazo-assinatura'),
        inputTermoPrazoAR: document.getElementById('termo-prazo-ar'),
        inputTermoPrazoDigital: document.getElementById('termo-prazo-digital'),
        inputTermoPrazoOutro: document.getElementById('termo-prazo-outro'),
        checkTermoNotaEmpenho: document.getElementById('termo-nota-empenho'),
        termoScomContainer: document.getElementById('termo-scom-container'),
        checkTermoScomMais25: document.getElementById('termo-scom-mais-25'),
        termoScomInputContainer: document.getElementById('termo-scom-input-container'),
        inputTermoScomPercentual: document.getElementById('termo-scom-percentual'),
        // Preview
        previewTermoPrazoAssinatura: document.getElementById('preview-termo-prazo-assinatura'),
        previewTermoPrazoAR: document.getElementById('preview-termo-prazo-ar'),
        previewTermoPrazoDigital: document.getElementById('preview-termo-prazo-digital'),
        previewTermoPrazoOutro: document.getElementById('preview-termo-prazo-outro'),
        previewTermoNotaEmpenho: document.getElementById('preview-termo-nota-empenho'),
        previewTermoScom: document.getElementById('preview-termo-scom'),
    };

    // 3. INICIAR OS COMPONENTES DO DS GOV.BR
    iniciarComponentesDS();

    // 4. PREENCHER VALORES INICIAIS
    preencherValoresIniciais();

    // 5. REGISTRAR OS EVENTOS
    registrarEventos();
    
    // 6. NUMERAÇÃO INICIAL
    Numera();

    console.log('Editor de Edital carregado com sucesso.', Edital.dados);
});


/**
 * Inicia todos os componentes do GOV.BR que usamos na página.
 */
function iniciarComponentesDS() {
    document.querySelectorAll('.br-accordion').forEach(el => new core.BRAccordion('br-accordion', el));
    document.querySelectorAll('.br-datetimepicker').forEach(el => new core.BRDateTimePicker('br-datetimepicker', el));
    document.querySelectorAll('.br-select').forEach(el => new core.BRSelect('br-select', el));
}

/**
 * Preenche controles dinâmicos e valores iniciais.
 */
function preencherValoresIniciais() {
    // Padrão (Pregoeiro, Modalidade)
    if (Edital.elementos.previewPregoeiro.textContent.trim() === "") {
         Edital.elementos.previewPregoeiro.textContent = Edital.dados.pregoeiro;
    }
    Edital.elementos.previewSpansModalidade.forEach(span => {
        span.textContent = (span.getAttribute('aria-label') === 'M') 
            ? Edital.dados.modalidadeTexto.toUpperCase() 
            : Edital.dados.modalidadeTexto;
    });

    // Seção 3 (ME/EPP)
    if (Edital.dados.pref === '1') {
        buildControlsMeEpp(); 
        mapearEregistrarControlesMeEpp(); 
    }
    updateParticipacaoPreview(); 
    
    // Seção 6 (Proposta)
    buildPropostaControls();
    if (Edital.dados.srp === '1') {
        Edital.elementos.propostaSrpContainer.classList.remove('hidden');
    }
    updatePropostaPreview();

    // Seção 7 (Abertura)
    updateAberturaPreview(); 

    // Seção 8 (Julgamento)
    updateJulgamentoPreview();

    // Seção 9 (Habilitação)
    buildHabilitacaoControls();
    updateHabilitacaoPreview();

    // --- NOVO: Seção 10 (Termo de Contrato) ---
    updateTermoPreview();
}

/**
 * Constrói os controles de ME/EPP (Seção 3)
 */
function buildControlsMeEpp() {
    const container = Edital.elementos.participacaoMeEppContainer;
    if (container) {
        container.innerHTML = `
            <div class="row"><div class="col-10 d-inline-block "><div class="br-checkbox small"><input id="participa-exclusiva" name="participa-exclusiva" type="checkbox"/><label for="participa-exclusiva">Itens com participação <b>exclusiva</b> de ME/EPP</label></div></div><div class="col-2"><div class="ml-auto"><button class="br-button circle small" type="button" aria-label="Nota Explicativa" onclick="ExibirNota(1)"><i class="fas fa-chalkboard-teacher" aria-hidden="true"></i></button></div></div></div>
            <div id="divlistaparticipacaoexclusivo" class="hidden mt-2"><div class="br-input small input-highlight"><label for="listaparticipacaoexclusivo">Lista de itens</label><input id="listaparticipacaoexclusivo" name="listaparticipacaoexclusivo" type="text" placeholder="Lista de itens" /></div></div>
            <span class="br-divider sm p-2"></span>
            <div class="row"><div class="col-10 d-inline-block "><div class="br-checkbox small"><input id="participa-excluir-tratamento" name="participa-excluir-tratamento" type="checkbox"/><label for="participa-excluir-tratamento">Itens excluídos de <b>tratamento favorecido</b> de ME/EPP</label></div></div><div class="col-2"><div class="ml-auto"><button class="br-button circle small" type="button" aria-label="Nota Explicativa" onclick="ExibirNota(2)"><i class="fas fa-chalkboard-teacher" aria-hidden="true"></i></button></div></div></div>
            <div id="divlistaparticipacaopref" class="hidden mt-2"><div class="br-input small input-highlight"><label for="listaparticipacaopref">Lista de itens</label><input id="listaparticipacaopref" name="listaparticipacaopref" type="text" placeholder="Lista de itens" /></div></div>
        `;
    }
}

/**
 * Mapeia e registra eventos para os controles de ME/EPP criados dinamicamente.
 */
function mapearEregistrarControlesMeEpp() {
    Edital.elementos.checkParticipaExclusiva = document.getElementById('participa-exclusiva');
    Edital.elementos.divListaExclusiva = document.getElementById('divlistaparticipacaoexclusivo');
    Edital.elementos.inputListaExclusiva = document.getElementById('listaparticipacaoexclusivo');
    Edital.elementos.checkExcluirTratamento = document.getElementById('participa-excluir-tratamento');
    Edital.elementos.divListaPref = document.getElementById('divlistaparticipacaopref');
    Edital.elementos.inputListaPref = document.getElementById('listaparticipacaopref');
    // Registra Eventos
    Edital.elementos.checkParticipaExclusiva?.addEventListener('change', (e) => { Edital.estado.participacaoExclusiva = e.target.checked; handleParticipacaoChange(); });
    Edital.elementos.inputListaExclusiva?.addEventListener('input', (e) => { Edital.estado.participacaoExclusivaItens = e.target.value; handleParticipacaoChange(); });
    Edital.elementos.checkExcluirTratamento?.addEventListener('change', (e) => { Edital.estado.participacaoExcluirTratamento = e.target.checked; handleParticipacaoChange(); });
    Edital.elementos.inputListaPref?.addEventListener('input', (e) => { Edital.estado.participacaoExcluirTratamentoItens = e.target.value; handleParticipacaoChange(); });
}


/**
 * Adiciona os "ouvintes" de eventos (cliques, digitação) aos controles.
 */
function registrarEventos() {
    
    // --- Eventos Básicos (Data, Hora, Objeto) ---
    Edital.elementos.textareaObjeto?.addEventListener('keyup', (e) => { Edital.elementos.previewObjeto.innerHTML = e.target.value.replace(/\n/g, '<br />'); });
    Edital.elementos.inputDataSessao?.addEventListener('change', (e) => { Edital.elementos.previewDataSessao.textContent = e.target.value; });
    Edital.elementos.inputHoraInicial?.addEventListener('change', (e) => { Edital.elementos.previewHoraInicial.textContent = e.target.value; });

    // --- Eventos Objeto ---
    Edital.elementos.objetoTipoGroup?.addEventListener('change', (e) => {
        Edital.estado.objetoTipo = e.target.value; Edital.estado.objetoSubTipo = null; 
        document.querySelectorAll('input[name="objeto-item-sub"]').forEach(r => r.checked = false);
        document.querySelectorAll('input[name="objeto-grupo-sub"]').forEach(r => r.checked = false);
        handleObjetoChange();
    });
    Edital.elementos.objetoItemSubGroup?.addEventListener('change', (e) => { Edital.estado.objetoSubTipo = e.target.value; handleObjetoChange(); });
    Edital.elementos.objetoGrupoSubGroup?.addEventListener('change', (e) => { Edital.estado.objetoSubTipo = e.target.value; handleObjetoChange(); });
    Edital.elementos.inputNumItens?.addEventListener('input', (e) => { validateIntegerInput(e.target); Edital.estado.objetoNumItens = e.target.value; updateObjetoPreview(); });
    Edital.elementos.inputNumGrupos?.addEventListener('input', (e) => { validateIntegerInput(e.target); Edital.estado.objetoNumGrupos = e.target.value; updateObjetoPreview(); });

    // --- Eventos de Participação ---
    Edital.elementos.checkSCOM?.addEventListener('change', (e) => { Edital.estado.isSCOM = e.target.checked; handleParticipacaoChange(); });
    Edital.elementos.checkVedarCoop?.addEventListener('change', (e) => { Edital.estado.vedarCooperativas = e.target.checked; handleParticipacaoChange(); });
    Edital.elementos.checkVedarConsorcio?.addEventListener('change', (e) => { Edital.estado.vedarConsorcio = e.target.checked; handleParticipacaoChange(); });

    // --- Eventos de Apresentação ---
    Edital.elementos.checkInversaoFases?.addEventListener('change', (e) => {
        if (e.target.checked) {
            const certeza = confirm("ATENÇÃO: Você tem certeza?\nVerifique a Nota Explicativa antes!\n\nEsta ação afetará outras seções (Habilitação e Recursos).");
            if (certeza) Edital.estado.inversaoFases = true;
            else e.target.checked = false;
        } else { Edital.estado.inversaoFases = false; }
        handleApresentacaoChange();
    });

    // --- Eventos da Seção 6 Proposta ---
    Edital.elementos.checkPropostaMarca?.addEventListener('change', (e) => { Edital.estado.propostaMarca = e.target.checked; updatePropostaPreview(); });
    Edital.elementos.checkPropostaFabricante?.addEventListener('change', (e) => { Edital.estado.propostaFabricante = e.target.checked; updatePropostaPreview(); });
    Edital.elementos.inputPropostaValidade?.addEventListener('input', (e) => { validateIntegerInput(e.target, 3); Edital.estado.propostaValidade = e.target.value || 60; updatePropostaPreview(); });
    Edital.elementos.propostaCriterioContainer?.addEventListener('click', (e) => {
        if (e.target.id === 'btn-add-prop-item') TabelaPropostaAdicionar();
        if (e.target.id === 'btn-remove-prop-item') TabelaPropostaRemover();
    });
    Edital.elementos.checkPropostaVedaSimples?.addEventListener('change', (e) => { Edital.estado.propostaVedaSimples = e.target.checked; updatePropostaPreview(); });
    Edital.elementos.checkPropostaQtdeMinima?.addEventListener('change', (e) => { Edital.estado.propostaQtdeMinima = e.target.checked; updatePropostaPreview(); });

    // --- Eventos da Seção 7 Abertura ---
    Edital.elementos.inputAberturaPrazoAdequacao?.addEventListener('input', (e) => { validateIntegerInput(e.target, 2); Edital.estado.aberturaPrazoAdequacao = e.target.value || 2; updateAberturaPreview(); });
    Edital.elementos.intervaloMinimoContainer.addEventListener('change', (e) => {
        if (e.target.id === 'ckintervaloidentico') { Edital.estado.aberturaIntervaloUnico = e.target.checked; buildTabelaIntervalo(); }
        if (e.target.id === 'inputvalorintmin-unico') {
            Edital.estado.aberturaIntervaloValorUnico = e.target.value || 'R$ 0,01';
            Edital.estado.aberturaTabelaIntervalo.forEach(item => item.intervalo = Edital.estado.aberturaIntervaloValorUnico);
            updateAberturaPreview();
        }
        if (e.target.classList.contains('input-intervalo-item')) {
            const id = parseInt(e.target.dataset.id, 10);
            const item = Edital.estado.aberturaTabelaIntervalo.find(i => i.id === id);
            if (item) item.intervalo = e.target.value || 'R$ 0,01';
            updateAberturaPreview();
        }
    });

    // --- Eventos da Seção 8 Julgamento ---
    Edital.elementos.checkJulgamentoCustosRelevantes?.addEventListener('change', (e) => { Edital.estado.julgamentoCustosRelevantes = e.target.checked; handleJulgamentoChange(); });
    Edital.elementos.inputJulgamentoPrazoReadequacao?.addEventListener('input', (e) => { validateIntegerInput(e.target, 2); Edital.estado.julgamentoPrazoReadequacao = e.target.value || 2; handleJulgamentoChange(); });
    Edital.elementos.julgamentoScomContainer?.addEventListener('click', (e) => {
        if (e.target.id === 'btn-add-dissidio-item') TabelaDissidioAdicionar();
        if (e.target.id === 'btn-remove-dissidio-item') TabelaDissidioRemover();
    });

    // --- Eventos da Seção 9 Habilitação ---
    Edital.elementos.checkHabilitacaoOutrosMeios?.addEventListener('change', (e) => { Edital.estado.habilitacaoOutrosMeios = e.target.checked; handleHabilitacaoChange(); });
    Edital.elementos.inputHabilitacaoOutrosMeiosTexto?.addEventListener('input', (e) => { Edital.estado.habilitacaoOutrosMeiosTexto = e.target.value; handleHabilitacaoChange(); });
    Edital.elementos.checkHabilitacaoVistoria?.addEventListener('change', (e) => { Edital.estado.habilitacaoVistoria = e.target.checked; handleHabilitacaoChange(); });
    Edital.elementos.inputHabilitacaoVistoriaTexto?.addEventListener('input', (e) => { Edital.estado.habilitacaoVistoriaTexto = e.target.value; handleHabilitacaoChange(); });
    Edital.elementos.inputHabilitacaoPrazoSicaf?.addEventListener('input', (e) => { validateIntegerInput(e.target, 2); Edital.estado.habilitacaoPrazoSicaf = e.target.value || 2; handleHabilitacaoChange(); });
    Edital.elementos.inputHabilitacaoPrazoNovosDocs?.addEventListener('input', (e) => { validateIntegerInput(e.target, 2); Edital.estado.habilitacaoPrazoNovosDocs = e.target.value || 2; handleHabilitacaoChange(); });
    Edital.elementos.habilitacaoConsorcioContainer?.addEventListener('input', (e) => {
        if (e.target.id === 'habilitacao-consorcio-percentual') {
            validateIntegerInput(e.target, 2);
            Edital.estado.habilitacaoConsorcioPercentual = e.target.value;
            handleHabilitacaoChange();
        }
    });

    // --- NOVO: Eventos da Seção 10 Termo de Contrato ---
    Edital.elementos.inputTermoPrazoAssinatura?.addEventListener('input', (e) => { validateIntegerInput(e.target, 2); Edital.estado.termoPrazoAssinatura = e.target.value; handleTermoChange(); });
    Edital.elementos.inputTermoPrazoAR?.addEventListener('input', (e) => { validateIntegerInput(e.target, 2); Edital.estado.termoPrazoAR = e.target.value; handleTermoChange(); });
    Edital.elementos.inputTermoPrazoDigital?.addEventListener('input', (e) => { validateIntegerInput(e.target, 2); Edital.estado.termoPrazoDigital = e.target.value; handleTermoChange(); });
    Edital.elementos.inputTermoPrazoOutro?.addEventListener('input', (e) => { validateIntegerInput(e.target, 2); Edital.estado.termoPrazoOutro = e.target.value; handleTermoChange(); });
    Edital.elementos.checkTermoNotaEmpenho?.addEventListener('change', (e) => { Edital.estado.termoNotaEmpenho = e.target.checked; handleTermoChange(); });
    Edital.elementos.checkTermoScomMais25?.addEventListener('change', (e) => { Edital.estado.termoScomMais25 = e.target.checked; handleTermoChange(); });
    Edital.elementos.inputTermoScomPercentual?.addEventListener('input', (e) => { validateIntegerInput(e.target, 2); Edital.estado.termoScomMenos25Percentual = e.target.value; handleTermoChange(); });
}

/**
 * Placeholder para a função de Notas Explicativas
 */
function ExibirNota(id) {
    console.log(`Exibir nota explicativa ID: ${id}`);
    alert(`(Placeholder: Exibir Nota Explicativa ID: ${id})`);
}


// ===================================================================
// --- LÓGICA DO OBJETO (Seção 1) ---
// ===================================================================
function handleObjetoChange() {
    const { objetoTipo, objetoSubTipo } = Edital.estado;
    const { 
        objetoSubOptions, objetoItemOptions, objetoGrupoOptions, 
        objetoNumItensContainer, objetoNumGruposContainer
    } = Edital.elementos;
    objetoSubOptions.classList.add('hidden'); objetoItemOptions.classList.add('hidden'); objetoGrupoOptions.classList.add('hidden');
    objetoNumItensContainer.classList.add('hidden'); objetoNumGruposContainer.classList.add('hidden');
    if (objetoTipo) objetoSubOptions.classList.remove('hidden');
    switch (objetoTipo) {
        case 'item':
            objetoItemOptions.classList.remove('hidden');
            if (objetoSubTipo === 'varios-itens') objetoNumItensContainer.classList.remove('hidden');
            break;
        case 'grupo':
            objetoGrupoOptions.classList.remove('hidden');
            if (objetoSubTipo === 'grupo-unico') objetoNumItensContainer.classList.remove('hidden');
            break;
        case 'item-grupo':
            objetoNumItensContainer.classList.remove('hidden');
            objetoNumGruposContainer.classList.remove('hidden');
            break;
    }
    updateObjetoPreview();
}
function updateObjetoPreview() {
    const { objetoTipo, objetoSubTipo } = Edital.estado;
    const { previewObjetoDetalhe, inputNumItens, inputNumGrupos } = Edital.elementos;
    let html = ''; let numItens = removeLeadingZeros(inputNumItens.value); let numGrupos = removeLeadingZeros(inputNumGrupos.value);
    let numItensTexto = numItens ? ` ${numItens} (${numeroPorExtenso(numItens, 1)})` : '...';
    let numGruposTexto = numGrupos ? ` ${numGrupos} (${numeroPorExtenso(numGrupos, 1)})` : '...';
    let tipo = objetoTipo; if (objetoSubTipo) tipo = objetoSubTipo; 
    switch (tipo) {
        case 'varios-itens': html = `<p><mark>1.2. A licitação será dividida em <span id='objnumitem'>${numItensTexto}</span> itens...</mark></p>`; break;
        case 'item-unico': html = `<p><mark>1.2. A licitação será realizada em único item.</mark></p>`; break;
        case 'varios-grupos': html = `<p><mark>1.2. A licitação será dividida em grupos...</mark></p>`; break;
        case 'grupo-unico': html = `<p><mark>1.2. A licitação será realizada em grupo único...</mark></p>`; break;
        case 'item-grupo': html = `<p class='normal'><mark>1.2. A licitação será dividida em <span id='objnumitem'>${numItensTexto}</span> itens... e <span id='objnumgrupo'>${numGruposTexto}</span> grupos...</mark></p><p class='sub1'><mark>1.2.1 ...</mark></p><p class='sub1'><mark>1.2.2 ...</mark></p>`; break;
        default: html = ''; break;
    }
    previewObjetoDetalhe.innerHTML = html;
}


// ===================================================================
// --- LÓGICA DA PARTICIPAÇÃO (Seção 3) ---
// ===================================================================
function getItemPluralText() {
    const { objetoSubTipo } = Edital.estado;
    return (objetoSubTipo === 'item-unico') ? 'o item' : 'os itens';
}
function handleParticipacaoChange() {
    // --- Lógica de SCOM ---
    if (Edital.estado.isSCOM) {
        // Seção 3
        Edital.elementos.checkVedarCoop.checked = true;
        Edital.elementos.checkVedarCoop.disabled = true;
        Edital.elementos.participaCoopDiv.classList.add('hidden');
        Edital.elementos.participaCoopDivider.classList.add('hidden');
        Edital.estado.vedarCooperativas = true;
        // Seção 6
        Edital.elementos.propostaScomContainer.classList.remove('hidden');
        // Seção 8
        Edital.elementos.julgamentoScomContainer.classList.remove('hidden');
        buildJulgamentoScomControls(); 
        // --- NOVO: Seção 10 ---
        Edital.elementos.termoScomContainer.classList.remove('hidden');

    } else {
        // Seção 3
        Edital.elementos.checkVedarCoop.disabled = false;
        Edital.elementos.participaCoopDiv.classList.remove('hidden');
        Edital.elementos.participaCoopDivider.classList.remove('hidden');
        // Seção 6
        Edital.elementos.propostaScomContainer.classList.add('hidden');
        if (Edital.elementos.checkPropostaVedaSimples) Edital.elementos.checkPropostaVedaSimples.checked = false;
        Edital.estado.propostaVedaSimples = false;
        // Seção 8
        Edital.elementos.julgamentoScomContainer.classList.add('hidden');
        Edital.estado.julgamentoTabelaDissidios = [];
        Edital.estado.julgamentoPrazoReadequacao = 2;
        if (Edital.elementos.inputJulgamentoPrazoReadequacao) Edital.elementos.inputJulgamentoPrazoReadequacao.value = 2;
        // --- NOVO: Seção 10 ---
        Edital.elementos.termoScomContainer.classList.add('hidden');
        Edital.elementos.checkTermoScomMais25.checked = false;
        Edital.elementos.inputTermoScomPercentual.value = '';
        Edital.elementos.termoScomInputContainer.classList.add('hidden');
        Edital.estado.termoScomMais25 = false;
        Edital.estado.termoScomMenos25Percentual = '';
    }
    
    // --- Lógica ME/EPP ---
    if (Edital.elementos.divListaExclusiva) {
        if (Edital.estado.participacaoExclusiva) Edital.elementos.divListaExclusiva.classList.remove('hidden');
        else { Edital.elementos.divListaExclusiva.classList.add('hidden'); Edital.elementos.inputListaExclusiva.value = ''; Edital.estado.participacaoExclusivaItens = ''; }
    }
    if (Edital.elementos.divListaPref) {
        if (Edital.estado.participacaoExcluirTratamento) Edital.elementos.divListaPref.classList.remove('hidden');
        else { Edital.elementos.divListaPref.classList.add('hidden'); Edital.elementos.inputListaPref.value = ''; Edital.estado.participacaoExcluirTratamentoItens = ''; }
    }

    // --- NOVO: Lógica de Consórcio (impacta Seção 9) ---
    buildHabilitacaoControls(); // Reconstrói os controles da Seção 9

    // Atualiza todos os previews impactados
    updatePropostaPreview(); 
    updateParticipacaoPreview();
    updateJulgamentoPreview();
    updateHabilitacaoPreview();
    updateTermoPreview(); // --- NOVO
    Numera();
}
function updateParticipacaoPreview() {
    const { previewParticipacaoExclusiva, previewParticipacaoTratamento, previewParticipacaoCooperativa, previewParticipacaoConsorcio } = Edital.elementos;
    const { participacaoExclusiva, participacaoExclusivaItens, participacaoExcluirTratamento, participacaoExcluirTratamentoItens, vedarCooperativas, vedarConsorcio } = Edital.estado;
    const itemTexto = getItemPluralText();
    if (participacaoExclusiva) { const listaItens = participacaoExclusivaItens || '...'; previewParticipacaoExclusiva.innerHTML = `<p><mark><span class="numeraparticipacao"></span>.<span class="layer1numeraparticipacao"></span>. Para ${itemTexto} <span id="listaparticipacao35">${listaItens}</span>...</mark></p><p class=""><mark><span class="numeraparticipacao"></span>.<span class="layer1numeraparticipacao"></span>. A obtenção do benefício...</mark></p>`; }
    else previewParticipacaoExclusiva.innerHTML = '';
    if (participacaoExcluirTratamento) { const listaItens = participacaoExcluirTratamentoItens || '...'; previewParticipacaoTratamento.innerHTML = `<p class=""><mark><span class="numeraparticipacao"></span>.<span class="layer1numeraparticipacao"></span>. N${itemTexto} <span id="listaparticipacao38">${listaItens}</span> não será concedida...</mark></p>`; }
    else previewParticipacaoTratamento.innerHTML = `<p class=""><span class="numeraparticipacao"></span>.<span class="layer1numeraparticipacao"></span>. Será concedido tratamento favorecido...</p>`;
    if (vedarCooperativas) previewParticipacaoCooperativa.innerHTML = `<p class="sub1"><mark><span class="numeraparticipacao"></span>.<span class="layer1numeraparticipacao"></span>.<span class="layer2numeraparticipacao"></span>. sociedades cooperativas;</mark></p>`;
    else previewParticipacaoCooperativa.innerHTML = '';
    if (vedarConsorcio) previewParticipacaoConsorcio.innerHTML = `<p class="sub1"><mark><span class="numeraparticipacao"></span>.<span class="layer1numeraparticipacao"></span>.<span class="layer2numeraparticipacao"></span>. pessoas jurídicas reunidas em consórcio;</mark></p>`;
    else previewParticipacaoConsorcio.innerHTML = '';
}

// ===================================================================
// --- LÓGICA DA APRESENTAÇÃO (Seção 5) ---
// ===================================================================
function handleApresentacaoChange() {
    updateApresentacaoPreview();
    updateHabilitacaoPreview(); // Inversão de fases impacta Habilitação
    Numera();
}
function updateApresentacaoPreview() {
    const { previewInversaoFases, previewInversaoFasesDocs } = Edital.elementos;
    const { inversaoFases } = Edital.estado;
    if (inversaoFases) {
        previewInversaoFases.innerHTML = `<p class=""><mark><span class="numeraapresentacao"></span>.<span class="layer1numeraapresentacao"></span>. Na presente licitação, a fase de habilitação antecederá...</mark></p>`;
        previewInversaoFasesDocs.innerHTML = `<p class=""><mark><span class="numeraapresentacao"></span>.<span class="layer1numeraapresentacao"></span>. Caso a fase de habilitação anteceda...</mark></p>`;
    } else {
        previewInversaoFases.innerHTML = `<p class=""><span class="numeraapresentacao"></span>.<span class="layer1numeraapresentacao"></span>. Na presente licitação, a fase de habilitação sucederá...</p>`;
        previewInversaoFasesDocs.innerHTML = '';
    }
}


// ===================================================================
// --- LÓGICA DA PROPOSTA (Seção 6) ---
// ===================================================================
function buildPropostaControls() {
    const container = Edital.elementos.propostaCriterioContainer;
    if (!container) return;
    if (Edital.dados.cj === '1') { // 1 = Menor Preço
        container.innerHTML = `
            <div class="row"><div class="col-10 d-inline-block p-3"><span class="text-danger"><i class="fas fa-exclamation-triangle" aria-hidden="true"></i> Critério: Menor preço</span></div><div class="col-2"><div class="ml-auto"><button class="br-button circle small" type="button" onclick="ExibirNota(40)"><i class="fas fa-chalkboard-teacher" aria-hidden="true"></i></button></div></div></div>
            <div class="tabela-proposta-builder mb-2">
                <div class="row"><div class=" col d-flex justify-content-center text-weight-bold p-2">Tabela Preenchimento da Proposta</div></div>
                <div class="row mt-2"><div class="col m-2"><div class="br-input small input-highlight"><label for="tabpropitem"><small>Item/Grupo</small></label><input id="tabpropitem" name="tabpropitem" type="text" placeholder='Item/Grupo' /></div></div></div>
                <div class="row mt-2 d-flex justify-content-between">
                    <div class="col-5 m-2"><div class="br-select">
                        <div class="br-input small input-highlight"><label for="tabpreencheperiodicidade"><small>Periodicidade</small></label><input id="tabpreencheperiodicidade" type="text" placeholder="Selecione" /><button class="br-button" type="button" data-trigger="data-trigger"><i class="fas fa-angle-down" aria-hidden="true"></i></button></div>
                        <div class="br-list" tabindex="0">
                            <div class="br-item" tabindex="-1"><div class="br-radio"><input id="p-unitario" type="radio" name="tabperiodicidade" value="Unitário"/><label for="p-unitario">Unitário</label></div></div>
                            <div class="br-item" tabindex="-1"><div class="br-radio"><input id="p-mensal" type="radio" name="tabperiodicidade" value="Mensal"/><label for="p-mensal">Mensal</label></div></div>
                            <div class="br-item" tabindex="-1"><div class="br-radio"><input id="p-anual" type="radio" name="tabperiodicidade" value="Anual"/><label for="p-anual">Anual</label></div></div>
                            <div class="br-item" tabindex="-1"><div class="br-radio"><input id="p-total" type="radio" name="tabperiodicidade" value="Total"/><label for="p-total">Total</label></div></div>
                        </div>
                    </div></div>
                    <div class="col-5 m-2 d-flex justify-content-center align-items-end"><button id="btn-add-prop-item" class="br-button small primary" type="button"><i class="fas fa-plus" aria-hidden="true"></i> Adicionar Item</button></div>
                </div>
                <div class="tabela-proposta-builder-remover row">
                    <div class="col-8 d-inline-block mt-2"><label id="remover_item">Remover Item (Seq.).</label><div class="br-input small input-highlight mt-2"><label for="inputremoveitem"><small>Sequência</small></label><input id="inputremoveitem" name="inputremoveitem" type="number" /></div></div>
                    <div class="col-4 d-flex justify-content-center align-items-end"><button id="btn-remove-prop-item" class="br-button small danger" type="button"><i class="fas fa-trash" aria-hidden="true"></i> Remover</button></div>
                </div>
            </div>`;
        Edital.elementos.inputTabPropItem = document.getElementById('tabpropitem');
        Edital.elementos.inputTabPropPeriodicidade = document.getElementById('tabpreencheperiodicidade');
        Edital.elementos.inputTabPropRemover = document.getElementById('inputremoveitem');
        const newSelect = document.querySelector('#proposta-criterio-container .br-select');
        if (newSelect) new core.BRSelect('br-select', newSelect);
        buildTabelaIntervalo();
    } else { // 0 = Maior Desconto
        container.innerHTML = `<div class="row"><div class="col-10 d-inline-block p-3"><span class="text-danger"><i class="fas fa-exclamation-triangle" aria-hidden="true"></i> Critério: Maior Desconto</span></div></div>`;
        Edital.elementos.intervaloMinimoContainer.innerHTML = '';
    }
}
function TabelaPropostaAdicionar() {
    const itemEl = Edital.elementos.inputTabPropItem;
    const periodEl = Edital.elementos.inputTabPropPeriodicidade;
    if (!itemEl.value || !periodEl.value) { alert("Preencha o Item/Grupo e a Periodicidade."); return; }
    const novoItem = { id: Edital.estado.propostaTabelaItens.length + 1, itemGrupo: itemEl.value, periodicidade: periodEl.value };
    Edital.estado.propostaTabelaItens.push(novoItem);
    itemEl.value = ''; periodEl.value = '';
    sincronizarTabelaIntervalo();
    updatePropostaPreview();
    updateAberturaPreview();
    alert("Item adicionado!");
}
function TabelaPropostaRemover() {
    const idRemover = parseInt(Edital.elementos.inputTabPropRemover.value, 10);
    if (!idRemover || isNaN(idRemover)) { alert("Insira o número da Sequência."); return; }
    const tamanhoAntes = Edital.estado.propostaTabelaItens.length;
    Edital.estado.propostaTabelaItens = Edital.estado.propostaTabelaItens.filter(item => item.id !== idRemover);
    if (Edital.estado.propostaTabelaItens.length === tamanhoAntes) { alert(`Sequência ${idRemover} não encontrada.`); return; }
    Edital.estado.propostaTabelaItens.forEach((item, index) => { item.id = index + 1; });
    Edital.elementos.inputTabPropRemover.value = '';
    sincronizarTabelaIntervalo();
    updatePropostaPreview();
    updateAberturaPreview();
    alert(`Item ${idRemover} removido.`);
}
function updatePropostaPreview() {
    const { 
        previewPropostaCriterio, previewPropostaMarca, previewPropostaFabricante, 
        previewPropostaValidade, previewPropostaScom, previewPropostaSimples, 
        previewPropostaSrp
    } = Edital.elementos;
    const { 
        propostaMarca, propostaFabricante, propostaValidade, 
        isSCOM, propostaVedaSimples, propostaQtdeMinima 
    } = Edital.estado;
    const { srp } = Edital.dados;
    const subitem = `<p class='sub1'><mark><span class='numeraproposta'></span>.<span class='layer1numeraproposta'></span>.<span class='layer2numeraproposta '></span>.`;

    if (Edital.dados.cj === '1') previewPropostaCriterio.innerHTML = `${subitem} Valor expresso em Reais (R$).</mark></p>`;
    else previewPropostaCriterio.innerHTML = `${subitem} Desconto expresso em Percentuais (%).</mark></p>`;
    if (propostaMarca) previewPropostaMarca.innerHTML = `${subitem} Marca (opcional).</mark></p>`;
    else previewPropostaMarca.innerHTML = '';
    if (propostaFabricante) previewPropostaFabricante.innerHTML = `${subitem} Fabricante (opcional).</mark></p>`;
    else previewPropostaFabricante.innerHTML = '';
    renderPropostaTabelaPreview();
    const dias = propostaValidade || 60;
    previewPropostaValidade.innerHTML = `<mark>${dias} (${numeroPorExtenso(dias, 1)})</mark>`;
    if (isSCOM) {
        previewPropostaScom.innerHTML = `
            <p class=""><mark><span class="numeraproposta"></span>.<span class="layer1numeraproposta"></span>. Em se tratando de serviços com fornecimento de mão de obra...</mark></p>
            <p class=""><mark><span class="numeraproposta"></span>.<span class="layer1numeraproposta"></span>. Os custos mínimos relevantes...</mark></p>
        `;
        if (propostaVedaSimples) {
            previewPropostaSimples.innerHTML = `<p class=""><mark><span class="numeraproposta"></span>.<span class="layer1numeraproposta"></span>. Na presente licitação, a ME/EPP não poderão se beneficiar do Simples Nacional...</mark></p>`;
        } else {
            previewPropostaSimples.innerHTML = `<p class=""><span class="numeraproposta"></span>.<span class="layer1numeraproposta"></span>. Na presente licitação, a ME/EPP poderão se beneficiar do Simples Nacional.<mark>...</mark></p>`;
        }
    } else {
        previewPropostaScom.innerHTML = '';
        previewPropostaSimples.innerHTML = `<p class=""><span class="numeraproposta"></span>.<span class="layer1numeraproposta"></span>. Na presente licitação, a ME/EPP poderão se beneficiar do Simples Nacional.<mark>...</mark></p>`;
    }
    if (srp === '1' && propostaQtdeMinima) {
        previewPropostaSrp.innerHTML = `<p class="sub1 aftersub"><mark><span class="numeraproposta"></span>.<span class="layer1numeraproposta"></span>.<span class="layer2numeraproposta last"></span>. Quantidade cotada, devendo respeitar o mínimo...</mark></p>`;
    } else if (srp === '1') {
        previewPropostaSrp.innerHTML = `<p class="sub1 aftersub"><mark><span class="numeraproposta"></span>.<span class="layer1numeraproposta"></span>.<span class="layer2numeraproposta last"></span>. O licitante não poderá oferecer proposta em quantitativo inferior...</mark></p>`;
    } else {
        previewPropostaSrp.innerHTML = '';
    }
    Numera();
}
function renderPropostaTabelaPreview() {
    const { previewPropostaTabela } = Edital.elementos;
    const { propostaTabelaItens } = Edital.estado;
    if (propostaTabelaItens.length === 0) { previewPropostaTabela.innerHTML = ''; return; }
    let tableHtml = `<table class="tabela-preview"><thead><tr><th scope="col">Seq.</th><th scope="col">Item/Grupo</th><th scope="col">Periodicidade</th></tr></thead><tbody>`;
    propostaTabelaItens.forEach(item => {
        tableHtml += `<tr><td>${item.id}</td><td>${item.itemGrupo}</td><td>${item.periodicidade}</td></tr>`;
    });
    tableHtml += `</tbody></table>`;
    previewPropostaTabela.innerHTML = tableHtml;
}


// ===================================================================
// --- LÓGICA DA ABERTURA (Seção 7) ---
// ===================================================================
function buildTabelaIntervalo() {
    const container = Edital.elementos.intervaloMinimoContainer;
    const { propostaTabelaItens, aberturaIntervaloUnico, aberturaIntervaloValorUnico } = Edital.estado;
    if (Edital.dados.cj !== '1') { container.innerHTML = ''; return; }
    if (propostaTabelaItens.length === 0) {
        container.innerHTML = `<div class="row m-1"><div class="col "><span class="p-2 feedback warning text-down-01 text-weight-bold" role="alert"><i class="fas fa-exclamation-triangle " aria-hidden="true"></i> Cadastre itens na "Tabela... (Seção 6)"...</span></div></div>`;
        return;
    }
    let html = `
        <div class="tabela-intervalo-builder">
            <div class="row"><div class=" col d-flex justify-content-center text-weight-bold p-2">Tabela Intervalo Mínimo</div></div>
            <div class="row p-2"><div class="col"><div class="br-checkbox small"><input id="ckintervaloidentico" type="checkbox" ${aberturaIntervaloUnico ? 'checked' : ''} /><label for="ckintervaloidentico">Todos os Itens/Grupos terão o mesmo valor?</label></div></div></div>
            <span class="br-divider sm p-2"></span><div id="tabintervalomin" class="p-2">`;
    if (aberturaIntervaloUnico) {
        html += `<div class="row"><div class="col"><div class="br-input small input-highlight "><label for="inputvalorintmin-unico"><small>Informar valor para todos (R$)</small></label><input id="inputvalorintmin-unico" type="text" placeholder="R$ 0,01" value="${aberturaIntervaloValorUnico}" oninput="formatarMoeda(this)" /></div></div></div>`;
    } else {
        html += `<div class="row mb-2"><div class="col-7 text-weight-bold ">Item/Grupo</div><div class="col-4 text-weight-bold ">Valor (R$)</div></div>`;
        Edital.estado.aberturaTabelaIntervalo.forEach(item => {
            html += `<div class="row mb-2"><div class="col-7 ">${item.itemGrupo}</div><div class="col-4 "><div class="br-input small input-highlight"><input type="text" class="input-intervalo-item" data-id="${item.id}" value="${item.intervalo}" oninput="formatarMoeda(this)" /></div></div></div>`;
        });
    }
    html += `</div></div>`;
    container.innerHTML = html;
}
function sincronizarTabelaIntervalo() {
    const { propostaTabelaItens, aberturaTabelaIntervalo, aberturaIntervaloValorUnico } = Edital.estado;
    const mapaIntervalo = new Map(aberturaTabelaIntervalo.map(item => [item.id, item.intervalo]));
    Edital.estado.aberturaTabelaIntervalo = propostaTabelaItens.map(itemProposta => {
        return { id: itemProposta.id, itemGrupo: itemProposta.itemGrupo, intervalo: mapaIntervalo.get(itemProposta.id) || aberturaIntervaloValorUnico };
    });
    buildTabelaIntervalo();
}
function updateAberturaPreview() {
    const { 
        previewAberturaLanceTipo, previewAberturaLanceValor, previewAberturaIntervaloTipo,
        previewAberturaIntervaloValor, previewAberturaTabelaIntervalo, previewAberturaModoDisputa,
        previewAberturaModoDisputaRegras, previewAberturaMargemPref, previewAberturaEmpateMe,
        previewAberturaPrazoAdequacao
    } = Edital.elementos;
    const { cj, modoDisputa, impref, pref } = Edital.dados;
    const { aberturaPrazoAdequacao, propostaTabelaItens } = Edital.estado;

    if (cj === '1') { // Menor Preço
        previewAberturaLanceTipo.textContent = "valor unitário do item";
        previewAberturaLanceValor.textContent = "valor inferior";
        previewAberturaIntervaloTipo.textContent = "valor";
        if (propostaTabelaItens.length > 0) {
            previewAberturaIntervaloValor.textContent = "definido na tabela abaixo.";
            renderIntervaloTabelaPreview();
        } else {
            previewAberturaIntervaloValor.textContent = "R$ 0,01 (um centavo de real).";
            previewAberturaTabelaIntervalo.innerHTML = '';
        }
    } else { // Maior Desconto
        previewAberturaLanceTipo.textContent = "percentual de desconto do item";
        previewAberturaLanceValor.textContent = "percentual de desconto superior";
        previewAberturaIntervaloTipo.textContent = "percentual";
        previewAberturaIntervaloValor.textContent = "0,1% (um décimo por cento).";
        previewAberturaTabelaIntervalo.innerHTML = '';
    }
    
    // Modo de Disputa (Fixo "Aberto")
    previewAberturaModoDisputa.textContent = "aberto";
    const sub = `<p class='sub1'><mark><span class='numeraabertura'></span>.<span class='layer1numeraabertura'></span>.<span class='layer2numeraabertura'></span>.`;
    const subLast = `<p class='sub1 aftersub'><mark><span class='numeraabertura'></span>.<span class='layer1numeraabertura'></span>.<span class='layer2numeraabertura last'></span>.`;
    const margemPrefAtiva = impref === '1';
    previewAberturaModoDisputaRegras.innerHTML = `
        <p class="beforesub"><mark><span class="numeraabertura"></span>.<span class="layer1numeraabertura"></span>. No modo de disputa “aberto”, ...</mark></p>
        ${sub} A etapa de lances... dez minutos...</mark></p>
        ${sub} A prorrogação automática... dois minutos...</mark></p>
        ${sub} Não havendo novos lances...<mark style="background-color: ${margemPrefAtiva ? '#00FF00' : 'transparent'};">${margemPrefAtiva ? 'sem prejuízo da aplicação da margem de preferência...' : ''}</mark></mark></p>
        ${subLast} Definida a melhor proposta, se a diferença... for de pelo menos 5%...</mark></p>
    `;

    // Margem de Preferência e Empate ME
    if (impref === '1') {
        previewAberturaMargemPref.innerHTML = `<p title="Com Preferência" class="beforesub"><mark style="background-color: #00FF00;"><span class="numeraabertura"></span>.<span class="layer1numeraabertura"></span>. Ao final da fase de lances, será aplicado o benefício da margem de preferência...</mark></p>`;
    } else {
        previewAberturaMargemPref.innerHTML = '';
    }
    if (pref === '1') { // Tratamento ME/EPP
        const percentual = (Edital.dados.modalidade === '1') ? "5% (cinco por cento)" : "10% (dez por cento)"; // 1 = Pregão
        previewAberturaEmpateMe.innerHTML = `
            <p class="beforesub"><mark><span class="numeraabertura"></span>.<span class="layer1numeraabertura"></span>. Em relação a itens não exclusivos... será efetivada a verificação automática...</mark></p>
            ${sub} Nessas condições, as propostas de ME/EPP... na faixa de até ${percentual} serão consideradas empatadas...</mark></p>
            ${subLast} A licitante mais bem classificada... terá o direito de encaminhar uma última oferta...</mark></p>
        `;
    } else {
        previewAberturaEmpateMe.innerHTML = '';
    }

    // Prazo de Adequação
    const horas = aberturaPrazoAdequacao || 2;
    previewAberturaPrazoAdequacao.innerHTML = `<mark>${horas} (${numeroPorExtenso(horas, 0)})</mark>`;
    Numera();
}
function renderIntervaloTabelaPreview() {
    const { previewAberturaTabelaIntervalo } = Edital.elementos;
    const { aberturaTabelaIntervalo } = Edital.estado;
    if (aberturaTabelaIntervalo.length === 0) { previewAberturaTabelaIntervalo.innerHTML = ''; return; }
    let tableHtml = `<table class="tabela-preview"><thead><tr><th scope="col">Seq.</th><th scope="col">Item/Grupo</th><th scope="col">Intervalo Mínimo (R$)</th></tr></thead><tbody>`;
    aberturaTabelaIntervalo.forEach(item => {
        tableHtml += `<tr><td>${item.id}</td><td>${item.itemGrupo}</td><td>${item.intervalo}</td></tr>`;
    });
    tableHtml += `</tbody></table>`;
    previewAberturaTabelaIntervalo.innerHTML = tableHtml;
}
function formatarMoeda(input) {
    let valor = input.value;
    valor = valor.replace(/\D/g,"");
    valor = valor.replace(/(\d)(\d{2})$/,"$1,$2");
    valor = valor.replace(/(?=(\d{3})+(\D))\B/g,".");
    input.value = (valor.length > 0) ? 'R$ ' + valor : '';
}


// ===================================================================
// --- LÓGICA DO JULGAMENTO (Seção 8) ---
// ===================================================================
function buildJulgamentoScomControls() {
    const container = Edital.elementos.julgamentoDissidioBuilder;
    if (!container) return;
    container.innerHTML = `
        <div class="tabela-dissidio-builder">
            <div class="row"><div class=" col d-flex justify-content-center text-weight-bold p-2">Indicar os acordos, dissídios ou convenções</div></div>
            <div class="row mt-2"><div class="col m-2"><div class="br-input small input-highlight"><label for="inputSindicato"><small>Sindicato</small></label><input id="inputSindicato" name="inputSindicato" type="text" placeholder='Nome do Sindicato' /></div></div></div>
            <div class="row mt-2"><div class="col m-2"><div class="br-input small input-highlight"><label for="inputBase"><small>Base</small></label><input id="inputBase" name="inputBase" type="text" placeholder='Base Territorial' /></div></div></div>
            <div class="row mt-2 d-flex justify-content-center"><div class="col-5 m-2 d-flex justify-content-center align-items-end"><button id="btn-add-dissidio-item" class="br-button small primary" type="button"><i class="fas fa-plus" aria-hidden="true"></i> Adicionar Item</button></div></div>
            <div class="tabela-dissidio-builder-remover row">
                <div class="col-8 d-inline-block mt-2"><label>Remover Item (Seq.).</label><div class="br-input small input-highlight mt-2"><label for="inputremovedissidio"><small>Sequência</small></label><input id="inputremovedissidio" name="inputremovedissidio" type="number" /></div></div>
                <div class="col-4 d-flex justify-content-center align-items-end"><button id="btn-remove-dissidio-item" class="br-button small danger" type="button"><i class="fas fa-trash" aria-hidden="true"></i> Remover</button></div>
            </div>
        </div>`;
    Edital.elementos.inputDissidioSindicato = document.getElementById('inputSindicato');
    Edital.elementos.inputDissidioBase = document.getElementById('inputBase');
    Edital.elementos.inputDissidioRemover = document.getElementById('inputremovedissidio');
}
function TabelaDissidioAdicionar() {
    const sindicatoEl = Edital.elementos.inputDissidioSindicato;
    const baseEl = Edital.elementos.inputDissidioBase;
    if (!sindicatoEl.value || !baseEl.value) { alert("Preencha o Sindicato e a Base."); return; }
    const novoItem = { id: Edital.estado.julgamentoTabelaDissidios.length + 1, sindicato: sindicatoEl.value, base: baseEl.value };
    Edital.estado.julgamentoTabelaDissidios.push(novoItem);
    sindicatoEl.value = ''; baseEl.value = '';
    updateJulgamentoPreview();
    alert("Dissídio adicionado!");
}
function TabelaDissidioRemover() {
    const idRemover = parseInt(Edital.elementos.inputDissidioRemover.value, 10);
    if (!idRemover || isNaN(idRemover)) { alert("Insira o número da Sequência."); return; }
    const tamanhoAntes = Edital.estado.julgamentoTabelaDissidios.length;
    Edital.estado.julgamentoTabelaDissidios = Edital.estado.julgamentoTabelaDissidios.filter(item => item.id !== idRemover);
    if (Edital.estado.julgamentoTabelaDissidios.length === tamanhoAntes) { alert(`Sequência ${idRemover} não encontrada.`); return; }
    Edital.estado.julgamentoTabelaDissidios.forEach((item, index) => { item.id = index + 1; });
    Edital.elementos.inputDissidioRemover.value = '';
    updateJulgamentoPreview();
    alert(`Item ${idRemover} removido.`);
}
function handleJulgamentoChange() {
    updateJulgamentoPreview();
    Numera();
}
function updateJulgamentoPreview() {
    const { 
        previewJulgamentoScomDissidios, previewJulgamentoCustos, 
        previewJulgamentoScomProdutividade, previewJulgamentoScomDeclaracoes
    } = Edital.elementos;
    const { isSCOM, julgamentoCustosRelevantes, julgamentoPrazoReadequacao } = Edital.estado;

    if (julgamentoCustosRelevantes) {
        previewJulgamentoCustos.innerHTML = `<mark> e pela superação de custo unitário tido como relevante...</mark>`;
    } else {
        previewJulgamentoCustos.innerHTML = `<mark>.</mark>`;
    }

    if (isSCOM) {
        const prazo = julgamentoPrazoReadequacao || 2;
        const prazoTexto = `${prazo} (${numeroPorExtenso(prazo, 0)})`;
        previewJulgamentoScomDissidios.innerHTML = `
            <p class="beforesub"><mark><span class="numerajulgamento"></span>.<span class="layer1numerajulgamento"></span>. Em se tratando de serviços com fornecimento de mão de obra...</mark></p>
            <div id="preview-dissidio-tabela-container"></div>
            <p class="sub1 aftersub"><mark><span class="numerajulgamento"></span>.<span class="layer1numerajulgamento"></span>.<span class="layer2numerajulgamento last"></span>. O(s) sindicato(s) indicado(s)... não é (são) de utilização obrigatória...</mark></p>`;
        renderDissidioTabelaPreview(); 
        previewJulgamentoScomProdutividade.innerHTML = `<p class="sub1"><mark><mark><span class="numerajulgamento"></span>.<span class="layer1numerajulgamento"></span>.<span class="layer2numerajulgamento"></span>. Em se tratando de serviços com fornecimento de mão de obra...</mark></p>`;
        previewJulgamentoScomDeclaracoes.innerHTML = `
            <p class="beforesub"><mark><span class="numerajulgamento"></span>.<span class="layer1numerajulgamento"></span>. No caso de serviços com dedicação exclusiva de mão-de-obra...</mark></p>
            <p class="sub1"><mark><span class="numerajulgamento"></span>.<span class="layer1numerajulgamento"></span>.<span class="layer2numerajulgamento"></span>. declaração informando o enquadramento sindical...</p></mark>
            <p class="sub1"><mark><span class="numerajulgamento"></span>.<span class="layer1numerajulgamento"></span>.<span class="layer2numerajulgamento"></span>. cópia da carta ou registro sindical...</p></mark>
            <p class="sub1"><mark><span class="numerajulgamento"></span>.<span class="layer1numerajulgamento"></span>.<span class="layer2numerajulgamento"></span>. cópia do Acordo, Convenção Coletiva...</p></mark>
            <p class="sub1 aftersub"><mark><span class="numerajulgamento"></span>.<span class="layer1numerajulgamento"></span>.<span class="layer2numerajulgamento last"></span>. declaração de que se responsabiliza...</p></mark>
            <p><mark><span class="numerajulgamento"></span>.<span class="layer1numerajulgamento"></span>. O <span class="agente" aria-label="M"></span> concederá o prazo de no mínimo <mark><span id="julgamento7-2">${prazoTexto}</span></mark> horas...</p></mark>`;
    } else {
        previewJulgamentoScomDissidios.innerHTML = '';
        previewJulgamentoScomProdutividade.innerHTML = '';
        previewJulgamentoScomDeclaracoes.innerHTML = '';
    }
    Numera();
}
function renderDissidioTabelaPreview() {
    const container = document.getElementById('preview-dissidio-tabela-container');
    if (!container) return;
    const { julgamentoTabelaDissidios } = Edital.estado;
    if (julgamentoTabelaDissidios.length === 0) {
        container.innerHTML = '<p class="sub1"><mark><span class="numerajulgamento"></span>.<span class="layer1numerajulgamento"></span>.<span class="layer2numerajulgamento"></span>. [Nenhum dissídio informado]</mark></p>';
        return;
    }
    let tableHtml = `<table class="tabela-preview" style="margin-left: 1cm; width: 95%;"><thead><tr><th scope="col">Seq.</th><th scope="col">Sindicato</th><th scope="col">Base</th></tr></thead><tbody>`;
    julgamentoTabelaDissidios.forEach(item => {
        tableHtml += `<tr><td>${item.id}</td><td>${item.sindicato}</td><td>${item.base}</td></tr>`;
    });
    tableHtml += `</tbody></table>`;
    container.innerHTML = `<p class="sub1"><span class="numerajulgamento"></span>.<span class="layer1numerajulgamento"></span>.<span class="layer2numerajulgamento"></span>.</p>${tableHtml}`;
}


// ===================================================================
// --- LÓGICA DA HABILITAÇÃO (Seção 9) ---
// ===================================================================
function buildHabilitacaoControls() {
    const { habilitacaoMeEppContainer, habilitacaoConsorcioContainer } = Edital.elementos;
    const { pref } = Edital.dados;
    const { vedarConsorcio } = Edital.estado;

    if (pref === '1') {
        habilitacaoMeEppContainer.innerHTML = `<p class=""><mark><span class="numerahabilitacao"></span>.<span class="layer1numerahabilitacao"></span>. A comprovação de regularidade fiscal e trabalhista das microempresas...</mark></p>`;
    } else {
        habilitacaoMeEppContainer.innerHTML = '';
    }

    if (!vedarConsorcio) {
        habilitacaoConsorcioContainer.classList.remove('hidden');
        habilitacaoConsorcioContainer.innerHTML = `
            <div class='row'>
                <div class="col-10 d-inline-block " >
                    <div class="br-checkbox small">
                        <input id="habilitacao-consorcio-percentual-check" name="habilitacao-consorcio-percentual-check" type="checkbox" onchange="document.getElementById('habilitacao-consorcio-percentual-container').classList.toggle('hidden')"/>
                        <label for="habilitacao-consorcio-percentual-check">Consórcio não formado integralmente por ME/EPP?</label>
                    </div>
                </div>
                <div class="col-2"><div class="ml-auto"><button class="br-button circle small" type="button" onclick="ExibirNota(56)"><i class="fas fa-chalkboard-teacher" aria-hidden="true"></i></button></div></div>
            </div>
            <div id="habilitacao-consorcio-percentual-container" class="row hidden mt-2">  
                <div class="col mb-3">
                    <div class="br-input small input-highlight">
                        <label for="habilitacao-consorcio-percentual">Acréscimo (10% a 30%)</label>
                        <input id="habilitacao-consorcio-percentual" name="habilitacao-consorcio-percentual" type="number" min="10" max="30" placeholder="%" />
                    </div>
                </div>
            </div>
            <span class="br-divider sm p-2 mt-2"></span>`;
    } else {
        habilitacaoConsorcioContainer.innerHTML = '';
        habilitacaoConsorcioContainer.classList.add('hidden');
        Edital.estado.habilitacaoConsorcioPercentual = '';
    }
}
function handleHabilitacaoChange() {
    const { habilitacaoOutrosMeios, habilitacaoVistoria } = Edital.estado;

    if (habilitacaoOutrosMeios) {
        Edital.elementos.habilitacaoOutrosMeiosInputContainer.classList.remove('hidden');
    } else {
        Edital.elementos.habilitacaoOutrosMeiosInputContainer.classList.add('hidden');
        Edital.elementos.inputHabilitacaoOutrosMeiosTexto.value = '';
        Edital.estado.habilitacaoOutrosMeiosTexto = '';
    }
    if (habilitacaoVistoria) {
        Edital.elementos.habilitacaoVistoriaInputContainer.classList.remove('hidden');
    } else {
        Edital.elementos.habilitacaoVistoriaInputContainer.classList.add('hidden');
        Edital.elementos.inputHabilitacaoVistoriaTexto.value = '';
        Edital.estado.habilitacaoVistoriaTexto = '';
    }
    updateHabilitacaoPreview();
    Numera();
}
function updateHabilitacaoPreview() {
    const {
        previewHabilitacaoConsorcio, previewHabilitacaoMeEpp, previewHabilitacaoOutrosMeios,
        previewHabilitacaoVistoria, previewHabilitacaoInversaoFasesSicaf,
        previewHabilitacaoPrazoSicaf, previewHabilitacaoPrazoNovosDocs,
        previewHabilitacaoInversaoFasesFinal
    } = Edital.elementos;
    const {
        vedarConsorcio, habilitacaoConsorcioPercentual, habilitacaoOutrosMeios, 
        habilitacaoOutrosMeiosTexto, habilitacaoVistoria, habilitacaoVistoriaTexto, 
        habilitacaoPrazoSicaf, habilitacaoPrazoNovosDocs, inversaoFases
    } = Edital.estado;
    const { pref } = Edital.dados;

    if (!vedarConsorcio) {
        let textoPercentual = '';
        if (habilitacaoConsorcioPercentual && habilitacaoConsorcioPercentual >= 10 && habilitacaoConsorcioPercentual <= 30) {
            textoPercentual = `<p class="sub1"><mark><span class="numerahabilitacao"></span>.<span class="layer1numerahabilitacao"></span>.<span class="layer2numerahabilitacao last"></span>. Se o consórcio não for formado integralmente por ME/EPP... ${habilitacaoConsorcioPercentual}%...</mark></p>`;
        }
        previewHabilitacaoConsorcio.innerHTML = `<p class=""><mark><span class="numerahabilitacao"></span>.<span class="layer1numerahabilitacao"></span>. Quando permitida a participação de consórcio...</mark></p>${textoPercentual}`;
    } else {
        previewHabilitacaoConsorcio.innerHTML = '';
    }
    if (pref === '1') {
        previewHabilitacaoMeEpp.innerHTML = `<p class=""><mark><span class="numerahabilitacao"></span>.<span class="layer1numerahabilitacao"></span>. A comprovação de regularidade fiscal e trabalhista das ME/EPP...</mark></p>`;
    } else {
        previewHabilitacaoMeEpp.innerHTML = '';
    }
    if (habilitacaoOutrosMeios) {
        const texto = habilitacaoOutrosMeiosTexto || '...';
        previewHabilitacaoOutrosMeios.innerHTML = `, por cópia ou <mark>${texto}</mark>`;
    } else {
        previewHabilitacaoOutrosMeios.innerHTML = ` ou por cópia`;
    }
    if (habilitacaoVistoria) {
        const texto = habilitacaoVistoriaTexto || '...';
        previewHabilitacaoVistoria.innerHTML = `
            <p class=""><mark><span class="numerahabilitacao"></span>.<span class="layer1numerahabilitacao"></span>. Considerando que... a avaliação prévia do local é imprescindível...</mark></p>
            <p class="sub1"><mark><span class="numerahabilitacao"></span>.<span class="layer1numerahabilitacao"></span>.<span class="layer2numerahabilitacao"></span>. O licitante que optar por realizar vistoria prévia... a ser agendado <mark>${texto}</mark>...</mark></p>
            <p class="sub1"><mark><span class="numerahabilitacao"></span>.<span class="layer1numerahabilitacao"></span>.<span class="layer2numerahabilitacao last"></span>. Caso o licitante opte por não realizar vistoria...</mark></p>`;
    } else {
        previewHabilitacaoVistoria.innerHTML = '';
    }
    const prazoSicaf = habilitacaoPrazoSicaf || 2;
    previewHabilitacaoPrazoSicaf.innerHTML = `${prazoSicaf} (${numeroPorExtenso(prazoSicaf, 0)})`;
    const prazoNovosDocs = habilitacaoPrazoNovosDocs || 2;
    previewHabilitacaoPrazoNovosDocs.innerHTML = `${prazoNovosDocs} (${numeroPorExtenso(prazoNovosDocs, 0)})`;
    if (inversaoFases) {
        previewHabilitacaoInversaoFasesSicaf.innerHTML = `<p class="sub1"><span class="numerahabilitacao"></span>.<span class="layer1numerahabilitacao"></span>.<span class="layer2numerahabilitacao"></span>. Na hipótese de a fase de habilitação anteceder...</p>`;
        previewHabilitacaoInversaoFasesFinal.innerHTML = `<p class=""><span class="numerahabilitacao"></span>.<span class="layer1numerahabilitacao"></span>. Quando a fase de habilitação anteceder a de julgamento...</p>`;
    } else {
        previewHabilitacaoInversaoFasesSicaf.innerHTML = '';
        previewHabilitacaoInversaoFasesFinal.innerHTML = '';
    }
    Numera();
}


// ===================================================================
// --- NOVO: LÓGICA DO TERMO DE CONTRATO (Seção 10) ---
// ===================================================================

/**
 * Função central que reage a mudanças nos checkboxes do Termo de Contrato.
 */
function handleTermoChange() {
    const { termoScomMais25 } = Edital.estado;
    const { termoScomInputContainer, inputTermoScomPercentual } = Edital.elementos;

    // Controla a visibilidade do input de percentual SCOM
    if (termoScomMais25) {
        termoScomInputContainer.classList.add('hidden');
        inputTermoScomPercentual.value = '';
        Edital.estado.termoScomMenos25Percentual = '';
    } else {
        termoScomInputContainer.classList.remove('hidden');
    }

    updateTermoPreview();
    Numera();
}

/**
 * Atualiza o texto da Seção 10 "Termo de Contrato" no preview.
 */
function updateTermoPreview() {
    const {
        previewTermoPrazoAssinatura, previewTermoPrazoAR, previewTermoPrazoDigital,
        previewTermoPrazoOutro, previewTermoNotaEmpenho, previewTermoScom
    } = Edital.elementos;
    const {
        termoPrazoAssinatura, termoPrazoAR, termoPrazoDigital, termoPrazoOutro,
        termoNotaEmpenho, isSCOM, termoScomMais25, termoScomMenos25Percentual
    } = Edital.estado;

    // 1. Prazos
    const prazoAss = termoPrazoAssinatura || '[PRAZO]';
    previewTermoPrazoAssinatura.innerHTML = `<mark>${prazoAss} (${numeroPorExtenso(prazoAss, 1)})</mark>`;
    const prazoAR = termoPrazoAR || '[PRAZO]';
    previewTermoPrazoAR.innerHTML = `<mark>${prazoAR} (${numeroPorExtenso(prazoAR, 1)})</mark>`;
    const prazoDig = termoPrazoDigital || '[PRAZO]';
    previewTermoPrazoDigital.innerHTML = `<mark>${prazoDig} (${numeroPorExtenso(prazoDig, 1)})</mark>`;
    const prazoOutro = termoPrazoOutro || '[PRAZO]';
    previewTermoPrazoOutro.innerHTML = `<mark>${prazoOutro} (${numeroPorExtenso(prazoOutro, 1)})</mark>`;

    // 2. Nota de Empenho
    if (termoNotaEmpenho) {
        previewTermoNotaEmpenho.innerHTML = `
            <p class="beforesub"><mark><span class="numeratermo"></span>.<span class="layer1numeratermo"></span>. O Aceite da Nota de Empenho... implica o reconhecimento de que:</mark></p>
            <p class="sub1"><mark><span class="numeratermo"></span>.<span class="layer1numeratermo"></span>.<span class="layer2numeratermo"></span>. ...substituindo o contrato...</mark></p>
            <p class="sub1"><mark><span class="numeratermo"></span>.<span class="layer1numeratermo"></span>.<span class="layer2numeratermo "></span>. ...se vincula à sua proposta...</mark></p>
            <p class="sub1 aftersub"><mark><span class="numeratermo"></span>.<span class="layer1numeratermo"></span>.<span class="layer2numeratermo last"></span>. ...reconhece que as hipóteses de rescisão...</mark></p>
        `;
    } else {
        previewTermoNotaEmpenho.innerHTML = '';
    }

    // 3. SCOM (Mão de Obra)
    if (isSCOM) {
        let textoVagas = '';
        if (termoScomMais25) {
            textoVagas = `...em percentual igual ou superior a 8% (oito por cento) das vagas.`;
        } else {
            const percent = termoScomMenos25Percentual || '[PERCENTUAL]';
            textoVagas = `...no percentual de <mark>${percent} (${numeroPorExtenso(percent, 1)})</mark> %.`;
        }
        
        previewTermoScom.innerHTML = `
            <p class=""><mark><span class="numeratermo"></span>.<span class="layer1numeratermo"></span>. Na contratação de serviços com dedicação exclusiva de mão-de-obra... será exigida da empresa... do emprego de mão de obra constituída por mulheres vítimas de violência doméstica... ${textoVagas}</p></mark>
        `;
    } else {
        previewTermoScom.innerHTML = '';
    }

    Numera();
}


// ===================================================================
// --- SEÇÃO DE NUMERAÇÃO (Existente) ---
// ===================================================================
function iteraArrayNumeros(labels, num, conta = false) {
    let flagsub1 = false, flagsub2 = false;
    labels.forEach(function(label) {
        if (conta) {
            if (!label.closest('.sub1') && !label.closest('.sub2')) num++;
            flagsub1 = (label.closest('.sub1')) ? true : false;
            if (flagsub1 && label.className.includes('layer2')) num++;
        }
        let negrito = (label.classList.contains('negrito')) ? "bold" : "normal";
        label.textContent = num;
        label.style.fontFamily = 'Times New Roman, Times, serif';
        label.style.fontSize = '12pt';
        label.style.fontWeight = negrito;
        if (label.classList.contains('last')) num = 0;
    });
}
function Numera() {
    const arrayTitulos = [
        "numerasrp", "numeraparticipacao", "numerasigiloso", "numeraapresentacao", 
        "numeraproposta", "numeraabertura", "numerajulgamento", "numerahabilitacao", 
        "numeratermo", "numeraataregpreco", "numeracadres", "numerarecursos", 
        "numerainfracoes", "numeraimpugnacao", "numeragerais"
    ];
    let numTitulo = 2; // Começa em 2
    arrayTitulos.forEach((titulo) => {
        const labels = document.querySelectorAll(`.${titulo}`);
        if (labels.length > 0) { iteraArrayNumeros(labels, numTitulo, false); numTitulo++; }
    });
    arrayTitulos.forEach((titulo) => {
        const labels1 = document.querySelectorAll(`.layer1${titulo}`);
        if (labels1.length > 0) iteraArrayNumeros(labels1, 0, true);
    });
    arrayTitulos.forEach((titulo) => {
        const labels2 = document.querySelectorAll(`.layer2${titulo}`);
        if (labels2.length > 0) iteraArrayNumeros(labels2, 0, true);
    });
}

// ===================================================================
// --- SEÇÃO DE UTILITÁRIOS (Existentes) ---
// ===================================================================
function validateIntegerInput(input, max = 5) {
    if (input.value != "") {
        if (input.value.length > max) { input.value = input.value.slice(0, max); alert(`Tamanho máximo: ${max} caracteres`); return; }
        const intValue = parseInt(input.value, 10);
        if (isNaN(intValue) || intValue.toString() !== input.value || intValue < 0) { input.value = ''; alert("Apenas inteiros positivos."); }
    }
}
function removeLeadingZeros(numero) {
    if (numero === "" || numero === null || isNaN(parseInt(numero))) return "";
    return String(parseInt(numero, 10));
}
function numeroPorExtenso(n, tipo = 1) {
    if (n === null || n === "" || isNaN(parseInt(n))) return "";
    n = parseInt(n, 10);
    const unidades = ["zero", "um", "dois", "três", "quatro", "cinco", "seis", "sete", "oito", "nove"];
    const dezenas = ["", "dez", "vinte", "trinta", "quarenta", "cinquenta", "sessenta", "setenta", "oitenta", "noventa"];
    const especiais = ["dez", "onze", "doze", "treze", "catorze", "quinze", "dezesseis", "dezessete", "dezoito", "dezenove"];
    const centenas = ["", "cem", "duzentos", "trezentos", "quatrocentos", "quinhentos", "seiscentos", "setecentos", "oitocentos", "novecentos"];
    if (tipo === 0) { unidades[1] = "uma"; unidades[2] = "duas"; centenas[2] = "duzentas"; }
    let str = ""; if (n === 0) return unidades[0];
    if (n >= 1000000000) { str += numeroPorExtenso(Math.floor(n / 1000000000), 1) + " bilh" + (n >= 2000000000 ? "ões" : "ão"); n %= 1000000000; if (n > 0) str += " e "; }
    if (n >= 1000000) { str += numeroPorExtenso(Math.floor(n / 1000000), 1) + " milh" + (n >= 2000000 ? "ões" : "ão"); n %= 1000000; if (n > 0) str += " e "; }
    if (n >= 1000) { if (n >= 2000) { str += numeroPorExtenso(Math.floor(n / 1000), 1) + " mil"; } else { str += "mil"; } n %= 1000; if (n > 0 && n < 100) str += " e "; else if (n > 0) str += " "; }
    if (n >= 100) { str += centenas[Math.floor(n / 100)]; n %= 100; if (n > 0) str += " e "; }
    if (n >= 20) { str += dezenas[Math.floor(n / 10)]; n %= 10; if (n > 0) str += " e "; }
    if (n >= 10) { str += especiais[n - 10]; n = 0; }
    if (n > 0) { str += unidades[n]; }
    return str.trim();
}