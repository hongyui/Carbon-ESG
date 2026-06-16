## MODIFIED Requirements

### Requirement: Current-Session Endpoint

The backend SHALL expose `GET /api/me` (under `auth:sanctum`) returning the currently authenticated user **plus four boolean role flags** that the frontend uses to render role-aware navigation without additional round trips. The flags are computed per request from `User::isAdmin()`, `User::isSeller()`, `User::hasPurchased()`, and `User::isWorker()` (the fourth one is defined in the `jobs` capability).

The response shape is:

```json
{
  "user": {
    "id": 1,
    "name": "...",
    "email": "...",
    "isAdmin": false,
    "isSeller": true,
    "hasPurchased": false,
    "isWorker": false
  }
}
```

This is a backward-compatible extension of the phase-2 shape. Existing client code that reads only `user.id` / `user.name` / `user.email` continues to work unchanged; clients that read the existing three flags continue to work unchanged; clients that read `isWorker` get the new worker information for free.

#### Scenario: Authenticated request returns user resource with role flags
- **WHEN** an authenticated client GETs `/api/me`
- **THEN** the response is 200 with `{ user: { id, name, email, isAdmin, isSeller, hasPurchased, isWorker } }`. The `isAdmin` flag reflects the `users.role === 'admin'` check; `isSeller` is true iff the user has at least one row in `carbon_listings`; `hasPurchased` is true iff the user has at least one row in `carbon_purchases`; `isWorker` is true iff the user has at least one row in `worker_applications` with `status = 'approved'`. Password and remember token fields remain excluded

#### Scenario: Anonymous request returns 401
- **WHEN** no session cookie is present (or the session has expired)
- **THEN** the response is 401 with an empty body; the frontend axios interceptor relies on this status to trigger a redirect to `/login`

#### Scenario: Role flags reflect current state, not cached state
- **GIVEN** a user who creates their first carbon listing during the session
- **WHEN** the frontend re-fetches `/api/me` after the create succeeds
- **THEN** `user.isSeller` is now `true` (it was `false` on the prior call). The backend re-computes flags on each request; there is no per-session cache that could go stale

#### Scenario: isWorker flips when admin approves the application
- **GIVEN** a user whose `WorkerApplication.status` was `pending` on their last `GET /api/me`, and an admin who just approved the application in a different session
- **WHEN** the user re-fetches `GET /api/me`
- **THEN** `user.isWorker` is now `true`. The flag is computed from a fresh `EXISTS` query on `worker_applications` per request, so no relogin or token refresh is needed
