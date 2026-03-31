"use client";

import { useState } from "react";
import { Timer, User, TrendingUp, AlertCircle } from "lucide-react";
import { useSession } from "next-auth/react";
import { useRouter } from "next/navigation";

export default function BiddingEngine({ item }: { item: any }) {
  const { data: session, status } = useSession();
  const router = useRouter();

  const [currentBid, setCurrentBid] = useState<number>(parseFloat(item.currentBid));
  const [highestBidder, setHighestBidder] = useState<string>(item.highestBidder?.username || "None");
  const [bidAmount, setBidAmount] = useState<string>("");
  const [isBidding, setIsBidding] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const handlePlaceBid = async (e: React.FormEvent) => {
    e.preventDefault();
    setError(null);

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

  const suggestedBid = currentBid + 10;

  return (
    <div className="card p-6 flex flex-col gap-5 animate-fade-in-up">
      {/* Current bid display */}
      <div
        className="flex items-center justify-between pb-5"
        style={{ borderBottom: "1px solid var(--border)" }}
      >
        <div>
          <p className="text-xs font-semibold uppercase tracking-wider mb-1" style={{ color: "var(--text-muted)" }}>
            Current Bid
          </p>
          <div className="flex items-baseline gap-1.5">
            <span className="text-3xl font-bold" style={{ color: "var(--text-primary)" }}>
              ${currentBid.toFixed(2)}
            </span>
            <span className="text-sm font-medium" style={{ color: "var(--text-muted)" }}>USD</span>
          </div>
        </div>

        <div className="flex flex-col items-end gap-1.5">
          <p className="text-xs font-semibold uppercase tracking-wider" style={{ color: "var(--text-muted)" }}>
            Top Bidder
          </p>
          <div
            className="flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-semibold"
            style={{
              backgroundColor: "rgba(91, 106, 191, 0.08)",
              color: "var(--color-primary)",
            }}
          >
            <User className="h-3.5 w-3.5" />
            <span>{highestBidder}</span>
          </div>
        </div>
      </div>

      {/* Bid form or Login Prompt */}
      {status === "unauthenticated" ? (
        <div className="flex flex-col gap-3 py-2">
          <div className="alert alert-error bg-blue-50/50 text-blue-800 border-blue-200 dark:bg-blue-900/20 dark:text-blue-300 dark:border-blue-900/30">
            <AlertCircle className="h-4 w-4 shrink-0 mt-0.5" />
            <p className="text-sm">You must be logged in to participate in this auction.</p>
          </div>
          <button
            type="button"
            onClick={() => router.push("/auth/login")}
            className="btn btn-primary btn-lg w-full"
          >
            <User className="h-5 w-5" />
            <span>Login to Bid</span>
          </button>
        </div>
      ) : (
        <form onSubmit={handlePlaceBid} className="flex flex-col gap-4">
          {error && (
            <div className="alert alert-error">
              <AlertCircle className="h-4 w-4 shrink-0 mt-0.5" />
              <p>{error}</p>
            </div>
          )}

          <div>
            <label
              className="block text-sm font-medium mb-2"
              style={{ color: "var(--text-secondary)" }}
            >
              Your Bid Amount
            </label>
            <div className="relative">
              <span
                className="absolute left-3.5 top-1/2 -translate-y-1/2 text-lg font-semibold"
                style={{ color: "var(--text-muted)" }}
              >
                $
              </span>
              <input
                type="number"
                step="0.01"
                required
                disabled={isBidding || status === "loading"}
                value={bidAmount}
                onChange={(e) => setBidAmount(e.target.value)}
                className="input-field pl-8 text-lg font-semibold"
                style={{ padding: "0.875rem 1rem 0.875rem 2rem" }}
                placeholder={suggestedBid.toFixed(2)}
              />
            </div>
            <p className="text-xs mt-1.5" style={{ color: "var(--text-muted)" }}>
              Minimum: ${(currentBid + 0.01).toFixed(2)}
            </p>
          </div>

          <button
            type="submit"
            disabled={isBidding || status === "loading"}
            className="btn btn-primary btn-lg w-full"
            style={{ opacity: (isBidding || status === "loading") ? 0.6 : 1 }}
          >
            {isBidding ? (
              <span className="animate-pulse-soft">Placing Bid...</span>
            ) : (
              <>
                <TrendingUp className="h-5 w-5" />
                <span>Place Your Bid</span>
              </>
            )}
          </button>
        </form>
      )}

      {/* Disclaimer */}
      <div
        className="flex items-center justify-center gap-1.5 text-xs pt-3"
        style={{ color: "var(--text-muted)", borderTop: "1px solid var(--border)" }}
      >
        <Timer className="h-3.5 w-3.5" />
        <p>Bids are binding and cannot be retracted.</p>
      </div>
    </div>
  );
}
