"use client";

import { useTransition } from "react";
import { createAuction } from "./actions";
import { Gavel, ArrowRight, UploadCloud, DollarSign, Clock } from "lucide-react";

export default function CreateAuctionPage() {
  const [isPending, startTransition] = useTransition();

  const handleSubmit = (formData: FormData) => {
    startTransition(() => {
      createAuction(formData);
    });
  };

  return (
    <div className="max-w-2xl mx-auto py-4 sm:py-8 animate-fade-in-up">
      <div className="card p-6 sm:p-8 flex flex-col gap-6">
        {/* Header */}
        <div
          className="flex flex-col gap-2 pb-5"
          style={{ borderBottom: "1px solid var(--border)" }}
        >
          <div className="flex items-center gap-3">
            <div
              className="h-10 w-10 rounded-xl flex items-center justify-center"
              style={{ backgroundColor: "rgba(91, 106, 191, 0.1)", color: "var(--color-primary)" }}
            >
              <Gavel className="h-5 w-5" />
            </div>
            <div>
              <h1
                className="text-xl font-bold"
                style={{ color: "var(--text-primary)" }}
              >
                Create Auction
              </h1>
              <p className="text-sm" style={{ color: "var(--text-muted)" }}>
                List a new item for bidding
              </p>
            </div>
          </div>
        </div>

        {/* Form */}
        <form action={handleSubmit} className="flex flex-col gap-5">
          {/* Title */}
          <div className="flex flex-col gap-1.5">
            <label className="text-sm font-medium" style={{ color: "var(--text-secondary)" }}>
              Item Title
            </label>
            <input
              type="text"
              name="title"
              required
              placeholder="E.g. Vintage Leather Jacket"
              className="input-field"
            />
          </div>

          {/* Description */}
          <div className="flex flex-col gap-1.5">
            <label className="text-sm font-medium" style={{ color: "var(--text-secondary)" }}>
              Description
            </label>
            <textarea
              name="description"
              rows={4}
              required
              placeholder="Describe your item in detail..."
              className="input-field"
              style={{ resize: "none" }}
            />
          </div>

          {/* Image URL */}
          <div className="flex flex-col gap-1.5">
            <label className="text-sm font-medium" style={{ color: "var(--text-secondary)" }}>
              Image URL{" "}
              <span style={{ color: "var(--text-muted)", fontWeight: 400 }}>(Optional)</span>
            </label>
            <div className="relative">
              <UploadCloud
                className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4"
                style={{ color: "var(--text-muted)" }}
              />
              <input
                type="url"
                name="imageUrl"
                placeholder="https://example.com/image.jpg"
                className="input-field"
                style={{ paddingLeft: "2.25rem" }}
              />
            </div>
          </div>

          {/* Price & Duration Row */}
          <div className="grid sm:grid-cols-2 gap-5">
            <div className="flex flex-col gap-1.5">
              <label className="text-sm font-medium flex items-center gap-1.5" style={{ color: "var(--text-secondary)" }}>
                <DollarSign className="h-3.5 w-3.5" />
                Starting Bid
              </label>
              <div className="relative">
                <span
                  className="absolute left-3 top-1/2 -translate-y-1/2 text-sm font-semibold"
                  style={{ color: "var(--text-muted)" }}
                >
                  $
                </span>
                <input
                  type="number"
                  name="startingBid"
                  min="0"
                  step="0.01"
                  required
                  placeholder="0.00"
                  className="input-field font-semibold"
                  style={{ paddingLeft: "1.75rem" }}
                />
              </div>
            </div>

            <div className="flex flex-col gap-1.5">
              <label className="text-sm font-medium flex items-center gap-1.5" style={{ color: "var(--text-secondary)" }}>
                <Clock className="h-3.5 w-3.5" />
                Duration
              </label>
              <select
                name="durationHours"
                required
                defaultValue="24"
                className="input-field font-semibold cursor-pointer"
                style={{ appearance: "none" }}
              >
                <option value="1">1 Hour</option>
                <option value="12">12 Hours</option>
                <option value="24">24 Hours (1 Day)</option>
                <option value="48">48 Hours (2 Days)</option>
                <option value="72">72 Hours (3 Days)</option>
                <option value="168">7 Days</option>
              </select>
            </div>
          </div>

          {/* Actions */}
          <div
            className="flex flex-col-reverse sm:flex-row justify-end gap-3 pt-5 mt-1"
            style={{ borderTop: "1px solid var(--border)" }}
          >
            <button
              type="button"
              onClick={() => window.history.back()}
              className="btn btn-secondary"
            >
              Cancel
            </button>
            <button
              type="submit"
              disabled={isPending}
              className="btn btn-primary btn-lg"
              style={{ opacity: isPending ? 0.7 : 1 }}
            >
              {isPending ? "Starting Auction..." : "Start Auction"}
              {!isPending && <ArrowRight className="h-4 w-4" />}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}
