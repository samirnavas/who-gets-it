import { AuctionCard } from "@/components/AuctionCard";
import supabase from "@/lib/db";
import { Plus, Sparkles } from "lucide-react";
import Link from "next/link";

export default async function AuctionsPage() {
  const { data: rows, error } = await supabase
    .from("items")
    .select(`*, bidder:users!items_highest_bidder_id_fkey ( username )`)
    .eq("status", "active")
    .order("id", { ascending: false });

  if (error) console.error("Error fetching auctions:", error);

  const auctions = (rows || []).map((r: any) => ({
    id: r.id.toString(),
    title: r.title,
    currentBid: parseFloat(r.current_bid),
    endTime: r.end_time,
    imageUrl: r.image_url,
    highestBidder: r.bidder?.username || "No Bids",
    startingBid: parseFloat(r.starting_bid),
  }));

  return (
    <div className="flex flex-col gap-8 animate-fade-in-up pb-12">
      <div className="flex flex-col sm:flex-row sm:items-end justify-between gap-4 pb-6" style={{ borderBottom: "1px solid rgba(37,99,235,0.1)" }}>
        <div>
          <p className="text-xs font-bold uppercase tracking-widest mb-2 text-blue-500">Browse</p>
          <h1 className="text-4xl sm:text-5xl font-extrabold tracking-tighter text-slate-900">Explore Auctions</h1>
          <p className="mt-2 text-slate-500">Discover and bid on an exclusive selection of active items.</p>
        </div>
        <Link href="/auction/create" className="btn-primary px-6 py-3 shrink-0">
          <Plus className="h-4 w-4" /> List an Item
        </Link>
      </div>

      {auctions.length > 0 ? (
        <div className="grid gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 stagger-children">
          {auctions.map((a) => <AuctionCard key={a.id} {...a} />)}
        </div>
      ) : (
        <div className="card flex flex-col items-center justify-center py-24 text-center">
          <div className="h-16 w-16 rounded-2xl flex items-center justify-center mb-5"
            style={{ background: "linear-gradient(135deg, #2563EB, #4F46E5)", boxShadow: "0 8px 24px rgba(37,99,235,0.3)" }}>
            <Sparkles className="h-7 w-7 text-white" />
          </div>
          <h3 className="text-xl font-bold text-slate-800 mb-2">No Auctions Available</h3>
          <p className="mb-8 max-w-sm text-slate-500">There are currently no active listings. Be the first to start an auction!</p>
          <Link href="/auction/create" className="btn-primary px-6 py-3">
            <Plus className="h-4 w-4" /> Start an Auction
          </Link>
        </div>
      )}
    </div>
  );
}
