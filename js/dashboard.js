
// js/dashboard.js

document.addEventListener('DOMContentLoaded', function () {

    // 1. Animate Counters
    function animateCounters() {
        const counters = document.querySelectorAll('.stat-val');
        counters.forEach(counter => {
            const target = +counter.getAttribute('data-count');
            counter.innerText = '0';

            const updateCounter = () => {
                const value = +counter.innerText;
                const increment = target / 200; // Speed of animation

                if (value < target) {
                    counter.innerText = `${Math.ceil(value + increment)}`;
                    setTimeout(updateCounter, 1);
                } else {
                    counter.innerText = target.toLocaleString();
                }
            };
            updateCounter();
        });
    }

    // 2. Chart Configurations
    const chartOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'right',
                labels: {
                    usePointStyle: true,
                    padding: 20,
                    font: {
                        size: 14,
                        family: 'Sarabun'
                    },
                    generateLabels: (chart) => {
                        const data = chart.data;
                        return data.labels.map((label, i) => ({
                            text: `${label}: ${data.datasets[0].data[i]}`,
                            fillStyle: data.datasets[0].backgroundColor[i],
                            strokeStyle: 'transparent',
                            pointStyle: 'circle',
                            index: i
                        }));
                    }
                }
            }
        }
    };

    // 3. Initialize Charts
    const pmCtx = document.getElementById('pmStatusChart')?.getContext('2d');
    let pmChart;
    if (pmCtx) {
        pmChart = new Chart(pmCtx, {
            type: 'doughnut',
            data: {
                labels: [], // Will be loaded via fetch
                datasets: [{
                    data: [],
                    backgroundColor: ['#8b5cf6', '#f59e0b', '#10b981', '#455a64'],
                    borderWidth: 0
                }]
            },
            options: { ...chartOptions, cutout: '70%' }
        });
    }

    const serviceCtx = document.getElementById('serviceStatusChart')?.getContext('2d');
    let serviceChart;
    if (serviceCtx) {
        serviceChart = new Chart(serviceCtx, {
            type: 'pie',
            data: {
                labels: [],
                datasets: [{
                    data: [],
                    backgroundColor: ['#2ecc71', '#3498db', '#9b59b6', '#f1c40f'],
                    borderWidth: 0
                }]
            },
            options: chartOptions
        });
    }

    const productCtx = document.getElementById('productStatusChart')?.getContext('2d');
    let productChart;
    if (productCtx) {
        productChart = new Chart(productCtx, {
            type: 'doughnut',
            data: {
                labels: [],
                datasets: [{
                    data: [],
                    backgroundColor: ['#e74c3c', '#34495e', '#cddc39', '#95a5a6'],
                    borderWidth: 0
                }]
            },
            options: { ...chartOptions, cutout: '70%' }
        });
    }

    // 4. Fetch and Load Chart Data
    async function loadChartData(type, year, chart) {
        if (!chart) return;
        try {
            const response = await fetch(`dashboard_chart_data.php?type=${type}&year=${year}`);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const data = await response.json();
            chart.data.labels = data.labels;
            chart.data.datasets[0].data = data.data;
            chart.update();
        } catch (error) {
            console.error(`Failed to load chart data for ${type}:`, error);
        }
    }

    // 5. Initial Load and Event Listeners
    const yearPmSelect = document.getElementById('yearPmSelect');
    const yearServiceSelect = document.getElementById('yearServiceSelect');
    const yearProductSelect = document.getElementById('yearProductSelect');

    loadChartData('pm', yearPmSelect?.value || '', pmChart);
    loadChartData('service', yearServiceSelect?.value || '', serviceChart);
    loadChartData('product', yearProductSelect?.value || '', productChart);

    yearPmSelect?.addEventListener('change', (e) => loadChartData('pm', e.target.value, pmChart));
    yearServiceSelect?.addEventListener('change', (e) => loadChartData('service', e.target.value, serviceChart));
    yearProductSelect?.addEventListener('change', (e) => loadChartData('product', e.target.value, productChart));

    animateCounters();
});
