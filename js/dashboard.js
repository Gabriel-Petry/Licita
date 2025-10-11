document.addEventListener('DOMContentLoaded', () => {
    const yearSelect = document.getElementById('year-filter');

    const fetchDataAndRender = () => {
        if (!yearSelect) return;
        
        const selectedYear = yearSelect.value;
        const apiUrl = `/api_dashboard.php?year=${selectedYear}`;

        document.querySelector('.kpi-container').style.opacity = '0.5';
        document.querySelector('.dashboard-grid-container').style.opacity = '0.5';

        fetch(apiUrl)
            .then(res => {
                if (!res.ok) throw new Error('Falha na resposta da rede');
                return res.json();
            })
            .then(data => {
                if (data.error) throw new Error(data.error);
                
                document.getElementById('kpiLicitacoesAno').textContent = data.kpis.gerais.licitacoes_ano;
                document.getElementById('kpiDiretasAno').textContent = data.kpis.gerais.diretas_ano;
                document.getElementById('kpiHomologadasAno').textContent = data.kpis.gerais.homologadas_ano;
                document.getElementById('kpiFracassadasDesertasAno').textContent = data.kpis.gerais.fracassadas_desertas_ano;

                document.getElementById('kpiValorEstimadoAno').textContent = 'R$ ' + (data.kpis.financeiros.valor_estimado_ano/1).toLocaleString('pt-BR', {minimumFractionDigits:2});
                document.getElementById('kpiValorAdjudicadoAno').textContent = 'R$ ' + (data.kpis.financeiros.valor_adjudicado_ano/1).toLocaleString('pt-BR', {minimumFractionDigits:2});
                document.getElementById('kpiEconomiaAno').textContent = 'R$ ' + (data.kpis.financeiros.economia_ano/1).toLocaleString('pt-BR', {minimumFractionDigits:2});
                document.getElementById('kpiValorDiretasAno').textContent = 'R$ ' + (data.kpis.financeiros.valor_total_diretas_ano/1).toLocaleString('pt-BR', {minimumFractionDigits:2});
                
                renderDashboardCharts(data);

                document.querySelector('.kpi-container').style.opacity = '1';
                document.querySelector('.dashboard-grid-container').style.opacity = '1';
            })
            .catch(err => {
                console.error('Erro ao buscar dados do dashboard:', err);
                const container = document.querySelector('.dashboard-grid-container');
                if(container) container.innerHTML = '<p class="chip error">Não foi possível carregar os dados do dashboard.</p>';
                document.querySelector('.kpi-container').style.opacity = '1';
                document.querySelector('.dashboard-grid-container').style.opacity = '1';
            });
    };

    if (yearSelect) {
        yearSelect.addEventListener('change', fetchDataAndRender);
    }

    fetchDataAndRender();

    function renderDashboardCharts(data) {
        if (!data) { console.error("Dados para os gráficos não foram fornecidos."); return; }
        
        const chartColors = ['#7c3aed', '#4f46e5', '#0ea5e9', '#10b981', '#f59e0b', '#ef4444', '#64748b'];
        
        const doughnutPieOptions = { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } };
        const barOptions = { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } };
        const horizontalBarOptions = { ...barOptions, indexAxis: 'y' };

        function createOrUpdateChart(chartId, type, chartData, options) {
            const canvas = document.getElementById(chartId);
            if (!canvas) return;
            const existingChart = Chart.getChart(canvas);
            if (existingChart) existingChart.destroy();
            new Chart(canvas, { type, data: chartData, options });
        }

        if (data.status) createOrUpdateChart('chartStatus', 'doughnut', { labels: data.status.labels, datasets: [{ data: data.status.series, backgroundColor: chartColors }] }, doughnutPieOptions);
        if (data.contagem_por_modalidade) createOrUpdateChart('chartContagemModalidade', 'pie', { labels: data.contagem_por_modalidade.labels, datasets: [{ data: data.contagem_por_modalidade.series, backgroundColor: chartColors }] }, doughnutPieOptions);
        if (data.gasto_por_orgao) createOrUpdateChart('chartGastoOrgao', 'bar', { labels: data.gasto_por_orgao.labels, datasets: [{ label: 'Valor Adjudicado (R$)', data: data.gasto_por_orgao.series, backgroundColor: 'rgba(34, 197, 94, 0.7)' }] }, barOptions);
        if (data.contagem_por_agente) createOrUpdateChart('chartAgentes', 'bar', { labels: data.contagem_por_agente.labels, datasets: [{ label: 'Quantidade', data: data.contagem_por_agente.series, backgroundColor: 'rgba(245, 158, 11, 0.7)' }] }, barOptions);
        if (data.contagem_por_responsavel) createOrUpdateChart('chartResponsaveis', 'bar', { labels: data.contagem_por_responsavel.labels, datasets: [{ label: 'Quantidade', data: data.contagem_por_responsavel.series, backgroundColor: 'rgba(239, 68, 68, 0.7)' }] }, barOptions);
        if (data.desempenho_por_orgao) {
            const stackedOptions = { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: true, position: 'top' } }, scales: { x: { stacked: true }, y: { stacked: true, beginAtZero: true } } };
            createOrUpdateChart('chartDesempenhoOrgao', 'bar', { labels: data.desempenho_por_orgao.labels, datasets: data.desempenho_por_orgao.datasets }, stackedOptions);
        }
        if (data.tempo_medio_homologacao) {
            createOrUpdateChart('chartTempoMedio', 'bar', { labels: data.tempo_medio_homologacao.labels, datasets: [{ label: 'Dias', data: data.tempo_medio_homologacao.series, backgroundColor: 'rgba(14, 165, 233, 0.7)' }] }, horizontalBarOptions);
        }
        if (data.mapa_calor) {
            const heatmapOptions = { responsive: true, maintainAspectRatio: false, plugins: { legend: false, tooltip: { callbacks: { title: () => '', label: (c) => `Licitações: ${c.raw.v}` } } }, scales: { y: { type: 'category', labels: data.mapa_calor.labels_orgaos, grid: { display: false } }, x: { type: 'category', labels: data.mapa_calor.labels_meses, grid: { display: false } } } };
            createOrUpdateChart('chartMapaCalor', 'matrix', { datasets: [{ data: data.mapa_calor.series, backgroundColor: (c) => { if (!c.raw) return 'rgba(0,0,0,0.1)'; const alpha = c.raw.v > 0 ? 0.2 + (c.raw.v / 10) : 0.1; return `rgba(34, 197, 94, ${alpha})`; }, borderColor: 'rgba(0,0,0,0.5)', borderWidth: 1, width: ({chart}) => (chart.chartArea || {}).width / data.mapa_calor.labels_meses.length - 1, height: ({chart}) => (chart.chartArea || {}).height / data.mapa_calor.labels_orgaos.length - 1 }] }, heatmapOptions);
        }
    }

    const tooltip = document.getElementById('calendar-tooltip');
    if (tooltip) {
        document.querySelectorAll('.calendario td[data-tooltip]').forEach(day => {
            day.addEventListener('mousemove', (e) => {
                tooltip.innerHTML = day.getAttribute('data-tooltip');
                tooltip.style.display = 'block';
                let top = e.clientY + 15;
                let left = e.clientX + 15;
                if (left + tooltip.offsetWidth > window.innerWidth) {
                    left = e.clientX - tooltip.offsetWidth - 15;
                }
                tooltip.style.left = left + 'px';
                tooltip.style.top = top + 'px';
            });
            day.addEventListener('mouseleave', () => {
                tooltip.style.display = 'none';
            });
        });
    }
    const eventPopup = document.getElementById('calendar-event-popup');
    if (eventPopup) {
        const popupTitle = document.getElementById('popup-title');
        const popupContent = document.getElementById('popup-content');
        const calendarTitle = document.querySelector('.calendario-header h3');
        document.querySelectorAll('.calendario td.evento').forEach(day => {
            day.addEventListener('click', () => {
                const dayNumber = day.dataset.day;
                const eventsHtml = day.getAttribute('data-tooltip');
                const monthYear = calendarTitle.textContent;
                popupTitle.textContent = `Licitações - ${dayNumber} de ${monthYear}`;
                popupContent.innerHTML = eventsHtml;
                window.location.hash = 'calendar-event-popup';
            });
        });
    }
});