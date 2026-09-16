import Link from 'next/link';
import { notFound } from 'next/navigation';
import { getCompany, CompanyNotFoundError } from '@/lib/api';
import { VerificationStamp } from '@/components/VerificationStamp';
import { SiteHeader } from '@/components/SiteHeader';

const CATEGORY_LABELS: Record<string, string> = {
  buying: 'Buying Lead',
  selling: 'Selling Lead',
  partnership: 'Partnership Lead',
};

export default async function CompanyProfilePage({
  params,
}: {
  params: Promise<{ id: string }>;
}) {
  const { id } = await params;
  const companyId = parseInt(id, 10);

  if (Number.isNaN(companyId)) {
    notFound();
  }

  let company;
  try {
    company = await getCompany(companyId);
  } catch (err) {
    if (err instanceof CompanyNotFoundError) {
      notFound();
    }
    return (
      <>
        <SiteHeader />
        <main className="max-w-2xl mx-auto px-6 py-20 text-center flex-1 w-full">
          <p className="font-mono text-xs tracking-widest text-signal mb-3">API UNREACHABLE</p>
          <h1 className="font-display font-bold text-2xl text-ink">Could not load this company</h1>
          <Link href="/companies" className="inline-block mt-6 text-sm text-seal underline">
            Back to Directory
          </Link>
        </main>
      </>
    );
  }

  return (
    <>
      <SiteHeader />

      <main className="max-w-4xl mx-auto px-6 py-12 flex-1 w-full">
        <nav className="font-mono text-xs text-manifest mb-8">
          <Link href="/companies" className="hover:text-ink">Companies</Link>
        </nav>

        <div className="flex items-start gap-5 mb-10">
          {company.verification_status === 'verified' ? (
            <VerificationStamp />
          ) : (
            <div className="w-14 h-14 rounded-full border border-dashed border-manifest/40 shrink-0" />
          )}
          <div>
            <h1 className="font-display font-bold text-3xl text-ink leading-tight">{company.name}</h1>
            <p className="font-mono text-sm text-manifest mt-2">
              {company.country}
              {company.industry ? ` · ${company.industry}` : ''}
              {company.role ? ` · ${company.role}` : ''}
            </p>
            <p className="font-mono text-[10px] text-manifest mt-2 uppercase tracking-wide">
              Verification: <span className={company.verification_status === 'verified' ? 'text-seal' : ''}>{company.verification_status}</span>
            </p>
          </div>
        </div>

        <div className="grid md:grid-cols-3 gap-8">
          <div className="md:col-span-2 space-y-8">
            {company.description && (
              <section>
                <p className="font-mono text-[10px] tracking-widest text-manifest mb-3">ABOUT</p>
                <p className="text-ink leading-relaxed">{company.description}</p>
              </section>
            )}

            {company.products.length > 0 && (
              <section>
                <p className="font-mono text-[10px] tracking-widest text-manifest mb-3">PRODUCTS</p>
                <div className="flex flex-wrap gap-2">
                  {company.products.map((p) => (
                    <span key={p} className="font-mono text-xs border border-line rounded-sm px-2.5 py-1 text-ink">
                      {p}
                    </span>
                  ))}
                </div>
              </section>
            )}

            {company.markets.length > 0 && (
              <section>
                <p className="font-mono text-[10px] tracking-widest text-manifest mb-3">MARKETS</p>
                <div className="flex flex-wrap gap-2">
                  {company.markets.map((m) => (
                    <span key={m} className="font-mono text-xs border border-line rounded-sm px-2.5 py-1 text-ink">
                      {m}
                    </span>
                  ))}
                </div>
              </section>
            )}

            <section>
              <p className="font-mono text-[10px] tracking-widest text-manifest mb-3">
                PUBLISHED OPPORTUNITIES ({company.published_opportunities_count})
              </p>
              {company.opportunities.length === 0 ? (
                <p className="text-sm text-manifest italic">None published yet.</p>
              ) : (
                <div className="space-y-3">
                  {company.opportunities.map((opp) => (
                    <Link
                      key={opp.id}
                      href={`/opportunities/${opp.id}`}
                      className="block border border-line rounded-sm p-4 hover:border-ink transition-colors"
                    >
                      <div className="flex items-center justify-between gap-3">
                        <span className="font-mono text-[10px] tracking-widest text-manifest border border-line px-2 py-0.5 rounded-sm">
                          {CATEGORY_LABELS[opp.category]?.toUpperCase()}
                        </span>
                        {opp.overall_score !== null && (
                          <span className="font-mono text-[10px] text-seal">{opp.overall_score}/100</span>
                        )}
                      </div>
                      <p className="font-display font-medium text-ink mt-2">{opp.title}</p>
                      <p className="font-mono text-xs text-manifest mt-1">{opp.country}</p>
                    </Link>
                  ))}
                </div>
              )}
            </section>
          </div>

          <aside>
            <div className="border border-line rounded-md p-5">
              <p className="font-mono text-[10px] tracking-widest text-manifest mb-4">
                ACTIVITY BREAKDOWN
              </p>
              <div className="space-y-3 font-mono text-sm">
                <div className="flex justify-between">
                  <span className="text-manifest">Buying</span>
                  <span className="text-ink">{company.counts.buying}</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-manifest">Selling</span>
                  <span className="text-ink">{company.counts.selling}</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-manifest">Partnership</span>
                  <span className="text-ink">{company.counts.partnership}</span>
                </div>
              </div>
              {company.website && (
                <a
                  href={company.website}
                  target="_blank"
                  rel="noopener noreferrer nofollow"
                  className="block mt-5 pt-5 border-t border-line text-sm text-seal underline"
                >
                  Visit website →
                </a>
              )}
            </div>
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
