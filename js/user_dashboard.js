// =========================================
// JS สำหรับหน้า Dashboard User - user_dashboard.js
// =========================================

let charts = {};
const commonOptions = {
    responsive: true,
    maintainAspectRatio: false,
    animation: {
        duration: 1000,
        easing: 'easeOutQuart'
    },
    plugins: {
        legend: {
            position: 'right',
            labels: {
                padding: 20,
                font: { size: 12, family: 'Sarabun', weight: '600' },
                usePointStyle: true,
                pointStyle: 'circle'
            }
        },
        tooltip: {
            backgroundColor: 'rgba(30, 41, 59, 0.9)',
            padding: 12,
            titleFont: { family: 'Sarabun', size: 14 },
            bodyFont: { family: 'Sarabun', size: 13 },
            cornerRadius: 8,
            displayColors: true
        }
    },
    cutout: '70%'
};

const createChart = (id, labels, data, colors, type = 'doughnut') => {
    const ctx = document.getElementById(id).getContext('2d');
    return new Chart(ctx, {
        type: type,
        data: {
            labels: labels,
            datasets: [{
                data: data,
                backgroundColor: colors,
                borderWidth: 0,
                hoverOffset: 10
            }]
        },
        options: commonOptions
    });
};

document.addEventListener('DOMContentLoaded', function() {
    // Mouse Glow Effect
    const body = document.querySelector('body');
    document.addEventListener('mousemove', (e) => {
        body.style.setProperty('--x', e.clientX + 'px');
        body.style.setProperty('--y', e.clientY + 'px');
    });

    updateChart('pm', '');
    updateChart('service', '');
    updateChart('product', '');
});

function updateChart(type, year) {
    let chartId = type + 'StatusChart';
    let colors = [];
    let chartType = (type === 'service' || type === 'product') ? 'doughnut' : 'doughnut';

    if (type === 'pm') colors = ['#8b5cf6', '#f59e0b', '#10b981', '#455a64']; // Purple, Orange, Green, Grey
    else if (type === 'service') colors = ['#10b981', '#3b82f6', '#8b5cf6']; // Green, Blue, Purple
    else if (type === 'product') colors = ['#ef4444', '#3b82f6', '#f59e0b', '#10b981']; // Red, Blue, Orange, Green

    const canvas = document.getElementById(chartId);
    const container = canvas.parentElement;
    
    // Show loading state if needed (optional)
    
    fetch(`user_dashboard.php?ajax_action=get_chart_data&type=${type}&year=${year}`)
        .then(response => response.json())
        .then(data => {
            const hasData = data.data && data.data.length > 0 && data.data.some(val => val > 0);
            const existingMsg = container.querySelector('.no-data-msg');
            if (existingMsg) existingMsg.remove();

            if (!hasData) {
                canvas.style.display = 'none';
                if (charts[type]) {
                    charts[type].destroy();
                    charts[type] = null;
                }
                
                const msgDiv = document.createElement('div');
                msgDiv.className = 'no-data-msg';
                msgDiv.innerHTML = `
                    <div style="display:flex; flex-direction:column; align-items:center; justify-content:center; height:100%; color:#94a3b8;">
                        <i class="fas fa-chart-pie fa-3x" style="opacity:0.2; margin-bottom:15px;"></i>
                        <span style="font-size:1rem; font-weight:500;">ไม่พบข้อมูลในปีนี้</span>
                    </div>`;
                msgDiv.style.cssText = "position:absolute; inset:0; display:flex; align-items:center; justify-content:center;";
                container.appendChild(msgDiv);
            } else {
                canvas.style.display = 'block';
                if (charts[type]) {
                    charts[type].data.labels = data.labels;
                    charts[type].data.datasets[0].data = data.data;
                    charts[type].update();
                } else {
                    charts[type] = createChart(chartId, data.labels, data.data, colors, chartType);
                }
            }
        })
        .catch(err => console.error('Chart Error:', err));
}
