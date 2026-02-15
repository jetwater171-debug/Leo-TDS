// ── Helper: get flow distribution value for a given flow index ──
function getFlowDist(fi) {
    var sel = document.querySelector('.flow-dist[data-fi="' + fi + '"]');
    return sel ? sel.value : 'equal';
}

// ── Helper: check if weights look like equal distribution (differ by at most 1) ──
function looksEqualDistributed(weights) {
    if (weights.length === 0) return true;
    var min = Math.min.apply(null, weights);
    var max = Math.max.apply(null, weights);
    return (max - min) <= 1 && weights.reduce(function(a, b) { return a + b; }, 0) === 100;
}

// ── Helper: distribute 100 equally among count items (largest remainder) ──
function equalWeights(count) {
    if (count <= 0) return [];
    var base = Math.floor(100 / count);
    var remainder = 100 - base * count;
    var result = [];
    for (var i = 0; i < count; i++) {
        result.push(base + (i < remainder ? 1 : 0));
    }
    return result;
}

// ── Helper: redistribute weights for a set of weight inputs after add/remove ──
function redistributeWeights(weightInputs) {
    var count = weightInputs.length;
    if (count === 0) return;
    // Read current weights
    var current = [];
    for (var i = 0; i < count; i++) {
        current.push(parseInt(weightInputs[i].value, 10) || 0);
    }
    // Check if all existing weights (excluding last which is the new empty one) look equal
    var existing = current.slice(0, count - 1);
    var shouldRedistribute = existing.length === 0 || looksEqualDistributed(existing);
    if (shouldRedistribute) {
        var newWeights = equalWeights(count);
        for (var j = 0; j < count; j++) {
            weightInputs[j].value = newWeights[j];
        }
    }
}

// ── Helper: redistribute weights after an item is removed ──
function redistributeWeightsAfterDelete(weightInputs, removedWeight) {
    var count = weightInputs.length;
    if (count === 0) return;
    var current = [];
    for (var i = 0; i < count; i++) {
        current.push(parseInt(weightInputs[i].value, 10) || 0);
    }
    // If remaining weights + removed weight looked equal, redistribute
    var withRemoved = current.concat([removedWeight]);
    if (looksEqualDistributed(withRemoved)) {
        var newWeights = equalWeights(count);
        for (var j = 0; j < count; j++) {
            weightInputs[j].value = newWeights[j];
        }
    }
}

document.addEventListener('DOMContentLoaded', function () {

    // ── Toggle preland folders visibility (delegated) ──
    document.addEventListener('change', function (e) {
        if (!e.target.classList.contains('flow-preland-action')) return;
        var fi = e.target.dataset.fi;
        var el = document.getElementById('flow-preland-folders-' + fi);
        if (el) el.style.display = e.target.value === 'folder' ? 'block' : 'none';
    });

    // ── Toggle land folders/redirects visibility (delegated) ──
    document.addEventListener('change', function (e) {
        if (!e.target.classList.contains('flow-land-action')) return;
        var fi = e.target.dataset.fi;
        var folders = document.getElementById('flow-land-folders-' + fi);
        var redirects = document.getElementById('flow-land-redirects-' + fi);
        if (folders) folders.style.display = e.target.value === 'folder' ? 'block' : 'none';
        if (redirects) redirects.style.display = e.target.value === 'redirect' ? 'block' : 'none';
    });

    // ── Toggle flow-level distribution (thompson opts + weight cols) ──
    document.addEventListener('change', function (e) {
        if (!e.target.classList.contains('flow-dist')) return;
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
    });

    // ── Toggle optimize_mode visibility when preland action changes ──
    document.addEventListener('change', function (e) {
        if (!e.target.classList.contains('flow-preland-action')) return;
        var fi = e.target.dataset.fi;
        var wrap = document.getElementById('flow-optimize-mode-wrap-' + fi);
        if (wrap) wrap.style.display = e.target.value === 'folder' ? 'block' : 'none';
    });

    // ── Delete path items (delegated) ──
    document.addEventListener('click', function (e) {
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
    });

    // ── Helper: build a folder row HTML ──
    function buildFolderRow(type, folderName, showWeight) {
        var label = type === 'preland' ? 'Prelanding folder:' : 'Landing folder:';
        var inputClass = type === 'preland' ? 'flow-preland-folder' : 'flow-land-folder';
        var weightClass = type === 'preland' ? 'flow-preland-weight' : 'flow-land-weight';
        var removeClass = type === 'preland' ? 'flow-remove-preland' : 'flow-remove-land-folder';
        var modeClass = type === 'preland' ? 'flow-preland-mode' : 'flow-land-mode';
        return '<div class="form-group-inner flow-path-item"><div class="row">' +
            '<div class="col-lg-3"><label class="login2 pull-left pull-left-pro">' + label + '</label></div>' +
            '<div class="col-lg-3"><input type="text" class="form-control ' + inputClass + '" value="' + folderName + '" placeholder="folder" readonly /></div>' +
            '<div class="col-lg-2 flow-weight-col" style="display:' + (showWeight ? 'block' : 'none') + '">' +
            '<input type="number" step="1" class="form-control ' + weightClass + '" value="" placeholder="%" style="width:70px" /></div>' +
            '<div class="col-lg-3"><div class="btn-group btn-group-sm">' +
            '<a href="javascript:void(0)" class="btn btn-outline-secondary load-mode-btn ' + modeClass + '" data-mode="base" data-modes="base,direct" title="Loading mode"><i class="bi bi-house-door"></i></a>' +
            '<a href="javascript:void(0)" class="btn btn-warning flow-edit-folder" title="Edit files"><i class="bi bi-pencil-square"></i></a>' +
            '<a href="javascript:void(0)" class="btn btn-danger ' + removeClass + '" title="Delete"><i class="bi bi-trash"></i></a>' +
            '</div></div></div></div>';
    }

    // ── Folder Picker Modal logic ──
    var fpResolve = null;
    var fpFolders = [];

    window.openFolderPicker = openFolderPicker;
    function openFolderPicker(folders) {
        fpFolders = folders;
        var $list = $('#fp-list');
        var $empty = $('#fp-empty');
        var $search = $('#fp-search');
        $search.val('');
        renderFpList(folders, $list, $empty);

        $search.off('input').on('input', function () {
            var q = $(this).val().toLowerCase();
            var filtered = folders.filter(function (f) { return f.toLowerCase().indexOf(q) !== -1; });
            renderFpList(filtered, $list, $empty);
        });

        $('#fp-ok').off('click').on('click', function () {
            var sel = $('input[name=fp-folder]:checked').val();
            $.modal.close();
            if (fpResolve) fpResolve(sel || null);
            fpResolve = null;
        });

        $('#fp-cancel').off('click').on('click', function () {
            $.modal.close();
            if (fpResolve) fpResolve(null);
            fpResolve = null;
        });

        $('#folderPickerModal').modal({
            modalClass: 'ywbmodal',
            fadeDuration: 250,
            fadeDelay: 0.80,
            showClose: false
        });

        return new Promise(function (resolve) { fpResolve = resolve; });
    }

    function renderFpList(folders, $list, $empty) {
        if (!folders.length) {
            $list.html('');
            $empty.show();
            return;
        }
        $empty.hide();
        var html = '';
        folders.forEach(function (f) {
            html += '<label><input type="radio" name="fp-folder" value="' + f + '"> ' + f + '</label>';
        });
        $list.html(html);

        $list.find('label').on('click', function () {
            $list.find('label').removeClass('fp-selected');
            $(this).addClass('fp-selected');
        });
    }

    // ── Add Existing folder (delegated) ──
    document.addEventListener('click', function (e) {
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
                container.insertAdjacentHTML('beforeend', buildFolderRow(type, choice, showWeight));
                if (showWeight) {
                    var weightClass = type === 'preland' ? '.flow-preland-weight' : '.flow-land-weight';
                    redistributeWeights(container.querySelectorAll(weightClass));
                }
            });
        }).catch(function (err) { btn.disabled = false; alert('Error: ' + err); });
    });

    // ── Upload ZIP (bottom button, creates new row) ──
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.flow-upload-zip');
        if (!btn) return;
        var fi = btn.dataset.fi;
        var type = btn.dataset.type;
        if (!fi || !type) return;

        var containerId = type === 'preland' ? 'flow-preland-items-' + fi : 'flow-land-folder-items-' + fi;
        var container = document.getElementById(containerId);
        var showWeight = getFlowDist(fi) === 'weighted';

        // Pick file first (preserves user gesture), then ask for folder name
        var fileInput = document.createElement('input');
        fileInput.type = 'file';
        fileInput.accept = '.zip';
        fileInput.style.display = 'none';
        document.body.appendChild(fileInput);

        fileInput.addEventListener('change', function () {
            if (!fileInput.files.length) { fileInput.remove(); return; }
            var file = fileInput.files[0];

            var folderName = prompt('Enter folder name for the new landing:');
            if (!folderName || !folderName.trim()) { fileInput.remove(); return; }
            folderName = folderName.trim();
            if (!/^[a-zA-Z0-9_\-\.]+$/.test(folderName)) {
                alert('Invalid folder name. Use only letters, numbers, hyphens, underscores, dots.');
                fileInput.remove();
                return;
            }

            var fd = new FormData();
            fd.append('zipfile', file);
            fd.append('folder', folderName);

            btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Uploading...';
            btn.style.pointerEvents = 'none';

            fetch('zipupload.php', { method: 'POST', body: fd })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data.error) {
                        alert('Upload error: ' + data.result);
                    } else {
                        container.insertAdjacentHTML('beforeend', buildFolderRow(type, data.folder, showWeight));
                        if (showWeight) {
                            var weightClass = type === 'preland' ? '.flow-preland-weight' : '.flow-land-weight';
                            redistributeWeights(container.querySelectorAll(weightClass));
                        }
                    }
                })
                .catch(function (err) { alert('Upload failed: ' + err); })
                .finally(function () {
                    btn.innerHTML = '<i class="bi bi-upload"></i> Upload ZIP';
                    btn.style.pointerEvents = '';
                    fileInput.remove();
                });
        });
        fileInput.click();
    });

    // ── Add land redirect (delegated) ──
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.flow-add-land-redirect');
        if (!btn) return;
        var fi = btn.dataset.fi;
        var container = document.getElementById('flow-land-redirect-items-' + fi);
        var showWeight = getFlowDist(fi) === 'weighted';
        var html = '<div class="form-group-inner flow-path-item"><div class="row">' +
            '<div class="col-lg-3"><label class="login2 pull-left pull-left-pro">Redirect URL:</label></div>' +
            '<div class="col-lg-4"><input type="text" class="form-control flow-land-redirect" value="" placeholder="https://..." /></div>' +
            '<div class="col-lg-2 flow-weight-col" style="display:' + (showWeight ? 'block' : 'none') + '">' +
            '<input type="number" step="1" class="form-control flow-land-weight" value="" placeholder="%" style="width:70px" /></div>' +
            '<div class="col-lg-1"><a href="javascript:void(0)" class="btn btn-danger btn-sm flow-remove-land-redirect">✕ Delete</a></div>' +
            '</div></div>';
        container.insertAdjacentHTML('beforeend', html);
        if (showWeight) {
            redistributeWeights(container.querySelectorAll('.flow-land-weight'));
        }
    });

    // ── Add Flow ──
    var flowCounter = document.querySelectorAll('.flow-list-row').length;

    document.getElementById('add-flow-btn').addEventListener('click', function () {
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

        // 3. Add flow section
        var secHtml = '<section id="sec-flow-' + fi + '" class="camp-section flow-section" data-flow-index="' + fi + '">' +
            '<h5 class="flow-section-title">' + flowName + '</h5>' +

            '<div class="flow-group"><span class="flow-group-title">Flow Filters</span>' +
            '<div class="form-group-inner">' +
            '<div class="row"><div id="flow-filters-' + fi + '"></div></div>' +
            '</div></div>' +

            '<div class="flow-group"><span class="flow-group-title">Distribution</span>' +
            '<div class="form-group-inner">' +
            '<select class="form-select flow-dist" data-fi="' + fi + '">' +
            '<option value="equal" selected>Equal</option><option value="weighted">Weighted</option><option value="thompson">Thompson Sampling</option></select></div>' +

            '<div class="flow-thompson-opts" id="flow-thompson-opts-' + fi + '" style="display:none">' +
            '<div class="form-group-inner"><label class="login2 pull-left pull-left-pro">Optimize for:</label>' +
            '<div class="bt-df-checkbox pull-left">' +
            '<div class="row"><div class="col-lg-12"><div class="i-checks pull-left"><label><input type="radio" checked value="Lead" name="flow_' + fi + '_optimize_for" class="flow-optimize-for" data-fi="' + fi + '" /> Lead</label></div></div></div>' +
            '<div class="row"><div class="col-lg-12"><div class="i-checks pull-left"><label><input type="radio" value="Purchase" name="flow_' + fi + '_optimize_for" class="flow-optimize-for" data-fi="' + fi + '" /> Purchase</label></div></div></div>' +
            '</div></div>' +
            '<div class="form-group-inner flow-optimize-mode-wrap" id="flow-optimize-mode-wrap-' + fi + '" style="display:none">' +
            '<label class="login2 pull-left pull-left-pro">Optimize mode:</label>' +
            '<div class="bt-df-checkbox pull-left">' +
            '<div class="row"><div class="col-lg-12"><div class="i-checks pull-left"><label><input type="radio" checked value="funnels" name="flow_' + fi + '_optimize_mode" class="flow-optimize-mode" data-fi="' + fi + '" /> Funnels (preland+land combos)</label></div></div></div>' +
            '<div class="row"><div class="col-lg-12"><div class="i-checks pull-left"><label><input type="radio" value="separate" name="flow_' + fi + '_optimize_mode" class="flow-optimize-mode" data-fi="' + fi + '" /> Separate (independent)</label></div></div></div>' +
            '</div></div></div></div>' +

            '<div class="flow-group"><span class="flow-group-title">Prelanding method</span>' +
            '<div class="form-group-inner">' +
            '<div class="bt-df-checkbox pull-left">' +
            '<div class="row"><div class="col-lg-12"><div class="i-checks pull-left"><label>' +
            '<input type="radio" checked value="none" name="flow_' + fi + '_preland_action" class="flow-preland-action" data-fi="' + fi + '" /> Don\'t use prelanding' +
            '</label></div></div></div>' +
            '<div class="row"><div class="col-lg-12"><div class="i-checks pull-left"><label>' +
            '<input type="radio" value="folder" name="flow_' + fi + '_preland_action" class="flow-preland-action" data-fi="' + fi + '" /> Local prelanding(s) from folder' +
            '</label></div></div></div>' +
            '</div></div>' +

            '<div class="flow-preland-folders" id="flow-preland-folders-' + fi + '" style="display:none">' +
            '<div class="flow-preland-items" id="flow-preland-items-' + fi + '"></div>' +
            '<a href="javascript:void(0)" class="btn btn-primary btn-sm flow-add-existing" data-fi="' + fi + '" data-type="preland"><i class="bi bi-folder-symlink"></i> Add Existing</a> ' +
            '<a href="javascript:void(0)" class="btn btn-info btn-sm flow-upload-zip" data-fi="' + fi + '" data-type="preland"><i class="bi bi-upload"></i> Upload ZIP</a>' +
            '</div></div>' +

            '<div class="flow-group"><span class="flow-group-title">Landing method</span>' +
            '<div class="form-group-inner">' +
            '<div class="bt-df-checkbox pull-left">' +
            '<div class="row"><div class="col-lg-12"><div class="i-checks pull-left"><label>' +
            '<input type="radio" checked value="folder" name="flow_' + fi + '_land_action" class="flow-land-action" data-fi="' + fi + '" /> Local landing(s) from folder' +
            '</label></div></div></div>' +
            '<div class="row"><div class="col-lg-12"><div class="i-checks pull-left"><label>' +
            '<input type="radio" value="redirect" name="flow_' + fi + '_land_action" class="flow-land-action" data-fi="' + fi + '" /> Redirect(s)' +
            '</label></div></div></div>' +
            '</div></div>' +

            '<div class="flow-land-folders" id="flow-land-folders-' + fi + '" style="display:block">' +
            '<div class="flow-land-folder-items" id="flow-land-folder-items-' + fi + '"></div>' +
            '<a href="javascript:void(0)" class="btn btn-primary btn-sm flow-add-existing" data-fi="' + fi + '" data-type="land"><i class="bi bi-folder-symlink"></i> Add Existing</a> ' +
            '<a href="javascript:void(0)" class="btn btn-info btn-sm flow-upload-zip" data-fi="' + fi + '" data-type="land"><i class="bi bi-upload"></i> Upload ZIP</a>' +
            '</div>' +

            '<div class="flow-land-redirects" id="flow-land-redirects-' + fi + '" style="display:none">' +
            '<div class="flow-land-redirect-items" id="flow-land-redirect-items-' + fi + '"></div>' +
            '<a href="javascript:void(0)" class="btn btn-primary btn-sm flow-add-land-redirect" data-fi="' + fi + '">+ Add Redirect</a>' +
            '<div class="form-group-inner" style="margin-top:10px">' +
            '<label class="login2 pull-left pull-left-pro">Redirect type:</label>' +
            '<div class="bt-df-checkbox pull-left">' +
            '<div class="row"><div class="col-lg-12"><div class="i-checks pull-left"><label><input type="radio" checked value="302" name="flow_' + fi + '_redirect_type" class="flow-redirect-type" /> 302</label></div></div></div>' +
            '<div class="row"><div class="col-lg-12"><div class="i-checks pull-left"><label><input type="radio" value="301" name="flow_' + fi + '_redirect_type" class="flow-redirect-type" /> 301</label></div></div></div>' +
            '<div class="row"><div class="col-lg-12"><div class="i-checks pull-left"><label><input type="radio" value="303" name="flow_' + fi + '_redirect_type" class="flow-redirect-type" /> 303</label></div></div></div>' +
            '<div class="row"><div class="col-lg-12"><div class="i-checks pull-left"><label><input type="radio" value="307" name="flow_' + fi + '_redirect_type" class="flow-redirect-type" /> 307</label></div></div></div>' +
            '</div></div></div></div>' +

            '</section>';

        // Insert section before sec-scripts (or at end of camp-content)
        var scriptsSection = document.getElementById('sec-scripts');
        if (scriptsSection) {
            scriptsSection.insertAdjacentHTML('beforebegin', secHtml);
        } else {
            document.querySelector('.camp-content').insertAdjacentHTML('beforeend', secHtml);
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
    });

    // ── Edit folder (delegated) ──
    document.addEventListener('click', function (e) {
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
    });

    // ── Flow list: Move Up ──
    document.addEventListener('click', function (e) {
        if (!e.target.classList.contains('flow-move-up')) return;
        var row = e.target.closest('.flow-list-row');
        var prev = row.previousElementSibling;
        if (prev && prev.classList.contains('flow-list-row')) {
            row.parentNode.insertBefore(row, prev);
        }
    });

    // ── Flow list: Move Down ──
    document.addEventListener('click', function (e) {
        if (!e.target.classList.contains('flow-move-down')) return;
        var row = e.target.closest('.flow-list-row');
        var next = row.nextElementSibling;
        if (next && next.classList.contains('flow-list-row')) {
            row.parentNode.insertBefore(next, row);
        }
    });

    // ── Flow list: Delete ──
    document.addEventListener('click', function (e) {
        if (!e.target.classList.contains('flow-delete')) return;
        if (!confirm('Delete this flow?')) return;
        var row = e.target.closest('.flow-list-row');
        var fi = row.dataset.flowIndex;
        // Remove section
        var sec = document.getElementById('sec-flow-' + fi);
        if (sec) sec.remove();
        // Remove sidebar nav item
        var nav = document.querySelector('.flow-nav-item[data-flow-index="' + fi + '"]');
        if (nav) nav.remove();
        // Remove list row
        row.remove();
    });



});

// ── Collect all flows data as JSON for form submit ──
window.collectFlowsData = function () {
    var flows = [];
    var rows = document.querySelectorAll('.flow-list-row');
    rows.forEach(function (row) {
        var fi = row.dataset.flowIndex;
        var sec = document.getElementById('sec-flow-' + fi);
        if (!sec) return;

        var name = row.querySelector('.flow-name-label').value || 'Flow';

        // Flow filters from QueryBuilder
        var fb = $('#flow-filters-' + fi);
        var filters = {};
        try { filters = fb.queryBuilder('getRules') || {}; } catch (e) {}

        // Prelanding
        var prelandAction = 'none';
        var prelandRadio = sec.querySelector('input.flow-preland-action:checked');
        if (prelandRadio) prelandAction = prelandRadio.value;

        var prelandDist = 'equal';
        var prelandDistSel = sec.querySelector('.flow-preland-dist');
        if (prelandDistSel) prelandDist = prelandDistSel.value;

        var prelandFolders = [];
        var prelandWeights = [];
        var prelandLoadmode = {};
        sec.querySelectorAll('.flow-preland-folder').forEach(function (inp) {
            if (inp.value.trim()) {
                var folder = inp.value.trim();
                prelandFolders.push(folder);
                var weightInp = inp.closest('.flow-path-item').querySelector('.flow-preland-weight');
                prelandWeights.push(parseInt(weightInp ? weightInp.value : 0, 10) || 0);
                var modeBtn = inp.closest('.flow-path-item').querySelector('.flow-preland-mode');
                if (modeBtn) prelandLoadmode[folder] = modeBtn.dataset.mode || 'base';
            }
        });

        // Landing
        var landAction = 'folder';
        var landRadio = sec.querySelector('input.flow-land-action:checked');
        if (landRadio) landAction = landRadio.value;

        var landDist = 'equal';
        var landDistSel = sec.querySelector('.flow-land-dist');
        if (landDistSel) landDist = landDistSel.value;

        var landFolders = [];
        var landRedirectUrls = [];
        var landWeights = [];
        var redirectType = 302;

        var landLoadmode = {};

        if (landAction === 'folder') {
            sec.querySelectorAll('.flow-land-folder').forEach(function (inp) {
                if (inp.value.trim()) {
                    var folder = inp.value.trim();
                    landFolders.push(folder);
                    var w = inp.closest('.flow-path-item').querySelector('.flow-land-weight');
                    landWeights.push(parseInt(w ? w.value : 0, 10) || 0);
                    var modeBtn = inp.closest('.flow-path-item').querySelector('.flow-land-mode');
                    if (modeBtn) landLoadmode[folder] = modeBtn.dataset.mode || 'base';
                }
            });
        } else {
            sec.querySelectorAll('.flow-land-redirect').forEach(function (inp) {
                if (inp.value.trim()) {
                    landRedirectUrls.push(inp.value.trim());
                    var w = inp.closest('.flow-path-item').querySelector('.flow-land-weight');
                    landWeights.push(parseInt(w ? w.value : 0, 10) || 0);
                }
            });
            var rtRadio = sec.querySelector('input.flow-redirect-type:checked');
            if (rtRadio) redirectType = parseInt(rtRadio.value);
        }

        // Flow-level distribution
        var flowDist = 'equal';
        var flowDistSel = sec.querySelector('.flow-dist');
        if (flowDistSel) flowDist = flowDistSel.value;

        var optimizeFor = 'Lead';
        var ofRadio = sec.querySelector('.flow-optimize-for:checked');
        if (ofRadio) optimizeFor = ofRadio.value;

        var optimizeMode = 'funnels';
        var omRadio = sec.querySelector('.flow-optimize-mode:checked');
        if (omRadio) optimizeMode = omRadio.value;

        flows.push({
            name: name,
            filters: filters,
            distribution: flowDist,
            optimize_for: optimizeFor,
            optimize_mode: optimizeMode,
            prelanding: {
                action: prelandAction,
                folders: prelandFolders,
                distribution: prelandDist,
                weights: prelandWeights,
                directload: prelandLoadmode
            },
            landing: {
                action: landAction,
                folders: landFolders,
                redirect: { urls: landRedirectUrls, type: redirectType },
                distribution: landDist,
                weights: landWeights,
                directload: landLoadmode
            }
        });
    });
    return JSON.stringify(flows);
};
