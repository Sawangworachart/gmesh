// =========================================
// JS สำหรับหน้า Alarms (Admin) - warn_admin.js
// =========================================

let fullCalendar;

$(document).ready(function() {
    // Mouse Glow Effect
    const body = document.querySelector('body');
    document.addEventListener('mousemove', (e) => {
        body.style.setProperty('--x', e.clientX + 'px');
        body.style.setProperty('--y', e.clientY + 'px');
    });

    animateCounters();

    // Search Logic
    $("#searchInput").on("keyup", function() {
        var value = $(this).val().toLowerCase().trim();

        $(".tab-content:visible tbody tr").each(function() {
            var rowText = $(this).text().toLowerCase();
            if (rowText.indexOf(value) > -1) {
                $(this).fadeIn(200);
            } else {
                $(this).hide();
            }
        });

        var visibleRows = $(".tab-content:visible tbody tr:visible").length;
        if (visibleRows === 0) {
            if ($("#no-results").length === 0) {
                $(".tab-content:visible tbody").append('<tr id="no-results"><td colspan="4" style="text-align:center; padding:20px; color:#94a3b8;">ไม่พบข้อมูลที่ค้นหา...</td></tr>');
            }
        } else {
            $("#no-results").remove();
        }
    });
});

function switchTab(tabId) {
    $('.tab-content').hide();
    $('#content-' + tabId).show();

    $('.ma-tab').removeClass('active');
    $('.ma-tab[data-tab="' + tabId + '"]').addClass('active');

    $('.stat-box').removeClass('active');
    $('.stat-box[data-tab="' + tabId + '"]').addClass('active');

    $("#searchInput").trigger("keyup");
}

function loadDetail(id) {
    if (!id) return;
    $.ajax({
        url: 'warn_admin.php',
        type: 'POST',
        data: {
            action: 'get_ma_detail',
            ma_id: id
        },
        dataType: 'json',
        success: function(res) {
            if (res.success) {
                $('#detailBody').html(res.html);
                $('#modalDetail').addClass('show');
            } else {
                Swal.fire('Error', res.error, 'error');
            }
        }
    });
}

function markAsComplete(id, currentDate, projectName, maNote, btn) {
    Swal.fire({
        title: '<span style="color: #1e293b; font-size: 1.4rem; font-weight: 700;">บันทึกผลดำเนินงาน</span>',
        html: `
        <div style="text-align:left; padding: 10px 5px;">
            <div style="background: #eff6ff; padding: 12px; border-radius: 12px; border: 1px solid #dbeafe; margin-bottom: 20px;">
                <div style="font-size: 0.8rem; color: #3b82f6; font-weight: 600; text-transform: uppercase; margin-bottom: 4px;">ข้อมูลงาน</div>
                <div style="font-size: 1rem; color: #1e3a8a; font-weight: 700; line-height: 1.4;">${projectName}</div>
                <div style="font-size: 0.9rem; color: #60a5fa; margin-top: 2px;">
                    <i class="fas fa-tag"></i> ${maNote || 'งาน MA'}
                </div>
            </div>

            <div style="margin-bottom: 15px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; color: #64748b; margin-bottom: 5px;">
                    <i class="far fa-calendar-alt"></i> วันที่ดำเนินการจริง
                </label>
                <input type="date" id="swal-ma-date" class="swal2-input" 
                       style="margin: 0; width: 100%; border-radius: 10px; border: 1px solid #e2e8f0; font-family: 'Sarabun', sans-serif;" 
                       value="${currentDate}">
            </div>

            <div style="margin-bottom: 15px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; color: #64748b; margin-bottom: 5px;">
                    <i class="far fa-edit"></i> หมายเหตุ / รายละเอียดงาน
                </label>
                <textarea id="swal-remark" class="swal2-textarea" 
                          style="margin: 0; width: 100%; height: 100px; border-radius: 10px; border: 1px solid #e2e8f0; font-family: 'Sarabun', sans-serif; font-size: 0.9rem;" 
                          placeholder="ระบุรายละเอียดการเข้าบริการ..."></textarea>
            </div>

            <div style="background: #f8fafc; padding: 15px; border-radius: 12px; border: 1px dashed #cbd5e1;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; color: #475569; margin-bottom: 8px;">
                    <i class="fas fa-paperclip"></i> แนบไฟล์หลักฐาน (รูปภาพหรือ PDF)
                </label>
                <input type="file" id="swal-file" 
                       style="font-size: 0.8rem; color: #64748b; width: 100%;">
            </div>
        </div>
        `,
        icon: 'info',
        iconColor: '#3b82f6',
        showCancelButton: true,
        confirmButtonText: '<i class="fas fa-check-circle"></i> บันทึก',
        cancelButtonText: 'ไว้ทีหลัง',
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#94a3b8',
        reverseButtons: true,
        preConfirm: () => {
            const maDate = document.getElementById('swal-ma-date').value;
            const remark = document.getElementById('swal-remark').value;
            const file = document.getElementById('swal-file').files[0];

            if (!maDate) {
                Swal.showValidationMessage('กรุณาระบุวันที่ดำเนินการ');
                return false;
            }

            const formData = new FormData();
            formData.append('action', 'mark_complete');
            formData.append('ma_id', id);
            formData.append('ma_date', maDate);
            formData.append('remark', remark);
            if (file) {
                formData.append('file', file);
            }
            return formData;
        }
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: 'warn_admin.php',
                type: 'POST',
                data: result.value,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(res) {
                    if (res.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'บันทึกสำเร็จ',
                            timer: 1000,
                            showConfirmButton: false
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire('ผิดพลาด', res.error, 'error');
                    }
                }
            });
        }
    });
}

function animateCounters() {
    $('.stat-val').each(function() {
        const $el = $(this);
        const target = parseInt($el.data('count'), 10) || 0;
        if (target === 0) {
            $el.text('0');
            return;
        }
        let current = 0;
        const duration = 800;
        const stepTime = Math.max(Math.floor(duration / target), 20);
        const counter = setInterval(() => {
            current++;
            $el.text(current);
            if (current >= target) {
                $el.text(target);
                clearInterval(counter);
            }
        }, stepTime);
    });
}

function closeModals(e) {
    if (e.target === e.currentTarget) {
        $(e.currentTarget).removeClass('show');
    }
}

// Calendar Logic
function updateMonthlySummary() {
    if (!fullCalendar) return;

    const currentViewDate = fullCalendar.getDate();
    const currentMonth = currentViewDate.getMonth();
    const currentYear = currentViewDate.getFullYear();

    // Note: calendarEvents variable needs to be passed from PHP or fetched via AJAX
    // For now assuming it's available in global scope or handled differently
    if (typeof calendarEvents === 'undefined') return;

    const monthlyEvents = calendarEvents.filter(ev => {
        const evDate = new Date(ev.start);
        return evDate.getMonth() === currentMonth && evDate.getFullYear() === currentYear;
    });

    const listContainer = $('#monthly-list');
    listContainer.empty();

    if (monthlyEvents.length === 0) {
        listContainer.append('<div style="text-align:center; color:#94a3b8; padding:20px;">ไม่มีงานในเดือนนี้</div>');
        return;
    }

    monthlyEvents.forEach(ev => {
        const html = `
        <div class="summary-item" onclick="loadDetail(${ev.id})" style="border-left-color: ${ev.borderColor}">
            <div class="summary-item-title">${ev.title}</div>
            <div class="summary-item-sub"><i class="far fa-building"></i> อื่นๆ</div>
        </div>
    `;
        listContainer.append(html);
    });
}

function triggerCalendarModal() {
    $('.modal-overlay').removeClass('show');
    $('#modalCalendar').addClass('show');

    setTimeout(() => {
        if (!fullCalendar) {
            const calendarEl = document.getElementById('calendar-area');
            
            // Note: urgentDates and calendarEvents need to be available
            if (typeof calendarEvents === 'undefined') {
                console.error("Calendar events data missing");
                return;
            }

            fullCalendar = new FullCalendar.Calendar(calendarEl, {
                locale: 'th',
                initialView: 'dayGridMonth',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: ''
                },
                events: calendarEvents,
                dayCellDidMount: function(info) {
                    if (typeof urgentDates !== 'undefined' && urgentDates.includes(info.dateStr)) {
                        info.el.style.backgroundColor = '#fffbeb';
                        info.el.style.transition = 'background-color 0.3s';
                    }
                },
                eventClick: function(info) {
                    loadDetail(info.event.id);
                },
                datesSet: function() {
                    updateMonthlySummary();
                }
            });
            fullCalendar.render();
        } else {
            fullCalendar.updateSize();
            updateMonthlySummary();
        }
    }, 200);
}
