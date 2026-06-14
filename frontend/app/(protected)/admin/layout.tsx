import { redirect } from 'next/navigation';
import { getSessionFromCookies } from '@/lib/session/server';

export default async function AdminLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  const user = await getSessionFromCookies();
  if (!user?.isAdmin) {
    redirect('/me');
  }
  return <>{children}</>;
}
