import Link from 'next/link';
import { notFound } from 'next/navigation';
import { SiteHeader } from '@/components/SiteHeader';
import { ReviewActions } from '@/components/ReviewActions';
import { ScoreEditor } from '@/components/ScoreEditor';
import { AuditLogList } from '@/components/AuditLogList';
import { requireReviewer } from '@/lib/auth';
import { getAdminOpportunity, getOpportunityAuditLog } from '@/lib/admin-api';

export default async function AdminOpportunityDetailPage({
  params,
}: {
  params: Promise<{ id: string }>;
}) {
  await requireReviewer();
  const { id } = await params;
  const oppId = parseInt(id, 10);
  if (Number.isNaN(oppId)) notFound();

  const opportunity = await getAdminOpportunity(oppId);
  const auditLog = opportunity ? await getOpportunityAuditLog(oppId) : null;

  if (!opportunity) {
    return (
      <>
        <SiteHeader />
        <main className="max-w-2xl mx-auto px-6 py-20 text-center flex-1 w-full">
          <p className="text-manifest">Could not load this opportunity, or it doesn&apos;t exist.</p>
          <Link href="/admin/opportunities" className="inline-block mt-4 text-sm text-seal underline">
            Back to queue
          </Link>
        </main>
      </>
    );
  }

  return (
    <>
      <SiteHeader />
      <main className="max-w-3xl mx-auto px-6 py-12 flex-1 w-full">
        <Link href="/admin/opportunities" className="font-mono text-xs text-manifest hover:text-ink">
          ← Opportunities
        </Link>

        <div className="mt-4 mb-8">
          <span className="font-mono text-[10px] uppercase tracking-widest border border-line px-2 py-1 rounded-sm">
            {opportunity.status}
          </span>
          <h1 className="font-display font-bold text-2xl text-ink mt-3">{opportunity.title}</h1>
          <p className="font-mono text-sm text-manifest mt-1">
            {opportunity.country} · {opportunity.category}
            {opportunity.quantity ? ` · ${opportunity.quantity}` : ''}
            {opportunity.payment_terms ? ` · ${opportunity.payment_terms}` : ''}
          </p>
        </div>

        <section className="mb-8">
          <p className="font-mono text-[10px] tracking-widest text-manifest mb-2">DESCRIPTION</p>
          <p className="text-ink whitespace-pre-line">{opportunity.description}</p>
        </section>

        {opportunity.preferred_origin && (
          <section className="mb-8">
            <p className="font-mono text-[10px] tracking-widest text-manifest mb-2">PREFERRED ORIGIN</p>
            <p className="text-ink">{opportunity.preferred_origin}</p>
          </section>
        )}

        <section className="mb-8">
          <p className="font-mono text-[10px] tracking-widest text-manifest mb-2">
            SUBMITTER — NEVER SHOWN PUBLICLY
          </p>
          <div className="border border-line rounded-sm p-4 text-sm space-y-1">
            <p className="text-ink">
              {opportunity.submitted_by_name || '—'}
              {opportunity.submitted_by_email ? ` — ${opportunity.submitted_by_email}` : ''}
            </p>
            {opportunity.submitted_by_company && (
              <p className="text-manifest">{opportunity.submitted_by_company}</p>
            )}
            {opportunity.submitted_by_phone && <p className="text-manifest">{opportunity.submitted_by_phone}</p>}
          </div>
        </section>

        <section className="mb-8">
          <p className="font-mono text-[10px] tracking-widest text-manifest mb-2">
            EVIDENCE ({opportunity.evidence.length})
          </p>
          {opportunity.evidence.length === 0 ? (
            <p className="text-sm text-manifest italic">No evidence recorded.</p>
          ) : (
            <div className="space-y-2">
              {opportunity.evidence.map((e) => (
                <div key={e.id} className="border border-line rounded-sm p-3 text-sm">
                  <div className="flex items-center justify-between gap-3">
                    <span className="font-mono text-[10px] text-manifest uppercase">
                      {e.evidence_type || 'evidence'}
                    </span>
                    {e.discovered_at && (
                      <span className="font-mono text-[10px] text-manifest">
                        {new Date(e.discovered_at).toLocaleString()}
                      </span>
                    )}
                  </div>
                  {e.excerpt && <p className="italic text-ink/80 mt-1">&ldquo;{e.excerpt}&rdquo;</p>}
                  {e.original_url && (
                    <a
                      href={e.original_url}
                      target="_blank"
                      rel="noopener noreferrer nofollow"
                      className="text-xs text-seal underline mt-1 inline-block"
                    >
                      View source →
                    </a>
                  )}
                </div>
              ))}
            </div>
          )}
        </section>

        {opportunity.verified_by && (
          <section className="mb-8">
            <p className="font-mono text-[10px] tracking-widest text-manifest mb-2">VERIFIED BY</p>
            <p className="text-sm text-ink">
              {opportunity.verified_by.name}
              {opportunity.verified_at ? ` on ${new Date(opportunity.verified_at).toLocaleDateString()}` : ''}
            </p>
          </section>
        )}

        <ScoreEditor
          opportunityId={opportunity.id}
          initialScores={{
            commercial_intent_score: opportunity.commercial_intent_score,
            source_reliability_score: opportunity.source_reliability_score,
            evidence_strength_score: opportunity.evidence_strength_score,
            recency_score: opportunity.recency_score,
            company_confidence_score: opportunity.company_confidence_score,
            match_potential_score: opportunity.match_potential_score,
            overall_score: opportunity.overall_score,
          }}
        />

        <ReviewActions opportunityId={opportunity.id} currentStatus={opportunity.status} />

        <section className="border-t border-line pt-6 mt-8">
          <p className="font-mono text-[10px] tracking-widest text-manifest mb-3">HISTORY</p>
          <AuditLogList entries={auditLog} />
        </section>
      </main>
      <footer className="bg-ink text-paper/50 mt-auto">
        <div className="max-w-6xl mx-auto px-6 py-8 font-mono text-xs">
          &copy; {new Date().getFullYear()} GiMtradefin
        </div>
      </footer>
    </>
  );
}
