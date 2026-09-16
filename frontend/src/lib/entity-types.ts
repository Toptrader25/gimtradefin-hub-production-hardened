// Shared entity / matching / risk / intent types — safe for client components.
// Keep this file free of next/headers, auth, and fetch helpers.

export interface EntitySummary {
  id: string;
  entity_type: string;
  canonical_name: string;
  country_name: string | null;
  status: string;
  resolution_state: string;
  last_seen_at: string;
}

export interface EntityDetail extends EntitySummary {
  legal_name: string | null;
  country_code: string | null;
  website_domain: string | null;
  resolution_confidence: number;
  first_seen_at: string;
  aliases: Array<{ id: string; alias: string }>;
  identifiers: Array<{ id: string; identifier_type: string; identifier_value: string }>;
  observations: Array<{ id: string; observation_type: string; observed_at: string; details?: unknown }>;
  links: Array<{ id: string; source_type: string; source_id: string; created_at: string }>;
}

export interface EntityMatch {
  id: string;
  entity_id: string;
  score: number;
  matched_entity_id?: string;
}

export interface CommercialProfile {
  entity_id: string;
  primary_role: string | null;
  confidence: number | null;
  summary: string | null;
  [key: string]: unknown;
}

export interface CommercialFact {
  id: string;
  fact_type: string;
  value: string | null;
  observed_at: string;
}

export interface CommercialRole {
  id: string;
  role: string;
  confidence: number;
  calculated_at: string;
}

export interface EntityRelationship {
  id: string;
  from_entity_id: string;
  to_entity_id: string;
  relationship_type: string;
  observed_at: string;
}

export interface OpportunityMatch {
  id: string;
  entity_id: string;
  matched_entity_id: string;
  matched_entity_name?: string;
  score: number;
  match_type: string | null;
}

export interface MatchExplanation {
  match_id: string;
  factors: Array<{ factor: string; weight: number; contribution: number }>;
  narrative?: string;
}

export interface RiskAssessment {
  id: string;
  subject_type: string;
  subject_id: string;
  risk_score: number;
  risk_band: string;
  decision: string;
  dimensions: Record<string, number> | null;
  top_signals: Array<{ signal: string; weight?: number }> | null;
  calculated_at: string;
}

export interface RiskReviewCase {
  id: string;
  subject_type: string;
  subject_id: string;
  case_type: string;
  priority: string;
  status: string;
  reason: string | null;
  created_at: string;
}

export interface IntentAssessment {
  id: string;
  subject_type: string;
  subject_id: string;
  intent_score: number;
  intent_stage: string;
  buying_temperature: number;
  confidence: number;
  dimensions: Record<string, number> | null;
  top_signals: Array<{ signal: string; weight?: number }> | null;
  independent_source_count: number;
  calculated_at: string;
}
