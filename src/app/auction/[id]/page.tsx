import db from "@/lib/db";
import { notFound } from "next/navigation";
import { RowDataPacket } from "mysql2";
import BiddingEngine from "@/components/BiddingEngine";
import { ChevronRight, Home, LayoutList, CheckCircle2, Info, Calendar, DollarSign } from "lucide-react";
import Link from "next/link";

export default async function AuctionPage({ params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;

  const [rows] = await db.query<RowDataPacket[]>(`
    SELECT 
      i.*, 
      u.username as owner_username,
      hb.username as bidder_username
    FROM items i
    JOIN users u ON i.user_id = u.id
    LEFT JOIN users hb ON i.highest_bidder_id = hb.id
    WHERE i.id = ?`,
    [parseInt(id)]
  );

  const rawItem = rows[0];

  if (!rawItem) {
    notFound();
  }

  const item = {
    owner: { username: rawItem.owner_username },
    highestBidder: rawItem.bidder_username ? { username: rawItem.bidder_username } : null,
    endTime: rawItem.end_time,
    currentBid: rawItem.current_bid,
    startingBid: rawItem.starting_bid,
    imageUrl: rawItem.image_url,
    title: rawItem.title,
    description: rawItem.description,
    id: rawItem.id,
    status: rawItem.status as string,
  };

  const isEnded = new Date(item.endTime).getTime() < new Date().getTime() || item.status !== "active";

  return (
    <div className="flex flex-col gap-6 max-w-6xl mx-auto animate-fade-in-up">

      {/* Breadcrumbs */}
      <nav
        className="flex items-center gap-1.5 text-sm font-medium flex-wrap"
        style={{ color: "var(--text-muted)" }}
      >
        <Link
          href="/"
          className="flex items-center gap-1 hover:opacity-80 transition-opacity"
          style={{ color: "var(--text-muted)" }}
        >
          <Home className="h-3.5 w-3.5" /> Home
        </Link>
        <ChevronRight className="h-3.5 w-3.5" />
        <span style={{ color: "var(--text-primary)" }} className="truncate max-w-[200px] sm:max-w-xs">
          {item.title}
        </span>
      </nav>

      {/* Main Grid */}
      <div className="grid lg:grid-cols-5 gap-6 lg:gap-8">

        {/* Left Column: Image & Details (3/5) */}
        <div className="lg:col-span-3 flex flex-col gap-6">
          {/* Image */}
          <div
            className="relative aspect-[4/3] rounded-xl overflow-hidden group"
            style={{ backgroundColor: "var(--bg-secondary)" }}
          >
            <img
              src={item.imageUrl || "https://images.unsplash.com/photo-1599305090598-fe179d501c27?auto=format&fit=crop&q=80&w=800"}
              alt={item.title}
              className="object-cover w-full h-full transition-transform duration-500 group-hover:scale-[1.03]"
            />
            {/* Status badge */}
            <div className="absolute top-3 left-3">
              <div className={`badge ${isEnded ? "badge-ended" : "badge-active"}`}>
                {!isEnded && <span className="h-1.5 w-1.5 rounded-full bg-current animate-pulse-soft" />}
                {isEnded ? "Ended" : "Live"}
              </div>
            </div>
          </div>

          {/* Details Card */}
          <div className="card p-6 flex flex-col gap-5">
            <div>
              <h1
                className="text-2xl sm:text-3xl font-bold tracking-tight"
                style={{ color: "var(--text-primary)" }}
              >
                {item.title}
              </h1>
              <p className="mt-2 text-sm" style={{ color: "var(--text-secondary)" }}>
                Listed by{" "}
                <span className="font-semibold" style={{ color: "var(--color-primary)" }}>
                  @{item.owner.username}
                </span>
              </p>
            </div>

            <div className="divider" />

            {/* Description */}
            <div>
              <h3
                className="text-sm font-semibold uppercase tracking-wider mb-3 flex items-center gap-2"
                style={{ color: "var(--text-muted)" }}
              >
                <Info className="h-3.5 w-3.5" />
                Description
              </h3>
              <p
                className="text-sm leading-relaxed"
                style={{ color: "var(--text-secondary)" }}
              >
                {item.description || "No description provided for this item."}
              </p>
            </div>

            {/* Auction Info Grid */}
            <div
              className="grid grid-cols-2 gap-4 p-4 rounded-xl"
              style={{ backgroundColor: "var(--bg-secondary)", border: "1px solid var(--border)" }}
            >
              <div className="flex flex-col gap-1">
                <span className="flex items-center gap-1.5 text-xs font-medium" style={{ color: "var(--text-muted)" }}>
                  <DollarSign className="h-3 w-3" />
                  Starting Bid
                </span>
                <span className="text-sm font-bold" style={{ color: "var(--text-primary)" }}>
                  ${item.startingBid.toString()}
                </span>
              </div>
              <div className="flex flex-col gap-1">
                <span className="flex items-center gap-1.5 text-xs font-medium" style={{ color: "var(--text-muted)" }}>
                  <Calendar className="h-3 w-3" />
                  Ends At
                </span>
                <span className="text-sm font-bold" style={{ color: "var(--text-primary)" }}>
                  {new Date(item.endTime).toLocaleString()}
                </span>
              </div>
            </div>
          </div>
        </div>

        {/* Right Column: Bidding Engine (2/5) */}
        <div className="lg:col-span-2 lg:sticky lg:top-20 h-max">
          {isEnded ? (
            <div
              className="card p-8 flex flex-col items-center justify-center text-center gap-4"
            >
              <div
                className="h-14 w-14 rounded-full flex items-center justify-center"
                style={{ backgroundColor: "rgba(212, 91, 91, 0.1)", color: "var(--color-danger)" }}
              >
                <CheckCircle2 className="h-7 w-7" />
              </div>
              <div>
                <h2 className="text-xl font-bold mb-2" style={{ color: "var(--text-primary)" }}>
                  Auction Ended
                </h2>
                <p className="text-sm" style={{ color: "var(--text-secondary)" }}>
                  Winning Bid:{" "}
                  <span className="font-bold" style={{ color: "var(--text-primary)" }}>
                    ${item.currentBid.toString()}
                  </span>
                </p>
                <p className="text-sm mt-1" style={{ color: "var(--text-secondary)" }}>
                  Winner:{" "}
                  <span className="font-semibold" style={{ color: "var(--color-primary)" }}>
                    {item.highestBidder?.username || "No bids placed"}
                  </span>
                </p>
              </div>
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
