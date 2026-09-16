import Link from 'next/link';
import { EvidencePipeline } from '@/components/EvidencePipeline';
import { VerificationStamp } from '@/components/VerificationStamp';
import { SiteHeader } from '@/components/SiteHeader';
import { SiteFooter } from '@/components/SiteFooter';
import { EmptyState } from '@/components/EmptyState';
import { getStats, getOpportunities } from '@/lib/api';
import { OpportunityCategory } from '@/types/opportunity';

const CATEGORY_META: Record<OpportunityCategory, { label: string; detail: string }> = {
  buying: {
    label: 'Buying Leads',
    detail: 'Companies sourcing product, raw materials, or equipment',
  },
  selling: {
    label: 'Selling Leads',
    detail: 'Verified supply — exports, surplus, manufactured goods',
  },
  partnership: {
    label: 'Partnerships',
    detail: 'Distributors, joint ventures, agents, investors',
  },
};

const PREVIEW_CATEGORY_LABEL: Record<OpportunityCategory, string> = {
  buying: 'Buying Lead',
  selling: 'Selling Lead',
  partnership: 'Partnership Lead',
};

const PIPELINE_STEPS = [
  {
    n: '1',
    title: 'A signal is discovered',
    body: 'A connector reads a source — a buyer request, a distributor search, a trade announcement — and records it with its original URL and timestamp.',
  },
  {
    n: '2',
    title: 'It is scored, not assumed',
    body: 'Source reliability, evidence strength, recency, and commercial intent are weighted into a single score. Nothing is published on a guess.',
  },
  {
    n: '3',
    title: 'AI analyzes the evidence',
    body: "The system extracts product, country, and industry from what was actually found — and marks anything it can't confirm as Unknown, never invented.",
  },
  {
    n: '4',
    title: 'A person verifies it',
    body: 'AI never marks an opportunity verified. A GiMtradefin reviewer checks the evidence and makes the call — approve, reject, or request more.',
  },
];

export default async function Home() {
  const [stats, preview] = await Promise.all([
    getStats(),
    getOpportunities({ page: 1 }).catch(() => null),
  ]);
  const previewItems = preview?.data.slice(0, 3) ?? [];

  return (
    <>
      <SiteHeader />

      <main>
        {/* HERO */}
        <section className="max-w-6xl mx-auto px-4 sm:px-6 pt-12 sm:pt-16 pb-12 sm:pb-14">
          <p className="font-mono text-xs tracking-[0.2em] text-seal font-medium mb-5">
            EVIDENCE-GROUNDED TRADE INTELLIGENCE
          </p>
          <h1 className="font-display font-bold text-4xl md:text-6xl text-ink leading-[1.05] max-w-3xl">
            Every opportunity,
            <br />
            traced to its source.
          </h1>
          <p className="text-manifest text-lg max-w-xl mt-6 leading-relaxed">
            GiMtradefin doesn&apos;t just list trade leads. Every buying, selling, and
            partnership opportunity here carries its evidence — where it came from,
            what was verified, and by whom.
          </p>

          <div className="mt-10 border border-line bg-paper rounded-md p-5 max-w-2xl">
            <p className="font-mono text-[10px] tracking-widest text-manifest mb-3">
              HOW AN OPPORTUNITY REACHES YOU
            </p>
            <EvidencePipeline />
          </div>

          <form action="/opportunities" className="mt-8 flex max-w-xl border border-ink rounded-sm overflow-hidden">
            <input
              type="text"
              name="search"
              placeholder="What are you looking to buy, sell, or source a partner for?"
              className="flex-1 px-4 py-3 text-sm outline-none bg-paper placeholder:text-manifest/70"
            />
            <button className="bg-ink text-paper px-5 text-sm font-medium hover:bg-ink/90 transition-colors">
              Search
            </button>
          </form>

          {stats && (
            <div className="flex flex-wrap gap-8 sm:gap-10 mt-12 font-mono">
              <div>
                <div className="text-2xl text-ink font-medium">{stats.published_opportunities}</div>
                <div className="text-[11px] text-manifest tracking-wide mt-0.5">PUBLISHED OPPORTUNITIES</div>
              </div>
              <div>
                <div className="text-2xl text-ink font-medium">{stats.countries}</div>
                <div className="text-[11px] text-manifest tracking-wide mt-0.5">COUNTRIES</div>
              </div>
              <div>
                <div className="text-2xl text-seal font-medium">{stats.verified_companies}</div>
                <div className="text-[11px] text-manifest tracking-wide mt-0.5">VERIFIED COMPANIES</div>
              </div>
            </div>
          )}
        </section>

        {/* CATEGORIES */}
        <section className="border-t border-line bg-chart-dim/40">
          <div className="max-w-6xl mx-auto px-6 py-16">
            <div className="grid md:grid-cols-3 gap-px bg-line border border-line rounded-md overflow-hidden">
              {(Object.keys(CATEGORY_META) as OpportunityCategory[]).map((slug) => (
                <Link
                  key={slug}
                  href={`/opportunities?category=${slug}`}
                  className="bg-paper p-6 hover:bg-chart transition-colors group"
                >
                  {stats && (
                    <div className="font-mono text-[10px] text-manifest tracking-widest mb-3">
                      {stats.by_category[slug]} LIVE
                    </div>
                  )}
                  <h3 className="font-display font-semibold text-lg text-ink group-hover:text-seal transition-colors">
                    {CATEGORY_META[slug].label}
                  </h3>
                  <p className="text-sm text-manifest mt-2 leading-relaxed">{CATEGORY_META[slug].detail}</p>
                </Link>
              ))}
            </div>
          </div>
        </section>

        {/* RECENTLY PUBLISHED */}
        {previewItems.length > 0 && (
          <section className="max-w-6xl mx-auto px-6 py-16">
            <div className="flex items-center justify-between mb-8">
              <div>
                <p className="font-mono text-[10px] tracking-widest text-seal font-medium mb-2">
                  LIVE FEED
                </p>
                <h2 className="font-display font-bold text-2xl text-ink">Recently Published</h2>
              </div>
              <Link href="/opportunities" className="text-sm text-seal underline shrink-0">
                View all →
              </Link>
            </div>

            <div className="grid md:grid-cols-3 gap-4">
              {previewItems.map((opp) => (
                <Link
                  key={opp.id}
                  href={`/opportunities/${opp.id}`}
                  className="border border-line bg-paper rounded-md p-5 hover:border-ink transition-colors"
                >
                  <div className="flex items-start justify-between gap-2 mb-3">
                    <span className="font-mono text-[10px] tracking-widest text-manifest border border-line px-2 py-0.5 rounded-sm">
                      {PREVIEW_CATEGORY_LABEL[opp.category].toUpperCase()}
                    </span>
                    <VerificationStamp size="sm" />
                  </div>
                  <h3 className="font-display font-semibold text-ink leading-snug">{opp.title}</h3>
                  <p className="font-mono text-xs text-manifest mt-2">
                    {opp.country}
                    {opp.quantity ? ` · ${opp.quantity}` : ''}
                  </p>
                  <p className="text-sm text-ink/70 mt-2 line-clamp-2">{opp.description}</p>
                </Link>
              ))}
            </div>
          </section>
        )}

        {/* LIVE FEED — empty / unavailable */}
        {previewItems.length === 0 && (
          <section className="max-w-6xl mx-auto px-6 py-16">
            <p className="font-mono text-[10px] tracking-widest text-seal font-medium mb-2">
              LIVE FEED
            </p>
            <h2 className="font-display font-bold text-2xl text-ink mb-6">Recently Published</h2>
            <EmptyState
              title={preview === null ? 'Marketplace data is warming up' : 'No published opportunities yet'}
              body={
                preview === null
                  ? 'The API could not be reached just now. Browse again shortly, or submit a real lead for review while we reconnect.'
                  : 'Once a reviewer publishes a verified opportunity, it appears here with its evidence trail. You can submit a lead anytime.'
              }
              primaryHref="/submit"
              primaryLabel="Submit an Opportunity"
              secondaryHref="/login"
              secondaryLabel="Sign in"
            />
          </section>
        )}

        {/* HOW VERIFICATION WORKS */}
        <section className="max-w-6xl mx-auto px-6 py-16">
          <h2 className="font-display font-bold text-2xl md:text-3xl text-ink max-w-md">
            AI analyzes. A person verifies.
          </h2>
          <p className="text-manifest mt-3 max-w-lg leading-relaxed">
            This is the one rule the platform never breaks: AI does not invent missing
            information, and AI does not verify a lead.
          </p>

          <div className="grid md:grid-cols-4 gap-6 mt-10">
            {PIPELINE_STEPS.map((step) => (
              <div key={step.n} className="border-t-2 border-ink pt-4">
                <span className="font-mono text-xs text-manifest">{step.n}</span>
                <h3 className="font-display font-semibold text-ink mt-2">{step.title}</h3>
                <p className="text-sm text-manifest mt-2 leading-relaxed">{step.body}</p>
              </div>
            ))}
          </div>

          <div className="mt-10 border border-line bg-paper rounded-md p-6 flex items-center gap-5 max-w-xl">
            <VerificationStamp />
            <p className="text-sm text-manifest leading-relaxed">
              Every opportunity marked <span className="text-seal font-medium">Verified</span> has
              been individually reviewed by a GiMtradefin analyst against its original
              source evidence — not approved automatically.
            </p>
          </div>
        </section>

        {/* CTA */}
        <section className="border-t border-line bg-chart-dim/40">
          <div className="max-w-6xl mx-auto px-6 py-16 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div>
              <h2 className="font-display font-bold text-2xl text-ink">
                Have a real opportunity to list?
              </h2>
              <p className="text-manifest mt-2">
                Submissions are reviewed before they&apos;re published — see the pipeline above.
              </p>
            </div>
            <Link
              href="/submit"
              className="shrink-0 bg-ink text-paper px-6 py-3 rounded-sm text-sm font-medium hover:bg-ink/90 transition-colors text-center"
            >
              Submit an Opportunity
            </Link>
          </div>
        </section>
      </main>

      <SiteFooter />
    </>
  );
}
