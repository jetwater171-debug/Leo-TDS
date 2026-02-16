// ── Clone a <template> by id, return DocumentFragment ──
export function cloneTemplate(id) {
    var tpl = document.getElementById(id);
    if (!tpl) throw new Error('Template not found: ' + id);
    return tpl.content.cloneNode(true);
}

// ── Fill placeholders in a cloned fragment ──
// Replaces data-fi, name attributes containing __FI__, text content, ids, etc.
function fillPlaceholders(fragment, fi, extraReplacements) {
    // Replace __FI__ in all attributes and text
    var walker = document.createTreeWalker(fragment, NodeFilter.SHOW_ELEMENT);
    while (walker.nextNode()) {
        var el = walker.currentNode;
        for (var i = 0; i < el.attributes.length; i++) {
            var attr = el.attributes[i];
            if (attr.value.indexOf('__FI__') !== -1) {
                attr.value = attr.value.replace(/__FI__/g, fi);
            }
        }
    }
    // Extra replacements (key → value in attributes)
    if (extraReplacements) {
        Object.keys(extraReplacements).forEach(function (placeholder) {
            var val = extraReplacements[placeholder];
            var allEls = fragment.querySelectorAll('*');
            allEls.forEach(function (el) {
                for (var i = 0; i < el.attributes.length; i++) {
                    var attr = el.attributes[i];
                    if (attr.value.indexOf(placeholder) !== -1) {
                        attr.value = attr.value.replace(new RegExp(placeholder.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), 'g'), val);
                    }
                }
                // Also replace in text content for labels/titles
                if (el.childNodes.length === 1 && el.childNodes[0].nodeType === 3) {
                    var text = el.childNodes[0].textContent;
                    if (text.indexOf(placeholder) !== -1) {
                        el.childNodes[0].textContent = text.replace(new RegExp(placeholder.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), 'g'), val);
                    }
                }
            });
        });
    }
    return fragment;
}

// ── Build a folder row from template ──
export function buildFolderRow(type, folderName, showWeight) {
    var frag = cloneTemplate('tpl-folder-row');

    // Set type-specific classes and labels
    var label = type === 'preland' ? 'Prelanding folder:' : 'Landing folder:';
    var inputClass = type === 'preland' ? 'flow-preland-folder' : 'flow-land-folder';
    var weightClass = type === 'preland' ? 'flow-preland-weight' : 'flow-land-weight';
    var removeClass = type === 'preland' ? 'flow-remove-preland' : 'flow-remove-land-folder';
    var modeClass = type === 'preland' ? 'flow-preland-mode' : 'flow-land-mode';

    var labelEl = frag.querySelector('[data-role="folder-label"]');
    if (labelEl) labelEl.textContent = label;

    var folderInput = frag.querySelector('[data-role="folder-input"]');
    if (folderInput) {
        folderInput.value = folderName;
        folderInput.className = 'form-control ' + inputClass;
    }

    var weightCol = frag.querySelector('.flow-weight-col');
    if (weightCol) weightCol.style.display = showWeight ? 'block' : 'none';

    var weightInput = frag.querySelector('[data-role="weight-input"]');
    if (weightInput) weightInput.className = 'form-control ' + weightClass;

    var removeBtn = frag.querySelector('[data-role="remove-btn"]');
    if (removeBtn) removeBtn.className = 'btn btn-danger ' + removeClass;

    var modeBtn = frag.querySelector('[data-role="mode-btn"]');
    if (modeBtn) modeBtn.classList.add(modeClass);

    return frag;
}

// ── Build a redirect row from template ──
export function buildRedirectRow(showWeight) {
    var frag = cloneTemplate('tpl-redirect-row');

    var weightCol = frag.querySelector('.flow-weight-col');
    if (weightCol) weightCol.style.display = showWeight ? 'block' : 'none';

    return frag;
}

// ── Build a full flow section from template ──
export function buildFlowSection(fi, flowName) {
    var frag = cloneTemplate('tpl-flow-section');
    fillPlaceholders(frag, fi, { '__FLOWNAME__': flowName });

    // Set the title text
    var title = frag.querySelector('.flow-section-title');
    if (title) title.textContent = flowName;

    return frag;
}
