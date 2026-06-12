'use client';

import { useState, type FormEvent } from 'react';
import { useRouter } from 'next/navigation';
import Link from 'next/link';
import { api, ensureCsrfCookie } from '@/lib/api';
import { useSession } from '@/lib/session/SessionProvider';
import type { User } from '@/lib/types/user';

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
            ?.message ?? 'Registration failed';
        setError(message);
      }
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <form onSubmit={onSubmit} className="flex flex-col gap-4">
      <h1 className="text-2xl font-bold">Create account</h1>
      <label className="flex flex-col gap-1">
        <span className="text-sm">Name</span>
        <input
          type="text"
          required
          value={form.name}
          onChange={(e) => update('name', e.target.value)}
          className="rounded border border-zinc-300 px-3 py-2 dark:border-zinc-700"
        />
      </label>
      <label className="flex flex-col gap-1">
        <span className="text-sm">Email</span>
        <input
          type="email"
          required
          value={form.email}
          onChange={(e) => update('email', e.target.value)}
          className="rounded border border-zinc-300 px-3 py-2 dark:border-zinc-700"
        />
      </label>
      <label className="flex flex-col gap-1">
        <span className="text-sm">Password (min 8 chars)</span>
        <input
          type="password"
          required
          minLength={8}
          value={form.password}
          onChange={(e) => update('password', e.target.value)}
          className="rounded border border-zinc-300 px-3 py-2 dark:border-zinc-700"
        />
      </label>
      <label className="flex flex-col gap-1">
        <span className="text-sm">Confirm password</span>
        <input
          type="password"
          required
          minLength={8}
          value={form.password_confirmation}
          onChange={(e) => update('password_confirmation', e.target.value)}
          className="rounded border border-zinc-300 px-3 py-2 dark:border-zinc-700"
        />
      </label>
      {error && <p className="text-sm text-red-600">{error}</p>}
      <button
        type="submit"
        disabled={submitting}
        className="rounded bg-zinc-900 px-4 py-2 text-white disabled:opacity-50 dark:bg-zinc-100 dark:text-zinc-900"
      >
        {submitting ? 'Creating…' : 'Create account'}
      </button>
      <p className="text-sm text-zinc-500">
        Already have one?{' '}
        <Link href="/login" className="underline">
          Sign in
        </Link>
      </p>
    </form>
  );
}
