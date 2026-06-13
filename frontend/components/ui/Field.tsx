import type { InputHTMLAttributes } from 'react';
import { useId } from 'react';

export interface FieldProps
  extends Omit<InputHTMLAttributes<HTMLInputElement>, 'id' | 'className'> {
  label: string;
  hint?: string;
  error?: string;
  className?: string;
}

export function Field({
  label,
  hint,
  error,
  className = '',
  ...input
}: FieldProps) {
  const id = useId();
  const hintId = hint ? `${id}-hint` : undefined;
  const errorId = error ? `${id}-error` : undefined;
  const describedBy = [hintId, errorId].filter(Boolean).join(' ') || undefined;

  const inputCls = [
    'rounded-md border bg-white px-3 py-2.5 text-sm text-zinc-900 transition',
    'placeholder:text-zinc-400',
    'focus:outline-none focus:ring-2',
    error
      ? 'border-red-500 focus:border-red-500 focus:ring-red-500/20'
      : 'border-zinc-300 focus:border-emerald-600 focus:ring-emerald-600/20',
    className,
  ]
    .filter(Boolean)
    .join(' ');

  return (
    <div className="flex flex-col gap-1.5">
      <label htmlFor={id} className="text-sm font-medium text-zinc-700">
        {label}
      </label>
      <input
        id={id}
        aria-describedby={describedBy}
        aria-invalid={error ? true : undefined}
        {...input}
        className={inputCls}
      />
      {hint && !error && (
        <p id={hintId} className="text-xs text-zinc-500">
          {hint}
        </p>
      )}
      {error && (
        <p id={errorId} className="text-xs text-red-600">
          {error}
        </p>
      )}
    </div>
  );
}
