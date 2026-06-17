import 'server-only';
import { cookies } from 'next/headers';
import type { User } from '@/lib/types/user';

const API_URL = process.env.NEXT_PUBLIC_API_URL ?? 'http://localhost:8000';
const FRONTEND_URL =
  process.env.NEXT_PUBLIC_FRONTEND_URL ?? 'http://localhost:3000';

export async function getSessionFromCookies(): Promise<User | null> {
  const cookieStore = await cookies();
  const cookieHeader = cookieStore
    .getAll()
    .map((c) => `${c.name}=${c.value}`)
    .join('; ');

  if (!cookieHeader) return null;

  const response = await fetch(`${API_URL}/api/me`, {
    headers: {
      Cookie: cookieHeader,
      Accept: 'application/json',
      // Sanctum's EnsureFrontendRequestsAreStateful middleware needs
      // Referer (or Origin) to match SANCTUM_STATEFUL_DOMAINS — otherwise
      // a server-to-server fetch carrying valid session cookies still
      // gets treated as a stateless API call and returns 401.
      Referer: FRONTEND_URL,
    },
    cache: 'no-store',
  });

  if (!response.ok) return null;

  const data: { user: User } = await response.json();
  return data.user;
}
