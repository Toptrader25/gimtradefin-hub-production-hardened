'use server';

import { revalidatePath } from 'next/cache';
import { getAuthToken } from './auth';
import type { MatchExplanation } from './entity-types';
import { explainMatch } from './entity-api';

const API_BASE_URL = process.env.NEXT_PUBLIC_API_BASE_URL || 'http://localhost:8000/api/v1';

export interface AssessActionResult {
  ok: boolean;
  message?: string;
}

async function postAuthed(path: string): Promise<AssessActionResult> {
  const token = await getAuthToken();
  if (!token) return { ok: false, message: 'Not signed in.' };

  try {
    const res = await fetch(`${API_BASE_URL}${path}`, {
      method: 'POST',
      headers: { Authorization: `Bearer ${token}` },
    });
    if (!res.ok) {
      const body = await res.json().catch(() => null);
      return { ok: false, message: body?.message || `Request failed (${res.status}).` };
    }
    return { ok: true };
  } catch {
    return { ok: false, message: 'Could not reach the API.' };
  }
}

export async function assessCandidateRiskAction(candidateId: string): Promise<AssessActionResult> {
  const result = await postAuthed(`/trust/candidates/${candidateId}/assess`);
  revalidatePath('/admin/lead-hunter/candidates');
  return result;
}

export async function assessCandidateIntentAction(candidateId: string): Promise<AssessActionResult> {
  const result = await postAuthed(`/intent/candidates/${candidateId}/assess`);
  revalidatePath('/admin/lead-hunter/candidates');
  return result;
}

export async function assessEntityRiskAction(entityId: string): Promise<AssessActionResult> {
  const result = await postAuthed(`/trust/entities/${entityId}/assess`);
  revalidatePath(`/admin/lead-hunter/entities/${entityId}`);
  return result;
}

export async function assessEntityIntentAction(entityId: string): Promise<AssessActionResult> {
  const result = await postAuthed(`/intent/entities/${entityId}/assess`);
  revalidatePath(`/admin/lead-hunter/entities/${entityId}`);
  return result;
}

export interface LanguageAnalysis {
  detected_language: string | null;
  sentiment: string | null;
  confidence: number | null;
  [key: string]: unknown;
}

/** Returns the analysis directly rather than just ok/message, since the
 *  UI needs to actually display the result (detected language, etc.),
 *  not just confirm the action ran. */
export async function analyzeCandidateLanguageAction(
  candidateId: string,
  text: string
): Promise<{ ok: boolean; message?: string; analysis?: LanguageAnalysis }> {
  const token = await getAuthToken();
  if (!token) return { ok: false, message: 'Not signed in.' };

  try {
    const res = await fetch(`${API_BASE_URL}/multilingual/analyze`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', Authorization: `Bearer ${token}` },
      body: JSON.stringify({ asset_type: 'candidate', asset_id: candidateId, text }),
    });
    const data = await res.json().catch(() => null);
    if (!res.ok) {
      return { ok: false, message: data?.message || `Request failed (${res.status}).` };
    }
    return { ok: true, analysis: data };
  } catch {
    return { ok: false, message: 'Could not reach the API.' };
  }
}

export async function buildCommercialProfileAction(entityId: string): Promise<AssessActionResult> {
  const result = await postAuthed(`/commercial-intelligence/entities/${entityId}/profile`);
  revalidatePath(`/admin/lead-hunter/entities/${entityId}`);
  return result;
}

export async function buildSemanticProfileAction(entityId: string): Promise<AssessActionResult> {
  const result = await postAuthed(`/semantic/entities/${entityId}/profile`);
  revalidatePath(`/admin/lead-hunter/entities/${entityId}`);
  return result;
}

export async function recordMatchFeedbackAction(
  matchId: string,
  decision: string,
  notes: string
): Promise<AssessActionResult> {
  const token = await getAuthToken();
  if (!token) return { ok: false, message: 'Not signed in.' };
  try {
    const res = await fetch(`${API_BASE_URL}/matching/${matchId}/feedback`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', Authorization: `Bearer ${token}` },
      body: JSON.stringify({ decision, notes }),
    });
    if (!res.ok) {
      const body = await res.json().catch(() => null);
      return { ok: false, message: body?.message || `Request failed (${res.status}).` };
    }
    return { ok: true };
  } catch {
    return { ok: false, message: 'Could not reach the API.' };
  }
}

export async function explainMatchAction(matchId: string): Promise<MatchExplanation | null> {
  return explainMatch(matchId);
}

