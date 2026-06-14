import { serverGet } from '@/lib/api/server';
import type { CarbonListing, Paginated } from '@/lib/api/listings';
import { Button } from '@/components/ui/Button';
import { EmptyState } from '@/components/ui/EmptyState';
import { ReviewActions } from './ReviewActions';

export default async function AdminReviewPage({
  searchParams,
}: {
  searchParams: Promise<{ page?: string }>;
}) {
  const params = await searchParams;
  const page = Math.max(1, parseInt(params.page ?? '1', 10) || 1);

  const data = await serverGet<Paginated<CarbonListing>>(
    `/api/admin/carbon-listings/pending?page=${page}`,
  );
  const listings = data?.data ?? [];
  const lastPage = data?.last_page ?? 1;
  const total = data?.total ?? 0;

  return (
    <main className="mx-auto max-w-5xl px-6 py-12 lg:px-12 lg:py-16">
      <header className="mb-10">
        <p className="text-xs font-medium tracking-[0.25em] text-emerald-700 uppercase">
          後台
        </p>
        <h1 className="mt-3 text-3xl font-bold tracking-tight text-zinc-900 lg:text-4xl">
          審核佇列
        </h1>
        <p className="mt-3 text-base text-zinc-600">
          先進先出。核准後立即上市場,退件可以留下原因給賣家。
        </p>
      </header>

      {listings.length === 0 ? (
        <EmptyState
          title="目前沒有待審核的上架"
          body="新的上架會自動出現在這裡,排序由舊到新。"
        />
      ) : (
        <>
          <ul className="space-y-6">
            {listings.map((listing) => (
              <li
                key={listing.id}
                className="rounded-md border border-zinc-200 bg-white p-6"
              >
                <div className="flex flex-wrap items-start justify-between gap-3">
                  <div>
                    <p className="text-xs font-medium tracking-[0.18em] text-zinc-500 uppercase">
                      {listing.location} · 送出於{' '}
                      {new Date(listing.created_at).toLocaleString('zh-TW')}
                    </p>
                    <h3 className="mt-2 text-xl font-semibold text-zinc-900">
                      {listing.title}
                    </h3>
                  </div>
                  <p className="text-lg font-semibold text-emerald-700">
                    NT$ {Number(listing.price_twd).toLocaleString()}
                  </p>
                </div>

                <dl className="mt-5 grid grid-cols-2 gap-x-8 gap-y-3 border-t border-zinc-100 pt-5 sm:grid-cols-3">
                  <div>
                    <dt className="text-[10px] font-medium tracking-[0.15em] text-zinc-500 uppercase">
                      面積
                    </dt>
                    <dd className="mt-0.5 text-sm font-medium text-zinc-700">
                      {listing.hectares} 公頃
                    </dd>
                  </div>
                  <div>
                    <dt className="text-[10px] font-medium tracking-[0.15em] text-zinc-500 uppercase">
                      估計年吸碳量
                    </dt>
                    <dd className="mt-0.5 text-sm font-medium text-zinc-700">
                      {listing.tonnes_co2e} 公噸 CO₂e
                    </dd>
                  </div>
                  <div>
                    <dt className="text-[10px] font-medium tracking-[0.15em] text-zinc-500 uppercase">
                      上架者 ID
                    </dt>
                    <dd className="mt-0.5 text-sm font-medium text-zinc-700">
                      #{listing.user_id}
                    </dd>
                  </div>
                </dl>

                <p className="mt-5 whitespace-pre-wrap text-sm leading-relaxed text-zinc-700">
                  {listing.description}
                </p>

                <div className="mt-6 border-t border-zinc-100 pt-5">
                  <ReviewActions listingId={listing.id} />
                </div>
              </li>
            ))}
          </ul>

          {lastPage > 1 && (
            <nav className="mt-10 flex items-center justify-between border-t border-zinc-200 pt-6">
              <p className="text-sm text-zinc-500">
                第 {page} / {lastPage} 頁 · 共 {total} 筆
              </p>
              <div className="flex gap-3">
                {page > 1 && (
                  <Button
                    variant="ghost"
                    href={`/admin/review?page=${page - 1}`}
                  >
                    上一頁
                  </Button>
                )}
                {page < lastPage && (
                  <Button
                    variant="ghost"
                    href={`/admin/review?page=${page + 1}`}
                  >
                    下一頁
                  </Button>
                )}
              </div>
            </nav>
          )}
        </>
      )}
    </main>
  );
}
