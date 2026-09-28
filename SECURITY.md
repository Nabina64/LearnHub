# LearnHub - Security Upgrade

This covers the second round of security work, on top of what was
already in place (HTTPS/session hardening, CSRF, prepared statements,
output escaping, etc.).

## 0. Setup (do this first)

Nothing to do for most installs - `includes/schema.php` adds the new
columns/tables automatically on the next page load, and grandfathers
in every existing user as email-verified so nobody gets locked out.

If your database user does not have `ALTER`/`CREATE` permission, run
this once by hand instead (phpMyAdmin -> `online_learning` -> SQL tab):

```
database/security_upgrade.sql
```

Also add one new line to your `.env` (see `.env.example`):

```
APP_URL=https://yourdomain.com/online-learning-platform
```

This is used to build the links inside verification/reset emails.
If you skip it, the app guesses it from the current request, which
is fine for local development but not reliable in production
(cron jobs, queued email, etc. have no "current request").

---

## 1. Login rate limiting

Login attempts are throttled two ways at once:

- **Per account** - 5 failed attempts in 15 minutes locks that email
  address out for 15 minutes.
- **Per IP address** - 20 failed attempts in 15 minutes (across any
  emails) locks that IP out for 15 minutes.

This stops both "guess one person's password over and over" and
"spray one common password across many accounts" attacks. A correct
password always gets through regardless of the IP counter.

Implementation: `includes/security.php` (`rate_limit_hit()` /
`rate_limit_count()`), a generic `bucket -> timestamp` table
(`rate_limit_hits`) reused by password reset and verification-resend
too. Applied in `auth/login.php`.

---

## 2. Forgot password

- `auth/forgot-password.php` - enter your email, get a reset link.
- `auth/reset-password.php` - the link from that email, sets a new password.

Notes:

- The confirmation message is identical whether or not the email is
  registered, so this page can't be used to find out who has an
  account.
- The link contains a random 64-character token; only its SHA-256
  hash is stored in the database (`users.reset_token_hash`), so a
  database leak alone can't be used to reset anyone's password.
- Links expire after **1 hour** and are single-use (cleared as soon
  as they're used).
- Requests are rate-limited (3/hour per email, 10/hour per IP).

---

## 3. Email verification

New registrations must click a link emailed to them before they can
login (`users.email_verified_at`). Teachers still separately need
admin approval on top of that - both checks apply.

- `auth/verify-email.php` - the link from the registration email.
- `auth/resend-verification.php` - request a new one (same
  "same message either way" + rate-limiting as forgot-password).
- The login page shows a "Resend verification email" link
  automatically when someone tries to login before verifying.

**Existing users are unaffected** - the schema upgrade marks every
account that existed before this change as already verified.

---

## 4. Centralized role authorization

Every admin/teacher/student-only page used to repeat its own
`if ($_SESSION['user_role'] !== '...') { redirect(...); }` check -
easy to typo or forget on a new page. All ~60 of those are now one
call to a single, audited function:

```php
require_role("admin");
require_role("teacher", "../index.php");
require_role(["admin", "teacher"]);
```

Defined once in `auth/auth_check.php`. Every denied attempt is
written to the audit log automatically.

---

## 5. Security headers

`includes/security.php` -> `send_security_headers()`, called on
every single request from `includes/session.php`, before anything
else runs:

- `Content-Security-Policy` (locked down to the CDNs/video embeds
  the app actually uses)
- `X-Frame-Options: SAMEORIGIN` (clickjacking)
- `X-Content-Type-Options: nosniff`
- `Referrer-Policy: strict-origin-when-cross-origin`
- `Permissions-Policy` (camera/mic/geolocation/payment all disabled)
- `Strict-Transport-Security` (only once the site is actually served
  over HTTPS - see `FORCE_HTTPS` in `.env`)

---

## 6. Better password policy

One rule, defined once (`validate_password_strength()` in
`includes/security.php`), used everywhere a password is set:
registration, "change password" in account settings, and password
reset.

- At least 8 characters (max 72 - bcrypt ignores anything past that)
- At least one uppercase, one lowercase, one number, one special character
- Rejects a short list of extremely common passwords

---

## 7. Upload directory protection

`uploads/.htaccess` already blocked PHP execution in that folder;
it now also:

- disables directory listing (`Options -Indexes`)
- blocks double-extension tricks like `shell.php.jpg`

New `.htaccess` files also stop `/config`, `/includes` and
`/database` from ever being requested directly (those folders are
only ever meant to be `require()`'d by other PHP files, and
`/database` holds raw `.sql` dumps of the schema). A root
`.htaccess` blocks direct access to dotfiles (`.env` especially)
and stray `.sql`/`.md`/`.log` files.

---

## 8. Audit logs

**Admin -> Audit Logs** (`admin/audit-logs.php`) shows a searchable,
filterable, paginated trail of:

- `login_success` / `login_failed` / `login_locked`
- `registered`, `email_verified`, `verification_resent`
- `password_changed`, `password_reset_requested`, `password_reset`
- `access_denied` (someone tried to open a page their role doesn't allow)
- `teacher_approved` / `teacher_rejected`
- `user_deleted` / `user_deleted_forever` / `user_restored`
- `course_deleted` / `course_deleted_forever` / `course_restored`
- `account_deleted` (self-service), `logout`

Each row has a timestamp, the acting user's email (if any), their IP
address, and a plain-English description. Implementation:
`includes/audit.php` (`audit_log()`), table `audit_logs`. Writing to
the log can never break the page that triggered it - failures are
only written to the PHP error log.
