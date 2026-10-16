# Security regression checks

Run from the repository root with PHP CLI:

```sh
php tests/security/callback_lock.php
```

The callback suite runs the real response handler with strict CS-Cart and database
doubles. It checks locking before reads, contending callbacks, paid duplicates,
parent-order handling, timeout/NULL fail-closed behavior, and release after normal
processing, exceptions and exit-based redirects. It is not a real MySQL or
CS-Cart integration test. `APS_ADDON_DIR` can select a baseline add-on directory;
the unpatched Release-Feature handler fails the lock-before-read assertion.

## dba3f03d-0792-4867-8459-2a613fd816f7

Both return and notify previously read the unpaid status without serialization.
A database/store-scoped MySQL named lock now covers the status read, gateway
processing, token saving and order completion. Contention waits up to five
seconds; timeout or NULL returns HTTP 503 with Retry-After, without reading or
modifying the order. The existing paid-state check runs inside the lock.
Shutdown cleanup covers CS-Cart/gateway exit paths; finally covers exceptions.

Before release, test parallel signed notify/return callbacks on the supported
CS-Cart and MySQL/MariaDB versions, including parent orders and 3DS. Confirm one
completion/stock transition and one token insertion, a later paid duplicate is
ignored, and callbacks retry successfully after 503. GET_LOCK and RELEASE_LOCK
must use the same primary database connection (no routing of these SELECTs to
replicas, no connection multiplexing while a lock is held). This serializes
these two callback paths, not unrelated cron/admin order updates, and does not
claim crash-safe exactly-once processing of external side effects.

## 7613f9e8-5dc2-4c6f-8805-cbcea4e861d3

Run `php tests/security/logger.php`. Debug logging accepts only boolean true or
CS-Cart's explicit `Y`. Disabled logging performs no directory creation or write.
Enabled logging retains a small allowlist of validated diagnostic fields and
fixed event/gateway labels. Nested arrays and JSON are sanitized; arbitrary text,
URLs, objects, unknown fields and malformed JSON are redacted. Payment request
arrays are not modified. Recursion is bounded. New log directories use 0750.

This deliberately removes raw response messages and request URLs from debug
output. It does not erase historical logs, backups or copies in log aggregators.
Before release, verify admin log viewing and all gateway debug modes in CS-Cart.
Handle historical sensitive logs and credential exposure through the approved
security/retention process; this code change does not remediate old copies.

## c11114cd-d40b-446c-9163-58cdfd8b62b7

Run `php tests/security/token_ownership.php`. The suite uses real Gateway, Cc and
Installments classes, with database lookups and HTTP calls replaced by local
doubles. Cross-customer, unknown, case-mismatched and malformed tokens, and guest
saved-card usage, are rejected before signing a request or calling APS. Both
`processRequest` and `tokenRequest` enforce ownership. Failed credit-card and
installments actions do not execute HTTP calls. An empty token radio value still
selects a new card, matching the shipped template.

Fresh new-card tokenization is distinct from saved-card reuse: it requires a
valid APS response signature, tokenization success code/status, the persisted
order reference and matching merchant configuration. Existing token ownership
must not conflict. It is validated before token saving or installment response
hooks. Callback customer identity comes from the stored order, not request
metadata. Guest and remember-me-off new-card flows remain possible.

Before release, exercise real saved-card, guest/new-card, remember-me on/off,
installments (including embedded), 3DS and repayment paths against APS sandbox.
Verify APS tokenization callbacks carry the expected reference/configuration
fields and that CS-Cart order details contain aps_reference. Cross-customer
attempts must produce no outgoing charge request. No live charge or sandbox
transaction was made during local testing.
