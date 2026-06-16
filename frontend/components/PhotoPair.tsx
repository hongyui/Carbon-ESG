interface PhotoPairProps {
  before: string;
  after: string;
  className?: string;
}

const API_URL =
  process.env.NEXT_PUBLIC_API_URL ?? 'http://localhost:8000';

function resolveSrc(path: string): string {
  if (path.startsWith('http://') || path.startsWith('https://')) return path;
  return `${API_URL}/storage/${path.replace(/^\/+/, '')}`;
}

/**
 * Side-by-side maintenance photos for a worker job report. Used by the
 * worker job detail surface after approval and by the admin report
 * review queue. Per DESIGN_SYSTEM.md.
 */
export function PhotoPair({ before, after, className }: PhotoPairProps) {
  return (
    <div className={`grid grid-cols-2 gap-3 ${className ?? ''}`}>
      <figure className="flex flex-col gap-2">
        {/* eslint-disable-next-line @next/next/no-img-element */}
        <img
          src={resolveSrc(before)}
          alt="維護前環境照片"
          className="aspect-[4/3] w-full rounded-md border border-zinc-200 object-cover"
        />
        <figcaption className="text-center text-xs font-medium tracking-[0.2em] text-zinc-600 uppercase">
          前 · Before
        </figcaption>
      </figure>
      <figure className="flex flex-col gap-2">
        {/* eslint-disable-next-line @next/next/no-img-element */}
        <img
          src={resolveSrc(after)}
          alt="維護後環境照片"
          className="aspect-[4/3] w-full rounded-md border border-zinc-200 object-cover"
        />
        <figcaption className="text-center text-xs font-medium tracking-[0.2em] text-zinc-600 uppercase">
          後 · After
        </figcaption>
      </figure>
    </div>
  );
}
