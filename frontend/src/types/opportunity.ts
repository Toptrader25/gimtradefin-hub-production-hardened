// Mirrors the "opportunities" table defined in the Laravel API.
// Keep this in sync with backend/database/migrations/*_create_opportunities_table.php

export type OpportunityCategory = 'buying' | 'selling' | 'partnership';

export type OpportunityStatus =
  | 'test'        // synthetic, dev-only — never shown outside internal tooling
  | 'discovered'  // found by a source connector, unreviewed
  | 'reviewing'   // GiMtradefin is investigating it
  | 'verified'    // independently verified, not yet public
  | 'published'   // approved for public display
  | 'expired'
  | 'rejected';

export interface Company {
  id: number;
  name: string;
  country: string;
  industry: string | null;
  role: string | null; // e.g. "Importer / Distributor"
  verification_status: 'unknown' | 'pending' | 'verified';
}

export interface Evidence {
  original_url: string | null;
  excerpt: string | null;
  evidence_type: string | null;
  discovered_at: string | null;
}

export interface Opportunity {
  id: number;
  title: string;
  category: OpportunityCategory;
  status: OpportunityStatus;
  description: string;
  quantity: string | null;
  country: string;
  preferred_origin: string | null;
  payment_terms: string | null;
  commercial_intent_score: number | null; // 0-100, null until AI-scored
  source_reliability_score: number | null;
  evidence_strength_score: number | null;
  overall_score: number | null;
  company: Company | null;
  published_at: string | null;
  created_at: string;
}

// Returned by GET /opportunities/{id} only — evidence is deliberately
// left out of the list endpoint to keep list responses light.
export interface OpportunityDetail extends Opportunity {
  evidence: Evidence[];
}

export interface PaginatedOpportunities {
  data: Opportunity[];
  meta: {
    current_page: number;
    last_page: number;
    total: number;
  };
}

export interface SuggestedMatch {
  id: number;
  title: string;
  category: OpportunityCategory;
  country: string;
  same_country: boolean;
  company: { id: number; name: string } | null;
}
