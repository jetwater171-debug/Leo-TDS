// ── Collect prelanding data from a flow section ──
function collectPrelandData(sec) {
    var prelandAction = 'none';
    var prelandRadio = sec.querySelector('input.flow-preland-action:checked');
    if (prelandRadio) prelandAction = prelandRadio.value;

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

    return {
        action: prelandAction,
        folders: prelandFolders,
        distribution: 'equal',
        weights: prelandWeights,
        directload: prelandLoadmode
    };
}

// ── Collect landing data from a flow section ──
function collectLandData(sec) {
    var landAction = 'folder';
    var landRadio = sec.querySelector('input.flow-land-action:checked');
    if (landRadio) landAction = landRadio.value;

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
        var rtSel = sec.querySelector('select.flow-redirect-type');
        if (rtSel) redirectType = parseInt(rtSel.value);
    }

    return {
        action: landAction,
        folders: landFolders,
        redirect: { urls: landRedirectUrls, type: redirectType },
        distribution: 'equal',
        weights: landWeights,
        directload: landLoadmode
    };
}

// ── Collect distribution settings from a flow section ──
function collectDistributionData(sec, fi) {
    var flowDist = 'equal';
    var flowDistSel = sec.querySelector('.flow-dist');
    if (flowDistSel) flowDist = flowDistSel.value;

    var optimizeFor = 'Lead';
    var ofRadio = sec.querySelector('.flow-optimize-for:checked');
    if (ofRadio) optimizeFor = ofRadio.value;

    var optimizeMode = 'funnels';
    var omRadio = sec.querySelector('.flow-optimize-mode:checked');
    if (omRadio) optimizeMode = omRadio.value;

    return {
        distribution: flowDist,
        optimize_for: optimizeFor,
        optimize_mode: optimizeMode
    };
}

// ── Collect all flows data as JSON for form submit ──
export function collectFlowsData() {
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

        var preland = collectPrelandData(sec);
        var land = collectLandData(sec);
        var dist = collectDistributionData(sec, fi);

        flows.push({
            name: name,
            filters: filters,
            distribution: dist.distribution,
            optimize_for: dist.optimize_for,
            optimize_mode: dist.optimize_mode,
            prelanding: preland,
            landing: land
        });
    });
    return JSON.stringify(flows);
}
