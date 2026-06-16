import { redirect } from 'next/navigation';
import { getSessionFromCookies } from '@/lib/session/server';
import { getApplicationStatusFromCookies } from '@/lib/session/getApplicationStatus';
import { AppHeader } from '@/components/AppHeader';

export default async function ProtectedLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  const user = await getSessionFromCookies();
  if (!user) {
    redirect('/login');
  }
  const application = await getApplicationStatusFromCookies();
  return (
    <>
      <AppHeader applicationStatus={application?.status ?? null} />
      {children}
    </>
  );
}
