// =========================================
// JS สำหรับหน้า PM Schedule (Admin)
// =========================================

$(document).ready(function() {
    // Mouse Glow Effect
    const body = document.querySelector('body');
    document.addEventListener('mousemove', (e) => {
        body.style.setProperty('--x', e.clientX + 'px');
        body.style.setProperty('--y', e.clientY + 'px');
    });

    // Handle Modal Overlay Click
    $('.modal').on('click', function(e) {
        if ($(e.target).hasClass('modal')) {
            closeModal('schedule-modal');
        }
    });
});

// --- Modal Functions ---

function openModal(modalId) {
    const modal = document.getElementById(modalId);
    modal.classList.add('show');
    modal.style.display = 'flex'; // Ensure display flex for centering
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    modal.classList.remove('show');
    setTimeout(() => {
        modal.style.display = 'none';
    }, 300); // Wait for transition
}

// --- Edit Logic ---

function editSchedule(data) {
    // Reset Form
    document.getElementById('scheduleForm').reset();

    // Set Action to Edit
    document.getElementById('form-action').value = 'edit';
    document.getElementById('edit_id').value = data.id;
    
    // Update UI Texts
    document.getElementById('modal-title').textContent = 'แก้ไขตาราง PM: ' + data.contract_number;
    document.getElementById('submit-text').textContent = 'บันทึกการแก้ไข';

    // Fill Form Data
    document.getElementById('contract_id').value = data.contract_id;
    document.getElementById('department').value = data.department || '';
    document.getElementById('device').value = data.device || '';
    document.getElementById('tor_year').value = data.tor_year;
    document.getElementById('visit_count').value = data.visit_done;
    document.getElementById('alert_email').value = data.alert_email || '';
    
    // Date Format: The input[type="date"] expects YYYY-MM-DD
    // Ensure data.next_visit_raw matches this format
    document.getElementById('next_visit').value = data.next_visit_raw;

    openModal('schedule-modal');
}

// Initialize for Add Mode
function initAddMode() {
    document.getElementById('scheduleForm').reset();
    document.getElementById('form-action').value = 'add';
    document.getElementById('edit_id').value = '';
    document.getElementById('modal-title').textContent = 'กำหนดตารางการเข้าบำรุงรักษา';
    document.getElementById('submit-text').textContent = 'บันทึกข้อมูล';
    
    // Set default date to today or empty
    // document.getElementById('next_visit').valueAsDate = new Date();
    
    openModal('schedule-modal');
}

// --- Delete Confirmation ---

function confirmDelete(event, form) {
    event.preventDefault();
    Swal.fire({
        title: 'ยืนยันการลบ?',
        text: 'ข้อมูลจะถูกลบถาวร ไม่สามารถกู้คืนได้!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'ลบข้อมูล',
        cancelButtonText: 'ยกเลิก'
    }).then((result) => {
        if (result.isConfirmed) {
            form.submit();
        }
    });
}

// --- Search Function ---

function searchTable() {
    const input = document.getElementById("searchInput");
    const filter = input.value.toUpperCase();
    const table = document.querySelector("table tbody");
    const tr = table.getElementsByTagName("tr");

    for (let i = 0; i < tr.length; i++) {
        // Search in Contract (0) and Customer/Device (1) columns
        let tdContract = tr[i].getElementsByTagName("td")[0];
        let tdCustomer = tr[i].getElementsByTagName("td")[1];

        if (tdContract || tdCustomer) {
            let txtContract = tdContract.textContent || tdContract.innerText;
            let txtCustomer = tdCustomer.textContent || tdCustomer.innerText;

            if (txtContract.toUpperCase().indexOf(filter) > -1 || txtCustomer.toUpperCase().indexOf(filter) > -1) {
                tr[i].style.display = "";
            } else {
                tr[i].style.display = "none";
            }
        }
    }
}
