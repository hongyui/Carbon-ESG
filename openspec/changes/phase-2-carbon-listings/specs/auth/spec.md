## MODIFIED Requirements

### Requirement: Current-Session Endpoint

The backend SHALL expose `GET /api/me` (under `auth:sanctum`) returning the currently authenticated user **plus three boolean role flags** that the frontend uses to render role-aware navigation without additional round trips. The flags are computed per request from `User::isAdmin()`, `User::isSeller()`, and `User::hasPurchased()` (defined in the `carbon-listings` capability).

The response shape is now:

```json
{
  "user": {
    "id": 1,
    "name": "...",
    "email": "...",
    "isAdmin": false,
    "isSeller": true,
    "hasPurchased": false
  }
}
```

This is a backward-compatible extension. Existing client code that reads only `user.id` / `user.name` / `user.email` continues to work unchanged; clients that read the flags get role information for free on every render.

#### Scenario: Authenticated request returns user resource with role flags
- **WHEN** an authenticated client GETs `/api/me`
- **THEN** the response is 200 with `{ user: { id, name, email, isAdmin, isSeller, hasPurchased } }`. The `isAdmin` flag reflects the `users.role === 'admin'` check; `isSeller` is true iff the user has at least one row in `carbon_listings`; `hasPurchased` is true iff the user has at least one row in `carbon_purchases`. Password and remember token fields remain excluded

#### Scenario: Anonymous request returns 401
- **WHEN** no session cookie is present (or the session has expired)
- **THEN** the response is 401 with an empty body; the frontend axios interceptor relies on this status to trigger a redirect to `/login`. (Unchanged from phase-1.)

#### Scenario: Role flags reflect current state, not cached state
- **GIVEN** a user who creates their first carbon listing during the session
- **WHEN** the frontend re-fetches `/api/me` after the create succeeds
- **THEN** `user.isSeller` is now `true` (it was `false` on the prior call). The backend re-computes flags on each request; there is no per-session cache that could go stale
