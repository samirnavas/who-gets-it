import supabase from "@/lib/db";
import { notFound } from "next/navigation";
import BiddingEngine from "@/components/BiddingEngine";
import { ChevronRight, Home, Info, Calendar, DollarSign, Check, Trophy, ShieldAlert, Award } from "lucide-react";
import Link from "next/link";
import { parseDateTime } from "@/lib/date";
import { getServerSession } from "next-auth/next";
import { authOptions } from "@/app/api/auth/[...nextauth]/route";

export default async function AuctionPage({ params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;

  const session = await getServerSession(authOptions);
  const currentUserId = session?.user ? parseInt((session.user as any).id) : null;

  const { data: rawItem, error } = await supabase
    .from("items")
    .select(`*, owner:users!items_user_id_fkey ( username ), bidder:users!items_highest_bidder_id_fkey ( username )`)
    .eq("id", parseInt(id))
    .single();

  if (error || !rawItem) notFound();

  // Fetch bids for this item in descending order
  const { data: rawBids } = await supabase
    .from("bids")
    .select(`*, user:users!bids_user_id_fkey ( username )`)
    .eq("item_id", parseInt(id))
    .order("bid_amount", { ascending: false });

  const bidList = (rawBids || []).map((b: any, index: number) => ({
    id: b.id,
    userId: b.user_id,
    username: b.user?.username || "Anonymous",
    amount: parseFloat(b.bid_amount),
    position: index + 1,
  }));

  const item = {
    owner: { username: rawItem.owner?.username },
    ownerId: rawItem.user_id,
    highestBidder: rawItem.bidder?.username ? { username: rawItem.bidder.username } : null,
    endTime: rawItem.end_time,
    currentBid: rawItem.current_bid,
    startingBid: rawItem.starting_bid,
    imageUrl: rawItem.image_url,
    title: rawItem.title,
    description: rawItem.description,
    id: rawItem.id,
    status: rawItem.status as string,
  };

  const isEnded = parseDateTime(item.endTime).getTime() < Date.now() || item.status !== "active";

  return (
    <div className="flex flex-col gap-6 max-w-6xl mx-auto animate-fade-in-up pb-12">

      {/* Breadcrumb */}
      <nav className="flex items-center gap-2 text-sm flex-wrap text-slate-400">
        <Link href="/" className="flex items-center gap-1 hover:text-blue-600 transition-colors"><Home className="h-3.5 w-3.5" /> Home</Link>
        <ChevronRight className="h-3.5 w-3.5" />
        <Link href="/auctions" className="hover:text-blue-600 transition-colors">Auctions</Link>
        <ChevronRight className="h-3.5 w-3.5" />
        <span className="truncate max-w-[180px] sm:max-w-xs text-slate-700 font-medium">{item.title}</span>
      </nav>

      {/* Grid */}
      <div className="grid lg:grid-cols-5 gap-6">

        {/* Left — 3 cols */}
        <div className="lg:col-span-3 flex flex-col gap-5">
          <div className="relative aspect-[4/3] rounded-2xl overflow-hidden bg-blue-50">
            <img
              src={item.imageUrl || "https://images.unsplash.com/photo-1599305090598-fe179d501c27?auto=format&fit=crop&q=80&w=800"}
              alt={item.title}
              className="object-cover w-full h-full"
            />
            <div className="absolute inset-0" style={{ background: "linear-gradient(to top, rgba(15,23,42,0.45) 0%, transparent 60%)" }} />
            <div className="absolute top-4 left-4">
              {isEnded
                ? <span className="badge-ended">Ended</span>
                : <span className="badge-live"><span className="h-1.5 w-1.5 rounded-full bg-blue-500 animate-pulse" /> Live Auction</span>}
            </div>
          </div>

          <div className="card-solid rounded-2xl p-6 flex flex-col gap-5">
            <div>
              <h1 className="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight mb-2">{item.title}</h1>
              <p className="text-sm text-slate-500">
                Listed by <span className="font-semibold text-blue-600">@{item.owner.username}</span>
              </p>
            </div>

            <div className="h-px" style={{ background: "rgba(37,99,235,0.1)" }} />

            <div>
              <h3 className="text-xs font-bold uppercase tracking-wider flex items-center gap-2 mb-3 text-blue-500">
                <Info className="h-3.5 w-3.5" /> Description
              </h3>
              <p className="text-sm leading-relaxed whitespace-pre-line text-slate-600">
                {item.description || "No description provided."}
              </p>
            </div>

            <div className="grid grid-cols-2 gap-4 p-4 rounded-xl"
              style={{ background: "rgba(37,99,235,0.04)", border: "1px solid rgba(37,99,235,0.1)" }}>
              <div>
                <p className="text-[10px] font-bold uppercase tracking-wider mb-1 flex items-center gap-1 text-blue-500">
                  <DollarSign className="h-3 w-3" /> Starting Bid
                </p>
                <p className="text-base font-bold text-slate-800">${item.startingBid}</p>
              </div>
              <div>
                <p className="text-[10px] font-bold uppercase tracking-wider mb-1 flex items-center gap-1 text-blue-500">
                  <Calendar className="h-3 w-3" /> Ends At
                </p>
                <p className="text-base font-bold text-slate-800">
                  {parseDateTime(item.endTime).toLocaleString(undefined, { month: "short", day: "numeric", hour: "2-digit", minute: "2-digit" })}
                </p>
              </div>
            </div>
          </div>

          {/* Bidding History & Rank */}
          <div className="card-solid rounded-2xl p-6 flex flex-col gap-5">
            <div>
              <h2 className="text-xl font-extrabold text-slate-900 tracking-tight flex items-center gap-2">
                <Trophy className="h-5 w-5 text-blue-500" /> Bidding History & Rank
              </h2>
              <p className="text-sm mt-0.5 text-slate-500">Live competitive bids in descending order</p>
            </div>

            <div className="h-px" style={{ background: "rgba(37,99,235,0.1)" }} />

            {bidList.length === 0 ? (
              <div className="py-8 text-center text-slate-400 text-sm">
                No bids placed yet. Be the first to place a bid!
              </div>
            ) : (
              <div className="flex flex-col gap-2 max-h-[350px] overflow-y-auto pr-1">
                {bidList.map((bid) => {
                  const isCurrentUsersBid = bid.userId === currentUserId;
                  const isWinningBid = bid.position === 1;

                  return (
                    <div
                      key={bid.id}
                      className={`flex items-center justify-between p-3 rounded-xl transition-all ${
                        isCurrentUsersBid 
                          ? "bg-blue-50/70 border border-blue-200/60" 
                          : "bg-slate-50 border border-slate-100 hover:bg-slate-100/70"
                      }`}
                    >
                      <div className="flex items-center gap-3">
                        <div
                          className={`h-7 w-7 rounded-lg flex items-center justify-center font-bold text-xs ${
                            isWinningBid
                              ? "bg-amber-100 text-amber-700 border border-amber-200"
                              : "bg-slate-200/80 text-slate-600 border border-slate-300/40"
                          }`}
                        >
                          #{bid.position}
                        </div>

                        <div>
                          <p className="text-sm font-semibold text-slate-800 flex items-center gap-1.5">
                            @{bid.username}
                            {isCurrentUsersBid && (
                              <span className="px-1.5 py-0.5 text-[9px] font-bold uppercase bg-blue-100 text-blue-700 rounded-md border border-blue-200">
                                You
                              </span>
                            )}
                            {isWinningBid && !isEnded && (
                              <span className="px-1.5 py-0.5 text-[9px] font-bold uppercase bg-amber-100 text-amber-700 rounded-md border border-amber-200 flex items-center gap-0.5">
                                <Award className="h-2.5 w-2.5" /> High Bid
                              </span>
                            )}
                          </p>
                        </div>
                      </div>

                      <div className="text-right">
                        <p className={`text-sm font-bold ${isWinningBid ? "text-amber-600" : "text-blue-600"}`}>
                          ${bid.amount.toLocaleString()}
                        </p>
                      </div>
                    </div>
                  );
                })}
              </div>
            )}
          </div>
        </div>

        {/* Right — 2 cols sticky */}
        <div className="lg:col-span-2 lg:sticky lg:top-24 h-max">
          {isEnded ? (
            <div className="card-solid rounded-2xl p-8 flex flex-col items-center text-center gap-5 border-2 border-amber-300/40"
              style={{ background: "linear-gradient(180deg, #FFFFFF 0%, #FFFDF7 100%)", boxShadow: "0 10px 30px rgba(245,158,11,0.06)" }}>
              <div className="h-16 w-16 rounded-2xl flex items-center justify-center mb-1 animate-bounce"
                style={{ background: "linear-gradient(135deg, #F59E0B, #D97706)", boxShadow: "0 8px 24px rgba(245,158,11,0.35)" }}>
                <Trophy className="h-8 w-8 text-white" />
              </div>
              <div>
                <h2 className="text-2xl font-extrabold text-slate-900 tracking-tight">Auction Ended</h2>
                <p className="text-xs text-amber-600 font-bold uppercase tracking-widest mt-1">Official Results</p>
              </div>
              
              <div className="h-px w-full" style={{ background: "rgba(245,158,11,0.15)" }} />

              {item.highestBidder?.username ? (
                <div className="flex flex-col gap-2 w-full">
                  <div className="p-4 rounded-xl bg-amber-50/60 border border-amber-100">
                    <p className="text-[10px] font-bold text-amber-700 uppercase tracking-widest mb-1">Winner</p>
                    <p className="text-xl font-extrabold text-slate-900 flex items-center justify-center gap-1.5">
                      👑 @{item.highestBidder.username}
                      {item.highestBidder.username === session?.user?.name && (
                        <span className="px-1.5 py-0.5 text-[9px] font-bold uppercase bg-amber-100 text-amber-700 rounded-md border border-amber-200">
                          You
                        </span>
                      )}
                    </p>
                  </div>
                  <div className="p-4 rounded-xl bg-slate-50 border border-slate-100">
                    <p className="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Winning Bid</p>
                    <p className="text-2xl font-extrabold text-slate-800">${parseFloat(item.currentBid.toString()).toLocaleString()}</p>
                  </div>
                </div>
              ) : (
                <div className="p-4 rounded-xl bg-slate-50 border border-slate-100 w-full text-slate-500 text-sm font-semibold">
                  No bids were placed on this item.
                </div>
              )}
            </div>
          ) : (
            <BiddingEngine item={{ id: item.id, currentBid: item.currentBid.toString(), highestBidder: item.highestBidder, ownerId: item.ownerId }} />
          )}
        </div>
      </div>
    </div>
  );
}
