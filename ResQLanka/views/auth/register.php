<?php

require_once __DIR__ . "/../../config/session.php";

$registerErrors = $_SESSION["register_errors"] ?? [];
$old = $_SESSION["register_old"] ?? [];

unset($_SESSION["register_errors"]);
unset($_SESSION["register_old"]);

function oldValue(string $field, array $old): string
{
    return htmlspecialchars(
        $old[$field] ?? "",
        ENT_QUOTES,
        "UTF-8"
    );
}
?>
