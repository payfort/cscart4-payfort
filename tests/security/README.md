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
