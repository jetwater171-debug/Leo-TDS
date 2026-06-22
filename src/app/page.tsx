import Link from "next/link";
import { Activity, ArrowRight, Database, Globe2, LockKeyhole, Radio, ShieldCheck } from "lucide-react";

const checks = [
  { label: "Proxy Next 16", value: "Ativo", icon: Radio },
  { label: "Supabase Postgres", value: "Pronto", icon: Database },
  { label: "Painel Admin", value: "Seguro", icon: LockKeyhole },
  { label: "Rotas de Campanha", value: "Online", icon: Globe2 },
];

export default function Home() {
  return (
    <main className="min-h-screen overflow-hidden bg-[#070a13] text-white">
      <section className="relative flex min-h-screen items-center">
        <div className="absolute inset-0 bg-[radial-gradient(circle_at_18%_18%,rgba(20,184,166,0.22),transparent_34%),radial-gradient(circle_at_82%_16%,rgba(59,130,246,0.18),transparent_30%),linear-gradient(135deg,#070a13_0%,#111827_42%,#07111f_100%)]" />
        <div className="absolute inset-x-0 bottom-0 h-px bg-gradient-to-r from-transparent via-cyan-400/60 to-transparent" />

        <div className="relative mx-auto grid w-full max-w-7xl grid-cols-1 gap-10 px-6 py-12 lg:grid-cols-[1.05fr_0.95fr] lg:px-8">
          <div className="flex flex-col justify-center">
            <div className="mb-8 inline-flex w-fit items-center gap-2 rounded-full border border-cyan-400/20 bg-cyan-400/10 px-3 py-1.5 text-xs font-semibold uppercase tracking-[0.22em] text-cyan-200">
              <ShieldCheck className="h-4 w-4" />
              YellowTDS Next
            </div>

            <h1 className="max-w-4xl text-5xl font-semibold leading-[1.02] tracking-normal text-white md:text-7xl">
              Painel profissional para campanhas, rotas e postbacks.
            </h1>
            <p className="mt-6 max-w-2xl text-base leading-7 text-slate-300 md:text-lg">
              Build migrado para Next.js 16, pronto para Vercel e Supabase, com proxy de campanha,
              logs, painel administrativo, filtros e entrega de landings em pastas versionadas.
            </p>

            <div className="mt-9 flex flex-col gap-3 sm:flex-row">
              <Link href="/admin" className="btn btn-primary h-12 px-6">
                Abrir painel
                <ArrowRight className="h-4 w-4" />
              </Link>
              <Link href="/admin/login" className="btn btn-secondary h-12 px-6">
                Login administrativo
              </Link>
            </div>
          </div>

          <div className="flex items-center">
            <div className="w-full rounded-2xl border border-white/10 bg-white/[0.04] p-5 shadow-2xl backdrop-blur-xl">
              <div className="mb-5 flex items-center justify-between border-b border-white/10 pb-4">
                <div>
                  <p className="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">System Console</p>
                  <h2 className="mt-1 text-xl font-semibold text-white">Deploy readiness</h2>
                </div>
                <div className="rounded-full border border-emerald-400/20 bg-emerald-400/10 px-3 py-1 text-xs font-semibold text-emerald-300">
                  Live
                </div>
              </div>

              <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                {checks.map((item) => {
                  const Icon = item.icon;
                  return (
                    <div key={item.label} className="rounded-xl border border-white/10 bg-[#0f1426]/80 p-4">
                      <Icon className="mb-5 h-5 w-5 text-cyan-300" />
                      <p className="text-sm font-medium text-white">{item.label}</p>
                      <p className="mt-1 text-xs text-slate-400">{item.value}</p>
                    </div>
                  );
                })}
              </div>

              <div className="mt-5 rounded-xl border border-cyan-400/20 bg-cyan-400/10 p-4">
                <div className="flex items-center gap-3">
                  <Activity className="h-5 w-5 text-cyan-200" />
                  <div>
                    <p className="text-sm font-semibold text-white">Arquitetura final</p>
                    <p className="mt-1 text-xs leading-5 text-slate-300">
                      Vercel Proxy + Route Handlers Node + Supabase Postgres + pastas em caching/.
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>
    </main>
  );
}
