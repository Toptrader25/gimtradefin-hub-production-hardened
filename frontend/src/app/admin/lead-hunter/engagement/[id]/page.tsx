import Link from 'next/link';
import { SiteHeader } from '@/components/SiteHeader';
import { EngagementActionPanel } from '@/components/EngagementActionPanel';
import { requireReviewer } from '@/lib/auth';
import { getEngagementSnapshot } from '@/lib/engagement-api';

export default async function EngagementDetailPage({
  params,
}: {
  params: Promise<{ id: string }>;
}) {
  await requireReviewer();
  const { id } = await params;
  const snapshot = await getEngagementSnapshot(id);

  if (!snapshot) {
    return (
      <>
        <SiteHeader />
        <main className="max-w-2xl mx-auto px-6 py-20 text-center flex-1 w-full">
          <p className="text-manifest">Could not load this engagement, or it doesn&apos;t exist.</p>
          <Link href="/admin/lead-hunter/engagement" className="inline-block mt-4 text-sm text-seal underline">
            Back to engagement
          </Link>
        </main>
      </>
    );
  }

  const { opportunity, contacts, activities, tasks, messages, outcomes, next_best_actions } = snapshot;

  return (
    <>
      <SiteHeader />
      <main className="max-w-3xl mx-auto px-6 py-12 flex-1 w-full">
        <Link href="/admin/lead-hunter/engagement" className="font-mono text-xs text-manifest hover:text-ink">
          ← Engagement
        </Link>

        <div className="mt-4 mb-8">
          <span className="font-mono text-[10px] uppercase tracking-widest border border-line px-2 py-1 rounded-sm mr-2">
            {opportunity.stage}
          </span>
          <span className="font-mono text-[10px] text-manifest">{opportunity.status}</span>
          <h1 className="font-display font-bold text-2xl text-ink mt-3">{opportunity.title}</h1>
          {opportunity.owner && <p className="text-sm text-manifest mt-1">Owner: {opportunity.owner}</p>}
        </div>

        {next_best_actions.length > 0 && (
          <section className="mb-8 border border-seal bg-seal-dim rounded-sm p-4">
            <p className="font-mono text-[10px] tracking-widest text-seal mb-2">NEXT BEST ACTIONS</p>
            <ul className="text-sm text-ink space-y-1 list-disc list-inside">
              {next_best_actions.map((a, i) => (
                <li key={i}>{a}</li>
              ))}
            </ul>
          </section>
        )}

        <div className="grid sm:grid-cols-2 gap-6 mb-8">
          <section>
            <p className="font-mono text-[10px] tracking-widest text-manifest mb-2">
              CONTACTS ({contacts.length})
            </p>
            {contacts.length === 0 ? (
              <p className="text-sm text-manifest italic">None yet.</p>
            ) : (
              <div className="space-y-1">
                {contacts.map((c) => (
                  <div key={c.id} className="border border-line rounded-sm p-2 text-sm">
                    <p className="text-ink">{c.name}</p>
                    <p className="font-mono text-[10px] text-manifest">
                      {c.role || 'No role'} · {c.verified ? 'Verified' : 'Unverified'}
                    </p>
                  </div>
                ))}
              </div>
            )}
          </section>

          <section>
            <p className="font-mono text-[10px] tracking-widest text-manifest mb-2">
              OPEN TASKS ({tasks.filter((t) => t.status === 'open').length})
            </p>
            {tasks.length === 0 ? (
              <p className="text-sm text-manifest italic">None.</p>
            ) : (
              <div className="space-y-1">
                {tasks.map((t) => (
                  <div key={t.id} className="border border-line rounded-sm p-2 text-sm">
                    <p className="text-ink">{t.title}</p>
                    <p className="font-mono text-[10px] text-manifest">
                      {t.status} {t.assignee ? `· ${t.assignee}` : ''}
                    </p>
                  </div>
                ))}
              </div>
            )}
          </section>
        </div>

        <section className="mb-8">
          <p className="font-mono text-[10px] tracking-widest text-manifest mb-2">
            ACTIVITY LOG ({activities.length})
          </p>
          {activities.length === 0 ? (
            <p className="text-sm text-manifest italic">Nothing logged yet.</p>
          ) : (
            <div className="space-y-2">
              {activities.slice(0, 10).map((a) => (
                <div key={a.id} className="border border-line rounded-sm p-3 text-sm">
                  <div className="flex items-center justify-between">
                    <span className="font-mono text-[10px] uppercase text-manifest">{a.type}</span>
                    <span className="font-mono text-[10px] text-manifest">
                      {new Date(a.occurred_at).toLocaleString()}
                    </span>
                  </div>
                  {a.subject && <p className="text-ink mt-1">{a.subject}</p>}
                  {a.body && <p className="text-ink/70 mt-1">{a.body}</p>}
                  <p className="font-mono text-[10px] text-manifest mt-1">by {a.actor}</p>
                </div>
              ))}
            </div>
          )}
        </section>

        {messages.length > 0 && (
          <section className="mb-8">
            <p className="font-mono text-[10px] tracking-widest text-manifest mb-2">MESSAGES ({messages.length})</p>
            <div className="space-y-2">
              {messages.slice(0, 5).map((m) => (
                <div key={m.id} className="border border-line rounded-sm p-3 text-sm">
                  <span className="font-mono text-[10px] uppercase text-manifest">
                    {m.channel} · {m.status}
                  </span>
                  <p className="text-ink mt-1">{m.body}</p>
                </div>
              ))}
            </div>
          </section>
        )}

        {outcomes.length > 0 && (
          <section className="mb-8">
            <p className="font-mono text-[10px] tracking-widest text-manifest mb-2">OUTCOMES ({outcomes.length})</p>
            <div className="space-y-2">
              {outcomes.map((o) => (
                <div key={o.id} className="border border-line rounded-sm p-3 text-sm">
                  <span className="font-mono text-[10px] uppercase text-seal">{o.outcome}</span>
                  {o.score !== null && <span className="font-mono text-[10px] text-manifest ml-2">Score: {o.score}</span>}
                  {o.notes && <p className="text-ink/80 mt-1">{o.notes}</p>}
                </div>
              ))}
            </div>
          </section>
        )}

        <EngagementActionPanel opportunityId={opportunity.id} />
      </main>
      <footer className="bg-ink text-paper/50 mt-auto">
        <div className="max-w-6xl mx-auto px-6 py-8 font-mono text-xs">
          &copy; {new Date().getFullYear()} GiMtradefin
        </div>
      </footer>
    </>
  );
}
