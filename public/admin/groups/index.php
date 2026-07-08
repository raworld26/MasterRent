<?php
declare(strict_types=1);
require_once __DIR__ . "/../../../includes/bootstrap.php";
require_login();
require_service("admin.groups.index");
$items = groups_all();
$canManage = has_service("admin.groups.manage");
$html = "<div class=\"admin-toolbar\"><h1>Gruppi utente</h1>";
if ($canManage) {
    $html .= "<a class=\"button-primary\" href=\"" . e(url_for("admin/groups/create.php")) . "\">+ Nuovo gruppo</a>";
}
$html .= "</div>";
$html .= "<table class=\"data-table\"><thead><tr><th>Nome</th><th>Codice</th><th>Membri</th><th>Sistema</th><th>Azioni</th></tr></thead><tbody>";
foreach ($items as $item) {
    $actions = "";
    if ($canManage) {
        $actions = "<a class=\"button-small button-secondary\" href=\"" . e(url_for("admin/groups/create.php?id=" . $item["id"])) . "\">Modifica</a> ";
        if (!(int)$item["is_system"]) {
            $actions .= "<form method=\"post\" action=\"" . e(url_for("admin/groups/delete.php")) . "\" class=\"inline-form\" onsubmit=\"return confirm('Eliminare questo gruppo?')\">"
                . csrf_field("delete_group") . "<input type=\"hidden\" name=\"id\" value=\"" . (int) $item["id"] . "\">"
                . "<button type=\"submit\" class=\"button-small button-danger\">Elimina</button></form>";
        }
    }
    $sysBadge = (int)$item["is_system"] ? "<span class=\"status-pill\">Sì</span>" : "<span class=\"muted\">No</span>";
    $html .= "<tr><td>" . e($item["name"]) . "</td><td><code>" . e($item["code"]) . "</code></td><td>" . (int)$item["member_count"] . "</td><td>" . $sysBadge . "</td><td>" . $actions . "</td></tr>";
}
if ($items === []) {
    $html .= "<tr><td colspan=\"5\" class=\"muted\">Nessun gruppo presente.</td></tr>";
}
$html .= "</tbody></table>";
render_admin_page("Gruppi utente", $html, "admin.groups.index");
