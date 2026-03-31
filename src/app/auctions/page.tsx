import { AuctionCard } from "@/components/AuctionCard";
import db from "@/lib/db";
import { RowDataPacket } from "mysql2";
import { Plus, Gavel } from "lucide-react";
import Link from "next/link";

export default async function AuctionsPage() {
  const [rows] = await db.query<RowDataPacket[]>(`
    SELECT i.*, u.username as bidder_username 
    FROM items i 
    LEFT JOIN users u ON i.highest_bidder_id = u.id 
    WHERE i.status = 'active'
    ORDER BY i.created_at DESC 
  `);

  const auctions = rows.map((r) => ({
    id: r.id.toString(),
    title: r.title,
    currentBid: parseFloat(r.current_bid),
    endTime: new Date(r.end_time),
    imageUrl: r.image_url,
    highestBidder: r.bidder_username || "No Bids",
    startingBid: parseFloat(r.starting_bid),
  }));

  return (
    <div className="flex flex-col gap-8 animate-fade-in-up pb-12">
      <div className="flex flex-col sm:flex-row sm:items-end justify-between gap-4 pb-6 border-b" style={{ borderColor: "var(--border)" }}>
        <div className="flex flex-col gap-2">
          <h1 className="text-3xl sm:text-4xl font-extrabold tracking-tight" style={{ color: "var(--text-primary)" }}>
            Explore Auctions
          </h1>
          <p className="text-base sm:text-lg max-w-2xl" style={{ color: "var(--text-secondary)" }}>
            Discover and bid on an exclusive selection of active items. Don't miss out on these real-time opportunities to win big!
          </p>
        </div>
        <Link href="/auction/create" className="btn btn-primary shadow-lg shadow-primary/20 shrink-0">
          <Plus className="h-4 w-4" />
          List an Item
        </Link>
      </div>
      
      {auctions.length > 0 ? (
        <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 stagger-children mt-2">
          {auctions.map((auction) => (
            <AuctionCard key={auction.id} {...auction} />
          ))}
        </div>
      ) : (
        <div className="flex flex-col items-center justify-center py-24 px-4 text-center rounded-3xl border-2 border-dashed mt-4 transition-colors hover:border-solid" style={{ backgroundColor: "var(--bg-secondary)", borderColor: "var(--border)" }}>
           <div className="p-4 rounded-2xl mb-5 shadow-sm" style={{ backgroundColor: "var(--bg-card)" }}>
              <Gavel className="h-8 w-8" style={{ color: "var(--text-muted)" }} />
            </div>
            <h3 className="text-2xl font-bold mb-2 tracking-tight" style={{ color: "var(--text-primary)" }}>No Auctions Available</h3>
            <p className="max-w-md text-sm sm:text-base mb-8 leading-relaxed" style={{ color: "var(--text-secondary)" }}>
              There are currently no active listings. Check back later or be the first to start an auction!
            </p>
            <Link href="/auction/create" className="btn btn-primary btn-lg shadow-lg shadow-primary/20">
              <Plus className="h-5 w-5" />
              Start an Auction
            </Link>
        </div>
      )}
    </div>
  );
}
