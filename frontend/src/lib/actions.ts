'use server';

import { submitEnquiry, submitOpportunity, EnquiryInput, OpportunitySubmissionInput } from './api';
import { OpportunityCategory } from '@/types/opportunity';

export interface EnquiryFormState {
  status: 'idle' | 'success' | 'error';
  message?: string;
}

export async function submitEnquiryAction(
  opportunityId: number,
  _prevState: EnquiryFormState,
  formData: FormData
): Promise<EnquiryFormState> {
  const name = String(formData.get('enquirer_name') || '').trim();
  const email = String(formData.get('enquirer_email') || '').trim();
  const company = String(formData.get('enquirer_company') || '').trim();
  const message = String(formData.get('message') || '').trim();

  if (!name || !email) {
    return { status: 'error', message: 'Name and email are required.' };
  }

  const input: EnquiryInput = {
    enquirer_name: name,
    enquirer_email: email,
    enquirer_company: company || undefined,
    type: 'introduction_request',
    message: message || undefined,
  };

  try {
    await submitEnquiry(opportunityId, input);
    return { status: 'success', message: "Request sent. GiMtradefin will review and facilitate the introduction." };
  } catch {
    return { status: 'error', message: 'Could not submit your request right now. Please try again shortly.' };
  }
}

export interface SubmitOpportunityFormState {
  status: 'idle' | 'success' | 'error';
  message?: string;
}

export async function submitOpportunityAction(
  _prevState: SubmitOpportunityFormState,
  formData: FormData
): Promise<SubmitOpportunityFormState> {
  // Honeypot check first, before touching anything else — bots that fill
  // every field still get silently rejected here.
  const honeypot = String(formData.get('website') || '');
  if (honeypot.length > 0) {
    // Report fake success so a bot doesn't learn the honeypot was hit.
    return { status: 'success', message: 'Submitted for review.' };
  }

  const title = String(formData.get('title') || '').trim();
  const category = String(formData.get('category') || '').trim() as OpportunityCategory;
  const description = String(formData.get('description') || '').trim();
  const country = String(formData.get('country') || '').trim();
  const submitterName = String(formData.get('submitted_by_name') || '').trim();
  const submitterEmail = String(formData.get('submitted_by_email') || '').trim();

  if (!title || !category || !description || !country || !submitterName || !submitterEmail) {
    return { status: 'error', message: 'Please fill in all required fields.' };
  }

  const input: OpportunitySubmissionInput = {
    title,
    category,
    description,
    country,
    quantity: String(formData.get('quantity') || '').trim() || undefined,
    preferred_origin: String(formData.get('preferred_origin') || '').trim() || undefined,
    payment_terms: String(formData.get('payment_terms') || '').trim() || undefined,
    submitted_by_name: submitterName,
    submitted_by_email: submitterEmail,
    submitted_by_company: String(formData.get('submitted_by_company') || '').trim() || undefined,
    submitted_by_phone: String(formData.get('submitted_by_phone') || '').trim() || undefined,
  };

  try {
    const result = await submitOpportunity(input);
    return { status: 'success', message: result.message };
  } catch (err) {
    return {
      status: 'error',
      message: err instanceof Error ? err.message : 'Could not submit right now. Please try again shortly.',
    };
  }
}
