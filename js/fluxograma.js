document.addEventListener('DOMContentLoaded', function() {

    if (!document.getElementById('paper-container')) {
        console.error("Erro Crítico: O elemento #paper-container não foi encontrado.");
        return;
    }

    const app = joint.shapes.app = {};

    app.CustomDocument = joint.shapes.standard.Path.define('app.CustomDocument', {
        attrs: {
            body: {
                d: 'M 0 0 L 120 0 L 120 60 Q 90 80 60 60 T 0 60 Z',
                fill: '#ffffff',
                stroke: '#000000',
                strokeWidth: 2
            },
            title: {
                text: 'Documento',
                fill: '#000000',
                fontSize: 14,
                fontWeight: 'bold',
                textVerticalAnchor: 'middle',
                textAnchor: 'middle',
                refX: '50%',
                refY: '35%'
            },
            description: {
                text: 'Descrição...',
                fill: '#555555',
                fontSize: 12,
                textVerticalAnchor: 'middle',
                textAnchor: 'middle',
                refX: '50%',
                refY: '65%'
            }
        }
    }, {
        markup: [{
            tagName: 'path',
            selector: 'body'
        }, {
            tagName: 'text',
            selector: 'title'
        }, {
            tagName: 'text',
            selector: 'description'
        }]
    });

    const portsConfig = {
        groups: {
            'inout': {
                position: { name: 'absolute' },
                attrs: { '.port-body': { r: 6, magnet: true, stroke: '#31d0c6', strokeWidth: 2, fill: '#fff' } },
                markup: '<g class="joint-port-body"><circle class="port-body"/></g>'
            }
        },
        items: [
            { id: 'top', group: 'inout', args: { x: '50%', y: 0 } },
            { id: 'right', group: 'inout', args: { x: '100%', y: '50%'} },
            { id: 'bottom', group: 'inout', args: { x: '50%', y: '100%'} },
            { id: 'left', group: 'inout', args: { x: 0, y: '50%'} }
        ]
    };

    const graph = new joint.dia.Graph({}, { cellNamespace: joint.shapes });
    const paper = new joint.dia.Paper({
        el: document.getElementById('paper-container'),
        model: graph,
        width: '100%',
        height: '100%',
        gridSize: 10,
        drawGrid: true,
        background: { color: 'transparent' },
        cellViewNamespace: joint.shapes,
        interactive: { elementMove: true },
        markAvailable: true,
        linkPinning: true,
        multiLinks: true,
        snapLinks: { radius: 75 },
        defaultLink: new joint.shapes.standard.Link({
            router: { name: 'manhattan' },
            connector: { name: 'rounded' },
            attrs: { line: { stroke: '#585858', strokeWidth: 2, targetMarker: { 'type': 'path', 'd': 'M 10 -5 L 0 0 L 10 5 z', 'fill': '#585858' } } }
        }),
        validateConnection: (cellViewS, magnetS, cellViewT, magnetT, end, linkView) => (cellViewS !== cellViewT)
    });

    const sidebar = document.getElementById('sidebar');
    sidebar.addEventListener('mousedown', function(e) {
        if (!e.target.classList.contains('shape-item')) return;
        
        const type = e.target.getAttribute('data-type');
        let shape;

        if (type === 'processo') {
            shape = new joint.shapes.standard.Rectangle({
                size: { width: 120, height: 60 },
                attrs: { body: { fill: '#ffffff', stroke: '#000000', rx: 4, ry: 4 }, label: { text: 'Processo', fill: '#000000' } },
                ports: { ...portsConfig }
            });
        } else if (type === 'decisao') {
            shape = new joint.shapes.standard.Polygon({
                size: { width: 100, height: 100 },
                attrs: { body: { fill: '#ffffff', stroke: '#000000', refPoints: '50,0 100,50 50,100 0,50' }, label: { text: 'Decisão', fill: '#000000' } },
                ports: { ...portsConfig }
            });
        } else if (type === 'inicio_fim') {
            shape = new joint.shapes.standard.Ellipse({
                size: { width: 120, height: 60 },
                attrs: { body: { fill: '#ffffff', stroke: '#000000' }, label: { text: 'Início/Fim', fill: '#000000' } },
                ports: { ...portsConfig }
            });
        } else if (type === 'document') {
            shape = new app.CustomDocument({
                size: { width: 150, height: 100 },
                ports: { ...portsConfig }
            });
        } 
        else if (type === 'module') {
            shape = new joint.shapes.standard.HeaderedRectangle({
                size: { width: 150, height: 80 },
                attrs: {
                    header: { fill: '#e0e0e0', stroke: '#000000' },
                    headerText: { text: 'Módulo', fill: '#000000' },
                    body: { fill: '#ffffff', stroke: '#000000' },
                    bodyText: { text: 'Conteúdo...', fill: '#555555' }
                },
                ports: { ...portsConfig }
            });
        } else if (type === 'annotation') {
            shape = new joint.shapes.standard.Path({
                size: { width: 150, height: 70 },
                attrs: {
                    body: {
                        refD: 'M 20 0 L 150 0 L 150 70 L 20 70 L 0 35 Z',
                        fill: '#ffffcc',
                        stroke: '#d6d6a8'
                    },
                    label: {
                        text: 'Escreva sua\nanotação aqui...',
                        fill: '#555555'
                    }
                }
            });
        }
        
        if (!shape) return;

        const ghost = document.createElement('div');
        ghost.textContent = e.target.textContent;
        ghost.classList.add('shape-item');
        ghost.style.position = 'absolute';
        ghost.style.zIndex = '9999';
        ghost.style.opacity = '0.7';
        ghost.style.pointerEvents = 'none';
        document.body.appendChild(ghost);

        const paperContainer = document.getElementById('paper-container');
        const paperRect = paperContainer.getBoundingClientRect();

        const moveGhost = (x, y) => {
            ghost.style.left = `${x - ghost.offsetWidth / 2}px`;
            ghost.style.top = `${y - ghost.offsetHeight / 2}px`;
        };
        moveGhost(e.clientX, e.clientY);

        const onMouseMove = (moveEvent) => moveGhost(moveEvent.clientX, moveEvent.clientY);

        const onMouseUp = (upEvent) => {
            document.removeEventListener('mousemove', onMouseMove);
            document.removeEventListener('mouseup', onMouseUp);

            const isOverPaper = upEvent.clientX > paperRect.left && upEvent.clientX < paperRect.right &&
                                  upEvent.clientY > paperRect.top && upEvent.clientY < paperRect.bottom;

            if (isOverPaper) {
                const localPoint = paper.clientToLocalPoint({ x: upEvent.clientX, y: upEvent.clientY });
                shape.position(localPoint.x - shape.size().width / 2, localPoint.y - shape.size().height / 2);
                graph.addCell(shape);
            }
            ghost.remove();
        };

        document.addEventListener('mousemove', onMouseMove);
        document.addEventListener('mouseup', onMouseUp);
    });

    const propertiesPanel = document.getElementById('properties-panel');
    const propTitle = document.getElementById('prop-title');
    const propDesc = document.getElementById('prop-desc');
    const btnRemove = document.getElementById('btn-remove');
    let selectedCell = null;
    
    const propWidth = document.getElementById('prop-width');
    const propHeight = document.getElementById('prop-height');
    const propBgColor = document.getElementById('prop-bg-color');
    const propBorderColor = document.getElementById('prop-border-color');
    const propTextColor = document.getElementById('prop-text-color');

    paper.on('cell:pointerclick', function(cellView) {
        if (cellView.model.isLink()) {
            selectedCell = null;
            propertiesPanel.style.display = 'none';
            return;
        }
        selectedCell = cellView.model;
        
        if (selectedCell.get('type') === 'app.CustomDocument') {
            propTitle.value = selectedCell.attr('title/text');
            propDesc.value = selectedCell.attr('description/text');
        } else {
            propTitle.value = selectedCell.attr('label/text') || selectedCell.attr('headerText/text') || '';
            propDesc.value = selectedCell.prop('custom/description') || selectedCell.attr('bodyText/text') || '';
        }

        const size = selectedCell.size();
        propWidth.value = size.width;
        propHeight.value = size.height;
        
        propBgColor.value = selectedCell.attr('body/fill') || selectedCell.attr('header/fill') || '#ffffff';
        propBorderColor.value = selectedCell.attr('body/stroke') || '#000000';
        propTextColor.value = selectedCell.attr('label/fill') || selectedCell.attr('title/fill') || selectedCell.attr('headerText/fill') || '#000000';

        propertiesPanel.style.display = 'block';
    });
    
    paper.on('blank:pointerclick', function() {
        selectedCell = null;
        propertiesPanel.style.display = 'none';
    });
    
    propTitle.addEventListener('input', function(e) {
        if (!selectedCell) return;
        if (selectedCell.get('type') === 'app.CustomDocument') {
            selectedCell.attr('title/text', e.target.value);
        } else if (selectedCell.attr('headerText')) {
            selectedCell.attr('headerText/text', e.target.value);
        } else {
            selectedCell.attr('label/text', e.target.value);
        }
    });
    propDesc.addEventListener('input', function(e) {
        if (!selectedCell) return;
        if (selectedCell.get('type') === 'app.CustomDocument') {
            selectedCell.attr('description/text', e.target.value);
        } else if (selectedCell.attr('bodyText')) {
            selectedCell.attr('bodyText/text', e.target.value);
        } else {
             selectedCell.prop('custom/description', e.target.value);
        }
    });

    propWidth.addEventListener('input', (e) => {
        if (selectedCell) selectedCell.resize(parseInt(e.target.value), selectedCell.size().height);
    });
    propHeight.addEventListener('input', (e) => {
        if (selectedCell) selectedCell.resize(selectedCell.size().width, parseInt(e.target.value));
    });
    propBgColor.addEventListener('input', (e) => {
        if (selectedCell) {
            if (selectedCell.attr('header')) {
                selectedCell.attr('header/fill', e.target.value);
            } else {
                selectedCell.attr('body/fill', e.target.value);
            }
        }
    });
    propBorderColor.addEventListener('input', (e) => {
        if (selectedCell) selectedCell.attr('body/stroke', e.target.value);
    });
    propTextColor.addEventListener('input', (e) => {
        if (selectedCell) {
            const color = e.target.value;
            if (selectedCell.attr('label')) selectedCell.attr('label/fill', color);
            if (selectedCell.attr('title')) selectedCell.attr('title/fill', color);
            if (selectedCell.attr('headerText')) selectedCell.attr('headerText/fill', color);
        }
    });

    btnRemove.addEventListener('click', function() {
        if (selectedCell) {
            selectedCell.remove();
            selectedCell = null;
            propertiesPanel.style.display = 'none';
        }
    });

    paper.on('link:mouseenter', (linkView) => {
        const removeButton = new joint.linkTools.Remove();
        linkView.addTools(new joint.dia.ToolsView({ tools: [removeButton] }));
    });

    paper.on('link:mouseleave', (linkView) => linkView.removeTools());

    const togglePorts = (cellView, visible) => {
        if (!cellView.model.isLink()) {
            const ports = cellView.el.querySelectorAll('.joint-port-body');
            ports.forEach(port => port.style.visibility = visible ? 'visible' : 'hidden');
        }
    };
    paper.on('cell:mouseenter', (cellView) => togglePorts(cellView, true));
    paper.on('cell:mouseleave', (cellView) => togglePorts(cellView, false));

    const paperContainer = document.getElementById('paper-container');
    let isPanning = false;
    let panStartX, panStartY;

    paperContainer.addEventListener('wheel', (event) => {
        if (!event.ctrlKey) return;
        event.preventDefault();
        const localPoint = paper.clientToLocalPoint({ x: event.offsetX, y: event.offsetY });
        const scale = paper.scale().sx;
        const newScale = event.deltaY < 0 ? scale * 1.1 : scale / 1.1;
        if (newScale > 0.3 && newScale < 4) {
            paper.scale(newScale, newScale, localPoint.x, localPoint.y);
        }
    });

    paper.on('blank:pointerdown', (event) => {
        if (!event.altKey) return;
        isPanning = true;
        panStartX = event.clientX;
        panStartY = event.clientY;
        paperContainer.style.cursor = 'grabbing';
    });

    paperContainer.addEventListener('mousemove', (event) => {
        if (!isPanning) return;
        const dx = event.clientX - panStartX;
        const dy = event.clientY - panStartY;
        paper.translate(paper.translate().tx + dx, paper.translate().ty + dy);
        panStartX = event.clientX;
        panStartY = event.clientY;
    });

    const stopPanning = () => {
        if (isPanning) {
            isPanning = false;
            paperContainer.style.cursor = 'default';
        }
    };
    paperContainer.addEventListener('mouseup', stopPanning);
    paperContainer.addEventListener('mouseleave', stopPanning);

    const btnSalvar = document.getElementById('btn-salvar-fluxograma');

    if (btnSalvar) {
        btnSalvar.addEventListener('click', () => {
            const inputNome = document.getElementById('fluxograma-nome');
            const nome = inputNome.value.trim();
            
            if (!nome) {
                alert('Por favor, dê um nome ao seu fluxograma antes de salvar.');
                inputNome.focus();
                return;
            }

            const dadosDoGrafico = graph.toJSON();

            btnSalvar.textContent = 'Salvando...';
            btnSalvar.disabled = true;

            fetch('/salvar-fluxograma', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    nome: nome,
                    dados_json: dadosDoGrafico
                })
            })
            .then(response => {
                if (!response.ok) {
                    return response.text().then(text => { throw new Error(`Erro do Servidor (Status ${response.status}): ${text}`) });
                }
                return response.json();
            })
            .then(data => {
                if (data.sucesso) {
                    alert(data.mensagem);
                } else {
                    throw new Error(data.mensagem || 'Ocorreu um erro desconhecido no servidor.');
                }
            })
            .catch(error => {
                console.error('Erro ao salvar:', error);
                alert('Ocorreu um erro ao tentar salvar o fluxograma. Verifique o console para mais detalhes.');
            })
            .finally(() => {
                btnSalvar.textContent = 'Salvar';
                btnSalvar.disabled = false;
            });
        });
    } else {
        console.error("Erro Crítico: O botão #btn-salvar-fluxograma não foi encontrado no HTML.");
    }
});