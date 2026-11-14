<?php
// router.php — Enrutador del servidor PHP integrado.
// Evita que se descarguen archivos internos (base de datos, configuración, includes).
$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/');

$bloqueado = preg_match('/\.(db|bat|ini|md)$/i', $path)
    || preg_match('#^/(includes|php|\.git)(/|$)#i', $path)
    || preg_match('#^/(db_config|router)\.php$#i', $path);

if ($bloqueado) {
    http_response_code(403);
    echo '403 - Acceso denegado';
    return true;
}
return false; // el servidor entrega el archivo o ejecuta el .php normalmente
