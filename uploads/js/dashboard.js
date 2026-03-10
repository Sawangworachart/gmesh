// dashboard.js

document.addEventListener('DOMContentLoaded', function() {
    // -------------------------------------------------------
    // 1. Chart Logic (Original)
    // -------------------------------------------------------
    const pmLabels = window.pmLabels || [];
    const pmData = window.pmData || [];

    if (pmLabels.length === 0 || pmData.length === 0) {
        console.warn("No PM status data available for chart.");
        const chartContainer = document.getElementById('pmStatusChart').parentNode;
        if(chartContainer) {
            chartContainer.innerHTML = '<div class="h-100 d-flex justify-content-center align-items-center text-muted">ไม่พบข้อมูลสถานะโครงการ</div>';
        }
    } else {
        const ctxPm = document.getElementById('pmStatusChart').getContext('2d');
        
        const backgroundColors = [
            '#3b82f6', 
            '#10b981', 
            '#f59e0b', 
            '#8b5cf6', 
            '#ef4444', 
            '#64748b', 
        ];

        new Chart(ctxPm, {
            type: 'doughnut', 
            data: {
                labels: pmLabels,
                datasets: [{
                    label: 'จำนวนโครงการ',
                    data: pmData,
                    backgroundColor: backgroundColors.slice(0, pmLabels.length),
                    borderColor: '#fff', 
                    borderWidth: 3, 
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { 
                        display: true,
                        position: 'right', 
                        labels: {
                            color: '#4b5563',
                            font: { family: "'Sarabun', sans-serif", size: 13, weight: '500' }
                        }
                    },
                    tooltip: {
                        backgroundColor: '#fff',
                        titleColor: '#2d3748',
                        bodyColor: '#718096',
                        borderColor: '#e2e8f0',
                        borderWidth: 1,
                        padding: 12,
                        cornerRadius: 8,
                        displayColors: true,
                        callbacks: {
                            label: function(context) {
                                let label = context.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.parsed !== null) {
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const value = context.parsed;
                                    const percentage = ((value / total) * 100).toFixed(1) + '%';
                                    label += value + ' โครงการ (' + percentage + ')';
                                }
                                return label;
                            }
                        }
                    }
                },
                scales: {
                    y: { display: false },
                    x: { display: false }
                },
                animation: {
                    duration: 1000,
                    easing: 'easeOutQuart'
                }
            }
        });
    }

    // -------------------------------------------------------
    // 2. Modal Logic for Project Detail
    // -------------------------------------------------------
    const projectDetailModal = document.getElementById('projectDetailModal');
    if (projectDetailModal) {
        projectDetailModal.addEventListener('show.bs.modal', function (event) {
            // ปุ่มที่กดเพื่อเปิด Modal
            const button = event.relatedTarget;
            
            // ดึงข้อมูลจาก Data Attributes
            const name = button.getAttribute('data-name');
            const customer = button.getAttribute('data-customer');
            const date = button.getAttribute('data-date');
            const status = button.getAttribute('data-status');
            const badgeClass = button.getAttribute('data-badge');

            // อ้างอิง Element ใน Modal
            const modalTitle = projectDetailModal.querySelector('#modalProjectName');
            const modalCustomer = projectDetailModal.querySelector('#modalProjectCustomer');
            const modalDate = projectDetailModal.querySelector('#modalProjectDate');
            const modalStatus = projectDetailModal.querySelector('#modalProjectStatus');

            // ใส่ข้อมูลลงไป
            modalTitle.textContent = name;
            modalCustomer.textContent = customer;
            modalDate.textContent = date;
            
            // จัดการข้อความ Status
            modalStatus.textContent = status;
        });
    }
});