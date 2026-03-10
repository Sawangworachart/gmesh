// customers.js
const API_URL = 'customers.php';

$(document).ready(function() {
    loadGroups();

    // Setup Modals
    $('#customerForm').submit(function(e) {
        e.preventDefault();
        submitForm($(this), '#customerModal', loadGroups);
    });

    $('#groupForm').submit(function(e) {
        e.preventDefault();
        submitForm($(this), '#groupModal', loadGroups);
    });
});

// Generic Submit
function submitForm(form, modalId, callback) {
    $.post(API_URL, form.serialize(), function(res) {
        if (res.status === 'success') {
            Swal.fire({ icon: 'success', title: 'สำเร็จ', text: res.message, timer: 1000, showConfirmButton: false });
            $(modalId).modal('hide');
            callback();
        } else {
            Swal.fire('Error', res.message, 'error');
        }
    }, 'json');
}

// Load Data & Render
function loadGroups() {
    $('#groups-container').html('<div class="text-center p-5"><i class="fas fa-spinner fa-spin"></i> กำลังโหลดข้อมูล...</div>');

    $.get(API_URL + '?action=fetch_all', function(data) {
        const container = $('#groups-container');
        container.empty();

        // 1. Ungrouped (ไม่มีกลุ่ม)
        if (data.ungrouped.length > 0) {
            container.append(createGroupHtml('null', 'ไม่ได้จัดกลุ่ม', data.ungrouped, false));
        } else {
             // ถ้าไม่มีรายการที่ไม่มีกลุ่ม ก็สร้างกล่องเปล่าไว้รองรับการลาก
            container.append(createGroupHtml('null', 'ไม่ได้จัดกลุ่ม', [], false));
        }

        // 2. Groups
        data.groups.forEach(group => {
            container.append(createGroupHtml(group.group_id, group.group_name, group.customers, true));
        });

        // Initialize Sortable (Drag & Drop)
        initSortable();

    }, 'json');
}

// Generate HTML for Group
function createGroupHtml(id, name, customers, isEditable) {
    const customerHtml = customers.map(c => `
        <div class="customer-item" id="cust-${c.customers_id}" data-id="${c.customers_id}" data-name="${c.customers_name}">
            <div class="info">
                <div class="info-main">${c.customers_name}</div>
                <div class="info-sub">
                    <span><i class="fas fa-building"></i> ${c.agency || '-'}</span>
                    <span><i class="fas fa-phone"></i> ${c.phone}</span>
                </div>
            </div>
            <div class="item-actions">
                <button class="btn-edit-item" onclick="openModal('edit', ${c.customers_id})"><i class="fas fa-pen"></i></button>
                <button class="btn-del-item" onclick="deleteCustomer(${c.customers_id})"><i class="fas fa-trash"></i></button>
            </div>
        </div>
    `).join('');

    const actionsHtml = isEditable ? `
        <div class="group-actions" onclick="event.stopPropagation()">
            <button onclick="openGroupModal('edit', ${id}, '${name}')"><i class="fas fa-edit"></i></button>
            <button onclick="deleteGroup(${id})"><i class="fas fa-trash-alt"></i></button>
        </div>
    ` : '';

    return `
        <div class="group-card" id="group-${id}">
            <div class="group-header" onclick="toggleGroup('${id}')">
                <div class="group-title">
                    <i class="fas ${isEditable ? 'fa-folder' : 'fa-box-open'} text-primary me-2"></i> ${name}
                    <span class="badge bg-secondary ms-2 rounded-pill">${customers.length}</span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    ${actionsHtml}
                    <i class="fas fa-chevron-down transition-icon"></i>
                </div>
            </div>
            <div class="customer-list" id="list-${id}" data-group-id="${id}">
                ${customerHtml}
            </div>
        </div>
    `;
}

// Drag & Drop Logic
function initSortable() {
    $(".customer-list").sortable({
        connectWith: ".customer-list",
        placeholder: "ui-sortable-placeholder",
        handle: ".customer-item",
        opacity: 0.8,
        receive: function(event, ui) {
            const customerId = ui.item.data('id');
            const newGroupId = $(this).data('group-id');
            
            $.post(API_URL, {
                action: 'move_customer',
                customers_id: customerId,
                group_id: newGroupId
            }, function(res) {
                if (res.status === 'success') {
                    updateBadges();
                } else {
                    $(ui.sender).sortable('cancel');
                    Swal.fire('Error', 'ย้ายไม่สำเร็จ', 'error');
                }
            }, 'json');
        }
    }).disableSelection();
}

function updateBadges() {
    $('.group-card').each(function() {
        const count = $(this).find('.customer-item').length;
        $(this).find('.badge').text(count);
    });
}

// Toggle Collapse
function toggleGroup(id) {
    $(`#list-${id}`).slideToggle(200);
}

// Search Filter
function filterCustomers() {
    const value = $('#searchInput').val().toLowerCase();
    $('.customer-item').each(function() {
        const text = $(this).text().toLowerCase();
        $(this).toggle(text.indexOf(value) > -1);
    });
    $('.group-card').each(function() {
        if ($(this).find('.customer-item:visible').length > 0) {
            $(this).show();
            $(this).find('.customer-list').show(); // Auto expand when searching
        } else {
            $(this).hide();
        }
    });
}

// Auto Group
function autoGroup() {
    Swal.fire({
        title: 'จัดกลุ่มอัตโนมัติ?',
        text: "ระบบจะสร้างกลุ่มจากชื่อบริษัทที่ซ้ำกัน",
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'ตกลง'
    }).then((r) => {
        if (r.isConfirmed) {
            $.post(API_URL, { action: 'auto_group' }, function(res) {
                Swal.fire('เสร็จสิ้น', res.message, 'success');
                loadGroups();
            }, 'json');
        }
    });
}

// Modal Functions
function openModal(mode, id = null) {
    $('#customerModal').modal('show');
    $('#customerForm')[0].reset();
    if (mode === 'create') {
        $('#modalTitle').text('เพิ่มลูกค้าใหม่');
        $('#form_action').val('create');
    } else {
        $('#modalTitle').text('แก้ไขข้อมูล');
        $('#form_action').val('edit');
        $('#customers_id').val(id);
        $.get(API_URL + '?action=fetch_single&id=' + id, function(data) {
            $('#customers_name').val(data.customers_name);
            $('#agency').val(data.agency);
            $('#contact_name').val(data.contact_name);
            $('#phone').val(data.phone);
            $('#address').val(data.address);
            $('#province').val(data.province);
        }, 'json');
    }
}
function closeModal() { $('#customerModal').modal('hide'); }

function deleteCustomer(id) {
    Swal.fire({ title: 'ยืนยันลบ?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33' })
    .then((r) => {
        if (r.isConfirmed) $.post(API_URL, { action: 'delete', id: id }, (res) => { loadGroups(); }, 'json');
    });
}

function openGroupModal(mode, id = null, name = '') {
    $('#groupModal').modal('show');
    $('#group_name').val(name);
    if(mode==='create') {
        $('#groupModalTitle').text('สร้างกลุ่มใหม่'); $('#group_action').val('create_group');
    } else {
        $('#groupModalTitle').text('แก้ไขกลุ่ม'); $('#group_action').val('edit_group'); $('#group_id').val(id);
    }
}
function closeGroupModal() { $('#groupModal').modal('hide'); }

function deleteGroup(id) {
    Swal.fire({ title: 'ลบกลุ่มนี้?', text: "ลูกค้าจะย้ายไปที่ 'ไม่จัดกลุ่ม'", icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33' })
    .then((r) => {
        if (r.isConfirmed) $.post(API_URL, { action: 'delete_group', group_id: id }, (res) => { loadGroups(); }, 'json');
    });
}