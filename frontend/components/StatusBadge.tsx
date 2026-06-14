import type { ListingStatus } from '@/lib/api/listings';

const config: Record<ListingStatus, { label: string; cls: string }> = {
  pending: {
    label: '審核中',
    cls: 'bg-zinc-100 text-zinc-700 border-zinc-200',
  },
  approved: {
    label: '已核准',
    cls: 'bg-emerald-50 text-emerald-700 border-emerald-200',
  },
  rejected: {
    label: '已退件',
    cls: 'bg-red-50 text-red-700 border-red-200',
  },
  recalled: {
    label: '已撤回',
    cls: 'bg-amber-50 text-amber-700 border-amber-200',
  },
  sold: {
    label: '已售出',
    cls: 'bg-emerald-700 text-white border-emerald-700',
  },
};

export function StatusBadge({ status }: { status: ListingStatus }) {
  const c = config[status];
  return (
    <span
      className={`inline-flex items-center rounded-md border px-2 py-0.5 text-xs font-medium ${c.cls}`}
    >
      {c.label}
    </span>
  );
}
