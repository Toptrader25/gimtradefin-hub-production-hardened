import { getAuthToken } from './auth';

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

export interface Candidate {
  id: string;
  title: string;
  source_slug: string;
  country: string | null;
  signal_type: string | null;
  url: string | null;
  first_seen_at: string;
}

export interface VerificationCaseSummary {
  id: string;
  entity_id: string | null;
  candidate_id: string | null;
  case_type: string;
  status: string;
  priority: string;
  verification_score: number;
  qualification_score: number;
  decision: string;
  due_at: string | null;
  created_at: string;
}

export interface VerificationCheck {
  id: string;
  case_id: string;
  category: string;
  check_code: string;
  status: string;
  weight: number;
  score: number | null;
  finding: string | null;
}

export interface VerificationCaseSnapshot {
  case: VerificationCaseSummary & { context: unknown; summary: unknown };
  checks: VerificationCheck[];
  qualification: Array<{ question_code: string; answer: string; score: number | null }>;
  gates: { critical_unresolved: number; required_failed: number; evidence_ready: boolean };
  next_actions: string[];
}

export interface EntitySummary {
  id: string;
  canonical_name: string;
  country_name?: string;
  last_seen_at: string;
}

interface Paginated<T> {
  data: T[];
  meta: { current_page: number; last_page: number; total: number };
}

export async function getCandidates(page = 1): Promise<Paginated<Candidate> | null> {
  const res = await authFetch(`/admin/lead-hunter/candidates?page=${page}`);
  if (!res.ok) return null;
  return res.json();
}

export async function getVerificationCases(params: { status?: string; page?: number } = {}): Promise<Paginated<VerificationCaseSummary> | null> {
  const q = new URLSearchParams();
  if (params.status) q.set('status', params.status);
  if (params.page) q.set('page', String(params.page));
  const res = await authFetch(`/admin/lead-hunter/cases?${q.toString()}`);
  if (!res.ok) return null;
  return res.json();
}

export async function getVerificationCase(id: string): Promise<VerificationCaseSnapshot | null> {
  const res = await authFetch(`/verification/cases/${id}`);
  if (!res.ok) return null;
  return res.json();
}

export async function getEntities(page = 1): Promise<{ data: EntitySummary[]; last_page: number } | null> {
  const res = await authFetch(`/entities?page=${page}`);
  if (!res.ok) return null;
  const json = await res.json();
  // Laravel's paginate() shape when returned directly (not wrapped by us)
  return { data: json.data, last_page: json.last_page };
}
