import Link from 'next/link';
import { SiteHeader } from '@/components/SiteHeader';
import { RiskPanel } from '@/components/RiskPanel';
import { IntentPanel } from '@/components/IntentPanel';
import { CommercialIntelligencePanel } from '@/components/CommercialIntelligencePanel';
import { OpportunityMatchList } from '@/components/OpportunityMatchList';
import { BuildSemanticProfileButton } from '@/components/BuildSemanticProfileButton';
import { CreateCaseFromEntityButton } from '@/components/CreateCaseFromEntityButton';
import { CreateEngagementButton } from '@/components/CreateEngagementButton';
import { requireReviewer } from '@/lib/auth';
import {
  getEntity,
  getLatestRisk,
  getLatestEntityIntent,
  getEntityDedupeMatches,
  getCommercialProfile,
  getCommercialFacts,
  getCommercialRoles,
  getOpportunityMatches,
} from '@/lib/entity-api';
import { assessEntityRiskAction, assessEntityIntentAction } from '@/lib/entity-actions';

export default async function EntityDetailPage({
  params,
}: {
  params: Promise<{ id: string }>;
}) {
  await requireReviewer();
  const { id } = await params;

  const [entity, risk, intent, dedupeMatches, commercialProfile, facts, roles, opportunityMatches] =
    await Promise.all([
      getEntity(id),
      getLatestRisk('entity', id),
      getLatestEntityIntent(id),
      getEntityDedupeMatches(id),
      getCommercialProfile(id),
      getCommercialFacts(id),
      getCommercialRoles(id),
      getOpportunityMatches(id),
    ]);

  if (!entity) {
    return (
      <>
        <SiteHeader />
        <main className="max-w-2xl mx-auto px-6 py-20 text-center flex-1 w-full">
          <p className="text-manifest">Could not load this entity, or it doesn&apos;t exist.</p>
          <Link href="/admin/lead-hunter/entities" className="inline-block mt-4 text-sm text-seal underline">
            Back to entities
          </Link>
        </main>
      </>
    );
  }

  const assessRisk = assessEntityRiskAction.bind(null, id);
  const assessIntent = assessEntityIntentAction.bind(null, id);

  return (
    <>
      <SiteHeader />
      <main className="max-w-3xl mx-auto px-6 py-12 flex-1 w-full">
        <Link href="/admin/lead-hunter/entities" className="font-mono text-xs text-manifest hover:text-ink">
          ← Entities
        </Link>

        <div className="flex items-start justify-between gap-4 mt-4 mb-8">
          <div>
            <span className="font-mono text-[10px] uppercase tracking-widest border border-line px-2 py-1 rounded-sm">
              {entity.resolution_state}
            </span>
            <h1 className="font-display font-bold text-2xl text-ink mt-3">{entity.canonical_name}</h1>
            {entity.legal_name && entity.legal_name !== entity.canonical_name && (
              <p className="text-sm text-manifest mt-1">{entity.legal_name}</p>
            )}
            <p className="font-mono text-xs text-manifest mt-2">
              {entity.entity_type}
              {entity.country_name ? ` · ${entity.country_name}` : ''}
              {entity.website_domain ? ` · ${entity.website_domain}` : ''}
            </p>
            <p className="font-mono text-[10px] text-manifest mt-1">
              Resolution confidence: {Math.round(entity.resolution_confidence * 100)}%
            </p>
          </div>
          <div className="flex gap-3 shrink-0">
            <CreateEngagementButton entityId={entity.id} entityName={entity.canonical_name} />
            <CreateCaseFromEntityButton entityId={entity.id} />
          </div>
        </div>

        <div className="grid sm:grid-cols-2 gap-4 mb-6">
          <RiskPanel assessment={risk} onAssess={assessRisk} />
          <IntentPanel assessment={intent} onAssess={assessIntent} />
        </div>

        <div className="mb-8">
          <CommercialIntelligencePanel entityId={id} profile={commercialProfile} facts={facts} roles={roles} />
        </div>

        {entity.aliases.length > 0 && (
          <section className="mb-8">
            <p className="font-mono text-[10px] tracking-widest text-manifest mb-2">ALIASES</p>
            <div className="flex flex-wrap gap-2">
              {entity.aliases.map((a) => (
                <span key={a.id} className="font-mono text-xs border border-line rounded-sm px-2 py-1 text-ink">
                  {a.alias}
                </span>
              ))}
            </div>
          </section>
        )}

        {entity.identifiers.length > 0 && (
          <section className="mb-8">
            <p className="font-mono text-[10px] tracking-widest text-manifest mb-2">IDENTIFIERS</p>
            <div className="space-y-1">
              {entity.identifiers.map((i) => (
                <p key={i.id} className="font-mono text-xs text-ink">
                  {i.identifier_type}: {i.identifier_value}
                </p>
              ))}
            </div>
          </section>
        )}

        <section className="mb-8">
          <div className="flex items-center justify-between mb-2">
            <p className="font-mono text-[10px] tracking-widest text-manifest">
              OPPORTUNITY MATCHES — WHO THIS ENTITY COULD TRADE WITH
            </p>
            <BuildSemanticProfileButton entityId={id} />
          </div>
          <OpportunityMatchList matches={opportunityMatches} />
        </section>

        {dedupeMatches.length > 0 && (
          <section className="mb-8">
            <p className="font-mono text-[10px] tracking-widest text-manifest mb-2">
              POSSIBLE DUPLICATE ENTITIES — DIFFERENT FROM OPPORTUNITY MATCHES ABOVE
            </p>
            <div className="space-y-2">
              {dedupeMatches.slice(0, 10).map((m) => (
                <div key={m.id} className="flex items-center justify-between border border-line rounded-sm p-2 text-sm">
                  <span className="font-mono text-xs text-manifest">{m.matched_entity_id || m.id}</span>
                  <span className="font-mono text-xs text-signal">{Math.round(m.score * 100) / 100}</span>
                </div>
              ))}
            </div>
          </section>
        )}

        {entity.observations.length > 0 && (
          <section>
            <p className="font-mono text-[10px] tracking-widest text-manifest mb-2">
              RECENT OBSERVATIONS ({entity.observations.length})
            </p>
            <div className="space-y-1">
              {entity.observations.slice(0, 10).map((o) => (
                <p key={o.id} className="font-mono text-xs text-manifest">
                  {o.observation_type} — {new Date(o.observed_at).toLocaleString()}
                </p>
              ))}
            </div>
          </section>
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
