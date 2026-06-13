'use client';

import { useRouter } from 'next/navigation';
import { api } from '@/lib/api';
import { useSession } from '@/lib/session/SessionProvider';
import { Button } from '@/components/ui/Button';

export function LogoutButton() {
  const router = useRouter();
  const { setUser } = useSession();

  async function onClick() {
    try {
      await api.post('/api/logout');
    } catch {
      // ignore network/401; still clear local state below
    }
    setUser(null);
    router.push('/login');
    router.refresh();
  }

  return (
    <Button variant="ghost" onClick={onClick}>
      登出
    </Button>
  );
}
