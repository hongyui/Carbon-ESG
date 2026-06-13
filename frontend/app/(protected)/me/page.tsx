import { getSessionFromCookies } from '@/lib/session/server';
import { LogoutButton } from '@/components/LogoutButton';
import { Button } from '@/components/ui/Button';

export default async function MePage() {
  const user = await getSessionFromCookies();
  if (!user) return null;

  return (
    <main className="mx-auto flex min-h-[100dvh] max-w-7xl flex-col justify-center gap-10 px-6 py-16 lg:px-12 lg:py-20">
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
        <h2 className="text-xl font-bold text-zinc-900 lg:text-2xl">
          下一步
        </h2>
        <p className="mt-3 max-w-xl text-base leading-relaxed text-zinc-700">
          帳戶已建立。土地登錄、碳匯量測、市場交易功能將於 phase-2 開放。
          目前可以先回首頁,熟悉平台的設計與運作方式。
        </p>
        <div className="mt-6 flex flex-wrap gap-3">
          <Button href="/">回首頁</Button>
          <LogoutButton />
        </div>
      </section>
    </main>
  );
}
