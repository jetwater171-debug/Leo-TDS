let columnsSortable = null;

function addColumnsToList(selectedClmns, availableClmns) {
    const $list = $('#columnsList');
    $list.empty();
    
    // First add selected columns in their saved order
    selectedClmns.forEach(column => {
        $list.append(createSortableItem(
            typeof column === 'string' ? column : column.field,
            formatColumnName(typeof column === 'string' ? column : column.field),
            true
        ));
    });

    // Then add unselected columns
    availableClmns.forEach(column => {
        const columnField = typeof column === 'string' ? column : column.field;
        const isSelected = selectedClmns.some(sc => 
            (typeof sc === 'string' ? sc : sc.field) === columnField
        );
        
        if (!isSelected) {
            $list.append(createSortableItem(
                columnField,
                formatColumnName(columnField),
                false
            ));
        }
    });
    
    // Destroy existing Sortable instance if it exists
    if (columnsSortable) {
        columnsSortable.destroy();
    }
    
    // Initialize new Sortable instance
    columnsSortable = initializeSortable('columnsList', 'columns');

    // Setup select/deselect buttons
    setupSelectButtons('selectAllColumns', 'deselectAllColumns', 'columnsList');

    // Attach checkbox change handlers
    $('#columnsList input[type="checkbox"]').on('change', function() {
        updateSaveButtonState();
    });

    // Initial button state
    updateSaveButtonState();
}

function getSelectedColumns() {
    return getSelectedItems('columnsList');
}

function setSaveButtonHandler(handlerUrl) {
    setupModalButtons('saveColumns', 'closeModal', async () => {
        const selectedColumns = getSelectedColumns();
        
        if (selectedColumns.length === 0) {
            alert('Please select at least one column');
            return;
        }

        try {
            const response = await fetch(handlerUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(selectedColumns)
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
            alert('Error saving columns: ' + error.message);
        }
    });
}