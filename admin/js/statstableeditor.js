// Stats Table Editor functionality
function initializeStatsTableEditor(availableColumns, selectedMetrics, selectedDimensions, tableName, saveUrl) {
    // Initialize Sortable.js for both lists
    new Sortable(document.getElementById('metricsColumns'), {
        animation: 150,
        group: 'metrics'
    });
    new Sortable(document.getElementById('dimensionsColumns'), {
        animation: 150,
        group: 'dimensions'
    });

    // Set table name if provided
    const tableNameInput = document.getElementById('tableName');
    if (tableName) {
        console.log('Setting table name to:', tableName);
        tableNameInput.value = tableName;
    }

    // Convert arrays to proper format
    function convertToColumnObjects(array) {
        if (Array.isArray(array) && array.length > 0 && typeof array[0] === 'string') {
            return array.map(field => ({
                field: field,
                title: field.charAt(0).toUpperCase() + field.slice(1).replace(/_/g, ' ')
            }));
        }
        return array;
    }

    function convertToMetricObjects(array) {
        if (Array.isArray(array) && array.length > 0 && typeof array[0] === 'string') {
            return array.map(field => ({
                field: field,
                width: -1
            }));
        }
        return array;
    }

    availableColumns = convertToColumnObjects(availableColumns);
    selectedMetrics = convertToMetricObjects(selectedMetrics);
    selectedDimensions = convertToMetricObjects(selectedDimensions);

    // Populate columns lists
    function addColumnsToList($list, columns, selectedItems, fieldKey = 'field') {
        $list.empty();
        columns.forEach(column => {
            const isSelected = selectedItems.some(item => 
                (typeof item === 'string' ? item : item[fieldKey]) === column.field
            );
            const $item = $(`
                <div class="column-item" data-field="${column.field}">
                    <input type="checkbox" ${isSelected ? 'checked' : ''}>
                    <span>${column.title || column.field}</span>
                </div>
            `);
            $list.append($item);
        });
    }

    addColumnsToList($('#metricsColumns'), availableColumns, selectedMetrics);
    addColumnsToList($('#dimensionsColumns'), availableColumns, selectedDimensions);

    // Select/Deselect All handlers
    function setupSelectButtons(prefix, containerId) {
        $(`#selectAll${prefix}`).click(() => {
            $(`#${containerId} input[type="checkbox"]`).prop('checked', true);
        });

        $(`#deselectAll${prefix}`).click(() => {
            $(`#${containerId} input[type="checkbox"]`).prop('checked', false);
        });
    }

    setupSelectButtons('Metrics', 'metricsColumns');
    setupSelectButtons('Dimensions', 'dimensionsColumns');

    // Save button handler
    $('#saveTableBtn').off('click').click(async () => {
        const tableName = $('#tableName').val().trim();
        console.log('Saving with table name:', tableName);
        
        if (!tableName) {
            alert('Please enter a table name');
            return;
        }

        const selectedMetrics = $('#metricsColumns .column-item')
            .filter((_, item) => $(item).find('input').is(':checked'))
            .map((_, item) => ({
                field: $(item).data('field'),
                width: -1
            }))
            .get();

        const selectedDimensions = $('#dimensionsColumns .column-item')
            .filter((_, item) => $(item).find('input').is(':checked'))
            .map((_, item) => $(item).data('field'))
            .get();

        if (selectedMetrics.length === 0) {
            alert('Please select at least one metric');
            return;
        }

        const data = {
            name: tableName,
            columns: selectedMetrics,
            groupby: selectedDimensions
        };

        try {
            const response = await fetch(saveUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            });

            if (!response.ok) {
                throw new Error('Network response was not ok');
            }

            $.modal.close();
            location.reload();
        } catch (error) {
            alert('Error saving table: ' + error.message);
        }
    });

    // Cancel button handler
    $('#cancelTableBtn').click(() => {
        $.modal.close();
    });
}
