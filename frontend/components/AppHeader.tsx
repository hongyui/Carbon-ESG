'use client';

import Link from 'next/link';
import { usePathname, useRouter } from 'next/navigation';
import { api } from '@/lib/api';
import { useSession } from '@/lib/session/SessionProvider';
import { Button } from '@/components/ui/Button';
import type { WorkerApplicationStatus } from '@/lib/api/worker';

interface AppHeaderProps {
  /**
   * Snapshot of the user's worker_application status, fetched server-side
   * by (protected)/layout.tsx so the header can branch without an extra
   * client round trip. null means "no application row exists." undefined
   * means the layout didn't fetch (treated like null for nav purposes).
   */
  applicationStatus?: WorkerApplicationStatus | null;
}

/**
 * Authenticated app shell header. Distinct from the marketing
 * <StickyHeader> (which does scroll-aware transparency over the hero
 * photo). This header is opaque, sticky, and renders role-aware nav
 * based on the four flags returned by /api/me plus the worker
 * application status fetched in the protected layout.
 */
export function AppHeader({ applicationStatus = null }: AppHeaderProps) {
  const router = useRouter();
  const pathname = usePathname();
  const { user, setUser } = useSession();

  if (!user) return null;

  async function onLogout() {
    try {
      await api.post('/api/logout');
    } catch {
      // ignore network/401 — local state cleared regardless
    }
    setUser(null);
    router.push('/login');
    router.refresh();
  }

  const isWorker = !!user.isWorker;
  const hasNonApprovedApp =
    applicationStatus === 'pending' || applicationStatus === 'rejected';

  return (
    <header className="sticky top-0 z-30 border-b border-zinc-200 bg-white">
      <div className="mx-auto flex h-16 max-w-7xl items-center justify-between gap-4 px-6 lg:px-12">
        <Link
          href="/me"
          className="text-base font-semibold tracking-tight text-zinc-900 transition hover:text-zinc-700"
        >
          Carbon-ESG
        </Link>

        <nav className="flex flex-wrap items-center gap-1">
          <NavLink href="/market" active={pathname?.startsWith('/market')}>
            市場
          </NavLink>
          <NavLink
            href="/seller/listings"
            active={pathname?.startsWith('/seller')}
          >
            我的上架
          </NavLink>
          <NavLink
            href="/purchases"
            active={pathname?.startsWith('/purchases')}
          >
            我的購買
          </NavLink>

          {isWorker && (
            <>
              <NavLink
                href="/worker/jobs"
                active={
                  pathname?.startsWith('/worker/jobs') &&
                  !pathname?.startsWith('/worker/jobs/mine')
                }
              >
                工作機會
              </NavLink>
              <NavLink
                href="/worker/jobs/mine"
                active={pathname?.startsWith('/worker/jobs/mine')}
              >
                我的工作
              </NavLink>
            </>
          )}
          {!isWorker && hasNonApprovedApp && (
            <NavLink
              href="/worker/apply/status"
              active={pathname?.startsWith('/worker/apply')}
            >
              申請狀態
            </NavLink>
          )}
          {!isWorker && !hasNonApprovedApp && (
            <NavLink
              href="/worker/apply"
              active={pathname?.startsWith('/worker/apply')}
            >
              工人申請
            </NavLink>
          )}

          {user.isAdmin && (
            <>
              <NavLink
                href="/admin/review"
                active={
                  pathname?.startsWith('/admin/review') ?? false
                }
              >
                後台審核
              </NavLink>
              <NavLink
                href="/admin/worker-applications"
                active={
                  pathname?.startsWith('/admin/worker-applications') ?? false
                }
              >
                工人申請審核
              </NavLink>
              <NavLink
                href="/admin/job-reports"
                active={
                  pathname?.startsWith('/admin/job-reports') ?? false
                }
              >
                工作回報審核
              </NavLink>
            </>
          )}
        </nav>

        <div className="flex items-center gap-3">
          <span className="hidden text-sm text-zinc-600 sm:inline">
            {user.name}
          </span>
          <Button variant="ghost" onClick={onLogout}>
            登出
          </Button>
        </div>
      </div>
    </header>
  );
}

function NavLink({
  href,
  children,
  active,
}: {
  href: string;
  children: React.ReactNode;
  active?: boolean;
}) {
  return (
    <Link
      href={href}
      className={`rounded-md px-3 py-2 text-sm transition ${
        active
          ? 'bg-emerald-50 text-emerald-700'
          : 'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900'
      }`}
    >
      {children}
    </Link>
  );
}
