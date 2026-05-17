"use client";

import { useTransition } from "react";
import { createAuction } from "./actions";
import { Gavel, ArrowRight, UploadCloud, DollarSign, Clock } from "lucide-react";

export default function CreateAuctionPage() {
  const [isPending, startTransition] = useTransition();

  const handleSubmit = (formData: FormData) => {
    startTransition(() => createAuction(formData));
  };

  return (
    <div className="max-w-2xl mx-auto py-4 sm:py-8 animate-fade-in-up">
      <div className="card-solid rounded-2xl p-6 sm:p-10 flex flex-col gap-7">

        <div className="flex items-center gap-4 pb-6" style={{ borderBottom: "1px solid rgba(37,99,235,0.1)" }}>
          <div className="h-12 w-12 rounded-xl flex items-center justify-center shrink-0"
            style={{ background: "linear-gradient(135deg, #2563EB, #4F46E5)", boxShadow: "0 4px 16px rgba(37,99,235,0.3)" }}>
            <Gavel className="h-6 w-6 text-white" />
          </div>
          <div>
            <h1 className="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">Create Auction</h1>
            <p className="text-sm mt-0.5 text-slate-500">List a new item for bidding</p>
          </div>
        </div>

        <form action={handleSubmit} className="flex flex-col gap-5">
          <div className="flex flex-col gap-2">
            <label className="text-sm font-semibold text-slate-700">Item Title</label>
            <input type="text" name="title" required placeholder="E.g. Vintage Leather Jacket" className="input" />
          </div>

          <div className="flex flex-col gap-2">
            <label className="text-sm font-semibold text-slate-700">Description</label>
            <textarea name="description" rows={4} required placeholder="Describe your item in detail…" className="input resize-none" />
          </div>

          <div className="flex flex-col gap-2">
            <label className="text-sm font-semibold text-slate-700">
              Image URL <span className="font-normal text-slate-400">(Optional)</span>
            </label>
            <div className="relative">
              <UploadCloud className="absolute left-4 top-1/2 -translate-y-1/2 h-5 w-5 text-blue-400" />
              <input type="url" name="imageUrl" placeholder="https://example.com/image.jpg" className="input pl-12" />
            </div>
          </div>

          <div className="grid sm:grid-cols-2 gap-5">
            <div className="flex flex-col gap-2">
              <label className="text-sm font-semibold text-slate-700 flex items-center gap-1.5">
                <DollarSign className="h-4 w-4 text-blue-400" /> Starting Bid
              </label>
              <div className="relative">
                <span className="absolute left-4 top-1/2 -translate-y-1/2 font-bold text-blue-400">$</span>
                <input type="number" name="startingBid" min="0" step="0.01" required placeholder="0.00" className="input pl-8 font-semibold" />
              </div>
            </div>

            <div className="flex flex-col gap-2">
              <label className="text-sm font-semibold text-slate-700 flex items-center gap-1.5">
                <Clock className="h-4 w-4 text-blue-400" /> Duration
              </label>
              <div className="relative">
                <select name="durationHours" required defaultValue="24" className="input appearance-none cursor-pointer font-semibold pr-9">
                  <option value="1">1 Hour</option>
                  <option value="12">12 Hours</option>
                  <option value="24">24 Hours (1 Day)</option>
                  <option value="48">48 Hours (2 Days)</option>
                  <option value="72">72 Hours (3 Days)</option>
                  <option value="168">7 Days</option>
                </select>
                <div className="absolute inset-y-0 right-3 flex items-center pointer-events-none">
                  <svg className="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M19 9l-7 7-7-7" />
                  </svg>
                </div>
              </div>
            </div>
          </div>

          <div className="flex flex-col-reverse sm:flex-row justify-end gap-3 pt-5 mt-1" style={{ borderTop: "1px solid rgba(37,99,235,0.1)" }}>
            <button type="button" onClick={() => window.history.back()} className="btn-ghost px-6 py-3 text-sm">Cancel</button>
            <button type="submit" disabled={isPending} className="btn-primary px-8 py-3 text-sm">
              {isPending ? "Starting Auction…" : "Start Auction"}
              {!isPending && <ArrowRight className="h-4 w-4" />}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}
