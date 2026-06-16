import { redirect } from 'next/navigation';
import { getApplicationStatusFromCookies } from '@/lib/session/getApplicationStatus';
import { WorkerStatusBadge } from '@/components/WorkerStatusBadge';
import { Button } from '@/components/ui/Button';

export default async function WorkerApplyStatusPage() {
  const application = await getApplicationStatusFromCookies();

  if (!application) {
    redirect('/worker/apply');
  }

  return (
    <main className="mx-auto max-w-3xl px-6 py-12 lg:px-12 lg:py-16">
      <header className="mb-10">
        <p className="text-xs font-medium tracking-[0.25em] text-emerald-700 uppercase">
          工人申請
        </p>
        <h1 className="mt-3 text-3xl font-bold tracking-tight text-zinc-900 lg:text-4xl">
          申請狀態
        </h1>
        <div className="mt-4">
          <WorkerStatusBadge kind="application" status={application.status} />
        </div>
      </header>

      <section className="grid grid-cols-1 gap-6 rounded-md border border-zinc-200 bg-white p-6 sm:grid-cols-2">
        <Row label="申請原因">{application.reason}</Row>
        <Row label="工作經驗">{application.has_experience ? '有' : '無'}</Row>
        <Row label="年齡">{application.age} 歲</Row>
        <Row label="居住地">{application.residence}</Row>
        <Row label="聯絡方式">{application.contact}</Row>
        <Row label="送出時間">
          {new Date(application.created_at).toLocaleString('zh-Hant')}
        </Row>
        {application.status === 'rejected' && application.review_reason && (
          <div className="sm:col-span-2">
            <Row label="退件原因">
              <span className="text-red-700">{application.review_reason}</span>
            </Row>
          </div>
        )}
      </section>

      <div className="mt-8 flex flex-wrap gap-3">
        {application.status === 'approved' && (
          <Button href="/worker/jobs">前往工作機會</Button>
        )}
        <Button variant="ghost" href="/me">
          回到帳戶總覽
        </Button>
      </div>
    </main>
  );
}

function Row({
  label,
  children,
}: {
  label: string;
  children: React.ReactNode;
}) {
  return (
    <div className="flex flex-col gap-1">
      <span className="text-xs font-medium tracking-[0.2em] text-zinc-500 uppercase">
        {label}
      </span>
      <span className="text-sm text-zinc-900">{children}</span>
    </div>
  );
}
