"use client";

import { signOut } from "next-auth/react";
import { LogOut } from "lucide-react";

export default function LogoutButton() {
  return (
    <button
      onClick={() => signOut({ callbackUrl: "/" })}
      className="flex items-center gap-2 rounded-xl bg-red-50 text-red-600 px-5 py-2.5 font-bold hover:bg-red-100 hover:text-red-700 transition"
    >
      <LogOut className="h-5 w-5" />
      Sign Out
    </button>
  );
}
