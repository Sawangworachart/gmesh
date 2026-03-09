// =========================================
// JS สำหรับหน้า Dashboard (Admin)
// =========================================

document.addEventListener('DOMContentLoaded', function() {
    // 1. เริ่มต้น Animation ตัวเลข (Counter Animation)
    animateCounters();

    // 2. ตรวจสอบว่ามีข้อมูลสำหรับกราฟหรือไม่
    if (typeof window.dashboardData !== 'undefined') {
        initCharts(window.dashboardData);
    }
});

/**
 * ฟังก์ชันสร้างกราฟทั้งหมด (Charts Initialization)
 * @param {Object} data - ข้อมูลที่ส่งมาจาก PHP
 */
function initCharts(data) {
    // --- 1. กราฟ PM Status (Doughnut) ---
    const ctxPM = document.getElementById('pmStatusChart');
    if (ctxPM) {
        const pmChart = new Chart(ctxPM.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: data.pm.labels,
                datasets: [{
                    data: data.pm.data,
                    backgroundColor: ['#8b5cf6', '#f59e0b', '#10b981', '#455a64'],
                    borderWidth: 0
                }]
            },
            options: getChartOptions('doughnut')
        });

        // Event Listener สำหรับเปลี่ยนปี
        document.getElementById('yearPmSelect').addEventListener('change', e => {
            loadChart('pm', e.target.value, pmChart);
        });
    }

    // --- 2. กราฟ Service Status (Pie) ---
    const ctxService = document.getElementById('serviceStatusChart');
    if (ctxService) {
        const serviceChart = new Chart(ctxService.getContext('2d'), {
            type: 'pie',
            data: {
                labels: data.service.labels,
                datasets: [{
                    data: data.service.data,
                    backgroundColor: ['#2ecc71', '#3498db', '#9b59b6', '#f1c40f'],
                    borderWidth: 0
                }]
            },
            options: getChartOptions('pie')
        });

        document.getElementById('yearServiceSelect').addEventListener('change', e => {
            loadChart('service', e.target.value, serviceChart);
        });
    }

    // --- 3. กราฟ Product Status (Doughnut) ---
    const ctxProduct = document.getElementById('productStatusChart');
    if (ctxProduct) {
        const productChart = new Chart(ctxProduct.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: data.product.labels,
                datasets: [{
                    data: data.product.data,
                    backgroundColor: ['#e74c3c', '#34495e', '#cddc39', '#95a5a6'],
                    borderWidth: 0
                }]
            },
            options: getChartOptions('doughnut')
        });

        document.getElementById('yearProductSelect').addEventListener('change', e => {
            loadChart('product', e.target.value, productChart);
        });
    }
}

/**
 * คืนค่า Option ของกราฟเพื่อให้ตั้งค่าที่เดียว
 */
function getChartOptions(type) {
    return {
        responsive: true,
        maintainAspectRatio: false,
        cutout: type === 'doughnut' ? '70%' : undefined,
        plugins: {
            legend: {
                position: 'right',
                labels: {
                    usePointStyle: true,
                    padding: 20,
                    font: { size: 16, weight: '400' },
                    generateLabels: (chart) => {
                        const data = chart.data;
                        if (data.labels.length && data.datasets.length) {
                            return data.labels.map((label, i) => ({
                                text: `${label}: ${data.datasets[0].data[i]}`,
                                fillStyle: data.datasets[0].backgroundColor[i],
                                strokeStyle: 'transparent',
                                pointStyle: 'circle',
                                index: i
                            }));
                        }
                        return [];
                    }
                }
            }
        }
    };
}

/**
 * โหลดข้อมูลกราฟใหม่เมื่อเปลี่ยนปี (AJAX)
 */
async function loadChart(type, year, chart) {
    try {
        const res = await fetch(`includes/dashboard_chart_data.php?type=${type}&year=${year}`);
        const data = await res.json();
        chart.data.labels = data.labels;
        chart.data.datasets[0].data = data.data;
        chart.update();
    } catch (error) {
        console.error('Error loading chart data:', error);
    }
}

/**
 * เอฟเฟกต์นับเลข (Counter Animation)
 */
function animateCounters() {
    $('.stat-val').each(function() {
        const $this = $(this);
        const countTo = parseInt($this.attr('data-count')) || 0;
        
        $({ countNum: 0 }).animate({
            countNum: countTo
        }, {
            duration: 1500,
            easing: 'swing',
            step: function() {
                $this.text(Math.floor(this.countNum).toLocaleString());
            },
            complete: function() {
                $this.text(this.countNum.toLocaleString());
            }
        });
    });
}
