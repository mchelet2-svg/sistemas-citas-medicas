<?php
// Compatibilidad: el PHP portable puede no incluir la extensión mbstring.
if (!function_exists('mb_strlen')) {
    function mb_strlen($s, $enc = null) { return preg_match_all('/./us', (string)$s); }
}

// db_config.php — Conexión SQLite (PDO), creación de tablas y migración automática.
// Funciona sobre la base original (citas_medicas.db) sin perder datos.

date_default_timezone_set('America/Guayaquil');

const ESPECIALIDADES = ['Medicina General', 'Pediatría', 'Odontología', 'Cardiología', 'Dermatología', 'Ginecología'];
const ESTADOS        = ['Registrada', 'Confirmada', 'Atendida', 'Cancelada'];
const ROLES          = ['admin', 'paciente'];
const TEL_CONTACTO   = '0990 000 000';   // Número de contacto y recordatorios de citas (ficticio; cámbielo aquí)

$db_path = __DIR__ . DIRECTORY_SEPARATOR . 'citas_medicas.db';

// URL base (permite correr el sistema en la raíz o en una subcarpeta)
$docroot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '');
$here    = realpath(__DIR__);
define('BASE_URL', ($docroot && strpos($here, $docroot) === 0)
    ? str_replace('\\', '/', substr($here, strlen($docroot))) : '');

function columnas(PDO $pdo, string $tabla): array {
    return array_column($pdo->query("PRAGMA table_info($tabla)")->fetchAll(), 'name');
}

try {
    $pdo = new PDO('sqlite:' . $db_path);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Tablas (si ya existen de la versión anterior, no se tocan)
    $pdo->exec("CREATE TABLE IF NOT EXISTS usuarios (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        nombre TEXT,
        usuario TEXT UNIQUE,
        password TEXT,
        rol TEXT DEFAULT 'paciente',
        creado TEXT,
        telefono TEXT
    )");
    $pdo->exec("CREATE TABLE IF NOT EXISTS citas (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        usuario_id INTEGER,
        paciente_nombre TEXT,
        especialidad TEXT,
        fecha DATE,
        hora TEXT,
        motivo TEXT,
        estado TEXT DEFAULT 'Registrada',
        creado TEXT
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS medicos (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        nombre TEXT NOT NULL,
        especialidad TEXT NOT NULL,
        descripcion TEXT,
        foto TEXT,
        titulo TEXT,
        formacion TEXT,
        correo TEXT,
        telefono TEXT,
        activo INTEGER DEFAULT 1,
        creado TEXT
    )");

    // Migración desde la versión anterior (columnas nuevas)
    $cu = columnas($pdo, 'usuarios');
    foreach (['nombre' => 'TEXT', 'rol' => "TEXT DEFAULT 'paciente'", 'creado' => 'TEXT', 'telefono' => 'TEXT'] as $c => $t) {
        if (!in_array($c, $cu)) $pdo->exec("ALTER TABLE usuarios ADD COLUMN $c $t");
    }
    $cc = columnas($pdo, 'citas');
    foreach (['medico_id' => 'INTEGER', 'usuario_id' => 'INTEGER', 'motivo' => 'TEXT', 'creado' => 'TEXT', 'estado' => "TEXT DEFAULT 'Registrada'"] as $c => $t) {
        if (!in_array($c, $cc)) $pdo->exec("ALTER TABLE citas ADD COLUMN $c $t");
    }

    foreach (['titulo', 'formacion', 'correo', 'telefono'] as $c) {
        if (!in_array($c, columnas($pdo, 'medicos'))) $pdo->exec("ALTER TABLE medicos ADD COLUMN $c TEXT");
    }

    // Médicos de ejemplo (nombres ficticios) si la tabla está vacía
    if ((int)$pdo->query("SELECT COUNT(*) FROM medicos")->fetchColumn() === 0) {
        $m = $pdo->prepare("INSERT INTO medicos (nombre, especialidad, descripcion, creado) VALUES (?,?,?,?)");
        foreach ([
            ['Dra. Camila Andrade', 'Medicina General', 'Atención integral del adulto, control de salud y prevención.'],
            ['Dr. Marco Intriago', 'Medicina General', 'Chequeos preventivos, enfermedades crónicas y certificados médicos.'],
            ['Dr. Luis Paredes', 'Pediatría', 'Control del niño sano, vacunación y enfermedades de la infancia.'],
            ['Dra. Valeria Mora', 'Odontología', 'Limpieza, calzas, ortodoncia y estética dental.'],
            ['Dr. Andrés Salazar', 'Cardiología', 'Presión arterial, electrocardiograma y salud del corazón.'],
            ['Dra. Daniela Rivas', 'Dermatología', 'Acné, alergias de la piel y revisión de lunares.'],
            ['Dra. Sofía Navarrete', 'Ginecología', 'Control prenatal, planificación familiar y salud de la mujer.'],
        ] as $d) $m->execute([$d[0], $d[1], $d[2], date('Y-m-d H:i:s')]);
    }

    // Datos de perfil de ejemplo (ficticios) para los médicos de muestra; se editan desde Admin > Médicos
    $perfil = $pdo->prepare("UPDATE medicos SET titulo=?, formacion=?, correo=?, telefono=? WHERE nombre=? AND (titulo IS NULL OR titulo='')");
    foreach ([
        ['Dra. Camila Andrade',  'Médica General',                'Título de Médica – Maestría en Salud Pública',      'camila.andrade@medicitas.test',  '0990000101'],
        ['Dr. Marco Intriago',   'Médico General',                'Título de Médico – Diplomado en Medicina Familiar', 'marco.intriago@medicitas.test',  '0990000102'],
        ['Dr. Luis Paredes',     'Pediatra',                      'Especialidad en Pediatría – Posgrado en Neonatología', 'luis.paredes@medicitas.test', '0990000103'],
        ['Dra. Valeria Mora',    'Odontóloga',                    'Título de Odontología – Especialidad en Ortodoncia', 'valeria.mora@medicitas.test',   '0990000104'],
        ['Dr. Andrés Salazar',   'Cardiólogo',                    'Especialidad en Cardiología – Fellowship en Ecocardiografía', 'andres.salazar@medicitas.test', '0990000105'],
        ['Dra. Daniela Rivas',   'Dermatóloga',                   'Especialidad en Dermatología – Diplomado en Dermatoscopía', 'daniela.rivas@medicitas.test', '0990000106'],
        ['Dra. Sofía Navarrete', 'Ginecóloga y obstetra',         'Especialidad en Ginecología y Obstetricia',         'sofia.navarrete@medicitas.test', '0990000107'],
    ] as $p) $perfil->execute([$p[1], $p[2], $p[3], $p[4], $p[0]]);

    // Fotos incluidas para los 7 médicos de ejemplo (solo si aún no tienen foto)
    foreach (['Camila Andrade'=>'camila','Marco Intriago'=>'marco','Luis Paredes'=>'luis','Valeria Mora'=>'valeria','Andrés Salazar'=>'andres','Daniela Rivas'=>'daniela','Sofía Navarrete'=>'sofia'] as $nom => $f) {
        if (is_file(__DIR__ . "/uploads/medico_$f.jpg"))
            $pdo->prepare("UPDATE medicos SET foto=? WHERE nombre LIKE ? AND (foto IS NULL OR foto='')")->execute(["medico_$f.jpg", "%$nom"]);
    }

    // Carpeta para fotos de médicos
    if (!is_dir(__DIR__ . '/uploads')) @mkdir(__DIR__ . '/uploads', 0777, true);

    // Contraseñas antiguas en texto plano -> hash seguro
    foreach ($pdo->query("SELECT id, password FROM usuarios")->fetchAll() as $u) {
        if (strpos((string)$u['password'], '$2y$') !== 0 && strpos((string)$u['password'], '$argon') !== 0) {
            $pdo->prepare("UPDATE usuarios SET password=? WHERE id=?")
                ->execute([password_hash((string)$u['password'], PASSWORD_DEFAULT), $u['id']]);
        }
    }

    // Usuarios por defecto (admin / 1234 se mantiene según el manual)
    $now = date('Y-m-d H:i:s');
    $ins = $pdo->prepare("INSERT OR IGNORE INTO usuarios (nombre, usuario, password, rol, creado) VALUES (?,?,?,?,?)");
    $ins->execute(['Administrador', 'admin', password_hash('1234', PASSWORD_DEFAULT), 'admin', $now]);
    $ins->execute(['Paciente de Prueba', 'paciente', password_hash('1234', PASSWORD_DEFAULT), 'paciente', $now]);
    $pdo->exec("UPDATE usuarios SET rol='admin' WHERE usuario='admin'");
    $pdo->exec("UPDATE usuarios SET rol='paciente' WHERE rol IS NULL OR rol=''");
    $pdo->exec("UPDATE usuarios SET nombre=usuario WHERE nombre IS NULL OR nombre=''");

} catch (PDOException $e) {
    die('Error en el almacenamiento local: ' . htmlspecialchars($e->getMessage()));
}
