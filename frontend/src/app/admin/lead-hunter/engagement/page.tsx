import Link from 'next/link';
import { SiteHeader } from '@/components/SiteHeader';
import { requireReviewer } from '@/lib/auth';
import { getEngagementOpportunities } from '@/lib/engagement-api';

const STAGES = ['approved', 'nurture', 'contacted', 'engaged', 'qualified', 'meeting', 'rfq', 'won', 'lost'];

export default async function EngagementPage({
  searchParams,
}: {
  searchParams: Promise<{ stage?: string; page?: string }>;
}) {
  await requireReviewer();
  const params = await searchParams;
  const page = params.page ? parseInt(params.page, 10) : 1;
  const result = await getEngagementOpportunities({ stage: params.stage, page });

  return (
    <>
      <SiteHeader />
      <main className="max-w-5xl mx-auto px-6 py-12 flex-1 w-full">
        <Link href="/admin/lead-hunter" className="font-mono text-xs text-manifest hover:text-ink">
          ← Lead Hunter
        </Link>
        <h1 className="font-display font-bold text-3xl text-ink mt-3 mb-2">Engagement</h1>
        <p className="text-sm text-manifest mb-8 max-w-lg">
          After verification: tracking outreach on a discovered opportunity through to a real
          commercial outcome. Start one from any entity&apos;s page.
        </p>

        <div className="flex gap-2 mb-8 flex-wrap">
          <Link
            href="/admin/lead-hunter/engagement"
            className={`px-3 py-1.5 rounded-sm border text-xs font-mono ${
              !params.stage ? 'bg-ink text-paper border-ink' : 'border-line text-manifest hover:border-ink'
            }`}
          >
            ALL
          </Link>
          {STAGES.map((s) => (
            <Link
              key={s}
              href={`/admin/lead-hunter/engagement?stage=${s}`}
              className={`px-3 py-1.5 rounded-sm border text-xs font-mono uppercase ${
                params.stage === s ? 'bg-ink text-paper border-ink' : 'border-line text-manifest hover:border-ink'
              }`}
            >
              {s}
            </Link>
          ))}
        </div>

        {!result && (
          <div className="border border-signal bg-signal-dim rounded-sm p-4 text-sm text-ink font-mono">
            Could not reach the admin API.
          </div>
        )}

        {result && result.data.length === 0 && (
          <div className="border border-line bg-paper rounded-sm p-10 text-center text-manifest">
            Nothing here yet.
          </div>
        )}

        <div className="space-y-3">
          {result?.data.map((o) => (
            <Link
              key={o.id}
              href={`/admin/lead-hunter/engagement/${o.id}`}
              className="flex items-center justify-between gap-4 border border-line bg-paper rounded-md p-4 hover:border-ink transition-colors"
            >
              <div>
                <span className="font-mono text-[10px] uppercase tracking-widest text-manifest border border-line px-2 py-0.5 rounded-sm mr-2">
                  {o.stage}
                </span>
                <span className="font-mono text-[10px] text-manifest">{o.status}</span>
                <p className="font-display font-medium text-ink mt-1">{o.title}</p>
                {o.owner && <p className="font-mono text-xs text-manifest mt-1">Owner: {o.owner}</p>}
              </div>
              <span className="font-mono text-sm text-seal shrink-0">{o.priority}</span>
            </Link>
          ))}
        </div>

        {result && result.meta.last_page > 1 && (
          <div className="flex justify-center gap-2 mt-10 font-mono text-sm">
            {Array.from({ length: result.meta.last_page }, (_, i) => i + 1).map((p) => (
              <Link
                key={p}
                href={`/admin/lead-hunter/engagement?page=${p}${params.stage ? `&stage=${params.stage}` : ''}`}
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
