import { getOpportunities } from '@/lib/api';
import { OpportunityCategory } from '@/types/opportunity';
import { VerificationStamp } from '@/components/VerificationStamp';
import { SiteHeader } from '@/components/SiteHeader';
import Link from 'next/link';

const CATEGORY_LABELS: Record<OpportunityCategory, string> = {
  buying: 'Buying Lead',
  selling: 'Selling Lead',
  partnership: 'Partnership Lead',
};

function buildQuery(overrides: Record<string, string | number | undefined>): string {
  const params = new URLSearchParams();
  Object.entries(overrides).forEach(([key, value]) => {
    if (value !== undefined && value !== '') params.set(key, String(value));
  });
  const qs = params.toString();
  return qs ? `?${qs}` : '';
}

export default async function OpportunitiesPage({
  searchParams,
}: {
  searchParams: Promise<{ category?: OpportunityCategory; country?: string; search?: string; page?: string }>;
}) {
  const params = await searchParams;
  const page = params.page ? parseInt(params.page, 10) : 1;

  // If the API isn't reachable yet (e.g. Laravel backend not deployed),
  // fail gracefully instead of crashing the whole page.
  let result;
  let apiError: string | null = null;
  try {
    result = await getOpportunities({
      category: params.category,
      country: params.country,
      search: params.search,
      page,
    });
  } catch {
    apiError = 'Could not reach the opportunities API. Is the Laravel backend running?';
  }

  const activeFilters = { category: params.category, country: params.country, search: params.search };

  return (
    <>
      <SiteHeader />

      <main className="max-w-5xl mx-auto px-6 py-12 flex-1 w-full">
        <p className="font-mono text-xs tracking-[0.2em] text-seal font-medium mb-3">
          LIVE — HUMAN-VERIFIED ONLY
        </p>
        <h1 className="font-display font-bold text-3xl text-ink mb-8">Trade Opportunities</h1>

        <div className="flex gap-2 mb-6 flex-wrap">
          {(['buying', 'selling', 'partnership'] as OpportunityCategory[]).map((cat) => (
            <Link
              key={cat}
              href={`/opportunities${buildQuery({ ...activeFilters, category: params.category === cat ? undefined : cat, page: undefined })}`}
              className={`px-4 py-2 rounded-sm border text-sm font-medium transition-colors ${
                params.category === cat
                  ? 'bg-ink text-paper border-ink'
                  : 'bg-paper text-manifest border-line hover:border-ink'
              }`}
            >
              {CATEGORY_LABELS[cat]}s
            </Link>
          ))}
        </div>

        <form action="/opportunities" className="flex flex-wrap gap-2 mb-10 pb-6 border-b border-line">
          {params.category && <input type="hidden" name="category" value={params.category} />}
          <input
            type="text"
            name="search"
            defaultValue={params.search}
            placeholder="Search title, description, quantity…"
            className="flex-1 min-w-[220px] border border-line rounded-sm px-3 py-2 text-sm outline-none focus:border-ink bg-paper"
          />
          <input
            type="text"
            name="country"
            defaultValue={params.country}
            placeholder="Country"
            className="w-40 border border-line rounded-sm px-3 py-2 text-sm outline-none focus:border-ink bg-paper"
          />
          <button className="bg-ink text-paper px-4 py-2 rounded-sm text-sm font-medium hover:bg-ink/90 transition-colors">
            Filter
          </button>
          {(params.search || params.country) && (
            <Link
              href={`/opportunities${buildQuery({ category: params.category })}`}
              className="px-4 py-2 text-sm text-manifest hover:text-ink transition-colors"
            >
              Clear
            </Link>
          )}
        </form>

        {apiError && (
          <div className="rounded-sm border border-signal bg-signal-dim p-4 text-ink text-sm font-mono">
            {apiError}
          </div>
        )}

        {result && result.data.length === 0 && (
          <div className="rounded-sm border border-line bg-paper p-10 text-center text-manifest">
            {params.search || params.country
              ? 'No published opportunities match these filters.'
              : 'No published opportunities yet. Once the intelligence engine verifies one, it appears here.'}
          </div>
        )}

        <div className="space-y-4">
          {result?.data.map((opp) => (
            <Link
              key={opp.id}
              href={`/opportunities/${opp.id}`}
              className="flex items-start gap-5 rounded-md border border-line bg-paper p-5 hover:border-ink transition-colors"
            >
              <VerificationStamp size="sm" />
              <div className="flex-1 min-w-0">
                <div className="flex items-center gap-3 mb-2">
                  <span className="font-mono text-[10px] tracking-widest text-manifest border border-line px-2 py-1 rounded-sm">
                    {CATEGORY_LABELS[opp.category].toUpperCase()}
                  </span>
                  {opp.overall_score !== null && (
                    <span className="font-mono text-[10px] text-seal">
                      SCORE {opp.overall_score}/100
                    </span>
                  )}
                </div>
                <h2 className="font-display font-semibold text-lg text-ink">{opp.title}</h2>
                <p className="text-sm text-manifest mt-1 font-mono">
                  {opp.country}
                  {opp.quantity ? ` · ${opp.quantity}` : ''}
                  {opp.payment_terms ? ` · ${opp.payment_terms}` : ''}
                </p>
                <p className="text-sm text-ink/70 mt-2 line-clamp-2">{opp.description}</p>
              </div>
            </Link>
          ))}
        </div>

        {result && result.meta.last_page > 1 && (
          <div className="flex justify-center gap-2 mt-10 font-mono text-sm">
            {Array.from({ length: result.meta.last_page }, (_, i) => i + 1).map((p) => (
              <Link
                key={p}
                href={`/opportunities${buildQuery({ ...activeFilters, page: p })}`}
                className={`w-8 h-8 flex items-center justify-center rounded-sm ${
                  p === result.meta.current_page ? 'bg-ink text-paper' : 'bg-chart-dim text-manifest'
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
