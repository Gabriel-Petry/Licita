var Edital = {
    dados: {}
};

function atualizarAlturaAcordeaoPai(elementoFilho) {
    if (!elementoFilho) return;
    var content = elementoFilho.closest('.nav-group-content');

    if (content && content.parentElement.classList.contains('active')) {
        content.style.maxHeight = "none";
        var novaAltura = content.scrollHeight;
        content.style.maxHeight = novaAltura + "px";
    }
}

window.atualizarPreview = function(elementId, valor) {
    var el = document.getElementById(elementId);
    if (el) {
        el.innerText = valor;
    }
};

window.atualizarTextoDisputa = function() {
    console.log("Atualizando textos de disputa...");

    var radios = document.getElementsByName('input-modo-disputa');
    var modo = 'aberto';
    var textoHeader = 'Aberto';

    for (var i = 0; i < radios.length; i++) {
        if (radios[i].checked) {
            modo = radios[i].value;
            var label = document.querySelector('label[for="' + radios[i].id + '"]');
            if (label) {
                textoHeader = label.innerText.trim();
            }
            break;
        }
    }

    var elHeader = document.getElementById('view-modo-disputa-header');
    if (elHeader) {
        elHeader.innerText = textoHeader;
    }

    var textos = {
        aberto: {
            intro: "Será adotado o modo de disputa aberto, em que os licitantes apresentarão lances públicos e sucessivos, observando as regras constantes no item 7.",
            p1: "A etapa competitiva, de envio de lances na sessão pública, durará 10 (dez) minutos e, após isso, será prorrogada automaticamente pelo sistema quando houver lance ofertado nos últimos dois minutos do período de duração da sessão pública.",
            p2: "A prorrogação automática da etapa de envio de lances será de dois minutos e ocorrerá sucessivamente sempre que houver lances enviados nesse período de prorrogação, inclusive quando se tratar de lances intermediários.",
            p3: "Na hipótese de não haver novos lances, a sessão pública será encerrada automaticamente.",
            p4: "Encerrada a sessão pública sem prorrogação automática pelo sistema, o pregoeiro poderá, assessorado pela equipe de apoio, admitir o reinício da etapa de envio de lances, em prol da consecução do melhor preço, mediante justificativa.",
            p5: null,
            p6: null
        },
        aberto_fechado: {
            intro: "Será adotado o modo de disputa aberto e fechado, em que os licitantes apresentarão lances públicos e sucessivos, com lance final e fechado, observando as regras constantes no item 7.",
            p1: "A etapa de lances da sessão pública terá duração inicial de quinze minutos. Após esse prazo, o sistema encaminhará aviso de fechamento iminente dos lances, após o que transcorrerá o período de até dez minutos, aleatoriamente determinado, findo o qual será automaticamente encerrada a recepção de lances.",
            p2: "Encerrado o prazo previsto no subitem anterior, o sistema abrirá oportunidade para que o autor da oferta de valor mais baixo e os das ofertas com preços até 10% (dez por cento) superiores àquela possam ofertar um lance final e fechado em até cinco minutos, o qual será sigiloso até o encerramento deste prazo.",
            p3: "No procedimento de que trata o subitem supra, o licitante poderá optar por manter o seu último lance da etapa aberta, ou por ofertar melhor lance.",
            p4: "Não havendo pelo menos três ofertas nas condições definidas neste item, poderão os autores dos melhores lances subsequentes, na ordem de classificação, até o máximo de três, oferecer um lance final e fechado em até cinco minutos, o qual será sigiloso até o encerramento deste prazo.",
            p5: null,
            p6: null
        },
        fechado: {
            intro: "No modo de disputa “fechado e aberto”, poderão participar da etapa aberta somente os licitantes que apresentarem a proposta de menor preço/ maior percentual de desconto e os das propostas até 10% (dez por cento) superiores/inferiores àquela, em que os licitantes apresentarão lances públicos e sucessivos, até o encerramento da sessão e eventuais prorrogações.",
            p1: "Não havendo pelo menos 3 (três) propostas nas condições definidas no item anterior, poderão os licitantes que apresentaram as três melhores propostas, consideradas as empatadas, oferecer novos lances sucessivos.",
            p2: "A etapa de lances da sessão pública terá duração de dez minutos e, após isso, será prorrogada automaticamente pelo sistema quando houver lance ofertado nos últimos dois minutos do período de duração da sessão pública.",
            p3: "A prorrogação automática da etapa de lances, de que trata o subitem anterior, será de dois minutos e ocorrerá sucessivamente sempre que houver lances enviados nesse período de prorrogação, inclusive no caso de lances intermediários.",
            p4: "Não havendo novos lances na forma estabelecida nos itens anteriores, a sessão pública encerrar-se-á automaticamente, e o sistema ordenará e divulgará os lances conforme a ordem final de classificação.",
            p5: "Definida a melhor proposta, se a diferença em relação à proposta classificada em segundo lugar for de pelo menos 5% (cinco por cento), o Pregoeiro, auxiliado pela equipe de apoio, poderá admitir o reinício da disputa aberta, para a definição das demais colocações.",
            p6: "Após o reinício previsto no subitem supra, os licitantes serão convocados para apresentar lances intermediários."
        }
    };

    var t = textos[modo] || textos['aberto'];

    function set(id, txt) {
        var el = document.getElementById(id);
        if (el) {
            if (txt) {
                el.innerText = txt;
                el.style.display = 'block';
                el.style.backgroundColor = "#fff3cd";
                setTimeout(function() {
                    el.style.backgroundColor = "transparent";
                }, 500);
            } else {
                el.style.display = 'none';
                el.innerText = '';
            }
        }
    }

    set('md-intro', t.intro);
    set('md-p1', t.p1);
    set('md-p2', t.p2);
    set('md-p3', t.p3);
    set('md-p4', t.p4);
    set('md-p5', t.p5);
    set('md-p6', t.p6);

    if (typeof NumeraTudo === 'function') NumeraTudo();
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

    var elReferencia = document.getElementById('opt-item');
    if (typeof atualizarAlturaAcordeaoPai === 'function') {
        atualizarAlturaAcordeaoPai(elReferencia);
    }
};

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
    if (typeof NumeraTudo === 'function') NumeraTudo();
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
    if (typeof NumeraTudo === 'function') NumeraTudo();
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
        if (tbodyId !== 'view-tabela-itens-body') alert("Erro: Tabela destino não encontrada.");
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
                    if (confirm("Encontramos " + data.itens.length + " itens. Deseja adicionar?")) {
                        data.itens.forEach(function(item) {
                            window.adicionarItemTabela(item);
                        });
                        alert("Concluído!");
                    }
                } else {
                    alert("Nenhum item válido encontrado.");
                }
                input.value = '';
            })
            .catch(function(error) {
                document.body.style.cursor = 'default';
                console.error(error);
                alert('Erro ao processar arquivo.');
                input.value = '';
            });
    }
};

window.atualizarHabilitacao = function() {
    var ecoRadios = document.getElementsByName('opt-hab-eco');
    var ecoVal = 'complexa';
    for (var i = 0; i < ecoRadios.length; i++)
        if (ecoRadios[i].checked) ecoVal = ecoRadios[i].value;

    if (ecoVal === 'simples') {
        if (document.getElementById('texto-eco-simples')) document.getElementById('texto-eco-simples').style.display = 'block';
        if (document.getElementById('texto-eco-complexa')) document.getElementById('texto-eco-complexa').style.display = 'none';
    } else {
        if (document.getElementById('texto-eco-simples')) document.getElementById('texto-eco-simples').style.display = 'none';
        if (document.getElementById('texto-eco-complexa')) document.getElementById('texto-eco-complexa').style.display = 'block';
    }

    var tecRadios = document.getElementsByName('opt-hab-tec');
    var tecVal = 'nao';
    for (var i = 0; i < tecRadios.length; i++)
        if (tecRadios[i].checked) tecVal = tecRadios[i].value;

    var toolsTec = document.getElementById('tools-hab-tec');
    var listaTec = document.getElementById('lista-tec-dinamica');
    var textoTecSim = document.getElementById('texto-tec-sim');
    var textoTecNao = document.getElementById('texto-tec-nao');

    if (tecVal === 'sim') {
        if (toolsTec) toolsTec.style.display = 'block';
        if (textoTecSim) textoTecSim.style.display = 'block';
        if (listaTec) listaTec.style.display = 'block';
        if (textoTecNao) textoTecNao.style.display = 'none';

        var inputQtdTec = document.getElementById('input-qtd-tec');
        if (inputQtdTec && inputQtdTec.value > 0 && (!listaTec.innerHTML || listaTec.innerHTML.trim() === "")) {
            window.gerarListaTecnicaAmostra('tec');
        }
    } else {
        if (toolsTec) toolsTec.style.display = 'none';
        if (textoTecSim) textoTecSim.style.display = 'none';
        if (listaTec) listaTec.style.display = 'none';
        if (textoTecNao) textoTecNao.style.display = 'block';
    }

    var amoRadios = document.getElementsByName('opt-hab-amostra');
    var amoVal = 'nao';
    for (var i = 0; i < amoRadios.length; i++)
        if (amoRadios[i].checked) amoVal = amoRadios[i].value;

    var toolsAmo = document.getElementById('tools-hab-amostra');
    var listaAmo = document.getElementById('lista-amostra-dinamica');
    var textoAmoSim = document.getElementById('texto-amostra-sim');
    var textoAmoNao = document.getElementById('texto-amostra-nao');

    if (amoVal === 'sim') {
        if (toolsAmo) toolsAmo.style.display = 'block';
        if (textoAmoSim) textoAmoSim.style.display = 'block';
        if (listaAmo) listaAmo.style.display = 'block';
        if (textoAmoNao) textoAmoNao.style.display = 'none';

        var inputQtdAmo = document.getElementById('input-qtd-amostra');
        if (inputQtdAmo && inputQtdAmo.value > 0 && (!listaAmo.innerHTML || listaAmo.innerHTML.trim() === "")) {
            window.gerarListaTecnicaAmostra('amostra');
        }
    } else {
        if (toolsAmo) toolsAmo.style.display = 'none';
        if (textoAmoSim) textoAmoSim.style.display = 'none';
        if (listaAmo) listaAmo.style.display = 'none';
        if (textoAmoNao) textoAmoNao.style.display = 'block';
    }

    var elReferencia = document.getElementById('eco-simples');
    atualizarAlturaAcordeaoPai(elReferencia);

    if (typeof NumeraTudo === 'function') NumeraTudo();
};

window.gerarListaTecnicaAmostra = function(tipo) {
    var inputQtd = document.getElementById('input-qtd-' + tipo);
    var qtd = parseInt(inputQtd ? inputQtd.value : 0) || 0;

    var sidebarContainer = document.getElementById('sidebar-' + tipo + '-container');
    var previewContainer = document.getElementById('lista-' + tipo + '-dinamica');

    if (!sidebarContainer || !previewContainer) return;

    sidebarContainer.innerHTML = "";
    previewContainer.innerHTML = "";

    for (var i = 1; i <= qtd; i++) {
        var wrapper = document.createElement('div');
        wrapper.className = "mb-2 p-2";
        wrapper.style.border = "1px dashed #ccc";
        wrapper.style.borderRadius = "4px";
        wrapper.style.backgroundColor = "#fdfdfd";

        var label = document.createElement('label');
        label.innerText = "Item " + i + ":";
        label.style.fontWeight = "bold";
        label.style.fontSize = "0.85em";

        var textarea = document.createElement('textarea');
        textarea.rows = 2;
        textarea.className = "br-textarea small";
        textarea.style.width = "100%";
        textarea.placeholder = "Descreva a exigência...";
        textarea.dataset.itemId = i;

        var divCheck = document.createElement('div');
        divCheck.style.marginTop = "5px";
        var checkbox = document.createElement('input');
        checkbox.type = "checkbox";
        checkbox.id = "check-sub-" + tipo + "-" + i;
        checkbox.dataset.itemId = i;

        var labelCheck = document.createElement('label');
        labelCheck.htmlFor = "check-sub-" + tipo + "-" + i;
        labelCheck.innerText = " É subparágrafo?";
        labelCheck.style.fontSize = "0.8em";
        labelCheck.style.marginLeft = "5px";
        labelCheck.style.fontWeight = "normal";

        divCheck.appendChild(checkbox);
        divCheck.appendChild(labelCheck);

        var pPreview = document.createElement('p');
        pPreview.className = "subitem-4";
        pPreview.id = "view-" + tipo + "-" + i;
        pPreview.innerText = "_________________________________";

        textarea.addEventListener('input', function(e) {
            var id = e.target.dataset.itemId;
            var el = document.getElementById('view-' + tipo + '-' + id);
            if(el) {
                el.innerText = e.target.value || "_________________________________";
                if (typeof NumeraTudo === 'function') NumeraTudo();
            }
        });

        checkbox.addEventListener('change', function(e) {
            var id = e.target.dataset.itemId;
            var el = document.getElementById('view-' + tipo + '-' + id);
            if(el) {
                if (e.target.checked) {
                    el.className = "subitem-5";
                    el.style.marginLeft = "80px";
                } else {
                    el.className = "subitem-4";
                    el.style.marginLeft = "";
                }
                if (typeof NumeraTudo === 'function') NumeraTudo();
            }
        });

        wrapper.appendChild(label);
        wrapper.appendChild(textarea);
        wrapper.appendChild(divCheck);
        sidebarContainer.appendChild(wrapper);

        previewContainer.appendChild(pPreview);
    }

    if (typeof atualizarAlturaAcordeaoPai === 'function') {
        atualizarAlturaAcordeaoPai(sidebarContainer);
    }
    if (typeof NumeraTudo === 'function') NumeraTudo();
};

window.gerarCamposDotacao = function() {
    var inputQtd = document.getElementById('input-qtd-dotacoes');
    var qtd = parseInt(inputQtd ? inputQtd.value : 0) || 0;

    var sidebarContainer = document.getElementById('sidebar-dotacoes-container');
    var previewContainer = document.getElementById('container-dotacoes');

    if (!sidebarContainer || !previewContainer) return;

    sidebarContainer.innerHTML = "";
    previewContainer.innerHTML = "";

    for (var i = 1; i <= qtd; i++) {
        var divInput = document.createElement('div');
        divInput.className = "br-textarea small mb-2";

        var label = document.createElement('label');
        label.innerText = "Dotação " + i + ":";

        var textarea = document.createElement('textarea');
        textarea.rows = 2;
        textarea.placeholder = "Ex: Órgão: 02.00 - Secretaria... Funcional: 04.122...";
        textarea.dataset.dotacaoId = i;

        var pPreview = document.createElement('p');
        pPreview.className = "subitem-3";
        pPreview.id = "view-dotacao-" + i;
        pPreview.innerText = "_________________________________________________";

        textarea.addEventListener('input', function(e) {
            var id = e.target.dataset.dotacaoId;
            var el = document.getElementById('view-dotacao-' + id);
            if(el) {
                el.innerText = e.target.value || "_________________________________________________";
                if (typeof NumeraTudo === 'function') NumeraTudo();
            }
        });

        divInput.appendChild(label);
        divInput.appendChild(textarea);
        sidebarContainer.appendChild(divInput);

        previewContainer.appendChild(pPreview);
    }

    if (typeof atualizarAlturaAcordeaoPai === 'function') {
        atualizarAlturaAcordeaoPai(sidebarContainer);
    }

    if (typeof NumeraTudo === 'function') NumeraTudo();
};

window.atualizarVistoria = function() {
    var radios = document.getElementsByName('opt-vistoria');
    var val = 'nao';
    for (var i = 0; i < radios.length; i++) {
        if (radios[i].checked) val = radios[i].value;
    }

    var tools = document.getElementById('tools-vistoria');
    var texto = document.getElementById('texto-vistoria');

    if (val === 'sim') {
        if (tools) tools.style.display = 'block';
        if (texto) texto.style.display = 'block';
    } else {
        if (tools) tools.style.display = 'none';
        if (texto) texto.style.display = 'none';
    }

    var elRef = document.getElementById('vistoria-sim');
    if (typeof atualizarAlturaAcordeaoPai === 'function') {
        atualizarAlturaAcordeaoPai(elRef);
    }

    if (typeof NumeraTudo === 'function') NumeraTudo();
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
            window.adicionarItemTabela();
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

    var inputQtdDotacoes = document.getElementById('input-qtd-dotacoes');
    if (inputQtdDotacoes) {
        inputQtdDotacoes.addEventListener('input', window.gerarCamposDotacao);
    }

    var inputQtdTec = document.getElementById('input-qtd-tec');
    if (inputQtdTec) {
        inputQtdTec.addEventListener('input', function(){ window.gerarListaTecnicaAmostra('tec'); });
    }

    var inputQtdAmo = document.getElementById('input-qtd-amostra');
    if (inputQtdAmo) {
        inputQtdAmo.addEventListener('input', function(){ window.gerarListaTecnicaAmostra('amostra'); });
    }

    var radiosJulgamento = document.getElementsByName('input-tipo-jul');
    radiosJulgamento.forEach(function(radio) {
        radio.addEventListener('change', window.atualizarModoDisputa);
    });

    var radiosDisputa = document.getElementsByName('input-modo-disputa');
    radiosDisputa.forEach(function(radio) {
        radio.addEventListener('change', window.atualizarTextoDisputa);
    });

    var radiosHabEco = document.getElementsByName('opt-hab-eco');
    radiosHabEco.forEach(function(r) {
        r.addEventListener('change', window.atualizarHabilitacao);
    });

    var radiosHabTec = document.getElementsByName('opt-hab-tec');
    radiosHabTec.forEach(function(r) {
        r.addEventListener('change', window.atualizarHabilitacao);
    });

    var radiosAmostra = document.getElementsByName('opt-hab-amostra');
    radiosAmostra.forEach(function(r) {
        r.addEventListener('change', window.atualizarHabilitacao);
    });

    var radiosVistoria = document.getElementsByName('opt-vistoria');
    radiosVistoria.forEach(function(r) {
        r.addEventListener('change', window.atualizarVistoria);
    });

    if (typeof initNavAccordion === 'function') initNavAccordion();
    if (typeof initLiveEdit === 'function') initLiveEdit();
    if (typeof initReverseLiveEdit === 'function') initReverseLiveEdit();

    window.atualizarModoDisputa();
    window.atualizarTextoDisputa();
    window.atualizarHabilitacao();

    window.gerarCamposDotacao();
    window.atualizarVistoria();

    if(inputQtdTec && inputQtdTec.value > 0) window.gerarListaTecnicaAmostra('tec');
    if(inputQtdAmo && inputQtdAmo.value > 0) window.gerarListaTecnicaAmostra('amostra');

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
            if (content) document.getElementById('hidden_html_content').value = content.innerHTML;
        });
    }
});

function initLiveEdit() {
    var inputs = document.querySelectorAll('[data-live-target]');
    inputs.forEach(function(input) {
        input.addEventListener('input', function() {
            var targetId = this.getAttribute('data-live-target');
            var targetEl = document.getElementById(targetId);
            if (targetEl) targetEl.innerText = this.value;
        });
    });
}

function initReverseLiveEdit() {
    var editables = document.querySelectorAll('.editable-field');
    editables.forEach(function(el) {
        el.addEventListener('input', function() {
            var myId = this.id;
            var content = this.innerText;
            var inputEsquerda = document.querySelector('[data-live-target="' + myId + '"]');
            if (inputEsquerda) inputEsquerda.value = content;
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

    var allDetails = document.querySelectorAll('details');
    allDetails.forEach(function(det) {
        det.addEventListener('toggle', function() {
            atualizarAlturaAcordeaoPai(this);
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
                cont4 = 0,
                cont5 = 0;
            var itens = secao.querySelectorAll('.subitem, .subitem-3, .subitem-4, .subitem-5');

            itens.forEach(function(paragrafo) {
                if (paragrafo.offsetParent === null) return;

                var prefixo = "";
                if (paragrafo.classList.contains('subitem')) {
                    cont2++;
                    cont3 = 0;
                    cont4 = 0;
                    cont5 = 0;
                    prefixo = cont1 + "." + cont2 + ". ";
                } else if (paragrafo.classList.contains('subitem-3')) {
                    if (cont2 === 0) cont2 = 1;
                    cont3++;
                    cont4 = 0;
                    cont5 = 0;
                    prefixo = cont1 + "." + cont2 + "." + cont3 + ". ";
                } else if (paragrafo.classList.contains('subitem-4')) {
                    if (cont2 === 0) cont2 = 1;
                    if (cont3 === 0) cont3 = 1;
                    cont4++;
                    cont5 = 0;
                    prefixo = cont1 + "." + cont2 + "." + cont3 + "." + cont4 + ". ";
                } else if (paragrafo.classList.contains('subitem-5')) {
                    if (cont2 === 0) cont2 = 1;
                    if (cont3 === 0) cont3 = 1;
                    if (cont4 === 0) cont4 = 1;
                    cont5++;
                    prefixo = cont1 + "." + cont2 + "." + cont3 + "." + cont4 + "." + cont5 + ". ";
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

            if (secao.id) {
                var linkSidebar = document.querySelector('.editor-sidebar a[href="#' + secao.id + '"]:not(.br-button)');
                var tituloGrupo = document.querySelector('.editor-sidebar .titulo-grupo[data-target="' + secao.id + '"]');

                if (linkSidebar) {
                    var textoLimpo = linkSidebar.innerText.replace(/^\d+\.?\s*/, '');
                    linkSidebar.innerText = cont1 + ". " + textoLimpo;
                } else if (tituloGrupo) {
                    var textoLimpoG = tituloGrupo.innerText.replace(/^\d+\.?\s*/, '');
                    tituloGrupo.innerText = cont1 + ". " + textoLimpoG;
                }
            }
            secao.dataset.numeroCapitulo = cont1;
            cont1++;
        }
    });
}