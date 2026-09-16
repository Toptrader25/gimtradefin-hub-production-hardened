import Link from 'next/link';
import { SiteHeader } from '@/components/SiteHeader';
import { requireReviewer } from '@/lib/auth';
import { getCandidates, getVerificationCases } from '@/lib/lead-hunter-api';

export default async function LeadHunterOverviewPage() {
  await requireReviewer();
  const [candidates, queuedCases] = await Promise.all([
    getCandidates(1),
    getVerificationCases({ status: 'queued' }),
  ]);

  return (
    <>
      <SiteHeader />
      <main className="max-w-5xl mx-auto px-6 py-12 flex-1 w-full">
        <Link href="/admin" className="font-mono text-xs text-manifest hover:text-ink">
          ← Dashboard
        </Link>
        <h1 className="font-display font-bold text-3xl text-ink mt-3 mb-2">Lead Hunter</h1>
        <p className="text-sm text-manifest mb-8 max-w-lg">
          The discovery pipeline: Source → Candidate → Entity → Verification Case → Publication
          Decision. Nothing here becomes a public opportunity without a human decision on a
          verification case.
        </p>

        <div className="grid sm:grid-cols-2 gap-4 mb-10">
          <Link
            href="/admin/lead-hunter/candidates"
            className="border border-line bg-paper rounded-md p-5 hover:border-ink transition-colors"
          >
            <div className="font-mono text-3xl text-ink">{candidates?.meta.total ?? '—'}</div>
            <div className="text-sm text-manifest mt-1">Discovered candidates</div>
          </Link>
          <Link
            href="/admin/lead-hunter/cases?status=queued"
            className="border border-line bg-paper rounded-md p-5 hover:border-ink transition-colors"
          >
            <div className="font-mono text-3xl text-seal">{queuedCases?.meta.total ?? '—'}</div>
            <div className="text-sm text-manifest mt-1">Cases queued for review</div>
          </Link>
        </div>

        <div className="flex flex-wrap gap-6">
          <Link href="/admin/lead-hunter/candidates" className="text-sm text-seal underline">
            Browse candidates →
          </Link>
          <Link href="/admin/lead-hunter/entities" className="text-sm text-seal underline">
            Resolved entities →
          </Link>
          <Link href="/admin/lead-hunter/cases" className="text-sm text-seal underline">
            All verification cases →
          </Link>
          <Link href="/admin/lead-hunter/risk-reviews" className="text-sm text-seal underline">
            Risk reviews →
          </Link>
          <Link href="/admin/lead-hunter/engagement" className="text-sm text-seal underline">
            Engagement →
          </Link>
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
