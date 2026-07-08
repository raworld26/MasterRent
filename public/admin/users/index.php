<?php
declare(strict_types=1);
require_once __DIR__ . "/../../../includes/bootstrap.php";
require_login();
require_service("admin.users.index");
$items = users_all_admin();
$canManage = has_service("admin.users.manage");
$html = "<div class=\"admin-toolbar\"><h1>Utenti</h1>";
if ($canManage) {
    $html .= "<a class=\"button-primary\" href=\"" . e(url_for("admin/users/create.php")) . "\">+ Nuovo utente</a>";
}
$html .= "</div>";
$html .= "<table class=\"data-table\"><thead><tr><th>Nome</th><th>Email</th><th>Stato</th><th>Gruppi</th><th>Azioni</th></tr></thead><tbody>";
foreach ($items as $item) {
    $actions = "";
    if ($canManage) {
        $actions = "<a class=\"button-small button-secondary\" href=\"" . e(url_for("admin/users/create.php?id=" . $item["id"])) . "\">Modifica</a> ";
        if ((int)$item["id"] !== (int)($_SESSION["user_id"] ?? 0)) {
            $actions .= "<form method=\"post\" action=\"" . e(url_for("admin/users/delete.php")) . "\" class=\"inline-form\" onsubmit=\"return confirm('Eliminare o sospendere questo utente?')\">"
                . csrf_field("delete_user") . "<input type=\"hidden\" name=\"id\" value=\"" . (int) $item["id"] . "\">"
                . "<button type=\"submit\" class=\"button-small button-danger\">Elimina</button></form>";
        }
    }
    $statusBadge = $item["status"] === "active" ? "<span class=\"status-pill\"><span class=\"dot dot-green\"></span> Attivo</span>" : "<span class=\"status-pill\"><span class=\"dot dot-red\"></span> Sospeso</span>";
    $html .= "<tr><td>" . e($item["first_name"] . " " . $item["last_name"]) . "</td><td><code>" . e($item["email"]) . "</code></td><td>" . $statusBadge . "</td><td>" . e((string)($item["groups"] ?? "")) . "</td><td>" . $actions . "</td></tr>";
}
if ($items === []) {
    $html .= "<tr><td colspan=\"5\" class=\"muted\">Nessun utente presente.</td></tr>";
}
$html .= "</tbody></table>";
render_admin_page("Utenti", $html, "admin.users.index");
