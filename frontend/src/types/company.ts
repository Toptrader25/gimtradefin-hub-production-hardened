export type VerificationStatus = 'unknown' | 'pending' | 'verified';

export interface CompanySummary {
  id: number;
  name: string;
  country: string;
  industry: string | null;
  role: string | null;
  verification_status: VerificationStatus;
  published_opportunities_count: number;
}

export interface CompanyOpportunitySummary {
  id: number;
  title: string;
  category: 'buying' | 'selling' | 'partnership';
  country: string;
  overall_score: number | null;
  published_at: string | null;
}

export interface CompanyDetail extends CompanySummary {
  description: string | null;
  website: string | null;
  markets: string[];
  products: string[];
  counts: {
    buying: number;
    selling: number;
    partnership: number;
  };
  opportunities: CompanyOpportunitySummary[];
}

export interface PaginatedCompanies {
  data: CompanySummary[];
  meta: {
    current_page: number;
    last_page: number;
    total: number;
  };
}
