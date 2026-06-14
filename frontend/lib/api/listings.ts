/**
 * Client-side typed wrapper for the carbon-listings API.
 *
 * For server components, fetch directly using next/headers cookies
 * (see lib/session/server.ts for the pattern); this module's axios
 * client only carries credentials inside the browser.
 */

import { api } from '@/lib/api';

export type ListingStatus =
  | 'pending'
  | 'approved'
  | 'rejected'
  | 'recalled'
  | 'sold';

export interface CarbonListing {
  id: number;
  user_id: number;
  title: string;
  description: string;
  hectares: string;
  tonnes_co2e: string;
  location: string;
  price_twd: string;
  status: ListingStatus;
  admin_note: string | null;
  approved_by: number | null;
  approved_at: string | null;
  created_at: string;
  updated_at: string;
}

export interface CarbonPurchase {
  id: number;
  carbon_listing_id: number;
  buyer_id: number;
  price_twd: string;
  created_at: string;
  updated_at: string;
  carbon_listing?: CarbonListing;
}

export interface CreateListingInput {
  title: string;
  description: string;
  hectares: number;
  tonnes_co2e: number;
  location: string;
  price_twd: number;
}

export interface Paginated<T> {
  data: T[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  from: number | null;
  to: number | null;
}

/* ─── Seller endpoints ─── */

export async function createListing(input: CreateListingInput): Promise<CarbonListing> {
  const { data } = await api.post<{ listing: CarbonListing }>(
    '/api/carbon-listings',
    input,
  );
  return data.listing;
}

export async function getMyListings(): Promise<CarbonListing[]> {
  const { data } = await api.get<{ listings: CarbonListing[] }>(
    '/api/carbon-listings/mine',
  );
  return data.listings;
}

export async function getListing(id: number): Promise<CarbonListing> {
  const { data } = await api.get<{ listing: CarbonListing }>(
    `/api/carbon-listings/${id}`,
  );
  return data.listing;
}

export async function recallListing(id: number): Promise<CarbonListing> {
  const { data } = await api.post<{ listing: CarbonListing }>(
    `/api/carbon-listings/${id}/recall`,
  );
  return data.listing;
}

/* ─── Buyer endpoints ─── */

export async function getMarket(page = 1): Promise<Paginated<CarbonListing>> {
  const { data } = await api.get<Paginated<CarbonListing>>(
    '/api/carbon-listings',
    { params: { page } },
  );
  return data;
}

export async function purchaseListing(id: number): Promise<CarbonPurchase> {
  const { data } = await api.post<{ purchase: CarbonPurchase }>(
    `/api/carbon-listings/${id}/purchase`,
  );
  return data.purchase;
}

export async function getMyPurchases(page = 1): Promise<Paginated<CarbonPurchase>> {
  const { data } = await api.get<Paginated<CarbonPurchase>>('/api/purchases', {
    params: { page },
  });
  return data;
}

/* ─── Admin endpoints ─── */

export async function getPendingQueue(page = 1): Promise<Paginated<CarbonListing>> {
  const { data } = await api.get<Paginated<CarbonListing>>(
    '/api/admin/carbon-listings/pending',
    { params: { page } },
  );
  return data;
}

export async function adminApprove(id: number): Promise<CarbonListing> {
  const { data } = await api.post<{ listing: CarbonListing }>(
    `/api/admin/carbon-listings/${id}/approve`,
  );
  return data.listing;
}

export async function adminReject(
  id: number,
  reason?: string,
): Promise<CarbonListing> {
  const { data } = await api.post<{ listing: CarbonListing }>(
    `/api/admin/carbon-listings/${id}/reject`,
    { reason },
  );
  return data.listing;
}
