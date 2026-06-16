'use client';

import { useState, type FormEvent } from 'react';
import { useParams, useRouter } from 'next/navigation';
import { Button } from '@/components/ui/Button';
import { submitReport } from '@/lib/api/worker';

export default function SubmitReportPage() {
  const params = useParams<{ id: string }>();
  const jobId = Number(params?.id);
  const router = useRouter();

  const [start, setStart] = useState('');
  const [end, setEnd] = useState('');
  const [content, setContent] = useState('');
  const [beforeFile, setBeforeFile] = useState<File | null>(null);
  const [afterFile, setAfterFile] = useState<File | null>(null);
  const [beforePreview, setBeforePreview] = useState<string | null>(null);
  const [afterPreview, setAfterPreview] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);

  function onFile(
    target: 'before' | 'after',
    e: React.ChangeEvent<HTMLInputElement>,
  ) {
    const f = e.target.files?.[0] ?? null;
    if (target === 'before') {
      setBeforeFile(f);
      setBeforePreview(f ? URL.createObjectURL(f) : null);
    } else {
      setAfterFile(f);
      setAfterPreview(f ? URL.createObjectURL(f) : null);
    }
  }

  async function onSubmit(e: FormEvent<HTMLFormElement>) {
    e.preventDefault();
    setError(null);

    if (!beforeFile || !afterFile) {
      setError('請上傳前後環境照片各一張。');
      return;
    }
    if (new Date(end) <= new Date(start)) {
      setError('結束時間必須晚於開始時間。');
      return;
    }

    setSubmitting(true);
    try {
      await submitReport(jobId, {
        datetime_start: start,
        datetime_end: end,
        content,
        before_image: beforeFile,
        after_image: afterFile,
      });
      router.push(`/worker/jobs/${jobId}`);
      router.refresh();
    } catch (err) {
      const r = err as {
        response?: {
          data?: { errors?: Record<string, string[]>; message?: string };
        };
      };
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
          工作回報
        </p>
        <h1 className="mt-3 text-3xl font-bold tracking-tight text-zinc-900 lg:text-4xl">
          提交回報
        </h1>
        <p className="mt-3 text-base text-zinc-600">
          填寫實際整地的開始與結束時間,並上傳維護前後兩張環境照片。檔案上限 5 MB,僅接受 JPG / PNG。
        </p>
      </header>

      <form
        onSubmit={onSubmit}
        encType="multipart/form-data"
        className="flex flex-col gap-5"
      >
        <div className="grid grid-cols-1 gap-5 sm:grid-cols-2">
          <div className="flex flex-col gap-1.5">
            <label
              htmlFor="start"
              className="text-sm font-medium text-zinc-700"
            >
              開始時間
            </label>
            <input
              id="start"
              type="datetime-local"
              required
              value={start}
              onChange={(e) => setStart(e.target.value)}
              className="rounded-md border border-zinc-300 bg-white px-3 py-2.5 text-sm text-zinc-900 transition focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-600/20"
            />
          </div>
          <div className="flex flex-col gap-1.5">
            <label htmlFor="end" className="text-sm font-medium text-zinc-700">
              結束時間
            </label>
            <input
              id="end"
              type="datetime-local"
              required
              value={end}
              onChange={(e) => setEnd(e.target.value)}
              className="rounded-md border border-zinc-300 bg-white px-3 py-2.5 text-sm text-zinc-900 transition focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-600/20"
            />
          </div>
        </div>

        <div className="grid grid-cols-1 gap-5 sm:grid-cols-2">
          <PhotoField
            label="前環境照片"
            preview={beforePreview}
            onChange={(e) => onFile('before', e)}
          />
          <PhotoField
            label="後環境照片"
            preview={afterPreview}
            onChange={(e) => onFile('after', e)}
          />
        </div>

        <div className="flex flex-col gap-1.5">
          <label
            htmlFor="content"
            className="text-sm font-medium text-zinc-700"
          >
            維護內容描述
          </label>
          <textarea
            id="content"
            required
            rows={5}
            value={content}
            onChange={(e) => setContent(e.target.value)}
            className="rounded-md border border-zinc-300 bg-white px-3 py-2.5 text-sm text-zinc-900 transition placeholder:text-zinc-400 focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-600/20"
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

        <div className="mt-2 flex flex-wrap gap-3">
          <Button type="submit" disabled={submitting}>
            {submitting ? '送出中⋯' : '提交回報'}
          </Button>
          <Button variant="ghost" href={`/worker/jobs/${jobId}`}>
            取消
          </Button>
        </div>
      </form>
    </main>
  );
}

function PhotoField({
  label,
  preview,
  onChange,
}: {
  label: string;
  preview: string | null;
  onChange: (e: React.ChangeEvent<HTMLInputElement>) => void;
}) {
  return (
    <div className="flex flex-col gap-2">
      <span className="text-sm font-medium text-zinc-700">{label}</span>
      {preview && (
        // eslint-disable-next-line @next/next/no-img-element
        <img
          src={preview}
          alt={`${label}預覽`}
          className="aspect-[4/3] w-full rounded-md border border-zinc-200 object-cover"
        />
      )}
      <input
        type="file"
        accept="image/jpeg,image/png,image/jpg"
        required
        onChange={onChange}
        className="block w-full text-sm text-zinc-700 file:mr-3 file:rounded-md file:border-0 file:bg-emerald-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-emerald-700 hover:file:bg-emerald-100"
      />
    </div>
  );
}
