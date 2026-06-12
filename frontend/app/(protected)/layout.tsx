import { redirect } from 'next/navigation';
import { getSessionFromCookies } from '@/lib/session/server';

export default async function ProtectedLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  const user = await getSessionFromCookies();
  if (!user) {
    redirect('/login');
  }
  return <>{children}</>;
}
