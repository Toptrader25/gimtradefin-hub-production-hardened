import { getAuthToken } from './auth';
import { NotificationsResponse } from '@/types/notification';

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
    cache: 'no-store', // auth-scoped data must never be stale/shared across users
  });
}

// ---------- Me (any signed-in user) ----------

export interface MyOpportunity {
  id: number;
  title: string;
  category: string;
  status: string;
  country: string;
  overall_score: number | null;
  created_at: string;
  published_at: string | null;
}

export interface MyEnquiry {
  id: number;
  type: string;
  status: string;
  message: string | null;
  opportunity: { id: number; title: string; category: string } | null;
  created_at: string;
}

export async function getMyOpportunities(): Promise<MyOpportunity[] | null> {
  const res = await authFetch('/me/opportunities');
  if (!res.ok) return null;
  const data = await res.json();
  return data.data;
}

export async function getMyEnquiries(): Promise<MyEnquiry[] | null> {
  const res = await authFetch('/me/enquiries');
  if (!res.ok) return null;
  const data = await res.json();
  return data.data;
}

// ---------- Admin (role-checked server-side by Laravel) ----------

export interface AdminOpportunitySummary {
  id: number;
  title: string;
  category: string;
  status: string;
  country: string;
  overall_score: number | null;
  evidence_count: number | null;
  company: { id: number; name: string } | null;
  created_at: string;
}

export interface AdminOpportunityDetail extends AdminOpportunitySummary {
  quantity: string | null;
  preferred_origin: string | null;
  payment_terms: string | null;
  description: string;
  submitted_by_name: string | null;
  submitted_by_email: string | null;
  submitted_by_company: string | null;
  submitted_by_phone: string | null;
  commercial_intent_score: number | null;
  source_reliability_score: number | null;
  evidence_strength_score: number | null;
  recency_score: number | null;
  company_confidence_score: number | null;
  match_potential_score: number | null;
  evidence: Array<{
    id: number;
    evidence_type: string | null;
    excerpt: string | null;
    original_url: string | null;
    discovered_at: string | null;
    source_confidence: number | null;
  }>;
  verified_by: { id: number; name: string } | null;
  verified_at: string | null;
}

export interface AdminStats {
  by_status: {
    discovered: number;
    reviewing: number;
    verified: number;
    published: number;
    expired: number;
    rejected: number;
  };
  pending_review: number;
}

export interface AdminCompanySummary {
  id: number;
  name: string;
  country: string;
  role: string | null;
  verification_status: string;
  opportunities_count: number;
  created_at: string;
}

export interface AdminPaginated<T> {
  data: T[];
  meta: { current_page: number; last_page: number; total: number };
}

export interface AuditLogEntry {
  id: number;
  action: string;
  changes: Record<string, unknown> | null;
  auditable_type: string;
  auditable_id: number;
  user: { id: number; name: string } | null;
  created_at: string;
}

export interface AdminUser {
  id: number;
  name: string;
  email: string;
  role: string;
}

export async function getAdminStats(): Promise<AdminStats | null> {
  const res = await authFetch('/admin/stats');
  if (!res.ok) return null;
  return res.json();
}

export async function getAdminOpportunities(params: {
  status?: string;
  category?: string;
  page?: number;
} = {}): Promise<AdminPaginated<AdminOpportunitySummary> | null> {
  const q = new URLSearchParams();
  if (params.status) q.set('status', params.status);
  if (params.category) q.set('category', params.category);
  if (params.page) q.set('page', String(params.page));
  const res = await authFetch(`/admin/opportunities?${q.toString()}`);
  if (!res.ok) return null;
  return res.json();
}

export async function getAdminOpportunity(id: number): Promise<AdminOpportunityDetail | null> {
  const res = await authFetch(`/admin/opportunities/${id}`);
  if (!res.ok) return null;
  return res.json();
}

export async function getAdminCompanies(params: {
  verification_status?: string;
  page?: number;
} = {}): Promise<AdminPaginated<AdminCompanySummary> | null> {
  const q = new URLSearchParams();
  if (params.verification_status) q.set('verification_status', params.verification_status);
  if (params.page) q.set('page', String(params.page));
  const res = await authFetch(`/admin/companies?${q.toString()}`);
  if (!res.ok) return null;
  return res.json();
}

export async function getAuditLog(): Promise<AuditLogEntry[] | null> {
  const res = await authFetch('/admin/audit-log');
  if (!res.ok) return null;
  const data = await res.json();
  return data.data;
}

export async function getOpportunityAuditLog(id: number): Promise<AuditLogEntry[] | null> {
  const res = await authFetch(`/admin/opportunities/${id}/audit-log`);
  if (!res.ok) return null;
  const data = await res.json();
  return data.data;
}

export async function getCompanyAuditLog(id: number): Promise<AuditLogEntry[] | null> {
  const res = await authFetch(`/admin/companies/${id}/audit-log`);
  if (!res.ok) return null;
  const data = await res.json();
  return data.data;
}

export async function getAdminUsers(): Promise<AdminUser[] | null> {
  const res = await authFetch('/admin/users');
  if (!res.ok) return null;
  const data = await res.json();
  return data.data;
}

// ---------- Notifications (any signed-in user) ----------

export async function getMyNotifications(): Promise<NotificationsResponse | null> {
  const res = await authFetch('/me/notifications');
  if (!res.ok) return null;
  return res.json();
}

// ---------- Admin company detail ----------

export interface AdminCompanyDetail {
  id: number;
  name: string;
  country: string;
  industry: string | null;
  role: string | null;
  description: string | null;
  website: string | null;
  markets: string[];
  products: string[];
  verification_status: string;
  verified_at: string | null;
  opportunities_count: number;
  created_at: string;
  opportunities: Array<{ id: number; title: string; category: string; status: string; country: string }>;
}

export async function getAdminCompany(id: number): Promise<AdminCompanyDetail | null> {
  const res = await authFetch(`/admin/companies/${id}`);
  if (!res.ok) return null;
  return res.json();
}
