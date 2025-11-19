var Edital = {
    dados: {}
};

window.atualizarPreview = function(elementId, valor) {
    var el = document.getElementById(elementId);
    if (el) {
        el.innerText = valor;
    }
};

window.atualizarModoDisputa = function() {
    var radios = document.getElementsByName('input-tipo-jul');
    var selecionado = 'item';
    for (var i = 0; i < radios.length; i++) {
        if (radios[i].checked) {
            selecionado = radios[i].value;
        }
    }

    var dataStore = document.getElementById('edital-data-store');
    var criterioValor = (Edital.dados && Edital.dados.cj) ? Edital.dados.cj : (dataStore ? dataStore.dataset.cj : '1');
    var prefixo = (criterioValor === '0') ? "Maior Desconto" : "Menor Preço";

    var textoFinal = "";
    if (selecionado === 'global') {
        textoFinal = prefixo + " Global";
    } else if (selecionado === 'lote') {
        textoFinal = prefixo + " por Lote";
    } else {
        textoFinal = prefixo + " por Item";
    }

    var elementoTexto = document.getElementById('texto-tipo-julgamento');
    if (elementoTexto) {
        elementoTexto.innerText = textoFinal;
    }

    var divQtd = document.getElementById('div-qtd-lotes');
    var divSelDestino = document.getElementById('div-select-lote-destino');

    if (selecionado === 'lote') {
        if (divQtd) divQtd.style.display = 'block';
        if (divSelDestino) divSelDestino.style.display = 'block';
        window.gerarEstruturaLotes();
    } else {
        if (divQtd) divQtd.style.display = 'none';
        if (divSelDestino) divSelDestino.style.display = 'none';
        window.gerarTabelaUnica();
    }

    recalcularAlturaMenuAtivo();
};

function recalcularAlturaMenuAtivo() {
    var activeGroup = document.querySelector('.nav-group.active');
    if (activeGroup) {
        var content = activeGroup.querySelector('.nav-group-content');
        if (content) {
            content.style.maxHeight = 'none';
            var novaAltura = content.scrollHeight;
            content.style.maxHeight = novaAltura + "px";
        }
    }
}

window.gerarEstruturaLotes = function() {
    var inputQtd = document.getElementById('input-qtd-lotes');
    var qtd = parseInt(inputQtd.value) || 1;

    var container = document.getElementById('container-tabelas-itens');
    var selectDestino = document.getElementById('select-lote-destino');

    if (!container) return;

    container.innerHTML = "";
    if (selectDestino) selectDestino.innerHTML = "";

    for (var i = 1; i <= qtd; i++) {
        var pTitulo = document.createElement('p');
        pTitulo.className = "subitem-3 bold mt-3";
        pTitulo.style.textTransform = "uppercase";
        pTitulo.innerText = "LOTE " + (i < 10 ? '0' + i : i);
        container.appendChild(pTitulo);

        var table = document.createElement('table');
        table.style.width = "100%";
        table.style.borderCollapse = "collapse";
        table.style.marginBottom = "15px";

        table.innerHTML = `
            <thead>
                <tr style="background-color: #f2f2f2;">
                    <th style="border: 1px solid #000; padding: 5px; text-align: center; width: 10%;">ITEM</th>
                    <th style="border: 1px solid #000; padding: 5px; text-align: center; width: 10%;">UNID.</th>
                    <th style="border: 1px solid #000; padding: 5px; text-align: left;">DESCRIÇÃO</th>
                    <th style="border: 1px solid #000; padding: 5px; text-align: center; width: 15%;">QTD.</th>
                </tr>
            </thead>
            <tbody id="view-tabela-lote-${i}-body"></tbody>
        `;
        container.appendChild(table);

        if (selectDestino) {
            var option = document.createElement('option');
            option.value = i;
            option.text = "Lote " + i;
            selectDestino.appendChild(option);
        }
    }

    if (typeof NumeraTudo === 'function') {
        NumeraTudo();
    }
};

window.gerarTabelaUnica = function() {
    var container = document.getElementById('container-tabelas-itens');
    if (!container) return;

    container.innerHTML = `
        <table style="width: 100%; border-collapse: collapse; margin-top: 10px;">
            <thead>
                <tr style="background-color: #f2f2f2;">
                    <th style="border: 1px solid #000; padding: 5px; text-align: center; width: 10%;">ITEM</th>
                    <th style="border: 1px solid #000; padding: 5px; text-align: center; width: 10%;">MEDIDA</th>
                    <th style="border: 1px solid #000; padding: 5px; text-align: left;">DESCRIÇÃO</th>
                    <th style="border: 1px solid #000; padding: 5px; text-align: center; width: 15%;">QUANT.</th>
                </tr>
            </thead>
            <tbody id="view-tabela-itens-body"></tbody>
        </table>
    `;

    if (typeof NumeraTudo === 'function') {
        NumeraTudo();
    }
};

window.adicionarItemTabela = function(dados) {
    var isManual = !dados || (dados instanceof Event);

    var nr = !isManual ? dados.nr : document.getElementById('add-item-nr').value;
    var un = !isManual ? dados.un : document.getElementById('add-item-un').value;
    var qtd = !isManual ? dados.qtd : document.getElementById('add-item-qtd').value;
    var desc = !isManual ? dados.desc : document.getElementById('add-item-desc').value;

    if (!desc && isManual) {
        alert("Preencha a Descrição.");
        return;
    }

    var tbodyId = 'view-tabela-itens-body';
    var selectDestino = document.getElementById('select-lote-destino');

    if (selectDestino && selectDestino.offsetParent !== null) {
        var loteSelecionado = selectDestino.value;
        tbodyId = 'view-tabela-lote-' + loteSelecionado + '-body';
    }

    var tbody = document.getElementById(tbodyId);
    if (!tbody) {
        console.error('Tabela destino não encontrada: ' + tbodyId);
        if (tbodyId !== 'view-tabela-itens-body') {
            alert("Erro: A tabela do Lote " + selectDestino.value + " não foi encontrada.");
        }
        return;
    }

    var row = document.createElement('tr');
    row.innerHTML =
        '<td class="center" style="text-align:center; border:1px solid #000; padding:5px;">' + nr + '</td>' +
        '<td class="center" style="text-align:center; border:1px solid #000; padding:5px;">' + un + '</td>' +
        '<td style="border:1px solid #000; padding:5px;">' + desc + '</td>' +
        '<td class="center" style="text-align:center; border:1px solid #000; padding:5px;">' + qtd + '</td>';

    tbody.appendChild(row);

    if (isManual) {
        document.getElementById('add-item-nr').value = "";
        document.getElementById('add-item-desc').value = "";
        document.getElementById('add-item-un').value = "";
        document.getElementById('add-item-qtd').value = "";
    }
};

window.limparTabela = function() {
    if (confirm("Tem certeza? Isso apagará itens de TODOS os lotes/tabelas.")) {
        var tbody = document.getElementById('view-tabela-itens-body');
        if (tbody) tbody.innerHTML = "";

        var container = document.getElementById('container-tabelas-itens');
        if (container) {
            var tbodies = container.querySelectorAll('tbody');
            tbodies.forEach(function(tb) {
                tb.innerHTML = "";
            });
        }
    }
};

window.importarItens = function(input) {
    if (input.files && input.files[0]) {
        var formData = new FormData();
        formData.append('arquivo', input.files[0]);
        document.body.style.cursor = 'wait';

        fetch('/api_importar_itens.php', {
                method: 'POST',
                body: formData
            })
            .then(function(response) {
                return response.json();
            })
            .then(function(data) {
                document.body.style.cursor = 'default';
                if (data.erro) {
                    alert('Erro: ' + data.erro);
                } else if (data.itens && data.itens.length > 0) {
                    if (confirm("Encontramos " + data.itens.length + " itens. Deseja adicionar à tabela?")) {
                        data.itens.forEach(function(item) {
                            window.adicionarItemTabela(item);
                        });
                        alert("Importação concluída!");
                    }
                } else {
                    alert("Nenhum item válido encontrado na planilha.");
                }
                input.value = '';
            })
            .catch(function(error) {
                document.body.style.cursor = 'default';
                console.error('Erro na requisição:', error);
                alert('Erro ao processar arquivo. Verifique o console.');
                input.value = '';
            });
    }
};

document.addEventListener('DOMContentLoaded', function() {
    var dataStore = document.getElementById('edital-data-store');
    if (dataStore) {
        Edital.dados = { ...dataStore.dataset
        };
    }

    var btnAdd = document.getElementById('btn-add-item');
    if (btnAdd) {
        btnAdd.addEventListener('click', function(e) {
            e.preventDefault();
            adicionarItemTabela();
        });
    }

    var btnLimpar = document.getElementById('btn-limpar-tabela');
    if (btnLimpar) {
        btnLimpar.addEventListener('click', function(e) {
            e.preventDefault();
            limparTabela();
        });
    }

    var inputImportar = document.getElementById('input-importar-planilha');
    if (inputImportar) {
        inputImportar.addEventListener('change', function() {
            window.importarItens(this);
        });
    }

    var inputQtdLotes = document.getElementById('input-qtd-lotes');
    if (inputQtdLotes) {
        inputQtdLotes.addEventListener('input', window.gerarEstruturaLotes);
    }

    var radios = document.getElementsByName('input-tipo-jul');
    radios.forEach(function(radio) {
        radio.addEventListener('change', window.atualizarModoDisputa);
    });

    if (typeof initNavAccordion === 'function') initNavAccordion();
    if (typeof initLiveEdit === 'function') initLiveEdit();

    window.atualizarModoDisputa();

    if (typeof NumeraTudo === 'function') NumeraTudo();

    var observer = new MutationObserver(function() {
        if (typeof NumeraTudo === 'function') NumeraTudo();
    });
    var previewContainer = document.querySelector('.editor-preview');
    if (previewContainer) {
        observer.observe(previewContainer, {
            attributes: true,
            subtree: true,
            attributeFilter: ['class', 'style']
        });
    }

    var formPdf = document.getElementById('pdf-form');
    if (formPdf) {
        formPdf.addEventListener('submit', function() {
            var content = document.querySelector('.document-paper');
            if (content) {
                document.getElementById('hidden_html_content').value = content.innerHTML;
            }
        });
    }
});

function initLiveEdit() {
    var inputs = document.querySelectorAll('[data-live-target]');
    inputs.forEach(function(input) {
        input.addEventListener('input', function() {
            var targetId = this.getAttribute('data-live-target');
            var targetEl = document.getElementById(targetId);
            if (targetEl) {
                if (this.tagName === 'TEXTAREA') {
                    targetEl.innerText = this.value;
                } else {
                    targetEl.innerText = this.value;
                }
            }
        });
    });
}

function initNavAccordion() {
    var headers = document.querySelectorAll('.nav-group-header');
    headers.forEach(function(header) {
        header.addEventListener('click', function() {
            var group = this.parentElement;
            var content = group.querySelector('.nav-group-content');

            group.classList.toggle('active');

            if (group.classList.contains('active')) {
                content.style.maxHeight = content.scrollHeight + "px";
            } else {
                content.style.maxHeight = null;
            }
        });
    });

    var links = document.querySelectorAll('.nav-item, .sub-item');
    links.forEach(function(link) {
        link.addEventListener('click', function() {
            document.querySelectorAll('.nav-item, .sub-item').forEach(function(l) {
                l.classList.remove('active-link');
            });
            link.classList.add('active-link');
        });
    });
}

function NumeraTudo() {
    var secoes = document.querySelectorAll('.secao-numerada');
    var cont1 = 1;
    secoes.forEach(function(secao) {
        var style = window.getComputedStyle(secao);

        if (style.display !== 'none' && !secao.classList.contains('hidden')) {
            var spanTitulo = secao.querySelector('.nr-titulo');
            if (spanTitulo) spanTitulo.textContent = cont1;

            var cont2 = 0,
                cont3 = 0,
                cont4 = 0;
            var itens = secao.querySelectorAll('.subitem, .subitem-3, .subitem-4');

            itens.forEach(function(paragrafo) {
                if (paragrafo.classList.contains('hidden')) return;

                var prefixo = "";
                if (paragrafo.classList.contains('subitem')) {
                    cont2++;
                    cont3 = 0;
                    cont4 = 0;
                    prefixo = cont1 + "." + cont2 + ". ";
                } else if (paragrafo.classList.contains('subitem-3')) {
                    if (cont2 === 0) cont2 = 1;
                    cont3++;
                    cont4 = 0;
                    prefixo = cont1 + "." + cont2 + "." + cont3 + ". ";
                } else if (paragrafo.classList.contains('subitem-4')) {
                    if (cont2 === 0) cont2 = 1;
                    if (cont3 === 0) cont3 = 1;
                    cont4++;
                    prefixo = cont1 + "." + cont2 + "." + cont3 + "." + cont4 + ". ";
                }

                var spanNumero = paragrafo.querySelector('.nr-auto');
                if (!spanNumero) {
                    spanNumero = document.createElement('span');
                    spanNumero.className = 'nr-auto';
                    spanNumero.style.fontWeight = 'bold';
                    spanNumero.style.marginRight = '5px';
                    paragrafo.prepend(spanNumero);
                }
                spanNumero.textContent = prefixo;
            });

            secao.dataset.numeroCapitulo = cont1;
            cont1++;
        }
    });

    var linksSidebar = document.querySelectorAll('.nav-item, .titulo-grupo');
    linksSidebar.forEach(function(link) {
        var targetId = null;
        if (link.tagName === 'A') {
            targetId = link.getAttribute('href') ? link.getAttribute('href').substring(1) : null;
        } else if (link.tagName === 'SPAN') {
            targetId = link.parentElement.parentElement.querySelector('a') ? null : null;
        }
    });
}