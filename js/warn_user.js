/**
 * ไฟล์: assets/js/warn_user.js
 * คำอธิบาย: สคริปต์สำหรับหน้า Alarms (User) จัดการปฏิทินและ Modal รายละเอียด
 */

const monthNames = ["มกราคม", "กุมภาพันธ์", "มีนาคม", "เมษายน", "พฤษภาคม", "มิถุนายน", "กรกฎาคม", "สิงหาคม", "กันยายน", "ตุลาคม", "พฤศจิกายน", "ธันวาคม"];
let currDate = new Date();
let currMonth = currDate.getMonth();
let currYear = currDate.getFullYear();

$(document).ready(function() {
    // ฟังก์ชันค้นหาในตาราง
    $("#tableSearchInput").on("keyup", function() {
        var value = $(this).val().toLowerCase();
        $("#notifTableBody tr").filter(function() { 
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1) 
        });
    });

    // ปุ่มเปลี่ยนเดือนในปฏิทิน
    document.getElementById('prevMonth').onclick = () => { 
        currMonth--; 
        if(currMonth < 0) { currMonth = 11; currYear--; } 
        renderCalendar(currMonth, currYear); 
    };
    
    document.getElementById('nextMonth').onclick = () => { 
        currMonth++; 
        if(currMonth > 11) { currMonth = 0; currYear++; } 
        renderCalendar(currMonth, currYear); 
    };
});

/**
 * เปิด Modal ปฏิทิน
 */
function openCalendarModal() { 
    renderCalendar(currMonth, currYear); 
    new bootstrap.Modal(document.getElementById('calendarModal')).show(); 
}

/**
 * วาดปฏิทินตามเดือนและปีที่ระบุ
 */
function renderCalendar(month, year) {
    const firstDay = new Date(year, month, 1).getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    const grid = document.getElementById('calendarDays');
    
    // แสดงชื่อเดือนและปี
    document.getElementById('monthYearLabel').innerText = `${monthNames[month]} ${year + 543}`;
    
    let html = "";
    
    // ช่องว่างก่อนวันที่ 1
    for (let i = 0; i < firstDay; i++) {
        html += `<div></div>`;
    }
    
    // วนลูปสร้างวันที่
    for (let day = 1; day <= daysInMonth; day++) {
        let d = String(day).padStart(2, '0');
        let m = String(month + 1).padStart(2, '0');
        let key = `${year}-${m}-${d}`;
        
        // เช็คว่ามี event หรือไม่
        let eventClass = calendarEvents[key] ? 'has-event-day' : '';
        
        // เช็คว่าเป็นวันนี้หรือไม่
        let isToday = (day === new Date().getDate() && month === new Date().getMonth() && year === new Date().getFullYear());
        let todayClass = isToday ? 'today-day' : '';
        
        html += `<div class="cal-cell ${eventClass} ${todayClass}" onclick="showDayEvents('${key}')">${day}</div>`;
    }
    
    grid.innerHTML = html; 
    
    // แสดงรายการงานของเดือนนี้เป็นค่าเริ่มต้น
    showDayEvents(null);
}

/**
 * แสดงรายการงานใน Panel ด้านขวา
 * @param {string|null} key - วันที่รูปแบบ YYYY-MM-DD หรือ null เพื่อแสดงทั้งหมดในเดือน
 */
function showDayEvents(key) {
    const list = document.getElementById('monthEventList'); 
    let events = [];
    
    if(key && calendarEvents[key]) { 
        // กรณีเลือกวันที่เฉพาะเจาะจง
        events = calendarEvents[key]; 
        document.getElementById('sideTitle').innerText = "งานวันที่ " + key.split('-').reverse().join('/'); 
    } else { 
        // กรณีแสดงทั้งหมดในเดือนปัจจุบัน
        document.getElementById('sideTitle').innerText = "งานประจำเดือนนี้"; 
        
        // กรองเฉพาะ Event ในเดือนที่เลือก
        let monthPrefix = `${currYear}-${String(currMonth+1).padStart(2,'0')}`;
        Object.keys(calendarEvents).forEach(k => { 
            if(k.startsWith(monthPrefix)) { 
                calendarEvents[k].forEach(e => events.push({...e, date: k})); 
            } 
        }); 
        
        // เรียงตามวันที่
        events.sort((a, b) => a.date.localeCompare(b.date));
    }

    // แสดงผล HTML
    if (events.length === 0) { 
        list.innerHTML = `<div class='text-center mt-5'><i class='fas fa-folder-open fa-3x mb-3 opacity-20'></i><p class="text-muted">ไม่มีรายการงาน</p></div>`; 
    } else { 
        list.innerHTML = events.map(e => `
            <div class="event-item-card shadow-sm" onclick="viewProject(${e.id})">
                <div class="fw-bold mb-1" style="color:#333;">${e.title}</div>
                <small class="d-block mb-1 text-muted"><i class="fas fa-user-circle me-1"></i> ${e.customer}</small>
                ${e.date ? `<small class='text-primary fw-bold'><i class='far fa-calendar-alt me-1'></i>${e.date.split('-').reverse().join('/')}</small>` : ''}
            </div>`).join(''); 
    }
}

/**
 * เปิด Modal ดูรายละเอียดโครงการ
 * @param {number} id - MA ID
 */
function viewProject(id) {
    // ปิด Modal ปฏิทินถ้าเปิดอยู่
    const calModalEl = document.getElementById('calendarModal');
    const calModal = bootstrap.Modal.getInstance(calModalEl);
    if(calModal) calModal.hide();

    // เรียก API ดึงข้อมูล
    $.post('warn_user.php', { action: 'get_ma_detail', ma_id: id }, function(res) {
        if(res.success) { 
            $('#modalNote').html(res.html); 
            new bootstrap.Modal(document.getElementById('detailModal')).show(); 
        } else {
            Swal.fire('Error', res.message || 'ไม่พบข้อมูล', 'error');
        }
    }, 'json');
}

/**
 * กรองตารางตามสถานะ
 * @param {string} statusClass - ชื่อคลาสของสถานะ (all, st-warning, etc.)
 * @param {HTMLElement} element - ปุ่มที่ถูกกด
 */
function filterTable(statusClass, element) {
    $('.stat-card').removeClass('active');
    if (element) $(element).addClass('active');
    
    if (statusClass === 'all') { 
        $('.ma-row').fadeIn(200); 
    } else { 
        $('.ma-row').hide(); 
        $('.' + statusClass).fadeIn(200); 
    }
}
