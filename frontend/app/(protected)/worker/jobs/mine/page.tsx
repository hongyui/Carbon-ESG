import Link from 'next/link';
import { redirect } from 'next/navigation';
import { getSessionFromCookies } from '@/lib/session/server';
import { serverGet } from '@/lib/api/server';
import { EmptyState } from '@/components/ui/EmptyState';
import { WorkerStatusBadge } from '@/components/WorkerStatusBadge';
import { Button } from '@/components/ui/Button';
import type { Paginated } from '@/lib/api/listings';
import type { WorkerJob, WorkerJobStatus } from '@/lib/api/worker';

const SECTIONS: { key: WorkerJobStatus[]; title: string; body: string }[] = [
  {
    key: ['claimed'],
    title: '進行中',
    body: '已認領但尚未提交回報的工作。',
  },
  {
    key: ['reported'],
    title: '審核中',
    body: '已提交回報、等待後台審核。',
  },
  {
    key: ['approved'],
    title: '已完成',
    body: '審核通過的工作紀錄。',
  },
];

export default async function WorkerMyJobsPage() {
  const user = await getSessionFromCookies();
  if (!user) redirect('/login');

  const page =
    (await serverGet<Paginated<WorkerJob>>('/api/worker-jobs/mine')) ?? null;
  const jobs = page?.data ?? [];

  return (
    <main className="mx-auto max-w-5xl px-6 py-12 lg:px-12 lg:py-16">
      <header className="mb-10">
        <p className="text-xs font-medium tracking-[0.25em] text-emerald-700 uppercase">
          我的工作
        </p>
        <h1 className="mt-3 text-3xl font-bold tracking-tight text-zinc-900 lg:text-4xl">
          認領與回報紀錄
        </h1>
      </header>

      {jobs.length === 0 ? (
        <EmptyState
          title="尚未認領任何工作"
          body="前往「工作機會」查看正在等待維護的土地。"
          cta={<Button href="/worker/jobs">查看工作機會</Button>}
        />
      ) : (
        <div className="flex flex-col gap-10">
          {SECTIONS.map((section) => {
            const items = jobs.filter((j) => section.key.includes(j.status));
            if (items.length === 0) return null;
            return (
              <section key={section.title}>
                <h2 className="text-xl font-semibold text-zinc-900">
                  {section.title}
                </h2>
                <p className="mt-1 text-sm text-zinc-600">{section.body}</p>
                <ul className="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2">
                  {items.map((job) => (
                    <li
                      key={job.id}
                      className="flex flex-col gap-3 rounded-md border border-zinc-200 bg-white p-5 transition hover:border-emerald-200"
                    >
                      <div className="flex items-center justify-between gap-3">
                        <p className="text-xs font-medium tracking-[0.2em] text-emerald-700 uppercase">
                          {job.carbon_listing?.location ?? '—'}
                        </p>
                        <WorkerStatusBadge kind="job" status={job.status} />
                      </div>
                      <h3 className="text-base font-semibold text-zinc-900">
                        {job.carbon_listing?.title ?? `工作 #${job.id}`}
                      </h3>
                      <div className="text-xs text-zinc-500">
                        認領時間:{' '}
                        {job.claimed_at
                          ? new Date(job.claimed_at).toLocaleString('zh-Hant')
                          : '—'}
                      </div>
                      <Link
                        href={`/worker/jobs/${job.id}`}
                        className="mt-1 inline-flex h-9 w-fit items-center rounded-md border border-zinc-300 px-3 text-sm font-medium text-zinc-700 transition hover:border-emerald-600 hover:text-emerald-700"
                      >
                        查看詳情
                      </Link>
                    </li>
                  ))}
                </ul>
              </section>
            );
          })}
        </div>
      )}
    </main>
  );
}
