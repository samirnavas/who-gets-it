import type { Metadata } from "next";
import { Inter } from "next/font/google";
import "./globals.css";
import { Navbar } from "@/components/Navbar";

const inter = Inter({ subsets: ["latin"] });

export const metadata: Metadata = {
  title: "Who Gets It | Modern Real-time Auctions",
  description: "Bid on your favorite items in real-time. Secure, fast, and exciting auctions.",
};

export default function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  return (
    <html lang="en" className="scroll-smooth">
      <body className={`${inter.className} min-h-screen bg-slate-50 text-slate-900 flex flex-col`}>
        <Navbar />
        <main className="flex-grow container mx-auto px-4 py-8">
          {children}
        </main>
        <footer className="border-t border-slate-200 bg-white py-8 mt-12">
          <div className="container mx-auto px-4 text-center text-slate-600">
            <p className="font-medium text-lg text-indigo-600 mb-2">Who Gets It</p>
            <p>&copy; {new Date().getFullYear()} Modern Bidding Platform. All rights reserved.</p>
          </div>
        </footer>
      </body>
    </html>
  );
}
