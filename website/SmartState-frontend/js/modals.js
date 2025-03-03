function showNotification(msg, title) {
    const content = document.createElement('div');
    content.style.display = 'flex'; // Apply flexbox to align elements
    content.style.justifyContent = 'space-between'; // Space between content and close button
    content.style.alignItems = 'center'; // Align items vertically
    content.innerHTML = `
        <div>
            <h6 class="text-white m-0">${title}</h6>
            <span class="text-white">${msg}</span>
        </div>`;

    Toastify({
        node: content,
        style: {
            background: '#0dcaf0',
            display: "flex",
        },
        close: true
    }).showToast();
}

function showSuccess(msg, title = 'Success') {
    const content = document.createElement('div');
    content.style.display = 'flex'; // Apply flexbox to align elements
    content.style.justifyContent = 'space-between'; // Space between content and close button
    content.style.alignItems = 'center'; // Align items vertically
    content.innerHTML = `
        <div>
            <h6 class="text-white m-0">${title}</h6>
            <span class="text-white">${msg}</span>
        </div>`;

    Toastify({
        node: content,
        style: {
            background: '#28a745',
            display: "flex",
        },
        close: true
    }).showToast();
}


function showWarning(msg, title = 'Warning') {
    const content = document.createElement('div');
    content.style.display = 'flex'; // Apply flexbox to align elements
    content.style.justifyContent = 'space-between'; // Space between content and close button
    content.style.alignItems = 'center'; // Align items vertically
    content.innerHTML = `
        <div>
            <h6 class="text-white m-0">${title}</h6>
            <span class="text-white">${msg}</span>
        </div>`;

    Toastify({
        node: content,
        style: {
            background: '#ffc107',
            display: "flex",
        },
        close: true
    }).showToast();
}

function showError(msg, title = 'Error') {
    const content = document.createElement('div');
    content.style.display = 'flex'; // Apply flexbox to align elements
    content.style.justifyContent = 'space-between'; // Space between content and close button
    content.style.alignItems = 'center'; // Align items vertically
    content.innerHTML = `
        <div>
            <h6 class="text-white m-0">${title}</h6>
            <span class="text-white">${msg}</span>
        </div>`;

    Toastify({
        node: content,
        style: {
            background: '#dc3545',
            display: "flex",
        },
        close: true
    }).showToast();
}

var confirmModal = $('#confirmModal');
var confirmModalTitle = $('#confirmModalLabel');
var confirmModalInternalId = $('#confirmModalInternalId');
var confirmModalTextSpan = $('#confirmModalTextSpan');
var confirmModalButton = $('#confirmModalConfirmButton');

confirmModal.on('hidden.bs.modal', function() {
    confirmModalTitle.html('');
    confirmModalInternalId.val('');
    confirmModalTextSpan.html('');
});


function showConfirmModal(){
    $('#confirmModal').modal("show");
}
function hideConfirmModal(){
    $('#confirmModal').modal("hide");
}