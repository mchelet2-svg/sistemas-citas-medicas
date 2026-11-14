<?php
// index.php — Página de bienvenida (visitantes). Si ya hay sesión, redirige al panel según el rol.
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/ilustracion.php';
if ($u = usuario_actual()) redirect(inicio_por_rol($u['rol']));

$medicos  = $pdo->query("SELECT * FROM medicos WHERE activo=1 ORDER BY especialidad, nombre")->fetchAll();
$heroFoto = is_file(__DIR__ . '/img/hospital.jpg') ? url('img/hospital.jpg') : null;
$porEsp   = [];
foreach ($medicos as $m) $porEsp[$m['especialidad']][] = $m;
$iconos   = ['Medicina General' => '🩺', 'Pediatría' => '🧒', 'Odontología' => '🦷', 'Cardiología' => '❤️', 'Dermatología' => '🧴', 'Ginecología' => '🤰'];
?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>MediCitas · Agenda tu cita médica en línea</title>
    <link rel="stylesheet" href="<?= url('css/style.css') ?>">
</head>
<body class="lp">
<header class="lp-nav">
    <div class="lp-in">
        <a class="brand dark" href="<?= url('') ?>"><span class="logo">✚</span> MediCitas</a>
        <nav>
            <a href="#especialidades">Especialidades</a>
            <a href="#medicos">Nuestros médicos</a>
            <a href="#como">Cómo funciona</a>
        </nav>
        <div class="lp-cta"><a class="btn sm ghost" href="<?= url('login.php') ?>">Iniciar sesión</a><a class="btn sm" href="<?= url('registro.php') ?>">Crear cuenta</a></div>
    </div>
</header>

<section class="hero">
    <div class="lp-in hero-grid">
        <div class="hero-text">
            <span class="pill">Atención médica sin filas ni llamadas</span>
            <h1>Agenda tu cita médica en <em>minutos</em></h1>
            <p>Elige a tu especialista, escoge el día y la hora que te convienen y recibe tu cita confirmada. Rápido, seguro y desde cualquier dispositivo.</p>
            <div class="hero-btns">
                <a class="btn big" href="<?= url('registro.php') ?>">Agendar mi cita</a>
                <a class="btn big ghost" href="#medicos">Conocer a los médicos</a>
            </div>
            <ul class="hero-points"><li>✔ <?= count($medicos) ?> médicos disponibles</li><li>✔ Lunes a sábado, 08:00–17:00</li><li>✔ Reprograma o cancela cuando quieras</li></ul>
        </div>
        <div class="hero-img">
            <?php if ($heroFoto): ?><img class="hero-foto" src="<?= e($heroFoto) ?>" alt="Hospital MediCitas"><?php else: ?><?= ilustracion_hospital() ?><?php endif; ?>
        </div>
    </div>
</section>

<section class="lp-band">
    <div class="lp-in band-grid">
        <div><strong><?= count($medicos) ?></strong><span>Médicos especialistas</span></div>
        <div><strong><?= count(ESPECIALIDADES) ?></strong><span>Especialidades</span></div>
        <div><strong>24/7</strong><span>Agenda en línea</span></div>
        <div><strong>30 min</strong><span>Por cita, sin esperas</span></div>
    </div>
</section>

<section id="especialidades" class="lp-sec">
    <div class="lp-in">
        <h2 class="lp-h">Nuestras especialidades</h2>
        <p class="lp-sub">Cuidamos de toda tu familia, desde los más pequeños hasta los adultos mayores.</p>
        <div class="esp-grid">
            <?php foreach (ESPECIALIDADES as $es): ?>
                <div class="esp"><span class="ico"><?= $iconos[$es] ?? '✚' ?></span><strong><?= e($es) ?></strong>
                    <div class="esp-docs">
                        <?php foreach ($porEsp[$es] ?? [] as $d): ?>
                            <div class="esp-doc"><?= avatar($d, 'sm') ?><span><?= e($d['nombre']) ?></span></div>
                        <?php endforeach; ?>
                        <?php if (empty($porEsp[$es])): ?><small class="muted">Próximamente</small><?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section id="medicos" class="lp-sec alt">
    <div class="lp-in">
        <h2 class="lp-h">Conoce a nuestro equipo médico</h2>
        <p class="lp-sub">Profesionales con formación y experiencia, listos para atenderte.</p>
        <div class="docs big">
            <?php foreach ($medicos as $m): ?>
            <div class="card doc-card">
                <?= avatar($m, 'xl') ?>
                <h2><?= e($m['nombre']) ?></h2>
                <span class="badge b-paciente"><?= e($m['especialidad']) ?></span>
                <?php if (!empty($m['titulo'])): ?><strong class="cargo"><?= e($m['titulo']) ?></strong><?php endif; ?>
                <?php if (!empty($m['formacion'])): ?><span class="formacion">🎓 <?= e($m['formacion']) ?></span><?php endif; ?>
                <p class="muted"><?= e($m['descripcion']) ?></p>
                <a class="btn sm" href="<?= url('login.php') ?>">Agendar con este médico</a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="lp-sec recordatorio">
    <div class="lp-in rec-box">
        <div class="rec-ico">🔔</div>
        <div><h2>No olvides tu cita</h2>
        <p>Te recordamos tu próxima cita médica. Para confirmar, cambiar o consultar cualquier horario, comunícate con nuestra línea de atención.</p></div>
        <a class="rec-tel" href="tel:<?= e(preg_replace('/\s+/', '', TEL_CONTACTO)) ?>">☎ <?= e(TEL_CONTACTO) ?><small>Central de citas y recordatorios</small></a>
    </div>
</section>

<section id="como" class="lp-sec">
    <div class="lp-in">
        <h2 class="lp-h">¿Cómo funciona?</h2>
        <div class="pasos">
            <div class="paso"><span class="num">1</span><h3>Crea tu cuenta</h3><p>Regístrate gratis con tu nombre y un usuario en menos de un minuto.</p></div>
            <div class="paso"><span class="num">2</span><h3>Elige médico y horario</h3><p>Filtra por especialidad y mira al instante las horas libres de cada médico.</p></div>
            <div class="paso"><span class="num">3</span><h3>Confirma tu cita</h3><p>Revisa y reprograma tus citas desde tu panel cuando lo necesites.</p></div>
        </div>
        <div class="center" style="margin-top:28px"><a class="btn big" href="<?= url('registro.php') ?>">Comenzar ahora</a></div>
    </div>
</section>

<footer class="lp-foot">
    <div class="lp-in"><strong>✚ MediCitas</strong> · Sistema Web de Citas Médicas Portable · PHP + SQLite · UNEMI – Interacción Humano-Computador<br>
    ☎ Contacto: <?= e(TEL_CONTACTO) ?><br><small>Médicos, correos y teléfonos de ejemplo con fines académicos.</small></div>
</footer>
</body>
</html>
