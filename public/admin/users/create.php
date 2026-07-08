<?php
declare(strict_types=1);
require_once __DIR__ . "/../../../includes/bootstrap.php";
require_login();
require_service("admin.users.manage");
$id = (int) query_str("id");
$edit = $id > 0;
$data = ["email" => "", "first_name" => "", "last_name" => "", "phone" => "", "status" => "active"];
$selectedGroups = [];
if ($edit) {
    $row = user_find_admin($id);
    if ($row === null) {
        set_flash("danger", "Utente non trovato.");
        redirect(url_for("admin/users/index.php"));
    }
    $data = $row;
    $selectedGroups = user_group_ids($id);
}
$errors = [];
if (($_SERVER["REQUEST_METHOD"] ?? "GET") === "POST") {
    if (!verify_csrf_token((string) ($_POST["csrf_token"] ?? ""), "user_form")) {
        $errors[] = "Token CSRF non valido.";
    }
    $data["email"] = post_str("email");
    $data["first_name"] = post_str("first_name");
    $data["last_name"] = post_str("last_name");
    $data["phone"] = post_str("phone");
    $data["status"] = post_str("status");
    $password = post_str("password");
    $selectedGroups = isset($_POST["groups"]) && is_array($_POST["groups"]) ? array_map("intval", $_POST["groups"]) : [];

    if ($data["email"] === "") $errors[] = "Email obbligatoria.";
    if ($data["first_name"] === "") $errors[] = "Nome obbligatorio.";
    if ($data["last_name"] === "") $errors[] = "Cognome obbligatorio.";
    if (!$edit && $password === "") $errors[] = "Password obbligatoria per un nuovo utente.";
    if ($password !== "" && strlen($password) < 8) $errors[] = "La password deve essere di almeno 8 caratteri.";
    
    if ($data["email"] !== "") {
        $existing = db()->prepare("SELECT id FROM users WHERE email = :email AND deleted_at IS NULL LIMIT 1");
        $existing->execute(["email" => $data["email"]]);
        $existingId = $existing->fetchColumn();
        if ($existingId && (!$edit || (int)$existingId !== $id)) {
            $errors[] = "Email già in uso.";
        }
    }

    if ($errors === []) {
        if ($edit) {
            user_update_admin($id, $data);
            user_set_groups($id, $selectedGroups);
            if ($password !== "") {
                user_update_password_admin($id, password_hash($password, PASSWORD_BCRYPT, ["cost" => 12]));
            }
            set_flash("success", "Utente aggiornato.");
        } else {
            $data["password_hash"] = password_hash($password, PASSWORD_BCRYPT, ["cost" => 12]);
            $newId = user_create_admin($data);
            user_set_groups($newId, $selectedGroups);
            set_flash("success", "Utente creato.");
        }
        redirect(url_for("admin/users/index.php"));
    }
}
$actionUrl = $edit ? url_for("admin/users/create.php?id=" . $id) : url_for("admin/users/create.php");
$html = "<div class=\"admin-toolbar\"><h1>" . ($edit ? "Modifica utente" : "Nuovo utente") . "</h1></div>";
if ($errors !== []) {
    $html .= "<div class=\"alert alert-danger\"><ul>";
    foreach ($errors as $err) $html .= "<li>" . e($err) . "</li>";
    $html .= "</ul></div>";
}
$html .= "<section class=\"panel form-panel\"><form class=\"admin-form\" method=\"post\" action=\"" . e($actionUrl) . "\">"
    . csrf_field("user_form")
    . "<div style=\"display:grid;grid-template-columns:1fr 1fr;gap:1rem;\">"
    . "<label>Nome<input type=\"text\" name=\"first_name\" value=\"" . e((string) $data["first_name"]) . "\" required></label>"
    . "<label>Cognome<input type=\"text\" name=\"last_name\" value=\"" . e((string) $data["last_name"]) . "\" required></label>"
    . "</div>"
    . "<label>Email<input type=\"email\" name=\"email\" value=\"" . e((string) $data["email"]) . "\" required></label>"
    . "<label>Telefono<input type=\"text\" name=\"phone\" value=\"" . e((string) $data["phone"]) . "\"></label>"
    . "<div style=\"display:grid;grid-template-columns:1fr 1fr;gap:1rem;\">"
    . "<label>Stato<select name=\"status\" required>" . select_options([["id"=>"active","name"=>"Attivo"], ["id"=>"suspended","name"=>"Sospeso"]], $data["status"]) . "</select></label>"
    . "<label>Password " . ($edit ? "<small class=\"muted\">(lascia vuoto per non cambiare)</small>" : "") . "<input type=\"password\" name=\"password\" " . (!$edit ? "required" : "") . " minlength=\"8\"></label>"
    . "</div>"
    . "<fieldset><legend>Gruppi</legend><div style=\"display:flex;flex-wrap:wrap;gap:1rem;\">";
foreach (groups_all() as $g) {
    $html .= "<label><input type=\"checkbox\" name=\"groups[]\" value=\"" . $g["id"] . "\"" . checked_attr(in_array((int)$g["id"], $selectedGroups, true)) . "> " . e($g["name"]) . "</label>";
}
$html .= "</div></fieldset>"
    . "<div class=\"form-actions\"><button class=\"button-primary\" type=\"submit\">" . ($edit ? "Salva modifiche" : "Crea utente") . "</button> "
    . "<a href=\"" . e(url_for("admin/users/index.php")) . "\">Annulla</a></div>"
    . "</form></section>";
render_admin_page($edit ? "Modifica utente" : "Nuovo utente", $html, "admin.users.index");
