import Link from 'next/link';
import { SiteHeader } from '@/components/SiteHeader';
import { requireUser } from '@/lib/auth';
import { getMyOpportunities, getMyEnquiries } from '@/lib/admin-api';

const STATUS_LABEL: Record<string, string> = {
  discovered: 'Submitted — awaiting review',
  reviewing: 'In review',
  verified: 'Verified — pending publish',
  published: 'Published (live)',
  expired: 'Expired',
  rejected: 'Not accepted',
  test: 'Test record',
};

const STATUS_COLOR: Record<string, string> = {
  discovered: 'text-manifest',
  reviewing: 'text-signal',
  verified: 'text-seal',
  published: 'text-seal',
  expired: 'text-manifest',
  rejected: 'text-[#A63D3D]',
  test: 'text-manifest',
};

export default async function DashboardPage() {
  const user = await requireUser();
  const [opportunities, enquiries] = await Promise.all([getMyOpportunities(), getMyEnquiries()]);

  return (
    <>
      <SiteHeader />
      <main className="max-w-3xl mx-auto px-6 py-12 flex-1 w-full">
        <p className="font-mono text-xs tracking-[0.2em] text-seal font-medium mb-3">MY DASHBOARD</p>
        <div className="flex items-center justify-between mb-1">
          <h1 className="font-display font-bold text-3xl text-ink">Welcome back, {user.name}.</h1>
          <Link href="/dashboard/settings" className="text-xs text-seal underline shrink-0">
            Account settings
          </Link>
        </div>
        <p className="text-manifest mb-10">Track what you&apos;ve submitted and where it stands.</p>

        <section className="mb-12">
          <div className="flex items-center justify-between mb-4">
            <p className="font-mono text-[10px] tracking-widest text-manifest">MY SUBMITTED OPPORTUNITIES</p>
            <Link href="/submit" className="text-xs text-seal underline">
              + Submit another
            </Link>
          </div>

          {opportunities === null && (
            <div className="border border-signal bg-signal-dim rounded-sm p-4 text-sm text-ink font-mono">
              Could not reach the API.
            </div>
          )}

          {opportunities && opportunities.length === 0 && (
            <div className="border border-line bg-paper rounded-sm p-8 text-center text-manifest text-sm">
              You haven&apos;t submitted anything yet.{' '}
              <Link href="/submit" className="text-seal underline">
                Submit your first opportunity →
              </Link>
            </div>
          )}

          <div className="space-y-3">
            {opportunities?.map((o) => (
              <div key={o.id} className="border border-line bg-paper rounded-md p-4">
                <div className="flex items-center justify-between gap-3">
                  <p className="font-display font-medium text-ink">{o.title}</p>
                  {o.status === 'published' ? (
                    <Link href={`/opportunities/${o.id}`} className="text-xs text-seal underline shrink-0">
                      View live →
                    </Link>
                  ) : null}
                </div>
                <p className="font-mono text-xs text-manifest mt-1">
                  {o.country} · {o.category}
                </p>
                <p className={`font-mono text-xs mt-2 ${STATUS_COLOR[o.status] || 'text-manifest'}`}>
                  {STATUS_LABEL[o.status] || o.status.toUpperCase()}
                </p>
              </div>
            ))}
          </div>
        </section>

        <section>
          <p className="font-mono text-[10px] tracking-widest text-manifest mb-4">
            MY INTRODUCTION REQUESTS
          </p>

          {enquiries === null && (
            <div className="border border-signal bg-signal-dim rounded-sm p-4 text-sm text-ink font-mono">
              Could not reach the API.
            </div>
          )}

          {enquiries && enquiries.length === 0 && (
            <div className="border border-line bg-paper rounded-sm p-8 text-center text-manifest text-sm">
              No introduction requests yet.
            </div>
          )}

          <div className="space-y-3">
            {enquiries?.map((e) => (
              <div key={e.id} className="border border-line bg-paper rounded-md p-4">
                {e.opportunity ? (
                  <Link
                    href={`/opportunities/${e.opportunity.id}`}
                    className="font-display font-medium text-ink hover:text-seal transition-colors"
                  >
                    {e.opportunity.title}
                  </Link>
                ) : (
                  <p className="font-display font-medium text-ink">General enquiry</p>
                )}
                <p className="font-mono text-xs text-manifest mt-1 uppercase">
                  {e.type.replace('_', ' ')} · {e.status}
                </p>
              </div>
            ))}
          </div>
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
