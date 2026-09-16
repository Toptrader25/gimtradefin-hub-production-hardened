'use server';

import { revalidatePath } from 'next/cache';
import { getAuthToken } from './auth';

const API_BASE_URL = process.env.NEXT_PUBLIC_API_BASE_URL || 'http://localhost:8000/api/v1';

export interface LeadHunterActionResult {
  ok: boolean;
  message?: string;
  data?: unknown;
}

async function postAuthed(path: string, body: Record<string, unknown> = {}): Promise<LeadHunterActionResult> {
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
    return { ok: true, data };
  } catch {
    return { ok: false, message: 'Could not reach the API.' };
  }
}

export async function resolveCandidateAction(candidateId: string): Promise<LeadHunterActionResult> {
  const result = await postAuthed(`/entities/resolve/${candidateId}`);
  revalidatePath('/admin/lead-hunter/candidates');
  return result;
}

export async function createVerificationCaseAction(input: {
  candidate_id?: string;
  entity_id?: string;
}): Promise<LeadHunterActionResult> {
  const result = await postAuthed('/verification/cases', input);
  revalidatePath('/admin/lead-hunter/cases');
  return result;
}

export async function startCaseAction(caseId: string, reviewer: string): Promise<LeadHunterActionResult> {
  const result = await postAuthed(`/verification/cases/${caseId}/start`, { reviewer });
  revalidatePath(`/admin/lead-hunter/cases/${caseId}`);
  return result;
}

export async function recordCheckAction(
  caseId: string,
  checkCode: string,
  status: string,
  reviewer: string,
  finding?: string
): Promise<LeadHunterActionResult> {
  const result = await postAuthed(`/verification/cases/${caseId}/checks`, {
    check_code: checkCode,
    status,
    reviewer,
    finding: finding || undefined,
  });
  revalidatePath(`/admin/lead-hunter/cases/${caseId}`);
  return result;
}

export async function decideCaseAction(
  caseId: string,
  decision: string,
  reviewer: string,
  reason: string
): Promise<LeadHunterActionResult> {
  const result = await postAuthed(`/verification/cases/${caseId}/decision`, { decision, reviewer, reason });
  revalidatePath(`/admin/lead-hunter/cases/${caseId}`);
  revalidatePath('/admin/lead-hunter/cases');
  return result;
}
