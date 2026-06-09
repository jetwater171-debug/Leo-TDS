"use client";

import React, { useState, useEffect } from "react";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { 
  Plus, 
  Search, 
  Trash2, 
  Edit2, 
  FileText, 
  Globe, 
  TrendingUp, 
  Users, 
  Target, 
  DollarSign, 
  Calendar,
  Loader2,
  AlertTriangle
} from "lucide-react";

interface CampaignSummary {
  id: number;
  name: string;
  settings: any;
  clicks: string | number;
  uniques: string | number;
  conversions: string | number;
  revenue: string | number;
  costs: string | number;
}

export default function DashboardPage() {
  const router = useRouter();
  const [campaigns, setCampaigns] = useState<CampaignSummary[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  
  // Date filter state
  const [dateRange, setDateRange] = useState("today"); // today, yesterday, 7days, 30days
  const [customDates, setCustomDates] = useState({ start: "", end: "" });
  
  // New campaign modal state
  const [showNewModal, setShowNewModal] = useState(false);
  const [newCampName, setNewCampName] = useState("");
  const [creating, setCreating] = useState(false);
  
  // Delete confirm state
  const [deleteConfirmId, setDeleteConfirmId] = useState<number | null>(null);
  const [deleting, setDeleting] = useState(false);

  // Search filter
  const [searchQuery, setSearchQuery] = useState("");

  // Calculate start/end timestamps based on dateRange selection
  const getTimestamps = () => {
    const now = new Date();
    const startOfToday = new Date(now.getFullYear(), now.getMonth(), now.getDate());
    
    let start = 0;
    let end = Math.floor(now.getTime() / 1000);

    if (dateRange === "today") {
      start = Math.floor(startOfToday.getTime() / 1000);
    } else if (dateRange === "yesterday") {
      const startOfYesterday = new Date(startOfToday);
      startOfYesterday.setDate(startOfYesterday.getDate() - 1);
      start = Math.floor(startOfYesterday.getTime() / 1000);
      end = Math.floor(startOfToday.getTime() / 1000) - 1;
    } else if (dateRange === "7days") {
      const startOf7DaysAgo = new Date(startOfToday);
      startOf7DaysAgo.setDate(startOf7DaysAgo.getDate() - 7);
      start = Math.floor(startOf7DaysAgo.getTime() / 1000);
    } else if (dateRange === "30days") {
      const startOf30DaysAgo = new Date(startOfToday);
      startOf30DaysAgo.setDate(startOf30DaysAgo.getDate() - 30);
      start = Math.floor(startOf30DaysAgo.getTime() / 1000);
    } else if (dateRange === "custom" && customDates.start && customDates.end) {
      start = Math.floor(new Date(customDates.start + "T00:00:00").getTime() / 1000);
      end = Math.floor(new Date(customDates.end + "T23:59:59").getTime() / 1000);
    }

    return { start, end };
  };

  const fetchCampaigns = async () => {
    setLoading(true);
    setError("");
    const { start, end } = getTimestamps();
    
    try {
      const res = await fetch(`/api/campaigns?startdate=${start}&enddate=${end}`);
      if (!res.ok) {
        throw new Error("Falha ao carregar campanhas");
      }
      const data = await res.json();
      setCampaigns(data);
    } catch (err: any) {
      setError(err.message || "Erro desconhecido ao carregar campanhas");
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchCampaigns();
  }, [dateRange, customDates]);

  // Create Campaign
  const handleCreateCampaign = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!newCampName.trim()) return;
    setCreating(true);

    try {
      const res = await fetch("/api/campaigns", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ name: newCampName }),
      });
      const data = await res.json();
      if (res.ok && data.success) {
        setShowNewModal(false);
        setNewCampName("");
        router.push(`/admin/campaigns/${data.id}`);
      } else {
        alert(data.error || "Erro ao criar campanha");
      }
    } catch (err) {
      alert("Falha de rede ao criar campanha");
    } finally {
      setCreating(false);
    }
  };

  // Delete Campaign
  const handleDeleteCampaign = async (id: number) => {
    setDeleting(true);
    try {
      const res = await fetch(`/api/campaigns?id=${id}`, {
        method: "DELETE",
      });
      if (res.ok) {
        setCampaigns(campaigns.filter((c) => c.id !== id));
        setDeleteConfirmId(null);
      } else {
        alert("Erro ao excluir campanha");
      }
    } catch (err) {
      alert("Erro ao excluir campanha");
    } finally {
      setDeleting(false);
    }
  };

  // Calculate global summary stats
  const totals = campaigns.reduce(
    (acc, curr) => {
      acc.clicks += Number(curr.clicks || 0);
      acc.uniques += Number(curr.uniques || 0);
      acc.conversions += Number(curr.conversions || 0);
      acc.revenue += Number(curr.revenue || 0);
      acc.costs += Number(curr.costs || 0);
      return acc;
    },
    { clicks: 0, uniques: 0, conversions: 0, revenue: 0, costs: 0 }
  );

  const globalProfit = totals.revenue - totals.costs;
  const globalRoi = totals.costs > 0 ? (globalProfit * 100) / totals.costs : 0;
  const globalCR = totals.clicks > 0 ? (totals.conversions * 100) / totals.clicks : 0;

  // Filter campaigns list based on search query
  const filteredCampaigns = campaigns.filter((c) => {
    const q = searchQuery.toLowerCase();
    const matchesName = c.name.toLowerCase().includes(q);
    const settings = typeof c.settings === 'string' ? JSON.parse(c.settings) : c.settings;
    const domains = Array.isArray(settings?.domains) ? settings.domains.join(" ").toLowerCase() : "";
    return matchesName || domains.includes(q);
  });

  return (
    <div className="space-y-8 animate-fade-in">
      {/* Title & Toolbar Header */}
      <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
          <h2 className="admin-title">Painel de Campanhas</h2>
          <p className="text-sm text-slate-400">Gerencie seus fluxos de tráfego e visualize estatísticas de redirecionamento</p>
        </div>

        {/* Action Button */}
        <button
          onClick={() => setShowNewModal(true)}
          className="btn btn-primary h-11"
        >
          <Plus className="h-4 w-4" />
          Nova Campanha
        </button>
      </div>

      {/* Stats Quick Cards Grid */}
      <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div className="glass-card flex items-center gap-4 bg-[#0f1426]/40 p-5 rounded-xl border border-white/5">
          <div className="p-3 bg-cyan-500/10 text-cyan-400 rounded-lg">
            <Users className="h-6 w-6" />
          </div>
          <div>
            <span className="block text-xs font-medium text-slate-400">Total Cliques</span>
            <span className="text-xl font-bold text-white">{totals.clicks}</span>
            <span className="block text-[10px] text-slate-500">{totals.uniques} únicos</span>
          </div>
        </div>

        <div className="glass-card flex items-center gap-4 bg-[#0f1426]/40 p-5 rounded-xl border border-white/5">
          <div className="p-3 bg-indigo-500/10 text-indigo-400 rounded-lg">
            <Target className="h-6 w-6" />
          </div>
          <div>
            <span className="block text-xs font-medium text-slate-400">Conversões</span>
            <span className="text-xl font-bold text-white">{totals.conversions}</span>
            <span className="block text-[10px] text-indigo-400">{globalCR.toFixed(2)}% CR</span>
          </div>
        </div>

        <div className="glass-card flex items-center gap-4 bg-[#0f1426]/40 p-5 rounded-xl border border-white/5">
          <div className="p-3 bg-emerald-500/10 text-emerald-400 rounded-lg">
            <DollarSign className="h-6 w-6" />
          </div>
          <div>
            <span className="block text-xs font-medium text-slate-400">Faturamento</span>
            <span className="text-xl font-bold text-emerald-400">R$ {totals.revenue.toFixed(2)}</span>
            <span className="block text-[10px] text-slate-500">Custo: R$ {totals.costs.toFixed(2)}</span>
          </div>
        </div>

        <div className="glass-card flex items-center gap-4 bg-[#0f1426]/40 p-5 rounded-xl border border-white/5">
          <div className="p-3 bg-amber-500/10 text-amber-400 rounded-lg">
            <TrendingUp className="h-6 w-6" />
          </div>
          <div>
            <span className="block text-xs font-medium text-slate-400">Lucro Líquido</span>
            <span className={`text-xl font-bold ${globalProfit >= 0 ? "text-emerald-400" : "text-red-400"}`}>
              R$ {globalProfit.toFixed(2)}
            </span>
            <span className="block text-[10px] text-amber-400">{globalRoi.toFixed(0)}% ROI</span>
          </div>
        </div>
      </div>

      {/* Filters & Control bar */}
      <div className="glass-card bg-[#0f1426]/60 p-4 rounded-xl border border-white/5 flex flex-col md:flex-row justify-between items-stretch md:items-center gap-4">
        {/* Search */}
        <div className="relative flex-1 max-w-md">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-500" />
          <input
            type="text"
            placeholder="Filtrar por nome ou domínio..."
            value={searchQuery}
            onChange={(e) => setSearchQuery(e.target.value)}
            className="input-control pl-10"
          />
        </div>

        {/* Date Filters */}
        <div className="flex flex-wrap items-center gap-2">
          {["today", "yesterday", "7days", "30days", "custom"].map((range) => (
            <button
              key={range}
              onClick={() => setDateRange(range)}
              className={`btn px-3 py-1.5 text-xs font-medium rounded-lg uppercase ${
                dateRange === range
                  ? "bg-cyan-500/10 border border-cyan-500/30 text-cyan-400"
                  : "bg-white/5 border border-transparent text-slate-400 hover:bg-white/10"
              }`}
            >
              {range === "today" && "Hoje"}
              {range === "yesterday" && "Ontem"}
              {range === "7days" && "7 dias"}
              {range === "30days" && "30 dias"}
              {range === "custom" && "Personalizado"}
            </button>
          ))}

          {dateRange === "custom" && (
            <div className="flex items-center gap-2 ml-2 text-xs">
              <input
                type="date"
                value={customDates.start}
                onChange={(e) => setCustomDates({ ...customDates, start: e.target.value })}
                className="input-control py-1 px-2 text-xs w-28"
              />
              <span className="text-slate-500">até</span>
              <input
                type="date"
                value={customDates.end}
                onChange={(e) => setCustomDates({ ...customDates, end: e.target.value })}
                className="input-control py-1 px-2 text-xs w-28"
              />
            </div>
          )}
        </div>
      </div>

      {/* Main Campaign List Card */}
      <div className="glass-card bg-[#0f1426]/30 p-0 overflow-hidden rounded-xl border border-white/5">
        {loading ? (
          <div className="flex flex-col items-center justify-center py-20 gap-3">
            <Loader2 className="h-8 w-8 text-cyan-400 animate-spin" />
            <span className="text-sm text-slate-400">Carregando dados das campanhas...</span>
          </div>
        ) : error ? (
          <div className="flex flex-col items-center justify-center py-16 gap-3 text-red-400">
            <AlertTriangle className="h-8 w-8" />
            <span>{error}</span>
            <button onClick={fetchCampaigns} className="btn btn-secondary text-xs mt-2">
              Tentar Novamente
            </button>
          </div>
        ) : filteredCampaigns.length === 0 ? (
          <div className="flex flex-col items-center justify-center py-20 gap-3 text-slate-400">
            <Globe className="h-10 w-10 text-slate-600" />
            <span>Nenhuma campanha encontrada.</span>
            {searchQuery && <span className="text-xs text-slate-500">Tente buscar por outro termo.</span>}
            {!searchQuery && (
              <button onClick={() => setShowNewModal(true)} className="btn btn-primary text-xs mt-2">
                Criar Primeira Campanha
              </button>
            )}
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-left border-collapse">
              <thead>
                <tr className="border-b border-white/5 bg-white/[0.01] text-xs font-semibold text-slate-400 uppercase tracking-wider">
                  <th className="py-4 px-6">Campanha</th>
                  <th className="py-4 px-4">Domínios Conectados</th>
                  <th className="py-4 px-4 text-center">Cliques (Únicos)</th>
                  <th className="py-4 px-4 text-center">Conversões (CR)</th>
                  <th className="py-4 px-4 text-right">Faturamento / ROI</th>
                  <th className="py-4 px-6 text-right">Ações</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-white/5 text-sm text-slate-300">
                {filteredCampaigns.map((camp) => {
                  const settings = typeof camp.settings === "string" ? JSON.parse(camp.settings) : camp.settings;
                  const domains: string[] = settings?.domains || [];
                  const profit = Number(camp.revenue || 0) - Number(camp.costs || 0);
                  const roi = Number(camp.costs || 0) > 0 ? (profit * 100) / Number(camp.costs) : 0;
                  const cr = Number(camp.clicks || 0) > 0 ? (Number(camp.conversions || 0) * 100) / Number(camp.clicks) : 0;

                  return (
                    <tr key={camp.id} className="hover:bg-white/[0.02] transition-colors">
                      <td className="py-4 px-6 font-medium text-white">
                        <Link href={`/admin/campaigns/${camp.id}`} className="hover:text-cyan-400 transition-colors block">
                          {camp.name}
                        </Link>
                        <span className="text-[10px] font-mono text-slate-500">ID: {camp.id}</span>
                      </td>
                      <td className="py-4 px-4 max-w-xs truncate">
                        <div className="flex flex-col gap-1">
                          {domains.length === 0 ? (
                            <span className="text-red-400/80 text-xs italic">Nenhum domínio</span>
                          ) : (
                            domains.slice(0, 2).map((dom) => (
                              <span key={dom} className="text-xs bg-slate-500/10 border border-slate-500/20 text-slate-300 px-2 py-0.5 rounded font-mono inline-block w-fit">
                                {dom}
                              </span>
                            ))
                          )}
                          {domains.length > 2 && (
                            <span className="text-[10px] text-slate-500 font-mono">+{domains.length - 2} mais</span>
                          )}
                        </div>
                      </td>
                      <td className="py-4 px-4 text-center">
                        <div className="font-semibold text-white">{camp.clicks}</div>
                        <div className="text-xs text-slate-500 font-mono">{camp.uniques} únicos</div>
                      </td>
                      <td className="py-4 px-4 text-center">
                        <div className="font-semibold text-white">{camp.conversions}</div>
                        <div className="text-xs text-cyan-400 font-mono">{cr.toFixed(2)}% CR</div>
                      </td>
                      <td className="py-4 px-4 text-right">
                        <div className="font-bold text-emerald-400">R$ {Number(camp.revenue || 0).toFixed(2)}</div>
                        <div className="text-[10px] text-slate-400">
                          {roi >= 0 ? "+" : ""}{roi.toFixed(0)}% ROI
                        </div>
                      </td>
                      <td className="py-4 px-6 text-right">
                        <div className="flex justify-end gap-2">
                          <Link
                            href={`/admin/campaigns/${camp.id}`}
                            className="p-2 bg-white/5 border border-white/5 hover:border-cyan-500/30 hover:text-cyan-400 rounded-lg transition-all"
                            title="Editar Configurações"
                          >
                            <Edit2 className="h-4 w-4" />
                          </Link>

                          <Link
                            href={`/admin/clicks?campId=${camp.id}`}
                            className="p-2 bg-white/5 border border-white/5 hover:border-indigo-500/30 hover:text-indigo-400 rounded-lg transition-all"
                            title="Ver Logs de Cliques"
                          >
                            <FileText className="h-4 w-4" />
                          </Link>

                          {deleteConfirmId === camp.id ? (
                            <div className="flex gap-1 items-center bg-red-950/20 border border-red-500/30 rounded-lg p-0.5">
                              <button
                                disabled={deleting}
                                onClick={() => handleDeleteCampaign(camp.id)}
                                className="px-2 py-1 bg-red-600 hover:bg-red-700 text-white rounded text-xs font-semibold"
                              >
                                Sim
                              </button>
                              <button
                                onClick={() => setDeleteConfirmId(null)}
                                className="px-2 py-1 bg-white/5 hover:bg-white/10 text-slate-400 rounded text-xs"
                              >
                                Não
                              </button>
                            </div>
                          ) : (
                            <button
                              onClick={() => setDeleteConfirmId(camp.id)}
                              className="p-2 bg-white/5 border border-white/5 hover:bg-red-500/20 hover:text-red-400 rounded-lg transition-all"
                              title="Excluir Campanha"
                            >
                              <Trash2 className="h-4 w-4" />
                            </button>
                          )}
                        </div>
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </div>
        )}
      </div>

      {/* New Campaign Modal Dialog */}
      {showNewModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
          <div className="glass-card bg-[#0f1426] border border-white/10 w-full max-w-md p-6 rounded-xl shadow-2xl relative">
            <h3 className="text-lg font-bold text-white mb-2">Criar Nova Campanha</h3>
            <p className="text-xs text-slate-400 mb-6">Insira um nome identificador para a campanha de tráfego.</p>

            <form onSubmit={handleCreateCampaign} className="space-y-4">
              <div>
                <label className="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">
                  Nome da Campanha
                </label>
                <input
                  type="text"
                  required
                  value={newCampName}
                  onChange={(e) => setNewCampName(e.target.value)}
                  placeholder="Ex: Campanha Google Ads Fone"
                  className="input-control"
                  disabled={creating}
                  autoFocus
                />
              </div>

              <div className="flex justify-end gap-3 pt-4 border-t border-white/5">
                <button
                  type="button"
                  onClick={() => {
                    setShowNewModal(false);
                    setNewCampName("");
                  }}
                  className="btn btn-secondary h-10 text-xs"
                  disabled={creating}
                >
                  Cancelar
                </button>
                <button
                  type="submit"
                  className="btn btn-primary h-10 text-xs font-semibold"
                  disabled={creating}
                >
                  {creating ? (
                    <>
                      <Loader2 className="h-4 w-4 animate-spin" />
                      Criando...
                    </>
                  ) : (
                    "Criar e Configurar"
                  )}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
}
