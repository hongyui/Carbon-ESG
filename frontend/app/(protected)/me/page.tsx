import { getSessionFromCookies } from '@/lib/session/server';
import { Button } from '@/components/ui/Button';

export default async function MePage() {
  const user = await getSessionFromCookies();
  if (!user) return null;

  return (
    <main className="mx-auto flex max-w-7xl flex-col gap-10 px-6 py-16 lg:px-12 lg:py-20">
      <header>
        <p className="text-xs font-medium tracking-[0.25em] text-emerald-700 uppercase">
          帳戶總覽
        </p>
        <h1 className="mt-3 text-4xl font-bold tracking-tight text-zinc-900 lg:text-5xl">
          歡迎,{user.name}。
        </h1>
        <p className="mt-3 text-base text-zinc-600">{user.email}</p>
      </header>

      <section className="border-t border-zinc-200 pt-10">
        <h2 className="text-xl font-bold text-zinc-900 lg:text-2xl">下一步</h2>
        <p className="mt-3 max-w-xl text-base leading-relaxed text-zinc-700">
          這是你的帳戶總覽,你可以從這裡進入碳市場、查看自己上架的土地與購買紀錄。
        </p>
        <div className="mt-6 flex flex-wrap gap-3">
          <Button href="/market">前往市場</Button>
          <Button variant="ghost" href="/seller/listings">
            我的上架
          </Button>
          <Button variant="ghost" href="/purchases">
            我的購買
          </Button>
          {user.isAdmin && (
            <Button variant="ghost" href="/admin/review">
              後台審核
            </Button>
          )}
        </div>
      </section>
    </main>
  );
}
