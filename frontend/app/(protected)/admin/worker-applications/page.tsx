import { serverGet } from '@/lib/api/server';
import type { Paginated } from '@/lib/api/listings';
import type { WorkerApplication } from '@/lib/api/worker';
import { Button } from '@/components/ui/Button';
import { EmptyState } from '@/components/ui/EmptyState';
import { WorkerApplicationActions } from './WorkerApplicationActions';

export default async function AdminWorkerApplicationsPage({
  searchParams,
}: {
  searchParams: Promise<{ page?: string }>;
}) {
  const params = await searchParams;
  const page = Math.max(1, parseInt(params.page ?? '1', 10) || 1);

  const data = await serverGet<Paginated<WorkerApplication>>(
    `/api/admin/worker-applications/pending?page=${page}`,
  );
  const applications = data?.data ?? [];
  const lastPage = data?.last_page ?? 1;
  const total = data?.total ?? 0;

  return (
    <main className="mx-auto max-w-5xl px-6 py-12 lg:px-12 lg:py-16">
      <header className="mb-10">
        <p className="text-xs font-medium tracking-[0.25em] text-emerald-700 uppercase">
          後台
        </p>
        <h1 className="mt-3 text-3xl font-bold tracking-tight text-zinc-900 lg:text-4xl">
          工人申請審核
        </h1>
        <p className="mt-3 text-base text-zinc-600">
          先進先出。核准後申請人會立即在「工作機會」看到開放認領的工作。
        </p>
      </header>

      {applications.length === 0 ? (
        <EmptyState
          title="目前沒有待審核的申請"
          body="新的申請會自動出現在這裡,排序由舊到新。"
        />
      ) : (
        <>
          <ul className="space-y-6">
            {applications.map((application) => (
              <li
                key={application.id}
                className="rounded-md border border-zinc-200 bg-white p-6"
              >
                <div className="flex flex-wrap items-start justify-between gap-3">
                  <div>
                    <p className="text-xs font-medium tracking-[0.18em] text-zinc-500 uppercase">
                      {application.user?.name ?? `申請人 #${application.user_id}`}{' '}
                      · 送出於{' '}
                      {new Date(application.created_at).toLocaleString('zh-TW')}
                    </p>
                    <h3 className="mt-2 text-xl font-semibold text-zinc-900">
                      {application.user?.email ?? '—'}
                    </h3>
                  </div>
                  <span
                    className={`inline-flex items-center rounded-md border px-2 py-0.5 text-xs font-medium ${
                      application.has_experience
                        ? 'border-emerald-200 bg-emerald-50 text-emerald-700'
                        : 'border-zinc-200 bg-zinc-100 text-zinc-700'
                    }`}
                  >
                    {application.has_experience ? '有相關經驗' : '無相關經驗'}
                  </span>
                </div>

                <dl className="mt-5 grid grid-cols-2 gap-x-8 gap-y-3 border-t border-zinc-100 pt-5 sm:grid-cols-3">
                  <div>
                    <dt className="text-[10px] font-medium tracking-[0.15em] text-zinc-500 uppercase">
                      年齡
                    </dt>
                    <dd className="mt-0.5 text-sm font-medium text-zinc-700">
                      {application.age} 歲
                    </dd>
                  </div>
                  <div>
                    <dt className="text-[10px] font-medium tracking-[0.15em] text-zinc-500 uppercase">
                      居住地
                    </dt>
                    <dd className="mt-0.5 text-sm font-medium text-zinc-700">
                      {application.residence}
                    </dd>
                  </div>
                  <div>
                    <dt className="text-[10px] font-medium tracking-[0.15em] text-zinc-500 uppercase">
                      聯絡方式
                    </dt>
                    <dd className="mt-0.5 text-sm font-medium text-zinc-700">
                      {application.contact}
                    </dd>
                  </div>
                </dl>

                <p className="mt-5 whitespace-pre-wrap text-sm leading-relaxed text-zinc-700">
                  {application.reason}
                </p>

                <div className="mt-6 border-t border-zinc-100 pt-5">
                  <WorkerApplicationActions applicationId={application.id} />
                </div>
              </li>
            ))}
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
                    href={`/admin/worker-applications?page=${page - 1}`}
                  >
                    上一頁
                  </Button>
                )}
                {page < lastPage && (
                  <Button
                    variant="ghost"
                    href={`/admin/worker-applications?page=${page + 1}`}
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
