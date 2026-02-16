import { collectFlowsData } from './collectors.js';
import { openFolderPicker } from './folder-picker.js';
import {
    initFlowCounter,
    handlePrelandActionChange,
    handleLandActionChange,
    handleDistChange,
    handleRemoveItem,
    handleAddExisting,
    handleUploadZipClick,
    handleAddRedirect,
    handleEditFolder,
    handleMoveUp,
    handleMoveDown,
    handleDeleteFlow,
    handleAddFlow
} from './handlers.js';

// ── Window exports for backward compat with inline scripts ──
window.collectFlowsData = collectFlowsData;
window.openFolderPicker = openFolderPicker;

// ── Event dispatch maps ──
var clickSelectors = [
    { sel: '.flow-remove-preland, .flow-remove-land-folder, .flow-remove-land-redirect', fn: handleRemoveItem },
    { sel: '.flow-add-existing', fn: handleAddExisting },
    { sel: '.flow-upload-zip', fn: handleUploadZipClick },
    { sel: '.flow-add-land-redirect', fn: handleAddRedirect },
    { sel: '.flow-edit-folder', fn: handleEditFolder },
    { sel: '.flow-move-up', fn: handleMoveUp },
    { sel: '.flow-move-down', fn: handleMoveDown },
    { sel: '.flow-delete', fn: handleDeleteFlow }
];

var changeSelectors = [
    { sel: '.flow-preland-action', fn: handlePrelandActionChange },
    { sel: '.flow-land-action', fn: handleLandActionChange },
    { sel: '.flow-dist', fn: handleDistChange }
];

// ── Single delegated click listener ──
document.addEventListener('click', function (e) {
    for (var i = 0; i < clickSelectors.length; i++) {
        if (e.target.closest(clickSelectors[i].sel)) {
            clickSelectors[i].fn(e);
            return;
        }
    }
});

// ── Single delegated change listener ──
document.addEventListener('change', function (e) {
    for (var i = 0; i < changeSelectors.length; i++) {
        if (e.target.matches(changeSelectors[i].sel)) {
            changeSelectors[i].fn(e);
            return;
        }
    }
});

// ── Init ──
initFlowCounter();

// ── Add Flow button ──
var addFlowBtn = document.getElementById('add-flow-btn');
if (addFlowBtn) addFlowBtn.addEventListener('click', handleAddFlow);
