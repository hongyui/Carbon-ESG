'use client';

import { useState } from 'react';
import { useRouter } from 'next/navigation';
import { Button } from '@/components/ui/Button';
import { adminApprove, adminReject } from '@/lib/api/listings';

export function ReviewActions({ listingId }: { listingId: number }) {
  const router = useRouter();
  const [mode, setMode] = useState<'idle' | 'rejecting'>('idle');
  const [reason, setReason] = useState('');
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);

  async function onApprove() {
    setError(null);
    setSubmitting(true);
    try {
      await adminApprove(listingId);
      router.refresh();
    } catch (err) {
      const message =
        (err as { response?: { data?: { message?: string } } }).response?.data
          ?.message ?? '核准失敗,請稍後再試。';
      setError(message);
    } finally {
      setSubmitting(false);
    }
  }

  async function onReject() {
    setError(null);
    setSubmitting(true);
    try {
      await adminReject(listingId, reason.trim() || undefined);
      router.refresh();
    } catch (err) {
      const message =
        (err as { response?: { data?: { message?: string } } }).response?.data
          ?.message ?? '退件失敗,請稍後再試。';
      setError(message);
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
            htmlFor={`reject-reason-${listingId}`}
            className="text-sm font-medium text-zinc-700"
          >
            退件原因(可選)
          </label>
          <textarea
            id={`reject-reason-${listingId}`}
            rows={3}
            value={reason}
            onChange={(e) => setReason(e.target.value)}
            placeholder="留給賣家的修改建議,例如:照片不清楚、面積資訊不一致⋯"
            className="rounded-md border border-zinc-300 bg-white px-3 py-2.5 text-sm text-zinc-900 transition placeholder:text-zinc-400 focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-600/20"
          />
        </div>
        <div className="flex flex-wrap gap-3">
          <Button onClick={onReject} disabled={submitting} aria-label="確認退件">
            {submitting ? '處理中⋯' : '確認退件'}
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
        <Button
          onClick={onApprove}
          disabled={submitting}
          aria-label="核准這筆上架"
        >
          {submitting ? '處理中⋯' : '核准'}
        </Button>
        <Button
          variant="ghost"
          onClick={() => setMode('rejecting')}
          disabled={submitting}
          aria-label="退件這筆上架"
        >
          退件
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
