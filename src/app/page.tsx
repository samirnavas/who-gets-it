import Link from "next/link";
import { Gavel, TrendingUp, ShieldCheck, Zap } from "lucide-react";
import { AuctionCard } from "@/components/AuctionCard";

export default function Home() {
  const mockAuctions = [
    {
      id: "1",
      title: "Silver Lining Collector's Coin",
      currentBid: 145.50,
      endTime: new Date(Date.now() + 1000 * 60 * 60 * 2), // 2 hours from now
      imageUrl: "https://images.unsplash.com/photo-1599305090598-fe179d501c27?auto=format&fit=crop&q=80&w=600",
      highestBidder: "Alex99",
      startingBid: 50.00
    },
    {
      id: "2",
      title: "Minimalist Workspace Suite",
      currentBid: 320.00,
      endTime: new Date(Date.now() + 1000 * 60 * 60 * 1.5), 
      imageUrl: "https://images.unsplash.com/photo-1494438639946-1ebd1d20bf85?auto=format&fit=crop&q=80&w=600",
      highestBidder: "Samantha_W",
      startingBid: 200.00
    },
    {
      id: "3",
      title: "Vintage Field Camera Pro",
      currentBid: 850.00,
      endTime: new Date(Date.now() + 1000 * 60 * 30), 
      imageUrl: "https://images.unsplash.com/photo-1516035069371-29a1b244cc32?auto=format&fit=crop&q=80&w=600",
      highestBidder: "PhotoGeek",
      startingBid: 450.00
    }
  ];

  return (
    <div className="flex flex-col gap-12 py-6">
      {/* Hero Section */}
      <section className="relative overflow-hidden rounded-3xl bg-gradient-to-br from-indigo-600 via-indigo-700 to-indigo-900 p-8 text-white shadow-2xl md:p-16">
        <div className="absolute -right-20 -top-20 h-64 w-64 rounded-full bg-white/10 blur-3xl" />
        <div className="absolute -bottom-20 -left-20 h-64 w-64 rounded-full bg-indigo-400/20 blur-3xl" />
        
        <div className="relative z-10 flex flex-col items-center gap-6 text-center md:items-start md:text-left">
          <div className="inline-flex items-center gap-2 rounded-full bg-white/20 px-4 py-1 text-sm font-medium backdrop-blur-sm">
            <Zap className="h-4 w-4 text-yellow-300 fill-yellow-300" />
            <span>Active Real-time Platform</span>
          </div>
          <h1 className="text-4xl font-extrabold tracking-tight md:text-6xl text-balance">
            Your Ultimate Destination for <span className="text-yellow-300 italic">Competitive Bidding.</span>
          </h1>
          <p className="max-w-xl text-lg text-indigo-100 md:text-xl">
            Bid on unique items, track live auctions, and win items at amazing prices on the most secure bidding engine.
          </p>
          <div className="mt-4 flex flex-wrap gap-4">
            <Link href="/auctions" className="rounded-xl bg-white px-8 py-3.5 text-indigo-900 font-bold shadow-lg transition hover:bg-slate-100 hover:scale-105 active:scale-95">
              Browse Auctions
            </Link>
            <Link href="/how-it-works" className="rounded-xl border-2 border-white/30 bg-white/10 px-8 py-3.5 font-bold backdrop-blur-md transition hover:bg-white/20">
              Learn More
            </Link>
          </div>
        </div>
      </section>

      {/* Feature Section */}
      <section className="grid gap-6 md:grid-cols-3">
        {[
          { icon: Gavel, title: "Fair Play", desc: "Advanced anti-snipe features to keep it fair for everyone." },
          { icon: TrendingUp, title: "Real-time Tracker", desc: "Live bid tracking and instant notifications when outbid." },
          { icon: ShieldCheck, title: "Verified Bidders", desc: "Secure platform with identity verification for all users." },
        ].map((feature, i) => (
          <div key={i} className="group relative rounded-2xl bg-white p-6 shadow-sm border border-slate-100 transition hover:shadow-md hover:-translate-y-1">
            <div className="mb-4 inline-flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 transition group-hover:bg-indigo-600 group-hover:text-white">
              <feature.icon className="h-6 w-6" />
            </div>
            <h3 className="mb-2 text-xl font-bold">{feature.title}</h3>
            <p className="text-slate-500 leading-relaxed">{feature.desc}</p>
          </div>
        ))}
      </section>

      {/* Active Auctions Grid */}
      <section className="flex flex-col gap-6">
        <div className="flex items-center justify-between">
          <h2 className="text-3xl font-bold tracking-tight">Active Auctions 🔥</h2>
          <Link href="/auctions" className="text-indigo-600 font-semibold hover:underline">View all</Link>
        </div>
        
        <div className="grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
          {mockAuctions.map((auction) => (
            <AuctionCard key={auction.id} {...auction} />
          ))}
        </div>
      </section>
    </div>
  );
}
