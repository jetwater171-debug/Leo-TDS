// Common functions for sortable lists
function formatColumnName(field) {
    return field.charAt(0).toUpperCase() + field.slice(1).replace(/_/g, ' ');
}

function initializeSortable(elementId, group) {
    return new Sortable(document.getElementById(elementId), {
        animation: 150,
        group: group
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
        return array.map(field => ({
            field: field,
            width: -1
        }));
    }
    return array;
}

function createSortableItem(field, title, isChecked) {
    return `
        <div class="sortable-item" data-field="${field}">
            <input type="checkbox" ${isChecked ? 'checked' : ''}>
            <span>${title || formatColumnName(field)}</span>
        </div>
    `;
}

function getSelectedItems(containerId) {
    return $(`#${containerId} .sortable-item`)
        .filter((_, item) => $(item).find('input').is(':checked'))
        .map((_, item) => ({
            field: $(item).data('field'),
            width: -1
        }))
        .get();
}

function setupSelectButtons(selectAllId, deselectAllId, containerId) {
    $(`#${selectAllId}`).click(() => {
        $(`#${containerId} input[type="checkbox"]`).prop('checked', true);
    });

    $(`#${deselectAllId}`).click(() => {
        $(`#${containerId} input[type="checkbox"]`).prop('checked', false);
    });
}

function setupModalButtons(saveButtonId, cancelButtonId, saveCallback) {
    $(`#${saveButtonId}`).off('click').click(saveCallback);
    
    $(`#${cancelButtonId}`).click(() => {
        $.modal.close();
    });
}
