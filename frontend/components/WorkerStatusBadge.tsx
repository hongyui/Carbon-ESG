import type {
  WorkerApplicationStatus,
  WorkerJobStatus,
  WorkerJobReportStatus,
} from '@/lib/api/worker';

const APPLICATION: Record<
  WorkerApplicationStatus,
  { label: string; cls: string }
> = {
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
};

const JOB: Record<WorkerJobStatus, { label: string; cls: string }> = {
  open: {
    label: '開放認領',
    cls: 'bg-emerald-50 text-emerald-700 border-emerald-200',
  },
  claimed: {
    label: '進行中',
    cls: 'bg-amber-50 text-amber-700 border-amber-200',
  },
  reported: {
    label: '回報審核中',
    cls: 'bg-zinc-100 text-zinc-700 border-zinc-200',
  },
  approved: {
    label: '已完成',
    cls: 'bg-emerald-700 text-white border-emerald-700',
  },
};

const REPORT: Record<
  WorkerJobReportStatus,
  { label: string; cls: string }
> = {
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
};

interface AppProps {
  kind: 'application';
  status: WorkerApplicationStatus;
}
interface JobProps {
  kind: 'job';
  status: WorkerJobStatus;
}
interface ReportProps {
  kind: 'report';
  status: WorkerJobReportStatus;
}
type Props = AppProps | JobProps | ReportProps;

export function WorkerStatusBadge(props: Props) {
  const config =
    props.kind === 'application'
      ? APPLICATION[props.status]
      : props.kind === 'job'
        ? JOB[props.status]
        : REPORT[props.status];

  return (
    <span
      className={`inline-flex items-center rounded-md border px-2 py-0.5 text-xs font-medium ${config.cls}`}
    >
      {config.label}
    </span>
  );
}
