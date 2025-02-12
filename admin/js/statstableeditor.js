// Stats Table Editor functionality
function initializeStatsTableEditor(availableColumns, selectedMetrics, availableDimensions, selectedDimensions, tableName, saveUrl) {
    // Initialize Sortable.js for both lists
    initializeSortable('metricsColumns', 'metrics');
    initializeSortable('dimensionsColumns', 'dimensions');

    // Set table name if provided
    const tableNameInput = document.getElementById('tableName');
    if (tableName) {
        tableNameInput.value = tableName;
    }

    // Add metrics columns
    addColumnsToList('metricsColumns', selectedMetrics, availableColumns, true);

    // Add dimensions columns
    addColumnsToList('dimensionsColumns', selectedDimensions, availableDimensions, true);

    // Setup select/deselect buttons
    setupSelectButtons('selectAllMetrics', 'deselectAllMetrics', 'metricsColumns');
    setupSelectButtons('selectAllDimensions', 'deselectAllDimensions', 'dimensionsColumns');

    // Attach checkbox change handlers
    $('#metricsColumns input[type="checkbox"], #dimensionsColumns input[type="checkbox"]').on('change', function() {
        updateSaveButtonState();
    });

    // Initial button state
    updateSaveButtonState();

    // Setup save handler
    $('#saveTableBtn').click(async () => {
        const name = tableNameInput.value.trim();
        if (!name) {
            alert('Please enter a table name');
            return;
        }

        const columns = getSelectedItems('metricsColumns');
        if (columns.length === 0) {
            alert('Please select at least one metric column');
            return;
        }

        const groupby = getSelectedItems('dimensionsColumns');
        if (groupby.length === 0) {
            alert('Please select at least one dimension for grouping');
            return;
        }

        try {
            const response = await fetch(saveUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'statstable',
                    table: {
                        name: name,
                        columns: columns,
                        groupby: groupby
                    }
                })
            });

            if (!response.ok) {
                throw new Error('Network response was not ok');
            }

            const data = await response.json();
            if (!data.error) {
                window.location.reload();
            } else {
                throw new Error(data.msg);
            }
        } catch (error) {
            alert('Error saving table: ' + error.message);
        }
    });

    // Setup cancel handler
    $('#cancelTableBtn').click(() => {
        $.modal.close();
    });
}

function deleteStatsTable(tableName, deleteUrl) {
    if (!confirm(`Are you sure you want to delete table "${tableName}"?`)) {
        return;
    }

    fetch(deleteUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            action: 'statstable',
            action: 'delete',
            tableName: tableName
        })
    })
    .then(response => response.json())
    .then(data => {
        if (!data.error) {
            window.location.reload();
        } else {
            throw new Error(data.msg);
        }
    })
    .catch(error => {
        alert('Error deleting table: ' + error.message);
    });
}

// Helper functions
function initializeSortable(containerId, group) {
    new Sortable(document.getElementById(containerId), {
        animation: 150,
        group: group
    });
}

function addColumnsToList(containerId, selectedItems, columns, isSelected) {
    const $list = $('#' + containerId);
    $list.empty();
    
    columns.forEach(column => {
        const field = typeof column === 'string' ? column : column.field;
        const title = typeof column === 'string' ? formatColumnName(column) : (column.title || formatColumnName(column.field));
        const $item = $(`
            <div class="column-item" data-field="${field}">
                <span class="drag-handle">☰</span>
                <input type="checkbox" ${isSelected ? 'checked' : ''}>
                <span>${title}</span>
            </div>
        `);
        $list.append($item);
    });

    // Attach checkbox change handlers
    $(`#${containerId} input[type="checkbox"]`).on('change', function() {
        updateSaveButtonState();
    });
}

function setupSelectButtons(selectAllId, deselectAllId, containerId) {
    $('#' + selectAllId).click(() => {
        $('#' + containerId + ' input[type="checkbox"]').prop('checked', true);
    });

    $('#' + deselectAllId).click(() => {
        $('#' + containerId + ' input[type="checkbox"]').prop('checked', false);
    });
}

function getSelectedItems(containerId) {
    return $('#' + containerId + ' .column-item')
        .filter((_, item) => $(item).find('input').is(':checked'))
        .map((_, item) => $(item).data('field'))
        .get();
}

function updateSaveButtonState() {
    const metricsSelected = $('#metricsColumns input[type="checkbox"]:checked').length > 0;
    const dimensionsSelected = $('#dimensionsColumns input[type="checkbox"]:checked').length > 0;
    const tableName = document.getElementById('tableName').value.trim();

    $('#saveTableBtn').prop('disabled', !metricsSelected || !dimensionsSelected || !tableName);
}
