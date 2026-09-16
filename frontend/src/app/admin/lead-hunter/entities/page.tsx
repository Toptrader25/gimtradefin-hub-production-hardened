import Link from 'next/link';
import { SiteHeader } from '@/components/SiteHeader';
import { requireReviewer } from '@/lib/auth';
import { getEntities } from '@/lib/entity-api';

export default async function EntitiesPage({
  searchParams,
}: {
  searchParams: Promise<{ page?: string }>;
}) {
  await requireReviewer();
  const params = await searchParams;
  const page = params.page ? parseInt(params.page, 10) : 1;
  const result = await getEntities(page);

  return (
    <>
      <SiteHeader />
      <main className="max-w-5xl mx-auto px-6 py-12 flex-1 w-full">
        <Link href="/admin/lead-hunter" className="font-mono text-xs text-manifest hover:text-ink">
          ← Lead Hunter
        </Link>
        <h1 className="font-display font-bold text-3xl text-ink mt-3 mb-2">Resolved Entities</h1>
        <p className="text-sm text-manifest mb-8 max-w-lg">
          Companies/organizations Lead Hunter has resolved from candidates — the hub for risk,
          commercial intent, and verification for each one.
        </p>

        {!result && (
          <div className="border border-signal bg-signal-dim rounded-sm p-4 text-sm text-ink font-mono">
            Could not reach the admin API.
          </div>
        )}

        {result && result.data.length === 0 && (
          <div className="border border-line bg-paper rounded-sm p-10 text-center text-manifest">
            No entities resolved yet — resolve a candidate first.
          </div>
        )}

        <div className="grid sm:grid-cols-2 gap-4">
          {result?.data.map((e) => (
            <Link
              key={e.id}
              href={`/admin/lead-hunter/entities/${e.id}`}
              className="border border-line bg-paper rounded-md p-5 hover:border-ink transition-colors"
            >
              <span className="font-mono text-[10px] uppercase tracking-widest text-manifest border border-line px-2 py-0.5 rounded-sm">
                {e.resolution_state}
              </span>
              <h2 className="font-display font-semibold text-ink mt-2">{e.canonical_name}</h2>
              <p className="font-mono text-xs text-manifest mt-1">
                {e.entity_type}
                {e.country_name ? ` · ${e.country_name}` : ''}
              </p>
            </Link>
          ))}
        </div>

        {result && result.last_page > 1 && (
          <div className="flex justify-center gap-2 mt-10 font-mono text-sm">
            {Array.from({ length: result.last_page }, (_, i) => i + 1).map((p) => (
              <Link
                key={p}
                href={`/admin/lead-hunter/entities?page=${p}`}
                className={`w-8 h-8 flex items-center justify-center rounded-sm ${
                  p === result.current_page ? 'bg-ink text-paper' : 'bg-chart-dim text-manifest'
                }`}
              >
                {p}
              </Link>
            ))}
          </div>
        )}
      </main>
      <footer className="bg-ink text-paper/50 mt-auto">
        <div className="max-w-6xl mx-auto px-6 py-8 font-mono text-xs">
          &copy; {new Date().getFullYear()} GiMtradefin
        </div>
      </footer>
    </>
  );
}
