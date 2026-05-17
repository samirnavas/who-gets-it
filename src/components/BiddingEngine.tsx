"use client";

import { useState } from "react";
import { Timer, User, TrendingUp, AlertCircle, ShieldAlert } from "lucide-react";
import { useSession } from "next-auth/react";
import { useRouter } from "next/navigation";

export default function BiddingEngine({ item }: { item: any }) {
  const { data: session, status } = useSession();
  const router = useRouter();

  const isOwner = session?.user && (session.user as any).id?.toString() === item.ownerId?.toString();

  const [currentBid, setCurrentBid] = useState<number>(parseFloat(item.currentBid));
  const [highestBidder, setHighestBidder] = useState<string>(item.highestBidder?.username || "None");
  const [bidAmount, setBidAmount] = useState("");
  const [isBidding, setIsBidding] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const handlePlaceBid = async (e: React.FormEvent) => {
    e.preventDefault();
    setError(null);
    if (status !== "authenticated") { router.push("/auth/login"); return; }
    if (isOwner) {
      setError("You cannot place a bid on your own auction listing.");
      return;
    }
    const newBid = parseFloat(bidAmount);
    if (isNaN(newBid) || newBid <= currentBid) {
      setError(`Bid must be greater than $${currentBid.toFixed(2)}`);
      return;
    }
    setIsBidding(true);
    try {
      const res = await fetch("/api/bids", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ itemId: item.id, bidAmount: newBid }),
      });
      const data = await res.json();
      if (!res.ok) throw new Error(data.error || "Failed to place bid");
      setCurrentBid(newBid);
      setHighestBidder(session?.user?.name || "You");
      setBidAmount("");
      router.refresh();
    } catch (err: any) {
      setError(err.message);
    } finally {
      setIsBidding(false);
    }
  };

  return (
    <div className="card-solid rounded-2xl p-6 sm:p-7 flex flex-col gap-6">
      {/* Current bid */}
      <div className="flex flex-col gap-3.5 pb-5" style={{ borderBottom: "1px solid rgba(37,99,235,0.1)" }}>
        <div>
          <p className="text-[10px] font-bold uppercase tracking-widest mb-1.5 text-blue-500">Current Bid</p>
          <div className="flex items-baseline gap-2">
            <span className="text-4xl font-extrabold text-slate-900">${currentBid.toFixed(2)}</span>
            <span className="text-sm text-slate-400">USD</span>
          </div>
        </div>
        
        {/* Highest bidder info moved to own line to accommodate long usernames/emails cleanly */}
        <div className="flex items-center justify-between text-xs mt-1">
          <span className="text-slate-400 font-semibold uppercase tracking-wider text-[9px]">Highest Bidder</span>
          <div
            className="flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg font-bold text-xs max-w-[200px]"
            style={{ background: "rgba(37,99,235,0.06)", border: "1px solid rgba(37,99,235,0.12)", color: "#2563EB" }}
          >
            <User className="h-3.5 w-3.5" />
            <span className="truncate">@{highestBidder}</span>
          </div>
        </div>
      </div>

      {status === "unauthenticated" ? (
        <div className="flex flex-col gap-4 text-center py-3">
          <p className="text-sm text-slate-500">You must be logged in to bid.</p>
          <button onClick={() => router.push(`/auth/login?callbackUrl=/auction/${item.id}`)} className="btn-primary py-4 text-base">
            <User className="h-5 w-5" /> Login to Bid
          </button>
        </div>
      ) : isOwner ? (
        <div className="flex flex-col items-center gap-3 text-center py-5 px-3 rounded-2xl"
          style={{ background: "rgba(239,68,68,0.04)", border: "1px dashed rgba(239,68,68,0.2)" }}>
          <ShieldAlert className="h-8 w-8 text-rose-500 animate-pulse" />
          <p className="text-sm font-semibold text-slate-700">This is your auction listing.</p>
          <p className="text-xs text-slate-500">You are the creator of this item and are not allowed to place bids on it.</p>
        </div>
      ) : (
        <form onSubmit={handlePlaceBid} className="flex flex-col gap-5">
          {error && (
            <div className="flex items-start gap-3 p-3 rounded-xl text-sm"
              style={{ background: "rgba(239,68,68,0.07)", border: "1px solid rgba(239,68,68,0.18)", color: "#DC2626" }}>
              <AlertCircle className="h-4 w-4 shrink-0 mt-0.5" />
              {error}
            </div>
          )}
          <div>
            <label className="block text-sm font-semibold mb-2 text-slate-600">Your Bid Amount</label>
            <div className="relative">
              <span className="absolute left-4 top-1/2 -translate-y-1/2 text-lg font-bold text-blue-400">$</span>
              <input
                type="number"
                step="0.01"
                required
                disabled={isBidding}
                value={bidAmount}
                onChange={e => setBidAmount(e.target.value)}
                className="input pl-10 text-lg font-bold"
                placeholder={(currentBid + 10).toFixed(2)}
              />
            </div>
            <p className="text-xs mt-1.5 px-1 text-slate-400">Minimum: ${(currentBid + 0.01).toFixed(2)}</p>
          </div>
          <button type="submit" disabled={isBidding} className="btn-primary py-4 text-base">
            {isBidding ? <span className="animate-pulse">Placing Bid…</span> : <><TrendingUp className="h-5 w-5" /> Place Your Bid</>}
          </button>
        </form>
      )}

      <div className="flex items-center justify-center gap-1.5 pt-3 text-xs text-slate-400"
        style={{ borderTop: "1px solid rgba(37,99,235,0.08)" }}>
        <Timer className="h-3.5 w-3.5" /> Bids are binding and cannot be retracted.
      </div>
    </div>
  );
}
