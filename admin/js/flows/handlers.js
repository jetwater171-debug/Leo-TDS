import { getFlowDist, redistributeWeights, redistributeWeightsAfterDelete } from './weights.js';
import { buildFolderRow, buildRedirectRow, buildFlowSection } from './templates.js';
import { openFolderPicker } from './folder-picker.js';
import { handleZipUpload } from './zip-upload.js';

// ── State ──
var flowCounter = 0;

export function initFlowCounter() {
    flowCounter = document.querySelectorAll('.flow-list-row').length;
}

// ═══════════════════════════════════════════
// CHANGE handlers
// ═══════════════════════════════════════════

// ── Toggle preland folders visibility ──
export function handlePrelandActionChange(e) {
    var fi = e.target.dataset.fi;
    var el = document.getElementById('flow-preland-folders-' + fi);
    if (el) el.style.display = e.target.value === 'folder' ? 'block' : 'none';
    // Toggle optimize_mode visibility
    var wrap = document.getElementById('flow-optimize-mode-wrap-' + fi);
    if (wrap) wrap.style.display = e.target.value === 'folder' ? 'block' : 'none';
}

// ── Toggle land folders/redirects visibility ──
export function handleLandActionChange(e) {
    var fi = e.target.dataset.fi;
    var folders = document.getElementById('flow-land-folders-' + fi);
    var redirects = document.getElementById('flow-land-redirects-' + fi);
    if (folders) folders.style.display = e.target.value === 'folder' ? 'block' : 'none';
    if (redirects) redirects.style.display = e.target.value === 'redirect' ? 'block' : 'none';
}

// ── Toggle flow-level distribution (thompson opts + weight cols) ──
export function handleDistChange(e) {
    var fi = e.target.dataset.fi;
    var val = e.target.value;
    var opts = document.getElementById('flow-thompson-opts-' + fi);
    if (opts) opts.style.display = val === 'thompson' ? 'block' : 'none';
    // Show weight cols only when weighted
    var sec = document.getElementById('sec-flow-' + fi);
    if (!sec) return;
    var showW = val === 'weighted';
    sec.querySelectorAll('.flow-weight-col').forEach(function (col) {
        col.style.display = showW ? 'block' : 'none';
    });
}

// ═══════════════════════════════════════════
// CLICK handlers
// ═══════════════════════════════════════════

// ── Delete path items ──
export function handleRemoveItem(e) {
    var removeBtn = e.target.closest('.flow-remove-preland, .flow-remove-land-folder, .flow-remove-land-redirect');
    if (!removeBtn) return;
    var item = removeBtn.closest('.flow-path-item');
    if (!item) return;
    var sec = item.closest('.flow-section');
    var fi = sec ? sec.dataset.flowIndex : '';
    var isWeighted = getFlowDist(fi) === 'weighted';
    // Read removed weight before removing
    var removedWeightInp = item.querySelector('input[type="number"]');
    var removedWeight = removedWeightInp ? (parseInt(removedWeightInp.value, 10) || 0) : 0;
    // Determine which selector to use for remaining weights
    var weightClass = '';
    if (removeBtn.classList.contains('flow-remove-preland')) weightClass = '.flow-preland-weight';
    else if (removeBtn.classList.contains('flow-remove-land-folder')) weightClass = '.flow-land-weight';
    else if (removeBtn.classList.contains('flow-remove-land-redirect')) weightClass = '.flow-land-weight';
    item.remove();
    if (isWeighted && weightClass && sec) {
        var remaining = sec.querySelectorAll(weightClass);
        redistributeWeightsAfterDelete(remaining, removedWeight);
    }
}

// ── Add Existing folder ──
export function handleAddExisting(e) {
    var btn = e.target.closest('.flow-add-existing');
    if (!btn) return;
    var fi = btn.dataset.fi;
    var type = btn.dataset.type; // 'preland' or 'land'
    var containerId = type === 'preland' ? 'flow-preland-items-' + fi : 'flow-land-folder-items-' + fi;
    var container = document.getElementById(containerId);
    var showWeight = getFlowDist(fi) === 'weighted';

    btn.disabled = true;
    fetch('listfolders.php').then(function (r) { return r.json(); }).then(function (data) {
        btn.disabled = false;
        if (data.error) { alert(data.result); return; }
        if (!data.folders.length) { alert('No landing folders found. Upload a ZIP first.'); return; }

        openFolderPicker(data.folders).then(function (choice) {
            if (!choice) return;
            container.appendChild(buildFolderRow(type, choice, showWeight));
            if (showWeight) {
                var weightClass = type === 'preland' ? '.flow-preland-weight' : '.flow-land-weight';
                redistributeWeights(container.querySelectorAll(weightClass));
            }
        });
    }).catch(function (err) { btn.disabled = false; alert('Error: ' + err); });
}

// ── Upload ZIP ──
export function handleUploadZipClick(e) {
    var btn = e.target.closest('.flow-upload-zip');
    if (!btn) return;
    handleZipUpload(btn);
}

// ── Add land redirect ──
export function handleAddRedirect(e) {
    var btn = e.target.closest('.flow-add-land-redirect');
    if (!btn) return;
    var fi = btn.dataset.fi;
    var container = document.getElementById('flow-land-redirect-items-' + fi);
    var showWeight = getFlowDist(fi) === 'weighted';
    container.appendChild(buildRedirectRow(showWeight));
    if (showWeight) {
        redistributeWeights(container.querySelectorAll('.flow-land-weight'));
    }
}

// ── Edit folder ──
export function handleEditFolder(e) {
    var btn = e.target.closest('.flow-edit-folder');
    if (!btn) return;
    var item = btn.closest('.flow-path-item');
    if (!item) return;
    var folderInput = item.querySelector('.flow-preland-folder, .flow-land-folder');
    if (!folderInput || !folderInput.value.trim()) {
        alert('Please enter a folder name first.');
        return;
    }
    if (typeof window.openFileEditor === 'function') {
        window.openFileEditor(folderInput.value.trim());
    } else {
        alert('File editor not loaded.');
    }
}

// ── Flow list: Move Up ──
export function handleMoveUp(e) {
    var btn = e.target.closest('.flow-move-up');
    if (!btn) return;
    var row = btn.closest('.flow-list-row');
    var prev = row.previousElementSibling;
    if (prev && prev.classList.contains('flow-list-row')) {
        row.parentNode.insertBefore(row, prev);
    }
}

// ── Flow list: Move Down ──
export function handleMoveDown(e) {
    var btn = e.target.closest('.flow-move-down');
    if (!btn) return;
    var row = btn.closest('.flow-list-row');
    var next = row.nextElementSibling;
    if (next && next.classList.contains('flow-list-row')) {
        row.parentNode.insertBefore(next, row);
    }
}

// ── Flow list: Delete ──
export function handleDeleteFlow(e) {
    var btn = e.target.closest('.flow-delete');
    if (!btn) return;
    if (!confirm('Delete this flow?')) return;
    var row = btn.closest('.flow-list-row');
    var fi = row.dataset.flowIndex;
    // Remove section
    var sec = document.getElementById('sec-flow-' + fi);
    if (sec) sec.remove();
    // Remove sidebar nav item
    var nav = document.querySelector('.flow-nav-item[data-flow-index="' + fi + '"]');
    if (nav) nav.remove();
    // Remove list row
    row.remove();
}

// ── Add Flow ──
export function handleAddFlow() {
    var flowName = prompt('Enter flow name (cannot be changed later):');
    if (!flowName || !flowName.trim()) return;
    flowName = flowName.trim();

    // Validate uniqueness
    var existing = document.querySelectorAll('.flow-name-label');
    for (var i = 0; i < existing.length; i++) {
        if (existing[i].value === flowName) {
            alert('Flow name "' + flowName + '" already exists. Choose a different name.');
            return;
        }
    }

    var fi = 'new_' + flowCounter;
    flowCounter++;

    // 1. Add list row
    var rowHtml = '<div class="flow-list-row" data-flow-index="' + fi + '">' +
        '<input type="text" class="form-control flow-name-label" value="' + flowName + '" readonly style="display:inline-block;width:200px;cursor:default;" /> ' +
        '<a href="javascript:void(0)" class="btn btn-primary btn-sm flow-move-up" title="Move Up">&uarr;</a> ' +
        '<a href="javascript:void(0)" class="btn btn-primary btn-sm flow-move-down" title="Move Down">&darr;</a> ' +
        '<a href="javascript:void(0)" class="btn btn-danger btn-sm flow-delete" title="Delete">✕ Delete</a>' +
        '</div>';
    document.getElementById('flows-list').insertAdjacentHTML('beforeend', rowHtml);

    // 2. Add sidebar nav item (after last flow-nav-item or after sec-flows link)
    var navHtml = '<li class="flow-nav-item" data-flow-index="' + fi + '"><a href="#sec-flow-' + fi + '">&nbsp;&nbsp;' + flowName + '</a></li>';
    var lastNav = document.querySelectorAll('.flow-nav-item');
    if (lastNav.length > 0) {
        lastNav[lastNav.length - 1].insertAdjacentHTML('afterend', navHtml);
    } else {
        var flowsNavLink = document.querySelector('a[href="#sec-flows"]');
        if (flowsNavLink) flowsNavLink.closest('li').insertAdjacentHTML('afterend', navHtml);
    }

    // 3. Add flow section from template
    var sectionFrag = buildFlowSection(fi, flowName);

    // Insert section before sec-scripts (or at end of camp-content)
    var scriptsSection = document.getElementById('sec-scripts');
    if (scriptsSection) {
        scriptsSection.parentNode.insertBefore(sectionFrag, scriptsSection);
    } else {
        document.querySelector('.camp-content').appendChild(sectionFrag);
    }

    // 4. Init QueryBuilder for the new flow's filters
    if (typeof $ !== 'undefined' && typeof $.fn.queryBuilder !== 'undefined') {
        try {
            $('#flow-filters-' + fi).queryBuilder({
                operators: $.fn.queryBuilder.constructor.DEFAULTS.operators.concat(typeof paramOperators !== 'undefined' ? paramOperators : []),
                filters: typeof tdsFilters !== 'undefined' ? tdsFilters : []
            });
        } catch (e) { console.warn('Could not init QueryBuilder for flow ' + fi, e); }
    }

    // 5. Navigate to the new flow section
    if (window.showSection) window.showSection('sec-flow-' + fi);
}
