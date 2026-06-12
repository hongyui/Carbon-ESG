export default function Home() {
  const apiUrl = process.env.NEXT_PUBLIC_API_URL ?? 'http://localhost:8000';

  return (
    <main className="flex min-h-screen flex-col items-center justify-center gap-4 p-8 font-sans">
      <h1 className="text-4xl font-bold">Hello, Carbon-ESG</h1>
      <p className="text-sm text-zinc-500 dark:text-zinc-400">
        Backend API:{' '}
        <code className="rounded bg-zinc-100 px-2 py-1 font-mono dark:bg-zinc-800">
          {apiUrl}
        </code>
      </p>
    </main>
  );
}
