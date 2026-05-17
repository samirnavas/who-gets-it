import { NextRequest, NextResponse } from "next/server";
import supabase from "@/lib/db";
import { getServerSession } from "next-auth/next";
import { authOptions } from "@/app/api/auth/[...nextauth]/route";
import { parseDateTime } from "@/lib/date";

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

    // 1. Fetch the item (including user_id to prevent creator bidding)
    const { data: item, error: fetchError } = await supabase
      .from('items')
      .select('id, current_bid, end_time, status, user_id')
      .eq('id', parseInt(itemId))
      .single();

    if (fetchError || !item) {
      throw new Error("Item not found");
    }

    if (item.user_id === userId) {
      throw new Error("You cannot place a bid on your own auction listing.");
    }

    const now = new Date().getTime();
    const endTime = parseDateTime(item.end_time).getTime();

    if (item.status !== "active" || endTime < now) {
      throw new Error("Auction for this item is already closed");
    }

    if (parseFloat(bidAmount) <= parseFloat(item.current_bid.toString())) {
      throw new Error(`Bid amount must be higher than current bid of $${item.current_bid}`);
    }

    // Check if the user already has a bid on this item
    const { data: existingBid } = await supabase
      .from('bids')
      .select('id, bid_amount')
      .eq('item_id', item.id)
      .eq('user_id', userId)
      .maybeSingle();

    if (existingBid && parseFloat(bidAmount) <= parseFloat(existingBid.bid_amount.toString())) {
      throw new Error(`Your new bid must be higher than your previous bid of $${existingBid.bid_amount}`);
    }

    // 2. Update the item conditionally (optimistic locking equivalent)
    const { data: updatedItem, error: updateError } = await supabase
      .from('items')
      .update({ current_bid: bidAmount, highest_bidder_id: userId })
      .eq('id', item.id)
      // Make sure the current_bid hasn't changed to be higher than our bid
      .lt('current_bid', bidAmount)
      .select('id')
      .single();

    if (updateError || !updatedItem) {
      throw new Error("Someone else placed a higher bid! Please try again.");
    }

    // 3. Create or update the bid record
    let bidResult;
    let dbError;

    if (existingBid) {
      const { data, error } = await supabase
        .from('bids')
        .update({ bid_amount: bidAmount })
        .eq('id', existingBid.id)
        .select('id')
        .single();
      bidResult = data;
      dbError = error;
    } else {
      const { data, error } = await supabase
        .from('bids')
        .insert([{ item_id: item.id, user_id: userId, bid_amount: bidAmount }])
        .select('id')
        .single();
      bidResult = data;
      dbError = error;
    }

    if (dbError) {
      console.error("Failed to insert/update bid record", dbError);
    }

    return NextResponse.json({
      success: true,
      bid: { id: bidResult?.id, amount: bidAmount }
    }, { status: 201 });

  } catch (error: any) {
    console.error("Bid Placement Error:", error);
    return NextResponse.json(
      { error: error.message || "Failed to place bid due to an internal error." },
      { status: 500 }
    );
  }
}
