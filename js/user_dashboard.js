
// js/user_dashboard.js

document.addEventListener('DOMContentLoaded', function () {

    const chartConfig = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false // We'll create a custom legend if needed
            }
        }
    };

    // PM Status Chart
    const pmCtx = document.getElementById('pmStatusChart')?.getContext('2d');
    const pmChart = pmCtx ? new Chart(pmCtx, {
        type: 'doughnut',
        data: { labels: [], datasets: [{ data: [], backgroundColor: ['#8b5cf6', '#f59e0b', '#10b981'] }] },
        options: { ...chartConfig, cutout: '70%' }
    }) : null;

    // Service Status Chart
    const serviceCtx = document.getElementById('serviceStatusChart')?.getContext('2d');
    const serviceChart = serviceCtx ? new Chart(serviceCtx, {
        type: 'pie',
        data: { labels: [], datasets: [{ data: [], backgroundColor: ['#2ecc71', '#3498db', '#9b59b6'] }] },
        options: chartConfig
    }) : null;

    // Product Status Chart
    const productCtx = document.getElementById('productStatusChart')?.getContext('2d');
    const productChart = productCtx ? new Chart(productCtx, {
        type: 'doughnut',
        data: { labels: [], datasets: [{ data: [], backgroundColor: ['#e74c3c', '#34495e', '#cddc39', '#95a5a6'] }] },
        options: { ...chartConfig, cutout: '70%' }
    }) : null;

    window.updateChart = async (type, year) => {
        let chart;
        if (type === 'pm') chart = pmChart;
        else if (type === 'service') chart = serviceChart;
        else if (type === 'product') chart = productChart;

        if (chart) {
            try {
                const response = await fetch(`user_dashboard.php?ajax_action=get_chart_data&type=${type}&year=${year}`);
                const data = await response.json();
                chart.data.labels = data.labels;
                chart.data.datasets[0].data = data.data;
                chart.update();
            } catch (error) {
                console.error('Failed to update chart:', error);
            }
        }
    };

    // Initial chart load
    updateChart('pm', '');
    updateChart('service', '');
    updateChart('product', '');

});
