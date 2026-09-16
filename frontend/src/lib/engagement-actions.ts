'use server';

import { revalidatePath } from 'next/cache';
import { getAuthToken } from './auth';

const API_BASE_URL = process.env.NEXT_PUBLIC_API_BASE_URL || 'http://localhost:8000/api/v1';

export interface EngagementActionResult {
  ok: boolean;
  message?: string;
}

async function postAuthed(path: string, body: Record<string, unknown> = {}): Promise<EngagementActionResult> {
  const token = await getAuthToken();
  if (!token) return { ok: false, message: 'Not signed in.' };

  try {
    const res = await fetch(`${API_BASE_URL}${path}`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', Authorization: `Bearer ${token}` },
      body: JSON.stringify(body),
    });
    const data = await res.json().catch(() => null);
    if (!res.ok) {
      return { ok: false, message: data?.message || `Request failed (${res.status}).` };
    }
    return { ok: true };
  } catch {
    return { ok: false, message: 'Could not reach the API.' };
  }
}

export async function createEngagementFromEntityAction(entityId: string, title: string): Promise<EngagementActionResult> {
  const result = await postAuthed('/engagement/opportunities', { entity_id: entityId, title });
  revalidatePath('/admin/lead-hunter/engagement');
  return result;
}

export async function changeEngagementStageAction(
  opportunityId: string,
  stage: string,
  actor: string,
  reason: string
): Promise<EngagementActionResult> {
  const result = await postAuthed(`/engagement/opportunities/${opportunityId}/stage`, { stage, actor, reason });
  revalidatePath(`/admin/lead-hunter/engagement/${opportunityId}`);
  revalidatePath('/admin/lead-hunter/engagement');
  return result;
}

export async function addEngagementContactAction(
  opportunityId: string,
  name: string,
  role: string
): Promise<EngagementActionResult> {
  const result = await postAuthed(`/engagement/opportunities/${opportunityId}/contacts`, { name, role });
  revalidatePath(`/admin/lead-hunter/engagement/${opportunityId}`);
  return result;
}

export async function logEngagementActivityAction(
  opportunityId: string,
  type: string,
  actor: string,
  subject: string,
  body: string
): Promise<EngagementActionResult> {
  const result = await postAuthed(`/engagement/opportunities/${opportunityId}/activities`, {
    type,
    direction: 'internal',
    actor,
    subject,
    body,
  });
  revalidatePath(`/admin/lead-hunter/engagement/${opportunityId}`);
  return result;
}

export async function recordEngagementOutcomeAction(
  opportunityId: string,
  outcome: string,
  score: string,
  notes: string,
  actor: string
): Promise<EngagementActionResult> {
  const result = await postAuthed(`/engagement/opportunities/${opportunityId}/outcomes`, {
    outcome,
    score: score ? parseInt(score, 10) : null,
    notes,
    actor,
  });
  revalidatePath(`/admin/lead-hunter/engagement/${opportunityId}`);
  return result;
}
