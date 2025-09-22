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

document.addEventListener('DOMContentLoaded', () => {
  const tooltip = document.getElementById('calendar-tooltip');
  const calendarDaysWithEvents = document.querySelectorAll('.calendario td[data-tooltip]');

  calendarDaysWithEvents.forEach(day => {
    day.addEventListener('mousemove', (e) => {
      tooltip.innerHTML = day.getAttribute('data-tooltip');
      tooltip.classList.add('visible');

      let top = e.pageY + 15;
      let left = e.pageX + 15;

      if (left + tooltip.offsetWidth > window.innerWidth) {
        left = e.pageX - tooltip.offsetWidth - 15;
      }
      if (top + tooltip.offsetHeight > window.innerHeight) {
        top = e.pageY - tooltip.offsetHeight - 15;
      }

      tooltip.style.left = left + 'px';
      tooltip.style.top = top + 'px';
    });

    day.addEventListener('mouseleave', () => {
      tooltip.classList.remove('visible');
    });
  });
});