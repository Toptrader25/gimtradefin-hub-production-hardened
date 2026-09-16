import Link from 'next/link';
import { getCompanies } from '@/lib/api';
import { VerificationStamp } from '@/components/VerificationStamp';
import { SiteHeader } from '@/components/SiteHeader';
import { SiteFooter } from '@/components/SiteFooter';
import { EmptyState } from '@/components/EmptyState';

export default async function CompaniesPage({
  searchParams,
}: {
  searchParams: Promise<{ search?: string; country?: string; page?: string }>;
}) {
  const params = await searchParams;
  const page = params.page ? parseInt(params.page, 10) : 1;

  let result;
  let apiError: string | null = null;
  try {
    result = await getCompanies({ search: params.search, country: params.country, page });
  } catch {
    apiError = 'Could not reach the companies API. The marketplace backend may be redeploying — try again shortly.';
  }

  return (
    <>
      <SiteHeader />

      <main className="max-w-5xl mx-auto px-4 sm:px-6 py-10 sm:py-12 flex-1 w-full">
        <p className="font-mono text-xs tracking-[0.2em] text-seal font-medium mb-3">
          COMPANY INTELLIGENCE
        </p>
        <h1 className="font-display font-bold text-3xl text-ink mb-3">Company Directory</h1>
        <p className="text-manifest max-w-lg mb-8 leading-relaxed">
          Every company here has at least one opportunity that&apos;s been published — this
          isn&apos;t a self-reported listing page. The green stamp means independently verified;
          without it, the company is real but not yet reviewed.
        </p>

        <form action="/companies" className="flex flex-wrap gap-2 mb-10 max-w-xl">
          <input
            name="search"
            defaultValue={params.search}
            placeholder="Search company name…"
            className="flex-1 min-w-[200px] border border-line rounded-sm px-3 py-2 text-sm outline-none focus:border-ink bg-paper"
          />
          <input
            name="country"
            defaultValue={params.country}
            placeholder="Country"
            className="w-40 border border-line rounded-sm px-3 py-2 text-sm outline-none focus:border-ink bg-paper"
          />
          <button className="bg-ink text-paper px-4 py-2 rounded-sm text-sm font-medium hover:bg-ink/90 transition-colors">
            Search
          </button>
          {(params.search || params.country) && (
            <Link
              href="/companies"
              className="px-4 py-2 text-sm text-manifest hover:text-ink transition-colors"
            >
              Clear
            </Link>
          )}
        </form>

        {apiError && (
          <div className="mb-8">
            <div className="rounded-sm border border-signal bg-signal-dim p-4 text-ink text-sm font-mono mb-4">
              {apiError}
            </div>
            <EmptyState
              title="Directory temporarily unavailable"
              body="Company profiles load from the same API as opportunities. Try again shortly, or submit a lead while we reconnect."
              primaryHref="/submit"
              primaryLabel="Submit an Opportunity"
              secondaryHref="/login"
              secondaryLabel="Sign in"
            />
          </div>
        )}

        {result && result.data.length === 0 && (
          <EmptyState
            title={params.search || params.country ? 'No companies match these filters' : 'No companies with published opportunities yet'}
            body={
              params.search || params.country
                ? 'Try a different country or clear your search.'
                : 'Companies appear here only after at least one of their opportunities is published. Submit a lead to get started.'
            }
            primaryHref="/submit"
            primaryLabel="Submit an Opportunity"
            secondaryHref={params.search || params.country ? '/companies' : '/opportunities'}
            secondaryLabel={params.search || params.country ? 'Clear filters' : 'Browse opportunities'}
          />
        )}

        <div className="grid sm:grid-cols-2 gap-4">
          {result?.data.map((company) => (
            <Link
              key={company.id}
              href={`/companies/${company.id}`}
              className="flex items-start gap-4 border border-line bg-paper rounded-md p-5 hover:border-ink transition-colors"
            >
              {company.verification_status === 'verified' ? (
                <VerificationStamp size="sm" />
              ) : (
                <div className="w-9 h-9 rounded-full border border-dashed border-manifest/40 shrink-0" />
              )}
              <div className="min-w-0">
                <h2 className="font-display font-semibold text-ink truncate">{company.name}</h2>
                <p className="font-mono text-xs text-manifest mt-1">
                  {company.country}
                  {company.industry ? ` · ${company.industry}` : ''}
                </p>
                {company.role && <p className="text-sm text-manifest mt-1">{company.role}</p>}
                <div className="flex items-center gap-2 mt-2">
                  <p className="font-mono text-[10px] text-seal">
                    {company.published_opportunities_count} PUBLISHED
                  </p>
                  {company.verification_status !== 'verified' && (
                    <p className="font-mono text-[10px] text-manifest">· UNVERIFIED</p>
                  )}
                </div>
              </div>
            </Link>
          ))}
        </div>

        {result && result.meta.last_page > 1 && (
          <div className="flex justify-center gap-2 mt-10 font-mono text-sm">
            {Array.from({ length: result.meta.last_page }, (_, i) => i + 1).map((p) => (
              <Link
                key={p}
                href={`/companies?page=${p}${params.search ? `&search=${params.search}` : ''}${params.country ? `&country=${params.country}` : ''}`}
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

      <SiteFooter />
    </>
  );
}
