"use client";

import Link from "next/link";
import { useState } from "react";
import { Menu, X, Gavel, User, LogOut, Bell } from "lucide-react";

export const Navbar = () => {
  const [isOpen, setIsOpen] = useState(false);
  const [isNotificationsOpen, setIsNotificationsOpen] = useState(false);

  return (
    <header className="sticky top-0 z-50 w-full border-b border-slate-200 bg-white/80 backdrop-blur-md">
      <div className="container mx-auto flex h-16 items-center justify-between px-4">
        <Link href="/" className="flex items-center gap-2 text-2xl font-bold tracking-tight text-indigo-600 transition hover:opacity-80">
          <Gavel className="h-8 w-8" />
          <span>Who Gets It</span>
        </Link>

        {/* Desktop Nav */}
        <nav className="hidden items-center gap-8 md:flex">
          <Link href="/auctions" className="text-sm font-medium text-slate-700 transition hover:text-indigo-600">
            Active Auctions
          </Link>
          <Link href="/my-bids" className="text-sm font-medium text-slate-700 transition hover:text-indigo-600">
            My Bids
          </Link>
          
          <div className="flex items-center gap-4 border-l border-slate-200 pl-4">
            <button
              onClick={() => setIsNotificationsOpen(!isNotificationsOpen)}
              className="relative p-2 text-slate-600 transition hover:bg-slate-100 hover:text-indigo-600 rounded-full"
            >
              <Bell className="h-5 w-5" />
              <span className="absolute top-1.5 right-1.5 h-2 w-2 rounded-full bg-red-500 ring-2 ring-white" />
            </button>
            
            <Link href="/profile" className="flex items-center gap-2 p-2 text-slate-600 transition hover:bg-indigo-50 hover:text-indigo-600 rounded-lg">
              <User className="h-5 w-5" />
              <span className="text-sm font-medium">Account</span>
            </Link>
          </div>
        </nav>

        {/* Mobile Nav Toggle */}
        <button
          onClick={() => setIsOpen(!isOpen)}
          className="p-2 text-slate-600 md:hidden"
        >
          {isOpen ? <X className="h-6 w-6" /> : <Menu className="h-6 w-6" />}
        </button>
      </div>

      {/* Mobile Menu */}
      {isOpen && (
        <div className="border-t border-slate-100 bg-white p-4 md:hidden animate-in slide-in-from-top-4 duration-300">
          <nav className="flex flex-col gap-4">
            <Link href="/auctions" className="text-lg font-medium py-2" onClick={() => setIsOpen(false)}>Active Auctions</Link>
            <Link href="/my-bids" className="text-lg font-medium py-2" onClick={() => setIsOpen(false)}>My Bids</Link>
            <Link href="/profile" className="text-lg font-medium py-2" onClick={() => setIsOpen(false)}>Account</Link>
            <button className="flex w-full items-center gap-2 rounded-xl bg-slate-100 p-3 text-red-600 transition hover:bg-red-50">
              <LogOut className="h-5 w-5" />
              <span className="font-semibold">Sign Out</span>
            </button>
          </nav>
        </div>
      )}
    </header>
  );
};
