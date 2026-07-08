<?php
declare(strict_types=1);
require_once __DIR__ . "/../../../includes/bootstrap.php";
require_login();
require_service("admin.poles.index");
$items = poles_all();
$canManage = has_service("admin.poles.manage");
$html = "<div class=\"admin-toolbar\"><h1>Poli universitari</h1>";
if ($canManage) {
    $html .= "<a class=\"button-primary\" href=\"" . e(url_for("admin/poles/create.php")) . "\">+ Nuovo polo</a>";
}
$html .= "</div>";
$html .= "<table class=\"data-table\"><thead><tr><th>Nome</th><th>Codice</th><th>Descrizione</th><th>Azioni</th></tr></thead><tbody>";
foreach ($items as $item) {
    $actions = "";
    if ($canManage) {
        $actions = "<a class=\"button-small button-secondary\" href=\"" . e(url_for("admin/poles/create.php?id=" . $item["id"])) . "\">Modifica</a> "
            . "<form method=\"post\" action=\"" . e(url_for("admin/poles/delete.php")) . "\" class=\"inline-form\" onsubmit=\"return confirm('Eliminare questo polo?')\">"
            . csrf_field("delete_pole") . "<input type=\"hidden\" name=\"id\" value=\"" . (int) $item["id"] . "\">"
            . "<button type=\"submit\" class=\"button-small button-danger\">Elimina</button></form>";
    }
    $html .= "<tr><td>" . e($item["name"]) . "</td><td><code>" . e($item["code"]) . "</code></td><td>" . e(excerpt((string) ($item["description"] ?? ""), 80)) . "</td><td>" . $actions . "</td></tr>";
}
if ($items === []) {
    $html .= "<tr><td colspan=\"4\" class=\"muted\">Nessun polo presente.</td></tr>";
}
$html .= "</tbody></table>";
render_admin_page("Poli universitari", $html, "admin.poles.index");
