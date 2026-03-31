"use client";

import Image from "next/image";
import Link from "next/link";
import { useEffect, useState } from "react";
import { Timer, ArrowUpRight, User } from "lucide-react";

interface AuctionCardProps {
  id: string;
  title: string;
  currentBid: number;
  endTime: Date;
  imageUrl: string;
  highestBidder: string;
}

export const AuctionCard = ({ id, title, currentBid, endTime, imageUrl, highestBidder }: AuctionCardProps) => {
  const [timeLeft, setTimeLeft] = useState("");

  useEffect(() => {
    const timer = setInterval(() => {
      const now = new Date().getTime();
      const distance = endTime.getTime() - now;

      if (distance < 0) {
        clearInterval(timer);
        setTimeLeft("Auction Ended");
        return;
      }

      const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
      const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
      const seconds = Math.floor((distance % (1000 * 60)) / 1000);

      setTimeLeft(`${hours}h ${minutes}m ${seconds}s`);
    }, 1000);

    return () => clearInterval(timer);
  }, [endTime]);

  return (
    <div className="group overflow-hidden rounded-2xl bg-white border border-slate-100 shadow-sm transition hover:shadow-xl hover:ring-2 hover:ring-indigo-500/20">
      <div className="relative aspect-[4/3] overflow-hidden">
        <img
          src={imageUrl}
          alt={title}
          className="h-full w-full object-cover transition duration-500 group-hover:scale-110"
        />
        <div className="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent group-hover:from-black/70" />
        <div className="absolute bottom-4 left-4 right-4 flex items-center justify-between text-white">
          <div className="flex items-center gap-1.5 rounded-full bg-white/20 px-3 py-1 text-xs font-semibold backdrop-blur-md">
            <Timer className="h-3 w-3" />
            <span className="tabular-nums">{timeLeft}</span>
          </div>
        </div>
      </div>

      <div className="flex flex-col gap-4 p-5">
        <h3 className="line-clamp-1 text-lg font-bold text-slate-800 transition group-hover:text-indigo-600">
          {title}
        </h3>
        
        <div className="flex items-end justify-between border-b border-slate-50 pb-4">
          <div className="flex flex-col">
            <span className="text-[10px] font-bold uppercase tracking-wider text-slate-400">Current Bid</span>
            <span className="text-2xl font-black text-slate-900">${currentBid.toFixed(2)}</span>
          </div>
          <div className="flex flex-col items-end">
            <span className="text-[10px] font-bold uppercase tracking-wider text-slate-400">Highest Bidder</span>
            <div className="flex items-center gap-1 text-indigo-600 font-bold">
              <User className="h-3 w-3" />
              <span className="text-sm">{highestBidder}</span>
            </div>
          </div>
        </div>

        <Link
          href={`/auction/${id}`}
          className="flex items-center justify-center gap-2 rounded-xl bg-slate-900 py-3 text-sm font-bold text-white transition hover:bg-indigo-600 active:scale-95"
        >
          Place Your Bid
          <ArrowUpRight className="h-4 w-4" />
        </Link>
      </div>
    </div>
  );
};
