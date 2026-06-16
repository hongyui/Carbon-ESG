'use client';

import { useState } from 'react';
import { useRouter } from 'next/navigation';
import { Button } from '@/components/ui/Button';
import { adminApproveReport, adminRejectReport } from '@/lib/api/worker';

export function JobReportActions({ reportId }: { reportId: number }) {
  const router = useRouter();
  const [mode, setMode] = useState<'idle' | 'rejecting'>('idle');
  const [reason, setReason] = useState('');
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);

  async function onApprove() {
    setError(null);
    setSubmitting(true);
    try {
      await adminApproveReport(reportId);
      router.refresh();
    } catch (err) {
      setError(
        (err as { response?: { data?: { message?: string } } }).response?.data
          ?.message ?? '核准失敗,請稍後再試。',
      );
    } finally {
      setSubmitting(false);
    }
  }

  async function onReject() {
    setError(null);
    setSubmitting(true);
    try {
      await adminRejectReport(reportId, reason.trim() || undefined);
      router.refresh();
    } catch (err) {
      setError(
        (err as { response?: { data?: { message?: string } } }).response?.data
          ?.message ?? '退件失敗,請稍後再試。',
      );
    } finally {
      setSubmitting(false);
      setMode('idle');
      setReason('');
    }
  }

  if (mode === 'rejecting') {
    return (
      <div className="flex flex-col gap-3">
        <div className="flex flex-col gap-1.5">
          <label
            htmlFor={`reject-reason-report-${reportId}`}
            className="text-sm font-medium text-zinc-700"
          >
            退件原因(可選)
          </label>
          <textarea
            id={`reject-reason-report-${reportId}`}
            rows={3}
            value={reason}
            onChange={(e) => setReason(e.target.value)}
            placeholder="留給工人的說明,例如:前後照片不是同一塊地、整地不完整⋯"
            className="rounded-md border border-zinc-300 bg-white px-3 py-2.5 text-sm text-zinc-900 transition placeholder:text-zinc-400 focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-600/20"
          />
        </div>
        <div className="flex flex-wrap gap-3">
          <Button onClick={onReject} disabled={submitting}>
            {submitting ? '處理中⋯' : '退件回報'}
          </Button>
          <Button
            variant="ghost"
            onClick={() => {
              setMode('idle');
              setReason('');
            }}
            disabled={submitting}
          >
            取消
          </Button>
        </div>
        {error && (
          <p
            role="alert"
            className="rounded-md border border-red-200 bg-red-50 px-3 py-2.5 text-sm text-red-700"
          >
            {error}
          </p>
        )}
      </div>
    );
  }

  return (
    <div className="flex flex-col gap-3">
      <div className="flex flex-wrap gap-3">
        <Button onClick={onApprove} disabled={submitting}>
          {submitting ? '處理中⋯' : '核准回報'}
        </Button>
        <Button
          variant="ghost"
          onClick={() => setMode('rejecting')}
          disabled={submitting}
        >
          退件回報
        </Button>
      </div>
      {error && (
        <p
          role="alert"
          className="rounded-md border border-red-200 bg-red-50 px-3 py-2.5 text-sm text-red-700"
        >
          {error}
        </p>
      )}
    </div>
  );
}
