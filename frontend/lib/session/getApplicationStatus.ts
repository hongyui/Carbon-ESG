import 'server-only';
import { cookies } from 'next/headers';
import type { WorkerApplication } from '@/lib/api/worker';

const API_URL = process.env.NEXT_PUBLIC_API_URL ?? 'http://localhost:8000';

/**
 * Server-side helper: return the current user's worker_applications row
 * if any, else null. Used by the (protected) layout to render
 * worker-aware nav (申請中 / 已核准 / 已退件 / 工人申請) without an
 * extra round trip in the client.
 */
export async function getApplicationStatusFromCookies(): Promise<WorkerApplication | null> {
  const cookieStore = await cookies();
  const cookieHeader = cookieStore
    .getAll()
    .map((c) => `${c.name}=${c.value}`)
    .join('; ');

  if (!cookieHeader) return null;

  const response = await fetch(`${API_URL}/api/worker-applications/mine`, {
    headers: {
      Cookie: cookieHeader,
      Accept: 'application/json',
    },
    cache: 'no-store',
  });

  if (!response.ok) return null;

  const data: { application: WorkerApplication } = await response.json();
  return data.application;
}
