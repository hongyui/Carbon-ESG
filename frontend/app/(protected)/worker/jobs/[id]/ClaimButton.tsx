'use client';

import { useState } from 'react';
import { useRouter } from 'next/navigation';
import { Button } from '@/components/ui/Button';
import { claimJob } from '@/lib/api/worker';

export function ClaimButton({ jobId }: { jobId: number }) {
  const router = useRouter();
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);

  async function onClaim() {
    if (!window.confirm('確定要認領這份工作?認領後請於合理時間內完成並提交回報。')) {
      return;
    }
    setError(null);
    setSubmitting(true);
    try {
      await claimJob(jobId);
      router.refresh();
    } catch (err) {
      const r = err as {
        response?: { status?: number; data?: { message?: string } };
      };
      if (r.response?.status === 409) {
        setError(r.response.data?.message ?? '這個工作機會已經被別人認領了。');
      } else if (r.response?.status === 403) {
        setError('您目前無法認領這份工作。');
      } else {
        setError('認領失敗,請稍後再試。');
      }
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <div className="flex flex-col gap-2">
      <Button onClick={onClaim} disabled={submitting}>
        {submitting ? '認領中⋯' : '認領'}
      </Button>
      {error && (
        <p
          role="alert"
          className="rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700"
        >
          {error}
        </p>
      )}
    </div>
  );
}
