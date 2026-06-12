import 'server-only';
import { cookies } from 'next/headers';
import type { User } from '@/lib/types/user';

const API_URL = process.env.NEXT_PUBLIC_API_URL ?? 'http://localhost:8000';

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
    },
    cache: 'no-store',
  });

  if (!response.ok) return null;

  const data: { user: User } = await response.json();
  return data.user;
}
