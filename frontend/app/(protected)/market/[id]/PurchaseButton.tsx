'use client';

import { useState } from 'react';
import { useRouter } from 'next/navigation';
import { Button } from '@/components/ui/Button';
import { purchaseListing } from '@/lib/api/listings';

export function PurchaseButton({
  listingId,
  priceLabel,
}: {
  listingId: number;
  priceLabel: string;
}) {
  const router = useRouter();
  const [confirming, setConfirming] = useState(false);
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);

  async function onPurchase() {
    setError(null);
    setSubmitting(true);
    try {
      await purchaseListing(listingId);
      router.push('/purchases');
      router.refresh();
    } catch (err) {
      const status = (err as { response?: { status?: number } }).response
        ?.status;
      if (status === 409) {
        setError('這筆碳匯已經被別人買走了,回到市場再選一塊吧。');
      } else if (status === 403) {
        setError('這筆碳匯目前無法購買,請回到市場再選一塊。');
      } else {
        const message =
          (err as { response?: { data?: { message?: string } } }).response?.data
            ?.message ?? '購買失敗,請稍後再試。';
        setError(message);
      }
      setSubmitting(false);
      setConfirming(false);
    }
  }

  if (!confirming) {
    return (
      <div className="flex flex-col gap-3">
        <Button onClick={() => setConfirming(true)}>購買</Button>
        {error && (
          <div className="flex flex-col gap-2">
            <p
              role="alert"
              className="rounded-md border border-red-200 bg-red-50 px-3 py-2.5 text-sm text-red-700"
            >
              {error}
            </p>
            <Button variant="ghost" href="/market">
              回到市場
            </Button>
          </div>
        )}
      </div>
    );
  }

  return (
    <div className="flex flex-col gap-3 rounded-md border border-emerald-200 bg-emerald-50/40 p-4">
      <p className="text-sm text-zinc-700">
        確認以 <span className="font-semibold text-emerald-700">{priceLabel}</span>{' '}
        購買這筆碳匯?購買後會立即過戶到你的帳號。
      </p>
      <div className="flex flex-wrap gap-3">
        <Button onClick={onPurchase} disabled={submitting}>
          {submitting ? '處理中⋯' : '確定購買'}
        </Button>
        <Button
          variant="ghost"
          onClick={() => setConfirming(false)}
          disabled={submitting}
        >
          取消
        </Button>
      </div>
    </div>
  );
}
