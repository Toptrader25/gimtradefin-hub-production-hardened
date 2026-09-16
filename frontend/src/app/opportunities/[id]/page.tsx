import Link from 'next/link';
import { notFound } from 'next/navigation';
import { getOpportunity, getOpportunityMatches, OpportunityNotFoundError } from '@/lib/api';
import { OpportunityCategory } from '@/types/opportunity';
import { VerificationStamp } from '@/components/VerificationStamp';
import { RequestIntroductionForm } from '@/components/RequestIntroductionForm';
import { SiteHeader } from '@/components/SiteHeader';

const CATEGORY_LABELS: Record<OpportunityCategory, string> = {
  buying: 'Buying Lead',
  selling: 'Selling Lead',
  partnership: 'Partnership Lead',
};

const SCORE_ROWS: Array<{ key: 'commercial_intent_score' | 'source_reliability_score' | 'evidence_strength_score'; label: string }> = [
  { key: 'commercial_intent_score', label: 'Commercial Intent' },
  { key: 'source_reliability_score', label: 'Source Reliability' },
  { key: 'evidence_strength_score', label: 'Evidence Strength' },
];

function ScoreBar({ value }: { value: number | null }) {
  if (value === null) {
    return <span className="font-mono text-xs text-manifest">Not yet scored</span>;
  }
  return (
    <div className="flex items-center gap-2 w-full">
      <div className="flex-1 h-1.5 bg-chart-dim rounded-full overflow-hidden">
        <div className="h-full bg-seal" style={{ width: `${value}%` }} />
      </div>
      <span className="font-mono text-xs text-ink w-8 text-right">{value}</span>
    </div>
  );
}

export default async function OpportunityDetailPage({
  params,
}: {
  params: Promise<{ id: string }>;
}) {
  const { id } = await params;
  const opportunityId = parseInt(id, 10);

  if (Number.isNaN(opportunityId)) {
    notFound();
  }

  let opportunity;
  try {
    opportunity = await getOpportunity(opportunityId);
  } catch (err) {
    if (err instanceof OpportunityNotFoundError) {
      notFound();
    }
    // API unreachable entirely — different from "doesn't exist", so a
    // distinct message rather than a bare 404.
    return (
      <>
        <SiteHeader />
        <main className="max-w-2xl mx-auto px-6 py-20 text-center flex-1 w-full">
          <p className="font-mono text-xs tracking-widest text-signal mb-3">API UNREACHABLE</p>
          <h1 className="font-display font-bold text-2xl text-ink">Could not load this opportunity</h1>
          <p className="text-manifest mt-3">The Laravel API isn&apos;t responding. Check it&apos;s deployed and running.</p>
          <Link href="/opportunities" className="inline-block mt-6 text-sm text-seal underline">
            Back to Opportunities
          </Link>
        </main>
      </>
    );
  }

  const matches = await getOpportunityMatches(opportunity.id);

  return (
    <>
      <SiteHeader />

      <main className="max-w-4xl mx-auto px-6 py-12 flex-1 w-full">
        <nav className="font-mono text-xs text-manifest mb-8">
          <Link href="/opportunities" className="hover:text-ink">Opportunities</Link>
          <span className="mx-2">/</span>
          <Link href={`/opportunities?category=${opportunity.category}`} className="hover:text-ink">
            {CATEGORY_LABELS[opportunity.category]}s
          </Link>
        </nav>

        <div className="flex items-start gap-5 mb-8">
          {opportunity.status === 'published' && <VerificationStamp />}
          <div>
            <span className="font-mono text-[10px] tracking-widest text-manifest border border-line px-2 py-1 rounded-sm">
              {CATEGORY_LABELS[opportunity.category].toUpperCase()}
            </span>
            <h1 className="font-display font-bold text-3xl text-ink mt-3 leading-tight">
              {opportunity.title}
            </h1>
            <p className="font-mono text-sm text-manifest mt-2">
              {opportunity.country}
              {opportunity.quantity ? ` · ${opportunity.quantity}` : ''}
              {opportunity.payment_terms ? ` · ${opportunity.payment_terms}` : ''}
            </p>
          </div>
        </div>

        <div className="grid md:grid-cols-3 gap-8">
          <div className="md:col-span-2 space-y-8">
            <section>
              <p className="font-mono text-[10px] tracking-widest text-manifest mb-3">DESCRIPTION</p>
              <p className="text-ink leading-relaxed whitespace-pre-line">{opportunity.description}</p>
            </section>

            {opportunity.preferred_origin && (
              <section>
                <p className="font-mono text-[10px] tracking-widest text-manifest mb-2">PREFERRED ORIGIN / MARKET</p>
                <p className="text-ink">{opportunity.preferred_origin}</p>
              </section>
            )}

            <section>
              <p className="font-mono text-[10px] tracking-widest text-manifest mb-3">
                EVIDENCE TRAIL — WHERE THIS CAME FROM
              </p>
              {opportunity.evidence.length === 0 ? (
                <p className="text-sm text-manifest italic">
                  No evidence recorded yet for this opportunity.
                </p>
              ) : (
                <ul className="space-y-3">
                  {opportunity.evidence.map((e, i) => (
                    <li key={i} className="border border-line rounded-sm p-4">
                      <div className="flex items-center justify-between gap-3 mb-2">
                        <span className="font-mono text-[10px] text-manifest uppercase tracking-wide">
                          {e.evidence_type || 'evidence'}
                        </span>
                        {e.discovered_at && (
                          <span className="font-mono text-[10px] text-manifest">
                            {new Date(e.discovered_at).toLocaleDateString()}
                          </span>
                        )}
                      </div>
                      {e.excerpt && <p className="text-sm text-ink/80 italic">&ldquo;{e.excerpt}&rdquo;</p>}
                      {e.original_url && (
                        <a
                          href={e.original_url}
                          target="_blank"
                          rel="noopener noreferrer nofollow"
                          className="text-xs text-seal underline mt-2 inline-block"
                        >
                          View original source →
                        </a>
                      )}
                    </li>
                  ))}
                </ul>
              )}
            </section>

            {opportunity.company && (
              <section>
                <p className="font-mono text-[10px] tracking-widest text-manifest mb-3">LISTED BY</p>
                <Link
                  href={`/companies/${opportunity.company.id}`}
                  className="block border border-line rounded-sm p-4 hover:border-ink transition-colors"
                >
                  <p className="font-display font-semibold text-ink">{opportunity.company.name}</p>
                  <p className="text-sm text-manifest mt-1">
                    {opportunity.company.country}
                    {opportunity.company.industry ? ` · ${opportunity.company.industry}` : ''}
                    {opportunity.company.role ? ` · ${opportunity.company.role}` : ''}
                  </p>
                  <p className="font-mono text-[10px] text-manifest mt-2">
                    VERIFICATION: {opportunity.company.verification_status.toUpperCase()}
                  </p>
                </Link>
              </section>
            )}

            {matches.length > 0 && (
              <section>
                <p className="font-mono text-[10px] tracking-widest text-manifest mb-1">SUGGESTED MATCHES</p>
                <p className="text-xs text-manifest mb-3">
                  Rule-based, not AI — same category opposite (buying↔selling) or partnership, same or similar country.
                </p>
                <div className="space-y-2">
                  {matches.map((m) => (
                    <Link
                      key={m.id}
                      href={`/opportunities/${m.id}`}
                      className="flex items-center justify-between gap-3 border border-line rounded-sm p-3 hover:border-ink transition-colors"
                    >
                      <div className="min-w-0">
                        <p className="text-sm text-ink truncate">{m.title}</p>
                        <p className="font-mono text-[10px] text-manifest mt-0.5">
                          {m.country}
                          {m.company ? ` · ${m.company.name}` : ''}
                        </p>
                      </div>
                      {m.same_country && (
                        <span className="font-mono text-[9px] text-seal shrink-0">SAME COUNTRY</span>
                      )}
                    </Link>
                  ))}
                </div>
              </section>
            )}
          </div>

          <aside className="space-y-6">
            <div className="border border-line rounded-md p-5">
              <p className="font-mono text-[10px] tracking-widest text-manifest mb-4">OPPORTUNITY SCORE</p>
              <div className="space-y-3">
                {SCORE_ROWS.map((row) => (
                  <div key={row.key}>
                    <p className="text-xs text-ink mb-1">{row.label}</p>
                    <ScoreBar value={opportunity[row.key]} />
                  </div>
                ))}
              </div>
              {opportunity.overall_score !== null && (
                <div className="mt-4 pt-4 border-t border-line flex items-center justify-between">
                  <span className="font-mono text-xs text-manifest">OVERALL</span>
                  <span className="font-mono text-lg text-seal font-medium">
                    {opportunity.overall_score}/100
                  </span>
                </div>
              )}
            </div>

            <RequestIntroductionForm opportunityId={opportunity.id} />
          </aside>
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
