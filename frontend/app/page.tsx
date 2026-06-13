import Image from 'next/image';
import Link from 'next/link';
import { ArrowUpRight } from 'lucide-react';
import { getSessionFromCookies } from '@/lib/session/server';
import { StickyHeader } from '@/components/StickyHeader';
import { Reveal } from '@/components/Reveal';

export default async function Home() {
  const user = await getSessionFromCookies();

  return (
    <>
      <StickyHeader user={user} />

      {/* ═══════════ HERO ═══════════ */}
      <section className="relative min-h-[100dvh] w-full overflow-hidden">
        <Image
          src="/hero-rice-fields.jpg"
          alt="花東縱谷的稻田與遠方山脈"
          fill
          priority
          sizes="100vw"
          className="object-cover"
        />
        <div className="absolute inset-0 bg-linear-to-t from-black/80 via-black/20 to-black/45" />

        <div className="absolute inset-x-0 bottom-0">
          <div className="mx-auto max-w-7xl px-6 pb-20 lg:px-12 lg:pb-28">
            <div className="max-w-3xl">
              <p className="mb-6 inline-flex items-center gap-3 text-xs font-medium tracking-[0.3em] text-emerald-300 uppercase">
                <span className="inline-block h-px w-10 bg-emerald-300" />
                Carbon-ESG · 田野到證書
              </p>
              <h1 className="text-4xl leading-[1.1] font-bold tracking-tight text-white sm:text-5xl lg:text-6xl xl:text-7xl">
                把每一筆減碳,
                <br />
                變成可以驗證的證書。
              </h1>
              <p className="mt-7 max-w-xl text-base leading-relaxed text-white/85 lg:text-lg">
                Carbon-ESG 把土地、碳匯、買家、執行者接在同一條鏈上。
                從一塊農地到一間鋼鐵廠,每筆減碳都能被驗證、定價、結算。
              </p>
              <div className="mt-10 flex flex-wrap items-center gap-3">
                {user ? (
                  <Link
                    href="/me"
                    className="inline-flex items-center gap-2 rounded-md bg-white px-6 py-3 text-sm font-semibold text-zinc-900 transition hover:bg-zinc-100 active:scale-[0.98]"
                  >
                    前往儀表板
                    <ArrowUpRight className="h-4 w-4" strokeWidth={2} />
                  </Link>
                ) : (
                  <>
                    <Link
                      href="/register"
                      className="inline-flex items-center gap-2 rounded-md bg-emerald-600 px-6 py-3 text-sm font-semibold text-white transition hover:bg-emerald-500 active:scale-[0.98]"
                    >
                      建立帳號
                      <ArrowUpRight className="h-4 w-4" strokeWidth={2} />
                    </Link>
                    <Link
                      href="/login"
                      className="inline-flex items-center rounded-md border border-white/30 px-6 py-3 text-sm font-semibold text-white transition hover:bg-white/10 active:scale-[0.98]"
                    >
                      登入
                    </Link>
                  </>
                )}
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* ═══════════ MARKET CONTEXT ═══════════ */}
      <section className="relative bg-zinc-950 py-20 text-white lg:py-28">
        <div className="mx-auto max-w-7xl px-6 lg:px-12">
          <Reveal>
            <p className="max-w-2xl text-lg leading-relaxed text-zinc-300 lg:text-xl">
              碳市場不是抽象的金融遊戲。它的每一張證書,都對應到一塊真實的土地、一個排放的工廠、或一座吸碳的森林。
            </p>
          </Reveal>

          <div className="mt-12 grid grid-cols-1 gap-3 lg:mt-16 lg:grid-cols-12 lg:grid-rows-2 lg:gap-4">
            <Reveal
              delay={0.1}
              className="lg:col-span-7 lg:col-start-1 lg:row-span-2"
            >
              <figure className="group relative aspect-[4/3] overflow-hidden lg:aspect-auto lg:h-full lg:min-h-[420px]">
                <Image
                  src="https://picsum.photos/seed/taiwan-forest-canopy-2026/1400/1050"
                  alt="台灣山林覆蓋"
                  fill
                  sizes="(min-width: 1024px) 55vw, 100vw"
                  className="object-cover transition duration-[800ms] group-hover:scale-[1.04]"
                />
                <div className="absolute inset-0 bg-linear-to-t from-zinc-950/90 via-zinc-950/10 to-transparent" />
                <figcaption className="absolute inset-x-6 bottom-6 lg:inset-x-9 lg:bottom-9">
                  <p className="text-[11px] font-semibold tracking-[0.3em] text-emerald-300 uppercase">
                    Forest · 森林
                  </p>
                  <p className="mt-3 max-w-md text-2xl leading-[1.25] font-bold text-white lg:text-3xl">
                    台灣國土近 60% 是森林,
                    亞洲少見的天然碳匯密度。
                  </p>
                </figcaption>
              </figure>
            </Reveal>

            <Reveal delay={0.2} className="lg:col-span-5 lg:col-start-8">
              <figure className="group relative aspect-[16/10] overflow-hidden lg:h-full">
                <Image
                  src="https://picsum.photos/seed/taiwan-steel-industry-cbam/1200/750"
                  alt="工業排放"
                  fill
                  sizes="(min-width: 1024px) 40vw, 100vw"
                  className="object-cover transition duration-[800ms] group-hover:scale-[1.04]"
                />
                <div className="absolute inset-0 bg-linear-to-t from-zinc-950/90 via-zinc-950/10 to-transparent" />
                <figcaption className="absolute inset-x-6 bottom-6">
                  <p className="text-[11px] font-semibold tracking-[0.3em] text-emerald-300 uppercase">
                    Industry · 工業
                  </p>
                  <p className="mt-2 max-w-sm text-lg leading-[1.3] font-bold text-white lg:text-xl">
                    CBAM 2026 上路後,排放成本將直接寫進出口報關單。
                  </p>
                </figcaption>
              </figure>
            </Reveal>

            <Reveal delay={0.3} className="lg:col-span-5 lg:col-start-8">
              <figure className="group relative aspect-[16/10] overflow-hidden lg:h-full">
                <Image
                  src="https://picsum.photos/seed/yilan-rice-paddy-aerial-spring/1200/750"
                  alt="閒置農地"
                  fill
                  sizes="(min-width: 1024px) 40vw, 100vw"
                  className="object-cover transition duration-[800ms] group-hover:scale-[1.04]"
                />
                <div className="absolute inset-0 bg-linear-to-t from-zinc-950/90 via-zinc-950/10 to-transparent" />
                <figcaption className="absolute inset-x-6 bottom-6">
                  <p className="text-[11px] font-semibold tracking-[0.3em] text-emerald-300 uppercase">
                    Farmland · 農地
                  </p>
                  <p className="mt-2 max-w-sm text-lg leading-[1.3] font-bold text-white lg:text-xl">
                    閒置農地的減碳潛力,目前還沒被市場計算進去。
                  </p>
                </figcaption>
              </figure>
            </Reveal>
          </div>
        </div>
      </section>

      {/* ═══════════ CONCEPT ═══════════ */}
      <section id="about" className="relative bg-white py-24 lg:py-32">
        <div
          aria-hidden
          className="pointer-events-none absolute right-0 bottom-0 hidden h-80 w-80 opacity-[0.06] lg:block"
          style={{
            backgroundImage:
              'radial-gradient(circle, #047857 1px, transparent 1px)',
            backgroundSize: '14px 14px',
          }}
        />

        <div className="relative mx-auto max-w-7xl px-6 lg:px-12">
          <Reveal>
            <div className="max-w-3xl">
              <h2 className="text-3xl leading-[1.15] font-bold tracking-tight text-zinc-900 sm:text-4xl lg:text-5xl">
                先把兩個詞講清楚。
              </h2>
              <p className="mt-5 max-w-2xl text-lg leading-relaxed text-zinc-600">
                碳權跟碳匯經常被混為一談。Carbon-ESG 是把它們接起來的那條管線。
              </p>
            </div>
          </Reveal>

          <div className="mt-14 grid grid-cols-1 gap-x-16 gap-y-14 border-t border-zinc-200 pt-14 md:grid-cols-2">
            <Reveal delay={0.1}>
              <article>
                <h3 className="text-2xl leading-tight font-bold text-zinc-900 lg:text-3xl">
                  碳權,是一張為減碳簽下的證書。
                </h3>
                <p className="mt-5 leading-relaxed text-zinc-700">
                  企業為了少排一噸 CO₂ 而獲得的一張證書。可在市場買賣,可以抵稅,可以宣告 ESG 目標完成。
                  讓「減碳」這個抽象動作,變成一個有價格、有買主、有條件的金融商品。
                </p>
              </article>
            </Reveal>

            <Reveal delay={0.2}>
              <article>
                <h3 className="text-2xl leading-tight font-bold text-zinc-900 lg:text-3xl">
                  碳匯,是自然界替我們算好的那筆帳。
                </h3>
                <p className="mt-5 leading-relaxed text-zinc-700">
                  森林吸 CO₂、海洋吸 CO₂、稻田吸 CO₂。這些自然吸收能力被量測、登錄、授信後,就能轉成可購買的碳權。
                  讓守住一片土地的人,因為「守住」這件事而獲得回報。
                </p>
              </article>
            </Reveal>
          </div>
        </div>
      </section>

      {/* ═══════════ WHY IT MATTERS ═══════════ */}
      <section className="bg-zinc-50 py-24 lg:py-32">
        <div className="mx-auto max-w-7xl px-6 lg:px-12">
          <div className="grid grid-cols-1 gap-12 lg:grid-cols-12 lg:gap-16">
            <Reveal className="lg:col-span-6 lg:pt-4">
              <h2 className="text-3xl leading-[1.15] font-bold tracking-tight text-zinc-900 sm:text-4xl lg:text-5xl">
                沒有市場,
                <br />
                減碳只是口號。
              </h2>
              <div className="mt-8 space-y-5 leading-relaxed text-zinc-700 lg:text-lg">
                <p>
                  把外部成本(排碳對環境的破壞)變成內部價格(碳權的交易價),才會有人在決策時把它算進去。
                </p>
                <p>
                  Carbon-ESG 不是慈善。我們把對的誘因放到對的地方:
                  守住土地的人因為「守住」獲得回報,
                  排放的企業因為減排獲得抵減,
                  中間靠鏈上紀錄串起信任。
                </p>
              </div>
            </Reveal>

            <Reveal delay={0.15} className="lg:col-span-6">
              <figure>
                <div className="relative aspect-[3/4] w-full overflow-hidden">
                  <Image
                    src="/field-workers.jpg"
                    alt="兩位工作者在草原上檢視土壤"
                    fill
                    sizes="(min-width: 1024px) 45vw, 100vw"
                    className="object-cover"
                  />
                </div>
                <figcaption className="mt-3 text-sm text-zinc-500">
                  在地工作者執行土壤檢測,作為碳匯量測的依據。
                </figcaption>
              </figure>
            </Reveal>
          </div>
        </div>
      </section>

      {/* ═══════════ HOW IT WORKS ═══════════ */}
      <section id="features" className="bg-white">
        <div className="mx-auto max-w-7xl px-6 pt-24 pb-16 lg:px-12 lg:pt-32 lg:pb-20">
          <Reveal>
            <div className="max-w-3xl">
              <h2 className="text-3xl leading-[1.15] font-bold tracking-tight text-zinc-900 sm:text-4xl lg:text-5xl">
                四個動作,
                <br />
                把整條鏈接起來。
              </h2>
              <p className="mt-5 max-w-2xl text-lg leading-relaxed text-zinc-600">
                這不是技術秀。把碳匯實際進入市場、讓買賣雙方放心交易、
                讓減碳收益真的回到在地,這四件事缺一個,整條鏈就斷了。
              </p>
            </div>
          </Reveal>

          <div className="mt-14 grid grid-cols-1 gap-x-16 gap-y-14 md:grid-cols-2">
            <Reveal delay={0.1}>
              <NumberedStep
                n="01"
                title="碳匯整合"
                body="把私有地、公有地、社區共有林、農會契作地的碳匯統一登錄上鏈。每筆都帶有面積、樹種、地理位置、有效期間。小至 0.5 公頃的家庭農地,大至 500 公頃的國有林,在同一個市場下都能交易,小地主第一次能跟大企業同桌談價。"
                meta="支援類型 · 森林 / 農地土壤 / 紅樹林 / 校地復育"
              />
            </Reveal>
            <Reveal delay={0.2}>
              <NumberedStep
                n="02"
                title="動態定價"
                body="不採用月度公告價、也不接受行政指導價,而是訂單簿即時撮合。價格隨買賣雙方的真實意願浮動,碳稅在交易瞬間自動結算。小地主不會被中間商盤剝,大買家不會被「行政估價」綁架,雙邊看到的是同一個真實價格。"
                meta="定價機制 · 訂單簿撮合 / 浮動價格 / 即時碳稅"
              />
            </Reveal>
          </div>
        </div>

        {/* Pullquote + land photo callout */}
        <div className="bg-zinc-50 py-20 lg:py-24">
          <div className="mx-auto max-w-7xl px-6 lg:px-12">
            <div className="grid grid-cols-1 items-center gap-10 lg:grid-cols-12 lg:gap-16">
              <Reveal className="lg:col-span-7">
                <p className="text-2xl leading-[1.35] font-medium text-zinc-900 lg:text-3xl">
                  每塊閒置的土地,
                  <br className="hidden sm:inline" />
                  都是一筆還沒被計算的減碳潛力。
                </p>
                <p className="mt-6 max-w-xl leading-relaxed text-zinc-600 lg:text-lg">
                  台灣現存閒置或休耕的農地約 11 萬公頃,接近 6 個台北市的面積。
                  這些土地過去因為價格、勞動力、出路問題被荒廢,
                  碳匯市場給了它們新的角色:不必種糧、不必養豬,
                  只要被守護就有價值。
                </p>
                <div className="mt-7 flex flex-wrap gap-x-5 gap-y-2 text-xs tracking-[0.15em] text-zinc-500 uppercase">
                  <span>彰化 · 大城廢魚塭</span>
                  <span className="text-zinc-300">/</span>
                  <span>台南 · 鹽水休耕稻田</span>
                  <span className="text-zinc-300">/</span>
                  <span>花蓮 · 玉里廢校</span>
                </div>
              </Reveal>
              <Reveal delay={0.15} className="lg:col-span-5">
                <figure>
                  <div className="relative aspect-[3/2] w-full overflow-hidden">
                    <Image
                      src="/field-land.jpg"
                      alt="尚未整合的閒置土地"
                      fill
                      sizes="(min-width: 1024px) 40vw, 100vw"
                      className="object-cover"
                    />
                  </div>
                </figure>
              </Reveal>
            </div>
          </div>
        </div>

        <div className="mx-auto max-w-7xl px-6 pt-16 pb-24 lg:px-12 lg:pt-20 lg:pb-32">
          <div className="grid grid-cols-1 gap-x-16 gap-y-14 md:grid-cols-2">
            <Reveal delay={0.1}>
              <NumberedStep
                n="03"
                title="鏈上結算"
                body="每筆碳權交易都由以太坊智能合約執行結算。買方付款、賣方移轉碳權、平台抽分潤,三件事在同一個 transaction 內原子化發生,不會出現「錢付了但證書沒到」的窘境。鏈上紀錄永久且公開,任何第三方都能驗證:這噸 CO₂ 在哪一塊地、誰買、誰賣、付了多少。"
                meta="技術基礎 · Ethereum 主網 / ERC-1155 標準 / 公開審計"
              />
            </Reveal>
            <Reveal delay={0.2}>
              <NumberedStep
                n="04"
                title="回饋在地"
                body="每筆碳權交易自動分潤 8% 到 15%,直接付給在地維護土地、紀錄碳匯、執行土壤檢測的工作者。這不是 ESG 報告書上的「社會責任」段落,而是寫進智能合約、必須執行的條款。減碳的價值真正回到減碳的人手上,而不是經過一層基金會、兩層 NGO、三層審查後剩下的零頭。"
                meta="分潤對象 · 在地檢測員 / 維護工作者 / 部落協議地主"
              />
            </Reveal>
          </div>
        </div>
      </section>

      {/* ═══════════ FIELD CASES GALLERY ═══════════ */}
      <section className="bg-zinc-50 py-24 lg:py-32">
        <div className="mx-auto max-w-7xl px-6 lg:px-12">
          <Reveal>
            <div className="max-w-3xl">
              <h2 className="text-3xl leading-[1.15] font-bold tracking-tight text-zinc-900 sm:text-4xl lg:text-5xl">
                上線後,
                <br />
                會出現的場景。
              </h2>
              <p className="mt-5 max-w-2xl text-lg leading-relaxed text-zinc-600">
                這些不是已經發生的交易,是 Carbon-ESG 設計要接住的真實情境。
                三塊不同節奏的土地,以及一個正在搜尋抵減量的排放方。
              </p>
            </div>
          </Reveal>

          <div className="mt-14 grid grid-cols-1 gap-x-8 gap-y-12 lg:grid-cols-12 lg:gap-y-16">
            {/* Card 1 — Land case, large with photo */}
            <FieldCaseLand
              colSpan="lg:col-span-8"
              location="台東 鹿野"
              status="待整合"
              meta="9.2 公頃 · 紅葉部落公有林"
              body="排放量已完成第三方初測,正在等候授信機構出具驗證書。部落會議上週通過分潤條款,確認碳權收益的 12% 將回到部落基金。一旦上線,這會是東部第一筆社區共有的碳匯資產。"
              details={[
                { label: '主要樹種', value: '樟樹 · 楓香 · 台灣肖楠' },
                { label: '驗證階段', value: '第三方初測完成,等候授信' },
                { label: '交易型態', value: '社區共有' },
                { label: '預估上線', value: '2026 Q3' },
              ]}
              seed="taitung-luye-village-tropical-forest"
              aspect="aspect-[16/9]"
              delay={0}
            />

            {/* Card 2 — Land case, medium with photo */}
            <FieldCaseLand
              colSpan="lg:col-span-6"
              location="高雄 甲仙"
              status="正在量測"
              meta="2.1 公頃 · 私有竹林"
              body="土壤碳含量初測中,預計兩季後可進入授信流程。地主希望走長期持有的低週轉模式,等市場累積足夠流動性後再賣。竹子的成長週期短、碳吸收效率高,適合作為入門級碳匯標的。"
              details={[
                { label: '樹種', value: '桂竹 · 麻竹' },
                { label: '預估年吸碳量', value: '約 4.8 噸 CO₂e' },
              ]}
              seed="kaohsiung-jiaxian-bamboo-mist-mountain"
              aspect="aspect-[4/5]"
              delay={0.1}
            />

            {/* Card 3 — Land case, medium with photo (new seed) */}
            <FieldCaseLand
              colSpan="lg:col-span-6"
              location="台中 東勢"
              status="洽談中"
              meta="0.8 公頃 · 廢校改造"
              body="東勢區地方發展協會嘗試把廢棄校舍周邊綠地轉成示範碳匯林。校內 30 餘棵原生樹齡都在 40 年以上,初估碳匯潛力可觀。協會希望以此案作為地方創生 + 碳匯資產的串接示範。"
              details={[
                { label: '主要樹種', value: '茄苳 · 烏心石' },
                { label: '案件特色', value: '地方創生串接' },
              ]}
              seed="taichung-mountain-cedar-trees-sunlight-trail"
              aspect="aspect-[4/5]"
              delay={0.15}
            />

            {/* Card 4 — Buyer case, NO PHOTO, dark themed card */}
            <FieldCaseBuyer
              colSpan="lg:col-span-12"
              location="桃園 觀音"
              status="買方搜尋中"
              meta="鋼鐵業排放抵減"
              headline="此排放方需於 2027 前找到 18,500 公噸 CO₂e 的抵減量。"
              body="目前 Carbon-ESG 演算法正在比對六塊符合條件的東部碳匯地。買方傾向長約鎖定價格,賣方傾向短約跟漲,我們在中間做撮合協商。預計三週內可完成首批配對。"
              details={[
                { label: '所需量', value: '18,500 公噸 CO₂e' },
                { label: '截止日', value: '2027 Q4' },
                { label: '已比對地塊', value: '6 塊 / 東部優先' },
                { label: '撮合狀態', value: '進行中' },
              ]}
              delay={0.2}
            />
          </div>
        </div>
      </section>

      {/* ═══════════ FINAL CTA ═══════════ */}
      <section className="relative overflow-hidden bg-zinc-950 py-28 text-white lg:py-40">
        <Image
          src="https://picsum.photos/seed/carbon-esg-dusk-mountain/1800/1200"
          alt=""
          fill
          sizes="100vw"
          className="object-cover opacity-[0.18]"
        />
        <div className="absolute inset-0 bg-linear-to-b from-zinc-950 via-zinc-950/85 to-zinc-950" />

        <div className="relative mx-auto max-w-3xl px-6 text-center lg:px-12">
          <Reveal>
            <h2 className="text-4xl leading-[1.1] font-bold tracking-tight sm:text-5xl lg:text-6xl">
              從一片土地開始。
            </h2>
            <p className="mx-auto mt-8 max-w-xl text-lg leading-relaxed text-zinc-300">
              把你的土地登錄上鏈,或選一片想守護的土地買下它的碳匯。
              行動從這裡開始。
            </p>
            <div className="mt-12 flex flex-wrap items-center justify-center gap-3">
              {user ? (
                <Link
                  href="/me"
                  className="inline-flex items-center gap-2 rounded-md bg-white px-7 py-3.5 text-sm font-semibold text-zinc-900 transition hover:bg-zinc-100 active:scale-[0.98]"
                >
                  前往儀表板
                  <ArrowUpRight className="h-4 w-4" strokeWidth={2} />
                </Link>
              ) : (
                <>
                  <Link
                    href="/register"
                    className="inline-flex items-center gap-2 rounded-md bg-emerald-600 px-7 py-3.5 text-sm font-semibold text-white transition hover:bg-emerald-500 active:scale-[0.98]"
                  >
                    建立帳號
                    <ArrowUpRight className="h-4 w-4" strokeWidth={2} />
                  </Link>
                  <Link
                    href="/login"
                    className="inline-flex items-center rounded-md border border-white/25 px-7 py-3.5 text-sm font-semibold text-white transition hover:bg-white/10 active:scale-[0.98]"
                  >
                    登入
                  </Link>
                </>
              )}
            </div>
          </Reveal>
        </div>
      </section>

      {/* ═══════════ FOOTER ═══════════ */}
      <footer className="bg-zinc-900 text-zinc-400">
        <div className="mx-auto grid max-w-7xl grid-cols-1 gap-12 px-6 py-16 lg:grid-cols-3 lg:px-12">
          <div>
            <div className="flex items-center gap-3">
              <Image
                src="/logo.png"
                alt=""
                width={36}
                height={36}
                className="opacity-95"
              />
              <div>
                <p className="font-semibold text-white">Carbon-ESG</p>
                <p className="mt-0.5 text-xs text-zinc-500">碳權交易整合平台</p>
              </div>
            </div>
            <p className="mt-6 max-w-sm text-sm leading-relaxed text-zinc-400">
              把減碳變成可交易的價值,讓守護土地的人也獲得回報。
            </p>
          </div>
          <nav>
            <p className="text-sm font-semibold text-white">關於平台</p>
            <ul className="mt-4 space-y-2 text-sm">
              <li>
                <a href="#about" className="transition hover:text-white">
                  碳權與碳匯
                </a>
              </li>
              <li>
                <a href="#features" className="transition hover:text-white">
                  運作方式
                </a>
              </li>
              <li>
                <Link href="/register" className="transition hover:text-white">
                  建立帳號
                </Link>
              </li>
              <li>
                <Link href="/login" className="transition hover:text-white">
                  登入
                </Link>
              </li>
            </ul>
          </nav>
          <div>
            <p className="text-sm font-semibold text-white">聯絡</p>
            <ul className="mt-4 space-y-2 text-sm">
              <li>info@carbon-esg.example</li>
              <li>台北市 · Carbon-ESG 計畫</li>
            </ul>
          </div>
        </div>
        <div className="border-t border-zinc-800">
          <div className="mx-auto max-w-7xl px-6 py-6 text-xs text-zinc-500 lg:px-12">
            © 2026 Carbon-ESG · 碳權交易整合平台
          </div>
        </div>
      </footer>
    </>
  );
}

/* ─────────────────────────────────────────────────────────────────
   NumberedStep — now with optional metadata footer
   ───────────────────────────────────────────────────────────────── */
function NumberedStep({
  n,
  title,
  body,
  meta,
}: {
  n: string;
  title: string;
  body: string;
  meta?: string;
}) {
  return (
    <article className="grid grid-cols-[auto_1fr] gap-x-6">
      <span className="font-mono text-xl text-emerald-700 lg:text-2xl">
        {n}
      </span>
      <div>
        <h3 className="text-xl leading-tight font-bold text-zinc-900 lg:text-2xl">
          {title}
        </h3>
        <p className="mt-3 leading-relaxed text-zinc-700">{body}</p>
        {meta && (
          <p className="mt-5 border-t border-zinc-200 pt-4 text-xs tracking-[0.18em] text-zinc-500 uppercase">
            {meta}
          </p>
        )}
      </div>
    </article>
  );
}

/* ─────────────────────────────────────────────────────────────────
   FieldCaseLand — photo-led card with details list
   ───────────────────────────────────────────────────────────────── */
function FieldCaseLand({
  colSpan,
  location,
  status,
  meta,
  body,
  details,
  seed,
  aspect,
  delay,
}: {
  colSpan: string;
  location: string;
  status: string;
  meta: string;
  body: string;
  details?: { label: string; value: string }[];
  seed: string;
  aspect: string;
  delay: number;
}) {
  return (
    <Reveal delay={delay} className={colSpan}>
      <article className="group">
        <figure
          className={`relative ${aspect} w-full overflow-hidden bg-zinc-200`}
        >
          <Image
            src={`https://picsum.photos/seed/${seed}/1200/900`}
            alt={location}
            fill
            sizes="(min-width: 1024px) 60vw, 100vw"
            className="object-cover transition duration-[800ms] group-hover:scale-[1.04]"
          />
        </figure>
        <div className="mt-5 flex flex-wrap items-center gap-x-3 gap-y-1">
          <span className="inline-flex items-center gap-1.5 text-[11px] font-semibold tracking-[0.18em] text-emerald-700 uppercase">
            <span className="inline-block h-1.5 w-1.5 rounded-full bg-emerald-500" />
            {status}
          </span>
          <span className="text-zinc-300">·</span>
          <span className="text-sm text-zinc-500">{meta}</span>
        </div>
        <h3 className="mt-2 text-xl leading-tight font-bold text-zinc-900 lg:text-2xl">
          {location}
        </h3>
        <p className="mt-3 max-w-2xl leading-relaxed text-zinc-700">{body}</p>
        {details && details.length > 0 && (
          <dl className="mt-6 grid grid-cols-1 gap-x-8 gap-y-3 border-t border-zinc-200 pt-5 sm:grid-cols-2">
            {details.map((d) => (
              <div key={d.label} className="text-sm">
                <dt className="text-xs tracking-[0.15em] text-zinc-500 uppercase">
                  {d.label}
                </dt>
                <dd className="mt-1 text-zinc-800">{d.value}</dd>
              </div>
            ))}
          </dl>
        )}
      </article>
    </Reveal>
  );
}

/* ─────────────────────────────────────────────────────────────────
   FieldCaseBuyer — no photo, dark typography-led buyer-side card
   Visually distinct from land cards to mark the demand side
   ───────────────────────────────────────────────────────────────── */
function FieldCaseBuyer({
  colSpan,
  location,
  status,
  meta,
  headline,
  body,
  details,
  delay,
}: {
  colSpan: string;
  location: string;
  status: string;
  meta: string;
  headline: string;
  body: string;
  details: { label: string; value: string }[];
  delay: number;
}) {
  return (
    <Reveal delay={delay} className={colSpan}>
      <article className="relative overflow-hidden bg-zinc-900 text-white">
        <div
          aria-hidden
          className="absolute inset-0 opacity-[0.05]"
          style={{
            backgroundImage:
              'radial-gradient(circle, #ffffff 1px, transparent 1px)',
            backgroundSize: '22px 22px',
          }}
        />
        <div
          aria-hidden
          className="absolute top-0 left-0 h-full w-1 bg-linear-to-b from-emerald-400 via-emerald-500 to-emerald-700"
        />

        <div className="relative grid grid-cols-1 gap-y-8 p-8 lg:grid-cols-12 lg:gap-x-10 lg:p-12">
          <div className="lg:col-span-7">
            <div className="flex flex-wrap items-center gap-x-3 gap-y-2">
              <span className="inline-flex items-center gap-2 rounded-full bg-emerald-500/15 px-3 py-1 text-[10px] font-semibold tracking-[0.2em] text-emerald-300 uppercase ring-1 ring-emerald-400/30">
                Buyer Side
              </span>
              <span className="text-[11px] font-semibold tracking-[0.2em] text-white/60 uppercase">
                {status} · {meta}
              </span>
            </div>
            <h3 className="mt-5 text-2xl leading-tight font-bold lg:text-3xl">
              {location}
            </h3>
            <p className="mt-3 text-lg leading-snug font-medium text-emerald-100/90 lg:text-xl">
              {headline}
            </p>
            <p className="mt-5 max-w-xl leading-relaxed text-white/75">
              {body}
            </p>
          </div>

          <dl className="grid grid-cols-2 gap-x-6 gap-y-5 self-start border-t border-white/15 pt-6 lg:col-span-5 lg:border-t-0 lg:border-l lg:pt-0 lg:pl-10">
            {details.map((d) => (
              <div key={d.label}>
                <dt className="text-[10px] tracking-[0.2em] text-white/45 uppercase">
                  {d.label}
                </dt>
                <dd className="mt-1.5 text-base font-semibold text-white">
                  {d.value}
                </dd>
              </div>
            ))}
          </dl>
        </div>
      </article>
    </Reveal>
  );
}
