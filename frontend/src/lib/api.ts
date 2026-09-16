import { PaginatedOpportunities, OpportunityCategory, OpportunityDetail, SuggestedMatch } from '@/types/opportunity';
import { PaginatedCompanies, CompanyDetail } from '@/types/company';
import { PlatformStats } from '@/types/stats';
import { getAuthToken } from './auth';

const API_BASE_URL = process.env.NEXT_PUBLIC_API_BASE_URL || 'http://localhost:8000/api/v1';

/**
 * Real, computed platform stats — replaces what used to be hardcoded
 * placeholder numbers on the homepage. Returns null (not fake zeros with
 * a misleading appearance of success) if the API can't be reached, so
 * the homepage can distinguish "genuinely zero" from "couldn't check".
 */
export async function getStats(): Promise<PlatformStats | null> {
  try {
    const res = await fetch(`${API_BASE_URL}/stats`, { next: { revalidate: 60 } });
    if (!res.ok) return null;
    return res.json();
  } catch {
    return null;
  }
}

/**
 * Fetches published opportunities from the Laravel API.
 * Only ever requests "published" status — draft/discovered/reviewing
 * leads are never exposed to this public frontend. That filtering
 * happens server-side in Laravel, not here, but we never even ask
 * for anything else.
 */
export async function getOpportunities(params: {
  category?: OpportunityCategory;
  country?: string;
  search?: string;
  page?: number;
} = {}): Promise<PaginatedOpportunities> {
  const query = new URLSearchParams();
  if (params.category) query.set('category', params.category);
  if (params.country) query.set('country', params.country);
  if (params.search) query.set('search', params.search);
  if (params.page) query.set('page', String(params.page));

  const res = await fetch(`${API_BASE_URL}/opportunities?${query.toString()}`, {
    // Revalidate periodically rather than caching forever or fetching on
    // every request — opportunities don't change second-to-second.
    next: { revalidate: 60 },
  });

  if (!res.ok) {
    throw new Error(`Failed to fetch opportunities: ${res.status}`);
  }

  return res.json();
}

/** Thrown specifically on a 404 so callers can distinguish "doesn't exist
 *  or isn't published" from "the API is down" and respond differently
 *  (notFound() vs an error banner). */
export class OpportunityNotFoundError extends Error {}

export async function getOpportunity(id: number): Promise<OpportunityDetail> {
  const res = await fetch(`${API_BASE_URL}/opportunities/${id}`, {
    next: { revalidate: 60 },
  });

  if (res.status === 404) {
    throw new OpportunityNotFoundError(`Opportunity ${id} not found or not published`);
  }

  if (!res.ok) {
    throw new Error(`Failed to fetch opportunity ${id}: ${res.status}`);
  }

  return res.json();
}

export interface EnquiryInput {
  enquirer_name: string;
  enquirer_email: string;
  enquirer_company?: string;
  type: 'introduction_request' | 'financing_assessment' | 'general';
  message?: string;
}

/** Submits a "Request Introduction" enquiry against a specific opportunity. */
export async function submitEnquiry(opportunityId: number, input: EnquiryInput): Promise<void> {
  const token = await getAuthToken();
  const res = await fetch(`${API_BASE_URL}/opportunities/${opportunityId}/enquiries`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      ...(token ? { Authorization: `Bearer ${token}` } : {}),
    },
    body: JSON.stringify(input),
  });

  if (!res.ok) {
    throw new Error(`Failed to submit enquiry: ${res.status}`);
  }
}

export interface OpportunitySubmissionInput {
  title: string;
  category: OpportunityCategory;
  description: string;
  quantity?: string;
  country: string;
  preferred_origin?: string;
  payment_terms?: string;
  submitted_by_name: string;
  submitted_by_email: string;
  submitted_by_company?: string;
  submitted_by_phone?: string;
  website?: string; // honeypot — must stay empty
}

/**
 * Submits a new opportunity for review. Always lands as status="discovered"
 * on the backend — never published automatically. See
 * OpportunitySubmissionController for the enforcement.
 */
export async function submitOpportunity(input: OpportunitySubmissionInput): Promise<{ id: number; message: string }> {
  const token = await getAuthToken();
  const res = await fetch(`${API_BASE_URL}/opportunities`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      ...(token ? { Authorization: `Bearer ${token}` } : {}),
    },
    body: JSON.stringify(input),
  });

  if (!res.ok) {
    const body = await res.json().catch(() => null);
    throw new Error(body?.message || `Failed to submit opportunity: ${res.status}`);
  }

  return res.json();
}

/**
 * Fetches the public company directory. Only companies with at least
 * one published opportunity are ever returned — enforced server-side
 * in CompanyController, not here.
 */
export async function getCompanies(params: {
  country?: string;
  industry?: string;
  search?: string;
  page?: number;
} = {}): Promise<PaginatedCompanies> {
  const query = new URLSearchParams();
  if (params.country) query.set('country', params.country);
  if (params.industry) query.set('industry', params.industry);
  if (params.search) query.set('search', params.search);
  if (params.page) query.set('page', String(params.page));

  const res = await fetch(`${API_BASE_URL}/companies?${query.toString()}`, {
    next: { revalidate: 60 },
  });

  if (!res.ok) {
    throw new Error(`Failed to fetch companies: ${res.status}`);
  }

  return res.json();
}

export class CompanyNotFoundError extends Error {}

export async function getCompany(id: number): Promise<CompanyDetail> {
  const res = await fetch(`${API_BASE_URL}/companies/${id}`, {
    next: { revalidate: 60 },
  });

  if (res.status === 404) {
    throw new CompanyNotFoundError(`Company ${id} not found or has no published opportunities`);
  }

  if (!res.ok) {
    throw new Error(`Failed to fetch company ${id}: ${res.status}`);
  }

  return res.json();
}

/** Rule-based suggested matches — see OpportunityController::matches(). */
export async function getOpportunityMatches(id: number): Promise<SuggestedMatch[]> {
  try {
    const res = await fetch(`${API_BASE_URL}/opportunities/${id}/matches`, { next: { revalidate: 60 } });
    if (!res.ok) return [];
    const data = await res.json();
    return data.data;
  } catch {
    return [];
  }
}
