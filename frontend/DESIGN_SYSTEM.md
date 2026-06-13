# Carbon-ESG Design System

The visual contract for all Carbon-ESG surfaces. Apply this to marketing pages,
auth flows, member dashboards, and admin / 後台 tooling. Every page on every
subdomain must read as the same product.

## Brand Read

- **Audience**: Taiwanese landowners (rural, sometimes low tech-literacy),
  ESG-buying corporates, internal ops admins.
- **Tone**: editorial trust + earthy restraint. Not SaaS gloss, not crypto
  futurism, not government bureaucratic.
- **Dials**: `DESIGN_VARIANCE 6 / MOTION_INTENSITY 3 / VISUAL_DENSITY 3` on
  marketing surfaces. Admin surfaces shift density to `5-6` (denser working
  tool) but keep variance and motion the same.

## Tokens

### Color

| Role | Tailwind class | Hex |
|---|---|---|
| Background, default | `bg-white` | `#ffffff` |
| Background, alternate band | `bg-zinc-50` | `#fafafa` |
| Background, dark zone | `bg-zinc-950` | `#09090b` |
| Ink, body | `text-zinc-900` | `#18181b` |
| Ink, secondary | `text-zinc-700` | `#3f3f46` |
| Ink, caption | `text-zinc-600` | `#52525b` |
| Ink, muted meta | `text-zinc-500` | `#71717a` |
| Accent, CTA fill | `bg-emerald-600` | `#059669` |
| Accent, text on light | `text-emerald-700` | `#047857` |
| Accent, text on dark | `text-emerald-300` | `#6ee7b7` |
| Error | `text-red-700` / `bg-red-50` | `#b91c1c` / `#fef2f2` |

**One accent.** No purple, no neon, no second highlight color. Status meaning
comes from copy + structure, not extra greens / yellows / oranges.

### Typography

- **Font**: Plus Jakarta Sans via `next/font/google` declared once in
  `app/layout.tsx`. Weights 400 / 500 / 600 / 700 / 800.
- **Chinese fallback chain**: `PingFang TC, Noto Sans TC, Source Han Sans TC,
  system-ui, sans-serif`.
- **No serif.** No emphasis-via-serif injection in headlines. No italic for
  decoration.

| Role | Class string |
|---|---|
| H1 page | `text-4xl sm:text-5xl lg:text-6xl font-bold tracking-tight leading-[1.1] text-zinc-900` |
| H2 section | `text-3xl sm:text-4xl lg:text-5xl font-bold tracking-tight leading-[1.15] text-zinc-900` |
| H3 subsection | `text-xl lg:text-2xl font-bold leading-tight text-zinc-900` |
| Body | `text-base leading-relaxed text-zinc-700` |
| Lede subhead | `text-lg leading-relaxed text-zinc-600` |
| Meta uppercase | `text-xs font-medium tracking-[0.25em] uppercase text-zinc-500` |
| Inline error | `text-sm text-red-700` |

### Spacing

- **Container**: `mx-auto max-w-7xl px-6 lg:px-12`.
- **Section padding (marketing)**: `py-24 lg:py-32` default, `py-20 lg:py-28`
  dense, `py-28 lg:py-40` CTA section.
- **Section padding (member / admin)**: `py-12 lg:py-16`. Tools work harder,
  paddings shrink.
- **Grid**: prefer CSS Grid with explicit `gap-x-{n} gap-y-{n}`. No
  `w-[calc(33%-1rem)]` flex math.

### Shape

- **Buttons**: `rounded-md` (6px). All interactive elements.
- **Inputs**: `rounded-md`.
- **Photos**: no rounding by default (full-bleed editorial). `rounded-2xl` only
  inside feature-card surfaces.
- **One radius per layer.** No `rounded-full` pill buttons in a `rounded-md`
  page. No square cards on a pill-button surface.

### Motion

- **Library**: `motion` (formerly Framer Motion), imported from `motion/react`.
- **Reveal**: shared `<Reveal>` client component, `opacity 0 → 1` and
  `y 32 → 0`, duration `0.85s`, ease `[0.22, 1, 0.36, 1]`. Honors
  `useReducedMotion()`.
- **Active feedback**: `active:scale-[0.98]` on all clickable elements (use
  the `<Button>` primitive, which already includes this).
- **Hard bans**: no pulsing dots, no shimmer loops, no blurred orbs, no
  scroll-hijack, no auto-rotating carousels, no parallax. If you find
  yourself reaching for one, the dial is wrong.

## Components

All shared primitives live in `frontend/components/ui/`. Page-specific
composites live in `frontend/components/`.

### `<Button>` (primitive)

Four variants. Same height and padding across all surfaces.

```tsx
<Button variant="primary">建立帳號</Button>       {/* emerald-600 fill */}
<Button variant="secondary">登入</Button>         {/* white, on dark photo */}
<Button variant="ghost">取消</Button>             {/* zinc-300 border on light */}
<Button variant="ghostInverse">登入</Button>      {/* white border on dark */}
<Button href="/me">前往儀表板</Button>             {/* renders as Link */}
```

**CTA-label lock** (use these exact strings everywhere - no synonyms):

| Intent | Locked label |
|---|---|
| Register | `建立帳號` |
| Login | `登入` |
| Authenticated entry | `前往儀表板` |
| Sign out | `登出` |
| Submit (generic form) | `送出` |
| Cancel | `取消` |

Do NOT introduce "立即註冊" / "註冊一個帳號" / "Sign up" / "Sign in" variants.
If you need a new intent, add it here first.

### `<Field>` (form input)

Label above (never placeholder-as-label), optional hint below the input,
inline error below the hint, accessible `aria-describedby` wiring, focus ring
`emerald-600/20`.

```tsx
<Field label="電子郵件" type="email" autoComplete="email" required ... />
<Field label="密碼" type="password" hint="至少 8 個字元" error={errors.password} />
```

### `<Reveal>`

Scroll-fade-up wrapper for marketing sections. **Do NOT use on auth or admin**
surfaces. Those are tools, not narrative; users in a hurry should not be
animated into existence.

### `<StickyHeader>`

Marketing-only. Fixed nav that toggles from transparent (over hero photo) to
white (after scroll) via IntersectionObserver. Authenticated surfaces get a
different app shell (built when admin / member area expands).

## Surface Patterns

### Marketing / landing (`app/page.tsx`)

- Asymmetric editorial layouts; real photography; subtle scroll fade-ups.
- Section header: heading + 1-line sub stacked vertically. No "left big
  headline + right tiny floater".
- Max 2 eyebrows per 6 sections; zero is also fine.
- Wrap top-level section content in `<Reveal>` with delay cascade
  `0.1 / 0.15 / 0.2`.

### Auth (`app/(auth)/...`)

- **Split-screen 50 / 50** on desktop. Left: hero photo + dark gradient +
  brand wordmark + tagline. Right: white form panel, single column, max-w-sm.
- Mobile: photo collapses to a `28vh` top band, form stacks below.
- Form heading: `text-3xl font-bold tracking-tight`.
- Always include the cross-link to the sibling auth page
  (`登入` ↔ `建立帳號`).
- Error: red 50 background + red 200 border + red 700 ink in a
  `rounded-md` block.
- Forms use `<Field>` and `<Button>` — no inline Tailwind utility duplication.

### Authenticated member (`app/(protected)/...`)

- Plus Jakarta Sans, same tokens.
- Container `max-w-7xl px-6 lg:px-12`, section padding `py-12 lg:py-16`
  (denser than marketing).
- No marketing photography on tool pages. Surface-specific imagery only when
  it carries information (a land-detail page can show its aerial photo).
- Use the H1 + small uppercase meta pattern from `app/(protected)/me/page.tsx`
  as the default page-header style.

### Admin / 後台 (future, NOT built yet)

> This design system intentionally does NOT extend marketing-page aesthetics
> into dense product UI. Building a Carbon / Fluent / Radix substitute by
> hand in Tailwind is a tarpit. Use a real component library.

**Recommended path** when admin work begins:

1. Install **shadcn/ui** (you own the source):

   ```bash
   cd frontend
   npx shadcn@latest init
   ```

2. Override the generated CSS variables in `app/globals.css` to inherit our
   token system. This is what keeps the brand cohesive without you having to
   re-style every shadcn component:

   ```css
   :root {
     --background: 0 0% 100%;
     --foreground: 240 6% 10%;        /* zinc-900 */
     --muted: 240 5% 96%;             /* zinc-100 */
     --muted-foreground: 240 4% 46%;  /* zinc-500 */
     --border: 240 6% 90%;            /* zinc-200 */
     --input: 240 6% 90%;
     --primary: 158 64% 40%;          /* emerald-600 */
     --primary-foreground: 0 0% 100%;
     --ring: 158 64% 40%;
     --radius: 0.375rem;              /* rounded-md */
     --destructive: 0 72% 51%;        /* red-600 */
     --destructive-foreground: 0 0% 100%;
   }
   ```

3. Use shadcn primitives for: `DataTable`, `Form`, `Dialog`, `Tabs`,
   `Command`, `Sheet`, `DropdownMenu`. These cover the dense-UI patterns the
   marketing aesthetic intentionally avoids.

4. **Inherit brand**: top app bar shows `Carbon-ESG` wordmark in
   `font-semibold tracking-tight text-zinc-900`. Use the same Plus Jakarta
   Sans. Use the same `emerald-600` for primary actions and active states.
   The CSS variable map above keeps shadcn aligned without per-component
   styling.

5. Density target: `VISUAL_DENSITY: 5-6`. Smaller paddings (`p-4 / p-6`),
   `font-mono` for numeric columns, `divide-y` for tables. This DIVERGES
   from marketing density on purpose — admin is a tool, marketing is a
   poster. Both inherit palette and typography; they differ on density and
   layout patterns.

6. Member-facing forms (auth, profile, settings) keep the `<Field>` and
   `<Button>` primitives from this design system, not shadcn's. Reserve
   shadcn for admin-only / table-heavy surfaces.

## Hard Bans (every surface, no exceptions)

- **Em-dashes** (`—`, `–`). Use a period, comma, colon, or line break.
  This includes copy in code comments that's user-visible.
- **AI purple** / multi-stop gradient text. Single emerald is the only
  brand accent.
- **Blurred orbs / shimmer pulses / pulsing status dots** as decoration.
- **Hand-rolled decorative SVG illustrations.** Lucide icons are allowed
  (the project already depends on lucide-react). One icon family only.
- **Glassmorphism cards** for primary feature surfaces.
- **3-equal-card layouts**. Use 2-col zigzag, asymmetric grid, or bento.
- **Performative labels** like "Field Notes", "Start here", "Quietly in
  use at". Use plain functional language.
- **Fake-precise numbers** (99.99%, 500+, 24/7) unless they are real and
  cited. Mock data must be obviously illustrative.
- **Page theme flips mid-scroll.** Pick light or dark for the whole page;
  one full theme transition (light → dark CTA + footer) is allowed at
  most once.

## File layout

```
frontend/
├── DESIGN_SYSTEM.md               # ← this file
├── app/
│   ├── globals.css                # token + font setup
│   ├── layout.tsx                 # next/font + SessionProvider
│   ├── page.tsx                   # marketing landing
│   ├── (auth)/
│   │   ├── layout.tsx             # split-screen photo + form
│   │   ├── login/page.tsx
│   │   └── register/page.tsx
│   └── (protected)/
│       ├── layout.tsx             # session guard
│       └── me/page.tsx
├── components/
│   ├── ui/                        # shared primitives
│   │   ├── Button.tsx
│   │   └── Field.tsx
│   ├── Reveal.tsx                 # scroll-fade-up (marketing only)
│   ├── StickyHeader.tsx           # marketing nav (do not reuse)
│   └── LogoutButton.tsx           # composite, uses Button
└── lib/
    ├── api.ts
    ├── session/
    └── types/
```

## Adding a new surface

Before writing CSS:

1. Decide which surface category it falls under (marketing / auth / member /
   admin).
2. Inherit the matching pattern from above.
3. Import `<Button>` / `<Field>` / `<Reveal>` rather than inlining utilities.
4. Run the hard-ban list against the page before committing.
5. If you find yourself reaching for a new primitive (Modal, Toast,
   Accordion, Dropdown), add it under `components/ui/` so the next surface
   inherits it instead of re-inventing.
