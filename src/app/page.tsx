import Link from "next/link";
import { Plus, ArrowRight, Sparkles, TrendingUp, ShieldCheck, Zap } from "lucide-react";
import { AuctionCard } from "@/components/AuctionCard";
import db from "@/lib/db";
import { RowDataPacket } from "mysql2";

export default async function Home() {
  const [rows] = await db.query<RowDataPacket[]>(`
    SELECT i.*, u.username as bidder_username 
    FROM items i 
    LEFT JOIN users u ON i.highest_bidder_id = u.id 
    WHERE i.status = 'active' 
    ORDER BY i.created_at DESC 
    LIMIT 9
  `);

  const auctions = rows.map(r => ({
    id: r.id.toString(),
    title: r.title,
    currentBid: parseFloat(r.current_bid),
    endTime: new Date(r.end_time),
    imageUrl: r.image_url,
    highestBidder: r.bidder_username || "No Bids",
    startingBid: parseFloat(r.starting_bid)
  }));

  return (
    <div className="flex flex-col gap-16 sm:gap-24 animate-fade-in-up pb-12">
      {/* Hero Section */}
      <section className="relative flex flex-col items-center justify-center text-center gap-6 pt-12 sm:pt-24 pb-8 sm:pb-16 px-4">
        {/* Decorative background glow */}
        <div 
          className="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-full max-w-2xl h-[300px] sm:h-[400px] blur-[100px] -z-10 rounded-full opacity-30 dark:opacity-20 pointer-events-none"
          style={{ background: "radial-gradient(circle, var(--color-primary) 0%, transparent 70%)" }}
        />

        <div 
          className="inline-flex items-center gap-2 px-4 py-2 rounded-full backdrop-blur-md shadow-sm border mb-4 transition-transform hover:scale-105"
          style={{ 
            backgroundColor: "var(--bg-header)", 
            borderColor: "var(--border)",
            color: "var(--color-primary)"
          }}
        >
          <Sparkles className="h-4 w-4" />
          <span className="text-xs font-bold uppercase tracking-widest">
            Next-Gen Bidding
          </span>
        </div>
        
        <h1 
          className="text-4xl sm:text-5xl lg:text-7xl font-extrabold leading-tight tracking-tight max-w-4xl"
          style={{ color: "var(--text-primary)" }}
        >
          Discover & Win <br className="hidden sm:block" />
          <span className="text-gradient">Extraordinary Items</span>
        </h1>
        
        <p 
          className="text-lg sm:text-xl max-w-2xl leading-relaxed mt-2"
          style={{ color: "var(--text-secondary)" }}
        >
          Join the most dynamic auction platform. Bid in real-time on curated items, with fair transparent pricing and instant updates.
        </p>
        
        <div className="flex flex-col sm:flex-row items-center justify-center gap-4 mt-8 w-full sm:w-auto">
          <Link 
            href="/auctions" 
            className="btn btn-primary btn-lg w-full sm:w-auto shadow-lg shadow-indigo-500/20 group"
          >
            Explore Auctions
            <ArrowRight className="h-4 w-4 transition-transform group-hover:translate-x-1" />
          </Link>
          <Link 
            href="/auction/create" 
            className="btn btn-secondary btn-lg w-full sm:w-auto group"
          >
            <Plus className="h-4 w-4 transition-transform group-hover:rotate-90" />
            List an Item
          </Link>
        </div>

        {/* Features Row */}
        <div className="grid grid-cols-1 sm:grid-cols-3 gap-6 sm:gap-12 mt-16 pt-10 border-t w-full max-w-4xl" style={{ borderColor: "var(--border)" }}>
          <div className="flex flex-col items-center gap-3">
            <div className="p-3 rounded-2xl transition-transform hover:-translate-y-1" style={{ backgroundColor: "rgba(98, 114, 217, 0.1)", color: "var(--color-primary)" }}>
              <Zap className="h-6 w-6" />
            </div>
            <h3 className="font-bold" style={{ color: "var(--text-primary)" }}>Real-Time Bids</h3>
            <p className="text-sm text-center" style={{ color: "var(--text-muted)" }}>Instant updates without refreshing the page.</p>
          </div>
          <div className="flex flex-col items-center gap-3">
            <div className="p-3 rounded-2xl transition-transform hover:-translate-y-1" style={{ backgroundColor: "rgba(76, 175, 130, 0.1)", color: "var(--color-success)" }}>
              <ShieldCheck className="h-6 w-6" />
            </div>
            <h3 className="font-bold" style={{ color: "var(--text-primary)" }}>Secure Platform</h3>
            <p className="text-sm text-center" style={{ color: "var(--text-muted)" }}>Verified users and transparent bidding history.</p>
          </div>
          <div className="flex flex-col items-center gap-3">
            <div className="p-3 rounded-2xl transition-transform hover:-translate-y-1" style={{ backgroundColor: "rgba(232, 152, 90, 0.1)", color: "var(--color-accent)" }}>
              <TrendingUp className="h-6 w-6" />
            </div>
            <h3 className="font-bold" style={{ color: "var(--text-primary)" }}>Best Value</h3>
            <p className="text-sm text-center" style={{ color: "var(--text-muted)" }}>Discover rare items at fair market prices.</p>
          </div>
        </div>
      </section>

      {/* Active Auctions Section */}
      <section className="flex flex-col gap-8 max-w-7xl mx-auto w-full">
        <div className="flex flex-col sm:flex-row sm:items-end justify-between gap-4 pb-6" style={{ borderBottom: "1px solid var(--border)" }}>
          <div className="flex flex-col gap-2">
            <h2 className="text-2xl sm:text-3xl font-bold tracking-tight" style={{ color: "var(--text-primary)" }}>
              Live Auctions
            </h2>
            <p className="text-sm sm:text-base" style={{ color: "var(--text-secondary)" }}>
              Don't miss out on these actively bidding items.
            </p>
          </div>
          <Link
            href="/auctions"
            className="group flex items-center gap-2 text-sm font-semibold transition-all px-4 py-2 rounded-lg"
            style={{ backgroundColor: "var(--bg-secondary)", color: "var(--text-primary)" }}
          >
            View All
            <ArrowRight className="h-4 w-4 transition-transform group-hover:translate-x-1" style={{ color: "var(--color-primary)" }} />
          </Link>
        </div>

        {auctions.length > 0 ? (
          <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 stagger-children">
            {auctions.map((auction) => (
              <AuctionCard key={auction.id} {...auction} />
            ))}
          </div>
        ) : (
          <div className="flex flex-col items-center justify-center py-24 px-4 text-center rounded-3xl border-2 border-dashed transition-colors hover:border-solid" style={{ backgroundColor: "var(--bg-secondary)", borderColor: "var(--border)" }}>
            <div className="p-4 rounded-2xl mb-5 shadow-sm" style={{ backgroundColor: "var(--bg-card)" }}>
              <Sparkles className="h-8 w-8" style={{ color: "var(--text-muted)" }} />
            </div>
            <h3 className="text-2xl font-bold mb-2 tracking-tight" style={{ color: "var(--text-primary)" }}>No Active Auctions</h3>
            <p className="max-w-md text-sm sm:text-base mb-8 leading-relaxed" style={{ color: "var(--text-secondary)" }}>
              We're waiting for the next big thing. Be the first to start a new auction and attract bidders right away!
            </p>
            <Link href="/auction/create" className="btn btn-primary btn-lg shadow-lg shadow-primary/20">
              <Plus className="h-5 w-5" />
              List an Item
            </Link>
          </div>
        )}
      </section>
    </div>
  );
}
