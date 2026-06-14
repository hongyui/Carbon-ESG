'use client';

import { useState } from 'react';
import { useRouter } from 'next/navigation';
import { Button } from '@/components/ui/Button';
import { recallListing } from '@/lib/api/listings';

export function RecallButton({ listingId }: { listingId: number }) {
  const router = useRouter();
  const [confirming, setConfirming] = useState(false);
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);

  async function onRecall() {
    setError(null);
    setSubmitting(true);
    try {
      await recallListing(listingId);
      router.refresh();
    } catch (err) {
      const message =
        (err as { response?: { data?: { message?: string } } }).response?.data
          ?.message ?? '撤回失敗,請稍後再試。';
      setError(message);
    } finally {
      setSubmitting(false);
      setConfirming(false);
    }
  }

  if (!confirming) {
    return (
      <Button variant="ghost" onClick={() => setConfirming(true)}>
        撤回
      </Button>
    );
  }

  return (
    <div className="flex flex-col gap-3">
      <p className="text-sm text-zinc-700">
        確定要撤回?撤回後就不能再恢復,需要重新送出審核。
      </p>
      <div className="flex flex-wrap gap-3">
        <Button onClick={onRecall} disabled={submitting}>
          {submitting ? '處理中⋯' : '確定撤回'}
        </Button>
        <Button variant="ghost" onClick={() => setConfirming(false)}>
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
