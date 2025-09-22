// htdocs/js/demandas.js

document.addEventListener('DOMContentLoaded', () => {
    // Procura o formulário de nova demanda na página
    const form = document.getElementById('form-nova-demanda');
    // Se não encontrar o formulário, não faz mais nada
    if (!form) return;

    const container = form.querySelector('#itens-container');
    const addButton = form.querySelector('#add-item-btn');
    let itemIndex = 0;

    const addItem = () => {
        const itemDiv = document.createElement('div');
        itemDiv.className = 'demanda-item';
        itemDiv.style.cssText = 'padding: 1rem; border: 1px solid var(--cor-borda); border-radius: var(--raio-borda); margin-bottom: 1rem;';

        itemDiv.innerHTML = `
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <strong>Item ${itemIndex + 1}</strong>
                <button type="button" class="btn warn btn-sm remove-item-btn">Remover</button>
            </div>
            <label>Descrição do Item</label>
            <textarea name="items[${itemIndex}][descricao]" rows="2" required></textarea>
            <div class="grid grid-3" style="margin-top: 1rem;">
                <div><label>Quantidade</label><input type="text" name="items[${itemIndex}][quantidade]" class="numeric-mask" required></div>
                <div><label>Unidade de Medida</label><input name="items[${itemIndex}][unidade]" required placeholder="Ex: Un, Cx, Kg..."></div>
                <div><label>Valor Unitário Estimado (R$)</label><input type="text" name="items[${itemIndex}][valor_unitario]" class="numeric-mask" required></div>
            </div>
        `;

        container.appendChild(itemDiv);
        itemIndex++;
    };

    if (addButton) {
        addButton.addEventListener('click', addItem);
        
        // Adiciona o primeiro item automaticamente
        if (container.children.length === 0) {
            addItem();
        }
    }

    if (container) {
        // Event listener para o botão de remover
        container.addEventListener('click', (e) => {
            if (e.target.classList.contains('remove-item-btn')) {
                e.target.closest('.demanda-item').remove();
            }
        });
        
        // Event listener para a máscara de formatação numérica
        container.addEventListener('input', (e) => {
            if (e.target.classList.contains('numeric-mask')) {
                let value = e.target.value.replace(/\D/g, "");
                value = value.replace(/(\d)(\d{2})$/, "$1,$2");
                value = value.replace(/(?=(\d{3})+(\D))\B/g, ".");
                e.target.value = value;
            }
        });
    }
});