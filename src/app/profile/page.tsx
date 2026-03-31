import { getServerSession } from "next-auth/next";
import { authOptions } from "@/app/api/auth/[...nextauth]/route";
import { redirect } from "next/navigation";
import { prisma } from "@/lib/prisma";
import { User, Calendar, Plus, Package } from "lucide-react";
import Link from "next/link";
import LogoutButton from "./LogoutButton";

export default async function ProfilePage() {
  const session = await getServerSession(authOptions);

  if (!session || !session.user) {
    redirect("/auth/login");
  }

  const user = await prisma.user.findUnique({
    where: { id: parseInt((session.user as any).id) },
    include: {
      bids: {
        include: { item: true },
        orderBy: { createdAt: "desc" },
      },
      items: {
        orderBy: { createdAt: "desc" },
      }
    },
  });

  if (!user) {
    redirect("/auth/login");
  }

  const activeBids = user.bids.filter((b: any) => b.item.status === 'active');
  const pastBids = user.bids.filter((b: any) => b.item.status !== 'active');
  const myItems = user.items;

  return (
    <div className="max-w-4xl mx-auto py-8 flex flex-col gap-8 px-4">
      <div className="flex items-center justify-between">
        <h1 className="text-3xl font-extrabold text-slate-900">Your Account</h1>
        <div className="flex items-center gap-4">
          <Link 
            href="/auction/create" 
            className="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-xl font-bold transition shadow-sm"
          >
            <Plus className="h-4 w-4" />
            Put Item to Bid
          </Link>
          <LogoutButton />
        </div>
      </div>

      <div className="bg-white rounded-3xl p-8 shadow-sm border border-slate-100 flex flex-col gap-6">
        <div className="flex items-center gap-6 pb-6 border-b border-slate-100">
          <div className="h-24 w-24 bg-indigo-100 rounded-full flex items-center justify-center text-indigo-600">
            <User className="h-10 w-10" />
          </div>
          <div>
            <h2 className="text-2xl font-bold text-slate-900">@{user.username}</h2>
            <div className="flex items-center gap-4 mt-2 text-sm text-slate-500 font-medium">
              <span className="flex items-center gap-1.5"><Calendar className="h-4 w-4" /> Joined {new Date(user.createdAt).toLocaleDateString()}</span>
              <span className="flex items-center gap-1.5 bg-slate-100 px-2.5 py-1 rounded-md uppercase tracking-wide text-xs">{user.role}</span>
            </div>
          </div>
        </div>

        <div className="grid md:grid-cols-2 gap-8 mt-2">
          {/* Currently Bidding */}
          <div>
            <h3 className="text-xl font-bold text-slate-900 mb-4 flex items-center gap-2">
              <span className="h-2 w-2 rounded-full bg-green-500"></span>
              Currently Bidding
            </h3>
            
            {activeBids.length === 0 ? (
              <div className="text-center py-8 bg-slate-50 rounded-2xl border border-dashed border-slate-200">
                <p className="text-slate-500 font-medium text-sm">No active bids.</p>
                <Link href="/" className="text-indigo-600 font-bold hover:underline mt-2 inline-block text-sm">Explore Auctions</Link>
              </div>
            ) : (
              <div className="flex flex-col gap-3">
                {activeBids.slice(0, 5).map((bid: any) => (
                  <div key={bid.id} className="flex items-center justify-between p-4 rounded-2xl border border-slate-100 hover:border-indigo-100 hover:bg-indigo-50/50 transition bg-white">
                    <div className="flex flex-col truncate pr-4">
                      <Link href={`/auction/${bid.item.id}`} className="font-bold text-slate-900 hover:text-indigo-600 transition truncate">
                        {bid.item.title}
                      </Link>
                      <span className="text-xs text-slate-500 font-medium mt-1">Ends: {new Date(bid.item.endTime).toLocaleDateString()}</span>
                    </div>
                    <div className="flex flex-col items-end shrink-0">
                      <span className="font-black text-lg text-slate-900">${bid.bidAmount.toString()}</span>
                    </div>
                  </div>
                ))}
              </div>
            )}
          </div>

          {/* Recently Bidded */}
          <div>
            <h3 className="text-xl font-bold text-slate-900 mb-4 flex items-center gap-2">
              <span className="h-2 w-2 rounded-full bg-slate-400"></span>
              Recently Bidded (Past)
            </h3>
            
            {pastBids.length === 0 ? (
              <div className="text-center py-8 bg-slate-50 rounded-2xl border border-dashed border-slate-200">
                <p className="text-slate-500 font-medium text-sm">No past bids.</p>
              </div>
            ) : (
              <div className="flex flex-col gap-3">
                {pastBids.slice(0, 5).map((bid: any) => (
                  <div key={bid.id} className="flex items-center justify-between p-4 rounded-2xl border border-slate-100 bg-slate-50">
                    <div className="flex flex-col truncate pr-4 opacity-75">
                      <Link href={`/auction/${bid.item.id}`} className="font-bold text-slate-700 hover:text-indigo-600 transition truncate">
                        {bid.item.title}
                      </Link>
                      <span className="text-xs text-slate-500 mt-1">{bid.item.status.toUpperCase()}</span>
                    </div>
                    <div className="flex flex-col items-end shrink-0 opacity-75">
                      <span className="font-bold text-slate-700">${bid.bidAmount.toString()}</span>
                    </div>
                  </div>
                ))}
              </div>
            )}
          </div>
        </div>

        {/* My Listed Items */}
        <div className="mt-8 pt-8 border-t border-slate-100">
          <h3 className="text-xl font-bold text-slate-900 mb-4 flex items-center gap-2">
            <Package className="h-5 w-5 text-indigo-500" />
            My Listed Auctions
          </h3>
          
          {myItems.length === 0 ? (
            <div className="text-center py-8 bg-slate-50 rounded-2xl border border-dashed border-slate-200">
              <p className="text-slate-500 font-medium">You haven't listed any items.</p>
              <Link href="/auction/create" className="text-indigo-600 font-bold hover:underline mt-2 inline-block">Put an Item to Bid</Link>
            </div>
          ) : (
            <div className="grid sm:grid-cols-2 md:grid-cols-3 gap-4">
              {myItems.map((item: any) => (
                <div key={item.id} className="p-4 rounded-2xl border border-slate-100 hover:border-indigo-100 transition bg-white flex flex-col justify-between">
                  <div>
                     <Link href={`/auction/${item.id}`} className="font-bold text-slate-900 hover:text-indigo-600 transition line-clamp-1 mb-1">
                        {item.title}
                     </Link>
                     <div className="flex items-center justify-between mt-2">
                       <span className={`text-[10px] font-bold px-2 py-0.5 rounded uppercase tracking-wider ${
                         item.status === 'active' ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-600'
                       }`}>
                         {item.status}
                       </span>
                       <span className="text-xs font-semibold text-slate-500">
                         Current bid: <span className="text-slate-900">${item.currentBid.toString()}</span>
                       </span>
                     </div>
                  </div>
                </div>
              ))}
            </div>
          )}
        </div>

      </div>
    </div>
  );
}
