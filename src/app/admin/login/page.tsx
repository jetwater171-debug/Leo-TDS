"use client";

import React, { useState, useEffect, useRef } from "react";
import { useRouter } from "next/navigation";
import { Shield, Eye, EyeOff, Loader2 } from "lucide-react";

export default function LoginPage() {
  const router = useRouter();
  const [password, setPassword] = useState("");
  const [showPassword, setShowPassword] = useState(false);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");
  const canvasRef = useRef<HTMLCanvasElement | null>(null);

  // Matrix Rain Effect
  useEffect(() => {
    const canvas = canvasRef.current;
    if (!canvas) return;

    const ctx = canvas.getContext("2d");
    if (!ctx) return;

    // Set canvas dimensions
    const resizeCanvas = () => {
      canvas.width = window.innerWidth;
      canvas.height = window.innerHeight;
    };
    resizeCanvas();
    window.addEventListener("resize", resizeCanvas);

    // Characters definition
    const chars = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZMODULESYSTEMCLOAKYELLOWTDS";
    const charArr = chars.split("");

    const fontSize = 14;
    const columns = Math.floor(canvas.width / fontSize);

    // Drops array
    const drops: number[] = Array(columns).fill(1);

    const draw = () => {
      // Semi-transparent black background to create trail effect
      ctx.fillStyle = "rgba(7, 10, 19, 0.08)";
      ctx.fillRect(0, 0, canvas.width, canvas.height);

      // Green code glow color
      ctx.fillStyle = "#0f0";
      ctx.font = `${fontSize}px monospace`;

      for (let i = 0; i < drops.length; i++) {
        // Random character
        const text = charArr[Math.floor(Math.random() * charArr.length)];
        
        // Slightly random opacity for premium digital feel
        ctx.fillStyle = Math.random() > 0.95 ? "#fff" : "rgba(0, 255, 65, 0.85)";
        
        ctx.fillText(text, i * fontSize, drops[i] * fontSize);

        // Reset drops if they exceed canvas height
        if (drops[i] * fontSize > canvas.height && Math.random() > 0.975) {
          drops[i] = 0;
        }

        // Increment drop Y coordinate
        drops[i]++;
      }
    };

    const interval = setInterval(draw, 33); // ~30 FPS

    return () => {
      clearInterval(interval);
      window.removeEventListener("resize", resizeCanvas);
    };
  }, []);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError("");
    setLoading(true);

    try {
      const res = await fetch("/api/auth", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ password }),
      });

      const data = await res.json();

      if (res.ok && data.success) {
        router.push("/admin");
        router.refresh();
      } else {
        setError(data.msg || "Senha incorreta. Tente novamente.");
      }
    } catch (err) {
      setError("Ocorreu um erro ao tentar conectar com o servidor.");
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="relative flex min-h-screen items-center justify-center overflow-hidden bg-[#070a13] font-sans">
      {/* Matrix Background */}
      <canvas
        ref={canvasRef}
        className="absolute inset-0 block h-full w-full opacity-35"
      />

      {/* Decorative gradient overlay */}
      <div className="absolute inset-0 bg-gradient-to-tr from-[#070a13] via-transparent to-cyan-950/25 pointer-events-none" />

      {/* Login Card Container */}
      <div className="relative w-full max-w-md p-4 animate-fade-in">
        <div className="glass-card flex flex-col items-center border border-white/10 bg-[#0f1426]/70 shadow-2xl backdrop-blur-md rounded-2xl p-8">
          
          {/* Logo / Shield Icon */}
          <div className="mb-6 flex h-14 w-14 items-center justify-center rounded-full bg-cyan-500/10 border border-cyan-500/20 text-cyan-400 shadow-[0_0_15px_rgba(6,182,212,0.2)]">
            <Shield className="h-7 w-7" />
          </div>

          <h2 className="text-2xl font-bold tracking-tight text-white mb-2 font-sans text-center">
            YellowTDS
          </h2>
          <p className="text-sm text-slate-400 mb-8 text-center">
            Área de Acesso Administrativo Restrita
          </p>

          <form onSubmit={handleSubmit} className="w-full flex flex-col gap-5">
            <div>
              <label className="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-2">
                Senha de Acesso
              </label>
              
              <div className="relative">
                <input
                  type={showPassword ? "text" : "password"}
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  className="input-control pr-12 text-center text-lg tracking-widest placeholder:tracking-normal placeholder:text-sm font-mono"
                  placeholder="••••••••••••"
                  required
                  disabled={loading}
                />
                
                <button
                  type="button"
                  onClick={() => setShowPassword(!showPassword)}
                  className="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-white transition-colors"
                  tabIndex={-1}
                >
                  {showPassword ? (
                    <EyeOff className="h-5 w-5" />
                  ) : (
                    <Eye className="h-5 w-5" />
                  )}
                </button>
              </div>
            </div>

            {error && (
              <div className="rounded-lg bg-red-500/10 border border-red-500/20 p-3 text-sm text-red-400 text-center animate-pulse">
                {error}
              </div>
            )}

            <button
              type="submit"
              disabled={loading}
              className="btn btn-primary w-full h-12 text-sm font-semibold uppercase tracking-wider mt-2 flex items-center justify-center gap-2"
            >
              {loading ? (
                <>
                  <Loader2 className="h-5 w-5 animate-spin" />
                  Verificando...
                </>
              ) : (
                "Entrar no Sistema"
              )}
            </button>
          </form>

          {/* Copyright info */}
          <div className="mt-8 text-center text-xs text-slate-500 font-mono">
            v3.0.0-next // vercel_edge
          </div>
        </div>
      </div>
    </div>
  );
}
