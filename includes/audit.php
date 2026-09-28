<?php

/**
 * Audit log - a permanent, admin-visible trail of security-relevant
 * events: logins (success + failure), logouts, password changes and
 * resets, registrations, role/approval changes, deletions, and
 * denied access attempts (see require_role() in auth/auth_check.php).
 *
 * Writing to the audit log must NEVER break the page that triggered
 * it, so audit_log() swallows its own errors.
 */

/**
 * @param PDO|null    $pdo
 * @param int|null    $user_id      The acting user, if known/logged in.
 * @param string      $action       Short machine-readable tag, e.g.
 *                                  "login_success", "login_failed",
 *                                  "password_reset", "user_deleted".
 * @param string      $description  Human-readable detail for the log.
 * @param string|null $user_email   Denormalized snapshot of the email
 *                                  at the time of the event (useful
 *                                  for login_failed, where there may
 *                                  be no matching user_id at all, and
 *                                  for events on accounts that get
 *                                  deleted later).
 */
function audit_log($pdo, $user_id, $action, $description = "", $user_email = null)
{
    if (!($pdo instanceof PDO)) {
        return;
    }

    try {

        $stmt = $pdo->prepare(
            "INSERT INTO audit_logs
             (user_id, user_email, action, description, ip_address)
             VALUES (?, ?, ?, ?, ?)"
        );

        $stmt->execute([
            $user_id ?: null,
            $user_email !== null ? $user_email : ($_SESSION["user_email"] ?? null),
            substr($action, 0, 60),
            $description,
            function_exists("get_client_ip") ? get_client_ip() : null,
        ]);

    } catch (Throwable $e) {

        error_log("LearnHub audit_log: " . $e->getMessage());
    }
}


/**
 * Paginated audit log rows for admin/audit-logs.php, newest first.
 * Optional filters: action (exact match) and q (matches email or
 * description).
 */
function get_audit_logs($pdo, $page, $perPage, $filters = [])
{
    $page = max(1, (int) $page);
    $perPage = max(1, min(100, (int) $perPage));
    $offset = ($page - 1) * $perPage;

    $where = [];
    $params = [];

    if (!empty($filters["action"])) {
        $where[] = "action = ?";
        $params[] = $filters["action"];
    }

    if (!empty($filters["q"])) {
        $where[] = "(user_email LIKE ? OR description LIKE ?)";
        $like = "%" . $filters["q"] . "%";
        $params[] = $like;
        $params[] = $like;
    }

    $whereSql = $where ? ("WHERE " . implode(" AND ", $where)) : "";

    $countStmt = $pdo->prepare(
        "SELECT COUNT(*) AS total FROM audit_logs $whereSql"
    );
    $countStmt->execute($params);
    $total = (int) $countStmt->fetch()["total"];

    $stmt = $pdo->prepare(
        "SELECT id, user_id, user_email, action, description, ip_address, created_at
         FROM audit_logs
         $whereSql
         ORDER BY created_at DESC, id DESC
         LIMIT $perPage OFFSET $offset"
    );
    $stmt->execute($params);

    return [
        "rows"        => $stmt->fetchAll(),
        "total"       => $total,
        "total_pages" => max(1, (int) ceil($total / $perPage)),
    ];
}


/** Distinct action tags seen so far, for the filter dropdown. */
function get_audit_action_types($pdo)
{
    try {

        return $pdo->query(
            "SELECT DISTINCT action FROM audit_logs ORDER BY action"
        )->fetchAll(PDO::FETCH_COLUMN);

    } catch (Throwable $e) {

        return [];
    }
}
