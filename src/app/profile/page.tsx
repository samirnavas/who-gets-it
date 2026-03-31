import { getServerSession } from "next-auth/next";
import { authOptions } from "@/app/api/auth/[...nextauth]/route";
import { redirect } from "next/navigation";
import db from "@/lib/db";
import { User, Calendar, Plus, Package, TrendingUp, Clock } from "lucide-react";
import Link from "next/link";
import LogoutButton from "./LogoutButton";
import { RowDataPacket } from "mysql2";

export default async function ProfilePage() {
  const session = await getServerSession(authOptions);

  if (!session || !session.user) {
    redirect("/auth/login");
  }

  const userId = parseInt((session.user as any).id);

  const [userRows] = await db.query<RowDataPacket[]>(
    "SELECT * FROM users WHERE id = ?",
    [userId]
  );
  const userBase = userRows[0];

  if (!userBase) {
    redirect("/auth/login");
  }

  const [bidRows] = await db.query<RowDataPacket[]>(
    `SELECT b.*, i.title, i.end_time, i.status as item_status 
     FROM bids b 
     JOIN items i ON b.item_id = i.id 
     WHERE b.user_id = ? 
     ORDER BY b.created_at DESC`,
    [userId]
  );

  const [itemRows] = await db.query<RowDataPacket[]>(
    "SELECT * FROM items WHERE user_id = ? ORDER BY created_at DESC",
    [userId]
  );

  const user = {
    username: userBase.username as string,
    role: userBase.role as string,
    createdAt: userBase.created_at,
    bids: bidRows.map(b => ({
      id: b.id,
      bidAmount: b.bid_amount,
      item: { id: b.item_id, title: b.title, status: b.item_status, endTime: b.end_time }
    })),
    items: itemRows.map(i => ({
      currentBid: i.current_bid,
      status: i.status as string,
      title: i.title as string,
      id: i.id
    }))
  };

  const activeBids = user.bids.filter((b: any) => b.item.status === 'active');
  const pastBids = user.bids.filter((b: any) => b.item.status !== 'active');
  const myItems = user.items;

  return (
    <div className="max-w-4xl mx-auto py-4 flex flex-col gap-6 animate-fade-in-up">
      {/* Header */}
      <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <h1 className="text-2xl font-bold" style={{ color: "var(--text-primary)" }}>
          Account
        </h1>
        <div className="flex items-center gap-3">
          <Link href="/auction/create" className="btn btn-primary text-sm">
            <Plus className="h-4 w-4" />
            New Auction
          </Link>
          <LogoutButton />
        </div>
      </div>

      {/* Profile Card */}
      <div className="card p-6 flex flex-col gap-6">
        {/* User info */}
        <div
          className="flex items-center gap-4 pb-5"
          style={{ borderBottom: "1px solid var(--border)" }}
        >
          <div
            className="h-16 w-16 rounded-full flex items-center justify-center shrink-0"
            style={{ backgroundColor: "rgba(91, 106, 191, 0.1)", color: "var(--color-primary)" }}
          >
            <User className="h-7 w-7" />
          </div>
          <div className="min-w-0">
            <h2 className="text-lg font-bold truncate" style={{ color: "var(--text-primary)" }}>
              @{user.username}
            </h2>
            <div className="flex flex-wrap items-center gap-3 mt-1.5">
              <span
                className="flex items-center gap-1.5 text-xs font-medium"
                style={{ color: "var(--text-muted)" }}
              >
                <Calendar className="h-3.5 w-3.5" />
                Joined {new Date(user.createdAt).toLocaleDateString()}
              </span>
              <span className="badge badge-active">{user.role}</span>
            </div>
          </div>
        </div>

        {/* Stats Row */}
        <div className="stats-panel">
          <div>
            <span className="text-xl font-bold block" style={{ color: "var(--text-primary)" }}>
              {activeBids.length}
            </span>
            <span className="text-xs font-medium" style={{ color: "var(--text-muted)" }}>Active Bids</span>
          </div>
          <div>
            <span className="text-xl font-bold block" style={{ color: "var(--text-primary)" }}>
              {pastBids.length}
            </span>
            <span className="text-xs font-medium" style={{ color: "var(--text-muted)" }}>Past Bids</span>
          </div>
          <div>
            <span className="text-xl font-bold block" style={{ color: "var(--text-primary)" }}>
              {myItems.length}
            </span>
            <span className="text-xs font-medium" style={{ color: "var(--text-muted)" }}>Listed Items</span>
          </div>
        </div>

        {/* Bids Sections */}
        <div className="grid md:grid-cols-2 gap-6">
          {/* Active Bids */}
          <div>
            <h3
              className="text-sm font-semibold uppercase tracking-wider mb-3 flex items-center gap-2"
              style={{ color: "var(--text-muted)" }}
            >
              <TrendingUp className="h-3.5 w-3.5" style={{ color: "var(--color-success)" }} />
              Active Bids
            </h3>

            {activeBids.length === 0 ? (
              <div
                className="py-8 text-center rounded-xl"
                style={{ backgroundColor: "var(--bg-secondary)", border: "1.5px dashed var(--border)" }}
              >
                <p className="text-xs font-medium mb-1" style={{ color: "var(--text-muted)" }}>
                  No active bids yet.
                </p>
                <Link
                  href="/"
                  className="text-xs font-semibold hover:underline"
                  style={{ color: "var(--color-primary)" }}
                >
                  Browse auctions
                </Link>
              </div>
            ) : (
              <div className="flex flex-col gap-2">
                {activeBids.slice(0, 5).map((bid: any) => (
                  <Link
                    key={bid.id}
                    href={`/auction/${bid.item.id}`}
                    className="flex items-center justify-between p-3 rounded-xl transition-colors bg-[var(--bg-card)] border border-[var(--border)] hover:border-[var(--border-focus)] hover:bg-[rgba(91,106,191,0.04)]"
                  >
                    <div className="flex flex-col min-w-0 pr-3">
                      <span
                        className="text-sm font-semibold truncate"
                        style={{ color: "var(--text-primary)" }}
                      >
                        {bid.item.title}
                      </span>
                      <span className="text-xs mt-0.5 flex items-center gap-1" style={{ color: "var(--text-muted)" }}>
                        <Clock className="h-3 w-3" />
                        Ends {new Date(bid.item.endTime).toLocaleDateString()}
                      </span>
                    </div>
                    <span
                      className="text-sm font-bold shrink-0"
                      style={{ color: "var(--text-primary)" }}
                    >
                      ${bid.bidAmount.toString()}
                    </span>
                  </Link>
                ))}
              </div>
            )}
          </div>

          {/* Past Bids */}
          <div>
            <h3
              className="text-sm font-semibold uppercase tracking-wider mb-3 flex items-center gap-2"
              style={{ color: "var(--text-muted)" }}
            >
              <Clock className="h-3.5 w-3.5" />
              Past Bids
            </h3>

            {pastBids.length === 0 ? (
              <div
                className="py-8 text-center rounded-xl"
                style={{ backgroundColor: "var(--bg-secondary)", border: "1.5px dashed var(--border)" }}
              >
                <p className="text-xs font-medium" style={{ color: "var(--text-muted)" }}>
                  No past bids.
                </p>
              </div>
            ) : (
              <div className="flex flex-col gap-2">
                {pastBids.slice(0, 5).map((bid: any) => (
                  <Link
                    key={bid.id}
                    href={`/auction/${bid.item.id}`}
                    className="flex items-center justify-between p-3 rounded-xl"
                    style={{
                      backgroundColor: "var(--bg-secondary)",
                      border: "1px solid var(--border)",
                      opacity: 0.75,
                    }}
                  >
                    <div className="flex flex-col min-w-0 pr-3">
                      <span
                        className="text-sm font-medium truncate"
                        style={{ color: "var(--text-secondary)" }}
                      >
                        {bid.item.title}
                      </span>
                      <span className="badge badge-ended text-[10px] mt-1 w-fit">
                        {bid.item.status.toUpperCase()}
                      </span>
                    </div>
                    <span
                      className="text-sm font-semibold shrink-0"
                      style={{ color: "var(--text-secondary)" }}
                    >
                      ${bid.bidAmount.toString()}
                    </span>
                  </Link>
                ))}
              </div>
            )}
          </div>
        </div>

        {/* My Listed Items */}
        <div style={{ borderTop: "1px solid var(--border)", paddingTop: "1.5rem" }}>
          <h3
            className="text-sm font-semibold uppercase tracking-wider mb-3 flex items-center gap-2"
            style={{ color: "var(--text-muted)" }}
          >
            <Package className="h-3.5 w-3.5" style={{ color: "var(--color-primary)" }} />
            My Auctions
          </h3>

          {myItems.length === 0 ? (
            <div
              className="py-8 text-center rounded-xl"
              style={{ backgroundColor: "var(--bg-secondary)", border: "1.5px dashed var(--border)" }}
            >
              <p className="text-xs font-medium mb-1" style={{ color: "var(--text-muted)" }}>
                You haven&apos;t listed any items.
              </p>
              <Link
                href="/auction/create"
                className="text-xs font-semibold hover:underline"
                style={{ color: "var(--color-primary)" }}
              >
                Create your first auction
              </Link>
            </div>
          ) : (
            <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-3">
              {myItems.map((item: any) => (
                <Link
                  key={item.id}
                  href={`/auction/${item.id}`}
                  className="card card-hover p-4 flex flex-col gap-2.5 block"
                >
                  <span
                    className="text-sm font-semibold truncate"
                    style={{ color: "var(--text-primary)" }}
                  >
                    {item.title}
                  </span>
                  <div className="flex items-center justify-between">
                    <span className={`badge ${item.status === 'active' ? 'badge-active' : 'badge-ended'}`}>
                      {item.status}
                    </span>
                    <span className="text-xs font-semibold" style={{ color: "var(--text-secondary)" }}>
                      ${item.currentBid.toString()}
                    </span>
                  </div>
                </Link>
              ))}
            </div>
          )}
        </div>
      </div>
    </div>
  );
}
