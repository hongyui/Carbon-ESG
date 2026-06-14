import Link from 'next/link';
import { serverGet } from '@/lib/api/server';
import type { CarbonPurchase, Paginated } from '@/lib/api/listings';
import { Button } from '@/components/ui/Button';
import { EmptyState } from '@/components/ui/EmptyState';

export default async function PurchasesPage({
  searchParams,
}: {
  searchParams: Promise<{ page?: string }>;
}) {
  const params = await searchParams;
  const page = Math.max(1, parseInt(params.page ?? '1', 10) || 1);

  const data = await serverGet<Paginated<CarbonPurchase>>(
    `/api/purchases?page=${page}`,
  );
  const purchases = data?.data ?? [];
  const lastPage = data?.last_page ?? 1;
  const total = data?.total ?? 0;

  return (
    <main className="mx-auto max-w-5xl px-6 py-12 lg:px-12 lg:py-16">
      <header className="mb-10">
        <h1 className="text-3xl font-bold tracking-tight text-zinc-900 lg:text-4xl">
          我的購買
        </h1>
        <p className="mt-2 text-base text-zinc-600">
          所有完成過戶的碳匯,最新的排在最上面。
        </p>
      </header>

      {purchases.length === 0 ? (
        <EmptyState
          title="還沒有購買紀錄"
          body="到市場挑一塊你想守護的地,讓這份支持留下紀錄。"
          cta={<Button href="/market">前往市場</Button>}
        />
      ) : (
        <>
          <ul className="divide-y divide-zinc-200 border-y border-zinc-200">
            {purchases.map((purchase) => {
              const listing = purchase.carbon_listing;
              const title = listing?.title ?? `Listing #${purchase.carbon_listing_id}`;
              const location = listing?.location;
              return (
                <li key={purchase.id} className="py-6">
                  <div className="flex flex-wrap items-start justify-between gap-4">
                    <div className="flex flex-col gap-1">
                      {location && (
                        <p className="text-xs font-medium tracking-[0.18em] text-zinc-500 uppercase">
                          {location}
                        </p>
                      )}
                      {listing ? (
                        <Link
                          href={`/market/${listing.id}`}
                          className="text-lg font-semibold text-zinc-900 hover:text-emerald-700"
                        >
                          {title}
                        </Link>
                      ) : (
                        <p className="text-lg font-semibold text-zinc-900">
                          {title}
                        </p>
                      )}
                      <p className="text-sm text-zinc-500">
                        購買於 {new Date(purchase.created_at).toLocaleString('zh-TW')}
                      </p>
                    </div>
                    <p className="text-lg font-semibold text-emerald-700">
                      NT$ {Number(purchase.price_twd).toLocaleString()}
                    </p>
                  </div>
                </li>
              );
            })}
          </ul>

          {lastPage > 1 && (
            <nav className="mt-10 flex items-center justify-between border-t border-zinc-200 pt-6">
              <p className="text-sm text-zinc-500">
                第 {page} / {lastPage} 頁 · 共 {total} 筆
              </p>
              <div className="flex gap-3">
                {page > 1 && (
                  <Button variant="ghost" href={`/purchases?page=${page - 1}`}>
                    上一頁
                  </Button>
                )}
                {page < lastPage && (
                  <Button variant="ghost" href={`/purchases?page=${page + 1}`}>
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
