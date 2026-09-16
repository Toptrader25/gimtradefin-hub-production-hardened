'use server';

import { revalidatePath } from 'next/cache';
import { getAuthToken } from './auth';

const API_BASE_URL = process.env.NEXT_PUBLIC_API_BASE_URL || 'http://localhost:8000/api/v1';

export interface AdminActionResult {
  ok: boolean;
  message?: string;
}

/**
 * Changes an opportunity's status via the role-checked admin endpoint.
 * If the signed-in user isn't actually admin/reviewer, Laravel's
 * EnsureUserIsReviewer middleware returns a 403 here regardless of
 * what the frontend UI shows — this action doesn't (and shouldn't)
 * duplicate that check client-side.
 */
export async function updateOpportunityStatusAction(
  opportunityId: number,
  status: string,
  notes: string
): Promise<AdminActionResult> {
  const token = await getAuthToken();
  if (!token) {
    return { ok: false, message: 'Not signed in.' };
  }

  try {
    const res = await fetch(`${API_BASE_URL}/admin/opportunities/${opportunityId}/status`, {
      method: 'PATCH',
      headers: { 'Content-Type': 'application/json', Authorization: `Bearer ${token}` },
      body: JSON.stringify({ status, notes: notes || undefined }),
    });

    if (!res.ok) {
      const body = await res.json().catch(() => null);
      return { ok: false, message: body?.message || `Request failed (${res.status}).` };
    }

    revalidatePath(`/admin/opportunities/${opportunityId}`);
    revalidatePath('/admin/opportunities');
    revalidatePath('/admin');
    revalidatePath('/opportunities'); // public list should reflect a new publish/unpublish
    revalidatePath(`/opportunities/${opportunityId}`);

    return { ok: true };
  } catch {
    return { ok: false, message: 'Could not reach the API.' };
  }
}

export async function updateCompanyVerificationAction(
  companyId: number,
  verificationStatus: string
): Promise<AdminActionResult> {
  const token = await getAuthToken();
  if (!token) {
    return { ok: false, message: 'Not signed in.' };
  }

  try {
    const res = await fetch(`${API_BASE_URL}/admin/companies/${companyId}/verify`, {
      method: 'PATCH',
      headers: { 'Content-Type': 'application/json', Authorization: `Bearer ${token}` },
      body: JSON.stringify({ verification_status: verificationStatus }),
    });

    if (!res.ok) {
      const body = await res.json().catch(() => null);
      return { ok: false, message: body?.message || `Request failed (${res.status}).` };
    }

    revalidatePath('/admin/companies');
    revalidatePath('/companies');
    revalidatePath(`/companies/${companyId}`);

    return { ok: true };
  } catch {
    return { ok: false, message: 'Could not reach the API.' };
  }
}

export async function updateOpportunityScoresAction(
  opportunityId: number,
  scores: Record<string, number | null>
): Promise<AdminActionResult> {
  const token = await getAuthToken();
  if (!token) return { ok: false, message: 'Not signed in.' };

  try {
    const res = await fetch(`${API_BASE_URL}/admin/opportunities/${opportunityId}/scores`, {
      method: 'PATCH',
      headers: { 'Content-Type': 'application/json', Authorization: `Bearer ${token}` },
      body: JSON.stringify(scores),
    });

    if (!res.ok) {
      const body = await res.json().catch(() => null);
      return { ok: false, message: body?.message || `Request failed (${res.status}).` };
    }

    revalidatePath(`/admin/opportunities/${opportunityId}`);
    revalidatePath(`/opportunities/${opportunityId}`);

    return { ok: true };
  } catch {
    return { ok: false, message: 'Could not reach the API.' };
  }
}

export async function updateUserRoleAction(userId: number, role: string): Promise<AdminActionResult> {
  const token = await getAuthToken();
  if (!token) return { ok: false, message: 'Not signed in.' };

  try {
    const res = await fetch(`${API_BASE_URL}/admin/users/${userId}/role`, {
      method: 'PATCH',
      headers: { 'Content-Type': 'application/json', Authorization: `Bearer ${token}` },
      body: JSON.stringify({ role }),
    });

    if (!res.ok) {
      const body = await res.json().catch(() => null);
      return { ok: false, message: body?.message || `Request failed (${res.status}).` };
    }

    revalidatePath('/admin/users');
    return { ok: true };
  } catch {
    return { ok: false, message: 'Could not reach the API.' };
  }
}

export async function syncLeadHunterAction(): Promise<AdminActionResult & { synced?: number }> {
  const token = await getAuthToken();
  if (!token) return { ok: false, message: 'Not signed in.' };

  try {
    const res = await fetch(`${API_BASE_URL}/admin/lead-hunter/sync`, {
      method: 'POST',
      headers: { Authorization: `Bearer ${token}` },
    });

    if (!res.ok) {
      const body = await res.json().catch(() => null);
      return { ok: false, message: body?.message || `Request failed (${res.status}).` };
    }

    const data = await res.json();
    revalidatePath('/admin');
    revalidatePath('/admin/opportunities');

    return { ok: true, synced: data.synced, message: `Synced ${data.synced} new opportunit${data.synced === 1 ? 'y' : 'ies'}.` };
  } catch {
    return { ok: false, message: 'Could not reach the API.' };
  }
}

export async function markNotificationReadAction(notificationId: number): Promise<void> {
  const token = await getAuthToken();
  if (!token) return;
  try {
    await fetch(`${API_BASE_URL}/me/notifications/${notificationId}/read`, {
      method: 'PATCH',
      headers: { Authorization: `Bearer ${token}` },
    });
    revalidatePath('/', 'layout');
  } catch {
    // best-effort
  }
}

export async function markAllNotificationsReadAction(): Promise<void> {
  const token = await getAuthToken();
  if (!token) return;
  try {
    await fetch(`${API_BASE_URL}/me/notifications/read-all`, {
      method: 'PATCH',
      headers: { Authorization: `Bearer ${token}` },
    });
    revalidatePath('/', 'layout');
  } catch {
    // best-effort
  }
}
