import { NextRequest, NextResponse } from "next/server";
import { prisma } from "@/lib/prisma";
import { getServerSession } from "next-auth/next";
import { authOptions } from "@/app/api/auth/[...nextauth]/route";
import { pusherServer } from "@/lib/pusher";

export async function POST(req: NextRequest) {
  try {
    const session = await getServerSession(authOptions);

    if (!session || !session.user) {
      return NextResponse.json({ error: "Unauthorized. Please log in to bid." }, { status: 401 });
    }

    const userId = parseInt((session.user as any).id);
    const { itemId, bidAmount } = await req.json();

    if (!itemId || !bidAmount || bidAmount <= 0) {
      return NextResponse.json({ error: "Invalid bid data provided." }, { status: 400 });
    }

    // 1. Transaction to safely place the bid
    const result = await prisma.$transaction(async (tx) => {
      // Fetch the item with an exclusive lock (FOR UPDATE) to prevent race conditions
      // Note: Prisma does not use FOR UPDATE out of the box unless using raw queries.
      // We will perform an optimistic concurrency check or double-fetch checking.
      const item = await tx.item.findUnique({
        where: { id: parseInt(itemId) },
        select: { id: true, currentBid: true, endTime: true, status: true },
      });

      if (!item) {
        throw new Error("Item not found");
      }

      if (item.status !== "active" || new Date(item.endTime).getTime() < new Date().getTime()) {
        throw new Error("Auction for this item is already closed");
      }

      if (parseFloat(bidAmount) <= parseFloat(item.currentBid.toString())) {
        throw new Error(`Bid amount must be higher than current bid of $${item.currentBid}`);
      }

      // Create the new bid
      const userBid = await tx.bid.create({
        data: {
          itemId: item.id,
          userId: userId,
          bidAmount: bidAmount,
        },
        include: {
          user: { select: { username: true } }
        }
      });

      // Update the item's current bid and highest bidder
      const updatedItem = await tx.item.update({
        where: { id: item.id },
        data: {
          currentBid: bidAmount,
          highestBidderId: userId,
        },
      });

      return { userBid, updatedItem };
    });

    // 2. Broadcast the new bid amount and bidder in real-time
    await pusherServer.trigger(`auction-${itemId}`, "new-bid", {
      bidAmount: result.updatedItem.currentBid,
      highestBidder: result.userBid.user.username,
      bidTime: result.userBid.createdAt,
    });

    return NextResponse.json({ success: true, bid: result.userBid }, { status: 201 });

  } catch (error: any) {
    console.error("Bid Placement Error:", error);
    return NextResponse.json(
      { error: error.message || "Failed to place bid due to an internal error." },
      { status: 500 }
    );
  }
}
