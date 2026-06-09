"use client";

import React from "react";
import Link from "next/link";
import { usePathname, useRouter } from "next/navigation";
import { 
  LayoutDashboard, 
  ClipboardList, 
  Settings, 
  LogOut, 
  Shield, 
  Radio
} from "lucide-react";

export default function AdminLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  const pathname = usePathname();
  const router = useRouter();

  // Handle Logout
  const handleLogout = async () => {
    try {
      const res = await fetch("/api/auth", { method: "DELETE" });
      if (res.ok) {
        router.push("/admin/login");
        router.refresh();
      }
    } catch (err) {
      console.error("Erro ao fazer logout:", err);
    }
  };

  const menuItems = [
    {
      name: "Painel",
      path: "/admin",
      icon: LayoutDashboard,
    },
    {
      name: "Logs de Cliques",
      path: "/admin/clicks",
      icon: ClipboardList,
    },
  ];

  return (
    <div className="flex min-h-screen bg-[#070a13] font-sans text-slate-100">
      {/* Sidebar navigation */}
      <aside className="w-64 border-r border-white/5 bg-[#0f1426]/80 backdrop-blur-md flex flex-col justify-between p-6">
        <div>
          {/* Logo Brand */}
          <div className="flex items-center gap-3 mb-10 px-2">
            <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-cyan-500/10 border border-cyan-500/20 text-cyan-400">
              <Shield className="h-5 w-5" />
            </div>
            <div>
              <h1 className="font-bold text-lg leading-none tracking-tight text-white">YellowTDS</h1>
              <span className="text-[10px] font-mono text-cyan-400 tracking-wider">CLOAKER // CORE</span>
            </div>
          </div>

          {/* Navigation Links */}
          <nav className="flex flex-col gap-2">
            {menuItems.map((item) => {
              const Icon = item.icon;
              const isActive = pathname === item.path || (item.path !== "/admin" && pathname.startsWith(item.path));
              return (
                <Link
                  key={item.path}
                  href={item.path}
                  className={`flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium transition-all ${
                    isActive
                      ? "bg-gradient-to-r from-cyan-500/10 to-blue-500/10 border border-cyan-500/20 text-cyan-400 shadow-[0_0_15px_rgba(6,182,212,0.05)]"
                      : "border border-transparent text-slate-400 hover:bg-white/5 hover:text-slate-200"
                  }`}
                >
                  <Icon className="h-5 w-5 shrink-0" />
                  {item.name}
                </Link>
              );
            })}
          </nav>
        </div>

        {/* Footer Actions */}
        <div className="flex flex-col gap-4">
          <div className="flex items-center gap-2 px-3 py-2 rounded-lg bg-emerald-500/5 border border-emerald-500/10 text-emerald-400 text-xs font-mono">
            <Radio className="h-4 w-4 animate-pulse shrink-0" />
            <span>Edge Engine Ativa</span>
          </div>

          <button
            onClick={handleLogout}
            className="flex items-center gap-3 px-4 py-3 w-full rounded-lg text-sm font-medium border border-transparent text-red-400 hover:bg-red-500/5 hover:border-red-500/10 transition-all cursor-pointer"
          >
            <LogOut className="h-5 w-5 shrink-0" />
            Sair
          </button>
        </div>
      </aside>

      {/* Main Content Workspace */}
      <main className="flex-1 overflow-y-auto min-h-screen">
        <div className="admin-container">
          {children}
        </div>
      </main>
    </div>
  );
}
