import { notFound } from 'next/navigation';
import { serverGet } from '@/lib/api/server';
import type { CarbonListing } from '@/lib/api/listings';
import { Button } from '@/components/ui/Button';
import { StatusBadge } from '@/components/StatusBadge';
import { RecallButton } from './RecallButton';

export default async function SellerListingDetailPage({
  params,
}: {
  params: Promise<{ id: string }>;
}) {
  const { id } = await params;
  const data = await serverGet<{ listing: CarbonListing }>(
    `/api/carbon-listings/${id}`,
  );
  if (!data) notFound();

  const listing = data.listing;
  const canRecall =
    listing.status === 'pending' || listing.status === 'approved';

  return (
    <main className="mx-auto max-w-3xl px-6 py-12 lg:px-12 lg:py-16">
      <header className="mb-10 flex flex-wrap items-start justify-between gap-4">
        <div>
          <Button variant="ghost" href="/seller/listings">
            ← 返回我的上架
          </Button>
          <h1 className="mt-4 text-3xl font-bold tracking-tight text-zinc-900 lg:text-4xl">
            {listing.title}
          </h1>
          <div className="mt-3 flex items-center gap-3">
            <StatusBadge status={listing.status} />
            <span className="text-sm text-zinc-500">{listing.location}</span>
          </div>
        </div>
      </header>

      <dl className="grid grid-cols-1 gap-x-12 gap-y-6 border-t border-zinc-200 pt-8 sm:grid-cols-2">
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
          <dd className="mt-1 text-lg font-semibold text-zinc-900">
            NT$ {Number(listing.price_twd).toLocaleString()}
          </dd>
        </div>
        <div>
          <dt className="text-xs font-medium tracking-[0.18em] text-zinc-500 uppercase">
            送出時間
          </dt>
          <dd className="mt-1 text-sm text-zinc-700">
            {new Date(listing.created_at).toLocaleString('zh-TW')}
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

      {listing.admin_note && (
        <section className="mt-8 rounded-md border border-amber-200 bg-amber-50 p-4">
          <h2 className="text-xs font-medium tracking-[0.18em] text-amber-800 uppercase">
            管理員備註
          </h2>
          <p className="mt-2 text-sm text-amber-900">{listing.admin_note}</p>
        </section>
      )}

      <section className="mt-10 border-t border-zinc-200 pt-8">
        {canRecall ? (
          <RecallButton listingId={listing.id} />
        ) : (
          <p className="text-sm text-zinc-500">
            這筆上架的狀態為「
            {listing.status === 'sold'
              ? '已售出'
              : listing.status === 'rejected'
                ? '已退件'
                : '已撤回'}
            」,無法再做變更。
          </p>
        )}
      </section>
    </main>
  );
}
