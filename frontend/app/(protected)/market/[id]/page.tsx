import { notFound } from 'next/navigation';
import { serverGet } from '@/lib/api/server';
import { getSessionFromCookies } from '@/lib/session/server';
import type { CarbonListing } from '@/lib/api/listings';
import { Button } from '@/components/ui/Button';
import { PurchaseButton } from './PurchaseButton';

export default async function MarketListingDetailPage({
  params,
}: {
  params: Promise<{ id: string }>;
}) {
  const { id } = await params;

  const [data, user] = await Promise.all([
    serverGet<{ listing: CarbonListing }>(`/api/carbon-listings/${id}`),
    getSessionFromCookies(),
  ]);

  if (!data) notFound();

  const listing = data.listing;
  const isOwner = user?.id === listing.user_id;
  const priceLabel = `NT$ ${Number(listing.price_twd).toLocaleString()}`;

  return (
    <main className="mx-auto max-w-3xl px-6 py-12 lg:px-12 lg:py-16">
      <header className="mb-10">
        <Button variant="ghost" href="/market">
          ← 回到市場
        </Button>
        <p className="mt-6 text-xs font-medium tracking-[0.25em] text-emerald-700 uppercase">
          {listing.location}
        </p>
        <h1 className="mt-3 text-3xl font-bold tracking-tight text-zinc-900 lg:text-4xl">
          {listing.title}
        </h1>
      </header>

      <dl className="grid grid-cols-1 gap-x-12 gap-y-6 border-t border-zinc-200 pt-8 sm:grid-cols-3">
        <div>
          <dt className="text-xs font-medium tracking-[0.18em] text-zinc-500 uppercase">
            面積
          </dt>
          <dd className="mt-1 text-lg font-semibold text-zinc-900">
            {listing.hectares} 公頃
          </dd>
        </div>
        <div>
          <dt className="text-xs font-medium tracking-[0.18em] text-zinc-500 uppercase">
            估計年吸碳量
          </dt>
          <dd className="mt-1 text-lg font-semibold text-zinc-900">
            {listing.tonnes_co2e} 公噸 CO₂e
          </dd>
        </div>
        <div>
          <dt className="text-xs font-medium tracking-[0.18em] text-zinc-500 uppercase">
            開價
          </dt>
          <dd className="mt-1 text-lg font-semibold text-emerald-700">
            {priceLabel}
          </dd>
        </div>
      </dl>

      <section className="mt-10 border-t border-zinc-200 pt-8">
        <h2 className="text-xs font-medium tracking-[0.18em] text-zinc-500 uppercase">
          詳細說明
        </h2>
        <p className="mt-3 leading-relaxed text-zinc-700 whitespace-pre-wrap">
          {listing.description}
        </p>
      </section>

      <section className="mt-10 border-t border-zinc-200 pt-8">
        {isOwner ? (
          <p className="rounded-md border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm text-zinc-600">
            這是您自己的上架,無法購買。可從「我的上架」查看狀態或撤回。
          </p>
        ) : (
          <PurchaseButton listingId={listing.id} priceLabel={priceLabel} />
        )}
      </section>
    </main>
  );
}
