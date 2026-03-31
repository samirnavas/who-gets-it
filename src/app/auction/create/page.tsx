"use client";

import { useTransition } from "react";
import { createAuction } from "./actions";
import { Gavel, ArrowRight, UploadCloud } from "lucide-react";

export default function CreateAuctionPage() {
  const [isPending, startTransition] = useTransition();

  const handleSubmit = (formData: FormData) => {
    startTransition(() => {
      createAuction(formData);
    });
  };

  return (
    <div className="max-w-3xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
      <div className="bg-white rounded-3xl p-8 shadow-sm border border-slate-100 flex flex-col gap-8">
        <div className="flex flex-col gap-2 pb-6 border-b border-slate-100">
          <div className="flex items-center gap-3">
            <div className="h-10 w-10 bg-indigo-50 rounded-xl flex items-center justify-center text-indigo-600">
              <Gavel className="h-5 w-5" />
            </div>
            <h1 className="text-3xl font-extrabold text-slate-900">Put Item to Bid</h1>
          </div>
          <p className="text-slate-500 font-medium ml-13">Create a new auction listing for an item.</p>
        </div>

        <form action={handleSubmit} className="flex flex-col gap-6">
          <div className="grid md:grid-cols-2 gap-6">
            <div className="md:col-span-2">
              <label className="block text-sm font-bold text-slate-700 mb-2">Item Title</label>
              <input
                type="text"
                name="title"
                required
                placeholder="E.g. Vintage Leather Jacket"
                className="w-full rounded-xl border-2 border-slate-200 bg-slate-50 py-3 px-4 text-slate-900 transition focus:border-indigo-600 focus:bg-white focus:outline-none focus:ring-4 focus:ring-indigo-600/10 font-medium"
              />
            </div>

            <div className="md:col-span-2">
              <label className="block text-sm font-bold text-slate-700 mb-2">Description</label>
              <textarea
                name="description"
                rows={4}
                required
                placeholder="Describe your item in detail..."
                className="w-full rounded-xl border-2 border-slate-200 bg-slate-50 py-3 px-4 text-slate-900 transition focus:border-indigo-600 focus:bg-white focus:outline-none focus:ring-4 focus:ring-indigo-600/10 font-medium resize-none"
              ></textarea>
            </div>

            <div className="md:col-span-2">
              <label className="block text-sm font-bold text-slate-700 mb-2">
                Image URL <span className="text-slate-400 font-normal">(Optional)</span>
              </label>
              <div className="relative">
                <input
                  type="url"
                  name="imageUrl"
                  placeholder="https://example.com/image.jpg"
                  className="w-full rounded-xl border-2 border-slate-200 bg-slate-50 py-3 pl-11 pr-4 text-slate-900 transition focus:border-indigo-600 focus:bg-white focus:outline-none focus:ring-4 focus:ring-indigo-600/10 font-medium"
                />
                <UploadCloud className="absolute left-3.5 top-3.5 h-5 w-5 text-slate-400" />
              </div>
            </div>

            <div>
              <label className="block text-sm font-bold text-slate-700 mb-2">Starting Bid ($)</label>
              <div className="relative">
                <span className="absolute left-4 top-3.5 font-bold text-slate-500">$</span>
                <input
                  type="number"
                  name="startingBid"
                  min="0"
                  step="0.01"
                  required
                  placeholder="0.00"
                  className="w-full rounded-xl border-2 border-slate-200 bg-slate-50 py-3 pl-8 pr-4 text-slate-900 transition focus:border-indigo-600 focus:bg-white focus:outline-none focus:ring-4 focus:ring-indigo-600/10 font-bold"
                />
              </div>
            </div>

            <div>
              <label className="block text-sm font-bold text-slate-700 mb-2">Duration (Hours)</label>
              <select
                name="durationHours"
                required
                defaultValue="24"
                className="w-full rounded-xl border-2 border-slate-200 bg-slate-50 py-3 px-4 text-slate-900 transition focus:border-indigo-600 focus:bg-white focus:outline-none focus:ring-4 focus:ring-indigo-600/10 font-bold appearance-none cursor-pointer"
              >
                <option value="1">1 Hour</option>
                <option value="12">12 Hours</option>
                <option value="24">24 Hours (1 Day)</option>
                <option value="48">48 Hours (2 Days)</option>
                <option value="72">72 Hours (3 Days)</option>
                <option value="168">168 Hours (7 Days)</option>
              </select>
            </div>
          </div>

          <div className="pt-6 mt-4 border-t border-slate-100 flex justify-end gap-3">
            <button
              type="button"
              onClick={() => window.history.back()}
              className="px-6 py-3 rounded-xl font-bold text-slate-600 hover:bg-slate-100 transition"
            >
              Cancel
            </button>
            <button
              type="submit"
              disabled={isPending}
              className="group relative flex items-center justify-center gap-2 rounded-xl bg-indigo-600 py-3 px-8 font-bold text-white shadow-lg shadow-indigo-600/20 transition hover:bg-indigo-700 active:scale-95 disabled:opacity-70"
            >
              {isPending ? "Starting Auction..." : "Start Auction"}
              {!isPending && <ArrowRight className="h-4 w-4 transition group-hover:translate-x-1" />}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}
