<?php
declare(strict_types=1);
require_once __DIR__ . "/../../../includes/bootstrap.php";
require_login();
require_service("admin.reviews.index");
$items = reviews_all_for_admin();
$canManage = has_service("admin.reviews.manage");

if ($canManage && ($_SERVER["REQUEST_METHOD"] ?? "GET") === "POST") {
    if (!verify_csrf_token((string) ($_POST["csrf_token"] ?? ""), "moderate_review")) {
        set_flash("danger", "Token CSRF non valido.");
    } else {
        $action = post_str("action");
        $id = post_int("id");
        if ($action === "publish") {
            review_set_status($id, "published");
            set_flash("success", "Recensione pubblicata.");
        } elseif ($action === "hide") {
            review_set_status($id, "hidden");
            set_flash("info", "Recensione nascosta.");
        } elseif ($action === "delete") {
            review_delete($id);
            set_flash("success", "Recensione eliminata.");
        }
    }
    redirect(url_for("admin/reviews/index.php"));
}

$html = "<div class=\"admin-toolbar\"><h1>Recensioni</h1></div>";
$html .= "<table class=\"data-table\"><thead><tr><th>Stanza</th><th>Studente</th><th>Voto</th><th>Titolo</th><th>Stato</th><th>Azioni</th></tr></thead><tbody>";
foreach ($items as $item) {
    $actions = "";
    if ($canManage) {
        if ($item["status"] === "published") {
            $actions .= "<form method=\"post\" action=\"\" class=\"inline-form\">" . csrf_field("moderate_review") . "<input type=\"hidden\" name=\"action\" value=\"hide\"><input type=\"hidden\" name=\"id\" value=\"" . (int)$item["id"] . "\"><button type=\"submit\" class=\"button-small button-secondary\">Nascondi</button></form> ";
        } else {
            $actions .= "<form method=\"post\" action=\"\" class=\"inline-form\">" . csrf_field("moderate_review") . "<input type=\"hidden\" name=\"action\" value=\"publish\"><input type=\"hidden\" name=\"id\" value=\"" . (int)$item["id"] . "\"><button type=\"submit\" class=\"button-small button-primary\">Pubblica</button></form> ";
        }
        $actions .= "<form method=\"post\" action=\"\" class=\"inline-form\" onsubmit=\"return confirm('Eliminare definitivamente questa recensione?')\">" . csrf_field("moderate_review") . "<input type=\"hidden\" name=\"action\" value=\"delete\"><input type=\"hidden\" name=\"id\" value=\"" . (int)$item["id"] . "\"><button type=\"submit\" class=\"button-small button-danger\">Elimina</button></form>";
    }
    $statusLabel = $item["status"] === "published" ? "Pubblicata" : "Nascosta";
    $html .= "<tr><td><a href=\"" . e(url_for("room.php?id=" . $item["room_id"])) . "\">" . e($item["room_name"]) . "</a></td><td>" . e($item["author"]) . "</td><td>" . stars_html((int)$item["rating"]) . "</td><td>" . e($item["title"]) . "</td><td>" . $statusLabel . "</td><td>" . $actions . "</td></tr>";
}
if ($items === []) {
    $html .= "<tr><td colspan=\"6\" class=\"muted\">Nessuna recensione presente.</td></tr>";
}
$html .= "</tbody></table>";
render_admin_page("Recensioni", $html, "admin.reviews.index");
