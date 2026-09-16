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

export interface EngagementOpportunitySummary {
  id: string;
  title: string;
  stage: string;
  status: string;
  owner: string | null;
  priority: number;
  next_action: string | null;
  next_action_at: string | null;
  entity_id: string | null;
}

export interface EngagementContact {
  id: string;
  name: string;
  role: string | null;
  verified: boolean;
}

export interface EngagementActivity {
  id: string;
  type: string;
  direction: string;
  actor: string;
  subject: string | null;
  body: string | null;
  occurred_at: string;
}

export interface EngagementTask {
  id: string;
  task_type: string;
  status: string;
  title: string;
  assignee: string | null;
  due_at: string | null;
}

export interface EngagementMessage {
  id: string;
  channel: string;
  direction: string;
  status: string;
  body: string;
  created_at: string;
}

export interface EngagementOutcome {
  id: string;
  outcome: string;
  score: number | null;
  notes: string | null;
  occurred_at: string;
}

export interface EngagementSnapshot {
  opportunity: EngagementOpportunitySummary & { commercial_context: string | null };
  contacts: EngagementContact[];
  activities: EngagementActivity[];
  tasks: EngagementTask[];
  messages: EngagementMessage[];
  outcomes: EngagementOutcome[];
  next_best_actions: string[];
}

export async function getEngagementOpportunities(params: { stage?: string; page?: number } = {}) {
  const q = new URLSearchParams();
  if (params.stage) q.set('stage', params.stage);
  if (params.page) q.set('page', String(params.page));
  const res = await authFetch(`/admin/lead-hunter/engagement-opportunities?${q.toString()}`);
  if (!res.ok) return null;
  return res.json() as Promise<{
    data: EngagementOpportunitySummary[];
    meta: { current_page: number; last_page: number; total: number };
  }>;
}

export async function getEngagementSnapshot(id: string): Promise<EngagementSnapshot | null> {
  const res = await authFetch(`/engagement/opportunities/${id}`);
  if (!res.ok) return null;
  return res.json();
}
