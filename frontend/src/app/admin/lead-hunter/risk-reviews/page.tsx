import Link from 'next/link';
import { SiteHeader } from '@/components/SiteHeader';
import { requireReviewer } from '@/lib/auth';
import { getRiskReviews } from '@/lib/entity-api';

const PRIORITY_COLOR: Record<string, string> = {
  critical: 'text-[#A63D3D]',
  high: 'text-signal',
  medium: 'text-manifest',
  low: 'text-manifest',
};

export default async function RiskReviewsPage() {
  await requireReviewer();
  const result = await getRiskReviews();

  return (
    <>
      <SiteHeader />
      <main className="max-w-4xl mx-auto px-6 py-12 flex-1 w-full">
        <Link href="/admin/lead-hunter" className="font-mono text-xs text-manifest hover:text-ink">
          ← Lead Hunter
        </Link>
        <h1 className="font-display font-bold text-3xl text-ink mt-3 mb-2">Risk Reviews</h1>
        <p className="text-sm text-manifest mb-8 max-w-lg">
          Candidates or entities the trust/risk pipeline flagged as needing a human look, separate
          from full verification cases.
        </p>

        {!result && (
          <div className="border border-signal bg-signal-dim rounded-sm p-4 text-sm text-ink font-mono">
            Could not reach the admin API.
          </div>
        )}

        {result && result.data.length === 0 && (
          <div className="border border-line bg-paper rounded-sm p-10 text-center text-manifest">
            Nothing flagged right now.
          </div>
        )}

        <div className="space-y-3">
          {result?.data.map((r) => (
            <div key={r.id} className="border border-line bg-paper rounded-md p-4">
              <div className="flex items-center justify-between gap-3">
                <span className="font-mono text-[10px] uppercase tracking-widest border border-line px-2 py-0.5 rounded-sm">
                  {r.status}
                </span>
                <span className={`font-mono text-[10px] uppercase ${PRIORITY_COLOR[r.priority] || ''}`}>
                  {r.priority}
                </span>
              </div>
              <p className="font-display font-medium text-ink mt-2">{r.case_type}</p>
              <p className="font-mono text-xs text-manifest mt-1">
                {r.subject_type} {r.subject_id}
              </p>
              {r.reason && <p className="text-sm text-ink/80 mt-2">{r.reason}</p>}
              {r.subject_type === 'entity' && (
                <Link
                  href={`/admin/lead-hunter/entities/${r.subject_id}`}
                  className="text-xs text-seal underline mt-2 inline-block"
                >
                  View entity →
                </Link>
              )}
            </div>
          ))}
        </div>
      </main>
      <footer className="bg-ink text-paper/50 mt-auto">
        <div className="max-w-6xl mx-auto px-6 py-8 font-mono text-xs">
          &copy; {new Date().getFullYear()} GiMtradefin
        </div>
      </footer>
    </>
  );
}
