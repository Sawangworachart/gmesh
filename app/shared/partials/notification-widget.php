<?php
// notification_widget.php
// Modern Toast Stack (Bottom-Right)
// Features: Multiple Notifications, Slide Up Stack, LocalStorage Memory

if (!isset($conn)) { return; }

// 1. ดึงข้อมูลงาน "วันนี้" (CURDATE) ทั้งหมด (ตัด LIMIT 1 ออก)
$sql_notify = "SELECT m.ma_id, m.ma_date, m.note, 
               p.project_name, p.responsible_person, 
               c.customers_name
               FROM ma_schedule m 
               JOIN pm_project p ON m.pmproject_id = p.pmproject_id 
               LEFT JOIN customers c ON p.customers_id = c.customers_id
               WHERE m.ma_date = CURDATE() 
               ORDER BY m.ma_id DESC"; // เรียงจากล่าสุด

$query_notify = mysqli_query($conn, $sql_notify);

// เก็บข้อมูลลง Array ก่อน
$notify_list = [];
if ($query_notify) {
    while ($row = mysqli_fetch_assoc($query_notify)) {
        $notify_list[] = $row;
    }
}

// ถ้าไม่มีงานวันนี้ จบการทำงาน
if (empty($notify_list)) return;
?>

<style>
    /* Container มุมขวาล่าง (Right: 24px) */
    /* ใช้ flex-direction: column-reverse เพื่อให้รายการใหม่ดันขึ้นข้างบน */
    #ma-toast-container {
        position: fixed;
        bottom: 24px;
        right: 24px;
        z-index: 10000;
        font-family: 'Sarabun', sans-serif;
        display: flex;
        flex-direction: column-reverse; 
        gap: 12px; /* ระยะห่างระหว่างการ์ด */
        pointer-events: none; /* ให้คลิกทะลุพื้นที่ว่างได้ */
    }

    /* ตัวการ์ดแจ้งเตือน (Toast) */
    .ma-toast {
        background: #ffffff;
        width: 320px;
        padding: 14px;
        border-radius: 12px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1), 0 2px 5px rgba(0,0,0,0.05);
        display: flex; /* เริ่มต้นเป็น flex แต่ซ่อนด้วย opacity/transform */
        align-items: center;
        gap: 12px;
        cursor: pointer;
        transition: all 0.4s cubic-bezier(0.25, 1, 0.5, 1);
        border-left: 5px solid #5599ff;
        pointer-events: auto; /* ให้คลิกที่การ์ดได้ */
        
        /* Animation เริ่มต้น: ซ่อนและเลื่อนลงต่ำ */
        opacity: 0;
        transform: translateX(50px) scale(0.9);
        height: 0;
        padding-top: 0;
        padding-bottom: 0;
        margin: 0;
        overflow: hidden;
    }

    /* เมื่อแสดงผล */
    .ma-toast.show {
        opacity: 1;
        transform: translateX(0) scale(1);
        height: auto; /* ปล่อยให้สูงตามเนื้อหา */
        padding: 14px;
        overflow: visible;
    }

    .ma-toast:hover {
        transform: translateX(-5px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
    }

    /* Icon ด้านซ้าย */
    .ma-toast-icon {
        width: 40px;
        height: 40px;
        background: #eef2ff;
        color: #5599ff;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        font-size: 1.1rem;
    }

    /* Content ตรงกลาง */
    .ma-toast-content {
        flex-grow: 1;
        overflow: hidden;
    }

    .ma-toast-title {
        font-size: 0.9rem;
        font-weight: 700;
        color: #2d3436;
        margin-bottom: 2px;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    
    .ma-toast-badge {
        font-size: 0.6rem;
        background: #ff4757;
        color: white;
        padding: 2px 6px;
        border-radius: 8px;
        font-weight: 600;
    }

    .ma-toast-desc {
        font-size: 0.8rem;
        color: #636e72;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .ma-toast-sub {
        font-size: 0.75rem;
        color: #a4b0be;
        margin-top: 2px;
    }

    /* ปุ่มปิด */
    .ma-toast-close {
        color: #dfe6e9;
        background: transparent;
        border: none;
        font-size: 1rem;
        cursor: pointer;
        padding: 0 0 0 8px;
        transition: color 0.2s;
    }
    .ma-toast-close:hover { color: #ff4757; }

</style>

<div id="ma-toast-container">
    <?php foreach($notify_list as $index => $item): 
        $ma_id = $item['ma_id'];
        $project_name = htmlspecialchars($item['project_name']);
        $customer_name = htmlspecialchars($item['customers_name']);
    ?>
    
    <div class="ma-toast" id="toast-<?php echo $ma_id; ?>" data-id="<?php echo $ma_id; ?>" onclick="handleToastClick(event, <?php echo $ma_id; ?>)">
        <div class="ma-toast-icon">
            <i class="fas fa-bell"></i>
        </div>
        <div class="ma-toast-content">
            <div class="ma-toast-title">
                ถึงกำหนด MA
                <span class="ma-toast-badge">Today</span>
            </div>
            <div class="ma-toast-desc" title="<?php echo $project_name; ?>">
                <?php echo $project_name; ?>
            </div>
            <div class="ma-toast-sub">
                <i class="fas fa-building me-1"></i> <?php echo $customer_name; ?>
            </div>
        </div>
        <button class="ma-toast-close" onclick="closeToast(event, <?php echo $ma_id; ?>)">
            <i class="fas fa-times"></i>
        </button>
    </div>

    <?php endforeach; ?>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        // วนลูปเช็ค Toast ทุกตัวที่เรนเดอร์ออกมา
        const toasts = document.querySelectorAll('.ma-toast');
        
        toasts.forEach((toast, index) => {
            const id = toast.getAttribute('data-id');
            const storageKey = 'ma_notification_closed_' + id;

            // ถ้ายังไม่เคยปิด -> ให้แสดงผล
            if (!localStorage.getItem(storageKey)) {
                // ตั้งเวลาให้เด้งขึ้นมาทีละอัน (Stagger Animation)
                setTimeout(() => {
                    toast.classList.add('show');
                }, 100 + (index * 200)); // ดีเลย์เพิ่มขึ้นทีละ 200ms
            }
        });
    });

    // ฟังก์ชันปิด (แยกราย ID)
    function closeToast(e, id) {
        e.stopPropagation();
        
        const toast = document.getElementById('toast-' + id);
        const storageKey = 'ma_notification_closed_' + id;

        if(toast) {
            // Animation ปิด
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(50px) scale(0.9)';
            toast.style.height = '0';
            toast.style.padding = '0';
            toast.style.margin = '0';

            // บันทึกว่าปิดแล้ว
            localStorage.setItem(storageKey, 'true');

            // ลบออกจาก DOM เพื่อความสะอาด (หลังจาก Animation จบ)
            setTimeout(() => {
                toast.remove();
            }, 500);
        }
    }

    // ฟังก์ชันคลิกดูรายละเอียด
    function handleToastClick(e, id) {
        if (typeof viewProject === "function") {
            viewProject(id);
        } else {
            window.location.href = 'warn_admin.php?focus_ma=' + id;
        }
    }
</script>