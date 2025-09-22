function createSearchableSelect(originalSelect) {
    originalSelect.style.display = 'none';

    const container = document.createElement('div');
    container.className = 'searchable-select-container';

    const input = document.createElement('input');
    input.type = 'text';
    input.className = 'searchable-select-input';
    input.placeholder = 'Digite para buscar...';

    const dropdown = document.createElement('div');
    dropdown.className = 'searchable-select-dropdown';

    Array.from(originalSelect.options).forEach(option => {
        if (!option.value) return;
        
        const optionDiv = document.createElement('div');
        optionDiv.className = 'searchable-select-option';
        optionDiv.textContent = option.textContent;
        optionDiv.dataset.value = option.value;
        optionDiv.dataset.fullText = option.dataset.fullText || option.textContent;
        dropdown.appendChild(optionDiv);

        if (option.selected) {
            input.value = option.dataset.fullText || option.textContent;
        }
    });
    
    const moreIndicator = document.createElement('div');
    moreIndicator.className = 'searchable-select-option';
    moreIndicator.textContent = '...';
    moreIndicator.style.textAlign = 'center';
    moreIndicator.style.cursor = 'default';
    moreIndicator.style.display = 'none'; 
    dropdown.appendChild(moreIndicator);

    container.appendChild(input);
    container.appendChild(dropdown);
    originalSelect.parentNode.insertBefore(container, originalSelect.nextSibling);

    function updateDropdownView() {
        const filter = input.value.toLowerCase();
        const allOptions = dropdown.querySelectorAll('.searchable-select-option:not(:last-child)');
        
        const filteredOptions = Array.from(allOptions).filter(optionDiv => {
            const text = optionDiv.dataset.fullText.toLowerCase();
            return text.includes(filter);
        });

        allOptions.forEach(option => option.style.display = 'none');

        filteredOptions.slice(0, 2).forEach(option => {
            option.style.display = 'block';
        });

        if (filteredOptions.length > 2) {
            moreIndicator.style.display = 'block';
        } else {
            moreIndicator.style.display = 'none';
        }
    }

    input.addEventListener('click', () => {
        updateDropdownView();
        dropdown.style.display = 'block';
        input.select();
    });

    input.addEventListener('input', updateDropdownView);

    dropdown.addEventListener('click', (e) => {
        if (e.target.classList.contains('searchable-select-option') && e.target !== moreIndicator) {
            const selectedValue = e.target.dataset.value;
            const selectedText = e.target.dataset.fullText;

            input.value = selectedText;
            originalSelect.value = selectedValue;
            originalSelect.dispatchEvent(new Event('change', { 'bubbles': true }));

            dropdown.style.display = 'none';
        }
    });

    document.addEventListener('click', (e) => {
        if (!container.contains(e.target)) {
            dropdown.style.display = 'none';
        }
    });
}