function renderDashboardCharts(data) {
  if (!data) {
    console.error("Dados para os gráficos não foram fornecidos.");
    return;
  }
  
  const chartColors = ['#7c3aed', '#4f46e5', '#0ea5e9', '#10b981', '#f59e0b', '#ef4444'];
  const chartOptions = {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { display: false } }
  };

  if (data.status && document.getElementById('chartStatus')) {
    new Chart(document.getElementById('chartStatus'), {
      type: 'doughnut',
      data: { labels: data.status.labels, datasets: [{ data: data.status.series, backgroundColor: chartColors }] },
      options: chartOptions
    });
  }
  if (data.meses && document.getElementById('chartMeses')) {
    new Chart(document.getElementById('chartMeses'), {
      type: 'bar',
      data: { labels: data.meses.labels, datasets: [{ label: 'Valor Estimado (R$)', data: data.meses.series, backgroundColor: 'rgba(124, 58, 237, 0.7)' }] },
      options: chartOptions
    });
  }
  if (data.valores_por_orgao && document.getElementById('chartValoresOrgao')) {
    new Chart(document.getElementById('chartValoresOrgao'), {
      type: 'bar',
      data: { labels: data.valores_por_orgao.labels, datasets: [{ label: 'Quantidade de Licitações', data: data.valores_por_orgao.series, backgroundColor: 'rgba(14, 165, 233, 0.7)' }] },
      options: { ...chartOptions }
    });
  }
  if (data.contagem_por_modalidade && document.getElementById('chartContagemModalidade')) {
    new Chart(document.getElementById('chartContagemModalidade'), {
      type: 'pie',
      data: { labels: data.contagem_por_modalidade.labels, datasets: [{ data: data.contagem_por_modalidade.series, backgroundColor: chartColors }] },
      options: chartOptions
    });
  }
}

document.addEventListener('input', function(event) {
    if (event.target && event.target.matches('input[name="inicio_vigencia"]')) {

        const campoInicio = event.target;
        const formAtual = campoInicio.closest('form');
        if (!formAtual) return;

        const displayElement = formAtual.querySelector('.texto-validade-calculada');
        if (!displayElement) return;

        if (campoInicio.value) {
            const dataInicio = new Date(campoInicio.value + 'T00:00:00');
            dataInicio.setDate(dataInicio.getDate() + 365);
            
            const dia = String(dataInicio.getDate()).padStart(2, '0');
            const mes = String(dataInicio.getMonth() + 1).padStart(2, '0');
            const ano = dataInicio.getFullYear();

            displayElement.textContent = `${dia}/${mes}/${ano}`;
        } else {
            displayElement.textContent = '--';
        }
    }
});