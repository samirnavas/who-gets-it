import type { Metadata } from "next";
import { Inter } from "next/font/google";
import "./globals.css";
import { Navbar } from "@/components/Navbar";
import { Providers } from "@/components/Providers";

const inter = Inter({ subsets: ["latin"], display: "swap" });

export const metadata: Metadata = {
  title: "BidSphere — Real-time Auctions",
  description: "A modern auction platform. Bid on items in real-time.",
};

export default function RootLayout({ children }: Readonly<{ children: React.ReactNode }>) {
  return (
    <html lang="en" className="scroll-smooth">
      <body className={`${inter.className} min-h-screen flex flex-col text-slate-900`}>
        {/* Rich animated background */}
        <div className="page-bg" />

        <Providers>
          <Navbar />
          <main className="flex-grow w-full max-w-7xl mx-auto px-4 sm:px-8 py-8">
            {children}
          </main>

          {/* Footer */}
          <footer style={{ background: "rgba(255,255,255,0.7)", borderTop: "1px solid rgba(37,99,235,0.1)", backdropFilter: "blur(12px)" }}>
            <div className="max-w-7xl mx-auto px-4 sm:px-8 py-6">
              <div className="flex flex-col sm:flex-row items-center justify-between gap-3">
                <div className="flex items-center gap-3">
                  <div className="h-8 w-8 rounded-xl flex items-center justify-center text-white text-xs font-bold"
                    style={{ background: "linear-gradient(135deg, #2563EB, #4F46E5)", boxShadow: "0 4px 12px rgba(37,99,235,0.35)" }}>
                    BS
                  </div>
                  <span className="font-bold text-slate-800">BidSphere</span>
                </div>
                <p className="text-sm text-slate-500">
                  &copy; {new Date().getFullYear()} BidSphere. All rights reserved.
                </p>
              </div>
            </div>
          </footer>
        </Providers>
      </body>
    </html>
  );
}
