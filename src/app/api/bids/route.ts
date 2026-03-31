import { NextRequest, NextResponse } from "next/server";
import db from "@/lib/db";
import { getServerSession } from "next-auth/next";
import { authOptions } from "@/app/api/auth/[...nextauth]/route";
import { ResultSetHeader, RowDataPacket } from "mysql2";

export async function POST(req: NextRequest) {
  let connection;
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

    // Get a connection for the transaction
    connection = await db.getConnection();
    await connection.beginTransaction();

    try {
      // 1. Fetch the item with FOR UPDATE to lock the row
      const [itemRows] = await connection.query<RowDataPacket[]>(
        "SELECT id, current_bid, end_time, status FROM items WHERE id = ? FOR UPDATE",
        [parseInt(itemId)]
      );

      const item = itemRows[0];

      if (!item) {
        throw new Error("Item not found");
      }

      const now = new Date().getTime();
      const endTime = new Date(item.end_time).getTime();

      if (item.status !== "active" || endTime < now) {
        throw new Error("Auction for this item is already closed");
      }

      if (parseFloat(bidAmount) <= parseFloat(item.current_bid.toString())) {
        throw new Error(`Bid amount must be higher than current bid of $${item.current_bid}`);
      }

      // 2. Create the new bid
      const [bidResult] = await connection.query<ResultSetHeader>(
        "INSERT INTO bids (item_id, user_id, bid_amount) VALUES (?, ?, ?)",
        [item.id, userId, bidAmount]
      );

      // 3. Update the item's current bid and highest bidder
      await connection.query(
        "UPDATE items SET current_bid = ?, highest_bidder_id = ? WHERE id = ?",
        [bidAmount, userId, item.id]
      );

      await connection.commit();

      return NextResponse.json({
        success: true,
        bid: { id: bidResult.insertId, amount: bidAmount }
      }, { status: 201 });

    } catch (txError: any) {
      await connection.rollback();
      throw txError;
    }

  } catch (error: any) {
    console.error("Bid Placement Error:", error);
    return NextResponse.json(
      { error: error.message || "Failed to place bid due to an internal error." },
      { status: 500 }
    );
  } finally {
    if (connection) connection.release();
  }
}
