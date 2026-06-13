'use client';

import { useState, type FormEvent } from 'react';
import { useRouter } from 'next/navigation';
import Link from 'next/link';
import { api, ensureCsrfCookie } from '@/lib/api';
import { useSession } from '@/lib/session/SessionProvider';
import type { User } from '@/lib/types/user';
import { Button } from '@/components/ui/Button';
import { Field } from '@/components/ui/Field';

interface RegisterForm {
  name: string;
  email: string;
  password: string;
  password_confirmation: string;
}

export default function RegisterPage() {
  const router = useRouter();
  const { setUser } = useSession();
  const [form, setForm] = useState<RegisterForm>({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
  });
  const [error, setError] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);

  function update(field: keyof RegisterForm, value: string) {
    setForm((prev) => ({ ...prev, [field]: value }));
  }

  async function onSubmit(e: FormEvent<HTMLFormElement>) {
    e.preventDefault();
    setError(null);
    setSubmitting(true);
    try {
      await ensureCsrfCookie();
      const response = await api.post<{ user: User }>('/api/register', form);
      setUser(response.data.user);
      router.push('/me');
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
            ?.message ?? '建立帳號失敗,請稍後再試。';
        setError(message);
      }
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <form onSubmit={onSubmit} className="flex flex-col gap-6">
      <header>
        <h1 className="text-3xl font-bold tracking-tight text-zinc-900">
          建立帳號
        </h1>
        <p className="mt-2 text-sm text-zinc-600">
          已經有帳號了?{' '}
          <Link
            href="/login"
            className="font-medium text-emerald-700 underline-offset-4 hover:underline"
          >
            登入
          </Link>
        </p>
      </header>

      <div className="flex flex-col gap-4">
        <Field
          label="姓名"
          type="text"
          autoComplete="name"
          required
          value={form.name}
          onChange={(e) => update('name', e.target.value)}
        />
        <Field
          label="電子郵件"
          type="email"
          autoComplete="email"
          required
          value={form.email}
          onChange={(e) => update('email', e.target.value)}
        />
        <Field
          label="密碼"
          type="password"
          autoComplete="new-password"
          required
          minLength={8}
          value={form.password}
          onChange={(e) => update('password', e.target.value)}
          hint="至少 8 個字元"
        />
        <Field
          label="確認密碼"
          type="password"
          autoComplete="new-password"
          required
          minLength={8}
          value={form.password_confirmation}
          onChange={(e) => update('password_confirmation', e.target.value)}
        />
      </div>

      {error && (
        <p
          role="alert"
          className="rounded-md border border-red-200 bg-red-50 px-3 py-2.5 text-sm text-red-700"
        >
          {error}
        </p>
      )}

      <Button type="submit" disabled={submitting} className="mt-1 w-full">
        {submitting ? '建立中⋯' : '建立帳號'}
      </Button>
    </form>
  );
}
