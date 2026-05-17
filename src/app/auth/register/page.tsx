"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";
import Link from "next/link";
import { UserPlus, AlertCircle, ArrowRight, Eye, EyeOff, CheckCircle2 } from "lucide-react";

export default function RegisterPage() {
  const router = useRouter();
  const [username, setUsername] = useState("");
  const [password, setPassword] = useState("");
  const [error, setError] = useState("");
  const [success, setSuccess] = useState(false);
  const [loading, setLoading] = useState(false);
  const [showPassword, setShowPassword] = useState(false);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);
    setError("");
    try {
      const res = await fetch("/api/auth/register", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ username, password }),
      });
      const data = await res.json();
      if (!res.ok) throw new Error(data.error || "Registration failed");
      setSuccess(true);
      setTimeout(() => router.push("/auth/login"), 2000);
    } catch (err: any) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="flex min-h-[75vh] items-center justify-center py-12 px-4">
      <div className="w-full max-w-sm animate-fade-in-up">
        <div className="card-solid rounded-2xl p-8 flex flex-col gap-7">
          <div className="flex flex-col items-center gap-4">
            <div className="h-14 w-14 rounded-xl flex items-center justify-center"
              style={{ background: "linear-gradient(135deg, #4F46E5, #7C3AED)", boxShadow: "0 6px 20px rgba(99,102,241,0.3)" }}>
              <UserPlus className="h-7 w-7 text-white" />
            </div>
            <div className="text-center">
              <h1 className="text-2xl font-extrabold text-slate-900 tracking-tight">Create an account</h1>
              <p className="text-sm mt-1 text-slate-500">Join BidSphere and start bidding</p>
            </div>
          </div>

          <form className="flex flex-col gap-5" onSubmit={handleSubmit}>
            {error && (
              <div className="flex items-start gap-3 p-3 rounded-xl text-sm"
                style={{ background: "rgba(239,68,68,0.07)", border: "1px solid rgba(239,68,68,0.18)", color: "#DC2626" }}>
                <AlertCircle className="h-4 w-4 shrink-0 mt-0.5" /> {error}
              </div>
            )}
            {success && (
              <div className="flex items-start gap-3 p-3 rounded-xl text-sm"
                style={{ background: "rgba(34,197,94,0.07)", border: "1px solid rgba(34,197,94,0.2)", color: "#16A34A" }}>
                <CheckCircle2 className="h-4 w-4 shrink-0 mt-0.5" /> Account created! Redirecting…
              </div>
            )}
            <div className="flex flex-col gap-2">
              <label className="text-sm font-semibold text-slate-700">Username</label>
              <input type="text" required value={username} onChange={e => setUsername(e.target.value)} className="input" placeholder="Choose a username" autoComplete="username" />
            </div>
            <div className="flex flex-col gap-2">
              <label className="text-sm font-semibold text-slate-700">Password</label>
              <div className="relative">
                <input type={showPassword ? "text" : "password"} required value={password} onChange={e => setPassword(e.target.value)} className="input pr-12" placeholder="Create a password" autoComplete="new-password" />
                <button type="button" onClick={() => setShowPassword(!showPassword)} className="absolute right-3 top-1/2 -translate-y-1/2 p-1.5 rounded-lg text-slate-400 hover:text-blue-500 transition-all" tabIndex={-1}>
                  {showPassword ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                </button>
              </div>
            </div>
            <button type="submit" disabled={loading || success} className="btn-primary py-4 text-base mt-1">
              {loading ? "Creating account…" : "Sign Up"} {!loading && !success && <ArrowRight className="h-4 w-4" />}
            </button>
          </form>

          <div className="pt-5 text-center text-sm text-slate-500" style={{ borderTop: "1px solid rgba(37,99,235,0.1)" }}>
            Already have an account?{" "}
            <Link href="/auth/login" className="font-semibold text-blue-600 hover:underline">Sign in</Link>
          </div>
        </div>
      </div>
    </div>
  );
}
