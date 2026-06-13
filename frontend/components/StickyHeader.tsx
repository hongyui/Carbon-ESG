'use client';

import { useEffect, useRef, useState } from 'react';
import Link from 'next/link';
import type { User } from '@/lib/types/user';

export function StickyHeader({ user }: { user: User | null }) {
  const [scrolled, setScrolled] = useState(false);
  const sentinelRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    const el = sentinelRef.current;
    if (!el) return;
    const obs = new IntersectionObserver(
      ([entry]) => setScrolled(!entry.isIntersecting),
      { threshold: 0 },
    );
    obs.observe(el);
    return () => obs.disconnect();
  }, []);

  return (
    <>
      {/* Sentinel — when this scrolls out of view, switch header to solid */}
      <div
        ref={sentinelRef}
        aria-hidden
        className="pointer-events-none absolute top-0 left-0 h-px w-full"
      />
      <header
        className={`fixed inset-x-0 top-0 z-50 transition-[background-color,border-color,backdrop-filter] duration-300 ${
          scrolled
            ? 'border-b border-zinc-200/70 bg-white/85 backdrop-blur-md'
            : 'border-b border-transparent bg-transparent'
        }`}
      >
        <div className="mx-auto flex max-w-7xl items-center justify-between px-6 py-4 lg:px-12">
          <Link
            href="/"
            className={`text-base font-semibold tracking-tight transition-colors ${
              scrolled ? 'text-zinc-900' : 'text-white'
            }`}
          >
            Carbon-ESG
          </Link>
          <nav className="flex items-center gap-1">
            <a
              href="#about"
              className={`hidden rounded-md px-3 py-2 text-sm transition-colors sm:inline-block ${
                scrolled
                  ? 'text-zinc-600 hover:text-zinc-900'
                  : 'text-white/80 hover:text-white'
              }`}
            >
              碳權與碳匯
            </a>
            <a
              href="#features"
              className={`hidden rounded-md px-3 py-2 text-sm transition-colors sm:inline-block ${
                scrolled
                  ? 'text-zinc-600 hover:text-zinc-900'
                  : 'text-white/80 hover:text-white'
              }`}
            >
              運作方式
            </a>
            {user ? (
              <Link
                href="/me"
                className={`ml-2 inline-flex items-center rounded-md px-4 py-2 text-sm font-medium transition-colors ${
                  scrolled
                    ? 'bg-zinc-900 text-white hover:bg-zinc-800'
                    : 'bg-white text-zinc-900 hover:bg-zinc-100'
                }`}
              >
                前往儀表板
              </Link>
            ) : (
              <Link
                href="/login"
                className={`ml-2 inline-flex items-center rounded-md px-4 py-2 text-sm font-medium transition-colors ${
                  scrolled
                    ? 'bg-zinc-900 text-white hover:bg-zinc-800'
                    : 'bg-white text-zinc-900 hover:bg-zinc-100'
                }`}
              >
                登入
              </Link>
            )}
          </nav>
        </div>
      </header>
    </>
  );
}
