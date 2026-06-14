import type { ReactNode } from 'react';

export interface EmptyStateProps {
  icon?: ReactNode;
  title: string;
  body?: string;
  cta?: ReactNode;
}

/**
 * Empty-state primitive for any list / queue surface (seller has no
 * listings yet, buyer has no purchases yet, admin queue is empty).
 * Single look across the app per DESIGN_SYSTEM.md.
 */
export function EmptyState({ icon, title, body, cta }: EmptyStateProps) {
  return (
    <div className="flex flex-col items-center justify-center gap-4 rounded-md border border-dashed border-zinc-300 bg-zinc-50 px-6 py-16 text-center">
      {icon && (
        <div className="text-zinc-400" aria-hidden>
          {icon}
        </div>
      )}
      <h2 className="text-xl font-semibold text-zinc-900">{title}</h2>
      {body && <p className="max-w-md text-sm text-zinc-600">{body}</p>}
      {cta && <div className="mt-2">{cta}</div>}
    </div>
  );
}
