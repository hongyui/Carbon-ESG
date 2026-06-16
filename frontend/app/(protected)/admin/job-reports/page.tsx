import { serverGet } from '@/lib/api/server';
import type { Paginated } from '@/lib/api/listings';
import type { WorkerJobReport } from '@/lib/api/worker';
import { Button } from '@/components/ui/Button';
import { EmptyState } from '@/components/ui/EmptyState';
import { PhotoPair } from '@/components/PhotoPair';
import { JobReportActions } from './JobReportActions';

export default async function AdminJobReportsPage({
  searchParams,
}: {
  searchParams: Promise<{ page?: string }>;
}) {
  const params = await searchParams;
  const page = Math.max(1, parseInt(params.page ?? '1', 10) || 1);

  const data = await serverGet<Paginated<WorkerJobReport>>(
    `/api/admin/job-reports/pending?page=${page}`,
  );
  const reports = data?.data ?? [];
  const lastPage = data?.last_page ?? 1;
  const total = data?.total ?? 0;

  return (
    <main className="mx-auto max-w-5xl px-6 py-12 lg:px-12 lg:py-16">
      <header className="mb-10">
        <p className="text-xs font-medium tracking-[0.25em] text-emerald-700 uppercase">
          後台
        </p>
        <h1 className="mt-3 text-3xl font-bold tracking-tight text-zinc-900 lg:text-4xl">
          工作回報審核
        </h1>
        <p className="mt-3 text-base text-zinc-600">
          檢視工人提交的前後對照與整地內容,核准後該筆工作完成;退件後工人可重新提交。
        </p>
      </header>

      {reports.length === 0 ? (
        <EmptyState
          title="目前沒有待審核的回報"
          body="工人提交回報後會出現在這裡,排序由舊到新。"
        />
      ) : (
        <>
          <ul className="space-y-8">
            {reports.map((report) => {
              const job = report.worker_job;
              const listing = job?.carbon_listing;
              return (
                <li
                  key={report.id}
                  className="rounded-md border border-zinc-200 bg-white p-6"
                >
                  <div className="flex flex-wrap items-start justify-between gap-3">
                    <div>
                      <p className="text-xs font-medium tracking-[0.18em] text-zinc-500 uppercase">
                        {listing?.location ?? '—'} ·{' '}
                        {report.worker?.name ?? `工人 #${report.worker_id}`}
                      </p>
                      <h3 className="mt-2 text-xl font-semibold text-zinc-900">
                        {listing?.title ?? `工作 #${job?.id ?? report.worker_job_id}`}
                      </h3>
                    </div>
                    <p className="text-xs text-zinc-500">
                      提交於{' '}
                      {new Date(report.created_at).toLocaleString('zh-TW')}
                    </p>
                  </div>

                  <dl className="mt-5 grid grid-cols-2 gap-x-8 gap-y-3 border-t border-zinc-100 pt-5 sm:grid-cols-3">
                    <div>
                      <dt className="text-[10px] font-medium tracking-[0.15em] text-zinc-500 uppercase">
                        開始時間
                      </dt>
                      <dd className="mt-0.5 text-sm font-medium text-zinc-700">
                        {new Date(report.datetime_start).toLocaleString(
                          'zh-TW',
                        )}
                      </dd>
                    </div>
                    <div>
                      <dt className="text-[10px] font-medium tracking-[0.15em] text-zinc-500 uppercase">
                        結束時間
                      </dt>
                      <dd className="mt-0.5 text-sm font-medium text-zinc-700">
                        {new Date(report.datetime_end).toLocaleString('zh-TW')}
                      </dd>
                    </div>
                    <div>
                      <dt className="text-[10px] font-medium tracking-[0.15em] text-zinc-500 uppercase">
                        工人聯絡
                      </dt>
                      <dd className="mt-0.5 text-sm font-medium text-zinc-700">
                        {report.worker?.email ?? '—'}
                      </dd>
                    </div>
                  </dl>

                  <div className="mt-6">
                    <PhotoPair
                      before={report.before_image_path}
                      after={report.after_image_path}
                    />
                  </div>

                  <p className="mt-5 whitespace-pre-wrap text-sm leading-relaxed text-zinc-700">
                    {report.content}
                  </p>

                  <div className="mt-6 border-t border-zinc-100 pt-5">
                    <JobReportActions reportId={report.id} />
                  </div>
                </li>
              );
            })}
          </ul>

          {lastPage > 1 && (
            <nav className="mt-10 flex items-center justify-between border-t border-zinc-200 pt-6">
              <p className="text-sm text-zinc-500">
                第 {page} / {lastPage} 頁 · 共 {total} 筆
              </p>
              <div className="flex gap-3">
                {page > 1 && (
                  <Button
                    variant="ghost"
                    href={`/admin/job-reports?page=${page - 1}`}
                  >
                    上一頁
                  </Button>
                )}
                {page < lastPage && (
                  <Button
                    variant="ghost"
                    href={`/admin/job-reports?page=${page + 1}`}
                  >
                    下一頁
                  </Button>
                )}
              </div>
            </nav>
          )}
        </>
      )}
    </main>
  );
}
