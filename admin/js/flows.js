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

    // ── Toggle weight columns for preland distribution (delegated) ──
    document.addEventListener('change', function (e) {
        if (!e.target.classList.contains('flow-preland-dist')) return;
        var fi = e.target.dataset.fi;
        var show = e.target.value === 'weighted';
        document.querySelectorAll('#flow-preland-items-' + fi + ' .flow-weight-col').forEach(function (col) {
            col.style.display = show ? 'block' : 'none';
        });
    });

    // ── Toggle weight columns for land distribution (delegated) ──
    document.addEventListener('change', function (e) {
        if (!e.target.classList.contains('flow-land-dist')) return;
        var fi = e.target.dataset.fi;
        var show = e.target.value === 'weighted';
        var section = document.getElementById('sec-flow-' + fi);
        if (!section) return;
        section.querySelectorAll('.flow-weight-col').forEach(function (col) {
            if (!col.closest('.flow-preland-folders')) {
                col.style.display = show ? 'block' : 'none';
            }
        });
    });

    // ── Delete path items (delegated) ──
    document.addEventListener('click', function (e) {
        if (e.target.classList.contains('flow-remove-preland') ||
            e.target.classList.contains('flow-remove-land-folder') ||
            e.target.classList.contains('flow-remove-land-redirect')) {
            var item = e.target.closest('.flow-path-item');
            if (item) item.remove();
        }
    });

    // ── Add preland folder (delegated) ──
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.flow-add-preland');
        if (!btn) return;
        var fi = btn.dataset.fi;
        var container = document.getElementById('flow-preland-items-' + fi);
        var dist = document.querySelector('.flow-preland-dist[data-fi="' + fi + '"]');
        var showWeight = dist && dist.value === 'weighted';
        var html = '<div class="form-group-inner flow-path-item"><div class="row">' +
            '<div class="col-lg-3"><label class="login2 pull-left pull-left-pro">Prelanding folder:</label></div>' +
            '<div class="col-lg-3"><input type="text" class="form-control flow-preland-folder" value="" placeholder="preland1" /></div>' +
            '<div class="col-lg-2 flow-weight-col" style="display:' + (showWeight ? 'block' : 'none') + '">' +
            '<input type="number" class="form-control flow-preland-weight" value="" placeholder="%" style="width:70px" /></div>' +
            '<div class="col-lg-1"><a href="javascript:void(0)" class="btn btn-danger btn-sm flow-remove-preland">Delete</a></div>' +
            '</div></div>';
        container.insertAdjacentHTML('beforeend', html);
    });

    // ── Add land folder (delegated) ──
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.flow-add-land-folder');
        if (!btn) return;
        var fi = btn.dataset.fi;
        var container = document.getElementById('flow-land-folder-items-' + fi);
        var dist = document.querySelector('.flow-land-dist[data-fi="' + fi + '"]');
        var showWeight = dist && dist.value === 'weighted';
        var html = '<div class="form-group-inner flow-path-item"><div class="row">' +
            '<div class="col-lg-3"><label class="login2 pull-left pull-left-pro">Landing folder:</label></div>' +
            '<div class="col-lg-3"><input type="text" class="form-control flow-land-folder" value="" placeholder="land1" /></div>' +
            '<div class="col-lg-2 flow-weight-col" style="display:' + (showWeight ? 'block' : 'none') + '">' +
            '<input type="number" class="form-control flow-land-weight" value="" placeholder="%" style="width:70px" /></div>' +
            '<div class="col-lg-1"><a href="javascript:void(0)" class="btn btn-danger btn-sm flow-remove-land-folder">Delete</a></div>' +
            '</div></div>';
        container.insertAdjacentHTML('beforeend', html);
    });

    // ── Add land redirect (delegated) ──
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.flow-add-land-redirect');
        if (!btn) return;
        var fi = btn.dataset.fi;
        var container = document.getElementById('flow-land-redirect-items-' + fi);
        var dist = document.querySelector('.flow-land-dist[data-fi="' + fi + '"]');
        var showWeight = dist && dist.value === 'weighted';
        var html = '<div class="form-group-inner flow-path-item"><div class="row">' +
            '<div class="col-lg-3"><label class="login2 pull-left pull-left-pro">Redirect URL:</label></div>' +
            '<div class="col-lg-4"><input type="text" class="form-control flow-land-redirect" value="" placeholder="https://..." /></div>' +
            '<div class="col-lg-2 flow-weight-col" style="display:' + (showWeight ? 'block' : 'none') + '">' +
            '<input type="number" class="form-control flow-land-weight" value="" placeholder="%" style="width:70px" /></div>' +
            '<div class="col-lg-1"><a href="javascript:void(0)" class="btn btn-danger btn-sm flow-remove-land-redirect">Delete</a></div>' +
            '</div></div>';
        container.insertAdjacentHTML('beforeend', html);
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
            '<a href="javascript:void(0)" class="btn btn-danger btn-sm flow-delete" title="Delete">Delete</a>' +
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

            '<div class="form-group-inner">' +
            '<label class="login2 pull-left pull-left-pro">Flow Filters:</label>' +
            '<div class="row"><div id="flow-filters-' + fi + '"></div></div>' +
            '</div>' +

            '<div class="form-group-inner">' +
            '<label class="login2 pull-left pull-left-pro">Prelanding method:</label>' +
            '<div class="bt-df-checkbox pull-left">' +
            '<div class="row"><div class="col-lg-12"><div class="i-checks pull-left"><label>' +
            '<input type="radio" checked value="none" name="flow_' + fi + '_preland_action" class="flow-preland-action" data-fi="' + fi + '" /> Don\'t use prelanding' +
            '</label></div></div></div>' +
            '<div class="row"><div class="col-lg-12"><div class="i-checks pull-left"><label>' +
            '<input type="radio" value="folder" name="flow_' + fi + '_preland_action" class="flow-preland-action" data-fi="' + fi + '" /> Local prelanding(s) from folder' +
            '</label></div></div></div>' +
            '</div></div>' +

            '<div class="flow-preland-folders" id="flow-preland-folders-' + fi + '" style="display:none">' +
            '<div class="form-group-inner"><label class="login2 pull-left pull-left-pro">Distribution:</label>' +
            '<select class="form-select flow-preland-dist" data-fi="' + fi + '">' +
            '<option value="equal" selected>Equal</option><option value="weighted">Weighted</option></select></div>' +
            '<div class="flow-preland-items" id="flow-preland-items-' + fi + '"></div>' +
            '<a href="javascript:void(0)" class="btn btn-primary btn-sm flow-add-preland" data-fi="' + fi + '">Add Prelanding</a>' +
            '</div>' +

            '<div class="form-group-inner">' +
            '<label class="login2 pull-left pull-left-pro">Landing method:</label>' +
            '<div class="bt-df-checkbox pull-left">' +
            '<div class="row"><div class="col-lg-12"><div class="i-checks pull-left"><label>' +
            '<input type="radio" checked value="folder" name="flow_' + fi + '_land_action" class="flow-land-action" data-fi="' + fi + '" /> Local landing(s) from folder' +
            '</label></div></div></div>' +
            '<div class="row"><div class="col-lg-12"><div class="i-checks pull-left"><label>' +
            '<input type="radio" value="redirect" name="flow_' + fi + '_land_action" class="flow-land-action" data-fi="' + fi + '" /> Redirect(s)' +
            '</label></div></div></div>' +
            '</div></div>' +

            '<div class="form-group-inner">' +
            '<label class="login2 pull-left pull-left-pro">Distribution:</label>' +
            '<select class="form-select flow-land-dist" data-fi="' + fi + '">' +
            '<option value="equal" selected>Equal</option><option value="weighted">Weighted</option></select></div>' +

            '<div class="flow-land-folders" id="flow-land-folders-' + fi + '" style="display:block">' +
            '<div class="flow-land-folder-items" id="flow-land-folder-items-' + fi + '"></div>' +
            '<a href="javascript:void(0)" class="btn btn-primary btn-sm flow-add-land-folder" data-fi="' + fi + '">Add Landing Folder</a>' +
            '</div>' +

            '<div class="flow-land-redirects" id="flow-land-redirects-' + fi + '" style="display:none">' +
            '<div class="flow-land-redirect-items" id="flow-land-redirect-items-' + fi + '"></div>' +
            '<a href="javascript:void(0)" class="btn btn-primary btn-sm flow-add-land-redirect" data-fi="' + fi + '">Add Redirect</a>' +
            '<div class="form-group-inner" style="margin-top:10px">' +
            '<label class="login2 pull-left pull-left-pro">Redirect type:</label>' +
            '<div class="bt-df-checkbox pull-left">' +
            '<div class="row"><div class="col-lg-12"><div class="i-checks pull-left"><label><input type="radio" checked value="302" name="flow_' + fi + '_redirect_type" class="flow-redirect-type" /> 302</label></div></div></div>' +
            '<div class="row"><div class="col-lg-12"><div class="i-checks pull-left"><label><input type="radio" value="301" name="flow_' + fi + '_redirect_type" class="flow-redirect-type" /> 301</label></div></div></div>' +
            '<div class="row"><div class="col-lg-12"><div class="i-checks pull-left"><label><input type="radio" value="303" name="flow_' + fi + '_redirect_type" class="flow-redirect-type" /> 303</label></div></div></div>' +
            '<div class="row"><div class="col-lg-12"><div class="i-checks pull-left"><label><input type="radio" value="307" name="flow_' + fi + '_redirect_type" class="flow-redirect-type" /> 307</label></div></div></div>' +
            '</div></div></div>' +

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
        sec.querySelectorAll('.flow-preland-folder').forEach(function (inp) {
            if (inp.value.trim()) {
                prelandFolders.push(inp.value.trim());
                var weightInp = inp.closest('.flow-path-item').querySelector('.flow-preland-weight');
                prelandWeights.push(parseFloat(weightInp ? weightInp.value : 0) || 0);
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

        if (landAction === 'folder') {
            sec.querySelectorAll('.flow-land-folder').forEach(function (inp) {
                if (inp.value.trim()) {
                    landFolders.push(inp.value.trim());
                    var w = inp.closest('.flow-path-item').querySelector('.flow-land-weight');
                    landWeights.push(parseFloat(w ? w.value : 0) || 0);
                }
            });
        } else {
            sec.querySelectorAll('.flow-land-redirect').forEach(function (inp) {
                if (inp.value.trim()) {
                    landRedirectUrls.push(inp.value.trim());
                    var w = inp.closest('.flow-path-item').querySelector('.flow-land-weight');
                    landWeights.push(parseFloat(w ? w.value : 0) || 0);
                }
            });
            var rtRadio = sec.querySelector('input.flow-redirect-type:checked');
            if (rtRadio) redirectType = parseInt(rtRadio.value);
        }

        flows.push({
            name: name,
            filters: filters,
            prelanding: {
                action: prelandAction,
                folders: prelandFolders,
                distribution: prelandDist,
                weights: prelandWeights
            },
            landing: {
                action: landAction,
                folders: landFolders,
                redirect: { urls: landRedirectUrls, type: redirectType },
                distribution: landDist,
                weights: landWeights
            }
        });
    });
    return JSON.stringify(flows);
};
