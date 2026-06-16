'use client';

import { useState, type FormEvent } from 'react';
import { useRouter } from 'next/navigation';
import { Button } from '@/components/ui/Button';
import { Field } from '@/components/ui/Field';
import { createListing } from '@/lib/api/listings';

interface NewListingForm {
  title: string;
  description: string;
  hectares: string;
  tonnes_co2e: string;
  location: string;
  price_twd: string;
  needs_workers: boolean;
}

export default function NewListingPage() {
  const router = useRouter();
  const [form, setForm] = useState<NewListingForm>({
    title: '',
    description: '',
    hectares: '',
    tonnes_co2e: '',
    location: '',
    price_twd: '',
    needs_workers: false,
  });
  const [error, setError] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);

  function update<K extends keyof NewListingForm>(
    field: K,
    value: NewListingForm[K],
  ) {
    setForm((prev) => ({ ...prev, [field]: value }));
  }

  async function onSubmit(e: FormEvent<HTMLFormElement>) {
    e.preventDefault();
    setError(null);
    setSubmitting(true);
    try {
      await createListing({
        title: form.title,
        description: form.description,
        hectares: parseFloat(form.hectares),
        tonnes_co2e: parseFloat(form.tonnes_co2e),
        location: form.location,
        price_twd: parseFloat(form.price_twd),
        needs_workers: form.needs_workers,
      });
      router.push('/seller/listings');
      router.refresh();
    } catch (err) {
      const errors = (
        err as {
          response?: {
            data?: { errors?: Record<string, string[]>; message?: string };
          };
        }
      ).response?.data?.errors;
      if (errors) {
        setError(Object.values(errors).flat().join(' · '));
      } else {
        const message =
          (err as { response?: { data?: { message?: string } } }).response?.data
            ?.message ?? '送出失敗,請稍後再試。';
        setError(message);
      }
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <main className="mx-auto max-w-3xl px-6 py-12 lg:px-12 lg:py-16">
      <header className="mb-10">
        <p className="text-xs font-medium tracking-[0.25em] text-emerald-700 uppercase">
          送出審核
        </p>
        <h1 className="mt-3 text-3xl font-bold tracking-tight text-zinc-900 lg:text-4xl">
          新上架碳匯
        </h1>
        <p className="mt-3 text-base text-zinc-600">
          填寫土地與碳匯的基本資訊。送出後會進入管理員審核,通過後就會出現在市場。
        </p>
      </header>

      <form onSubmit={onSubmit} className="flex flex-col gap-5">
        <Field
          label="標題"
          type="text"
          required
          value={form.title}
          onChange={(e) => update('title', e.target.value)}
          hint="一句話描述這塊地,例如「台東 鹿野 9.2 公頃 紅葉部落公有林」"
        />
        <Field
          label="所在地"
          type="text"
          required
          value={form.location}
          onChange={(e) => update('location', e.target.value)}
        />
        <div className="grid grid-cols-1 gap-5 sm:grid-cols-2">
          <Field
            label="面積 (公頃)"
            type="number"
            step="0.01"
            min="0.01"
            required
            value={form.hectares}
            onChange={(e) => update('hectares', e.target.value)}
          />
          <Field
            label="估計年吸碳量 (公噸 CO₂e)"
            type="number"
            step="0.01"
            min="0.01"
            required
            value={form.tonnes_co2e}
            onChange={(e) => update('tonnes_co2e', e.target.value)}
          />
        </div>
        <Field
          label="開價 (TWD)"
          type="number"
          step="1"
          min="1"
          required
          value={form.price_twd}
          onChange={(e) => update('price_twd', e.target.value)}
        />
        <div className="flex flex-col gap-1.5">
          <label
            htmlFor="description"
            className="text-sm font-medium text-zinc-700"
          >
            詳細說明
          </label>
          <textarea
            id="description"
            required
            rows={6}
            value={form.description}
            onChange={(e) => update('description', e.target.value)}
            className="rounded-md border border-zinc-300 bg-white px-3 py-2.5 text-sm text-zinc-900 transition placeholder:text-zinc-400 focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-600/20"
          />
        </div>

        <label className="flex items-start gap-3 rounded-md border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm text-zinc-700">
          <input
            type="checkbox"
            checked={form.needs_workers}
            onChange={(e) => update('needs_workers', e.target.checked)}
            className="mt-0.5 size-4 rounded border-zinc-300 text-emerald-600 focus:ring-emerald-600/30"
          />
          <span>
            <span className="block font-medium text-zinc-900">
              這塊地需要工人後續維護
            </span>
            <span className="mt-1 block text-xs text-zinc-600">
              勾選後,賣出時系統會自動建立一筆工作機會,供已核准工人認領。
            </span>
          </span>
        </label>

        {error && (
          <p
            role="alert"
            className="rounded-md border border-red-200 bg-red-50 px-3 py-2.5 text-sm text-red-700"
          >
            {error}
          </p>
        )}

        <div className="mt-2 flex flex-wrap gap-3">
          <Button type="submit" disabled={submitting}>
            {submitting ? '送出中⋯' : '送出審核'}
          </Button>
          <Button variant="ghost" href="/seller/listings">
            取消
          </Button>
        </div>
      </form>
    </main>
  );
}
