// Stats Table Editor functionality
function initializeStatsTableEditor(availableColumns, selectedMetrics, availableDimensions, selectedDimensions, tableName, saveUrl) {
    const MAX_GROUPBY_SELECTIONS = 3;
    
    // Initialize Sortable.js for both lists
    initializeSortable('metricsColumns', 'metrics');
    initializeSortable('dimensionsColumns', 'dimensions');

    // Set table name if provided
    const tableNameInput = document.getElementById('tableName');
    if (tableName) {
        tableNameInput.value = tableName;
    }

    // Add metrics columns
    addColumnsToList('metricsColumns', selectedMetrics, availableColumns);

    // Add dimensions columns
    addColumnsToList('dimensionsColumns', selectedDimensions, availableDimensions);

    // Setup metrics select/deselect buttons
    setupSelectButtons('selectAllMetrics', 'deselectAllMetrics', 'metricsColumns');

    // Attach metrics checkbox change handler
    $('#metricsColumns input[type="checkbox"]').on('change', function() {
        updateSaveButtonState();
    });

    // Special handler for dimensions checkboxes
    $('#dimensionsColumns input[type="checkbox"]').on('change', function() {
        const $allDimensionCheckboxes = $('#dimensionsColumns input[type="checkbox"]');
        const selectedCount = $allDimensionCheckboxes.filter(':checked').length;

        // If we have 3 selected items, disable all unselected checkboxes
        $allDimensionCheckboxes.not(':checked').prop('disabled', selectedCount >= MAX_GROUPBY_SELECTIONS);

        // If we have less than 3 selected items, enable all checkboxes
        if (selectedCount < MAX_GROUPBY_SELECTIONS) {
            $allDimensionCheckboxes.prop('disabled', false);
        }

        updateSaveButtonState();
    });

    // Add table name input handler
    $('#tableName').on('input', function() {
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

        if (groupby.length > MAX_GROUPBY_SELECTIONS) {
            alert(`You can select at most ${MAX_GROUPBY_SELECTIONS} dimensions for grouping`);
            return;
        }

        try {
            const response = await fetch(saveUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'save',
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
function addColumnsToList(containerId, selectedItems, columns) {
    const $list = $('#' + containerId);
    $list.empty();
    
    columns.forEach(column => {
        const field = typeof column === 'string' ? column : column.field;
        const title = typeof column === 'string' ? formatColumnName(column) : (column.title || formatColumnName(column.field));
        const isSelected = selectedItems.some(item => 
            (typeof item === 'string' ? item : item.field) === field
        );
        
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
        $('#' + containerId + ' input[type="checkbox"]').not(':disabled').prop('checked', true).trigger('change');
    });

    $('#' + deselectAllId).click(() => {
        $('#' + containerId + ' input[type="checkbox"]').prop('checked', false).trigger('change');
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
    const tableName = $('#tableName').val().trim();

    $('#saveTableBtn').prop('disabled', !metricsSelected || !dimensionsSelected || !tableName);
}

function formatColumnName(field) {
    return field.split(/(?=[A-Z])/).join(' ').toLowerCase().replace(/\b\w/g, l => l.toUpperCase());
}

function initializeSortable(containerId, group) {
    new Sortable(document.getElementById(containerId), {
        animation: 150,
        group: group
    });
}
