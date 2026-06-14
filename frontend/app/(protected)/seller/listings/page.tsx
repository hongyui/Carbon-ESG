import Link from 'next/link';
import { serverGet } from '@/lib/api/server';
import type { CarbonListing } from '@/lib/api/listings';
import { Button } from '@/components/ui/Button';
import { EmptyState } from '@/components/ui/EmptyState';
import { StatusBadge } from '@/components/StatusBadge';

export default async function SellerListingsPage() {
  const data = await serverGet<{ listings: CarbonListing[] }>(
    '/api/carbon-listings/mine',
  );
  const listings = data?.listings ?? [];

  return (
    <main className="mx-auto max-w-7xl px-6 py-12 lg:px-12 lg:py-16">
      <header className="mb-10 flex flex-wrap items-end justify-between gap-4">
        <div>
          <h1 className="text-3xl font-bold tracking-tight text-zinc-900 lg:text-4xl">
            我的上架
          </h1>
          <p className="mt-2 text-base text-zinc-600">
            所有狀態的碳匯,最新的排在最上面。
          </p>
        </div>
        <Button href="/seller/listings/new">送出審核</Button>
      </header>

      {listings.length === 0 ? (
        <EmptyState
          title="還沒有上架的碳匯"
          body="把你想守護的那塊地登錄上來,通過審核後就會進市場。"
          cta={<Button href="/seller/listings/new">送出審核</Button>}
        />
      ) : (
        <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
          {listings.map((listing) => (
            <Link
              key={listing.id}
              href={`/seller/listings/${listing.id}`}
              className="group flex flex-col gap-3 rounded-md border border-zinc-200 bg-white p-6 transition hover:border-zinc-300 hover:bg-zinc-50"
            >
              <div className="flex items-start justify-between gap-3">
                <h3 className="text-lg font-semibold text-zinc-900 group-hover:text-emerald-700">
                  {listing.title}
                </h3>
                <StatusBadge status={listing.status} />
              </div>
              <p className="text-sm text-zinc-600">
                {listing.location} · {listing.hectares} 公頃
              </p>
              <p className="text-sm font-medium text-zinc-700">
                NT$ {Number(listing.price_twd).toLocaleString()}
              </p>
            </Link>
          ))}
        </div>
      )}
    </main>
  );
}
