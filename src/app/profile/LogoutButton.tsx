"use client";

import { signOut } from "next-auth/react";
import { LogOut } from "lucide-react";

export default function LogoutButton() {
  return (
    <button
      onClick={() => signOut({ callbackUrl: "/" })}
      className="btn btn-danger text-sm"
    >
      <LogOut className="h-4 w-4" />
      Sign Out
    </button>
  );
}
