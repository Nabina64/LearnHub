<?php

require_once "../auth/auth_check.php";
require_once "../config/database.php";
require_once "../includes/functions.php";


/* Admin Only */

require_role("admin", "../index.php");


$pageTitle = "Audit Logs - LearnHub";

$page = max(1, intval($_GET["page"] ?? 1));
$perPage = 40;

$filters = [
    "action" => trim($_GET["action"] ?? ""),
    "q"      => trim($_GET["q"] ?? ""),
];

$result = get_audit_logs($pdo, $page, $perPage, $filters);
$logs = $result["rows"];
$totalPages = $result["total_pages"];

$actionTypes = get_audit_action_types($pdo);

$queryParams = array_filter([
    "action" => $filters["action"],
    "q"      => $filters["q"],
]);

/** Small color hint so failures/deletions stand out at a glance. */
function audit_badge_class($action)
{
    if (strpos($action, "failed") !== false || strpos($action, "locked") !== false || strpos($action, "denied") !== false) {
        return "bg-danger";
    }

    if (strpos($action, "deleted") !== false || strpos($action, "rejected") !== false) {
        return "bg-warning text-dark";
    }

    if (strpos($action, "success") !== false || strpos($action, "verified") !== false || strpos($action, "approved") !== false || strpos($action, "restored") !== false) {
        return "bg-success";
    }

    return "bg-secondary";
}

require_once "../includes/header.php";
require_once "../includes/navbar.php";

?>

<div class="container py-5">

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">

        <div>
            <h2 class="fw-bold">
                &#128220; Audit Logs
            </h2>
            <p class="text-muted mb-0">
                A permanent record of logins, security events and
                destructive admin actions across the site.
            </p>
        </div>

        <a href="dashboard.php" class="btn btn-outline-secondary">
            &larr; Back to Dashboard
        </a>

    </div>


    <!-- Filters -->

    <form method="GET" class="row g-2 mb-4">

        <div class="col-md-4">
            <input
                type="text"
                name="q"
                class="form-control"
                placeholder="Search email or description..."
                value="<?php echo htmlspecialchars($filters["q"]); ?>"
            >
        </div>

        <div class="col-md-3">
            <select name="action" class="form-select">
                <option value="">All actions</option>
                <?php foreach ($actionTypes as $type): ?>
                    <option
                        value="<?php echo htmlspecialchars($type); ?>"
                        <?php echo $type === $filters["action"] ? "selected" : ""; ?>
                    >
                        <?php echo htmlspecialchars($type); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-2">
            <button type="submit" class="btn btn-primary w-100">
                Filter
            </button>
        </div>

        <?php if ($filters["action"] !== "" || $filters["q"] !== ""): ?>
            <div class="col-md-2">
                <a href="audit-logs.php" class="btn btn-outline-secondary w-100">
                    Clear
                </a>
            </div>
        <?php endif; ?>

    </form>


    <?php if (count($logs) > 0): ?>

        <div class="table-responsive">

            <table class="table table-bordered table-hover bg-white align-middle">

                <thead class="table-dark">
                    <tr>
                        <th>When</th>
                        <th>Action</th>
                        <th>User</th>
                        <th>IP Address</th>
                        <th>Details</th>
                    </tr>
                </thead>

                <tbody>

                    <?php foreach ($logs as $log): ?>

                        <tr>
                            <td class="text-nowrap">
                                <?php echo date("M d, Y g:i A", strtotime($log["created_at"])); ?>
                            </td>

                            <td>
                                <span class="badge <?php echo audit_badge_class($log["action"]); ?>">
                                    <?php echo htmlspecialchars($log["action"]); ?>
                                </span>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($log["user_email"] ?? "-"); ?>
                            </td>

                            <td>
                                <code><?php echo htmlspecialchars($log["ip_address"] ?? "-"); ?></code>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($log["description"] ?? ""); ?>
                            </td>
                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        </div>

        <?php render_pagination($page, $totalPages, $queryParams); ?>

    <?php else: ?>

        <div class="alert alert-info">
            No audit log entries match these filters.
        </div>

    <?php endif; ?>

</div>

<?php require_once "../includes/footer.php"; ?>
