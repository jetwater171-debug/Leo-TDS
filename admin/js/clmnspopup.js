function addColumnsToList(selectedClmns, availableClmns) {
    let $list = $('#columnsList');
    $list.empty();
    
    // First add selected columns in their saved order
    selectedClmns.forEach(column => {
        $list.append(createSortableItem(column.field, formatColumnName(column.field), true));
    });

    // Then add unselected columns
    availableClmns.forEach(column => {
        if (!selectedClmns.some(sc => sc.field === column)) {
            $list.append(createSortableItem(column, formatColumnName(column), false));
        }
    });
    
    initializeSortable('columnsList', 'columns');
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

            $.modal.close();
            location.reload();
        } catch (error) {
            alert('Error saving columns: ' + error.message);
        }
    });
}