"use server";

import db from "@/lib/db";
import { getServerSession } from "next-auth/next";
import { authOptions } from "@/app/api/auth/[...nextauth]/route";
import { redirect } from "next/navigation";
import { ResultSetHeader } from "mysql2";

export async function createAuction(formData: FormData) {
  const session = await getServerSession(authOptions);

  if (!session || !session.user) {
    throw new Error("Unauthorized");
  }

  const userId = parseInt((session.user as any).id);
  
  const title = formData.get("title") as string;
  const description = formData.get("description") as string;
  const imageUrl = formData.get("imageUrl") as string || "https://images.unsplash.com/photo-1584982631720-33b069d8be8d?q=80&w=600&auto=format&fit=crop";
  const startingBid = parseFloat(formData.get("startingBid") as string);
  const durationHours = parseInt(formData.get("durationHours") as string);

  if (!title || startingBid === null || isNaN(startingBid) || isNaN(durationHours)) {
    throw new Error("Missing required fields");
  }

  const endTime = new Date();
  endTime.setHours(endTime.getHours() + durationHours);

  // Format endTime for MySQL (YYYY-MM-DD HH:MM:SS)
  const mysqlEndTime = endTime.toISOString().slice(0, 19).replace('T', ' ');

  const [result] = await db.query<ResultSetHeader>(
    `INSERT INTO items 
     (user_id, title, description, image_url, starting_bid, current_bid, end_time, status) 
     VALUES (?, ?, ?, ?, ?, ?, ?, 'active')`,
    [userId, title, description, imageUrl, startingBid, startingBid, mysqlEndTime]
  );

  redirect(`/auction/${result.insertId}`);
}
