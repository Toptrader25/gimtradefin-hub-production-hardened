import Link from 'next/link';
import { SiteHeader } from '@/components/SiteHeader';
import { CaseChecklist } from '@/components/CaseChecklist';
import { CaseDecisionPanel } from '@/components/CaseDecisionPanel';
import { StartCaseButton } from '@/components/StartCaseButton';
import { requireReviewer } from '@/lib/auth';
import { getVerificationCase } from '@/lib/lead-hunter-api';

export default async function VerificationCaseDetailPage({
  params,
}: {
  params: Promise<{ id: string }>;
}) {
  await requireReviewer();
  const { id } = await params;

  const snapshot = await getVerificationCase(id);

  if (!snapshot) {
    return (
      <>
        <SiteHeader />
        <main className="max-w-2xl mx-auto px-6 py-20 text-center flex-1 w-full">
          <p className="text-manifest">Could not load this case, or it doesn&apos;t exist.</p>
          <Link href="/admin/lead-hunter/cases" className="inline-block mt-4 text-sm text-seal underline">
            Back to cases
          </Link>
        </main>
      </>
    );
  }

  const { case: c, checks, qualification, gates, next_actions } = snapshot;

  return (
    <>
      <SiteHeader />
      <main className="max-w-3xl mx-auto px-6 py-12 flex-1 w-full">
        <Link href="/admin/lead-hunter/cases" className="font-mono text-xs text-manifest hover:text-ink">
          ← Verification Cases
        </Link>

        <div className="flex items-start justify-between gap-4 mt-4 mb-8">
          <div>
            <span className="font-mono text-[10px] uppercase tracking-widest border border-line px-2 py-1 rounded-sm mr-2">
              {c.status}
            </span>
            <span className="font-mono text-[10px] uppercase text-signal">{c.priority} priority</span>
            <h1 className="font-display font-bold text-2xl text-ink mt-3">{c.case_type} verification</h1>
            <p className="font-mono text-xs text-manifest mt-1">
              Verification {c.verification_score}/100 · Qualification {c.qualification_score}/100 · Decision: {c.decision}
            </p>
          </div>
          {c.status === 'queued' && <StartCaseButton caseId={c.id} />}
        </div>

        {next_actions.length > 0 && (
          <section className="mb-8 border border-line rounded-sm p-4">
            <p className="font-mono text-[10px] tracking-widest text-manifest mb-2">NEXT ACTIONS</p>
            <ul className="text-sm text-ink space-y-1 list-disc list-inside">
              {next_actions.map((a, i) => (
                <li key={i}>{a}</li>
              ))}
            </ul>
          </section>
        )}

        <section className="mb-8">
          <p className="font-mono text-[10px] tracking-widest text-manifest mb-3">CHECKLIST</p>
          <CaseChecklist caseId={c.id} checks={checks} />
        </section>

        {qualification.length > 0 && (
          <section className="mb-8">
            <p className="font-mono text-[10px] tracking-widest text-manifest mb-3">QUALIFICATION</p>
            <div className="space-y-2">
              {qualification.map((q, i) => (
                <div key={i} className="border border-line rounded-sm p-3 text-sm">
                  <span className="font-mono text-xs text-manifest">{q.question_code}</span>
                  <p className="text-ink mt-1">{q.answer}</p>
                </div>
              ))}
            </div>
          </section>
        )}

        <CaseDecisionPanel caseId={c.id} gates={gates} />
      </main>
      <footer className="bg-ink text-paper/50 mt-auto">
        <div className="max-w-6xl mx-auto px-6 py-8 font-mono text-xs">
          &copy; {new Date().getFullYear()} GiMtradefin
        </div>
      </footer>
    </>
  );
}
