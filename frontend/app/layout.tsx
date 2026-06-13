import type { Metadata } from 'next';
import { Plus_Jakarta_Sans } from 'next/font/google';
import './globals.css';
import { SessionProvider } from '@/lib/session/SessionProvider';
import { getSessionFromCookies } from '@/lib/session/server';

const jakarta = Plus_Jakarta_Sans({
  variable: '--font-jakarta',
  subsets: ['latin'],
  display: 'swap',
  weight: ['400', '500', '600', '700', '800'],
});

export const metadata: Metadata = {
  title: 'Carbon-ESG · 碳權交易整合平台',
  description:
    '把土地、碳匯、買家、執行者接在同一條鏈上 — Carbon-ESG 讓減碳變成可交易、可追蹤、可驗證的價值。',
};

export default async function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  const user = await getSessionFromCookies();

  return (
    <html lang="zh-Hant-TW" className={`${jakarta.variable} antialiased`}>
      <body className="min-h-full flex flex-col">
        <SessionProvider initialUser={user}>{children}</SessionProvider>
      </body>
    </html>
  );
}
