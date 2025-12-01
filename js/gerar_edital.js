document.addEventListener("DOMContentLoaded", function() {
    var radioDivulgado = document.getElementById('radio_v_divulgado');
    var radioSigiloso = document.getElementById('radio_v_sigiloso');
    var containerValor = document.getElementById('container_valor_estimado');
    var inputValor = document.getElementById('input_valor_estimado');

    function atualizarVisibilidadeValor() {
        if (radioSigiloso && radioSigiloso.checked) {
            if(containerValor) containerValor.style.display = 'none';
            if(inputValor) {
                inputValor.value = ''; 
                inputValor.required = false;
            }
        } else {
            if(containerValor) containerValor.style.display = 'block';
            if(inputValor) inputValor.required = true;
        }
    }

    function formatarMoeda(e) {
        var valor = e.target.value;
        
        valor = valor.replace(/\D/g, "");
        
        if (valor === "") {
            e.target.value = "";
            return;
        }
        
        valor = (parseInt(valor) / 100).toFixed(2) + "";
        valor = valor.replace(".", ",");
        valor = valor.replace(/(\d)(?=(\d{3})+(?!\d))/g, "$1.");
        
        e.target.value = valor;
    }

    if (radioDivulgado) {
        radioDivulgado.addEventListener('change', atualizarVisibilidadeValor);
    }
    
    if (radioSigiloso) {
        radioSigiloso.addEventListener('change', atualizarVisibilidadeValor);
    }

    if (inputValor) {
        inputValor.addEventListener('keyup', formatarMoeda);
        inputValor.addEventListener('input', formatarMoeda);
    }

    atualizarVisibilidadeValor();
});