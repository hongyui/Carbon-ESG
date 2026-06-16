export interface User {
  id: number;
  name: string;
  email: string;
  /** Granted via users.role === 'admin'. Optional because legacy
   *  payloads (older sessions, register/login responses) may omit it. */
  isAdmin?: boolean;
  /** Inferred from existence of carbon_listings rows for this user. */
  isSeller?: boolean;
  /** Inferred from existence of carbon_purchases rows for this user. */
  hasPurchased?: boolean;
  /** Inferred from existence of an approved worker_applications row. */
  isWorker?: boolean;
}
