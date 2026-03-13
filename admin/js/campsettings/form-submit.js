// ── Form submit handler ──
document.getElementById("campsettings")?.addEventListener("submit", async (e) => {
    e.preventDefault();

    const urlParams = new URLSearchParams(window.location.search);
    const campId = urlParams.get('campId');
    if (campId === null) {
        alert("No campaign ID found!");
        return false;
    }

    let rules = $('#filtersbuilder').queryBuilder('getRules');
    let flowsJson = window.collectFlowsData ? window.collectFlowsData() : '[]';
    let whiteData = window.collectWhiteData ? window.collectWhiteData() : { folders: [], loadmode: {} };
    let domainsData = window.collectDomainsData ? window.collectDomainsData() : [];
    let dwsData = window.collectDomainSpecificData ? window.collectDomainSpecificData() : [];
    let scriptRules = window.collectScriptRedirectRules ? window.collectScriptRedirectRules() : { next: [], submit: [] };
    let formData = new FormData(document.getElementById("campsettings"));
    let filteredFormData = new FormData();
    for (let [key, value] of formData.entries()) {
        if (!key.startsWith("filtersbuilder") && !key.startsWith("flow_") && !key.startsWith("dws_") && !key.startsWith("scripts.nextredirect.rules") && !key.startsWith("scripts.submitredirect.rules")) {
            filteredFormData.append(key, value);
        }
    }
    filteredFormData.append("filters", JSON.stringify(rules));
    filteredFormData.append("flows", flowsJson);
    filteredFormData.append("white_folders", JSON.stringify(whiteData.folders));
    filteredFormData.append("white_loadmode", JSON.stringify(whiteData.loadmode));
    filteredFormData.append("domains_list", JSON.stringify(domainsData));
    filteredFormData.append("white_domainspecific", JSON.stringify(dwsData));
    filteredFormData.append("scripts_nextredirect_rules_json", JSON.stringify(scriptRules.next));
    filteredFormData.append("scripts_submitredirect_rules_json", JSON.stringify(scriptRules.submit));
    let settingsBody = new URLSearchParams(filteredFormData.entries()).toString();

    let res = await fetch(`campeditor.php?action=save&campId=${campId}`, {
        method: "POST",
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: settingsBody
    });
    let js = await res.json();
    if (js.error)
        alert(`An error occured: ${js.result}`);
    else
        alert("Settings saved!");
    return false;
});
