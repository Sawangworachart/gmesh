
        let charts = {};
        const commonOptions = {
            responsive: true,
            maintainAspectRatio: false,
            animation: {
        duration: 800,
        easing: 'easeOutQuart'
    },
            plugins: {
                legend: { 
                    position: 'right', 
                    labels: { 
                        padding: 15, 
                        font: { size: 13, family: 'Sarabun', weight: '600' }, 
                        usePointStyle: true,
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
            },
            cutout: '62%'
        };

        const createChart = (id, labels, data, colors, type='doughnut') => {
            const ctx = document.getElementById(id).getContext('2d');
            return new Chart(ctx, {
                type: type,
                data: {
                    labels: labels,
                    datasets: [{ data: data, backgroundColor: colors, borderWidth: 0 }]
                },
                options: commonOptions
            });
        };

        document.addEventListener('DOMContentLoaded', function() {
            updateChart('pm', '');
            updateChart('service', '');
            updateChart('product', '');
        });

        function updateChart(type, year) {
            let chartId = type + 'StatusChart';
            let colors = [];
            let chartType = (type === 'service' || type === 'product') ? 'pie' : 'doughnut';
            
            if(type === 'pm') colors = ['#8b5cf6', '#f59e0b', '#10b981', '#455a64'];
            else if(type === 'service') colors = ['#10b981', '#3b82f6', '#8b5cf6'];
            else if(type === 'product') colors = ['#e74c3c', '#34495e', '#cddc39', '#95a5a6'];

            fetch(`user_dashboard.php?ajax_action=get_chart_data&type=${type}&year=${year}`)
                .then(response => response.json())
                .then(data => {
                    const canvas = document.getElementById(chartId);
                    const container = canvas.parentElement;
                    const hasData = data.data && data.data.length > 0 && data.data.some(val => val > 0);
                    const existingMsg = container.querySelector('.no-data-msg');
                    if (existingMsg) existingMsg.remove();

                    if (!hasData) {
                        canvas.style.display = 'none';
                        if (charts[type]) charts[type].destroy();
                        charts[type] = null;
                        const msgDiv = document.createElement('div');
                        msgDiv.className = 'no-data-msg';
                        msgDiv.innerHTML = `<div style="display:flex; flex-direction:column; align-items:center; justify-content:center; height:100%; color:#94a3b8;"><i class="fas fa-folder-open fa-2x" style="opacity:0.3; margin-bottom:10px;"></i><span style="font-size:1rem;">ไม่พบข้อมูลในปีนี้</span></div>`;
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
                });
        }
