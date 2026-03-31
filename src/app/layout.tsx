import type { Metadata } from "next";
import { Inter } from "next/font/google";
import "./globals.css";
import { Navbar } from "@/components/Navbar";
import { Providers } from "@/components/Providers";

const inter = Inter({
  subsets: ["latin"],
  display: "swap",
});

export const metadata: Metadata = {
  title: "BidSphere — Real-time Auctions",
  description: "A modern auction platform. Bid on items in real-time with a clean, intuitive experience.",
};

export default function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  return (
    <html lang="en" className="scroll-smooth">
      <body
        className={`${inter.className} min-h-screen flex flex-col`}
        style={{ backgroundColor: "var(--bg-primary)", color: "var(--text-primary)" }}
      >
        <Providers>
          <Navbar />
          <main className="flex-grow w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">
            {children}
          </main>
          <footer
            className="mt-auto border-t"
            style={{ borderColor: "var(--border)", backgroundColor: "var(--bg-card)" }}
          >
            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
              <div className="flex flex-col sm:flex-row items-center justify-between gap-4">
                <div className="flex items-center gap-2">
                  <div
                    className="h-7 w-7 rounded-lg flex items-center justify-center text-xs font-black"
                    style={{ backgroundColor: "var(--color-primary)", color: "var(--text-inverse)" }}
                  >
                    B
                  </div>
                  <span className="font-bold text-sm" style={{ color: "var(--text-primary)" }}>
                    BidSphere
                  </span>
                </div>
                <p className="text-xs" style={{ color: "var(--text-muted)" }}>
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
