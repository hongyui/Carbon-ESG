/**
 * Client-side typed wrapper for the worker API:
 * applications + jobs + job reports + admin review.
 *
 * For server components, fetch via serverGet from lib/api/server.ts.
 */

import { api } from '@/lib/api';
import type { CarbonListing, Paginated } from '@/lib/api/listings';

export type WorkerApplicationStatus = 'pending' | 'approved' | 'rejected';
export type WorkerJobStatus = 'open' | 'claimed' | 'reported' | 'approved';
export type WorkerJobReportStatus = 'pending' | 'approved' | 'rejected';

export interface WorkerApplication {
  id: number;
  user_id: number;
  reason: string;
  has_experience: boolean;
  age: number;
  residence: string;
  contact: string;
  status: WorkerApplicationStatus;
  reviewer_id: number | null;
  review_reason: string | null;
  reviewed_at: string | null;
  created_at: string;
  updated_at: string;
  user?: { id: number; name: string; email: string };
}

export interface WorkerJob {
  id: number;
  carbon_listing_id: number;
  worker_id: number | null;
  status: WorkerJobStatus;
  claimed_at: string | null;
  created_at: string;
  updated_at: string;
  carbon_listing?: CarbonListing;
  report?: WorkerJobReport | null;
}

export interface WorkerJobReport {
  id: number;
  worker_job_id: number;
  worker_id: number;
  datetime_start: string;
  datetime_end: string;
  before_image_path: string;
  after_image_path: string;
  content: string;
  status: WorkerJobReportStatus;
  reviewer_id: number | null;
  review_reason: string | null;
  reviewed_at: string | null;
  created_at: string;
  updated_at: string;
  worker_job?: WorkerJob;
  worker?: { id: number; name: string; email: string };
}

export interface CreateApplicationInput {
  reason: string;
  has_experience: boolean;
  age: number;
  residence: string;
  contact: string;
}

/* ─── Worker application ─── */

export async function createApplication(
  input: CreateApplicationInput,
): Promise<WorkerApplication> {
  const { data } = await api.post<{ application: WorkerApplication }>(
    '/api/worker-applications',
    input,
  );
  return data.application;
}

export async function getMyApplication(): Promise<WorkerApplication | null> {
  try {
    const { data } = await api.get<{ application: WorkerApplication }>(
      '/api/worker-applications/mine',
    );
    return data.application;
  } catch (err) {
    if ((err as { response?: { status?: number } }).response?.status === 404) {
      return null;
    }
    throw err;
  }
}

/* ─── Worker jobs ─── */

export async function getOpenJobs(page = 1): Promise<Paginated<WorkerJob>> {
  const { data } = await api.get<Paginated<WorkerJob>>(
    '/api/worker-jobs/open',
    { params: { page } },
  );
  return data;
}

export async function getMyJobs(page = 1): Promise<Paginated<WorkerJob>> {
  const { data } = await api.get<Paginated<WorkerJob>>(
    '/api/worker-jobs/mine',
    { params: { page } },
  );
  return data;
}

export async function getJob(id: number): Promise<WorkerJob> {
  const { data } = await api.get<{ job: WorkerJob }>(`/api/worker-jobs/${id}`);
  return data.job;
}

export async function claimJob(id: number): Promise<WorkerJob> {
  const { data } = await api.post<{ job: WorkerJob }>(
    `/api/worker-jobs/${id}/claim`,
  );
  return data.job;
}

export async function submitReport(
  id: number,
  payload: {
    datetime_start: string;
    datetime_end: string;
    content: string;
    before_image: File;
    after_image: File;
  },
): Promise<WorkerJobReport> {
  const fd = new FormData();
  fd.append('datetime_start', payload.datetime_start);
  fd.append('datetime_end', payload.datetime_end);
  fd.append('content', payload.content);
  fd.append('before_image', payload.before_image);
  fd.append('after_image', payload.after_image);

  const { data } = await api.post<{ report: WorkerJobReport }>(
    `/api/worker-jobs/${id}/report`,
    fd,
    { headers: { 'Content-Type': 'multipart/form-data' } },
  );
  return data.report;
}

/* ─── Admin endpoints ─── */

export async function getPendingApplications(
  page = 1,
): Promise<Paginated<WorkerApplication>> {
  const { data } = await api.get<Paginated<WorkerApplication>>(
    '/api/admin/worker-applications/pending',
    { params: { page } },
  );
  return data;
}

export async function adminApproveApplication(
  id: number,
): Promise<WorkerApplication> {
  const { data } = await api.post<{ application: WorkerApplication }>(
    `/api/admin/worker-applications/${id}/approve`,
  );
  return data.application;
}

export async function adminRejectApplication(
  id: number,
  reason?: string,
): Promise<WorkerApplication> {
  const { data } = await api.post<{ application: WorkerApplication }>(
    `/api/admin/worker-applications/${id}/reject`,
    { reason },
  );
  return data.application;
}

export async function getPendingReports(
  page = 1,
): Promise<Paginated<WorkerJobReport>> {
  const { data } = await api.get<Paginated<WorkerJobReport>>(
    '/api/admin/job-reports/pending',
    { params: { page } },
  );
  return data;
}

export async function adminApproveReport(
  id: number,
): Promise<WorkerJobReport> {
  const { data } = await api.post<{ report: WorkerJobReport }>(
    `/api/admin/job-reports/${id}/approve`,
  );
  return data.report;
}

export async function adminRejectReport(
  id: number,
  reason?: string,
): Promise<WorkerJobReport> {
  const { data } = await api.post<{ report: WorkerJobReport }>(
    `/api/admin/job-reports/${id}/reject`,
    { reason },
  );
  return data.report;
}
