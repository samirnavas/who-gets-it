"use client";

import { useState, useEffect } from "react";
import { pusherClient } from "@/lib/pusher";
import { Timer, User, TrendingUp, AlertCircle } from "lucide-react";
import { useSession } from "next-auth/react";
import { useRouter } from "next/navigation";

// Real-time Next.js Bidding Engine Component
export default function BiddingEngine({ item }: { item: any }) {
  const { data: session, status } = useSession();
  const router = useRouter();

  const [currentBid, setCurrentBid] = useState<number>(parseFloat(item.currentBid));
  const [highestBidder, setHighestBidder] = useState<string>(item.highestBidder?.username || "None");
  const [bidAmount, setBidAmount] = useState<string>("");
  const [isBidding, setIsBidding] = useState(false);
  const [error, setError] = useState<string | null>(null);
  
  // Real-time Socket Setup
  useEffect(() => {
    // Subscribe to the unique channel for this specific auction item
    const channel = pusherClient.subscribe(`auction-${item.id}`);

    // Listen for the "new-bid" event sent from our API Route
    channel.bind("new-bid", (data: any) => {
      setCurrentBid(parseFloat(data.bidAmount));
      setHighestBidder(data.highestBidder);
      
      // Clear any errors if someone else placed a higher bid
      setError(null);
    });

    return () => {
      pusherClient.unsubscribe(`auction-${item.id}`);
    };
  }, [item.id]);

  const handlePlaceBid = async (e: React.FormEvent) => {
    e.preventDefault();
    setError(null);

    // Frontend validation
    if (status !== "authenticated") {
      router.push("/auth/login");
      return;
    }

    const newBid = parseFloat(bidAmount);
    if (isNaN(newBid) || newBid <= currentBid) {
      setError(`Bid must be greater than current bid ($${currentBid.toFixed(2)})`);
      return;
    }

    setIsBidding(true);
    try {
      const response = await fetch("/api/bids", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ itemId: item.id, bidAmount: newBid }),
      });

      const data = await response.json();

      if (!response.ok) {
        throw new Error(data.error || "Failed to place bid");
      }
      
      // Successful bid will trigger the Pusher event, which updates the UI via useEffect!
      setBidAmount("");
    } catch (err: any) {
      setError(err.message);
    } finally {
      setIsBidding(false);
    }
  };

  return (
    <div className="rounded-3xl bg-white p-8 shadow-2xl border border-slate-100 flex flex-col gap-6">
      <div className="flex items-center justify-between border-b border-slate-100 pb-6">
        <div>
          <p className="text-sm font-bold uppercase tracking-wider text-slate-400 mb-1">Current Highest Bid</p>
          <div className="flex items-end gap-2">
            <span className="text-5xl font-black text-slate-900">${currentBid.toFixed(2)}</span>
            <span className="text-slate-500 mb-2 font-medium">USD</span>
          </div>
        </div>
        
        <div className="flex flex-col items-end gap-1 text-right">
          <p className="text-sm font-bold uppercase tracking-wider text-slate-400">Highest Bidder</p>
          <div className="flex items-center gap-2 rounded-full bg-indigo-50 px-4 py-2 text-indigo-700">
            <User className="h-4 w-4" />
            <span className="font-bold">{highestBidder}</span>
          </div>
        </div>
      </div>

      <form onSubmit={handlePlaceBid} className="flex flex-col gap-4">
        {error && (
          <div className="flex items-center gap-2 rounded-xl bg-red-50 p-4 text-sm font-medium text-red-600 border border-red-100">
            <AlertCircle className="h-5 w-5 shrink-0" />
            <p>{error}</p>
          </div>
        )}

        <div className="relative">
          <span className="absolute left-4 top-1/2 -translate-y-1/2 text-2xl font-bold text-slate-400">$</span>
          <input
            type="number"
            step="0.01"
            required
            disabled={isBidding}
            value={bidAmount}
            onChange={(e) => setBidAmount(e.target.value)}
            className="w-full rounded-2xl border-2 border-slate-200 bg-slate-50 py-5 pl-10 pr-6 text-2xl font-bold text-slate-900 transition focus:border-indigo-600 focus:bg-white focus:outline-none focus:ring-4 focus:ring-indigo-600/10 disabled:opacity-50"
            placeholder={(currentBid + 10).toFixed(2)}
          />
        </div>

        <button
          type="submit"
          disabled={isBidding}
          className="group relative flex w-full items-center justify-center gap-3 rounded-2xl bg-indigo-600 py-5 text-lg font-bold text-white shadow-xl shadow-indigo-600/20 transition hover:bg-indigo-700 active:scale-95 disabled:pointer-events-none disabled:bg-slate-300 disabled:shadow-none"
        >
          {isBidding ? (
            <span className="animate-pulse">Placing Bid...</span>
          ) : (
            <>
              <TrendingUp className="h-6 w-6 transition group-hover:-translate-y-1 group-hover:translate-x-1" />
              <span>Place Your Bid</span>
            </>
          )}
        </button>
      </form>

      <div className="mt-4 flex items-center justify-center gap-2 text-sm font-medium text-slate-500">
        <Timer className="h-4 w-4" />
        <p>Bids are legally binding and cannot be retracted.</p>
      </div>
    </div>
  );
}
