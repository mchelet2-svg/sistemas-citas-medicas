<?php
// horas.php — Devuelve en JSON las horas ocupadas de un médico en una fecha (para el formulario).
require_once __DIR__ . '/includes/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');
if (!usuario_actual()) { http_response_code(401); echo '[]'; exit; }
$f = $_GET['fecha'] ?? '';
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $f)) { echo '{"ocupadas":[]}'; exit; }
echo json_encode(['ocupadas' => horas_ocupadas($pdo, (int)($_GET['medico'] ?? 0), $f, (int)($_GET['excluir'] ?? 0) ?: null)]);
