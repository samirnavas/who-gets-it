import { NextRequest, NextResponse } from "next/server";
import db from "@/lib/db";
import bcrypt from "bcryptjs";
import { z } from "zod";
import { ResultSetHeader, RowDataPacket } from "mysql2";

const registerSchema = z.object({
  username: z.string().min(3, "Username must be at least 3 characters long"),
  password: z.string().min(6, "Password must be at least 6 characters long"),
});

export async function POST(req: NextRequest) {
  try {
    const body = await req.json();
    const result = registerSchema.safeParse(body);

    if (!result.success) {
      return NextResponse.json({ error: result.error.issues[0].message }, { status: 400 });
    }

    const { username, password } = result.data;

    // Check if user already exists
    const [rows] = await db.query<RowDataPacket[]>(
      "SELECT id FROM users WHERE username = ?",
      [username]
    );

    if (rows.length > 0) {
      return NextResponse.json({ error: "Username already taken" }, { status: 400 });
    }

    // Hash password
    const passwordHash = await bcrypt.hash(password, 10);

    // Create user
    const [insertResult] = await db.query<ResultSetHeader>(
      "INSERT INTO users (username, password_hash) VALUES (?, ?)",
      [username, passwordHash]
    );

    return NextResponse.json({ message: "User registered successfully", userId: insertResult.insertId }, { status: 201 });
  } catch (error) {
    console.error("Registration error:", error);
    return NextResponse.json({ error: "Internal server error" }, { status: 500 });
  }
}
