document.addEventListener('DOMContentLoaded', function () {
    const previewContainer = document.getElementById('fluxograma-preview-container');
    const paperContainer = document.getElementById('preview-paper');
    const placeholder = document.getElementById('preview-placeholder');
    const fluxogramaList = document.getElementById('fluxograma-list');

    if (!paperContainer || !fluxogramaList || !previewContainer) {
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


    const graph = new joint.dia.Graph({}, { cellNamespace: joint.shapes });
    const paper = new joint.dia.Paper({
        el: paperContainer,
        model: graph,
        width: '100%',
        height: '100%',
        gridSize: 10,
        drawGrid: { name: 'dot', args: { color: '#ddd' } },
        background: { color: 'transparent' },
        interactive: false
    });

    function carregarPreview(item) {
        const fluxogramaId = item.getAttribute('data-id');

        document.querySelectorAll('.fluxo-item.active').forEach(el => el.classList.remove('active'));
        item.classList.add('active');
        
        placeholder.classList.remove('placeholder-active');
        paperContainer.style.display = 'block';
        
        fetch(`/api_get_fluxograma.php?id=${fluxogramaId}`)
            .then(response => {
                return response.json().then(data => ({ 
                    ok: response.ok, 
                    status: response.status,
                    data 
                }));
            })
            .then(({ ok, status, data }) => {
                if (!ok) {
                    throw new Error(data.mensagem || `Erro ${status} do servidor.`);
                }
                graph.fromJSON(data);
                paper.unfreeze();
            })
            .catch(error => {
                console.error('Erro ao carregar preview:', error);
                alert(`Ocorreu um erro ao carregar o preview: ${error.message}`);
                
                placeholder.classList.add('placeholder-active');
                paperContainer.style.display = 'none';
            });
    }

    fluxogramaList.addEventListener('click', function(e) {
        const deleteButton = e.target.closest('.btn-action.delete');
        if (deleteButton) {
            e.stopPropagation();
            const fluxogramaId = deleteButton.getAttribute('data-id');
            const listItem = deleteButton.closest('.fluxo-item');
            const fluxogramaName = listItem.querySelector('.fluxo-item-name').textContent;

            if (confirm(`Tem a certeza de que deseja excluir o fluxograma "${fluxogramaName}"? Esta ação não pode ser desfeita.`)) {
                fetch('/api_delete_fluxograma.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: fluxogramaId })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.sucesso) {
                        alert(data.mensagem);
                        listItem.remove();
                    } else {
                        throw new Error(data.mensagem || 'Ocorreu um erro desconhecido.');
                    }
                })
                .catch(error => {
                    console.error('Erro ao excluir:', error);
                    alert(`Ocorreu um erro ao tentar excluir o fluxograma: ${error.message}`);
                });
            }
            return;
        }

        const item = e.target.closest('.fluxo-item');
        if (item) {
            carregarPreview(item);
        }
    });

    let isPanning = false;
    let panStartX, panStartY;

    previewContainer.addEventListener('wheel', (event) => {
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
        previewContainer.style.cursor = 'grabbing';
    });

    previewContainer.addEventListener('mousemove', (event) => {
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
            previewContainer.style.cursor = 'default';
        }
    };
    previewContainer.addEventListener('mouseup', stopPanning);
    previewContainer.addEventListener('mouseleave', stopPanning);
});