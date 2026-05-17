import Link from "next/link";
import { Plus, ArrowRight, Sparkles, TrendingUp, ShieldCheck, Zap } from "lucide-react";
import { AuctionCard } from "@/components/AuctionCard";
import supabase from "@/lib/db";

export default async function Home() {
  const { data: rows, error } = await supabase
    .from("items")
    .select(`*, bidder:users!items_highest_bidder_id_fkey ( username )`)
    .eq("status", "active")
    .order("id", { ascending: false })
    .limit(8);

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
    <div className="flex flex-col gap-16 pb-12 animate-fade-in-up">

      {/* ── HERO ──────────────────────────────────────────────── */}
      <section className="relative flex flex-col items-center text-center gap-6 pt-14 sm:pt-20 pb-10 px-4">
        {/* Hero glow */}
        <div
          className="absolute top-0 left-1/2 -translate-x-1/2 w-[700px] h-[400px] rounded-full pointer-events-none"
          style={{ background: "radial-gradient(ellipse, rgba(37,99,235,0.12) 0%, transparent 70%)", filter: "blur(60px)", zIndex: 0 }}
        />

        <div className="relative z-10 flex flex-col items-center gap-6">
          {/* Badge */}
          <div
            className="inline-flex items-center gap-2 px-4 py-1.5 rounded-full text-xs font-bold uppercase tracking-widest"
            style={{ background: "rgba(37,99,235,0.08)", border: "1px solid rgba(37,99,235,0.2)", color: "#2563EB" }}
          >
            <Sparkles className="h-3.5 w-3.5" />
            Live Auction Platform
          </div>

          <h1 className="text-5xl sm:text-7xl font-extrabold tracking-tighter leading-[1.08] text-slate-900 max-w-4xl">
            Bid on the<br />
            <span className="text-gradient">extraordinary.</span>
          </h1>

          <p className="text-base sm:text-xl max-w-2xl leading-relaxed text-slate-500">
            Real-time bidding with a beautiful, intuitive interface. Discover unique items and place your bids instantly.
          </p>

          <div className="flex flex-col sm:flex-row items-center gap-4 mt-2 w-full sm:w-auto">
            <Link href="/auctions" className="btn-primary px-8 py-4 text-base w-full sm:w-auto">
              Explore Auctions <ArrowRight className="h-4 w-4" />
            </Link>
            <Link href="/auction/create" className="btn-ghost px-8 py-4 text-base w-full sm:w-auto">
              <Plus className="h-4 w-4" /> List an Item
            </Link>
          </div>
        </div>

        {/* Divider */}
        <div className="absolute inset-x-0 bottom-0 h-px" style={{ background: "linear-gradient(90deg, transparent, rgba(37,99,235,0.15), transparent)" }} />
      </section>

      {/* ── FEATURE CARDS ─────────────────────────────────────── */}
      <section className="grid grid-cols-1 md:grid-cols-3 gap-4 px-1 stagger-children">
        {[
          { icon: Zap, title: "Instant Bids", desc: "Real-time updates the moment someone outbids you.", color: "#2563EB", bg: "rgba(37,99,235,0.07)", border: "rgba(37,99,235,0.15)" },
          { icon: ShieldCheck, title: "Secure", desc: "Verified participants and fully transparent bidding history.", color: "#6366F1", bg: "rgba(99,102,241,0.07)", border: "rgba(99,102,241,0.15)" },
          { icon: TrendingUp, title: "Fair Value", desc: "Market-driven pricing powered by real competitive bids.", color: "#8B5CF6", bg: "rgba(139,92,246,0.07)", border: "rgba(139,92,246,0.15)" },
        ].map(({ icon: Icon, title, desc, color, bg, border }) => (
          <div key={title} className="card p-6 flex flex-col gap-4">
            <div
              className="h-11 w-11 rounded-xl flex items-center justify-center"
              style={{ background: bg, border: `1px solid ${border}` }}
            >
              <Icon className="h-5 w-5" style={{ color }} />
            </div>
            <div>
              <h3 className="font-bold text-slate-800 text-base mb-1">{title}</h3>
              <p className="text-sm leading-relaxed text-slate-500">{desc}</p>
            </div>
          </div>
        ))}
      </section>

      {/* ── LIVE AUCTIONS ─────────────────────────────────────── */}
      <section className="flex flex-col gap-6">
        <div className="flex items-end justify-between pb-4" style={{ borderBottom: "1px solid rgba(37,99,235,0.1)" }}>
          <div>
            <h2 className="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">Live Auctions</h2>
            <p className="text-sm mt-1 text-slate-500">Discover currently active listings.</p>
          </div>
          <Link href="/auctions" className="btn-ghost text-sm px-4 py-2">
            View All <ArrowRight className="h-3.5 w-3.5" />
          </Link>
        </div>

        {auctions.length > 0 ? (
          <div className="grid gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 stagger-children">
            {auctions.map((a) => <AuctionCard key={a.id} {...a} />)}
          </div>
        ) : (
          <div className="card flex flex-col items-center justify-center py-24 px-4 text-center">
            <div className="h-16 w-16 rounded-2xl flex items-center justify-center mb-5"
              style={{ background: "linear-gradient(135deg, #2563EB, #4F46E5)", boxShadow: "0 8px 24px rgba(37,99,235,0.3)" }}>
              <Sparkles className="h-7 w-7 text-white" />
            </div>
            <h3 className="text-xl font-bold text-slate-800 mb-2">It&apos;s quiet here</h3>
            <p className="mb-8 max-w-md text-slate-500">There are no active auctions. Be the first to list an item!</p>
            <Link href="/auction/create" className="btn-primary px-6 py-3">
              <Plus className="h-4 w-4" /> List an Item
            </Link>
          </div>
        )}
      </section>
    </div>
  );
}
