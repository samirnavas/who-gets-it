import { getServerSession } from "next-auth/next";
import { authOptions } from "@/app/api/auth/[...nextauth]/route";
import { redirect } from "next/navigation";
import { prisma } from "@/lib/prisma";
import { User, Mail, Calendar, LogOut } from "lucide-react";
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
        take: 5,
      },
    },
  });

  if (!user) {
    redirect("/auth/login");
  }

  return (
    <div className="max-w-4xl mx-auto py-8 flex flex-col gap-8">
      <div className="flex items-center justify-between">
        <h1 className="text-3xl font-extrabold text-slate-900">Your Account</h1>
        <LogoutButton />
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

        <div>
          <h3 className="text-xl font-bold text-slate-900 mb-4">Recent Bidding Activity</h3>
          
          {user.bids.length === 0 ? (
            <div className="text-center py-8 bg-slate-50 rounded-2xl border border-dashed border-slate-200">
              <p className="text-slate-500 font-medium">You haven't placed any bids yet.</p>
              <Link href="/" className="text-indigo-600 font-bold hover:underline mt-2 inline-block">Start Bidding</Link>
            </div>
          ) : (
            <div className="flex flex-col gap-3">
              {user.bids.map((bid) => (
                <div key={bid.id} className="flex items-center justify-between p-4 rounded-2xl border border-slate-100 hover:border-indigo-100 hover:bg-indigo-50/50 transition bg-white">
                  <div className="flex flex-col">
                    <Link href={`/auction/${bid.item.id}`} className="font-bold text-slate-900 hover:text-indigo-600 transition">
                      {bid.item.title}
                    </Link>
                    <span className="text-xs text-slate-500 font-medium mt-1">{new Date(bid.createdAt).toLocaleString()}</span>
                  </div>
                  <div className="flex flex-col items-end">
                    <span className="font-black text-lg text-slate-900">${bid.bidAmount.toString()}</span>
                    <span className={`text-xs font-bold uppercase tracking-wide ${
                      bid.status === 'active' ? 'text-green-600' : 'text-slate-400'
                    }`}>
                      {bid.status}
                    </span>
                  </div>
                </div>
              ))}
              <Link href="/my-bids" className="text-center text-sm font-bold text-indigo-600 hover:text-indigo-700 py-3 mt-2">
                View All Bids →
              </Link>
            </div>
          )}
        </div>
      </div>
    </div>
  );
}
