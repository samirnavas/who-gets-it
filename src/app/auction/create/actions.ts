"use server";

import supabase from "@/lib/db";
import { getServerSession } from "next-auth/next";
import { authOptions } from "@/app/api/auth/[...nextauth]/route";
import { redirect } from "next/navigation";

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

  const pgEndTime = endTime.toISOString();

  const { data, error } = await supabase
    .from('items')
    .insert([{
      user_id: userId,
      title,
      description,
      image_url: imageUrl,
      starting_bid: startingBid,
      current_bid: startingBid,
      end_time: pgEndTime,
      status: 'active'
    }])
    .select('id')
    .single();

  if (error || !data) {
    console.error("Error creating auction:", error);
    throw new Error("Could not create auction");
  }

  redirect(`/auction/${data.id}`);
}
