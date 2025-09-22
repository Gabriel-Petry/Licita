document.addEventListener('DOMContentLoaded', () => {

    /**
     * ====== LÓGICA DE LEITURA DO TOKEN DE SEGURANÇA ======
     * Esta é a forma correta e robusta de obter o token.
     */
    const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    /**
     * ====== MENU HAMBÚRGUER ======
     */
    const navToggle = document.getElementById('nav-toggle');
    const navMenu = document.getElementById('nav-menu');

    if (navToggle && navMenu) {
        navToggle.addEventListener('click', () => {
            navToggle.classList.toggle('is-active');
            navMenu.classList.toggle('is-active');
        });
    }

    const confirmDeleteButtons = document.querySelectorAll('.btn-confirm-delete');
    confirmDeleteButtons.forEach(button => {
        button.addEventListener('click', (event) => {
            const message = event.currentTarget.dataset.confirmMessage || 'Você tem certeza?';
            if (!confirm(message)) {
                event.preventDefault();
            }
        });
    });

    const confirmSubmitForms = document.querySelectorAll('.form-confirm-submit');
    confirmSubmitForms.forEach(form => {
        form.addEventListener('submit', (event) => {
            const message = event.currentTarget.dataset.confirmMessage || 'Você tem certeza que deseja enviar este formulário?';
            if (!confirm(message)) {
                event.preventDefault();
            }
        });
    });

    // Lógica para o popup de homologação em licitacoes.php
    const urlParams = new URLSearchParams(window.location.search);
    const homologarId = urlParams.get('homologar');

    if (homologarId) {
        const homologarPopup = document.getElementById('homologar-popup');
        const homologarIdField = document.getElementById('homologar-licitacao-id');
        
        if (homologarPopup && homologarIdField) {
            homologarIdField.value = homologarId;
            window.location.hash = 'homologar-popup';
        }
    }

    // Lógica para o pop-up de desomologação em homologadas.php
    document.querySelectorAll('.btn-desomologar').forEach(button => {
        button.addEventListener('click', (e) => {
            const licitacaoId = e.target.dataset.id;
            const licitacaoProcesso = e.target.dataset.processo;
            const popupTitle = document.getElementById('desomologar-title');
            const popupIdField = document.getElementById('desomologar-licitacao-id');
            
            if (popupTitle) popupTitle.textContent = `Desomologar Proc. ${licitacaoProcesso}`;
            if (popupIdField) popupIdField.value = licitacaoId;
        });
    });

    // Lógica para o pop-up de consulta de homologadas.php
    document.querySelectorAll('.btn-consultar').forEach(button => {
        button.addEventListener('click', (e) => {
            const targetPopupId = e.currentTarget.hash.substring(1);
            const popup = document.getElementById(targetPopupId);
            if (!popup) return;

            const consultarContent = popup.querySelector('.consultar-content');
            const consultarTitle = popup.querySelector('.consultar-title');
            const data = JSON.parse(e.currentTarget.dataset.licitacao);
            
            consultarTitle.textContent = `Detalhes do Processo ${data.processo || ''}`;
            
            const formatDate = (dateString) => dateString ? new Intl.DateTimeFormat('pt-BR', { timeZone: 'UTC' }).format(new Date(dateString)) : '--';
            const formatCurrency = (value) => (value !== null && value !== undefined) ? parseFloat(value).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' }) : 'R$ --';
            
            consultarContent.innerHTML = `
                <div class="grid grid-3" style="gap: 1.5rem;">
                    <div><strong>Órgão:</strong><br>${data.orgao || '--'}</div>
                    <div><strong>Modalidade:</strong><br>${data.modalidade || '--'}</div>
                    <div><strong>Status:</strong><br><span class="chip">${data.status || '--'}</span></div>
                    <div><strong>Valor Estimado:</strong><br>${formatCurrency(data.valor_estimado)}</div>
                    <div><strong>Valor Adjudicado:</strong><br>${formatCurrency(data.valor_adjudicado)}</div>
                    <div><strong>Economia:</strong><br>${(data.valor_adjudicado) ? formatCurrency(data.valor_estimado - data.valor_adjudicado) : '--'}</div>
                    <div><strong>Data Licitação:</strong><br>${formatDate(data.data_licitacao)}</div>
                    <div><strong>Data Conclusão:</strong><br>${formatDate(data.data_homologacao)}</div>
                    <div><strong>Nº Edital:</strong><br>${data.n_edital || '--'}</div>
                    <div><strong>Responsável p/ Edital:</strong><br>${data.responsavel_elaboracao || '--'}</div>
                    <div><strong>Agente de Contratação:</strong><br>${data.agente_contratacao || '--'}</div>
                </div>
                <hr style="margin: 1.5rem 0; border-color: var(--cor-borda);">
                <div><strong>Objeto:</strong><p style="margin-top: 0.5rem;">${data.objeto || '--'}</p></div>
                <div><strong>Observação:</strong><p style="margin-top: 0.5rem; white-space: pre-wrap;">${data.observacao || 'Nenhuma.'}</p></div>
            `;
        });
    });

    document.querySelectorAll('.searchable-select').forEach(select => {
        if (typeof createSearchableSelect === 'function') {
            createSearchableSelect(select);
        }
    });

    document.querySelectorAll('.popup-overlay form').forEach(form => {
        form.addEventListener('submit', (event) => {
            const submitter = event.submitter;

            if (submitter && submitter.name === 'action' && submitter.value === 'delete') {
                return;
            }

            const popup = form.closest('.popup-overlay');
            if (popup) {
                 window.location.hash = '#';
            }
        });
    });

    
    //Preenchimento automático do objeto do contrato.
    document.querySelectorAll('.popup-overlay form').forEach(form => {
        const licitacaoSelect = form.querySelector('select[name="licitacao_id"]');
        const objetoTextarea = form.querySelector('textarea[name="objeto"]');

        if (licitacaoSelect && objetoTextarea) {
            licitacaoSelect.addEventListener('change', () => {
                const selectedOption = licitacaoSelect.options[licitacaoSelect.selectedIndex];
                const objeto = selectedOption.dataset.objeto || '';
                objetoTextarea.value = objeto;
            });
        }
    });

     //LÓGICA DE ALTERAÇÃO E PERSISTÊNCIA DO TEMA
    const themePopup = document.getElementById('themes-popup');
    if (themePopup) {
        themePopup.addEventListener('click', (event) => {
            const themeButton = event.target.closest('button[data-theme]');
            if (!themeButton) return;

            const selectedTheme = themeButton.dataset.theme;
            document.body.setAttribute('data-theme', selectedTheme);

            if (!CSRF_TOKEN) {
                alert('Erro crítico: Token de segurança não encontrado. Por favor, recarregue a página.');
                return;
            }

            fetch('/salvar_tema', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    tema: selectedTheme,
                    csrf: CSRF_TOKEN
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                } else {
                    alert('Não foi possível salvar sua preferência de tema. Erro: ' + data.message);
                    window.location.reload();
                }
            })
            .catch(error => {
                console.error('Erro na requisição para salvar tema:', error);
                alert('Ocorreu um erro de comunicação ao tentar salvar o tema.');
            });
        });
    }

    const debounce = (func, delay = 300) => {
        let timeout;
        return (...args) => {
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                func.apply(this, args);
            }, delay);
        };
    };

    /**
     * Inicializa a funcionalidade de filtro automático para um formulário específico.
     * @param {string} formId - O ID do formulário de filtro.
     * @param {string} containerId - O ID do container onde o conteúdo da tabela será renderizado.
     */
    const initAutoFiltering = (formId, containerId) => {
        const form = document.getElementById(formId);
        const container = document.getElementById(containerId);

        if (!form || !container) {
            return;
        }

        const performFilter = async () => {
            const formData = new FormData(form);
            const params = new URLSearchParams(formData);
            const url = `${window.location.pathname}?${params.toString()}`;

            history.pushState(null, '', url);
            
            try {
                const response = await fetch(url, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                if (!response.ok) {
                    throw new Error('A resposta da rede não foi bem-sucedida.');
                }
                const newContent = await response.text();
                container.innerHTML = newContent;
            } catch (error) {
                console.error('Erro ao buscar os dados de filtro:', error);
                container.innerHTML = '<p style="text-align: center; color: red;">Ocorreu um erro ao carregar os dados.</p>';
            }
        };

        const debouncedFilter = debounce(performFilter, 350);

        form.addEventListener('submit', (e) => {
            e.preventDefault();
        });

        form.querySelectorAll('input[type="text"], select').forEach(input => {
            if (input.type === 'text') {
                input.addEventListener('keyup', debouncedFilter);
            } else {
                input.addEventListener('change', performFilter);
            }
        });
    };

    // Inicializa todos os filtros automáticos
    initAutoFiltering('filter-form', 'licitacoes-table-container');
    initAutoFiltering('resumo-filter-form', 'resumo-table-container');
    initAutoFiltering('diretas-filter-form', 'diretas-table-container');
    initAutoFiltering('contratos-filter-form', 'contratos-table-container');
    initAutoFiltering('homologadas-filter-form', 'homologadas-table-container');
    initAutoFiltering('atas-filter-form', 'atas-table-container');
    initAutoFiltering('fornecedores-filter-form', 'fornecedores-table-container');
    
});