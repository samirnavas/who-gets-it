import PusherServer from "pusher";
import PusherClient from "pusher-js";

// Server instance for triggering events (used in API routes/Server Actions)
export const pusherServer = new PusherServer({
  appId: process.env.PUSHER_APP_ID || "app_id",
  key: process.env.NEXT_PUBLIC_PUSHER_KEY || "key",
  secret: process.env.PUSHER_SECRET || "secret",
  cluster: process.env.NEXT_PUBLIC_PUSHER_CLUSTER || "us2",
  useTLS: true,
});

// Client instance for subscribing to events (used in React components)
export const pusherClient = new PusherClient(process.env.NEXT_PUBLIC_PUSHER_KEY || "key", {
  cluster: process.env.NEXT_PUBLIC_PUSHER_CLUSTER || "us2",
});
