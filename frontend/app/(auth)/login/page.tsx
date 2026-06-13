'use client';

import { useState, type FormEvent } from 'react';
import { useRouter } from 'next/navigation';
import Link from 'next/link';
import { api, ensureCsrfCookie } from '@/lib/api';
import { useSession } from '@/lib/session/SessionProvider';
import type { User } from '@/lib/types/user';
import { Button } from '@/components/ui/Button';
import { Field } from '@/components/ui/Field';

export default function LoginPage() {
  const router = useRouter();
  const { setUser } = useSession();
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);

  async function onSubmit(e: FormEvent<HTMLFormElement>) {
    e.preventDefault();
    setError(null);
    setSubmitting(true);
    try {
      await ensureCsrfCookie();
      const response = await api.post<{ user: User }>('/api/login', {
        email,
        password,
      });
      setUser(response.data.user);
      router.push('/me');
      router.refresh();
    } catch (err) {
      const message =
        (err as { response?: { data?: { message?: string } } }).response?.data
          ?.message ?? '登入失敗,請確認帳號密碼是否正確。';
      setError(message);
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <form onSubmit={onSubmit} className="flex flex-col gap-6">
      <header>
        <h1 className="text-3xl font-bold tracking-tight text-zinc-900">
          登入
        </h1>
        <p className="mt-2 text-sm text-zinc-600">
          還沒有帳號?{' '}
          <Link
            href="/register"
            className="font-medium text-emerald-700 underline-offset-4 hover:underline"
          >
            建立帳號
          </Link>
        </p>
      </header>

      <div className="flex flex-col gap-4">
        <Field
          label="電子郵件"
          type="email"
          autoComplete="email"
          required
          value={email}
          onChange={(e) => setEmail(e.target.value)}
        />
        <Field
          label="密碼"
          type="password"
          autoComplete="current-password"
          required
          value={password}
          onChange={(e) => setPassword(e.target.value)}
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
        {submitting ? '登入中⋯' : '登入'}
      </Button>
    </form>
  );
}
