<?php
declare(strict_types=1);
require_once __DIR__ . "/../../../includes/bootstrap.php";
require_login();
require_service("admin.bookings.index");
$items = bookings_all_for_admin();
$html = "<div class=\"admin-toolbar\"><h1>Richieste e caparre <span class=\"muted\">(" . count($items) . ")</span></h1></div>";
$html .= "<table class=\"data-table\"><thead><tr><th>ID</th><th>Stanza</th><th>Annuncio</th><th>Studente</th><th>Proprietario</th><th>Stato</th><th>Creata il</th><th>Caparra</th><th>Azioni</th></tr></thead><tbody>";
foreach ($items as $item) {
    $date = date("d/m/Y", strtotime($item["created_at"]));
    $deposit = $item["deposit_amount"] ? format_price($item["deposit_amount"]) : "—";
    $actions = "<a class=\"button-small button-secondary\" href=\"" . e(url_for("booking.php?id=" . $item["id"])) . "\">Dettaglio</a>";
    $html .= "<tr><td>" . (int)$item["id"] . "</td><td>" . e($item["room_name"]) . "</td><td>" . e(excerpt((string)$item["property_title"], 40)) . "</td><td>" . e($item["student_name"]) . "</td><td>" . e($item["landlord_name"]) . "</td><td>" . booking_status_badge($item["status"]) . "</td><td>" . $date . "</td><td>" . $deposit . "</td><td>" . $actions . "</td></tr>";
}
if ($items === []) {
    $html .= "<tr><td colspan=\"9\" class=\"muted\">Nessuna richiesta presente.</td></tr>";
}
$html .= "</tbody></table>";
render_admin_page("Richieste e caparre", $html, "admin.bookings.index");
