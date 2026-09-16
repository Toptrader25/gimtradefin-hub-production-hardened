import 'server-only';

import { getAuthToken } from './auth';
import type {
  EntitySummary,
  EntityDetail,
  EntityMatch,
  CommercialProfile,
  CommercialFact,
  CommercialRole,
  EntityRelationship,
  OpportunityMatch,
  MatchExplanation,
  RiskAssessment,
  RiskReviewCase,
  IntentAssessment,
} from './entity-types';

export type {
  EntitySummary,
  EntityDetail,
  EntityMatch,
  CommercialProfile,
  CommercialFact,
  CommercialRole,
  EntityRelationship,
  OpportunityMatch,
  MatchExplanation,
  RiskAssessment,
  RiskReviewCase,
  IntentAssessment,
} from './entity-types';

const API_BASE_URL = process.env.NEXT_PUBLIC_API_BASE_URL || 'http://localhost:8000/api/v1';

async function authFetch(path: string, init: RequestInit = {}): Promise<Response> {
  const token = await getAuthToken();
  return fetch(`${API_BASE_URL}${path}`, {
    ...init,
    headers: {
      'Content-Type': 'application/json',
      ...(token ? { Authorization: `Bearer ${token}` } : {}),
      ...init.headers,
    },
    cache: 'no-store',
  });
}

// ---------- Entities ----------

export async function getEntities(page = 1): Promise<{ data: EntitySummary[]; current_page: number; last_page: number; total: number } | null> {
  const res = await authFetch(`/entities?page=${page}`);
  if (!res.ok) return null;
  return res.json();
}

export async function getEntity(id: string): Promise<EntityDetail | null> {
  const res = await authFetch(`/entities/${id}`);
  if (!res.ok) return null;
  return res.json();
}

/** Duplicate-entity resolution candidates (Stage 3) — NOT the same thing
 *  as OpportunityMatch below (Stage 7 buyer/seller/partner matching).
 *  Confusingly similar names in the underlying package; kept distinct
 *  here on purpose. */
export async function getEntityDedupeMatches(entityId: string): Promise<EntityMatch[]> {
  const res = await authFetch(`/entities/${entityId}/matches`);
  if (!res.ok) return [];
  const data = await res.json();
  return data.data ?? [];
}

// ---------- Commercial Intelligence (Stage 6) ----------

export async function getCommercialProfile(entityId: string): Promise<CommercialProfile | null> {
  const res = await authFetch(`/commercial-intelligence/entities/${entityId}/profile`);
  if (!res.ok) return null;
  return res.json();
}

export async function getCommercialFacts(entityId: string): Promise<CommercialFact[]> {
  const res = await authFetch(`/commercial-intelligence/entities/${entityId}/facts`);
  if (!res.ok) return [];
  const data = await res.json();
  return data.data ?? [];
}

export async function getCommercialRoles(entityId: string): Promise<CommercialRole[]> {
  const res = await authFetch(`/commercial-intelligence/entities/${entityId}/roles`);
  if (!res.ok) return [];
  return res.json();
}

export async function getEntityRelationships(entityId: string): Promise<EntityRelationship[]> {
  const res = await authFetch(`/commercial-intelligence/entities/${entityId}/relationships`);
  if (!res.ok) return [];
  const data = await res.json();
  return data.data ?? [];
}

// ---------- Opportunity Matching (Stage 7 — buyer/seller/partner) ----------

export async function getOpportunityMatches(entityId: string): Promise<OpportunityMatch[]> {
  const res = await authFetch(`/matching/entity/${entityId}`);
  if (!res.ok) return [];
  const data = await res.json();
  return Array.isArray(data) ? data : data.data ?? [];
}

export async function explainMatch(matchId: string): Promise<MatchExplanation | null> {
  const res = await authFetch(`/matching/${matchId}/explain`);
  if (!res.ok) return null;
  return res.json();
}

// ---------- Trust / Risk ----------

export async function getLatestRisk(subjectType: 'candidate' | 'entity', id: string): Promise<RiskAssessment | null> {
  const res = await authFetch(`/trust/${subjectType}s/${id}/latest`);
  if (!res.ok) return null;
  const data = await res.json();
  return data && data.id ? data : null;
}

export async function getRiskReviews(): Promise<{ data: RiskReviewCase[] } | null> {
  const res = await authFetch('/trust/reviews');
  if (!res.ok) return null;
  return res.json();
}

// ---------- Commercial Intent ----------

export async function getLatestIntent(candidateId: string): Promise<IntentAssessment | null> {
  const res = await authFetch(`/intent/candidates/${candidateId}/latest`);
  if (!res.ok) return null;
  const data = await res.json();
  return data && data.id ? data : null;
}

/** Uses the entity-level intent route added to close a real gap — see
 *  routes/commercial_intent.php. Risk had this symmetry already; intent
 *  originally didn't. */
export async function getLatestEntityIntent(entityId: string): Promise<IntentAssessment | null> {
  const res = await authFetch(`/intent/entities/${entityId}/latest`);
  if (!res.ok) return null;
  const data = await res.json();
  return data && data.id ? data : null;
}
