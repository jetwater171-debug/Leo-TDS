"use client";

import React, { useState, useEffect, Suspense } from "react";
import { useSearchParams, useRouter } from "next/navigation";
import { 
  Search, 
  RefreshCw, 
  MapPin, 
  Laptop, 
  Smartphone, 
  Tablet, 
  HelpCircle, 
  ShieldCheck, 
  ShieldAlert, 
  DollarSign, 
  ExternalLink,
  ChevronLeft,
  ChevronRight,
  Loader2,
  Calendar
} from "lucide-react";

interface ClickRow {
  id: number;
  campaign_id: number;
  time: number;
  ip: string;
  country: string;
  lang: string;
  os: string;
  osver: string;
  device: string;
  brand: string;
  model: string;
  client: string;
  clientver: string;
  ua: string;
  userid?: string;
  clickid?: string;
  flow?: string;
  path?: string[];
  step?: number;
  status?: string;
  payout?: number;
  cost?: number;
  reason?: string;
}

function ClicksLogContent() {
  const router = useRouter();
  const searchParams = useSearchParams();
  const initialCampId = searchParams.get("campId") || "";

  // Campaigns list for selector
  const [campaigns, setCampaigns] = useState<any[]>([]);
  const [selectedCampId, setSelectedCampId] = useState(initialCampId);

  // Filter type
  const [activeFilter, setActiveFilter] = useState<"allowed" | "blocked" | "leads" | "trafficback">("allowed");

  // Date range filter
  const [dateRange, setDateRange] = useState("today"); // today, yesterday, 7days, 30days
  const [customDates, setCustomDates] = useState({ start: "", end: "" });

  // Pagination & Search
  const [page, setPage] = useState(1);
  const [pageSize] = useState(20);
  const [totalPages, setTotalPages] = useState(1);
  const [searchTerm, setSearchTerm] = useState("");
  const [searchTermApplied, setSearchTermApplied] = useState("");

  // Data state
  const [clicks, setClicks] = useState<ClickRow[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");

  // Format date helper on the client side to avoid hydration mismatches
  const [mounted, setMounted] = useState(false);

  useEffect(() => {
    setMounted(true);
    // Fetch campaigns for dropdown
    const fetchCampaignsList = async () => {
      try {
        const res = await fetch("/api/campaigns");
        if (res.ok) {
          const data = await res.json();
          setCampaigns(data);
        }
      } catch (err) {
        console.error("Erro ao carregar lista de campanhas:", err);
      }
    };
    fetchCampaignsList();
  }, []);

  // Sync state if query parameters change
  useEffect(() => {
    setSelectedCampId(initialCampId);
    setPage(1);
  }, [initialCampId]);

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

  const fetchLogs = async () => {
    setLoading(true);
    setError("");
    const { start, end } = getTimestamps();
    
    let url = `/api/clicks?filter=${activeFilter}&startdate=${start}&enddate=${end}&page=${page}&size=${pageSize}`;
    
    if (selectedCampId) {
      url += `&campId=${selectedCampId}`;
    }
    
    if (searchTermApplied) {
      url += `&searchTerm=${encodeURIComponent(searchTermApplied)}`;
    }

    try {
      const res = await fetch(url);
      if (!res.ok) {
        throw new Error("Falha ao carregar logs");
      }
      const data = await res.json();
      setClicks(data.data || []);
      setTotalPages(data.last_page || 1);
    } catch (err: any) {
      setError(err.message || "Erro desconhecido ao carregar logs.");
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchLogs();
  }, [activeFilter, selectedCampId, dateRange, customDates, page, searchTermApplied]);

  // Reset page when key filters change
  const handleFilterChange = (filter: "allowed" | "blocked" | "leads" | "trafficback") => {
    setActiveFilter(filter);
    setPage(1);
  };

  const handleCampChange = (campId: string) => {
    setSelectedCampId(campId);
    setPage(1);
    // update url parameter
    const params = new URLSearchParams(window.location.search);
    if (campId) {
      params.set("campId", campId);
    } else {
      params.delete("campId");
    }
    router.replace(`/admin/clicks?${params.toString()}`);
  };

  const handleSearchSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    setSearchTermApplied(searchTerm);
    setPage(1);
  };

  // Device render helper
  const renderDeviceIcon = (device: string) => {
    if (device === "mobile") return <span title="Celular"><Smartphone className="h-4 w-4 text-slate-400" /></span>;
    if (device === "tablet") return <span title="Tablet"><Tablet className="h-4 w-4 text-slate-400" /></span>;
    return <span title="Computador"><Laptop className="h-4 w-4 text-slate-400" /></span>;
  };

  // Date formatter helper
  const formatDate = (unixTimestamp: number) => {
    if (!mounted) return "";
    return new Date(unixTimestamp * 1000).toLocaleString("pt-BR", {
      timeZone: "America/Sao_Paulo",
      day: "2-digit",
      month: "2-digit",
      year: "numeric",
      hour: "2-digit",
      minute: "2-digit",
      second: "2-digit"
    });
  };

  return (
    <div className="space-y-8 animate-fade-in">
      {/* Title */}
      <div>
        <h2 className="admin-title">Logs de Cliques</h2>
        <p className="text-sm text-slate-400">Rastreie acessos, bloqueios e conversões de tráfego em tempo real</p>
      </div>

      {/* Control Panel: Filters, Search, Dropdowns */}
      <div className="glass-card bg-[#0f1426]/60 p-5 rounded-xl border border-white/5 space-y-4">
        <div className="flex flex-col lg:flex-row gap-4 justify-between items-stretch lg:items-center">
          {/* Dropdown selectors */}
          <div className="flex flex-col sm:flex-row gap-3">
            <div className="w-full sm:w-56">
              <label className="block text-[10px] font-semibold uppercase tracking-wider text-slate-500 mb-1.5">
                Filtrar por Campanha
              </label>
              <select
                value={selectedCampId}
                onChange={(e) => handleCampChange(e.target.value)}
                className="input-control h-10 py-1"
              >
                <option value="">Todas as Campanhas</option>
                {campaigns.map((c) => (
                  <option key={c.id} value={c.id}>
                    {c.name} (ID: {c.id})
                  </option>
                ))}
              </select>
            </div>

            <div className="w-full sm:w-56">
              <label className="block text-[10px] font-semibold uppercase tracking-wider text-slate-500 mb-1.5">
                Filtro de Data
              </label>
              <select
                value={dateRange}
                onChange={(e) => setDateRange(e.target.value)}
                className="input-control h-10 py-1"
              >
                <option value="today">Hoje (Fuso SP)</option>
                <option value="yesterday">Ontem</option>
                <option value="7days">Últimos 7 dias</option>
                <option value="30days">Últimos 30 dias</option>
                <option value="custom">Período Personalizado</option>
              </select>
            </div>

            {dateRange === "custom" && (
              <div className="flex items-center gap-2 pt-5">
                <input
                  type="date"
                  value={customDates.start}
                  onChange={(e) => setCustomDates({ ...customDates, start: e.target.value })}
                  className="input-control h-10 py-1 px-2 text-xs w-28"
                />
                <span className="text-slate-500 text-xs">até</span>
                <input
                  type="date"
                  value={customDates.end}
                  onChange={(e) => setCustomDates({ ...customDates, end: e.target.value })}
                  className="input-control h-10 py-1 px-2 text-xs w-28"
                />
              </div>
            )}
          </div>

          {/* Search form */}
          <form onSubmit={handleSearchSubmit} className="flex items-end gap-2 lg:max-w-xs flex-1">
            <div className="relative flex-1">
              <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-500" />
              <input
                type="text"
                placeholder={activeFilter === "blocked" || activeFilter === "trafficback" ? "Buscar por IP..." : "Buscar clickid / userid / IP..."}
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                className="input-control pl-10 h-10"
              />
            </div>
            <button type="submit" className="btn btn-secondary h-10 px-4">
              Filtrar
            </button>
          </form>
        </div>

        {/* Categories Tabs bar */}
        <div className="flex border-t border-white/5 pt-4 gap-2">
          {[
            { key: "allowed", label: "Cliques Aceitos (Black)", color: "text-emerald-400" },
            { key: "blocked", label: "Cliques Bloqueados (White)", color: "text-red-400" },
            { key: "leads", label: "Conversões (Leads)", color: "text-cyan-400" },
            { key: "trafficback", label: "Bypass (Trafficback)", color: "text-amber-400" }
          ].map((tab) => {
            const isActive = activeFilter === tab.key;
            return (
              <button
                key={tab.key}
                onClick={() => handleFilterChange(tab.key as any)}
                className={`px-4 py-2 rounded-lg text-xs font-semibold uppercase tracking-wider transition-all cursor-pointer ${
                  isActive
                    ? "bg-white/5 border border-white/10 text-white font-bold"
                    : "text-slate-500 hover:text-slate-300 hover:bg-white/[0.01]"
                }`}
              >
                <span className={isActive ? tab.color : ""}>{tab.label}</span>
              </button>
            );
          })}
        </div>
      </div>

      {/* Clicks Table Card */}
      <div className="glass-card bg-[#0f1426]/30 p-0 overflow-hidden rounded-xl border border-white/5">
        {loading ? (
          <div className="flex flex-col items-center justify-center py-24 gap-3">
            <Loader2 className="h-8 w-8 text-cyan-400 animate-spin" />
            <span className="text-sm text-slate-400">Carregando logs de clique...</span>
          </div>
        ) : error ? (
          <div className="flex flex-col items-center justify-center py-16 gap-3 text-red-400">
            <span>{error}</span>
            <button onClick={fetchLogs} className="btn btn-secondary text-xs mt-2">
              Tentar Novamente
            </button>
          </div>
        ) : clicks.length === 0 ? (
          <div className="flex flex-col items-center justify-center py-24 gap-2 text-slate-400 text-sm">
            <span>Nenhum log encontrado para os critérios informados.</span>
            <span className="text-xs text-slate-500">Certifique-se de que a campanha possui tráfego ativo.</span>
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-left border-collapse">
              <thead>
                <tr className="border-b border-white/5 bg-white/[0.01] text-xs font-semibold text-slate-400 uppercase tracking-wider">
                  <th className="py-4 px-5">Data/Fuso SP</th>
                  <th className="py-4 px-4">Localização & IP</th>
                  <th className="py-4 px-4">Plataforma (SO/Nav)</th>
                  
                  {activeFilter === "blocked" && (
                    <th className="py-4 px-4">Motivo do Bloqueio</th>
                  )}

                  {activeFilter !== "blocked" && activeFilter !== "trafficback" && (
                    <>
                      <th className="py-4 px-4">Fluxo / Rota</th>
                      <th className="py-4 px-4 text-center">Status / Payout</th>
                    </>
                  )}

                  <th className="py-4 px-5 text-right font-mono">Click ID / User ID</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-white/5 text-sm text-slate-300">
                {clicks.map((row) => (
                  <tr key={row.id} className="hover:bg-white/[0.01] transition-colors">
                    {/* Time */}
                    <td className="py-4 px-5 whitespace-nowrap text-xs text-slate-400 font-mono">
                      {formatDate(row.time)}
                    </td>

                    {/* Geo & IP */}
                    <td className="py-4 px-4">
                      <div className="flex items-center gap-2">
                        <MapPin className="h-3.5 w-3.5 text-cyan-400" />
                        <span className="font-bold text-white uppercase">{row.country}</span>
                        <span className="text-xs text-slate-500 font-mono">({row.lang})</span>
                      </div>
                      <span className="block text-xs font-mono text-slate-400 mt-0.5">{row.ip}</span>
                    </td>

                    {/* Platform */}
                    <td className="py-4 px-4">
                      <div className="flex items-center gap-1.5">
                        {renderDeviceIcon(row.device)}
                        <span className="text-white font-medium">{row.os}</span>
                        <span className="text-xs text-slate-500">v{row.osver}</span>
                      </div>
                      <span className="block text-xs text-slate-400 truncate max-w-xs" title={row.ua}>
                        {row.client} ({row.clientver})
                      </span>
                    </td>

                    {/* Intercept Reason (Only for Blocked) */}
                    {activeFilter === "blocked" && (
                      <td className="py-4 px-4">
                        <span className="px-2 py-0.5 rounded text-xs bg-red-950/20 border border-red-500/20 text-red-400 font-medium">
                          {row.reason}
                        </span>
                      </td>
                    )}

                    {/* Flow & variant (Allowed / Leads) */}
                    {activeFilter !== "blocked" && activeFilter !== "trafficback" && (
                      <td className="py-4 px-4 max-w-xs truncate">
                        <div className="font-medium text-slate-200">{row.flow || "Fluxo Padrão"}</div>
                        <div className="text-xs text-slate-500 font-mono truncate">
                          Passo {Number(row.step) + 1}
                        </div>
                      </td>
                    )}

                    {/* Status / Payout */}
                    {activeFilter !== "blocked" && activeFilter !== "trafficback" && (
                      <td className="py-4 px-4 text-center">
                        {row.status ? (
                          <div className="inline-flex flex-col items-center">
                            <span className="px-2 py-0.5 rounded text-xs bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 font-semibold">
                              {row.status}
                            </span>
                            {Number(row.payout || 0) > 0 && (
                              <span className="text-[10px] text-emerald-400 font-mono font-bold mt-1">
                                +R$ {Number(row.payout).toFixed(2)}
                              </span>
                            )}
                          </div>
                        ) : (
                          <span className="text-xs text-slate-500">Sem conversão</span>
                        )}
                      </td>
                    )}

                    {/* Clickid / Userid */}
                    <td className="py-4 px-5 text-right font-mono text-xs text-slate-500">
                      {row.clickid ? (
                        <>
                          <div className="text-[10px] text-slate-400 hover:text-white select-all cursor-copy">
                            CID: {row.clickid}
                          </div>
                          {row.userid && (
                            <div className="text-[9px] text-slate-600 select-all cursor-copy">
                              UID: {row.userid}
                            </div>
                          )}
                        </>
                      ) : (
                        <span className="text-slate-600 italic">N/A</span>
                      )}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>

      {/* Pagination control footer */}
      {totalPages > 1 && !loading && (
        <div className="flex justify-between items-center bg-[#0f1426]/20 p-4 rounded-xl border border-white/5">
          <span className="text-xs text-slate-400">
            Página <strong className="text-white">{page}</strong> de <strong className="text-white">{totalPages}</strong>
          </span>
          
          <div className="flex gap-2">
            <button
              onClick={() => setPage(Math.max(1, page - 1))}
              disabled={page === 1}
              className="btn btn-secondary py-1 px-3 text-xs flex items-center gap-1 cursor-pointer disabled:opacity-40"
            >
              <ChevronLeft className="h-4 w-4" />
              Anterior
            </button>
            <button
              onClick={() => setPage(Math.min(totalPages, page + 1))}
              disabled={page === totalPages}
              className="btn btn-secondary py-1 px-3 text-xs flex items-center gap-1 cursor-pointer disabled:opacity-40"
            >
              Próximo
              <ChevronRight className="h-4 w-4" />
            </button>
          </div>
        </div>
      )}
    </div>
  );
}

export default function ClicksLogPage() {
  return (
    <Suspense fallback={
      <div className="flex flex-col items-center justify-center py-40 gap-3">
        <Loader2 className="h-8 w-8 text-cyan-400 animate-spin" />
        <span className="text-sm text-slate-400">Preparando painel de logs...</span>
      </div>
    }>
      <ClicksLogContent />
    </Suspense>
  );
}
