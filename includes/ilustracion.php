<?php
// includes/ilustracion.php — Ilustración vectorial propia: hospital con médicos (no requiere internet).
function medico_svg(int $cx, string $piel, string $pelo, string $bata = '#ffffff', string $gafas = ''): string {
    return '
    <g transform="translate(' . $cx . ',0)">
        <ellipse cx="0" cy="428" rx="46" ry="7" fill="#0b3d57" opacity=".18"/>
        <path d="M-40 428 L-40 352 Q-40 328 -16 324 L16 324 Q40 328 40 352 L40 428 Z" fill="' . $bata . '" stroke="#cfe3ee" stroke-width="2"/>
        <path d="M-12 324 L0 352 L12 324 Z" fill="#12b886"/>
        <path d="M-22 332 Q-26 372 -8 380 Q6 384 8 366" fill="none" stroke="#0b6e99" stroke-width="3.2" stroke-linecap="round"/>
        <circle cx="9" cy="368" r="5" fill="#0b6e99"/>
        <rect x="-8" y="306" width="16" height="22" rx="6" fill="' . $piel . '"/>
        <circle cx="0" cy="290" r="23" fill="' . $piel . '"/>
        <path d="M-24 288 Q-26 262 0 262 Q26 262 24 288 Q16 274 0 274 Q-16 274 -24 288 Z" fill="' . $pelo . '"/>
        <circle cx="-8" cy="292" r="2.4" fill="#17283b"/><circle cx="8" cy="292" r="2.4" fill="#17283b"/>
        <path d="M-7 301 Q0 307 7 301" fill="none" stroke="#17283b" stroke-width="2" stroke-linecap="round"/>' . $gafas . '
    </g>';
}
function ilustracion_hospital(): string {
    $gafas = '<g fill="none" stroke="#17283b" stroke-width="1.8"><circle cx="-8" cy="292" r="6.5"/><circle cx="8" cy="292" r="6.5"/><path d="M-1.5 292 H1.5"/></g>';
    $v = '';
    for ($r = 0; $r < 3; $r++) for ($c = 0; $c < 5; $c++) {
        $x = 192 + $c * 36; $y = 168 + $r * 46;
        $v .= '<rect x="' . $x . '" y="' . $y . '" width="22" height="28" rx="4" fill="' . (($r + $c) % 3 === 0 ? '#ffe9a8' : '#bfe3f4') . '"/>';
    }
    return '<svg viewBox="0 0 600 450" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Ilustración de un hospital con dos médicos">
    <defs>
        <linearGradient id="cielo" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#7fd1ee"/><stop offset="1" stop-color="#e6f6fc"/></linearGradient>
        <linearGradient id="edif" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#ffffff"/><stop offset="1" stop-color="#e8f2f8"/></linearGradient>
    </defs>
    <rect width="600" height="450" rx="28" fill="url(#cielo)"/>
    <circle cx="505" cy="78" r="38" fill="#fff3b8" opacity=".95"/>
    <g fill="#fff" opacity=".92"><ellipse cx="110" cy="80" rx="44" ry="15"/><ellipse cx="140" cy="68" rx="30" ry="14"/><ellipse cx="420" cy="52" rx="36" ry="12"/><ellipse cx="445" cy="44" rx="24" ry="11"/></g>
    <rect y="396" width="600" height="54" fill="#7ccf9d"/><rect y="418" width="600" height="32" fill="#68bd8b"/>
    <rect x="70" y="250" width="110" height="146" rx="6" fill="url(#edif)" stroke="#cfe3ee" stroke-width="2"/>
    <rect x="420" y="250" width="110" height="146" rx="6" fill="url(#edif)" stroke="#cfe3ee" stroke-width="2"/>
    <g fill="#bfe3f4"><rect x="90" y="276" width="22" height="28" rx="4"/><rect x="132" y="276" width="22" height="28" rx="4"/><rect x="90" y="326" width="22" height="28" rx="4"/><rect x="132" y="326" width="22" height="28" rx="4"/>
    <rect x="440" y="276" width="22" height="28" rx="4"/><rect x="482" y="276" width="22" height="28" rx="4"/><rect x="440" y="326" width="22" height="28" rx="4"/><rect x="482" y="326" width="22" height="28" rx="4"/></g>
    <rect x="170" y="118" width="260" height="278" rx="8" fill="url(#edif)" stroke="#cfe3ee" stroke-width="2"/>
    <rect x="170" y="118" width="260" height="30" rx="8" fill="#0b6e99"/>
    <circle cx="300" cy="82" r="34" fill="#12b886"/><path d="M290 62 h20 v14 h14 v20 h-14 v14 h-20 v-14 h-14 v-20 h14 z" fill="#fff"/>
    ' . $v . '
    <path d="M268 396 V350 Q268 334 284 334 H316 Q332 334 332 350 V396 Z" fill="#0b6e99"/>
    <path d="M300 336 V396" stroke="#fff" stroke-width="2" opacity=".55"/>
    <g><rect x="32" y="352" width="8" height="46" fill="#8a6a4a"/><circle cx="36" cy="338" r="26" fill="#3fa56b"/><circle cx="22" cy="352" r="16" fill="#4fb87b"/></g>
    <g><rect x="562" y="360" width="8" height="38" fill="#8a6a4a"/><circle cx="566" cy="346" r="22" fill="#3fa56b"/></g>
    ' . medico_svg(222, '#f1c9a5', '#3b2a20', '#ffffff') . medico_svg(378, '#c98f66', '#1f1612', '#ffffff', $gafas) . '
    </svg>';
}
