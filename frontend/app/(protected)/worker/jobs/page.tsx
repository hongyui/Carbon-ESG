import Link from 'next/link';
import { redirect } from 'next/navigation';
import { getSessionFromCookies } from '@/lib/session/server';
import { serverGet } from '@/lib/api/server';
import { EmptyState } from '@/components/ui/EmptyState';
import { Button } from '@/components/ui/Button';
import type { Paginated } from '@/lib/api/listings';
import type { WorkerJob } from '@/lib/api/worker';

export default async function WorkerJobsPage() {
  const user = await getSessionFromCookies();
  if (!user) redirect('/login');
  if (!user.isWorker) redirect('/worker/apply');

  const page =
    (await serverGet<Paginated<WorkerJob>>('/api/worker-jobs/open')) ?? null;
  const jobs = page?.data ?? [];

  return (
    <main className="mx-auto max-w-7xl px-6 py-12 lg:px-12 lg:py-16">
      <header className="mb-10">
        <p className="text-xs font-medium tracking-[0.25em] text-emerald-700 uppercase">
          工作機會
        </p>
        <h1 className="mt-3 text-3xl font-bold tracking-tight text-zinc-900 lg:text-4xl">
          開放認領
        </h1>
        <p className="mt-3 text-base text-zinc-600">
          這些是已售出、賣家標記需要維護的土地。先到先認領,認領後請依時程前往整地並提交回報。
        </p>
      </header>

      {jobs.length === 0 ? (
        <EmptyState
          title="目前沒有開放認領的工作"
          body="當賣家上架碳匯並勾選『需要工人維護』,且買家完成購買,系統會自動建立一筆工作機會在這裡。"
          cta={
            <Button variant="ghost" href="/worker/jobs/mine">
              查看我的工作
            </Button>
          }
        />
      ) : (
        <ul className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
          {jobs.map((job) => (
            <li
              key={job.id}
              className="flex flex-col gap-4 rounded-md border border-zinc-200 bg-white p-6 transition hover:border-emerald-200 hover:shadow-md"
            >
              <p className="text-xs font-medium tracking-[0.2em] text-emerald-700 uppercase">
                {job.carbon_listing?.location ?? '地點未公開'}
              </p>
              <h2 className="text-lg font-semibold text-zinc-900">
                {job.carbon_listing?.title ?? `工作 #${job.id}`}
              </h2>
              {job.carbon_listing && (
                <p className="line-clamp-3 text-sm text-zinc-600">
                  {job.carbon_listing.description}
                </p>
              )}
              <dl className="grid grid-cols-2 gap-2 text-xs text-zinc-600">
                <div>
                  <dt className="font-medium text-zinc-500">面積</dt>
                  <dd>{job.carbon_listing?.hectares ?? '—'} 公頃</dd>
                </div>
                <div>
                  <dt className="font-medium text-zinc-500">吸碳量</dt>
                  <dd>{job.carbon_listing?.tonnes_co2e ?? '—'} 公噸</dd>
                </div>
              </dl>
              <div className="mt-auto">
                <Link
                  href={`/worker/jobs/${job.id}`}
                  className="inline-flex h-9 items-center rounded-md bg-emerald-700 px-4 text-sm font-medium text-white transition hover:bg-emerald-800"
                >
                  查看詳情
                </Link>
              </div>
            </li>
          ))}
        </ul>
      )}
    </main>
  );
}
