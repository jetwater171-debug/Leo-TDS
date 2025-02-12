// Common functions for sortable lists
function formatColumnName(field) {
    return field.charAt(0).toUpperCase() + field.slice(1).replace(/_/g, ' ');
}

function initializeSortable(elementId, group) {
    return new Sortable(document.getElementById(elementId), {
        animation: 150,
        group: group,
        handle: '.drag-handle', // Add handle for dragging
        ghostClass: 'sortable-ghost',
        chosenClass: 'sortable-chosen',
        dragClass: 'sortable-drag'
    });
}

function convertArrayToObjects(array) {
    if (Array.isArray(array) && array.length > 0 && typeof array[0] === 'string') {
        return array.map(field => ({
            field: field,
            title: formatColumnName(field)
        }));
    }
    return array;
}

function convertToColumnObjects(array) {
    if (Array.isArray(array) && array.length > 0 && typeof array[0] === 'string') {
        return array.map(field => (field));
    }
    return array;
}

function createSortableItem(field, title, isChecked) {
    return `
        <div class="sortable-item" data-field="${field}">
            <span class="drag-handle">☰</span>
            <input type="checkbox" ${isChecked ? 'checked' : ''} onchange="handleCheckboxChange(this)">
            <span>${title || formatColumnName(field)}</span>
        </div>
    `;
}

function getSelectedItems(containerId) {
    return $(`#${containerId} .sortable-item`)
        .filter((_, item) => $(item).find('input').is(':checked'))
        .map((_, item) => $(item).data('field'))
        .get();
}

function setupSelectButtons(selectAllId, deselectAllId, containerId) {
    $(`#${selectAllId}`).click(() => {
        $(`#${containerId} input[type="checkbox"]`).prop('checked', true);
        updateSaveButtonState();
    });

    $(`#${deselectAllId}`).click(() => {
        $(`#${containerId} input[type="checkbox"]`).prop('checked', false);
        updateSaveButtonState();
    });
}

function setupModalButtons(saveButtonId, cancelButtonId, saveCallback) {
    const $saveButton = $(`#${saveButtonId}`);
    $saveButton.off('click').click(saveCallback);
    
    $(`#${cancelButtonId}`).click(() => {
        $.modal.close();
    });

    // Initial button state
    updateSaveButtonState();
}

function handleCheckboxChange() {
    updateSaveButtonState();
}

function updateSaveButtonState() {
    // For clmnspopup.html
    const $saveColumnsBtn = $('#saveColumns');
    if ($saveColumnsBtn.length) {
        const hasCheckedColumns = $('#columnsList input:checked').length > 0;
        $saveColumnsBtn.prop('disabled', !hasCheckedColumns);
        $saveColumnsBtn.toggleClass('btn-disabled', !hasCheckedColumns);
    }

    // For statstableeditor.html
    const $saveTableBtn = $('#saveTableBtn');
    if ($saveTableBtn.length) {
        const hasCheckedMetrics = $('#metricsColumns input:checked').length > 0;
        const hasCheckedDimensions = $('#dimensionsColumns input:checked').length > 0;
        const isEnabled = hasCheckedMetrics && hasCheckedDimensions;
        $saveTableBtn.prop('disabled', !isEnabled);
        $saveTableBtn.toggleClass('btn-disabled', !isEnabled);
    }
}
