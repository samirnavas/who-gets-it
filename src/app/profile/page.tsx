import { getServerSession } from "next-auth/next";
import { authOptions } from "@/app/api/auth/[...nextauth]/route";
import { redirect } from "next/navigation";
import supabase from "@/lib/db";
import { User, Plus, Package, TrendingUp, Clock, History } from "lucide-react";
import Link from "next/link";
import LogoutButton from "./LogoutButton";
import { parseDateTime } from "@/lib/date";

export default async function ProfilePage() {
  const session = await getServerSession(authOptions);
  if (!session?.user) redirect("/auth/login");

  const userId = parseInt((session.user as any).id);

  const { data: userBase } = await supabase.from("users").select("*").eq("id", userId).single();
  if (!userBase) redirect("/auth/login");

  const { data: bidRows } = await supabase
    .from("bids")
    .select(`*, item:items!bids_item_id_fkey ( title, end_time, status )`)
    .eq("user_id", userId)
    .order("id", { ascending: false });

  const { data: itemRows } = await supabase
    .from("items")
    .select("*")
    .eq("user_id", userId)
    .order("id", { ascending: false });

  const bids = (bidRows || []).map((b: any) => ({
    id: b.id, bidAmount: b.bid_amount,
    item: { id: b.item_id, title: b.item?.title, status: b.item?.status, endTime: b.item?.end_time },
  }));
  const items = (itemRows || []).map((i: any) => ({
    id: i.id, title: i.title, status: i.status as string, currentBid: i.current_bid,
  }));

  const activeBids = bids.filter(b => b.item.status === "active");
  const pastBids   = bids.filter(b => b.item.status !== "active");

  return (
    <div className="max-w-4xl mx-auto py-4 flex flex-col gap-6 animate-fade-in-up pb-12">

      <div className="flex items-center justify-between">
        <h1 className="text-3xl font-extrabold text-slate-900 tracking-tight">Account</h1>
        <div className="flex items-center gap-3">
          <Link href="/auction/create" className="btn-primary text-sm px-4 py-2.5">
            <Plus className="h-4 w-4" /> New Auction
          </Link>
          <LogoutButton />
        </div>
      </div>

      <div className="card-solid rounded-2xl p-6 flex flex-col gap-6">
        {/* User info */}
        <div className="flex items-center gap-5 pb-5" style={{ borderBottom: "1px solid rgba(37,99,235,0.1)" }}>
          <div className="rounded-2xl flex items-center justify-center shrink-0"
            style={{ height: 72, width: 72, background: "linear-gradient(135deg, #2563EB, #4F46E5)", boxShadow: "0 6px 20px rgba(37,99,235,0.25)" }}>
            <User className="h-8 w-8 text-white" />
          </div>
          <div>
            <h2 className="text-2xl font-extrabold text-slate-900">@{userBase.username}</h2>
            <div className="flex items-center gap-2 mt-1.5">
              <span className="px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider rounded-full"
                style={{ background: "rgba(37,99,235,0.08)", border: "1px solid rgba(37,99,235,0.18)", color: "#2563EB" }}>
                {userBase.role}
              </span>
            </div>
          </div>
        </div>

        {/* Stats */}
        <div className="grid grid-cols-3 gap-3">
          {[
            { label: "Active Bids", val: activeBids.length, color: "#2563EB" },
            { label: "Past Bids",   val: pastBids.length,   color: "#6366F1" },
            { label: "Listed",      val: items.length,      color: "#8B5CF6" },
          ].map(({ label, val, color }) => (
            <div key={label} className="stat-block">
              <p className="text-2xl font-extrabold text-slate-900">{val}</p>
              <p className="text-xs font-bold mt-1 uppercase tracking-wider" style={{ color }}>{label}</p>
            </div>
          ))}
        </div>

        {/* Bids grid */}
        <div className="grid md:grid-cols-2 gap-6 pt-2">
          <div>
            <h3 className="text-xs font-bold uppercase tracking-wider flex items-center gap-2 mb-4 text-blue-500">
              <TrendingUp className="h-4 w-4" /> Active Bids
            </h3>
            {activeBids.length === 0 ? (
              <div className="py-8 text-center rounded-xl" style={{ background: "rgba(37,99,235,0.04)", border: "1px dashed rgba(37,99,235,0.15)" }}>
                <p className="text-sm mb-2 text-slate-500">No active bids yet.</p>
                <Link href="/" className="text-sm font-semibold text-blue-600 hover:underline">Browse auctions</Link>
              </div>
            ) : (
              <div className="flex flex-col gap-2">
                {activeBids.slice(0, 5).map(bid => (
                  <Link key={bid.id} href={`/auction/${bid.item.id}`}
                    className="flex items-center justify-between p-3 rounded-xl transition-all hover:bg-blue-50"
                    style={{ background: "rgba(37,99,235,0.04)", border: "1px solid rgba(37,99,235,0.1)" }}>
                    <div className="min-w-0 pr-3">
                      <p className="text-sm font-semibold text-slate-800 truncate">{bid.item.title}</p>
                      <p className="text-xs mt-0.5 flex items-center gap-1 text-slate-400">
                        <Clock className="h-3 w-3" /> {parseDateTime(bid.item.endTime).toLocaleDateString()}
                      </p>
                    </div>
                    <p className="text-sm font-bold shrink-0 text-blue-600">${bid.bidAmount}</p>
                  </Link>
                ))}
              </div>
            )}
          </div>

          <div>
            <h3 className="text-xs font-bold uppercase tracking-wider flex items-center gap-2 mb-4 text-indigo-500">
              <History className="h-4 w-4" /> Past Bids
            </h3>
            {pastBids.length === 0 ? (
              <div className="py-8 text-center rounded-xl" style={{ background: "rgba(37,99,235,0.03)", border: "1px dashed rgba(37,99,235,0.12)" }}>
                <p className="text-sm text-slate-500">No past bids.</p>
              </div>
            ) : (
              <div className="flex flex-col gap-2">
                {pastBids.slice(0, 5).map(bid => (
                  <Link key={bid.id} href={`/auction/${bid.item.id}`}
                    className="flex items-center justify-between p-3 rounded-xl transition-all hover:bg-slate-50"
                    style={{ background: "rgba(100,116,139,0.04)", border: "1px solid rgba(100,116,139,0.12)" }}>
                    <div className="min-w-0 pr-3">
                      <p className="text-sm truncate text-slate-500">{bid.item.title}</p>
                      <span className="text-[10px] font-bold uppercase tracking-wider px-1.5 py-0.5 rounded mt-1 inline-block bg-slate-100 text-slate-400">
                        {bid.item.status}
                      </span>
                    </div>
                    <p className="text-sm font-semibold shrink-0 text-slate-400">${bid.bidAmount}</p>
                  </Link>
                ))}
              </div>
            )}
          </div>
        </div>

        {/* My Items */}
        <div className="pt-5" style={{ borderTop: "1px solid rgba(37,99,235,0.1)" }}>
          <h3 className="text-xs font-bold uppercase tracking-wider flex items-center gap-2 mb-4 text-violet-500">
            <Package className="h-4 w-4" /> My Auctions
          </h3>
          {items.length === 0 ? (
            <div className="py-10 text-center rounded-xl" style={{ background: "rgba(37,99,235,0.03)", border: "1px dashed rgba(37,99,235,0.12)" }}>
              <p className="text-sm mb-2 text-slate-500">You haven&apos;t listed any items.</p>
              <Link href="/auction/create" className="text-sm font-semibold text-blue-600 hover:underline">Create your first auction</Link>
            </div>
          ) : (
            <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-3">
              {items.map(item => (
                <Link key={item.id} href={`/auction/${item.id}`}
                  className="flex flex-col gap-2.5 p-4 rounded-xl transition-all hover:bg-blue-50"
                  style={{ background: "rgba(37,99,235,0.04)", border: "1px solid rgba(37,99,235,0.1)" }}>
                  <p className="text-sm font-semibold text-slate-800 truncate">{item.title}</p>
                  <div className="flex items-center justify-between">
                    <span className="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full"
                      style={item.status === "active"
                        ? { background: "rgba(37,99,235,0.1)", border: "1px solid rgba(37,99,235,0.2)", color: "#2563EB" }
                        : { background: "rgba(100,116,139,0.08)", color: "#64748B" }}>
                      {item.status}
                    </span>
                    <p className="text-sm font-bold text-blue-600">${item.currentBid}</p>
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
