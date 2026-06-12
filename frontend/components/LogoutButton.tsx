'use client';

import { useRouter } from 'next/navigation';
import { api } from '@/lib/api';
import { useSession } from '@/lib/session/SessionProvider';

export function LogoutButton() {
  const router = useRouter();
  const { setUser } = useSession();

  async function onClick() {
    try {
      await api.post('/api/logout');
    } catch {
      // ignore network/401 — still clear local state below
    }
    setUser(null);
    router.push('/login');
    router.refresh();
  }

  return (
    <button
      onClick={onClick}
      className="rounded border border-zinc-300 px-4 py-2 text-sm dark:border-zinc-700"
    >
      Sign out
    </button>
  );
}
