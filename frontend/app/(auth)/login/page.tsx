'use client';

import { useState, type FormEvent } from 'react';
import { useRouter } from 'next/navigation';
import Link from 'next/link';
import { api, ensureCsrfCookie } from '@/lib/api';
import { useSession } from '@/lib/session/SessionProvider';
import type { User } from '@/lib/types/user';

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
          ?.message ?? 'Sign in failed';
      setError(message);
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <form onSubmit={onSubmit} className="flex flex-col gap-4">
      <h1 className="text-2xl font-bold">Sign in</h1>
      <label className="flex flex-col gap-1">
        <span className="text-sm">Email</span>
        <input
          type="email"
          required
          value={email}
          onChange={(e) => setEmail(e.target.value)}
          className="rounded border border-zinc-300 px-3 py-2 dark:border-zinc-700"
        />
      </label>
      <label className="flex flex-col gap-1">
        <span className="text-sm">Password</span>
        <input
          type="password"
          required
          value={password}
          onChange={(e) => setPassword(e.target.value)}
          className="rounded border border-zinc-300 px-3 py-2 dark:border-zinc-700"
        />
      </label>
      {error && <p className="text-sm text-red-600">{error}</p>}
      <button
        type="submit"
        disabled={submitting}
        className="rounded bg-zinc-900 px-4 py-2 text-white disabled:opacity-50 dark:bg-zinc-100 dark:text-zinc-900"
      >
        {submitting ? 'Signing in…' : 'Sign in'}
      </button>
      <p className="text-sm text-zinc-500">
        No account?{' '}
        <Link href="/register" className="underline">
          Register
        </Link>
      </p>
    </form>
  );
}
