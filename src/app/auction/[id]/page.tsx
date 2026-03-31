import { prisma } from "@/lib/prisma";
import { notFound } from "next/navigation";
import Image from "next/image";
import BiddingEngine from "@/components/BiddingEngine";
import { ChevronRight, Home, LayoutList, CheckCircle2 } from "lucide-react";
import Link from "next/link";

// Server Component fetching the initial Auction data
export default async function AuctionPage({ params }: { params: { id: string } }) {
  const item = await prisma.item.findUnique({
    where: { id: parseInt(params.id) },
    include: {
      owner: { select: { username: true } },
      highestBidder: { select: { username: true } },
    },
  });

  if (!item) {
    notFound();
  }

  // Calculate elapsed time or time remaining server-side
  const isEnded = new Date(item.endTime).getTime() < new Date().getTime() || item.status !== "active";

  return (
    <div className="flex flex-col gap-8 max-w-7xl mx-auto py-6">
      
      {/* Breadcrumbs */}
      <nav className="flex items-center gap-2 text-sm text-slate-500 font-medium">
        <Link href="/" className="hover:text-indigo-600 transition flex items-center gap-1">
          <Home className="h-4 w-4" /> Home
        </Link>
        <ChevronRight className="h-4 w-4" />
        <Link href="/auctions" className="hover:text-indigo-600 transition flex items-center gap-1">
          <LayoutList className="h-4 w-4" /> Auctions
        </Link>
        <ChevronRight className="h-4 w-4" />
        <span className="text-slate-900 truncate max-w-xs">{item.title}</span>
      </nav>

      {/* Main Grid */}
      <div className="grid lg:grid-cols-2 gap-12">
        
        {/* Left Column: Image & Details */}
        <div className="flex flex-col gap-8">
          <div className="relative aspect-[4/3] rounded-3xl overflow-hidden bg-white shadow-sm border border-slate-100 group">
            <img 
              src={item.imageUrl || "https://images.unsplash.com/photo-1599305090598-fe179d501c27?auto=format&fit=crop&q=80&w=800"} 
              alt={item.title} 
              className="object-cover w-full h-full cursor-zoom-in transition duration-500 group-hover:scale-105"
            />
          </div>

          <div className="flex flex-col bg-white rounded-3xl p-8 shadow-sm border border-slate-100 gap-6">
            <div className="flex items-start justify-between">
              <div>
                <h1 className="text-3xl font-extrabold text-slate-900 tracking-tight">{item.title}</h1>
                <p className="mt-2 text-lg text-slate-600">Listed by <span className="font-bold text-indigo-600">@{item.owner.username}</span></p>
              </div>
            </div>

            <div className="w-full h-px bg-slate-100"></div>
            
            <div className="prose prose-slate prose-lg max-w-none text-slate-600 leading-relaxed">
              <h3 className="text-xl font-bold text-slate-900 mb-4 flex items-center gap-2">
                <CheckCircle2 className="h-5 w-5 text-indigo-500" />
                Description & Details
              </h3>
              <p>{item.description || "No detailed description provided for this item. Please bid carefully."}</p>
            </div>
            
            <div className="bg-slate-50 border border-slate-100 rounded-2xl p-6 mt-4">
               <h4 className="font-bold text-slate-900 mb-2">Auction Details</h4>
               <dl className="grid grid-cols-2 gap-4 text-sm">
                 <div>
                   <dt className="text-slate-500">Starting Bid</dt>
                   <dd className="font-bold text-slate-900">${item.startingBid.toString()}</dd>
                 </div>
                 <div>
                   <dt className="text-slate-500">Ends At</dt>
                   <dd className="font-bold text-slate-900">{new Date(item.endTime).toLocaleString()}</dd>
                 </div>
               </dl>
            </div>
          </div>
        </div>

        {/* Right Column: Bidding Engine */}
        <div className="flex flex-col lg:sticky lg:top-24 h-max">
          {/* We pass the serialized item down to the Client Component */}
          {isEnded ? (
            <div className="rounded-3xl bg-slate-50 p-8 border border-slate-200 flex flex-col items-center justify-center text-center gap-4 py-16">
              <div className="h-16 w-16 bg-red-100 text-red-600 rounded-full flex items-center justify-center mb-2">
                 <CheckCircle2 className="h-8 w-8" />
              </div>
              <h2 className="text-2xl font-black text-slate-900">Auction Ended</h2>
              <p className="text-slate-500 mb-4 text-lg">
                Winning Bid: <span className="font-bold text-slate-900">${item.currentBid.toString()}</span><br/>
                Winner: <span className="font-bold text-indigo-600">{item.highestBidder?.username || "No bids placed"}</span>
              </p>
            </div>
          ) : (
            <BiddingEngine item={{
              id: item.id,
              currentBid: item.currentBid.toString(),
              highestBidder: item.highestBidder
            }} />
          )}
        </div>
      </div>
    </div>
  );
}
