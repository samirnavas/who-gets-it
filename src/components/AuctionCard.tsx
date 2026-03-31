"use client";

import Link from "next/link";
import { useEffect, useState } from "react";
import { ArrowUpRight, User, Clock } from "lucide-react";

interface AuctionCardProps {
  id: string;
  title: string;
  currentBid: number;
  endTime: Date;
  imageUrl: string;
  highestBidder: string;
  startingBid?: number;
}

export const AuctionCard = ({ id, title, currentBid, endTime, imageUrl, highestBidder }: AuctionCardProps) => {
  const [timeLeft, setTimeLeft] = useState("");
  const [isEnded, setIsEnded] = useState(false);

  useEffect(() => {
    const timer = setInterval(() => {
      const now = new Date().getTime();
      const distance = endTime.getTime() - now;

      if (distance < 0) {
        clearInterval(timer);
        setTimeLeft("Ended");
        setIsEnded(true);
        return;
      }

      const days = Math.floor(distance / (1000 * 60 * 60 * 24));
      const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
      const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
      const seconds = Math.floor((distance % (1000 * 60)) / 1000);

      if (days > 0) {
        setTimeLeft(`${days}d ${hours}h`);
      } else if (hours > 0) {
        setTimeLeft(`${hours}h ${minutes}m`);
      } else {
        setTimeLeft(`${minutes}m ${seconds}s`);
      }
    }, 1000);

    return () => clearInterval(timer);
  }, [endTime]);

  return (
    <Link href={`/auction/${id}`} className="card card-hover group block overflow-hidden shadow-sm hover:shadow-xl transition-all duration-300">
      {/* Image */}
      <div className="relative aspect-[4/3] overflow-hidden bg-secondary">
        <img
          src={imageUrl}
          alt={title}
          className="h-full w-full object-cover transition-transform duration-700 ease-out group-hover:scale-110"
        />
        {/* Gradient overlay */}
        <div
          className="absolute inset-0"
          style={{
            background: "linear-gradient(to top, rgba(0,0,0,0.7) 0%, rgba(0,0,0,0.2) 50%, transparent 100%)",
          }}
        />
        {/* Timer badge */}
        <div className="absolute top-3 right-3">
          <div
            className="flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold shadow-lg backdrop-blur-md transition-colors"
            style={{
              backgroundColor: isEnded ? "rgba(0,0,0,0.7)" : "rgba(255,255,255,0.15)",
              color: "#ffffff",
              border: "1px solid rgba(255,255,255,0.1)",
            }}
          >
            {!isEnded && (
              <span
                className="h-2 w-2 rounded-full animate-pulse shadow-sm"
                style={{ backgroundColor: "var(--color-danger)" }}
              />
            )}
            <Clock className="h-3.5 w-3.5" />
            <span className="tracking-wide">{timeLeft || "..."}</span>
          </div>
        </div>
        {/* Title on image */}
        <div className="absolute bottom-4 left-4 right-4 translate-y-2 group-hover:translate-y-0 transition-transform duration-300">
          <h3 className="text-white font-extrabold text-lg leading-tight line-clamp-2 drop-shadow-lg">
            {title}
          </h3>
        </div>
      </div>

      {/* Card body */}
      <div className="p-5 flex flex-col gap-5">
        {/* Bid & Bidder row */}
        <div className="flex items-end justify-between gap-3">
          <div>
            <span className="text-xs font-semibold block mb-1 uppercase tracking-wider" style={{ color: "var(--text-muted)" }}>
              Current Bid
            </span>
            <span className="text-2xl font-black tracking-tight" style={{ color: "var(--text-primary)" }}>
              ${currentBid.toLocaleString()}
            </span>
          </div>
          <div className="flex flex-col items-end gap-1">
            <span className="text-[10px] font-semibold uppercase tracking-wider" style={{ color: "var(--text-muted)" }}>
              Highest Bidder
            </span>
            <div className="flex items-center gap-1.5">
              <div
                className="h-5 w-5 rounded-full flex items-center justify-center shrink-0"
                style={{ backgroundColor: "rgba(91, 106, 191, 0.1)", color: "var(--color-primary)" }}
              >
                <User className="h-3 w-3" />
              </div>
              <span className="text-sm font-medium truncate max-w-[90px]" style={{ color: "var(--text-secondary)" }}>
                {highestBidder}
              </span>
            </div>
          </div>
        </div>

        {/* CTA */}
        <div
          className="flex items-center justify-center gap-2 py-3 rounded-xl text-sm font-bold transition-all shadow-sm group-hover:shadow-md"
          style={{
            backgroundColor: "var(--bg-secondary)",
            color: "var(--color-primary)",
          }}
        >
          {isEnded ? "View Result" : "Place a Bid"}
          <ArrowUpRight className="h-4 w-4 transition-transform duration-300 group-hover:translate-x-1 group-hover:-translate-y-1" />
        </div>
      </div>
    </Link>
  );
};
