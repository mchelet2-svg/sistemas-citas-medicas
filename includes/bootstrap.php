<?php
// includes/bootstrap.php — Núcleo común: conexión, sesión, seguridad y utilidades.
require_once __DIR__ . '/../db_config.php';

if (session_status() === PHP_SESSION_NONE) session_start();

function e($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function url(string $p = ''): string { return BASE_URL . '/' . ltrim($p, '/'); }
function redirect(string $p): void { header('Location: ' . url($p)); exit; }

// Mensajes temporales (flash)
function flash(string $tipo, string $msg): void { $_SESSION['flash'][] = [$tipo, $msg]; }
function flashes(): array { $f = $_SESSION['flash'] ?? []; unset($_SESSION['flash']); return $f; }

// CSRF
function csrf_field(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
    return '<input type="hidden" name="csrf" value="' . e($_SESSION['csrf']) . '">';
}
function csrf_check(): void {
    if (!isset($_POST['csrf'], $_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $_POST['csrf'])) {
        http_response_code(400);
        die('Solicitud no válida (token CSRF). Regrese e intente de nuevo.');
    }
}

// Usuario actual (se consulta a la BD para que un usuario eliminado pierda el acceso)
function usuario_actual(): ?array {
    global $pdo;
    static $u = false;
    if ($u === false) {
        $u = null;
        if (!empty($_SESSION['uid'])) {
            $st = $pdo->prepare("SELECT id, nombre, usuario, rol FROM usuarios WHERE id=?");
            $st->execute([$_SESSION['uid']]);
            $u = $st->fetch() ?: null;
        }
    }
    return $u;
}

function requiere_login(?string $rol = null): array {
    $u = usuario_actual();
    if (!$u) redirect('login.php');
    if ($rol && $u['rol'] !== $rol) { http_response_code(403); die('403 - No tiene permisos para ver esta página.'); }
    return $u;
}

function inicio_por_rol(string $rol): string { return $rol === 'admin' ? 'admin/index.php' : 'paciente/index.php'; }

function fecha_larga(string $f): string {
    $meses = ['', 'ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
    $t = strtotime($f);
    return $t ? date('d', $t) . ' ' . $meses[(int)date('n', $t)] . ' ' . date('Y', $t) : e($f);
}

function badge_estado(?string $est): string {
    $est = $est ?: 'Registrada';
    return '<span class="badge b-' . strtolower($est) . '">' . e($est) . '</span>';
}

function horarios(): array {
    $h = [];
    for ($m = 8 * 60; $m <= 16 * 60 + 30; $m += 30) $h[] = sprintf('%02d:%02d', intdiv($m, 60), $m % 60);
    return $h;
}

// Avatar de médico: foto subida o iniciales con color
function avatar(array $m, string $cls = ''): string {
    if (!empty($m['foto']) && is_file(__DIR__ . '/../uploads/' . $m['foto'])) {
        return '<img class="avatar ' . $cls . '" src="' . e(url('uploads/' . $m['foto'])) . '" alt="' . e($m['nombre']) . '">';
    }
    return '<span class="avatar retrato ' . $cls . '" title="' . e($m['nombre']) . '">' . retrato_svg($m) . '</span>';
}

// Retrato ilustrado del médico (se usa cuando no hay fotografía subida)
function retrato_svg(array $m): string {
    $f = (bool)preg_match('/^Dra\./u', $m['nombre']);
    $fijos = [
        'Dra. Camila Andrade'  => ['#f1c9a5', '#3b2a20', 'largo',  false],
        'Dr. Marco Intriago'   => ['#d9a679', '#1f1612', 'corto',  true],
        'Dr. Luis Paredes'     => ['#f1c9a5', '#8a5a2b', 'corto',  false],
        'Dra. Valeria Mora'    => ['#c98f66', '#2a1a12', 'mono',   false],
        'Dr. Andrés Salazar'   => ['#e0b48e', '#9a9a9a', 'corto',  true],
        'Dra. Daniela Rivas'   => ['#a8714b', '#1a1210', 'largo',  true],
        'Dra. Sofía Navarrete' => ['#f4d2b4', '#b5651d', 'largo',  false],
    ];
    $h = crc32($m['nombre']);
    $pieles = ['#f4d2b4', '#f1c9a5', '#e0b48e', '#d9a679', '#c98f66', '#a8714b'];
    $pelos  = ['#1f1612', '#3b2a20', '#5a3a22', '#8a5a2b', '#b5651d', '#9a9a9a'];
    [$piel, $pelo, $estilo, $gafas] = $fijos[$m['nombre']]
        ?? [$pieles[$h % 6], $pelos[($h >> 3) % 6], $f ? ['largo', 'mono'][($h >> 5) % 2] : 'corto', (bool)(($h >> 7) % 2)];
    $hue = 185 + ($h % 40);
    $s  = '<svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">';
    $s .= '<rect width="100" height="100" fill="hsl(' . $hue . ',50%,86%)"/>';
    if ($estilo === 'largo') $s .= '<path d="M27 54 Q22 22 50 20 Q78 22 73 54 L77 82 Q50 72 23 82 Z" fill="' . $pelo . '"/>';
    $s .= '<path d="M10 100 Q12 74 38 70 L62 70 Q88 74 90 100 Z" fill="#fff" stroke="#cfe3ee" stroke-width="1.5"/>';
    $s .= '<path d="M42 70 L50 87 L58 70 Z" fill="#12b886"/>';
    $s .= '<path d="M34 75 Q31 91 46 93" fill="none" stroke="#0b6e99" stroke-width="2.2" stroke-linecap="round"/><circle cx="47" cy="93" r="2.6" fill="#0b6e99"/>';
    $s .= '<rect x="44" y="58" width="12" height="15" rx="5" fill="' . $piel . '"/>';
    $s .= '<ellipse cx="50" cy="44" rx="17" ry="20" fill="' . $piel . '"/>';
    if ($estilo === 'mono') $s .= '<circle cx="50" cy="19" r="8" fill="' . $pelo . '"/>';
    $s .= '<path d="M32 43 Q30 21 50 21 Q70 21 68 43 Q62 30 50 30 Q38 30 32 43 Z" fill="' . $pelo . '"/>';
    $s .= '<circle cx="43" cy="46" r="1.9" fill="#17283b"/><circle cx="57" cy="46" r="1.9" fill="#17283b"/>';
    $s .= '<path d="M44 54 Q50 59 56 54" fill="none" stroke="#17283b" stroke-width="1.8" stroke-linecap="round"/>';
    if ($gafas) $s .= '<g fill="none" stroke="#17283b" stroke-width="1.5"><circle cx="43" cy="46" r="5.5"/><circle cx="57" cy="46" r="5.5"/><path d="M48.5 46 H51.5"/></g>';
    return $s . '</svg>';
}

// Valida fecha/hora/médico. Devuelve lista de errores (vacía = correcto).
function validar_horario(PDO $pdo, int $medicoId, string $fecha, string $hora, ?int $excluirId = null): array {
    $err = [];
    $d = DateTime::createFromFormat('Y-m-d', $fecha);
    if (!$d || $d->format('Y-m-d') !== $fecha) return ['Fecha no válida.'];
    if ($fecha < date('Y-m-d')) $err[] = 'La fecha no puede estar en el pasado.';
    elseif ((int)$d->format('N') === 7) $err[] = 'No se atiende los domingos.';
    if (!in_array($hora, horarios(), true)) $err[] = 'Seleccione un horario disponible.';
    if ($fecha === date('Y-m-d') && $hora <= date('H:i')) $err[] = 'Ese horario de hoy ya pasó.';
    if (!$err) {
        $st = $pdo->prepare("SELECT 1 FROM citas WHERE medico_id=? AND fecha=? AND hora=? AND estado<>'Cancelada' AND id<>?");
        $st->execute([$medicoId, $fecha, $hora, $excluirId ?? 0]);
        if ($st->fetch()) $err[] = 'Ese horario ya está ocupado con este médico. Elija otra hora.';
    }
    return $err;
}

function horas_ocupadas(PDO $pdo, int $medicoId, string $fecha, ?int $excluirId = null): array {
    $st = $pdo->prepare("SELECT hora FROM citas WHERE medico_id=? AND fecha=? AND estado<>'Cancelada' AND id<>?");
    $st->execute([$medicoId, $fecha, $excluirId ?? 0]);
    $o = $st->fetchAll(PDO::FETCH_COLUMN);
    if ($fecha === date('Y-m-d')) foreach (horarios() as $h) if ($h <= date('H:i')) $o[] = $h;
    return array_values(array_unique($o));
}
