import Image from 'next/image';
import Link from 'next/link';

export default function AuthLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  return (
    <div className="grid min-h-[100dvh] grid-cols-1 lg:grid-cols-2">
      {/* ─── Brand panel (left on desktop, top band on mobile) ─── */}
      <div className="relative h-[28vh] overflow-hidden lg:h-auto">
        <Image
          src="/hero-rice-fields.jpg"
          alt=""
          fill
          sizes="(min-width: 1024px) 50vw, 100vw"
          priority
          className="object-cover"
        />
        <div className="absolute inset-0 bg-linear-to-br from-zinc-950/65 via-zinc-950/35 to-emerald-900/55" />
        <div className="relative flex h-full flex-col justify-between p-6 lg:p-12">
          <Link
            href="/"
            className="text-base font-semibold tracking-tight text-white"
          >
            Carbon-ESG
          </Link>
          <div className="hidden lg:block">
            <p className="text-xs font-medium tracking-[0.3em] text-emerald-300 uppercase">
              碳權交易整合平台
            </p>
            <p className="mt-5 max-w-md text-3xl leading-snug font-bold text-white">
              把每一筆減碳,
              <br />
              變成可以驗證的證書。
            </p>
            <p className="mt-4 max-w-md text-sm leading-relaxed text-white/80">
              建立帳號後即可登錄土地碳匯、查看市場買賣、追蹤鏈上結算紀錄。
            </p>
          </div>
        </div>
      </div>

      {/* ─── Form panel (right on desktop, below on mobile) ─── */}
      <div className="relative flex flex-col bg-white">
        <div className="flex flex-1 items-center justify-center px-6 py-12 lg:px-12 lg:py-0">
          <div className="w-full max-w-sm">{children}</div>
        </div>
        <div className="border-t border-zinc-100 px-6 py-5 text-center text-xs text-zinc-500 lg:px-12">
          © 2026 Carbon-ESG · 碳權交易整合平台
        </div>
      </div>
    </div>
  );
}
