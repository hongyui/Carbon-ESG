import Link from 'next/link';
import { serverGet } from '@/lib/api/server';
import type { CarbonListing, Paginated } from '@/lib/api/listings';
import { Button } from '@/components/ui/Button';
import { EmptyState } from '@/components/ui/EmptyState';

export default async function MarketPage({
  searchParams,
}: {
  searchParams: Promise<{ page?: string }>;
}) {
  const params = await searchParams;
  const page = Math.max(1, parseInt(params.page ?? '1', 10) || 1);

  const data = await serverGet<Paginated<CarbonListing>>(
    `/api/carbon-listings?page=${page}`,
  );

  const listings = data?.data ?? [];
  const lastPage = data?.last_page ?? 1;
  const total = data?.total ?? 0;

  return (
    <main className="mx-auto max-w-7xl px-6 py-12 lg:px-12 lg:py-16">
      <header className="mb-10">
        <p className="text-xs font-medium tracking-[0.25em] text-emerald-700 uppercase">
          市場
        </p>
        <h1 className="mt-3 text-3xl font-bold tracking-tight text-zinc-900 lg:text-4xl">
          目前可購買的碳匯
        </h1>
        <p className="mt-3 text-base text-zinc-600">
          每一塊地都通過管理員審核。點進去看詳細,選一塊適合的支持下去。
        </p>
      </header>

      {listings.length === 0 ? (
        <EmptyState
          title="目前沒有可購買的碳匯"
          body="新的上架正在等候管理員審核,請稍後再回來看看。"
        />
      ) : (
        <>
          <div className="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
            {listings.map((listing) => (
              <Link
                key={listing.id}
                href={`/market/${listing.id}`}
                className="group flex flex-col gap-4 rounded-md border border-zinc-200 bg-white p-6 transition hover:border-emerald-300 hover:bg-emerald-50/30"
              >
                <p className="text-xs font-medium tracking-[0.18em] text-zinc-500 uppercase">
                  {listing.location}
                </p>
                <h3 className="text-xl font-semibold text-zinc-900 group-hover:text-emerald-700">
                  {listing.title}
                </h3>
                <p className="line-clamp-3 text-sm text-zinc-600">
                  {listing.description}
                </p>
                <dl className="mt-auto flex items-end justify-between border-t border-zinc-100 pt-4">
                  <div>
                    <dt className="text-[10px] font-medium tracking-[0.15em] text-zinc-500 uppercase">
                      面積 / 吸碳量
                    </dt>
                    <dd className="mt-0.5 text-sm font-medium text-zinc-700">
                      {listing.hectares} 公頃 · {listing.tonnes_co2e} tCO₂e
                    </dd>
                  </div>
                  <div className="text-right">
                    <dt className="text-[10px] font-medium tracking-[0.15em] text-zinc-500 uppercase">
                      開價
                    </dt>
                    <dd className="mt-0.5 text-base font-semibold text-emerald-700">
                      NT$ {Number(listing.price_twd).toLocaleString()}
                    </dd>
                  </div>
                </dl>
              </Link>
            ))}
          </div>

          {lastPage > 1 && (
            <nav className="mt-10 flex items-center justify-between border-t border-zinc-200 pt-6">
              <p className="text-sm text-zinc-500">
                第 {page} / {lastPage} 頁 · 共 {total} 筆
              </p>
              <div className="flex gap-3">
                {page > 1 && (
                  <Button variant="ghost" href={`/market?page=${page - 1}`}>
                    上一頁
                  </Button>
                )}
                {page < lastPage && (
                  <Button variant="ghost" href={`/market?page=${page + 1}`}>
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
