"use client";

import Link from "next/link";
import { useState } from "react";
import { Menu, X, Gavel, User, PlusCircle } from "lucide-react";

export const Navbar = () => {
  const [isOpen, setIsOpen] = useState(false);

  return (
    <header className="sticky top-0 z-[100] w-full glass-header">
      <div className="max-w-7xl mx-auto flex h-14 items-center justify-between px-4 sm:px-6 lg:px-8">
        {/* Logo */}
        <Link href="/" className="flex items-center gap-2.5 group">
          <div
            className="h-8 w-8 rounded-lg flex items-center justify-center transition-transform group-hover:scale-105"
            style={{ backgroundColor: "var(--color-primary)", color: "var(--text-inverse)" }}
          >
            <Gavel className="h-4 w-4" />
          </div>
          <span
            className="text-base font-bold tracking-tight transition-colors"
            style={{ color: "var(--text-primary)" }}
          >
            BidSphere
          </span>
        </Link>

        {/* Desktop Nav */}
        <nav className="hidden items-center gap-1 md:flex">
          <Link
            href="/"
            className="btn btn-ghost text-sm"
          >
            Home
          </Link>
          <Link
            href="/auction/create"
            className="btn btn-primary text-sm ml-2"
          >
            <PlusCircle className="h-4 w-4" />
            Create Auction
          </Link>
          <Link
            href="/profile"
            className="ml-2 flex items-center justify-center h-9 w-9 rounded-full transition-colors"
            style={{ backgroundColor: "var(--bg-secondary)", color: "var(--text-secondary)" }}
            onMouseEnter={(e) => {
              e.currentTarget.style.backgroundColor = "rgba(91, 106, 191, 0.12)";
              e.currentTarget.style.color = "var(--color-primary)";
            }}
            onMouseLeave={(e) => {
              e.currentTarget.style.backgroundColor = "var(--bg-secondary)";
              e.currentTarget.style.color = "var(--text-secondary)";
            }}
          >
            <User className="h-4 w-4" />
          </Link>
        </nav>

        {/* Mobile Toggle */}
        <button
          onClick={() => setIsOpen(!isOpen)}
          className="p-2 rounded-lg md:hidden transition-colors"
          style={{ color: "var(--text-primary)" }}
          aria-label={isOpen ? "Close menu" : "Open menu"}
        >
          {isOpen ? <X className="h-5 w-5" /> : <Menu className="h-5 w-5" />}
        </button>
      </div>

      {/* Mobile Menu */}
      {isOpen && (
        <div
          className="md:hidden animate-slide-down"
          style={{
            borderTop: "1px solid var(--border)",
            backgroundColor: "var(--bg-card)",
            padding: "1rem 1.5rem",
          }}
        >
          <nav className="flex flex-col gap-1">
            <Link
              href="/"
              className="btn btn-ghost justify-start text-base py-3"
              onClick={() => setIsOpen(false)}
            >
              Home
            </Link>
            <Link
              href="/profile"
              className="btn btn-ghost justify-start text-base py-3"
              onClick={() => setIsOpen(false)}
            >
              <User className="h-4 w-4" />
              Account
            </Link>
            <div className="divider my-2" />
            <Link
              href="/auction/create"
              className="btn btn-primary justify-center text-base py-3"
              onClick={() => setIsOpen(false)}
            >
              <PlusCircle className="h-4 w-4" />
              Create Auction
            </Link>
          </nav>
        </div>
      )}
    </header>
  );
};
