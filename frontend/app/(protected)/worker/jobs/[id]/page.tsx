import Link from 'next/link';
import { notFound, redirect } from 'next/navigation';
import { getSessionFromCookies } from '@/lib/session/server';
import { serverGet } from '@/lib/api/server';
import { WorkerStatusBadge } from '@/components/WorkerStatusBadge';
import { PhotoPair } from '@/components/PhotoPair';
import { Button } from '@/components/ui/Button';
import type { WorkerJob } from '@/lib/api/worker';
import { ClaimButton } from './ClaimButton';

interface PageProps {
  params: Promise<{ id: string }>;
}

export default async function WorkerJobDetailPage({ params }: PageProps) {
  const { id } = await params;

  const user = await getSessionFromCookies();
  if (!user) redirect('/login');

  const payload = await serverGet<{ job: WorkerJob }>(
    `/api/worker-jobs/${id}`,
  );
  if (!payload) notFound();

  const job = payload.job;
  const listing = job.carbon_listing;
  const isAssignedWorker = job.worker_id === user.id;

  return (
    <main className="mx-auto max-w-4xl px-6 py-12 lg:px-12 lg:py-16">
      <header className="mb-8 flex flex-col gap-4">
        <Link
          href="/worker/jobs"
          className="text-sm text-zinc-600 transition hover:text-zinc-900"
        >
          ← 工作機會
        </Link>
        <div className="flex flex-wrap items-center gap-3">
          <p className="text-xs font-medium tracking-[0.25em] text-emerald-700 uppercase">
            {listing?.location ?? '地點未公開'}
          </p>
          <WorkerStatusBadge kind="job" status={job.status} />
        </div>
        <h1 className="text-3xl font-bold tracking-tight text-zinc-900 lg:text-4xl">
          {listing?.title ?? `工作 #${job.id}`}
        </h1>
      </header>

      {listing && (
        <section className="rounded-md border border-zinc-200 bg-white p-6">
          <h2 className="text-sm font-medium tracking-[0.2em] text-zinc-500 uppercase">
            土地說明
          </h2>
          <p className="mt-3 text-sm leading-relaxed text-zinc-700">
            {listing.description}
          </p>
          <dl className="mt-6 grid grid-cols-2 gap-4 text-sm text-zinc-700 sm:grid-cols-4">
            <Stat label="面積">{listing.hectares} 公頃</Stat>
            <Stat label="吸碳量">{listing.tonnes_co2e} 公噸</Stat>
            <Stat label="售價">TWD {listing.price_twd}</Stat>
            <Stat label="認領時間">
              {job.claimed_at
                ? new Date(job.claimed_at).toLocaleString('zh-Hant')
                : '尚未認領'}
            </Stat>
          </dl>
        </section>
      )}

      <section className="mt-8 flex flex-wrap gap-3">
        {job.status === 'open' && <ClaimButton jobId={job.id} />}
        {job.status === 'claimed' && isAssignedWorker && (
          <Button href={`/worker/jobs/${job.id}/report`}>提交回報</Button>
        )}
        {job.status === 'reported' && isAssignedWorker && (
          <span className="rounded-md border border-zinc-200 bg-zinc-50 px-4 py-2 text-sm text-zinc-700">
            您的回報正在等待後台審核
          </span>
        )}
        {job.status === 'approved' && (
          <span className="rounded-md border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm text-emerald-700">
            這份工作已完成並通過審核
          </span>
        )}
      </section>

      {job.report && (
        <section className="mt-10">
          <h2 className="text-sm font-medium tracking-[0.2em] text-zinc-500 uppercase">
            維護回報
          </h2>
          <div className="mt-4 rounded-md border border-zinc-200 bg-white p-6">
            <div className="mb-4 flex flex-wrap items-center gap-3">
              <WorkerStatusBadge kind="report" status={job.report.status} />
              <span className="text-xs text-zinc-500">
                {new Date(job.report.created_at).toLocaleString('zh-Hant')}
              </span>
            </div>
            <PhotoPair
              before={job.report.before_image_path}
              after={job.report.after_image_path}
            />
            <p className="mt-4 text-sm text-zinc-700">{job.report.content}</p>
            {job.report.status === 'rejected' &&
              job.report.review_reason && (
                <p className="mt-3 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
                  退件原因:{job.report.review_reason}
                </p>
              )}
          </div>
        </section>
      )}
    </main>
  );
}

function Stat({ label, children }: { label: string; children: React.ReactNode }) {
  return (
    <div>
      <dt className="text-xs font-medium tracking-[0.2em] text-zinc-500 uppercase">
        {label}
      </dt>
      <dd className="mt-1">{children}</dd>
    </div>
  );
}
