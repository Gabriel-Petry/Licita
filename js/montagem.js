var Edital = {
    dados: {},
};

window.atualizarPreview = function(elementId, valor) {
    var el = document.getElementById(elementId);
    if (el) el.innerText = valor;
};

window.adicionarItemTabela = function(dados) {
    var nr   = dados ? dados.nr   : document.getElementById('add-item-nr').value;
    var un   = dados ? dados.un   : document.getElementById('add-item-un').value;
    var qtd  = dados ? dados.qtd  : document.getElementById('add-item-qtd').value;
    var desc = dados ? dados.desc : document.getElementById('add-item-desc').value;

    if (!desc) {
        if(!dados) alert("Preencha a Descrição.");
        return;
    }

    var tbody = document.getElementById('view-tabela-itens-body');
    if (!tbody) {
        console.error('Tabela "view-tabela-itens-body" não encontrada.');
        return;
    }
    var row = document.createElement('tr');

    row.innerHTML = 
        '<td class="center" style="text-align:center;">' + nr + '</td>' +
        '<td class="center" style="text-align:center;">' + un + '</td>' +
        '<td>' + desc + '</td>' +
        '<td class="center" style="text-align:center;">' + qtd + '</td>';

    tbody.appendChild(row);

    if (!dados) {
        document.getElementById('add-item-nr').value = "";
        document.getElementById('add-item-desc').value = "";
        document.getElementById('add-item-un').value = "";
        document.getElementById('add-item-qtd').value = "";
    }
};

window.limparTabela = function() {
    if(confirm("Tem certeza que deseja apagar todos os itens da tabela?")) {
        var tbody = document.getElementById('view-tabela-itens-body');
        if(tbody) tbody.innerHTML = "";
    }
};

window.importarItens = function(input) {
    if (input.files && input.files[0]) {
        var formData = new FormData();
        formData.append('arquivo', input.files[0]);
        document.body.style.cursor = 'wait';

        fetch('api_importar_itens.php', {
            method: 'POST',
            body: formData
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            document.body.style.cursor = 'default';
            if (data.erro) {
                alert('Erro: ' + data.erro);
            } else if (data.itens && data.itens.length > 0) {
                if(confirm("Encontramos " + data.itens.length + " itens. Deseja adicionar à tabela?")) {
                    data.itens.forEach(function(item) {
                        adicionarItemTabela(item);
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
            console.error('Erro:', error);
            alert('Erro ao processar arquivo.');
            input.value = '';
        });
    }
};

document.addEventListener('DOMContentLoaded', function() {
    var dataStore = document.getElementById('edital-data-store');
    if (dataStore) Edital.dados = { ...dataStore.dataset };

    NumeraTudo();
    initNavAccordion();
    initLiveEdit();

    var observer = new MutationObserver(function() { NumeraTudo(); });
    var previewContainer = document.querySelector('.editor-preview');
    if (previewContainer) {
        observer.observe(previewContainer, { attributes: true, subtree: true, attributeFilter: ['class', 'style'] });
    }

    var formPdf = document.getElementById('pdf-form');
    if(formPdf) {
        formPdf.addEventListener('submit', function() {
             var content = document.querySelector('.document-paper');
             if(content) {
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

            var cont2 = 0, cont3 = 0, cont4 = 0;
            var itens = secao.querySelectorAll('.subitem, .subitem-3, .subitem-4');
            itens.forEach(function(paragrafo) {
                if (paragrafo.classList.contains('hidden')) return;
                var prefixo = "";
                if (paragrafo.classList.contains('subitem')) {
                    cont2++; cont3 = 0; cont4 = 0;
                    prefixo = cont1 + "." + cont2 + ". ";
                } else if (paragrafo.classList.contains('subitem-3')) {
                    if (cont2 === 0) cont2 = 1;
                    cont3++; cont4 = 0;
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
        if(link.tagName === 'A') {
             targetId = link.getAttribute('href') ? link.getAttribute('href').substring(1) : null;
        } else if (link.tagName === 'SPAN') {
            targetId = link.dataset.targetId;
        }
        
        if(targetId) {
            var targetEl = document.getElementById(targetId);
            if(targetEl && targetEl.dataset.numeroCapitulo) {
                if (!link.dataset.originalText) {
                    link.dataset.originalText = link.innerText.replace(/^[0-9]+\.\s*/, '');
                }
                link.innerText = targetEl.dataset.numeroCapitulo + ". " + link.dataset.originalText;
            }
        }
    });
}