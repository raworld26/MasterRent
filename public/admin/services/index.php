<?php
declare(strict_types=1);
require_once __DIR__ . "/../../../includes/bootstrap.php";
require_login();
require_service("admin.services.index");
$items = services_all_admin();
$canManage = has_service("admin.services.manage");
$html = "<div class=\"admin-toolbar\"><h1>Servizi</h1>";
if ($canManage) {
    $html .= "<a class=\"button-primary\" href=\"" . e(url_for("admin/services/create.php")) . "\">+ Nuovo servizio</a>";
}
$html .= "</div>";
$html .= "<table class=\"data-table\"><thead><tr><th>Nome</th><th>Codice</th><th>Area</th><th>Gruppi</th><th>Menu</th><th>Attivo</th><th>Azioni</th></tr></thead><tbody>";
foreach ($items as $item) {
    $actions = "";
    if ($canManage) {
        $actions = "<a class=\"button-small button-secondary\" href=\"" . e(url_for("admin/services/create.php?id=" . $item["id"])) . "\">Modifica</a> "
            . "<form method=\"post\" action=\"" . e(url_for("admin/services/delete.php")) . "\" class=\"inline-form\" onsubmit=\"return confirm('Eliminare questo servizio?')\">"
            . csrf_field("delete_service") . "<input type=\"hidden\" name=\"id\" value=\"" . (int) $item["id"] . "\">"
            . "<button type=\"submit\" class=\"button-small button-danger\">Elimina</button></form>";
    }
    $actBadge = (int)$item["is_active"] ? "<span class=\"status-pill\"><span class=\"dot dot-green\"></span> Sì</span>" : "<span class=\"muted\">No</span>";
    $menuBadge = (int)$item["is_menu_item"] ? "Sì" : "No";
    $html .= "<tr><td>" . e($item["name"]) . "</td><td><code>" . e($item["code"]) . "</code></td><td>" . e($item["area"]) . "</td><td>" . (int)$item["group_count"] . "</td><td>" . $menuBadge . "</td><td>" . $actBadge . "</td><td>" . $actions . "</td></tr>";
}
if ($items === []) {
    $html .= "<tr><td colspan=\"7\" class=\"muted\">Nessun servizio presente.</td></tr>";
}
$html .= "</tbody></table>";
render_admin_page("Servizi", $html, "admin.services.index");
