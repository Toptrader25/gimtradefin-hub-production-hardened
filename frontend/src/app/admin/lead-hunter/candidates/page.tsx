import Link from 'next/link';
import { SiteHeader } from '@/components/SiteHeader';
import { CandidateActions } from '@/components/CandidateActions';
import { LanguageAnalysisButton } from '@/components/LanguageAnalysisButton';
import { requireReviewer } from '@/lib/auth';
import { getCandidates } from '@/lib/lead-hunter-api';

export default async function CandidatesPage({
  searchParams,
}: {
  searchParams: Promise<{ page?: string }>;
}) {
  await requireReviewer();
  const params = await searchParams;
  const page = params.page ? parseInt(params.page, 10) : 1;
  const result = await getCandidates(page);

  return (
    <>
      <SiteHeader />
      <main className="max-w-5xl mx-auto px-6 py-12 flex-1 w-full">
        <Link href="/admin/lead-hunter" className="font-mono text-xs text-manifest hover:text-ink">
          ← Lead Hunter
        </Link>
        <h1 className="font-display font-bold text-3xl text-ink mt-3 mb-2">Discovered Candidates</h1>
        <p className="text-sm text-manifest mb-8 max-w-lg">
          Raw signals a source connector found — nothing here is verified yet. Resolve a candidate
          to an entity, or go straight to creating a verification case.
        </p>

        {!result && (
          <div className="border border-signal bg-signal-dim rounded-sm p-4 text-sm text-ink font-mono">
            Could not reach the admin API.
          </div>
        )}

        {result && result.data.length === 0 && (
          <div className="border border-line bg-paper rounded-sm p-10 text-center text-manifest">
            No candidates discovered yet. Source connectors are disabled by default until
            permission is confirmed — see docs/LEAD_HUNTER_DEPLOYMENT_READINESS.md.
          </div>
        )}

        <div className="space-y-3">
          {result?.data.map((c) => (
            <div
              key={c.id}
              className="flex items-start justify-between gap-4 border border-line bg-paper rounded-md p-4"
            >
              <div className="min-w-0">
                <p className="font-display font-medium text-ink">{c.title}</p>
                <p className="font-mono text-xs text-manifest mt-1">
                  {c.source_slug}
                  {c.country ? ` · ${c.country}` : ''}
                  {c.signal_type ? ` · ${c.signal_type}` : ''}
                </p>
                {c.url && (
                  <a
                    href={c.url}
                    target="_blank"
                    rel="noopener noreferrer nofollow"
                    className="text-xs text-seal underline mt-1 inline-block"
                  >
                    View source →
                  </a>
                )}
              </div>
              <div className="flex flex-col items-end gap-2 shrink-0">
                <CandidateActions candidateId={c.id} />
                <LanguageAnalysisButton candidateId={c.id} text={c.title} />
              </div>
            </div>
          ))}
        </div>

        {result && result.meta.last_page > 1 && (
          <div className="flex justify-center gap-2 mt-10 font-mono text-sm">
            {Array.from({ length: result.meta.last_page }, (_, i) => i + 1).map((p) => (
              <Link
                key={p}
                href={`/admin/lead-hunter/candidates?page=${p}`}
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
