'use client';

import { useState, type FormEvent } from 'react';
import { useRouter } from 'next/navigation';
import { Button } from '@/components/ui/Button';
import { Field } from '@/components/ui/Field';
import { createApplication } from '@/lib/api/worker';

interface ApplyForm {
  reason: string;
  has_experience: 'yes' | 'no';
  age: string;
  residence: string;
  contact: string;
}

export default function WorkerApplyPage() {
  const router = useRouter();
  const [form, setForm] = useState<ApplyForm>({
    reason: '',
    has_experience: 'no',
    age: '',
    residence: '',
    contact: '',
  });
  const [error, setError] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);

  function update<K extends keyof ApplyForm>(field: K, value: ApplyForm[K]) {
    setForm((prev) => ({ ...prev, [field]: value }));
  }

  async function onSubmit(e: FormEvent<HTMLFormElement>) {
    e.preventDefault();
    setError(null);
    setSubmitting(true);
    try {
      await createApplication({
        reason: form.reason,
        has_experience: form.has_experience === 'yes',
        age: parseInt(form.age, 10),
        residence: form.residence,
        contact: form.contact,
      });
      router.push('/worker/apply/status');
      router.refresh();
    } catch (err) {
      const r = err as {
        response?: {
          status?: number;
          data?: { errors?: Record<string, string[]>; message?: string };
        };
      };
      if (r.response?.status === 403 || r.response?.status === 409) {
        // Already applied — bounce to status page
        router.push('/worker/apply/status');
        return;
      }
      const errors = r.response?.data?.errors;
      if (errors) {
        setError(Object.values(errors).flat().join(' · '));
      } else {
        setError(r.response?.data?.message ?? '送出失敗,請稍後再試。');
      }
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <main className="mx-auto max-w-3xl px-6 py-12 lg:px-12 lg:py-16">
      <header className="mb-10">
        <p className="text-xs font-medium tracking-[0.25em] text-emerald-700 uppercase">
          加入工人計畫
        </p>
        <h1 className="mt-3 text-3xl font-bold tracking-tight text-zinc-900 lg:text-4xl">
          工人申請
        </h1>
        <p className="mt-3 text-base text-zinc-600">
          告訴我們你為什麼想加入。申請通過後,你會在「工作機會」看到正在等待維護的土地,可以認領並提交回報。
        </p>
      </header>

      <form onSubmit={onSubmit} className="flex flex-col gap-5">
        <div className="flex flex-col gap-1.5">
          <label htmlFor="reason" className="text-sm font-medium text-zinc-700">
            申請原因
          </label>
          <textarea
            id="reason"
            required
            rows={5}
            value={form.reason}
            onChange={(e) => update('reason', e.target.value)}
            className="rounded-md border border-zinc-300 bg-white px-3 py-2.5 text-sm text-zinc-900 transition placeholder:text-zinc-400 focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-600/20"
          />
        </div>

        <fieldset className="flex flex-col gap-2">
          <legend className="text-sm font-medium text-zinc-700">
            是否有相關工作經驗
          </legend>
          <div className="flex gap-4">
            <label className="flex items-center gap-2 text-sm text-zinc-700">
              <input
                type="radio"
                name="has_experience"
                checked={form.has_experience === 'yes'}
                onChange={() => update('has_experience', 'yes')}
                className="size-4 border-zinc-300 text-emerald-600 focus:ring-emerald-600/30"
              />
              是
            </label>
            <label className="flex items-center gap-2 text-sm text-zinc-700">
              <input
                type="radio"
                name="has_experience"
                checked={form.has_experience === 'no'}
                onChange={() => update('has_experience', 'no')}
                className="size-4 border-zinc-300 text-emerald-600 focus:ring-emerald-600/30"
              />
              否
            </label>
          </div>
        </fieldset>

        <div className="grid grid-cols-1 gap-5 sm:grid-cols-2">
          <Field
            label="年齡"
            type="number"
            required
            min="18"
            max="99"
            value={form.age}
            onChange={(e) => update('age', e.target.value)}
          />
          <Field
            label="居住地 (鄉鎮)"
            type="text"
            required
            value={form.residence}
            onChange={(e) => update('residence', e.target.value)}
          />
        </div>

        <Field
          label="聯絡方式"
          type="text"
          required
          value={form.contact}
          onChange={(e) => update('contact', e.target.value)}
          hint="手機 / Email / LINE 都可以"
        />

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
            {submitting ? '送出中⋯' : '送出申請'}
          </Button>
          <Button variant="ghost" href="/me">
            取消
          </Button>
        </div>
      </form>
    </main>
  );
}
