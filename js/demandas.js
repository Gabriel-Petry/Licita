document.addEventListener('DOMContentLoaded', () => {

    const applyMask = (input) => {
        let value = input.value.replace(/\D/g, "");
        value = value.replace(/(\d)(\d{2})$/, "$1,$2");
        value = value.replace(/(?=(\d{3})+(\D))\B/g, ".");
        input.value = value;
    };
    
    const addItem = (container, itemIndex) => {
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
    };

    const allForms = document.querySelectorAll('.form-popup');

    allForms.forEach(form => {
        const container = form.querySelector('.itens-container');
        const addButton = form.querySelector('.add-item-btn');
        
        if (!container || !addButton) {
            return;
        }

        let itemIndex = container.querySelectorAll('.demanda-item').length;

        addButton.addEventListener('click', () => {
            addItem(container, itemIndex);
            itemIndex++;
        });

        container.addEventListener('click', (e) => {
            if (e.target.classList.contains('remove-item-btn')) {
                e.target.closest('.demanda-item').remove();
            }
        });

        container.addEventListener('input', (e) => {
             if (e.target.classList.contains('numeric-mask')) {
                applyMask(e.target);
            }
        });
        
        container.querySelectorAll('.numeric-mask').forEach(input => {
            applyMask(input);
        });

        if (form.id === 'form-nova-demanda' && itemIndex === 0) {
            addItem(container, 0);
            itemIndex = 1;
        }
    });
});