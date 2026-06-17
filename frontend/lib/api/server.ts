import 'server-only';
import { cookies } from 'next/headers';

const API_URL = process.env.NEXT_PUBLIC_API_URL ?? 'http://localhost:8000';
const FRONTEND_URL =
  process.env.NEXT_PUBLIC_FRONTEND_URL ?? 'http://localhost:3000';

/**
 * Server-side GET helper. Forwards incoming browser cookies to the
 * Laravel backend so the session is preserved in RSC fetches. Returns
 * null on any non-2xx response (auth failure handling is delegated to
 * the wrapping layout's session guard).
 *
 * Includes Referer so Sanctum's EnsureFrontendRequestsAreStateful
 * middleware treats the request as SPA-stateful — without it the
 * session cookie is ignored and Laravel returns 401.
 */
export async function serverGet<T>(path: string): Promise<T | null> {
  const cookieStore = await cookies();
  const cookieHeader = cookieStore
    .getAll()
    .map((c) => `${c.name}=${c.value}`)
    .join('; ');

  if (!cookieHeader) return null;

  const response = await fetch(`${API_URL}${path}`, {
    headers: {
      Cookie: cookieHeader,
      Accept: 'application/json',
      Referer: FRONTEND_URL,
    },
    cache: 'no-store',
  });

  if (!response.ok) return null;

  return response.json() as Promise<T>;
}
