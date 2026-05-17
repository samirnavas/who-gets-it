"use client";

import Link from "next/link";
import { useState } from "react";
import { Menu, X, User, PlusCircle } from "lucide-react";

export const Navbar = () => {
  const [isOpen, setIsOpen] = useState(false);

  return (
    <header className="sticky top-0 z-50 w-full">
      <div className="max-w-7xl mx-auto px-4 sm:px-8 pt-4">
        <div className="nav-glass rounded-2xl px-5 sm:px-6">
          <div className="flex h-14 items-center justify-between">
            {/* Logo */}
            <Link href="/" className="flex items-center gap-3 group">
              <div
                className="h-8 w-8 rounded-xl flex items-center justify-center text-white font-bold text-xs transition-all group-hover:scale-110"
                style={{ background: "linear-gradient(135deg, #2563EB, #4F46E5)", boxShadow: "0 4px 12px rgba(37,99,235,0.35)" }}
              >
                BS
              </div>
              <span className="text-base font-bold text-slate-800">BidSphere</span>
            </Link>

            {/* Desktop Nav */}
            <nav className="hidden items-center gap-2 md:flex">
              <Link
                href="/auctions"
                className="text-sm font-semibold px-4 py-2 rounded-xl text-slate-500 hover:text-blue-600 hover:bg-blue-50 transition-all"
              >
                Explore
              </Link>
              <Link href="/auction/create" className="btn-primary text-sm px-5 py-2.5">
                <PlusCircle className="h-4 w-4" />
                Create
              </Link>
              <Link
                href="/profile"
                className="flex items-center justify-center h-9 w-9 rounded-xl transition-all ml-1 text-blue-600 hover:bg-blue-50"
                style={{ background: "rgba(37,99,235,0.07)", border: "1px solid rgba(37,99,235,0.15)" }}
              >
                <User className="h-4 w-4" />
              </Link>
            </nav>

            {/* Mobile Toggle */}
            <button
              onClick={() => setIsOpen(!isOpen)}
              className="p-2 rounded-xl md:hidden transition-all text-slate-500 hover:bg-blue-50"
              aria-label="Toggle menu"
            >
              {isOpen ? <X className="h-5 w-5" /> : <Menu className="h-5 w-5" />}
            </button>
          </div>
        </div>
      </div>

      {/* Mobile Menu */}
      {isOpen && (
        <div className="max-w-7xl mx-auto px-4 sm:px-8 mt-2 md:hidden">
          <div className="nav-glass rounded-2xl px-5 py-4 animate-fade-in">
            <nav className="flex flex-col gap-1">
              <Link
                href="/auctions"
                className="text-sm font-semibold py-2.5 px-3 rounded-xl text-slate-600 hover:text-blue-600 hover:bg-blue-50 transition-all"
                onClick={() => setIsOpen(false)}
              >
                Explore Auctions
              </Link>
              <Link
                href="/profile"
                className="flex items-center gap-2 text-sm font-semibold py-2.5 px-3 rounded-xl text-slate-600 hover:text-blue-600 hover:bg-blue-50 transition-all"
                onClick={() => setIsOpen(false)}
              >
                <User className="h-4 w-4" /> Account
              </Link>
              <div className="my-2 h-px bg-blue-100" />
              <Link href="/auction/create" className="btn-primary text-sm py-3" onClick={() => setIsOpen(false)}>
                <PlusCircle className="h-5 w-5" /> Create Auction
              </Link>
            </nav>
          </div>
        </div>
      )}
    </header>
  );
};
