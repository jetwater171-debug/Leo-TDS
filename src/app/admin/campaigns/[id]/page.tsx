"use client";

import React, { useState, useEffect } from "react";
import { useRouter } from "next/navigation";
import { 
  ArrowLeft, 
  Save, 
  Plus, 
  Trash2, 
  ShieldAlert, 
  Globe, 
  Folder, 
  Code, 
  Link as LinkIcon, 
  Check, 
  Settings2,
  HelpCircle,
  Loader2,
  Copy,
  ChevronDown
} from "lucide-react";

interface PageProps {
  params: Promise<{ id: string }>;
}

export default function CampaignEditorPage({ params }: PageProps) {
  const router = useRouter();
  const { id: campaignId } = React.use(params);

  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [name, setName] = useState("");
  const [settings, setSettings] = useState<any>(null);
  
  // Navigation tab
  const [activeTab, setActiveTab] = useState("general"); // general, white, black, advanced
  
  // Input helpers
  const [newDomain, setNewDomain] = useState("");
  const [copiedKey, setCopiedKey] = useState(false);

  // Load campaign settings from API
  useEffect(() => {
    const loadCampaign = async () => {
      try {
        const res = await fetch(`/api/campaigns?id=${campaignId}`);
        if (!res.ok) {
          throw new Error("Campanha não encontrada");
        }
        const data = await res.json();
        setName(data.name);
        setSettings(data.settings);
      } catch (err) {
        alert("Erro ao carregar detalhes da campanha.");
        router.push("/admin");
      } finally {
        setLoading(false);
      }
    };
    loadCampaign();
  }, [campaignId, router]);

  // Copy API key utility
  const copyApiKey = () => {
    if (!settings?.apikey) return;
    navigator.clipboard.writeText(settings.apikey);
    setCopiedKey(true);
    setTimeout(() => setCopiedKey(false), 2000);
  };

  // Add domain
  const handleAddDomain = () => {
    if (!newDomain.trim()) return;
    // Basic validation
    const domain = newDomain.trim().toLowerCase();
    if (!settings.domains.includes(domain)) {
      setSettings({
        ...settings,
        domains: [...settings.domains, domain]
      });
    }
    setNewDomain("");
  };

  // Remove domain
  const handleRemoveDomain = (idxToRemove: number) => {
    setSettings({
      ...settings,
      domains: settings.domains.filter((_: any, idx: number) => idx !== idxToRemove)
    });
  };

  // Save campaign settings
  const handleSave = async () => {
    setSaving(true);
    try {
      const res = await fetch("/api/campaigns", {
        method: "PUT",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          id: Number(campaignId),
          name,
          settings
        })
      });
      if (res.ok) {
        alert("Configurações salvas com sucesso!");
      } else {
        const errData = await res.json();
        alert(errData.error || "Erro ao salvar campanha");
      }
    } catch (err) {
      alert("Falha de rede ao salvar configurações");
    } finally {
      setSaving(false);
    }
  };

  // WHITE PAGE SETTINGS UPDATE HELPERS
  const updateWhiteField = (field: string, value: any) => {
    setSettings({
      ...settings,
      white: {
        ...settings.white,
        [field]: value
      }
    });
  };

  const updateWhiteRedirect = (field: string, value: any) => {
    setSettings({
      ...settings,
      white: {
        ...settings.white,
        redirect: {
          ...settings.white.redirect,
          [field]: value
        }
      }
    });
  };

  // Add rule to white filters
  const handleAddWhiteRule = () => {
    const rules = settings.white.filters?.rules || [];
    const newRule = {
      field: "country",
      operator: "is",
      value: "BR",
      id: Math.random().toString(36).substr(2, 9)
    };
    
    setSettings({
      ...settings,
      white: {
        ...settings.white,
        filters: {
          ...settings.white.filters,
          rules: [...rules, newRule]
        }
      }
    });
  };

  // Remove rule from white filters
  const handleRemoveWhiteRule = (id: string) => {
    const rules = settings.white.filters?.rules || [];
    setSettings({
      ...settings,
      white: {
        ...settings.white,
        filters: {
          ...settings.white.filters,
          rules: rules.filter((r: any) => r.id !== id)
        }
      }
    });
  };

  // Update specific white filter rule value
  const handleUpdateWhiteRule = (id: string, field: string, value: any) => {
    const rules = settings.white.filters?.rules || [];
    const updated = rules.map((r: any) => {
      if (r.id === id) {
        return { ...r, [field]: value };
      }
      return r;
    });

    setSettings({
      ...settings,
      white: {
        ...settings.white,
        filters: {
          ...settings.white.filters,
          rules: updated
        }
      }
    });
  };

  // BLACK FLOWS HELPERS
  const handleAddFlow = () => {
    const newFlow = {
      name: `Fluxo ${settings.black.flows.length + 1}`,
      filters: { condition: "AND", rules: [] },
      distribution: "equal",
      optimize_for: "Lead",
      optimize_mode: "funnels",
      steps: [
        {
          action: "redirect",
          folders: [],
          redirect: { urls: [{ url: "https://google.com", label: "google" }], type: 302 },
          weights: []
        }
      ]
    };
    setSettings({
      ...settings,
      black: {
        ...settings.black,
        flows: [...settings.black.flows, newFlow]
      }
    });
  };

  const handleRemoveFlow = (idx: number) => {
    setSettings({
      ...settings,
      black: {
        ...settings.black,
        flows: settings.black.flows.filter((_: any, i: number) => i !== idx)
      }
    });
  };

  const updateFlowField = (flowIdx: number, field: string, value: any) => {
    const updatedFlows = [...settings.black.flows];
    updatedFlows[flowIdx] = {
      ...updatedFlows[flowIdx],
      [field]: value
    };
    setSettings({
      ...settings,
      black: {
        ...settings.black,
        flows: updatedFlows
      }
    });
  };

  // Add rule to flow filters
  const handleAddFlowRule = (flowIdx: number) => {
    const updatedFlows = [...settings.black.flows];
    const rules = updatedFlows[flowIdx].filters.rules || [];
    const newRule = {
      field: "device",
      operator: "is",
      value: "mobile",
      id: Math.random().toString(36).substr(2, 9)
    };
    
    updatedFlows[flowIdx].filters = {
      ...updatedFlows[flowIdx].filters,
      rules: [...rules, newRule]
    };

    setSettings({
      ...settings,
      black: {
        ...settings.black,
        flows: updatedFlows
      }
    });
  };

  // Remove rule from flow filters
  const handleRemoveFlowRule = (flowIdx: number, ruleId: string) => {
    const updatedFlows = [...settings.black.flows];
    const rules = updatedFlows[flowIdx].filters.rules || [];
    
    updatedFlows[flowIdx].filters = {
      ...updatedFlows[flowIdx].filters,
      rules: rules.filter((r: any) => r.id !== ruleId)
    };

    setSettings({
      ...settings,
      black: {
        ...settings.black,
        flows: updatedFlows
      }
    });
  };

  // Update specific flow rule field
  const handleUpdateFlowRule = (flowIdx: number, ruleId: string, field: string, value: any) => {
    const updatedFlows = [...settings.black.flows];
    const rules = updatedFlows[flowIdx].filters.rules || [];
    const updatedRules = rules.map((r: any) => {
      if (r.id === ruleId) {
        return { ...r, [field]: value };
      }
      return r;
    });

    updatedFlows[flowIdx].filters = {
      ...updatedFlows[flowIdx].filters,
      rules: updatedRules
    };

    setSettings({
      ...settings,
      black: {
        ...settings.black,
        flows: updatedFlows
      }
    });
  };

  // Add step to flow
  const handleAddStep = (flowIdx: number) => {
    const updatedFlows = [...settings.black.flows];
    const steps = updatedFlows[flowIdx].steps || [];
    const newStep = {
      action: "redirect",
      folders: [],
      redirect: { urls: [{ url: "https://google.com", label: `url_${steps.length + 1}` }], type: 302 },
      weights: []
    };
    updatedFlows[flowIdx].steps = [...steps, newStep];
    setSettings({
      ...settings,
      black: {
        ...settings.black,
        flows: updatedFlows
      }
    });
  };

  // Remove step from flow
  const handleRemoveStep = (flowIdx: number, stepIdx: number) => {
    const updatedFlows = [...settings.black.flows];
    updatedFlows[flowIdx].steps = updatedFlows[flowIdx].steps.filter((_: any, idx: number) => idx !== stepIdx);
    setSettings({
      ...settings,
      black: {
        ...settings.black,
        flows: updatedFlows
      }
    });
  };

  // Update step values
  const updateStepField = (flowIdx: number, stepIdx: number, field: string, value: any) => {
    const updatedFlows = [...settings.black.flows];
    updatedFlows[flowIdx].steps[stepIdx] = {
      ...updatedFlows[flowIdx].steps[stepIdx],
      [field]: value
    };
    setSettings({
      ...settings,
      black: {
        ...settings.black,
        flows: updatedFlows
      }
    });
  };

  // ADVANCED SETTINGS UPDATE
  const updateAdvancedScripts = (field: string, subField: string, value: any) => {
    setSettings({
      ...settings,
      scripts: {
        ...settings.scripts,
        [field]: {
          ...settings.scripts[field],
          [subField]: value
        }
      }
    });
  };

  const updateAdvancedPostback = (field: string, value: any) => {
    setSettings({
      ...settings,
      postback: {
        ...settings.postback,
        [field]: value
      }
    });
  };

  if (loading) {
    return (
      <div className="flex flex-col items-center justify-center py-40 gap-3">
        <Loader2 className="h-8 w-8 text-cyan-400 animate-spin" />
        <span className="text-sm text-slate-400">Carregando detalhes da campanha...</span>
      </div>
    );
  }

  return (
    <div className="space-y-8 animate-fade-in">
      {/* Header with back button and Save */}
      <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div className="flex items-center gap-3">
          <button
            onClick={() => router.push("/admin")}
            className="p-2.5 bg-[#0f1426]/60 border border-white/5 hover:border-cyan-500/25 rounded-xl text-slate-400 hover:text-white transition-all cursor-pointer"
          >
            <ArrowLeft className="h-5 w-5" />
          </button>
          <div>
            <h2 className="admin-title">{name || "Configurar Campanha"}</h2>
            <p className="text-sm text-slate-400">Edite as regras de direcionamento e filtros de segurança</p>
          </div>
        </div>

        {/* Save button */}
        <button
          onClick={handleSave}
          disabled={saving}
          className="btn btn-primary h-11 px-6 flex items-center gap-2 cursor-pointer"
        >
          {saving ? (
            <Loader2 className="h-4 w-4 animate-spin" />
          ) : (
            <Save className="h-4 w-4" />
          )}
          {saving ? "Salvando..." : "Salvar Campanha"}
        </button>
      </div>

      {/* Tabs list navigation */}
      <div className="flex border-b border-white/5 gap-2">
        {[
          { key: "general", label: "Geral", icon: Settings2 },
          { key: "white", label: "Página Segura (White)", icon: ShieldAlert },
          { key: "black", label: "Fluxos de Oferta (Black)", icon: Globe },
          { key: "advanced", label: "Avançado & S2S", icon: Code }
        ].map((tab) => {
          const TabIcon = tab.icon;
          const isActive = activeTab === tab.key;
          return (
            <button
              key={tab.key}
              onClick={() => setActiveTab(tab.key)}
              className={`flex items-center gap-2 px-6 py-3 border-b-2 text-sm font-medium transition-all cursor-pointer ${
                isActive
                  ? "border-cyan-500 text-cyan-400 bg-white/[0.01]"
                  : "border-transparent text-slate-400 hover:text-slate-200"
              }`}
            >
              <TabIcon className="h-4 w-4" />
              {tab.label}
            </button>
          );
        })}
      </div>

      {/* TAB CONTENT: GENERAL */}
      {activeTab === "general" && (
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
          {/* Main settings column */}
          <div className="lg:col-span-2 space-y-6">
            <div className="glass-card bg-[#0f1426]/40 p-6 rounded-xl border border-white/5 space-y-4">
              <h3 className="text-base font-bold text-white mb-2">Identificação</h3>
              
              <div>
                <label className="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">
                  Nome da Campanha
                </label>
                <input
                  type="text"
                  value={name}
                  onChange={(e) => setName(e.target.value)}
                  className="input-control font-medium"
                  placeholder="Nome de controle"
                />
              </div>

              <div className="pt-2">
                <label className="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">
                  Chave API S2S (Leads e Eventos Externos)
                </label>
                <div className="flex gap-2">
                  <input
                    type="text"
                    readOnly
                    value={settings?.apikey || ""}
                    className="input-control bg-slate-950/20 font-mono text-xs select-all text-slate-300"
                  />
                  <button
                    onClick={copyApiKey}
                    className="btn btn-secondary h-11 px-3 border border-white/5 text-slate-400 hover:text-white"
                  >
                    {copiedKey ? <Check className="h-4 w-4 text-emerald-400" /> : <Copy className="h-4 w-4" />}
                  </button>
                </div>
                <span className="block text-[10px] text-slate-500 mt-1">
                  Esta chave de autenticação é utilizada para registrar conversões externas via URL de Postback.
                </span>
              </div>
            </div>

            {/* Domain Manager */}
            <div className="glass-card bg-[#0f1426]/40 p-6 rounded-xl border border-white/5 space-y-4">
              <div>
                <h3 className="text-base font-bold text-white mb-1">Domínios Conectados</h3>
                <p className="text-xs text-slate-400">Apenas acessos que vierem através destes domínios serão processados pela campanha.</p>
              </div>

              <div className="flex gap-2">
                <input
                  type="text"
                  value={newDomain}
                  onChange={(e) => setNewDomain(e.target.value)}
                  placeholder="ex: meu-site-oferta.com"
                  className="input-control font-mono"
                  onKeyDown={(e) => e.key === "Enter" && (e.preventDefault(), handleAddDomain())}
                />
                <button
                  type="button"
                  onClick={handleAddDomain}
                  className="btn btn-primary h-11 px-4"
                >
                  <Plus className="h-4 w-4" />
                  Adicionar
                </button>
              </div>

              {/* Domains list */}
              <div className="divide-y divide-white/5">
                {settings?.domains?.length === 0 ? (
                  <div className="py-6 text-center text-xs text-slate-500 italic">
                    Nenhum domínio configurado. A campanha não interceptará tráfego.
                  </div>
                ) : (
                  settings?.domains?.map((dom: string, index: number) => (
                    <div key={dom} className="flex justify-between items-center py-3">
                      <span className="font-mono text-sm text-cyan-400">{dom}</span>
                      <button
                        onClick={() => handleRemoveDomain(index)}
                        className="text-red-400/70 hover:text-red-400 p-1 rounded hover:bg-red-500/5 transition-all"
                      >
                        <Trash2 className="h-4 w-4" />
                      </button>
                    </div>
                  ))
                )}
              </div>
            </div>
          </div>

          {/* Quick tips column */}
          <div className="space-y-6">
            <div className="glass-card bg-[#0e172a]/20 border border-white/5 p-6 rounded-xl space-y-4">
              <h4 className="text-xs font-bold uppercase tracking-wider text-cyan-400 flex items-center gap-1.5">
                <HelpCircle className="h-4 w-4" />
                Como funciona?
              </h4>
              <p className="text-xs text-slate-300 leading-relaxed">
                Ao rodar no Vercel, o Edge Middleware escuta as requisições em tempo real. Se o domínio do visitante corresponder à lista, o cloaker é ativado.
              </p>
              <p className="text-xs text-slate-300 leading-relaxed">
                Você pode conectar subdomínios ou usar coringas (Ex: <code>*.meudominio.com</code>) para filtrar páginas de pouso em massa.
              </p>
            </div>
          </div>
        </div>
      )}

      {/* TAB CONTENT: WHITE PAGE */}
      {activeTab === "white" && (
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
          {/* Main Rules Column */}
          <div className="lg:col-span-2 space-y-6">
            {/* White Action configuration */}
            <div className="glass-card bg-[#0f1426]/40 p-6 rounded-xl border border-white/5 space-y-6">
              <div>
                <h3 className="text-base font-bold text-white mb-1">Ação de Tráfego Filtrado</h3>
                <p className="text-xs text-slate-400">O que o robô de moderação, bot ou visitante bloqueado deve visualizar ao acessar?</p>
              </div>

              {/* Action selector */}
              <div className="grid grid-cols-3 gap-3">
                {[
                  { key: "error", label: "Erro HTTP", icon: ShieldAlert },
                  { key: "redirect", label: "Redirecionar URL", icon: LinkIcon },
                  { key: "folder", label: "Exibir Pasta Segura", icon: Folder }
                ].map((act) => {
                  const ActIcon = act.icon;
                  const isActive = settings?.white?.action === act.key;
                  return (
                    <button
                      key={act.key}
                      onClick={() => updateWhiteField("action", act.key)}
                      className={`flex flex-col items-center gap-2 p-4 rounded-xl border text-center transition-all cursor-pointer ${
                        isActive
                          ? "bg-cyan-500/5 border-cyan-500/30 text-cyan-400"
                          : "bg-white/5 border-transparent text-slate-400 hover:text-slate-200"
                      }`}
                    >
                      <ActIcon className="h-5 w-5" />
                      <span className="text-xs font-semibold">{act.label}</span>
                    </button>
                  );
                })}
              </div>

              {/* Detailed inputs depending on action selection */}
              {settings?.white?.action === "error" && (
                <div className="space-y-2">
                  <label className="block text-xs font-semibold uppercase tracking-wider text-slate-400">
                    Código de Erro HTTP
                  </label>
                  <select
                    value={settings.white.errorcodes?.[0] || "404"}
                    onChange={(e) => updateWhiteField("errorcodes", [e.target.value])}
                    className="input-control"
                  >
                    <option value="404">404 Not Found (Recomendado)</option>
                    <option value="403">403 Forbidden</option>
                    <option value="500">500 Internal Server Error</option>
                    <option value="502">502 Bad Gateway</option>
                  </select>
                </div>
              )}

              {settings?.white?.action === "redirect" && (
                <div className="space-y-4">
                  <div>
                    <label className="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">
                      Tipo de Redirecionamento
                    </label>
                    <select
                      value={settings.white.redirect?.type || "302"}
                      onChange={(e) => updateWhiteRedirect("type", e.target.value)}
                      className="input-control"
                    >
                      <option value="302">302 Moved Temporarily (Recomendado)</option>
                      <option value="301">301 Moved Permanently</option>
                    </select>
                  </div>
                  <div>
                    <label className="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">
                      URL de Destino
                    </label>
                    <input
                      type="url"
                      value={settings.white.redirect?.urls?.[0] || ""}
                      onChange={(e) => updateWhiteRedirect("urls", [e.target.value])}
                      placeholder="https://google.com"
                      className="input-control font-mono"
                    />
                  </div>
                </div>
              )}

              {settings?.white?.action === "folder" && (
                <div className="space-y-2">
                  <label className="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">
                    Nome da Pasta Segura (Whites)
                  </label>
                  <input
                    type="text"
                    value={settings.white.folders?.[0] || ""}
                    onChange={(e) => updateWhiteField("folders", [e.target.value])}
                    placeholder="white_site"
                    className="input-control font-mono"
                  />
                  <span className="block text-[10px] text-slate-500 mt-1">
                    Insira o nome da pasta de arquivos estáticos salva no projeto (Ex: <code>white_site</code> serve arquivos dentro de <code>public/whites/white_site/index.html</code>).
                  </span>
                </div>
              )}
            </div>

            {/* Safety Filter Rules Card */}
            <div className="glass-card bg-[#0f1426]/40 p-6 rounded-xl border border-white/5 space-y-4">
              <div className="flex justify-between items-center">
                <div>
                  <h3 className="text-base font-bold text-white mb-1">Regras de Filtragem Ativas</h3>
                  <p className="text-xs text-slate-400">Quais condições classificam um visitante como "Página Segura (White)"?</p>
                </div>
                <button
                  onClick={handleAddWhiteRule}
                  className="btn btn-secondary py-1.5 px-3 text-xs flex items-center gap-1.5"
                >
                  <Plus className="h-4 w-4" />
                  Nova Regra
                </button>
              </div>

              {/* Condition group toggle */}
              {settings?.white?.filters?.rules?.length > 1 && (
                <div className="flex items-center gap-2 p-2 bg-slate-950/20 rounded-lg w-fit">
                  <span className="text-xs text-slate-400 ml-1">Condição entre regras:</span>
                  <select
                    value={settings.white.filters.condition || "AND"}
                    onChange={(e) => {
                      setSettings({
                        ...settings,
                        white: {
                          ...settings.white,
                          filters: {
                            ...settings.white.filters,
                            condition: e.target.value
                          }
                        }
                      });
                    }}
                    className="bg-transparent border-none text-xs text-cyan-400 font-bold focus:outline-none cursor-pointer"
                  >
                    <option value="AND">E (AND)</option>
                    <option value="OR">OU (OR)</option>
                  </select>
                </div>
              )}

              {/* Filters list */}
              <div className="space-y-3 pt-2">
                {settings?.white?.filters?.rules?.length === 0 ? (
                  <div className="py-10 text-center border border-dashed border-white/5 rounded-xl text-slate-500 text-xs">
                    Nenhum filtro personalizado ativo. Todo o tráfego passará para a oferta (Black) se não for detectado por regras automáticas.
                  </div>
                ) : (
                  settings.white.filters.rules.map((rule: any) => (
                    <div key={rule.id} className="flex flex-col sm:flex-row gap-2 items-center bg-[#0a0d17]/50 border border-white/5 p-3 rounded-lg">
                      
                      {/* Field */}
                      <select
                        value={rule.field}
                        onChange={(e) => handleUpdateWhiteRule(rule.id, "field", e.target.value)}
                        className="input-control py-1 px-2 text-xs sm:w-40"
                      >
                        <option value="country">País (GeoIP)</option>
                        <option value="lang">Idioma navegador</option>
                        <option value="os">Sistema Operacional</option>
                        <option value="device">Dispositivo</option>
                        <option value="ua">User-Agent</option>
                        <option value="referer">Referer URL</option>
                        <option value="query_param">Parâmetro URL</option>
                        <option value="vpn_proxy">VPN / Proxy Check</option>
                      </select>

                      {/* Operator */}
                      <select
                        value={rule.operator}
                        onChange={(e) => handleUpdateWhiteRule(rule.id, "operator", e.target.value)}
                        className="input-control py-1 px-2 text-xs sm:w-36"
                        disabled={rule.field === "vpn_proxy"}
                      >
                        {rule.field === "vpn_proxy" ? (
                          <option value="is">Igual a</option>
                        ) : (
                          <>
                            <option value="is">Igual a</option>
                            <option value="is_not">Diferente de</option>
                            <option value="contains">Contém texto</option>
                            <option value="not_contains">Não contém</option>
                            <option value="regex">Expressão Regular</option>
                          </>
                        )}
                      </select>

                      {/* Value selector/input */}
                      {rule.field === "vpn_proxy" ? (
                        <select
                          value={rule.value}
                          onChange={(e) => handleUpdateWhiteRule(rule.id, "value", e.target.value)}
                          className="input-control py-1 px-2 text-xs flex-1"
                        >
                          <option value="true">Detectado VPN/Proxy (Bloquear)</option>
                          <option value="false">Não é VPN/Proxy (Liberar)</option>
                        </select>
                      ) : rule.field === "device" ? (
                        <select
                          value={rule.value}
                          onChange={(e) => handleUpdateWhiteRule(rule.id, "value", e.target.value)}
                          className="input-control py-1 px-2 text-xs flex-1"
                        >
                          <option value="mobile">Mobile (Smartphone)</option>
                          <option value="desktop">Desktop (Computador)</option>
                          <option value="tablet">Tablet</option>
                        </select>
                      ) : (
                        <input
                          type="text"
                          value={rule.value}
                          onChange={(e) => handleUpdateWhiteRule(rule.id, "value", e.target.value)}
                          placeholder="Ex: BR ou facebook.com"
                          className="input-control py-1 px-2 text-xs flex-1 font-mono"
                        />
                      )}

                      {/* Delete */}
                      <button
                        onClick={() => handleRemoveWhiteRule(rule.id)}
                        className="text-red-400 hover:text-red-300 p-1.5 rounded hover:bg-red-500/5 cursor-pointer shrink-0"
                      >
                        <Trash2 className="h-4 w-4" />
                      </button>
                    </div>
                  ))
                )}
              </div>
            </div>
          </div>

          {/* Quick tips column */}
          <div className="space-y-6">
            <div className="glass-card bg-[#0e172a]/20 border border-white/5 p-6 rounded-xl space-y-4">
              <h4 className="text-xs font-bold uppercase tracking-wider text-cyan-400 flex items-center gap-1.5">
                <HelpCircle className="h-4 w-4" />
                Dicas de Filtragem
              </h4>
              <p className="text-xs text-slate-300 leading-relaxed">
                <strong>Bloquear VPN:</strong> A ativação da regra <em>VPN / Proxy Check</em> consulta a API externa na nuvem para analisar se a conexão vem de servidores de hospedagem e proxy residenciais conhecidos de bots.
              </p>
              <p className="text-xs text-slate-300 leading-relaxed">
                <strong>Por País:</strong> Selecione <em>País (GeoIP)</em>, operador <em>Diferente de</em> e digite <strong>BR</strong> para bloquear robôs de outros países que tentem inspecionar seu link.
              </p>
            </div>
          </div>
        </div>
      )}

      {/* TAB CONTENT: BLACK FLOWS */}
      {activeTab === "black" && (
        <div className="space-y-6">
          <div className="flex justify-between items-center">
            <div>
              <h3 className="text-base font-bold text-white mb-1">Canais de Oferta (Fluxos Black)</h3>
              <p className="text-xs text-slate-400">Adicione variantes de tráfego, faça Testes A/B automatizados e organize funis de vendas.</p>
            </div>
            <button
              onClick={handleAddFlow}
              className="btn btn-primary h-10 px-4 flex items-center gap-1.5"
            >
              <Plus className="h-4 w-4" />
              Adicionar Fluxo
            </button>
          </div>

          {/* Flows loop */}
          {settings?.black?.flows?.length === 0 ? (
            <div className="glass-card bg-[#0f1426]/30 py-20 text-center rounded-xl border border-dashed border-white/5 text-slate-500 text-sm">
              Nenhum fluxo de oferta ativo. O tráfego aceito será redirecionado para a URL de TrafficBack geral.
            </div>
          ) : (
            <div className="space-y-6">
              {settings.black.flows.map((flow: any, flowIdx: number) => (
                <div key={flowIdx} className="glass-card bg-[#0f1426]/40 p-6 rounded-xl border border-white/5 space-y-6 relative">
                  
                  {/* Flow Header with Delete */}
                  <div className="flex justify-between items-center border-b border-white/5 pb-4">
                    <div className="flex flex-col sm:flex-row gap-3 items-start sm:items-center">
                      <input
                        type="text"
                        value={flow.name}
                        onChange={(e) => updateFlowField(flowIdx, "name", e.target.value)}
                        className="bg-transparent border-b border-white/10 hover:border-white/30 text-white font-bold text-lg focus:outline-none focus:border-cyan-500 pb-0.5 w-60"
                      />
                      <span className="text-xs text-slate-500 font-mono">Fluxo #{flowIdx + 1}</span>
                    </div>

                    <button
                      onClick={() => handleRemoveFlow(flowIdx)}
                      className="text-red-400 hover:text-red-300 p-2 rounded hover:bg-red-500/5 flex items-center gap-1 text-xs"
                    >
                      <Trash2 className="h-4 w-4" />
                      Remover Fluxo
                    </button>
                  </div>

                  {/* Flow settings row */}
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {/* Distribution strategy */}
                    <div className="space-y-2">
                      <label className="block text-xs font-semibold uppercase tracking-wider text-slate-400">
                        Distribuição da Rota (Testes A/B)
                      </label>
                      <select
                        value={flow.distribution || "equal"}
                        onChange={(e) => updateFlowField(flowIdx, "distribution", e.target.value)}
                        className="input-control"
                      >
                        <option value="equal">Igualitária (Rotativo uniforme)</option>
                        <option value="weighted">Pesos (Definir pesos manualmente)</option>
                        <option value="abtest">Otimização IA (Thompson Sampling com conversões)</option>
                      </select>
                      <span className="block text-[10px] text-slate-500">
                        Determina como os cliques reais serão distribuídos entre as URLs do fluxo.
                      </span>
                    </div>

                    {/* Optimize mode info if IA is active */}
                    {flow.distribution === "abtest" && (
                      <div className="space-y-2">
                        <label className="block text-xs font-semibold uppercase tracking-wider text-slate-400">
                          Otimizar para Conversão do tipo
                        </label>
                        <select
                          value={flow.optimize_for || "Lead"}
                          onChange={(e) => updateFlowField(flowIdx, "optimize_for", e.target.value)}
                          className="input-control"
                        >
                          <option value="Lead">Lead (Adesão de formulários)</option>
                          <option value="Purchase">Purchase (Vendas confirmadas)</option>
                        </select>
                      </div>
                    )}
                  </div>

                  {/* Flow target conditions (Filters to segment user) */}
                  <div className="space-y-4 p-4 bg-slate-950/20 rounded-xl border border-white/5">
                    <div className="flex justify-between items-center">
                      <div>
                        <h4 className="text-xs font-bold text-white">Segmentação do Fluxo</h4>
                        <p className="text-[10px] text-slate-400">Quais visitantes entram especificamente neste fluxo (Ex: tráfego apenas do celular)?</p>
                      </div>
                      <button
                        onClick={() => handleAddFlowRule(flowIdx)}
                        className="btn btn-secondary py-1 px-2.5 text-[10px] flex items-center gap-1"
                      >
                        <Plus className="h-3.5 w-3.5" />
                        Adicionar Condição
                      </button>
                    </div>

                    {/* Rules list */}
                    {flow.filters?.rules?.length === 0 ? (
                      <div className="text-[11px] text-slate-500 italic py-2 text-center">
                        Sem filtros. Qualquer visitante elegível a tráfego real entrará neste fluxo (Fluxo padrão).
                      </div>
                    ) : (
                      <div className="space-y-2">
                        {flow.filters.rules.map((rule: any) => (
                          <div key={rule.id} className="flex gap-2 items-center bg-[#070a13]/30 p-2 rounded border border-white/5">
                            
                            {/* Field */}
                            <select
                              value={rule.field}
                              onChange={(e) => handleUpdateFlowRule(flowIdx, rule.id, "field", e.target.value)}
                              className="input-control py-0.5 px-2 text-xs w-36 h-8"
                            >
                              <option value="country">País (GeoIP)</option>
                              <option value="lang">Idioma navegador</option>
                              <option value="os">Sistema Operacional</option>
                              <option value="device">Dispositivo</option>
                              <option value="ua">User-Agent</option>
                              <option value="referer">Referer URL</option>
                              <option value="query_param">Parâmetro URL</option>
                            </select>

                            {/* Operator */}
                            <select
                              value={rule.operator}
                              onChange={(e) => handleUpdateFlowRule(flowIdx, rule.id, "operator", e.target.value)}
                              className="input-control py-0.5 px-2 text-xs w-28 h-8"
                            >
                              <option value="is">Igual a</option>
                              <option value="is_not">Diferente de</option>
                              <option value="contains">Contém</option>
                              <option value="not_contains">Não contém</option>
                              <option value="regex">Regex</option>
                            </select>

                            {/* Value */}
                            {rule.field === "device" ? (
                              <select
                                value={rule.value}
                                onChange={(e) => handleUpdateFlowRule(flowIdx, rule.id, "value", e.target.value)}
                                className="input-control py-0.5 px-2 text-xs flex-1 h-8"
                              >
                                <option value="mobile">Mobile (Celular)</option>
                                <option value="desktop">Desktop (PC)</option>
                                <option value="tablet">Tablet</option>
                              </select>
                            ) : (
                              <input
                                type="text"
                                value={rule.value}
                                onChange={(e) => handleUpdateFlowRule(flowIdx, rule.id, "value", e.target.value)}
                                placeholder="Valor da regra"
                                className="input-control py-0.5 px-2 text-xs flex-1 font-mono h-8"
                              />
                            )}

                            {/* Delete rule */}
                            <button
                              onClick={() => handleRemoveFlowRule(flowIdx, rule.id)}
                              className="text-red-400 hover:text-red-300 p-1 rounded hover:bg-red-500/5 cursor-pointer"
                            >
                              <Trash2 className="h-3.5 w-3.5" />
                            </button>
                          </div>
                        ))}
                      </div>
                    )}
                  </div>

                  {/* Flow Steps / Targets */}
                  <div className="space-y-4">
                    <div className="flex justify-between items-center">
                      <h4 className="text-xs font-bold uppercase tracking-wider text-slate-400">Passos do Funil (Ofertas)</h4>
                      <button
                        onClick={() => handleAddStep(flowIdx)}
                        className="btn btn-secondary py-1 px-3 text-xs flex items-center gap-1"
                      >
                        <Plus className="h-4 w-4" />
                        Adicionar Passo
                      </button>
                    </div>

                    <div className="space-y-4">
                      {flow.steps.map((step: any, stepIdx: number) => (
                        <div key={stepIdx} className="bg-white/[0.02] border border-white/5 p-4 rounded-xl space-y-4">
                          
                          {/* Step Header */}
                          <div className="flex justify-between items-center">
                            <span className="text-xs font-bold text-cyan-400">Passo {stepIdx + 1}</span>
                            {flow.steps.length > 1 && (
                              <button
                                onClick={() => handleRemoveStep(flowIdx, stepIdx)}
                                className="text-red-400/70 hover:text-red-400 text-[11px] flex items-center gap-1"
                              >
                                <Trash2 className="h-3.5 w-3.5" />
                                Remover Passo
                              </button>
                            )}
                          </div>

                          {/* Step type */}
                          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                              <label className="block text-[11px] font-semibold text-slate-400 mb-1">Ação</label>
                              <select
                                value={step.action || "redirect"}
                                onChange={(e) => updateStepField(flowIdx, stepIdx, "action", e.target.value)}
                                className="input-control py-1 px-2 text-xs"
                              >
                                <option value="redirect">Redirecionar Externamente (URL)</option>
                                <option value="folder">Carregar Pasta Interna (Landing)</option>
                              </select>
                            </div>
                            
                            {step.action === "redirect" && (
                              <div>
                                <label className="block text-[11px] font-semibold text-slate-400 mb-1">Tipo Redirect</label>
                                <select
                                  value={step.redirect?.type || 302}
                                  onChange={(e) => {
                                    const val = Number(e.target.value);
                                    updateStepField(flowIdx, stepIdx, "redirect", {
                                      ...step.redirect,
                                      type: val
                                    });
                                  }}
                                  className="input-control py-1 px-2 text-xs"
                                >
                                  <option value={302}>302 Found (Temporário)</option>
                                  <option value={301}>301 Moved (Permanente)</option>
                                  <option value={307}>307 Temporary Redirect</option>
                                </select>
                              </div>
                            )}
                          </div>

                          {/* Variants list (URLs or Folders) */}
                          <div className="space-y-2">
                            <div className="flex justify-between items-center">
                              <span className="text-[11px] font-bold text-slate-400">Variantes de Destino</span>
                              <button
                                onClick={() => {
                                  if (step.action === "redirect") {
                                    const urls = step.redirect?.urls || [];
                                    const newUrls = [...urls, { url: "https://google.com", label: `v_${urls.length + 1}` }];
                                    updateStepField(flowIdx, stepIdx, "redirect", { ...step.redirect, urls: newUrls });
                                  } else {
                                    const folders = step.folders || [];
                                    const newFolders = [...folders, `folder_${folders.length + 1}`];
                                    updateStepField(flowIdx, stepIdx, "folders", newFolders);
                                  }
                                }}
                                className="text-cyan-400 hover:text-cyan-300 text-[10px] flex items-center gap-1 font-semibold"
                              >
                                <Plus className="h-3.5 w-3.5" /> Adicionar Variante
                              </button>
                            </div>

                            {/* Render items list */}
                            <div className="space-y-2">
                              {step.action === "redirect" ? (
                                step.redirect?.urls?.map((urlItem: any, urlIdx: number) => (
                                  <div key={urlIdx} className="flex gap-2 items-center">
                                    <input
                                      type="text"
                                      value={urlItem.label}
                                      onChange={(e) => {
                                        const updatedUrls = [...step.redirect.urls];
                                        updatedUrls[urlIdx] = { ...updatedUrls[urlIdx], label: e.target.value };
                                        updateStepField(flowIdx, stepIdx, "redirect", { ...step.redirect, urls: updatedUrls });
                                      }}
                                      placeholder="Rótulo"
                                      className="input-control py-1 px-2 text-xs w-28 font-mono h-8"
                                    />
                                    <input
                                      type="url"
                                      value={urlItem.url}
                                      onChange={(e) => {
                                        const updatedUrls = [...step.redirect.urls];
                                        updatedUrls[urlIdx] = { ...updatedUrls[urlIdx], url: e.target.value };
                                        updateStepField(flowIdx, stepIdx, "redirect", { ...step.redirect, urls: updatedUrls });
                                      }}
                                      placeholder="URL de destino"
                                      className="input-control py-1 px-2 text-xs flex-1 font-mono h-8"
                                    />
                                    
                                    {flow.distribution === "weighted" && (
                                      <input
                                        type="number"
                                        value={step.weights?.[urlIdx] ?? 10}
                                        onChange={(e) => {
                                          const weights = [...(step.weights || [])];
                                          weights[urlIdx] = Number(e.target.value);
                                          updateStepField(flowIdx, stepIdx, "weights", weights);
                                        }}
                                        placeholder="Peso"
                                        className="input-control py-1 px-2 text-xs w-16 font-mono text-center h-8"
                                        title="Peso do direcionador"
                                      />
                                    )}

                                    {step.redirect.urls.length > 1 && (
                                      <button
                                        onClick={() => {
                                          const updatedUrls = step.redirect.urls.filter((_: any, idx: number) => idx !== urlIdx);
                                          const weights = (step.weights || []).filter((_: any, idx: number) => idx !== urlIdx);
                                          updateStepField(flowIdx, stepIdx, "redirect", { ...step.redirect, urls: updatedUrls });
                                          updateStepField(flowIdx, stepIdx, "weights", weights);
                                        }}
                                        className="text-red-400 hover:text-red-300 p-1.5"
                                      >
                                        <Trash2 className="h-4 w-4" />
                                      </button>
                                    )}
                                  </div>
                                ))
                              ) : (
                                step.folders?.map((foldItem: string, foldIdx: number) => (
                                  <div key={foldIdx} className="flex gap-2 items-center">
                                    <input
                                      type="text"
                                      value={foldItem}
                                      onChange={(e) => {
                                        const updatedFolders = [...step.folders];
                                        updatedFolders[foldIdx] = e.target.value;
                                        updateStepField(flowIdx, stepIdx, "folders", updatedFolders);
                                      }}
                                      placeholder="Nome da pasta do lander"
                                      className="input-control py-1 px-2 text-xs flex-1 font-mono h-8"
                                    />

                                    {flow.distribution === "weighted" && (
                                      <input
                                        type="number"
                                        value={step.weights?.[foldIdx] ?? 10}
                                        onChange={(e) => {
                                          const weights = [...(step.weights || [])];
                                          weights[foldIdx] = Number(e.target.value);
                                          updateStepField(flowIdx, stepIdx, "weights", weights);
                                        }}
                                        placeholder="Peso"
                                        className="input-control py-1 px-2 text-xs w-16 font-mono text-center h-8"
                                        title="Peso do lander"
                                      />
                                    )}

                                    {step.folders.length > 1 && (
                                      <button
                                        onClick={() => {
                                          const updatedFolders = step.folders.filter((_: any, idx: number) => idx !== foldIdx);
                                          const weights = (step.weights || []).filter((_: any, idx: number) => idx !== foldIdx);
                                          updateStepField(flowIdx, stepIdx, "folders", updatedFolders);
                                          updateStepField(flowIdx, stepIdx, "weights", weights);
                                        }}
                                        className="text-red-400 hover:text-red-300 p-1.5"
                                      >
                                        <Trash2 className="h-4 w-4" />
                                      </button>
                                    )}
                                  </div>
                                ))
                              )}
                            </div>
                          </div>
                        </div>
                      ))}
                    </div>
                  </div>
                </div>
              ))}
            </div>
          )}
        </div>
      )}

      {/* TAB CONTENT: ADVANCED / POSTBACK */}
      {activeTab === "advanced" && (
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
          {/* Postback configurations */}
          <div className="glass-card bg-[#0f1426]/40 p-6 rounded-xl border border-white/5 space-y-6">
            <div>
              <h3 className="text-base font-bold text-white mb-1">Mapeamento de Postback S2S</h3>
              <p className="text-xs text-slate-400">Configure as rotas de callback de vendas para suas plataformas de afiliação.</p>
            </div>

            <div className="space-y-4">
              <div>
                <label className="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">
                  Valor recebido para Lead
                </label>
                <input
                  type="text"
                  value={settings?.postback?.events?.lead || "Lead"}
                  onChange={(e) => {
                    setSettings({
                      ...settings,
                      postback: {
                        ...settings.postback,
                        events: { ...settings.postback.events, lead: e.target.value }
                      }
                    });
                  }}
                  className="input-control font-mono"
                />
              </div>

              <div>
                <label className="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">
                  Valor recebido para Venda Aprovada (Purchase)
                </label>
                <input
                  type="text"
                  value={settings?.postback?.events?.purchase || "Purchase"}
                  onChange={(e) => {
                    setSettings({
                      ...settings,
                      postback: {
                        ...settings.postback,
                        events: { ...settings.postback.events, purchase: e.target.value }
                      }
                    });
                  }}
                  className="input-control font-mono"
                />
              </div>

              <div className="pt-2">
                <h4 className="text-xs font-semibold text-slate-300 mb-2">URL de Postback S2S</h4>
                <div className="p-3 bg-slate-950/40 rounded-lg border border-white/5 text-[11px] font-mono leading-relaxed text-slate-400">
                  <div className="text-slate-300 font-bold mb-1">URL Postback Global:</div>
                  <code>
                    https://{settings?.domains?.[0] || "seu-dominio.com"}/api/postback?apikey={settings?.apikey || "API_KEY"}&clickid=&#123;clickid&#125;&status=&#123;status&#125;&payout=&#123;payout&#125;
                  </code>
                </div>
                <span className="block text-[10px] text-slate-500 mt-2">
                  Adicione a URL acima nas configurações de postback/webhook da Kiwify, PerfectPay, Hotmart, etc. Use os tokens correspondentes de cada plataforma.
                </span>
              </div>
            </div>
          </div>

          {/* Advanced bot checks & scripts */}
          <div className="glass-card bg-[#0f1426]/40 p-6 rounded-xl border border-white/5 space-y-6">
            <div>
              <h3 className="text-base font-bold text-white mb-1">Scripts & Defesas Adicionais</h3>
              <p className="text-xs text-slate-400">Integre rastreamento comportamental de usuários e defesas contra espionagem.</p>
            </div>

            <div className="space-y-6">
              {/* JS Bot Check toggle */}
              <div className="flex justify-between items-center p-3 bg-white/[0.01] border border-white/5 rounded-xl">
                <div>
                  <span className="block text-sm font-semibold text-white">Desafio Javascript (Anti-Spyware)</span>
                  <span className="block text-[10px] text-slate-400 max-w-xs">Exige que o navegador execute cálculos de áudio e fuso horário antes de acessar a página Black.</span>
                </div>
                <label className="relative inline-flex items-center cursor-pointer">
                  <input
                    type="checkbox"
                    checked={settings?.black?.jsbotdetection?.enabled || false}
                    onChange={(e) => {
                      setSettings({
                        ...settings,
                        black: {
                          ...settings.black,
                          jsbotdetection: {
                            ...settings.black.jsbotdetection,
                            enabled: e.target.checked
                          }
                        }
                      });
                    }}
                    className="sr-only peer"
                  />
                  <div className="w-11 h-6 bg-slate-800 rounded-full peer peer-focus:ring-2 peer-focus:ring-cyan-500/25 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-slate-400 after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-cyan-500 peer-checked:after:bg-white" />
                </label>
              </div>

              {/* Advanced Scroll Events logging */}
              <div className="space-y-4">
                <div className="flex justify-between items-center">
                  <div>
                    <span className="block text-sm font-semibold text-white">Rastrear Eventos de Comportamento</span>
                    <span className="block text-[10px] text-slate-400">Grava as porcentagens de rolagem do usuário para validar tráfego humano.</span>
                  </div>
                  <label className="relative inline-flex items-center cursor-pointer">
                    <input
                      type="checkbox"
                      checked={settings?.scripts?.events?.scroll?.use || false}
                      onChange={(e) => updateAdvancedScripts("events", "scroll", {
                        ...settings?.scripts?.events?.scroll,
                        use: e.target.checked
                      })}
                      className="sr-only peer"
                    />
                    <div className="w-11 h-6 bg-slate-800 rounded-full peer peer-focus:ring-2 peer-focus:ring-cyan-500/25 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-slate-400 after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-cyan-500 peer-checked:after:bg-white" />
                  </label>
                </div>
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
