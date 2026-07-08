<?php
declare(strict_types=1);
require_once __DIR__ . "/../../../includes/bootstrap.php";
require_login();
require_service("admin.properties.index");
$items = properties_for_admin();
$canManage = has_service("admin.properties.manage");
$html = "<div class=\"admin-toolbar\"><h1>Annunci</h1>";
if ($canManage) {
    $html .= "<a class=\"button-primary\" href=\"" . e(url_for("admin/properties/form.php")) . "\">+ Nuovo annuncio</a>";
}
$html .= "</div>";
$html .= "<table class=\"data-table\"><thead><tr><th>Titolo</th><th>Quartiere</th><th>Proprietario</th><th>Stanze</th><th>Azioni</th></tr></thead><tbody>";
foreach ($items as $item) {
    $actions = "<a class=\"button-small button-primary\" href=\"" . e(url_for("admin/properties/view.php?id=" . $item["id"])) . "\">Dettaglio</a> ";
    if ($canManage) {
        $actions .= "<a class=\"button-small button-secondary\" href=\"" . e(url_for("admin/properties/form.php?id=" . $item["id"])) . "\">Modifica</a> "
            . "<form method=\"post\" action=\"" . e(url_for("admin/properties/delete.php")) . "\" class=\"inline-form\" onsubmit=\"return confirm('Eliminare questo annuncio e tutte le sue stanze?')\">"
            . csrf_field("delete_property") . "<input type=\"hidden\" name=\"id\" value=\"" . (int) $item["id"] . "\">"
            . "<button type=\"submit\" class=\"button-small button-danger\">Elimina</button></form>";
    }
    $html .= "<tr><td>" . e($item["title"]) . "</td><td>" . e($item["neighborhood_name"]) . "</td><td>" . e($item["landlord_name"]) . "</td><td>" . (int)$item["room_count"] . "</td><td>" . $actions . "</td></tr>";
}
if ($items === []) {
    $html .= "<tr><td colspan=\"5\" class=\"muted\">Nessun annuncio presente.</td></tr>";
}
$html .= "</tbody></table>";
render_admin_page("Annunci", $html, "admin.properties.index");
