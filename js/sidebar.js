// js/sidebar.js

/**
 * Sidebar Toggling System
 */
(function() {
    // Initial State Check
    const isCollapsed = localStorage.getItem('sidebar-collapsed') === 'true';
    if (isCollapsed && window.innerWidth > 992) {
        document.body.classList.add('sidebar-collapsed');
    }

    window.toggleSidebar = function() {
        if (window.innerWidth > 992) {
            const isCollapsed = document.body.classList.toggle('sidebar-collapsed');
            localStorage.setItem('sidebar-collapsed', isCollapsed);
        } else {
            document.body.classList.toggle('sidebar-mobile-open');
            // Overlay is handled by CSS
        }
    };

    // Close mobile sidebar on overlay click
    $(document).on('click', '.sidebar-overlay', function() {
        document.body.classList.remove('sidebar-mobile-open');
    });

    // Close mobile sidebar on window resize
    $(window).on('resize', function() {
        if (window.innerWidth > 992) {
            document.body.classList.remove('sidebar-mobile-open');
        }
    });
})();

/**
 * Notification System (SweetAlert2)
 */
function showNotificationToast(data) {
    if (typeof Swal === 'undefined') return;

    let htmlContent = '';
    
    if (data.today > 0) {
        htmlContent += `
        <div class="notify-row">
            <div class="notify-badge badge-today">
                <i class="fas fa-calendar-check"></i> วันนี้
            </div>
            <div class="notify-count" style="color: #f59e0b;">${data.today} <span>งาน</span></div>
        </div>`;
    }

    if (data.future > 0) {
        htmlContent += `
        <div class="notify-row">
            <div class="notify-badge badge-soon">
                <i class="fas fa-hourglass-half"></i> เร็วๆ นี้ (7 วัน)
            </div>
            <div class="notify-count" style="color: #22c55e;">${data.future} <span>งาน</span></div>
        </div>`;
    }

    if (data.overdue > 0) {
        htmlContent += `
        <div class="notify-row">
            <div class="notify-badge badge-overdue">
                <i class="fas fa-exclamation-circle"></i> เกินกำหนด
            </div>
            <div class="notify-count" style="color: #ef4444;">${data.overdue} <span>งาน</span></div>
        </div>`;
    }

    if (!htmlContent) return;

    const Toast = Swal.mixin({
        toast: true,
        position: 'bottom-end',
        showConfirmButton: false,
        timer: 8000,
        timerProgressBar: true,
        customClass: {
            popup: 'notify-toast'
        },
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer)
            toast.addEventListener('mouseleave', Swal.resumeTimer)
            toast.addEventListener('click', () => {
                window.location.href = data.redirectUrl || 'warn_admin.php';
            })
        }
    });

    Toast.fire({
        html: `
        <div class="notify-header">
            <i class="fas fa-bell ${data.overdue > 0 ? 'text-danger' : 'text-warning'}"></i>
            <span>แจ้งเตือนงานบำรุงรักษา</span>
        </div>
        <div class="notify-body">
            ${htmlContent}
        </div>`
    });
}
