import { getSessionFromCookies } from '@/lib/session/server';
import { LogoutButton } from '@/components/LogoutButton';

export default async function MePage() {
  const user = await getSessionFromCookies();
  if (!user) return null;

  return (
    <main className="flex min-h-screen flex-col items-center justify-center gap-6 p-8 font-sans">
      <h1 className="text-3xl font-bold">Hi, {user.name}</h1>
      <p className="text-sm text-zinc-500 dark:text-zinc-400">{user.email}</p>
      <LogoutButton />
    </main>
  );
}
