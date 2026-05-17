"use client";

import Link from "next/link";
import { useEffect, useState } from "react";
import { ArrowRight, User, Clock } from "lucide-react";
import { parseDateTime } from "@/lib/date";

interface AuctionCardProps {
  id: string;
  title: string;
  currentBid: number;
  endTime: string | Date;
  imageUrl: string;
  highestBidder: string;
  startingBid?: number;
}

export const AuctionCard = ({ id, title, currentBid, endTime, imageUrl, highestBidder }: AuctionCardProps) => {
  const [timeLeft, setTimeLeft] = useState("");
  const [isEnded, setIsEnded] = useState(false);

  useEffect(() => {
    const parsedEndTime = parseDateTime(endTime);
    const tick = () => {
      const dist = parsedEndTime.getTime() - Date.now();
      if (dist < 0) { setTimeLeft("Ended"); setIsEnded(true); return; }
      const d = Math.floor(dist / 86400000);
      const h = Math.floor((dist % 86400000) / 3600000);
      const m = Math.floor((dist % 3600000) / 60000);
      const s = Math.floor((dist % 60000) / 1000);
      setTimeLeft(d > 0 ? `${d}d ${h}h` : h > 0 ? `${h}h ${m}m` : `${m}m ${s}s`);
    };
    tick();
    const timer = setInterval(tick, 1000);
    return () => clearInterval(timer);
  }, [endTime]);

  return (
    <Link href={`/auction/${id}`} className="group block card overflow-hidden" style={{ padding: 0 }}>
      {/* Image */}
      <div className="relative aspect-[4/3] overflow-hidden bg-blue-50">
        <img
          src={imageUrl}
          alt={title}
          className="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105"
        />
        {/* Gradient overlay for text readability */}
        <div className="absolute inset-0" style={{ background: "linear-gradient(to top, rgba(15,23,42,0.65) 0%, rgba(15,23,42,0.15) 45%, transparent 100%)" }} />

        {/* Timer chip */}
        <div className="absolute top-3 right-3">
          <div
            className="flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-xs font-bold"
            style={{ background: "rgba(255,255,255,0.88)", backdropFilter: "blur(8px)", border: "1px solid rgba(255,255,255,0.5)", color: isEnded ? "#64748B" : "#2563EB" }}
          >
            {!isEnded && <span className="h-1.5 w-1.5 rounded-full animate-pulse bg-blue-500" />}
            <Clock className="h-3 w-3" />
            {timeLeft || "…"}
          </div>
        </div>

        {/* Title over image */}
        <div className="absolute bottom-3 left-3 right-3">
          <h3 className="text-white font-bold text-sm leading-snug line-clamp-2 drop-shadow">{title}</h3>
        </div>
      </div>

      {/* Body */}
      <div className="p-4 flex items-center justify-between gap-3 bg-white">
        <div>
          <p className="text-[10px] font-bold uppercase tracking-wider mb-0.5 text-blue-500">Current Bid</p>
          <p className="text-lg font-extrabold text-slate-900">${currentBid.toLocaleString()}</p>
          <div className="flex items-center gap-1.5 mt-1">
            <User className="h-3 w-3 text-slate-400" />
            <span className="text-xs truncate max-w-[100px] text-slate-500">{highestBidder}</span>
          </div>
        </div>
        <div
          className="h-9 w-9 rounded-xl flex items-center justify-center shrink-0 transition-all group-hover:scale-110"
          style={{ background: "linear-gradient(135deg, #2563EB, #4F46E5)", boxShadow: "0 4px 14px rgba(37,99,235,0.3)", color: "white" }}
        >
          <ArrowRight className="h-4 w-4" />
        </div>
      </div>
    </Link>
  );
};
