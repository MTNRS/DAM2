# Proyecto: jocarsa-asistencias

## 1. Estructura del proyecto

```text
jocarsa-asistencias/
├── admin/
│   ├── applications/
│   │   ├── calendario_semanal.php
│   │   ├── cuadricula_asistencia.php
│   │   ├── estadisticas_empleados.php
│   │   ├── horario_semanal.php
│   │   ├── hoy.php
│   │   ├── resumen_sesiones.php
│   │   ├── reuniones.php
│   │   ├── solicitudes_personal.php
│   │   └── temporalizacion_vacaciones.php
│   ├── absence_attachment.php
│   ├── calendario_semanal.php
│   ├── cuadricula_asistencia.php
│   ├── entity_tables.php
│   ├── estadisticas_empleados.php
│   ├── functions.php
│   ├── horario_semanal.php
│   ├── index.php
│   ├── logout.php
│   ├── resumen_sesiones.php
│   ├── sick_leave_attachment.php
│   ├── solicitudes_personal.php
│   └── temporalizacion_vacaciones.php
├── assets/
│   ├── app.js
│   └── jocarsa-iu.css
├── database/
│   ├── .htaccess
│   ├── audit-hmac.key
│   ├── install.php
│   ├── jocarsa-asistencias.sqlite-shm
│   ├── jocarsa-asistencias.sqlite-wal
│   └── schema.sql
├── front/
│   ├── api.php
│   ├── app.js
│   └── index.php
├── src/
│   ├── AbsenceAttachment.php
│   ├── AbsenceService.php
│   ├── ApplicationAudit.php
│   ├── AttendanceAudit.php
│   ├── AttendanceTypeCatalog.php
│   ├── Auth.php
│   ├── CompanyLocation.php
│   ├── ContractCatalog.php
│   ├── Csrf.php
│   ├── Database.php
│   ├── EmailNotifier.php
│   ├── Env.php
│   ├── helpers.php
│   ├── ScheduleCompliance.php
│   ├── Session.php
│   ├── Settings.php
│   ├── TenantContext.php
│   ├── V1DumpImporter.php
│   └── View.php
├── superadmin/
│   ├── import.php
│   ├── index.php
│   └── logout.php
├── tools/
│   ├── import_v1.php
│   └── seed_demo2.php
├── .env.example
├── .gitignore
├── .htaccess
├── bootstrap.php
├── CHANGELOG.md
├── index.php
├── nohtaccess
├── README-BAJAS.md
├── README-FIX.md
├── README-LAYOUT-FIX.md
├── README-MOBILE-UPDATE.md
├── README-PATCH.md
├── README.md
├── site.webmanifest
└── VERSION
```

## 2. Contenido de los archivos

### admin/applications/calendario_semanal.php

```php
<?php
declare(strict_types=1);

if (!defined('JA_ADMIN_APPLICATION')) {
    http_response_code(404);
    exit;
}

$baseDate = (string)($_GET['date'] ?? date('Y-m-d'));
if (!ja_admin_valid_date($baseDate) || $baseDate === '') {
    $baseDate = date('Y-m-d');
}
$selectedDate = new DateTimeImmutable($baseDate);
$weekStart = $selectedDate->modify('monday this week');
$weekEnd = $weekStart->modify('+6 days');
$previousWeek = $weekStart->modify('-7 days');
$nextWeek = $weekStart->modify('+7 days');

$statement = $pdo->prepare(
    "SELECT s.*, e.nombre, e.apellido, e.departamento, e.role,
            COALESCE((SELECT c.contract_type FROM contracts c
                      WHERE c.tenant_id=s.tenant_id AND c.employee_id=s.employee_id
                      ORDER BY c.id DESC LIMIT 1), ct.name) AS contract_name
     FROM schedules s
     JOIN employees e ON e.id=s.employee_id AND e.tenant_id=s.tenant_id
     LEFT JOIN contract_types ct ON ct.id=e.contract_type_id AND ct.tenant_id=e.tenant_id
     WHERE s.tenant_id=:tenant
       AND e.active=1
       AND (s.valid_from IS NULL OR s.valid_from<=:week_end)
       AND (s.valid_until IS NULL OR s.valid_until>=:week_start)
     ORDER BY e.apellido,e.nombre,s.weekday,s.start_time"
);
$statement->execute([
    'tenant' => $tenantId,
    'week_start' => $weekStart->format('Y-m-d'),
    'week_end' => $weekEnd->format('Y-m-d'),
]);
$calendar = [];
foreach ($statement->fetchAll() as $row) {
    $employeeId = (int)$row['employee_id'];
    if (!isset($calendar[$employeeId])) {
        $calendar[$employeeId] = ['employee' => $row, 'days' => []];
    }
    $calendar[$employeeId]['days'][(int)$row['weekday']][] = $row;
}
?>
<section class="application-stack">
  <div class="application-toolbar">
    <div><h2>Calendario semanal</h2><p>Semana del <?= ja_h($weekStart->format('d/m/Y')) ?> al <?= ja_h($weekEnd->format('d/m/Y')) ?></p></div>
    <div class="actions">
      <a class="button" href="<?= ja_h(ja_admin_query_url(['group' => 'applications', 'app' => 'calendario_semanal', 'date' => $previousWeek->format('Y-m-d')])) ?>">Semana anterior</a>
      <a class="button primary" href="<?= ja_h(ja_admin_query_url(['group' => 'applications', 'app' => 'calendario_semanal', 'date' => date('Y-m-d')])) ?>">Semana actual</a>
      <a class="button" href="<?= ja_h(ja_admin_query_url(['group' => 'applications', 'app' => 'calendario_semanal', 'date' => $nextWeek->format('Y-m-d')])) ?>">Semana siguiente</a>
    </div>
  </div>

  <?php if (!$calendar): ?>
    <div class="empty">No hay horarios activos para esta semana.</div>
  <?php else: ?>
    <div class="calendar-week-wrap">
      <table class="calendar-week">
        <thead><tr><th>Empleado</th><?php foreach ($weekdays as $day): ?><th><?= ja_h($day) ?></th><?php endforeach; ?></tr></thead>
        <tbody>
        <?php foreach ($calendar as $item): $employee = $item['employee']; ?>
          <tr>
            <td class="employee-cell"><strong><?= ja_h(ja_admin_employee_name($employee)) ?></strong><span><?= ja_h($employee['departamento'] ?: 'Sin departamento') ?></span><small><?= ja_h($employee['contract_name'] ?: 'Sin contrato asociado') ?></small></td>
            <?php foreach ($weekdays as $dayNumber => $day): ?>
              <td><?php if (empty($item['days'][$dayNumber])): ?><span class="no-shift">—</span><?php else: ?><?php foreach ($item['days'][$dayNumber] as $shift): ?><div class="shift-chip"><strong><?= ja_h(ja_admin_short_time($shift['start_time'])) ?> – <?= ja_h(ja_admin_short_time($shift['end_time'])) ?></strong><span><?= ja_h($shift['valid_from'] ?: 'Siempre') ?> → <?= ja_h($shift['valid_until'] ?: 'Sin fin') ?></span></div><?php endforeach; ?><?php endif; ?></td>
            <?php endforeach; ?>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>



```

### admin/applications/cuadricula_asistencia.php

```php
<?php
declare(strict_types=1);

if (!defined('JA_ADMIN_APPLICATION')) {
    http_response_code(404);
    exit;
}

$gridMonth = trim((string)($_GET['month'] ?? date('Y-m')));
if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $gridMonth)) {
    $gridMonth = date('Y-m');
}
$gridStart = new DateTimeImmutable($gridMonth . '-01');
$gridEnd = $gridStart->modify('last day of this month');
$gridPrevious = $gridStart->modify('-1 month')->format('Y-m');
$gridNext = $gridStart->modify('+1 month')->format('Y-m');
$gridToday = date('Y-m-d');
$gridMonthNames = [1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril', 5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto', 9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre'];
$gridMonthLabel = ucfirst($gridMonthNames[(int)$gridStart->format('n')]) . ' ' . $gridStart->format('Y');
$gridDates = [];
for ($date = $gridStart; $date <= $gridEnd; $date = $date->modify('+1 day')) {
    $gridDates[] = $date->format('Y-m-d');
}

$gridDepartment = trim((string)($_GET['department'] ?? ''));
$gridRole = trim((string)($_GET['role'] ?? ''));
$employeeSql = 'SELECT id,nombre,apellido,departamento,role FROM employees WHERE tenant_id=:tenant AND active=1';
$employeeParameters = ['tenant' => $tenantId];
if ($gridDepartment !== '') {
    $employeeSql .= ' AND departamento=:department';
    $employeeParameters['department'] = $gridDepartment;
}
if ($gridRole !== '') {
    $employeeSql .= ' AND role=:role';
    $employeeParameters['role'] = $gridRole;
}
$employeeSql .= ' ORDER BY apellido COLLATE NOCASE,nombre COLLATE NOCASE';
$statement = $pdo->prepare($employeeSql);
$statement->execute($employeeParameters);
$gridEmployees = $statement->fetchAll();

$statement = $pdo->prepare("SELECT DISTINCT departamento FROM employees WHERE tenant_id=:tenant AND active=1 AND departamento IS NOT NULL AND trim(departamento)<>'' ORDER BY departamento COLLATE NOCASE");
$statement->execute(['tenant' => $tenantId]);
$gridDepartments = array_map(static fn(array $row): string => (string)$row['departamento'], $statement->fetchAll());
$statement = $pdo->prepare("SELECT DISTINCT role FROM employees WHERE tenant_id=:tenant AND active=1 AND role IS NOT NULL AND trim(role)<>'' ORDER BY role COLLATE NOCASE");
$statement->execute(['tenant' => $tenantId]);
$gridRoles = array_map(static fn(array $row): string => (string)$row['role'], $statement->fetchAll());

$statement = $pdo->prepare(
    "SELECT a.*,ft.name AS attendance_type_name
     FROM attendance a
     LEFT JOIN attendance_types ft ON ft.id=a.attendance_type_id AND ft.tenant_id=a.tenant_id
     WHERE a.tenant_id=:tenant AND a.work_date BETWEEN :start AND :end
     ORDER BY a.work_date,a.start_time,a.id"
);
$statement->execute(['tenant' => $tenantId, 'start' => $gridStart->format('Y-m-d'), 'end' => $gridEnd->format('Y-m-d')]);
$gridAttendance = [];
foreach ($statement->fetchAll() as $record) {
    $gridAttendance[(int)$record['employee_id']][(string)$record['work_date']][] = $record;
}

$statement = $pdo->prepare(
    "SELECT attendance_id,'admin' AS source FROM attendance_corrections WHERE tenant_id=:tenant1 AND status='pending'
     UNION ALL
     SELECT attendance_id,'worker' AS source FROM worker_attendance_corrections WHERE tenant_id=:tenant2 AND status='pending'"
);
$statement->execute(['tenant1' => $tenantId, 'tenant2' => $tenantId]);
$gridPendingByAttendance = [];
foreach ($statement->fetchAll() as $pending) {
    $gridPendingByAttendance[(int)$pending['attendance_id']] = (string)$pending['source'];
}

$statement = $pdo->prepare('SELECT employee_id,weekday,valid_from,valid_until FROM schedules WHERE tenant_id=:tenant AND (valid_from IS NULL OR valid_from<=:end) AND (valid_until IS NULL OR valid_until>=:start)');
$statement->execute(['tenant' => $tenantId, 'start' => $gridStart->format('Y-m-d'), 'end' => $gridEnd->format('Y-m-d')]);
$gridSchedules = [];
foreach ($statement->fetchAll() as $schedule) {
    $gridSchedules[(int)$schedule['employee_id']][(int)$schedule['weekday']][] = $schedule;
}

$statement = $pdo->prepare("SELECT employee_id,start_date,end_date,absence_type FROM absences WHERE tenant_id=:tenant AND status='approved' AND start_date<=:end AND (end_date IS NULL OR end_date>=:start)");
$statement->execute(['tenant' => $tenantId, 'start' => $gridStart->format('Y-m-d'), 'end' => $gridEnd->format('Y-m-d')]);
$gridAbsences = [];
foreach ($statement->fetchAll() as $absence) {
    $absenceStart = max((string)$absence['start_date'], $gridStart->format('Y-m-d'));
    $absenceEnd = $absence['end_date'] ? min((string)$absence['end_date'], $gridEnd->format('Y-m-d')) : $gridEnd->format('Y-m-d');
    for ($date = new DateTimeImmutable($absenceStart); $date->format('Y-m-d') <= $absenceEnd; $date = $date->modify('+1 day')) {
        $gridAbsences[(int)$absence['employee_id']][$date->format('Y-m-d')] = $absence;
    }
}

$statement = $pdo->prepare('SELECT holiday_date,name FROM holidays WHERE tenant_id=:tenant AND holiday_date BETWEEN :start AND :end');
$statement->execute(['tenant' => $tenantId, 'start' => $gridStart->format('Y-m-d'), 'end' => $gridEnd->format('Y-m-d')]);
$gridHolidays = [];
foreach ($statement->fetchAll() as $holiday) {
    $gridHolidays[(string)$holiday['holiday_date']] = (string)$holiday['name'];
}

$scheduleApplies = static function (int $employeeId, string $date) use ($gridSchedules): bool {
    $weekday = (int)(new DateTimeImmutable($date))->format('N');
    foreach ($gridSchedules[$employeeId][$weekday] ?? [] as $schedule) {
        if ((!$schedule['valid_from'] || $schedule['valid_from'] <= $date) && (!$schedule['valid_until'] || $schedule['valid_until'] >= $date)) {
            return true;
        }
    }
    return false;
};

$gridCells = [];
$gridStats = [];
foreach ($gridEmployees as $employee) {
    $employeeId = (int)$employee['id'];
    $stats = ['worked' => 0, 'missing' => 0, 'minutes' => 0, 'incidents' => 0, 'percent' => 100];
    foreach ($gridDates as $date) {
        $records = $gridAttendance[$employeeId][$date] ?? [];
        $cell = ['state' => 'none', 'label' => 'Sin horario ni registro', 'record_id' => 0, 'pending_source' => ''];
        if ($records) {
            $labels = [];
            $hasComplete = false;
            $hasOpen = false;
            $hasError = false;
            foreach ($records as $record) {
                $cell['record_id'] = (int)$record['id'];
                $pendingSource = $gridPendingByAttendance[(int)$record['id']] ?? '';
                if ($pendingSource !== '') {
                    $cell['pending_source'] = $pendingSource;
                }
                $start = ja_admin_short_time($record['start_time']);
                $end = ja_admin_short_time($record['end_time']);
                $type = $record['attendance_type_name'] ? ' · ' . $record['attendance_type_name'] : '';
                $labels[] = $start . '–' . $end . $type;
                $duration = ja_admin_duration_minutes($record['start_time'], $record['end_time']);
                if ($duration !== null) {
                    $hasComplete = true;
                    $stats['minutes'] += $duration;
                } elseif ($record['start_time'] && !$record['end_time'] && $date >= $gridToday) {
                    $hasOpen = true;
                } else {
                    $hasError = true;
                }
            }
            $cell['label'] = implode(' | ', $labels);
            if ($cell['pending_source'] !== '') {
                $cell['state'] = 'pending';
                $cell['label'] = 'Corrección pendiente · ' . $cell['label'];
                $stats['incidents']++;
            } elseif ($hasError) {
                $cell['state'] = 'error';
                $cell['label'] = 'Registro incompleto · ' . $cell['label'];
                $stats['incidents']++;
            } elseif ($hasOpen) {
                $cell['state'] = 'open';
                $cell['label'] = 'Jornada abierta · ' . $cell['label'];
                $stats['incidents']++;
            } elseif ($hasComplete) {
                $cell['state'] = 'worked';
                $stats['worked']++;
            }
        } elseif (isset($gridHolidays[$date])) {
            $cell['state'] = 'holiday';
            $cell['label'] = 'Festivo · ' . $gridHolidays[$date];
        } elseif (isset($gridAbsences[$employeeId][$date])) {
            $cell['state'] = 'absence';
            $cell['label'] = 'Ausencia aprobada · ' . $gridAbsences[$employeeId][$date]['absence_type'];
        } elseif ($scheduleApplies($employeeId, $date)) {
            if ($date < $gridToday) {
                $cell['state'] = 'missing';
                $cell['label'] = 'Falta registro para una jornada prevista';
                $stats['missing']++;
            } else {
                $cell['state'] = 'expected';
                $cell['label'] = 'Jornada prevista';
            }
        }
        $gridCells[$employeeId][$date] = $cell;
    }
    $comparable = $stats['worked'] + $stats['missing'];
    $stats['percent'] = $comparable > 0 ? (int)round(($stats['worked'] / $comparable) * 100) : 100;
    $gridStats[$employeeId] = $stats;
}

$gridBaseQuery = ['group' => 'applications', 'app' => 'cuadricula_asistencia', 'department' => $gridDepartment ?: null, 'role' => $gridRole ?: null];
?>
<section class="application-stack worker-attendance-grid" id="workerAttendanceGrid" data-columns="month" data-symbols="dots" data-color="1">
  <div class="application-toolbar">
    <div><h2>Cuadrícula de asistencia</h2><p><?= ja_h($gridMonthLabel) ?> · <?= count($gridEmployees) ?> personas visibles · cada celda abre el alta o la corrección auditada.</p></div>
    <div class="actions"><a class="button" href="<?= ja_h(ja_admin_query_url($gridBaseQuery + ['month' => $gridPrevious])) ?>">Mes anterior</a><a class="button primary" href="<?= ja_h(ja_admin_query_url($gridBaseQuery + ['month' => date('Y-m')])) ?>">Mes actual</a><a class="button" href="<?= ja_h(ja_admin_query_url($gridBaseQuery + ['month' => $gridNext])) ?>">Mes siguiente</a></div>
  </div>

  <form class="worker-grid-filters" method="get">
    <input type="hidden" name="group" value="applications"><input type="hidden" name="app" value="cuadricula_asistencia">
    <label>Mes<input type="month" name="month" value="<?= ja_h($gridMonth) ?>"></label>
    <label>Departamento<select name="department"><option value="">Todos</option><?php foreach ($gridDepartments as $department): ?><option value="<?= ja_h($department) ?>" <?= $gridDepartment === $department ? 'selected' : '' ?>><?= ja_h($department) ?></option><?php endforeach; ?></select></label>
    <label>Rol<select name="role"><option value="">Todos</option><?php foreach ($gridRoles as $role): ?><option value="<?= ja_h($role) ?>" <?= $gridRole === $role ? 'selected' : '' ?>><?= ja_h($role) ?></option><?php endforeach; ?></select></label>
    <button class="button primary" type="submit">Aplicar</button><a class="button" href="?group=applications&amp;app=cuadricula_asistencia">Limpiar</a>
  </form>

  <section class="card worker-grid-options">
    <div class="worker-grid-option-row">
      <label><span>Saturación</span><input id="workerGridSaturation" type="range" min="20" max="180" value="100"></label><strong id="workerGridSaturationValue">100%</strong>
      <label><span>Fundido a blanco</span><input id="workerGridFade" type="range" min="0" max="95" value="0"></label><strong id="workerGridFadeValue">0%</strong>
      <label class="check"><input id="workerGridColor" type="checkbox" checked> Color ON/OFF</label>
    </div>
    <div class="worker-grid-button-row"><strong>Columnas:</strong><button class="button small active" type="button" data-worker-columns="month">Mes completo</button><button class="button small" type="button" data-worker-columns="last7">Últimos 7 días</button></div>
    <div class="worker-grid-button-row"><strong>Símbolos:</strong><button class="button small active" type="button" data-worker-symbols="dots">● / ·</button><button class="button small" type="button" data-worker-symbols="ticks">✓ / ×</button><button class="button small" type="button" data-worker-symbols="letters">A / F</button><button class="button small" type="button" data-worker-symbols="blank">Vacío</button></div>
  </section>

  <section class="card worker-grid-card">
    <div class="ju-table-caption"><div><h3>Plantilla por día</h3><p>Los estados se calculan a partir de fichajes, horarios, ausencias, festivos y correcciones pendientes.</p></div><span class="badge ok"><?= count($gridDates) ?> días</span></div>
    <div class="worker-grid-search">
      <strong>Buscar persona</strong><input id="workerGridSearch" type="search" placeholder="Pulsa / o Ctrl+F y escribe nombre..." autocomplete="off"><button class="button small" id="workerGridClear" type="button">Limpiar</button>
      <span id="workerGridSearchCount"></span><span class="key">/</span><span class="key">Ctrl+F</span><span class="key">↑↓</span><span class="key">Enter</span><span class="key">Esc</span>
    </div>
    <div class="worker-grid-table-wrap"><table class="worker-grid-table"><thead><tr><th class="worker-grid-name">Persona</th><th class="worker-grid-stats">Stats</th><th class="worker-grid-progress">%</th><?php foreach ($gridDates as $index => $date): $dateObject = new DateTimeImmutable($date); ?><th class="worker-grid-date <?= $index < count($gridDates) - 7 ? 'worker-grid-history' : 'worker-grid-last7' ?> <?= $date === $gridToday ? 'is-today' : '' ?>"><span><?= ja_h($dateObject->format('d/m/Y')) ?></span></th><?php endforeach; ?></tr></thead><tbody>
      <?php foreach ($gridEmployees as $employee): $employeeId = (int)$employee['id']; $stats = $gridStats[$employeeId]; $employeeName = ja_admin_employee_name($employee); ?>
        <tr data-worker-row data-worker-name="<?= ja_h(function_exists('mb_strtolower') ? mb_strtolower($employeeName, 'UTF-8') : strtolower($employeeName)) ?>">
          <th class="worker-grid-name" data-worker-select><span class="worker-grid-person"><strong><?= ja_h($employeeName) ?></strong><small><?= ja_h($employee['departamento'] ?: 'Sin departamento') ?> · <?= ja_h($employee['role'] ?: 'Sin rol') ?></small></span><a class="worker-grid-report" href="<?= ja_h(ja_admin_query_url(['group' => 'applications', 'app' => 'estadisticas_empleados', 'employee_id' => $employeeId])) ?>" title="Abrir estadísticas">↗</a></th>
          <td class="worker-grid-stats"><span class="oktxt"><?= (int)$stats['worked'] ?></span> / <span class="kotxt"><?= (int)$stats['missing'] ?></span><small><?= ja_h(ja_admin_minutes_label((int)$stats['minutes'])) ?></small></td>
          <td class="worker-grid-progress"><div class="worker-grid-progress-track"><i style="width:<?= (int)$stats['percent'] ?>%"></i></div><strong><?= (int)$stats['percent'] ?>%</strong></td>
          <?php foreach ($gridDates as $index => $date): $cell = $gridCells[$employeeId][$date];
            if ($cell['record_id'] > 0) {
                $cellHref = $cell['pending_source'] === 'worker'
                    ? ja_admin_query_url(['group' => 'applications', 'app' => 'solicitudes_personal'])
                    : ja_admin_query_url(['group' => 'tables', 'view' => 'attendance', 'record_id' => $cell['record_id']]);
            } elseif ($cell['state'] === 'absence') {
                $cellHref = ja_admin_query_url(['group' => 'tables', 'view' => 'absences']);
            } elseif ($cell['state'] === 'holiday') {
                $cellHref = ja_admin_query_url(['group' => 'tables', 'view' => 'holidays']);
            } else {
                $cellHref = ja_admin_query_url(['group' => 'tables', 'view' => 'attendance', 'employee_id' => $employeeId, 'date' => $date]);
            }
          ?><td class="worker-grid-cell <?= $index < count($gridDates) - 7 ? 'worker-grid-history' : 'worker-grid-last7' ?> <?= $date === $gridToday ? 'is-today' : '' ?>" data-worker-state="<?= ja_h($cell['state']) ?>"><a href="<?= ja_h($cellHref) ?>" title="<?= ja_h($cell['label']) ?>" aria-label="<?= ja_h($employeeName . ', ' . $date . ': ' . $cell['label']) ?>"><span></span></a></td><?php endforeach; ?>
        </tr>
      <?php endforeach; ?>
      <?php if (!$gridEmployees): ?><tr><td colspan="<?= count($gridDates) + 3 ?>"><div class="empty">No hay personas que coincidan con estos filtros.</div></td></tr><?php endif; ?>
    </tbody></table></div>
    <div class="worker-grid-legend"><span data-legend-state="none"><i></i>sin horario</span><span data-legend-state="worked"><i></i>trabajado</span><span data-legend-state="missing"><i></i>falta registro</span><span data-legend-state="open"><i></i>abierta</span><span data-legend-state="absence"><i></i>ausencia aprobada</span><span data-legend-state="pending"><i></i>corrección pendiente</span><span data-legend-state="holiday"><i></i>festivo</span><span data-legend-state="error"><i></i>error</span></div>
  </section>
</section>
<script>
(() => {
  const root = document.getElementById('workerAttendanceGrid');
  if (!root) return;
  const $ = selector => root.querySelector(selector);
  const $$ = selector => [...root.querySelectorAll(selector)];
  const storage = {
    saturation: 'jocarsa_asistencias_grid_saturation', fade: 'jocarsa_asistencias_grid_fade',
    color: 'jocarsa_asistencias_grid_color', columns: 'jocarsa_asistencias_grid_columns', symbols: 'jocarsa_asistencias_grid_symbols'
  };
  const symbols = {
    dots: {worked:'●',missing:'·',open:'◐',absence:'A',pending:'?',holiday:'◆',error:'!',expected:'○',none:''},
    ticks: {worked:'✓',missing:'×',open:'◐',absence:'A',pending:'?',holiday:'H',error:'!',expected:'○',none:''},
    letters: {worked:'A',missing:'F',open:'O',absence:'J',pending:'P',holiday:'H',error:'E',expected:'V',none:''},
    blank: {worked:'',missing:'',open:'',absence:'',pending:'',holiday:'',error:'',expected:'',none:''}
  };
  const saturation = $('#workerGridSaturation');
  const fade = $('#workerGridFade');
  const color = $('#workerGridColor');
  const apply = () => {
    const saturationValue = Math.max(20, Math.min(180, Number(saturation.value || 100)));
    const fadeValue = Math.max(0, Math.min(95, Number(fade.value || 0)));
    const columnMode = root.dataset.columns || 'month';
    const symbolMode = root.dataset.symbols || 'dots';
    root.style.setProperty('--worker-grid-saturation', `${saturationValue}%`);
    root.style.setProperty('--worker-grid-fade', `${fadeValue}%`);
    root.dataset.color = color.checked ? '1' : '0';
    $('#workerGridSaturationValue').textContent = `${saturationValue}%`;
    $('#workerGridFadeValue').textContent = `${fadeValue}%`;
    $$('[data-worker-columns]').forEach(button => button.classList.toggle('active', button.dataset.workerColumns === columnMode));
    $$('[data-worker-symbols]').forEach(button => button.classList.toggle('active', button.dataset.workerSymbols === symbolMode));
    $$('[data-worker-state]').forEach(cell => {
      const state = cell.dataset.workerState || 'none';
      const target = cell.querySelector('span');
      if (target) target.textContent = symbols[symbolMode]?.[state] ?? '';
    });
    localStorage.setItem(storage.saturation, String(saturationValue));
    localStorage.setItem(storage.fade, String(fadeValue));
    localStorage.setItem(storage.color, color.checked ? '1' : '0');
    localStorage.setItem(storage.columns, columnMode);
    localStorage.setItem(storage.symbols, symbolMode);
  };
  saturation.value = localStorage.getItem(storage.saturation) || '100';
  fade.value = localStorage.getItem(storage.fade) || '0';
  color.checked = localStorage.getItem(storage.color) !== '0';
  root.dataset.columns = localStorage.getItem(storage.columns) || 'month';
  root.dataset.symbols = localStorage.getItem(storage.symbols) || 'dots';
  saturation.addEventListener('input', apply);
  fade.addEventListener('input', apply);
  color.addEventListener('change', apply);
  $$('[data-worker-columns]').forEach(button => button.addEventListener('click', () => { root.dataset.columns = button.dataset.workerColumns; apply(); }));
  $$('[data-worker-symbols]').forEach(button => button.addEventListener('click', () => { root.dataset.symbols = button.dataset.workerSymbols; apply(); }));
  apply();

  const search = $('#workerGridSearch');
  const count = $('#workerGridSearchCount');
  let focused = -1;
  const visibleRows = () => $$('[data-worker-row]').filter(row => row.hidden === false);
  const focusRow = index => {
    const rows = visibleRows();
    $$('.keyboard-focused-row').forEach(row => row.classList.remove('keyboard-focused-row'));
    if (!rows.length) { focused = -1; return; }
    focused = Math.max(0, Math.min(index, rows.length - 1));
    rows[focused].classList.add('keyboard-focused-row');
    rows[focused].scrollIntoView({block:'nearest', inline:'nearest'});
  };
  const filterRows = () => {
    const query = search.value.trim().toLocaleLowerCase('es-ES');
    let visible = 0;
    const rows = $$('[data-worker-row]');
    rows.forEach(row => { row.hidden = !!query && !(row.dataset.workerName || '').includes(query); if (!row.hidden) visible++; });
    count.textContent = query ? `${visible}/${rows.length} visibles` : `${rows.length} personas`;
    focused = visible ? 0 : -1;
    if (query) focusRow(0); else $$('.keyboard-focused-row').forEach(row => row.classList.remove('keyboard-focused-row'));
  };
  search.addEventListener('input', filterRows);
  search.addEventListener('keydown', event => {
    if (event.key === 'Escape') { event.preventDefault(); search.value = ''; filterRows(); }
    if (event.key === 'ArrowDown') { event.preventDefault(); focusRow(focused + 1); }
    if (event.key === 'ArrowUp') { event.preventDefault(); focusRow(focused - 1); }
    if (event.key === 'Enter') { event.preventDefault(); const rows = visibleRows(); if (rows[focused]) { $$('.selected-row').forEach(row => row.classList.remove('selected-row')); rows[focused].classList.add('selected-row'); } }
  });
  $('#workerGridClear').addEventListener('click', () => { search.value = ''; filterRows(); search.focus(); });
  $$('[data-worker-select]').forEach(cell => cell.addEventListener('click', event => { if (event.target.closest('a')) return; const row = cell.closest('tr'); $$('.selected-row').forEach(item => item.classList.remove('selected-row')); row.classList.add('selected-row'); }));
  document.addEventListener('keydown', event => {
    const typing = ['INPUT','TEXTAREA','SELECT'].includes(document.activeElement?.tagName || '');
    if ((event.key === '/' && !typing) || (event.ctrlKey && event.key.toLowerCase() === 'f')) { event.preventDefault(); search.focus(); search.select(); }
  });
  filterRows();
})();
</script>

```

### admin/applications/estadisticas_empleados.php

```php
<?php
declare(strict_types=1);

if (!defined('JA_ADMIN_APPLICATION')) {
    http_response_code(404);
    exit;
}

$department = trim((string)($_GET['department'] ?? ''));
$role = trim((string)($_GET['role'] ?? ''));
$dateFrom = trim((string)($_GET['from'] ?? ''));
$dateTo = trim((string)($_GET['to'] ?? ''));
$rangeMode = (string)($_GET['range_mode'] ?? 'manual');
$weekValue = (string)($_GET['week_value'] ?? '');
$monthValue = (string)($_GET['month_value'] ?? '');
if ($rangeMode === 'week' && preg_match('/^(\d{4})-W(\d{2})$/', $weekValue, $m)) { $d=(new DateTimeImmutable())->setISODate((int)$m[1],(int)$m[2]); $dateFrom=$d->format('Y-m-d'); $dateTo=$d->modify('+6 days')->format('Y-m-d'); }
elseif ($rangeMode === 'month' && preg_match('/^(\d{4})-(\d{2})$/', $monthValue, $m)) { $d=new DateTimeImmutable($monthValue.'-01'); $dateFrom=$d->format('Y-m-d'); $dateTo=$d->modify('last day of this month')->format('Y-m-d'); }
if (!ja_admin_valid_date($dateFrom)) {
    $dateFrom = '';
}
if (!ja_admin_valid_date($dateTo)) {
    $dateTo = '';
}

$employeeSql = "SELECT e.id,e.nombre,e.apellido,e.departamento,e.role,e.work_center_id,e.contract_type_id,
                       c.name AS center_name,ct.name AS contract_name,
                       COUNT(a.id) AS records,COUNT(DISTINCT a.work_date) AS days,
                       MAX(a.work_date) AS last_date,
                       SUM(CASE WHEN a.start_time IS NOT NULL AND a.end_time IS NULL THEN 1 ELSE 0 END) AS open_records
                FROM employees e
                LEFT JOIN work_centers c ON c.id=e.work_center_id AND c.tenant_id=e.tenant_id
                LEFT JOIN contract_types ct ON ct.id=e.contract_type_id AND ct.tenant_id=e.tenant_id
                LEFT JOIN attendance a ON a.employee_id=e.id AND a.tenant_id=e.tenant_id
                WHERE e.tenant_id=:tenant";
$employeeParams = ['tenant' => $tenantId];
if ($department !== '') {
    $employeeSql .= ' AND e.departamento=:department';
    $employeeParams['department'] = $department;
}
if ($role !== '') {
    $employeeSql .= ' AND e.role=:role';
    $employeeParams['role'] = $role;
}
$employeeSql .= ' GROUP BY e.id ORDER BY e.apellido,e.nombre';
$statement = $pdo->prepare($employeeSql);
$statement->execute($employeeParams);
$filteredEmployees = $statement->fetchAll();

$selectedId = (int)($_GET['employee_id'] ?? 0);
$selected = null;
foreach ($filteredEmployees as $candidate) {
    if ((int)$candidate['id'] === $selectedId) {
        $selected = $candidate;
        break;
    }
}
if (!$selected && $filteredEmployees) {
    $selected = $filteredEmployees[0];
    $selectedId = (int)$selected['id'];
}

$departmentsStatement = $pdo->prepare("SELECT DISTINCT departamento FROM employees WHERE tenant_id=:tenant AND departamento IS NOT NULL AND departamento<>'' ORDER BY departamento");
$departmentsStatement->execute(['tenant' => $tenantId]);
$departmentOptions = $departmentsStatement->fetchAll();
$rolesStatement = $pdo->prepare("SELECT DISTINCT role FROM employees WHERE tenant_id=:tenant AND role IS NOT NULL AND role<>'' ORDER BY role");
$rolesStatement->execute(['tenant' => $tenantId]);
$roleOptions = $rolesStatement->fetchAll();

$stats = [];
$employeeAttendance = [];
$monthly = [];
$weekdayCounts = array_fill(1, 7, 0);
$arrivalBuckets = [];
$employeeIncidents = [];
$employeeAbsences = [];
$employeeVacations = [];
$scheduleRows = [];
$totalMinutes = 0;
$durations = [];
$daysWorked = [];
$startMinutes = [];
$endMinutes = [];
$closedRecords = 0;
$openRecords = 0;
$missingStart = 0;
$lateCount = 0;
$earlyCount = 0;
$scheduledMinutes = 0;
$workedScheduledDays = 0;
$typeMinutes = [];
$meetingMinutes = 0;
$meetingRows = [];

if ($selected) {
    $attendanceSql = 'SELECT a.*,ft.name AS attendance_type_name FROM attendance a LEFT JOIN attendance_types ft ON ft.id=a.attendance_type_id AND ft.tenant_id=a.tenant_id WHERE a.tenant_id=:tenant AND a.employee_id=:employee';
    $attendanceParams = ['tenant' => $tenantId, 'employee' => $selectedId];
    if ($dateFrom !== '') {
        $attendanceSql .= ' AND a.work_date>=:date_from';
        $attendanceParams['date_from'] = $dateFrom;
    }
    if ($dateTo !== '') {
        $attendanceSql .= ' AND a.work_date<=:date_to';
        $attendanceParams['date_to'] = $dateTo;
    }
    $attendanceSql .= ' ORDER BY a.work_date DESC,a.start_time DESC,a.id DESC';
    $statement = $pdo->prepare($attendanceSql);
    $statement->execute($attendanceParams);
    $employeeAttendance = $statement->fetchAll();

    $attendanceByDate = [];
    foreach ($employeeAttendance as &$record) {
        $duration = ja_admin_duration_minutes($record['start_time'], $record['end_time']);
        $record['duration_minutes'] = $duration;
        $weekday = (int)(new DateTimeImmutable($record['work_date']))->format('N');
        $record['weekday'] = $weekday;
        $weekdayCounts[$weekday]++;
        $daysWorked[$record['work_date']] = true;
        $attendanceByDate[$record['work_date']][] = $record;
        $month = substr((string)$record['work_date'], 0, 7);
        $monthly[$month] ??= ['month' => $month, 'minutes' => 0, 'days' => []];
        $monthly[$month]['days'][$record['work_date']] = true;
        if ($record['start_time']) {
            $minutes = ja_admin_time_minutes($record['start_time']);
            if ($minutes !== null) {
                $startMinutes[] = $minutes;
                $hour = intdiv($minutes, 60);
                $arrivalBuckets[$hour] = ($arrivalBuckets[$hour] ?? 0) + 1;
            }
        } else {
            $missingStart++;
        }
        if ($record['end_time']) {
            $minutes = ja_admin_time_minutes($record['end_time']);
            if ($minutes !== null) {
                $endMinutes[] = $minutes;
            }
        }
        if ($duration !== null) {
            $durations[] = $duration;
            $totalMinutes += $duration;
            $typeLabel = trim((string)($record['attendance_type_name'] ?? '')) ?: 'Sin tipo';
            $typeMinutes[$typeLabel] = ($typeMinutes[$typeLabel] ?? 0) + $duration;
            $closedRecords++;
            $monthly[$month]['minutes'] += $duration;
        } elseif ($record['start_time'] && !$record['end_time']) {
            $openRecords++;
        }
    }
    unset($record);
    $meetingSql="SELECT * FROM meetings WHERE tenant_id=:tenant AND (scope_type='all' OR (scope_type='role' AND target_role=:role) OR (scope_type='employee' AND employee_id=:employee))"; $meetingParams=['tenant'=>$tenantId,'role'=>(string)($selected['role']??''),'employee'=>$selectedId];
    if($dateFrom!==''){ $meetingSql.=' AND event_date>=:mf'; $meetingParams['mf']=$dateFrom; } if($dateTo!==''){ $meetingSql.=' AND event_date<=:mt'; $meetingParams['mt']=$dateTo; }
    $meetingSql.=' ORDER BY event_date DESC,start_time DESC'; $mq=$pdo->prepare($meetingSql);$mq->execute($meetingParams);$meetingRows=$mq->fetchAll();
    foreach($meetingRows as $meeting){$mm=ja_admin_duration_minutes($meeting['start_time'],$meeting['end_time']);if($mm!==null){$meetingMinutes+=$mm;$totalMinutes+=$mm;$typeMinutes['Reuniones · fuera del horario establecido']=($typeMinutes['Reuniones · fuera del horario establecido']??0)+$mm;}}
    arsort($typeMinutes);
    ksort($monthly);
    $monthly = array_slice(array_values($monthly), -12);
    ksort($arrivalBuckets);

    $statement = $pdo->prepare('SELECT * FROM schedules WHERE tenant_id=:tenant AND employee_id=:employee ORDER BY weekday,start_time');
    $statement->execute(['tenant' => $tenantId, 'employee' => $selectedId]);
    $scheduleRows = $statement->fetchAll();
    foreach ($attendanceByDate as $date => $dayRecords) {
        $dayNumber = (int)(new DateTimeImmutable($date))->format('N');
        $applicable = array_values(array_filter($scheduleRows, static function (array $schedule) use ($date, $dayNumber): bool {
            return (int)$schedule['weekday'] === $dayNumber
                && (!$schedule['valid_from'] || $schedule['valid_from'] <= $date)
                && (!$schedule['valid_until'] || $schedule['valid_until'] >= $date);
        }));
        if (!$applicable) {
            continue;
        }
        $actualStarts = array_values(array_filter(array_map(static fn(array $row): ?int => ja_admin_time_minutes($row['start_time']), $dayRecords), static fn(?int $v): bool => $v !== null));
        $actualEnds = array_values(array_filter(array_map(static fn(array $row): ?int => ja_admin_time_minutes($row['end_time']), $dayRecords), static fn(?int $v): bool => $v !== null));
        if (!$actualStarts || !$actualEnds) {
            continue;
        }
        $workedScheduledDays++;
        $scheduledStarts = [];
        $scheduledEnds = [];
        foreach ($applicable as $schedule) {
            $duration = ja_admin_duration_minutes($schedule['start_time'], $schedule['end_time']);
            if ($duration !== null) {
                $scheduledMinutes += $duration;
            }
            $scheduledStarts[] = ja_admin_time_minutes($schedule['start_time']) ?? 0;
            $scheduledEnds[] = ja_admin_time_minutes($schedule['end_time']) ?? 0;
        }
        if (min($actualStarts) > min($scheduledStarts) + 5) {
            $lateCount++;
        }
        if (max($actualEnds) < max($scheduledEnds) - 5) {
            $earlyCount++;
        }
    }

    $statement = $pdo->prepare('SELECT * FROM incidents WHERE tenant_id=:tenant AND employee_id=:employee ORDER BY incident_date DESC LIMIT 100');
    $statement->execute(['tenant' => $tenantId, 'employee' => $selectedId]);
    $employeeIncidents = $statement->fetchAll();
    $statement = $pdo->prepare('SELECT * FROM absences WHERE tenant_id=:tenant AND employee_id=:employee ORDER BY start_date DESC LIMIT 100');
    $statement->execute(['tenant' => $tenantId, 'employee' => $selectedId]);
    $employeeAbsences = $statement->fetchAll();
    $statement = $pdo->prepare('SELECT * FROM vacations WHERE tenant_id=:tenant AND employee_id=:employee ORDER BY start_date DESC LIMIT 100');
    $statement->execute(['tenant' => $tenantId, 'employee' => $selectedId]);
    $employeeVacations = $statement->fetchAll();

    sort($durations);
    $durationCount = count($durations);
    $median = $durationCount ? ($durationCount % 2 ? $durations[intdiv($durationCount, 2)] : ($durations[$durationCount / 2 - 1] + $durations[$durationCount / 2]) / 2) : null;
    $dates = array_keys($daysWorked);
    sort($dates);
    $stats = [
        'first_date' => $dates[0] ?? null,
        'last_date' => $dates ? $dates[count($dates) - 1] : null,
        'average' => $durationCount ? $totalMinutes / $durationCount : null,
        'median' => $median,
        'minimum' => $durations[0] ?? null,
        'maximum' => $durations ? $durations[count($durations) - 1] : null,
        'average_start' => $startMinutes ? array_sum($startMinutes) / count($startMinutes) : null,
        'average_end' => $endMinutes ? array_sum($endMinutes) / count($endMinutes) : null,
        'earliest_start' => $startMinutes ? min($startMinutes) : null,
        'latest_start' => $startMinutes ? max($startMinutes) : null,
    ];
}

$totalRecords = count($employeeAttendance);
$closure = $totalRecords ? round(($closedRecords / $totalRecords) * 100, 1) : null;
$punctuality = $workedScheduledDays ? max(0, round((1 - ($lateCount / $workedScheduledDays)) * 100, 1)) : null;
$balance = $scheduledMinutes ? $totalMinutes - $scheduledMinutes : null;
$maxMonthly = max(1, ...array_map(static fn(array $row): int => (int)$row['minutes'], $monthly ?: [['minutes' => 1]]));
$maxWeekday = max(1, ...$weekdayCounts);
$maxArrival = max(1, ...array_values($arrivalBuckets ?: [1]));
$timeFromMinutes = static function (int|float|null $minutes): string {
    if ($minutes === null) {
        return '—';
    }
    $minutes = (int)round($minutes);
    return sprintf('%02d:%02d', intdiv($minutes, 60) % 24, $minutes % 60);
};
?>
<section class="employee-statistics">
  <aside class="statistics-sidebar">
    <article class="statistics-filter">
      <h3>📊 Estadísticas</h3><p>Filtra y selecciona un empleado para ver su analítica completa de asistencia.</p>
      <form method="get">
        <input type="hidden" name="group" value="applications"><input type="hidden" name="app" value="estadisticas_empleados">
        <label>Departamento<select name="department"><option value="">Todos</option><?php foreach ($departmentOptions as $option): ?><option value="<?= ja_h($option['departamento']) ?>" <?= $department === $option['departamento'] ? 'selected' : '' ?>><?= ja_h($option['departamento']) ?></option><?php endforeach; ?></select></label>
        <label>Rol<select name="role"><option value="">Todos</option><?php foreach ($roleOptions as $option): ?><option value="<?= ja_h($option['role']) ?>" <?= $role === $option['role'] ? 'selected' : '' ?>><?= ja_h($option['role']) ?></option><?php endforeach; ?></select></label>
        <div class="range-switch"><label><input type="radio" name="range_mode" value="manual" <?= $rangeMode==='manual'?'checked':'' ?>> Fechas</label><label><input type="radio" name="range_mode" value="week" <?= $rangeMode==='week'?'checked':'' ?>> Semana</label><label><input type="radio" name="range_mode" value="month" <?= $rangeMode==='month'?'checked':'' ?>> Mes</label></div><label class="range-manual">Desde<input type="date" name="from" value="<?= ja_h($dateFrom) ?>"></label><label class="range-manual">Hasta<input type="date" name="to" value="<?= ja_h($dateTo) ?>"></label><label class="range-week">Semana del año<input type="week" name="week_value" value="<?= ja_h($weekValue ?: date('o-\WW')) ?>"></label><label class="range-month">Mes del año<input type="month" name="month_value" value="<?= ja_h($monthValue ?: date('Y-m')) ?>"></label>
        <?php if ($selectedId): ?><input type="hidden" name="employee_id" value="<?= $selectedId ?>"><?php endif; ?>
        <button class="button primary" type="submit">Aplicar</button><a class="button" href="?group=applications&app=estadisticas_empleados">Limpiar</a>
      </form>
    </article>
    <div class="statistics-employee-list">
      <?php if (!$filteredEmployees): ?><div class="empty">No hay empleados con esos filtros.</div><?php endif; ?>
      <?php foreach ($filteredEmployees as $employee): $url = ja_admin_query_url(['group' => 'applications', 'app' => 'estadisticas_empleados', 'employee_id' => $employee['id'], 'department' => $department, 'role' => $role, 'from' => $dateFrom, 'to' => $dateTo, 'range_mode'=>$rangeMode, 'week_value'=>$weekValue, 'month_value'=>$monthValue]); ?>
        <a class="statistics-employee <?= (int)$employee['id'] === $selectedId ? 'active' : '' ?>" href="<?= ja_h($url) ?>"><strong><?= ja_h(ja_admin_employee_name($employee)) ?></strong><span><?= ja_h($employee['departamento'] ?: 'Sin departamento') ?> · <?= ja_h($employee['role'] ?: 'Sin rol') ?></span><small><?= (int)$employee['days'] ?> días · <?= (int)$employee['records'] ?> registros · <?= (int)$employee['open_records'] > 0 ? 'abierta' : 'normal' ?></small></a>
      <?php endforeach; ?>
    </div>
  </aside>

  <div class="statistics-main">
    <?php if (!$selected): ?><div class="empty">Selecciona un empleado para cargar sus estadísticas.</div><?php else: ?>
      <section class="statistics-hero">
        <div><span class="hero-label">Empleado seleccionado</span><h2><?= ja_h(ja_admin_employee_name($selected)) ?></h2><p><?= ja_h($selected['departamento'] ?: 'Sin departamento') ?> · <?= ja_h($selected['role'] ?: 'Sin rol') ?> · <?= ja_h($selected['center_name'] ?: 'Sin centro') ?></p></div>
        <div class="statistics-hero-meta"><div><strong><?= ja_h($stats['first_date'] ?: '—') ?></strong><span>Primera asistencia</span></div><div><strong><?= ja_h($stats['last_date'] ?: '—') ?></strong><span>Última asistencia</span></div><div><strong><?= $closure === null ? '—' : ja_h((string)$closure . '%') ?></strong><span>Cierre de registros</span></div><div><strong><?= $punctuality === null ? '—' : ja_h((string)$punctuality . '%') ?></strong><span>Puntualidad estimada</span></div></div>
      </section>

      <section class="statistics-kpis">
      <?php $kpis = [
          ['Días trabajados', count($daysWorked), 'Días únicos con registro'],
          ['Horas totales', ja_admin_minutes_label($totalMinutes), 'Suma de registros cerrados'],
          ['Media diaria', $daysWorked ? ja_admin_minutes_label($totalMinutes / count($daysWorked)) : '—', 'Promedio por día trabajado'],
          ['Media por registro', ja_admin_minutes_label($stats['average'] ?? null), 'Promedio de fichajes cerrados'],
          ['Mediana', ja_admin_minutes_label($stats['median'] ?? null), 'Duración central'],
          ['Jornada máxima', ja_admin_minutes_label($stats['maximum'] ?? null), 'Registro cerrado más largo'],
          ['Jornada mínima', ja_admin_minutes_label($stats['minimum'] ?? null), 'Registro cerrado más corto'],
          ['Registros abiertos', $openRecords, 'Sin hora de salida'],
          ['Entrada media', $timeFromMinutes($stats['average_start'] ?? null), 'Hora media de entrada'],
          ['Salida media', $timeFromMinutes($stats['average_end'] ?? null), 'Hora media de salida'],
          ['Más temprana', $timeFromMinutes($stats['earliest_start'] ?? null), 'Entrada más temprana'],
          ['Más tardía', $timeFromMinutes($stats['latest_start'] ?? null), 'Entrada más tardía'],
          ['Incidencias', count($employeeIncidents), 'Eventos registrados'],
          ['Ausencias', count($employeeAbsences), 'Solicitudes o ausencias'],
          ['Vacaciones', count($employeeVacations), 'Periodos registrados'],
          ['Saldo horario', ja_admin_minutes_label($balance), 'Trabajado vs horario teórico'],
      ]; foreach ($kpis as $kpi): ?><article class="statistics-kpi"><small><?= ja_h($kpi[0]) ?></small><strong><?= ja_h($kpi[1]) ?></strong><span><?= ja_h($kpi[2]) ?></span></article><?php endforeach; ?>
      </section>

      <article class="card"><div class="ju-table-caption"><div><h3>Horas por tipo</h3><p>Total del periodo, incluyendo reuniones/eventos planificados.</p></div><span class="ju-pill"><?= ja_h(ja_admin_minutes_label($totalMinutes)) ?></span></div><div class="statistics-type-totals"><?php if(!$typeMinutes):?><p>No hay horas cerradas en el periodo.</p><?php endif;?><?php foreach($typeMinutes as $typeName=>$minutes):?><div><strong><?= ja_h($typeName) ?></strong><span><?= ja_h(ja_admin_minutes_label($minutes)) ?></span></div><?php endforeach;?></div></article>

      <section class="statistics-two">
        <article class="card"><div class="ju-table-caption"><div><h3>Evolución mensual</h3><p>Horas y días por mes.</p></div><span class="ju-pill">12 meses</span></div><div class="statistics-bars"><?php if (!$monthly): ?><p>No hay datos mensuales.</p><?php endif; ?><?php foreach ($monthly as $row): ?><div class="statistics-bar"><span><?= ja_h($row['month']) ?></span><div><i style="width:<?= (int)round(((int)$row['minutes'] / $maxMonthly) * 100) ?>%"></i></div><strong><?= ja_h(ja_admin_minutes_label((int)$row['minutes'])) ?></strong></div><?php endforeach; ?></div></article>
        <article class="card"><div class="ju-table-caption"><div><h3>Distribución semanal</h3><p>Registros por día.</p></div><span class="ju-pill">Semana</span></div><div class="statistics-bars"><?php foreach ($weekdays as $number => $day): ?><div class="statistics-bar"><span><?= ja_h($day) ?></span><div><i style="width:<?= (int)round(($weekdayCounts[$number] / $maxWeekday) * 100) ?>%"></i></div><strong><?= $weekdayCounts[$number] ?></strong></div><?php endforeach; ?></div></article>
      </section>

      <section class="statistics-two">
        <article class="card"><div class="ju-table-caption"><div><h3>Histograma de entradas</h3><p>Cantidad de entradas por hora.</p></div><span class="ju-pill">Horas</span></div><div class="statistics-bars"><?php if (!$arrivalBuckets): ?><p>No hay horas de entrada.</p><?php endif; ?><?php foreach ($arrivalBuckets as $hour => $count): ?><div class="statistics-bar"><span><?= sprintf('%02d:00', $hour) ?></span><div><i style="width:<?= (int)round(($count / $maxArrival) * 100) ?>%"></i></div><strong><?= $count ?></strong></div><?php endforeach; ?></div></article>
        <article class="card"><div class="ju-table-caption"><div><h3>Cumplimiento horario</h3><p>Comparación estimada con el horario.</p></div><span class="ju-pill">Estimado</span></div><dl class="statistics-summary"><div><dt>Días comparables</dt><dd><?= $workedScheduledDays ?></dd></div><div><dt>Llegadas tarde</dt><dd><?= $lateCount ?></dd></div><div><dt>Salidas anticipadas</dt><dd><?= $earlyCount ?></dd></div><div><dt>Horas teóricas</dt><dd><?= ja_h(ja_admin_minutes_label($scheduledMinutes ?: null)) ?></dd></div><div><dt>Horas reales</dt><dd><?= ja_h(ja_admin_minutes_label($totalMinutes)) ?></dd></div><div><dt>Saldo</dt><dd><?= ja_h(ja_admin_minutes_label($balance)) ?></dd></div></dl></article>
      </section>

      <article class="card"><div class="ju-table-caption"><div><h3>Últimos registros de asistencia</h3><p>Hasta 240 registros del periodo seleccionado.</p></div><a class="button small" href="?group=tables&view=attendance">Abrir tabla</a></div><div class="statistics-table-wrap"><table><thead><tr><th>Fecha</th><th>Día</th><th>Tipo</th><th>Entrada</th><th>Salida</th><th>Duración</th><th>Estado</th></tr></thead><tbody><?php if (!$employeeAttendance): ?><tr><td colspan="7">No hay registros.</td></tr><?php endif; ?><?php foreach (array_slice($employeeAttendance, 0, 240) as $record): $state = !$record['start_time'] ? 'Sin entrada' : (!$record['end_time'] ? 'Abierto' : 'Cerrado'); ?><tr><td><?= ja_h($record['work_date']) ?></td><td><?= ja_h($weekdays[$record['weekday']]) ?></td><td><?= ja_h($record['attendance_type_name'] ?: 'Sin tipo') ?></td><td><?= ja_h(ja_admin_short_time($record['start_time'])) ?></td><td><?= ja_h(ja_admin_short_time($record['end_time'])) ?></td><td><?= ja_h(ja_admin_minutes_label($record['duration_minutes'])) ?></td><td><span class="badge <?= $state === 'Cerrado' ? 'ok' : 'warning' ?>"><?= ja_h($state) ?></span></td></tr><?php endforeach; ?></tbody></table></div></article>

      <section class="statistics-two"><article class="card"><h3>Incidencias</h3><div class="statistics-table-wrap"><table><thead><tr><th>Fecha</th><th>Tipo</th><th>Notas</th></tr></thead><tbody><?php if (!$employeeIncidents): ?><tr><td colspan="3">Sin incidencias.</td></tr><?php endif; ?><?php foreach ($employeeIncidents as $incident): ?><tr><td><?= ja_h($incident['incident_date']) ?></td><td><?= ja_h($incident['type']) ?></td><td class="wrap"><?= ja_h($incident['notes']) ?></td></tr><?php endforeach; ?></tbody></table></div></article><article class="card"><h3>Ausencias y vacaciones</h3><div class="statistics-table-wrap"><table><thead><tr><th>Tipo</th><th>Inicio</th><th>Fin</th><th>Estado</th></tr></thead><tbody><?php if (!$employeeAbsences && !$employeeVacations): ?><tr><td colspan="4">Sin periodos.</td></tr><?php endif; ?><?php foreach ($employeeAbsences as $absence): ?><tr><td><?= ja_h($absence['absence_type']) ?></td><td><?= ja_h($absence['start_date']) ?></td><td><?= ja_h($absence['end_date'] ?: 'Sin fecha final') ?></td><td><?= ja_h($absence['status']) ?></td></tr><?php endforeach; ?><?php foreach ($employeeVacations as $vacation): ?><tr><td>Vacaciones</td><td><?= ja_h($vacation['start_date']) ?></td><td><?= ja_h($vacation['end_date']) ?></td><td><?= $vacation['approved'] ? 'Aprobado' : 'Pendiente' ?></td></tr><?php endforeach; ?></tbody></table></div></article></section>
    <?php endif; ?>
  </div>
</section>

```

### admin/applications/horario_semanal.php

```php
<?php
declare(strict_types=1);

if (!defined('JA_ADMIN_APPLICATION')) {
    http_response_code(404);
    exit;
}

$selectedId = (int)($_GET['employee_id'] ?? 0);
if ($selectedId <= 0 && $employees) {
    $selectedId = (int)$employees[0]['id'];
}

$selectedEmployee = null;
foreach ($employees as $employee) {
    if ((int)$employee['id'] === $selectedId) {
        $selectedEmployee = $employee;
        break;
    }
}

$employeeRole = trim((string)($selectedEmployee['role'] ?? ''));
$typeStatement = $pdo->prepare(
    "SELECT id,name,is_default,active FROM attendance_types
     WHERE tenant_id=:tenant AND lower(trim(role))=lower(trim(:role))
     ORDER BY is_default DESC,name COLLATE NOCASE"
);
$typeStatement->execute(['tenant' => $tenantId, 'role' => $employeeRole]);
$scheduleTypes = $typeStatement->fetchAll();
$activeScheduleTypes = array_values(array_filter($scheduleTypes, static fn(array $type): bool => !empty($type['active'])));
$typeIds = array_map(static fn(array $type): int => (int)$type['id'], $scheduleTypes);
$typeNames = [0 => 'Horario general'];
foreach ($scheduleTypes as $scheduleType) { $typeNames[(int)$scheduleType['id']] = (string)$scheduleType['name']; }

$segmentsStatement = $pdo->prepare(
    "SELECT valid_from,valid_until,COUNT(*) block_count,
            MIN(start_time) first_time,MAX(end_time) last_time
     FROM schedules
     WHERE tenant_id=:tenant AND employee_id=:employee
     GROUP BY valid_from,valid_until
     ORDER BY CASE WHEN valid_from IS NULL THEN 1 ELSE 0 END,
              valid_from DESC,
              CASE WHEN valid_until IS NULL THEN 1 ELSE 0 END,
              valid_until DESC"
);
$segmentsStatement->execute(['tenant' => $tenantId, 'employee' => $selectedId]);
$scheduleSegments = $segmentsStatement->fetchAll();

// segment_from/segment_until identify the existing period being edited. The
// literal __NULL__ preserves legacy rows whose start/end date is SQL NULL.
$requestedSegmentFrom = array_key_exists('segment_from', $_GET) ? (string)$_GET['segment_from'] : null;
$requestedSegmentUntil = array_key_exists('segment_until', $_GET) ? (string)$_GET['segment_until'] : null;
$isNewSegment = isset($_GET['new_segment']) || !$scheduleSegments;

if (!$isNewSegment && $requestedSegmentFrom === null) {
    $first = $scheduleSegments[0];
    $requestedSegmentFrom = $first['valid_from'] === null ? '__NULL__' : (string)$first['valid_from'];
    $requestedSegmentUntil = $first['valid_until'] === null ? '__NULL__' : (string)$first['valid_until'];
}

$originalFromIsNull = !$isNewSegment && $requestedSegmentFrom === '__NULL__';
$originalUntilIsNull = !$isNewSegment && ($requestedSegmentUntil === '__NULL__' || $requestedSegmentUntil === null || $requestedSegmentUntil === '');
$originalValidFrom = $originalFromIsNull ? null : ($requestedSegmentFrom ?? null);
$originalValidUntil = $originalUntilIsNull ? null : $requestedSegmentUntil;

$validFrom = $isNewSegment ? date('Y-m-d') : (string)($originalValidFrom ?? '');
$validUntil = $isNewSegment ? '' : (string)($originalValidUntil ?? '');
if (isset($_GET['draft_from']) && ja_admin_valid_date((string)$_GET['draft_from'])) {
    $validFrom = (string)$_GET['draft_from'];
}
if (isset($_GET['draft_until']) && ja_admin_valid_date((string)$_GET['draft_until'])) {
    $validUntil = (string)$_GET['draft_until'];
}

$existingSlots = array_fill(1, 7, []);
if ($selectedId > 0 && !$isNewSegment) {
    $sql = 'SELECT weekday,start_time,end_time,attendance_type_id FROM schedules
            WHERE tenant_id=:tenant AND employee_id=:employee';
    $params = ['tenant' => $tenantId, 'employee' => $selectedId];
    if ($originalFromIsNull) {
        $sql .= ' AND valid_from IS NULL';
    } else {
        $sql .= ' AND valid_from=:from';
        $params['from'] = $originalValidFrom;
    }
    if ($originalUntilIsNull) {
        $sql .= ' AND valid_until IS NULL';
    } else {
        $sql .= ' AND valid_until=:until';
        $params['until'] = $originalValidUntil;
    }
    $sql .= ' ORDER BY weekday,start_time';
    $statement = $pdo->prepare($sql);
    $statement->execute($params);
    foreach ($statement->fetchAll() as $row) {
        $start = ja_admin_time_minutes($row['start_time']);
        $end = ja_admin_time_minutes($row['end_time']);
        if ($start === null || $end === null) continue;
        if ($end <= $start) $end += 1440;
        $typeId = $row['attendance_type_id'] === null ? 0 : (int)$row['attendance_type_id'];
        for ($minutes = $start; $minutes < min($end, 1440); $minutes += 30) {
            $existingSlots[(int)$row['weekday']][sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60)] = $typeId;
        }
    }
}

$selectedSlotCount = array_sum(array_map('count', $existingSlots));
$palette = ['#ef8f73','#df7e68','#ce6f5e','#bd604f','#ac5142','#9b4336','#8a352c','#79281f'];
$segmentLabel = static function (?string $from, ?string $until): string {
    $fromLabel = $from ? (new DateTimeImmutable($from))->format('d/m/Y') : 'Sin fecha de inicio';
    $untilLabel = $until ? (new DateTimeImmutable($until))->format('d/m/Y') : 'Sin fecha final';
    return $fromLabel . ' → ' . $untilLabel;
};
?>
<section class="application-stack schedule-editor" id="typedScheduleEditor">
  <div class="application-toolbar">
    <div><h2>Definir horario semanal</h2><p>Cada segmento tiene sus propias fechas de vigencia y sus bloques semanales.</p></div>
    <a class="button primary" href="<?= ja_h(ja_admin_query_url(['group'=>'applications','app'=>'horario_semanal','employee_id'=>$selectedId,'new_segment'=>1])) ?>">Nuevo segmento</a>
  </div>

  <form method="get" class="schedule-selector schedule-employee-selector">
    <input type="hidden" name="group" value="applications"><input type="hidden" name="app" value="horario_semanal">
    <label>Empleado<select name="employee_id" required><?php foreach ($employees as $employee): ?><option value="<?= (int)$employee['id'] ?>" <?= $selectedId === (int)$employee['id'] ? 'selected' : '' ?>><?= ja_h(ja_admin_employee_name($employee)) ?></option><?php endforeach; ?></select></label>
    <button class="button" type="submit">Ver horarios</button>
  </form>

  <?php if ($scheduleSegments): ?>
  <article class="card schedule-segments-card">
    <div class="ju-table-caption"><div><h3>Segmentos de horario</h3><p>Selecciona un segmento para editar sus fechas y su cuadrícula. Los cambios de fecha se guardan con el botón inferior.</p></div><span class="badge ok"><?= count($scheduleSegments) ?> segmentos</span></div>
    <div class="schedule-segment-list">
      <?php foreach ($scheduleSegments as $segment):
        $segmentFrom = $segment['valid_from'] === null ? '__NULL__' : (string)$segment['valid_from'];
        $segmentUntil = $segment['valid_until'] === null ? '__NULL__' : (string)$segment['valid_until'];
        $active = !$isNewSegment && $segmentFrom === $requestedSegmentFrom && $segmentUntil === ($requestedSegmentUntil ?? '__NULL__');
      ?>
        <a class="schedule-segment-pill <?= $active ? 'active' : '' ?>" href="<?= ja_h(ja_admin_query_url(['group'=>'applications','app'=>'horario_semanal','employee_id'=>$selectedId,'segment_from'=>$segmentFrom,'segment_until'=>$segmentUntil])) ?>">
          <strong><?= ja_h($segmentLabel($segment['valid_from'], $segment['valid_until'])) ?></strong>
          <small><?= (int)$segment['block_count'] ?> bloques · <?= ja_h(ja_admin_short_time($segment['first_time'])) ?>–<?= ja_h(ja_admin_short_time($segment['last_time'])) ?></small>
          <?php if ($segment['valid_from'] === null): ?><span class="badge warning">Fecha inicial pendiente</span><?php endif; ?>
        </a>
      <?php endforeach; ?>
    </div>
  </article>
  <?php endif; ?>

  <?php if ($selectedId > 0): ?>
  <form method="post" id="scheduleGridForm">
    <input type="hidden" name="csrf" value="<?= ja_h($csrf) ?>">
    <input type="hidden" name="action" value="save_schedule_grid">
    <input type="hidden" name="employee_id" value="<?= $selectedId ?>">
    <input type="hidden" name="editing_existing_segment" value="<?= $isNewSegment ? '0' : '1' ?>">
    <input type="hidden" name="original_valid_from_is_null" value="<?= $originalFromIsNull ? '1' : '0' ?>">
    <input type="hidden" name="original_valid_until_is_null" value="<?= $originalUntilIsNull ? '1' : '0' ?>">
    <input type="hidden" name="original_valid_from" value="<?= ja_h((string)($originalValidFrom ?? '')) ?>">
    <input type="hidden" name="original_valid_until" value="<?= ja_h((string)($originalValidUntil ?? '')) ?>">

    <section class="card schedule-period-editor">
      <div class="ju-table-caption">
        <div><h3><?= $isNewSegment ? 'Nuevo segmento' : 'Vigencia del segmento' ?></h3><p><?= $isNewSegment ? 'Define el periodo antes de guardar la nueva cuadrícula.' : 'Estas fechas pertenecen al segmento seleccionado. Modificarlas trasladará todos sus bloques al nuevo periodo.' ?></p></div>
        <span class="badge <?= $isNewSegment ? 'warning' : 'ok' ?>"><?= $isNewSegment ? 'Sin guardar' : 'Editando segmento' ?></span>
      </div>
      <div class="grid-3">
        <label>Fecha de inicio<input type="date" name="valid_from" value="<?= ja_h($validFrom) ?>" required></label>
        <label>Fecha final opcional<input type="date" name="valid_until" value="<?= ja_h($validUntil) ?>"></label>
        <div class="schedule-period-preview"><small>Periodo resultante</small><strong id="schedulePeriodPreview"><?= ja_h($segmentLabel($validFrom ?: null, $validUntil ?: null)) ?></strong></div>
      </div>
      <?php if ($originalFromIsNull && !$isNewSegment): ?><div class="notice warning">Este es un segmento antiguo sin fecha inicial. Introduce una fecha y guarda para normalizarlo.</div><?php endif; ?>
      <p class="form-hint">La fecha inicial es obligatoria. Deja la fecha final vacía únicamente cuando el horario deba continuar indefinidamente.</p>
    </section>

    <section class="card schedule-type-palette">
      <div><h3>Tipo que vas a pintar</h3><p><?= $activeScheduleTypes ? 'Selecciona un tipo y pulsa o arrastra sobre la cuadrícula.' : 'Este rol no tiene tipos activos; el horario se guardará sin tipo.' ?></p></div>
      <div class="schedule-type-buttons">
        <button type="button" class="schedule-type-choice active" data-schedule-type="0" style="--type-color:#b9aaa5"><i></i><span>Horario general</span></button>
        <?php foreach ($activeScheduleTypes as $index => $type): ?><button type="button" class="schedule-type-choice" data-schedule-type="<?= (int)$type['id'] ?>" style="--type-color:<?= ja_h($palette[$index % count($palette)]) ?>"><i></i><span><?= ja_h($type['name']) ?></span><?php if (!empty($type['is_default'])): ?><small>Predeterminado</small><?php endif; ?></button><?php endforeach; ?>
        <button type="button" class="schedule-type-choice schedule-eraser" data-schedule-type="erase"><i></i><span>Borrar</span></button>
      </div>
    </section>

    <div class="schedule-grid-wrap">
      <table class="schedule-grid typed-schedule-grid">
        <thead><tr><th>Hora</th><?php foreach ($weekdays as $day): ?><th><?= ja_h($day) ?></th><?php endforeach; ?></tr></thead>
        <tbody>
        <?php for ($minutes = 0; $minutes < 1440; $minutes += 30): $time = sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60); ?>
          <tr><td><?= ja_h($time) ?></td><?php foreach ($weekdays as $dayNumber => $day): $typeId = $existingSlots[$dayNumber][$time] ?? null; $selected = $typeId !== null; $typeIndex = array_search((int)$typeId, $typeIds, true); $color = $typeId === 0 ? '#b9aaa5' : ($typeIndex === false ? '#ef8f73' : $palette[$typeIndex % count($palette)]); ?><td class="schedule-slot <?= $selected ? 'selected' : '' ?>" tabindex="0" role="checkbox" aria-checked="<?= $selected ? 'true' : 'false' ?>" data-day="<?= $dayNumber ?>" data-time="<?= ja_h($time) ?>" data-type-id="<?= $selected ? (int)$typeId : '' ?>" style="--slot-color:<?= ja_h($color) ?>"><span data-block-label><?= $selected ? ja_h($typeNames[(int)$typeId] ?? 'Horario general') : '' ?></span></td><?php endforeach; ?></tr>
        <?php endfor; ?>
        </tbody>
      </table>
    </div>
    <div class="schedule-savebar"><strong id="scheduleTotal"><?= ja_h(number_format($selectedSlotCount * .5, 1, ',', '.')) ?> horas seleccionadas</strong><button class="button primary" type="submit"><?= $isNewSegment ? 'Crear segmento' : 'Guardar fechas y horario' ?></button></div>
  </form>
  <?php else: ?><div class="empty">No hay empleados disponibles.</div><?php endif; ?>
</section>

```

### admin/applications/hoy.php

```php
<?php
/* VERSION: HOY-NAVEGABLE-ROUTE-FIX-2026-08-29-2320 */
declare(strict_types=1);

if (!defined('JA_ADMIN_APPLICATION')) {
    http_response_code(404);
    exit;
}

/* -------------------------------------------------------------------------
 * HOY / NAVEGACIÓN POR DÍAS
 *
 * - Por defecto carga la fecha actual.
 * - ?date=YYYY-MM-DD permite avanzar/retroceder día a día.
 * - La cuadrícula siempre se calcula para la fecha seleccionada.
 * - Solo la fecha real de hoy compara contra la hora actual y se autorecarga.
 * ---------------------------------------------------------------------- */

$realToday = new DateTimeImmutable('today');
$requestedDate = isset($_GET['date']) ? trim((string)$_GET['date']) : '';
$selectedDay = $realToday;

if ($requestedDate !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $requestedDate)) {
    $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $requestedDate);
    $errors = DateTimeImmutable::getLastErrors();
    if ($parsed instanceof DateTimeImmutable && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))) {
        $selectedDay = $parsed;
    }
}

$selectedDate = $selectedDay->format('Y-m-d');
$selectedWeekday = (int)$selectedDay->format('N');
$todayDate = $realToday->format('Y-m-d');
$todayTime = date('H:i:s');
$isToday = ($selectedDate === $todayDate);
$isPast = ($selectedDate < $todayDate);
$isFuture = ($selectedDate > $todayDate);

$previousDate = $selectedDay->modify('-1 day')->format('Y-m-d');
$nextDate = $selectedDay->modify('+1 day')->format('Y-m-d');

$previousUrl = ja_admin_query_url([
    'group' => 'applications',
    'app' => 'hoy',
    'date' => $previousDate,
]);
$nextUrl = ja_admin_query_url([
    'group' => 'applications',
    'app' => 'hoy',
    'date' => $nextDate,
]);
$todayUrl = ja_admin_query_url([
    'group' => 'applications',
    'app' => 'hoy',
    'date' => $todayDate,
]);

$weekdayNames = [
    1 => 'lunes',
    2 => 'martes',
    3 => 'miércoles',
    4 => 'jueves',
    5 => 'viernes',
    6 => 'sábado',
    7 => 'domingo',
];
$monthNames = [
    1 => 'enero',
    2 => 'febrero',
    3 => 'marzo',
    4 => 'abril',
    5 => 'mayo',
    6 => 'junio',
    7 => 'julio',
    8 => 'agosto',
    9 => 'septiembre',
    10 => 'octubre',
    11 => 'noviembre',
    12 => 'diciembre',
];
$prettyDate = $weekdayNames[$selectedWeekday]
    . ', ' . $selectedDay->format('j')
    . ' de ' . $monthNames[(int)$selectedDay->format('n')]
    . ' de ' . $selectedDay->format('Y');

/* Empleados + horarios previstos para la fecha seleccionada --------------- */
$statement = $pdo->prepare(
    "SELECT e.id,e.nombre,e.apellido,e.departamento,e.role,
            s.start_time,s.end_time,s.attendance_type_id,
            ft.name AS attendance_type_name
     FROM schedules s
     JOIN employees e
       ON e.id=s.employee_id
      AND e.tenant_id=s.tenant_id
      AND e.active=1
     LEFT JOIN attendance_types ft
       ON ft.id=s.attendance_type_id
      AND ft.tenant_id=s.tenant_id
     WHERE s.tenant_id=:tenant
       AND s.weekday=:weekday
       AND (s.valid_from IS NULL OR s.valid_from<=:selected_date)
       AND (s.valid_until IS NULL OR s.valid_until>=:selected_date)
     ORDER BY e.apellido COLLATE NOCASE,
              e.nombre COLLATE NOCASE,
              s.start_time,
              s.end_time,
              s.id"
);
$statement->execute([
    'tenant' => $tenantId,
    'weekday' => $selectedWeekday,
    'selected_date' => $selectedDate,
]);

$selectedEmployees = [];
foreach ($statement->fetchAll() as $row) {
    $employeeId = (int)$row['id'];

    if (!isset($selectedEmployees[$employeeId])) {
        $selectedEmployees[$employeeId] = [
            'employee' => [
                'id' => $employeeId,
                'nombre' => $row['nombre'],
                'apellido' => $row['apellido'],
                'departamento' => $row['departamento'],
                'role' => $row['role'],
            ],
            'schedules' => [],
        ];
    }

    $selectedEmployees[$employeeId]['schedules'][] = [
        'start_time' => $row['start_time'],
        'end_time' => $row['end_time'],
        'attendance_type_id' => $row['attendance_type_id'],
        'attendance_type_name' => $row['attendance_type_name'],
    ];
}

$employeeIds = array_keys($selectedEmployees);

/* Fichajes de la fecha seleccionada --------------------------------------- */
$selectedAttendance = [];
if ($employeeIds) {
    $placeholders = implode(',', array_fill(0, count($employeeIds), '?'));

    if ($isToday) {
        // Hoy conservamos también una posible jornada anterior que siga abierta.
        $statement = $pdo->prepare(
            "SELECT a.id,a.employee_id,a.work_date,a.start_time,a.end_time,
                    a.attendance_type_id,
                    ft.name AS attendance_type_name
             FROM attendance a
             LEFT JOIN attendance_types ft
               ON ft.id=a.attendance_type_id
              AND ft.tenant_id=a.tenant_id
             WHERE a.tenant_id=?
               AND a.employee_id IN ($placeholders)
               AND (
                    a.work_date=?
                    OR (a.work_date<? AND a.end_time IS NULL)
               )
             ORDER BY a.employee_id,a.work_date,a.start_time,a.id"
        );
        $params = array_merge([$tenantId], $employeeIds, [$selectedDate, $selectedDate]);
    } else {
        $statement = $pdo->prepare(
            "SELECT a.id,a.employee_id,a.work_date,a.start_time,a.end_time,
                    a.attendance_type_id,
                    ft.name AS attendance_type_name
             FROM attendance a
             LEFT JOIN attendance_types ft
               ON ft.id=a.attendance_type_id
              AND ft.tenant_id=a.tenant_id
             WHERE a.tenant_id=?
               AND a.employee_id IN ($placeholders)
               AND a.work_date=?
             ORDER BY a.employee_id,a.work_date,a.start_time,a.id"
        );
        $params = array_merge([$tenantId], $employeeIds, [$selectedDate]);
    }

    $statement->execute($params);
    foreach ($statement->fetchAll() as $record) {
        $selectedAttendance[(int)$record['employee_id']][] = $record;
    }
}

/* Ausencias aprobadas ------------------------------------------------------ */
$selectedAbsences = [];
if ($employeeIds) {
    $placeholders = implode(',', array_fill(0, count($employeeIds), '?'));
    $statement = $pdo->prepare(
        "SELECT employee_id,start_date,start_time,end_date,end_time,absence_type
         FROM absences
         WHERE tenant_id=?
           AND employee_id IN ($placeholders)
           AND status='approved'
           AND start_date<=?
           AND (end_date IS NULL OR end_date>=?)
         ORDER BY id DESC"
    );
    $statement->execute(array_merge([$tenantId], $employeeIds, [$selectedDate, $selectedDate]));
    foreach ($statement->fetchAll() as $absence) {
        $selectedAbsences[(int)$absence['employee_id']][] = $absence;
    }
}

/* Vacaciones aprobadas ----------------------------------------------------- */
$selectedVacations = [];
if ($employeeIds) {
    $placeholders = implode(',', array_fill(0, count($employeeIds), '?'));
    $statement = $pdo->prepare(
        "SELECT employee_id,start_date,end_date
         FROM vacations
         WHERE tenant_id=?
           AND employee_id IN ($placeholders)
           AND approved=1
           AND start_date<=?
           AND end_date>=?
         ORDER BY id DESC"
    );
    $statement->execute(array_merge([$tenantId], $employeeIds, [$selectedDate, $selectedDate]));
    foreach ($statement->fetchAll() as $vacation) {
        $selectedVacations[(int)$vacation['employee_id']] = $vacation;
    }
}

/* Festivo ----------------------------------------------------------------- */
$statement = $pdo->prepare(
    'SELECT name,description
     FROM holidays
     WHERE tenant_id=:tenant
       AND holiday_date=:selected_date
     LIMIT 1'
);
$statement->execute([
    'tenant' => $tenantId,
    'selected_date' => $selectedDate,
]);
$selectedHoliday = $statement->fetch() ?: null;

/* Construcción de tarjetas ------------------------------------------------ */
$cards = [];

foreach ($selectedEmployees as $employeeId => $data) {
    $employee = $data['employee'];
    $schedules = $data['schedules'];
    $records = $selectedAttendance[$employeeId] ?? [];
    $absence = ($selectedAbsences[$employeeId] ?? [])[0] ?? null;
    $vacation = $selectedVacations[$employeeId] ?? null;
    $earliestStart = substr((string)$schedules[0]['start_time'], 0, 8);

    $hasOpenRecord = false;
    foreach ($records as $record) {
        if (!$record['end_time']) {
            $hasOpenRecord = true;
            break;
        }
    }

    $excuse = '';
    if ($selectedHoliday) {
        $excuse = 'Festivo · ' . (string)$selectedHoliday['name'];
    } elseif ($absence) {
        $excuse = 'Ausencia · ' . (string)$absence['absence_type'];
    } elseif ($vacation) {
        $excuse = 'Vacaciones';
    }

    if ($records) {
        $status = $hasOpenRecord ? 'in_progress' : 'completed';
    } elseif ($excuse !== '') {
        $status = 'excused';
    } elseif ($isPast) {
        $status = 'not_started';
    } elseif ($isFuture) {
        $status = 'upcoming';
    } elseif ($earliestStart <= $todayTime) {
        $status = 'not_started';
    } else {
        $status = 'upcoming';
    }

    $labels = [
        'not_started' => $isPast ? 'No inició' : 'No ha iniciado',
        'in_progress' => 'En curso',
        'completed' => 'Finalizado',
        'upcoming' => 'Pendiente',
        'excused' => $excuse ?: 'Justificado',
    ];

    $cards[] = [
        'employee' => $employee,
        'schedules' => $schedules,
        'records' => $records,
        'status' => $status,
        'label' => $labels[$status],
    ];
}
?>

<style>
.today-date-nav{
    display:grid;
    grid-template-columns:auto minmax(220px,1fr) auto;
    gap:10px;
    align-items:stretch;
    margin:0 0 14px;
}
.today-date-nav-button,
.today-date-nav-current,
.today-date-nav-today{
    border:1px solid var(--line,#d9c5be);
    background:#fff;
    color:inherit;
    text-decoration:none;
    min-height:48px;
    padding:9px 14px;
    display:flex;
    align-items:center;
    justify-content:center;
    font-weight:800;
}
.today-date-nav-button:hover,
.today-date-nav-today:hover{
    background:rgba(255,255,255,.65);
}
.today-date-nav-center{
    display:flex;
    gap:8px;
    min-width:0;
}
.today-date-nav-current{
    flex:1;
    flex-direction:column;
    line-height:1.2;
    min-width:0;
}
.today-date-nav-current strong{
    font-size:15px;
    text-transform:capitalize;
}
.today-date-nav-current small{
    margin-top:3px;
    opacity:.62;
    font-size:10px;
}
.today-date-nav-today{
    white-space:nowrap;
}
.today-plain-grid{
    display:grid;
    grid-template-columns:repeat(auto-fill,minmax(220px,1fr));
    gap:12px;
    width:100%;
}
.today-employee-card{
    min-height:150px;
    padding:16px;
    display:flex;
    flex-direction:column;
    gap:9px;
    color:#2b2523;
    border:1px solid rgba(0,0,0,.10);
    text-decoration:none;
    box-shadow:0 2px 8px rgba(0,0,0,.04);
    transition:transform .12s ease,box-shadow .12s ease;
}
.today-employee-card:hover{
    transform:translateY(-2px);
    box-shadow:0 7px 18px rgba(0,0,0,.10);
}
.today-employee-card.is-not_started{background:#f8d7da;border-color:#e4a6ac;}
.today-employee-card.is-in_progress{background:#fff0c7;border-color:#e7ca75;}
.today-employee-card.is-completed{background:#dcefe2;border-color:#9fc9aa;}
.today-employee-card.is-upcoming{background:#e8e2f2;border-color:#c1b2d8;}
.today-employee-card.is-excused{background:#dfeaec;border-color:#abc5ca;}
.today-employee-card-name{margin:0;font-size:18px;line-height:1.08;}
.today-employee-card-meta{color:rgba(43,37,35,.70);font-size:11px;line-height:1.35;}
.today-employee-card-times{display:flex;flex-wrap:wrap;gap:5px;}
.today-employee-card-time{
    padding:4px 7px;
    background:rgba(255,255,255,.56);
    border:1px solid rgba(0,0,0,.08);
    font-size:11px;
    font-weight:700;
}
.today-employee-card-status{
    margin-top:auto;
    padding-top:7px;
    border-top:1px solid rgba(0,0,0,.10);
    font-size:12px;
    font-weight:900;
}
.today-grid-empty{
    padding:28px;
    background:#fff;
    border:1px solid var(--line,#ddd);
    text-align:center;
    color:var(--muted,#777);
}
@media(max-width:720px){
    .today-date-nav{grid-template-columns:1fr 1fr;}
    .today-date-nav-center{grid-column:1 / -1;grid-row:1;}
    .today-date-nav-button{grid-row:2;}
}
@media(max-width:620px){
    .today-plain-grid{grid-template-columns:repeat(2,minmax(0,1fr));gap:8px;}
    .today-employee-card{min-height:135px;padding:12px;}
    .today-employee-card-name{font-size:15px;}
}
@media(max-width:390px){
    .today-plain-grid{grid-template-columns:1fr;}
    .today-date-nav-center{flex-direction:column;}
}
</style>

<section class="application-stack"<?= $isToday ? ' data-today-auto-refresh="60"' : '' ?>>
    <nav class="today-date-nav" aria-label="Navegación por fecha">
        <a class="today-date-nav-button" href="<?= ja_h($previousUrl) ?>">← Anterior</a>

        <div class="today-date-nav-center">
            <div class="today-date-nav-current">
                <strong><?= ja_h($prettyDate) ?></strong>
                <small><?= ja_h($selectedDate) ?></small>
            </div>
            <?php if (!$isToday): ?>
                <a class="today-date-nav-today" href="<?= ja_h($todayUrl) ?>">Hoy</a>
            <?php endif; ?>
        </div>

        <a class="today-date-nav-button" href="<?= ja_h($nextUrl) ?>">Siguiente →</a>
    </nav>

    <?php if ($cards): ?>
        <div class="today-plain-grid">
            <?php foreach ($cards as $card):
                $employee = $card['employee'];
                $href = ja_admin_query_url([
                    'group' => 'tables',
                    'view' => 'attendance',
                    'employee_id' => (int)$employee['id'],
                    'date' => $selectedDate,
                ]);
            ?>
                <a
                    class="today-employee-card <?= ja_h('is-' . $card['status']) ?>"
                    href="<?= ja_h($href) ?>"
                    title="<?= ja_h($card['label']) ?>"
                >
                    <h3 class="today-employee-card-name">
                        <?= ja_h(ja_admin_employee_name($employee)) ?>
                    </h3>

                    <div class="today-employee-card-meta">
                        <?= ja_h($employee['departamento'] ?: 'Sin departamento') ?>
                        ·
                        <?= ja_h($employee['role'] ?: 'Sin rol') ?>
                    </div>

                    <div class="today-employee-card-times">
                        <?php foreach ($card['schedules'] as $schedule): ?>
                            <span class="today-employee-card-time">
                                <?= ja_h(ja_admin_short_time($schedule['start_time'])) ?>
                                –
                                <?= ja_h(ja_admin_short_time($schedule['end_time'])) ?>
                                <?php if ($schedule['attendance_type_name']): ?>
                                    · <?= ja_h($schedule['attendance_type_name']) ?>
                                <?php endif; ?>
                            </span>
                        <?php endforeach; ?>
                    </div>

                    <div class="today-employee-card-status">
                        <?= ja_h($card['label']) ?>

                        <?php if ($card['records']): ?>
                            <?php
                            $lastRecord = end($card['records']);
                            reset($card['records']);
                            ?>
                            <?php if (!empty($lastRecord['start_time'])): ?>
                                · entrada <?= ja_h(ja_admin_short_time($lastRecord['start_time'])) ?>
                            <?php endif; ?>
                            <?php if (!empty($lastRecord['end_time'])): ?>
                                · salida <?= ja_h(ja_admin_short_time($lastRecord['end_time'])) ?>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="today-grid-empty">
            No hay empleados con horario previsto para esta fecha.
        </div>
    <?php endif; ?>
</section>
```

### admin/applications/resumen_sesiones.php

```php
<?php
declare(strict_types=1);

if (!defined('JA_ADMIN_APPLICATION')) {
    http_response_code(404);
    exit;
}

$week = (int)($_GET['week'] ?? date('W'));
$year = (int)($_GET['year'] ?? date('o'));
if ($week < 1 || $week > 53 || $year < 2000 || $year > 2200) {
    $week = (int)date('W');
    $year = (int)date('o');
}
$weekStart = (new DateTimeImmutable())->setISODate($year, $week)->setTime(0, 0);
$weekEnd = $weekStart->modify('+6 days');
$previous = $weekStart->modify('-7 days');
$next = $weekStart->modify('+7 days');
$weekDates = [];
for ($index = 0; $index < 7; $index++) {
    $weekDates[] = $weekStart->modify('+' . $index . ' days')->format('Y-m-d');
}

$statement = $pdo->prepare('SELECT id,nombre,apellido,departamento,role FROM employees WHERE tenant_id=:tenant AND active=1 ORDER BY apellido,nombre');
$statement->execute(['tenant' => $tenantId]);
$summaryEmployees = $statement->fetchAll();

$statement = $pdo->prepare('SELECT * FROM attendance WHERE tenant_id=:tenant AND work_date BETWEEN :start AND :end ORDER BY id DESC');
$statement->execute(['tenant' => $tenantId, 'start' => $weekStart->format('Y-m-d'), 'end' => $weekEnd->format('Y-m-d')]);
$attendanceByEmployeeDate = [];
foreach ($statement->fetchAll() as $record) {
    $employeeId = (int)$record['employee_id'];
    $date = $record['work_date'];
    $attendanceByEmployeeDate[$employeeId][$date] ??= $record;
}

$statement = $pdo->prepare('SELECT * FROM schedules WHERE tenant_id=:tenant AND (valid_from IS NULL OR valid_from<=:end) AND (valid_until IS NULL OR valid_until>=:start) ORDER BY start_time');
$statement->execute(['tenant' => $tenantId, 'start' => $weekStart->format('Y-m-d'), 'end' => $weekEnd->format('Y-m-d')]);
$scheduleByEmployeeDay = [];
foreach ($statement->fetchAll() as $schedule) {
    $scheduleByEmployeeDay[(int)$schedule['employee_id']][(int)$schedule['weekday']][] = $schedule;
}

$statement = $pdo->prepare('SELECT * FROM holidays WHERE tenant_id=:tenant AND holiday_date BETWEEN :start AND :end');
$statement->execute(['tenant' => $tenantId, 'start' => $weekStart->format('Y-m-d'), 'end' => $weekEnd->format('Y-m-d')]);
$holidayByDate = [];
foreach ($statement->fetchAll() as $holiday) {
    $holidayByDate[$holiday['holiday_date']] = $holiday;
}

$statement = $pdo->prepare("SELECT * FROM absences WHERE tenant_id=:tenant AND status='approved' AND start_date<=:end AND (end_date IS NULL OR end_date>=:start)");
$statement->execute(['tenant' => $tenantId, 'start' => $weekStart->format('Y-m-d'), 'end' => $weekEnd->format('Y-m-d')]);
$approvedAbsences = $statement->fetchAll();
$absenceFor = static function (int $employeeId, string $date) use ($approvedAbsences): ?array {
    foreach ($approvedAbsences as $absence) {
        if ((int)$absence['employee_id'] === $employeeId && $absence['start_date'] <= $date && (!$absence['end_date'] || $absence['end_date'] >= $date)) {
            return $absence;
        }
    }
    return null;
};
$today = date('Y-m-d');
?>
<section class="application-stack sessions-summary">
  <div class="application-toolbar">
    <div><h2>Resumen de sesiones</h2><p>Semana <?= ja_h((string)$year) ?>-W<?= ja_h(str_pad((string)$week, 2, '0', STR_PAD_LEFT)) ?> · <?= ja_h($weekStart->format('d/m/Y')) ?> → <?= ja_h($weekEnd->format('d/m/Y')) ?></p></div>
    <div class="actions"><a class="button" href="<?= ja_h(ja_admin_query_url(['group' => 'applications', 'app' => 'resumen_sesiones', 'week' => $previous->format('W'), 'year' => $previous->format('o')])) ?>">Semana anterior</a><a class="button primary" href="<?= ja_h(ja_admin_query_url(['group' => 'applications', 'app' => 'resumen_sesiones', 'week' => date('W'), 'year' => date('o')])) ?>">Semana actual</a><a class="button" href="<?= ja_h(ja_admin_query_url(['group' => 'applications', 'app' => 'resumen_sesiones', 'week' => $next->format('W'), 'year' => $next->format('o')])) ?>">Semana siguiente</a></div>
  </div>
  <div class="session-legend"><span><i class="present"></i>Asistió</span><span><i class="error"></i>Error de registro</span><span><i class="absent"></i>Ausencia</span><span><i class="holiday"></i>Festivo</span><span><i class="noschedule"></i>Sin horas asignadas</span><span><i class="open"></i>Jornada abierta hoy</span></div>
  <div class="sessions-table-wrap"><table class="sessions-table"><thead><tr><th>Empleado</th><?php foreach ($weekDates as $date): $dayNumber = (int)(new DateTimeImmutable($date))->format('N'); ?><th><?= ja_h($weekdays[$dayNumber]) ?><small><?= ja_h((new DateTimeImmutable($date))->format('d/m')) ?></small></th><?php endforeach; ?></tr></thead><tbody>
  <?php foreach ($summaryEmployees as $employee): $employeeId = (int)$employee['id']; ?><tr><th><strong><?= ja_h(ja_admin_employee_name($employee)) ?></strong><small><?= ja_h($employee['departamento'] ?: 'Sin departamento') ?></small></th>
    <?php foreach ($weekDates as $date):
        $record = $attendanceByEmployeeDate[$employeeId][$date] ?? null;
        $absence = $absenceFor($employeeId, $date);
        $weekday = (int)(new DateTimeImmutable($date))->format('N');
        $applicableSchedule = array_values(array_filter($scheduleByEmployeeDay[$employeeId][$weekday] ?? [], static fn(array $schedule): bool => (!$schedule['valid_from'] || $schedule['valid_from'] <= $date) && (!$schedule['valid_until'] || $schedule['valid_until'] >= $date)));
        $status = 'noschedule';
        $label = 'Sin horario';
        if (isset($holidayByDate[$date])) {
            $status = 'holiday';
            $label = $holidayByDate[$date]['name'];
        } elseif ($record && $record['start_time'] && $record['end_time']) {
            $status = 'present';
            $label = ja_admin_short_time($record['start_time']) . ' – ' . ja_admin_short_time($record['end_time']);
        } elseif ($record && $record['start_time'] && !$record['end_time']) {
            $status = $date === $today ? 'open' : 'error';
            $label = $date === $today ? 'Abierta ' . ja_admin_short_time($record['start_time']) : 'Falta salida';
        } elseif ($record) {
            $status = 'error';
            $label = 'Registro incompleto';
        } elseif ($absence) {
            $status = 'absent';
            $label = $absence['absence_type'];
        } elseif ($applicableSchedule) {
            $status = 'absent';
            $label = 'Sin asistencia';
        }
    ?><td><a class="session-state <?= ja_h($status) ?>" href="?group=tables&view=attendance" title="<?= ja_h($label) ?>"><?= ja_h($label) ?></a></td><?php endforeach; ?>
  </tr><?php endforeach; ?>
  <?php if (!$summaryEmployees): ?><tr><td colspan="8">No hay empleados activos.</td></tr><?php endif; ?>
  </tbody></table></div>
</section>


```

### admin/applications/reuniones.php

```php
<?php
declare(strict_types=1);
if (!defined('JA_ADMIN_APPLICATION')) { http_response_code(404); exit; }
$scope=(string)($_GET['meeting_scope']??'employee'); if(!in_array($scope,['all','role','employee'],true))$scope='employee';
$selectedId=(int)($_GET['employee_id']??0); if($selectedId<=0 && $employees)$selectedId=(int)$employees[0]['id'];
$roles=[]; foreach($employees as $e){$r=trim((string)($e['role']??''));if($r!=='')$roles[$r]=$r;} ksort($roles,SORT_NATURAL|SORT_FLAG_CASE);
$selectedRole=(string)($_GET['meeting_role']??''); if($selectedRole===''&&$roles)$selectedRole=(string)array_key_first($roles);
$weekRaw=(string)($_GET['week']??date('Y-m-d')); if(!ja_admin_valid_date($weekRaw))$weekRaw=date('Y-m-d');
$monday=(new DateTimeImmutable($weekRaw))->modify('monday this week'); $weekStart=$monday->format('Y-m-d'); $weekEnd=$monday->modify('+6 days')->format('Y-m-d');
$slots=array_fill(1,7,[]);$titles=[];$sql='SELECT event_date,start_time,end_time,title FROM meetings WHERE tenant_id=:tenant AND scope_type=:scope AND event_date BETWEEN :a AND :b';$params=['tenant'=>$tenantId,'scope'=>$scope,'a'=>$weekStart,'b'=>$weekEnd];
if($scope==='employee'){$sql.=' AND employee_id=:employee';$params['employee']=$selectedId;}elseif($scope==='role'){$sql.=' AND target_role=:role';$params['role']=$selectedRole;} $sql.=' ORDER BY event_date,start_time';
$q=$pdo->prepare($sql);$q->execute($params);foreach($q->fetchAll() as $row){$d=(int)(new DateTimeImmutable($row['event_date']))->format('N');$a=ja_admin_time_minutes($row['start_time']);$b=ja_admin_time_minutes($row['end_time']);if($a===null||$b===null)continue;for($m=$a;$m<$b;$m+=30)$slots[$d][sprintf('%02d:%02d',intdiv($m,60),$m%60)]=true;$titles[]=(string)$row['title'];}
$defaultTitle=$titles[0]??'Reunión';$count=array_sum(array_map('count',$slots));$days=[1=>'Lunes',2=>'Martes',3=>'Miércoles',4=>'Jueves',5=>'Viernes',6=>'Sábado',7=>'Domingo'];
?>
<section class="application-stack schedule-editor" id="meetingEditor">
 <div class="application-toolbar"><div><h2>Reuniones</h2><p>Planificación real por semana. Las reuniones computan como tiempo de trabajo y como actividad fuera del horario establecido.</p></div><span class="autosave-status" id="meetingAutosave">Guardado</span></div>
 <nav class="meeting-scope-tabs"><a class="<?= $scope==='all'?'active':'' ?>" href="<?= ja_h(ja_admin_query_url(['group'=>'applications','app'=>'reuniones','meeting_scope'=>'all','week'=>$weekStart])) ?>">Toda la plantilla</a><a class="<?= $scope==='role'?'active':'' ?>" href="<?= ja_h(ja_admin_query_url(['group'=>'applications','app'=>'reuniones','meeting_scope'=>'role','meeting_role'=>$selectedRole,'week'=>$weekStart])) ?>">Por tipo de empleado</a><a class="<?= $scope==='employee'?'active':'' ?>" href="<?= ja_h(ja_admin_query_url(['group'=>'applications','app'=>'reuniones','meeting_scope'=>'employee','employee_id'=>$selectedId,'week'=>$weekStart])) ?>">Por empleado</a></nav>
 <form method="get" class="schedule-selector schedule-employee-selector"><input type="hidden" name="group" value="applications"><input type="hidden" name="app" value="reuniones"><input type="hidden" name="meeting_scope" value="<?= ja_h($scope) ?>">
 <?php if($scope==='role'):?><label>Tipo de empleado<select name="meeting_role" onchange="this.form.submit()"><?php foreach($roles as $r):?><option <?= $r===$selectedRole?'selected':'' ?>><?= ja_h($r) ?></option><?php endforeach;?></select></label><?php endif;?>
 <?php if($scope==='employee'):?><label>Empleado<select name="employee_id" onchange="this.form.submit()"><?php foreach($employees as $e):?><option value="<?= (int)$e['id'] ?>" <?= $selectedId===(int)$e['id']?'selected':'' ?>><?= ja_h(ja_admin_employee_name($e)) ?></option><?php endforeach;?></select></label><?php endif;?>
 <label>Semana<input type="date" name="week" value="<?= ja_h($weekStart) ?>" onchange="this.form.submit()"></label><a class="button" href="<?= ja_h(ja_admin_query_url(['group'=>'applications','app'=>'reuniones','meeting_scope'=>$scope,'meeting_role'=>$selectedRole,'employee_id'=>$selectedId,'week'=>$monday->modify('-7 days')->format('Y-m-d')])) ?>">← Anterior</a><a class="button" href="<?= ja_h(ja_admin_query_url(['group'=>'applications','app'=>'reuniones','meeting_scope'=>$scope,'meeting_role'=>$selectedRole,'employee_id'=>$selectedId,'week'=>$monday->modify('+7 days')->format('Y-m-d')])) ?>">Siguiente →</a></form>
 <form method="post" id="meetingGridForm"><input type="hidden" name="csrf" value="<?= ja_h($csrf) ?>"><input type="hidden" name="action" value="save_meeting_grid"><input type="hidden" name="meeting_scope" value="<?= ja_h($scope) ?>"><input type="hidden" name="employee_id" value="<?= $selectedId ?>"><input type="hidden" name="meeting_role" value="<?= ja_h($selectedRole) ?>"><input type="hidden" name="week_start" value="<?= ja_h($weekStart) ?>">
 <section class="card schedule-type-palette"><label>Nombre/tipo del evento<input type="text" name="meeting_title" value="<?= ja_h($defaultTitle) ?>" maxlength="120" placeholder="Reunión, tutoría, coordinación..."></label><p><?php if($scope==='all'):?>Se aplicará a <strong>toda la plantilla</strong>.<?php elseif($scope==='role'):?>Se aplicará a empleados de tipo <strong><?= ja_h($selectedRole) ?></strong>.<?php else:?>Se aplicará únicamente al empleado seleccionado.<?php endif;?> Cada clic se guarda automáticamente.</p></section>
 <div class="schedule-grid-wrap"><table class="schedule-grid typed-schedule-grid meeting-grid"><thead><tr><th>Hora</th><?php foreach($days as $n=>$name):$date=$monday->modify('+'.($n-1).' days');?><th><?= ja_h($name) ?><small><?= $date->format('d/m') ?></small></th><?php endforeach;?></tr></thead><tbody><?php for($m=0;$m<1440;$m+=30):$time=sprintf('%02d:%02d',intdiv($m,60),$m%60);?><tr><td><?= $time ?></td><?php foreach($days as $n=>$x):$on=!empty($slots[$n][$time]);?><td class="meeting-slot schedule-slot <?= $on?'selected':'' ?>" data-day="<?= $n ?>" data-time="<?= $time ?>" aria-checked="<?= $on?'true':'false' ?>"><span></span></td><?php endforeach;?></tr><?php endfor;?></tbody></table></div>
 <div class="schedule-savebar"><strong id="meetingTotal"><?= number_format($count*.5,1,',','.') ?> horas de reuniones/eventos</strong><span>Autoguardado activo</span></div></form>
</section>
```

### admin/applications/solicitudes_personal.php

```php
<?php
declare(strict_types=1);

if (!defined('JA_ADMIN_APPLICATION')) {
    http_response_code(403);
    exit;
}

$statusLabels = ['pending' => 'Pendiente', 'accepted' => 'Aprobada', 'rejected' => 'Rechazada'];
$pendingCount = count(array_filter($workerCorrections, static fn(array $row): bool => $row['status'] === 'pending'));
?>
<div class="application-toolbar">
  <div><h3>Solicitudes del personal</h3><p>Revisa las modificaciones propuestas por los trabajadores. Aprobar aplica el cambio; rechazar conserva la jornada vigente.</p></div>
  <span class="badge <?= $pendingCount ? 'warning' : 'ok' ?>"><?= $pendingCount ?> pendientes</span>
</div>

<?php if (!$workerAttendanceEditEnabled): ?>
  <div class="notice warning">La creación de nuevas solicitudes está deshabilitada en Configuración. Las solicitudes existentes siguen disponibles para su resolución.</div>
<?php endif; ?>

<?php if (!$workerCorrections): ?>
  <div class="empty">Todavía no hay solicitudes de corrección iniciadas por trabajadores.</div>
<?php endif; ?>

<div class="correction-list">
<?php foreach ($workerCorrections as $request): $status = (string)$request['status']; $pending = $status === 'pending'; ?>
  <article class="card correction-card <?= $pending ? 'is-pending' : '' ?>">
    <div class="correction-card-head">
      <div><p class="eyebrow">Jornada #<?= (int)$request['attendance_id'] ?></p><h3><?= ja_h($request['employee_name']) ?></h3><p>Solicitada por <?= ja_h($request['worker_user_name']) ?> · <?= ja_h($request['requested_at']) ?></p></div>
      <span class="badge <?= $status === 'accepted' ? 'ok' : ($status === 'rejected' ? 'danger' : 'warning') ?>"><?= ja_h($statusLabels[$status] ?? $status) ?></span>
    </div>
    <div class="correction-comparison">
      <div><small>REGISTRO ACTUAL</small><strong><?= ja_h($request['original_work_date']) ?></strong><span><?= ja_h(ja_admin_short_time($request['original_start_time'])) ?> — <?= ja_h(ja_admin_short_time($request['original_end_time'])) ?></span><span>Tipo: <?= ja_h($request['original_attendance_type_name'] ?: 'Sin tipo') ?></span></div>
      <div class="correction-arrow">→</div>
      <div><small>PROPUESTA DEL TRABAJADOR</small><strong><?= ja_h($request['proposed_work_date']) ?></strong><span><?= ja_h(ja_admin_short_time($request['proposed_start_time'])) ?> — <?= ja_h(ja_admin_short_time($request['proposed_end_time'])) ?></span><span>Tipo: <?= ja_h($request['proposed_attendance_type_name'] ?: 'Sin tipo') ?></span></div>
    </div>
    <div class="correction-reason"><strong>Motivo del trabajador</strong><p><?= ja_h($request['reason']) ?></p></div>
    <?php if ($pending): ?>
      <form method="post" class="stack">
        <input type="hidden" name="csrf" value="<?= ja_h($csrf) ?>">
        <input type="hidden" name="action" value="worker_correction_decision">
        <input type="hidden" name="request_id" value="<?= (int)$request['id'] ?>">
        <label>Comentario para el trabajador<textarea name="admin_comment" maxlength="1000" placeholder="Opcional: explica el motivo de la decisión."></textarea></label>
        <div class="actions"><button class="button primary" type="submit" name="decision" value="accepted" data-confirm="¿Aprobar y aplicar esta corrección?">Aprobar y aplicar</button><button class="button danger" type="submit" name="decision" value="rejected" data-confirm="¿Rechazar esta corrección y conservar la jornada original?">Rechazar</button></div>
      </form>
    <?php else: ?>
      <div class="correction-response"><strong>Decisión de <?= ja_h($request['admin_name'] ?: 'administración') ?></strong><p><?= ja_h($request['admin_comment'] ?: 'Sin comentario.') ?></p><small><?= ja_h($request['responded_at']) ?></small></div>
    <?php endif; ?>
    <div class="correction-hashes"><span>Huella de propuesta</span><code><?= ja_h($request['proposal_event_hash'] ?: '—') ?></code><?php if ($request['decision_event_hash']): ?><span>Huella de decisión</span><code><?= ja_h($request['decision_event_hash']) ?></code><?php endif; ?></div>
  </article>
<?php endforeach; ?>
</div>


```

### admin/applications/temporalizacion_vacaciones.php

```php
<?php
declare(strict_types=1);

if (!defined('JA_ADMIN_APPLICATION')) {
    http_response_code(404);
    exit;
}

$vacationYear = (int)($_GET['year'] ?? date('Y'));
if ($vacationYear < 2000 || $vacationYear > 2100) {
    $vacationYear = (int)date('Y');
}
$vacationDepartment = trim((string)($_GET['department'] ?? ''));
$vacationOnlyWithPeriods = (string)($_GET['only_with_vacations'] ?? '') === '1';
$vacationStart = new DateTimeImmutable(sprintf('%04d-01-01', $vacationYear));
$vacationEnd = new DateTimeImmutable(sprintf('%04d-12-31', $vacationYear));
$vacationDates = [];
for ($date = $vacationStart; $date <= $vacationEnd; $date = $date->modify('+1 day')) {
    $vacationDates[] = $date;
}
$vacationDayCount = count($vacationDates);
$vacationMonthNames = [1 => 'Ene', 2 => 'Feb', 3 => 'Mar', 4 => 'Abr', 5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Ago', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dic'];

$statement = $pdo->prepare("SELECT DISTINCT departamento FROM employees WHERE tenant_id=:tenant AND active=1 AND departamento IS NOT NULL AND trim(departamento)<>'' ORDER BY departamento COLLATE NOCASE");
$statement->execute(['tenant' => $tenantId]);
$vacationDepartments = array_column($statement->fetchAll(), 'departamento');

$employeeSql = "SELECT id,nombre,apellido,departamento,role FROM employees WHERE tenant_id=:tenant AND active=1";
$employeeParams = ['tenant' => $tenantId];
if ($vacationDepartment !== '') {
    $employeeSql .= ' AND departamento=:department';
    $employeeParams['department'] = $vacationDepartment;
}
$employeeSql .= ' ORDER BY apellido COLLATE NOCASE,nombre COLLATE NOCASE';
$statement = $pdo->prepare($employeeSql);
$statement->execute($employeeParams);
$vacationEmployees = $statement->fetchAll();
$vacationEmployeeIds = array_fill_keys(array_map(static fn(array $employee): int => (int)$employee['id'], $vacationEmployees), true);

$statement = $pdo->prepare(
    "SELECT v.id,v.employee_id,v.start_date,v.end_date,v.approved
     FROM vacations v
     WHERE v.tenant_id=:tenant AND v.start_date<=:year_end AND v.end_date>=:year_start
     ORDER BY v.employee_id,v.start_date,v.end_date,v.id"
);
$statement->execute([
    'tenant' => $tenantId,
    'year_start' => $vacationStart->format('Y-m-d'),
    'year_end' => $vacationEnd->format('Y-m-d'),
]);

$vacationBars = [];
$vacationPeriodCount = 0;
$vacationApprovedCount = 0;
$vacationVisibleDays = 0;
foreach ($statement->fetchAll() as $period) {
    $employeeId = (int)$period['employee_id'];
    if (!isset($vacationEmployeeIds[$employeeId])) {
        continue;
    }
    $originalStart = new DateTimeImmutable((string)$period['start_date']);
    $originalEnd = new DateTimeImmutable((string)$period['end_date']);
    $visibleStart = $originalStart < $vacationStart ? $vacationStart : $originalStart;
    $visibleEnd = $originalEnd > $vacationEnd ? $vacationEnd : $originalEnd;
    $startIndex = (int)$vacationStart->diff($visibleStart)->format('%a') + 1;
    $span = (int)$visibleStart->diff($visibleEnd)->format('%a') + 1;
    $periodDayCount = (int)$originalStart->diff($originalEnd)->format('%a') + 1;

    $track = 1;
    while (isset($vacationBars[$employeeId]['track_ends'][$track]) && $vacationBars[$employeeId]['track_ends'][$track] >= $startIndex) {
        $track++;
    }
    $vacationBars[$employeeId]['track_ends'][$track] = $startIndex + $span - 1;
    $vacationBars[$employeeId]['items'][] = $period + [
        'start_index' => $startIndex,
        'span' => $span,
        'day_count' => $periodDayCount,
        'track' => $track,
    ];
    $vacationBars[$employeeId]['tracks'] = max((int)($vacationBars[$employeeId]['tracks'] ?? 0), $track);
    $vacationPeriodCount++;
    $vacationApprovedCount += !empty($period['approved']) ? 1 : 0;
    $vacationVisibleDays += $span;
}
$vacationEmployeesWithPeriods = count(array_filter($vacationBars, static fn(array $item): bool => !empty($item['items'])));
if ($vacationOnlyWithPeriods) {
    $vacationEmployees = array_values(array_filter(
        $vacationEmployees,
        static fn(array $employee): bool => !empty($vacationBars[(int)$employee['id']]['items'])
    ));
}
$vacationBaseQuery = [
    'group' => 'applications',
    'app' => 'temporalizacion_vacaciones',
    'department' => $vacationDepartment ?: null,
    'only_with_vacations' => $vacationOnlyWithPeriods ? '1' : null,
];
$vacationTodayIndex = $vacationYear === (int)date('Y') ? (int)date('z') : null;
?>
<section class="application-stack vacation-timeline">
  <div class="application-toolbar">
    <div><h2>Temporalización vacaciones</h2><p>Vista general diaria de <?= ja_h((string)$vacationYear) ?> · <?= count($vacationEmployees) ?> personas visibles.</p></div>
    <div class="actions">
      <a class="button" href="<?= ja_h(ja_admin_query_url($vacationBaseQuery + ['year' => $vacationYear - 1])) ?>">← <?= $vacationYear - 1 ?></a>
      <a class="button primary" href="<?= ja_h(ja_admin_query_url($vacationBaseQuery + ['year' => (int)date('Y')])) ?>">Año actual</a>
      <a class="button" href="<?= ja_h(ja_admin_query_url($vacationBaseQuery + ['year' => $vacationYear + 1])) ?>"><?= $vacationYear + 1 ?> →</a>
    </div>
  </div>

  <form class="vacation-timeline-filters" method="get">
    <input type="hidden" name="group" value="applications"><input type="hidden" name="app" value="temporalizacion_vacaciones">
    <label>Año<input type="number" name="year" min="2000" max="2100" value="<?= $vacationYear ?>"></label>
    <label>Departamento<select name="department"><option value="">Todos</option><?php foreach ($vacationDepartments as $department): ?><option value="<?= ja_h($department) ?>" <?= $vacationDepartment === $department ? 'selected' : '' ?>><?= ja_h($department) ?></option><?php endforeach; ?></select></label>
    <label class="conciliation-switch vacation-only-switch"><span><strong>Solo con vacaciones</strong><small>Oculta las filas sin periodos en el año.</small></span><input type="checkbox" name="only_with_vacations" value="1" <?= $vacationOnlyWithPeriods ? 'checked' : '' ?> onchange="this.form.submit()"><i aria-hidden="true"></i></label>
    <label class="vacation-zoom-control" for="vacationZoom"><span><strong>Zoom horizontal</strong><output id="vacationZoomValue" for="vacationZoom">100%</output></span><input id="vacationZoom" type="range" min="18" max="64" step="1" value="32"></label>
    <button class="button primary" type="submit">Aplicar</button>
    <a class="button" href="?group=applications&amp;app=temporalizacion_vacaciones">Limpiar</a>
    <a class="button" href="?group=tables&amp;view=vacations">Gestionar vacaciones</a>
  </form>

  <div class="vacation-timeline-metrics">
    <article class="metric"><span>Personas con vacaciones</span><strong><?= $vacationEmployeesWithPeriods ?></strong></article>
    <article class="metric"><span>Periodos visibles</span><strong><?= $vacationPeriodCount ?></strong></article>
    <article class="metric"><span>Días representados</span><strong><?= $vacationVisibleDays ?></strong></article>
    <article class="metric"><span>Periodos aprobados</span><strong><?= $vacationApprovedCount ?> / <?= $vacationPeriodCount ?></strong></article>
  </div>

  <article class="card vacation-timeline-card">
    <div class="ju-table-caption"><div><h3>Calendario anual de la plantilla</h3><p>Desplázate horizontalmente. La columna de personas permanece fija; cada barra representa un periodo y se puede abrir para editarlo.</p></div><div class="vacation-timeline-legend"><span><i></i>Aprobadas</span><span class="pending"><i></i>Pendientes</span></div></div>
    <div class="vacation-timeline-scroll"<?= $vacationTodayIndex !== null ? ' data-today-index="' . $vacationTodayIndex . '"' : '' ?>>
      <table class="vacation-timeline-table">
        <colgroup><col class="vacation-person-column"><?php foreach ($vacationDates as $unused): ?><col class="vacation-day-column"><?php endforeach; ?></colgroup>
        <thead><tr><th class="vacation-person-head">Persona</th><?php foreach ($vacationDates as $date): $weekday = (int)$date->format('N'); $firstOfMonth = $date->format('j') === '1'; $isToday = $date->format('Y-m-d') === date('Y-m-d'); ?><th class="vacation-date-head <?= $weekday >= 6 ? 'is-weekend' : '' ?> <?= $firstOfMonth ? 'is-month-start' : '' ?> <?= $isToday ? 'is-today' : '' ?>" title="<?= ja_h($date->format('d/m/Y')) ?>"><small><?= $firstOfMonth ? ja_h($vacationMonthNames[(int)$date->format('n')]) : '' ?></small><span><?= ja_h($date->format('d')) ?></span></th><?php endforeach; ?></tr></thead>
        <tbody>
          <?php foreach ($vacationEmployees as $employee): $employeeId = (int)$employee['id']; $timeline = $vacationBars[$employeeId] ?? ['items' => [], 'tracks' => 1]; $tracks = max(1, (int)($timeline['tracks'] ?? 1)); ?>
            <tr>
              <th class="vacation-person-cell"><strong><?= ja_h(ja_admin_employee_name($employee)) ?></strong><small><?= ja_h($employee['departamento'] ?: 'Sin departamento') ?> · <?= ja_h($employee['role'] ?: 'Sin rol') ?></small></th>
              <td class="vacation-lane-cell" colspan="<?= $vacationDayCount ?>">
                <div class="vacation-lane" style="--vacation-days:<?= $vacationDayCount ?>;--vacation-tracks:<?= $tracks ?>">
                  <?php foreach ($timeline['items'] as $period): $approved = !empty($period['approved']); $periodLabel = (new DateTimeImmutable((string)$period['start_date']))->format('d/m') . '–' . (new DateTimeImmutable((string)$period['end_date']))->format('d/m'); $daysLabel = (int)$period['day_count'] . ((int)$period['day_count'] === 1 ? ' día' : ' días'); $compactBar = (int)$period['span'] <= 3; ?>
                    <a class="vacation-bar <?= $approved ? 'is-approved' : 'is-pending' ?> <?= $compactBar ? 'is-compact' : '' ?>" data-days="<?= ja_h($daysLabel) ?>" style="grid-column:<?= (int)$period['start_index'] ?> / span <?= (int)$period['span'] ?>;grid-row:<?= (int)$period['track'] ?>" href="<?= ja_h(ja_admin_query_url(['group' => 'tables', 'view' => 'vacations', 'record_id' => (int)$period['id']])) ?>" title="<?= ja_h($periodLabel . ' · ' . $daysLabel . ($approved ? ' · Aprobadas' : ' · Pendientes')) ?>"><b class="vacation-day-count" title="<?= ja_h($daysLabel) ?>"><?= $compactBar ? (int)$period['day_count'] . 'd' : ja_h($daysLabel) ?></b><span class="vacation-bar-copy"><strong><?= ja_h($periodLabel) ?></strong><small><?= $approved ? 'Aprobadas' : 'Pendientes' ?></small></span></a>
                  <?php endforeach; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$vacationEmployees): ?><tr><td colspan="<?= $vacationDayCount + 1 ?>"><div class="empty">No hay personas que coincidan con el filtro seleccionado.</div></td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </article>
</section>
```

### admin/absence_attachment.php

```php
<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

use JA\AbsenceAttachment;
use JA\Auth;
use JA\Database;
use JA\Session;
use JA\TenantContext;

Session::start('admin');
$tenant = TenantContext::current();
$admin = Auth::requireTenantAdmin();
$tenantId = (int)$tenant['id'];
$absenceId = (int)($_GET['id'] ?? 0);

$statement = Database::pdo()->prepare(
    'SELECT attachment,attachment_name FROM absences WHERE id=:id AND tenant_id=:tenant LIMIT 1'
);
$statement->execute(['id' => $absenceId, 'tenant' => $tenantId]);
$absence = $statement->fetch();
if (!$absence || empty($absence['attachment'])) {
    http_response_code(404);
    exit('Adjunto no encontrado.');
}
AbsenceAttachment::download((string)$absence['attachment'], (string)($absence['attachment_name'] ?? 'adjunto'), $tenantId);

```

### admin/calendario_semanal.php

```php
<?php
declare(strict_types=1);

if (!defined('JA_ADMIN_APPLICATION')) {
    http_response_code(404);
    exit;
}

$baseDate = (string)($_GET['date'] ?? date('Y-m-d'));
if (!ja_admin_valid_date($baseDate) || $baseDate === '') {
    $baseDate = date('Y-m-d');
}
$selectedDate = new DateTimeImmutable($baseDate);
$weekStart = $selectedDate->modify('monday this week');
$weekEnd = $weekStart->modify('+6 days');
$previousWeek = $weekStart->modify('-7 days');
$nextWeek = $weekStart->modify('+7 days');

$statement = $pdo->prepare(
    "SELECT s.*, e.nombre, e.apellido, e.departamento, e.role,
            COALESCE((SELECT c.contract_type FROM contracts c
                      WHERE c.tenant_id=s.tenant_id AND c.employee_id=s.employee_id
                      ORDER BY c.id DESC LIMIT 1), ct.name) AS contract_name
     FROM schedules s
     JOIN employees e ON e.id=s.employee_id AND e.tenant_id=s.tenant_id
     LEFT JOIN contract_types ct ON ct.id=e.contract_type_id AND ct.tenant_id=e.tenant_id
     WHERE s.tenant_id=:tenant
       AND e.active=1
       AND (s.valid_from IS NULL OR s.valid_from<=:week_end)
       AND (s.valid_until IS NULL OR s.valid_until>=:week_start)
     ORDER BY e.apellido,e.nombre,s.weekday,s.start_time"
);
$statement->execute([
    'tenant' => $tenantId,
    'week_start' => $weekStart->format('Y-m-d'),
    'week_end' => $weekEnd->format('Y-m-d'),
]);
$calendar = [];
foreach ($statement->fetchAll() as $row) {
    $employeeId = (int)$row['employee_id'];
    if (!isset($calendar[$employeeId])) {
        $calendar[$employeeId] = ['employee' => $row, 'days' => []];
    }
    $calendar[$employeeId]['days'][(int)$row['weekday']][] = $row;
}
?>
<section class="application-stack">
  <div class="application-toolbar">
    <div><h2>Calendario semanal</h2><p>Semana del <?= ja_h($weekStart->format('d/m/Y')) ?> al <?= ja_h($weekEnd->format('d/m/Y')) ?></p></div>
    <div class="actions">
      <a class="button" href="<?= ja_h(ja_admin_query_url(['group' => 'applications', 'app' => 'calendario_semanal', 'date' => $previousWeek->format('Y-m-d')])) ?>">Semana anterior</a>
      <a class="button primary" href="<?= ja_h(ja_admin_query_url(['group' => 'applications', 'app' => 'calendario_semanal', 'date' => date('Y-m-d')])) ?>">Semana actual</a>
      <a class="button" href="<?= ja_h(ja_admin_query_url(['group' => 'applications', 'app' => 'calendario_semanal', 'date' => $nextWeek->format('Y-m-d')])) ?>">Semana siguiente</a>
    </div>
  </div>

  <?php if (!$calendar): ?>
    <div class="empty">No hay horarios activos para esta semana.</div>
  <?php else: ?>
    <div class="calendar-week-wrap">
      <table class="calendar-week">
        <thead><tr><th>Empleado</th><?php foreach ($weekdays as $day): ?><th><?= ja_h($day) ?></th><?php endforeach; ?></tr></thead>
        <tbody>
        <?php foreach ($calendar as $item): $employee = $item['employee']; ?>
          <tr>
            <td class="employee-cell"><strong><?= ja_h(ja_admin_employee_name($employee)) ?></strong><span><?= ja_h($employee['departamento'] ?: 'Sin departamento') ?></span><small><?= ja_h($employee['contract_name'] ?: 'Sin contrato asociado') ?></small></td>
            <?php foreach ($weekdays as $dayNumber => $day): ?>
              <td><?php if (empty($item['days'][$dayNumber])): ?><span class="no-shift">—</span><?php else: ?><?php foreach ($item['days'][$dayNumber] as $shift): ?><div class="shift-chip"><strong><?= ja_h(ja_admin_short_time($shift['start_time'])) ?> – <?= ja_h(ja_admin_short_time($shift['end_time'])) ?></strong><span><?= ja_h($shift['valid_from'] ?: 'Siempre') ?> → <?= ja_h($shift['valid_until'] ?: 'Sin fin') ?></span></div><?php endforeach; ?><?php endif; ?></td>
            <?php endforeach; ?>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>



```

### admin/cuadricula_asistencia.php

```php
<?php
declare(strict_types=1);

if (!defined('JA_ADMIN_APPLICATION')) {
    http_response_code(404);
    exit;
}

$gridMonth = trim((string)($_GET['month'] ?? date('Y-m')));
if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $gridMonth)) {
    $gridMonth = date('Y-m');
}
$gridStart = new DateTimeImmutable($gridMonth . '-01');
$gridEnd = $gridStart->modify('last day of this month');
$gridPrevious = $gridStart->modify('-1 month')->format('Y-m');
$gridNext = $gridStart->modify('+1 month')->format('Y-m');
$gridToday = date('Y-m-d');
$gridMonthNames = [1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril', 5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto', 9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre'];
$gridMonthLabel = ucfirst($gridMonthNames[(int)$gridStart->format('n')]) . ' ' . $gridStart->format('Y');
$gridDates = [];
for ($date = $gridStart; $date <= $gridEnd; $date = $date->modify('+1 day')) {
    $gridDates[] = $date->format('Y-m-d');
}

$gridDepartment = trim((string)($_GET['department'] ?? ''));
$gridRole = trim((string)($_GET['role'] ?? ''));
$employeeSql = 'SELECT id,nombre,apellido,departamento,role FROM employees WHERE tenant_id=:tenant AND active=1';
$employeeParameters = ['tenant' => $tenantId];
if ($gridDepartment !== '') {
    $employeeSql .= ' AND departamento=:department';
    $employeeParameters['department'] = $gridDepartment;
}
if ($gridRole !== '') {
    $employeeSql .= ' AND role=:role';
    $employeeParameters['role'] = $gridRole;
}
$employeeSql .= ' ORDER BY apellido COLLATE NOCASE,nombre COLLATE NOCASE';
$statement = $pdo->prepare($employeeSql);
$statement->execute($employeeParameters);
$gridEmployees = $statement->fetchAll();

$statement = $pdo->prepare("SELECT DISTINCT departamento FROM employees WHERE tenant_id=:tenant AND active=1 AND departamento IS NOT NULL AND trim(departamento)<>'' ORDER BY departamento COLLATE NOCASE");
$statement->execute(['tenant' => $tenantId]);
$gridDepartments = array_map(static fn(array $row): string => (string)$row['departamento'], $statement->fetchAll());
$statement = $pdo->prepare("SELECT DISTINCT role FROM employees WHERE tenant_id=:tenant AND active=1 AND role IS NOT NULL AND trim(role)<>'' ORDER BY role COLLATE NOCASE");
$statement->execute(['tenant' => $tenantId]);
$gridRoles = array_map(static fn(array $row): string => (string)$row['role'], $statement->fetchAll());

$statement = $pdo->prepare(
    "SELECT a.*,ft.name AS attendance_type_name
     FROM attendance a
     LEFT JOIN attendance_types ft ON ft.id=a.attendance_type_id AND ft.tenant_id=a.tenant_id
     WHERE a.tenant_id=:tenant AND a.work_date BETWEEN :start AND :end
     ORDER BY a.work_date,a.start_time,a.id"
);
$statement->execute(['tenant' => $tenantId, 'start' => $gridStart->format('Y-m-d'), 'end' => $gridEnd->format('Y-m-d')]);
$gridAttendance = [];
foreach ($statement->fetchAll() as $record) {
    $gridAttendance[(int)$record['employee_id']][(string)$record['work_date']][] = $record;
}

$statement = $pdo->prepare(
    "SELECT attendance_id,'admin' AS source FROM attendance_corrections WHERE tenant_id=:tenant1 AND status='pending'
     UNION ALL
     SELECT attendance_id,'worker' AS source FROM worker_attendance_corrections WHERE tenant_id=:tenant2 AND status='pending'"
);
$statement->execute(['tenant1' => $tenantId, 'tenant2' => $tenantId]);
$gridPendingByAttendance = [];
foreach ($statement->fetchAll() as $pending) {
    $gridPendingByAttendance[(int)$pending['attendance_id']] = (string)$pending['source'];
}

$statement = $pdo->prepare('SELECT employee_id,weekday,valid_from,valid_until FROM schedules WHERE tenant_id=:tenant AND (valid_from IS NULL OR valid_from<=:end) AND (valid_until IS NULL OR valid_until>=:start)');
$statement->execute(['tenant' => $tenantId, 'start' => $gridStart->format('Y-m-d'), 'end' => $gridEnd->format('Y-m-d')]);
$gridSchedules = [];
foreach ($statement->fetchAll() as $schedule) {
    $gridSchedules[(int)$schedule['employee_id']][(int)$schedule['weekday']][] = $schedule;
}

$statement = $pdo->prepare("SELECT employee_id,start_date,end_date,absence_type FROM absences WHERE tenant_id=:tenant AND status='approved' AND start_date<=:end AND (end_date IS NULL OR end_date>=:start)");
$statement->execute(['tenant' => $tenantId, 'start' => $gridStart->format('Y-m-d'), 'end' => $gridEnd->format('Y-m-d')]);
$gridAbsences = [];
foreach ($statement->fetchAll() as $absence) {
    $absenceStart = max((string)$absence['start_date'], $gridStart->format('Y-m-d'));
    $absenceEnd = $absence['end_date'] ? min((string)$absence['end_date'], $gridEnd->format('Y-m-d')) : $gridEnd->format('Y-m-d');
    for ($date = new DateTimeImmutable($absenceStart); $date->format('Y-m-d') <= $absenceEnd; $date = $date->modify('+1 day')) {
        $gridAbsences[(int)$absence['employee_id']][$date->format('Y-m-d')] = $absence;
    }
}

$statement = $pdo->prepare('SELECT holiday_date,name FROM holidays WHERE tenant_id=:tenant AND holiday_date BETWEEN :start AND :end');
$statement->execute(['tenant' => $tenantId, 'start' => $gridStart->format('Y-m-d'), 'end' => $gridEnd->format('Y-m-d')]);
$gridHolidays = [];
foreach ($statement->fetchAll() as $holiday) {
    $gridHolidays[(string)$holiday['holiday_date']] = (string)$holiday['name'];
}

$scheduleApplies = static function (int $employeeId, string $date) use ($gridSchedules): bool {
    $weekday = (int)(new DateTimeImmutable($date))->format('N');
    foreach ($gridSchedules[$employeeId][$weekday] ?? [] as $schedule) {
        if ((!$schedule['valid_from'] || $schedule['valid_from'] <= $date) && (!$schedule['valid_until'] || $schedule['valid_until'] >= $date)) {
            return true;
        }
    }
    return false;
};

$gridCells = [];
$gridStats = [];
foreach ($gridEmployees as $employee) {
    $employeeId = (int)$employee['id'];
    $stats = ['worked' => 0, 'missing' => 0, 'minutes' => 0, 'incidents' => 0, 'percent' => 100];
    foreach ($gridDates as $date) {
        $records = $gridAttendance[$employeeId][$date] ?? [];
        $cell = ['state' => 'none', 'label' => 'Sin horario ni registro', 'record_id' => 0, 'pending_source' => ''];
        if ($records) {
            $labels = [];
            $hasComplete = false;
            $hasOpen = false;
            $hasError = false;
            foreach ($records as $record) {
                $cell['record_id'] = (int)$record['id'];
                $pendingSource = $gridPendingByAttendance[(int)$record['id']] ?? '';
                if ($pendingSource !== '') {
                    $cell['pending_source'] = $pendingSource;
                }
                $start = ja_admin_short_time($record['start_time']);
                $end = ja_admin_short_time($record['end_time']);
                $type = $record['attendance_type_name'] ? ' · ' . $record['attendance_type_name'] : '';
                $labels[] = $start . '–' . $end . $type;
                $duration = ja_admin_duration_minutes($record['start_time'], $record['end_time']);
                if ($duration !== null) {
                    $hasComplete = true;
                    $stats['minutes'] += $duration;
                } elseif ($record['start_time'] && !$record['end_time'] && $date >= $gridToday) {
                    $hasOpen = true;
                } else {
                    $hasError = true;
                }
            }
            $cell['label'] = implode(' | ', $labels);
            if ($cell['pending_source'] !== '') {
                $cell['state'] = 'pending';
                $cell['label'] = 'Corrección pendiente · ' . $cell['label'];
                $stats['incidents']++;
            } elseif ($hasError) {
                $cell['state'] = 'error';
                $cell['label'] = 'Registro incompleto · ' . $cell['label'];
                $stats['incidents']++;
            } elseif ($hasOpen) {
                $cell['state'] = 'open';
                $cell['label'] = 'Jornada abierta · ' . $cell['label'];
                $stats['incidents']++;
            } elseif ($hasComplete) {
                $cell['state'] = 'worked';
                $stats['worked']++;
            }
        } elseif (isset($gridHolidays[$date])) {
            $cell['state'] = 'holiday';
            $cell['label'] = 'Festivo · ' . $gridHolidays[$date];
        } elseif (isset($gridAbsences[$employeeId][$date])) {
            $cell['state'] = 'absence';
            $cell['label'] = 'Ausencia aprobada · ' . $gridAbsences[$employeeId][$date]['absence_type'];
        } elseif ($scheduleApplies($employeeId, $date)) {
            if ($date < $gridToday) {
                $cell['state'] = 'missing';
                $cell['label'] = 'Falta registro para una jornada prevista';
                $stats['missing']++;
            } else {
                $cell['state'] = 'expected';
                $cell['label'] = 'Jornada prevista';
            }
        }
        $gridCells[$employeeId][$date] = $cell;
    }
    $comparable = $stats['worked'] + $stats['missing'];
    $stats['percent'] = $comparable > 0 ? (int)round(($stats['worked'] / $comparable) * 100) : 100;
    $gridStats[$employeeId] = $stats;
}

$gridBaseQuery = ['group' => 'applications', 'app' => 'cuadricula_asistencia', 'department' => $gridDepartment ?: null, 'role' => $gridRole ?: null];
?>
<section class="application-stack worker-attendance-grid" id="workerAttendanceGrid" data-columns="month" data-symbols="dots" data-color="1">
  <div class="application-toolbar">
    <div><h2>Cuadrícula de asistencia</h2><p><?= ja_h($gridMonthLabel) ?> · <?= count($gridEmployees) ?> personas visibles · cada celda abre el alta o la corrección auditada.</p></div>
    <div class="actions"><a class="button" href="<?= ja_h(ja_admin_query_url($gridBaseQuery + ['month' => $gridPrevious])) ?>">Mes anterior</a><a class="button primary" href="<?= ja_h(ja_admin_query_url($gridBaseQuery + ['month' => date('Y-m')])) ?>">Mes actual</a><a class="button" href="<?= ja_h(ja_admin_query_url($gridBaseQuery + ['month' => $gridNext])) ?>">Mes siguiente</a></div>
  </div>

  <form class="worker-grid-filters" method="get">
    <input type="hidden" name="group" value="applications"><input type="hidden" name="app" value="cuadricula_asistencia">
    <label>Mes<input type="month" name="month" value="<?= ja_h($gridMonth) ?>"></label>
    <label>Departamento<select name="department"><option value="">Todos</option><?php foreach ($gridDepartments as $department): ?><option value="<?= ja_h($department) ?>" <?= $gridDepartment === $department ? 'selected' : '' ?>><?= ja_h($department) ?></option><?php endforeach; ?></select></label>
    <label>Rol<select name="role"><option value="">Todos</option><?php foreach ($gridRoles as $role): ?><option value="<?= ja_h($role) ?>" <?= $gridRole === $role ? 'selected' : '' ?>><?= ja_h($role) ?></option><?php endforeach; ?></select></label>
    <button class="button primary" type="submit">Aplicar</button><a class="button" href="?group=applications&amp;app=cuadricula_asistencia">Limpiar</a>
  </form>

  <section class="card worker-grid-options">
    <div class="worker-grid-option-row">
      <label><span>Saturación</span><input id="workerGridSaturation" type="range" min="20" max="180" value="100"></label><strong id="workerGridSaturationValue">100%</strong>
      <label><span>Fundido a blanco</span><input id="workerGridFade" type="range" min="0" max="95" value="0"></label><strong id="workerGridFadeValue">0%</strong>
      <label class="check"><input id="workerGridColor" type="checkbox" checked> Color ON/OFF</label>
    </div>
    <div class="worker-grid-button-row"><strong>Columnas:</strong><button class="button small active" type="button" data-worker-columns="month">Mes completo</button><button class="button small" type="button" data-worker-columns="last7">Últimos 7 días</button></div>
    <div class="worker-grid-button-row"><strong>Símbolos:</strong><button class="button small active" type="button" data-worker-symbols="dots">● / ·</button><button class="button small" type="button" data-worker-symbols="ticks">✓ / ×</button><button class="button small" type="button" data-worker-symbols="letters">A / F</button><button class="button small" type="button" data-worker-symbols="blank">Vacío</button></div>
  </section>

  <section class="card worker-grid-card">
    <div class="ju-table-caption"><div><h3>Plantilla por día</h3><p>Los estados se calculan a partir de fichajes, horarios, ausencias, festivos y correcciones pendientes.</p></div><span class="badge ok"><?= count($gridDates) ?> días</span></div>
    <div class="worker-grid-search">
      <strong>Buscar persona</strong><input id="workerGridSearch" type="search" placeholder="Pulsa / o Ctrl+F y escribe nombre..." autocomplete="off"><button class="button small" id="workerGridClear" type="button">Limpiar</button>
      <span id="workerGridSearchCount"></span><span class="key">/</span><span class="key">Ctrl+F</span><span class="key">↑↓</span><span class="key">Enter</span><span class="key">Esc</span>
    </div>
    <div class="worker-grid-table-wrap"><table class="worker-grid-table"><thead><tr><th class="worker-grid-name">Persona</th><th class="worker-grid-stats">Stats</th><th class="worker-grid-progress">%</th><?php foreach ($gridDates as $index => $date): $dateObject = new DateTimeImmutable($date); ?><th class="worker-grid-date <?= $index < count($gridDates) - 7 ? 'worker-grid-history' : 'worker-grid-last7' ?> <?= $date === $gridToday ? 'is-today' : '' ?>"><span><?= ja_h($dateObject->format('d/m/Y')) ?></span></th><?php endforeach; ?></tr></thead><tbody>
      <?php foreach ($gridEmployees as $employee): $employeeId = (int)$employee['id']; $stats = $gridStats[$employeeId]; $employeeName = ja_admin_employee_name($employee); ?>
        <tr data-worker-row data-worker-name="<?= ja_h(function_exists('mb_strtolower') ? mb_strtolower($employeeName, 'UTF-8') : strtolower($employeeName)) ?>">
          <th class="worker-grid-name" data-worker-select><span class="worker-grid-person"><strong><?= ja_h($employeeName) ?></strong><small><?= ja_h($employee['departamento'] ?: 'Sin departamento') ?> · <?= ja_h($employee['role'] ?: 'Sin rol') ?></small></span><a class="worker-grid-report" href="<?= ja_h(ja_admin_query_url(['group' => 'applications', 'app' => 'estadisticas_empleados', 'employee_id' => $employeeId])) ?>" title="Abrir estadísticas">↗</a></th>
          <td class="worker-grid-stats"><span class="oktxt"><?= (int)$stats['worked'] ?></span> / <span class="kotxt"><?= (int)$stats['missing'] ?></span><small><?= ja_h(ja_admin_minutes_label((int)$stats['minutes'])) ?></small></td>
          <td class="worker-grid-progress"><div class="worker-grid-progress-track"><i style="width:<?= (int)$stats['percent'] ?>%"></i></div><strong><?= (int)$stats['percent'] ?>%</strong></td>
          <?php foreach ($gridDates as $index => $date): $cell = $gridCells[$employeeId][$date];
            if ($cell['record_id'] > 0) {
                $cellHref = $cell['pending_source'] === 'worker'
                    ? ja_admin_query_url(['group' => 'applications', 'app' => 'solicitudes_personal'])
                    : ja_admin_query_url(['group' => 'tables', 'view' => 'attendance', 'record_id' => $cell['record_id']]);
            } elseif ($cell['state'] === 'absence') {
                $cellHref = ja_admin_query_url(['group' => 'tables', 'view' => 'absences']);
            } elseif ($cell['state'] === 'holiday') {
                $cellHref = ja_admin_query_url(['group' => 'tables', 'view' => 'holidays']);
            } else {
                $cellHref = ja_admin_query_url(['group' => 'tables', 'view' => 'attendance', 'employee_id' => $employeeId, 'date' => $date]);
            }
          ?><td class="worker-grid-cell <?= $index < count($gridDates) - 7 ? 'worker-grid-history' : 'worker-grid-last7' ?> <?= $date === $gridToday ? 'is-today' : '' ?>" data-worker-state="<?= ja_h($cell['state']) ?>"><a href="<?= ja_h($cellHref) ?>" title="<?= ja_h($cell['label']) ?>" aria-label="<?= ja_h($employeeName . ', ' . $date . ': ' . $cell['label']) ?>"><span></span></a></td><?php endforeach; ?>
        </tr>
      <?php endforeach; ?>
      <?php if (!$gridEmployees): ?><tr><td colspan="<?= count($gridDates) + 3 ?>"><div class="empty">No hay personas que coincidan con estos filtros.</div></td></tr><?php endif; ?>
    </tbody></table></div>
    <div class="worker-grid-legend"><span data-legend-state="none"><i></i>sin horario</span><span data-legend-state="worked"><i></i>trabajado</span><span data-legend-state="missing"><i></i>falta registro</span><span data-legend-state="open"><i></i>abierta</span><span data-legend-state="absence"><i></i>ausencia aprobada</span><span data-legend-state="pending"><i></i>corrección pendiente</span><span data-legend-state="holiday"><i></i>festivo</span><span data-legend-state="error"><i></i>error</span></div>
  </section>
</section>
<script>
(() => {
  const root = document.getElementById('workerAttendanceGrid');
  if (!root) return;
  const $ = selector => root.querySelector(selector);
  const $$ = selector => [...root.querySelectorAll(selector)];
  const storage = {
    saturation: 'jocarsa_asistencias_grid_saturation', fade: 'jocarsa_asistencias_grid_fade',
    color: 'jocarsa_asistencias_grid_color', columns: 'jocarsa_asistencias_grid_columns', symbols: 'jocarsa_asistencias_grid_symbols'
  };
  const symbols = {
    dots: {worked:'●',missing:'·',open:'◐',absence:'A',pending:'?',holiday:'◆',error:'!',expected:'○',none:''},
    ticks: {worked:'✓',missing:'×',open:'◐',absence:'A',pending:'?',holiday:'H',error:'!',expected:'○',none:''},
    letters: {worked:'A',missing:'F',open:'O',absence:'J',pending:'P',holiday:'H',error:'E',expected:'V',none:''},
    blank: {worked:'',missing:'',open:'',absence:'',pending:'',holiday:'',error:'',expected:'',none:''}
  };
  const saturation = $('#workerGridSaturation');
  const fade = $('#workerGridFade');
  const color = $('#workerGridColor');
  const apply = () => {
    const saturationValue = Math.max(20, Math.min(180, Number(saturation.value || 100)));
    const fadeValue = Math.max(0, Math.min(95, Number(fade.value || 0)));
    const columnMode = root.dataset.columns || 'month';
    const symbolMode = root.dataset.symbols || 'dots';
    root.style.setProperty('--worker-grid-saturation', `${saturationValue}%`);
    root.style.setProperty('--worker-grid-fade', `${fadeValue}%`);
    root.dataset.color = color.checked ? '1' : '0';
    $('#workerGridSaturationValue').textContent = `${saturationValue}%`;
    $('#workerGridFadeValue').textContent = `${fadeValue}%`;
    $$('[data-worker-columns]').forEach(button => button.classList.toggle('active', button.dataset.workerColumns === columnMode));
    $$('[data-worker-symbols]').forEach(button => button.classList.toggle('active', button.dataset.workerSymbols === symbolMode));
    $$('[data-worker-state]').forEach(cell => {
      const state = cell.dataset.workerState || 'none';
      const target = cell.querySelector('span');
      if (target) target.textContent = symbols[symbolMode]?.[state] ?? '';
    });
    localStorage.setItem(storage.saturation, String(saturationValue));
    localStorage.setItem(storage.fade, String(fadeValue));
    localStorage.setItem(storage.color, color.checked ? '1' : '0');
    localStorage.setItem(storage.columns, columnMode);
    localStorage.setItem(storage.symbols, symbolMode);
  };
  saturation.value = localStorage.getItem(storage.saturation) || '100';
  fade.value = localStorage.getItem(storage.fade) || '0';
  color.checked = localStorage.getItem(storage.color) !== '0';
  root.dataset.columns = localStorage.getItem(storage.columns) || 'month';
  root.dataset.symbols = localStorage.getItem(storage.symbols) || 'dots';
  saturation.addEventListener('input', apply);
  fade.addEventListener('input', apply);
  color.addEventListener('change', apply);
  $$('[data-worker-columns]').forEach(button => button.addEventListener('click', () => { root.dataset.columns = button.dataset.workerColumns; apply(); }));
  $$('[data-worker-symbols]').forEach(button => button.addEventListener('click', () => { root.dataset.symbols = button.dataset.workerSymbols; apply(); }));
  apply();

  const search = $('#workerGridSearch');
  const count = $('#workerGridSearchCount');
  let focused = -1;
  const visibleRows = () => $$('[data-worker-row]').filter(row => row.hidden === false);
  const focusRow = index => {
    const rows = visibleRows();
    $$('.keyboard-focused-row').forEach(row => row.classList.remove('keyboard-focused-row'));
    if (!rows.length) { focused = -1; return; }
    focused = Math.max(0, Math.min(index, rows.length - 1));
    rows[focused].classList.add('keyboard-focused-row');
    rows[focused].scrollIntoView({block:'nearest', inline:'nearest'});
  };
  const filterRows = () => {
    const query = search.value.trim().toLocaleLowerCase('es-ES');
    let visible = 0;
    const rows = $$('[data-worker-row]');
    rows.forEach(row => { row.hidden = !!query && !(row.dataset.workerName || '').includes(query); if (!row.hidden) visible++; });
    count.textContent = query ? `${visible}/${rows.length} visibles` : `${rows.length} personas`;
    focused = visible ? 0 : -1;
    if (query) focusRow(0); else $$('.keyboard-focused-row').forEach(row => row.classList.remove('keyboard-focused-row'));
  };
  search.addEventListener('input', filterRows);
  search.addEventListener('keydown', event => {
    if (event.key === 'Escape') { event.preventDefault(); search.value = ''; filterRows(); }
    if (event.key === 'ArrowDown') { event.preventDefault(); focusRow(focused + 1); }
    if (event.key === 'ArrowUp') { event.preventDefault(); focusRow(focused - 1); }
    if (event.key === 'Enter') { event.preventDefault(); const rows = visibleRows(); if (rows[focused]) { $$('.selected-row').forEach(row => row.classList.remove('selected-row')); rows[focused].classList.add('selected-row'); } }
  });
  $('#workerGridClear').addEventListener('click', () => { search.value = ''; filterRows(); search.focus(); });
  $$('[data-worker-select]').forEach(cell => cell.addEventListener('click', event => { if (event.target.closest('a')) return; const row = cell.closest('tr'); $$('.selected-row').forEach(item => item.classList.remove('selected-row')); row.classList.add('selected-row'); }));
  document.addEventListener('keydown', event => {
    const typing = ['INPUT','TEXTAREA','SELECT'].includes(document.activeElement?.tagName || '');
    if ((event.key === '/' && !typing) || (event.ctrlKey && event.key.toLowerCase() === 'f')) { event.preventDefault(); search.focus(); search.select(); }
  });
  filterRows();
})();
</script>

```

### admin/entity_tables.php

```php
<?php
declare(strict_types=1);

use JA\ContractCatalog;
use JA\AttendanceTypeCatalog;

/**
 * Las trece entradas originales corresponden a las tablas de negocio heredadas.
 * La versión 2.0 incorpora Llamamientos y Tipos de fichaje por rol.
 * Los nombres físicos son los de la arquitectura SQLite multi-tenant 2.x.
 * dias_de_la_semana se mantiene como catálogo virtual porque el día se
 * guarda como un entero validado en schedules y no necesita tabla auxiliar.
 *
 * @return array<string,array{title:string,hint:string}>
 */
function ja_admin_business_table_views(): array
{
    return [
        'tenant_admins' => ['title' => 'admin', 'hint' => 'Accesos administrativos de esta organización.'],
        'attendance' => ['title' => 'Asistencia', 'hint' => 'Entradas, salidas y jornadas abiertas.'],
        'attendance_types' => ['title' => 'Tipos de fichaje', 'hint' => 'Categorías de jornada disponibles para cada rol.'],
        'absences' => ['title' => 'Ausencias', 'hint' => 'Solicitudes, aprobación y rechazo.'],
        'sick_leaves' => ['title' => 'Bajas', 'hint' => 'Bajas registradas directamente por administración.'],
        'work_centers' => ['title' => 'Centros de trabajo', 'hint' => 'Sedes y ubicaciones de la plantilla.'],
        'contracts' => ['title' => 'Contratos', 'hint' => 'Histórico contractual y condiciones económicas.'],
        'call_periods' => ['title' => 'Llamamientos', 'hint' => 'Periodos de actividad de los contratos fijo-discontinuos.'],
        'weekdays' => ['title' => 'Días de la semana', 'hint' => 'Catálogo normalizado utilizado por los horarios.'],
        'employees' => ['title' => 'Empleados', 'hint' => 'Plantilla, centros y contratos.'],
        'holidays' => ['title' => 'Festivos', 'hint' => 'Calendario laboral, festivos nacionales, autonómicos y locales.'],
        'schedules' => ['title' => 'Horarios', 'hint' => 'Franjas semanales y vigencias.'],
        'incidents' => ['title' => 'Incidencias', 'hint' => 'Incidencias registradas para cada empleado.'],
        'contract_types' => ['title' => 'Tipos de contrato', 'hint' => 'Catálogo de modalidades contractuales.'],
        'users' => ['title' => 'Usuarios', 'hint' => 'Accesos de empleados al front.'],
        'vacations' => ['title' => 'Vacaciones', 'hint' => 'Periodos vacacionales y estado de aprobación.'],
        'internal_communications' => ['title' => 'Comunicación interna laboral', 'hint' => 'Tipos y mensajes comunicados a la plantilla.'],
    ];
}

function ja_admin_business_action(
    PDO $pdo,
    int $tenantId,
    array $admin,
    string $action
): bool {
    if ($action === 'save_tenant_admin') {
        $id = (int)($_POST['record_id'] ?? 0);
        $fullName = ja_post('full_name');
        $username = ja_post('username');
        $email = ja_post('email') ?: null;
        $password = (string)($_POST['password'] ?? '');
        if ($fullName === '' || $username === '') {
            throw new RuntimeException('El nombre y el usuario son obligatorios.');
        }
        if ($email !== null && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('El correo electrónico no es válido.');
        }
        if ($id < 1 && strlen($password) < 8) {
            throw new RuntimeException('La contraseña inicial debe tener al menos 8 caracteres.');
        }
        if ($password !== '' && strlen($password) < 8) {
            throw new RuntimeException('La nueva contraseña debe tener al menos 8 caracteres.');
        }
        if ($id > 0) {
            $parameters = ['name' => $fullName, 'email' => $email, 'username' => $username, 'id' => $id, 'tenant' => $tenantId];
            $sql = 'UPDATE tenant_admins SET full_name=:name,email=:email,username=:username,updated_at=CURRENT_TIMESTAMP';
            if ($password !== '') {
                $sql .= ',password_hash=:password';
                $parameters['password'] = password_hash($password, PASSWORD_DEFAULT);
            }
            $sql .= ' WHERE id=:id AND tenant_id=:tenant';
            $pdo->prepare($sql)->execute($parameters);
        } else {
            $statement = $pdo->prepare('INSERT INTO tenant_admins (tenant_id,full_name,email,username,password_hash,active) VALUES (:tenant,:name,:email,:username,:password,1)');
            $statement->execute([
                'tenant' => $tenantId,
                'name' => $fullName,
                'email' => $email,
                'username' => $username,
                'password' => password_hash($password, PASSWORD_DEFAULT),
            ]);
        }
        return true;
    }

    if ($action === 'toggle_tenant_admin') {
        $id = (int)($_POST['record_id'] ?? 0);
        if ($id === (int)($admin['id'] ?? 0)) {
            throw new RuntimeException('No puedes desactivar la cuenta con la que has iniciado sesión.');
        }
        $statement = $pdo->prepare('UPDATE tenant_admins SET active=CASE active WHEN 1 THEN 0 ELSE 1 END,updated_at=CURRENT_TIMESTAMP WHERE id=:id AND tenant_id=:tenant');
        $statement->execute(['id' => $id, 'tenant' => $tenantId]);
        return true;
    }

    if ($action === 'save_attendance_type') {
        $id = (int)($_POST['record_id'] ?? 0);
        $role = AttendanceTypeCatalog::normalizeRole(ja_post('role'));
        $name = trim(ja_post('name'));
        if ($name === '' || strlen($name) > 100) {
            throw new RuntimeException('El nombre del tipo de fichaje es obligatorio y no puede superar 100 caracteres.');
        }
        $active = isset($_POST['active']) ? 1 : 0;
        $isDefault = isset($_POST['is_default']) ? 1 : 0;
        if ($isDefault) {
            $active = 1;
        }

        if (!$pdo->inTransaction()) {
            $pdo->beginTransaction();
        }
        if ($isDefault) {
            $clear = $pdo->prepare(
                'UPDATE attendance_types SET is_default=0,updated_at=CURRENT_TIMESTAMP
                 WHERE tenant_id=:tenant AND lower(trim(role))=lower(trim(:role))'
            );
            $clear->execute(['tenant' => $tenantId, 'role' => $role]);
        }
        $data = [
            'tenant' => $tenantId,
            'role' => $role,
            'name' => $name,
            'default_type' => $isDefault,
            'active' => $active,
        ];
        if ($id > 0) {
            $statement = $pdo->prepare(
                'UPDATE attendance_types
                 SET role=:role,name=:name,is_default=:default_type,active=:active,updated_at=CURRENT_TIMESTAMP
                 WHERE id=:id AND tenant_id=:tenant'
            );
            $statement->execute($data + ['id' => $id]);
            if ($statement->rowCount() !== 1) {
                throw new RuntimeException('Tipo de fichaje no válido para esta organización.');
            }
        } else {
            $pdo->prepare(
                'INSERT INTO attendance_types (tenant_id,role,name,is_default,active)
                 VALUES (:tenant,:role,:name,:default_type,:active)'
            )->execute($data);
        }
        return true;
    }

    if ($action === 'delete_attendance_type') {
        ja_admin_delete_tenant_row($pdo, 'attendance_types', (int)($_POST['record_id'] ?? 0), $tenantId);
        return true;
    }

    if ($action === 'save_work_center') {
        $id = (int)($_POST['record_id'] ?? 0);
        $name = ja_post('name');
        if ($name === '') {
            throw new RuntimeException('El nombre del centro es obligatorio.');
        }
        $data = [
            'tenant' => $tenantId,
            'name' => $name,
            'address' => ja_post('address') ?: null,
            'latitude' => ja_admin_nullable_number($_POST['latitude'] ?? null, 'latitud'),
            'longitude' => ja_admin_nullable_number($_POST['longitude'] ?? null, 'longitud'),
        ];
        if ($id > 0) {
            $pdo->prepare('UPDATE work_centers SET name=:name,address=:address,latitude=:latitude,longitude=:longitude WHERE id=:id AND tenant_id=:tenant')->execute($data + ['id' => $id]);
        } else {
            $pdo->prepare('INSERT INTO work_centers (tenant_id,name,address,latitude,longitude) VALUES (:tenant,:name,:address,:latitude,:longitude)')->execute($data);
        }
        return true;
    }

    if ($action === 'delete_work_center') {
        ja_admin_delete_tenant_row($pdo, 'work_centers', (int)($_POST['record_id'] ?? 0), $tenantId);
        return true;
    }

    if ($action === 'save_contract_type') {
        $id = (int)($_POST['record_id'] ?? 0);
        $name = ja_post('name');
        if ($name === '') {
            throw new RuntimeException('El nombre del tipo de contrato es obligatorio.');
        }
        $category = ja_post('category', 'special');
        if (!isset(ContractCatalog::categories()[$category])) {
            throw new RuntimeException('La categoría contractual no es válida.');
        }
        $description = ja_post('description') ?: null;
        $fixed = isset($_POST['is_fixed_discontinuous']) ? 1 : 0;
        $active = isset($_POST['active']) ? 1 : 0;
        $code = ja_admin_contract_code(ja_post('code') ?: $name);

        if ($id > 0) {
            $existing = ja_admin_fetch_tenant_row($pdo, 'contract_types', $tenantId, $id);
            if (!$existing) {
                throw new RuntimeException('Tipo de contrato no válido para esta organización.');
            }
            if (!empty($existing['is_system'])) {
                $code = (string)$existing['code'];
                $category = (string)$existing['category'];
                $fixed = (int)$existing['is_fixed_discontinuous'];
            }
            $pdo->prepare(
                'UPDATE contract_types
                 SET code=:code,name=:name,category=:category,description=:description,
                     is_fixed_discontinuous=:fixed,active=:active
                 WHERE id=:id AND tenant_id=:tenant'
            )->execute([
                'code' => $code,
                'name' => $name,
                'category' => $category,
                'description' => $description,
                'fixed' => $fixed,
                'active' => $active,
                'id' => $id,
                'tenant' => $tenantId,
            ]);
        } else {
            $pdo->prepare(
                'INSERT INTO contract_types
                   (tenant_id,code,name,category,description,is_fixed_discontinuous,is_system,active)
                 VALUES (:tenant,:code,:name,:category,:description,:fixed,0,:active)'
            )->execute([
                'tenant' => $tenantId,
                'code' => $code,
                'name' => $name,
                'category' => $category,
                'description' => $description,
                'fixed' => $fixed,
                'active' => $active,
            ]);
        }
        return true;
    }

    if ($action === 'delete_contract_type') {
        $id = (int)($_POST['record_id'] ?? 0);
        $row = ja_admin_fetch_tenant_row($pdo, 'contract_types', $tenantId, $id);
        if (!$row) {
            throw new RuntimeException('Tipo de contrato no válido.');
        }
        if (!empty($row['is_system'])) {
            throw new RuntimeException('Las modalidades oficiales del sistema no se eliminan; puedes desactivarlas.');
        }
        ja_admin_delete_tenant_row($pdo, 'contract_types', $id, $tenantId);
        return true;
    }

    if ($action === 'save_contract') {
        $id = (int)($_POST['record_id'] ?? 0);
        $employeeId = ja_admin_require_tenant_employee($pdo, $tenantId, (int)($_POST['employee_id'] ?? 0));
        $contractTypeId = (int)($_POST['contract_type_id'] ?? 0);
        $typeStatement = $pdo->prepare('SELECT id,name FROM contract_types WHERE id=:id AND tenant_id=:tenant LIMIT 1');
        $typeStatement->execute(['id' => $contractTypeId, 'tenant' => $tenantId]);
        $contractType = $typeStatement->fetch();
        $signedDate = ja_post('signed_date') ?: null;
        $endDate = ja_post('end_date') ?: null;
        if (!$contractType) {
            throw new RuntimeException('El tipo de contrato es obligatorio.');
        }
        ja_admin_validate_date_range($signedDate, $endDate, false);
        $workdayType = ja_post('workday_type', 'full_time');
        $workArrangement = ja_post('work_arrangement', 'on_site');
        if (!in_array($workdayType, ['full_time', 'part_time'], true)) {
            throw new RuntimeException('La jornada contractual no es válida.');
        }
        if (!in_array($workArrangement, ['on_site', 'remote', 'hybrid'], true)) {
            throw new RuntimeException('La modalidad de prestación no es válida.');
        }
        $data = [
            'tenant' => $tenantId,
            'employee' => $employeeId,
            'type_id' => $contractTypeId,
            'type' => (string)$contractType['name'],
            'signed' => $signedDate,
            'end' => $endDate,
            'salary' => ja_admin_nullable_number($_POST['annual_salary'] ?? null, 'salario anual'),
            'workday' => $workdayType,
            'arrangement' => $workArrangement,
            'notes' => ja_post('notes') ?: null,
        ];
        if ($id > 0) {
            $pdo->prepare(
                'UPDATE contracts SET employee_id=:employee,contract_type_id=:type_id,contract_type=:type,
                 signed_date=:signed,end_date=:end,annual_salary=:salary,workday_type=:workday,
                 work_arrangement=:arrangement,notes=:notes WHERE id=:id AND tenant_id=:tenant'
            )->execute($data + ['id' => $id]);
        } else {
            $pdo->prepare(
                'INSERT INTO contracts
                   (tenant_id,employee_id,contract_type_id,contract_type,signed_date,end_date,annual_salary,workday_type,work_arrangement,notes)
                 VALUES (:tenant,:employee,:type_id,:type,:signed,:end,:salary,:workday,:arrangement,:notes)'
            )->execute($data);
        }
        return true;
    }

    if ($action === 'delete_contract') {
        $id = (int)($_POST['record_id'] ?? 0);
        $calls = $pdo->prepare('SELECT COUNT(*) FROM call_periods WHERE contract_id=:id AND tenant_id=:tenant');
        $calls->execute(['id' => $id, 'tenant' => $tenantId]);
        if ((int)$calls->fetchColumn() > 0) {
            throw new RuntimeException('No se puede eliminar un contrato con llamamientos registrados.');
        }
        ja_admin_delete_tenant_row($pdo, 'contracts', $id, $tenantId);
        return true;
    }

    if ($action === 'save_call_period') {
        $id = (int)($_POST['record_id'] ?? 0);
        $contractId = (int)($_POST['contract_id'] ?? 0);
        $contract = $pdo->prepare(
            'SELECT c.id FROM contracts c
             JOIN contract_types ct ON ct.id=c.contract_type_id AND ct.tenant_id=c.tenant_id
             WHERE c.id=:contract AND c.tenant_id=:tenant AND ct.is_fixed_discontinuous=1 LIMIT 1'
        );
        $contract->execute(['contract' => $contractId, 'tenant' => $tenantId]);
        if (!$contract->fetchColumn()) {
            throw new RuntimeException('El llamamiento debe pertenecer a un contrato fijo-discontinuo de esta organización.');
        }
        $startDate = ja_post('start_date');
        $endDate = ja_post('end_date');
        ja_admin_validate_date_range($startDate, $endDate, true);
        $status = ja_post('status', 'planned');
        if (!in_array($status, ['planned', 'notified', 'active', 'completed', 'cancelled'], true)) {
            throw new RuntimeException('El estado del llamamiento no es válido.');
        }
        $noticeDate = ja_post('notice_date') ?: null;
        if (!ja_admin_valid_date($noticeDate)) {
            throw new RuntimeException('La fecha de aviso no es válida.');
        }
        if (!in_array($status, ['planned', 'cancelled'], true) && $noticeDate === null) {
            throw new RuntimeException('Indica la fecha de aviso antes de marcar el llamamiento como notificado o iniciado.');
        }
        $data = [
            'tenant' => $tenantId,
            'contract' => $contractId,
            'start' => $startDate,
            'end' => $endDate,
            'notice' => $noticeDate,
            'method' => ja_post('notification_method') ?: null,
            'status' => $status,
            'notes' => ja_post('notes') ?: null,
        ];
        if ($id > 0) {
            $pdo->prepare(
                'UPDATE call_periods SET contract_id=:contract,start_date=:start,end_date=:end,
                 notice_date=:notice,notification_method=:method,status=:status,notes=:notes,
                 updated_at=CURRENT_TIMESTAMP WHERE id=:id AND tenant_id=:tenant'
            )->execute($data + ['id' => $id]);
        } else {
            $pdo->prepare(
                'INSERT INTO call_periods
                   (tenant_id,contract_id,start_date,end_date,notice_date,notification_method,status,notes)
                 VALUES (:tenant,:contract,:start,:end,:notice,:method,:status,:notes)'
            )->execute($data);
        }
        return true;
    }

    if ($action === 'delete_call_period') {
        $id = (int)($_POST['record_id'] ?? 0);
        $statement = $pdo->prepare('SELECT status FROM call_periods WHERE id=:id AND tenant_id=:tenant');
        $statement->execute(['id' => $id, 'tenant' => $tenantId]);
        $status = $statement->fetchColumn();
        if ($status === false) {
            throw new RuntimeException('Llamamiento no válido.');
        }
        if ($status !== 'planned') {
            throw new RuntimeException('Solo puede eliminarse una previsión no notificada; cambia los demás llamamientos a cancelado.');
        }
        ja_admin_delete_tenant_row($pdo, 'call_periods', $id, $tenantId);
        return true;
    }

    if ($action === 'save_holiday') {
        $id = (int)($_POST['record_id'] ?? 0);
        $date = ja_post('holiday_date');
        $name = ja_post('name');
        if ($date === '' || !ja_admin_valid_date($date) || $name === '') {
            throw new RuntimeException('La fecha y el nombre del festivo son obligatorios.');
        }
        $data = ['tenant' => $tenantId, 'date' => $date, 'name' => $name, 'description' => ja_post('description') ?: null];
        if ($id > 0) {
            $pdo->prepare('UPDATE holidays SET holiday_date=:date,name=:name,description=:description WHERE id=:id AND tenant_id=:tenant')->execute($data + ['id' => $id]);
        } else {
            $pdo->prepare('INSERT INTO holidays (tenant_id,holiday_date,name,description) VALUES (:tenant,:date,:name,:description)')->execute($data);
        }
        return true;
    }

    if ($action === 'delete_holiday') {
        ja_admin_delete_tenant_row($pdo, 'holidays', (int)($_POST['record_id'] ?? 0), $tenantId);
        return true;
    }

    if ($action === 'save_incident') {
        $id = (int)($_POST['record_id'] ?? 0);
        $employeeId = ja_admin_require_tenant_employee($pdo, $tenantId, (int)($_POST['employee_id'] ?? 0));
        $type = ja_post('type');
        $date = ja_post('incident_date');
        if ($type === '' || $date === '' || !ja_admin_valid_date($date)) {
            throw new RuntimeException('El empleado, el tipo y la fecha de la incidencia son obligatorios.');
        }
        $data = ['tenant' => $tenantId, 'employee' => $employeeId, 'type' => $type, 'date' => $date, 'notes' => ja_post('notes') ?: null];
        if ($id > 0) {
            $pdo->prepare('UPDATE incidents SET employee_id=:employee,type=:type,incident_date=:date,notes=:notes WHERE id=:id AND tenant_id=:tenant')->execute($data + ['id' => $id]);
        } else {
            $pdo->prepare('INSERT INTO incidents (tenant_id,employee_id,type,incident_date,notes) VALUES (:tenant,:employee,:type,:date,:notes)')->execute($data);
        }
        return true;
    }

    if ($action === 'delete_incident') {
        ja_admin_delete_tenant_row($pdo, 'incidents', (int)($_POST['record_id'] ?? 0), $tenantId);
        return true;
    }

    if ($action === 'save_vacation') {
        $id = (int)($_POST['record_id'] ?? 0);
        $employeeId = ja_admin_require_tenant_employee($pdo, $tenantId, (int)($_POST['employee_id'] ?? 0));
        $startDate = ja_post('start_date');
        $endDate = ja_post('end_date');
        ja_admin_validate_date_range($startDate, $endDate, true);
        $data = [
            'tenant' => $tenantId,
            'employee' => $employeeId,
            'start' => $startDate,
            'end' => $endDate,
            'approved' => isset($_POST['approved']) ? 1 : 0,
        ];
        if ($id > 0) {
            $pdo->prepare('UPDATE vacations SET employee_id=:employee,start_date=:start,end_date=:end,approved=:approved WHERE id=:id AND tenant_id=:tenant')->execute($data + ['id' => $id]);
        } else {
            $pdo->prepare('INSERT INTO vacations (tenant_id,employee_id,start_date,end_date,approved) VALUES (:tenant,:employee,:start,:end,:approved)')->execute($data);
        }
        return true;
    }

    if ($action === 'delete_vacation') {
        ja_admin_delete_tenant_row($pdo, 'vacations', (int)($_POST['record_id'] ?? 0), $tenantId);
        return true;
    }

    return false;
}

function ja_admin_require_tenant_employee(PDO $pdo, int $tenantId, int $employeeId): int
{
    $statement = $pdo->prepare('SELECT id FROM employees WHERE id=:id AND tenant_id=:tenant');
    $statement->execute(['id' => $employeeId, 'tenant' => $tenantId]);
    if (!$statement->fetchColumn()) {
        throw new RuntimeException('Empleado no válido para esta organización.');
    }
    return $employeeId;
}

function ja_admin_contract_code(string $value): string
{
    $value = strtolower(strtr(trim($value), [
        'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n',
    ]));
    $value = trim((string)preg_replace('/[^a-z0-9]+/', '_', $value), '_');
    if ($value === '' || strlen($value) > 100) {
        throw new RuntimeException('El código del tipo de contrato no es válido.');
    }
    return $value;
}

function ja_admin_nullable_number(mixed $value, string $label): int|float|null
{
    $value = str_replace(',', '.', trim((string)$value));
    if ($value === '') {
        return null;
    }
    if (!is_numeric($value)) {
        throw new RuntimeException('El valor de ' . $label . ' no es numérico.');
    }
    return str_contains($value, '.') ? (float)$value : (int)$value;
}

function ja_admin_validate_date_range(?string $start, ?string $end, bool $required): void
{
    if ($required && (($start ?? '') === '' || ($end ?? '') === '')) {
        throw new RuntimeException('Las fechas de inicio y fin son obligatorias.');
    }
    if (!ja_admin_valid_date($start) || !ja_admin_valid_date($end)) {
        throw new RuntimeException('El periodo contiene una fecha no válida.');
    }
    if ($start !== null && $start !== '' && $end !== null && $end !== '' && $end < $start) {
        throw new RuntimeException('La fecha final no puede ser anterior a la fecha inicial.');
    }
}

function ja_admin_delete_tenant_row(PDO $pdo, string $table, int $id, int $tenantId): void
{
    $allowed = ['attendance_types', 'work_centers', 'contract_types', 'contracts', 'call_periods', 'holidays', 'incidents', 'vacations'];
    if (!in_array($table, $allowed, true)) {
        throw new RuntimeException('Tabla no permitida.');
    }
    $pdo->prepare('DELETE FROM "' . $table . '" WHERE id=:id AND tenant_id=:tenant')->execute(['id' => $id, 'tenant' => $tenantId]);
}

function ja_admin_render_business_table(
    PDO $pdo,
    int $tenantId,
    array $admin,
    string $view,
    array $employees,
    array $contractTypes,
    string $csrf
): void {
    $recordId = (int)($_GET['record_id'] ?? 0);
    $employeeOptions = static function (?int $selected = null) use ($employees): void {
        foreach ($employees as $employee) {
            $id = (int)$employee['id'];
            echo '<option value="' . $id . '"' . ($id === $selected ? ' selected' : '') . '>' . ja_h(ja_admin_employee_name($employee)) . '</option>';
        }
    };

    if ($view === 'weekdays') {
        $days = [1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo'];
        ?>
        <article class="card"><h3>Catálogo normalizado</h3><p>Desde la versión 2.0 no se duplica esta información en una tabla auxiliar: <code>schedules.weekday</code> admite únicamente valores del 1 al 7. Se conserva aquí la misma vista funcional de la aplicación heredada.</p></article>
        <?php if (isset($tableState)) ja_admin_render_table_controls($tableState); ?><div class="table-wrap"><table><thead><tr><th>ID</th><th>Nombre</th></tr></thead><tbody><?php foreach ($days as $id => $name): ?><tr><td><?= $id ?></td><td><?= ja_h($name) ?></td></tr><?php endforeach; ?></tbody></table></div>
        <?php
        return;
    }

    if ($view === 'tenant_admins') {
        $edit = ja_admin_fetch_tenant_row($pdo, 'tenant_admins', $tenantId, $recordId);
        $statement = $pdo->prepare('SELECT * FROM tenant_admins WHERE tenant_id=:tenant ORDER BY full_name,username');
        $statement->execute(['tenant' => $tenantId]);
        $rows = $statement->fetchAll();
        $tableState = ja_admin_server_table($rows, 'entity_' . $view);
        $GLOBALS['ja_admin_table_state_' . $view] = $tableState;
        $rows = $tableState['rows'];
        ?>
        <article class="card"><h3><?= $edit ? 'Editar administrador' : 'Nuevo administrador' ?></h3><form method="post" class="grid-2"><input type="hidden" name="csrf" value="<?= ja_h($csrf) ?>"><input type="hidden" name="action" value="save_tenant_admin"><input type="hidden" name="record_id" value="<?= (int)($edit['id'] ?? 0) ?>"><label>Nombre completo<input name="full_name" value="<?= ja_h($edit['full_name'] ?? '') ?>" required></label><label>Usuario<input name="username" value="<?= ja_h($edit['username'] ?? '') ?>" required></label><label>Email<input type="email" name="email" value="<?= ja_h($edit['email'] ?? '') ?>"></label><label><?= $edit ? 'Nueva contraseña (opcional)' : 'Contraseña inicial' ?><input type="password" name="password" minlength="8" <?= $edit ? '' : 'required' ?>></label><div class="actions"><button class="button primary" type="submit">Guardar administrador</button><?php if ($edit): ?><a class="button" href="?group=tables&amp;view=tenant_admins">Cancelar</a><?php endif; ?></div></form></article>
        <?php if (isset($tableState)) ja_admin_render_table_controls($tableState); ?><div class="table-wrap"><table><thead><tr><th>Nombre</th><th>Usuario</th><th>Email</th><th>Estado</th><th>Acciones</th></tr></thead><tbody><?php foreach ($rows as $row): ?><tr><td><?= ja_h($row['full_name']) ?></td><td><?= ja_h($row['username']) ?></td><td><?= ja_h($row['email']) ?></td><td><span class="badge <?= $row['active'] ? 'ok' : 'warning' ?>"><?= $row['active'] ? 'Activo' : 'Inactivo' ?></span></td><td><div class="actions"><a class="button small" href="?group=tables&amp;view=tenant_admins&amp;record_id=<?= (int)$row['id'] ?>">Editar</a><form method="post"><input type="hidden" name="csrf" value="<?= ja_h($csrf) ?>"><input type="hidden" name="action" value="toggle_tenant_admin"><input type="hidden" name="record_id" value="<?= (int)$row['id'] ?>"><button class="button small" type="submit" <?= (int)$row['id'] === (int)($admin['id'] ?? 0) ? 'disabled' : '' ?>><?= $row['active'] ? 'Desactivar' : 'Activar' ?></button></form></div></td></tr><?php endforeach; ?></tbody></table></div>
        <?php
        return;
    }

    if ($view === 'attendance_types') {
        $edit = ja_admin_fetch_tenant_row($pdo, 'attendance_types', $tenantId, $recordId);
        $rolesStatement = $pdo->prepare(
            "SELECT role FROM (
               SELECT upper(trim(role, char(9) || char(10) || char(13) || ' ')) role FROM employees WHERE tenant_id=:tenant1 AND trim(COALESCE(role,''), char(9) || char(10) || char(13) || ' ')<>''
               UNION
               SELECT upper(trim(role, char(9) || char(10) || char(13) || ' ')) role FROM attendance_types WHERE tenant_id=:tenant2 AND trim(role, char(9) || char(10) || char(13) || ' ')<>''
             ) roles ORDER BY role COLLATE NOCASE"
        );
        $rolesStatement->execute(['tenant1' => $tenantId, 'tenant2' => $tenantId]);
        $roles = array_values(array_filter(array_map('strval', $rolesStatement->fetchAll(PDO::FETCH_COLUMN))));
        $statement = $pdo->prepare(
            'SELECT ft.*,
              (SELECT COUNT(*) FROM attendance a WHERE a.tenant_id=ft.tenant_id AND a.attendance_type_id=ft.id) usage_count,
              (SELECT COUNT(*) FROM attendance_corrections c WHERE c.tenant_id=ft.tenant_id AND (c.original_attendance_type_id=ft.id OR c.proposed_attendance_type_id=ft.id)) correction_count,
              (SELECT COUNT(*) FROM worker_attendance_corrections c WHERE c.tenant_id=ft.tenant_id AND (c.original_attendance_type_id=ft.id OR c.proposed_attendance_type_id=ft.id)) worker_correction_count
             FROM attendance_types ft WHERE ft.tenant_id=:tenant
             ORDER BY ft.role COLLATE NOCASE,ft.is_default DESC,ft.name COLLATE NOCASE'
        );
        $statement->execute(['tenant' => $tenantId]);
        $rows = $statement->fetchAll();
        $tableState = ja_admin_server_table($rows, 'entity_' . $view);
        $GLOBALS['ja_admin_table_state_' . $view] = $tableState;
        $rows = $tableState['rows'];
        ?>
        <article class="card">
          <div class="ju-table-caption"><div><h3><?= $edit ? 'Editar tipo de fichaje' : 'Nuevo tipo de fichaje' ?></h3><p>Cada tipo pertenece a un rol. Si marcas uno como predeterminado aparecerá seleccionado al abrir el front móvil.</p></div><span class="badge ok"><?= count($roles) ?> roles disponibles</span></div>
          <?php if (!$roles): ?><div class="empty">Define primero un rol en la ficha de algún empleado.</div><?php else: ?>
          <form method="post" class="grid-2">
            <input type="hidden" name="csrf" value="<?= ja_h($csrf) ?>"><input type="hidden" name="action" value="save_attendance_type"><input type="hidden" name="record_id" value="<?= (int)($edit['id'] ?? 0) ?>">
            <label>Rol<select name="role" required><?php foreach ($roles as $role): ?><option value="<?= ja_h($role) ?>" <?= strcasecmp((string)($edit['role'] ?? ''), $role) === 0 ? 'selected' : '' ?>><?= ja_h($role) ?></option><?php endforeach; ?></select></label>
            <label>Nombre<input name="name" maxlength="100" value="<?= ja_h($edit['name'] ?? '') ?>" placeholder="Docencia" required></label>
            <label class="check"><input type="checkbox" name="is_default" value="1" <?= !empty($edit['is_default']) ? 'checked' : '' ?>> Seleccionado por defecto para este rol</label>
            <label class="check"><input type="checkbox" name="active" value="1" <?= !$edit || !empty($edit['active']) ? 'checked' : '' ?>> Visible en el front</label>
            <div class="actions"><button class="button primary" type="submit">Guardar tipo</button><?php if ($edit): ?><a class="button" href="?group=tables&amp;view=attendance_types">Cancelar</a><?php endif; ?></div>
          </form>
          <?php endif; ?>
        </article>
        <?php if (isset($tableState)) ja_admin_render_table_controls($tableState); ?><div class="table-wrap"><table><thead><tr><th>Rol</th><th>Tipo</th><th>Predeterminado</th><th>Estado</th><th>Uso</th><th>Acciones</th></tr></thead><tbody>
          <?php if (!$rows): ?><tr><td colspan="6"><div class="empty">Todavía no hay tipos de fichaje.</div></td></tr><?php endif; ?>
          <?php foreach ($rows as $row): $used = (int)$row['usage_count'] + (int)$row['correction_count'] + (int)$row['worker_correction_count']; ?><tr><td><strong><?= ja_h($row['role']) ?></strong></td><td><?= ja_h($row['name']) ?></td><td><?= !empty($row['is_default']) ? '<span class="badge ok">Sí</span>' : 'No' ?></td><td><span class="badge <?= !empty($row['active']) ? 'ok' : 'warning' ?>"><?= !empty($row['active']) ? 'Activo' : 'Inactivo' ?></span></td><td><?= (int)$row['usage_count'] ?> jornadas</td><td><div class="actions"><a class="button small" href="?group=tables&amp;view=attendance_types&amp;record_id=<?= (int)$row['id'] ?>">Editar</a><?php if ($used === 0): ?><form method="post"><input type="hidden" name="csrf" value="<?= ja_h($csrf) ?>"><input type="hidden" name="action" value="delete_attendance_type"><input type="hidden" name="record_id" value="<?= (int)$row['id'] ?>"><button class="button danger small" type="submit" data-confirm="¿Eliminar este tipo de fichaje?">Eliminar</button></form><?php endif; ?></div></td></tr><?php endforeach; ?>
        </tbody></table></div>
        <?php
        return;
    }

    if ($view === 'work_centers') {
        $edit = ja_admin_fetch_tenant_row($pdo, 'work_centers', $tenantId, $recordId);
        $statement = $pdo->prepare('SELECT c.*,(SELECT COUNT(*) FROM employees e WHERE e.tenant_id=c.tenant_id AND e.work_center_id=c.id) employee_count FROM work_centers c WHERE c.tenant_id=:tenant ORDER BY c.name');
        $statement->execute(['tenant' => $tenantId]);
        $rows = $statement->fetchAll();
        $tableState = ja_admin_server_table($rows, 'entity_' . $view);
        $GLOBALS['ja_admin_table_state_' . $view] = $tableState;
        $rows = $tableState['rows'];
        ?>
        <article class="card"><h3><?= $edit ? 'Editar centro' : 'Nuevo centro de trabajo' ?></h3><form method="post" class="grid-2"><input type="hidden" name="csrf" value="<?= ja_h($csrf) ?>"><input type="hidden" name="action" value="save_work_center"><input type="hidden" name="record_id" value="<?= (int)($edit['id'] ?? 0) ?>"><label>Nombre<input name="name" value="<?= ja_h($edit['name'] ?? '') ?>" required></label><label>Dirección<input name="address" value="<?= ja_h($edit['address'] ?? '') ?>"></label><label>Latitud<input type="number" step="any" name="latitude" value="<?= ja_h($edit['latitude'] ?? '') ?>"></label><label>Longitud<input type="number" step="any" name="longitude" value="<?= ja_h($edit['longitude'] ?? '') ?>"></label><div class="actions"><button class="button primary" type="submit">Guardar centro</button><?php if ($edit): ?><a class="button" href="?group=tables&amp;view=work_centers">Cancelar</a><?php endif; ?></div></form></article>
        <?php ja_admin_render_simple_rows($rows, ['name' => 'Nombre', 'address' => 'Dirección', 'latitude' => 'Latitud', 'longitude' => 'Longitud', 'employee_count' => 'Empleados'], 'work_centers', 'delete_work_center', $csrf); ?>
        <?php
        return;
    }

    if ($view === 'contract_types') {
        $edit = ja_admin_fetch_tenant_row($pdo, 'contract_types', $tenantId, $recordId);
        $statement = $pdo->prepare(
            'SELECT ct.*,
              (SELECT COUNT(*) FROM employees e WHERE e.tenant_id=ct.tenant_id AND e.contract_type_id=ct.id) employee_count,
              (SELECT COUNT(*) FROM contracts c WHERE c.tenant_id=ct.tenant_id AND c.contract_type_id=ct.id) contract_count
             FROM contract_types ct WHERE ct.tenant_id=:tenant
             ORDER BY CASE ct.category WHEN \'indefinite\' THEN 1 WHEN \'temporary\' THEN 2 WHEN \'training\' THEN 3 ELSE 4 END,ct.name'
        );
        $statement->execute(['tenant' => $tenantId]);
        $rows = $statement->fetchAll();
        $tableState = ja_admin_server_table($rows, 'entity_' . $view);
        $GLOBALS['ja_admin_table_state_' . $view] = $tableState;
        $rows = $tableState['rows'];
        $categories = ContractCatalog::categories();
        $systemEdit = !empty($edit['is_system']);
        ?>
        <article class="card">
          <div class="ju-table-caption"><div><h3><?= $edit ? 'Editar modalidad' : 'Nueva modalidad personalizada' ?></h3><p>Las 19 modalidades oficiales se precargan en cada organización. Jornada y prestación se definen en cada contrato.</p></div><?php if ($systemEdit): ?><span class="badge ok">Modalidad del sistema</span><?php endif; ?></div>
          <form method="post" class="grid-3">
            <input type="hidden" name="csrf" value="<?= ja_h($csrf) ?>"><input type="hidden" name="action" value="save_contract_type"><input type="hidden" name="record_id" value="<?= (int)($edit['id'] ?? 0) ?>">
            <label>Nombre<input name="name" value="<?= ja_h($edit['name'] ?? '') ?>" required></label>
            <label>Código<input name="code" value="<?= ja_h($edit['code'] ?? '') ?>" placeholder="Se genera desde el nombre" <?= $systemEdit ? 'readonly' : '' ?>></label>
            <label>Categoría<select name="category" <?= $systemEdit ? 'disabled' : '' ?>><?php foreach ($categories as $key => $label): ?><option value="<?= ja_h($key) ?>" <?= ($edit['category'] ?? 'special') === $key ? 'selected' : '' ?>><?= ja_h($label) ?></option><?php endforeach; ?></select></label>
            <?php if ($systemEdit): ?><input type="hidden" name="category" value="<?= ja_h($edit['category']) ?>"><?php endif; ?>
            <label class="form-span-full">Descripción<textarea name="description"><?= ja_h($edit['description'] ?? '') ?></textarea></label>
            <label class="check"><input type="checkbox" name="is_fixed_discontinuous" value="1" <?= !empty($edit['is_fixed_discontinuous']) ? 'checked' : '' ?> <?= $systemEdit ? 'disabled' : '' ?>> Genera llamamientos</label>
            <?php if ($systemEdit && !empty($edit['is_fixed_discontinuous'])): ?><input type="hidden" name="is_fixed_discontinuous" value="1"><?php endif; ?>
            <label class="check"><input type="checkbox" name="active" value="1" <?= !$edit || !empty($edit['active']) ? 'checked' : '' ?>> Disponible para nuevos contratos</label>
            <div class="actions"><button class="button primary" type="submit">Guardar modalidad</button><?php if ($edit): ?><a class="button" href="?group=tables&amp;view=contract_types">Cancelar</a><?php endif; ?></div>
          </form>
        </article>
        <?php if (isset($tableState)) ja_admin_render_table_controls($tableState); ?><div class="table-wrap"><table><thead><tr><th>Modalidad</th><th>Categoría</th><th>Fijo-discontinuo</th><th>Uso</th><th>Estado</th><th>Acciones</th></tr></thead><tbody><?php foreach ($rows as $row): ?><tr><td><strong><?= ja_h($row['name']) ?></strong><br><small><?= ja_h($row['description'] ?? '') ?></small></td><td><?= ja_h($categories[$row['category']] ?? $row['category']) ?></td><td><?= !empty($row['is_fixed_discontinuous']) ? '<span class="badge ok">Sí · llamamientos</span>' : 'No' ?></td><td><?= (int)$row['contract_count'] ?> contratos · <?= (int)$row['employee_count'] ?> empleados</td><td><span class="badge <?= !empty($row['active']) ? 'ok' : 'warning' ?>"><?= !empty($row['active']) ? 'Activa' : 'Inactiva' ?></span></td><td><div class="actions"><a class="button small" href="?group=tables&amp;view=contract_types&amp;record_id=<?= (int)$row['id'] ?>">Editar</a><?php if (empty($row['is_system'])): ?><form method="post"><input type="hidden" name="csrf" value="<?= ja_h($csrf) ?>"><input type="hidden" name="action" value="delete_contract_type"><input type="hidden" name="record_id" value="<?= (int)$row['id'] ?>"><button class="button danger small" type="submit" data-confirm="¿Eliminar esta modalidad personalizada?">Eliminar</button></form><?php endif; ?></div></td></tr><?php endforeach; ?></tbody></table></div>
        <?php
        return;
    }

    if ($view === 'contracts') {
        $edit = ja_admin_fetch_tenant_row($pdo, 'contracts', $tenantId, $recordId);
        $statement = $pdo->prepare(
            "SELECT c.*,TRIM(e.nombre || ' ' || COALESCE(e.apellido,'')) employee_name,
                    COALESCE(ct.name,c.contract_type) contract_type_name,
                    COALESCE(ct.is_fixed_discontinuous,0) is_fixed_discontinuous,
                    (SELECT COUNT(*) FROM call_periods cp WHERE cp.tenant_id=c.tenant_id AND cp.contract_id=c.id) call_count
             FROM contracts c
             JOIN employees e ON e.id=c.employee_id AND e.tenant_id=c.tenant_id
             LEFT JOIN contract_types ct ON ct.id=c.contract_type_id AND ct.tenant_id=c.tenant_id
             WHERE c.tenant_id=:tenant ORDER BY COALESCE(c.signed_date,'') DESC,c.id DESC"
        );
        $statement->execute(['tenant' => $tenantId]);
        $rows = $statement->fetchAll();
        $tableState = ja_admin_server_table($rows, 'entity_' . $view);
        $GLOBALS['ja_admin_table_state_' . $view] = $tableState;
        $rows = $tableState['rows'];
        $workdayLabels = ['full_time' => 'Tiempo completo', 'part_time' => 'Tiempo parcial'];
        $arrangementLabels = ['on_site' => 'Presencial', 'remote' => 'A distancia', 'hybrid' => 'Híbrido'];
        ?>
        <article class="card"><h3><?= $edit ? 'Editar contrato' : 'Nuevo contrato' ?></h3><form method="post" class="grid-3"><input type="hidden" name="csrf" value="<?= ja_h($csrf) ?>"><input type="hidden" name="action" value="save_contract"><input type="hidden" name="record_id" value="<?= (int)($edit['id'] ?? 0) ?>"><label>Empleado<select name="employee_id" required><?php $employeeOptions(isset($edit['employee_id']) ? (int)$edit['employee_id'] : null); ?></select></label><label>Modalidad contractual<select name="contract_type_id" required><option value="">Selecciona una modalidad</option><?php foreach ($contractTypes as $type): ?><?php if (empty($type['active']) && (int)($edit['contract_type_id'] ?? 0) !== (int)$type['id']) continue; ?><option value="<?= (int)$type['id'] ?>" <?= (int)($edit['contract_type_id'] ?? 0) === (int)$type['id'] ? 'selected' : '' ?>><?= ja_h((ContractCatalog::categories()[$type['category']] ?? 'Especial') . ' · ' . $type['name']) ?></option><?php endforeach; ?></select></label><label>Jornada<select name="workday_type"><option value="full_time" <?= ($edit['workday_type'] ?? 'full_time') === 'full_time' ? 'selected' : '' ?>>Tiempo completo</option><option value="part_time" <?= ($edit['workday_type'] ?? '') === 'part_time' ? 'selected' : '' ?>>Tiempo parcial</option></select></label><label>Prestación<select name="work_arrangement"><option value="on_site" <?= ($edit['work_arrangement'] ?? 'on_site') === 'on_site' ? 'selected' : '' ?>>Presencial</option><option value="remote" <?= ($edit['work_arrangement'] ?? '') === 'remote' ? 'selected' : '' ?>>A distancia</option><option value="hybrid" <?= ($edit['work_arrangement'] ?? '') === 'hybrid' ? 'selected' : '' ?>>Híbrida</option></select></label><label>Fecha de firma<input type="date" name="signed_date" value="<?= ja_h($edit['signed_date'] ?? '') ?>"></label><label>Fecha de finalización<input type="date" name="end_date" value="<?= ja_h($edit['end_date'] ?? '') ?>"></label><label>Salario anual<input type="number" min="0" step="0.01" name="annual_salary" value="<?= ja_h($edit['annual_salary'] ?? '') ?>"></label><label class="form-span-full">Notas y causa concreta<textarea name="notes" placeholder="En contratos temporales, documenta aquí la causa y su relación con la duración."><?= ja_h($edit['notes'] ?? '') ?></textarea></label><div class="actions"><button class="button primary" type="submit">Guardar contrato</button><?php if ($edit): ?><a class="button" href="?group=tables&amp;view=contracts">Cancelar</a><?php endif; ?></div></form></article>
        <?php if (isset($tableState)) ja_admin_render_table_controls($tableState); ?><div class="table-wrap"><table><thead><tr><th>Empleado</th><th>Modalidad</th><th>Jornada / prestación</th><th>Vigencia</th><th>Salario anual</th><th>Acciones</th></tr></thead><tbody><?php if (!$rows): ?><tr><td colspan="6"><div class="empty">Todavía no hay contratos.</div></td></tr><?php endif; ?><?php foreach ($rows as $row): ?><tr><td><?= ja_h($row['employee_name']) ?></td><td><strong><?= ja_h($row['contract_type_name']) ?></strong><?php if (empty($row['contract_type_id'])): ?><br><span class="badge warning">Tipo V1 pendiente de clasificar</span><?php endif; ?></td><td><?= ja_h($workdayLabels[$row['workday_type']] ?? $row['workday_type']) ?><br><small><?= ja_h($arrangementLabels[$row['work_arrangement']] ?? $row['work_arrangement']) ?></small></td><td><?= ja_h($row['signed_date'] ?: 'Sin fecha') ?> — <?= ja_h($row['end_date'] ?: 'Sin finalización') ?></td><td><?= ja_h($row['annual_salary']) ?></td><td><div class="actions"><a class="button small" href="?group=tables&amp;view=contracts&amp;record_id=<?= (int)$row['id'] ?>">Editar</a><?php if (!empty($row['is_fixed_discontinuous'])): ?><a class="button small" href="?group=tables&amp;view=call_periods&amp;contract_id=<?= (int)$row['id'] ?>">Llamamientos (<?= (int)$row['call_count'] ?>)</a><?php endif; ?><form method="post"><input type="hidden" name="csrf" value="<?= ja_h($csrf) ?>"><input type="hidden" name="action" value="delete_contract"><input type="hidden" name="record_id" value="<?= (int)$row['id'] ?>"><button class="button danger small" type="submit" data-confirm="¿Eliminar este contrato?">Eliminar</button></form></div></td></tr><?php endforeach; ?></tbody></table></div>
        <?php
        return;
    }

    if ($view === 'call_periods') {
        $edit = ja_admin_fetch_tenant_row($pdo, 'call_periods', $tenantId, $recordId);
        $selectedContractId = (int)($edit['contract_id'] ?? ($_GET['contract_id'] ?? 0));
        $fixedContracts = $pdo->prepare(
            "SELECT c.id,TRIM(e.nombre || ' ' || COALESCE(e.apellido,'')) employee_name,c.signed_date
             FROM contracts c
             JOIN contract_types ct ON ct.id=c.contract_type_id AND ct.tenant_id=c.tenant_id AND ct.is_fixed_discontinuous=1
             JOIN employees e ON e.id=c.employee_id AND e.tenant_id=c.tenant_id
             WHERE c.tenant_id=:tenant ORDER BY e.apellido,e.nombre,c.signed_date DESC"
        );
        $fixedContracts->execute(['tenant' => $tenantId]);
        $fixedContractRows = $fixedContracts->fetchAll();
        $statement = $pdo->prepare(
            "SELECT cp.*,TRIM(e.nombre || ' ' || COALESCE(e.apellido,'')) employee_name,c.signed_date
             FROM call_periods cp
             JOIN contracts c ON c.id=cp.contract_id AND c.tenant_id=cp.tenant_id
             JOIN employees e ON e.id=c.employee_id AND e.tenant_id=c.tenant_id
             WHERE cp.tenant_id=:tenant
             ORDER BY CASE cp.status WHEN 'active' THEN 1 WHEN 'notified' THEN 2 WHEN 'planned' THEN 3 ELSE 4 END,cp.start_date DESC"
        );
        $statement->execute(['tenant' => $tenantId]);
        $rows = $statement->fetchAll();
        $tableState = ja_admin_server_table($rows, 'entity_' . $view);
        $GLOBALS['ja_admin_table_state_' . $view] = $tableState;
        $rows = $tableState['rows'];
        $statuses = ['planned' => 'Previsto', 'notified' => 'Notificado', 'active' => 'En actividad', 'completed' => 'Finalizado', 'cancelled' => 'Cancelado'];
        ?>
        <article class="card">
          <div class="ju-table-caption"><div><h3><?= $edit ? 'Editar llamamiento' : 'Nuevo llamamiento' ?></h3><p>Solo aparecen contratos fijo-discontinuos. Los periodos activos del mismo contrato no pueden solaparse.</p></div><span class="badge ok"><?= count($fixedContractRows) ?> contratos disponibles</span></div>
          <?php if (!$fixedContractRows): ?><div class="empty">Primero crea o clasifica un contrato como fijo-discontinuo.</div><?php else: ?><form method="post" class="grid-3"><input type="hidden" name="csrf" value="<?= ja_h($csrf) ?>"><input type="hidden" name="action" value="save_call_period"><input type="hidden" name="record_id" value="<?= (int)($edit['id'] ?? 0) ?>"><label>Contrato<select name="contract_id" required><?php foreach ($fixedContractRows as $contract): ?><option value="<?= (int)$contract['id'] ?>" <?= $selectedContractId === (int)$contract['id'] ? 'selected' : '' ?>><?= ja_h($contract['employee_name'] . ' · contrato #' . $contract['id'] . ' · ' . ($contract['signed_date'] ?: 'sin fecha')) ?></option><?php endforeach; ?></select></label><label>Inicio del periodo<input type="date" name="start_date" value="<?= ja_h($edit['start_date'] ?? '') ?>" required></label><label>Fin del periodo<input type="date" name="end_date" value="<?= ja_h($edit['end_date'] ?? '') ?>" required></label><label>Estado<select name="status"><?php foreach ($statuses as $key => $label): ?><option value="<?= ja_h($key) ?>" <?= ($edit['status'] ?? 'planned') === $key ? 'selected' : '' ?>><?= ja_h($label) ?></option><?php endforeach; ?></select></label><label>Fecha de aviso<input type="date" name="notice_date" value="<?= ja_h($edit['notice_date'] ?? '') ?>"></label><label>Medio de notificación<input name="notification_method" value="<?= ja_h($edit['notification_method'] ?? '') ?>" placeholder="Portal, correo, burofax..."></label><label class="form-span-full">Condiciones y notas<textarea name="notes" placeholder="Horario estimado, centro, condiciones de incorporación..."><?= ja_h($edit['notes'] ?? '') ?></textarea></label><div class="actions"><button class="button primary" type="submit">Guardar llamamiento</button><?php if ($edit): ?><a class="button" href="?group=tables&amp;view=call_periods">Cancelar</a><?php endif; ?></div></form><?php endif; ?>
        </article>
        <?php if (isset($tableState)) ja_admin_render_table_controls($tableState); ?><div class="table-wrap"><table><thead><tr><th>Persona / contrato</th><th>Periodo</th><th>Aviso</th><th>Estado</th><th>Condiciones</th><th>Acciones</th></tr></thead><tbody><?php if (!$rows): ?><tr><td colspan="6"><div class="empty">Todavía no hay llamamientos.</div></td></tr><?php endif; ?><?php foreach ($rows as $row): $statusClass = $row['status'] === 'cancelled' ? 'danger' : (in_array($row['status'], ['active', 'completed'], true) ? 'ok' : 'warning'); ?><tr><td><strong><?= ja_h($row['employee_name']) ?></strong><br><small>Contrato #<?= (int)$row['contract_id'] ?></small></td><td><?= ja_h($row['start_date']) ?> — <?= ja_h($row['end_date']) ?></td><td><?= ja_h($row['notice_date'] ?: 'No notificado') ?><br><small><?= ja_h($row['notification_method'] ?? '') ?></small></td><td><span class="badge <?= $statusClass ?>"><?= ja_h($statuses[$row['status']] ?? $row['status']) ?></span></td><td class="wrap"><?= ja_h($row['notes'] ?? '') ?></td><td><div class="actions"><a class="button small" href="?group=tables&amp;view=call_periods&amp;record_id=<?= (int)$row['id'] ?>">Editar</a><?php if ($row['status'] === 'planned'): ?><form method="post"><input type="hidden" name="csrf" value="<?= ja_h($csrf) ?>"><input type="hidden" name="action" value="delete_call_period"><input type="hidden" name="record_id" value="<?= (int)$row['id'] ?>"><button class="button danger small" type="submit" data-confirm="¿Eliminar esta previsión no notificada?">Eliminar</button></form><?php endif; ?></div></td></tr><?php endforeach; ?></tbody></table></div>
        <?php
        return;
    }

    if ($view === 'holidays') {
        $edit = ja_admin_fetch_tenant_row($pdo, 'holidays', $tenantId, $recordId);
        $statement = $pdo->prepare('SELECT * FROM holidays WHERE tenant_id=:tenant ORDER BY holiday_date DESC');
        $statement->execute(['tenant' => $tenantId]);
        $rows = $statement->fetchAll();
        $tableState = ja_admin_server_table($rows, 'entity_' . $view);
        $GLOBALS['ja_admin_table_state_' . $view] = $tableState;
        $rows = $tableState['rows'];
        ?>
        <article class="card"><h3><?= $edit ? 'Editar festivo' : 'Nuevo festivo' ?></h3><form method="post" class="grid-2"><input type="hidden" name="csrf" value="<?= ja_h($csrf) ?>"><input type="hidden" name="action" value="save_holiday"><input type="hidden" name="record_id" value="<?= (int)($edit['id'] ?? 0) ?>"><label>Fecha<input type="date" name="holiday_date" value="<?= ja_h($edit['holiday_date'] ?? '') ?>" required></label><label>Nombre<input name="name" value="<?= ja_h($edit['name'] ?? '') ?>" required></label><label class="form-span-full">Descripción<textarea name="description"><?= ja_h($edit['description'] ?? '') ?></textarea></label><div class="actions"><button class="button primary" type="submit">Guardar festivo</button><?php if ($edit): ?><a class="button" href="?group=tables&amp;view=holidays">Cancelar</a><?php endif; ?></div></form></article>
        <?php ja_admin_render_simple_rows($rows, ['holiday_date' => 'Fecha', 'name' => 'Nombre', 'description' => 'Descripción'], 'holidays', 'delete_holiday', $csrf, ['description']); ?>
        <?php
        return;
    }

    if ($view === 'incidents') {
        $edit = ja_admin_fetch_tenant_row($pdo, 'incidents', $tenantId, $recordId);
        $statement = $pdo->prepare("SELECT i.*,TRIM(e.nombre || ' ' || COALESCE(e.apellido,'')) employee_name FROM incidents i JOIN employees e ON e.id=i.employee_id AND e.tenant_id=i.tenant_id WHERE i.tenant_id=:tenant ORDER BY i.incident_date DESC,i.id DESC");
        $statement->execute(['tenant' => $tenantId]);
        $rows = $statement->fetchAll();
        $tableState = ja_admin_server_table($rows, 'entity_' . $view);
        $GLOBALS['ja_admin_table_state_' . $view] = $tableState;
        $rows = $tableState['rows'];
        ?>
        <article class="card"><h3><?= $edit ? 'Editar incidencia' : 'Nueva incidencia' ?></h3><form method="post" class="grid-2"><input type="hidden" name="csrf" value="<?= ja_h($csrf) ?>"><input type="hidden" name="action" value="save_incident"><input type="hidden" name="record_id" value="<?= (int)($edit['id'] ?? 0) ?>"><label>Empleado<select name="employee_id" required><?php $employeeOptions(isset($edit['employee_id']) ? (int)$edit['employee_id'] : null); ?></select></label><label>Tipo<input name="type" value="<?= ja_h($edit['type'] ?? '') ?>" required></label><label>Fecha<input type="date" name="incident_date" value="<?= ja_h($edit['incident_date'] ?? date('Y-m-d')) ?>" required></label><label>Notas<textarea name="notes"><?= ja_h($edit['notes'] ?? '') ?></textarea></label><div class="actions"><button class="button primary" type="submit">Guardar incidencia</button><?php if ($edit): ?><a class="button" href="?group=tables&amp;view=incidents">Cancelar</a><?php endif; ?></div></form></article>
        <?php ja_admin_render_simple_rows($rows, ['employee_name' => 'Empleado', 'incident_date' => 'Fecha', 'type' => 'Tipo', 'notes' => 'Notas'], 'incidents', 'delete_incident', $csrf, ['notes']); ?>
        <?php
        return;
    }

    if ($view === 'vacations') {
        $edit = ja_admin_fetch_tenant_row($pdo, 'vacations', $tenantId, $recordId);
        $statement = $pdo->prepare("SELECT v.*,TRIM(e.nombre || ' ' || COALESCE(e.apellido,'')) employee_name FROM vacations v JOIN employees e ON e.id=v.employee_id AND e.tenant_id=v.tenant_id WHERE v.tenant_id=:tenant ORDER BY v.start_date DESC,v.id DESC");
        $statement->execute(['tenant' => $tenantId]);
        $rows = $statement->fetchAll();
        $tableState = ja_admin_server_table($rows, 'entity_' . $view);
        $GLOBALS['ja_admin_table_state_' . $view] = $tableState;
        $rows = $tableState['rows'];
        ?>
        <article class="card"><h3><?= $edit ? 'Editar vacaciones' : 'Nuevo periodo de vacaciones' ?></h3><form method="post" class="grid-2"><input type="hidden" name="csrf" value="<?= ja_h($csrf) ?>"><input type="hidden" name="action" value="save_vacation"><input type="hidden" name="record_id" value="<?= (int)($edit['id'] ?? 0) ?>"><label>Empleado<select name="employee_id" required><?php $employeeOptions(isset($edit['employee_id']) ? (int)$edit['employee_id'] : null); ?></select></label><label>Fecha de inicio<input type="date" name="start_date" value="<?= ja_h($edit['start_date'] ?? '') ?>" required></label><label>Fecha de fin<input type="date" name="end_date" value="<?= ja_h($edit['end_date'] ?? '') ?>" required></label><label class="check"><input type="checkbox" name="approved" value="1" <?= !empty($edit['approved']) ? 'checked' : '' ?>> Aprobadas</label><div class="actions"><button class="button primary" type="submit">Guardar vacaciones</button><?php if ($edit): ?><a class="button" href="?group=tables&amp;view=vacations">Cancelar</a><?php endif; ?></div></form></article>
        <?php ja_admin_render_simple_rows($rows, ['employee_name' => 'Empleado', 'start_date' => 'Inicio', 'end_date' => 'Fin', 'approved' => 'Aprobadas'], 'vacations', 'delete_vacation', $csrf, [], ['approved']); ?>
        <?php
    }
}

function ja_admin_fetch_tenant_row(PDO $pdo, string $table, int $tenantId, int $id): ?array
{
    $allowed = ['tenant_admins', 'attendance_types', 'work_centers', 'contract_types', 'contracts', 'call_periods', 'holidays', 'incidents', 'vacations'];
    if ($id < 1 || !in_array($table, $allowed, true)) {
        return null;
    }
    $statement = $pdo->prepare('SELECT * FROM "' . $table . '" WHERE id=:id AND tenant_id=:tenant');
    $statement->execute(['id' => $id, 'tenant' => $tenantId]);
    return $statement->fetch() ?: null;
}

/**
 * @param array<int,array<string,mixed>> $rows
 * @param array<string,string> $columns
 * @param array<int,string> $wrapColumns
 * @param array<int,string> $booleanColumns
 */
function ja_admin_render_simple_rows(
    array $rows,
    array $columns,
    string $view,
    string $deleteAction,
    string $csrf,
    array $wrapColumns = [],
    array $booleanColumns = []
): void {
    ?>
    <?php $simpleState = $GLOBALS['ja_admin_table_state_' . $view] ?? null; if (is_array($simpleState)) ja_admin_render_table_controls($simpleState); ?><div class="table-wrap"><table><thead><tr><?php foreach ($columns as $label): ?><th><?= ja_h($label) ?></th><?php endforeach; ?><th>Acciones</th></tr></thead><tbody>
    <?php if (!$rows): ?><tr><td colspan="<?= count($columns) + 1 ?>"><div class="empty">Todavía no hay registros.</div></td></tr><?php endif; ?>
    <?php foreach ($rows as $row): ?><tr><?php foreach ($columns as $column => $label): ?><td class="<?= in_array($column, $wrapColumns, true) ? 'wrap' : '' ?>"><?php if (in_array($column, $booleanColumns, true)): ?><span class="badge <?= !empty($row[$column]) ? 'ok' : 'warning' ?>"><?= !empty($row[$column]) ? 'Sí' : 'No' ?></span><?php else: ?><?= ja_h($row[$column] ?? '') ?><?php endif; ?></td><?php endforeach; ?><td><div class="actions"><a class="button small" href="?group=tables&amp;view=<?= ja_h($view) ?>&amp;record_id=<?= (int)$row['id'] ?>">Editar</a><form method="post"><input type="hidden" name="csrf" value="<?= ja_h($csrf) ?>"><input type="hidden" name="action" value="<?= ja_h($deleteAction) ?>"><input type="hidden" name="record_id" value="<?= (int)$row['id'] ?>"><button class="button danger small" type="submit" data-confirm="¿Eliminar este registro?">Eliminar</button></form></div></td></tr><?php endforeach; ?>
    </tbody></table></div>
    <?php
}



```

### admin/estadisticas_empleados.php

```php
<?php
declare(strict_types=1);

if (!defined('JA_ADMIN_APPLICATION')) {
    http_response_code(404);
    exit;
}

$department = trim((string)($_GET['department'] ?? ''));
$role = trim((string)($_GET['role'] ?? ''));
$dateFrom = trim((string)($_GET['from'] ?? ''));
$dateTo = trim((string)($_GET['to'] ?? ''));
if (!ja_admin_valid_date($dateFrom)) {
    $dateFrom = '';
}
if (!ja_admin_valid_date($dateTo)) {
    $dateTo = '';
}

$employeeSql = "SELECT e.id,e.nombre,e.apellido,e.departamento,e.role,e.work_center_id,e.contract_type_id,
                       c.name AS center_name,ct.name AS contract_name,
                       COUNT(a.id) AS records,COUNT(DISTINCT a.work_date) AS days,
                       MAX(a.work_date) AS last_date,
                       SUM(CASE WHEN a.start_time IS NOT NULL AND a.end_time IS NULL THEN 1 ELSE 0 END) AS open_records
                FROM employees e
                LEFT JOIN work_centers c ON c.id=e.work_center_id AND c.tenant_id=e.tenant_id
                LEFT JOIN contract_types ct ON ct.id=e.contract_type_id AND ct.tenant_id=e.tenant_id
                LEFT JOIN attendance a ON a.employee_id=e.id AND a.tenant_id=e.tenant_id
                WHERE e.tenant_id=:tenant";
$employeeParams = ['tenant' => $tenantId];
if ($department !== '') {
    $employeeSql .= ' AND e.departamento=:department';
    $employeeParams['department'] = $department;
}
if ($role !== '') {
    $employeeSql .= ' AND e.role=:role';
    $employeeParams['role'] = $role;
}
$employeeSql .= ' GROUP BY e.id ORDER BY e.apellido,e.nombre';
$statement = $pdo->prepare($employeeSql);
$statement->execute($employeeParams);
$filteredEmployees = $statement->fetchAll();

$selectedId = (int)($_GET['employee_id'] ?? 0);
$selected = null;
foreach ($filteredEmployees as $candidate) {
    if ((int)$candidate['id'] === $selectedId) {
        $selected = $candidate;
        break;
    }
}
if (!$selected && $filteredEmployees) {
    $selected = $filteredEmployees[0];
    $selectedId = (int)$selected['id'];
}

$departmentsStatement = $pdo->prepare("SELECT DISTINCT departamento FROM employees WHERE tenant_id=:tenant AND departamento IS NOT NULL AND departamento<>'' ORDER BY departamento");
$departmentsStatement->execute(['tenant' => $tenantId]);
$departmentOptions = $departmentsStatement->fetchAll();
$rolesStatement = $pdo->prepare("SELECT DISTINCT role FROM employees WHERE tenant_id=:tenant AND role IS NOT NULL AND role<>'' ORDER BY role");
$rolesStatement->execute(['tenant' => $tenantId]);
$roleOptions = $rolesStatement->fetchAll();

$stats = [];
$employeeAttendance = [];
$monthly = [];
$weekdayCounts = array_fill(1, 7, 0);
$arrivalBuckets = [];
$employeeIncidents = [];
$employeeAbsences = [];
$employeeVacations = [];
$scheduleRows = [];
$totalMinutes = 0;
$durations = [];
$daysWorked = [];
$startMinutes = [];
$endMinutes = [];
$closedRecords = 0;
$openRecords = 0;
$missingStart = 0;
$lateCount = 0;
$earlyCount = 0;
$scheduledMinutes = 0;
$workedScheduledDays = 0;

if ($selected) {
    $attendanceSql = 'SELECT a.*,ft.name AS attendance_type_name FROM attendance a LEFT JOIN attendance_types ft ON ft.id=a.attendance_type_id AND ft.tenant_id=a.tenant_id WHERE a.tenant_id=:tenant AND a.employee_id=:employee';
    $attendanceParams = ['tenant' => $tenantId, 'employee' => $selectedId];
    if ($dateFrom !== '') {
        $attendanceSql .= ' AND a.work_date>=:date_from';
        $attendanceParams['date_from'] = $dateFrom;
    }
    if ($dateTo !== '') {
        $attendanceSql .= ' AND a.work_date<=:date_to';
        $attendanceParams['date_to'] = $dateTo;
    }
    $attendanceSql .= ' ORDER BY a.work_date DESC,a.start_time DESC,a.id DESC';
    $statement = $pdo->prepare($attendanceSql);
    $statement->execute($attendanceParams);
    $employeeAttendance = $statement->fetchAll();

    $attendanceByDate = [];
    foreach ($employeeAttendance as &$record) {
        $duration = ja_admin_duration_minutes($record['start_time'], $record['end_time']);
        $record['duration_minutes'] = $duration;
        $weekday = (int)(new DateTimeImmutable($record['work_date']))->format('N');
        $record['weekday'] = $weekday;
        $weekdayCounts[$weekday]++;
        $daysWorked[$record['work_date']] = true;
        $attendanceByDate[$record['work_date']][] = $record;
        $month = substr((string)$record['work_date'], 0, 7);
        $monthly[$month] ??= ['month' => $month, 'minutes' => 0, 'days' => []];
        $monthly[$month]['days'][$record['work_date']] = true;
        if ($record['start_time']) {
            $minutes = ja_admin_time_minutes($record['start_time']);
            if ($minutes !== null) {
                $startMinutes[] = $minutes;
                $hour = intdiv($minutes, 60);
                $arrivalBuckets[$hour] = ($arrivalBuckets[$hour] ?? 0) + 1;
            }
        } else {
            $missingStart++;
        }
        if ($record['end_time']) {
            $minutes = ja_admin_time_minutes($record['end_time']);
            if ($minutes !== null) {
                $endMinutes[] = $minutes;
            }
        }
        if ($duration !== null) {
            $durations[] = $duration;
            $totalMinutes += $duration;
            $closedRecords++;
            $monthly[$month]['minutes'] += $duration;
        } elseif ($record['start_time'] && !$record['end_time']) {
            $openRecords++;
        }
    }
    unset($record);
    ksort($monthly);
    $monthly = array_slice(array_values($monthly), -12);
    ksort($arrivalBuckets);

    $statement = $pdo->prepare('SELECT * FROM schedules WHERE tenant_id=:tenant AND employee_id=:employee ORDER BY weekday,start_time');
    $statement->execute(['tenant' => $tenantId, 'employee' => $selectedId]);
    $scheduleRows = $statement->fetchAll();
    foreach ($attendanceByDate as $date => $dayRecords) {
        $dayNumber = (int)(new DateTimeImmutable($date))->format('N');
        $applicable = array_values(array_filter($scheduleRows, static function (array $schedule) use ($date, $dayNumber): bool {
            return (int)$schedule['weekday'] === $dayNumber
                && (!$schedule['valid_from'] || $schedule['valid_from'] <= $date)
                && (!$schedule['valid_until'] || $schedule['valid_until'] >= $date);
        }));
        if (!$applicable) {
            continue;
        }
        $actualStarts = array_values(array_filter(array_map(static fn(array $row): ?int => ja_admin_time_minutes($row['start_time']), $dayRecords), static fn(?int $v): bool => $v !== null));
        $actualEnds = array_values(array_filter(array_map(static fn(array $row): ?int => ja_admin_time_minutes($row['end_time']), $dayRecords), static fn(?int $v): bool => $v !== null));
        if (!$actualStarts || !$actualEnds) {
            continue;
        }
        $workedScheduledDays++;
        $scheduledStarts = [];
        $scheduledEnds = [];
        foreach ($applicable as $schedule) {
            $duration = ja_admin_duration_minutes($schedule['start_time'], $schedule['end_time']);
            if ($duration !== null) {
                $scheduledMinutes += $duration;
            }
            $scheduledStarts[] = ja_admin_time_minutes($schedule['start_time']) ?? 0;
            $scheduledEnds[] = ja_admin_time_minutes($schedule['end_time']) ?? 0;
        }
        if (min($actualStarts) > min($scheduledStarts) + 5) {
            $lateCount++;
        }
        if (max($actualEnds) < max($scheduledEnds) - 5) {
            $earlyCount++;
        }
    }

    $statement = $pdo->prepare('SELECT * FROM incidents WHERE tenant_id=:tenant AND employee_id=:employee ORDER BY incident_date DESC LIMIT 100');
    $statement->execute(['tenant' => $tenantId, 'employee' => $selectedId]);
    $employeeIncidents = $statement->fetchAll();
    $statement = $pdo->prepare('SELECT * FROM absences WHERE tenant_id=:tenant AND employee_id=:employee ORDER BY start_date DESC LIMIT 100');
    $statement->execute(['tenant' => $tenantId, 'employee' => $selectedId]);
    $employeeAbsences = $statement->fetchAll();
    $statement = $pdo->prepare('SELECT * FROM vacations WHERE tenant_id=:tenant AND employee_id=:employee ORDER BY start_date DESC LIMIT 100');
    $statement->execute(['tenant' => $tenantId, 'employee' => $selectedId]);
    $employeeVacations = $statement->fetchAll();

    sort($durations);
    $durationCount = count($durations);
    $median = $durationCount ? ($durationCount % 2 ? $durations[intdiv($durationCount, 2)] : ($durations[$durationCount / 2 - 1] + $durations[$durationCount / 2]) / 2) : null;
    $dates = array_keys($daysWorked);
    sort($dates);
    $stats = [
        'first_date' => $dates[0] ?? null,
        'last_date' => $dates ? $dates[count($dates) - 1] : null,
        'average' => $durationCount ? $totalMinutes / $durationCount : null,
        'median' => $median,
        'minimum' => $durations[0] ?? null,
        'maximum' => $durations ? $durations[count($durations) - 1] : null,
        'average_start' => $startMinutes ? array_sum($startMinutes) / count($startMinutes) : null,
        'average_end' => $endMinutes ? array_sum($endMinutes) / count($endMinutes) : null,
        'earliest_start' => $startMinutes ? min($startMinutes) : null,
        'latest_start' => $startMinutes ? max($startMinutes) : null,
    ];
}

$totalRecords = count($employeeAttendance);
$closure = $totalRecords ? round(($closedRecords / $totalRecords) * 100, 1) : null;
$punctuality = $workedScheduledDays ? max(0, round((1 - ($lateCount / $workedScheduledDays)) * 100, 1)) : null;
$balance = $scheduledMinutes ? $totalMinutes - $scheduledMinutes : null;
$maxMonthly = max(1, ...array_map(static fn(array $row): int => (int)$row['minutes'], $monthly ?: [['minutes' => 1]]));
$maxWeekday = max(1, ...$weekdayCounts);
$maxArrival = max(1, ...array_values($arrivalBuckets ?: [1]));
$timeFromMinutes = static function (int|float|null $minutes): string {
    if ($minutes === null) {
        return '—';
    }
    $minutes = (int)round($minutes);
    return sprintf('%02d:%02d', intdiv($minutes, 60) % 24, $minutes % 60);
};
?>
<section class="employee-statistics">
  <aside class="statistics-sidebar">
    <article class="statistics-filter">
      <h3>📊 Estadísticas</h3><p>Filtra y selecciona un empleado para ver su analítica completa de asistencia.</p>
      <form method="get">
        <input type="hidden" name="group" value="applications"><input type="hidden" name="app" value="estadisticas_empleados">
        <label>Departamento<select name="department"><option value="">Todos</option><?php foreach ($departmentOptions as $option): ?><option value="<?= ja_h($option['departamento']) ?>" <?= $department === $option['departamento'] ? 'selected' : '' ?>><?= ja_h($option['departamento']) ?></option><?php endforeach; ?></select></label>
        <label>Rol<select name="role"><option value="">Todos</option><?php foreach ($roleOptions as $option): ?><option value="<?= ja_h($option['role']) ?>" <?= $role === $option['role'] ? 'selected' : '' ?>><?= ja_h($option['role']) ?></option><?php endforeach; ?></select></label>
        <label>Desde<input type="date" name="from" value="<?= ja_h($dateFrom) ?>"></label><label>Hasta<input type="date" name="to" value="<?= ja_h($dateTo) ?>"></label>
        <?php if ($selectedId): ?><input type="hidden" name="employee_id" value="<?= $selectedId ?>"><?php endif; ?>
        <button class="button primary" type="submit">Aplicar</button><a class="button" href="?group=applications&app=estadisticas_empleados">Limpiar</a>
      </form>
    </article>
    <div class="statistics-employee-list">
      <?php if (!$filteredEmployees): ?><div class="empty">No hay empleados con esos filtros.</div><?php endif; ?>
      <?php foreach ($filteredEmployees as $employee): $url = ja_admin_query_url(['group' => 'applications', 'app' => 'estadisticas_empleados', 'employee_id' => $employee['id'], 'department' => $department, 'role' => $role, 'from' => $dateFrom, 'to' => $dateTo]); ?>
        <a class="statistics-employee <?= (int)$employee['id'] === $selectedId ? 'active' : '' ?>" href="<?= ja_h($url) ?>"><strong><?= ja_h(ja_admin_employee_name($employee)) ?></strong><span><?= ja_h($employee['departamento'] ?: 'Sin departamento') ?> · <?= ja_h($employee['role'] ?: 'Sin rol') ?></span><small><?= (int)$employee['days'] ?> días · <?= (int)$employee['records'] ?> registros · <?= (int)$employee['open_records'] > 0 ? 'abierta' : 'normal' ?></small></a>
      <?php endforeach; ?>
    </div>
  </aside>

  <div class="statistics-main">
    <?php if (!$selected): ?><div class="empty">Selecciona un empleado para cargar sus estadísticas.</div><?php else: ?>
      <section class="statistics-hero">
        <div><span class="hero-label">Empleado seleccionado</span><h2><?= ja_h(ja_admin_employee_name($selected)) ?></h2><p><?= ja_h($selected['departamento'] ?: 'Sin departamento') ?> · <?= ja_h($selected['role'] ?: 'Sin rol') ?> · <?= ja_h($selected['center_name'] ?: 'Sin centro') ?></p></div>
        <div class="statistics-hero-meta"><div><strong><?= ja_h($stats['first_date'] ?: '—') ?></strong><span>Primera asistencia</span></div><div><strong><?= ja_h($stats['last_date'] ?: '—') ?></strong><span>Última asistencia</span></div><div><strong><?= $closure === null ? '—' : ja_h((string)$closure . '%') ?></strong><span>Cierre de registros</span></div><div><strong><?= $punctuality === null ? '—' : ja_h((string)$punctuality . '%') ?></strong><span>Puntualidad estimada</span></div></div>
      </section>

      <section class="statistics-kpis">
      <?php $kpis = [
          ['Días trabajados', count($daysWorked), 'Días únicos con registro'],
          ['Horas totales', ja_admin_minutes_label($totalMinutes), 'Suma de registros cerrados'],
          ['Media diaria', $daysWorked ? ja_admin_minutes_label($totalMinutes / count($daysWorked)) : '—', 'Promedio por día trabajado'],
          ['Media por registro', ja_admin_minutes_label($stats['average'] ?? null), 'Promedio de fichajes cerrados'],
          ['Mediana', ja_admin_minutes_label($stats['median'] ?? null), 'Duración central'],
          ['Jornada máxima', ja_admin_minutes_label($stats['maximum'] ?? null), 'Registro cerrado más largo'],
          ['Jornada mínima', ja_admin_minutes_label($stats['minimum'] ?? null), 'Registro cerrado más corto'],
          ['Registros abiertos', $openRecords, 'Sin hora de salida'],
          ['Entrada media', $timeFromMinutes($stats['average_start'] ?? null), 'Hora media de entrada'],
          ['Salida media', $timeFromMinutes($stats['average_end'] ?? null), 'Hora media de salida'],
          ['Más temprana', $timeFromMinutes($stats['earliest_start'] ?? null), 'Entrada más temprana'],
          ['Más tardía', $timeFromMinutes($stats['latest_start'] ?? null), 'Entrada más tardía'],
          ['Incidencias', count($employeeIncidents), 'Eventos registrados'],
          ['Ausencias', count($employeeAbsences), 'Solicitudes o ausencias'],
          ['Vacaciones', count($employeeVacations), 'Periodos registrados'],
          ['Saldo horario', ja_admin_minutes_label($balance), 'Trabajado vs horario teórico'],
      ]; foreach ($kpis as $kpi): ?><article class="statistics-kpi"><small><?= ja_h($kpi[0]) ?></small><strong><?= ja_h($kpi[1]) ?></strong><span><?= ja_h($kpi[2]) ?></span></article><?php endforeach; ?>
      </section>

      <section class="statistics-two">
        <article class="card"><div class="ju-table-caption"><div><h3>Evolución mensual</h3><p>Horas y días por mes.</p></div><span class="ju-pill">12 meses</span></div><div class="statistics-bars"><?php if (!$monthly): ?><p>No hay datos mensuales.</p><?php endif; ?><?php foreach ($monthly as $row): ?><div class="statistics-bar"><span><?= ja_h($row['month']) ?></span><div><i style="width:<?= (int)round(((int)$row['minutes'] / $maxMonthly) * 100) ?>%"></i></div><strong><?= ja_h(ja_admin_minutes_label((int)$row['minutes'])) ?></strong></div><?php endforeach; ?></div></article>
        <article class="card"><div class="ju-table-caption"><div><h3>Distribución semanal</h3><p>Registros por día.</p></div><span class="ju-pill">Semana</span></div><div class="statistics-bars"><?php foreach ($weekdays as $number => $day): ?><div class="statistics-bar"><span><?= ja_h($day) ?></span><div><i style="width:<?= (int)round(($weekdayCounts[$number] / $maxWeekday) * 100) ?>%"></i></div><strong><?= $weekdayCounts[$number] ?></strong></div><?php endforeach; ?></div></article>
      </section>

      <section class="statistics-two">
        <article class="card"><div class="ju-table-caption"><div><h3>Histograma de entradas</h3><p>Cantidad de entradas por hora.</p></div><span class="ju-pill">Horas</span></div><div class="statistics-bars"><?php if (!$arrivalBuckets): ?><p>No hay horas de entrada.</p><?php endif; ?><?php foreach ($arrivalBuckets as $hour => $count): ?><div class="statistics-bar"><span><?= sprintf('%02d:00', $hour) ?></span><div><i style="width:<?= (int)round(($count / $maxArrival) * 100) ?>%"></i></div><strong><?= $count ?></strong></div><?php endforeach; ?></div></article>
        <article class="card"><div class="ju-table-caption"><div><h3>Cumplimiento horario</h3><p>Comparación estimada con el horario.</p></div><span class="ju-pill">Estimado</span></div><dl class="statistics-summary"><div><dt>Días comparables</dt><dd><?= $workedScheduledDays ?></dd></div><div><dt>Llegadas tarde</dt><dd><?= $lateCount ?></dd></div><div><dt>Salidas anticipadas</dt><dd><?= $earlyCount ?></dd></div><div><dt>Horas teóricas</dt><dd><?= ja_h(ja_admin_minutes_label($scheduledMinutes ?: null)) ?></dd></div><div><dt>Horas reales</dt><dd><?= ja_h(ja_admin_minutes_label($totalMinutes)) ?></dd></div><div><dt>Saldo</dt><dd><?= ja_h(ja_admin_minutes_label($balance)) ?></dd></div></dl></article>
      </section>

      <article class="card"><div class="ju-table-caption"><div><h3>Últimos registros de asistencia</h3><p>Hasta 240 registros del periodo seleccionado.</p></div><a class="button small" href="?group=tables&view=attendance">Abrir tabla</a></div><div class="statistics-table-wrap"><table><thead><tr><th>Fecha</th><th>Día</th><th>Tipo</th><th>Entrada</th><th>Salida</th><th>Duración</th><th>Estado</th></tr></thead><tbody><?php if (!$employeeAttendance): ?><tr><td colspan="7">No hay registros.</td></tr><?php endif; ?><?php foreach (array_slice($employeeAttendance, 0, 240) as $record): $state = !$record['start_time'] ? 'Sin entrada' : (!$record['end_time'] ? 'Abierto' : 'Cerrado'); ?><tr><td><?= ja_h($record['work_date']) ?></td><td><?= ja_h($weekdays[$record['weekday']]) ?></td><td><?= ja_h($record['attendance_type_name'] ?: 'Sin tipo') ?></td><td><?= ja_h(ja_admin_short_time($record['start_time'])) ?></td><td><?= ja_h(ja_admin_short_time($record['end_time'])) ?></td><td><?= ja_h(ja_admin_minutes_label($record['duration_minutes'])) ?></td><td><span class="badge <?= $state === 'Cerrado' ? 'ok' : 'warning' ?>"><?= ja_h($state) ?></span></td></tr><?php endforeach; ?></tbody></table></div></article>

      <section class="statistics-two"><article class="card"><h3>Incidencias</h3><div class="statistics-table-wrap"><table><thead><tr><th>Fecha</th><th>Tipo</th><th>Notas</th></tr></thead><tbody><?php if (!$employeeIncidents): ?><tr><td colspan="3">Sin incidencias.</td></tr><?php endif; ?><?php foreach ($employeeIncidents as $incident): ?><tr><td><?= ja_h($incident['incident_date']) ?></td><td><?= ja_h($incident['type']) ?></td><td class="wrap"><?= ja_h($incident['notes']) ?></td></tr><?php endforeach; ?></tbody></table></div></article><article class="card"><h3>Ausencias y vacaciones</h3><div class="statistics-table-wrap"><table><thead><tr><th>Tipo</th><th>Inicio</th><th>Fin</th><th>Estado</th></tr></thead><tbody><?php if (!$employeeAbsences && !$employeeVacations): ?><tr><td colspan="4">Sin periodos.</td></tr><?php endif; ?><?php foreach ($employeeAbsences as $absence): ?><tr><td><?= ja_h($absence['absence_type']) ?></td><td><?= ja_h($absence['start_date']) ?></td><td><?= ja_h($absence['end_date'] ?: 'Sin fecha final') ?></td><td><?= ja_h($absence['status']) ?></td></tr><?php endforeach; ?><?php foreach ($employeeVacations as $vacation): ?><tr><td>Vacaciones</td><td><?= ja_h($vacation['start_date']) ?></td><td><?= ja_h($vacation['end_date']) ?></td><td><?= $vacation['approved'] ? 'Aprobado' : 'Pendiente' ?></td></tr><?php endforeach; ?></tbody></table></div></article></section>
    <?php endif; ?>
  </div>
</section>

```

### admin/functions.php

```php
<?php
declare(strict_types=1);

function ja_admin_employee_name(array $employee): string
{
    $name = trim((string)($employee['nombre'] ?? '') . ' ' . (string)($employee['apellido'] ?? ''));
    return $name !== '' ? $name : 'Empleado #' . (string)($employee['id'] ?? '');
}

function ja_admin_valid_date(?string $value): bool
{
    if ($value === null || $value === '') {
        return true;
    }
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    return $date instanceof DateTimeImmutable && $date->format('Y-m-d') === $value;
}

function ja_admin_time_minutes(?string $value): ?int
{
    if ($value === null || !preg_match('/^(\d{1,2}):(\d{2})/', $value, $parts)) {
        return null;
    }
    $hours = (int)$parts[1];
    $minutes = (int)$parts[2];
    if ($hours > 24 || $minutes > 59 || ($hours === 24 && $minutes !== 0)) {
        return null;
    }
    return ($hours * 60) + $minutes;
}

function ja_admin_duration_minutes(?string $start, ?string $end): ?int
{
    $startMinutes = ja_admin_time_minutes($start);
    $endMinutes = ja_admin_time_minutes($end);
    if ($startMinutes === null || $endMinutes === null) {
        return null;
    }
    if ($endMinutes < $startMinutes) {
        $endMinutes += 1440;
    }
    return $endMinutes - $startMinutes;
}

function ja_admin_minutes_label(int|float|null $minutes): string
{
    if ($minutes === null) {
        return '—';
    }
    $rounded = (int)round($minutes);
    $sign = $rounded < 0 ? '-' : '';
    $rounded = abs($rounded);
    return sprintf('%s%02dh %02dm', $sign, intdiv($rounded, 60), $rounded % 60);
}

function ja_admin_short_time(?string $time): string
{
    return $time ? substr($time, 0, 5) : '—';
}

function ja_admin_minutes_time(int $minutes): string
{
    if ($minutes === 1440) {
        return '24:00:00';
    }
    return sprintf('%02d:%02d:00', intdiv($minutes, 60), $minutes % 60);
}

function ja_admin_query_url(array $values): string
{
    return 'index.php?' . http_build_query(array_filter(
        $values,
        static fn(mixed $value): bool => $value !== null && $value !== ''
    ));
}




/**
 * Server-side search and pagination for admin tables.
 * Search is applied to the complete result set on the server, never only to
 * the rows currently visible in the browser.
 *
 * @return array{key:string,rows:array,total:int,filtered:int,page:int,pages:int,per_page:int,q:string}
 */
function ja_admin_server_table(array $rows, string $key, int $defaultPerPage = 25): array
{
    $key = preg_replace('/[^a-z0-9_]/i', '_', $key) ?: 'table';
    $q = trim((string)($_GET[$key . '_q'] ?? ''));
    $allowed = [10, 25, 50, 100, 250];
    $perPage = (int)($_GET[$key . '_per_page'] ?? $defaultPerPage);
    if (!in_array($perPage, $allowed, true)) {
        $perPage = $defaultPerPage;
    }
    $total = count($rows);
    if ($q !== '') {
        $needle = function_exists('mb_strtolower') ? mb_strtolower($q, 'UTF-8') : strtolower($q);
        $rows = array_values(array_filter($rows, static function (array $row) use ($needle): bool {
            $parts = [];
            foreach ($row as $value) {
                if (is_scalar($value) || $value === null) {
                    $parts[] = (string)$value;
                }
            }
            $haystack = implode(' ', $parts);
            $haystack = function_exists('mb_strtolower') ? mb_strtolower($haystack, 'UTF-8') : strtolower($haystack);
            return str_contains($haystack, $needle);
        }));
    }
    $filtered = count($rows);
    $pages = max(1, (int)ceil($filtered / $perPage));
    $page = max(1, min((int)($_GET[$key . '_page'] ?? 1), $pages));
    $offset = ($page - 1) * $perPage;
    return [
        'key' => $key,
        'rows' => array_slice($rows, $offset, $perPage),
        'total' => $total,
        'filtered' => $filtered,
        'page' => $page,
        'pages' => $pages,
        'per_page' => $perPage,
        'q' => $q,
    ];
}

function ja_admin_render_table_controls(array $state): void
{
    $key = (string)$state['key'];
    $page = (int)$state['page'];
    $pages = (int)$state['pages'];
    $query = $_GET;
    unset($query[$key . '_page'], $query[$key . '_q'], $query[$key . '_per_page']);
    $urlForPage = static function (int $target) use ($query, $key, $state): string {
        $values = $query;
        $values[$key . '_q'] = $state['q'];
        $values[$key . '_per_page'] = $state['per_page'];
        $values[$key . '_page'] = $target;
        return 'index.php?' . http_build_query(array_filter($values, static fn($v) => $v !== '' && $v !== null));
    };
    ?>
    <form method="get" class="admin-server-table-tools">
      <?php foreach ($query as $name => $value): if (!is_scalar($value)) continue; ?><input type="hidden" name="<?= ja_h((string)$name) ?>" value="<?= ja_h((string)$value) ?>"><?php endforeach; ?>
      <label class="admin-server-search">Buscar en todos los registros<input type="search" name="<?= ja_h($key) ?>_q" value="<?= ja_h((string)$state['q']) ?>" placeholder="Buscar en esta tabla..."></label>
      <label>Filas por página<select name="<?= ja_h($key) ?>_per_page"><?php foreach ([10,25,50,100,250] as $size): ?><option value="<?= $size ?>" <?= (int)$state['per_page'] === $size ? 'selected' : '' ?>><?= $size ?></option><?php endforeach; ?></select></label>
      <input type="hidden" name="<?= ja_h($key) ?>_page" value="1">
      <button class="button primary small" type="submit">Aplicar</button>
      <?php if ($state['q'] !== ''): ?><a class="button small" href="<?= ja_h($urlForPage(1)) ?>">Limpiar búsqueda</a><?php endif; ?>
      <span class="admin-table-count"><?= (int)$state['filtered'] ?> de <?= (int)$state['total'] ?> registros</span>
    </form>
    <nav class="admin-pagination" aria-label="Paginación">
      <a class="button small <?= $page <= 1 ? 'disabled' : '' ?>" href="<?= $page > 1 ? ja_h($urlForPage($page - 1)) : '#' ?>">Anterior</a>
      <span>Página <?= $page ?> de <?= $pages ?></span>
      <a class="button small <?= $page >= $pages ? 'disabled' : '' ?>" href="<?= $page < $pages ? ja_h($urlForPage($page + 1)) : '#' ?>">Siguiente</a>
    </nav>
    <?php
}

```

### admin/horario_semanal.php

```php
<?php
declare(strict_types=1);

if (!defined('JA_ADMIN_APPLICATION')) {
    http_response_code(404);
    exit;
}

$selectedId = (int)($_GET['employee_id'] ?? 0);
if ($selectedId <= 0 && $employees) {
    $selectedId = (int)$employees[0]['id'];
}

$selectedEmployee = null;
foreach ($employees as $employee) {
    if ((int)$employee['id'] === $selectedId) {
        $selectedEmployee = $employee;
        break;
    }
}

$employeeRole = trim((string)($selectedEmployee['role'] ?? ''));
$typeStatement = $pdo->prepare(
    "SELECT id,name,is_default,active FROM attendance_types
     WHERE tenant_id=:tenant AND lower(trim(role))=lower(trim(:role))
     ORDER BY is_default DESC,name COLLATE NOCASE"
);
$typeStatement->execute(['tenant' => $tenantId, 'role' => $employeeRole]);
$scheduleTypes = $typeStatement->fetchAll();
$activeScheduleTypes = array_values(array_filter($scheduleTypes, static fn(array $type): bool => !empty($type['active'])));
$typeIds = array_map(static fn(array $type): int => (int)$type['id'], $scheduleTypes);

$segmentsStatement = $pdo->prepare(
    "SELECT valid_from,valid_until,COUNT(*) block_count,
            MIN(start_time) first_time,MAX(end_time) last_time
     FROM schedules
     WHERE tenant_id=:tenant AND employee_id=:employee
     GROUP BY valid_from,valid_until
     ORDER BY CASE WHEN valid_from IS NULL THEN 1 ELSE 0 END,
              valid_from DESC,
              CASE WHEN valid_until IS NULL THEN 1 ELSE 0 END,
              valid_until DESC"
);
$segmentsStatement->execute(['tenant' => $tenantId, 'employee' => $selectedId]);
$scheduleSegments = $segmentsStatement->fetchAll();

// segment_from/segment_until identify the existing period being edited. The
// literal __NULL__ preserves legacy rows whose start/end date is SQL NULL.
$requestedSegmentFrom = array_key_exists('segment_from', $_GET) ? (string)$_GET['segment_from'] : null;
$requestedSegmentUntil = array_key_exists('segment_until', $_GET) ? (string)$_GET['segment_until'] : null;
$isNewSegment = isset($_GET['new_segment']) || !$scheduleSegments;

if (!$isNewSegment && $requestedSegmentFrom === null) {
    $first = $scheduleSegments[0];
    $requestedSegmentFrom = $first['valid_from'] === null ? '__NULL__' : (string)$first['valid_from'];
    $requestedSegmentUntil = $first['valid_until'] === null ? '__NULL__' : (string)$first['valid_until'];
}

$originalFromIsNull = !$isNewSegment && $requestedSegmentFrom === '__NULL__';
$originalUntilIsNull = !$isNewSegment && ($requestedSegmentUntil === '__NULL__' || $requestedSegmentUntil === null || $requestedSegmentUntil === '');
$originalValidFrom = $originalFromIsNull ? null : ($requestedSegmentFrom ?? null);
$originalValidUntil = $originalUntilIsNull ? null : $requestedSegmentUntil;

$validFrom = $isNewSegment ? date('Y-m-d') : (string)($originalValidFrom ?? '');
$validUntil = $isNewSegment ? '' : (string)($originalValidUntil ?? '');
if (isset($_GET['draft_from']) && ja_admin_valid_date((string)$_GET['draft_from'])) {
    $validFrom = (string)$_GET['draft_from'];
}
if (isset($_GET['draft_until']) && ja_admin_valid_date((string)$_GET['draft_until'])) {
    $validUntil = (string)$_GET['draft_until'];
}

$existingSlots = array_fill(1, 7, []);
if ($selectedId > 0 && !$isNewSegment) {
    $sql = 'SELECT weekday,start_time,end_time,attendance_type_id FROM schedules
            WHERE tenant_id=:tenant AND employee_id=:employee';
    $params = ['tenant' => $tenantId, 'employee' => $selectedId];
    if ($originalFromIsNull) {
        $sql .= ' AND valid_from IS NULL';
    } else {
        $sql .= ' AND valid_from=:from';
        $params['from'] = $originalValidFrom;
    }
    if ($originalUntilIsNull) {
        $sql .= ' AND valid_until IS NULL';
    } else {
        $sql .= ' AND valid_until=:until';
        $params['until'] = $originalValidUntil;
    }
    $sql .= ' ORDER BY weekday,start_time';
    $statement = $pdo->prepare($sql);
    $statement->execute($params);
    foreach ($statement->fetchAll() as $row) {
        $start = ja_admin_time_minutes($row['start_time']);
        $end = ja_admin_time_minutes($row['end_time']);
        if ($start === null || $end === null) continue;
        if ($end <= $start) $end += 1440;
        $typeId = $row['attendance_type_id'] === null ? 0 : (int)$row['attendance_type_id'];
        for ($minutes = $start; $minutes < min($end, 1440); $minutes += 30) {
            $existingSlots[(int)$row['weekday']][sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60)] = $typeId;
        }
    }
}

$selectedSlotCount = array_sum(array_map('count', $existingSlots));
$palette = ['#ef8f73','#df7e68','#ce6f5e','#bd604f','#ac5142','#9b4336','#8a352c','#79281f'];
$segmentLabel = static function (?string $from, ?string $until): string {
    $fromLabel = $from ? (new DateTimeImmutable($from))->format('d/m/Y') : 'Sin fecha de inicio';
    $untilLabel = $until ? (new DateTimeImmutable($until))->format('d/m/Y') : 'Sin fecha final';
    return $fromLabel . ' → ' . $untilLabel;
};
?>
<section class="application-stack schedule-editor" id="typedScheduleEditor">
  <div class="application-toolbar">
    <div><h2>Definir horario semanal</h2><p>Cada segmento tiene sus propias fechas de vigencia y sus bloques semanales.</p></div>
    <a class="button primary" href="<?= ja_h(ja_admin_query_url(['group'=>'applications','app'=>'horario_semanal','employee_id'=>$selectedId,'new_segment'=>1])) ?>">Nuevo segmento</a>
  </div>

  <form method="get" class="schedule-selector schedule-employee-selector">
    <input type="hidden" name="group" value="applications"><input type="hidden" name="app" value="horario_semanal">
    <label>Empleado<select name="employee_id" required><?php foreach ($employees as $employee): ?><option value="<?= (int)$employee['id'] ?>" <?= $selectedId === (int)$employee['id'] ? 'selected' : '' ?>><?= ja_h(ja_admin_employee_name($employee)) ?></option><?php endforeach; ?></select></label>
    <button class="button" type="submit">Ver horarios</button>
  </form>

  <?php if ($scheduleSegments): ?>
  <article class="card schedule-segments-card">
    <div class="ju-table-caption"><div><h3>Segmentos de horario</h3><p>Selecciona un segmento para editar sus fechas y su cuadrícula. Los cambios de fecha se guardan con el botón inferior.</p></div><span class="badge ok"><?= count($scheduleSegments) ?> segmentos</span></div>
    <div class="schedule-segment-list">
      <?php foreach ($scheduleSegments as $segment):
        $segmentFrom = $segment['valid_from'] === null ? '__NULL__' : (string)$segment['valid_from'];
        $segmentUntil = $segment['valid_until'] === null ? '__NULL__' : (string)$segment['valid_until'];
        $active = !$isNewSegment && $segmentFrom === $requestedSegmentFrom && $segmentUntil === ($requestedSegmentUntil ?? '__NULL__');
      ?>
        <a class="schedule-segment-pill <?= $active ? 'active' : '' ?>" href="<?= ja_h(ja_admin_query_url(['group'=>'applications','app'=>'horario_semanal','employee_id'=>$selectedId,'segment_from'=>$segmentFrom,'segment_until'=>$segmentUntil])) ?>">
          <strong><?= ja_h($segmentLabel($segment['valid_from'], $segment['valid_until'])) ?></strong>
          <small><?= (int)$segment['block_count'] ?> bloques · <?= ja_h(ja_admin_short_time($segment['first_time'])) ?>–<?= ja_h(ja_admin_short_time($segment['last_time'])) ?></small>
          <?php if ($segment['valid_from'] === null): ?><span class="badge warning">Fecha inicial pendiente</span><?php endif; ?>
        </a>
      <?php endforeach; ?>
    </div>
  </article>
  <?php endif; ?>

  <?php if ($selectedId > 0): ?>
  <form method="post" id="scheduleGridForm">
    <input type="hidden" name="csrf" value="<?= ja_h($csrf) ?>">
    <input type="hidden" name="action" value="save_schedule_grid">
    <input type="hidden" name="employee_id" value="<?= $selectedId ?>">
    <input type="hidden" name="editing_existing_segment" value="<?= $isNewSegment ? '0' : '1' ?>">
    <input type="hidden" name="original_valid_from_is_null" value="<?= $originalFromIsNull ? '1' : '0' ?>">
    <input type="hidden" name="original_valid_until_is_null" value="<?= $originalUntilIsNull ? '1' : '0' ?>">
    <input type="hidden" name="original_valid_from" value="<?= ja_h((string)($originalValidFrom ?? '')) ?>">
    <input type="hidden" name="original_valid_until" value="<?= ja_h((string)($originalValidUntil ?? '')) ?>">

    <section class="card schedule-period-editor">
      <div class="ju-table-caption">
        <div><h3><?= $isNewSegment ? 'Nuevo segmento' : 'Vigencia del segmento' ?></h3><p><?= $isNewSegment ? 'Define el periodo antes de guardar la nueva cuadrícula.' : 'Estas fechas pertenecen al segmento seleccionado. Modificarlas trasladará todos sus bloques al nuevo periodo.' ?></p></div>
        <span class="badge <?= $isNewSegment ? 'warning' : 'ok' ?>"><?= $isNewSegment ? 'Sin guardar' : 'Editando segmento' ?></span>
      </div>
      <div class="grid-3">
        <label>Fecha de inicio<input type="date" name="valid_from" value="<?= ja_h($validFrom) ?>" required></label>
        <label>Fecha final opcional<input type="date" name="valid_until" value="<?= ja_h($validUntil) ?>"></label>
        <div class="schedule-period-preview"><small>Periodo resultante</small><strong id="schedulePeriodPreview"><?= ja_h($segmentLabel($validFrom ?: null, $validUntil ?: null)) ?></strong></div>
      </div>
      <?php if ($originalFromIsNull && !$isNewSegment): ?><div class="notice warning">Este es un segmento antiguo sin fecha inicial. Introduce una fecha y guarda para normalizarlo.</div><?php endif; ?>
      <p class="form-hint">La fecha inicial es obligatoria. Deja la fecha final vacía únicamente cuando el horario deba continuar indefinidamente.</p>
    </section>

    <section class="card schedule-type-palette">
      <div><h3>Tipo que vas a pintar</h3><p><?= $activeScheduleTypes ? 'Selecciona un tipo y pulsa o arrastra sobre la cuadrícula.' : 'Este rol no tiene tipos activos; el horario se guardará sin tipo.' ?></p></div>
      <div class="schedule-type-buttons">
        <button type="button" class="schedule-type-choice active" data-schedule-type="0" style="--type-color:#b9aaa5"><i></i><span>Horario general</span></button>
        <?php foreach ($activeScheduleTypes as $index => $type): ?><button type="button" class="schedule-type-choice" data-schedule-type="<?= (int)$type['id'] ?>" style="--type-color:<?= ja_h($palette[$index % count($palette)]) ?>"><i></i><span><?= ja_h($type['name']) ?></span><?php if (!empty($type['is_default'])): ?><small>Predeterminado</small><?php endif; ?></button><?php endforeach; ?>
        <button type="button" class="schedule-type-choice schedule-eraser" data-schedule-type="erase"><i></i><span>Borrar</span></button>
      </div>
    </section>

    <div class="schedule-grid-wrap">
      <table class="schedule-grid typed-schedule-grid">
        <thead><tr><th>Hora</th><?php foreach ($weekdays as $day): ?><th><?= ja_h($day) ?></th><?php endforeach; ?></tr></thead>
        <tbody>
        <?php for ($minutes = 0; $minutes < 1440; $minutes += 30): $time = sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60); ?>
          <tr><td><?= ja_h($time) ?></td><?php foreach ($weekdays as $dayNumber => $day): $typeId = $existingSlots[$dayNumber][$time] ?? null; $selected = $typeId !== null; $typeIndex = array_search((int)$typeId, $typeIds, true); $color = $typeId === 0 ? '#b9aaa5' : ($typeIndex === false ? '#ef8f73' : $palette[$typeIndex % count($palette)]); ?><td class="schedule-slot <?= $selected ? 'selected' : '' ?>" tabindex="0" role="checkbox" aria-checked="<?= $selected ? 'true' : 'false' ?>" data-day="<?= $dayNumber ?>" data-time="<?= ja_h($time) ?>" data-type-id="<?= $selected ? (int)$typeId : '' ?>" style="--slot-color:<?= ja_h($color) ?>"><span></span></td><?php endforeach; ?></tr>
        <?php endfor; ?>
        </tbody>
      </table>
    </div>
    <div class="schedule-savebar"><strong id="scheduleTotal"><?= ja_h(number_format($selectedSlotCount * .5, 1, ',', '.')) ?> horas seleccionadas</strong><button class="button primary" type="submit"><?= $isNewSegment ? 'Crear segmento' : 'Guardar fechas y horario' ?></button></div>
  </form>
  <?php else: ?><div class="empty">No hay empleados disponibles.</div><?php endif; ?>
</section>

```

### admin/index.php

```php
<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/entity_tables.php';

use JA\Auth;
use JA\AbsenceAttachment;
use JA\AbsenceService;
use JA\AttendanceAudit;
use JA\Csrf;
use JA\CompanyLocation;
use JA\Database;
use JA\Session;
use JA\Settings;
use JA\TenantContext;
use JA\View;

Session::start('admin');
$tenant = TenantContext::current();
$tenantId = (int)$tenant['id'];
$pdo = Database::pdo();
$loginError = '';

if (empty($_SESSION['admin'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        try {
            Csrf::check($_POST['csrf'] ?? null);
            if (Auth::loginTenantAdmin(ja_post('username'), (string)($_POST['password'] ?? ''))) {
                ja_redirect('index.php');
            }
            $loginError = 'Usuario o contraseña incorrectos para este dominio.';
        } catch (Throwable $exception) {
            $loginError = $exception->getMessage();
        }
    }
    View::login('Administración', (string)$tenant['name'], $loginError, Csrf::token());
    exit;
}

$admin = Auth::requireTenantAdmin();
$anchoredRecords = AttendanceAudit::bootstrapTenant($pdo, $tenantId);
$applications = [
    'hoy' => ['title' => 'Hoy', 'hint' => 'Perspectiva en tiempo real de quién debía fichar, quién está trabajando y quién ha cerrado su jornada.'],
    'cuadricula_asistencia' => ['title' => 'Cuadrícula de asistencia', 'hint' => 'Vista mensual de la plantilla con búsqueda, estados y opciones visuales.'],
    'calendario_semanal' => ['title' => 'Calendario semanal', 'hint' => 'Calendario de turnos activos por empleado.'],
    'estadisticas_empleados' => ['title' => 'Estadísticas de empleados', 'hint' => 'Analítica completa de asistencia por persona.'],
    'horario_semanal' => ['title' => 'Horario semanal', 'hint' => 'Editor visual de horarios por medias horas.'],
    'reuniones' => ['title' => 'Reuniones', 'hint' => 'Calendario semanal real de reuniones y eventos computables como trabajo fuera del horario establecido.'],
    'resumen_sesiones' => ['title' => 'Resumen de sesiones', 'hint' => 'Estado semanal de asistencia de toda la plantilla.'],
    'solicitudes_personal' => ['title' => 'Solicitudes del personal', 'hint' => 'Aprobar o rechazar correcciones propuestas por las personas trabajadoras.'],
    'temporalizacion_vacaciones' => ['title' => 'Temporalización vacaciones', 'hint' => 'Vista anual de las vacaciones de toda la plantilla en una línea temporal diaria.'],
];

// Additive storage for dated meetings. Migrate the first patch transparently if necessary.
$meetingColumns=[]; try{$meetingColumns=$pdo->query("PRAGMA table_info(meetings)")->fetchAll();}catch(Throwable $e){}
if($meetingColumns && !in_array('scope_type',array_column($meetingColumns,'name'),true)){
 $pdo->beginTransaction();
 $pdo->exec("ALTER TABLE meetings RENAME TO meetings_v1_employee_only");
 $pdo->exec("CREATE TABLE meetings (id INTEGER PRIMARY KEY, tenant_id INTEGER NOT NULL, scope_type TEXT NOT NULL DEFAULT 'employee' CHECK(scope_type IN ('all','role','employee')), target_role TEXT, employee_id INTEGER, event_date TEXT NOT NULL, start_time TEXT NOT NULL, end_time TEXT NOT NULL, title TEXT NOT NULL DEFAULT 'Reunión', created_by_admin_id INTEGER, created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE, FOREIGN KEY (employee_id,tenant_id) REFERENCES employees(id,tenant_id) ON DELETE CASCADE)");
 $pdo->exec("INSERT INTO meetings(id,tenant_id,scope_type,employee_id,event_date,start_time,end_time,title,created_by_admin_id,created_at,updated_at) SELECT id,tenant_id,'employee',employee_id,event_date,start_time,end_time,title,created_by_admin_id,created_at,updated_at FROM meetings_v1_employee_only");
 $pdo->exec("DROP TABLE meetings_v1_employee_only"); $pdo->commit();
}
$pdo->exec("CREATE TABLE IF NOT EXISTS meetings (id INTEGER PRIMARY KEY, tenant_id INTEGER NOT NULL, scope_type TEXT NOT NULL DEFAULT 'employee' CHECK(scope_type IN ('all','role','employee')), target_role TEXT, employee_id INTEGER, event_date TEXT NOT NULL, start_time TEXT NOT NULL, end_time TEXT NOT NULL, title TEXT NOT NULL DEFAULT 'Reunión', created_by_admin_id INTEGER, created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE, FOREIGN KEY (employee_id,tenant_id) REFERENCES employees(id,tenant_id) ON DELETE CASCADE)");
$pdo->exec("CREATE INDEX IF NOT EXISTS ix_meetings_scope_date ON meetings(tenant_id,scope_type,target_role,employee_id,event_date,start_time)");

$tableViews = ja_admin_business_table_views();
$dashboardView = ['title' => 'Resumen', 'hint' => 'Indicadores y actividad reciente.'];

$hasExplicitDestination = isset($_GET['group']) || isset($_GET['view']) || isset($_GET['section']) || isset($_GET['config']);
$app = basename((string)($_GET['app'] ?? ($hasExplicitDestination ? '' : 'hoy')));
if (!isset($applications[$app])) {
    $app = '';
}
$view = (string)($_GET['view'] ?? $_GET['section'] ?? 'dashboard');
if ($view !== 'dashboard' && !isset($tableViews[$view])) {
    $view = 'dashboard';
}
$group = (string)($_GET['group'] ?? $_GET['grupo'] ?? ($app !== '' ? 'applications' : 'applications'));
if (!in_array($group, ['favorites', 'applications', 'tables', 'config', 'help', 'logout'], true)) {
    $group = 'applications';
}
if ($app !== '') {
    $group = 'applications';
}
if (isset($_GET['view']) || isset($_GET['section'])) {
    $group = 'tables';
}
$configPage = (string)($_GET['config'] ?? 'general');
if (!in_array($configPage, ['general', 'audit'], true)) {
    $configPage = 'general';
}
if ($group === 'tables' && $view === 'dashboard') {
    $view = 'attendance';
}

$redirectParams = [];
foreach (['group', 'app', 'view', 'config', 'employee_id', 'contract_id', 'department', 'role', 'from', 'to', 'date', 'month', 'week', 'year', 'valid_from', 'valid_until', 'only_with_vacations'] as $key) {
    if (isset($_GET[$key]) && is_scalar($_GET[$key])) {
        $redirectParams[$key] = (string)$_GET[$key];
    }
}
foreach ($_GET as $key => $value) {
    if (is_scalar($value) && preg_match('/^[a-z0-9_]+_(?:q|page|per_page)$/i', (string)$key)) {
        $redirectParams[(string)$key] = (string)$value;
    }
}
$redirectUrl = $redirectParams ? ja_admin_query_url($redirectParams) : 'index.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        Csrf::check($_POST['csrf'] ?? null);
        $action = ja_post('action');

        if (ja_admin_business_action($pdo, $tenantId, $admin, $action)) {
            // La operación específica de la tabla ya se ha procesado.
        } elseif ($action === 'save_employee') {
            $employeeId = (int)($_POST['employee_id'] ?? 0);
            $data = [
                'tenant' => $tenantId,
                'nombre' => ja_post('nombre'),
                'apellido' => ja_post('apellido') ?: null,
                'departamento' => ja_post('departamento') ?: null,
                'role' => ja_post('role') ?: null,
                'center' => ((int)($_POST['work_center_id'] ?? 0)) ?: null,
                'contract' => ((int)($_POST['contract_type_id'] ?? 0)) ?: null,
            ];
            if ($data['nombre'] === '') {
                throw new RuntimeException('El nombre es obligatorio.');
            }
            if ($employeeId > 0) {
                $statement = $pdo->prepare('UPDATE employees SET nombre=:nombre, apellido=:apellido, departamento=:departamento, role=:role, work_center_id=:center, contract_type_id=:contract, updated_at=CURRENT_TIMESTAMP WHERE id=:id AND tenant_id=:tenant');
                $statement->execute($data + ['id' => $employeeId]);
            } else {
                $statement = $pdo->prepare('INSERT INTO employees (tenant_id,nombre,apellido,departamento,role,work_center_id,contract_type_id) VALUES (:tenant,:nombre,:apellido,:departamento,:role,:center,:contract)');
                $statement->execute($data);
            }
        } elseif ($action === 'toggle_employee') {
            $statement = $pdo->prepare('UPDATE employees SET active=CASE active WHEN 1 THEN 0 ELSE 1 END, updated_at=CURRENT_TIMESTAMP WHERE id=:id AND tenant_id=:tenant');
            $statement->execute(['id' => (int)($_POST['employee_id'] ?? 0), 'tenant' => $tenantId]);
        } elseif ($action === 'save_attendance') {
            $employeeId = (int)($_POST['employee_id'] ?? 0);
            $workDate = ja_post('work_date');
            if (!ja_admin_valid_date($workDate) || $workDate === '') {
                throw new RuntimeException('Fecha de asistencia no válida.');
            }
            $recordId = (int)($_POST['record_id'] ?? 0);
            $reason = ja_post('reason');
            if ($recordId > 0) {
                AttendanceAudit::proposeCorrection(
                    $pdo,
                    $tenantId,
                    $recordId,
                    $workDate,
                    ja_post('start_time') ?: null,
                    ja_post('end_time') ?: null,
                    ((int)($_POST['attendance_type_id'] ?? 0)) ?: null,
                    $reason,
                    $admin
                );
                $_SESSION['flash'] = ['type' => 'ok', 'message' => 'Corrección enviada. La jornada no cambiará hasta que la persona trabajadora la acepte.'];
            } else {
                AttendanceAudit::createManualAttendance(
                    $pdo,
                    $tenantId,
                    $employeeId,
                    $workDate,
                    ja_post('start_time') ?: null,
                    ja_post('end_time') ?: null,
                    ((int)($_POST['attendance_type_id'] ?? 0)) ?: null,
                    $admin,
                    $reason
                );
                $_SESSION['flash'] = ['type' => 'ok', 'message' => 'Jornada manual registrada y añadida a la cadena de integridad.'];
            }
        } elseif ($action === 'delete_attendance') {
            throw new RuntimeException('Las jornadas no se eliminan. Debes proponer una corrección para conservar la trazabilidad.');
        } elseif ($action === 'worker_correction_decision') {
            $decision = ja_post('decision');
            AttendanceAudit::decideWorkerCorrection(
                $pdo,
                $tenantId,
                $admin,
                (int)($_POST['request_id'] ?? 0),
                $decision,
                ja_post('admin_comment')
            );
            $_SESSION['flash'] = [
                'type' => 'ok',
                'message' => $decision === 'accepted'
                    ? 'Solicitud aprobada. La jornada se ha actualizado y auditado.'
                    : 'Solicitud rechazada. La jornada original se conserva.',
            ];
        } elseif ($action === 'save_settings') {
            $companyLocationEnabled = isset($_POST['company_location_enabled']);
            $companyIpv4 = CompanyLocation::normalizeConfigured(ja_post('company_ipv4'), 4);
            $companyIpv6 = CompanyLocation::normalizeConfigured(ja_post('company_ipv6'), 6);
            $emailNotificationsEnabled = isset($_POST['email_notifications_enabled']);
            $notificationEmail = ja_post('notification_email');
            if ($companyLocationEnabled && $companyIpv4 === '' && $companyIpv6 === '') {
                throw new RuntimeException('Para activar la ubicación por IP debes indicar al menos una IPv4 o una IPv6 de la empresa.');
            }
            if ($notificationEmail !== '' && !filter_var($notificationEmail, FILTER_VALIDATE_EMAIL)) {
                throw new RuntimeException('La dirección de email para notificaciones no es válida.');
            }
            if ($emailNotificationsEnabled && $notificationEmail === '') {
                throw new RuntimeException('Indica una dirección de email antes de activar las notificaciones.');
            }
            Settings::set($pdo, $tenantId, Settings::WORKER_ATTENDANCE_EDIT, isset($_POST['worker_attendance_edit_enabled']) ? '1' : '0', (int)$admin['id']);
            $toastSeconds = max(1, min(120, (int)($_POST['internal_toast_seconds'] ?? 8)));
            $toastMode = in_array(ja_post('internal_toast_mode', 'stack'), ['stack', 'sequence'], true) ? ja_post('internal_toast_mode', 'stack') : 'stack';
            Settings::set($pdo, $tenantId, Settings::INTERNAL_TOAST_SECONDS, (string)$toastSeconds, (int)$admin['id']);
            Settings::set($pdo, $tenantId, Settings::INTERNAL_TOAST_MODE, $toastMode, (int)$admin['id']);
            Settings::set($pdo, $tenantId, Settings::COMPANY_LOCATION_ENABLED, $companyLocationEnabled ? '1' : '0', (int)$admin['id']);
            Settings::set($pdo, $tenantId, Settings::COMPANY_IPV4, $companyIpv4, (int)$admin['id']);
            Settings::set($pdo, $tenantId, Settings::COMPANY_IPV6, $companyIpv6, (int)$admin['id']);
            Settings::set($pdo, $tenantId, Settings::EMAIL_NOTIFICATIONS_ENABLED, $emailNotificationsEnabled ? '1' : '0', (int)$admin['id']);
            Settings::set($pdo, $tenantId, Settings::NOTIFICATION_EMAIL, $notificationEmail, (int)$admin['id']);
            Settings::set($pdo, $tenantId, Settings::NOTIFY_ABSENCE_REQUEST, isset($_POST['notify_absence_request']) ? '1' : '0', (int)$admin['id']);
            Settings::set($pdo, $tenantId, Settings::NOTIFY_OUT_OF_SCHEDULE_CLOCKING, isset($_POST['notify_out_of_schedule_clocking']) ? '1' : '0', (int)$admin['id']);
            $_SESSION['flash'] = ['type' => 'ok', 'message' => 'Configuración actualizada.'];
        } elseif ($action === 'toggle_user_conciliacion') {
            $statement = $pdo->prepare('UPDATE users SET conciliacion=CASE conciliacion WHEN 1 THEN 0 ELSE 1 END,updated_at=CURRENT_TIMESTAMP WHERE id=:id AND tenant_id=:tenant');
            $statement->execute(['id' => (int)($_POST['user_id'] ?? 0), 'tenant' => $tenantId]);
        } elseif ($action === 'save_internal_message_type') {
            $id = (int)($_POST['record_id'] ?? 0);
            $name = ja_post('name'); $color = ja_post('color', '#e9967a'); $emoji = ja_post('emoji', '📢');
            if ($name === '' || !preg_match('/^#[0-9a-fA-F]{6}$/', $color) || $emoji === '') throw new RuntimeException('Revisa el nombre, color y emoji del tipo.');
            if ($id > 0) {
                $pdo->prepare('UPDATE internal_message_types SET name=:name,color=:color,emoji=:emoji,active=:active,updated_at=CURRENT_TIMESTAMP WHERE id=:id AND tenant_id=:tenant')->execute(['name'=>$name,'color'=>$color,'emoji'=>$emoji,'active'=>isset($_POST['active'])?1:0,'id'=>$id,'tenant'=>$tenantId]);
            } else {
                $pdo->prepare('INSERT INTO internal_message_types (tenant_id,name,color,emoji,active) VALUES (:tenant,:name,:color,:emoji,:active)')->execute(['tenant'=>$tenantId,'name'=>$name,'color'=>$color,'emoji'=>$emoji,'active'=>isset($_POST['active'])?1:0]);
            }
        } elseif ($action === 'save_internal_message') {
            $id=(int)($_POST['record_id']??0); $typeId=(int)($_POST['message_type_id']??0); $start=ja_post('start_date'); $days=max(1,min(3650,(int)($_POST['highlight_days']??1))); $message=ja_post('message');
            if (!ja_admin_valid_date($start) || $start==='' || $message==='') throw new RuntimeException('La fecha y el mensaje son obligatorios.');
            $check=$pdo->prepare('SELECT id FROM internal_message_types WHERE id=:id AND tenant_id=:tenant'); $check->execute(['id'=>$typeId,'tenant'=>$tenantId]); if(!$check->fetchColumn()) throw new RuntimeException('Tipo de comunicación no válido.');
            if($id>0){$pdo->prepare('UPDATE internal_messages SET message_type_id=:type,start_date=:start,highlight_days=:days,message=:message,active=:active,updated_at=CURRENT_TIMESTAMP WHERE id=:id AND tenant_id=:tenant')->execute(['type'=>$typeId,'start'=>$start,'days'=>$days,'message'=>$message,'active'=>isset($_POST['active'])?1:0,'id'=>$id,'tenant'=>$tenantId]);}
            else{$pdo->prepare('INSERT INTO internal_messages (tenant_id,message_type_id,start_date,highlight_days,message,active,created_by_admin_id) VALUES (:tenant,:type,:start,:days,:message,:active,:admin)')->execute(['tenant'=>$tenantId,'type'=>$typeId,'start'=>$start,'days'=>$days,'message'=>$message,'active'=>isset($_POST['active'])?1:0,'admin'=>(int)$admin['id']]);}
        } elseif ($action === 'delete_internal_message') {
            $pdo->prepare('DELETE FROM internal_messages WHERE id=:id AND tenant_id=:tenant')->execute(['id'=>(int)($_POST['record_id']??0),'tenant'=>$tenantId]);
        } elseif ($action === 'save_schedule') {
            $employeeId = (int)($_POST['employee_id'] ?? 0);
            $check = $pdo->prepare('SELECT id FROM employees WHERE id=:id AND tenant_id=:tenant');
            $check->execute(['id' => $employeeId, 'tenant' => $tenantId]);
            if (!$check->fetchColumn()) {
                throw new RuntimeException('Empleado no válido para esta organización.');
            }
            $statement = $pdo->prepare('INSERT INTO schedules (tenant_id,employee_id,weekday,valid_from,valid_until,start_time,end_time) VALUES (:tenant,:employee,:weekday,:from,:until,:start,:end)');
            $statement->execute([
                'tenant' => $tenantId,
                'employee' => $employeeId,
                'weekday' => (int)($_POST['weekday'] ?? 0),
                'from' => ja_post('valid_from') ?: null,
                'until' => ja_post('valid_until') ?: null,
                'start' => ja_post('start_time'),
                'end' => ja_post('end_time'),
            ]);
        } elseif ($action === 'save_schedule_grid') {
            $employeeId = (int)($_POST['employee_id'] ?? 0);
            $validFrom = ja_post('valid_from');
            $validUntil = ja_post('valid_until') ?: null;
            $editingExistingSegment = (string)($_POST['editing_existing_segment'] ?? '0') === '1';
            $originalFromIsNull = (string)($_POST['original_valid_from_is_null'] ?? '0') === '1';
            $originalUntilIsNull = (string)($_POST['original_valid_until_is_null'] ?? '0') === '1';
            $originalValidFrom = ja_post('original_valid_from') ?: null;
            $originalValidUntil = ja_post('original_valid_until') ?: null;
            if ($employeeId <= 0 || $validFrom === '' || !ja_admin_valid_date($validFrom) || !ja_admin_valid_date($validUntil) || ($validUntil !== null && $validUntil < $validFrom)) {
                throw new RuntimeException('La fecha inicial es obligatoria y el periodo de vigencia debe ser válido.');
            }
            $check = $pdo->prepare('SELECT id,role FROM employees WHERE id=:id AND tenant_id=:tenant');
            $check->execute(['id' => $employeeId, 'tenant' => $tenantId]);
            $employeeForSchedule = $check->fetch();
            if (!$employeeForSchedule) {
                throw new RuntimeException('Empleado no válido para esta organización.');
            }
            $typesStatement = $pdo->prepare(
                "SELECT id FROM attendance_types
                 WHERE tenant_id=:tenant AND active=1
                   AND lower(trim(role))=lower(trim(:role))"
            );
            $typesStatement->execute(['tenant'=>$tenantId,'role'=>(string)($employeeForSchedule['role'] ?? '')]);
            $allowedTypes = array_fill_keys(array_map('intval', $typesStatement->fetchAll(PDO::FETCH_COLUMN)), true);
            $allowedTypes[0] = true;
            $allowedSlots = [];
            for ($minutes = 0; $minutes < 1440; $minutes += 30) {
                $allowedSlots[sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60)] = $minutes;
            }
            $postedSlots = is_array($_POST['slots'] ?? null) ? $_POST['slots'] : [];
            $pdo->beginTransaction();

            // When an existing segment is edited, remove it using its original
            // SQL identity. This is what makes changing its dates persistent.
            if ($editingExistingSegment) {
                $deleteSql = 'DELETE FROM schedules WHERE tenant_id=:tenant AND employee_id=:employee';
                $deleteParams = ['tenant' => $tenantId, 'employee' => $employeeId];
                if ($originalFromIsNull) {
                    $deleteSql .= ' AND valid_from IS NULL';
                } else {
                    if ($originalValidFrom === null || !ja_admin_valid_date($originalValidFrom)) {
                        throw new RuntimeException('No se pudo identificar el segmento original. Vuelve a abrirlo e inténtalo de nuevo.');
                    }
                    $deleteSql .= ' AND valid_from=:original_from';
                    $deleteParams['original_from'] = $originalValidFrom;
                }
                if ($originalUntilIsNull) {
                    $deleteSql .= ' AND valid_until IS NULL';
                } else {
                    if ($originalValidUntil === null || !ja_admin_valid_date($originalValidUntil)) {
                        throw new RuntimeException('No se pudo identificar la fecha final original del segmento.');
                    }
                    $deleteSql .= ' AND valid_until=:original_until';
                    $deleteParams['original_until'] = $originalValidUntil;
                }
                $pdo->prepare($deleteSql)->execute($deleteParams);
            } else {
                // A new segment must not silently overwrite another period.
                $duplicateSql = 'SELECT COUNT(*) FROM schedules WHERE tenant_id=:tenant AND employee_id=:employee AND valid_from=:from';
                $duplicateParams = ['tenant' => $tenantId, 'employee' => $employeeId, 'from' => $validFrom];
                if ($validUntil === null) {
                    $duplicateSql .= ' AND valid_until IS NULL';
                } else {
                    $duplicateSql .= ' AND valid_until=:until';
                    $duplicateParams['until'] = $validUntil;
                }
                $duplicate = $pdo->prepare($duplicateSql);
                $duplicate->execute($duplicateParams);
                if ((int)$duplicate->fetchColumn() > 0) {
                    throw new RuntimeException('Ya existe un segmento con estas fechas. Selecciónalo para editarlo.');
                }
            }
            for ($weekday = 1; $weekday <= 7; $weekday++) {
                $raw = is_array($postedSlots[$weekday] ?? null) ? $postedSlots[$weekday] : [];
                $typedMinutes = [];
                foreach ($raw as $slot => $rawTypeId) {
                    $slot = (string)$slot;
                    $typeId = (int)$rawTypeId;
                    if (isset($allowedSlots[$slot]) && isset($allowedTypes[$typeId])) {
                        $typedMinutes[$allowedSlots[$slot]] = $typeId;
                    }
                }
                ksort($typedMinutes);
                $blocks = [];
                foreach ($typedMinutes as $slotMinutes => $typeId) {
                    $lastIndex = count($blocks) - 1;
                    if ($lastIndex < 0 || $slotMinutes !== $blocks[$lastIndex][1] || $typeId !== $blocks[$lastIndex][2]) {
                        $blocks[] = [$slotMinutes, $slotMinutes + 30, $typeId];
                    } else {
                        $blocks[$lastIndex][1] = $slotMinutes + 30;
                    }
                }
                foreach ($blocks as [$startMinutes, $endMinutes, $typeId]) {
                    $insert = $pdo->prepare('INSERT INTO schedules (tenant_id,employee_id,weekday,valid_from,valid_until,start_time,end_time,attendance_type_id) VALUES (:tenant,:employee,:weekday,:from,:until,:start,:end,:type)');
                    $insert->execute([
                        'tenant'=>$tenantId,'employee'=>$employeeId,'weekday'=>$weekday,
                        'from'=>$validFrom,'until'=>$validUntil,
                        'start'=>ja_admin_minutes_time($startMinutes),'end'=>ja_admin_minutes_time($endMinutes),
                        'type'=>$typeId > 0 ? $typeId : null,
                    ]);
                }
            }
            $pdo->commit();
            $redirectUrl = ja_admin_query_url(['group'=>'applications','app'=>'horario_semanal','employee_id'=>$employeeId,'segment_from'=>$validFrom,'segment_until'=>$validUntil ?? '__NULL__']);
        } elseif ($action === 'save_meeting_grid') {
            $scope=ja_post('meeting_scope'); if(!in_array($scope,['all','role','employee'],true)) throw new RuntimeException('Ámbito de reunión no válido.');
            $employeeId=(int)($_POST['employee_id']??0); $targetRole=trim(ja_post('meeting_role')); $weekStart=ja_post('week_start'); $title=trim(ja_post('meeting_title')); if($title==='')$title='Reunión';
            if(!ja_admin_valid_date($weekStart))throw new RuntimeException('Semana no válida.');
            if($scope==='employee'){$check=$pdo->prepare('SELECT id FROM employees WHERE id=:id AND tenant_id=:tenant');$check->execute(['id'=>$employeeId,'tenant'=>$tenantId]);if(!$check->fetchColumn())throw new RuntimeException('Empleado no válido.');}
            if($scope==='role'&&$targetRole==='')throw new RuntimeException('Tipo de empleado no válido.');
            $monday=(new DateTimeImmutable($weekStart))->modify('monday this week');$from=$monday->format('Y-m-d');$to=$monday->modify('+6 days')->format('Y-m-d');$posted=is_array($_POST['meeting_slots']??null)?$_POST['meeting_slots']:[];
            $where='tenant_id=:tenant AND scope_type=:scope AND event_date BETWEEN :from AND :to';$del=['tenant'=>$tenantId,'scope'=>$scope,'from'=>$from,'to'=>$to];if($scope==='employee'){$where.=' AND employee_id=:employee';$del['employee']=$employeeId;}elseif($scope==='role'){$where.=' AND target_role=:role';$del['role']=$targetRole;}
            $pdo->beginTransaction();$d=$pdo->prepare('DELETE FROM meetings WHERE '.$where);$d->execute($del);
            for($day=1;$day<=7;$day++){$raw=is_array($posted[$day]??null)?$posted[$day]:[];$mins=[];foreach($raw as $time=>$on){$m=ja_admin_time_minutes((string)$time);if($m!==null&&$m>=0&&$m<1440&&$m%30===0)$mins[$m]=true;}ksort($mins);$blocks=[];foreach(array_keys($mins) as $m){$i=count($blocks)-1;if($i<0||$m!==$blocks[$i][1])$blocks[]=[$m,$m+30];else$blocks[$i][1]=$m+30;}$date=$monday->modify('+'.($day-1).' days')->format('Y-m-d');foreach($blocks as[$a,$b]){$ins=$pdo->prepare('INSERT INTO meetings(tenant_id,scope_type,target_role,employee_id,event_date,start_time,end_time,title,created_by_admin_id) VALUES(:tenant,:scope,:role,:employee,:date,:start,:end,:title,:admin)');$ins->execute(['tenant'=>$tenantId,'scope'=>$scope,'role'=>$scope==='role'?$targetRole:null,'employee'=>$scope==='employee'?$employeeId:null,'date'=>$date,'start'=>ja_admin_minutes_time($a),'end'=>ja_admin_minutes_time($b),'title'=>$title,'admin'=>(int)$admin['id']]);}}
            $pdo->commit();
            if(($_POST['autosave']??'')==='1'){header('Content-Type: application/json; charset=utf-8');echo json_encode(['ok'=>true,'saved_at'=>date('H:i:s')]);exit;}
            $redirectUrl=ja_admin_query_url(['group'=>'applications','app'=>'reuniones','meeting_scope'=>$scope,'meeting_role'=>$targetRole,'employee_id'=>$employeeId,'week'=>$monday->format('Y-m-d')]);
        } elseif ($action === 'delete_schedule') {
            $statement = $pdo->prepare('DELETE FROM schedules WHERE id=:id AND tenant_id=:tenant');
            $statement->execute(['id' => (int)($_POST['schedule_id'] ?? 0), 'tenant' => $tenantId]);
        } elseif ($action === 'create_admin_absence') {
            $range = AbsenceService::normalizeRange(
                ja_post('start_date'),
                ja_post('start_time') ?: null,
                ja_post('end_date') ?: null,
                ja_post('end_time') ?: null
            );
            $attachment = AbsenceAttachment::store($_FILES['attachment'] ?? null, $tenantId);
            AbsenceService::createByAdmin(
                $pdo,
                $tenantId,
                (int)($_POST['employee_id'] ?? 0),
                (int)$admin['id'],
                $range,
                ja_post('absence_type'),
                ja_post('reason'),
                $attachment
            );
            $_SESSION['flash'] = ['type' => 'ok', 'message' => 'Ausencia propuesta. La persona trabajadora debe aceptarla desde su portal.'];
        } elseif ($action === 'save_sick_leave') {
            $id = (int)($_POST['record_id'] ?? 0);
            $employeeId = (int)($_POST['employee_id'] ?? 0);
            $range = AbsenceService::normalizeRange(
                ja_post('start_date'),
                ja_post('start_time'),
                ja_post('end_date'),
                ja_post('end_time')
            );
            $leaveType = trim(ja_post('leave_type')) ?: 'Baja médica';
            $reason = trim(ja_post('reason')) ?: null;
            $attachment = AbsenceAttachment::store($_FILES['attachment'] ?? null, $tenantId);
            if ($employeeId < 1) { throw new RuntimeException('Selecciona un empleado.'); }
            if ($id > 0) {
                $current = $pdo->prepare('SELECT attachment,attachment_name FROM sick_leaves WHERE id=:id AND tenant_id=:tenant LIMIT 1');
                $current->execute(['id'=>$id,'tenant'=>$tenantId]);
                $old = $current->fetch();
                if (!$old) { throw new RuntimeException('La baja no existe.'); }
                $stmt = $pdo->prepare('UPDATE sick_leaves SET employee_id=:employee,start_date=:start_date,start_time=:start_time,end_date=:end_date,end_time=:end_time,leave_type=:type,reason=:reason,attachment=:attachment,attachment_name=:attachment_name,updated_at=CURRENT_TIMESTAMP WHERE id=:id AND tenant_id=:tenant');
                $stmt->execute([
                    'employee'=>$employeeId,'start_date'=>$range['start_date'],'start_time'=>$range['start_time'],
                    'end_date'=>$range['end_date'],'end_time'=>$range['end_time'],'type'=>$leaveType,'reason'=>$reason,
                    'attachment'=>$attachment['path'] ?? $old['attachment'],'attachment_name'=>$attachment['name'] ?? $old['attachment_name'],
                    'id'=>$id,'tenant'=>$tenantId
                ]);
            } else {
                $stmt = $pdo->prepare('INSERT INTO sick_leaves (tenant_id,employee_id,start_date,start_time,end_date,end_time,leave_type,reason,attachment,attachment_name,created_by_admin_id) VALUES (:tenant,:employee,:start_date,:start_time,:end_date,:end_time,:type,:reason,:attachment,:attachment_name,:admin)');
                $stmt->execute([
                    'tenant'=>$tenantId,'employee'=>$employeeId,'start_date'=>$range['start_date'],'start_time'=>$range['start_time'],
                    'end_date'=>$range['end_date'],'end_time'=>$range['end_time'],'type'=>$leaveType,'reason'=>$reason,
                    'attachment'=>$attachment['path'] ?? null,'attachment_name'=>$attachment['name'] ?? null,'admin'=>(int)$admin['id']
                ]);
            }
            $_SESSION['flash'] = ['type' => 'ok', 'message' => $id > 0 ? 'Baja actualizada correctamente.' : 'Baja registrada correctamente.'];
        } elseif ($action === 'delete_sick_leave') {
            $id = (int)($_POST['record_id'] ?? 0);
            $stmt = $pdo->prepare('DELETE FROM sick_leaves WHERE id=:id AND tenant_id=:tenant');
            $stmt->execute(['id'=>$id,'tenant'=>$tenantId]);
            $_SESSION['flash'] = ['type' => 'ok', 'message' => 'Baja eliminada correctamente.'];
        } elseif ($action === 'absence_status') {
            $status = ja_post('status');
            if (!in_array($status, ['pending', 'approved', 'rejected'], true)) {
                throw new RuntimeException('Estado de ausencia no válido.');
            }
            $statement = $pdo->prepare("UPDATE absences SET status=:status, approved=:approved
                WHERE id=:id AND tenant_id=:tenant AND source<>'admin'");
            $statement->execute(['status' => $status, 'approved' => $status === 'approved' ? 1 : 0, 'id' => (int)($_POST['absence_id'] ?? 0), 'tenant' => $tenantId]);
            if ($statement->rowCount() !== 1) {
                throw new RuntimeException('Las ausencias propuestas por administración solo pueden ser aceptadas o rechazadas por la persona trabajadora.');
            }
        } elseif ($action === 'create_user') {
            $password = (string)($_POST['password'] ?? '');
            if (strlen($password) < 8) {
                throw new RuntimeException('La contraseña debe tener al menos 8 caracteres.');
            }
            $employeeId = (int)($_POST['employee_id'] ?? 0);
            $employeeStatement = $pdo->prepare('SELECT nombre, apellido FROM employees WHERE id=:id AND tenant_id=:tenant');
            $employeeStatement->execute(['id' => $employeeId, 'tenant' => $tenantId]);
            $employeeRow = $employeeStatement->fetch();
            if (!$employeeRow) {
                throw new RuntimeException('Empleado no válido.');
            }
            $statement = $pdo->prepare("INSERT INTO users (tenant_id,employee_id,name,email,username,password_hash,role,active) VALUES (:tenant,:employee,:name,:email,:username,:password,'worker',1)");
            $statement->execute([
                'tenant' => $tenantId,
                'employee' => $employeeId,
                'name' => trim((string)$employeeRow['nombre'] . ' ' . (string)$employeeRow['apellido']),
                'email' => ja_post('email') ?: null,
                'username' => ja_post('username'),
                'password' => password_hash($password, PASSWORD_DEFAULT),
            ]);
        } elseif ($action === 'toggle_user') {
            $statement = $pdo->prepare('UPDATE users SET active=CASE active WHEN 1 THEN 0 ELSE 1 END, updated_at=CURRENT_TIMESTAMP WHERE id=:id AND tenant_id=:tenant');
            $statement->execute(['id' => (int)($_POST['user_id'] ?? 0), 'tenant' => $tenantId]);
        } elseif ($action === 'reset_user_password') {
            $userId = (int)($_POST['user_id'] ?? 0);
            $newPassword = (string)($_POST['new_password'] ?? '');
            if (strlen($newPassword) < 8) {
                throw new RuntimeException('La nueva contraseña debe tener al menos 8 caracteres.');
            }
            $statement = $pdo->prepare('UPDATE users SET password_hash=:password,updated_at=CURRENT_TIMESTAMP WHERE id=:id AND tenant_id=:tenant');
            $statement->execute([
                'password' => password_hash($newPassword, PASSWORD_DEFAULT),
                'id' => $userId,
                'tenant' => $tenantId,
            ]);
            if ($statement->rowCount() !== 1) {
                throw new RuntimeException('Usuario no válido para esta organización.');
            }
            $_SESSION['flash'] = ['type' => 'ok', 'message' => 'Contraseña restablecida correctamente.'];
        } else {
            throw new RuntimeException('Operación no reconocida.');
        }
        if ($pdo->inTransaction()) {
            $pdo->commit();
        }
        if (!isset($_SESSION['flash'])) {
            $_SESSION['flash'] = ['type' => 'ok', 'message' => 'Cambios guardados correctamente.'];
        }
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $_SESSION['flash'] = ['type' => 'danger', 'message' => $exception->getMessage()];
    }
    ja_redirect($redirectUrl);
}

$employeeStatement = $pdo->prepare('SELECT e.*, c.name AS center_name, ct.name AS contract_name FROM employees e LEFT JOIN work_centers c ON c.id=e.work_center_id AND c.tenant_id=e.tenant_id LEFT JOIN contract_types ct ON ct.id=e.contract_type_id AND ct.tenant_id=e.tenant_id WHERE e.tenant_id=:tenant ORDER BY e.apellido,e.nombre');
$employeeStatement->execute(['tenant' => $tenantId]);
$employees = $employeeStatement->fetchAll();
$selectedEmployee = null;
$selectedEmployeeId = (int)($_GET['employee_id'] ?? 0);
foreach ($employees as $candidate) {
    if ((int)$candidate['id'] === $selectedEmployeeId) {
        $selectedEmployee = $candidate;
        break;
    }
}

$centersStatement = $pdo->prepare('SELECT * FROM work_centers WHERE tenant_id=:tenant ORDER BY name');
$centersStatement->execute(['tenant' => $tenantId]);
$centers = $centersStatement->fetchAll();
$typesStatement = $pdo->prepare("SELECT * FROM contract_types WHERE tenant_id=:tenant ORDER BY CASE category WHEN 'indefinite' THEN 1 WHEN 'temporary' THEN 2 WHEN 'training' THEN 3 ELSE 4 END,name");
$typesStatement->execute(['tenant' => $tenantId]);
$contractTypes = $typesStatement->fetchAll();
$attendanceTypesStatement = $pdo->prepare('SELECT * FROM attendance_types WHERE tenant_id=:tenant ORDER BY role COLLATE NOCASE,is_default DESC,name COLLATE NOCASE');
$attendanceTypesStatement->execute(['tenant' => $tenantId]);
$attendanceTypes = $attendanceTypesStatement->fetchAll();
$attendanceStatement = $pdo->prepare(
    "SELECT a.*, TRIM(e.nombre || ' ' || COALESCE(e.apellido,'')) AS employee_name,
            e.role AS employee_role,ft.name AS attendance_type_name,
            COALESCE(c.id,wc.id) AS pending_correction_id,
            c.id AS admin_pending_correction_id,wc.id AS worker_pending_correction_id
     FROM attendance a
     JOIN employees e ON e.id=a.employee_id AND e.tenant_id=a.tenant_id
     LEFT JOIN attendance_types ft ON ft.id=a.attendance_type_id AND ft.tenant_id=a.tenant_id
     LEFT JOIN attendance_corrections c
       ON c.attendance_id=a.id AND c.tenant_id=a.tenant_id AND c.status='pending'
     LEFT JOIN worker_attendance_corrections wc
       ON wc.attendance_id=a.id AND wc.tenant_id=a.tenant_id AND wc.status='pending'
     WHERE a.tenant_id=:tenant
     ORDER BY a.work_date DESC,a.start_time DESC"
);
$attendanceStatement->execute(['tenant' => $tenantId]);
$attendance = $attendanceStatement->fetchAll();
$workerCorrectionStatement = $pdo->prepare(
    "SELECT c.*,TRIM(e.nombre || ' ' || COALESCE(e.apellido,'')) AS employee_name,
            u.name AS worker_user_name,ta.full_name AS admin_name,
            original_ft.name AS original_attendance_type_name,
            proposed_ft.name AS proposed_attendance_type_name
     FROM worker_attendance_corrections c
     JOIN employees e ON e.id=c.employee_id AND e.tenant_id=c.tenant_id
     JOIN users u ON u.id=c.requested_by_user_id AND u.tenant_id=c.tenant_id
     LEFT JOIN tenant_admins ta ON ta.id=c.responded_by_admin_id AND ta.tenant_id=c.tenant_id
     LEFT JOIN attendance_types original_ft ON original_ft.id=c.original_attendance_type_id AND original_ft.tenant_id=c.tenant_id
     LEFT JOIN attendance_types proposed_ft ON proposed_ft.id=c.proposed_attendance_type_id AND proposed_ft.tenant_id=c.tenant_id
     WHERE c.tenant_id=:tenant
     ORDER BY CASE c.status WHEN 'pending' THEN 0 ELSE 1 END,c.requested_at DESC
     LIMIT 500"
);
$workerCorrectionStatement->execute(['tenant' => $tenantId]);
$workerCorrections = $workerCorrectionStatement->fetchAll();
$selectedAttendance = null;
$selectedAttendanceId = (int)($_GET['record_id'] ?? 0);
foreach ($attendance as $candidate) {
    if ((int)$candidate['id'] === $selectedAttendanceId) {
        $selectedAttendance = $candidate;
        break;
    }
}
if ($selectedAttendanceId > 0 && $selectedAttendance === null) {
    $selectedStatement = $pdo->prepare(
        "SELECT a.*, TRIM(e.nombre || ' ' || COALESCE(e.apellido,'')) AS employee_name,
                e.role AS employee_role,ft.name AS attendance_type_name,
                COALESCE(c.id,wc.id) AS pending_correction_id,
                c.id AS admin_pending_correction_id,wc.id AS worker_pending_correction_id
         FROM attendance a
         JOIN employees e ON e.id=a.employee_id AND e.tenant_id=a.tenant_id
         LEFT JOIN attendance_types ft ON ft.id=a.attendance_type_id AND ft.tenant_id=a.tenant_id
         LEFT JOIN attendance_corrections c
           ON c.attendance_id=a.id AND c.tenant_id=a.tenant_id AND c.status='pending'
         LEFT JOIN worker_attendance_corrections wc
           ON wc.attendance_id=a.id AND wc.tenant_id=a.tenant_id AND wc.status='pending'
         WHERE a.id=:id AND a.tenant_id=:tenant LIMIT 1"
    );
    $selectedStatement->execute(['id' => $selectedAttendanceId, 'tenant' => $tenantId]);
    $selectedAttendance = $selectedStatement->fetch() ?: null;
}
$attendanceCorrections = [];
$attendanceAuditLog = [];
$attendanceIntegrity = ['valid' => true, 'checked_events' => 0, 'checked_records' => 0, 'last_hash' => '', 'errors' => []];
if ($group === 'tables' && $view === 'attendance') {
    $correctionStatement = $pdo->prepare(
        "SELECT c.*, TRIM(e.nombre || ' ' || COALESCE(e.apellido,'')) AS employee_name,
                ta.full_name AS admin_name, u.name AS worker_user_name,
                original_ft.name AS original_attendance_type_name,
                proposed_ft.name AS proposed_attendance_type_name
         FROM attendance_corrections c
         JOIN employees e ON e.id=c.employee_id AND e.tenant_id=c.tenant_id
         JOIN tenant_admins ta ON ta.id=c.requested_by_admin_id AND ta.tenant_id=c.tenant_id
         LEFT JOIN users u ON u.id=c.responded_by_user_id AND u.tenant_id=c.tenant_id
         LEFT JOIN attendance_types original_ft ON original_ft.id=c.original_attendance_type_id AND original_ft.tenant_id=c.tenant_id
         LEFT JOIN attendance_types proposed_ft ON proposed_ft.id=c.proposed_attendance_type_id AND proposed_ft.tenant_id=c.tenant_id
         WHERE c.tenant_id=:tenant
         ORDER BY CASE c.status WHEN 'pending' THEN 0 ELSE 1 END,c.requested_at DESC
         LIMIT 300"
    );
    $correctionStatement->execute(['tenant' => $tenantId]);
    $attendanceCorrections = $correctionStatement->fetchAll();
    $auditStatement = $pdo->prepare(
        "SELECT l.*, TRIM(e.nombre || ' ' || COALESCE(e.apellido,'')) AS employee_name
         FROM attendance_audit_log l
         JOIN attendance a ON a.id=l.attendance_id AND a.tenant_id=l.tenant_id
         JOIN employees e ON e.id=a.employee_id AND e.tenant_id=a.tenant_id
         WHERE l.tenant_id=:tenant
         ORDER BY l.sequence DESC LIMIT 500"
    );
    $auditStatement->execute(['tenant' => $tenantId]);
    $attendanceAuditLog = $auditStatement->fetchAll();
    $attendanceIntegrity = AttendanceAudit::verifyTenant($pdo, $tenantId);
}
$scheduleStatement = $pdo->prepare("SELECT s.*, TRIM(e.nombre || ' ' || COALESCE(e.apellido,'')) AS employee_name FROM schedules s JOIN employees e ON e.id=s.employee_id AND e.tenant_id=s.tenant_id WHERE s.tenant_id=:tenant ORDER BY e.apellido,e.nombre,s.weekday,s.start_time");
$scheduleStatement->execute(['tenant' => $tenantId]);
$schedules = $scheduleStatement->fetchAll();
$absenceStatement = $pdo->prepare("SELECT a.*, TRIM(e.nombre || ' ' || COALESCE(e.apellido,'')) AS employee_name FROM absences a JOIN employees e ON e.id=a.employee_id AND e.tenant_id=a.tenant_id WHERE a.tenant_id=:tenant ORDER BY a.requested_at DESC");
$absenceStatement->execute(['tenant' => $tenantId]);
$absences = $absenceStatement->fetchAll();
$sickLeaveStatement = $pdo->prepare("SELECT sl.*, TRIM(e.nombre || ' ' || COALESCE(e.apellido,'')) AS employee_name FROM sick_leaves sl JOIN employees e ON e.id=sl.employee_id AND e.tenant_id=sl.tenant_id WHERE sl.tenant_id=:tenant ORDER BY sl.start_date DESC,sl.id DESC");
$sickLeaveStatement->execute(['tenant'=>$tenantId]);
$sickLeaves = $sickLeaveStatement->fetchAll();
$userStatement = $pdo->prepare("SELECT u.*, TRIM(e.nombre || ' ' || COALESCE(e.apellido,'')) AS employee_name FROM users u LEFT JOIN employees e ON e.id=u.employee_id AND e.tenant_id=u.tenant_id WHERE u.tenant_id=:tenant ORDER BY u.name");
$userStatement->execute(['tenant' => $tenantId]);
$users = $userStatement->fetchAll();
$metricsStatement = $pdo->prepare("SELECT (SELECT COUNT(*) FROM employees WHERE tenant_id=:t1 AND active=1) employees, (SELECT COUNT(*) FROM attendance WHERE tenant_id=:t2 AND work_date=:today) today_records, (SELECT COUNT(*) FROM attendance WHERE tenant_id=:t3 AND end_time IS NULL) open_shifts, (SELECT COUNT(*) FROM absences WHERE tenant_id=:t4 AND status='pending') pending_absences, (SELECT COUNT(*) FROM worker_attendance_corrections WHERE tenant_id=:t5 AND status='pending') pending_worker_corrections");
$metricsStatement->execute(['t1' => $tenantId, 't2' => $tenantId, 'today' => date('Y-m-d'), 't3' => $tenantId, 't4' => $tenantId, 't5' => $tenantId]);
$metrics = $metricsStatement->fetch();

$workerAttendanceEditEnabled = Settings::bool($pdo, $tenantId, Settings::WORKER_ATTENDANCE_EDIT);
$internalToastSeconds = max(1, min(120, (int)Settings::get($pdo, $tenantId, Settings::INTERNAL_TOAST_SECONDS)));
$internalToastMode = Settings::get($pdo, $tenantId, Settings::INTERNAL_TOAST_MODE);
$companyLocationEnabled = Settings::bool($pdo, $tenantId, Settings::COMPANY_LOCATION_ENABLED);
$companyIpv4 = Settings::get($pdo, $tenantId, Settings::COMPANY_IPV4);
$companyIpv6 = Settings::get($pdo, $tenantId, Settings::COMPANY_IPV6);
$emailNotificationsEnabled = Settings::bool($pdo, $tenantId, Settings::EMAIL_NOTIFICATIONS_ENABLED);
$notificationEmail = Settings::get($pdo, $tenantId, Settings::NOTIFICATION_EMAIL);
$notifyAbsenceRequest = Settings::bool($pdo, $tenantId, Settings::NOTIFY_ABSENCE_REQUEST);
$notifyOutOfScheduleClocking = Settings::bool($pdo, $tenantId, Settings::NOTIFY_OUT_OF_SCHEDULE_CLOCKING);
$internalMessageTypes = $pdo->prepare('SELECT * FROM internal_message_types WHERE tenant_id=:tenant ORDER BY name'); $internalMessageTypes->execute(['tenant'=>$tenantId]); $internalMessageTypes = $internalMessageTypes->fetchAll();
$internalMessages = $pdo->prepare("SELECT m.*,t.name type_name,t.color,t.emoji FROM internal_messages m JOIN internal_message_types t ON t.id=m.message_type_id AND t.tenant_id=m.tenant_id WHERE m.tenant_id=:tenant ORDER BY m.start_date DESC,m.id DESC"); $internalMessages->execute(['tenant'=>$tenantId]); $internalMessages=$internalMessages->fetchAll();
$applicationAuditRows = [];
if ($group === 'config' && $configPage === 'audit') {
    $applicationAuditStatement = $pdo->prepare(
        'SELECT * FROM application_audit_log
         WHERE tenant_id=:tenant
         ORDER BY id DESC LIMIT 1000'
    );
    $applicationAuditStatement->execute(['tenant' => $tenantId]);
    $applicationAuditRows = $applicationAuditStatement->fetchAll();
}

$groups = [
    'favorites' => ['icon' => '★', 'label' => 'Favoritos', 'hint' => 'Accesos guardados en este navegador'],
    'applications' => ['icon' => 'A', 'label' => 'Aplicaciones', 'hint' => 'Herramientas específicas de asistencia'],
    'tables' => ['icon' => 'T', 'label' => 'Tablas', 'hint' => 'Gestión de los datos del tenant'],
    'config' => ['icon' => '⚙', 'label' => 'Configuración', 'hint' => 'Opciones de funcionamiento y auditoría'],
    'help' => ['icon' => '?', 'label' => 'Ayuda', 'hint' => 'Documentación del módulo'],
    'logout' => ['icon' => '↻', 'label' => 'Salir', 'hint' => 'Cerrar la sesión actual'],
];
$itemsByGroup = ['favorites' => []];
foreach ($applications as $slug => $definition) {
    $itemsByGroup['applications'][] = ['name' => $slug, 'type' => 'Aplicación', 'hint' => $definition['hint'], 'href' => ja_admin_query_url(['group' => 'applications', 'app' => $slug]), 'active' => $app === $slug];
}
foreach ($tableViews as $slug => $definition) {
    $itemsByGroup['tables'][] = ['name' => $definition['title'], 'type' => 'Tabla', 'hint' => $definition['hint'], 'href' => ja_admin_query_url(['group' => 'tables', 'view' => $slug]), 'active' => $app === '' && $group === 'tables' && $view === $slug];
}
$itemsByGroup['config'] = [
    ['name' => 'Configuración general', 'type' => 'Ajustes', 'hint' => 'Permisos y comportamiento de la aplicación', 'href' => ja_admin_query_url(['group' => 'config', 'config' => 'general']), 'active' => $group === 'config' && $configPage === 'general'],
    ['name' => 'Registro de actividad', 'type' => 'Auditoría', 'hint' => 'Todas las peticiones y acciones de la aplicación', 'href' => ja_admin_query_url(['group' => 'config', 'config' => 'audit']), 'active' => $group === 'config' && $configPage === 'audit'],
];
$itemsByGroup['help'] = [
    ['name' => 'Manual de asistencia', 'type' => 'Documento', 'hint' => 'Guía de uso de la aplicación', 'href' => ja_admin_query_url(['group' => 'help']), 'active' => $group === 'help'],
    ['name' => 'Front de empleados', 'type' => 'Sistema', 'hint' => 'Abrir el portal de fichaje', 'href' => '../front/', 'active' => false],
];
$itemsByGroup['logout'] = [['name' => 'Cerrar sesión', 'type' => 'Sistema', 'hint' => 'Finalizar la sesión administrativa', 'href' => 'logout.php', 'active' => false]];

$search = trim((string)($_GET['q'] ?? ''));
$visibleItems = $itemsByGroup[$group] ?? [];
if ($search !== '') {
    $visibleItems = array_values(array_filter($visibleItems, static function (array $item) use ($search): bool {
        return stripos($item['name'] . ' ' . $item['type'] . ' ' . $item['hint'], $search) !== false;
    }));
}
$currentTitle = $app !== ''
    ? 'Aplicación: ' . $app
    : ($group === 'tables' ? $tableViews[$view]['title'] : ($group === 'config' ? ($configPage === 'audit' ? 'Registro de actividad' : 'Configuración general') : ($view === 'dashboard' ? $dashboardView['title'] : $groups[$group]['label'])));
$currentHint = $app !== ''
    ? $applications[$app]['hint']
    : ($group === 'tables' ? $tableViews[$view]['hint'] : ($group === 'config' ? ($configPage === 'audit' ? 'Trazabilidad global e inmutable de uso.' : 'Permisos y comportamiento del tenant.') : ($view === 'dashboard' ? $dashboardView['hint'] : $groups[$group]['hint'])));
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
$csrf = Csrf::token();
$weekdays = [1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo'];

View::head('Admin · ' . $tenant['name'], 'admin');
?>
<header id="topbar">
  <div id="marca"><div class="ju-logo"><img src="https://static.jocarsa.com/logos/darksalmon.svg" alt="Jocarsa"></div><div><h1>jocarsa | asistencias</h1><p>Gestión de asistencia, horarios, sesiones y datos administrativos.</p></div></div>
  <form id="search-form" method="get" action="index.php">
    <input type="hidden" name="group" value="<?= ja_h($group) ?>">
    <?php if ($app !== ''): ?><input type="hidden" name="app" value="<?= ja_h($app) ?>"><?php endif; ?>
    <?php if ($group === 'tables'): ?><input type="hidden" name="view" value="<?= ja_h($view) ?>"><?php endif; ?>
    <?php if ($group === 'config'): ?><input type="hidden" name="config" value="<?= ja_h($configPage) ?>"><?php endif; ?>
    <input id="search-input" type="search" name="q" value="<?= ja_h($search) ?>" placeholder="Buscar en el módulo actual...">
    <button class="search-btn" type="submit">Buscar</button>
    <a class="search-clear" href="<?= ja_h(ja_admin_query_url(['group' => $group, 'app' => $app ?: null, 'view' => $group === 'tables' ? $view : null, 'config' => $group === 'config' ? $configPage : null])) ?>">Limpiar</a>
  </form>
  <div id="herramientas">
    <a class="toolbtn-link" href="index.php">Inicio</a>
    <a class="toolbtn-link" href="?group=help">Ayuda</a>
  </div>
  <div id="admin-session"><span>Hola, <strong><?= ja_h($admin['name']) ?></strong></span><a href="logout.php">Cerrar sesión</a></div>
</header>

<main id="app" class="iu-admin-shell">
  <aside id="bandejas">
    <?php foreach ($groups as $key => $definition): ?>
      <a class="<?= $group === $key ? 'activo' : '' ?>" href="<?= $key === 'logout' ? 'logout.php' : '?group=' . ja_h($key) ?>">
        <span class="folder-line"><span><?= ja_h($definition['icon']) ?> <?= ja_h($definition['label']) ?></span><span class="folder-count" data-group-count="<?= ja_h($key) ?>"><?= count($itemsByGroup[$key] ?? []) ?></span></span>
      </a>
    <?php endforeach; ?>
  </aside>

  <section id="mensajes">
    <div class="ju-column-title"><strong><?= ja_h($groups[$group]['label']) ?></strong><span><?= ja_h($groups[$group]['hint']) ?></span></div>
    <div id="navFavsList" <?= $group === 'favorites' ? '' : 'hidden' ?>></div>
    <?php if ($group !== 'favorites'): ?>
      <?php if (!$visibleItems): ?><article class="activo"><div class="linea1"><strong>Sin elementos</strong></div><div class="linea2"><span>No hay resultados en esta categoría.</span></div></article><?php endif; ?>
      <?php foreach ($visibleItems as $item): ?>
        <article class="<?= !empty($item['active']) ? 'activo' : '' ?>" data-fav-row>
          <a class="ju-center-link" href="<?= ja_h($item['href']) ?>">
            <div class="linea1"><strong><?= ja_h($item['name']) ?></strong><span><?= ja_h($item['type']) ?></span></div>
            <div class="linea2"><span><?= ja_h($item['hint']) ?></span></div>
          </a>
          <button class="fav-star" type="button" data-fav-href="<?= ja_h($item['href']) ?>" data-fav-title="<?= ja_h($item['name']) ?>" data-fav-type="<?= ja_h($item['type']) ?>" aria-label="Marcar como favorito">☆</button>
        </article>
      <?php endforeach; ?>
    <?php endif; ?>
  </section>

  <section id="contenido">
    <div id="panel">
      <section class="email ju-page-heading">
        <div class="linea1"><strong><?= ja_h($currentTitle) ?></strong><span class="ju-pill"><?= ja_h($groups[$group]['label']) ?></span></div>
        <p><?= ja_h($currentHint) ?> Todos los datos pertenecen a <?= ja_h($tenant['name']) ?>.</p>
      </section>
      <?php if (is_array($flash)): ?><div class="notice <?= ja_h($flash['type'] ?? '') ?>"><?= ja_h($flash['message'] ?? '') ?></div><?php endif; ?>

      <section class="ju-legacy-content">
      <?php if ($app !== ''): ?>
        <?php define('JA_ADMIN_APPLICATION', true); require __DIR__ . '/applications/' . $app . '.php'; ?>
      <?php elseif ($group === 'config'): ?>
        <?php if ($configPage === 'general'): ?>
          <article class="card settings-card">
            <div class="ju-table-caption"><div><h3>Permisos de edición</h3><p>Controla qué operaciones puede iniciar la persona trabajadora desde su portal.</p></div><span class="badge <?= $workerAttendanceEditEnabled ? 'ok' : 'warning' ?>"><?= $workerAttendanceEditEnabled ? 'Habilitado' : 'Deshabilitado' ?></span></div>
            <form method="post" class="stack">
              <input type="hidden" name="csrf" value="<?= ja_h($csrf) ?>">
              <input type="hidden" name="action" value="save_settings">
              <label class="setting-toggle">
                <input type="checkbox" name="worker_attendance_edit_enabled" value="1" <?= $workerAttendanceEditEnabled ? 'checked' : '' ?>>
                <span><strong>Permitir que el trabajador solicite cambios en sus jornadas</strong><small>El trabajador solo propone. La jornada no cambia hasta que un administrador la aprueba.</small></span>
              </label>
              <label>Duración de avisos emergentes (segundos)<input type="number" name="internal_toast_seconds" min="1" max="120" value="<?= (int)$internalToastSeconds ?>"></label>
              <label>Presentación de varios avisos<select name="internal_toast_mode"><option value="stack" <?= $internalToastMode === 'stack' ? 'selected' : '' ?>>Apilados simultáneamente</option><option value="sequence" <?= $internalToastMode === 'sequence' ? 'selected' : '' ?>>Uno después de otro</option></select></label>
              <div class="settings-section-divider">
                <div><h3>Ubicación de la empresa por IP</h3><p>Compara la dirección pública de la conexión del trabajador con las direcciones de la empresa. Es un indicador informativo: no bloquea el acceso ni el fichaje.</p></div>
                <span class="badge <?= $companyLocationEnabled ? 'ok' : 'warning' ?>"><?= $companyLocationEnabled ? 'Activa' : 'Desactivada' ?></span>
              </div>
              <label class="setting-toggle">
                <input type="checkbox" name="company_location_enabled" value="1" <?= $companyLocationEnabled ? 'checked' : '' ?>>
                <span><strong>Mostrar si el fichaje se realiza desde la empresa</strong><small>Al activarlo, el portal mostrará “Fichaje desde la empresa” o “Fichaje fuera de la empresa”.</small></span>
              </label>
              <div class="grid-2">
                <label>IPv4 de la empresa<input type="text" name="company_ipv4" value="<?= ja_h($companyIpv4) ?>" placeholder="203.0.113.25" inputmode="decimal" autocomplete="off"><small>Dirección IPv4 pública exacta de la conexión de la empresa.</small></label>
                <label>IPv6 de la empresa<input type="text" name="company_ipv6" value="<?= ja_h($companyIpv6) ?>" placeholder="2001:db8::25" inputmode="text" autocomplete="off"><small>Dirección IPv6 pública exacta. Puedes configurar IPv4, IPv6 o ambas.</small></label>
              </div>
              <div class="settings-section-divider">
                <div><h3>Notificaciones por email</h3><p>Envía avisos de los eventos seleccionados a una dirección propia de esta empresa.</p></div>
                <span class="badge <?= $emailNotificationsEnabled ? 'ok' : 'warning' ?>"><?= $emailNotificationsEnabled ? 'Activas' : 'Desactivadas' ?></span>
              </div>
              <label class="setting-toggle">
                <input type="checkbox" name="email_notifications_enabled" value="1" <?= $emailNotificationsEnabled ? 'checked' : '' ?>>
                <span><strong>Activar el sistema de notificaciones por email</strong><small>El interruptor general permite detener todos los envíos sin perder la selección de eventos.</small></span>
              </label>
              <label>Email receptor de notificaciones<input type="email" name="notification_email" value="<?= ja_h($notificationEmail) ?>" placeholder="rrhh@empresa.com" autocomplete="email"><small>La dirección se guarda de forma independiente para <?= ja_h($tenant['name']) ?>.</small></label>
              <div class="notification-event-grid">
                <div class="ju-table-caption"><div><h3>Eventos que generan una notificación</h3><p>Cada evento se puede activar de forma independiente.</p></div><span class="badge">2 eventos disponibles</span></div>
                <div class="table-wrap"><table><thead><tr><th>Evento</th><th>Origen</th><th>Destinatario</th><th>Notificar</th></tr></thead><tbody>
                  <tr><td><strong>Nueva solicitud de ausencia</strong><br><small>Se avisa en cuanto la persona trabajadora envía la petición.</small></td><td>Portal del personal</td><td><?= ja_h($notificationEmail ?: 'Email configurado arriba') ?></td><td><label class="notification-event-check"><input type="checkbox" name="notify_absence_request" value="1" <?= $notifyAbsenceRequest ? 'checked' : '' ?>><span>Activo</span></label></td></tr>
                  <tr><td><strong>Fichaje fuera del horario establecido</strong><br><small>Se avisa al iniciar o finalizar una jornada fuera de cualquier franja vigente.</small></td><td>Fichaje del personal</td><td><?= ja_h($notificationEmail ?: 'Email configurado arriba') ?></td><td><label class="notification-event-check"><input type="checkbox" name="notify_out_of_schedule_clocking" value="1" <?= $notifyOutOfScheduleClocking ? 'checked' : '' ?>><span>Activo</span></label></td></tr>
                </tbody></table></div>
              </div>
              <div><button class="button primary" type="submit">Guardar configuración</button></div>
            </form>
          </article>
          <article class="card integrity-card integrity-ok"><div class="integrity-summary"><div><p class="eyebrow">Auditoría global</p><h3>Registro siempre activo</h3><p>Las páginas, acciones, accesos, errores y respuestas PHP se guardan automáticamente en SQLite. No se almacenan contraseñas ni cuerpos de formulario.</p></div><span class="integrity-seal">✓</span></div><p><a class="button" href="?group=config&amp;config=audit">Abrir registro de actividad</a></p></article>
        <?php else: ?>
          <article class="card">
            <div class="ju-table-caption"><div><h3>Registro global de actividad</h3><p>Últimos 1.000 eventos del tenant. El registro es append-only: SQLite bloquea su modificación y borrado.</p></div><span class="badge ok"><?= count($applicationAuditRows) ?> eventos</span></div>
            <?php if (!$applicationAuditRows): ?><div class="empty">El registro se llenará automáticamente con las siguientes peticiones.</div><?php else: ?>
              <div class="table-wrap audit-log-wrap"><table><thead><tr><th>Fecha</th><th>Área</th><th>Actor</th><th>Petición</th><th>Acción</th><th>Resultado</th><th>IP</th><th>Duración</th></tr></thead><tbody><?php foreach ($applicationAuditRows as $event): $outcome = (string)$event['outcome']; ?><tr><td><?= ja_h($event['occurred_at']) ?></td><td><?= ja_h($event['area']) ?></td><td><strong><?= ja_h($event['actor_label']) ?></strong><br><small><?= ja_h($event['actor_type']) ?><?= $event['actor_id'] ? ' #' . (int)$event['actor_id'] : '' ?></small></td><td><strong><?= ja_h($event['method']) ?></strong> <?= ja_h($event['request_uri']) ?><br><small>HTTP <?= (int)$event['response_status'] ?></small></td><td><?= ja_h($event['action_name'] ?: 'Vista') ?></td><td><span class="badge <?= $outcome === 'success' ? 'ok' : ($outcome === 'failure' ? 'danger' : 'warning') ?>"><?= ja_h($outcome) ?></span></td><td><?= ja_h($event['ip_address'] ?: '—') ?></td><td><?= (int)$event['duration_ms'] ?> ms</td></tr><?php endforeach; ?></tbody></table></div>
            <?php endif; ?>
          </article>
        <?php endif; ?>
      <?php elseif ($group === 'help'): ?>
        <article class="card"><h3>Ayuda de jocarsa | asistencias</h3><p>Usa la primera columna para cambiar de categoría, la segunda para seleccionar una aplicación o tabla, y este panel para trabajar con sus datos.</p><p>La organización se obtiene del dominio actual; los datos de otros tenants nunca se incluyen en estas consultas.</p></article>
      <?php elseif ($group === 'favorites'): ?>
        <article class="card"><h3>Favoritos</h3><p>Marca con la estrella cualquier aplicación o tabla para guardar aquí un acceso rápido en este navegador.</p></article>
      <?php elseif ($group === 'logout'): ?>
        <article class="card"><h3>Salir</h3><p><a class="button primary" href="logout.php">Cerrar la sesión actual</a></p></article>
      <?php elseif ($view === 'dashboard'): ?>
        <div class="metrics"><div class="metric"><span>Plantilla activa</span><strong><?= (int)$metrics['employees'] ?></strong></div><div class="metric"><span>Registros hoy</span><strong><?= (int)$metrics['today_records'] ?></strong></div><div class="metric"><span>Jornadas abiertas</span><strong><?= (int)$metrics['open_shifts'] ?></strong></div><div class="metric"><span>Ausencias pendientes</span><strong><?= (int)$metrics['pending_absences'] ?></strong></div><div class="metric"><span>Cambios del personal</span><strong><?= (int)$metrics['pending_worker_corrections'] ?></strong></div></div>
        <article class="card"><div class="ju-table-caption"><div><h3>Actividad reciente</h3><p>Últimos fichajes registrados.</p></div><a class="button small" href="?group=applications&app=estadisticas_empleados">Abrir estadísticas</a></div><?php if (!$attendance): ?><div class="empty">Todavía no hay jornadas registradas.</div><?php else: ?><div class="table-wrap"><table><thead><tr><th>Persona</th><th>Tipo</th><th>Fecha</th><th>Entrada</th><th>Salida</th></tr></thead><tbody><?php foreach (array_slice($attendance, 0, 12) as $record): ?><tr><td><?= ja_h($record['employee_name']) ?></td><td><?= ja_h($record['attendance_type_name'] ?: 'Sin tipo') ?></td><td><?= ja_h($record['work_date']) ?></td><td><?= ja_h(ja_admin_short_time($record['start_time'])) ?></td><td><?= $record['end_time'] ? ja_h(ja_admin_short_time($record['end_time'])) : '<span class="badge warning">Abierta</span>' ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></article>
      <?php elseif ($view === 'employees'): ?>
        <?php $employeeTableState = ja_admin_server_table($employees, 'employees'); $employeeRows = $employeeTableState['rows']; ?>
        <article class="card"><h3><?= $selectedEmployee ? 'Editar persona' : 'Nueva persona' ?></h3><form method="post" class="grid-2"><input type="hidden" name="csrf" value="<?= ja_h($csrf) ?>"><input type="hidden" name="action" value="save_employee"><input type="hidden" name="employee_id" value="<?= (int)($selectedEmployee['id'] ?? 0) ?>"><label>Nombre<input name="nombre" value="<?= ja_h($selectedEmployee['nombre'] ?? '') ?>" required></label><label>Apellidos<input name="apellido" value="<?= ja_h($selectedEmployee['apellido'] ?? '') ?>"></label><label>Departamento<input name="departamento" value="<?= ja_h($selectedEmployee['departamento'] ?? '') ?>"></label><label>Rol<input name="role" value="<?= ja_h($selectedEmployee['role'] ?? '') ?>"></label><label>Centro<select name="work_center_id"><option value="">Sin asignar</option><?php foreach ($centers as $center): ?><option value="<?= (int)$center['id'] ?>" <?= (int)($selectedEmployee['work_center_id'] ?? 0) === (int)$center['id'] ? 'selected' : '' ?>><?= ja_h($center['name']) ?></option><?php endforeach; ?></select></label><label>Contrato<select name="contract_type_id"><option value="">Sin asignar</option><?php foreach ($contractTypes as $type): ?><option value="<?= (int)$type['id'] ?>" <?= (int)($selectedEmployee['contract_type_id'] ?? 0) === (int)$type['id'] ? 'selected' : '' ?>><?= ja_h($type['name']) ?></option><?php endforeach; ?></select></label><div class="actions"><button class="button primary" type="submit">Guardar persona</button><?php if ($selectedEmployee): ?><a class="button" href="?group=tables&view=employees">Nueva</a><?php endif; ?></div></form></article>
        <?php ja_admin_render_table_controls($employeeTableState); ?><div class="table-wrap"><table><thead><tr><th>Persona</th><th>Departamento</th><th>Centro</th><th>Rol</th><th>Estado</th><th></th></tr></thead><tbody><?php foreach ($employeeRows as $employee): ?><tr><td><a href="?group=tables&view=employees&employee_id=<?= (int)$employee['id'] ?>"><strong><?= ja_h(ja_admin_employee_name($employee)) ?></strong></a></td><td><?= ja_h($employee['departamento']) ?></td><td><?= ja_h($employee['center_name']) ?></td><td><?= ja_h($employee['role']) ?></td><td><span class="badge <?= $employee['active'] ? 'ok' : 'warning' ?>"><?= $employee['active'] ? 'Activo' : 'Inactivo' ?></span></td><td><form method="post"><input type="hidden" name="csrf" value="<?= ja_h($csrf) ?>"><input type="hidden" name="action" value="toggle_employee"><input type="hidden" name="employee_id" value="<?= (int)$employee['id'] ?>"><button class="button small" type="submit"><?= $employee['active'] ? 'Desactivar' : 'Activar' ?></button></form></td></tr><?php endforeach; ?></tbody></table></div>
      <?php elseif ($view === 'attendance'): ?>
        <?php $attendanceTableState = ja_admin_server_table($attendance, 'attendance'); $attendanceRows = $attendanceTableState['rows']; ?>
        <?php
        $correctionStatus = ['pending' => 'Pendiente', 'accepted' => 'Aceptada', 'rejected' => 'Rechazada'];
        $auditEventLabels = [
            'baseline' => 'Anclaje inicial', 'clock_in' => 'Inicio de jornada', 'clock_out' => 'Fin de jornada',
            'admin_created' => 'Registro manual', 'imported' => 'Registro importado', 'correction_proposed' => 'Corrección propuesta',
            'correction_accepted' => 'Corrección aceptada', 'correction_rejected' => 'Corrección rechazada',
        ];
        ?>
        <article class="card attendance-editor">
          <div class="ju-table-caption"><div><h3><?= $selectedAttendance ? 'Proponer corrección' : 'Registrar jornada manual' ?></h3><p><?= $selectedAttendance ? 'El registro no cambiará hasta que la persona trabajadora lo acepte desde el front.' : 'Los registros manuales se incorporan inmediatamente, identificados como creados por administración.' ?></p></div><?php if ($selectedAttendance): ?><a class="button small" href="?group=tables&amp;view=attendance">Cancelar edición</a><?php endif; ?></div>
          <?php if ($selectedAttendance && !empty($selectedAttendance['pending_correction_id'])): ?>
            <div class="notice warning">Esta jornada ya tiene una corrección pendiente. Resuelve primero la solicitud desde la sección que corresponda.</div>
          <?php else: ?>
            <form method="post" class="grid-3">
              <input type="hidden" name="csrf" value="<?= ja_h($csrf) ?>">
              <input type="hidden" name="action" value="save_attendance">
              <input type="hidden" name="record_id" value="<?= (int)($selectedAttendance['id'] ?? 0) ?>">
              <?php if ($selectedAttendance): ?>
                <input type="hidden" name="employee_id" value="<?= (int)$selectedAttendance['employee_id'] ?>">
                <label>Persona<input value="<?= ja_h($selectedAttendance['employee_name']) ?>" readonly></label>
              <?php else: ?>
                <label>Persona<select name="employee_id" data-attendance-employee required><?php foreach ($employees as $employee): ?><option value="<?= (int)$employee['id'] ?>" data-role="<?= ja_h($employee['role'] ?? '') ?>" <?= (int)($_GET['employee_id'] ?? 0) === (int)$employee['id'] ? 'selected' : '' ?>><?= ja_h(ja_admin_employee_name($employee)) ?></option><?php endforeach; ?></select></label>
              <?php endif; ?>
              <label>Tipo de fichaje<select name="attendance_type_id" data-attendance-type data-existing-record="<?= $selectedAttendance ? '1' : '0' ?>" data-employee-role="<?= ja_h($selectedAttendance['employee_role'] ?? '') ?>"><option value="">Sin tipo</option><?php foreach ($attendanceTypes as $type): ?><?php if (empty($type['active']) && (int)($selectedAttendance['attendance_type_id'] ?? 0) !== (int)$type['id']) continue; ?><?php if ($selectedAttendance && strcasecmp(trim((string)$type['role']), trim((string)$selectedAttendance['employee_role'])) !== 0 && (int)($selectedAttendance['attendance_type_id'] ?? 0) !== (int)$type['id']) continue; ?><option value="<?= (int)$type['id'] ?>" data-role="<?= ja_h($type['role']) ?>" data-default="<?= !empty($type['is_default']) ? '1' : '0' ?>" <?= (int)($selectedAttendance['attendance_type_id'] ?? 0) === (int)$type['id'] ? 'selected' : '' ?>><?= ja_h($type['role'] . ' · ' . $type['name']) ?></option><?php endforeach; ?></select><small>Obligatorio en registros nuevos si el rol tiene tipos activos.</small></label>
              <?php $newAttendanceDate = (string)($_GET['date'] ?? ''); if (!ja_admin_valid_date($newAttendanceDate)) $newAttendanceDate = date('Y-m-d'); ?>
              <label>Fecha<input type="date" name="work_date" value="<?= ja_h($selectedAttendance['work_date'] ?? $newAttendanceDate) ?>" required></label>
              <label>Entrada<input type="time" step="1" name="start_time" value="<?= ja_h(isset($selectedAttendance['start_time']) ? substr((string)$selectedAttendance['start_time'], 0, 8) : '') ?>" required></label>
              <label>Salida<input type="time" step="1" name="end_time" value="<?= ja_h(isset($selectedAttendance['end_time']) ? substr((string)$selectedAttendance['end_time'], 0, 8) : '') ?>"></label>
              <label class="form-span-full">Motivo <?= $selectedAttendance ? 'de la corrección' : 'del registro manual' ?><textarea name="reason" maxlength="1000" required placeholder="Explica por qué se realiza esta operación."></textarea></label>
              <div class="actions form-span-full"><button class="button primary" type="submit"><?= $selectedAttendance ? 'Enviar al trabajador' : 'Registrar y auditar' ?></button></div>
            </form>
          <?php endif; ?>
        </article>

        <?php ja_admin_render_table_controls($attendanceTableState); ?><div class="table-wrap"><table><thead><tr><th>Persona</th><th>Tipo</th><th>Fecha</th><th>Entrada</th><th>Salida</th><th>Corrección</th><th></th></tr></thead><tbody><?php foreach ($attendanceRows as $record): ?><tr><td><?= ja_h($record['employee_name']) ?></td><td><?= ja_h($record['attendance_type_name'] ?: 'Sin tipo') ?></td><td><?= ja_h($record['work_date']) ?></td><td><?= ja_h(ja_admin_short_time($record['start_time'])) ?></td><td><?= $record['end_time'] ? ja_h(ja_admin_short_time($record['end_time'])) : '<span class="badge warning">Abierta</span>' ?></td><td><?= $record['pending_correction_id'] ? '<span class="badge warning">Pendiente</span>' : '<span class="badge ok">Sin pendientes</span>' ?></td><td><?php if ($record['worker_pending_correction_id']): ?><a class="button small" href="?group=applications&amp;app=solicitudes_personal">Revisar solicitud</a><?php elseif ($record['admin_pending_correction_id']): ?><span class="muted">Esperando al trabajador</span><?php else: ?><a class="button small" href="?group=tables&amp;view=attendance&amp;record_id=<?= (int)$record['id'] ?>">Editar</a><?php endif; ?></td></tr><?php endforeach; ?></tbody></table></div>

        <article class="card">
          <div class="ju-table-caption"><div><h3>Solicitudes de corrección</h3><p>Histórico bilateral: propuesta de empresa y decisión del trabajador.</p></div><span class="badge warning"><?= count(array_filter($attendanceCorrections, static fn(array $item): bool => $item['status'] === 'pending')) ?> pendientes</span></div>
          <?php if (!$attendanceCorrections): ?><div class="empty">Todavía no se ha propuesto ninguna corrección.</div><?php else: ?><div class="table-wrap"><table><thead><tr><th>Persona</th><th>Propuesta</th><th>Antes</th><th>Después</th><th>Motivo</th><th>Estado</th><th>Huellas</th></tr></thead><tbody><?php foreach ($attendanceCorrections as $correction): $status = (string)$correction['status']; ?><tr><td><strong><?= ja_h($correction['employee_name']) ?></strong><br><small><?= ja_h($correction['requested_at']) ?></small></td><td><?= ja_h($correction['admin_name']) ?></td><td><?= ja_h($correction['original_work_date']) ?><br><?= ja_h(ja_admin_short_time($correction['original_start_time'])) ?> — <?= ja_h(ja_admin_short_time($correction['original_end_time'])) ?><br><small><?= ja_h($correction['original_attendance_type_name'] ?: 'Sin tipo') ?></small></td><td><?= ja_h($correction['proposed_work_date']) ?><br><?= ja_h(ja_admin_short_time($correction['proposed_start_time'])) ?> — <?= $correction['proposed_end_time'] ? ja_h(ja_admin_short_time($correction['proposed_end_time'])) : 'Abierta' ?><br><small><?= ja_h($correction['proposed_attendance_type_name'] ?: 'Sin tipo') ?></small></td><td class="wrap"><?= ja_h($correction['reason']) ?><?php if ($correction['worker_comment']): ?><br><small>Respuesta: <?= ja_h($correction['worker_comment']) ?></small><?php endif; ?></td><td><span class="badge <?= $status === 'accepted' ? 'ok' : ($status === 'rejected' ? 'danger' : 'warning') ?>"><?= ja_h($correctionStatus[$status] ?? $status) ?></span><?php if ($correction['responded_at']): ?><br><small><?= ja_h($correction['responded_at']) ?></small><?php endif; ?></td><td><span class="audit-hash" title="Huella de propuesta">P <?= ja_h($correction['proposal_event_hash']) ?></span><?php if ($correction['decision_event_hash']): ?><span class="audit-hash" title="Huella de decisión">D <?= ja_h($correction['decision_event_hash']) ?></span><?php endif; ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?>
        </article>

        <article class="card integrity-card <?= $attendanceIntegrity['valid'] ? 'integrity-ok' : 'integrity-danger' ?>">
          <div class="integrity-summary"><div><p class="eyebrow">Integridad HMAC-SHA-256</p><h3><?= $attendanceIntegrity['valid'] ? 'Cadena íntegra' : 'Alerta: cadena no válida' ?></h3><p>Se han verificado <?= (int)$attendanceIntegrity['checked_events'] ?> eventos y <?= (int)$attendanceIntegrity['checked_records'] ?> jornadas. La huella autentica la trazabilidad; no sustituye una firma electrónica cualificada.</p></div><span class="integrity-seal"><?= $attendanceIntegrity['valid'] ? '✓' : '!' ?></span></div>
          <span class="audit-hash audit-hash-head" title="Última huella autenticada"><?= ja_h($attendanceIntegrity['last_hash']) ?></span>
          <?php if (!$attendanceIntegrity['valid']): ?><ul class="integrity-errors"><?php foreach ($attendanceIntegrity['errors'] as $integrityError): ?><li><?= ja_h($integrityError) ?></li><?php endforeach; ?></ul><?php endif; ?>
        </article>

        <article class="card"><div class="ju-table-caption"><div><h3>Registro inmutable de cambios</h3><p>Los eventos están encadenados: cada huella incorpora la del evento anterior.</p></div><span class="badge ok">Append-only</span></div><?php if (!$attendanceAuditLog): ?><div class="empty">No hay eventos.</div><?php else: ?><div class="table-wrap audit-log-wrap"><table><thead><tr><th>#</th><th>Fecha del evento</th><th>Persona</th><th>Acción</th><th>Actor</th><th>Huella SHA-256</th></tr></thead><tbody><?php foreach ($attendanceAuditLog as $event): ?><tr><td><?= (int)$event['sequence'] ?></td><td><?= ja_h($event['event_at']) ?></td><td><?= ja_h($event['employee_name']) ?></td><td><?= ja_h($auditEventLabels[$event['event_type']] ?? $event['event_type']) ?></td><td><?= ja_h($event['actor_label']) ?> <small>(<?= ja_h($event['actor_type']) ?>)</small></td><td><span class="audit-hash"><?= ja_h($event['event_hash']) ?></span></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></article>
      <?php elseif ($view === 'schedules'): ?>
        <?php $scheduleTableState = ja_admin_server_table($schedules, 'schedules'); $scheduleRows = $scheduleTableState['rows']; ?>
        <article class="card"><div class="ju-table-caption"><div><h3>Añadir franja horaria</h3><p>También puedes usar el editor visual de la aplicación Horario semanal.</p></div><a class="button small" href="?group=applications&app=horario_semanal">Editor visual</a></div><form method="post" class="grid-3"><input type="hidden" name="csrf" value="<?= ja_h($csrf) ?>"><input type="hidden" name="action" value="save_schedule"><label>Persona<select name="employee_id" required><?php foreach ($employees as $employee): ?><option value="<?= (int)$employee['id'] ?>"><?= ja_h(ja_admin_employee_name($employee)) ?></option><?php endforeach; ?></select></label><label>Día<select name="weekday"><?php foreach ($weekdays as $number => $day): ?><option value="<?= $number ?>"><?= ja_h($day) ?></option><?php endforeach; ?></select></label><label>Desde<input type="date" name="valid_from"></label><label>Hasta<input type="date" name="valid_until"></label><label>Entrada<input type="time" name="start_time" required></label><label>Salida<input type="time" name="end_time" required></label><div><button class="button primary" type="submit">Añadir franja</button></div></form></article>
        <?php ja_admin_render_table_controls($scheduleTableState); ?><div class="table-wrap"><table><thead><tr><th>Persona</th><th>Día</th><th>Vigencia</th><th>Horario</th><th></th></tr></thead><tbody><?php foreach ($scheduleRows as $schedule): ?><tr><td><?= ja_h($schedule['employee_name']) ?></td><td><?= ja_h($weekdays[(int)$schedule['weekday']] ?? $schedule['weekday']) ?></td><td><?= ja_h($schedule['valid_from'] ?: 'Siempre') ?> — <?= ja_h($schedule['valid_until'] ?: 'Sin fin') ?></td><td><?= ja_h(ja_admin_short_time($schedule['start_time'])) ?> — <?= ja_h(ja_admin_short_time($schedule['end_time'])) ?></td><td><form method="post"><input type="hidden" name="csrf" value="<?= ja_h($csrf) ?>"><input type="hidden" name="action" value="delete_schedule"><input type="hidden" name="schedule_id" value="<?= (int)$schedule['id'] ?>"><button class="button danger small" data-confirm="¿Eliminar esta franja?" type="submit">Eliminar</button></form></td></tr><?php endforeach; ?></tbody></table></div>
      <?php elseif ($view === 'sick_leaves'): ?>
        <?php
        $editSickLeave = null;
        $editId = (int)($_GET['edit'] ?? 0);
        if ($editId > 0) { foreach ($sickLeaves as $row) { if ((int)$row['id'] === $editId) { $editSickLeave = $row; break; } } }
        $sickLeaveTableState = ja_admin_server_table($sickLeaves, 'sick_leaves');
        $sickLeaveRows = $sickLeaveTableState['rows'];
        ?>
        <div class="page-head"><div><p class="eyebrow">Gestión administrativa</p><h2>Bajas</h2><p>Las bajas son registradas directamente por administración y no requieren confirmación de la persona trabajadora.</p></div></div>
        <article class="card"><h3><?= $editSickLeave ? 'Editar baja' : 'Nueva baja' ?></h3>
          <form method="post" enctype="multipart/form-data" class="grid-2">
            <input type="hidden" name="csrf" value="<?= ja_h($csrf) ?>"><input type="hidden" name="action" value="save_sick_leave"><input type="hidden" name="record_id" value="<?= (int)($editSickLeave['id'] ?? 0) ?>">
            <label>Empleado<select name="employee_id" required><option value="">Selecciona una persona</option><?php foreach ($employees as $employee): ?><?php if (empty($employee['active']) && (int)($editSickLeave['employee_id'] ?? 0) !== (int)$employee['id']) continue; ?><option value="<?= (int)$employee['id'] ?>" <?= (int)($editSickLeave['employee_id'] ?? 0) === (int)$employee['id'] ? 'selected' : '' ?>><?= ja_h(ja_admin_employee_name($employee)) ?></option><?php endforeach; ?></select></label>
            <label>Tipo<input name="leave_type" value="<?= ja_h($editSickLeave['leave_type'] ?? 'Baja médica') ?>" required></label>
            <label>Fecha de inicio<input type="date" name="start_date" value="<?= ja_h($editSickLeave['start_date'] ?? '') ?>" required></label>
            <label>Hora de inicio (opcional)<input type="time" name="start_time" step="60" value="<?= ja_h(isset($editSickLeave['start_time']) ? substr((string)$editSickLeave['start_time'],0,5) : '') ?>"></label>
            <label>Fecha final (opcional)<input type="date" name="end_date" value="<?= ja_h($editSickLeave['end_date'] ?? '') ?>"><small>Déjala vacía mientras la fecha de alta sea desconocida.</small></label>
            <label>Hora final (opcional)<input type="time" name="end_time" step="60" value="<?= ja_h(isset($editSickLeave['end_time']) ? substr((string)$editSickLeave['end_time'],0,5) : '') ?>"></label>
            <label>Adjunto PDF o JPG<input type="file" name="attachment" accept="application/pdf,image/jpeg,.pdf,.jpg,.jpeg"><small>Opcional · máximo 10 MB.</small></label>
            <label class="form-span-full">Motivo / observaciones<textarea name="reason" maxlength="2000"><?= ja_h($editSickLeave['reason'] ?? '') ?></textarea></label>
            <div class="actions form-span-full"><button class="button primary" type="submit">Guardar baja</button><?php if ($editSickLeave): ?><a class="button" href="?group=tables&amp;view=sick_leaves">Cancelar</a><?php endif; ?></div>
          </form>
        </article>
        <article class="card"><h3>Histórico de bajas</h3><?php ja_admin_render_table_controls($sickLeaveTableState); ?><div class="table-wrap"><table><thead><tr><th>Empleado</th><th>Periodo</th><th>Tipo</th><th>Motivo / adjunto</th><th>Acciones</th></tr></thead><tbody>
          <?php if (!$sickLeaveRows): ?><tr><td colspan="5"><div class="empty">Todavía no hay bajas.</div></td></tr><?php endif; ?>
          <?php foreach ($sickLeaveRows as $leave): $startLabel=(string)$leave['start_date'].($leave['start_time']?' · '.ja_admin_short_time($leave['start_time']):' · día completo'); $endLabel=$leave['end_date']?(string)$leave['end_date'].($leave['end_time']?' · '.ja_admin_short_time($leave['end_time']):' · día completo'):'Sin fecha final'; ?>
            <tr><td><?= ja_h($leave['employee_name']) ?></td><td><strong><?= ja_h($startLabel) ?></strong><br><small>hasta <?= ja_h($endLabel) ?></small></td><td><?= ja_h($leave['leave_type']) ?></td><td class="wrap"><?= nl2br(ja_h($leave['reason'] ?: 'Sin observaciones.')) ?><?php if ($leave['attachment']): ?><br><a class="button small" href="sick_leave_attachment.php?id=<?= (int)$leave['id'] ?>">Descargar <?= ja_h($leave['attachment_name'] ?: 'adjunto') ?></a><?php endif; ?></td><td><div class="actions"><a class="button small" href="?group=tables&amp;view=sick_leaves&amp;edit=<?= (int)$leave['id'] ?>">Editar</a><form method="post" onsubmit="return confirm('¿Eliminar esta baja?');"><input type="hidden" name="csrf" value="<?= ja_h($csrf) ?>"><input type="hidden" name="action" value="delete_sick_leave"><input type="hidden" name="record_id" value="<?= (int)$leave['id'] ?>"><button class="button danger small" type="submit">Eliminar</button></form></div></td></tr>
          <?php endforeach; ?>
        </tbody></table></div></article>

      <?php elseif ($view === 'absences'): ?>
        <?php
        $absenceTableState = ja_admin_server_table($absences, 'absences');
        $absenceRows = $absenceTableState['rows'];
        $absenceStatusLabels = ['pending' => 'Pendiente', 'approved' => 'Aprobada', 'rejected' => 'Rechazada'];
        $absenceConfirmationLabels = ['not_required' => 'No necesaria', 'pending' => 'Esperando al empleado', 'accepted' => 'Aceptada por el empleado', 'rejected' => 'Rechazada por el empleado'];
        ?>
        <article class="card">
          <div class="ju-table-caption"><div><h3>Proponer una ausencia</h3><p>La ausencia no se aprobará hasta que la persona trabajadora la confirme desde su portal.</p></div><span class="badge">Acuerdo bilateral</span></div>
          <form method="post" enctype="multipart/form-data" class="grid-3">
            <input type="hidden" name="csrf" value="<?= ja_h($csrf) ?>"><input type="hidden" name="action" value="create_admin_absence">
            <label>Persona<select name="employee_id" required><?php foreach ($employees as $employee): ?><?php if (empty($employee['active'])) continue; ?><option value="<?= (int)$employee['id'] ?>"><?= ja_h(ja_admin_employee_name($employee)) ?></option><?php endforeach; ?></select></label>
            <label>Tipo<select name="absence_type" required><option value="Enfermedad">Enfermedad</option><option value="Asuntos propios">Asuntos propios</option><option value="Permiso">Permiso</option><option value="Otro">Otro</option></select></label>
            <label>Fecha de inicio<input type="date" name="start_date" required></label>
            <label>Hora de inicio (opcional)<input type="time" name="start_time" step="60"><small>Sin hora se entiende día completo.</small></label>
            <label>Fecha final (opcional)<input type="date" name="end_date"><small>Déjala vacía si todavía se desconoce.</small></label>
            <label>Hora final (opcional)<input type="time" name="end_time" step="60"></label>
            <label class="form-span-full">Motivo<textarea name="reason" maxlength="2000" placeholder="Explica la propuesta al empleado."></textarea></label>
            <label class="form-span-full">Adjunto PDF o JPG<input type="file" name="attachment" accept="application/pdf,image/jpeg,.pdf,.jpg,.jpeg"><small>Opcional · máximo 10 MB.</small></label>
            <div class="actions form-span-full"><button class="button primary" type="submit">Enviar al empleado para confirmar</button></div>
          </form>
        </article>
        <?php ja_admin_render_table_controls($absenceTableState); ?>
        <div class="table-wrap"><table><thead><tr><th>Persona</th><th>Periodo</th><th>Tipo</th><th>Motivo / adjunto</th><th>Origen y confirmación</th><th>Estado</th><th>Decisión</th></tr></thead><tbody>
        <?php if (!$absenceRows): ?><tr><td colspan="7"><div class="empty">Todavía no hay ausencias.</div></td></tr><?php endif; ?>
        <?php foreach ($absenceRows as $absence):
          $startLabel = (string)$absence['start_date'] . ($absence['start_time'] ? ' · ' . ja_admin_short_time($absence['start_time']) : ' · día completo');
          $endLabel = $absence['end_date'] ? (string)$absence['end_date'] . ($absence['end_time'] ? ' · ' . ja_admin_short_time($absence['end_time']) : ' · día completo') : 'Sin fecha final';
          $adminProposalPending = $absence['source'] === 'admin' && $absence['employee_confirmation_status'] === 'pending';
        ?>
          <tr>
            <td><?= ja_h($absence['employee_name']) ?></td>
            <td><strong><?= ja_h($startLabel) ?></strong><br><small>hasta <?= ja_h($endLabel) ?></small></td>
            <td><?= ja_h($absence['absence_type']) ?></td>
            <td class="wrap"><?= nl2br(ja_h($absence['reason'] ?: 'Sin motivo.')) ?><?php if ($absence['attachment']): ?><br><a class="button small" href="absence_attachment.php?id=<?= (int)$absence['id'] ?>">Descargar <?= ja_h($absence['attachment_name'] ?: 'adjunto') ?></a><?php endif; ?></td>
            <td><strong><?= $absence['source'] === 'admin' ? 'Administración' : ($absence['source'] === 'import' ? 'Importación' : 'Empleado') ?></strong><br><small><?= ja_h($absenceConfirmationLabels[$absence['employee_confirmation_status']] ?? $absence['employee_confirmation_status']) ?></small><?php if ($absence['employee_comment']): ?><br><small>Comentario: <?= ja_h($absence['employee_comment']) ?></small><?php endif; ?></td>
            <td><span class="badge <?= $absence['status'] === 'approved' ? 'ok' : ($absence['status'] === 'rejected' ? 'danger' : 'warning') ?>"><?= ja_h($absenceStatusLabels[$absence['status']] ?? $absence['status']) ?></span></td>
            <td><?php if ($absence['source'] === 'admin'): ?><span class="muted"><?= $adminProposalPending ? 'Debe responder el empleado' : 'Decisión registrada por el empleado' ?></span><?php else: ?><form method="post" class="actions"><input type="hidden" name="csrf" value="<?= ja_h($csrf) ?>"><input type="hidden" name="action" value="absence_status"><input type="hidden" name="absence_id" value="<?= (int)$absence['id'] ?>"><button class="button small" name="status" value="approved">Aprobar</button><button class="button danger small" name="status" value="rejected">Rechazar</button></form><?php endif; ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody></table></div>
      <?php elseif ($view === 'users'): ?>
        <?php $userTableState = ja_admin_server_table($users, 'users'); $userRows = $userTableState['rows']; ?>
        <article class="card"><h3>Crear acceso al front</h3><form method="post" class="grid-2"><input type="hidden" name="csrf" value="<?= ja_h($csrf) ?>"><input type="hidden" name="action" value="create_user"><label>Persona<select name="employee_id" required><?php foreach ($employees as $employee): ?><option value="<?= (int)$employee['id'] ?>"><?= ja_h(ja_admin_employee_name($employee)) ?></option><?php endforeach; ?></select></label><label>Usuario<input name="username" required></label><label>Email<input type="email" name="email"></label><label>Contraseña inicial<input type="password" name="password" minlength="8" required></label><div><button class="button primary" type="submit">Crear acceso</button></div></form></article>
        <?php ja_admin_render_table_controls($userTableState); ?><div class="table-wrap"><table><thead><tr><th>Persona</th><th>Usuario</th><th>Email</th><th>Conciliación</th><th>Estado</th><th></th></tr></thead><tbody><?php foreach ($userRows as $user): ?><tr><td><?= ja_h($user['employee_name'] ?: $user['name']) ?></td><td><?= ja_h($user['username']) ?></td><td><?= ja_h($user['email']) ?></td><td><form method="post"><input type="hidden" name="csrf" value="<?= ja_h($csrf) ?>"><input type="hidden" name="action" value="toggle_user_conciliacion"><input type="hidden" name="user_id" value="<?= (int)$user['id'] ?>"><button class="switch-button <?= !empty($user['conciliacion']) ? 'on' : '' ?>" type="submit" aria-pressed="<?= !empty($user['conciliacion']) ? 'true' : 'false' ?>"><?= !empty($user['conciliacion']) ? 'ON' : 'OFF' ?></button></form></td><td><span class="badge <?= $user['active'] ? 'ok' : 'warning' ?>"><?= $user['active'] ? 'Activo' : 'Inactivo' ?></span></td><td><div class="actions"><form method="post"><input type="hidden" name="csrf" value="<?= ja_h($csrf) ?>"><input type="hidden" name="action" value="toggle_user"><input type="hidden" name="user_id" value="<?= (int)$user['id'] ?>"><button class="button small" type="submit"><?= $user['active'] ? 'Desactivar' : 'Activar' ?></button></form><form method="post" class="user-password-reset"><input type="hidden" name="csrf" value="<?= ja_h($csrf) ?>"><input type="hidden" name="action" value="reset_user_password"><input type="hidden" name="user_id" value="<?= (int)$user['id'] ?>"><input type="password" name="new_password" minlength="8" placeholder="Nueva contraseña" required><button class="button small" type="submit">Restablecer</button></form></div></td></tr><?php endforeach; ?></tbody></table></div>
      <?php elseif ($view === 'internal_communications'): ?>
        <?php $messageTypeTableState = ja_admin_server_table($internalMessageTypes, 'communication_types'); $messageTypeRows = $messageTypeTableState['rows']; $messageTableState = ja_admin_server_table($internalMessages, 'communications'); $messageRows = $messageTableState['rows']; ?>
        <article class="card"><h3>Tipos de comunicación</h3><form method="post" class="grid-3"><input type="hidden" name="csrf" value="<?= ja_h($csrf) ?>"><input type="hidden" name="action" value="save_internal_message_type"><label>Nombre<input name="name" required placeholder="Información"></label><label>Color<input type="color" name="color" value="#e9967a"></label><label>Emoji<input name="emoji" value="📢" maxlength="16" required></label><label class="check"><input type="checkbox" name="active" value="1" checked> Activo</label><div><button class="button primary">Crear tipo</button></div></form><?php ja_admin_render_table_controls($messageTypeTableState); ?><div class="table-wrap"><table><thead><tr><th>Tipo</th><th>Color</th><th>Estado</th></tr></thead><tbody><?php foreach($messageTypeRows as $type): ?><tr><td><strong><?= ja_h($type['emoji'].' '.$type['name']) ?></strong></td><td><span class="message-color" style="background:<?= ja_h($type['color']) ?>"><?= ja_h($type['color']) ?></span></td><td><?= $type['active'] ? 'Activo' : 'Inactivo' ?></td></tr><?php endforeach; ?></tbody></table></div></article>
        <article class="card"><h3>Emitir comunicación interna laboral</h3><?php if(!$internalMessageTypes): ?><div class="empty">Crea primero un tipo de comunicación.</div><?php else: ?><form method="post" class="grid-2"><input type="hidden" name="csrf" value="<?= ja_h($csrf) ?>"><input type="hidden" name="action" value="save_internal_message"><label>Tipo<select name="message_type_id"><?php foreach($internalMessageTypes as $type): ?><option value="<?= (int)$type['id'] ?>"><?= ja_h($type['emoji'].' '.$type['name']) ?></option><?php endforeach; ?></select></label><label>Fecha de inicio<input type="date" name="start_date" value="<?= date('Y-m-d') ?>" required></label><label>Días destacado<input type="number" name="highlight_days" min="1" max="3650" value="1" required></label><label class="check"><input type="checkbox" name="active" value="1" checked> Publicado</label><label class="form-span-full">Mensaje<textarea name="message" required maxlength="5000"></textarea></label><div><button class="button primary">Emitir mensaje</button></div></form><?php endif; ?></article>
        <?php ja_admin_render_table_controls($messageTableState); ?><div class="table-wrap"><table><thead><tr><th>Inicio</th><th>Tipo</th><th>Días destacado</th><th>Mensaje</th><th>Estado</th><th></th></tr></thead><tbody><?php foreach($messageRows as $message): ?><tr><td><?= ja_h($message['start_date']) ?></td><td><span class="message-type" style="--message-color:<?= ja_h($message['color']) ?>"><?= ja_h($message['emoji'].' '.$message['type_name']) ?></span></td><td><?= (int)$message['highlight_days'] ?></td><td class="wrap"><?= nl2br(ja_h($message['message'])) ?></td><td><?= $message['active'] ? 'Publicado' : 'Oculto' ?></td><td><form method="post"><input type="hidden" name="csrf" value="<?= ja_h($csrf) ?>"><input type="hidden" name="action" value="delete_internal_message"><input type="hidden" name="record_id" value="<?= (int)$message['id'] ?>"><button class="button danger small" data-confirm="¿Eliminar esta comunicación?">Eliminar</button></form></td></tr><?php endforeach; ?></tbody></table></div>
      <?php else: ?>
        <?php ja_admin_render_business_table($pdo, $tenantId, $admin, $view, $employees, $contractTypes, $csrf); ?>
      <?php endif; ?>
      </section>
    </div>
  </section>
</main>
<script src="../assets/app.js?v=2.1.3"></script>
</body></html>

```

### admin/logout.php

```php
<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

JA\Session::start('admin');
JA\Session::destroy();
ja_redirect('index.php');



```

### admin/resumen_sesiones.php

```php
<?php
declare(strict_types=1);

if (!defined('JA_ADMIN_APPLICATION')) {
    http_response_code(404);
    exit;
}

$week = (int)($_GET['week'] ?? date('W'));
$year = (int)($_GET['year'] ?? date('o'));
if ($week < 1 || $week > 53 || $year < 2000 || $year > 2200) {
    $week = (int)date('W');
    $year = (int)date('o');
}
$weekStart = (new DateTimeImmutable())->setISODate($year, $week)->setTime(0, 0);
$weekEnd = $weekStart->modify('+6 days');
$previous = $weekStart->modify('-7 days');
$next = $weekStart->modify('+7 days');
$weekDates = [];
for ($index = 0; $index < 7; $index++) {
    $weekDates[] = $weekStart->modify('+' . $index . ' days')->format('Y-m-d');
}

$statement = $pdo->prepare('SELECT id,nombre,apellido,departamento,role FROM employees WHERE tenant_id=:tenant AND active=1 ORDER BY apellido,nombre');
$statement->execute(['tenant' => $tenantId]);
$summaryEmployees = $statement->fetchAll();

$statement = $pdo->prepare('SELECT * FROM attendance WHERE tenant_id=:tenant AND work_date BETWEEN :start AND :end ORDER BY id DESC');
$statement->execute(['tenant' => $tenantId, 'start' => $weekStart->format('Y-m-d'), 'end' => $weekEnd->format('Y-m-d')]);
$attendanceByEmployeeDate = [];
foreach ($statement->fetchAll() as $record) {
    $employeeId = (int)$record['employee_id'];
    $date = $record['work_date'];
    $attendanceByEmployeeDate[$employeeId][$date] ??= $record;
}

$statement = $pdo->prepare('SELECT * FROM schedules WHERE tenant_id=:tenant AND (valid_from IS NULL OR valid_from<=:end) AND (valid_until IS NULL OR valid_until>=:start) ORDER BY start_time');
$statement->execute(['tenant' => $tenantId, 'start' => $weekStart->format('Y-m-d'), 'end' => $weekEnd->format('Y-m-d')]);
$scheduleByEmployeeDay = [];
foreach ($statement->fetchAll() as $schedule) {
    $scheduleByEmployeeDay[(int)$schedule['employee_id']][(int)$schedule['weekday']][] = $schedule;
}

$statement = $pdo->prepare('SELECT * FROM holidays WHERE tenant_id=:tenant AND holiday_date BETWEEN :start AND :end');
$statement->execute(['tenant' => $tenantId, 'start' => $weekStart->format('Y-m-d'), 'end' => $weekEnd->format('Y-m-d')]);
$holidayByDate = [];
foreach ($statement->fetchAll() as $holiday) {
    $holidayByDate[$holiday['holiday_date']] = $holiday;
}

$statement = $pdo->prepare("SELECT * FROM absences WHERE tenant_id=:tenant AND status='approved' AND start_date<=:end AND (end_date IS NULL OR end_date>=:start)");
$statement->execute(['tenant' => $tenantId, 'start' => $weekStart->format('Y-m-d'), 'end' => $weekEnd->format('Y-m-d')]);
$approvedAbsences = $statement->fetchAll();
$absenceFor = static function (int $employeeId, string $date) use ($approvedAbsences): ?array {
    foreach ($approvedAbsences as $absence) {
        if ((int)$absence['employee_id'] === $employeeId && $absence['start_date'] <= $date && (!$absence['end_date'] || $absence['end_date'] >= $date)) {
            return $absence;
        }
    }
    return null;
};
$today = date('Y-m-d');
?>
<section class="application-stack sessions-summary">
  <div class="application-toolbar">
    <div><h2>Resumen de sesiones</h2><p>Semana <?= ja_h((string)$year) ?>-W<?= ja_h(str_pad((string)$week, 2, '0', STR_PAD_LEFT)) ?> · <?= ja_h($weekStart->format('d/m/Y')) ?> → <?= ja_h($weekEnd->format('d/m/Y')) ?></p></div>
    <div class="actions"><a class="button" href="<?= ja_h(ja_admin_query_url(['group' => 'applications', 'app' => 'resumen_sesiones', 'week' => $previous->format('W'), 'year' => $previous->format('o')])) ?>">Semana anterior</a><a class="button primary" href="<?= ja_h(ja_admin_query_url(['group' => 'applications', 'app' => 'resumen_sesiones', 'week' => date('W'), 'year' => date('o')])) ?>">Semana actual</a><a class="button" href="<?= ja_h(ja_admin_query_url(['group' => 'applications', 'app' => 'resumen_sesiones', 'week' => $next->format('W'), 'year' => $next->format('o')])) ?>">Semana siguiente</a></div>
  </div>
  <div class="session-legend"><span><i class="present"></i>Asistió</span><span><i class="error"></i>Error de registro</span><span><i class="absent"></i>Ausencia</span><span><i class="holiday"></i>Festivo</span><span><i class="noschedule"></i>Sin horas asignadas</span><span><i class="open"></i>Jornada abierta hoy</span></div>
  <div class="sessions-table-wrap"><table class="sessions-table"><thead><tr><th>Empleado</th><?php foreach ($weekDates as $date): $dayNumber = (int)(new DateTimeImmutable($date))->format('N'); ?><th><?= ja_h($weekdays[$dayNumber]) ?><small><?= ja_h((new DateTimeImmutable($date))->format('d/m')) ?></small></th><?php endforeach; ?></tr></thead><tbody>
  <?php foreach ($summaryEmployees as $employee): $employeeId = (int)$employee['id']; ?><tr><th><strong><?= ja_h(ja_admin_employee_name($employee)) ?></strong><small><?= ja_h($employee['departamento'] ?: 'Sin departamento') ?></small></th>
    <?php foreach ($weekDates as $date):
        $record = $attendanceByEmployeeDate[$employeeId][$date] ?? null;
        $absence = $absenceFor($employeeId, $date);
        $weekday = (int)(new DateTimeImmutable($date))->format('N');
        $applicableSchedule = array_values(array_filter($scheduleByEmployeeDay[$employeeId][$weekday] ?? [], static fn(array $schedule): bool => (!$schedule['valid_from'] || $schedule['valid_from'] <= $date) && (!$schedule['valid_until'] || $schedule['valid_until'] >= $date)));
        $status = 'noschedule';
        $label = 'Sin horario';
        if (isset($holidayByDate[$date])) {
            $status = 'holiday';
            $label = $holidayByDate[$date]['name'];
        } elseif ($record && $record['start_time'] && $record['end_time']) {
            $status = 'present';
            $label = ja_admin_short_time($record['start_time']) . ' – ' . ja_admin_short_time($record['end_time']);
        } elseif ($record && $record['start_time'] && !$record['end_time']) {
            $status = $date === $today ? 'open' : 'error';
            $label = $date === $today ? 'Abierta ' . ja_admin_short_time($record['start_time']) : 'Falta salida';
        } elseif ($record) {
            $status = 'error';
            $label = 'Registro incompleto';
        } elseif ($absence) {
            $status = 'absent';
            $label = $absence['absence_type'];
        } elseif ($applicableSchedule) {
            $status = 'absent';
            $label = 'Sin asistencia';
        }
    ?><td><a class="session-state <?= ja_h($status) ?>" href="?group=tables&view=attendance" title="<?= ja_h($label) ?>"><?= ja_h($label) ?></a></td><?php endforeach; ?>
  </tr><?php endforeach; ?>
  <?php if (!$summaryEmployees): ?><tr><td colspan="8">No hay empleados activos.</td></tr><?php endif; ?>
  </tbody></table></div>
</section>


```

### admin/sick_leave_attachment.php

```php
<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/bootstrap.php';
use JA\AbsenceAttachment; use JA\Auth; use JA\Database; use JA\Session; use JA\TenantContext;
Session::start('admin'); $tenant=TenantContext::current(); $tenantId=(int)$tenant['id']; $pdo=Database::pdo();
$admin=Auth::requireTenantAdmin();
$id=(int)($_GET['id']??0); $st=$pdo->prepare('SELECT attachment,attachment_name FROM sick_leaves WHERE id=:id AND tenant_id=:tenant LIMIT 1'); $st->execute(['id'=>$id,'tenant'=>$tenantId]); $row=$st->fetch();
if(!$row||empty($row['attachment'])){ http_response_code(404); exit('Adjunto no encontrado.'); }
AbsenceAttachment::download((string)$row['attachment'],(string)($row['attachment_name']??'adjunto'),$tenantId);
```

### admin/solicitudes_personal.php

```php
<?php
declare(strict_types=1);

if (!defined('JA_ADMIN_APPLICATION')) {
    http_response_code(403);
    exit;
}

$statusLabels = ['pending' => 'Pendiente', 'accepted' => 'Aprobada', 'rejected' => 'Rechazada'];
$pendingCount = count(array_filter($workerCorrections, static fn(array $row): bool => $row['status'] === 'pending'));
?>
<div class="application-toolbar">
  <div><h3>Solicitudes del personal</h3><p>Revisa las modificaciones propuestas por los trabajadores. Aprobar aplica el cambio; rechazar conserva la jornada vigente.</p></div>
  <span class="badge <?= $pendingCount ? 'warning' : 'ok' ?>"><?= $pendingCount ?> pendientes</span>
</div>

<?php if (!$workerAttendanceEditEnabled): ?>
  <div class="notice warning">La creación de nuevas solicitudes está deshabilitada en Configuración. Las solicitudes existentes siguen disponibles para su resolución.</div>
<?php endif; ?>

<?php if (!$workerCorrections): ?>
  <div class="empty">Todavía no hay solicitudes de corrección iniciadas por trabajadores.</div>
<?php endif; ?>

<div class="correction-list">
<?php foreach ($workerCorrections as $request): $status = (string)$request['status']; $pending = $status === 'pending'; ?>
  <article class="card correction-card <?= $pending ? 'is-pending' : '' ?>">
    <div class="correction-card-head">
      <div><p class="eyebrow">Jornada #<?= (int)$request['attendance_id'] ?></p><h3><?= ja_h($request['employee_name']) ?></h3><p>Solicitada por <?= ja_h($request['worker_user_name']) ?> · <?= ja_h($request['requested_at']) ?></p></div>
      <span class="badge <?= $status === 'accepted' ? 'ok' : ($status === 'rejected' ? 'danger' : 'warning') ?>"><?= ja_h($statusLabels[$status] ?? $status) ?></span>
    </div>
    <div class="correction-comparison">
      <div><small>REGISTRO ACTUAL</small><strong><?= ja_h($request['original_work_date']) ?></strong><span><?= ja_h(ja_admin_short_time($request['original_start_time'])) ?> — <?= ja_h(ja_admin_short_time($request['original_end_time'])) ?></span><span>Tipo: <?= ja_h($request['original_attendance_type_name'] ?: 'Sin tipo') ?></span></div>
      <div class="correction-arrow">→</div>
      <div><small>PROPUESTA DEL TRABAJADOR</small><strong><?= ja_h($request['proposed_work_date']) ?></strong><span><?= ja_h(ja_admin_short_time($request['proposed_start_time'])) ?> — <?= ja_h(ja_admin_short_time($request['proposed_end_time'])) ?></span><span>Tipo: <?= ja_h($request['proposed_attendance_type_name'] ?: 'Sin tipo') ?></span></div>
    </div>
    <div class="correction-reason"><strong>Motivo del trabajador</strong><p><?= ja_h($request['reason']) ?></p></div>
    <?php if ($pending): ?>
      <form method="post" class="stack">
        <input type="hidden" name="csrf" value="<?= ja_h($csrf) ?>">
        <input type="hidden" name="action" value="worker_correction_decision">
        <input type="hidden" name="request_id" value="<?= (int)$request['id'] ?>">
        <label>Comentario para el trabajador<textarea name="admin_comment" maxlength="1000" placeholder="Opcional: explica el motivo de la decisión."></textarea></label>
        <div class="actions"><button class="button primary" type="submit" name="decision" value="accepted" data-confirm="¿Aprobar y aplicar esta corrección?">Aprobar y aplicar</button><button class="button danger" type="submit" name="decision" value="rejected" data-confirm="¿Rechazar esta corrección y conservar la jornada original?">Rechazar</button></div>
      </form>
    <?php else: ?>
      <div class="correction-response"><strong>Decisión de <?= ja_h($request['admin_name'] ?: 'administración') ?></strong><p><?= ja_h($request['admin_comment'] ?: 'Sin comentario.') ?></p><small><?= ja_h($request['responded_at']) ?></small></div>
    <?php endif; ?>
    <div class="correction-hashes"><span>Huella de propuesta</span><code><?= ja_h($request['proposal_event_hash'] ?: '—') ?></code><?php if ($request['decision_event_hash']): ?><span>Huella de decisión</span><code><?= ja_h($request['decision_event_hash']) ?></code><?php endif; ?></div>
  </article>
<?php endforeach; ?>
</div>


```

### admin/temporalizacion_vacaciones.php

```php
<?php
declare(strict_types=1);

if (!defined('JA_ADMIN_APPLICATION')) {
    http_response_code(404);
    exit;
}

$vacationYear = (int)($_GET['year'] ?? date('Y'));
if ($vacationYear < 2000 || $vacationYear > 2100) {
    $vacationYear = (int)date('Y');
}
$vacationDepartment = trim((string)($_GET['department'] ?? ''));
$vacationOnlyWithPeriods = (string)($_GET['only_with_vacations'] ?? '') === '1';
$vacationStart = new DateTimeImmutable(sprintf('%04d-01-01', $vacationYear));
$vacationEnd = new DateTimeImmutable(sprintf('%04d-12-31', $vacationYear));
$vacationDates = [];
for ($date = $vacationStart; $date <= $vacationEnd; $date = $date->modify('+1 day')) {
    $vacationDates[] = $date;
}
$vacationDayCount = count($vacationDates);
$vacationMonthNames = [1 => 'Ene', 2 => 'Feb', 3 => 'Mar', 4 => 'Abr', 5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Ago', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dic'];

$statement = $pdo->prepare("SELECT DISTINCT departamento FROM employees WHERE tenant_id=:tenant AND active=1 AND departamento IS NOT NULL AND trim(departamento)<>'' ORDER BY departamento COLLATE NOCASE");
$statement->execute(['tenant' => $tenantId]);
$vacationDepartments = array_column($statement->fetchAll(), 'departamento');

$employeeSql = "SELECT id,nombre,apellido,departamento,role FROM employees WHERE tenant_id=:tenant AND active=1";
$employeeParams = ['tenant' => $tenantId];
if ($vacationDepartment !== '') {
    $employeeSql .= ' AND departamento=:department';
    $employeeParams['department'] = $vacationDepartment;
}
$employeeSql .= ' ORDER BY apellido COLLATE NOCASE,nombre COLLATE NOCASE';
$statement = $pdo->prepare($employeeSql);
$statement->execute($employeeParams);
$vacationEmployees = $statement->fetchAll();
$vacationEmployeeIds = array_fill_keys(array_map(static fn(array $employee): int => (int)$employee['id'], $vacationEmployees), true);

$statement = $pdo->prepare(
    "SELECT v.id,v.employee_id,v.start_date,v.end_date,v.approved
     FROM vacations v
     WHERE v.tenant_id=:tenant AND v.start_date<=:year_end AND v.end_date>=:year_start
     ORDER BY v.employee_id,v.start_date,v.end_date,v.id"
);
$statement->execute([
    'tenant' => $tenantId,
    'year_start' => $vacationStart->format('Y-m-d'),
    'year_end' => $vacationEnd->format('Y-m-d'),
]);

$vacationBars = [];
$vacationPeriodCount = 0;
$vacationApprovedCount = 0;
$vacationVisibleDays = 0;
foreach ($statement->fetchAll() as $period) {
    $employeeId = (int)$period['employee_id'];
    if (!isset($vacationEmployeeIds[$employeeId])) {
        continue;
    }
    $originalStart = new DateTimeImmutable((string)$period['start_date']);
    $originalEnd = new DateTimeImmutable((string)$period['end_date']);
    $visibleStart = $originalStart < $vacationStart ? $vacationStart : $originalStart;
    $visibleEnd = $originalEnd > $vacationEnd ? $vacationEnd : $originalEnd;
    $startIndex = (int)$vacationStart->diff($visibleStart)->format('%a') + 1;
    $span = (int)$visibleStart->diff($visibleEnd)->format('%a') + 1;
    $periodDayCount = (int)$originalStart->diff($originalEnd)->format('%a') + 1;

    $track = 1;
    while (isset($vacationBars[$employeeId]['track_ends'][$track]) && $vacationBars[$employeeId]['track_ends'][$track] >= $startIndex) {
        $track++;
    }
    $vacationBars[$employeeId]['track_ends'][$track] = $startIndex + $span - 1;
    $vacationBars[$employeeId]['items'][] = $period + [
        'start_index' => $startIndex,
        'span' => $span,
        'day_count' => $periodDayCount,
        'track' => $track,
    ];
    $vacationBars[$employeeId]['tracks'] = max((int)($vacationBars[$employeeId]['tracks'] ?? 0), $track);
    $vacationPeriodCount++;
    $vacationApprovedCount += !empty($period['approved']) ? 1 : 0;
    $vacationVisibleDays += $span;
}
$vacationEmployeesWithPeriods = count(array_filter($vacationBars, static fn(array $item): bool => !empty($item['items'])));
if ($vacationOnlyWithPeriods) {
    $vacationEmployees = array_values(array_filter(
        $vacationEmployees,
        static fn(array $employee): bool => !empty($vacationBars[(int)$employee['id']]['items'])
    ));
}
$vacationBaseQuery = [
    'group' => 'applications',
    'app' => 'temporalizacion_vacaciones',
    'department' => $vacationDepartment ?: null,
    'only_with_vacations' => $vacationOnlyWithPeriods ? '1' : null,
];
$vacationTodayIndex = $vacationYear === (int)date('Y') ? (int)date('z') : null;
?>
<section class="application-stack vacation-timeline">
  <div class="application-toolbar">
    <div><h2>Temporalización vacaciones</h2><p>Vista general diaria de <?= ja_h((string)$vacationYear) ?> · <?= count($vacationEmployees) ?> personas visibles.</p></div>
    <div class="actions">
      <a class="button" href="<?= ja_h(ja_admin_query_url($vacationBaseQuery + ['year' => $vacationYear - 1])) ?>">← <?= $vacationYear - 1 ?></a>
      <a class="button primary" href="<?= ja_h(ja_admin_query_url($vacationBaseQuery + ['year' => (int)date('Y')])) ?>">Año actual</a>
      <a class="button" href="<?= ja_h(ja_admin_query_url($vacationBaseQuery + ['year' => $vacationYear + 1])) ?>"><?= $vacationYear + 1 ?> →</a>
    </div>
  </div>

  <form class="vacation-timeline-filters" method="get">
    <input type="hidden" name="group" value="applications"><input type="hidden" name="app" value="temporalizacion_vacaciones">
    <label>Año<input type="number" name="year" min="2000" max="2100" value="<?= $vacationYear ?>"></label>
    <label>Departamento<select name="department"><option value="">Todos</option><?php foreach ($vacationDepartments as $department): ?><option value="<?= ja_h($department) ?>" <?= $vacationDepartment === $department ? 'selected' : '' ?>><?= ja_h($department) ?></option><?php endforeach; ?></select></label>
    <label class="conciliation-switch vacation-only-switch"><span><strong>Solo con vacaciones</strong><small>Oculta las filas sin periodos en el año.</small></span><input type="checkbox" name="only_with_vacations" value="1" <?= $vacationOnlyWithPeriods ? 'checked' : '' ?> onchange="this.form.submit()"><i aria-hidden="true"></i></label>
    <label class="vacation-zoom-control" for="vacationZoom"><span><strong>Zoom horizontal</strong><output id="vacationZoomValue" for="vacationZoom">100%</output></span><input id="vacationZoom" type="range" min="18" max="64" step="1" value="32"></label>
    <button class="button primary" type="submit">Aplicar</button>
    <a class="button" href="?group=applications&amp;app=temporalizacion_vacaciones">Limpiar</a>
    <a class="button" href="?group=tables&amp;view=vacations">Gestionar vacaciones</a>
  </form>

  <div class="vacation-timeline-metrics">
    <article class="metric"><span>Personas con vacaciones</span><strong><?= $vacationEmployeesWithPeriods ?></strong></article>
    <article class="metric"><span>Periodos visibles</span><strong><?= $vacationPeriodCount ?></strong></article>
    <article class="metric"><span>Días representados</span><strong><?= $vacationVisibleDays ?></strong></article>
    <article class="metric"><span>Periodos aprobados</span><strong><?= $vacationApprovedCount ?> / <?= $vacationPeriodCount ?></strong></article>
  </div>

  <article class="card vacation-timeline-card">
    <div class="ju-table-caption"><div><h3>Calendario anual de la plantilla</h3><p>Desplázate horizontalmente. La columna de personas permanece fija; cada barra representa un periodo y se puede abrir para editarlo.</p></div><div class="vacation-timeline-legend"><span><i></i>Aprobadas</span><span class="pending"><i></i>Pendientes</span></div></div>
    <div class="vacation-timeline-scroll"<?= $vacationTodayIndex !== null ? ' data-today-index="' . $vacationTodayIndex . '"' : '' ?>>
      <table class="vacation-timeline-table">
        <colgroup><col class="vacation-person-column"><?php foreach ($vacationDates as $unused): ?><col class="vacation-day-column"><?php endforeach; ?></colgroup>
        <thead><tr><th class="vacation-person-head">Persona</th><?php foreach ($vacationDates as $date): $weekday = (int)$date->format('N'); $firstOfMonth = $date->format('j') === '1'; $isToday = $date->format('Y-m-d') === date('Y-m-d'); ?><th class="vacation-date-head <?= $weekday >= 6 ? 'is-weekend' : '' ?> <?= $firstOfMonth ? 'is-month-start' : '' ?> <?= $isToday ? 'is-today' : '' ?>" title="<?= ja_h($date->format('d/m/Y')) ?>"><small><?= $firstOfMonth ? ja_h($vacationMonthNames[(int)$date->format('n')]) : '' ?></small><span><?= ja_h($date->format('d')) ?></span></th><?php endforeach; ?></tr></thead>
        <tbody>
          <?php foreach ($vacationEmployees as $employee): $employeeId = (int)$employee['id']; $timeline = $vacationBars[$employeeId] ?? ['items' => [], 'tracks' => 1]; $tracks = max(1, (int)($timeline['tracks'] ?? 1)); ?>
            <tr>
              <th class="vacation-person-cell"><strong><?= ja_h(ja_admin_employee_name($employee)) ?></strong><small><?= ja_h($employee['departamento'] ?: 'Sin departamento') ?> · <?= ja_h($employee['role'] ?: 'Sin rol') ?></small></th>
              <td class="vacation-lane-cell" colspan="<?= $vacationDayCount ?>">
                <div class="vacation-lane" style="--vacation-days:<?= $vacationDayCount ?>;--vacation-tracks:<?= $tracks ?>">
                  <?php foreach ($timeline['items'] as $period): $approved = !empty($period['approved']); $periodLabel = (new DateTimeImmutable((string)$period['start_date']))->format('d/m') . '–' . (new DateTimeImmutable((string)$period['end_date']))->format('d/m'); $daysLabel = (int)$period['day_count'] . ((int)$period['day_count'] === 1 ? ' día' : ' días'); $compactBar = (int)$period['span'] <= 3; ?>
                    <a class="vacation-bar <?= $approved ? 'is-approved' : 'is-pending' ?> <?= $compactBar ? 'is-compact' : '' ?>" style="grid-column:<?= (int)$period['start_index'] ?> / span <?= (int)$period['span'] ?>;grid-row:<?= (int)$period['track'] ?>" href="<?= ja_h(ja_admin_query_url(['group' => 'tables', 'view' => 'vacations', 'record_id' => (int)$period['id']])) ?>" title="<?= ja_h($periodLabel . ' · ' . $daysLabel . ($approved ? ' · Aprobadas' : ' · Pendientes')) ?>"><span class="vacation-bar-copy"><strong><?= ja_h($periodLabel) ?></strong><small><?= $approved ? 'Aprobadas' : 'Pendientes' ?></small></span><b class="vacation-day-count" title="<?= ja_h($daysLabel) ?>"><?= $compactBar ? (int)$period['day_count'] . 'd' : ja_h($daysLabel) ?></b></a>
                  <?php endforeach; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$vacationEmployees): ?><tr><td colspan="<?= $vacationDayCount + 1 ?>"><div class="empty">No hay personas que coincidan con el filtro seleccionado.</div></td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </article>
</section>
<script>
(() => {
  const scroll = document.querySelector('.vacation-timeline-scroll');
  if (!scroll) return;
  const zoom = document.querySelector('#vacationZoom');
  const zoomValue = document.querySelector('#vacationZoomValue');
  const storageKey = 'ja-vacation-day-width';
  const clampZoom = value => Math.min(64, Math.max(18, Number(value) || 32));
  const dayWidth = () => parseFloat(getComputedStyle(scroll).getPropertyValue('--vacation-day-width')) || 32;
  const personWidth = () => scroll.querySelector('.vacation-person-column')?.getBoundingClientRect().width || 240;
  const setZoom = value => {
    const width = clampZoom(value);
    scroll.style.setProperty('--vacation-day-width', `${width}px`);
    if (zoom) zoom.value = String(width);
    if (zoomValue) zoomValue.textContent = `${Math.round(width / 32 * 100)}%`;
    return width;
  };
  const centerToday = () => {
    if (scroll.dataset.todayIndex === undefined) return;
    const todayIndex = Number(scroll.dataset.todayIndex) || 0;
    const centeredPosition = personWidth() + (todayIndex + 0.5) * dayWidth() - scroll.clientWidth / 2;
    scroll.scrollLeft = Math.max(0, Math.min(scroll.scrollWidth - scroll.clientWidth, centeredPosition));
  };
  const scheduleCenter = () => requestAnimationFrame(() => requestAnimationFrame(centerToday));
  setZoom(localStorage.getItem(storageKey) || 32);
  zoom?.addEventListener('input', () => {
    const previousWidth = dayWidth();
    const centeredDay = Math.max(0, (scroll.scrollLeft + scroll.clientWidth / 2 - personWidth()) / previousWidth);
    const nextWidth = setZoom(zoom.value);
    localStorage.setItem(storageKey, String(nextWidth));
    if (scroll.dataset.todayIndex !== undefined) {
      centerToday();
    } else {
      scroll.scrollLeft = Math.max(0, personWidth() + centeredDay * nextWidth - scroll.clientWidth / 2);
    }
  });
  scheduleCenter();
  window.addEventListener('load', scheduleCenter, {once: true});
})();
</script>
```

### assets/app.js

```javascript
(() => {
  const stack = () => {
    let element = document.querySelector('.toast-stack');
    if (!element) {
      element = document.createElement('div');
      element.className = 'toast-stack';
      document.body.appendChild(element);
    }
    return element;
  };

  window.jaToast = (message, type = 'ok') => {
    const toast = document.createElement('div');
    toast.className = `toast chaflan ${type}`;
    const text = document.createElement('span');
    text.textContent = message;
    const close = document.createElement('button');
    close.type = 'button';
    close.setAttribute('aria-label', 'Cerrar');
    close.textContent = '×';
    close.addEventListener('click', () => toast.remove());
    toast.append(text, close);
    stack().appendChild(toast);
    setTimeout(() => toast.remove(), 5000);
  };

  const scheduleForm = document.getElementById('scheduleGridForm');
  if (scheduleForm) {
    let activeScheduleType = '0';
    let painting = false;
    const choices = [...document.querySelectorAll('[data-schedule-type]')];
    const choiceColor = typeId => {
      const choice = choices.find(item => item.dataset.scheduleType === String(typeId));
      return choice ? getComputedStyle(choice).getPropertyValue('--type-color').trim() : '#b9aaa5';
    };
    const updateScheduleTotal = () => {
      const hours = scheduleForm.querySelectorAll('.schedule-slot.selected').length * 0.5;
      const total = document.getElementById('scheduleTotal');
      if (total) total.textContent = `${hours.toLocaleString('es-ES',{minimumFractionDigits:1,maximumFractionDigits:1})} horas seleccionadas`;
    };
    const refreshPills = () => {
      for (let day=1; day<=7; day++) {
        const slots = [...scheduleForm.querySelectorAll(`.schedule-slot[data-day="${day}"]`)];
        slots.forEach((slot,index) => {
          slot.classList.remove('segment-start','segment-middle','segment-end','segment-single');
          if (!slot.classList.contains('selected')) return;
          const previous = slots[index-1];
          const next = slots[index+1];
          const type = slot.dataset.typeId || '0';
          const joinedPrevious = previous?.classList.contains('selected') && (previous.dataset.typeId || '0') === type;
          const joinedNext = next?.classList.contains('selected') && (next.dataset.typeId || '0') === type;
          slot.classList.add(!joinedPrevious && !joinedNext ? 'segment-single' : (!joinedPrevious ? 'segment-start' : (!joinedNext ? 'segment-end' : 'segment-middle')));
          const label=slot.querySelector('[data-block-label]'); if(label){ const group=[]; let j=index; while(j<slots.length && slots[j].classList.contains('selected') && (slots[j].dataset.typeId||'0')===type){group.push(slots[j]);j++;} group.forEach(x=>x.querySelector('[data-block-label]')?.classList.remove('block-label-visible')); const target=group.length===1?group[0]:group[1]; target?.querySelector('[data-block-label]')?.classList.add('block-label-visible'); }
        });
      }
    };
    const paintSlot = slot => {
      if (!slot) return;
      if (activeScheduleType === 'erase') {
        slot.classList.remove('selected');
        slot.dataset.typeId = '';
        slot.setAttribute('aria-checked','false');
      } else {
        slot.classList.add('selected');
        slot.dataset.typeId = activeScheduleType;
        slot.style.setProperty('--slot-color', choiceColor(activeScheduleType));
        slot.setAttribute('aria-checked','true');
      }
      refreshPills(); updateScheduleTotal();
    };
    choices.forEach(choice => choice.addEventListener('click', () => {
      activeScheduleType = choice.dataset.scheduleType;
      choices.forEach(item => item.classList.toggle('active', item === choice));
    }));
    scheduleForm.addEventListener('pointerdown', event => {
      const slot = event.target.closest('.schedule-slot');
      if (!slot) return;
      event.preventDefault(); painting = true; paintSlot(slot);
      slot.setPointerCapture?.(event.pointerId);
    });
    scheduleForm.addEventListener('pointerover', event => {
      if (painting) paintSlot(event.target.closest('.schedule-slot'));
    });
    document.addEventListener('pointerup', () => { painting = false; });
    scheduleForm.addEventListener('keydown', event => {
      const slot = event.target.closest('.schedule-slot');
      if (slot && (event.key === 'Enter' || event.key === ' ')) { event.preventDefault(); paintSlot(slot); }
    });
    const fromInput = scheduleForm.querySelector('input[name="valid_from"]');
    const untilInput = scheduleForm.querySelector('input[name="valid_until"]');
    const periodPreview = document.getElementById('schedulePeriodPreview');
    const formatPeriodDate = value => {
      if (!value) return '';
      const parts = value.split('-');
      return parts.length === 3 ? `${parts[2]}/${parts[1]}/${parts[0]}` : value;
    };
    const refreshPeriodPreview = () => {
      if (!periodPreview) return;
      const from = fromInput?.value || '';
      const until = untilInput?.value || '';
      periodPreview.textContent = `${from ? formatPeriodDate(from) : 'Fecha inicial obligatoria'} → ${until ? formatPeriodDate(until) : 'Sin fecha final'}`;
    };
    fromInput?.addEventListener('input', refreshPeriodPreview);
    untilInput?.addEventListener('input', refreshPeriodPreview);
    refreshPeriodPreview();

    scheduleForm.addEventListener('submit', event => {
      if (!fromInput?.value) {
        event.preventDefault();
        window.jaToast('Indica la fecha de inicio del segmento.', 'danger');
        fromInput?.focus();
        return;
      }
      if (untilInput?.value && untilInput.value < fromInput.value) {
        event.preventDefault();
        window.jaToast('La fecha final no puede ser anterior a la fecha inicial.', 'danger');
        untilInput.focus();
        return;
      }
      scheduleForm.querySelectorAll('.generated-slot').forEach(element => element.remove());
      scheduleForm.querySelectorAll('.schedule-slot.selected').forEach(slot => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = `slots[${slot.dataset.day}][${slot.dataset.time}]`;
        input.value = slot.dataset.typeId || '0';
        input.className = 'generated-slot';
        scheduleForm.appendChild(input);
      });
    });
    refreshPills(); updateScheduleTotal();
  }

  document.addEventListener('click', event => {
    const destructive = event.target.closest('[data-confirm]');
    if (destructive && !window.confirm(destructive.dataset.confirm || '¿Confirmas esta operación?')) event.preventDefault();
  });

  const attendanceEmployee = document.querySelector('[data-attendance-employee]');
  const attendanceType = document.querySelector('[data-attendance-type]');
  if (attendanceType) {
    const syncAttendanceTypes = () => {
      const employeeRole = attendanceEmployee
        ? (attendanceEmployee.selectedOptions[0]?.dataset.role || '')
        : (attendanceType.dataset.employeeRole || '');
      const role = employeeRole.trim().toLocaleLowerCase('es-ES');
      const current = attendanceType.value;
      const existingRecord = attendanceType.dataset.existingRecord === '1';
      let visible = 0;
      let defaultValue = '';
      [...attendanceType.options].forEach(option => {
        if (!option.value) return;
        const matches = Boolean(role) && (option.dataset.role || '').trim().toLocaleLowerCase('es-ES') === role;
        option.hidden = !matches;
        option.disabled = !matches;
        if (matches) {
          visible++;
          if (option.dataset.default === '1') defaultValue = option.value;
        }
      });
      const selected = attendanceType.selectedOptions[0];
      if (!selected || selected.disabled) attendanceType.value = defaultValue || '';
      if (!current && defaultValue && !existingRecord) attendanceType.value = defaultValue;
      attendanceType.required = visible > 0 && !existingRecord;
      attendanceType.options[0].textContent = visible > 0
        ? (existingRecord ? 'Conservar sin tipo' : 'Selecciona un tipo')
        : 'Sin tipos para este rol';
    };
    attendanceEmployee?.addEventListener('change', syncAttendanceTypes);
    syncAttendanceTypes();
  }

  const favoritesRoot = document.getElementById('navFavsList');
  if (document.querySelector('.iu-admin-shell')) {
    const storageKey = 'jocarsa_asistencias_favoritos_v2';
    const loadFavorites = () => {
      try { return JSON.parse(localStorage.getItem(storageKey) || '[]'); } catch { return []; }
    };
    const saveFavorites = items => localStorage.setItem(storageKey, JSON.stringify(items));
    const normalize = href => {
      try { const url = new URL(href, window.location.href); return url.pathname + url.search; } catch { return href; }
    };
    const renderFavorites = () => {
      const items = loadFavorites();
      const count = document.querySelector('[data-group-count="favorites"]');
      if (count) count.textContent = items.length;
      document.querySelectorAll('.fav-star[data-fav-href]').forEach(button => {
        const active = items.some(item => normalize(item.href) === normalize(button.dataset.favHref));
        button.classList.toggle('is-fav', active);
        button.textContent = active ? '★' : '☆';
      });
      if (!favoritesRoot) return;
      favoritesRoot.replaceChildren();
      if (!items.length) {
        const empty = document.createElement('article');
        empty.className = 'activo';
        const link = document.createElement('div');
        link.className = 'ju-center-link';
        const strong = document.createElement('strong');
        strong.textContent = 'Sin favoritos';
        const text = document.createElement('div');
        text.className = 'linea2';
        text.textContent = 'Pulsa la estrella de cualquier elemento para añadirlo.';
        link.append(strong, text);
        empty.appendChild(link);
        favoritesRoot.appendChild(empty);
        return;
      }
      items.forEach(item => {
        const article = document.createElement('article');
        const link = document.createElement('a');
        link.className = 'ju-center-link';
        link.href = item.href;
        const line = document.createElement('div');
        line.className = 'linea1';
        const strong = document.createElement('strong');
        strong.textContent = item.title;
        const type = document.createElement('span');
        type.textContent = item.type || 'Favorito';
        line.append(strong, type);
        const description = document.createElement('div');
        description.className = 'linea2';
        description.textContent = 'Acceso favorito';
        link.append(line, description);
        const remove = document.createElement('button');
        remove.type = 'button';
        remove.className = 'fav-star is-fav';
        remove.textContent = '★';
        remove.addEventListener('click', () => {
          saveFavorites(loadFavorites().filter(candidate => normalize(candidate.href) !== normalize(item.href)));
          renderFavorites();
        });
        article.append(link, remove);
        favoritesRoot.appendChild(article);
      });
    };
    document.querySelectorAll('.fav-star[data-fav-href]').forEach(button => {
      button.addEventListener('click', event => {
        event.preventDefault();
        event.stopPropagation();
        const items = loadFavorites();
        const href = button.dataset.favHref;
        const exists = items.some(item => normalize(item.href) === normalize(href));
        saveFavorites(exists ? items.filter(item => normalize(item.href) !== normalize(href)) : [...items, { href, title: button.dataset.favTitle, type: button.dataset.favType }]);
        renderFavorites();
      });
    });
    renderFavorites();
  }
})();


(() => {
  if (!document.querySelector('.iu-admin-shell')) return;

  const normalize = value => (value || '')
    .toLocaleLowerCase('es-ES')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .trim();

  const compareValues = (left, right) => {
    const a = left.trim();
    const b = right.trim();
    const numberA = Number(a.replace(/\s/g, '').replace(',', '.'));
    const numberB = Number(b.replace(/\s/g, '').replace(',', '.'));
    if (a !== '' && b !== '' && Number.isFinite(numberA) && Number.isFinite(numberB)) {
      return numberA - numberB;
    }
    const dateA = /^\d{4}-\d{2}-\d{2}/.test(a) ? Date.parse(a.slice(0, 10)) : NaN;
    const dateB = /^\d{4}-\d{2}-\d{2}/.test(b) ? Date.parse(b.slice(0, 10)) : NaN;
    if (Number.isFinite(dateA) && Number.isFinite(dateB)) return dateA - dateB;
    return a.localeCompare(b, 'es', {numeric: true, sensitivity: 'base'});
  };

  document.querySelectorAll('.table-wrap table').forEach((table, tableIndex) => {
    const tbody = table.tBodies[0];
    const headers = [...(table.tHead?.rows[0]?.cells || [])];
    if (!tbody || !headers.length || table.dataset.filterSortReady === '1') return;
    table.dataset.filterSortReady = '1';

    const wrapper = table.closest('.table-wrap');
    const container = wrapper?.parentElement;
    if (container?.querySelector(':scope > .admin-server-table-tools')) return;
    const toolbar = document.createElement('div');
    toolbar.className = 'admin-table-tools';
    const filter = document.createElement('input');
    filter.type = 'search';
    filter.placeholder = 'Filtrar esta tabla…';
    filter.setAttribute('aria-label', `Filtrar tabla ${tableIndex + 1}`);
    const counter = document.createElement('span');
    counter.className = 'admin-table-count';
    toolbar.append(filter, counter);
    wrapper.parentNode.insertBefore(toolbar, wrapper);

    const rows = () => [...tbody.rows].filter(row => !row.querySelector('.empty'));
    const applyFilter = () => {
      const query = normalize(filter.value);
      let visible = 0;
      const allRows = rows();
      allRows.forEach(row => {
        const matches = !query || normalize(row.textContent).includes(query);
        row.hidden = !matches;
        if (matches) visible++;
      });
      counter.textContent = `${visible} de ${allRows.length}`;
    };

    headers.forEach((header, columnIndex) => {
      const label = header.textContent.trim();
      if (!label || /acciones|decisión|^$/.test(normalize(label))) return;
      header.classList.add('admin-sortable');
      header.tabIndex = 0;
      header.title = 'Ordenar por esta columna';
      const sort = () => {
        const direction = header.dataset.sortDirection === 'asc' ? 'desc' : 'asc';
        headers.forEach(item => { delete item.dataset.sortDirection; item.classList.remove('sort-asc', 'sort-desc'); });
        header.dataset.sortDirection = direction;
        header.classList.add(direction === 'asc' ? 'sort-asc' : 'sort-desc');
        rows()
          .sort((rowA, rowB) => compareValues(rowA.cells[columnIndex]?.textContent || '', rowB.cells[columnIndex]?.textContent || '') * (direction === 'asc' ? 1 : -1))
          .forEach(row => tbody.appendChild(row));
        applyFilter();
      };
      header.addEventListener('click', sort);
      header.addEventListener('keydown', event => {
        if (event.key === 'Enter' || event.key === ' ') {
          event.preventDefault();
          sort();
        }
      });
    });

    filter.addEventListener('input', applyFilter);
    applyFilter();
  });

  const vacationScroll = document.querySelector('.vacation-timeline-scroll');
  if (vacationScroll) {
    const zoom = document.querySelector('#vacationZoom');
    const zoomValue = document.querySelector('#vacationZoomValue');
    const storageKey = 'ja-vacation-day-width';
    const clampZoom = value => Math.min(64, Math.max(18, Number(value) || 32));
    const readStoredZoom = () => {
      try { return localStorage.getItem(storageKey) || 32; } catch { return 32; }
    };
    const saveZoom = value => {
      try { localStorage.setItem(storageKey, String(value)); } catch { /* Preferencias no disponibles. */ }
    };
    const currentDayWidth = () => parseFloat(getComputedStyle(vacationScroll).getPropertyValue('--vacation-day-width')) || 32;
    const personWidth = () => vacationScroll.querySelector('.vacation-person-column')?.getBoundingClientRect().width || 240;
    const setZoom = value => {
      const width = clampZoom(value);
      vacationScroll.style.setProperty('--vacation-day-width', `${width}px`);
      if (zoom) zoom.value = String(width);
      if (zoomValue) zoomValue.textContent = `${Math.round(width / 32 * 100)}%`;
      return width;
    };
    const centerToday = () => {
      const todayHeader = vacationScroll.querySelector('.vacation-date-head.is-today');
      if (!todayHeader) return;
      const timelineViewportCenter = personWidth() + Math.max(0, vacationScroll.clientWidth - personWidth()) / 2;
      const target = todayHeader.offsetLeft + todayHeader.offsetWidth / 2 - timelineViewportCenter;
      vacationScroll.scrollLeft = Math.max(0, Math.min(vacationScroll.scrollWidth - vacationScroll.clientWidth, target));
    };
    const scheduleCenter = () => requestAnimationFrame(() => requestAnimationFrame(centerToday));

    setZoom(readStoredZoom());
    zoom?.addEventListener('input', () => {
      const previousWidth = currentDayWidth();
      const centeredDay = Math.max(0, (vacationScroll.scrollLeft + vacationScroll.clientWidth / 2 - personWidth()) / previousWidth);
      const nextWidth = setZoom(zoom.value);
      saveZoom(nextWidth);
      if (vacationScroll.querySelector('.vacation-date-head.is-today')) {
        centerToday();
      } else {
        vacationScroll.scrollLeft = Math.max(0, personWidth() + centeredDay * nextWidth - vacationScroll.clientWidth / 2);
      }
    });
    scheduleCenter();
    setTimeout(centerToday, 120);
    window.addEventListener('load', scheduleCenter, {once: true});
  }

  const todayOverview = document.querySelector('[data-today-auto-refresh]');
  if (todayOverview) {
    const seconds = Math.max(15, Number(todayOverview.dataset.todayAutoRefresh) || 60);
    window.setTimeout(() => window.location.reload(), seconds * 1000);
  }
})();

(() => {
 const form=document.getElementById('meetingGridForm'); if(form){let painting=false,saveTimer=null,controller=null;const status=document.getElementById('meetingAutosave');const refresh=()=>{form.querySelectorAll('input[data-meeting-hidden]').forEach(x=>x.remove());form.querySelectorAll('.meeting-slot.selected').forEach(slot=>{const i=document.createElement('input');i.type='hidden';i.dataset.meetingHidden='1';i.name=`meeting_slots[${slot.dataset.day}][${slot.dataset.time}]`;i.value='1';form.appendChild(i);});const t=document.getElementById('meetingTotal');if(t)t.textContent=`${(form.querySelectorAll('.meeting-slot.selected').length*.5).toLocaleString('es-ES',{minimumFractionDigits:1})} horas de reuniones/eventos`;};const save=()=>{refresh();clearTimeout(saveTimer);saveTimer=setTimeout(async()=>{if(controller)controller.abort();controller=new AbortController();const fd=new FormData(form);fd.set('autosave','1');if(status){status.textContent='Guardando…';status.classList.add('saving');}try{const r=await fetch(location.href,{method:'POST',body:fd,signal:controller.signal,headers:{'X-Requested-With':'fetch'}});const j=await r.json();if(!j.ok)throw new Error();if(status){status.textContent='Guardado '+(j.saved_at||'');status.classList.remove('saving','error');}}catch(e){if(e.name!=='AbortError'&&status){status.textContent='Error al guardar';status.classList.remove('saving');status.classList.add('error');}}},180);};const paint=slot=>{if(!slot)return;slot.classList.toggle('selected');slot.setAttribute('aria-checked',slot.classList.contains('selected')?'true':'false');refresh();save();};form.addEventListener('pointerdown',e=>{const x=e.target.closest('.meeting-slot');if(!x)return;e.preventDefault();painting=true;paint(x);x.dataset.paintSeen='1';});form.addEventListener('pointerover',e=>{if(painting){const x=e.target.closest('.meeting-slot');if(x&&!x.dataset.paintSeen){x.dataset.paintSeen='1';paint(x);}}});document.addEventListener('pointerup',()=>{painting=false;form.querySelectorAll('.meeting-slot').forEach(x=>delete x.dataset.paintSeen);});form.querySelector('[name="meeting_title"]')?.addEventListener('change',save);refresh();}
 const filters=document.querySelector('.statistics-filter'); if(filters){const sync=()=>{const mode=filters.querySelector('[name="range_mode"]:checked')?.value||'manual';filters.querySelectorAll('.range-manual').forEach(x=>x.hidden=mode!=='manual');filters.querySelectorAll('.range-week').forEach(x=>x.hidden=mode!=='week');filters.querySelectorAll('.range-month').forEach(x=>x.hidden=mode!=='month');};filters.querySelectorAll('[name="range_mode"]').forEach(x=>x.addEventListener('change',sync));sync();}
})();
```

### assets/jocarsa-iu.css

```css
:root {
  --brand: darksalmon;
  --brand-50: #fff8f5;
  --brand-100: #fce8e0;
  --brand-200: #f7cbbb;
  --brand-300: #e9967a;
  --brand-400: #d77f66;
  --brand-500: #b96050;
  --brand-600: #97483e;
  --brand-700: #73352f;
  --brand-800: #4e2724;
  --brand-900: #2c1d1b;
  --ink: #211b1a;
  --muted: #746965;
  --line: #e5d8d3;
  --paper: #fffdfc;
  --canvas: #f3ebe8;
  --ok: #26734d;
  --danger: #a63737;
  --warning: #9b681d;
  --cut: 12px;
  --sidebar: 250px;
  --rail: 310px;
}

* { box-sizing: border-box; }

html { min-height: 100%; background: var(--canvas); }

body {
  min-height: 100vh;
  margin: 0;
  color: var(--ink);
  background: var(--canvas);
  font: 15px/1.5 Ubuntu, Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
}

button, input, select, textarea { font: inherit; }
a { color: inherit; }

.chaflan {
  clip-path: polygon(0 0, calc(100% - var(--cut)) 0, 100% var(--cut), 100% 100%, var(--cut) 100%, 0 calc(100% - var(--cut)));
}

.topbar {
  min-height: 76px;
  padding: 12px 18px;
  display: flex;
  align-items: center;
  gap: 18px;
  color: white;
  background: var(--brand-900);
  border-bottom: 5px solid var(--brand);
}

.brand { min-width: 280px; display: flex; align-items: center; gap: 12px; }
.brand-mark {
  width: 44px;
  height: 44px;
  display: grid;
  place-items: center;
  flex: 0 0 auto;
  color: var(--brand-900);
  background: rgba(255,255,255,.96);
  font-weight: 900;
  letter-spacing: -.08em;
  clip-path: polygon(0 0, 32px 0, 44px 12px, 44px 44px, 12px 44px, 0 32px);
}
.brand-mark img{width:72%;height:72%;display:block;object-fit:contain;}
.brand h1 { margin: 0; font-size: 18px; line-height: 1.1; letter-spacing: -.02em; }
.brand p { margin: 4px 0 0; color: var(--brand-200); font-size: 12px; }
.topbar-spacer { flex: 1; }
.topbar-user { text-align: right; }
.topbar-user strong, .topbar-user small { display: block; }
.topbar-user small { color: var(--brand-200); }

.button, button.button {
  min-height: 40px;
  padding: 9px 15px;
  border: 1px solid var(--brand-600);
  border-radius: 0;
  color: var(--brand-800);
  background: var(--paper);
  font-weight: 750;
  text-decoration: none;
  cursor: pointer;
  clip-path: polygon(0 0, calc(100% - 8px) 0, 100% 8px, 100% 100%, 8px 100%, 0 calc(100% - 8px));
}
.button:hover { background: var(--brand-100); }
.button.primary { color: var(--brand-900); background: var(--brand); border-color: var(--brand); }
.button.primary:hover { background: var(--brand-200); }
.button.dark { color: #fff; background: var(--brand-800); border-color: var(--brand-700); }
.button.danger { color: #fff; background: var(--danger); border-color: var(--danger); }
.button.small { min-height: 32px; padding: 6px 10px; font-size: 13px; }
.button[disabled] { opacity: .55; cursor: not-allowed; }

.app-shell {
  min-height: calc(100vh - 76px);
  display: grid;
  grid-template-columns: var(--sidebar) var(--rail) minmax(0, 1fr);
}

.sidebar, .rail, .workspace { min-width: 0; }
.sidebar { padding: 20px 14px; color: #fff; background: var(--brand-800); }
.rail { padding: 20px 14px; background: var(--brand-100); border-right: 1px solid var(--line); overflow-y: auto; }
.workspace { padding: 24px; background: var(--paper); overflow-x: auto; }

.nav-title, .rail-title, .eyebrow {
  margin: 0 0 10px;
  color: var(--brand-500);
  font-size: 11px;
  font-weight: 900;
  letter-spacing: .14em;
  text-transform: uppercase;
}
.sidebar .nav-title { color: var(--brand-200); }
.nav-list, .item-list { display: grid; gap: 5px; margin: 0; padding: 0; list-style: none; }
.nav-list a, .item-list a {
  display: flex;
  align-items: center;
  gap: 10px;
  min-height: 44px;
  padding: 9px 12px;
  border-left: 4px solid transparent;
  text-decoration: none;
}
.nav-list a { color: var(--brand-100); }
.nav-list a:hover { background: rgba(255,255,255,.07); }
.nav-list a.active { color: #fff; background: var(--brand-700); border-left-color: var(--brand); }
.nav-icon {
  width: 26px;
  height: 26px;
  display: grid;
  place-items: center;
  color: var(--brand-900);
  background: var(--brand-200);
  font-size: 12px;
  font-weight: 900;
  clip-path: polygon(0 0, 20px 0, 26px 6px, 26px 26px, 6px 26px, 0 20px);
}
.item-list a { color: var(--brand-800); background: rgba(255,255,255,.48); }
.item-list a:hover, .item-list a.active { background: #fff; border-left-color: var(--brand-500); }
.item-list strong, .item-list small { display: block; }
.item-list small { color: var(--muted); }

.page-head { margin-bottom: 22px; display: flex; align-items: end; justify-content: space-between; gap: 20px; }
.page-head h2 { margin: 0; font-size: clamp(24px, 3vw, 36px); line-height: 1.05; letter-spacing: -.035em; }
.page-head p { margin: 7px 0 0; color: var(--muted); }
.actions { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; }

.metrics { display: grid; grid-template-columns: repeat(4, minmax(150px, 1fr)); gap: 12px; margin-bottom: 20px; }
.metric, .card {
  position: relative;
  padding: 18px;
  border: 1px solid var(--line);
  background: #fff;
  clip-path: polygon(0 0, calc(100% - 14px) 0, 100% 14px, 100% 100%, 14px 100%, 0 calc(100% - 14px));
}
.metric::before, .card::before { content: ""; position: absolute; left: 0; top: 0; bottom: 0; width: 5px; background: var(--brand); }
.metric span { display: block; color: var(--muted); font-size: 12px; text-transform: uppercase; letter-spacing: .08em; }
.metric strong { display: block; margin-top: 5px; font-size: 29px; line-height: 1; }
.card + .card { margin-top: 14px; }
.card h3 { margin: 0 0 14px; font-size: 18px; }
.card h4 { margin: 18px 0 8px; }

.table-wrap { width: 100%; overflow: auto; border: 1px solid var(--line); }
table { width: 100%; border-collapse: collapse; background: #fff; }
th, td { padding: 11px 12px; border-bottom: 1px solid var(--line); text-align: left; vertical-align: top; white-space: nowrap; }
th { position: sticky; top: 0; color: #fff; background: var(--brand-800); font-size: 12px; text-transform: uppercase; letter-spacing: .06em; }
tbody tr:hover { background: var(--brand-50); }
td.wrap { white-space: normal; min-width: 220px; }

.badge { display: inline-flex; align-items: center; min-height: 24px; padding: 2px 8px; color: var(--brand-800); background: var(--brand-100); border-left: 3px solid var(--brand-500); font-size: 12px; font-weight: 800; }
.badge.ok { color: #fff; background: var(--ok); border-color: var(--ok); }
.badge.danger { color: #fff; background: var(--danger); border-color: var(--danger); }
.badge.warning { color: #fff; background: var(--warning); border-color: var(--warning); }

.grid-2 { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; }
.grid-3 { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 14px; }
.stack { display: grid; gap: 13px; }
.inline-form { display: flex; align-items: end; gap: 10px; flex-wrap: wrap; }

label { display: grid; gap: 6px; color: var(--brand-800); font-size: 13px; font-weight: 750; }
input, select, textarea {
  width: 100%;
  min-height: 42px;
  padding: 9px 11px;
  border: 1px solid #cdbdb7;
  border-radius: 0;
  color: var(--ink);
  background: #fff;
  outline: 0;
}
textarea { min-height: 100px; resize: vertical; }
input:focus, select:focus, textarea:focus { border-color: var(--brand-500); box-shadow: inset 4px 0 var(--brand); }
input[type="checkbox"] { width: 18px; min-height: 18px; accent-color: var(--brand-600); }
.check { display: flex; align-items: center; gap: 8px; }

.notice { margin: 0 0 16px; padding: 12px 14px; border-left: 5px solid var(--brand-500); background: var(--brand-100); }
.notice.ok { color: #154b32; border-color: var(--ok); background: #e5f4eb; }
.notice.danger { color: #6d2020; border-color: var(--danger); background: #f9e4e4; }
.muted { color: var(--muted); }
.empty { padding: 28px; text-align: center; color: var(--muted); border: 1px dashed #cdbdb7; background: var(--brand-50); }

body[data-area="login"] {
  min-height: 100vh;
  overflow: auto;
  background: url(jocarsa-asistencias.png) center center / cover fixed no-repeat;
}
.ju-login-screen { min-height: 100vh; display: grid; place-items: center; padding: 36px 20px; }
.ju-login-layout {
  width: min(980px,100%);
  display: grid;
  grid-template-columns: minmax(300px,420px) minmax(340px,520px);
  justify-content: center;
  align-items: stretch;
  gap: 18px;
}
.ju-login-side,.ju-login-card {
  --cut: 22px;
  clip-path: polygon(0 0,calc(100% - var(--cut)) 0,100% var(--cut),100% 100%,var(--cut) 100%,0 calc(100% - var(--cut)));
}
.ju-login-side {
  min-height: 560px;
  padding: 30px;
  display: flex;
  flex-direction: column;
  color: #fff;
  background: var(--brand-900);
  border-bottom: 7px solid var(--brand);
  box-shadow: 0 24px 70px rgba(34,15,12,.3);
}
.ju-login-brand { display: flex; align-items: center; gap: 14px; }
.ju-login-logo-frame {
  width: 62px;
  height: 62px;
  flex: 0 0 auto;
  display: grid;
  place-items: center;
  background: rgba(255,255,255,.97);
  clip-path: polygon(0 0,47px 0,62px 15px,62px 62px,15px 62px,0 47px);
}
.ju-login-logo-frame img { width: 72%; height: 72%; display: block; object-fit: contain; }
.ju-login-brand h2 { margin: 2px 0 0; color: #fff; font-size: 19px; letter-spacing: -.025em; }
.ju-login-brand p { margin: 4px 0 0; color: rgba(255,255,255,.7); font-size: 12px; }
.ju-login-overline { margin: 0; color: var(--brand); font-size: 11px; font-weight: 900; letter-spacing: .12em; text-transform: uppercase; }
.ju-login-side-copy { margin: auto 0; padding: 44px 0 28px; }
.ju-login-side-copy h3 { margin: 9px 0 14px; color: #fff; font-size: clamp(28px,4vw,42px); line-height: 1.03; letter-spacing: -.045em; }
.ju-login-side-copy > p:not(.ju-login-overline) { max-width: 38ch; margin: 0; color: rgba(255,255,255,.72); line-height: 1.55; }
.ju-login-feature-list { margin: 22px 0 0; padding: 0; display: grid; gap: 10px; list-style: none; }
.ju-login-feature-list li { position: relative; padding-left: 21px; color: rgba(255,255,255,.9); font-size: 13px; font-weight: 750; }
.ju-login-feature-list li::before { content: ''; width: 9px; height: 9px; position: absolute; left: 0; top: .36em; background: var(--brand); transform: rotate(45deg); }
.ju-login-context { padding-top: 17px; display: grid; gap: 3px; border-top: 1px solid rgba(255,255,255,.18); }
.ju-login-context span { color: rgba(255,255,255,.56); font-size: 10px; font-weight: 900; letter-spacing: .1em; text-transform: uppercase; }
.ju-login-context strong { color: #fff; font-size: 13px; }
.ju-login-card {
  padding: 38px;
  min-height: 560px;
  align-self: stretch;
  display: flex;
  flex-direction: column;
  color: var(--ink);
  background: #fff;
  border-left: 7px solid var(--brand);
  box-shadow: 0 24px 70px rgba(34,15,12,.28);
}
.ju-login-card-head { margin-bottom: 26px; }
.ju-login-card-head h1 { margin: 8px 0 7px; font-size: clamp(29px,4vw,39px); line-height: 1.02; letter-spacing: -.045em; }
.ju-login-card-head > p:last-child { margin: 0; color: var(--muted); line-height: 1.5; }
.ju-login-form { display: grid; gap: 18px; }
.ju-login-form label { display: grid; gap: 7px; }
.ju-login-form label span { font-size: 12px; font-weight: 900; letter-spacing: .04em; text-transform: uppercase; }
.ju-login-form input { min-height: 48px; padding: 11px 13px; background: var(--brand-50); }
.ju-login-submit { width: 100%; min-height: 50px!important; margin-top: 2px; }
.ju-login-footer { margin-top: auto; padding-top: 17px; display: flex; justify-content: space-between; gap: 12px; color: var(--muted); border-top: 1px solid var(--line); font-size: 11px; }
.ju-login-footer strong { color: var(--brand-800); }

.front-shell { min-height: 100vh; background: url(jocarsa-asistencias.png) center center / cover fixed no-repeat; }
.front-main { width: min(1080px, calc(100% - 32px)); margin: 0 auto; padding: 24px 0 90px; }
.front-hero { display: grid; grid-template-columns: 1.2fr .8fr; gap: 14px; margin-bottom: 14px; }
.clock-card { display: grid; align-content: center; min-height: 250px; color: #fff; background: var(--brand-800); }
.clock-card::before { background: var(--brand); }
.clock { margin: 5px 0; font-size: clamp(45px, 9vw, 78px); font-weight: 900; line-height: 1; letter-spacing: -.06em; font-variant-numeric: tabular-nums; }
.shift-actions { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 20px; }
.shift-actions .button { min-height: 58px; }
.attendance-type-picker { margin-top: 18px; padding-top: 15px; border-top: 1px solid rgba(255,255,255,.24); }
.attendance-type-title { margin: 0 0 9px; color: #fff; font-size: 13px; font-weight: 900; text-transform: uppercase; letter-spacing: .06em; }
.attendance-type-buttons { display: grid; grid-template-columns: repeat(auto-fit,minmax(145px,1fr)); gap: 9px; }
.attendance-type-button { min-height: 62px; padding: 10px 12px; display: grid; align-content: center; gap: 2px; color: #fff; background: rgba(255,255,255,.08); border: 2px solid rgba(255,255,255,.3); cursor: pointer; text-align: left; }
.attendance-type-button strong { font-size: 16px; }
.attendance-type-button small { color: var(--brand-200); font-size: 10px; font-weight: 800; text-transform: uppercase; }
.attendance-type-button.selected { color: var(--brand-900); background: darksalmon; border-color: #fff; box-shadow: inset 5px 0 #fff; }
.attendance-type-button.selected small { color: var(--brand-900); }
.attendance-type-button[disabled] { cursor: default; opacity: .78; }
.attendance-type-picker > small { margin-top: 8px; display: block; color: var(--brand-200); }
.front-nav { position: fixed; left: 50%; bottom: 14px; z-index: 20; width: min(1040px, calc(100% - 28px)); transform: translateX(-50%); }
.front-nav-toggle { display: none; }
.front-nav-options { display: grid; grid-auto-flow: column; grid-auto-columns: minmax(0,1fr); color: #fff; background: var(--brand-900); border-top: 4px solid var(--brand); clip-path: polygon(0 0,calc(100% - 12px) 0,100% 12px,100% 100%,12px 100%,0 calc(100% - 12px)); }
.front-nav-options button { min-width: 0; padding: 12px 8px; border: 0; color: var(--brand-200); background: transparent; font-weight: 800; cursor: pointer; }
.front-nav-options button:hover,.front-nav-options button:focus-visible { color: #fff; background: rgba(255,255,255,.1); outline: 0; }
.front-nav-options button.active { color: var(--brand-900); background: var(--brand); }
.tab-panel[hidden] { display: none; }

.toast-stack { position: fixed; right: 18px; bottom: 18px; z-index: 100; width: min(380px, calc(100% - 36px)); display: grid; gap: 8px; }
.toast { padding: 13px 40px 13px 15px; position: relative; color: #fff; background: var(--brand-800); border-left: 6px solid var(--brand); }
.toast.ok { border-left-color: #62c892; }
.toast.danger { border-left-color: #ff7777; }
.toast button { position: absolute; right: 8px; top: 7px; border: 0; color: #fff; background: transparent; font-size: 22px; cursor: pointer; }

pre.import-summary { max-height: 360px; padding: 14px; overflow: auto; color: #fce8e0; background: var(--brand-900); }

/* Correcciones bilaterales e integridad de asistencia ------------------- */
.attendance-editor { border-left: 7px solid darksalmon; }
.integrity-card { border-left-width: 7px; }
.integrity-card.integrity-ok { border-left-color: var(--ok); }
.integrity-card.integrity-danger { border-left-color: var(--danger); background: #fff4f4; }
.integrity-summary { display: flex; align-items: center; justify-content: space-between; gap: 22px; }
.integrity-summary h3,.integrity-summary p { margin: 0; }
.integrity-summary p:last-child { margin-top: 6px; color: var(--muted); }
.integrity-seal { width: 58px; height: 58px; display: grid; place-items: center; flex: 0 0 auto; color: #fff; background: var(--ok); font-size: 32px; font-weight: 900; clip-path: polygon(0 0,calc(100% - 12px) 0,100% 12px,100% 100%,12px 100%,0 calc(100% - 12px)); }
.integrity-danger .integrity-seal { background: var(--danger); }
.integrity-errors { margin: 14px 0 0; color: var(--danger); }
.audit-hash { width: 260px; max-width: 34vw; margin: 3px 0; display: block; overflow: hidden; color: #5d4039; font-family: ui-monospace,SFMono-Regular,Consolas,monospace; font-size: 10px; text-overflow: ellipsis; white-space: nowrap; }
.audit-hash-head { width: 100%; max-width: none; margin-top: 14px; padding: 9px; color: #fff; background: var(--brand-900); }
.audit-log-wrap { max-height: 520px; }
.audit-log-wrap small { color: var(--muted); }

.correction-list { display: grid; gap: 14px; }
.correction-card { border-left: 7px solid #9b8f8b; }
.correction-card.is-pending { border-left-color: darksalmon; }
.correction-card-head { display: flex; align-items: start; justify-content: space-between; gap: 16px; }
.correction-card-head h3,.correction-card-head p { margin: 0; }
.correction-card-head p:last-child { margin-top: 5px; color: var(--muted); font-size: 12px; }
.correction-comparison { margin: 18px 0; display: grid; grid-template-columns: 1fr auto 1fr; align-items: stretch; gap: 10px; }
.correction-comparison > div:not(.correction-arrow) { padding: 14px; display: grid; gap: 5px; background: var(--brand-50); border: 1px solid var(--line); }
.correction-comparison small { color: var(--brand-700); font-weight: 900; }
.correction-comparison strong { font-size: 18px; }
.correction-arrow { display: grid; place-items: center; color: var(--brand-600); font-size: 26px; font-weight: 900; }
.correction-reason,.correction-response { margin: 12px 0; padding: 12px 14px; background: #fff8f5; border-left: 4px solid var(--brand-400); }
.correction-reason p,.correction-response p { margin: 5px 0 0; }
.correction-response small { color: var(--muted); }
.correction-hashes { margin-top: 15px; display: grid; grid-template-columns: 150px minmax(0,1fr); gap: 5px 9px; align-items: center; }
.correction-hashes span { color: var(--muted); font-size: 11px; font-weight: 800; }
.correction-hashes code,.front-audit-hash { min-width: 0; display: block; overflow: hidden; font-size: 10px; text-overflow: ellipsis; white-space: nowrap; }
.integrity-front { padding: 8px 11px; display: inline-flex; color: #fff; font-size: 12px; font-weight: 900; }
.integrity-front.ok { background: var(--ok); }
.integrity-front.danger { background: var(--danger); }
.setting-toggle { padding: 16px; display: grid; grid-template-columns: auto 1fr; align-items: start; gap: 13px; background: var(--brand-50); border-left: 5px solid var(--brand); cursor: pointer; }
.setting-toggle input { width: 22px; height: 22px; margin-top: 2px; }
.setting-toggle span { display: grid; gap: 4px; }
.setting-toggle small { color: var(--muted); }
.page-head.compact { margin-top: 22px; }

@media (max-width: 1050px) {
  .app-shell { grid-template-columns: 210px minmax(0, 1fr); }
  .rail { display: none; }
  .metrics { grid-template-columns: repeat(2, 1fr); }
}

@media (max-width: 720px) {
  .topbar { min-height: auto; flex-wrap: wrap; }
  .brand { min-width: 0; flex: 1; }
  .topbar-user { display: none; }
  .app-shell { display: block; }
  .sidebar { padding: 8px; overflow-x: auto; }
  .nav-title { display: none; }
  .nav-list { display: flex; width: max-content; }
  .nav-list a { min-height: 40px; padding: 7px 10px; }
  .nav-list a span:last-child { white-space: nowrap; }
  .workspace { padding: 16px; }
  .page-head { display: block; }
  .page-head .actions { margin-top: 12px; }
  .metrics, .grid-2, .grid-3, .front-hero { grid-template-columns: 1fr; }
  .clock-card { min-height: 220px; }
  .correction-comparison { grid-template-columns: 1fr; }
  .correction-arrow { transform: rotate(90deg); }
  .correction-hashes { grid-template-columns: 1fr; }
  .front-main { width: calc(100% - 20px); padding: 16px 0 calc(92px + env(safe-area-inset-bottom)); }
  .front-main .card,.front-main .metric { padding: 15px; }
  .front-main .page-head { margin-bottom: 16px; }
  .shift-actions { grid-template-columns: 1fr; }
  .attendance-view-switch { width: 100%; display: grid; grid-template-columns: 1fr 1fr; }
  .attendance-view-switch .button { width: 100%; }
  .front-nav { bottom: max(10px,env(safe-area-inset-bottom)); width: min(360px,calc(100% - 24px)); overflow: visible; }
  .front-nav-toggle { width: 100%; min-height: 58px; padding: 9px 14px; display: flex; align-items: center; justify-content: space-between; gap: 18px; border: 0; border-top: 4px solid var(--brand); color: #fff; background: var(--brand-900); box-shadow: 0 12px 32px rgba(40,19,15,.34); cursor: pointer; text-align: left; clip-path: polygon(0 0,calc(100% - 11px) 0,100% 11px,100% 100%,11px 100%,0 calc(100% - 11px)); }
  .front-nav-toggle span:first-child { min-width: 0; display: grid; gap: 2px; }
  .front-nav-toggle small { color: var(--brand-200); font-size: 9px; font-weight: 900; letter-spacing: .08em; text-transform: uppercase; }
  .front-nav-toggle strong { overflow: hidden; color: #fff; font-size: 15px; text-overflow: ellipsis; white-space: nowrap; }
  .front-nav-toggle-action { display: inline-flex; align-items: center; gap: 8px; color: var(--brand); font-size: 11px; font-weight: 900; letter-spacing: .06em; text-transform: uppercase; }
  .front-nav-toggle-action i { width: 24px; height: 24px; display: grid; place-items: center; color: var(--brand-900); background: var(--brand); font-style: normal; font-size: 16px; }
  .front-nav-options { max-height: min(64vh,480px); position: absolute; left: 0; right: 0; bottom: calc(100% + 8px); display: grid; grid-auto-flow: row; grid-auto-columns: auto; grid-template-columns: 1fr; overflow-y: auto; border-top-width: 5px; box-shadow: 0 18px 42px rgba(40,19,15,.4); }
  .front-nav[data-mobile-open="false"] .front-nav-options { display: none; }
  .front-nav-options button { min-height: 48px; padding: 12px 16px; font-size: 13px; text-align: left; border-bottom: 1px solid rgba(255,255,255,.1); }
  .front-nav-options button:last-child { border-bottom: 0; }
  .front-nav-options button.active::after { content: 'Sección actual'; float: right; margin-left: 12px; font-size: 9px; letter-spacing: .06em; text-transform: uppercase; }
}

/* Administración: estructura fiel a jocarsa-iu --------------------------- */
body[data-area="admin"] { height: 100vh; overflow: hidden; background: url(jocarsa-asistencias.png) center center / cover fixed no-repeat; }

#topbar {
  height: 76px;
  padding: 10px 18px;
  display: grid;
  grid-template-columns: 315px minmax(260px, 1fr) auto minmax(170px,auto);
  align-items: center;
  gap: 16px;
  color: #fff;
  background: #744d45;
  border: 0;
}
#admin-session{min-width:0;display:grid;justify-items:end;gap:5px;padding-left:14px;border-left:1px solid rgba(255,255,255,.2);font-size:12px;text-align:right}
#admin-session span{overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:rgba(255,255,255,.8)}
#admin-session strong{color:#fff}
#admin-session a{width:max-content;color:#fff;font-weight:850;text-decoration:none;border-bottom:2px solid darksalmon}
#admin-session a:hover{color:#ffd7cc}
#marca { display: flex; align-items: center; gap: 10px; min-width: 0; }
.ju-logo {
  width: 46px; height: 56px; display: grid; place-items: center; flex: 0 0 auto;
  color: #fff; background: rgba(255,255,255,.96); font-size: 18px; font-weight: 900;
  clip-path: polygon(0 0, calc(100% - 10px) 0, 100% 10px, 100% 100%, 0 100%);
}
.ju-logo img{width:72%;height:72%;display:block;object-fit:contain;}
#marca h1 { margin: 0; color: #fff; font-size: 18px; line-height: 1; letter-spacing: -.02em; }
#marca p { margin: 4px 0 0; color: rgba(255,255,255,.75); font-size: 12px; line-height: 1.12; }
#search-form { display: grid; grid-template-columns: minmax(140px,1fr) auto auto; align-items: center; gap: 10px; }
#search-input { min-height: 42px; color: #fff; background: rgba(255,255,255,.09); border: 1px solid rgba(255,255,255,.18); }
#search-input::placeholder { color: rgba(255,255,255,.62); }
#search-input:focus { border-color: darksalmon; box-shadow: inset 4px 0 darksalmon; }
.search-btn,.search-clear,.toolbtn-link {
  min-height: 40px; padding: 9px 15px; display: inline-flex; align-items: center; justify-content: center;
  border: 0; color: #fff; background: rgba(255,255,255,.11); text-decoration: none; font-weight: 800; cursor: pointer;
  clip-path: polygon(0 0, calc(100% - 9px) 0, 100% 9px, 100% 100%, 0 100%);
}
.search-btn:hover,.search-clear:hover,.toolbtn-link:hover { background: rgba(255,255,255,.19); }
.toolbtn-link.primario { color: #5b312b; background: darksalmon; }
#herramientas { display: flex; align-items: center; gap: 10px; }

#app.iu-admin-shell {
  height: calc(100vh - 76px);
  min-height: 0;
  display: grid;
  grid-template-columns: 260px 420px minmax(0,1fr);
  background: #fbefea;
}
#bandejas,#mensajes,#contenido,#panel { min-width: 0; min-height: 0; }
#bandejas,#mensajes,#panel { overflow: auto; scrollbar-width: thin; scrollbar-color: rgba(116,77,69,.45) transparent; }
#bandejas {
  position: relative;
  z-index: 3;
  padding: 20px 16px;
  color: #fff;
  background: #754f46;
  border-right: 1px solid rgba(255,255,255,.08);
}
#bandejas > a {
  position: relative;
  min-height: 52px; margin-bottom: 14px; padding: 14px 16px; display: block;
  color: #fff; background: rgba(255,255,255,.08); text-decoration: none; font-weight: 850;
  clip-path: polygon(0 0, calc(100% - 12px) 0, 100% 12px, 100% 100%, 0 100%);
  transition: transform .2s ease, background .2s ease;
}
#bandejas > a:hover { background: rgba(255,255,255,.13); }
#bandejas > a.activo {
  right: -16px;
  z-index: 4;
  width: calc(100% + 16px);
  color: #fff;
  background: #b47465;
  box-shadow: 0 10px 24px rgba(57,31,27,.16);
  clip-path: none;
}
#bandejas > a.activo::after {
  content: "";
  position: absolute;
  top: 0;
  right: -16px;
  bottom: 0;
  width: 16px;
  background: #b47465;
}
.folder-line { display: flex; align-items: center; justify-content: space-between; gap: 10px; }
.folder-count { min-width: 22px; padding: 1px 6px; text-align: center; color: #fff; background: rgba(255,255,255,.17); font-size: 12px; }

#mensajes { position: relative; z-index: 2; padding: 20px 12px; background: #b47465; border-right: 0; }
.ju-column-title {
  min-height: 76px; margin-bottom: 16px; padding: 18px 17px; display: grid; align-content: center;
  color: #fff; background: rgba(255,255,255,.14);
  clip-path: polygon(0 0, calc(100% - 13px) 0, 100% 13px, 100% 100%, 0 100%);
}
.ju-column-title strong { font-size: 20px; }
.ju-column-title span { margin-top: 4px; color: rgba(255,255,255,.78); font-size: 12px; }
#mensajes article {
  position: relative; min-height: 87px; margin-bottom: 12px; display: grid; grid-template-columns: 1fr 44px;
  color: #70463e; background: #fff7f3;
  clip-path: polygon(0 0, calc(100% - 13px) 0, 100% 13px, 100% 100%, 0 100%);
  transition: right .2s ease, width .2s ease, background .2s ease, box-shadow .2s ease;
}
#mensajes article.activo {
  right: -12px;
  z-index: 4;
  width: calc(100% + 12px);
  color: #291b19;
  background: #fff;
  box-shadow: 0 0 0 4px rgba(233,150,122,.12), 0 12px 26px rgba(57,31,27,.10);
  clip-path: none;
}
#mensajes article.activo::after {
  content: "";
  position: absolute;
  top: 0;
  right: -12px;
  bottom: 0;
  z-index: 0;
  width: 12px;
  background: #fff;
}
#mensajes article:hover { background: #fff; }
.ju-center-link { position: relative; z-index: 2; min-width: 0; padding: 14px 9px 13px 18px; color: inherit; text-decoration: none; }
#mensajes .linea1 { display: flex; align-items: center; justify-content: space-between; gap: 12px; }
#mensajes .linea1 strong { min-width: 0; overflow: hidden; text-overflow: ellipsis; font-size: 15px; color: #101828; }
#mensajes .linea1 span { color: #875a50; font-size: 12px; }
#mensajes .linea2 { margin-top: 8px; color: #93685e; font-size: 12px; line-height: 1.25; }
.fav-star { position: relative; z-index: 2; border: 0; color: #c18c7f; background: transparent; font-size: 25px; cursor: pointer; }
.fav-star.is-fav { color: #9d5146; }
#navFavsList[hidden] { display: none; }

#contenido { background: #faefeb; overflow: hidden; }
#panel { height: 100%; padding: 24px; }
.ju-page-heading {
  margin: 0 0 20px; padding: 15px 18px; color: #3a2521; background: #fff8f5; border: 1px solid #edcfc5;
  clip-path: polygon(0 0, calc(100% - 12px) 0, 100% 12px, 100% 100%, 0 100%);
}
.ju-page-heading .linea1 { display: flex; justify-content: space-between; align-items: center; gap: 18px; }
.ju-page-heading .linea1 strong { font-size: 17px; }
.ju-page-heading p { margin: 5px 0 0; color: #8a5f55; font-size: 12px; }
.ju-pill { display: inline-flex; padding: 3px 9px; color: #4c2c27; background: #eed5ce; font-size: 11px; font-weight: 900; text-transform: uppercase; letter-spacing: .04em; }
.ju-legacy-content { display: grid; gap: 16px; }
.ju-table-caption { margin-bottom: 14px; display: flex; align-items: center; justify-content: space-between; gap: 16px; }
.ju-table-caption h3,.ju-table-caption p { margin: 0; }
.ju-table-caption p { margin-top: 3px; color: var(--muted); font-size: 12px; }
.form-span-full { grid-column: 1 / -1; }

/* Aplicaciones originales */
.application-stack { display: grid; gap: 16px; }
.application-toolbar { padding: 18px; display: flex; align-items: center; justify-content: space-between; gap: 18px; background: #fff; border: 1px solid var(--line); }
.application-toolbar h2,.application-toolbar p { margin: 0; }
.application-toolbar p { margin-top: 4px; color: var(--muted); }
.calendar-week-wrap,.sessions-table-wrap,.schedule-grid-wrap { overflow: auto; background: #fff; border: 1px solid var(--line); }
.calendar-week { min-width: 1180px; }
.calendar-week td { min-width: 120px; white-space: normal; }
.calendar-week .employee-cell { min-width: 230px; background: #fbf4f1; }
.employee-cell strong,.employee-cell span,.employee-cell small { display: block; }
.employee-cell span,.employee-cell small { margin-top: 5px; color: var(--muted); }
.shift-chip { margin-bottom: 7px; padding: 8px; color: #4e2724; background: #f8e2da; border-left: 4px solid darksalmon; }
.shift-chip strong,.shift-chip span { display: block; }
.shift-chip span { margin-top: 3px; color: #8c5d53; font-size: 10px; }
.no-shift { color: #b6a39d; }

.schedule-selector { padding: 15px; display: flex; align-items: end; flex-wrap: wrap; gap: 12px; background: #fff; border-left: 6px solid darksalmon; }
.schedule-selector label { min-width: 190px; }
.schedule-selector .form-hint { width: 100%; margin: 0; color: var(--muted); font-size: 12px; }
.schedule-grid { min-width: 820px; table-layout: fixed; }
.schedule-grid th:first-child,.schedule-grid td:first-child { width: 72px; }
.schedule-grid td { height: 12px; padding: 0; border-right: 1px solid #efe2dd; cursor: pointer; }
.schedule-grid td:first-child { padding: 3px 7px; cursor: default; color: var(--muted); font-size: 10px; }
.schedule-grid .schedule-slot:hover { background: #f8ddd5; }
.schedule-grid .schedule-slot.selected { background: darksalmon; box-shadow: inset 0 1px rgba(255,255,255,.3); }
.schedule-savebar { position: sticky; bottom: -24px; z-index: 5; padding: 12px 15px; display: flex; align-items: center; justify-content: space-between; gap: 12px; color: #fff; background: #754f46; border-top: 4px solid darksalmon; }

.employee-statistics { display: grid; grid-template-columns: 280px minmax(0,1fr); gap: 20px; }
.statistics-sidebar { min-width: 0; }
.statistics-filter { padding: 16px; background: #fff; border: 1px solid var(--line); }
.statistics-filter h3,.statistics-filter p { margin: 0; }
.statistics-filter p { margin: 6px 0 12px; color: var(--muted); font-size: 12px; }
.statistics-filter form { display: grid; gap: 9px; }
.statistics-employee-list { max-height: 620px; margin-top: 14px; display: grid; gap: 9px; overflow: auto; }
.statistics-employee { padding: 13px 14px; color: #4e2724; background: #fff8f5; border: 1px solid #ead7d1; text-decoration: none; }
.statistics-employee:hover,.statistics-employee.active { background: #fff; box-shadow: inset 7px 0 0 darksalmon; }
.statistics-employee strong,.statistics-employee span,.statistics-employee small { display: block; }
.statistics-employee span,.statistics-employee small { margin-top: 4px; color: var(--muted); font-size: 11px; }
.statistics-main { min-width: 0; display: grid; gap: 14px; }
.statistics-hero { padding: 22px; display: flex; justify-content: space-between; gap: 20px; color: #fff; background: #8e5d53; clip-path: polygon(0 0, calc(100% - 14px) 0, 100% 14px, 100% 100%, 14px 100%, 0 calc(100% - 14px)); }
.statistics-hero h2 { margin: 5px 0; font-size: 25px; }
.statistics-hero p { margin: 0; color: rgba(255,255,255,.8); }
.hero-label { padding: 3px 8px; background: rgba(255,255,255,.17); font-size: 11px; font-weight: 900; }
.statistics-hero-meta { min-width: 370px; display: grid; grid-template-columns: repeat(2,1fr); gap: 10px; }
.statistics-hero-meta div { padding: 11px; background: rgba(255,255,255,.13); }
.statistics-hero-meta strong,.statistics-hero-meta span { display: block; }
.statistics-hero-meta span { font-size: 11px; }
.statistics-kpis { display: grid; grid-template-columns: repeat(4,minmax(130px,1fr)); gap: 12px; }
.statistics-kpi { min-height: 122px; padding: 16px; background: #fff; border: 1px solid #dcdfe4; clip-path: polygon(0 0, calc(100% - 11px) 0, 100% 11px, 100% 100%, 0 100%); }
.statistics-kpi small,.statistics-kpi strong,.statistics-kpi span { display: block; }
.statistics-kpi small { color: #80584f; font-weight: 900; text-transform: uppercase; }
.statistics-kpi strong { margin-top: 9px; color: #704238; font-size: 26px; line-height: 1; }
.statistics-kpi span { margin-top: 7px; color: var(--muted); font-size: 11px; }
.statistics-two { display: grid; grid-template-columns: repeat(2,minmax(0,1fr)); gap: 14px; }
.statistics-bars { display: grid; gap: 8px; }
.statistics-bar { display: grid; grid-template-columns: 75px minmax(60px,1fr) 72px; align-items: center; gap: 9px; font-size: 11px; }
.statistics-bar > div { height: 13px; background: #f4e0d9; }
.statistics-bar i { height: 100%; display: block; background: darksalmon; }
.statistics-summary { margin: 0; display: grid; gap: 7px; }
.statistics-summary div { padding-bottom: 6px; display: flex; justify-content: space-between; border-bottom: 1px dashed #ddc9c2; }
.statistics-summary dt,.statistics-summary dd { margin: 0; }
.statistics-summary dd { font-weight: 900; }
.statistics-table-wrap { max-height: 430px; overflow: auto; }

.session-legend { display: flex; flex-wrap: wrap; gap: 8px; }
.session-legend span { padding: 7px 10px; display: flex; align-items: center; gap: 7px; background: #fff; border: 1px solid var(--line); font-size: 11px; font-weight: 800; }
.session-legend i { width: 12px; height: 12px; display: block; }
.session-legend .present,.session-state.present { background: #26734d; }
.session-legend .open,.session-state.open { color: #4b3511; background: #e9b949; }
.session-legend .error,.session-state.error { background: #a63737; }
.session-legend .absent,.session-state.absent { background: #8f8987; }
.session-legend .holiday,.session-state.holiday { background: #426d8f; }
.session-legend .noschedule,.session-state.noschedule { color: #325369; background: #cde9f4; }
.sessions-table { min-width: 1120px; }
.sessions-table th:first-child { position: sticky; left: 0; z-index: 3; min-width: 220px; }
.sessions-table th small { display: block; margin-top: 3px; color: rgba(255,255,255,.7); }
.sessions-table tbody th { color: #4e2724; background: #fff8f5; }
.sessions-table tbody th strong,.sessions-table tbody th small { display: block; }
.sessions-table tbody th small { margin-top: 4px; color: var(--muted); }
.session-state { min-height: 42px; padding: 6px; display: grid; place-items: center; color: #fff; text-align: center; text-decoration: none; font-size: 11px; font-weight: 900; }

/* Cuadrícula mensual de asistencia, adaptada desde jocarsa-asistencia ----- */
.worker-attendance-grid {
  --worker-grid-saturation: 100%;
  --worker-grid-fade: 0%;
}
.worker-grid-filters {
  padding: 14px;
  display: grid;
  grid-template-columns: repeat(3,minmax(150px,1fr)) auto auto;
  align-items: end;
  gap: 10px;
  background: #fff;
  border-left: 6px solid darksalmon;
}
.worker-grid-options { display: grid; gap: 12px; }
.worker-grid-option-row {
  display: grid;
  grid-template-columns: minmax(170px,240px) 54px minmax(170px,240px) 54px minmax(150px,1fr);
  align-items: center;
  gap: 10px;
}
.worker-grid-option-row label { min-width: 0; }
.worker-grid-option-row input[type="range"] { min-height: 22px; padding: 0; }
.worker-grid-button-row { display: flex; align-items: center; flex-wrap: wrap; gap: 8px; }
.worker-grid-button-row > strong { min-width: 78px; color: var(--brand-800); font-size: 12px; }
.worker-grid-button-row .button.active { color: #fff; background: var(--brand-700); border-color: var(--brand-700); }
.worker-grid-card { min-width: 0; }
.worker-grid-search {
  margin-bottom: 12px;
  padding: 11px 12px;
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 8px;
  background: var(--brand-50);
  border: 1px solid var(--line);
}
.worker-grid-search > strong { font-size: 12px; }
.worker-grid-search input { min-width: 240px; min-height: 36px; flex: 1 1 320px; }
.worker-grid-search .key {
  padding: 2px 6px;
  color: var(--brand-800);
  background: #fff;
  border: 1px solid #d9c9c3;
  font-size: 10px;
  font-weight: 900;
}
#workerGridSearchCount { margin-left: 4px; color: var(--muted); font-size: 11px; }
.worker-grid-table-wrap { max-height: 600px; overflow: auto; border: 1px solid var(--line); }
.worker-grid-table { width: max-content; min-width: 100%; table-layout: fixed; }
.worker-grid-table th,.worker-grid-table td { padding: 4px; text-align: center; vertical-align: middle; }
.worker-grid-table .worker-grid-name { width: 230px; min-width: 230px; max-width: 230px; left: 0; z-index: 5; text-align: left; }
.worker-grid-table .worker-grid-stats { width: 92px; min-width: 92px; left: 230px; z-index: 4; }
.worker-grid-table .worker-grid-progress { width: 120px; min-width: 120px; left: 322px; z-index: 4; }
.worker-grid-table thead .worker-grid-name,.worker-grid-table thead .worker-grid-stats,.worker-grid-table thead .worker-grid-progress { position: sticky; top: 0; }
.worker-grid-table tbody .worker-grid-name,.worker-grid-table tbody .worker-grid-stats,.worker-grid-table tbody .worker-grid-progress { position: sticky; top: auto; background: #fff; }
.worker-grid-table tbody .worker-grid-name { display: flex; align-items: center; justify-content: space-between; gap: 6px; color: var(--ink); text-transform: none; letter-spacing: 0; cursor: pointer; }
.worker-grid-person { min-width: 0; }
.worker-grid-person strong,.worker-grid-person small { display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.worker-grid-person small { margin-top: 2px; color: var(--muted); font-size: 9px; font-weight: 500; }
.worker-grid-report { padding: 2px 6px; color: var(--brand-700); background: var(--brand-100); text-decoration: none; }
.worker-grid-table tbody .worker-grid-stats { color: var(--ink); font-size: 11px; }
.worker-grid-stats .oktxt { color: #07883f; font-weight: 900; }
.worker-grid-stats .kotxt { color: #c52f35; font-weight: 900; }
.worker-grid-stats small { display: block; margin-top: 2px; color: var(--muted); font-size: 8px; }
.worker-grid-progress-track { height: 9px; background: #e6e9ec; border: 1px solid #d3d7dc; }
.worker-grid-progress-track i { height: 100%; display: block; background: #00a857; }
.worker-grid-progress strong { display: block; margin-top: 2px; color: var(--ink); font-size: 9px; }
.worker-grid-date { width: 33px; min-width: 33px; max-width: 33px; height: 104px; padding: 4px 2px !important; }
.worker-grid-date span { display: inline-block; writing-mode: vertical-rl; transform: rotate(180deg); font-size: 9px; letter-spacing: 0; }
.worker-grid-date.is-today,.worker-grid-cell.is-today { box-shadow: inset 2px 0 darksalmon, inset -2px 0 darksalmon; }
.worker-grid-cell { width: 33px; min-width: 33px; max-width: 33px; background: #fbfcfc; }
.worker-grid-cell a {
  width: 24px;
  height: 24px;
  margin: auto;
  display: grid;
  place-items: center;
  color: #fff;
  background: #d1d5db;
  border: 1px solid transparent;
  border-radius: 50%;
  filter: saturate(var(--worker-grid-saturation));
  text-decoration: none;
  font-size: 11px;
  font-weight: 950;
  transition: transform .12s ease, box-shadow .12s ease;
}
.worker-grid-cell a:hover { z-index: 3; transform: scale(1.28); box-shadow: 0 3px 8px rgba(0,0,0,.2); }
.worker-grid-cell[data-worker-state="worked"] a { background: color-mix(in srgb,#00a857 calc(100% - var(--worker-grid-fade)),white var(--worker-grid-fade)); }
.worker-grid-cell[data-worker-state="missing"] a { width: 10px; height: 10px; color: transparent; background: color-mix(in srgb,#ef3f48 calc(100% - var(--worker-grid-fade)),white var(--worker-grid-fade)); }
.worker-grid-cell[data-worker-state="open"] a { color: #4b3511; background: color-mix(in srgb,#e9b949 calc(100% - var(--worker-grid-fade)),white var(--worker-grid-fade)); }
.worker-grid-cell[data-worker-state="absence"] a { background: color-mix(in srgb,#7b858c calc(100% - var(--worker-grid-fade)),white var(--worker-grid-fade)); }
.worker-grid-cell[data-worker-state="pending"] a { background: color-mix(in srgb,darksalmon calc(100% - var(--worker-grid-fade)),white var(--worker-grid-fade)); }
.worker-grid-cell[data-worker-state="holiday"] a { background: color-mix(in srgb,#426d8f calc(100% - var(--worker-grid-fade)),white var(--worker-grid-fade)); }
.worker-grid-cell[data-worker-state="error"] a { background: color-mix(in srgb,#a63737 calc(100% - var(--worker-grid-fade)),white var(--worker-grid-fade)); }
.worker-grid-cell[data-worker-state="expected"] a { color: #325369; background: color-mix(in srgb,#cde9f4 calc(100% - var(--worker-grid-fade)),white var(--worker-grid-fade)); border-color: #79a5b8; }
.worker-grid-cell[data-worker-state="none"] a { width: 8px; height: 8px; color: transparent; background: color-mix(in srgb,#d1d5db calc(100% - var(--worker-grid-fade)),white var(--worker-grid-fade)); }
.worker-attendance-grid[data-color="0"] .worker-grid-cell a { width: 24px; height: 24px; color: var(--ink); background: #fff; border-color: #9da4aa; filter: none; }
.worker-attendance-grid[data-color="0"] .worker-grid-cell[data-worker-state="none"] a { color: transparent; }
.worker-attendance-grid[data-columns="last7"] .worker-grid-history { display: none; }
.worker-grid-table tr.keyboard-focused-row > * { box-shadow: inset 0 2px #426d8f, inset 0 -2px #426d8f; }
.worker-grid-table tr.selected-row > * { box-shadow: inset 0 2px darksalmon, inset 0 -2px darksalmon; }
.worker-grid-legend { margin-top: 10px; display: flex; align-items: center; flex-wrap: wrap; gap: 8px 13px; color: var(--muted); font-size: 10px; }
.worker-grid-legend span { display: inline-flex; align-items: center; gap: 5px; }
.worker-grid-legend i { width: 11px; height: 11px; display: block; border-radius: 50%; background: #d1d5db; }
.worker-grid-legend [data-legend-state="worked"] i { background: #00a857; }
.worker-grid-legend [data-legend-state="missing"] i { background: #ef3f48; }
.worker-grid-legend [data-legend-state="open"] i { background: #e9b949; }
.worker-grid-legend [data-legend-state="absence"] i { background: #7b858c; }
.worker-grid-legend [data-legend-state="pending"] i { background: darksalmon; }
.worker-grid-legend [data-legend-state="holiday"] i { background: #426d8f; }
.worker-grid-legend [data-legend-state="error"] i { background: #a63737; }

@media (max-width: 1250px) {
  #app.iu-admin-shell { grid-template-columns: 220px 330px minmax(0,1fr); }
  .employee-statistics { grid-template-columns: 1fr; }
  .statistics-employee-list { max-height: 300px; }
  .statistics-kpis { grid-template-columns: repeat(2,minmax(130px,1fr)); }
  .worker-grid-option-row { grid-template-columns: minmax(140px,1fr) 48px minmax(140px,1fr) 48px; }
  .worker-grid-option-row .check { grid-column: 1/-1; }
}
@media (max-width: 900px) {
  .ju-login-screen { padding: 20px 12px; }
  .ju-login-layout { max-width: 560px; grid-template-columns: 1fr; }
  .ju-login-side { min-height: 0; }
  .ju-login-side-copy { margin: 10px 0; padding: 28px 0 20px; }
  .ju-login-card { width: 100%; min-height: 0; padding: 28px; }
  body[data-area="admin"] { height: auto; overflow: auto; }
  #topbar { height: auto; grid-template-columns: 1fr; }
  #herramientas { flex-wrap: wrap; }
  #app.iu-admin-shell { height: auto; display: block; }
  #bandejas { display: flex; gap: 8px; overflow-x: auto; }
  #bandejas > a { min-width: 165px; margin: 0; }
  #bandejas > a.activo,#mensajes article.activo { right: 0; width: auto; }
  #bandejas > a.activo::after,#mensajes article.activo::after { display: none; }
  #mensajes { max-height: 360px; }
  #panel { overflow: visible; }
  .statistics-hero,.application-toolbar { align-items: stretch; flex-direction: column; }
  .statistics-hero-meta,.statistics-two { grid-template-columns: 1fr; min-width: 0; }
  .worker-grid-filters { grid-template-columns: 1fr 1fr; }
}
@media (max-width: 620px) {
  #search-form { grid-template-columns: 1fr 1fr; }
  #search-input { grid-column: 1/-1; }
  #panel { padding: 12px; }
  .statistics-kpis { grid-template-columns: 1fr; }
  .worker-grid-filters,.worker-grid-option-row { grid-template-columns: 1fr; }
  .worker-grid-option-row .check { grid-column: auto; }
}



.admin-table-tools{display:flex;align-items:center;gap:.75rem;margin:.75rem 0 .35rem}.admin-table-tools input{min-width:min(28rem,100%);padding:.65rem .75rem;border:1px solid var(--line);background:#fff}.admin-table-count{font-size:.85rem;color:var(--muted)}th.admin-sortable{cursor:pointer;user-select:none;white-space:nowrap}th.admin-sortable::after{content:' ↕';opacity:.35}th.admin-sortable.sort-asc::after{content:' ↑';opacity:1}th.admin-sortable.sort-desc::after{content:' ↓';opacity:1}.user-password-reset{display:flex;gap:.35rem;align-items:center}.user-password-reset input{width:11rem;padding:.45rem .55rem;border:1px solid var(--line)}


/* V7 · conciliación y comunicación interna laboral */
.conciliation-switch{display:flex;align-items:center;justify-content:space-between;gap:16px;padding:14px 0;margin:12px 0;border-top:1px solid var(--line);border-bottom:1px solid var(--line)}
.conciliation-switch span{display:grid;gap:3px}.conciliation-switch small{color:var(--muted)}
.conciliation-switch input{position:absolute;opacity:0;pointer-events:none}.conciliation-switch i,.switch-button{width:58px;height:30px;border:0;border-radius:20px;background:#d8d8d8;position:relative;cursor:pointer;font-size:11px;font-weight:800}
.conciliation-switch i::after{content:"";position:absolute;width:24px;height:24px;left:3px;top:3px;background:white;border-radius:50%;transition:.2s;box-shadow:0 1px 4px #0004}
.conciliation-switch input:checked+i,.switch-button.on{background:var(--brand)}.conciliation-switch input:checked+i::after{transform:translateX(28px)}
.switch-button{color:#333}.switch-button.on{color:white}
.message-color,.message-type{display:inline-flex;align-items:center;padding:5px 9px;border-left:8px solid var(--message-color,currentColor);background:#fff;border-radius:3px}.message-color{color:#222}
.communication-list{display:grid;gap:14px}.communication-card{border-left:10px solid var(--message-color)}.communication-type{display:flex;align-items:center;gap:9px}.communication-type span{font-size:1.5rem}.communication-type time{margin-left:auto;color:var(--muted);font-size:.85rem}.communication-card p{white-space:normal;line-height:1.55}
.internal-toast-area{position:fixed;right:18px;bottom:86px;z-index:9999;display:flex;flex-direction:column;gap:10px;max-width:min(430px,calc(100vw - 36px))}.internal-toast{display:flex;gap:12px;background:#fff;border-left:10px solid var(--message-color);box-shadow:0 14px 40px #0004;padding:16px;animation:internalToastIn .25s ease}.internal-toast button{border:0;background:transparent;font-size:1.4rem;order:2;margin-left:auto;align-self:flex-start;cursor:pointer}.internal-toast p{margin:.4rem 0 0;line-height:1.4}@keyframes internalToastIn{from{opacity:0;transform:translateY(12px)}to{opacity:1;transform:none}}
@media(max-width:700px){.internal-toast-area{left:12px;right:12px;bottom:84px;max-width:none}.front-nav{overflow:visible}.front-nav-options button{min-width:0}}
.admin-server-table-tools{display:flex;align-items:end;gap:12px;flex-wrap:wrap;margin:16px 0 8px;padding:12px;background:#fff8f5;border-left:5px solid darksalmon}.admin-server-table-tools label{display:grid;gap:5px;font-weight:700}.admin-server-table-tools .admin-server-search{flex:1;min-width:260px}.admin-server-table-tools input,.admin-server-table-tools select{min-height:42px}.admin-server-table-tools .admin-table-count{margin-left:auto;align-self:center}.admin-pagination{display:flex;align-items:center;justify-content:flex-end;gap:10px;margin:8px 0 12px}.admin-pagination .disabled{pointer-events:none;opacity:.45}

/* 2.0 · horarios segmentados por tipo de fichaje */
.schedule-type-palette{display:flex;align-items:center;justify-content:space-between;gap:20px;flex-wrap:wrap}
.schedule-type-palette h3{margin-bottom:4px}.schedule-type-palette p{margin:0;color:var(--muted)}
.schedule-type-buttons{display:flex;gap:8px;flex-wrap:wrap}.schedule-type-choice{display:flex;align-items:center;gap:8px;padding:9px 13px;border:2px solid transparent;background:#fff;color:var(--text);font-weight:800;cursor:pointer;clip-path:polygon(0 0,calc(100% - 10px) 0,100% 10px,100% 100%,10px 100%,0 calc(100% - 10px))}.schedule-type-choice i{width:18px;height:18px;border-radius:999px;background:var(--type-color);box-shadow:inset 0 0 0 2px rgba(255,255,255,.55)}.schedule-type-choice small{font-weight:500;color:var(--muted)}.schedule-type-choice.active{border-color:var(--brand-900);background:#fff5f1}.schedule-eraser i{background:repeating-linear-gradient(135deg,#fff 0 4px,#8b7770 4px 7px)}
.schedule-segment-list{display:flex;gap:10px;flex-wrap:wrap}.schedule-segment-pill{display:grid;grid-template-columns:auto auto;gap:2px 8px;padding:10px 15px;background:#fff;border:1px solid var(--line);color:var(--text);text-decoration:none;border-radius:999px}.schedule-segment-pill small{grid-column:1/-1;color:var(--muted)}.schedule-segment-pill.active{background:var(--brand-900);color:#fff;border-color:var(--brand-900)}.schedule-segment-pill.active small{color:var(--brand-200)}
.typed-schedule-grid{border-collapse:separate;border-spacing:0 2px}.typed-schedule-grid td{height:18px;background:transparent!important;border-right:1px solid #f0e2dc;position:relative}.typed-schedule-grid .schedule-slot span{position:absolute;inset:0 5px;background:transparent;pointer-events:none}.typed-schedule-grid .schedule-slot.selected span{background:var(--slot-color);box-shadow:inset 0 1px rgba(255,255,255,.35)}.typed-schedule-grid .schedule-slot.segment-single span{border-radius:999px}.typed-schedule-grid .schedule-slot.segment-start span{border-radius:999px 999px 0 0;bottom:-2px}.typed-schedule-grid .schedule-slot.segment-middle span{top:-2px;bottom:-2px}.typed-schedule-grid .schedule-slot.segment-end span{border-radius:0 0 999px 999px;top:-2px}.typed-schedule-grid .schedule-slot:hover span{outline:2px solid rgba(82,34,30,.28);outline-offset:-2px}.typed-schedule-grid td:first-child{height:18px;padding:2px 7px}
.worker-schedule-segments{display:grid;gap:18px}.worker-schedule-card{border-left:8px solid var(--brand)}.worker-schedule-period{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap}.worker-schedule-week{display:grid;grid-template-columns:repeat(7,minmax(120px,1fr));gap:8px;overflow:auto;padding-bottom:4px}.worker-schedule-day{min-width:120px;background:#fff8f5;border:1px solid var(--line);padding:10px}.worker-schedule-day h4{margin:0 0 8px}.worker-schedule-block{display:block;margin:5px 0;padding:7px 10px;border-radius:999px;background:var(--schedule-color,#ef8f73);color:#321b18;font-weight:800}.worker-schedule-block small{display:block;font-weight:500;opacity:.78}
@media(max-width:700px){.schedule-type-palette{align-items:flex-start}.schedule-type-buttons{width:100%}.schedule-type-choice{flex:1 1 140px}.worker-schedule-week{grid-template-columns:repeat(7,150px)}}

/* 2.0 · editor explícito de vigencia de segmentos */
.schedule-employee-selector{align-items:end}
.schedule-period-editor{border-left:5px solid darksalmon}
.schedule-period-editor .grid-3{align-items:end}
.schedule-period-preview{min-height:64px;padding:10px 14px;border:1px solid #dcc7c0;background:#fff8f5;display:flex;flex-direction:column;justify-content:center}
.schedule-period-preview small{display:block;text-transform:uppercase;letter-spacing:.08em;color:#8b5b50;margin-bottom:4px}
.schedule-period-preview strong{font-size:1.05rem}
.schedule-segment-pill{display:flex;flex-direction:column;gap:3px;min-width:210px}
.schedule-segment-pill strong{font-size:.95rem}
.schedule-segment-pill .badge{align-self:flex-start;margin-top:3px}

/* 2.0 · indicador de ubicación por IP */
.settings-section-divider{margin-top:8px;padding-top:18px;display:flex;align-items:flex-start;justify-content:space-between;gap:18px;border-top:1px solid var(--line)}
.settings-section-divider h3,.settings-section-divider p{margin:0}.settings-section-divider p{margin-top:5px;color:var(--muted)}
.company-location-sign{margin-top:14px;padding:12px 14px;display:grid;grid-template-columns:38px 1fr;align-items:center;gap:11px;color:#fff;background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.28);border-left-width:6px}
.company-location-sign>span{width:34px;height:34px;display:grid;place-items:center;border-radius:999px;background:rgba(255,255,255,.16);font-size:19px;font-weight:900}
.company-location-sign div{display:grid;gap:2px}.company-location-sign strong{font-size:15px}.company-location-sign small{color:rgba(255,255,255,.76)}
.company-location-sign.is-company{border-left-color:#5ec78c;background:rgba(40,130,82,.24)}
.company-location-sign.is-away{border-left-color:#f6bd60;background:rgba(181,108,0,.22)}
.company-location-sign.is-unknown{border-left-color:#d7c7c1}

/* 2.0 · email, calendario mensual y temporalización de vacaciones */
.notification-event-grid{padding:14px;background:#fff8f5;border:1px solid var(--line)}
.notification-event-grid .ju-table-caption{margin-bottom:10px}.notification-event-grid table{margin:0}
.notification-event-grid td small{color:var(--muted)}
.notification-event-check{display:inline-flex;align-items:center;gap:8px;font-weight:800}.notification-event-check input{width:20px;height:20px;min-height:0}

.attendance-view-switch{display:flex;align-items:center;gap:7px;flex-wrap:wrap}
.attendance-view-switch .button.active{color:#fff;background:var(--brand-800);border-color:var(--brand-800)}
.worker-month-calendar-card{min-width:0;overflow:auto}
.worker-month-calendar-toolbar{margin-bottom:14px;display:flex;align-items:center;justify-content:flex-end;gap:8px;min-width:760px}
.worker-month-calendar-toolbar>div{margin-right:auto}.worker-month-calendar-toolbar h3,.worker-month-calendar-toolbar p{margin:0}
.worker-month-calendar-legend{min-width:760px;margin:-4px 0 14px;display:flex;align-items:center;gap:14px;flex-wrap:wrap;color:var(--muted);font-size:11px;font-weight:850}
.worker-month-calendar-legend span{display:inline-flex;align-items:center;gap:6px}.worker-month-calendar-legend i{width:12px;height:12px;display:block;border-radius:999px;background:#f2a48e}
.worker-month-calendar-legend .is-vacation i{background:#67ad83}.worker-month-calendar-legend .is-holiday i{background:#6d91c5}.worker-month-calendar-legend .is-absence i{background:#9671b7}
.worker-month-calendar{min-width:760px;display:grid;grid-template-columns:repeat(7,minmax(105px,1fr));border-top:1px solid var(--line);border-left:1px solid var(--line)}
.worker-month-weekday{padding:9px;text-align:center;color:#fff;background:var(--brand-800);border-right:1px solid rgba(255,255,255,.18);font-size:12px;font-weight:900;text-transform:uppercase;letter-spacing:.06em}
.worker-month-day{min-height:128px;padding:7px;background:#fff;border-right:1px solid var(--line);border-bottom:1px solid var(--line)}
.worker-month-day.is-empty{background:#f4f0ee}.worker-month-day.is-today{box-shadow:inset 0 0 0 3px darksalmon}
.worker-month-day>time{width:28px;height:28px;display:grid;place-items:center;margin:0 0 6px auto;color:var(--muted);background:#f5ebe7;border-radius:999px;font-size:12px;font-weight:900}
.worker-month-day.is-today>time{color:#fff;background:var(--brand-800)}
.worker-month-events{display:grid;gap:5px}.worker-month-event{padding:7px 9px;display:block;overflow:hidden;color:#3d211d;background:#f2a48e;border-left:5px solid #9e4d42;border-radius:999px;text-decoration:none}
.worker-month-event strong,.worker-month-event small{display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.worker-month-event strong{font-size:11px}.worker-month-event small{font-size:9px;opacity:.78}
.worker-month-event.is-open{background:#f5cf77;border-left-color:#a87400}.worker-month-event.is-pending{background:#f7ddd5;border:2px dashed #b45f52}
.worker-month-event.is-vacation{background:#dfeee6;border-left-color:#2f7d57}.worker-month-event.is-holiday{background:#e3edf8;border-left-color:#426d8f}.worker-month-event.is-absence{background:#eee6f5;border-left-color:#72539b}.worker-month-event.is-sick-leave{background:#f9e1e1;border-left-color:#a33f4a}
.worker-month-event.is-vacation.is-pending{background:#eef5f1;border-color:#4e8c68}.worker-month-event.is-absence.is-pending{background:#f5f0f9;border-color:#80609f}
.worker-month-loading{grid-column:1/-1;margin:14px}

.vacation-timeline-filters{padding:14px;display:grid;grid-template-columns:minmax(110px,150px) minmax(170px,1fr) minmax(220px,1fr) minmax(210px,1fr) auto auto auto;align-items:end;gap:10px;background:#fff;border-left:6px solid darksalmon}
.vacation-only-switch{min-height:54px;margin:0;padding:8px 10px;border:1px solid var(--line)}.vacation-only-switch small{font-size:10px}
.vacation-zoom-control{min-height:54px;padding:7px 10px;display:grid;gap:5px;background:var(--brand-50);border:1px solid var(--line)}.vacation-zoom-control span{display:flex;justify-content:space-between;gap:10px}.vacation-zoom-control output{color:var(--brand-700);font-size:11px;font-weight:900}.vacation-zoom-control input{width:100%;min-height:18px;padding:0}
.vacation-timeline-metrics{display:grid;grid-template-columns:repeat(4,minmax(150px,1fr));gap:10px}
.vacation-timeline-metrics .metric{min-height:94px}.vacation-timeline-card{min-width:0}
.vacation-timeline-legend{display:flex;align-items:center;gap:12px;flex-wrap:wrap;color:var(--muted);font-size:11px;font-weight:800}
.vacation-timeline-legend span{display:inline-flex;align-items:center;gap:6px}.vacation-timeline-legend i{width:28px;height:12px;display:block;background:darksalmon;border-radius:999px}
.vacation-timeline-legend .pending i{background:repeating-linear-gradient(135deg,#f9ddd5 0 5px,#c97663 5px 8px);border:1px dashed #a95447}
.vacation-timeline-scroll{--vacation-day-width:32px;max-height:68vh;overflow:auto;border:1px solid var(--line);scroll-behavior:auto}
.vacation-timeline-table{width:max-content;min-width:100%;table-layout:fixed;border-collapse:separate;border-spacing:0}
.vacation-timeline-table .vacation-person-column{width:240px}.vacation-timeline-table .vacation-day-column{width:var(--vacation-day-width)}
.vacation-timeline-table th,.vacation-timeline-table td{border:0;border-right:1px solid #eaded9;border-bottom:1px solid #eaded9}
.vacation-person-head{width:240px;min-width:240px;position:sticky;left:0;top:0;z-index:8;text-align:left!important;background:var(--brand-900)!important}
.vacation-date-head{width:var(--vacation-day-width);min-width:var(--vacation-day-width);max-width:var(--vacation-day-width);height:82px;padding:4px 2px!important;position:sticky;top:0;z-index:6;text-align:center;background:var(--brand-800)}
.vacation-date-head span{display:inline-block;writing-mode:vertical-rl;transform:rotate(180deg);font-size:9px}.vacation-date-head small{height:25px;display:block;color:#ffd9ce;font-size:9px;line-height:1}
.vacation-date-head.is-weekend{background:#70423d}.vacation-date-head.is-month-start{box-shadow:inset 3px 0 darksalmon}
.vacation-date-head.is-today{background:#b75243;box-shadow:inset 0 -5px #fff,inset 3px 0 darksalmon}
.vacation-person-cell{width:240px;min-width:240px;max-width:240px;padding:10px;position:sticky;left:0;z-index:5;text-align:left!important;color:var(--ink);background:#fff!important;text-transform:none!important;letter-spacing:0!important}
.vacation-person-cell strong,.vacation-person-cell small{display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.vacation-person-cell small{margin-top:3px;color:var(--muted);font-size:9px;font-weight:500}
.vacation-lane-cell{padding:0!important;background:#fff}
.vacation-lane{width:calc(var(--vacation-days) * var(--vacation-day-width));min-height:40px;height:calc(var(--vacation-tracks) * 34px + 6px);display:grid;grid-template-columns:repeat(var(--vacation-days),var(--vacation-day-width));grid-template-rows:repeat(var(--vacation-tracks),34px);align-content:center;background:repeating-linear-gradient(to right,transparent 0 calc(var(--vacation-day-width) - 1px),#eee3de calc(var(--vacation-day-width) - 1px) var(--vacation-day-width))}
.vacation-timeline-table tbody tr:hover .vacation-lane{background-color:#fff9f6}.vacation-timeline-table tbody tr:hover .vacation-person-cell{background:#fff7f3!important}
.vacation-bar{min-width:0;margin:3px 2px;padding:4px 9px;z-index:2;display:flex;align-items:center;gap:7px;overflow:hidden;color:#3c201c;background:darksalmon;border:1px solid #b45e4f;border-radius:999px;text-decoration:none;box-shadow:0 1px 3px rgba(76,35,29,.16)}
.vacation-bar-copy{min-width:0;display:grid;line-height:1.05}.vacation-bar strong,.vacation-bar small{overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.vacation-bar strong{font-size:10px}.vacation-bar small{font-size:8px;opacity:.76}.vacation-day-count{order:-1;flex:0 0 auto;padding:3px 7px;color:#fff;background:#5f3933;border:1px solid rgba(255,255,255,.45);border-radius:999px;font-size:10px;line-height:1.2;white-space:nowrap;box-shadow:0 1px 2px rgba(0,0,0,.15)}
.vacation-bar.is-compact{padding:3px 2px;justify-content:center}.vacation-bar.is-compact .vacation-bar-copy{display:none}.vacation-bar.is-compact .vacation-day-count{margin:0;padding:1px 2px;font-size:8px}
.vacation-bar.is-pending{background:repeating-linear-gradient(135deg,#f9ddd5 0 8px,#efb8a8 8px 13px);border-style:dashed}

/* 2.1 · perspectiva operativa del día */
.today-toolbar .badge{align-self:center}.today-kpis{display:grid;grid-template-columns:repeat(6,minmax(145px,1fr));gap:10px}
.today-kpi{min-height:118px;padding:15px;display:grid;align-content:center;gap:3px;background:#fff;border:1px solid var(--line);border-top:6px solid #8b7770;clip-path:polygon(0 0,calc(100% - 10px) 0,100% 10px,100% 100%,0 100%)}
.today-kpi span,.today-kpi strong,.today-kpi small{display:block}.today-kpi span{color:var(--muted);font-size:10px;font-weight:900;text-transform:uppercase;letter-spacing:.05em}.today-kpi strong{font-size:30px;line-height:1}.today-kpi small{color:var(--muted);font-size:10px}
.today-kpi.is-expected{border-top-color:#426d8f}.today-kpi.is-completed{border-top-color:#26734d}.today-kpi.is-progress{border-top-color:#d78b20}.today-kpi.is-missing{border-top-color:#a63737}.today-kpi.is-upcoming{border-top-color:#7d6d9c}.today-kpi.is-excused{border-top-color:#65767f}
.today-primary-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px}.today-status-panel{min-width:0;border-top:6px solid #8b7770}.today-status-panel.is-missing{border-top-color:#a63737}.today-status-panel.is-progress{border-top-color:#d78b20}.today-status-panel.is-completed{border-top-color:#26734d}
.today-person-list{max-height:290px;display:grid;gap:7px;overflow:auto}.today-person-list>a{padding:10px 12px;display:grid;color:inherit;background:#fff8f5;border:1px solid var(--line);text-decoration:none}.today-person-list>a:hover{background:#fff;box-shadow:inset 5px 0 darksalmon}.today-person-list strong,.today-person-list small{overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.today-person-list small{color:var(--muted);font-size:10px}
.today-detail-card{min-width:0}.today-detail-table td{vertical-align:middle}.today-time-chip{width:max-content;max-width:220px;margin:2px 4px 2px 0;padding:5px 8px;display:inline-grid;color:inherit;background:#f5ebe7;border-left:4px solid darksalmon;text-decoration:none}.today-time-chip.is-scheduled{border-left-color:#426d8f}.today-time-chip.is-record{border-left-color:#26734d}.today-time-chip strong,.today-time-chip small{display:block;white-space:nowrap}.today-time-chip small{color:var(--muted);font-size:9px}
.today-state{padding:5px 9px;display:inline-flex;border-radius:999px;background:#eee;color:#4d4643;font-size:10px;font-weight:900;white-space:nowrap}.today-state.is-completed{color:#155b39;background:#dceddf}.today-state.is-in_progress{color:#704500;background:#f9e7bd}.today-state.is-not_started{color:#822929;background:#f6dada}.today-state.is-upcoming{color:#574477;background:#ebe5f4}.today-state.is-excused{color:#40545d;background:#e2eaed}.today-state.is-off{color:#625a57;background:#eeeae8}

@media(max-width:1250px){.today-kpis{grid-template-columns:repeat(3,minmax(145px,1fr))}}
@media(max-width:900px){.vacation-timeline-filters{grid-template-columns:1fr 1fr}.vacation-timeline-metrics{grid-template-columns:1fr 1fr}.attendance-view-switch{margin-top:8px}.today-primary-grid{grid-template-columns:1fr}.today-kpis{grid-template-columns:repeat(2,minmax(145px,1fr))}}
@media(max-width:620px){.vacation-timeline-filters,.vacation-timeline-metrics,.today-kpis{grid-template-columns:1fr}.worker-month-calendar-toolbar{align-items:stretch;flex-wrap:wrap}.worker-month-calendar-toolbar>div{width:100%;margin-right:0}.worker-month-calendar-toolbar .button{flex:1}}


/* 2.1.6 · refuerzo de experiencia móvil y PWA -------------------------- */
html {
  -webkit-text-size-adjust: 100%;
  text-size-adjust: 100%;
  touch-action: manipulation;
}

body {
  max-width: 100%;
  overflow-wrap: anywhere;
  touch-action: manipulation;
}

img, svg, video, canvas { max-width: 100%; }
form, fieldset, label, .card, .metric, .workspace, .front-main { min-width: 0; }
fieldset { max-width: 100%; }

button, .button, a, input, select, textarea, [role="button"] {
  touch-action: manipulation;
}

input, select, textarea {
  max-width: 100%;
}

input[type="file"] {
  width: 100%;
  max-width: 100%;
  min-width: 0;
  overflow: hidden;
  padding: 6px;
}

input[type="file"]::file-selector-button {
  max-width: 46%;
  margin-right: 8px;
  padding: 8px 10px;
  border: 1px solid var(--brand-500);
  border-radius: 0;
  color: var(--brand-800);
  background: var(--brand-100);
  font: inherit;
  font-weight: 800;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.inline-form > *, .actions > *, .admin-server-table-tools > * {
  min-width: 0;
}

@media (max-width: 720px) {
  body { overflow-x: hidden; }

  .topbar {
    padding: 10px 12px;
    gap: 10px;
  }
  .brand-mark { width: 40px; height: 40px; }
  .brand h1 { font-size: 16px; }
  .brand p { font-size: 11px; }

  .workspace {
    width: 100%;
    max-width: 100vw;
    padding: 12px;
    overflow-x: hidden;
  }

  .page-head h2 { font-size: clamp(22px, 8vw, 30px); }
  .page-head p { max-width: 100%; }

  .card, .metric { max-width: 100%; }
  .actions, .inline-form, .admin-server-table-tools {
    width: 100%;
  }
  .actions > .button,
  .actions > button,
  .inline-form > .button,
  .inline-form > button {
    flex: 1 1 auto;
  }

  label { width: 100%; max-width: 100%; }
  input, select, textarea, button { min-width: 0; }

  .table-wrap,
  .statistics-table-wrap,
  .worker-grid-table-wrap,
  .vacation-scroll,
  .worker-schedule-week {
    max-width: 100%;
    -webkit-overflow-scrolling: touch;
    overscroll-behavior-inline: contain;
  }

  .front-main {
    width: 100%;
    max-width: 100vw;
    padding-left: 12px;
    padding-right: 12px;
  }

  .front-main .card,
  .front-main .metric {
    width: 100%;
    padding: 14px;
  }

  .front-nav {
    left: 50%;
    right: auto;
    width: min(340px, calc(100vw - 20px));
    transform: translateX(-50%);
  }

  .front-nav-toggle {
    min-width: 0;
    min-height: 54px;
    padding: 8px 12px;
  }

  .front-nav-options {
    max-width: 100%;
  }

  .front-nav-options button {
    width: 100%;
    min-width: 0;
  }

  .ju-login-screen { padding: 14px 10px; }
  .ju-login-layout { width: 100%; gap: 10px; }
  .ju-login-side, .ju-login-card { max-width: 100%; }
}

@media (max-width: 620px) {
  #topbar { padding: 10px 12px; }
  #panel { width: 100%; max-width: 100vw; overflow-x: hidden; }
  #bandejas { max-width: 100vw; -webkit-overflow-scrolling: touch; }

  .admin-server-table-tools .admin-server-search,
  .admin-table-tools input,
  .worker-grid-search input,
  .user-password-reset input {
    width: 100%;
    min-width: 0;
    max-width: 100%;
  }

  .admin-table-tools,
  .user-password-reset {
    width: 100%;
    flex-wrap: wrap;
  }

  .user-password-reset > * { flex: 1 1 140px; }

  input[type="file"]::file-selector-button {
    max-width: 42%;
    padding-inline: 8px;
  }
}

/* 2.1.7 · iOS: no focus zoom + stronger accidental zoom prevention -------- */
@media (max-width: 900px) {
  /* iOS Safari zooms focused controls whose computed font-size is below 16px. */
  input,
  select,
  textarea,
  button {
    font-size: 16px;
  }

  input[type="checkbox"],
  input[type="radio"],
  input[type="range"],
  input[type="color"] {
    font-size: initial;
  }

  html,
  body,
  button,
  .button,
  a,
  [role="button"] {
    touch-action: manipulation;
  }
}


/* 2.2.3 · Bajas + restauración del layout jocarsa-iu ----------------------- */
body[data-area="admin"] {
  height: 100vh;
  overflow: hidden;
}
#app.iu-admin-shell {
  width: 100%;
  max-width: 100%;
  min-width: 0;
}
#bandejas,#mensajes,#contenido,#panel {
  min-width: 0;
  min-height: 0;
}
#bandejas,#mensajes {
  overflow-y: auto;
  overflow-x: hidden;
}
/* Unión visual sin salir de la propia columna: se ocupa sólo su padding derecho. */
#bandejas > a.activo {
  right: 0;
  width: calc(100% + 16px);
}
#bandejas > a.activo::after { display:none; }
#mensajes article.activo {
  right: 0;
  width: calc(100% + 12px);
  box-shadow: 0 0 0 4px rgba(233,150,122,.12), 0 12px 26px rgba(57,31,27,.10);
}
#mensajes article.activo::after { display:none; }
#mensajes article,#mensajes .ju-column-title,#mensajes .ju-center-link,#mensajes .linea1,#mensajes .linea2 {
  min-width:0;
  max-width:100%;
}
#contenido {
  min-width:0;
  overflow:hidden;
}
#panel {
  min-width:0;
  width:100%;
  max-width:100%;
  overflow-y:auto;
  overflow-x:hidden;
}
#panel .table-wrap,#panel .calendar-week-wrap,#panel .sessions-table-wrap,#panel .schedule-grid-wrap,#panel .worker-grid-table-wrap,#panel .vacation-timeline-scroll,#panel .worker-month-calendar-card { max-width:100%; }
#panel .grid-2 > *,#panel .grid-3 > * { min-width:0; }
@media (max-width:1250px) {
  #app.iu-admin-shell { grid-template-columns:220px 330px minmax(0,1fr); }
}
@media (max-width:900px) {
  body[data-area="admin"] { height:auto; overflow:auto; }
  #app.iu-admin-shell { height:auto; display:block; }
  #bandejas { display:flex; gap:8px; overflow-x:auto; overflow-y:hidden; }
  #bandejas > a { min-width:165px; margin:0; }
  #bandejas > a.activo,#mensajes article.activo { right:0; width:auto; }
  #mensajes { max-height:360px; }
  #panel { overflow:visible; }
}


/* 2.2.4 · Unión visual columna 2 -> panel derecho --------------------------
   En 2.2.3 el ancho activo era correcto, pero `max-width:100%` lo recortaba.
   Quitamos ese límite SOLO para la tarjeta seleccionada. */
#mensajes article.activo {
  width: calc(100% + 12px);
  max-width: none;
  right: 0;
  z-index: 4;
  clip-path: none;
  background: #fff;
}

/* La tarjeta llega exactamente hasta el comienzo del panel derecho.
   El overflow horizontal del rail sigue oculto para evitar scroll lateral. */
#mensajes article.activo .ju-center-link {
  max-width: none;
}

@media (max-width: 900px) {
  #mensajes article.activo {
    width: auto;
    max-width: 100%;
  }
}

/* 2026-09-20 schedule labels, meeting planner and period switches */
.typed-schedule-grid .schedule-slot span[data-block-label]{display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:800;line-height:1;color:#fff;white-space:nowrap;overflow:visible;text-shadow:0 1px 2px rgba(0,0,0,.35);z-index:2}.typed-schedule-grid .schedule-slot span[data-block-label]:not(.block-label-visible){font-size:0}.typed-schedule-grid .schedule-slot span[data-block-label]:not(.block-label-visible)::after{content:''}.meeting-grid th small{display:block;font-weight:400;opacity:.7}.meeting-grid .meeting-slot.selected span{background:#9b4336}.range-switch{display:flex;gap:6px;align-items:center;flex-wrap:wrap}.range-switch label{display:flex!important;flex-direction:row!important;align-items:center;gap:4px;padding:7px 9px;background:rgba(255,255,255,.55);border-radius:6px}.statistics-type-totals{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:8px}.statistics-type-totals>div{display:flex;justify-content:space-between;gap:12px;padding:10px 12px;background:rgba(255,255,255,.55);border-radius:6px}.statistics-type-totals span{font-weight:800}

/* meetings/statistics UX refinement */
.statistics-filter .range-switch{display:grid;grid-template-columns:repeat(3,1fr);gap:7px;margin:12px 0}.statistics-filter .range-switch label{position:relative;justify-content:center;min-height:38px;padding:8px 10px!important;border:1px solid rgba(123,73,62,.22);background:#fff!important;border-radius:0!important;cursor:pointer;font-weight:700}.statistics-filter .range-switch input{appearance:none;-webkit-appearance:none;width:14px!important;height:14px!important;min-width:14px;border:2px solid #9a6255;border-radius:50%;margin:0}.statistics-filter .range-switch input:checked{border:4px solid #fff;outline:2px solid #d97960;background:#d97960}.statistics-filter .range-switch label:has(input:checked){background:#f2c2b6!important;border-color:#d97960;color:#63372e}.statistics-filter label[hidden]{display:none!important}.meeting-scope-tabs{display:grid;grid-template-columns:repeat(3,1fr);gap:8px}.meeting-scope-tabs a{padding:12px 14px;text-align:center;text-decoration:none;background:rgba(255,255,255,.65);color:#6c4037;font-weight:800;border:1px solid rgba(108,64,55,.18)}.meeting-scope-tabs a.active{background:#9b6255;color:#fff}.autosave-status{padding:7px 10px;background:rgba(255,255,255,.65);font-weight:800}.autosave-status.saving{opacity:.65}.autosave-status.error{background:#7b2d26;color:#fff}.schedule-savebar span{font-weight:700;opacity:.8}@media(max-width:800px){.meeting-scope-tabs,.statistics-filter .range-switch{grid-template-columns:1fr}}


/* Statistics period selector v4: stacked controls */
.statistics-filter .range-switch {
  display: flex !important;
  flex-direction: column !important;
  align-items: stretch !important;
  gap: 7px !important;
  margin: 14px 0 !important;
  width: 100% !important;
}
.statistics-filter .range-switch label {
  display: flex !important;
  flex-direction: row !important;
  align-items: center !important;
  justify-content: flex-start !important;
  gap: 10px !important;
  width: 100% !important;
  min-height: 42px !important;
  padding: 9px 12px !important;
  margin: 0 !important;
  box-sizing: border-box !important;
  cursor: pointer;
  text-align: left !important;
}
.statistics-filter .range-switch input[type="radio"] {
  appearance: none !important;
  -webkit-appearance: none !important;
  display: inline-block !important;
  flex: 0 0 18px !important;
  width: 18px !important;
  height: 18px !important;
  min-width: 18px !important;
  max-width: 18px !important;
  min-height: 18px !important;
  max-height: 18px !important;
  padding: 0 !important;
  margin: 0 !important;
  border: 2px solid #9a6255 !important;
  border-radius: 50% !important;
  box-sizing: border-box !important;
}
.statistics-filter .range-switch input[type="radio"]:checked {
  border: 4px solid #fff !important;
  outline: 2px solid #d97960 !important;
  background: #d97960 !important;
}
.statistics-filter .range-manual[hidden],
.statistics-filter .range-week[hidden],
.statistics-filter .range-month[hidden] {
  display: none !important;
}
```

### database/.htaccess

```text
Require all denied
```

### database/install.php

```php
<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Este instalador solo se ejecuta desde terminal.');
}

require_once dirname(__DIR__) . '/bootstrap.php';

use JA\Database;
use JA\Env;
use JA\AttendanceAudit;
use JA\AttendanceTypeCatalog;
use JA\ContractCatalog;
use JA\Settings;

$options = getopt('', ['password:', 'user:', 'name:', 'email:']);
$password = (string)($options['password'] ?? Env::get('INSTALL_SUPERADMIN_PASSWORD', ''));
if ($password === '' || $password === 'change_this_password' || strlen($password) < 10) {
    throw new RuntimeException(
        "Define INSTALL_SUPERADMIN_PASSWORD con al menos 10 caracteres en .env, " .
        "o ejecuta: php database/install.php --password=\"tu-clave-segura\""
    );
}

$superadminUser = trim((string)($options['user'] ?? Env::get('INSTALL_SUPERADMIN_USER', 'jocarsa')));
$superadminName = trim((string)($options['name'] ?? Env::get('INSTALL_SUPERADMIN_NAME', 'JOCARSA')));
$superadminEmail = trim((string)($options['email'] ?? Env::get('INSTALL_SUPERADMIN_EMAIL', 'info@example.com')));
if ($superadminUser === '' || $superadminName === '' || !filter_var($superadminEmail, FILTER_VALIDATE_EMAIL)) {
    throw new RuntimeException('Revisa el usuario, el nombre y el email iniciales del superadministrador.');
}

$pdo = Database::installPdo();
$schema = file_get_contents(__DIR__ . '/schema.sql');
if ($schema === false) {
    throw new RuntimeException('No se pudo leer database/schema.sql.');
}
$pdo->exec($schema);

$pdo->beginTransaction();
try {
    $tenantSlug = strtolower(trim((string)Env::get('LOCALHOST_TENANT_SLUG', 'local')));
    if (!preg_match('/^[a-z0-9][a-z0-9-]{0,98}[a-z0-9]$|^[a-z0-9]$/', $tenantSlug)) {
        throw new RuntimeException('LOCALHOST_TENANT_SLUG no es válido.');
    }
    $tenant = $pdo->prepare(
        "INSERT INTO tenants (name, slug, status, timezone)
         VALUES (:name, :slug, 'active', :timezone)
         ON CONFLICT(slug) DO UPDATE SET
           name = excluded.name,
           status = 'active',
           timezone = excluded.timezone,
           updated_at = CURRENT_TIMESTAMP"
    );
    $tenant->execute([
        'name' => 'Organización local',
        'slug' => $tenantSlug,
        'timezone' => (string)Env::get('APP_TIMEZONE', 'Europe/Madrid'),
    ]);

    $tenantIdStatement = $pdo->prepare('SELECT id FROM tenants WHERE slug = :slug LIMIT 1');
    $tenantIdStatement->execute(['slug' => $tenantSlug]);
    $tenantId = (int)$tenantIdStatement->fetchColumn();
    if ($tenantId < 1) {
        throw new RuntimeException('No se pudo obtener el tenant local.');
    }
    ContractCatalog::seedTenant($pdo, $tenantId);
    AttendanceTypeCatalog::seedDefaultTenant($pdo, $tenantId);
    Settings::seedTenant($pdo, $tenantId);

    $domain = $pdo->prepare(
        'INSERT INTO tenant_domains (tenant_id, domain, is_primary, is_localhost)
         VALUES (:tenant, :domain, :primary, 1)
         ON CONFLICT(domain) DO UPDATE SET
           tenant_id = excluded.tenant_id,
           is_primary = excluded.is_primary,
           is_localhost = 1'
    );
    foreach (['localhost', '127.0.0.1', '::1'] as $index => $host) {
        $domain->execute([
            'tenant' => $tenantId,
            'domain' => $host,
            'primary' => $index === 0 ? 1 : 0,
        ]);
    }

    $superadmin = $pdo->prepare(
        'INSERT INTO superadmins (full_name, email, username, password_hash, active)
         VALUES (:name, :email, :username, :password, 1)
         ON CONFLICT(username) DO UPDATE SET
           full_name = excluded.full_name,
           email = excluded.email,
           password_hash = excluded.password_hash,
           active = 1,
           updated_at = CURRENT_TIMESTAMP'
    );
    $superadmin->execute([
        'name' => $superadminName,
        'email' => $superadminEmail,
        'username' => $superadminUser,
        'password' => password_hash($password, PASSWORD_DEFAULT),
    ]);

    $meta = $pdo->prepare(
        'INSERT INTO app_meta (meta_key, meta_value, updated_at)
         VALUES (:key, :value, CURRENT_TIMESTAMP)
         ON CONFLICT(meta_key) DO UPDATE SET meta_value = excluded.meta_value, updated_at = CURRENT_TIMESTAMP'
    );
    $meta->execute(['key' => 'schema_version', 'value' => '2.2']);
    $pdo->exec('PRAGMA user_version = 12');
    $pdo->commit();
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    throw $exception;
}

AttendanceAudit::bootstrapTenant($pdo, $tenantId);

@chmod(Database::path(), 0660);

echo "Instalación SQLite completada.\n";
echo 'Archivo: ' . Database::path() . "\n";
echo 'Tenant local: ' . $tenantSlug . "\n";
echo 'Superadmin: ' . $superadminUser . "\n";

```

### database/schema.sql

```sql
PRAGMA foreign_keys = ON;

CREATE TABLE IF NOT EXISTS app_meta (
  meta_key TEXT PRIMARY KEY,
  meta_value TEXT NOT NULL,
  updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS tenants (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT NOT NULL,
  slug TEXT NOT NULL COLLATE NOCASE,
  status TEXT NOT NULL DEFAULT 'active' CHECK (status IN ('active', 'suspended')),
  timezone TEXT NOT NULL DEFAULT 'Europe/Madrid',
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE (slug)
);

CREATE TABLE IF NOT EXISTS tenant_domains (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  tenant_id INTEGER NOT NULL,
  domain TEXT NOT NULL COLLATE NOCASE,
  is_primary INTEGER NOT NULL DEFAULT 0 CHECK (is_primary IN (0, 1)),
  is_localhost INTEGER NOT NULL DEFAULT 0 CHECK (is_localhost IN (0, 1)),
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE (domain),
  FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS ix_tenant_domains_tenant
  ON tenant_domains (tenant_id);

CREATE UNIQUE INDEX IF NOT EXISTS uq_tenant_primary_domain
  ON tenant_domains (tenant_id)
  WHERE is_primary = 1;

CREATE TABLE IF NOT EXISTS superadmins (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  full_name TEXT NOT NULL,
  email TEXT NOT NULL COLLATE NOCASE,
  username TEXT NOT NULL COLLATE NOCASE,
  password_hash TEXT NOT NULL,
  active INTEGER NOT NULL DEFAULT 1 CHECK (active IN (0, 1)),
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE (username),
  UNIQUE (email)
);

CREATE TABLE IF NOT EXISTS tenant_admins (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  tenant_id INTEGER NOT NULL,
  legacy_id INTEGER,
  full_name TEXT NOT NULL,
  email TEXT COLLATE NOCASE,
  username TEXT NOT NULL COLLATE NOCASE,
  password_hash TEXT NOT NULL,
  active INTEGER NOT NULL DEFAULT 1 CHECK (active IN (0, 1)),
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE (id, tenant_id),
  UNIQUE (tenant_id, username),
  UNIQUE (tenant_id, email),
  UNIQUE (tenant_id, legacy_id),
  FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS ix_tenant_admins_tenant
  ON tenant_admins (tenant_id, active);

CREATE TABLE IF NOT EXISTS tenant_settings (
  tenant_id INTEGER NOT NULL,
  setting_key TEXT NOT NULL,
  setting_value TEXT NOT NULL,
  updated_by_admin_id INTEGER,
  updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (tenant_id, setting_key),
  CHECK (length(trim(setting_key)) BETWEEN 1 AND 100),
  FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
  FOREIGN KEY (updated_by_admin_id, tenant_id) REFERENCES tenant_admins(id, tenant_id) ON DELETE RESTRICT
);

CREATE TABLE IF NOT EXISTS contract_types (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  tenant_id INTEGER NOT NULL,
  legacy_id INTEGER,
  code TEXT COLLATE NOCASE,
  name TEXT NOT NULL,
  category TEXT NOT NULL DEFAULT 'special' CHECK (category IN ('indefinite', 'temporary', 'training', 'special')),
  description TEXT,
  is_fixed_discontinuous INTEGER NOT NULL DEFAULT 0 CHECK (is_fixed_discontinuous IN (0, 1)),
  is_system INTEGER NOT NULL DEFAULT 0 CHECK (is_system IN (0, 1)),
  active INTEGER NOT NULL DEFAULT 1 CHECK (active IN (0, 1)),
  UNIQUE (id, tenant_id),
  UNIQUE (tenant_id, legacy_id),
  FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS ix_contract_types_tenant
  ON contract_types (tenant_id, name);

CREATE UNIQUE INDEX IF NOT EXISTS uq_contract_types_code
  ON contract_types (tenant_id, code);

CREATE TABLE IF NOT EXISTS work_centers (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  tenant_id INTEGER NOT NULL,
  legacy_id INTEGER,
  name TEXT NOT NULL,
  address TEXT,
  latitude NUMERIC,
  longitude NUMERIC,
  UNIQUE (id, tenant_id),
  UNIQUE (tenant_id, legacy_id),
  FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS ix_work_centers_tenant
  ON work_centers (tenant_id, name);

CREATE TABLE IF NOT EXISTS employees (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  tenant_id INTEGER NOT NULL,
  legacy_id INTEGER,
  photo TEXT,
  nombre TEXT NOT NULL,
  apellido TEXT,
  departamento TEXT,
  contract_type_id INTEGER,
  work_center_id INTEGER,
  action_radius NUMERIC,
  role TEXT,
  active INTEGER NOT NULL DEFAULT 1 CHECK (active IN (0, 1)),
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE (id, tenant_id),
  UNIQUE (tenant_id, legacy_id),
  FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
  FOREIGN KEY (contract_type_id, tenant_id) REFERENCES contract_types(id, tenant_id) ON DELETE RESTRICT,
  FOREIGN KEY (work_center_id, tenant_id) REFERENCES work_centers(id, tenant_id) ON DELETE RESTRICT
);

CREATE INDEX IF NOT EXISTS ix_employees_tenant_name
  ON employees (tenant_id, active, apellido, nombre);

CREATE INDEX IF NOT EXISTS ix_employees_contract
  ON employees (contract_type_id, tenant_id);

CREATE INDEX IF NOT EXISTS ix_employees_center
  ON employees (work_center_id, tenant_id);

CREATE TABLE IF NOT EXISTS attendance_types (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  tenant_id INTEGER NOT NULL,
  role TEXT NOT NULL COLLATE NOCASE,
  name TEXT NOT NULL COLLATE NOCASE,
  is_default INTEGER NOT NULL DEFAULT 0 CHECK (is_default IN (0, 1)),
  active INTEGER NOT NULL DEFAULT 1 CHECK (active IN (0, 1)),
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE (id, tenant_id),
  UNIQUE (tenant_id, role, name),
  CHECK (length(trim(role)) BETWEEN 1 AND 100),
  CHECK (length(trim(name)) BETWEEN 1 AND 100),
  CHECK (is_default = 0 OR active = 1),
  FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS ix_attendance_types_role
  ON attendance_types (tenant_id, role, active, name);

CREATE UNIQUE INDEX IF NOT EXISTS uq_attendance_types_default_role
  ON attendance_types (tenant_id, role COLLATE NOCASE)
  WHERE is_default = 1;

CREATE TABLE IF NOT EXISTS users (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  tenant_id INTEGER NOT NULL,
  legacy_id INTEGER,
  employee_id INTEGER,
  name TEXT NOT NULL,
  email TEXT COLLATE NOCASE,
  username TEXT NOT NULL COLLATE NOCASE,
  password_hash TEXT NOT NULL,
  role TEXT NOT NULL DEFAULT 'worker',
  active INTEGER NOT NULL DEFAULT 1 CHECK (active IN (0, 1)),
  conciliacion INTEGER NOT NULL DEFAULT 0 CHECK (conciliacion IN (0, 1)),
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE (id, tenant_id),
  UNIQUE (tenant_id, username),
  UNIQUE (tenant_id, email),
  UNIQUE (tenant_id, legacy_id),
  FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
  FOREIGN KEY (employee_id, tenant_id) REFERENCES employees(id, tenant_id) ON DELETE RESTRICT
);

CREATE INDEX IF NOT EXISTS ix_users_employee
  ON users (employee_id, tenant_id);

CREATE TABLE IF NOT EXISTS attendance (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  tenant_id INTEGER NOT NULL,
  legacy_id INTEGER,
  employee_id INTEGER NOT NULL,
  attendance_type_id INTEGER,
  work_date TEXT NOT NULL,
  start_time TEXT,
  end_time TEXT,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE (id, tenant_id),
  UNIQUE (tenant_id, legacy_id),
  FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
  FOREIGN KEY (employee_id, tenant_id) REFERENCES employees(id, tenant_id) ON DELETE CASCADE,
  FOREIGN KEY (attendance_type_id, tenant_id) REFERENCES attendance_types(id, tenant_id) ON DELETE RESTRICT
);

CREATE INDEX IF NOT EXISTS ix_attendance_employee_date
  ON attendance (tenant_id, employee_id, work_date DESC, start_time DESC);

CREATE INDEX IF NOT EXISTS ix_attendance_employee_fk
  ON attendance (employee_id, tenant_id);

CREATE INDEX IF NOT EXISTS ix_attendance_type_fk
  ON attendance (attendance_type_id, tenant_id);

CREATE INDEX IF NOT EXISTS ix_attendance_open_shift
  ON attendance (tenant_id, employee_id, id DESC)
  WHERE end_time IS NULL;

CREATE TABLE IF NOT EXISTS attendance_corrections (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  tenant_id INTEGER NOT NULL,
  attendance_id INTEGER NOT NULL,
  employee_id INTEGER NOT NULL,
  requested_by_admin_id INTEGER NOT NULL,
  status TEXT NOT NULL DEFAULT 'pending' CHECK (status IN ('pending', 'accepted', 'rejected')),
  original_work_date TEXT NOT NULL,
  original_start_time TEXT,
  original_end_time TEXT,
  original_attendance_type_id INTEGER,
  proposed_work_date TEXT NOT NULL,
  proposed_start_time TEXT,
  proposed_end_time TEXT,
  proposed_attendance_type_id INTEGER,
  reason TEXT NOT NULL,
  requested_at TEXT NOT NULL,
  responded_at TEXT,
  responded_by_user_id INTEGER,
  worker_comment TEXT,
  proposal_event_hash TEXT,
  decision_event_hash TEXT,
  UNIQUE (id, tenant_id),
  CHECK (
    (status = 'pending' AND responded_at IS NULL AND responded_by_user_id IS NULL)
    OR
    (status IN ('accepted', 'rejected') AND responded_at IS NOT NULL AND responded_by_user_id IS NOT NULL)
  ),
  FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
  FOREIGN KEY (attendance_id, tenant_id) REFERENCES attendance(id, tenant_id) ON DELETE RESTRICT,
  FOREIGN KEY (employee_id, tenant_id) REFERENCES employees(id, tenant_id) ON DELETE RESTRICT,
  FOREIGN KEY (requested_by_admin_id, tenant_id) REFERENCES tenant_admins(id, tenant_id) ON DELETE RESTRICT,
  FOREIGN KEY (responded_by_user_id, tenant_id) REFERENCES users(id, tenant_id) ON DELETE RESTRICT,
  FOREIGN KEY (original_attendance_type_id, tenant_id) REFERENCES attendance_types(id, tenant_id) ON DELETE RESTRICT,
  FOREIGN KEY (proposed_attendance_type_id, tenant_id) REFERENCES attendance_types(id, tenant_id) ON DELETE RESTRICT
);

CREATE INDEX IF NOT EXISTS ix_attendance_corrections_employee
  ON attendance_corrections (tenant_id, employee_id, requested_at DESC);

CREATE INDEX IF NOT EXISTS ix_attendance_corrections_attendance
  ON attendance_corrections (tenant_id, attendance_id, requested_at DESC);

CREATE UNIQUE INDEX IF NOT EXISTS uq_attendance_correction_pending
  ON attendance_corrections (tenant_id, attendance_id)
  WHERE status = 'pending';

CREATE TABLE IF NOT EXISTS worker_attendance_corrections (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  tenant_id INTEGER NOT NULL,
  attendance_id INTEGER NOT NULL,
  employee_id INTEGER NOT NULL,
  requested_by_user_id INTEGER NOT NULL,
  status TEXT NOT NULL DEFAULT 'pending' CHECK (status IN ('pending', 'accepted', 'rejected')),
  original_work_date TEXT NOT NULL,
  original_start_time TEXT,
  original_end_time TEXT,
  original_attendance_type_id INTEGER,
  proposed_work_date TEXT NOT NULL,
  proposed_start_time TEXT,
  proposed_end_time TEXT,
  proposed_attendance_type_id INTEGER,
  reason TEXT NOT NULL,
  requested_at TEXT NOT NULL,
  responded_at TEXT,
  responded_by_admin_id INTEGER,
  admin_comment TEXT,
  proposal_event_hash TEXT,
  decision_event_hash TEXT,
  UNIQUE (id, tenant_id),
  CHECK (
    (status = 'pending' AND responded_at IS NULL AND responded_by_admin_id IS NULL)
    OR
    (status IN ('accepted', 'rejected') AND responded_at IS NOT NULL AND responded_by_admin_id IS NOT NULL)
  ),
  FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
  FOREIGN KEY (attendance_id, tenant_id) REFERENCES attendance(id, tenant_id) ON DELETE RESTRICT,
  FOREIGN KEY (employee_id, tenant_id) REFERENCES employees(id, tenant_id) ON DELETE RESTRICT,
  FOREIGN KEY (requested_by_user_id, tenant_id) REFERENCES users(id, tenant_id) ON DELETE RESTRICT,
  FOREIGN KEY (responded_by_admin_id, tenant_id) REFERENCES tenant_admins(id, tenant_id) ON DELETE RESTRICT,
  FOREIGN KEY (original_attendance_type_id, tenant_id) REFERENCES attendance_types(id, tenant_id) ON DELETE RESTRICT,
  FOREIGN KEY (proposed_attendance_type_id, tenant_id) REFERENCES attendance_types(id, tenant_id) ON DELETE RESTRICT
);

CREATE INDEX IF NOT EXISTS ix_worker_attendance_corrections_employee
  ON worker_attendance_corrections (tenant_id, employee_id, requested_at DESC);

CREATE INDEX IF NOT EXISTS ix_worker_attendance_corrections_status
  ON worker_attendance_corrections (tenant_id, status, requested_at DESC);

CREATE UNIQUE INDEX IF NOT EXISTS uq_worker_attendance_correction_pending
  ON worker_attendance_corrections (tenant_id, attendance_id)
  WHERE status = 'pending';

CREATE TRIGGER IF NOT EXISTS tr_admin_correction_no_worker_pending
BEFORE INSERT ON attendance_corrections
WHEN NEW.status='pending' AND EXISTS (
  SELECT 1 FROM worker_attendance_corrections c
  WHERE c.tenant_id=NEW.tenant_id AND c.attendance_id=NEW.attendance_id AND c.status='pending'
)
BEGIN
  SELECT RAISE(ABORT, 'La jornada ya tiene una solicitud del trabajador pendiente');
END;

CREATE TRIGGER IF NOT EXISTS tr_worker_correction_no_admin_pending
BEFORE INSERT ON worker_attendance_corrections
WHEN NEW.status='pending' AND EXISTS (
  SELECT 1 FROM attendance_corrections c
  WHERE c.tenant_id=NEW.tenant_id AND c.attendance_id=NEW.attendance_id AND c.status='pending'
)
BEGIN
  SELECT RAISE(ABORT, 'La jornada ya tiene una corrección administrativa pendiente');
END;

CREATE TRIGGER IF NOT EXISTS tr_worker_attendance_correction_type_tenant_insert
BEFORE INSERT ON worker_attendance_corrections
WHEN (
  NEW.original_attendance_type_id IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM attendance_types ft
    WHERE ft.id=NEW.original_attendance_type_id AND ft.tenant_id=NEW.tenant_id
  )
)
OR (
  NEW.proposed_attendance_type_id IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM attendance_types ft
    WHERE ft.id=NEW.proposed_attendance_type_id AND ft.tenant_id=NEW.tenant_id
  )
)
BEGIN
  SELECT RAISE(ABORT, 'El tipo de la solicitud no pertenece a esta organización');
END;

CREATE TRIGGER IF NOT EXISTS tr_worker_attendance_correction_type_tenant_update
BEFORE UPDATE OF original_attendance_type_id, proposed_attendance_type_id, tenant_id ON worker_attendance_corrections
WHEN (
  NEW.original_attendance_type_id IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM attendance_types ft
    WHERE ft.id=NEW.original_attendance_type_id AND ft.tenant_id=NEW.tenant_id
  )
)
OR (
  NEW.proposed_attendance_type_id IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM attendance_types ft
    WHERE ft.id=NEW.proposed_attendance_type_id AND ft.tenant_id=NEW.tenant_id
  )
)
BEGIN
  SELECT RAISE(ABORT, 'El tipo de la solicitud no pertenece a esta organización');
END;

CREATE TRIGGER IF NOT EXISTS tr_attendance_correction_type_tenant_insert
BEFORE INSERT ON attendance_corrections
WHEN (
  NEW.original_attendance_type_id IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM attendance_types ft
    WHERE ft.id=NEW.original_attendance_type_id AND ft.tenant_id=NEW.tenant_id
  )
)
OR (
  NEW.proposed_attendance_type_id IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM attendance_types ft
    WHERE ft.id=NEW.proposed_attendance_type_id AND ft.tenant_id=NEW.tenant_id
  )
)
BEGIN
  SELECT RAISE(ABORT, 'El tipo de la corrección no pertenece a esta organización');
END;

CREATE TRIGGER IF NOT EXISTS tr_attendance_correction_type_tenant_update
BEFORE UPDATE OF original_attendance_type_id, proposed_attendance_type_id, tenant_id ON attendance_corrections
WHEN (
  NEW.original_attendance_type_id IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM attendance_types ft
    WHERE ft.id=NEW.original_attendance_type_id AND ft.tenant_id=NEW.tenant_id
  )
)
OR (
  NEW.proposed_attendance_type_id IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM attendance_types ft
    WHERE ft.id=NEW.proposed_attendance_type_id AND ft.tenant_id=NEW.tenant_id
  )
)
BEGIN
  SELECT RAISE(ABORT, 'El tipo de la corrección no pertenece a esta organización');
END;

CREATE TRIGGER IF NOT EXISTS tr_attendance_type_role_insert
BEFORE INSERT ON attendance
WHEN NEW.attendance_type_id IS NOT NULL
  AND NOT EXISTS (
    SELECT 1
    FROM attendance_types ft
    JOIN employees e ON e.id=NEW.employee_id AND e.tenant_id=NEW.tenant_id
    WHERE ft.id=NEW.attendance_type_id
      AND ft.tenant_id=NEW.tenant_id
      AND ft.active=1
      AND lower(trim(ft.role, char(9) || char(10) || char(13) || ' '))
          =lower(trim(e.role, char(9) || char(10) || char(13) || ' '))
  )
BEGIN
  SELECT RAISE(ABORT, 'El tipo de fichaje no corresponde al rol de la persona');
END;

CREATE TRIGGER IF NOT EXISTS tr_attendance_type_role_update
BEFORE UPDATE OF attendance_type_id, employee_id, tenant_id ON attendance
WHEN NEW.attendance_type_id IS NOT NULL
  AND NOT EXISTS (
    SELECT 1
    FROM attendance_types ft
    JOIN employees e ON e.id=NEW.employee_id AND e.tenant_id=NEW.tenant_id
    WHERE ft.id=NEW.attendance_type_id
      AND ft.tenant_id=NEW.tenant_id
      AND lower(trim(ft.role, char(9) || char(10) || char(13) || ' '))
          =lower(trim(e.role, char(9) || char(10) || char(13) || ' '))
  )
BEGIN
  SELECT RAISE(ABORT, 'El tipo de fichaje no corresponde al rol de la persona');
END;

CREATE TRIGGER IF NOT EXISTS tr_attendance_type_identity_immutable
BEFORE UPDATE OF role, name ON attendance_types
WHEN (
  NEW.role <> OLD.role
  OR NEW.name <> OLD.name
)
AND (
  EXISTS (
    SELECT 1 FROM attendance a
    WHERE a.tenant_id=OLD.tenant_id AND a.attendance_type_id=OLD.id
  )
  OR EXISTS (
    SELECT 1 FROM attendance_corrections c
    WHERE c.tenant_id=OLD.tenant_id
      AND (c.original_attendance_type_id=OLD.id OR c.proposed_attendance_type_id=OLD.id)
  )
  OR EXISTS (
    SELECT 1 FROM worker_attendance_corrections c
    WHERE c.tenant_id=OLD.tenant_id
      AND (c.original_attendance_type_id=OLD.id OR c.proposed_attendance_type_id=OLD.id)
  )
)
BEGIN
  SELECT RAISE(ABORT, 'El nombre y el rol de un tipo ya utilizado son inmutables');
END;

CREATE TRIGGER IF NOT EXISTS tr_attendance_type_no_delete_when_used
BEFORE DELETE ON attendance_types
WHEN EXISTS (
  SELECT 1 FROM attendance a
  WHERE a.tenant_id=OLD.tenant_id AND a.attendance_type_id=OLD.id
)
OR EXISTS (
  SELECT 1 FROM attendance_corrections c
  WHERE c.tenant_id=OLD.tenant_id
    AND (c.original_attendance_type_id=OLD.id OR c.proposed_attendance_type_id=OLD.id)
)
OR EXISTS (
  SELECT 1 FROM worker_attendance_corrections c
  WHERE c.tenant_id=OLD.tenant_id
    AND (c.original_attendance_type_id=OLD.id OR c.proposed_attendance_type_id=OLD.id)
)
BEGIN
  SELECT RAISE(ABORT, 'El tipo de fichaje está asociado a registros de jornada');
END;

-- Nombres 2.x independientes: se crean también al actualizar una base 2.0
-- que ya tenía los triggers equivalentes con IF NOT EXISTS.
CREATE TRIGGER IF NOT EXISTS tr_attendance_type_identity_immutable_worker_request
BEFORE UPDATE OF role, name ON attendance_types
WHEN (NEW.role <> OLD.role OR NEW.name <> OLD.name)
AND EXISTS (
  SELECT 1 FROM worker_attendance_corrections c
  WHERE c.tenant_id=OLD.tenant_id
    AND (c.original_attendance_type_id=OLD.id OR c.proposed_attendance_type_id=OLD.id)
)
BEGIN
  SELECT RAISE(ABORT, 'El nombre y el rol de un tipo ya utilizado son inmutables');
END;

CREATE TRIGGER IF NOT EXISTS tr_attendance_type_no_delete_when_worker_request
BEFORE DELETE ON attendance_types
WHEN EXISTS (
  SELECT 1 FROM worker_attendance_corrections c
  WHERE c.tenant_id=OLD.tenant_id
    AND (c.original_attendance_type_id=OLD.id OR c.proposed_attendance_type_id=OLD.id)
)
BEGIN
  SELECT RAISE(ABORT, 'El tipo de fichaje está asociado a solicitudes de jornada');
END;

CREATE TABLE IF NOT EXISTS attendance_audit_heads (
  tenant_id INTEGER PRIMARY KEY,
  last_sequence INTEGER NOT NULL DEFAULT 0,
  last_hash TEXT NOT NULL DEFAULT '',
  head_mac TEXT NOT NULL DEFAULT '',
  updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS attendance_audit_log (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  tenant_id INTEGER NOT NULL,
  sequence INTEGER NOT NULL,
  attendance_id INTEGER NOT NULL,
  correction_id INTEGER,
  event_type TEXT NOT NULL CHECK (event_type IN (
    'baseline',
    'clock_in',
    'clock_out',
    'admin_created',
    'imported',
    'correction_proposed',
    'correction_accepted',
    'correction_rejected'
  )),
  actor_type TEXT NOT NULL CHECK (actor_type IN ('system', 'admin', 'worker')),
  actor_id INTEGER,
  actor_label TEXT NOT NULL,
  event_at TEXT NOT NULL,
  previous_hash TEXT NOT NULL,
  payload_json TEXT NOT NULL,
  event_hash TEXT NOT NULL,
  UNIQUE (tenant_id, sequence),
  UNIQUE (tenant_id, event_hash),
  FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
  FOREIGN KEY (attendance_id, tenant_id) REFERENCES attendance(id, tenant_id) ON DELETE RESTRICT,
  FOREIGN KEY (correction_id, tenant_id) REFERENCES attendance_corrections(id, tenant_id) ON DELETE RESTRICT
);

CREATE INDEX IF NOT EXISTS ix_attendance_audit_attendance
  ON attendance_audit_log (tenant_id, attendance_id, sequence DESC);

CREATE INDEX IF NOT EXISTS ix_attendance_audit_correction
  ON attendance_audit_log (tenant_id, correction_id, sequence DESC);

CREATE TRIGGER IF NOT EXISTS tr_attendance_audit_no_update
BEFORE UPDATE ON attendance_audit_log
BEGIN
  SELECT RAISE(ABORT, 'El registro de auditoría es inmutable');
END;

CREATE TRIGGER IF NOT EXISTS tr_attendance_audit_no_delete
BEFORE DELETE ON attendance_audit_log
BEGIN
  SELECT RAISE(ABORT, 'El registro de auditoría es inmutable');
END;

CREATE TABLE IF NOT EXISTS application_audit_log (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  request_id TEXT NOT NULL UNIQUE,
  tenant_id INTEGER,
  occurred_at TEXT NOT NULL,
  area TEXT NOT NULL,
  actor_type TEXT NOT NULL CHECK (actor_type IN ('anonymous', 'system', 'superadmin', 'admin', 'worker')),
  actor_id INTEGER,
  actor_label TEXT NOT NULL,
  method TEXT NOT NULL,
  request_uri TEXT NOT NULL,
  action_name TEXT,
  response_status INTEGER NOT NULL,
  outcome TEXT NOT NULL CHECK (outcome IN ('success', 'failure', 'redirect', 'unknown')),
  ip_address TEXT,
  user_agent TEXT,
  duration_ms INTEGER NOT NULL DEFAULT 0,
  peak_memory_bytes INTEGER NOT NULL DEFAULT 0,
  details_json TEXT NOT NULL DEFAULT '{}',
  FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE RESTRICT
);

CREATE INDEX IF NOT EXISTS ix_application_audit_tenant_date
  ON application_audit_log (tenant_id, occurred_at DESC);

CREATE INDEX IF NOT EXISTS ix_application_audit_actor
  ON application_audit_log (tenant_id, actor_type, actor_id, occurred_at DESC);

CREATE TRIGGER IF NOT EXISTS tr_application_audit_no_update
BEFORE UPDATE ON application_audit_log
BEGIN
  SELECT RAISE(ABORT, 'El registro global de actividad es inmutable');
END;

CREATE TRIGGER IF NOT EXISTS tr_application_audit_no_delete
BEFORE DELETE ON application_audit_log
BEGIN
  SELECT RAISE(ABORT, 'El registro global de actividad es inmutable');
END;

CREATE TABLE IF NOT EXISTS schedules (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  tenant_id INTEGER NOT NULL,
  legacy_id INTEGER,
  employee_id INTEGER NOT NULL,
  weekday INTEGER NOT NULL CHECK (weekday BETWEEN 1 AND 7),
  valid_from TEXT,
  valid_until TEXT,
  start_time TEXT NOT NULL,
  end_time TEXT NOT NULL,
  attendance_type_id INTEGER,
  UNIQUE (id, tenant_id),
  UNIQUE (tenant_id, legacy_id),
  CHECK (valid_from IS NULL OR valid_until IS NULL OR valid_until >= valid_from),
  FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
  FOREIGN KEY (employee_id, tenant_id) REFERENCES employees(id, tenant_id) ON DELETE CASCADE,
  FOREIGN KEY (attendance_type_id, tenant_id) REFERENCES attendance_types(id, tenant_id) ON DELETE RESTRICT
);

CREATE INDEX IF NOT EXISTS ix_schedules_employee
  ON schedules (tenant_id, employee_id, weekday, valid_from, valid_until);

CREATE INDEX IF NOT EXISTS ix_schedules_employee_fk
  ON schedules (employee_id, tenant_id);

CREATE INDEX IF NOT EXISTS ix_schedules_type_fk
  ON schedules (attendance_type_id, tenant_id);

CREATE TABLE IF NOT EXISTS incidents (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  tenant_id INTEGER NOT NULL,
  legacy_id INTEGER,
  employee_id INTEGER NOT NULL,
  type TEXT NOT NULL,
  incident_date TEXT NOT NULL,
  notes TEXT,
  UNIQUE (id, tenant_id),
  UNIQUE (tenant_id, legacy_id),
  FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
  FOREIGN KEY (employee_id, tenant_id) REFERENCES employees(id, tenant_id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS ix_incidents_employee
  ON incidents (tenant_id, employee_id, incident_date DESC);

CREATE INDEX IF NOT EXISTS ix_incidents_employee_fk
  ON incidents (employee_id, tenant_id);

CREATE TABLE IF NOT EXISTS absences (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  tenant_id INTEGER NOT NULL,
  legacy_id INTEGER,
  employee_id INTEGER NOT NULL,
  start_date TEXT NOT NULL,
  start_time TEXT,
  end_date TEXT,
  end_time TEXT,
  absence_type TEXT NOT NULL,
  reason TEXT,
  attachment TEXT,
  attachment_name TEXT,
  status TEXT NOT NULL DEFAULT 'pending' CHECK (status IN ('pending', 'approved', 'rejected')),
  requested_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  approved INTEGER NOT NULL DEFAULT 0 CHECK (approved IN (0, 1)),
  source TEXT NOT NULL DEFAULT 'worker' CHECK (source IN ('worker', 'admin', 'import')),
  created_by_admin_id INTEGER,
  employee_confirmation_status TEXT NOT NULL DEFAULT 'not_required'
    CHECK (employee_confirmation_status IN ('not_required', 'pending', 'accepted', 'rejected')),
  responded_by_user_id INTEGER,
  employee_comment TEXT,
  responded_at TEXT,
  UNIQUE (id, tenant_id),
  UNIQUE (tenant_id, legacy_id),
  CHECK (end_date IS NULL OR end_date >= start_date),
  CHECK (end_date IS NOT NULL OR end_time IS NULL),
  CHECK (source <> 'admin' OR created_by_admin_id IS NOT NULL),
  FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
  FOREIGN KEY (employee_id, tenant_id) REFERENCES employees(id, tenant_id) ON DELETE CASCADE,
  FOREIGN KEY (created_by_admin_id, tenant_id) REFERENCES tenant_admins(id, tenant_id) ON DELETE RESTRICT,
  FOREIGN KEY (responded_by_user_id, tenant_id) REFERENCES users(id, tenant_id) ON DELETE RESTRICT
);

CREATE INDEX IF NOT EXISTS ix_absences_employee
  ON absences (tenant_id, employee_id, start_date DESC);

CREATE INDEX IF NOT EXISTS ix_absences_employee_fk
  ON absences (employee_id, tenant_id);


CREATE TABLE IF NOT EXISTS sick_leaves (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  tenant_id INTEGER NOT NULL,
  employee_id INTEGER NOT NULL,
  start_date TEXT NOT NULL,
  start_time TEXT,
  end_date TEXT,
  end_time TEXT,
  leave_type TEXT NOT NULL DEFAULT 'Baja médica',
  reason TEXT,
  attachment TEXT,
  attachment_name TEXT,
  created_by_admin_id INTEGER NOT NULL,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE (id, tenant_id),
  CHECK (end_date IS NULL OR end_date >= start_date),
  CHECK (end_date IS NOT NULL OR end_time IS NULL),
  FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
  FOREIGN KEY (employee_id, tenant_id) REFERENCES employees(id, tenant_id) ON DELETE CASCADE,
  FOREIGN KEY (created_by_admin_id, tenant_id) REFERENCES tenant_admins(id, tenant_id) ON DELETE RESTRICT
);

CREATE INDEX IF NOT EXISTS ix_sick_leaves_employee
  ON sick_leaves (tenant_id, employee_id, start_date DESC);

CREATE INDEX IF NOT EXISTS ix_sick_leaves_employee_fk
  ON sick_leaves (employee_id, tenant_id);

CREATE TABLE IF NOT EXISTS vacations (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  tenant_id INTEGER NOT NULL,
  legacy_id INTEGER,
  employee_id INTEGER NOT NULL,
  start_date TEXT NOT NULL,
  end_date TEXT NOT NULL,
  approved INTEGER NOT NULL DEFAULT 0 CHECK (approved IN (0, 1)),
  UNIQUE (id, tenant_id),
  UNIQUE (tenant_id, legacy_id),
  CHECK (end_date >= start_date),
  FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
  FOREIGN KEY (employee_id, tenant_id) REFERENCES employees(id, tenant_id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS ix_vacations_employee
  ON vacations (tenant_id, employee_id, start_date DESC);

CREATE INDEX IF NOT EXISTS ix_vacations_employee_fk
  ON vacations (employee_id, tenant_id);

CREATE TABLE IF NOT EXISTS holidays (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  tenant_id INTEGER NOT NULL,
  legacy_id INTEGER,
  holiday_date TEXT NOT NULL,
  name TEXT NOT NULL,
  description TEXT,
  UNIQUE (id, tenant_id),
  UNIQUE (tenant_id, legacy_id),
  UNIQUE (tenant_id, holiday_date),
  FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS ix_holidays_date
  ON holidays (tenant_id, holiday_date);

CREATE TABLE IF NOT EXISTS contracts (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  tenant_id INTEGER NOT NULL,
  legacy_id INTEGER,
  employee_id INTEGER NOT NULL,
  contract_type_id INTEGER,
  contract_type TEXT NOT NULL,
  signed_date TEXT,
  end_date TEXT,
  annual_salary NUMERIC,
  workday_type TEXT NOT NULL DEFAULT 'full_time' CHECK (workday_type IN ('full_time', 'part_time')),
  work_arrangement TEXT NOT NULL DEFAULT 'on_site' CHECK (work_arrangement IN ('on_site', 'remote', 'hybrid')),
  notes TEXT,
  UNIQUE (id, tenant_id),
  UNIQUE (tenant_id, legacy_id),
  CHECK (signed_date IS NULL OR end_date IS NULL OR end_date >= signed_date),
  FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
  FOREIGN KEY (employee_id, tenant_id) REFERENCES employees(id, tenant_id) ON DELETE CASCADE,
  FOREIGN KEY (contract_type_id, tenant_id) REFERENCES contract_types(id, tenant_id) ON DELETE RESTRICT
);

CREATE INDEX IF NOT EXISTS ix_contracts_employee
  ON contracts (tenant_id, employee_id);

CREATE INDEX IF NOT EXISTS ix_contracts_employee_fk
  ON contracts (employee_id, tenant_id);

CREATE INDEX IF NOT EXISTS ix_contracts_type
  ON contracts (contract_type_id, tenant_id);

CREATE TRIGGER IF NOT EXISTS tr_contract_type_tenant_insert
BEFORE INSERT ON contracts
WHEN NEW.contract_type_id IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM contract_types ct
    WHERE ct.id = NEW.contract_type_id AND ct.tenant_id = NEW.tenant_id
  )
BEGIN
  SELECT RAISE(ABORT, 'El tipo de contrato no pertenece a esta organización');
END;

CREATE TRIGGER IF NOT EXISTS tr_contract_type_tenant_update
BEFORE UPDATE OF contract_type_id, tenant_id ON contracts
WHEN NEW.contract_type_id IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM contract_types ct
    WHERE ct.id = NEW.contract_type_id AND ct.tenant_id = NEW.tenant_id
  )
BEGIN
  SELECT RAISE(ABORT, 'El tipo de contrato no pertenece a esta organización');
END;

CREATE TABLE IF NOT EXISTS call_periods (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  tenant_id INTEGER NOT NULL,
  contract_id INTEGER NOT NULL,
  start_date TEXT NOT NULL,
  end_date TEXT NOT NULL,
  notice_date TEXT,
  notification_method TEXT,
  status TEXT NOT NULL DEFAULT 'planned' CHECK (status IN ('planned', 'notified', 'active', 'completed', 'cancelled')),
  notes TEXT,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE (id, tenant_id),
  UNIQUE (tenant_id, contract_id, start_date, end_date),
  CHECK (end_date >= start_date),
  CHECK (status = 'planned' OR status = 'cancelled' OR notice_date IS NOT NULL),
  FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
  FOREIGN KEY (contract_id, tenant_id) REFERENCES contracts(id, tenant_id) ON DELETE RESTRICT
);

CREATE INDEX IF NOT EXISTS ix_call_periods_contract
  ON call_periods (tenant_id, contract_id, start_date DESC);

CREATE INDEX IF NOT EXISTS ix_call_periods_status
  ON call_periods (tenant_id, status, start_date);

CREATE TRIGGER IF NOT EXISTS tr_call_period_fixed_insert
BEFORE INSERT ON call_periods
WHEN NOT EXISTS (
  SELECT 1
  FROM contracts c
  JOIN contract_types ct
    ON ct.id = c.contract_type_id AND ct.tenant_id = c.tenant_id
  WHERE c.id = NEW.contract_id
    AND c.tenant_id = NEW.tenant_id
    AND ct.is_fixed_discontinuous = 1
)
BEGIN
  SELECT RAISE(ABORT, 'Los llamamientos solo pueden asociarse a contratos fijo-discontinuos');
END;

CREATE TRIGGER IF NOT EXISTS tr_call_period_fixed_update
BEFORE UPDATE OF contract_id, tenant_id ON call_periods
WHEN NOT EXISTS (
  SELECT 1
  FROM contracts c
  JOIN contract_types ct
    ON ct.id = c.contract_type_id AND ct.tenant_id = c.tenant_id
  WHERE c.id = NEW.contract_id
    AND c.tenant_id = NEW.tenant_id
    AND ct.is_fixed_discontinuous = 1
)
BEGIN
  SELECT RAISE(ABORT, 'Los llamamientos solo pueden asociarse a contratos fijo-discontinuos');
END;

CREATE TRIGGER IF NOT EXISTS tr_call_period_overlap_insert
BEFORE INSERT ON call_periods
WHEN NEW.status <> 'cancelled' AND EXISTS (
  SELECT 1 FROM call_periods cp
  WHERE cp.tenant_id = NEW.tenant_id
    AND cp.contract_id = NEW.contract_id
    AND cp.status <> 'cancelled'
    AND NEW.start_date <= cp.end_date
    AND NEW.end_date >= cp.start_date
)
BEGIN
  SELECT RAISE(ABORT, 'El periodo de llamamiento se solapa con otro existente');
END;

CREATE TRIGGER IF NOT EXISTS tr_call_period_overlap_update
BEFORE UPDATE OF contract_id, start_date, end_date, status ON call_periods
WHEN NEW.status <> 'cancelled' AND EXISTS (
  SELECT 1 FROM call_periods cp
  WHERE cp.tenant_id = NEW.tenant_id
    AND cp.contract_id = NEW.contract_id
    AND cp.id <> NEW.id
    AND cp.status <> 'cancelled'
    AND NEW.start_date <= cp.end_date
    AND NEW.end_date >= cp.start_date
)
BEGIN
  SELECT RAISE(ABORT, 'El periodo de llamamiento se solapa con otro existente');
END;

CREATE TRIGGER IF NOT EXISTS tr_contract_keep_fixed_with_calls
BEFORE UPDATE OF contract_type_id ON contracts
WHEN EXISTS (
  SELECT 1 FROM call_periods cp
  WHERE cp.tenant_id = OLD.tenant_id AND cp.contract_id = OLD.id
)
AND NOT EXISTS (
  SELECT 1 FROM contract_types ct
  WHERE ct.id = NEW.contract_type_id
    AND ct.tenant_id = NEW.tenant_id
    AND ct.is_fixed_discontinuous = 1
)
BEGIN
  SELECT RAISE(ABORT, 'Un contrato con llamamientos debe seguir siendo fijo-discontinuo');
END;

CREATE TRIGGER IF NOT EXISTS tr_contract_type_no_delete_when_used
BEFORE DELETE ON contract_types
WHEN EXISTS (
  SELECT 1 FROM contracts c
  WHERE c.tenant_id = OLD.tenant_id AND c.contract_type_id = OLD.id
)
BEGIN
  SELECT RAISE(ABORT, 'El tipo de contrato está asociado a contratos');
END;

CREATE TABLE IF NOT EXISTS import_runs (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  tenant_id INTEGER NOT NULL,
  source_name TEXT NOT NULL,
  status TEXT NOT NULL DEFAULT 'running' CHECK (status IN ('running', 'completed', 'failed')),
  summary TEXT,
  started_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  finished_at TEXT,
  FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS ix_import_runs_tenant
  ON import_runs (tenant_id, started_at DESC);


CREATE TABLE IF NOT EXISTS internal_message_types (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  tenant_id INTEGER NOT NULL,
  name TEXT NOT NULL,
  color TEXT NOT NULL DEFAULT '#e9967a',
  emoji TEXT NOT NULL DEFAULT '📢',
  active INTEGER NOT NULL DEFAULT 1 CHECK (active IN (0,1)),
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE (id, tenant_id),
  UNIQUE (tenant_id, name),
  FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS ix_internal_message_types_tenant
  ON internal_message_types (tenant_id, active, name);

CREATE TABLE IF NOT EXISTS internal_messages (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  tenant_id INTEGER NOT NULL,
  message_type_id INTEGER NOT NULL,
  start_date TEXT NOT NULL,
  highlight_days INTEGER NOT NULL DEFAULT 1 CHECK (highlight_days BETWEEN 1 AND 3650),
  message TEXT NOT NULL,
  active INTEGER NOT NULL DEFAULT 1 CHECK (active IN (0,1)),
  created_by_admin_id INTEGER,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE (id, tenant_id),
  FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
  FOREIGN KEY (message_type_id, tenant_id) REFERENCES internal_message_types(id, tenant_id) ON DELETE RESTRICT,
  FOREIGN KEY (created_by_admin_id, tenant_id) REFERENCES tenant_admins(id, tenant_id) ON DELETE RESTRICT
);

CREATE INDEX IF NOT EXISTS ix_internal_messages_tenant_date
  ON internal_messages (tenant_id, active, start_date DESC);

PRAGMA user_version = 12;

```

### front/api.php

```php
<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

use JA\Auth;
use JA\AbsenceAttachment;
use JA\AbsenceService;
use JA\AttendanceAudit;
use JA\AttendanceTypeCatalog;
use JA\Csrf;
use JA\CompanyLocation;
use JA\Database;
use JA\EmailNotifier;
use JA\Session;
use JA\ScheduleCompliance;
use JA\Settings;
use JA\TenantContext;

Session::start('front');
$tenant = TenantContext::current();
$pdo = Database::pdo();

$minutesBetween = static function (?string $start, ?string $end): ?int {
    if (!$start || !$end) {
        return null;
    }
    $startSeconds = strtotime('2000-01-01 ' . $start);
    $endSeconds = strtotime('2000-01-01 ' . $end);
    if ($startSeconds === false || $endSeconds === false) {
        return null;
    }
    if ($endSeconds < $startSeconds) {
        $endSeconds += 86400;
    }
    return max(0, (int)(($endSeconds - $startSeconds) / 60));
};

try {
    $user = Auth::requireEmployee();
    $tenantId = (int)$tenant['id'];
    $employeeId = (int)$user['employee_id'];
    $notifyScheduleException = static function (string $eventType, string $date, string $time) use ($pdo, $tenantId, $employeeId, $tenant, $user): array {
        $compliance = ScheduleCompliance::evaluate($pdo, $tenantId, $employeeId, $date, substr($time, 0, 5));
        if (!$compliance['outside']) {
            return ['outside' => false, 'attempted' => false, 'sent' => false];
        }
        $statement = $pdo->prepare('SELECT nombre,apellido FROM employees WHERE id=:employee AND tenant_id=:tenant LIMIT 1');
        $statement->execute(['employee' => $employeeId, 'tenant' => $tenantId]);
        $employee = $statement->fetch() ?: ['nombre' => (string)$user['name'], 'apellido' => ''];
        $notification = EmailNotifier::notifyOutOfScheduleClocking(
            $pdo,
            $tenantId,
            $tenant,
            $employee,
            [
                'event_type' => $eventType,
                'date' => $date,
                'time' => substr($time, 0, 5),
                'schedule_label' => $compliance['schedule_label'],
                'ip_address' => (string)($_SERVER['REMOTE_ADDR'] ?? ''),
            ]
        );
        return ['outside' => true] + $notification;
    };
    AttendanceAudit::bootstrapTenant($pdo, $tenantId);
    $action = (string)($_GET['action'] ?? 'bootstrap');
    $method = (string)($_SERVER['REQUEST_METHOD'] ?? 'GET');
    $contentType = strtolower((string)($_SERVER['CONTENT_TYPE'] ?? ''));
    $payload = str_contains($contentType, 'multipart/form-data')
        ? $_POST
        : json_decode((string)file_get_contents('php://input'), true);
    $payload = is_array($payload) ? $payload : [];

    if ($method === 'POST') {
        Csrf::check($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($payload['csrf'] ?? null));
    }

    if ($action === 'absence_attachment' && $method === 'GET') {
        $statement = $pdo->prepare(
            'SELECT attachment,attachment_name FROM absences
             WHERE id=:id AND employee_id=:employee AND tenant_id=:tenant LIMIT 1'
        );
        $statement->execute([
            'id' => (int)($_GET['id'] ?? 0),
            'employee' => $employeeId,
            'tenant' => $tenantId,
        ]);
        $absence = $statement->fetch();
        if (!$absence || empty($absence['attachment'])) {
            throw new RuntimeException('El adjunto solicitado no está disponible.', 404);
        }
        AbsenceAttachment::download((string)$absence['attachment'], (string)($absence['attachment_name'] ?? 'adjunto'), $tenantId);
    }

    if ($action === 'sick_leave_attachment' && $method === 'GET') {
        $id=(int)($_GET['id'] ?? 0);
        $statement=$pdo->prepare('SELECT attachment,attachment_name FROM sick_leaves WHERE id=:id AND employee_id=:employee AND tenant_id=:tenant LIMIT 1');
        $statement->execute(['id'=>$id,'employee'=>$employeeId,'tenant'=>$tenantId]);
        $leave=$statement->fetch();
        if(!$leave || empty($leave['attachment'])) { throw new RuntimeException('Adjunto no encontrado.',404); }
        AbsenceAttachment::download((string)$leave['attachment'],(string)($leave['attachment_name'] ?? 'adjunto'),$tenantId);
    }

    if ($action === 'bootstrap' && $method === 'GET') {
        $employee = $pdo->prepare('SELECT id,nombre,apellido,departamento,role FROM employees WHERE id=:employee AND tenant_id=:tenant AND active=1');
        $employee->execute(['employee' => $employeeId, 'tenant' => $tenantId]);
        $employeeRow = $employee->fetch();
        if (!$employeeRow) {
            throw new RuntimeException('La persona asociada no está activa.', 403);
        }

        $open = $pdo->prepare(
            "SELECT a.id,a.work_date,a.start_time,a.attendance_type_id,
                    ft.name AS attendance_type_name,
                    COALESCE(c.id,wc.id) AS pending_correction_id
             FROM attendance a
             LEFT JOIN attendance_types ft ON ft.id=a.attendance_type_id AND ft.tenant_id=a.tenant_id
             LEFT JOIN attendance_corrections c
               ON c.attendance_id=a.id AND c.tenant_id=a.tenant_id AND c.status='pending'
             LEFT JOIN worker_attendance_corrections wc
               ON wc.attendance_id=a.id AND wc.tenant_id=a.tenant_id AND wc.status='pending'
             WHERE a.employee_id=:employee AND a.tenant_id=:tenant AND a.work_date=:today AND a.end_time IS NULL
             ORDER BY a.work_date DESC,a.start_time DESC,a.id DESC LIMIT 1"
        );
        $open->execute(['employee' => $employeeId, 'tenant' => $tenantId, 'today' => date('Y-m-d')]);
        $openShift = $open->fetch() ?: null;

        $attendanceStatement = $pdo->prepare(
            "SELECT a.id,a.work_date,a.start_time,a.end_time,a.attendance_type_id,
                    ft.name AS attendance_type_name,
                    CASE WHEN c.id IS NULL AND wc.id IS NULL THEN 0 ELSE 1 END AS correction_pending,
                    CASE WHEN wc.id IS NULL THEN 0 ELSE 1 END AS worker_correction_pending
             FROM attendance a
             LEFT JOIN attendance_types ft ON ft.id=a.attendance_type_id AND ft.tenant_id=a.tenant_id
             LEFT JOIN attendance_corrections c
               ON c.attendance_id=a.id AND c.tenant_id=a.tenant_id AND c.status='pending'
             LEFT JOIN worker_attendance_corrections wc
               ON wc.attendance_id=a.id AND wc.tenant_id=a.tenant_id AND wc.status='pending'
             WHERE a.employee_id=:employee AND a.tenant_id=:tenant
             ORDER BY a.work_date DESC,a.start_time DESC LIMIT 180"
        );
        $attendanceStatement->execute(['employee' => $employeeId, 'tenant' => $tenantId]);
        $attendance = $attendanceStatement->fetchAll();
        foreach ($attendance as &$record) {
            $record['worked_minutes'] = $minutesBetween($record['start_time'], $record['end_time']);
        }
        unset($record);

        $attendanceTypes = AttendanceTypeCatalog::availableForEmployee($pdo, $tenantId, $employeeId);

        $absences = $pdo->prepare(
            "SELECT a.id,a.start_date,a.start_time,a.end_date,a.end_time,a.absence_type,a.reason,a.status,
                    a.requested_at,a.source,a.employee_confirmation_status,a.employee_comment,a.responded_at,
                    a.attachment_name,CASE WHEN a.attachment IS NULL THEN 0 ELSE 1 END AS has_attachment,
                    ta.full_name AS admin_name
             FROM absences a
             LEFT JOIN tenant_admins ta ON ta.id=a.created_by_admin_id AND ta.tenant_id=a.tenant_id
             WHERE a.employee_id=:employee AND a.tenant_id=:tenant
             ORDER BY CASE WHEN a.employee_confirmation_status='pending' THEN 0 ELSE 1 END,a.requested_at DESC LIMIT 80"
        );
        $absences->execute(['employee' => $employeeId, 'tenant' => $tenantId]);

        $sickLeaves = $pdo->prepare("SELECT id,start_date,start_time,end_date,end_time,leave_type,reason,created_at,attachment_name,CASE WHEN attachment IS NULL THEN 0 ELSE 1 END AS has_attachment FROM sick_leaves WHERE employee_id=:employee AND tenant_id=:tenant ORDER BY start_date DESC,id DESC LIMIT 80");
        $sickLeaves->execute(['employee'=>$employeeId,'tenant'=>$tenantId]);

        $fixedContract = $pdo->prepare(
            'SELECT 1 FROM contracts c
             JOIN contract_types ct ON ct.id=c.contract_type_id AND ct.tenant_id=c.tenant_id
             WHERE c.employee_id=:employee AND c.tenant_id=:tenant
               AND ct.is_fixed_discontinuous=1
               AND (c.end_date IS NULL OR c.end_date >= :today)
             LIMIT 1'
        );
        $fixedContract->execute(['employee' => $employeeId, 'tenant' => $tenantId, 'today' => date('Y-m-d')]);
        $isFixedDiscontinuous = (bool)$fixedContract->fetchColumn();
        $callPeriods = $pdo->prepare(
            'SELECT cp.id,cp.contract_id,cp.start_date,cp.end_date,cp.notice_date,
                    cp.notification_method,cp.status,cp.notes
             FROM call_periods cp
             JOIN contracts c ON c.id=cp.contract_id AND c.tenant_id=cp.tenant_id
             WHERE cp.tenant_id=:tenant AND c.employee_id=:employee
               AND cp.status <> \'planned\' AND cp.notice_date IS NOT NULL
             ORDER BY cp.start_date DESC,cp.id DESC LIMIT 100'
        );
        $callPeriods->execute(['tenant' => $tenantId, 'employee' => $employeeId]);

        $corrections = $pdo->prepare(
            "SELECT c.*,ta.full_name AS admin_name,
                    original_ft.name AS original_attendance_type_name,
                    proposed_ft.name AS proposed_attendance_type_name
             FROM attendance_corrections c
             JOIN tenant_admins ta ON ta.id=c.requested_by_admin_id AND ta.tenant_id=c.tenant_id
             LEFT JOIN attendance_types original_ft ON original_ft.id=c.original_attendance_type_id AND original_ft.tenant_id=c.tenant_id
             LEFT JOIN attendance_types proposed_ft ON proposed_ft.id=c.proposed_attendance_type_id AND proposed_ft.tenant_id=c.tenant_id
             WHERE c.employee_id=:employee AND c.tenant_id=:tenant
             ORDER BY CASE c.status WHEN 'pending' THEN 0 ELSE 1 END,c.requested_at DESC
             LIMIT 100"
        );
        $corrections->execute(['employee' => $employeeId, 'tenant' => $tenantId]);

        $workerCorrections = $pdo->prepare(
            "SELECT c.*,u.name AS worker_user_name,ta.full_name AS admin_name,
                    original_ft.name AS original_attendance_type_name,
                    proposed_ft.name AS proposed_attendance_type_name
             FROM worker_attendance_corrections c
             JOIN users u ON u.id=c.requested_by_user_id AND u.tenant_id=c.tenant_id
             LEFT JOIN tenant_admins ta ON ta.id=c.responded_by_admin_id AND ta.tenant_id=c.tenant_id
             LEFT JOIN attendance_types original_ft ON original_ft.id=c.original_attendance_type_id AND original_ft.tenant_id=c.tenant_id
             LEFT JOIN attendance_types proposed_ft ON proposed_ft.id=c.proposed_attendance_type_id AND proposed_ft.tenant_id=c.tenant_id
             WHERE c.employee_id=:employee AND c.tenant_id=:tenant
             ORDER BY CASE c.status WHEN 'pending' THEN 0 ELSE 1 END,c.requested_at DESC
             LIMIT 100"
        );
        $workerCorrections->execute(['employee' => $employeeId, 'tenant' => $tenantId]);

        $auditLog = $pdo->prepare(
            'SELECT l.sequence,l.event_type,l.actor_type,l.actor_label,l.event_at,l.event_hash,l.correction_id
             FROM attendance_audit_log l
             JOIN attendance a ON a.id=l.attendance_id AND a.tenant_id=l.tenant_id
             WHERE l.tenant_id=:tenant AND a.employee_id=:employee
             ORDER BY l.sequence DESC LIMIT 200'
        );
        $auditLog->execute(['tenant' => $tenantId, 'employee' => $employeeId]);
        $integrity = AttendanceAudit::verifyTenant($pdo, $tenantId);

        $monday = new DateTimeImmutable('monday this week');
        $firstWeekStart = $monday->modify('-15 weeks');
        $lastWeekEnd = $monday->modify('+6 days');
        $weeklyWork = $pdo->prepare(
            'SELECT work_date,start_time,end_time FROM attendance
             WHERE employee_id=:employee AND tenant_id=:tenant
               AND work_date BETWEEN :from_date AND :until_date
               AND end_time IS NOT NULL'
        );
        $weeklyWork->execute([
            'employee' => $employeeId,
            'tenant' => $tenantId,
            'from_date' => $firstWeekStart->format('Y-m-d'),
            'until_date' => $lastWeekEnd->format('Y-m-d'),
        ]);
        $workedByWeek = [];
        foreach ($weeklyWork->fetchAll() as $work) {
            $date = DateTimeImmutable::createFromFormat('!Y-m-d', (string)$work['work_date']);
            if (!$date) {
                continue;
            }
            $isoKey = $date->format('oW');
            $workedByWeek[$isoKey] = ($workedByWeek[$isoKey] ?? 0) + ($minutesBetween($work['start_time'], $work['end_time']) ?? 0);
        }

        $schedule = $pdo->prepare("SELECT s.weekday,s.valid_from,s.valid_until,s.start_time,s.end_time,s.attendance_type_id,ft.name AS attendance_type_name FROM schedules s LEFT JOIN attendance_types ft ON ft.id=s.attendance_type_id AND ft.tenant_id=s.tenant_id WHERE s.employee_id=:employee AND s.tenant_id=:tenant ORDER BY COALESCE(s.valid_from,'0000-00-00') DESC,s.weekday,s.start_time");
        $schedule->execute(['employee' => $employeeId, 'tenant' => $tenantId]);
        $scheduleRows = $schedule->fetchAll();

        $holiday = $pdo->prepare('SELECT holiday_date FROM holidays WHERE tenant_id=:tenant AND holiday_date BETWEEN :from_date AND :until_date');
        $holiday->execute([
            'tenant' => $tenantId,
            'from_date' => $firstWeekStart->format('Y-m-d'),
            'until_date' => $lastWeekEnd->format('Y-m-d'),
        ]);
        $holidayDates = array_fill_keys(array_column($holiday->fetchAll(), 'holiday_date'), true);

        $weeklyRows = [];
        for ($index = 0; $index < 16; $index++) {
            $weekStart = $monday->modify('-' . $index . ' weeks');
            $isoKey = $weekStart->format('oW');
            $required = 0;
            for ($day = 0; $day < 7; $day++) {
                $date = $weekStart->modify('+' . $day . ' days');
                $dateString = $date->format('Y-m-d');
                if (isset($holidayDates[$dateString])) {
                    continue;
                }
                foreach ($scheduleRows as $slot) {
                    if ((int)$slot['weekday'] !== $day + 1 || ($slot['valid_from'] && $dateString < $slot['valid_from']) || ($slot['valid_until'] && $dateString > $slot['valid_until'])) {
                        continue;
                    }
                    $required += $minutesBetween($slot['start_time'], $slot['end_time']) ?? 0;
                }
            }
            $weeklyRows[] = [
                'iso_week' => $isoKey,
                'worked_minutes' => $workedByWeek[$isoKey] ?? 0,
                'required_minutes' => $required,
            ];
        }

        $userSettings = $pdo->prepare('SELECT conciliacion FROM users WHERE id=:id AND tenant_id=:tenant LIMIT 1');
        $userSettings->execute(['id'=>(int)$user['id'],'tenant'=>$tenantId]);
        $conciliacion = (bool)$userSettings->fetchColumn();
        $messagesStatement = $pdo->prepare("SELECT m.id,m.start_date,m.highlight_days,m.message,t.name AS type_name,t.color,t.emoji,
            CASE WHEN :today BETWEEN m.start_date AND date(m.start_date, '+' || (m.highlight_days - 1) || ' days') THEN 1 ELSE 0 END AS highlighted
            FROM internal_messages m JOIN internal_message_types t ON t.id=m.message_type_id AND t.tenant_id=m.tenant_id
            WHERE m.tenant_id=:tenant AND m.active=1
            ORDER BY m.start_date DESC,m.id DESC");
        $messagesStatement->execute(['today'=>date('Y-m-d'),'tenant'=>$tenantId]);
        $internalMessages=$messagesStatement->fetchAll();

        ja_json([
            'ok' => true,
            'tenant' => ['name' => $tenant['name'], 'timezone' => $tenant['timezone']],
            'user' => $user,
            'employee' => $employeeRow,
            'attendance_types' => $attendanceTypes,
            'requires_attendance_type' => $attendanceTypes !== [],
            'open_shift' => $openShift,
            'attendance' => $attendance,
            'absences' => $absences->fetchAll(),
            'sick_leaves' => $sickLeaves->fetchAll(),
            'fixed_discontinuous' => $isFixedDiscontinuous,
            'call_periods' => $callPeriods->fetchAll(),
            'corrections' => $corrections->fetchAll(),
            'worker_corrections' => $workerCorrections->fetchAll(),
            'worker_attendance_edit_enabled' => Settings::bool($pdo, $tenantId, Settings::WORKER_ATTENDANCE_EDIT),
            'company_location' => CompanyLocation::status($pdo, $tenantId),
            'conciliacion' => $conciliacion,
            'internal_messages' => $internalMessages,
            'internal_toast_seconds' => max(1,min(120,(int)Settings::get($pdo,$tenantId,Settings::INTERNAL_TOAST_SECONDS))),
            'internal_toast_mode' => Settings::get($pdo,$tenantId,Settings::INTERNAL_TOAST_MODE),
            'audit_log' => $auditLog->fetchAll(),
            'integrity' => [
                'valid' => $integrity['valid'],
                'checked_events' => $integrity['checked_events'],
                'last_hash' => $integrity['last_hash'],
            ],
            'weekly' => $weeklyRows,
            'schedules' => $scheduleRows,
        ]);
    }

    if ($action === 'attendance_month' && $method === 'GET') {
        $month = trim((string)($_GET['month'] ?? date('Y-m')));
        if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month)) {
            throw new RuntimeException('El mes solicitado no es válido.', 422);
        }
        $monthStart = new DateTimeImmutable($month . '-01');
        $monthEnd = $monthStart->modify('last day of this month');
        $statement = $pdo->prepare(
            "SELECT a.id,a.work_date,a.start_time,a.end_time,a.attendance_type_id,
                    ft.name AS attendance_type_name,
                    CASE WHEN c.id IS NULL AND wc.id IS NULL THEN 0 ELSE 1 END AS correction_pending,
                    CASE WHEN wc.id IS NULL THEN 0 ELSE 1 END AS worker_correction_pending
             FROM attendance a
             LEFT JOIN attendance_types ft ON ft.id=a.attendance_type_id AND ft.tenant_id=a.tenant_id
             LEFT JOIN attendance_corrections c
               ON c.attendance_id=a.id AND c.tenant_id=a.tenant_id AND c.status='pending'
             LEFT JOIN worker_attendance_corrections wc
               ON wc.attendance_id=a.id AND wc.tenant_id=a.tenant_id AND wc.status='pending'
             WHERE a.employee_id=:employee AND a.tenant_id=:tenant
               AND a.work_date BETWEEN :month_start AND :month_end
             ORDER BY a.work_date,a.start_time,a.id"
        );
        $statement->execute([
            'employee' => $employeeId,
            'tenant' => $tenantId,
            'month_start' => $monthStart->format('Y-m-d'),
            'month_end' => $monthEnd->format('Y-m-d'),
        ]);
        $records = $statement->fetchAll();
        foreach ($records as &$record) {
            $record['worked_minutes'] = $minutesBetween($record['start_time'], $record['end_time']);
        }
        unset($record);

        $vacationStatement = $pdo->prepare(
            "SELECT id,start_date,end_date,approved
             FROM vacations
             WHERE employee_id=:employee AND tenant_id=:tenant
               AND start_date<=:month_end AND end_date>=:month_start
             ORDER BY start_date,end_date,id"
        );
        $vacationStatement->execute([
            'employee' => $employeeId,
            'tenant' => $tenantId,
            'month_start' => $monthStart->format('Y-m-d'),
            'month_end' => $monthEnd->format('Y-m-d'),
        ]);

        $holidayStatement = $pdo->prepare(
            "SELECT id,holiday_date,name,description
             FROM holidays
             WHERE tenant_id=:tenant AND holiday_date BETWEEN :month_start AND :month_end
             ORDER BY holiday_date,id"
        );
        $holidayStatement->execute([
            'tenant' => $tenantId,
            'month_start' => $monthStart->format('Y-m-d'),
            'month_end' => $monthEnd->format('Y-m-d'),
        ]);

        $absenceStatement = $pdo->prepare(
            "SELECT id,start_date,start_time,end_date,end_time,absence_type,status
             FROM absences
             WHERE employee_id=:employee AND tenant_id=:tenant
               AND status IN ('pending','approved')
               AND start_date<=:month_end AND (end_date IS NULL OR end_date>=:month_start)
             ORDER BY start_date,end_date,id"
        );
        $absenceStatement->execute([
            'employee' => $employeeId,
            'tenant' => $tenantId,
            'month_start' => $monthStart->format('Y-m-d'),
            'month_end' => $monthEnd->format('Y-m-d'),
        ]);

        $sickLeaveStatement = $pdo->prepare(
            "SELECT id,start_date,start_time,end_date,end_time,leave_type
             FROM sick_leaves
             WHERE employee_id=:employee AND tenant_id=:tenant
               AND start_date<=:month_end AND (end_date IS NULL OR end_date>=:month_start)
             ORDER BY start_date,end_date,id"
        );
        $sickLeaveStatement->execute(['employee'=>$employeeId,'tenant'=>$tenantId,'month_start'=>$monthStart->format('Y-m-d'),'month_end'=>$monthEnd->format('Y-m-d')]);

        ja_json([
            'ok' => true,
            'month' => $month,
            'attendance' => $records,
            'vacations' => $vacationStatement->fetchAll(),
            'holidays' => $holidayStatement->fetchAll(),
            'absences' => $absenceStatement->fetchAll(),
            'sick_leaves' => $sickLeaveStatement->fetchAll(),
        ]);
    }

    if ($action === 'start' && $method === 'POST') {
        if (!AttendanceAudit::verifyTenant($pdo, $tenantId)['valid']) {
            throw new RuntimeException('La cadena de integridad presenta una incidencia. El fichaje ha sido bloqueado.', 409);
        }
        $clockDate = date('Y-m-d');
        $clockTime = date('H:i:s');
        $transactionOpen = false;
        try {
            $pdo->exec('BEGIN IMMEDIATE TRANSACTION');
            $transactionOpen = true;
            $open = $pdo->prepare('SELECT id FROM attendance WHERE employee_id=:employee AND tenant_id=:tenant AND work_date=:today AND end_time IS NULL LIMIT 1');
            $open->execute(['employee' => $employeeId, 'tenant' => $tenantId, 'today' => $clockDate]);
            if ($open->fetchColumn()) {
                $pdo->exec('ROLLBACK');
                $transactionOpen = false;
                ja_json(['ok' => false, 'error' => 'Ya existe una jornada abierta.'], 409);
            }

            $requestedTypeId = (int)($payload['attendance_type_id'] ?? 0);
            $attendanceType = AttendanceTypeCatalog::requireSelection(
                $pdo,
                $tenantId,
                $employeeId,
                $requestedTypeId > 0 ? $requestedTypeId : null
            );

            $insert = $pdo->prepare(
                'INSERT INTO attendance (tenant_id,employee_id,attendance_type_id,work_date,start_time)
                 VALUES (:tenant,:employee,:attendance_type,:work_date,:start_time)'
            );
            $insert->execute([
                'tenant' => $tenantId,
                'employee' => $employeeId,
                'attendance_type' => $attendanceType !== null ? (int)$attendanceType['id'] : null,
                'work_date' => $clockDate,
                'start_time' => $clockTime,
            ]);
            $attendanceId = (int)$pdo->lastInsertId();
            $rowStatement = $pdo->prepare('SELECT * FROM attendance WHERE id=:id AND tenant_id=:tenant');
            $rowStatement->execute(['id' => $attendanceId, 'tenant' => $tenantId]);
            $row = $rowStatement->fetch();
            if (!$row) {
                throw new RuntimeException('No se pudo autenticar el inicio de jornada.');
            }
            AttendanceAudit::recordStateEvent(
                $pdo,
                $tenantId,
                $row,
                'clock_in',
                'worker',
                (int)$user['id'],
                (string)$user['name']
            );
            $pdo->exec('COMMIT');
            $transactionOpen = false;
        } catch (Throwable $exception) {
            if ($transactionOpen) {
                $pdo->exec('ROLLBACK');
            }
            throw $exception;
        }
        $notification = $notifyScheduleException('clock_in', $clockDate, $clockTime);
        $message = $attendanceType !== null ? 'Jornada iniciada: ' . $attendanceType['name'] . '.' : 'Jornada iniciada.';
        if ($notification['outside']) {
            $message .= $notification['sent'] ? ' Se ha avisado del fichaje fuera de horario.' : ' El fichaje está fuera del horario establecido.';
        }
        ja_json(['ok' => true, 'message' => $message, 'notification' => $notification]);
    }

    if ($action === 'end' && $method === 'POST') {
        if (!AttendanceAudit::verifyTenant($pdo, $tenantId)['valid']) {
            throw new RuntimeException('La cadena de integridad presenta una incidencia. El fichaje ha sido bloqueado.', 409);
        }
        $clockDate = date('Y-m-d');
        $clockTime = date('H:i:s');
        $transactionOpen = false;
        try {
            $pdo->exec('BEGIN IMMEDIATE TRANSACTION');
            $transactionOpen = true;
            $open = $pdo->prepare(
                'SELECT * FROM attendance
                 WHERE employee_id=:employee AND tenant_id=:tenant AND work_date=:today AND end_time IS NULL
                 ORDER BY work_date DESC,start_time DESC,id DESC LIMIT 1'
            );
            $open->execute(['employee' => $employeeId, 'tenant' => $tenantId, 'today' => $clockDate]);
            $row = $open->fetch();
            if (!$row) {
                $pdo->exec('ROLLBACK');
                $transactionOpen = false;
                ja_json(['ok' => false, 'error' => 'No hay una jornada abierta.'], 409);
            }
            $update = $pdo->prepare(
                'UPDATE attendance SET end_time=:end_time,updated_at=:updated_at
                 WHERE id=:id AND tenant_id=:tenant AND end_time IS NULL'
            );
            $update->execute([
                'end_time' => $clockTime,
                'updated_at' => date('Y-m-d H:i:s'),
                'id' => (int)$row['id'],
                'tenant' => $tenantId,
            ]);
            $updated = $pdo->prepare('SELECT * FROM attendance WHERE id=:id AND tenant_id=:tenant');
            $updated->execute(['id' => (int)$row['id'], 'tenant' => $tenantId]);
            $updatedRow = $updated->fetch();
            if (!$updatedRow) {
                throw new RuntimeException('No se pudo autenticar el fin de jornada.');
            }
            AttendanceAudit::recordStateEvent(
                $pdo,
                $tenantId,
                $updatedRow,
                'clock_out',
                'worker',
                (int)$user['id'],
                (string)$user['name'],
                ['state_before' => array_filter([
                    'attendance_id' => (int)$row['id'],
                    'employee_id' => (int)$row['employee_id'],
                    'attendance_type_id' => $row['attendance_type_id'] !== null ? (int)$row['attendance_type_id'] : null,
                    'work_date' => (string)$row['work_date'],
                    'start_time' => $row['start_time'],
                    'end_time' => $row['end_time'],
                ], static fn(mixed $value, string $key): bool => $key !== 'attendance_type_id' || $value !== null, ARRAY_FILTER_USE_BOTH)]
            );
            $pdo->exec('COMMIT');
            $transactionOpen = false;
        } catch (Throwable $exception) {
            if ($transactionOpen) {
                $pdo->exec('ROLLBACK');
            }
            throw $exception;
        }
        $notification = $notifyScheduleException('clock_out', $clockDate, $clockTime);
        $message = 'Jornada finalizada.';
        if ($notification['outside']) {
            $message .= $notification['sent'] ? ' Se ha avisado del fichaje fuera de horario.' : ' El fichaje está fuera del horario establecido.';
        }
        ja_json(['ok' => true, 'message' => $message, 'notification' => $notification]);
    }

    if ($action === 'correction_decision' && $method === 'POST') {
        $correctionId = (int)($payload['correction_id'] ?? 0);
        $decision = trim((string)($payload['decision'] ?? ''));
        $comment = trim((string)($payload['comment'] ?? ''));
        $password = (string)($payload['password'] ?? '');
        if ($correctionId < 1) {
            throw new RuntimeException('Solicitud de corrección no válida.', 422);
        }
        $reauthenticate = $pdo->prepare(
            'SELECT password_hash FROM users
             WHERE id=:id AND tenant_id=:tenant AND employee_id=:employee AND active=1 LIMIT 1'
        );
        $reauthenticate->execute(['id' => (int)$user['id'], 'tenant' => $tenantId, 'employee' => $employeeId]);
        $passwordHash = $reauthenticate->fetchColumn();
        if (!is_string($passwordHash) || $password === '' || !Auth::verifyPassword($password, $passwordHash)) {
            throw new RuntimeException('Confirma la decisión con tu contraseña actual.', 403);
        }
        AttendanceAudit::decideCorrection(
            $pdo,
            $tenantId,
            $employeeId,
            (int)$user['id'],
            (string)$user['name'],
            $correctionId,
            $decision,
            $comment
        );
        ja_json([
            'ok' => true,
            'message' => $decision === 'accepted'
                ? 'Corrección aceptada. La jornada se ha actualizado y auditado.'
                : 'Corrección rechazada. La jornada original se mantiene sin cambios.',
        ]);
    }

    if ($action === 'worker_correction_request' && $method === 'POST') {
        if (!Settings::bool($pdo, $tenantId, Settings::WORKER_ATTENDANCE_EDIT)) {
            throw new RuntimeException('La empresa no tiene habilitadas las solicitudes de edición de jornada.', 403);
        }
        $attendanceId = (int)($payload['attendance_id'] ?? 0);
        $password = (string)($payload['password'] ?? '');
        if ($attendanceId < 1) {
            throw new RuntimeException('Selecciona una jornada válida.', 422);
        }
        $reauthenticate = $pdo->prepare(
            'SELECT password_hash FROM users
             WHERE id=:id AND tenant_id=:tenant AND employee_id=:employee AND active=1 LIMIT 1'
        );
        $reauthenticate->execute(['id' => (int)$user['id'], 'tenant' => $tenantId, 'employee' => $employeeId]);
        $passwordHash = $reauthenticate->fetchColumn();
        if (!is_string($passwordHash) || $password === '' || !Auth::verifyPassword($password, $passwordHash)) {
            throw new RuntimeException('Confirma la solicitud con tu contraseña actual.', 403);
        }
        AttendanceAudit::proposeCorrectionByWorker(
            $pdo,
            $tenantId,
            $attendanceId,
            $employeeId,
            (int)$user['id'],
            (string)$user['name'],
            trim((string)($payload['work_date'] ?? '')),
            trim((string)($payload['start_time'] ?? '')) ?: null,
            trim((string)($payload['end_time'] ?? '')) ?: null,
            ((int)($payload['attendance_type_id'] ?? 0)) ?: null,
            trim((string)($payload['reason'] ?? ''))
        );
        ja_json([
            'ok' => true,
            'message' => 'Solicitud enviada. La jornada solo cambiará si administración la aprueba.',
        ]);
    }

    if ($action === 'set_conciliacion' && $method === 'POST') {
        $enabled = !empty($payload['enabled']) ? 1 : 0;
        $statement=$pdo->prepare('UPDATE users SET conciliacion=:enabled,updated_at=CURRENT_TIMESTAMP WHERE id=:id AND tenant_id=:tenant AND employee_id=:employee');
        $statement->execute(['enabled'=>$enabled,'id'=>(int)$user['id'],'tenant'=>$tenantId,'employee'=>$employeeId]);
        ja_json(['ok'=>true,'message'=>$enabled ? 'Conciliación activada.' : 'Conciliación desactivada.','conciliacion'=>(bool)$enabled]);
    }

    if ($action === 'admin_absence_decision' && $method === 'POST') {
        $absenceId = (int)($payload['absence_id'] ?? 0);
        $decision = trim((string)($payload['decision'] ?? ''));
        $comment = trim((string)($payload['comment'] ?? ''));
        $password = (string)($payload['password'] ?? '');
        if ($absenceId < 1) {
            throw new RuntimeException('Selecciona una propuesta de ausencia válida.', 422);
        }
        $reauthenticate = $pdo->prepare(
            'SELECT password_hash FROM users
             WHERE id=:id AND tenant_id=:tenant AND employee_id=:employee AND active=1 LIMIT 1'
        );
        $reauthenticate->execute(['id' => (int)$user['id'], 'tenant' => $tenantId, 'employee' => $employeeId]);
        $passwordHash = $reauthenticate->fetchColumn();
        if (!is_string($passwordHash) || $password === '' || !Auth::verifyPassword($password, $passwordHash)) {
            throw new RuntimeException('Confirma la decisión con tu contraseña actual.', 403);
        }
        AbsenceService::decideAdminProposal(
            $pdo,
            $tenantId,
            $employeeId,
            (int)$user['id'],
            $absenceId,
            $decision,
            $comment
        );
        ja_json([
            'ok' => true,
            'message' => $decision === 'accepted'
                ? 'Ausencia aceptada y aprobada.'
                : 'Ausencia rechazada. Administración podrá consultar tu respuesta.',
        ]);
    }

    if ($action === 'absence' && $method === 'POST') {
        $range = AbsenceService::normalizeRange(
            trim((string)($payload['start_date'] ?? '')),
            trim((string)($payload['start_time'] ?? '')) ?: null,
            trim((string)($payload['end_date'] ?? '')) ?: null,
            trim((string)($payload['end_time'] ?? '')) ?: null
        );
        $type = trim((string)($payload['absence_type'] ?? ''));
        $reason = trim((string)($payload['reason'] ?? ''));
        $attachment = AbsenceAttachment::store($_FILES['attachment'] ?? null, $tenantId);
        $absenceId = AbsenceService::createByWorker($pdo, $tenantId, $employeeId, $range, $type, $reason, $attachment);
        $absenceStatement = $pdo->prepare('SELECT * FROM absences WHERE id=:id AND tenant_id=:tenant LIMIT 1');
        $absenceStatement->execute(['id' => $absenceId, 'tenant' => $tenantId]);
        $absence = $absenceStatement->fetch();
        if (!$absence) {
            throw new RuntimeException('No se pudo recuperar la solicitud registrada.');
        }
        $employeeStatement = $pdo->prepare('SELECT nombre,apellido FROM employees WHERE id=:employee AND tenant_id=:tenant LIMIT 1');
        $employeeStatement->execute(['employee' => $employeeId, 'tenant' => $tenantId]);
        $employeeNotification = $employeeStatement->fetch() ?: ['nombre' => (string)$user['name'], 'apellido' => ''];
        $notification = EmailNotifier::notifyAbsenceRequest(
            $pdo,
            $tenantId,
            $tenant,
            $employeeNotification,
            $absence
        );
        $message = 'Solicitud de ausencia enviada.';
        if ($notification['attempted'] && $notification['sent']) {
            $message .= ' Administración ha sido notificada por email.';
        } elseif ($notification['attempted']) {
            $message .= ' La solicitud está guardada, pero el email no ha podido enviarse.';
        }
        ja_json(['ok' => true, 'message' => $message, 'notification' => $notification]);
    }

    ja_json(['ok' => false, 'error' => 'Operación no encontrada.'], 404);
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $status = $exception->getCode();
    if (!is_int($status) || $status < 400 || $status > 599) {
        $status = 500;
    }
    ja_json(['ok' => false, 'error' => $status === 500 ? 'No se pudo completar la operación.' : $exception->getMessage()], $status);
}

```

### front/app.js

```javascript
(() => {
  const main = document.querySelector('.front-main');
  const csrf = main.dataset.csrf;
  let state = null;
  let tenantTimezone = null;
  let selectedAttendanceTypeId = null;
  let attendanceView = localStorage.getItem('ja-attendance-view') === 'calendar' ? 'calendar' : 'list';
  let attendanceCalendarMonth = (() => {
    const now = new Date();
    return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`;
  })();

  const escapeHtml = value => String(value ?? '').replace(/[&<>'"]/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[char]));
  const minutes = value => {
    const total = Math.max(0, Math.round(Number(value) || 0));
    return `${Math.floor(total / 60)}h ${String(total % 60).padStart(2, '0')}m`;
  };
  const formatDate = value => value ? new Intl.DateTimeFormat('es-ES', {dateStyle: 'medium'}).format(new Date(`${value}T12:00:00`)) : '—';
  const formatDateTime = value => {
    if (!value) return '—';
    const date = new Date(value);
    return Number.isNaN(date.getTime()) ? String(value) : new Intl.DateTimeFormat('es-ES', {dateStyle:'short',timeStyle:'medium'}).format(date);
  };
  const shortTime = value => value ? String(value).slice(0, 5) : '—';
  const absenceBoundary = (date, time, openLabel = 'Sin fecha final') => {
    if (!date) return openLabel;
    return `${formatDate(date)}${time ? ` · ${shortTime(time)}` : ' · día completo'}`;
  };

  async function request(action, options = {}) {
    const {query = {}, ...fetchOptions} = options;
    const params = new URLSearchParams({action, ...query});
    const headers = {'Accept': 'application/json', 'X-CSRF-Token': csrf, ...(options.headers || {})};
    if (!(fetchOptions.body instanceof FormData)) headers['Content-Type'] = 'application/json';
    const response = await fetch(`api.php?${params.toString()}`, {
      ...fetchOptions,
      headers
    });
    const data = await response.json().catch(() => ({ok: false, error: 'Respuesta no válida.'}));
    if (!response.ok || !data.ok) throw new Error(data.error || 'No se pudo completar la operación.');
    return data;
  }

  function attendanceTable(rows) {
    if (!rows.length) return '<div class="empty">Todavía no hay jornadas registradas.</div>';
    return `<table><thead><tr><th>Fecha</th><th>Tipo</th><th>Entrada</th><th>Salida</th><th>Trabajado</th><th>Estado</th></tr></thead><tbody>${rows.map(row => `<tr><td>${escapeHtml(formatDate(row.work_date))}</td><td>${escapeHtml(row.attendance_type_name || 'Sin tipo')}</td><td>${escapeHtml(shortTime(row.start_time))}</td><td>${row.end_time ? escapeHtml(shortTime(row.end_time)) : '<span class="badge warning">Abierta</span>'}</td><td>${row.worked_minutes == null ? '—' : escapeHtml(minutes(row.worked_minutes))}</td><td>${Number(row.correction_pending) ? '<span class="badge warning">Corrección pendiente</span>' : '<span class="badge ok">Confirmada</span>'}</td></tr>`).join('')}</tbody></table>`;
  }

  function monthOffset(month, offset) {
    const [year, number] = month.split('-').map(Number);
    const date = new Date(year, number - 1 + offset, 1);
    return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`;
  }

  function renderAttendanceView() {
    const calendar = attendanceView === 'calendar';
    document.querySelector('#attendanceListView').hidden = calendar;
    document.querySelector('#attendanceCalendarView').hidden = !calendar;
    const listButton = document.querySelector('#attendanceListButton');
    const calendarButton = document.querySelector('#attendanceCalendarButton');
    listButton.classList.toggle('active', !calendar);
    calendarButton.classList.toggle('active', calendar);
    listButton.setAttribute('aria-pressed', String(!calendar));
    calendarButton.setAttribute('aria-pressed', String(calendar));
  }

  function renderAttendanceCalendar(data) {
    const month = data.month;
    const rows = data.attendance || [];
    const vacations = data.vacations || [];
    const holidays = data.holidays || [];
    const absences = data.absences || [];
    const sickLeaves = data.sick_leaves || [];
    const [year, number] = month.split('-').map(Number);
    const first = new Date(year, number - 1, 1);
    const daysInMonth = new Date(year, number, 0).getDate();
    const leadingDays = (first.getDay() + 6) % 7;
    const monthLabel = new Intl.DateTimeFormat('es-ES', {month: 'long', year: 'numeric'}).format(first);
    document.querySelector('#attendanceCalendarLabel').textContent = monthLabel.charAt(0).toUpperCase() + monthLabel.slice(1);
    const byDate = rows.reduce((groups, row) => {
      (groups[row.work_date] ||= []).push(row);
      return groups;
    }, {});
    const now = new Date();
    const today = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;
    const weekdays = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];
    const cells = Array.from({length: leadingDays}, () => '<div class="worker-month-day is-empty" aria-hidden="true"></div>');
    for (let day = 1; day <= daysInMonth; day += 1) {
      const date = `${month}-${String(day).padStart(2, '0')}`;
      const attendanceEvents = byDate[date] || [];
      const vacationEvents = vacations.filter(row => row.start_date <= date && row.end_date >= date);
      const holidayEvents = holidays.filter(row => row.holiday_date === date);
      const absenceEvents = absences.filter(row => row.start_date <= date && (!row.end_date || row.end_date >= date));
      const sickLeaveEvents = sickLeaves.filter(row => row.start_date <= date && (!row.end_date || row.end_date >= date));
      const attendanceHtml = attendanceEvents.map(row => {
        const stateClass = row.end_time ? (Number(row.correction_pending) ? 'is-pending' : '') : 'is-open';
        const duration = row.worked_minutes == null ? '' : ` · ${minutes(row.worked_minutes)}`;
        return `<span class="worker-month-event is-attendance ${stateClass}" title="${escapeHtml(row.attendance_type_name || 'Sin tipo')}"><strong>${escapeHtml(shortTime(row.start_time))}–${row.end_time ? escapeHtml(shortTime(row.end_time)) : 'abierta'}</strong><small>${escapeHtml(row.attendance_type_name || 'Sin tipo')}${escapeHtml(duration)}</small></span>`;
      }).join('');
      const vacationHtml = vacationEvents.map(row => `<span class="worker-month-event is-vacation ${Number(row.approved) ? '' : 'is-pending'}"><strong>Vacaciones</strong><small>${Number(row.approved) ? 'Aprobadas' : 'Pendientes'}</small></span>`).join('');
      const holidayHtml = holidayEvents.map(row => `<span class="worker-month-event is-holiday" title="${escapeHtml(row.description || row.name)}"><strong>Festivo</strong><small>${escapeHtml(row.name)}</small></span>`).join('');
      const absenceHtml = absenceEvents.map(row => {
        const status = row.status === 'approved' ? 'Aprobada' : 'Pendiente';
        let time = 'Día completo';
        if (row.start_date === date && row.start_time) time = `Desde ${shortTime(row.start_time)}`;
        if (row.end_date === date && row.end_time) time = `Hasta ${shortTime(row.end_time)}`;
        if (row.start_date === date && row.end_date === date && row.start_time && row.end_time) time = `${shortTime(row.start_time)}–${shortTime(row.end_time)}`;
        return `<span class="worker-month-event is-absence ${row.status === 'pending' ? 'is-pending' : ''}"><strong>${escapeHtml(row.absence_type || 'Ausencia')}</strong><small>${escapeHtml(status)} · ${escapeHtml(time)}</small></span>`;
      }).join('');
      const sickLeaveHtml = sickLeaveEvents.map(row => {
        let time = 'Día completo';
        if (row.start_date === date && row.start_time) time = `Desde ${shortTime(row.start_time)}`;
        if (row.end_date === date && row.end_time) time = `Hasta ${shortTime(row.end_time)}`;
        if (row.start_date === date && row.end_date === date && row.start_time && row.end_time) time = `${shortTime(row.start_time)}–${shortTime(row.end_time)}`;
        return `<span class="worker-month-event is-sick-leave"><strong>${escapeHtml(row.leave_type || 'Baja')}</strong><small>${escapeHtml(time)}</small></span>`;
      }).join('');
      const eventCount = attendanceEvents.length + vacationEvents.length + holidayEvents.length + absenceEvents.length + sickLeaveEvents.length;
      cells.push(`<div class="worker-month-day ${date === today ? 'is-today' : ''} ${eventCount ? 'has-events' : ''}"><time datetime="${date}">${day}</time><div class="worker-month-events">${holidayHtml}${vacationHtml}${sickLeaveHtml}${absenceHtml}${attendanceHtml}</div></div>`);
    }
    while (cells.length % 7 !== 0) cells.push('<div class="worker-month-day is-empty" aria-hidden="true"></div>');
    document.querySelector('#attendanceMonthCalendar').innerHTML = `${weekdays.map(day => `<div class="worker-month-weekday">${day}</div>`).join('')}${cells.join('')}`;
  }

  async function loadAttendanceMonth() {
    const container = document.querySelector('#attendanceMonthCalendar');
    container.innerHTML = '<div class="empty worker-month-loading">Cargando calendario del mes…</div>';
    try {
      const data = await request('attendance_month', {query: {month: attendanceCalendarMonth}});
      renderAttendanceCalendar(data);
    } catch (error) {
      container.innerHTML = `<div class="notice danger worker-month-loading">${escapeHtml(error.message)}</div>`;
    }
  }

  function sickLeaveTable(rows) {
    if (!rows.length) return '<div class="empty">No hay bajas registradas.</div>';
    return `<table><thead><tr><th>Periodo</th><th>Tipo</th><th>Motivo / adjunto</th></tr></thead><tbody>${rows.map(row => {
      const attachment = Number(row.has_attachment) ? `<br><a class="button small" href="api.php?action=sick_leave_attachment&id=${Number(row.id)}">Descargar ${escapeHtml(row.attachment_name || 'adjunto')}</a>` : '';
      return `<tr><td><strong>${escapeHtml(absenceBoundary(row.start_date,row.start_time))}</strong><br><small>hasta ${escapeHtml(absenceBoundary(row.end_date,row.end_time))}</small></td><td>${escapeHtml(row.leave_type || 'Baja')}</td><td class="wrap">${escapeHtml(row.reason || 'Sin observaciones.')}${attachment}</td></tr>`;
    }).join('')}</tbody></table>`;
  }

  function absenceTable(rows) {
    if (!rows.length) return '<div class="empty">No hay solicitudes de ausencia.</div>';
    const statusLabels = {pending:'Pendiente',approved:'Aprobada',rejected:'Rechazada'};
    const confirmationLabels = {not_required:'No necesaria',pending:'Esperando tu respuesta',accepted:'Aceptada por ti',rejected:'Rechazada por ti'};
    return `<table><thead><tr><th>Periodo</th><th>Tipo</th><th>Motivo / adjunto</th><th>Origen</th><th>Estado</th></tr></thead><tbody>${rows.map(row => {
      const cls = row.status === 'approved' ? 'ok' : (row.status === 'rejected' ? 'danger' : 'warning');
      const attachment = Number(row.has_attachment) ? `<br><a class="button small" href="api.php?action=absence_attachment&id=${Number(row.id)}">Descargar ${escapeHtml(row.attachment_name || 'adjunto')}</a>` : '';
      const origin = row.source === 'admin' ? `Administración${row.admin_name ? ` · ${escapeHtml(row.admin_name)}` : ''}<br><small>${escapeHtml(confirmationLabels[row.employee_confirmation_status] || row.employee_confirmation_status)}</small>` : 'Solicitud propia';
      return `<tr><td><strong>${escapeHtml(absenceBoundary(row.start_date,row.start_time))}</strong><br><small>hasta ${escapeHtml(absenceBoundary(row.end_date,row.end_time))}</small></td><td>${escapeHtml(row.absence_type)}</td><td class="wrap">${escapeHtml(row.reason || 'Sin motivo.')}${attachment}</td><td>${origin}</td><td><span class="badge ${cls}">${escapeHtml(statusLabels[row.status] || row.status)}</span></td></tr>`;
    }).join('')}</tbody></table>`;
  }

  function adminAbsenceProposalCards(rows) {
    return rows.map(row => `<article class="card correction-card is-pending" data-admin-absence-id="${Number(row.id)}">
      <div class="correction-card-head"><div><p class="eyebrow">Ausencia propuesta por administración</p><h3>${escapeHtml(row.absence_type)}</h3><p>${escapeHtml(row.admin_name || 'Administración')} · ${escapeHtml(formatDateTime(row.requested_at))}</p></div><span class="badge warning">Pendiente</span></div>
      <div class="correction-comparison"><div><small>DESDE</small><strong>${escapeHtml(absenceBoundary(row.start_date,row.start_time))}</strong></div><div class="correction-arrow">→</div><div><small>HASTA</small><strong>${escapeHtml(absenceBoundary(row.end_date,row.end_time))}</strong></div></div>
      <div class="correction-reason"><strong>Motivo de la empresa</strong><p>${escapeHtml(row.reason || 'Sin motivo adicional.')}</p>${Number(row.has_attachment) ? `<a class="button small" href="api.php?action=absence_attachment&id=${Number(row.id)}">Descargar ${escapeHtml(row.attachment_name || 'adjunto')}</a>` : ''}</div>
      <div class="grid-2"><label>Comentario opcional<textarea data-admin-absence-comment maxlength="1000" placeholder="Puedes explicar tu decisión."></textarea></label><label>Contraseña actual<input type="password" data-admin-absence-password autocomplete="current-password" required><small>Se solicita de nuevo para confirmar que la decisión es tuya.</small></label></div>
      <div class="actions"><button class="button primary" type="button" data-admin-absence-decision="accepted">Aceptar ausencia</button><button class="button danger" type="button" data-admin-absence-decision="rejected">Rechazar</button></div>
    </article>`).join('');
  }

  const callStatus = {notified: 'Notificado', active: 'En actividad', completed: 'Finalizado', cancelled: 'Cancelado'};
  function callPeriodTable(rows) {
    if (!rows.length) return '<div class="empty">Todavía no tienes llamamientos notificados.</div>';
    return `<table><thead><tr><th>Periodo de actividad</th><th>Aviso</th><th>Medio</th><th>Estado</th><th>Condiciones</th></tr></thead><tbody>${rows.map(row => { const cls = row.status === 'cancelled' ? 'danger' : (['active','completed'].includes(row.status) ? 'ok' : 'warning'); return `<tr><td><strong>${escapeHtml(formatDate(row.start_date))} — ${escapeHtml(formatDate(row.end_date))}</strong><br><small>Contrato #${Number(row.contract_id)}</small></td><td>${escapeHtml(formatDate(row.notice_date))}</td><td>${escapeHtml(row.notification_method || 'Portal de personal')}</td><td><span class="badge ${cls}">${escapeHtml(callStatus[row.status] || row.status)}</span></td><td class="wrap">${escapeHtml(row.notes || '')}</td></tr>`; }).join('')}</tbody></table>`;
  }

  const correctionStatus = {pending: 'Pendiente', accepted: 'Aceptada', rejected: 'Rechazada'};
  const auditLabels = {
    baseline: 'Anclaje inicial', clock_in: 'Inicio de jornada', clock_out: 'Fin de jornada',
    admin_created: 'Registro manual', imported: 'Registro importado', correction_proposed: 'Corrección propuesta',
    correction_accepted: 'Corrección aceptada', correction_rejected: 'Corrección rechazada'
  };

  function correctionCards(rows) {
    if (!rows.length) return '<div class="empty">No hay correcciones propuestas.</div>';
    return rows.map(row => {
      const pending = row.status === 'pending';
      const statusClass = row.status === 'accepted' ? 'ok' : (row.status === 'rejected' ? 'danger' : 'warning');
      return `<article class="card correction-card ${pending ? 'is-pending' : ''}" data-correction-id="${Number(row.id)}">
        <div class="correction-card-head"><div><p class="eyebrow">Jornada #${Number(row.attendance_id)} · ${escapeHtml(formatDate(row.original_work_date))}</p><h3>${pending ? 'La empresa propone una corrección' : `Corrección ${escapeHtml(correctionStatus[row.status] || row.status).toLowerCase()}`}</h3><p>Propuesta por ${escapeHtml(row.admin_name)} · ${escapeHtml(formatDateTime(row.requested_at))}</p></div><span class="badge ${statusClass}">${escapeHtml(correctionStatus[row.status] || row.status)}</span></div>
        <div class="correction-comparison">
          <div><small>REGISTRO ACTUAL</small><strong>${escapeHtml(formatDate(row.original_work_date))}</strong><span>${escapeHtml(shortTime(row.original_start_time))} — ${escapeHtml(shortTime(row.original_end_time))}</span><span>Tipo: ${escapeHtml(row.original_attendance_type_name || 'Sin tipo')}</span></div>
          <div class="correction-arrow">→</div>
          <div><small>CAMBIO PROPUESTO</small><strong>${escapeHtml(formatDate(row.proposed_work_date))}</strong><span>${escapeHtml(shortTime(row.proposed_start_time))} — ${row.proposed_end_time ? escapeHtml(shortTime(row.proposed_end_time)) : 'Jornada abierta'}</span><span>Tipo: ${escapeHtml(row.proposed_attendance_type_name || 'Sin tipo')}</span></div>
        </div>
        <div class="correction-reason"><strong>Motivo de la empresa</strong><p>${escapeHtml(row.reason)}</p></div>
        ${pending ? `<div class="grid-2"><label>Comentario opcional<textarea data-correction-comment maxlength="1000" placeholder="Puedes explicar tu decisión."></textarea></label><label>Contraseña actual<input type="password" data-correction-password autocomplete="current-password" required><small>Se solicita de nuevo para confirmar que la decisión es tuya. No se guarda en el registro.</small></label></div><div class="actions"><button class="button primary" type="button" data-correction-decision="accepted">Aceptar corrección</button><button class="button danger" type="button" data-correction-decision="rejected">Rechazar</button></div>` : `<div class="correction-response"><strong>Tu respuesta</strong><p>${escapeHtml(row.worker_comment || 'Sin comentario.')}</p><small>${escapeHtml(formatDateTime(row.responded_at))}</small></div>`}
        <div class="correction-hashes"><span>Huella de propuesta</span><code>${escapeHtml(row.proposal_event_hash || '—')}</code>${row.decision_event_hash ? `<span>Huella de decisión</span><code>${escapeHtml(row.decision_event_hash)}</code>` : ''}</div>
      </article>`;
    }).join('');
  }

  function workerCorrectionCards(rows) {
    if (!rows.length) return '<div class="empty">Todavía no has solicitado ninguna corrección.</div>';
    return rows.map(row => {
      const pending = row.status === 'pending';
      const statusClass = row.status === 'accepted' ? 'ok' : (row.status === 'rejected' ? 'danger' : 'warning');
      return `<article class="card correction-card ${pending ? 'is-pending' : ''}">
        <div class="correction-card-head"><div><p class="eyebrow">Jornada #${Number(row.attendance_id)} · ${escapeHtml(formatDate(row.original_work_date))}</p><h3>${pending ? 'Esperando a administración' : `Solicitud ${escapeHtml(correctionStatus[row.status] || row.status).toLowerCase()}`}</h3><p>Enviada ${escapeHtml(formatDateTime(row.requested_at))}</p></div><span class="badge ${statusClass}">${escapeHtml(correctionStatus[row.status] || row.status)}</span></div>
        <div class="correction-comparison">
          <div><small>REGISTRO ACTUAL</small><strong>${escapeHtml(formatDate(row.original_work_date))}</strong><span>${escapeHtml(shortTime(row.original_start_time))} — ${escapeHtml(shortTime(row.original_end_time))}</span><span>Tipo: ${escapeHtml(row.original_attendance_type_name || 'Sin tipo')}</span></div>
          <div class="correction-arrow">→</div>
          <div><small>TU PROPUESTA</small><strong>${escapeHtml(formatDate(row.proposed_work_date))}</strong><span>${escapeHtml(shortTime(row.proposed_start_time))} — ${escapeHtml(shortTime(row.proposed_end_time))}</span><span>Tipo: ${escapeHtml(row.proposed_attendance_type_name || 'Sin tipo')}</span></div>
        </div>
        <div class="correction-reason"><strong>Tu motivo</strong><p>${escapeHtml(row.reason)}</p></div>
        ${pending ? '<div class="notice warning">La jornada todavía no se ha modificado.</div>' : `<div class="correction-response"><strong>Respuesta de ${escapeHtml(row.admin_name || 'administración')}</strong><p>${escapeHtml(row.admin_comment || 'Sin comentario.')}</p><small>${escapeHtml(formatDateTime(row.responded_at))}</small></div>`}
        <div class="correction-hashes"><span>Huella de propuesta</span><code>${escapeHtml(row.proposal_event_hash || '—')}</code>${row.decision_event_hash ? `<span>Huella de decisión</span><code>${escapeHtml(row.decision_event_hash)}</code>` : ''}</div>
      </article>`;
    }).join('');
  }

  function populateWorkerCorrectionForm() {
    const form = document.querySelector('#workerCorrectionForm');
    const attendanceSelect = document.querySelector('#workerCorrectionAttendance');
    const id = Number(attendanceSelect.value);
    const row = state.attendance.find(item => Number(item.id) === id);
    if (!row) return;
    form.elements.work_date.value = row.work_date || '';
    form.elements.start_time.value = row.start_time ? String(row.start_time).slice(0, 8) : '';
    form.elements.end_time.value = row.end_time ? String(row.end_time).slice(0, 8) : '';
    const typeSelect = document.querySelector('#workerCorrectionType');
    const activeTypes = state.attendance_types || [];
    const currentId = row.attendance_type_id == null ? null : Number(row.attendance_type_id);
    const options = [{id: '', name: 'Sin tipo'}].concat(activeTypes.map(type => ({id: Number(type.id), name: type.name})));
    if (currentId && !options.some(type => Number(type.id) === currentId)) {
      options.push({id: currentId, name: `${row.attendance_type_name || 'Tipo actual'} (actual)`});
    }
    typeSelect.innerHTML = options.map(type => `<option value="${escapeHtml(type.id)}" ${String(type.id) === String(currentId ?? '') ? 'selected' : ''}>${escapeHtml(type.name)}</option>`).join('');
  }

  function renderWorkerCorrectionEditor() {
    const enabled = Boolean(state.worker_attendance_edit_enabled);
    const editor = document.querySelector('#workerCorrectionEditor');
    const notice = document.querySelector('#workerCorrectionFeatureNotice');
    const form = document.querySelector('#workerCorrectionForm');
    const empty = document.querySelector('#workerCorrectionEmpty');
    const select = document.querySelector('#workerCorrectionAttendance');
    notice.hidden = enabled;
    editor.hidden = !enabled;
    if (!enabled) return;
    const eligible = state.attendance.filter(row => row.end_time && !Number(row.correction_pending));
    select.innerHTML = eligible.map(row => `<option value="${Number(row.id)}">${escapeHtml(formatDate(row.work_date))} · ${escapeHtml(shortTime(row.start_time))}—${escapeHtml(shortTime(row.end_time))} · ${escapeHtml(row.attendance_type_name || 'Sin tipo')}</option>`).join('');
    form.hidden = eligible.length === 0;
    empty.hidden = eligible.length !== 0;
    if (eligible.length) populateWorkerCorrectionForm();
  }

  function auditTable(rows) {
    if (!rows.length) return '<div class="empty">No hay eventos registrados.</div>';
    return `<table><thead><tr><th>#</th><th>Fecha</th><th>Acción</th><th>Actor</th><th>Huella</th></tr></thead><tbody>${rows.map(row => `<tr><td>${Number(row.sequence)}</td><td>${escapeHtml(formatDateTime(row.event_at))}</td><td>${escapeHtml(auditLabels[row.event_type] || row.event_type)}</td><td>${escapeHtml(row.actor_label)}</td><td><code class="front-audit-hash">${escapeHtml(row.event_hash)}</code></td></tr>`).join('')}</tbody></table>`;
  }

  function renderAttendanceTypes() {
    const picker = document.querySelector('#attendanceTypePicker');
    const buttons = document.querySelector('#attendanceTypeButtons');
    const hint = document.querySelector('#attendanceTypeHint');
    const types = state.attendance_types || [];
    const open = state.open_shift;
    picker.hidden = types.length === 0 && !open?.attendance_type_name;
    if (picker.hidden) {
      selectedAttendanceTypeId = null;
      buttons.replaceChildren();
      return;
    }

    if (open) {
      selectedAttendanceTypeId = open.attendance_type_id == null ? null : Number(open.attendance_type_id);
    } else if (!types.some(type => Number(type.id) === Number(selectedAttendanceTypeId))) {
      const defaultType = types.find(type => Number(type.is_default) === 1);
      selectedAttendanceTypeId = defaultType ? Number(defaultType.id) : null;
    }

    buttons.innerHTML = types.map(type => {
      const id = Number(type.id);
      const selected = id === Number(selectedAttendanceTypeId);
      return `<button type="button" class="attendance-type-button ${selected ? 'selected' : ''}" data-attendance-type-id="${id}" aria-pressed="${selected ? 'true' : 'false'}" ${open ? 'disabled' : ''}><strong>${escapeHtml(type.name)}</strong>${Number(type.is_default) ? '<small>Predeterminado</small>' : ''}</button>`;
    }).join('');
    if (open?.attendance_type_name) {
      hint.textContent = `Jornada en curso: ${open.attendance_type_name}.`;
    } else if (selectedAttendanceTypeId) {
      const selected = types.find(type => Number(type.id) === Number(selectedAttendanceTypeId));
      hint.textContent = `Seleccionado: ${selected?.name || ''}.`;
    } else {
      hint.textContent = 'Selecciona el trabajo que vas a iniciar.';
    }
  }

  function renderCompanyLocation() {
    const sign = document.querySelector('#companyLocationSign');
    const location = state.company_location || {enabled: false};
    sign.hidden = !location.enabled;
    if (!location.enabled) return;

    const label = document.querySelector('#companyLocationLabel');
    const detail = document.querySelector('#companyLocationDetail');
    const icon = document.querySelector('#companyLocationIcon');
    sign.classList.remove('is-company', 'is-away', 'is-unknown');
    if (location.at_company === true) {
      sign.classList.add('is-company');
      icon.textContent = '✓';
      label.textContent = 'Fichaje desde la empresa';
      detail.textContent = `Conexión verificada mediante IPv${Number(location.ip_version) || ''}.`;
    } else if (location.at_company === false) {
      sign.classList.add('is-away');
      icon.textContent = '↗';
      label.textContent = 'Fichaje fuera de la empresa';
      detail.textContent = `La conexión IPv${Number(location.ip_version) || ''} no coincide con la ubicación configurada.`;
    } else {
      sign.classList.add('is-unknown');
      icon.textContent = '?';
      label.textContent = 'Ubicación de conexión no disponible';
      detail.textContent = 'No se ha podido verificar desde dónde se realiza el fichaje.';
    }
  }

  function workerScheduleCards(rows) {
    if (!rows.length) return '<div class="empty">Todavía no tienes horarios definidos.</div>';
    const dayNames = {1:'Lunes',2:'Martes',3:'Miércoles',4:'Jueves',5:'Viernes',6:'Sábado',7:'Domingo'};
    const palette = ['#ef8f73','#df7e68','#ce6f5e','#bd604f','#ac5142','#9b4336','#8a352c','#79281f'];
    const typeColors = new Map();
    let colorIndex = 0;
    const groups = new Map();
    rows.forEach(row => {
      const key = `${row.valid_from || ''}|${row.valid_until || ''}`;
      if (!groups.has(key)) groups.set(key,{valid_from:row.valid_from,valid_until:row.valid_until,days:{}});
      const group = groups.get(key);
      const day = Number(row.weekday);
      if (!group.days[day]) group.days[day] = [];
      const typeKey = row.attendance_type_id == null ? 'general' : String(row.attendance_type_id);
      if (!typeColors.has(typeKey)) typeColors.set(typeKey, typeKey === 'general' ? '#b9aaa5' : palette[colorIndex++ % palette.length]);
      group.days[day].push({...row,color:typeColors.get(typeKey)});
    });
    const today = new Date().toISOString().slice(0,10);
    return [...groups.values()].map(group => {
      const fromLabel = group.valid_from ? formatDate(group.valid_from) : 'Sin fecha de inicio';
      const untilLabel = group.valid_until ? formatDate(group.valid_until) : 'Sin fecha final';
      const isLegacyUndated = !group.valid_from;
      const isFinished = Boolean(group.valid_until && group.valid_until < today);
      const status = isLegacyUndated
        ? '<span class="badge warning">Periodo pendiente de definir</span>'
        : (isFinished ? '<span class="badge">Finalizado</span>' : '<span class="badge ok">Vigente o futuro</span>');
      return `<article class="card worker-schedule-card"><div class="worker-schedule-period"><div><p class="eyebrow">Segmento de horario</p><h3>${escapeHtml(fromLabel)} → ${escapeHtml(untilLabel)}</h3></div>${status}</div><div class="worker-schedule-week">${Object.entries(dayNames).map(([day,name]) => `<section class="worker-schedule-day"><h4>${escapeHtml(name)}</h4>${(group.days[day] || []).length ? group.days[day].map(block => `<span class="worker-schedule-block" style="--schedule-color:${escapeHtml(block.color)}">${escapeHtml(shortTime(block.start_time))}–${escapeHtml(shortTime(block.end_time))}<small>${escapeHtml(block.attendance_type_name || 'Horario general')}</small></span>`).join('') : '<small>Sin horario</small>'}</section>`).join('')}</div></article>`;
    }).join('');
  }

  function communicationCards(rows) {
    if (!rows.length) return '<div class="empty">Todavía no hay comunicaciones internas.</div>';
    return rows.map(row => `<article class="card communication-card" style="--message-color:${escapeHtml(row.color)}"><div class="communication-type"><span>${escapeHtml(row.emoji)}</span><strong>${escapeHtml(row.type_name)}</strong><time>${escapeHtml(formatDate(row.start_date))}</time></div><p>${escapeHtml(row.message).replace(/\n/g,'<br>')}</p>${Number(row.highlighted) ? '<span class="badge ok">Destacado</span>' : ''}</article>`).join('');
  }

  let toastLoginShown = false;
  function showInternalToasts(rows) {
    if (toastLoginShown) return;
    toastLoginShown = true;
    const messages = rows.filter(row => Number(row.highlighted) === 1);
    if (!messages.length) return;
    const area = document.querySelector('#internalToastArea');
    const seconds = Math.max(1, Number(state.internal_toast_seconds) || 8);
    const createToast = row => {
      const toast = document.createElement('article');
      toast.className = 'internal-toast'; toast.style.setProperty('--message-color', row.color);
      toast.innerHTML = `<button type="button" aria-label="Cerrar">×</button><div><strong>${escapeHtml(row.emoji)} ${escapeHtml(row.type_name)}</strong><p>${escapeHtml(row.message).replace(/\n/g,'<br>')}</p></div>`;
      toast.querySelector('button').addEventListener('click',()=>toast.remove());
      area.appendChild(toast); setTimeout(()=>toast.remove(),seconds*1000);
    };
    if (state.internal_toast_mode === 'sequence') messages.forEach((row,index)=>setTimeout(()=>createToast(row),index*seconds*1000));
    else messages.forEach(createToast);
  }

  function render() {
    tenantTimezone = state.tenant.timezone;
    const open = state.open_shift;
    renderAttendanceView();
    renderCompanyLocation();
    renderAttendanceTypes();
    document.querySelector('#shiftStatus').textContent = open ? 'Jornada en curso' : 'Fuera de jornada';
    document.querySelector('#shiftDetail').textContent = open
      ? (`Entrada registrada a las ${shortTime(open.start_time)} · ${formatDate(open.work_date)}${open.attendance_type_name ? ` · ${open.attendance_type_name}` : ''}${open.pending_correction_id ? ' · Hay una corrección pendiente, pero puedes finalizar la jornada con normalidad.' : ''}`)
      : 'Puedes iniciar una nueva jornada cuando comiences a trabajar.';
    document.querySelector('#startShift').disabled = Boolean(open) || (Boolean(state.requires_attendance_type) && !selectedAttendanceTypeId);
    document.querySelector('#endShift').disabled = !open;
    document.querySelector('#recentAttendance').innerHTML = attendanceTable(state.attendance.slice(0, 8));
    document.querySelector('#allAttendance').innerHTML = attendanceTable(state.attendance);
    document.querySelector('#absenceList').innerHTML = absenceTable(state.absences);
    document.querySelector('#sickLeaveList').innerHTML = sickLeaveTable(state.sick_leaves || []);
    const adminAbsenceProposals = state.absences.filter(row => row.source === 'admin' && row.employee_confirmation_status === 'pending');
    document.querySelector('#adminAbsenceProposalSection').hidden = adminAbsenceProposals.length === 0;
    document.querySelector('#adminAbsenceProposalList').innerHTML = adminAbsenceProposalCards(adminAbsenceProposals);
    document.querySelector('#callPeriodList').innerHTML = callPeriodTable(state.call_periods || []);
    document.querySelector('#callPeriodTab').hidden = !state.fixed_discontinuous;
    document.querySelector('#correctionList').innerHTML = correctionCards(state.corrections);
    document.querySelector('#workerCorrectionList').innerHTML = workerCorrectionCards(state.worker_corrections || []);
    renderWorkerCorrectionEditor();
    document.querySelector('#workerAuditLog').innerHTML = auditTable(state.audit_log);
    const pendingCorrections = state.corrections.filter(item => item.status === 'pending').length;
    document.querySelector('#correctionTabLabel').textContent = pendingCorrections ? `Correcciones (${pendingCorrections})` : 'Correcciones';
    const pendingWorkerRequests = (state.worker_corrections || []).filter(item => item.status === 'pending').length;
    document.querySelector('#workerRequestTabLabel').textContent = pendingWorkerRequests ? `Solicitudes (${pendingWorkerRequests})` : 'Solicitar cambios';
    document.querySelector('#frontIntegrity').innerHTML = state.integrity.valid
      ? `<span class="integrity-front ok">✓ Cadena íntegra · ${Number(state.integrity.checked_events)} eventos</span>`
      : '<span class="integrity-front danger">! Incidencia de integridad</span>';
    document.querySelector('#conciliationSwitch').checked = Boolean(state.conciliacion);
    document.querySelector('#workerScheduleList').innerHTML = workerScheduleCards(state.schedules || []);
    document.querySelector('#internalCommunicationList').innerHTML = communicationCards(state.internal_messages || []);
    showInternalToasts(state.internal_messages || []);
    document.querySelector('#weekHours').textContent = state.weekly.length ? `${minutes(state.weekly[0].worked_minutes)} / ${minutes(state.weekly[0].required_minutes)}` : '0h 00m';
    syncMobileNavigation();
  }

  async function load() {
    state = await request('bootstrap');
    render();
    if (attendanceView === 'calendar') await loadAttendanceMonth();
  }

  function updateClock() {
    const now = new Date();
    const zone = tenantTimezone ? {timeZone: tenantTimezone} : {};
    document.querySelector('#clock').textContent = new Intl.DateTimeFormat('es-ES', {hour:'2-digit',minute:'2-digit',second:'2-digit',hour12:false,...zone}).format(now);
    document.querySelector('#clockDate').textContent = new Intl.DateTimeFormat('es-ES', {dateStyle:'full',...zone}).format(now);
  }

  const mobileNavigation = document.querySelector('.front-nav');
  const mobileNavigationToggle = document.querySelector('.front-nav-toggle');
  const mobileNavigationLabel = document.querySelector('.front-nav-toggle-label');
  const mobileNavigationMedia = window.matchMedia('(max-width: 720px)');

  function setMobileNavigation(open) {
    const expanded = Boolean(open) && mobileNavigationMedia.matches;
    mobileNavigation.dataset.mobileOpen = String(expanded);
    mobileNavigationToggle.setAttribute('aria-expanded', String(expanded));
    mobileNavigationToggle.querySelector('i').textContent = expanded ? '×' : '☰';
  }

  function syncMobileNavigation(button = document.querySelector('.front-nav-options [data-tab].active')) {
    mobileNavigationLabel.textContent = button?.textContent.trim() || 'Inicio';
  }

  mobileNavigationToggle.addEventListener('click', event => {
    event.stopPropagation();
    setMobileNavigation(mobileNavigation.dataset.mobileOpen !== 'true');
  });
  mobileNavigation.addEventListener('click', event => event.stopPropagation());
  document.addEventListener('click', () => setMobileNavigation(false));
  document.addEventListener('keydown', event => {
    if (event.key === 'Escape') {
      setMobileNavigation(false);
      mobileNavigationToggle.focus();
    }
  });
  if (typeof mobileNavigationMedia.addEventListener === 'function') {
    mobileNavigationMedia.addEventListener('change', () => setMobileNavigation(false));
  }

  document.querySelectorAll('[data-tab]').forEach(button => button.addEventListener('click', () => {
    document.querySelectorAll('[data-tab]').forEach(item => item.classList.toggle('active', item === button));
    document.querySelectorAll('[data-panel]').forEach(panel => panel.hidden = panel.dataset.panel !== button.dataset.tab);
    syncMobileNavigation(button);
    setMobileNavigation(false);
    if (mobileNavigationMedia.matches) window.scrollTo({top: 0, behavior: 'smooth'});
  }));

  document.querySelector('#attendanceListButton').addEventListener('click', () => {
    attendanceView = 'list';
    localStorage.setItem('ja-attendance-view', attendanceView);
    renderAttendanceView();
  });
  document.querySelector('#attendanceCalendarButton').addEventListener('click', async () => {
    attendanceView = 'calendar';
    localStorage.setItem('ja-attendance-view', attendanceView);
    renderAttendanceView();
    await loadAttendanceMonth();
  });
  document.querySelector('#attendancePreviousMonth').addEventListener('click', async () => {
    attendanceCalendarMonth = monthOffset(attendanceCalendarMonth, -1);
    await loadAttendanceMonth();
  });
  document.querySelector('#attendanceNextMonth').addEventListener('click', async () => {
    attendanceCalendarMonth = monthOffset(attendanceCalendarMonth, 1);
    await loadAttendanceMonth();
  });
  document.querySelector('#attendanceCurrentMonth').addEventListener('click', async () => {
    const now = new Date();
    attendanceCalendarMonth = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`;
    await loadAttendanceMonth();
  });

  document.querySelector('#startShift').addEventListener('click', async () => {
    try { const data = await request('start', {method:'POST', body:JSON.stringify({attendance_type_id: selectedAttendanceTypeId})}); jaToast(data.message); await load(); } catch (error) { jaToast(error.message, 'danger'); }
  });
  document.querySelector('#endShift').addEventListener('click', async () => {
    try { const data = await request('end', {method:'POST', body:'{}'}); jaToast(data.message); await load(); } catch (error) { jaToast(error.message, 'danger'); }
  });
  document.querySelector('#absenceForm').addEventListener('submit', async event => {
    event.preventDefault();
    const form = event.currentTarget;
    try {
      const data = await request('absence', {method:'POST', body:new FormData(form)});
      jaToast(data.message);
      form.reset();
      await load();
    } catch (error) {
      jaToast(error.message, 'danger');
    }
  });
  document.querySelector('#adminAbsenceProposalList').addEventListener('click', async event => {
    const button = event.target.closest('[data-admin-absence-decision]');
    if (!button) return;
    const card = button.closest('[data-admin-absence-id]');
    const password = card.querySelector('[data-admin-absence-password]')?.value || '';
    if (!password) {
      jaToast('Escribe tu contraseña actual para confirmar la decisión.', 'danger');
      card.querySelector('[data-admin-absence-password]')?.focus();
      return;
    }
    const decision = button.dataset.adminAbsenceDecision;
    const prompt = decision === 'accepted' ? '¿Aceptas esta ausencia propuesta por administración?' : '¿Rechazas esta ausencia propuesta por administración?';
    if (!window.confirm(prompt)) return;
    card.querySelectorAll('button').forEach(item => { item.disabled = true; });
    try {
      const data = await request('admin_absence_decision', {method:'POST', body:JSON.stringify({
        absence_id: Number(card.dataset.adminAbsenceId),
        decision,
        comment: card.querySelector('[data-admin-absence-comment]')?.value || '',
        password
      })});
      jaToast(data.message);
      await load();
    } catch (error) {
      card.querySelectorAll('button').forEach(item => { item.disabled = false; });
      jaToast(error.message, 'danger');
    }
  });
  document.querySelector('#correctionList').addEventListener('click', async event => {
    const button = event.target.closest('[data-correction-decision]');
    if (!button) return;
    const card = button.closest('[data-correction-id]');
    const decision = button.dataset.correctionDecision;
    const correctionId = Number(card.dataset.correctionId);
    const comment = card.querySelector('[data-correction-comment]')?.value || '';
    const password = card.querySelector('[data-correction-password]')?.value || '';
    if (!password) {
      jaToast('Escribe tu contraseña actual para confirmar la decisión.', 'danger');
      card.querySelector('[data-correction-password]')?.focus();
      return;
    }
    const prompt = decision === 'accepted'
      ? '¿Aceptas esta corrección? La jornada se actualizará con los valores propuestos.'
      : '¿Rechazas esta corrección? La jornada actual permanecerá sin cambios.';
    if (!window.confirm(prompt)) return;
    card.querySelectorAll('button').forEach(item => { item.disabled = true; });
    try {
      const data = await request('correction_decision', {method:'POST', body:JSON.stringify({correction_id: correctionId, decision, comment, password})});
      jaToast(data.message);
      await load();
    } catch (error) {
      jaToast(error.message, 'danger');
      card.querySelectorAll('button').forEach(item => { item.disabled = false; });
    }
  });

  document.querySelector('#workerCorrectionAttendance').addEventListener('change', populateWorkerCorrectionForm);
  document.querySelector('#workerCorrectionForm').addEventListener('submit', async event => {
    event.preventDefault();
    const form = event.currentTarget;
    const payload = Object.fromEntries(new FormData(form).entries());
    if (!window.confirm('¿Enviar esta propuesta a administración? La jornada actual no cambiará todavía.')) return;
    form.querySelectorAll('button,input,select,textarea').forEach(item => { item.disabled = true; });
    try {
      const data = await request('worker_correction_request', {method:'POST', body:JSON.stringify(payload)});
      jaToast(data.message);
      form.reset();
      await load();
    } catch (error) {
      jaToast(error.message, 'danger');
    } finally {
      form.querySelectorAll('button,input,select,textarea').forEach(item => { item.disabled = false; });
    }
  });

  document.querySelector('#attendanceTypeButtons').addEventListener('click', event => {
    const button = event.target.closest('[data-attendance-type-id]');
    if (!button || state?.open_shift) return;
    selectedAttendanceTypeId = Number(button.dataset.attendanceTypeId);
    renderAttendanceTypes();
    document.querySelector('#startShift').disabled = false;
  });

  document.querySelector('#conciliationSwitch').addEventListener('change', async event => {
    const enabled = event.currentTarget.checked;
    try { const data=await request('set_conciliacion',{method:'POST',body:JSON.stringify({enabled})}); state.conciliacion=data.conciliacion; jaToast(data.message); }
    catch(error){ event.currentTarget.checked=!enabled; jaToast(error.message,'danger'); }
  });

  updateClock();
  setInterval(updateClock, 1000);
  load().catch(error => jaToast(error.message, 'danger'));
})();

```

### front/index.php

```php
<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

use JA\Auth;
use JA\Csrf;
use JA\Session;
use JA\TenantContext;
use JA\View;

Session::start('front');
$tenant = TenantContext::current();

if (isset($_GET['logout'])) {
    Session::destroy();
    ja_redirect('index.php');
}

$error = '';
if (empty($_SESSION['employee_user'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        try {
            Csrf::check($_POST['csrf'] ?? null);
            if (Auth::loginEmployee(ja_post('username'), (string)($_POST['password'] ?? ''))) {
                ja_redirect('index.php');
            }
            $error = 'Usuario o contraseña incorrectos para esta organización.';
        } catch (Throwable $exception) {
            $error = $exception->getMessage();
        }
    }
    View::login('Acceso de personal', (string)$tenant['name'], $error, Csrf::token());
    exit;
}

try {
    $user = Auth::requireEmployee();
} catch (Throwable) {
    Session::destroy();
    ja_redirect('index.php');
}

$csrf = Csrf::token();
View::head('Mi jornada · ' . $tenant['name'], 'front');
?>
<div class="front-shell">
  <header class="topbar">
    <div class="brand"><div class="brand-mark"><img src="https://static.jocarsa.com/logos/darksalmon.svg" alt="Jocarsa"></div><div><h1>jocarsa | asistencias</h1><p><?= ja_h($tenant['name']) ?></p></div></div>
    <div class="topbar-spacer"></div><div class="topbar-user"><strong><?= ja_h($user['name']) ?></strong><small><?= ja_h($user['username']) ?></small></div><a class="button" href="?logout=1">Salir</a>
  </header>
  <main class="front-main" data-csrf="<?= ja_h($csrf) ?>">
    <section class="tab-panel" data-panel="home">
      <div class="front-hero">
        <article class="card clock-card"><p class="eyebrow">Hora local</p><div class="clock" id="clock">--:--:--</div><p id="clockDate">Cargando fecha…</p><div class="company-location-sign" id="companyLocationSign" hidden aria-live="polite"><span id="companyLocationIcon" aria-hidden="true">⌖</span><div><strong id="companyLocationLabel">Comprobando ubicación…</strong><small id="companyLocationDetail"></small></div></div><div class="attendance-type-picker" id="attendanceTypePicker" hidden><p class="attendance-type-title">Tipo de fichaje</p><div class="attendance-type-buttons" id="attendanceTypeButtons"></div><small id="attendanceTypeHint">Selecciona el trabajo que vas a iniciar.</small></div><label class="conciliation-switch"><span><strong>Conciliación</strong><small>Indica que estás usando medidas de conciliación.</small></span><input id="conciliationSwitch" type="checkbox"><i></i></label><div class="shift-actions"><button class="button primary" id="startShift" type="button">Iniciar jornada</button><button class="button" id="endShift" type="button">Finalizar jornada</button></div></article>
        <article class="card"><p class="eyebrow">Estado</p><h2 id="shiftStatus">Cargando…</h2><p class="muted" id="shiftDetail"></p><div class="metrics" style="grid-template-columns:1fr;margin-top:18px"><div class="metric"><span>Esta semana · trabajado / previsto</span><strong id="weekHours">—</strong></div></div></article>
      </div>
      <article class="card"><h3>Últimas jornadas</h3><div id="recentAttendance" class="table-wrap"></div></article>
    </section>

    <section class="tab-panel" data-panel="attendance" hidden>
      <div class="page-head"><div><p class="eyebrow">Histórico</p><h2>Mis jornadas</h2><p>Entradas, salidas y tiempo trabajado.</p></div><div class="attendance-view-switch" role="group" aria-label="Vista de jornadas"><button class="button small active" id="attendanceListButton" type="button" aria-pressed="true">Lista</button><button class="button small" id="attendanceCalendarButton" type="button" aria-pressed="false">Calendario mensual</button></div></div>
      <div id="attendanceListView"><div id="allAttendance" class="table-wrap"></div></div>
      <div id="attendanceCalendarView" hidden>
        <article class="card worker-month-calendar-card">
          <div class="worker-month-calendar-toolbar"><button class="button small" id="attendancePreviousMonth" type="button">← Mes anterior</button><div><p class="eyebrow">Calendario mensual</p><h3 id="attendanceCalendarLabel">Cargando…</h3></div><button class="button small" id="attendanceCurrentMonth" type="button">Mes actual</button><button class="button small" id="attendanceNextMonth" type="button">Mes siguiente →</button></div>
          <div class="worker-month-calendar-legend" aria-label="Leyenda del calendario"><span class="is-attendance"><i></i>Fichajes</span><span class="is-vacation"><i></i>Vacaciones</span><span class="is-holiday"><i></i>Festivos</span><span class="is-absence"><i></i>Ausencias</span></div>
          <div id="attendanceMonthCalendar" class="worker-month-calendar" aria-live="polite"></div>
        </article>
      </div>
    </section>

    <section class="tab-panel" data-panel="requests" hidden>
      <div class="page-head"><div><p class="eyebrow">Trabajador → empresa</p><h2>Solicitar una corrección</h2><p>Propón un cambio sobre una jornada cerrada. El registro actual se conservará hasta que administración lo apruebe.</p></div></div>
      <div class="notice warning" id="workerCorrectionFeatureNotice" hidden>La empresa no tiene habilitadas las solicitudes de edición del trabajador.</div>
      <article class="card attendance-editor" id="workerCorrectionEditor">
        <h3>Nueva solicitud</h3>
        <form id="workerCorrectionForm" class="grid-2">
          <label class="form-span-full">Jornada<select name="attendance_id" id="workerCorrectionAttendance" required></select><small>Solo aparecen jornadas cerradas sin otra corrección pendiente.</small></label>
          <label>Fecha<input type="date" name="work_date" required></label>
          <label>Tipo de fichaje<select name="attendance_type_id" id="workerCorrectionType"><option value="">Sin tipo</option></select></label>
          <label>Entrada<input type="time" step="1" name="start_time" required></label>
          <label>Salida<input type="time" step="1" name="end_time" required></label>
          <label class="form-span-full">Motivo<textarea name="reason" maxlength="1000" required placeholder="Explica qué dato debe corregirse y por qué."></textarea></label>
          <label class="form-span-full">Contraseña actual<input type="password" name="password" autocomplete="current-password" required><small>Confirma que la solicitud ha sido realizada por ti. La contraseña no se registra.</small></label>
          <div class="actions form-span-full"><button class="button primary" type="submit">Enviar a administración</button></div>
        </form>
        <div class="empty" id="workerCorrectionEmpty" hidden>No hay jornadas cerradas disponibles para solicitar cambios.</div>
      </article>
      <div class="page-head compact"><div><p class="eyebrow">Seguimiento</p><h2>Mis solicitudes</h2><p>Consulta qué cambios están pendientes, aprobados o rechazados.</p></div></div>
      <div id="workerCorrectionList" class="correction-list"></div>
    </section>

    <section class="tab-panel" data-panel="corrections" hidden>
      <div class="page-head"><div><p class="eyebrow">Acuerdo bilateral</p><h2>Correcciones de jornada</h2><p>La empresa puede proponer un cambio, pero tu jornada no se modificará hasta que lo aceptes.</p></div><div id="frontIntegrity"></div></div>
      <div id="correctionList" class="correction-list"></div>
      <article class="card"><h3>Registro de cambios</h3><p class="muted">Cada evento incluye una huella HMAC-SHA-256 encadenada con el anterior.</p><div id="workerAuditLog" class="table-wrap"></div></article>
    </section>

    <section class="tab-panel" data-panel="absences" hidden>
      <div class="page-head"><div><p class="eyebrow">Solicitudes</p><h2>Mis ausencias</h2><p>Solicita una ausencia, adjunta un justificante y consulta su estado.</p></div></div>
      <div id="adminAbsenceProposalSection" hidden>
        <div class="page-head compact"><div><p class="eyebrow">Empresa → trabajador</p><h2>Ausencias pendientes de tu confirmación</h2><p>Acepta o rechaza las ausencias propuestas por administración.</p></div></div>
        <div id="adminAbsenceProposalList" class="correction-list"></div>
      </div>
      <article class="card">
        <h3>Nueva solicitud</h3>
        <form id="absenceForm" class="grid-2" enctype="multipart/form-data">
          <label>Fecha de inicio<input type="date" name="start_date" required></label>
          <label>Hora de inicio (opcional)<input type="time" name="start_time" step="60"><small>Si no indicas horas, se entiende que la ausencia es de día completo.</small></label>
          <label>Fecha final (opcional)<input type="date" name="end_date"><small>Déjala vacía para una baja cuya finalización todavía se desconoce.</small></label>
          <label>Hora final (opcional)<input type="time" name="end_time" step="60"></label>
          <label>Tipo<select name="absence_type" required><option>Enfermedad</option><option>Asuntos propios</option><option>Permiso</option><option>Otro</option></select></label>
          <label>Adjunto PDF o JPG<input type="file" name="attachment" accept="application/pdf,image/jpeg,.pdf,.jpg,.jpeg"><small>Opcional · máximo 10 MB.</small></label>
          <label class="form-span-full">Motivo<textarea name="reason" maxlength="2000"></textarea></label>
          <div class="actions form-span-full"><button class="button primary" type="submit">Enviar solicitud</button></div>
        </form>
      </article>
      <article class="card"><h3>Histórico de ausencias</h3><div id="absenceList" class="table-wrap"></div></article>
    </section>

    <section class="tab-panel" data-panel="sick-leaves" hidden>
      <div class="page-head"><div><p class="eyebrow">Información de empresa</p><h2>Mis bajas</h2><p>Consulta las bajas registradas por administración. No requieren confirmación por tu parte.</p></div></div>
      <article class="card"><div id="sickLeaveList" class="table-wrap"></div></article>
    </section>

    <section class="tab-panel" data-panel="call-periods" hidden><div class="page-head"><div><p class="eyebrow">Contrato fijo-discontinuo</p><h2>Mis llamamientos</h2><p>Periodos de incorporación que la empresa ya te ha notificado.</p></div></div><article class="card"><div id="callPeriodList" class="table-wrap"></div></article></section>

    <section class="tab-panel" data-panel="schedules" hidden><div class="page-head"><div><p class="eyebrow">Planificación personal</p><h2>Mis horarios</h2><p>Consulta tus segmentos de horario, su vigencia y el tipo de fichaje previsto en cada franja.</p></div></div><div id="workerScheduleList" class="worker-schedule-segments"></div></section>

    <section class="tab-panel" data-panel="communications" hidden><div class="page-head"><div><p class="eyebrow">Información de empresa</p><h2>Comunicación interna laboral</h2><p>Histórico de mensajes emitidos por la organización.</p></div></div><div id="internalCommunicationList" class="communication-list"></div></section>
    <div id="internalToastArea" class="internal-toast-area" aria-live="polite"></div>
  </main>
  <nav class="front-nav" aria-label="Navegación" data-mobile-open="false">
    <button class="front-nav-toggle" type="button" aria-expanded="false" aria-controls="frontNavOptions">
      <span><small>Sección actual</small><strong class="front-nav-toggle-label">Inicio</strong></span>
      <span class="front-nav-toggle-action" aria-hidden="true">Menú <i>☰</i></span>
    </button>
    <div class="front-nav-options" id="frontNavOptions">
      <button class="active" data-tab="home">Inicio</button>
      <button data-tab="attendance">Jornadas</button>
      <button data-tab="requests" id="workerRequestTabLabel">Solicitar cambios</button>
      <button data-tab="corrections" id="correctionTabLabel">Confirmar cambios</button>
      <button data-tab="absences">Ausencias</button>
      <button data-tab="sick-leaves">Bajas</button>
      <button data-tab="call-periods" id="callPeriodTab">Llamamientos</button>
      <button data-tab="schedules">Horarios</button>
      <button data-tab="communications">Comunicación</button>
    </div>
  </nav>
</div>
<script src="../assets/app.js?v=2.2.0"></script><script src="app.js?v=2.2.0"></script>
<script src="https://ayuda.jocarsa.com/widget.js" data-jocarsa-ayuda="6b3065c2d4b23244e68443a409f38472f6092ce5c1bf70c2" defer></script></body></html>

```

### src/AbsenceAttachment.php

```php
<?php
declare(strict_types=1);

namespace JA;

use RuntimeException;

final class AbsenceAttachment
{
    private const MAX_BYTES = 10485760;
    private const TYPES = [
        'application/pdf' => 'pdf',
        'image/jpeg' => 'jpg',
    ];

    /** @return array{path:string,name:string}|null */
    public static function store(?array $upload, int $tenantId): ?array
    {
        if (!$upload || (int)($upload['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if ((int)($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('No se pudo completar la subida del adjunto.');
        }
        $temporary = (string)($upload['tmp_name'] ?? '');
        $size = (int)($upload['size'] ?? 0);
        if ($temporary === '' || !is_uploaded_file($temporary) || $size < 1 || $size > self::MAX_BYTES) {
            throw new RuntimeException('El adjunto debe ser un PDF o JPG de hasta 10 MB.');
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = (string)$finfo->file($temporary);
        $extension = self::TYPES[$mime] ?? null;
        if ($extension === null) {
            throw new RuntimeException('El adjunto debe estar en formato PDF, JPG o JPEG.');
        }

        $directory = JA_ROOT . '/database/absence-attachments/' . $tenantId;
        if (!is_dir($directory) && !mkdir($directory, 0770, true) && !is_dir($directory)) {
            throw new RuntimeException('No se pudo preparar el almacenamiento protegido del adjunto.');
        }
        $filename = bin2hex(random_bytes(24)) . '.' . $extension;
        $destination = $directory . '/' . $filename;
        if (!move_uploaded_file($temporary, $destination)) {
            throw new RuntimeException('No se pudo guardar el adjunto.');
        }
        @chmod($destination, 0660);

        $original = basename(trim((string)($upload['name'] ?? 'adjunto.' . $extension)));
        $original = preg_replace('/[^\pL\pN._ -]+/u', '_', $original) ?: 'adjunto.' . $extension;
        $original = function_exists('mb_substr') ? mb_substr($original, 0, 180, 'UTF-8') : substr($original, 0, 180);
        return ['path' => 'absence-attachments/' . $tenantId . '/' . $filename, 'name' => $original];
    }

    public static function download(string $relativePath, string $downloadName, int $tenantId): never
    {
        $prefix = 'absence-attachments/' . $tenantId . '/';
        if (!str_starts_with($relativePath, $prefix) || str_contains($relativePath, '..')) {
            throw new RuntimeException('El adjunto solicitado no es válido.', 404);
        }
        $path = JA_ROOT . '/database/' . $relativePath;
        if (!is_file($path) || !is_readable($path)) {
            throw new RuntimeException('El adjunto ya no está disponible.', 404);
        }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($path) ?: 'application/octet-stream';
        $safeName = str_replace(["\r", "\n", '"'], ['', '', "'"], basename($downloadName ?: 'adjunto'));
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . (string)filesize($path));
        header("Content-Disposition: attachment; filename*=UTF-8''" . rawurlencode($safeName));
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, no-store, max-age=0');
        readfile($path);
        exit;
    }
}
```

### src/AbsenceService.php

```php
<?php
declare(strict_types=1);

namespace JA;

use PDO;
use RuntimeException;

final class AbsenceService
{
    /**
     * @return array{start_date:string,start_time:?string,end_date:?string,end_time:?string}
     */
    public static function normalizeRange(
        string $startDate,
        ?string $startTime,
        ?string $endDate,
        ?string $endTime
    ): array {
        $startDate = trim($startDate);
        $startTime = self::normalizeTime($startTime);
        $endDate = trim((string)$endDate);
        $endDate = $endDate === '' ? null : $endDate;
        $endTime = self::normalizeTime($endTime);

        if (!self::validDate($startDate)) {
            throw new RuntimeException('La fecha de inicio de la ausencia no es válida.', 422);
        }
        if ($endDate !== null && !self::validDate($endDate)) {
            throw new RuntimeException('La fecha final de la ausencia no es válida.', 422);
        }
        if ($endDate !== null && $endDate < $startDate) {
            throw new RuntimeException('La fecha final no puede ser anterior a la fecha de inicio.', 422);
        }
        if ($endDate === null && $endTime !== null) {
            throw new RuntimeException('No se puede indicar una hora final sin fecha final.', 422);
        }
        if ($endDate === $startDate && $startTime !== null && $endTime !== null && $endTime <= $startTime) {
            throw new RuntimeException('En una ausencia del mismo día, la hora final debe ser posterior a la inicial.', 422);
        }

        return [
            'start_date' => $startDate,
            'start_time' => $startTime,
            'end_date' => $endDate,
            'end_time' => $endTime,
        ];
    }

    public static function createByWorker(
        PDO $pdo,
        int $tenantId,
        int $employeeId,
        array $range,
        string $type,
        string $reason,
        ?array $attachment = null
    ): int {
        $type = trim($type);
        if ($type === '') {
            throw new RuntimeException('Selecciona el tipo de ausencia.', 422);
        }

        $statement = $pdo->prepare(
            "INSERT INTO absences
               (tenant_id,employee_id,start_date,start_time,end_date,end_time,absence_type,reason,
                attachment,attachment_name,status,requested_at,approved,source,employee_confirmation_status)
             VALUES
               (:tenant,:employee,:start_date,:start_time,:end_date,:end_time,:type,:reason,
                :attachment,:attachment_name,'pending',:requested_at,0,'worker','not_required')"
        );
        $statement->execute([
            'tenant' => $tenantId,
            'employee' => $employeeId,
            'start_date' => $range['start_date'],
            'start_time' => $range['start_time'],
            'end_date' => $range['end_date'],
            'end_time' => $range['end_time'],
            'type' => $type,
            'reason' => trim($reason) ?: null,
            'attachment' => $attachment['path'] ?? null,
            'attachment_name' => $attachment['name'] ?? null,
            'requested_at' => date('Y-m-d H:i:s'),
        ]);
        return (int)$pdo->lastInsertId();
    }

    public static function createByAdmin(
        PDO $pdo,
        int $tenantId,
        int $employeeId,
        int $adminId,
        array $range,
        string $type,
        string $reason,
        ?array $attachment = null
    ): int {
        $type = trim($type);
        if ($type === '') {
            throw new RuntimeException('Selecciona el tipo de ausencia.');
        }
        $employee = $pdo->prepare('SELECT id FROM employees WHERE id=:employee AND tenant_id=:tenant AND active=1');
        $employee->execute(['employee' => $employeeId, 'tenant' => $tenantId]);
        if (!$employee->fetchColumn()) {
            throw new RuntimeException('La persona seleccionada no es válida para esta organización.');
        }

        $statement = $pdo->prepare(
            "INSERT INTO absences
               (tenant_id,employee_id,start_date,start_time,end_date,end_time,absence_type,reason,
                attachment,attachment_name,status,requested_at,approved,source,created_by_admin_id,
                employee_confirmation_status)
             VALUES
               (:tenant,:employee,:start_date,:start_time,:end_date,:end_time,:type,:reason,
                :attachment,:attachment_name,'pending',:requested_at,0,'admin',:admin,'pending')"
        );
        $statement->execute([
            'tenant' => $tenantId,
            'employee' => $employeeId,
            'start_date' => $range['start_date'],
            'start_time' => $range['start_time'],
            'end_date' => $range['end_date'],
            'end_time' => $range['end_time'],
            'type' => $type,
            'reason' => trim($reason) ?: null,
            'attachment' => $attachment['path'] ?? null,
            'attachment_name' => $attachment['name'] ?? null,
            'requested_at' => date('Y-m-d H:i:s'),
            'admin' => $adminId,
        ]);
        return (int)$pdo->lastInsertId();
    }

    public static function decideAdminProposal(
        PDO $pdo,
        int $tenantId,
        int $employeeId,
        int $userId,
        int $absenceId,
        string $decision,
        string $comment
    ): void {
        if (!in_array($decision, ['accepted', 'rejected'], true)) {
            throw new RuntimeException('La decisión sobre la ausencia no es válida.', 422);
        }

        $pdo->exec('BEGIN IMMEDIATE TRANSACTION');
        try {
            $row = $pdo->prepare(
                "SELECT id FROM absences
                 WHERE id=:id AND tenant_id=:tenant AND employee_id=:employee
                   AND source='admin' AND employee_confirmation_status='pending' LIMIT 1"
            );
            $row->execute(['id' => $absenceId, 'tenant' => $tenantId, 'employee' => $employeeId]);
            if (!$row->fetchColumn()) {
                throw new RuntimeException('La propuesta de ausencia ya no está pendiente.', 409);
            }

            $accepted = $decision === 'accepted';
            $update = $pdo->prepare(
                'UPDATE absences SET employee_confirmation_status=:confirmation,status=:status,approved=:approved,
                   responded_by_user_id=:user,employee_comment=:comment,responded_at=:responded_at
                 WHERE id=:id AND tenant_id=:tenant AND employee_id=:employee
                   AND employee_confirmation_status=\'pending\''
            );
            $update->execute([
                'confirmation' => $decision,
                'status' => $accepted ? 'approved' : 'rejected',
                'approved' => $accepted ? 1 : 0,
                'user' => $userId,
                'comment' => trim($comment) ?: null,
                'responded_at' => date('Y-m-d H:i:s'),
                'id' => $absenceId,
                'tenant' => $tenantId,
                'employee' => $employeeId,
            ]);
            $pdo->exec('COMMIT');
        } catch (\Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->exec('ROLLBACK');
            }
            throw $exception;
        }
    }

    private static function validDate(string $value): bool
    {
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        return $date !== false && $date->format('Y-m-d') === $value;
    }

    private static function normalizeTime(?string $value): ?string
    {
        $value = trim((string)$value);
        if ($value === '') {
            return null;
        }
        if (!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d(?::[0-5]\d)?$/', $value)) {
            throw new RuntimeException('La hora indicada no es válida.', 422);
        }
        return substr($value, 0, 5);
    }
}

```

### src/ApplicationAudit.php

```php
<?php
declare(strict_types=1);

namespace JA;

use Throwable;

/**
 * Global append-only request audit. It deliberately records metadata, not
 * request bodies, so passwords, comments and uploaded contents never enter
 * the activity log.
 */
final class ApplicationAudit
{
    private static bool $registered = false;
    private static float $startedAt = 0.0;
    private static string $requestId = '';
    /** @var array{type:string,id:?int,label:string,tenant_id:?int}|null */
    private static ?array $preservedActor = null;

    public static function registerRequest(): void
    {
        if (self::$registered) {
            return;
        }
        self::$registered = true;
        self::$startedAt = microtime(true);
        self::$requestId = bin2hex(random_bytes(16));
        register_shutdown_function([self::class, 'writeRequest']);
    }

    public static function preserveActor(): void
    {
        self::$preservedActor = self::actorFromSession();
    }

    public static function writeRequest(): void
    {
        try {
            $pdo = Database::pdo();
            if ($pdo->inTransaction()) {
                // A request ending with an open transaction cannot safely keep
                // partial business changes. Roll it back before auditing the failure.
                $pdo->rollBack();
            }
            $actor = self::$preservedActor ?? self::actorFromSession();
            $tenantId = $actor['tenant_id'] ?? self::tenantFromHost($pdo);
            $status = http_response_code();
            $status = is_int($status) && $status >= 100 ? $status : 200;
            $method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
            $area = self::area();
            $action = trim((string)($_POST['action'] ?? $_GET['action'] ?? ''));
            if ($action === '' && $method === 'POST' && str_ends_with((string)($_SERVER['SCRIPT_NAME'] ?? ''), '/index.php')) {
                $action = 'login_or_form_submit';
            }
            $lastError = error_get_last();
            $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR];
            $fatal = is_array($lastError) && in_array((int)($lastError['type'] ?? 0), $fatalTypes, true) ? $lastError : null;
            $flashType = is_array($_SESSION['flash'] ?? null) ? (string)($_SESSION['flash']['type'] ?? '') : '';
            $outcome = self::outcome($status, $method, $action, $actor['type'], $flashType, $fatal);
            $details = [
                'host' => (string)($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? ''),
                'referer' => self::limit((string)($_SERVER['HTTP_REFERER'] ?? ''), 1000),
                'fatal_error_type' => is_array($fatal) ? (int)($fatal['type'] ?? 0) : null,
            ];
            $insert = $pdo->prepare(
                'INSERT INTO application_audit_log (
                   request_id,tenant_id,occurred_at,area,actor_type,actor_id,actor_label,
                   method,request_uri,action_name,response_status,outcome,ip_address,user_agent,
                   duration_ms,peak_memory_bytes,details_json
                 ) VALUES (
                   :request_id,:tenant,:occurred_at,:area,:actor_type,:actor_id,:actor_label,
                   :method,:request_uri,:action_name,:status,:outcome,:ip,:user_agent,
                   :duration,:memory,:details
                 )'
            );
            $insert->execute([
                'request_id' => self::$requestId,
                'tenant' => $tenantId,
                'occurred_at' => date('Y-m-d\TH:i:s.uP'),
                'area' => $area,
                'actor_type' => $actor['type'],
                'actor_id' => $actor['id'],
                'actor_label' => self::limit($actor['label'], 255),
                'method' => self::limit($method, 12),
                'request_uri' => self::limit((string)($_SERVER['REQUEST_URI'] ?? $_SERVER['SCRIPT_NAME'] ?? ''), 2000),
                'action_name' => $action !== '' ? self::limit($action, 160) : null,
                'status' => $status,
                'outcome' => $outcome,
                'ip' => self::limit((string)($_SERVER['REMOTE_ADDR'] ?? ''), 64) ?: null,
                'user_agent' => self::limit((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 1000) ?: null,
                'duration' => max(0, (int)round((microtime(true) - self::$startedAt) * 1000)),
                'memory' => memory_get_peak_usage(true),
                'details' => json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}',
            ]);
        } catch (Throwable $exception) {
            error_log('[jocarsa-asistencias-audit] ' . $exception->getMessage());
        }
    }

    /** @return array{type:string,id:?int,label:string,tenant_id:?int} */
    private static function actorFromSession(): array
    {
        if (is_array($_SESSION['admin'] ?? null)) {
            $actor = $_SESSION['admin'];
            return ['type' => 'admin', 'id' => (int)($actor['id'] ?? 0) ?: null, 'label' => (string)($actor['name'] ?? $actor['username'] ?? 'Administración'), 'tenant_id' => (int)($actor['tenant_id'] ?? 0) ?: null];
        }
        if (is_array($_SESSION['employee_user'] ?? null)) {
            $actor = $_SESSION['employee_user'];
            return ['type' => 'worker', 'id' => (int)($actor['id'] ?? 0) ?: null, 'label' => (string)($actor['name'] ?? $actor['username'] ?? 'Trabajador'), 'tenant_id' => (int)($actor['tenant_id'] ?? 0) ?: null];
        }
        if (is_array($_SESSION['superadmin'] ?? null)) {
            $actor = $_SESSION['superadmin'];
            return ['type' => 'superadmin', 'id' => (int)($actor['id'] ?? 0) ?: null, 'label' => (string)($actor['name'] ?? $actor['username'] ?? 'Superadministración'), 'tenant_id' => null];
        }
        return ['type' => 'anonymous', 'id' => null, 'label' => 'Anónimo', 'tenant_id' => null];
    }

    private static function tenantFromHost(\PDO $pdo): ?int
    {
        try {
            $host = TenantContext::requestHost();
            $statement = $pdo->prepare('SELECT tenant_id FROM tenant_domains WHERE domain=:domain LIMIT 1');
            $statement->execute(['domain' => $host]);
            $id = (int)$statement->fetchColumn();
            return $id > 0 ? $id : null;
        } catch (Throwable) {
            return null;
        }
    }

    private static function area(): string
    {
        $script = strtolower((string)($_SERVER['SCRIPT_NAME'] ?? ''));
        foreach (['superadmin', 'admin', 'front', 'tools', 'database'] as $area) {
            if (str_contains($script, '/' . $area . '/')) {
                return $area;
            }
        }
        return 'public';
    }

    private static function outcome(int $status, string $method, string $action, string $actorType, string $flashType, ?array $fatal): string
    {
        if ($fatal !== null || $status >= 400 || $flashType === 'danger') {
            return 'failure';
        }
        if ($flashType === 'ok' || ($method === 'POST' && $action === 'login_or_form_submit' && $actorType !== 'anonymous')) {
            return 'success';
        }
        if ($status >= 300) {
            return 'redirect';
        }
        if ($method === 'POST' && $action === 'login_or_form_submit' && $actorType === 'anonymous') {
            return 'failure';
        }
        return $status >= 200 && $status < 300 ? 'success' : 'unknown';
    }

    private static function limit(string $value, int $length): string
    {
        return function_exists('mb_substr') ? mb_substr($value, 0, $length) : substr($value, 0, $length);
    }
}


```

### src/AttendanceAudit.php

```php
<?php
declare(strict_types=1);

namespace JA;

use DateTimeImmutable;
use PDO;
use RuntimeException;
use Throwable;

/**
 * Append-only, HMAC-authenticated audit trail for attendance records.
 *
 * The HMAC key lives outside SQLite. The log is chained per tenant and the
 * authenticated head makes removing the final events detectable as well.
 */
final class AttendanceAudit
{
    private const GENESIS_HASH = '0000000000000000000000000000000000000000000000000000000000000000';
    private const FORMAT_VERSION = 'ja-attendance-audit-v1';
    private static ?string $key = null;
    private static int $savepointCounter = 0;

    public static function bootstrapTenant(PDO $pdo, int $tenantId): int
    {
        self::key();
        $savepoint = self::beginSavepoint($pdo);
        try {
            $statement = $pdo->prepare(
                'SELECT a.* FROM attendance a
                 LEFT JOIN attendance_audit_log l
                   ON l.tenant_id=a.tenant_id AND l.attendance_id=a.id
                 WHERE a.tenant_id=:tenant AND l.id IS NULL
                 ORDER BY a.id'
            );
            $statement->execute(['tenant' => $tenantId]);
            $rows = $statement->fetchAll();
            foreach ($rows as $row) {
                self::append(
                    $pdo,
                    $tenantId,
                    (int)$row['id'],
                    null,
                    'baseline',
                    'system',
                    null,
                    'Anclaje automático V3',
                    ['state_after' => self::state($row), 'source' => 'preexisting_record']
                );
            }
            self::finishSavepoint($pdo, $savepoint, true);
            return count($rows);
        } catch (Throwable $exception) {
            self::finishSavepoint($pdo, $savepoint, false);
            throw $exception;
        }
    }

    public static function createManualAttendance(
        PDO $pdo,
        int $tenantId,
        int $employeeId,
        string $workDate,
        ?string $startTime,
        ?string $endTime,
        ?int $attendanceTypeId,
        array $admin,
        string $reason
    ): int {
        $workDate = self::date($workDate);
        $startTime = self::time($startTime, false);
        $endTime = self::time($endTime, true);
        $reason = trim($reason);
        if ($reason === '') {
            throw new RuntimeException('Indica el motivo del registro manual.');
        }

        $savepoint = self::beginSavepoint($pdo);
        try {
            self::requireEmployee($pdo, $tenantId, $employeeId);
            $attendanceType = AttendanceTypeCatalog::requireSelection($pdo, $tenantId, $employeeId, $attendanceTypeId);
            $attendanceTypeId = $attendanceType !== null ? (int)$attendanceType['id'] : null;
            if ($endTime === null) {
                $open = $pdo->prepare(
                    'SELECT id FROM attendance
                     WHERE tenant_id=:tenant AND employee_id=:employee AND end_time IS NULL LIMIT 1'
                );
                $open->execute(['tenant' => $tenantId, 'employee' => $employeeId]);
                if ($open->fetchColumn()) {
                    throw new RuntimeException('La persona ya tiene una jornada abierta.');
                }
            }
            $insert = $pdo->prepare(
                'INSERT INTO attendance (tenant_id,employee_id,attendance_type_id,work_date,start_time,end_time,created_at,updated_at)
                 VALUES (:tenant,:employee,:attendance_type,:date,:start,:end,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP)'
            );
            $insert->execute([
                'tenant' => $tenantId,
                'employee' => $employeeId,
                'attendance_type' => $attendanceTypeId,
                'date' => $workDate,
                'start' => $startTime,
                'end' => $endTime,
            ]);
            $attendanceId = (int)$pdo->lastInsertId();
            $row = self::attendance($pdo, $tenantId, $attendanceId);
            self::append(
                $pdo,
                $tenantId,
                $attendanceId,
                null,
                'admin_created',
                'admin',
                (int)($admin['id'] ?? 0),
                (string)($admin['name'] ?? $admin['username'] ?? 'Administración'),
                ['state_after' => self::state($row), 'reason' => $reason]
            );
            self::finishSavepoint($pdo, $savepoint, true);
            return $attendanceId;
        } catch (Throwable $exception) {
            self::finishSavepoint($pdo, $savepoint, false);
            throw $exception;
        }
    }

    public static function proposeCorrection(
        PDO $pdo,
        int $tenantId,
        int $attendanceId,
        string $workDate,
        ?string $startTime,
        ?string $endTime,
        ?int $attendanceTypeId,
        string $reason,
        array $admin
    ): int {
        $workDate = self::date($workDate);
        $startTime = self::time($startTime, false);
        $endTime = self::time($endTime, true);
        $reason = trim($reason);
        if ($reason === '') {
            throw new RuntimeException('El motivo de la corrección es obligatorio.');
        }

        $savepoint = self::beginSavepoint($pdo);
        try {
            $verification = self::verifyTenant($pdo, $tenantId);
            if (!$verification['valid']) {
                throw new RuntimeException('La cadena de integridad no es válida. No se puede proponer la corrección.');
            }
            $row = self::attendance($pdo, $tenantId, $attendanceId);
            $employeeId = (int)$row['employee_id'];
            $access = $pdo->prepare(
                'SELECT id FROM users
                 WHERE tenant_id=:tenant AND employee_id=:employee AND active=1
                 ORDER BY id LIMIT 1'
            );
            $access->execute(['tenant' => $tenantId, 'employee' => $employeeId]);
            if (!$access->fetchColumn()) {
                throw new RuntimeException('Esta persona no tiene un usuario activo en el front y no podría aceptar la corrección.');
            }

            $pending = $pdo->prepare(
                "SELECT id FROM attendance_corrections
                 WHERE tenant_id=:tenant AND attendance_id=:attendance AND status='pending'
                 UNION ALL
                 SELECT id FROM worker_attendance_corrections
                 WHERE tenant_id=:tenant AND attendance_id=:attendance AND status='pending'
                 LIMIT 1"
            );
            $pending->execute(['tenant' => $tenantId, 'attendance' => $attendanceId]);
            if ($pending->fetchColumn()) {
                throw new RuntimeException('Esta jornada ya tiene una corrección pendiente de respuesta.');
            }

            $original = self::state($row);
            $proposed = $original;
            $currentTypeId = $row['attendance_type_id'] !== null ? (int)$row['attendance_type_id'] : null;
            if ($attendanceTypeId === $currentTypeId) {
                $proposedTypeId = $currentTypeId;
            } else {
                $attendanceType = AttendanceTypeCatalog::requireSelection($pdo, $tenantId, $employeeId, $attendanceTypeId);
                $proposedTypeId = $attendanceType !== null ? (int)$attendanceType['id'] : null;
            }
            $proposed['work_date'] = $workDate;
            $proposed['start_time'] = $startTime;
            $proposed['end_time'] = $endTime;
            if ($proposedTypeId !== null) {
                $proposed['attendance_type_id'] = $proposedTypeId;
            } else {
                unset($proposed['attendance_type_id']);
            }
            if (self::canonicalJson($original) === self::canonicalJson($proposed)) {
                throw new RuntimeException('La propuesta no contiene ningún cambio.');
            }

            $requestedAt = self::now();
            $insert = $pdo->prepare(
                "INSERT INTO attendance_corrections (
                   tenant_id,attendance_id,employee_id,requested_by_admin_id,status,
                   original_work_date,original_start_time,original_end_time,
                   original_attendance_type_id,
                   proposed_work_date,proposed_start_time,proposed_end_time,
                   proposed_attendance_type_id,
                   reason,requested_at
                 ) VALUES (
                   :tenant,:attendance,:employee,:admin,'pending',
                   :original_date,:original_start,:original_end,
                   :original_attendance_type,
                   :proposed_date,:proposed_start,:proposed_end,
                   :proposed_attendance_type,
                   :reason,:requested_at
                 )"
            );
            $insert->execute([
                'tenant' => $tenantId,
                'attendance' => $attendanceId,
                'employee' => $employeeId,
                'admin' => (int)($admin['id'] ?? 0),
                'original_date' => $original['work_date'],
                'original_start' => $original['start_time'],
                'original_end' => $original['end_time'],
                'original_attendance_type' => $original['attendance_type_id'] ?? null,
                'proposed_date' => $proposed['work_date'],
                'proposed_start' => $proposed['start_time'],
                'proposed_end' => $proposed['end_time'],
                'proposed_attendance_type' => $proposed['attendance_type_id'] ?? null,
                'reason' => $reason,
                'requested_at' => $requestedAt,
            ]);
            $correctionId = (int)$pdo->lastInsertId();
            $request = [
                'id' => $correctionId,
                'attendance_id' => $attendanceId,
                'employee_id' => $employeeId,
                'original' => $original,
                'proposed' => $proposed,
                'reason' => $reason,
                'requested_at' => $requestedAt,
            ];
            $eventHash = self::append(
                $pdo,
                $tenantId,
                $attendanceId,
                $correctionId,
                'correction_proposed',
                'admin',
                (int)($admin['id'] ?? 0),
                (string)($admin['name'] ?? $admin['username'] ?? 'Administración'),
                ['request' => $request]
            );
            $pdo->prepare(
                'UPDATE attendance_corrections SET proposal_event_hash=:hash
                 WHERE id=:id AND tenant_id=:tenant'
            )->execute(['hash' => $eventHash, 'id' => $correctionId, 'tenant' => $tenantId]);
            self::finishSavepoint($pdo, $savepoint, true);
            return $correctionId;
        } catch (Throwable $exception) {
            self::finishSavepoint($pdo, $savepoint, false);
            throw $exception;
        }
    }

    public static function proposeCorrectionByWorker(
        PDO $pdo,
        int $tenantId,
        int $attendanceId,
        int $employeeId,
        int $userId,
        string $workerName,
        string $workDate,
        ?string $startTime,
        ?string $endTime,
        ?int $attendanceTypeId,
        string $reason
    ): int {
        if (!Settings::bool($pdo, $tenantId, Settings::WORKER_ATTENDANCE_EDIT)) {
            throw new RuntimeException('La empresa no tiene habilitadas las solicitudes de edición de jornada.');
        }
        $workDate = self::date($workDate);
        $startTime = self::time($startTime, false);
        $endTime = self::time($endTime, true);
        if ($endTime === null) {
            throw new RuntimeException('La hora de salida es obligatoria en una solicitud del trabajador.');
        }
        $reason = trim($reason);
        if ($reason === '') {
            throw new RuntimeException('Explica el motivo de la solicitud.');
        }
        if (strlen($reason) > 1000) {
            throw new RuntimeException('El motivo no puede superar los 1.000 caracteres.');
        }

        $savepoint = self::beginSavepoint($pdo);
        try {
            $verification = self::verifyTenant($pdo, $tenantId);
            if (!$verification['valid']) {
                throw new RuntimeException('La cadena de integridad no es válida. La solicitud ha sido bloqueada.');
            }
            $row = self::attendance($pdo, $tenantId, $attendanceId);
            if ((int)$row['employee_id'] !== $employeeId) {
                throw new RuntimeException('Solo puedes solicitar cambios sobre tus propias jornadas.');
            }
            if ($row['end_time'] === null) {
                throw new RuntimeException('Finaliza la jornada antes de solicitar su corrección.');
            }
            $access = $pdo->prepare(
                'SELECT id FROM users
                 WHERE id=:user AND tenant_id=:tenant AND employee_id=:employee AND active=1 LIMIT 1'
            );
            $access->execute(['user' => $userId, 'tenant' => $tenantId, 'employee' => $employeeId]);
            if (!$access->fetchColumn()) {
                throw new RuntimeException('El acceso del trabajador ya no está activo.');
            }
            $pending = $pdo->prepare(
                "SELECT id FROM attendance_corrections
                 WHERE tenant_id=:tenant AND attendance_id=:attendance AND status='pending'
                 UNION ALL
                 SELECT id FROM worker_attendance_corrections
                 WHERE tenant_id=:tenant AND attendance_id=:attendance AND status='pending'
                 LIMIT 1"
            );
            $pending->execute(['tenant' => $tenantId, 'attendance' => $attendanceId]);
            if ($pending->fetchColumn()) {
                throw new RuntimeException('Esta jornada ya tiene una solicitud de corrección pendiente.');
            }

            $original = self::state($row);
            $proposed = $original;
            $currentTypeId = $row['attendance_type_id'] !== null ? (int)$row['attendance_type_id'] : null;
            if ($attendanceTypeId === $currentTypeId) {
                $proposedTypeId = $currentTypeId;
            } else {
                $attendanceType = AttendanceTypeCatalog::requireSelection($pdo, $tenantId, $employeeId, $attendanceTypeId);
                $proposedTypeId = $attendanceType !== null ? (int)$attendanceType['id'] : null;
            }
            $proposed['work_date'] = $workDate;
            $proposed['start_time'] = $startTime;
            $proposed['end_time'] = $endTime;
            if ($proposedTypeId !== null) {
                $proposed['attendance_type_id'] = $proposedTypeId;
            } else {
                unset($proposed['attendance_type_id']);
            }
            if (self::canonicalJson($original) === self::canonicalJson($proposed)) {
                throw new RuntimeException('La solicitud no contiene ningún cambio.');
            }

            $requestedAt = self::now();
            $insert = $pdo->prepare(
                "INSERT INTO worker_attendance_corrections (
                   tenant_id,attendance_id,employee_id,requested_by_user_id,status,
                   original_work_date,original_start_time,original_end_time,original_attendance_type_id,
                   proposed_work_date,proposed_start_time,proposed_end_time,proposed_attendance_type_id,
                   reason,requested_at
                 ) VALUES (
                   :tenant,:attendance,:employee,:user,'pending',
                   :original_date,:original_start,:original_end,:original_type,
                   :proposed_date,:proposed_start,:proposed_end,:proposed_type,
                   :reason,:requested_at
                 )"
            );
            $insert->execute([
                'tenant' => $tenantId,
                'attendance' => $attendanceId,
                'employee' => $employeeId,
                'user' => $userId,
                'original_date' => $original['work_date'],
                'original_start' => $original['start_time'],
                'original_end' => $original['end_time'],
                'original_type' => $original['attendance_type_id'] ?? null,
                'proposed_date' => $proposed['work_date'],
                'proposed_start' => $proposed['start_time'],
                'proposed_end' => $proposed['end_time'],
                'proposed_type' => $proposed['attendance_type_id'] ?? null,
                'reason' => $reason,
                'requested_at' => $requestedAt,
            ]);
            $requestId = (int)$pdo->lastInsertId();
            $request = [
                'id' => $requestId,
                'attendance_id' => $attendanceId,
                'employee_id' => $employeeId,
                'requested_by_user_id' => $userId,
                'original' => $original,
                'proposed' => $proposed,
                'reason' => $reason,
                'requested_at' => $requestedAt,
            ];
            $eventHash = self::append(
                $pdo,
                $tenantId,
                $attendanceId,
                null,
                'correction_proposed',
                'worker',
                $userId,
                $workerName,
                ['worker_request' => $request]
            );
            $pdo->prepare(
                'UPDATE worker_attendance_corrections SET proposal_event_hash=:hash
                 WHERE id=:id AND tenant_id=:tenant'
            )->execute(['hash' => $eventHash, 'id' => $requestId, 'tenant' => $tenantId]);
            self::finishSavepoint($pdo, $savepoint, true);
            return $requestId;
        } catch (Throwable $exception) {
            self::finishSavepoint($pdo, $savepoint, false);
            throw $exception;
        }
    }

    public static function decideWorkerCorrection(
        PDO $pdo,
        int $tenantId,
        array $admin,
        int $requestId,
        string $decision,
        string $comment
    ): void {
        if (!in_array($decision, ['accepted', 'rejected'], true)) {
            throw new RuntimeException('Decisión administrativa no válida.');
        }
        $comment = trim($comment);
        if (strlen($comment) > 1000) {
            throw new RuntimeException('El comentario no puede superar los 1.000 caracteres.');
        }

        $savepoint = self::beginSavepoint($pdo);
        try {
            $verification = self::verifyTenant($pdo, $tenantId);
            if (!$verification['valid']) {
                throw new RuntimeException('La cadena de integridad no es válida. La decisión ha sido bloqueada.');
            }
            $statement = $pdo->prepare(
                "SELECT * FROM worker_attendance_corrections
                 WHERE id=:id AND tenant_id=:tenant AND status='pending' LIMIT 1"
            );
            $statement->execute(['id' => $requestId, 'tenant' => $tenantId]);
            $request = $statement->fetch();
            if (!$request) {
                throw new RuntimeException('La solicitud ya no está pendiente o no existe.');
            }
            $attendance = self::attendance($pdo, $tenantId, (int)$request['attendance_id']);
            $current = self::state($attendance);
            $original = self::workerCorrectionOriginal($request);
            if (self::canonicalJson($current) !== self::canonicalJson($original)) {
                throw new RuntimeException('La jornada cambió después de la solicitud. La decisión se ha bloqueado.');
            }
            $proposed = self::workerCorrectionProposed($request);
            $respondedAt = self::now();

            if ($decision === 'accepted') {
                $proposedTypeId = $proposed['attendance_type_id'] ?? null;
                $currentTypeId = $original['attendance_type_id'] ?? null;
                if ($proposedTypeId !== $currentTypeId) {
                    AttendanceTypeCatalog::requireSelection(
                        $pdo,
                        $tenantId,
                        (int)$request['employee_id'],
                        $proposedTypeId !== null ? (int)$proposedTypeId : null
                    );
                }
                $update = $pdo->prepare(
                    'UPDATE attendance SET work_date=:date,start_time=:start,end_time=:end,
                     attendance_type_id=:attendance_type,updated_at=CURRENT_TIMESTAMP
                     WHERE id=:id AND tenant_id=:tenant'
                );
                $update->execute([
                    'date' => $proposed['work_date'],
                    'start' => $proposed['start_time'],
                    'end' => $proposed['end_time'],
                    'attendance_type' => $proposedTypeId,
                    'id' => (int)$request['attendance_id'],
                    'tenant' => $tenantId,
                ]);
            }

            $adminId = (int)($admin['id'] ?? 0);
            $updateRequest = $pdo->prepare(
                "UPDATE worker_attendance_corrections
                 SET status=:status,responded_at=:responded_at,responded_by_admin_id=:admin,admin_comment=:comment
                 WHERE id=:id AND tenant_id=:tenant AND status='pending'"
            );
            $updateRequest->execute([
                'status' => $decision,
                'responded_at' => $respondedAt,
                'admin' => $adminId,
                'comment' => $comment !== '' ? $comment : null,
                'id' => $requestId,
                'tenant' => $tenantId,
            ]);
            if ($updateRequest->rowCount() !== 1) {
                throw new RuntimeException('La solicitud fue respondida en otra sesión.');
            }
            $decisionPayload = [
                'status' => $decision,
                'responded_at' => $respondedAt,
                'responded_by_admin_id' => $adminId,
                'admin_comment' => $comment !== '' ? $comment : null,
                'confirmed_in_admin_session' => true,
            ];
            $payload = ['worker_decision' => ['request_id' => $requestId, 'decision' => $decisionPayload], 'state_before' => $original];
            if ($decision === 'accepted') {
                $payload['state_after'] = $proposed;
            }
            $eventHash = self::append(
                $pdo,
                $tenantId,
                (int)$request['attendance_id'],
                null,
                $decision === 'accepted' ? 'correction_accepted' : 'correction_rejected',
                'admin',
                $adminId,
                (string)($admin['name'] ?? $admin['username'] ?? 'Administración'),
                $payload
            );
            $pdo->prepare(
                'UPDATE worker_attendance_corrections SET decision_event_hash=:hash
                 WHERE id=:id AND tenant_id=:tenant'
            )->execute(['hash' => $eventHash, 'id' => $requestId, 'tenant' => $tenantId]);
            self::finishSavepoint($pdo, $savepoint, true);
        } catch (Throwable $exception) {
            self::finishSavepoint($pdo, $savepoint, false);
            throw $exception;
        }
    }

    public static function decideCorrection(
        PDO $pdo,
        int $tenantId,
        int $employeeId,
        int $userId,
        string $workerName,
        int $correctionId,
        string $decision,
        string $comment
    ): void {
        if (!in_array($decision, ['accepted', 'rejected'], true)) {
            throw new RuntimeException('Decisión no válida.');
        }
        $comment = trim($comment);
        if (strlen($comment) > 1000) {
            throw new RuntimeException('El comentario no puede superar los 1.000 caracteres.');
        }

        $savepoint = self::beginSavepoint($pdo);
        try {
            $verification = self::verifyTenant($pdo, $tenantId);
            if (!$verification['valid']) {
                throw new RuntimeException('La cadena de integridad no es válida. La decisión ha sido bloqueada.');
            }
            $statement = $pdo->prepare(
                "SELECT * FROM attendance_corrections
                 WHERE id=:id AND tenant_id=:tenant AND employee_id=:employee AND status='pending' LIMIT 1"
            );
            $statement->execute(['id' => $correctionId, 'tenant' => $tenantId, 'employee' => $employeeId]);
            $correction = $statement->fetch();
            if (!$correction) {
                throw new RuntimeException('La propuesta ya no está pendiente o no te pertenece.');
            }

            $attendance = self::attendance($pdo, $tenantId, (int)$correction['attendance_id']);
            $current = self::state($attendance);
            $original = self::correctionOriginal($correction);
            $proposed = self::correctionProposed($correction);

            // Una jornada abierta puede finalizarse mientras existe una propuesta.
            // Ese cierre no invalida la corrección: se conserva la hora real de salida
            // salvo que la propuesta ya indicase expresamente otra hora.
            if (self::canonicalJson($current) !== self::canonicalJson($original)) {
                $originalWithoutEnd = $original;
                $currentWithoutEnd = $current;
                unset($originalWithoutEnd['end_time'], $currentWithoutEnd['end_time']);
                $closedWhilePending = $original['end_time'] === null
                    && $current['end_time'] !== null
                    && self::canonicalJson($currentWithoutEnd) === self::canonicalJson($originalWithoutEnd);
                if (!$closedWhilePending) {
                    throw new RuntimeException('La jornada cambió después de la propuesta. La decisión se ha bloqueado para proteger el registro.');
                }
                $original = $current;
                if ($proposed['end_time'] === null) {
                    $proposed['end_time'] = $current['end_time'];
                }
            }
            $respondedAt = self::now();

            if ($decision === 'accepted') {
                if ($proposed['end_time'] === null) {
                    $otherOpen = $pdo->prepare(
                        'SELECT id FROM attendance
                         WHERE tenant_id=:tenant AND employee_id=:employee AND work_date=:work_date
                           AND end_time IS NULL AND id<>:id LIMIT 1'
                    );
                    $otherOpen->execute([
                        'tenant' => $tenantId,
                        'employee' => $employeeId,
                        'work_date' => $proposed['work_date'],
                        'id' => (int)$correction['attendance_id'],
                    ]);
                    if ($otherOpen->fetchColumn()) {
                        throw new RuntimeException('No se puede aceptar una segunda jornada abierta. Revisa primero la jornada en curso.');
                    }
                }
                $update = $pdo->prepare(
                    'UPDATE attendance SET work_date=:date,start_time=:start,end_time=:end,
                     attendance_type_id=:attendance_type,updated_at=CURRENT_TIMESTAMP
                     WHERE id=:id AND tenant_id=:tenant'
                );
                $update->execute([
                    'date' => $proposed['work_date'],
                    'start' => $proposed['start_time'],
                    'end' => $proposed['end_time'],
                    'attendance_type' => $proposed['attendance_type_id'] ?? null,
                    'id' => (int)$correction['attendance_id'],
                    'tenant' => $tenantId,
                ]);
            }

            $updateCorrection = $pdo->prepare(
                'UPDATE attendance_corrections
                 SET status=:status,responded_at=:responded_at,responded_by_user_id=:user,worker_comment=:comment
                 WHERE id=:id AND tenant_id=:tenant AND status=\'pending\''
            );
            $updateCorrection->execute([
                'status' => $decision,
                'responded_at' => $respondedAt,
                'user' => $userId,
                'comment' => $comment !== '' ? $comment : null,
                'id' => $correctionId,
                'tenant' => $tenantId,
            ]);
            if ($updateCorrection->rowCount() !== 1) {
                throw new RuntimeException('La propuesta fue respondida en otra sesión.');
            }

            $payload = [
                'decision' => [
                    'status' => $decision,
                    'responded_at' => $respondedAt,
                    'responded_by_user_id' => $userId,
                    'worker_comment' => $comment !== '' ? $comment : null,
                    'reauthenticated' => true,
                ],
                'state_before' => $original,
            ];
            if ($decision === 'accepted') {
                $payload['state_after'] = $proposed;
            }
            $eventHash = self::append(
                $pdo,
                $tenantId,
                (int)$correction['attendance_id'],
                $correctionId,
                $decision === 'accepted' ? 'correction_accepted' : 'correction_rejected',
                'worker',
                $userId,
                $workerName,
                $payload
            );
            $pdo->prepare(
                'UPDATE attendance_corrections SET decision_event_hash=:hash
                 WHERE id=:id AND tenant_id=:tenant'
            )->execute(['hash' => $eventHash, 'id' => $correctionId, 'tenant' => $tenantId]);
            self::finishSavepoint($pdo, $savepoint, true);
        } catch (Throwable $exception) {
            self::finishSavepoint($pdo, $savepoint, false);
            throw $exception;
        }
    }

    public static function recordStateEvent(
        PDO $pdo,
        int $tenantId,
        array $attendance,
        string $eventType,
        string $actorType,
        ?int $actorId,
        string $actorLabel,
        array $extraPayload = []
    ): string {
        $payload = ['state_after' => self::state($attendance)] + $extraPayload;
        return self::append(
            $pdo,
            $tenantId,
            (int)$attendance['id'],
            null,
            $eventType,
            $actorType,
            $actorId,
            $actorLabel,
            $payload
        );
    }

    /** @return array{valid:bool,checked_events:int,checked_records:int,last_hash:string,errors:array<int,string>} */
    public static function verifyTenant(PDO $pdo, int $tenantId): array
    {
        $errors = [];
        $logs = $pdo->prepare('SELECT * FROM attendance_audit_log WHERE tenant_id=:tenant ORDER BY sequence');
        $logs->execute(['tenant' => $tenantId]);
        $rows = $logs->fetchAll();
        $previousHash = self::GENESIS_HASH;
        $expectedSequence = 1;
        $states = [];
        $proposals = [];
        $decisions = [];
        $workerProposals = [];
        $workerDecisions = [];

        foreach ($rows as $row) {
            $sequence = (int)$row['sequence'];
            if ($sequence !== $expectedSequence) {
                $errors[] = 'Secuencia discontinua antes del evento ' . $sequence . '.';
            }
            if (!hash_equals($previousHash, (string)$row['previous_hash'])) {
                $errors[] = 'El evento ' . $sequence . ' no enlaza con el hash anterior.';
            }
            $expectedHash = self::eventHash(
                $tenantId,
                $sequence,
                (int)$row['attendance_id'],
                $row['correction_id'] !== null ? (int)$row['correction_id'] : null,
                (string)$row['event_type'],
                (string)$row['actor_type'],
                $row['actor_id'] !== null ? (int)$row['actor_id'] : null,
                (string)$row['actor_label'],
                (string)$row['event_at'],
                (string)$row['previous_hash'],
                (string)$row['payload_json']
            );
            if (!hash_equals($expectedHash, (string)$row['event_hash'])) {
                $errors[] = 'La huella del evento ' . $sequence . ' no es válida.';
            }

            $payload = json_decode((string)$row['payload_json'], true);
            if (!is_array($payload)) {
                $errors[] = 'El contenido del evento ' . $sequence . ' no es JSON válido.';
            } else {
                if (isset($payload['state_after']) && is_array($payload['state_after'])) {
                    $states[(int)$row['attendance_id']] = $payload['state_after'];
                }
                if ($row['event_type'] === 'correction_proposed' && isset($payload['worker_request']) && is_array($payload['worker_request'])) {
                    $workerProposals[(int)$payload['worker_request']['id']] = ['request' => $payload['worker_request'], 'hash' => (string)$row['event_hash']];
                } elseif ($row['event_type'] === 'correction_proposed' && isset($payload['request']) && is_array($payload['request'])) {
                    $proposals[(int)$row['correction_id']] = ['request' => $payload['request'], 'hash' => (string)$row['event_hash']];
                }
                if (in_array($row['event_type'], ['correction_accepted', 'correction_rejected'], true) && isset($payload['worker_decision']) && is_array($payload['worker_decision'])) {
                    $workerDecisions[(int)$payload['worker_decision']['request_id']] = ['decision' => $payload['worker_decision']['decision'], 'hash' => (string)$row['event_hash']];
                } elseif (in_array($row['event_type'], ['correction_accepted', 'correction_rejected'], true) && isset($payload['decision']) && is_array($payload['decision'])) {
                    $decisions[(int)$row['correction_id']] = ['decision' => $payload['decision'], 'hash' => (string)$row['event_hash']];
                }
            }
            $previousHash = (string)$row['event_hash'];
            $expectedSequence = $sequence + 1;
            if (count($errors) >= 25) {
                break;
            }
        }

        $head = $pdo->prepare('SELECT * FROM attendance_audit_heads WHERE tenant_id=:tenant');
        $head->execute(['tenant' => $tenantId]);
        $headRow = $head->fetch();
        $lastSequence = count($rows) ? (int)end($rows)['sequence'] : 0;
        $lastHash = count($rows) ? (string)end($rows)['event_hash'] : self::GENESIS_HASH;
        if (!$headRow && count($rows)) {
            $errors[] = 'Falta la cabecera autenticada de la cadena.';
        } elseif ($headRow) {
            if ((int)$headRow['last_sequence'] !== $lastSequence || !hash_equals((string)$headRow['last_hash'], $lastHash)) {
                $errors[] = 'La cabecera no coincide con el último evento; puede haberse eliminado el final de la cadena.';
            }
            $expectedHeadMac = self::headMac($tenantId, (int)$headRow['last_sequence'], (string)$headRow['last_hash']);
            if (!hash_equals($expectedHeadMac, (string)$headRow['head_mac'])) {
                $errors[] = 'La autenticación de la cabecera no es válida.';
            }
        }

        $attendance = $pdo->prepare('SELECT * FROM attendance WHERE tenant_id=:tenant ORDER BY id');
        $attendance->execute(['tenant' => $tenantId]);
        $attendanceRows = $attendance->fetchAll();
        $present = [];
        foreach ($attendanceRows as $row) {
            $id = (int)$row['id'];
            $present[$id] = true;
            if (!isset($states[$id])) {
                $errors[] = 'La jornada ' . $id . ' no tiene un estado autenticado.';
            } elseif (self::canonicalJson(self::state($row)) !== self::canonicalJson($states[$id])) {
                $errors[] = 'El estado actual de la jornada ' . $id . ' no coincide con su último evento autenticado.';
            }
            if (count($errors) >= 25) {
                break;
            }
        }
        foreach (array_keys($states) as $attendanceId) {
            if (!isset($present[$attendanceId])) {
                $errors[] = 'La jornada autenticada ' . $attendanceId . ' ha desaparecido.';
            }
        }

        $corrections = $pdo->prepare('SELECT * FROM attendance_corrections WHERE tenant_id=:tenant ORDER BY id');
        $corrections->execute(['tenant' => $tenantId]);
        foreach ($corrections->fetchAll() as $correction) {
            $id = (int)$correction['id'];
            $expectedRequest = [
                'id' => $id,
                'attendance_id' => (int)$correction['attendance_id'],
                'employee_id' => (int)$correction['employee_id'],
                'original' => self::correctionOriginal($correction),
                'proposed' => self::correctionProposed($correction),
                'reason' => (string)$correction['reason'],
                'requested_at' => (string)$correction['requested_at'],
            ];
            if (!isset($proposals[$id]) || self::canonicalJson($expectedRequest) !== self::canonicalJson($proposals[$id]['request'])) {
                $errors[] = 'La solicitud de corrección ' . $id . ' no coincide con su propuesta autenticada.';
            } elseif (!hash_equals((string)($correction['proposal_event_hash'] ?? ''), $proposals[$id]['hash'])) {
                $errors[] = 'La huella de propuesta de la corrección ' . $id . ' no coincide.';
            }
            if ($correction['status'] !== 'pending') {
                $expectedDecision = [
                    'status' => (string)$correction['status'],
                    'responded_at' => (string)$correction['responded_at'],
                    'responded_by_user_id' => (int)$correction['responded_by_user_id'],
                    'worker_comment' => $correction['worker_comment'] !== null ? (string)$correction['worker_comment'] : null,
                    'reauthenticated' => true,
                ];
                if (!isset($decisions[$id]) || self::canonicalJson($expectedDecision) !== self::canonicalJson($decisions[$id]['decision'])) {
                    $errors[] = 'La respuesta de la corrección ' . $id . ' no coincide con su evento autenticado.';
                } elseif (!hash_equals((string)($correction['decision_event_hash'] ?? ''), $decisions[$id]['hash'])) {
                    $errors[] = 'La huella de decisión de la corrección ' . $id . ' no coincide.';
                }
            }
            if (count($errors) >= 25) {
                break;
            }
        }

        $workerCorrections = $pdo->prepare('SELECT * FROM worker_attendance_corrections WHERE tenant_id=:tenant ORDER BY id');
        $workerCorrections->execute(['tenant' => $tenantId]);
        foreach ($workerCorrections->fetchAll() as $correction) {
            $id = (int)$correction['id'];
            $expectedRequest = [
                'id' => $id,
                'attendance_id' => (int)$correction['attendance_id'],
                'employee_id' => (int)$correction['employee_id'],
                'requested_by_user_id' => (int)$correction['requested_by_user_id'],
                'original' => self::workerCorrectionOriginal($correction),
                'proposed' => self::workerCorrectionProposed($correction),
                'reason' => (string)$correction['reason'],
                'requested_at' => (string)$correction['requested_at'],
            ];
            if (!isset($workerProposals[$id]) || self::canonicalJson($expectedRequest) !== self::canonicalJson($workerProposals[$id]['request'])) {
                $errors[] = 'La solicitud del trabajador ' . $id . ' no coincide con su propuesta autenticada.';
            } elseif (!hash_equals((string)($correction['proposal_event_hash'] ?? ''), $workerProposals[$id]['hash'])) {
                $errors[] = 'La huella de propuesta del trabajador ' . $id . ' no coincide.';
            }
            if ($correction['status'] !== 'pending') {
                $expectedDecision = [
                    'status' => (string)$correction['status'],
                    'responded_at' => (string)$correction['responded_at'],
                    'responded_by_admin_id' => (int)$correction['responded_by_admin_id'],
                    'admin_comment' => $correction['admin_comment'] !== null ? (string)$correction['admin_comment'] : null,
                    'confirmed_in_admin_session' => true,
                ];
                if (!isset($workerDecisions[$id]) || self::canonicalJson($expectedDecision) !== self::canonicalJson($workerDecisions[$id]['decision'])) {
                    $errors[] = 'La decisión administrativa de la solicitud ' . $id . ' no coincide con su evento autenticado.';
                } elseif (!hash_equals((string)($correction['decision_event_hash'] ?? ''), $workerDecisions[$id]['hash'])) {
                    $errors[] = 'La huella de decisión de la solicitud ' . $id . ' no coincide.';
                }
            }
            if (count($errors) >= 25) {
                break;
            }
        }

        return [
            'valid' => $errors === [],
            'checked_events' => count($rows),
            'checked_records' => count($attendanceRows),
            'last_hash' => $lastHash,
            'errors' => $errors,
        ];
    }

    private static function append(
        PDO $pdo,
        int $tenantId,
        int $attendanceId,
        ?int $correctionId,
        string $eventType,
        string $actorType,
        ?int $actorId,
        string $actorLabel,
        array $payload
    ): string {
        $headStatement = $pdo->prepare('SELECT * FROM attendance_audit_heads WHERE tenant_id=:tenant');
        $headStatement->execute(['tenant' => $tenantId]);
        $head = $headStatement->fetch();
        if (!$head) {
            $initialMac = self::headMac($tenantId, 0, self::GENESIS_HASH);
            $pdo->prepare(
                'INSERT INTO attendance_audit_heads (tenant_id,last_sequence,last_hash,head_mac,updated_at)
                 VALUES (:tenant,0,:hash,:mac,CURRENT_TIMESTAMP)'
            )->execute(['tenant' => $tenantId, 'hash' => self::GENESIS_HASH, 'mac' => $initialMac]);
            $head = ['last_sequence' => 0, 'last_hash' => self::GENESIS_HASH, 'head_mac' => $initialMac];
        }
        $expectedHeadMac = self::headMac($tenantId, (int)$head['last_sequence'], (string)$head['last_hash']);
        if (!hash_equals($expectedHeadMac, (string)$head['head_mac'])) {
            throw new RuntimeException('La cabecera de integridad ha sido alterada.');
        }

        $sequence = (int)$head['last_sequence'] + 1;
        $previousHash = (string)$head['last_hash'];
        $eventAt = self::now();
        $payloadJson = self::canonicalJson($payload);
        $eventHash = self::eventHash(
            $tenantId,
            $sequence,
            $attendanceId,
            $correctionId,
            $eventType,
            $actorType,
            $actorId,
            $actorLabel,
            $eventAt,
            $previousHash,
            $payloadJson
        );
        $insert = $pdo->prepare(
            'INSERT INTO attendance_audit_log (
               tenant_id,sequence,attendance_id,correction_id,event_type,actor_type,actor_id,
               actor_label,event_at,previous_hash,payload_json,event_hash
             ) VALUES (
               :tenant,:sequence,:attendance,:correction,:event_type,:actor_type,:actor_id,
               :actor_label,:event_at,:previous_hash,:payload,:event_hash
             )'
        );
        $insert->execute([
            'tenant' => $tenantId,
            'sequence' => $sequence,
            'attendance' => $attendanceId,
            'correction' => $correctionId,
            'event_type' => $eventType,
            'actor_type' => $actorType,
            'actor_id' => $actorId,
            'actor_label' => $actorLabel,
            'event_at' => $eventAt,
            'previous_hash' => $previousHash,
            'payload' => $payloadJson,
            'event_hash' => $eventHash,
        ]);
        $nextHeadMac = self::headMac($tenantId, $sequence, $eventHash);
        $update = $pdo->prepare(
            'UPDATE attendance_audit_heads
             SET last_sequence=:sequence,last_hash=:hash,head_mac=:mac,updated_at=CURRENT_TIMESTAMP
             WHERE tenant_id=:tenant AND last_sequence=:old_sequence AND last_hash=:old_hash AND head_mac=:old_mac'
        );
        $update->execute([
            'sequence' => $sequence,
            'hash' => $eventHash,
            'mac' => $nextHeadMac,
            'tenant' => $tenantId,
            'old_sequence' => (int)$head['last_sequence'],
            'old_hash' => $previousHash,
            'old_mac' => (string)$head['head_mac'],
        ]);
        if ($update->rowCount() !== 1) {
            throw new RuntimeException('No se pudo avanzar la cadena de integridad.');
        }
        return $eventHash;
    }

    private static function eventHash(
        int $tenantId,
        int $sequence,
        int $attendanceId,
        ?int $correctionId,
        string $eventType,
        string $actorType,
        ?int $actorId,
        string $actorLabel,
        string $eventAt,
        string $previousHash,
        string $payloadJson
    ): string {
        $message = implode("\n", [
            self::FORMAT_VERSION,
            (string)$tenantId,
            (string)$sequence,
            (string)$attendanceId,
            $correctionId === null ? '' : (string)$correctionId,
            $eventType,
            $actorType,
            $actorId === null ? '' : (string)$actorId,
            $actorLabel,
            $eventAt,
            $previousHash,
            $payloadJson,
        ]);
        return hash_hmac('sha256', $message, self::key());
    }

    private static function headMac(int $tenantId, int $sequence, string $hash): string
    {
        return hash_hmac('sha256', self::FORMAT_VERSION . "\nhead\n" . $tenantId . "\n" . $sequence . "\n" . $hash, self::key());
    }

    private static function key(): string
    {
        if (self::$key !== null) {
            return self::$key;
        }
        $configured = trim((string)Env::get('AUDIT_HMAC_KEY', ''));
        if ($configured !== '' && $configured !== 'change_this_audit_key') {
            if (strlen($configured) < 32) {
                throw new RuntimeException('AUDIT_HMAC_KEY debe contener al menos 32 caracteres.');
            }
            return self::$key = $configured;
        }

        $configuredPath = trim((string)Env::get('AUDIT_HMAC_KEY_FILE', 'database/audit-hmac.key'));
        $path = str_starts_with($configuredPath, DIRECTORY_SEPARATOR)
            ? $configuredPath
            : JA_ROOT . DIRECTORY_SEPARATOR . ltrim($configuredPath, '/\\');
        $directory = dirname($path);
        if (!is_dir($directory)) {
            throw new RuntimeException('No existe el directorio de la clave de auditoría: ' . $directory);
        }
        if (!is_file($path) && !is_writable($directory)) {
            throw new RuntimeException('No se puede crear la clave de auditoría en: ' . $directory);
        }
        if (is_file($path) && !is_readable($path)) {
            throw new RuntimeException('El proceso PHP no puede leer la clave de auditoría: ' . $path);
        }
        if (!is_file($path)) {
            $handle = @fopen($path, 'x');
            if (is_resource($handle)) {
                fwrite($handle, bin2hex(random_bytes(32)) . PHP_EOL);
                fclose($handle);
                @chmod($path, 0640);
            }
        }
        $key = is_file($path) ? trim((string)file_get_contents($path)) : '';
        if (strlen($key) < 64) {
            throw new RuntimeException('No se pudo generar una clave de auditoría válida en: ' . $path);
        }
        return self::$key = $key;
    }

    private static function attendance(PDO $pdo, int $tenantId, int $attendanceId): array
    {
        $statement = $pdo->prepare('SELECT * FROM attendance WHERE id=:id AND tenant_id=:tenant LIMIT 1');
        $statement->execute(['id' => $attendanceId, 'tenant' => $tenantId]);
        $row = $statement->fetch();
        if (!$row) {
            throw new RuntimeException('La jornada indicada no existe en esta organización.');
        }
        return $row;
    }

    private static function requireEmployee(PDO $pdo, int $tenantId, int $employeeId): void
    {
        $statement = $pdo->prepare('SELECT id FROM employees WHERE id=:id AND tenant_id=:tenant');
        $statement->execute(['id' => $employeeId, 'tenant' => $tenantId]);
        if (!$statement->fetchColumn()) {
            throw new RuntimeException('Empleado no válido para esta organización.');
        }
    }

    private static function state(array $row): array
    {
        $state = [
            'attendance_id' => (int)$row['id'],
            'employee_id' => (int)$row['employee_id'],
            'work_date' => (string)$row['work_date'],
            'start_time' => $row['start_time'] !== null ? (string)$row['start_time'] : null,
            'end_time' => $row['end_time'] !== null ? (string)$row['end_time'] : null,
        ];
        if (isset($row['attendance_type_id']) && $row['attendance_type_id'] !== null) {
            $state['attendance_type_id'] = (int)$row['attendance_type_id'];
        }
        return $state;
    }

    private static function correctionOriginal(array $row): array
    {
        $state = [
            'attendance_id' => (int)$row['attendance_id'],
            'employee_id' => (int)$row['employee_id'],
            'work_date' => (string)$row['original_work_date'],
            'start_time' => $row['original_start_time'] !== null ? (string)$row['original_start_time'] : null,
            'end_time' => $row['original_end_time'] !== null ? (string)$row['original_end_time'] : null,
        ];
        if (isset($row['original_attendance_type_id']) && $row['original_attendance_type_id'] !== null) {
            $state['attendance_type_id'] = (int)$row['original_attendance_type_id'];
        }
        return $state;
    }

    private static function correctionProposed(array $row): array
    {
        $state = [
            'attendance_id' => (int)$row['attendance_id'],
            'employee_id' => (int)$row['employee_id'],
            'work_date' => (string)$row['proposed_work_date'],
            'start_time' => $row['proposed_start_time'] !== null ? (string)$row['proposed_start_time'] : null,
            'end_time' => $row['proposed_end_time'] !== null ? (string)$row['proposed_end_time'] : null,
        ];
        if (isset($row['proposed_attendance_type_id']) && $row['proposed_attendance_type_id'] !== null) {
            $state['attendance_type_id'] = (int)$row['proposed_attendance_type_id'];
        }
        return $state;
    }

    private static function workerCorrectionOriginal(array $row): array
    {
        return self::correctionOriginal($row);
    }

    private static function workerCorrectionProposed(array $row): array
    {
        return self::correctionProposed($row);
    }

    private static function date(string $value): string
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', trim($value));
        if (!$date || $date->format('Y-m-d') !== trim($value)) {
            throw new RuntimeException('La fecha de jornada no es válida.');
        }
        return $date->format('Y-m-d');
    }

    private static function time(?string $value, bool $nullable): ?string
    {
        $value = trim((string)$value);
        if ($value === '' && $nullable) {
            return null;
        }
        foreach (['!H:i:s', '!H:i'] as $format) {
            $time = DateTimeImmutable::createFromFormat($format, $value);
            if ($time !== false) {
                $expected = $format === '!H:i:s' ? 'H:i:s' : 'H:i';
                if ($time->format($expected) === $value) {
                    return $time->format('H:i:s');
                }
            }
        }
        throw new RuntimeException($nullable ? 'La hora indicada no es válida.' : 'La hora de entrada es obligatoria y debe ser válida.');
    }

    private static function now(): string
    {
        return (new DateTimeImmutable())->format('Y-m-d\TH:i:s.uP');
    }

    private static function canonicalJson(array $value): string
    {
        $normalise = static function (mixed $item) use (&$normalise): mixed {
            if (!is_array($item)) {
                return $item;
            }
            if (!array_is_list($item)) {
                ksort($item, SORT_STRING);
            }
            foreach ($item as $key => $child) {
                $item[$key] = $normalise($child);
            }
            return $item;
        };
        $json = json_encode($normalise($value), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION);
        if (!is_string($json)) {
            throw new RuntimeException('No se pudo serializar el evento de auditoría.');
        }
        return $json;
    }

    /**
     * SQLite does not allow BEGIN inside an existing transaction. SAVEPOINT is
     * deliberately used here because it is valid both as the outermost write
     * unit and nested inside a transaction owned by a caller.
     */
    private static function beginSavepoint(PDO $pdo): string
    {
        self::$savepointCounter++;
        $name = 'ja_attendance_audit_' . self::$savepointCounter;
        $pdo->exec('SAVEPOINT ' . $name);
        return $name;
    }

    private static function finishSavepoint(PDO $pdo, string $savepoint, bool $commit): void
    {
        if ($commit) {
            $pdo->exec('RELEASE SAVEPOINT ' . $savepoint);
            return;
        }

        // Roll back only this operation and preserve the application exception.
        try {
            $pdo->exec('ROLLBACK TO SAVEPOINT ' . $savepoint);
        } catch (Throwable) {
            // SQLite may already have cancelled the savepoint after a fatal SQL error.
        }
        try {
            $pdo->exec('RELEASE SAVEPOINT ' . $savepoint);
        } catch (Throwable) {
            // Nothing remains to release.
        }
    }
}



```

### src/AttendanceTypeCatalog.php

```php
<?php
declare(strict_types=1);

namespace JA;

use PDO;
use RuntimeException;

/**
 * Catálogo de tipos de fichaje por organización y rol de empleado.
 */
final class AttendanceTypeCatalog
{
    public static function upgradeLegacyColumns(PDO $pdo): void
    {
        if (self::tableExists($pdo, 'attendance')) {
            self::addColumn($pdo, 'attendance', 'attendance_type_id', 'INTEGER');
        }
        if (self::tableExists($pdo, 'attendance_corrections')) {
            self::addColumn($pdo, 'attendance_corrections', 'original_attendance_type_id', 'INTEGER');
            self::addColumn($pdo, 'attendance_corrections', 'proposed_attendance_type_id', 'INTEGER');
        }
    }

    /**
     * Crea los valores solicitados únicamente para la organización local
     * inicial. El administrador puede decidir después cuál es el predeterminado.
     */
    public static function seedDefaultTenant(PDO $pdo, int $tenantId): void
    {
        if ($tenantId < 1) {
            throw new RuntimeException('Organización no válida para los tipos de fichaje.');
        }
        $statement = $pdo->prepare(
            'INSERT INTO attendance_types (tenant_id,role,name,is_default,active)
             VALUES (:tenant,:role,:name,0,1)
             ON CONFLICT(tenant_id,role,name) DO NOTHING'
        );
        foreach (['Docencia', 'Complementarias'] as $name) {
            $statement->execute(['tenant' => $tenantId, 'role' => 'DOCENTES', 'name' => $name]);
        }
    }

    public static function seedDetectedDefaultTenant(PDO $pdo): void
    {
        if (!self::tableExists($pdo, 'tenants') || !self::tableExists($pdo, 'attendance_types')) {
            return;
        }
        $slug = strtolower(trim((string)Env::get('LOCALHOST_TENANT_SLUG', 'local')));
        $statement = $pdo->prepare(
            "SELECT DISTINCT t.id
             FROM tenants t
             LEFT JOIN tenant_domains d ON d.tenant_id=t.id
             WHERE lower(t.slug)=:slug_filter OR lower(d.domain) IN ('localhost','127.0.0.1','::1')
             ORDER BY CASE WHEN lower(t.slug)=:slug_order THEN 0 ELSE 1 END,t.id
             LIMIT 1"
        );
        $statement->execute(['slug_filter' => $slug, 'slug_order' => $slug]);
        $tenantId = (int)$statement->fetchColumn();
        if ($tenantId > 0) {
            self::seedDefaultTenant($pdo, $tenantId);
        }
    }

    /** @return array<int,array<string,mixed>> */
    public static function availableForEmployee(PDO $pdo, int $tenantId, int $employeeId): array
    {
        $statement = $pdo->prepare(
            "SELECT ft.id,ft.name,ft.role,ft.is_default
             FROM employees e
             JOIN attendance_types ft
               ON ft.tenant_id=e.tenant_id
              AND lower(trim(ft.role, char(9) || char(10) || char(13) || ' '))
                  =lower(trim(e.role, char(9) || char(10) || char(13) || ' '))
              AND ft.active=1
             WHERE e.id=:employee AND e.tenant_id=:tenant AND e.active=1
             ORDER BY ft.is_default DESC,ft.name COLLATE NOCASE,ft.id"
        );
        $statement->execute(['employee' => $employeeId, 'tenant' => $tenantId]);
        return $statement->fetchAll();
    }

    /**
     * Valida una selección para un fichaje nuevo. Si el rol tiene tipos activos,
     * uno de ellos es obligatorio; si no tiene ninguno, el fichaje sigue siendo
     * compatible con datos heredados y se guarda sin tipo.
     *
     * @return array<string,mixed>|null
     */
    public static function requireSelection(PDO $pdo, int $tenantId, int $employeeId, ?int $typeId): ?array
    {
        $available = self::availableForEmployee($pdo, $tenantId, $employeeId);
        if ($available === []) {
            if ($typeId !== null && $typeId > 0) {
                throw new RuntimeException('El tipo de fichaje no corresponde al rol de esta persona.');
            }
            return null;
        }
        if ($typeId === null || $typeId < 1) {
            throw new RuntimeException('Selecciona un tipo de fichaje para esta jornada.');
        }
        foreach ($available as $type) {
            if ((int)$type['id'] === $typeId) {
                return $type;
            }
        }
        throw new RuntimeException('El tipo de fichaje no está activo o no corresponde al rol de esta persona.');
    }

    public static function normalizeRole(string $role): string
    {
        $role = trim((string)preg_replace('/\s+/u', ' ', $role));
        $length = function_exists('mb_strlen') ? mb_strlen($role, 'UTF-8') : strlen($role);
        if ($role === '' || $length > 100) {
            throw new RuntimeException('El rol del tipo de fichaje es obligatorio y no puede superar 100 caracteres.');
        }
        return function_exists('mb_strtoupper') ? mb_strtoupper($role, 'UTF-8') : strtoupper($role);
    }

    private static function tableExists(PDO $pdo, string $table): bool
    {
        $statement = $pdo->prepare("SELECT 1 FROM sqlite_master WHERE type='table' AND name=:name LIMIT 1");
        $statement->execute(['name' => $table]);
        return (bool)$statement->fetchColumn();
    }

    private static function addColumn(PDO $pdo, string $table, string $column, string $definition): void
    {
        foreach ($pdo->query('PRAGMA table_info("' . $table . '")')->fetchAll() as $existing) {
            if ((string)$existing['name'] === $column) {
                return;
            }
        }
        $pdo->exec('ALTER TABLE "' . $table . '" ADD COLUMN "' . $column . '" ' . $definition);
    }
}

```

### src/Auth.php

```php
<?php
declare(strict_types=1);

namespace JA;

use PDO;

final class Auth
{
    public static function verifyPassword(string $plain, string $stored): bool
    {
        $info = password_get_info($stored);
        if (($info['algo'] ?? null) !== null) {
            return password_verify($plain, $stored);
        }
        return hash_equals($stored, $plain);
    }

    public static function passwordForStorage(string $legacy): string
    {
        $info = password_get_info($legacy);
        return (($info['algo'] ?? null) !== null) ? $legacy : password_hash($legacy, PASSWORD_DEFAULT);
    }

    public static function loginTenantAdmin(string $username, string $password): bool
    {
        $pdo = Database::pdo();
        $statement = $pdo->prepare('SELECT * FROM tenant_admins WHERE tenant_id = :tenant AND username = :username AND active = 1 LIMIT 1');
        $statement->execute(['tenant' => TenantContext::id(), 'username' => $username]);
        $user = $statement->fetch();
        if (!$user || !self::verifyPassword($password, (string)$user['password_hash'])) {
            return false;
        }
        session_regenerate_id(true);
        $_SESSION['admin'] = ['id' => (int)$user['id'], 'tenant_id' => (int)$user['tenant_id'], 'name' => $user['full_name'], 'username' => $user['username']];
        return true;
    }

    public static function requireTenantAdmin(): array
    {
        $admin = $_SESSION['admin'] ?? null;
        if (!is_array($admin) || (int)($admin['tenant_id'] ?? 0) !== TenantContext::id()) {
            header('Location: index.php');
            exit;
        }
        return $admin;
    }

    public static function loginEmployee(string $username, string $password): bool
    {
        $pdo = Database::pdo();
        $statement = $pdo->prepare('SELECT u.*, e.nombre, e.apellido FROM users u LEFT JOIN employees e ON e.id = u.employee_id AND e.tenant_id = u.tenant_id WHERE u.tenant_id = :tenant AND u.username = :username AND u.active = 1 LIMIT 1');
        $statement->execute(['tenant' => TenantContext::id(), 'username' => $username]);
        $user = $statement->fetch();
        if (!$user || !self::verifyPassword($password, (string)$user['password_hash'])) {
            return false;
        }
        session_regenerate_id(true);
        $_SESSION['employee_user'] = ['id' => (int)$user['id'], 'tenant_id' => (int)$user['tenant_id'], 'employee_id' => (int)$user['employee_id'], 'name' => trim((string)$user['nombre'] . ' ' . (string)$user['apellido']), 'username' => $user['username']];
        return true;
    }

    public static function requireEmployee(): array
    {
        $user = $_SESSION['employee_user'] ?? null;
        if (!is_array($user) || (int)($user['tenant_id'] ?? 0) !== TenantContext::id() || empty($user['employee_id'])) {
            throw new \RuntimeException('Debes iniciar sesión.', 401);
        }
        return $user;
    }

    public static function loginSuperadmin(string $username, string $password): bool
    {
        $statement = Database::pdo()->prepare('SELECT * FROM superadmins WHERE username = :username AND active = 1 LIMIT 1');
        $statement->execute(['username' => $username]);
        $user = $statement->fetch();
        if (!$user || !password_verify($password, (string)$user['password_hash'])) {
            return false;
        }
        session_regenerate_id(true);
        $_SESSION['superadmin'] = ['id' => (int)$user['id'], 'name' => $user['full_name'], 'username' => $user['username']];
        return true;
    }

    public static function requireSuperadmin(): array
    {
        $user = $_SESSION['superadmin'] ?? null;
        if (!is_array($user) || empty($user['id'])) {
            header('Location: index.php');
            exit;
        }
        return $user;
    }
}



```

### src/CompanyLocation.php

```php
<?php
declare(strict_types=1);

namespace JA;

use PDO;
use RuntimeException;

final class CompanyLocation
{
    /**
     * @return array{enabled:bool,at_company:?bool,ip_version:?int}
     */
    public static function status(PDO $pdo, int $tenantId): array
    {
        $enabled = Settings::bool($pdo, $tenantId, Settings::COMPANY_LOCATION_ENABLED);
        if (!$enabled) {
            return ['enabled' => false, 'at_company' => null, 'ip_version' => null];
        }

        $clientIp = self::clientIp();
        if ($clientIp === null) {
            return ['enabled' => true, 'at_company' => null, 'ip_version' => null];
        }

        $clientPacked = inet_pton($clientIp);
        $clientVersion = filter_var($clientIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false ? 4 : 6;
        $matches = false;
        foreach ([Settings::COMPANY_IPV4, Settings::COMPANY_IPV6] as $setting) {
            $configured = trim(Settings::get($pdo, $tenantId, $setting));
            if ($configured === '') {
                continue;
            }
            $configuredPacked = inet_pton($configured);
            if ($clientPacked !== false && $configuredPacked !== false && hash_equals($configuredPacked, $clientPacked)) {
                $matches = true;
                break;
            }
        }

        return ['enabled' => true, 'at_company' => $matches, 'ip_version' => $clientVersion];
    }

    public static function normalizeConfigured(string $value, int $version): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        $flag = $version === 4 ? FILTER_FLAG_IPV4 : FILTER_FLAG_IPV6;
        if (!in_array($version, [4, 6], true) || filter_var($value, FILTER_VALIDATE_IP, $flag) === false) {
            throw new RuntimeException($version === 4
                ? 'La IPv4 de la empresa no es válida.'
                : 'La IPv6 de la empresa no es válida.');
        }

        $packed = inet_pton($value);
        $canonical = $packed === false ? false : inet_ntop($packed);
        if (!is_string($canonical)) {
            throw new RuntimeException('No se pudo normalizar la dirección IP de la empresa.');
        }
        return $canonical;
    }

    public static function clientIp(): ?string
    {
        $candidates = [];
        if (Env::bool('APP_TRUST_PROXY', false)) {
            foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_REAL_IP'] as $header) {
                if (!empty($_SERVER[$header])) {
                    $candidates[] = (string)$_SERVER[$header];
                }
            }
            if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                foreach (explode(',', (string)$_SERVER['HTTP_X_FORWARDED_FOR']) as $forwardedIp) {
                    $candidates[] = $forwardedIp;
                }
            }
        }
        $candidates[] = (string)($_SERVER['REMOTE_ADDR'] ?? '');

        foreach ($candidates as $candidate) {
            $normalized = self::normalizeClientCandidate($candidate);
            if ($normalized !== null) {
                return $normalized;
            }
        }
        return null;
    }

    private static function normalizeClientCandidate(string $candidate): ?string
    {
        $candidate = trim($candidate, " \t\n\r\0\x0B\"");
        if ($candidate === '') {
            return null;
        }
        if (preg_match('/^\[([^]]+)](?::\d+)?$/', $candidate, $match)) {
            $candidate = $match[1];
        } elseif (substr_count($candidate, ':') === 1 && preg_match('/^(.+):(\d+)$/', $candidate, $match)) {
            $candidate = $match[1];
        }
        if (filter_var($candidate, FILTER_VALIDATE_IP) === false) {
            return null;
        }

        $packed = inet_pton($candidate);
        if ($packed === false) {
            return null;
        }
        $mappedPrefix = str_repeat("\0", 10) . "\xff\xff";
        if (strlen($packed) === 16 && substr($packed, 0, 12) === $mappedPrefix) {
            $packed = substr($packed, 12);
        }
        $canonical = inet_ntop($packed);
        return is_string($canonical) ? $canonical : null;
    }
}

```

### src/ContractCatalog.php

```php
<?php
declare(strict_types=1);

namespace JA;

use PDO;
use RuntimeException;

/**
 * Catálogo estructurado de modalidades contractuales españolas.
 *
 * La jornada (completa/parcial) y la forma de prestación
 * (presencial/distancia/híbrida) se guardan en el contrato, no como tipos.
 */
final class ContractCatalog
{
    /** @return array<string,string> */
    public static function categories(): array
    {
        return [
            'indefinite' => 'Indefinido',
            'temporary' => 'Temporal o de duración determinada',
            'training' => 'Formativo',
            'special' => 'Relación o supuesto especial',
        ];
    }

    /**
     * @return array<int,array{code:string,name:string,category:string,fixed:int,description:string}>
     */
    public static function definitions(): array
    {
        return [
            ['code' => 'indefinido_ordinario', 'name' => 'Indefinido ordinario', 'category' => 'indefinite', 'fixed' => 0, 'description' => 'Sin fecha de finalización; modalidad ordinaria.'],
            ['code' => 'indefinido_fijo_discontinuo', 'name' => 'Indefinido fijo-discontinuo', 'category' => 'indefinite', 'fixed' => 1, 'description' => 'Actividad estacional, de temporada o intermitente con periodos de ejecución ciertos.'],
            ['code' => 'indefinido_adscrito_obra_construccion', 'name' => 'Indefinido adscrito a obra (construcción)', 'category' => 'indefinite', 'fixed' => 0, 'description' => 'Modalidad indefinida específica del sector de la construcción.'],
            ['code' => 'indefinido_incentivado', 'name' => 'Indefinido con incentivos', 'category' => 'indefinite', 'fixed' => 0, 'description' => 'Indefinido sujeto a los requisitos del incentivo aplicable y del colectivo contratado.'],
            ['code' => 'temporal_produccion_imprevisible', 'name' => 'Temporal por circunstancias de la producción imprevisibles', 'category' => 'temporary', 'fixed' => 0, 'description' => 'Incremento ocasional e imprevisible o desajuste temporal de empleo.'],
            ['code' => 'temporal_produccion_previsible', 'name' => 'Temporal por circunstancias de la producción previsibles', 'category' => 'temporary', 'fixed' => 0, 'description' => 'Situación ocasional, previsible y de duración reducida y delimitada.'],
            ['code' => 'temporal_sustitucion_reserva', 'name' => 'Temporal por sustitución con reserva de puesto', 'category' => 'temporary', 'fixed' => 0, 'description' => 'Sustitución de una persona con derecho a reserva de puesto.'],
            ['code' => 'temporal_sustitucion_jornada_reducida', 'name' => 'Temporal por sustitución para completar jornada reducida', 'category' => 'temporary', 'fixed' => 0, 'description' => 'Completa la jornada reducida de otra persona trabajadora.'],
            ['code' => 'temporal_sustitucion_vacante', 'name' => 'Temporal por sustitución durante selección o promoción', 'category' => 'temporary', 'fixed' => 0, 'description' => 'Cobertura temporal de una vacante durante su selección o promoción definitiva.'],
            ['code' => 'formativo_alternancia', 'name' => 'Formación en alternancia', 'category' => 'training', 'fixed' => 0, 'description' => 'Compatibiliza trabajo retribuido y un proceso formativo con plan individual.'],
            ['code' => 'formativo_practica_profesional', 'name' => 'Formativo para la obtención de práctica profesional', 'category' => 'training', 'fixed' => 0, 'description' => 'Práctica profesional adecuada a una titulación previa y con plan formativo.'],
            ['code' => 'especial_relevo', 'name' => 'Contrato de relevo', 'category' => 'special', 'fixed' => 0, 'description' => 'Vinculado a la jubilación parcial de otra persona trabajadora.'],
            ['code' => 'especial_artistas', 'name' => 'Artistas y personal técnico o auxiliar', 'category' => 'special', 'fixed' => 0, 'description' => 'Relación laboral especial del ámbito artístico, técnico o auxiliar.'],
            ['code' => 'especial_deportistas', 'name' => 'Deportistas profesionales', 'category' => 'special', 'fixed' => 0, 'description' => 'Relación laboral especial de deportistas profesionales.'],
            ['code' => 'especial_investigacion', 'name' => 'Personal investigador', 'category' => 'special', 'fixed' => 0, 'description' => 'Modalidades y contratos específicos de personal investigador.'],
            ['code' => 'especial_discapacidad_indefinido', 'name' => 'Personas con discapacidad — indefinido', 'category' => 'special', 'fixed' => 0, 'description' => 'Contratación indefinida de personas con discapacidad, con sus requisitos específicos.'],
            ['code' => 'especial_discapacidad_temporal', 'name' => 'Personas con discapacidad — temporal', 'category' => 'special', 'fixed' => 0, 'description' => 'Contratación temporal de personas con discapacidad, cuando legalmente proceda.'],
            ['code' => 'especial_hogar_familiar', 'name' => 'Servicio del hogar familiar', 'category' => 'special', 'fixed' => 0, 'description' => 'Relación laboral especial al servicio del hogar familiar.'],
            ['code' => 'especial_pescadores', 'name' => 'Pescadores', 'category' => 'special', 'fixed' => 0, 'description' => 'Modelo específico del sector pesquero compatible con las modalidades vigentes.'],
        ];
    }

    /**
     * Añade las columnas contractuales de 2.0 antes de ejecutar el esquema completo. Esto
     * permite actualizar directamente una base V3 cuyos CREATE TABLE ya existen.
     */
    public static function upgradeLegacyColumns(PDO $pdo): void
    {
        if (self::tableExists($pdo, 'contract_types')) {
            self::addColumn($pdo, 'contract_types', 'code', 'TEXT');
            self::addColumn($pdo, 'contract_types', 'category', "TEXT NOT NULL DEFAULT 'special'");
            self::addColumn($pdo, 'contract_types', 'description', 'TEXT');
            self::addColumn($pdo, 'contract_types', 'is_fixed_discontinuous', 'INTEGER NOT NULL DEFAULT 0');
            self::addColumn($pdo, 'contract_types', 'is_system', 'INTEGER NOT NULL DEFAULT 0');
            self::addColumn($pdo, 'contract_types', 'active', 'INTEGER NOT NULL DEFAULT 1');
        }

        if (self::tableExists($pdo, 'contracts')) {
            self::addColumn($pdo, 'contracts', 'contract_type_id', 'INTEGER');
            self::addColumn($pdo, 'contracts', 'workday_type', "TEXT NOT NULL DEFAULT 'full_time'");
            self::addColumn($pdo, 'contracts', 'work_arrangement', "TEXT NOT NULL DEFAULT 'on_site'");
            self::addColumn($pdo, 'contracts', 'notes', 'TEXT');
        }
    }

    public static function seedAllTenants(PDO $pdo): void
    {
        if (!self::tableExists($pdo, 'tenants') || !self::tableExists($pdo, 'contract_types')) {
            return;
        }
        foreach ($pdo->query('SELECT id FROM tenants ORDER BY id')->fetchAll(PDO::FETCH_COLUMN) as $tenantId) {
            self::seedTenant($pdo, (int)$tenantId);
        }
        self::backfillContracts($pdo);
    }

    public static function seedTenant(PDO $pdo, int $tenantId): void
    {
        if ($tenantId < 1) {
            throw new RuntimeException('Tenant no válido para el catálogo contractual.');
        }
        $statement = $pdo->prepare(
            'INSERT INTO contract_types
               (tenant_id,code,name,category,description,is_fixed_discontinuous,is_system,active)
             VALUES (:tenant,:code,:name,:category,:description,:fixed,1,1)
             ON CONFLICT(tenant_id,code) DO UPDATE SET
               name=excluded.name,
               category=excluded.category,
               description=excluded.description,
               is_fixed_discontinuous=excluded.is_fixed_discontinuous,
               is_system=1'
        );
        foreach (self::definitions() as $definition) {
            $statement->execute([
                'tenant' => $tenantId,
                'code' => $definition['code'],
                'name' => $definition['name'],
                'category' => $definition['category'],
                'description' => $definition['description'],
                'fixed' => $definition['fixed'],
            ]);
        }
    }

    public static function resolveTypeId(PDO $pdo, int $tenantId, string $name): ?int
    {
        $normalized = self::normalize($name);
        $aliases = [
            'indefinido' => 'indefinido_ordinario',
            'indefinido ordinario' => 'indefinido_ordinario',
            'fijo discontinuo' => 'indefinido_fijo_discontinuo',
            'indefinido fijo discontinuo' => 'indefinido_fijo_discontinuo',
            'practicas' => 'formativo_practica_profesional',
            'contrato en practicas' => 'formativo_practica_profesional',
            'formacion en alternancia' => 'formativo_alternancia',
        ];
        if (isset($aliases[$normalized])) {
            $byCode = $pdo->prepare('SELECT id FROM contract_types WHERE tenant_id=:tenant AND code=:code LIMIT 1');
            $byCode->execute(['tenant' => $tenantId, 'code' => $aliases[$normalized]]);
            $id = (int)$byCode->fetchColumn();
            return $id > 0 ? $id : null;
        }

        $byName = $pdo->prepare('SELECT id FROM contract_types WHERE tenant_id=:tenant AND lower(trim(name))=lower(trim(:name)) ORDER BY is_system DESC,id LIMIT 1');
        $byName->execute(['tenant' => $tenantId, 'name' => $name]);
        $id = (int)$byName->fetchColumn();
        return $id > 0 ? $id : null;
    }

    private static function backfillContracts(PDO $pdo): void
    {
        if (!self::tableExists($pdo, 'contracts')) {
            return;
        }
        $rows = $pdo->query("SELECT id,tenant_id,contract_type FROM contracts WHERE contract_type_id IS NULL AND trim(contract_type) <> ''")->fetchAll();
        $update = $pdo->prepare('UPDATE contracts SET contract_type_id=:type WHERE id=:id AND tenant_id=:tenant AND contract_type_id IS NULL');
        foreach ($rows as $row) {
            $typeId = self::resolveTypeId($pdo, (int)$row['tenant_id'], (string)$row['contract_type']);
            if ($typeId !== null) {
                $update->execute(['type' => $typeId, 'id' => (int)$row['id'], 'tenant' => (int)$row['tenant_id']]);
            }
        }
    }

    private static function tableExists(PDO $pdo, string $table): bool
    {
        $statement = $pdo->prepare("SELECT 1 FROM sqlite_master WHERE type='table' AND name=:name LIMIT 1");
        $statement->execute(['name' => $table]);
        return (bool)$statement->fetchColumn();
    }

    private static function addColumn(PDO $pdo, string $table, string $column, string $definition): void
    {
        $columns = $pdo->query('PRAGMA table_info("' . $table . '")')->fetchAll();
        foreach ($columns as $existing) {
            if ((string)$existing['name'] === $column) {
                return;
            }
        }
        $pdo->exec('ALTER TABLE "' . $table . '" ADD COLUMN "' . $column . '" ' . $definition);
    }

    private static function normalize(string $value): string
    {
        $value = strtolower(trim(strtr($value, [
            'Á' => 'a', 'É' => 'e', 'Í' => 'i', 'Ó' => 'o', 'Ú' => 'u', 'Ü' => 'u', 'Ñ' => 'n',
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n',
        ])));
        $value = preg_replace('/[^a-z0-9]+/', ' ', $value) ?? $value;
        return trim(preg_replace('/\s+/', ' ', $value) ?? $value);
    }
}

```

### src/Csrf.php

```php
<?php
declare(strict_types=1);

namespace JA;

use RuntimeException;

final class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }
        return (string)$_SESSION['_csrf'];
    }

    public static function check(?string $token): void
    {
        if (!$token || !hash_equals(self::token(), $token)) {
            throw new RuntimeException('La sesión del formulario ha caducado. Recarga la página e inténtalo de nuevo.');
        }
    }
}



```

### src/Database.php

```php
<?php
declare(strict_types=1);

namespace JA;

use PDO;
use RuntimeException;

final class Database
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        return self::connect(false);
    }

    public static function installPdo(): PDO
    {
        return self::connect(true);
    }

    public static function path(): string
    {
        $configured = trim((string)Env::get('DB_PATH', 'database/jocarsa-asistencias.sqlite'));
        if ($configured === '') {
            throw new RuntimeException('DB_PATH no puede estar vacío.');
        }
        if ($configured[0] === DIRECTORY_SEPARATOR) {
            return $configured;
        }
        return JA_ROOT . DIRECTORY_SEPARATOR . ltrim($configured, '/\\');
    }

    private static function connect(bool $allowCreate): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }
        if (!in_array('sqlite', PDO::getAvailableDrivers(), true)) {
            throw new RuntimeException('Falta la extensión pdo_sqlite de PHP. En Ubuntu: sudo apt install php-sqlite3');
        }

        $path = self::path();
        $directory = dirname($path);
        if (!is_dir($directory)) {
            if (!$allowCreate || !mkdir($directory, 0770, true)) {
                throw new RuntimeException('No se pudo crear el directorio de SQLite: ' . $directory);
            }
        }
        if (!$allowCreate && !is_file($path)) {
            throw new RuntimeException('La base SQLite no está instalada. Ejecuta: php database/install.php --password="tu-clave-segura"');
        }
        if (!is_writable($directory)) {
            throw new RuntimeException('El directorio de SQLite no tiene permisos de escritura: ' . $directory);
        }
        if (is_file($path) && !is_writable($path)) {
            throw new RuntimeException('El archivo SQLite no tiene permisos de escritura: ' . $path);
        }

        $busyMilliseconds = max(1000, (int)Env::get('DB_BUSY_TIMEOUT', 5000));
        $pdo = new PDO('sqlite:' . $path, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_STRINGIFY_FETCHES => false,
            PDO::ATTR_TIMEOUT => max(1, (int)ceil($busyMilliseconds / 1000)),
        ]);
        $pdo->exec('PRAGMA foreign_keys = ON');
        $pdo->exec('PRAGMA busy_timeout = ' . $busyMilliseconds);
        $pdo->exec('PRAGMA journal_mode = WAL');
        $pdo->exec('PRAGMA synchronous = NORMAL');
        $pdo->exec('PRAGMA temp_store = MEMORY');
        $pdo->exec('PRAGMA cache_size = -20000');

        self::migrate($pdo);

        self::$pdo = $pdo;
        return self::$pdo;
    }

    private static function migrate(PDO $pdo): void
    {
        $version = (int)$pdo->query('PRAGMA user_version')->fetchColumn();
        if ($version >= 12) {
            return;
        }

        $schema = file_get_contents(JA_ROOT . '/database/schema.sql');
        if ($schema === false) {
            throw new RuntimeException('No se pudo leer el esquema SQLite para actualizar la aplicación.');
        }

        $pdo->beginTransaction();
        try {
            ContractCatalog::upgradeLegacyColumns($pdo);
            AttendanceTypeCatalog::upgradeLegacyColumns($pdo);
            $usersTableExists = (bool)$pdo->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name='users' LIMIT 1")->fetchColumn();
            if ($usersTableExists) {
                $userColumns = array_column($pdo->query("PRAGMA table_info(users)")->fetchAll(), 'name');
                if (!in_array('conciliacion', $userColumns, true)) {
                    $pdo->exec('ALTER TABLE users ADD COLUMN conciliacion INTEGER NOT NULL DEFAULT 0 CHECK (conciliacion IN (0,1))');
                }
            }
            $schedulesTableExists = (bool)$pdo->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name='schedules' LIMIT 1")->fetchColumn();
            if ($schedulesTableExists) {
                $scheduleColumns = array_column($pdo->query("PRAGMA table_info(schedules)")->fetchAll(), 'name');
                if (!in_array('attendance_type_id', $scheduleColumns, true)) {
                    $pdo->exec('ALTER TABLE schedules ADD COLUMN attendance_type_id INTEGER');
                }
            }
            self::upgradeAbsences($pdo);
            $pdo->exec($schema);
            ContractCatalog::seedAllTenants($pdo);
            AttendanceTypeCatalog::seedDetectedDefaultTenant($pdo);
            Settings::seedAllTenants($pdo);
            $pdo->exec(
                "INSERT INTO app_meta (meta_key,meta_value,updated_at)
                 VALUES ('schema_version','2.2',CURRENT_TIMESTAMP)
                 ON CONFLICT(meta_key) DO UPDATE SET meta_value=excluded.meta_value,updated_at=CURRENT_TIMESTAMP"
            );
            $pdo->exec('PRAGMA user_version = 12');
            $pdo->commit();
        } catch (\Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
    }

    private static function upgradeAbsences(PDO $pdo): void
    {
        $exists = (bool)$pdo->query(
            "SELECT 1 FROM sqlite_master WHERE type='table' AND name='absences' LIMIT 1"
        )->fetchColumn();
        if (!$exists) {
            return;
        }

        $details = $pdo->query('PRAGMA table_info(absences)')->fetchAll();
        $columns = array_column($details, 'name');
        $endDateDefinition = null;
        foreach ($details as $detail) {
            if ((string)$detail['name'] === 'end_date') {
                $endDateDefinition = $detail;
                break;
            }
        }
        $required = [
            'start_time', 'end_time', 'attachment_name', 'source', 'created_by_admin_id',
            'employee_confirmation_status', 'responded_by_user_id', 'employee_comment', 'responded_at',
        ];
        $needsRebuild = (int)($endDateDefinition['notnull'] ?? 1) === 1;
        foreach ($required as $column) {
            $needsRebuild = $needsRebuild || !in_array($column, $columns, true);
        }
        if (!$needsRebuild) {
            return;
        }

        $pdo->exec('ALTER TABLE absences RENAME TO absences_before_v11');
        $pdo->exec(
            "CREATE TABLE absences (
              id INTEGER PRIMARY KEY AUTOINCREMENT,
              tenant_id INTEGER NOT NULL,
              legacy_id INTEGER,
              employee_id INTEGER NOT NULL,
              start_date TEXT NOT NULL,
              start_time TEXT,
              end_date TEXT,
              end_time TEXT,
              absence_type TEXT NOT NULL,
              reason TEXT,
              attachment TEXT,
              attachment_name TEXT,
              status TEXT NOT NULL DEFAULT 'pending' CHECK (status IN ('pending', 'approved', 'rejected')),
              requested_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
              approved INTEGER NOT NULL DEFAULT 0 CHECK (approved IN (0, 1)),
              source TEXT NOT NULL DEFAULT 'worker' CHECK (source IN ('worker', 'admin', 'import')),
              created_by_admin_id INTEGER,
              employee_confirmation_status TEXT NOT NULL DEFAULT 'not_required'
                CHECK (employee_confirmation_status IN ('not_required', 'pending', 'accepted', 'rejected')),
              responded_by_user_id INTEGER,
              employee_comment TEXT,
              responded_at TEXT,
              UNIQUE (id, tenant_id),
              UNIQUE (tenant_id, legacy_id),
              CHECK (end_date IS NULL OR end_date >= start_date),
              CHECK (end_date IS NOT NULL OR end_time IS NULL),
              CHECK (source <> 'admin' OR created_by_admin_id IS NOT NULL),
              FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
              FOREIGN KEY (employee_id, tenant_id) REFERENCES employees(id, tenant_id) ON DELETE CASCADE,
              FOREIGN KEY (created_by_admin_id, tenant_id) REFERENCES tenant_admins(id, tenant_id) ON DELETE RESTRICT,
              FOREIGN KEY (responded_by_user_id, tenant_id) REFERENCES users(id, tenant_id) ON DELETE RESTRICT
            )"
        );

        $has = static fn(string $column): bool => in_array($column, $columns, true);
        $pdo->exec(
            'INSERT INTO absences
               (id,tenant_id,legacy_id,employee_id,start_date,start_time,end_date,end_time,absence_type,reason,
                attachment,attachment_name,status,requested_at,approved,source,created_by_admin_id,
                employee_confirmation_status,responded_by_user_id,employee_comment,responded_at)
             SELECT id,tenant_id,legacy_id,employee_id,start_date,'
                . ($has('start_time') ? 'start_time' : 'NULL') . ',end_date,'
                . ($has('end_time') ? 'end_time' : 'NULL') . ',absence_type,reason,attachment,'
                . ($has('attachment_name') ? 'attachment_name' : 'NULL') . ',status,requested_at,approved,'
                . ($has('source') ? "CASE WHEN source IN ('worker','admin','import') THEN source ELSE 'worker' END" : "'worker'") . ','
                . ($has('created_by_admin_id') ? 'created_by_admin_id' : 'NULL') . ','
                . ($has('employee_confirmation_status') ? 'employee_confirmation_status' : "'not_required'") . ','
                . ($has('responded_by_user_id') ? 'responded_by_user_id' : 'NULL') . ','
                . ($has('employee_comment') ? 'employee_comment' : 'NULL') . ','
                . ($has('responded_at') ? 'responded_at' : 'NULL')
                . ' FROM absences_before_v11'
        );
        $pdo->exec('DROP TABLE absences_before_v11');
    }

    public static function setTimezone(string $timezone): void
    {
        // SQLite stores application dates as ISO text. PHP owns the tenant timezone.
        new \DateTimeZone($timezone);
    }
}

```

### src/EmailNotifier.php

```php
<?php
declare(strict_types=1);

namespace JA;

use PDO;
use RuntimeException;

final class EmailNotifier
{
    private const SMTP_HOST = 'smtp.ionos.es';
    private const SMTP_PORT = 587;
    private const SMTP_USER = 'robot@jocarsa.com';
    private const SMTP_PASSWORD = 'RobotJOCARSA123$';
    private const SMTP_TIMEOUT = 12;

    /**
     * @param array<string,mixed> $tenant
     * @param array<string,mixed> $employee
     * @param array<string,mixed> $absence
     * @return array{attempted:bool,sent:bool}
     */
    public static function notifyAbsenceRequest(
        PDO $pdo,
        int $tenantId,
        array $tenant,
        array $employee,
        array $absence
    ): array {
        if (!Settings::bool($pdo, $tenantId, Settings::EMAIL_NOTIFICATIONS_ENABLED)
            || !Settings::bool($pdo, $tenantId, Settings::NOTIFY_ABSENCE_REQUEST)) {
            return ['attempted' => false, 'sent' => false];
        }

        $recipient = trim(Settings::get($pdo, $tenantId, Settings::NOTIFICATION_EMAIL));
        if (!filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            error_log('[jocarsa-asistencias] Notificación omitida: destinatario no válido.');
            return ['attempted' => true, 'sent' => false];
        }

        $employeeName = trim((string)($employee['nombre'] ?? '') . ' ' . (string)($employee['apellido'] ?? ''));
        if ($employeeName === '') {
            $employeeName = 'Persona trabajadora';
        }
        $tenantName = trim((string)($tenant['name'] ?? '')) ?: 'Empresa';
        $reason = trim((string)($absence['reason'] ?? '')) ?: 'Sin motivo adicional';
        $start = (string)$absence['start_date'] . (!empty($absence['start_time']) ? ' a las ' . substr((string)$absence['start_time'], 0, 5) : ' · día completo');
        $end = !empty($absence['end_date'])
            ? (string)$absence['end_date'] . (!empty($absence['end_time']) ? ' a las ' . substr((string)$absence['end_time'], 0, 5) : ' · día completo')
            : 'Sin fecha final';
        $period = $start . ' — ' . $end;
        $subject = 'Nueva solicitud de ausencia · ' . $employeeName;

        $plain = implode("\n", [
            'Se ha registrado una nueva solicitud de ausencia.',
            '',
            'Empresa: ' . $tenantName,
            'Persona: ' . $employeeName,
            'Tipo: ' . (string)$absence['absence_type'],
            'Periodo: ' . $period,
            'Motivo: ' . $reason,
            'Adjunto: ' . (!empty($absence['attachment_name']) ? (string)$absence['attachment_name'] : 'Sin adjunto'),
            'Solicitada: ' . (string)$absence['requested_at'],
            '',
            'La solicitud está pendiente de revisión en el panel de administración.',
        ]);

        $escape = static fn(string $value): string => htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $html = '<!doctype html><html lang="es"><meta charset="utf-8"><body style="margin:0;background:#f6efec;font-family:Arial,sans-serif;color:#3b2421">'
            . '<div style="max-width:640px;margin:24px auto;background:#fff;border-left:10px solid darksalmon;padding:28px">'
            . '<p style="margin:0 0 7px;color:#9a554b;font-size:12px;font-weight:bold;text-transform:uppercase;letter-spacing:.08em">jocarsa | asistencias</p>'
            . '<h1 style="margin:0 0 8px;font-size:25px">Nueva solicitud de ausencia</h1>'
            . '<p style="margin:0 0 22px;color:#755b56">La solicitud ha quedado registrada y está pendiente de revisión.</p>'
            . '<table role="presentation" style="width:100%;border-collapse:collapse">'
            . self::htmlRow('Empresa', $escape($tenantName))
            . self::htmlRow('Persona', $escape($employeeName))
            . self::htmlRow('Tipo', $escape((string)$absence['absence_type']))
            . self::htmlRow('Periodo', $escape($period))
            . self::htmlRow('Motivo', nl2br($escape($reason)))
            . self::htmlRow('Adjunto', $escape(!empty($absence['attachment_name']) ? (string)$absence['attachment_name'] : 'Sin adjunto'))
            . self::htmlRow('Solicitada', $escape((string)$absence['requested_at']))
            . '</table></div></body></html>';

        try {
            self::send($recipient, $subject, $plain, $html);
            return ['attempted' => true, 'sent' => true];
        } catch (\Throwable $exception) {
            error_log('[jocarsa-asistencias] No se pudo enviar la notificación SMTP: ' . $exception->getMessage());
            return ['attempted' => true, 'sent' => false];
        }
    }

    /**
     * @param array<string,mixed> $tenant
     * @param array<string,mixed> $employee
     * @param array<string,mixed> $event
     * @return array{attempted:bool,sent:bool}
     */
    public static function notifyOutOfScheduleClocking(
        PDO $pdo,
        int $tenantId,
        array $tenant,
        array $employee,
        array $event
    ): array {
        if (!Settings::bool($pdo, $tenantId, Settings::EMAIL_NOTIFICATIONS_ENABLED)
            || !Settings::bool($pdo, $tenantId, Settings::NOTIFY_OUT_OF_SCHEDULE_CLOCKING)) {
            return ['attempted' => false, 'sent' => false];
        }
        $recipient = trim(Settings::get($pdo, $tenantId, Settings::NOTIFICATION_EMAIL));
        if (!filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            error_log('[jocarsa-asistencias] Aviso de fichaje omitido: destinatario no válido.');
            return ['attempted' => true, 'sent' => false];
        }

        $employeeName = trim((string)($employee['nombre'] ?? '') . ' ' . (string)($employee['apellido'] ?? ''));
        $employeeName = $employeeName !== '' ? $employeeName : 'Persona trabajadora';
        $tenantName = trim((string)($tenant['name'] ?? '')) ?: 'Empresa';
        $eventLabel = ($event['event_type'] ?? '') === 'clock_out' ? 'Fin de jornada' : 'Inicio de jornada';
        $dateTime = (string)($event['date'] ?? '') . ' ' . (string)($event['time'] ?? '');
        $schedule = trim((string)($event['schedule_label'] ?? '')) ?: 'Sin horario aplicable';
        $ip = trim((string)($event['ip_address'] ?? '')) ?: 'No disponible';
        $subject = 'Aviso: fichaje fuera de horario · ' . $employeeName;

        $plain = implode("\n", [
            'Se ha registrado un fichaje fuera del horario establecido.', '',
            'Empresa: ' . $tenantName,
            'Persona: ' . $employeeName,
            'Evento: ' . $eventLabel,
            'Fecha y hora: ' . $dateTime,
            'Horario previsto: ' . $schedule,
            'IP: ' . $ip,
            '',
            'El fichaje se ha conservado; este mensaje es únicamente un aviso a la persona designada.',
        ]);
        $escape = static fn(string $value): string => htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $html = '<!doctype html><html lang="es"><meta charset="utf-8"><body style="margin:0;background:#f6efec;font-family:Arial,sans-serif;color:#3b2421">'
            . '<div style="max-width:640px;margin:24px auto;background:#fff;border-left:10px solid darksalmon;padding:28px">'
            . '<p style="margin:0 0 7px;color:#9a554b;font-size:12px;font-weight:bold;text-transform:uppercase;letter-spacing:.08em">jocarsa | asistencias</p>'
            . '<h1 style="margin:0 0 8px;font-size:25px">Fichaje fuera del horario establecido</h1>'
            . '<p style="margin:0 0 22px;color:#755b56">El registro se ha guardado normalmente y se emite este aviso informativo.</p>'
            . '<table role="presentation" style="width:100%;border-collapse:collapse">'
            . self::htmlRow('Empresa', $escape($tenantName))
            . self::htmlRow('Persona', $escape($employeeName))
            . self::htmlRow('Evento', $escape($eventLabel))
            . self::htmlRow('Fecha y hora', $escape($dateTime))
            . self::htmlRow('Horario previsto', $escape($schedule))
            . self::htmlRow('IP', $escape($ip))
            . '</table></div></body></html>';

        try {
            self::send($recipient, $subject, $plain, $html);
            return ['attempted' => true, 'sent' => true];
        } catch (\Throwable $exception) {
            error_log('[jocarsa-asistencias] No se pudo enviar el aviso de fichaje: ' . $exception->getMessage());
            return ['attempted' => true, 'sent' => false];
        }
    }

    private static function htmlRow(string $label, string $value): string
    {
        return '<tr><th style="width:120px;padding:10px;text-align:left;vertical-align:top;background:#fff7f3;border-bottom:1px solid #ead8d1">'
            . htmlspecialchars($label, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
            . '</th><td style="padding:10px;vertical-align:top;border-bottom:1px solid #ead8d1">' . $value . '</td></tr>';
    }

    private static function send(string $recipient, string $subject, string $plain, string $html): void
    {
        $errorNumber = 0;
        $errorMessage = '';
        $socket = @stream_socket_client(
            'tcp://' . self::SMTP_HOST . ':' . self::SMTP_PORT,
            $errorNumber,
            $errorMessage,
            self::SMTP_TIMEOUT,
            STREAM_CLIENT_CONNECT
        );
        if (!is_resource($socket)) {
            throw new RuntimeException('No se pudo conectar con el servidor SMTP.');
        }

        stream_set_timeout($socket, self::SMTP_TIMEOUT);
        try {
            self::expect($socket, [220]);
            $hostname = preg_replace('/[^a-zA-Z0-9.-]/', '', (string)gethostname()) ?: 'jocarsa.com';
            self::command($socket, 'EHLO ' . $hostname, [250]);
            self::command($socket, 'STARTTLS', [220]);
            $cryptoMethod = defined('STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT')
                ? STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT
                : STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;
            if (@stream_socket_enable_crypto($socket, true, $cryptoMethod) !== true) {
                throw new RuntimeException('No se pudo iniciar la conexión SMTP segura.');
            }
            self::command($socket, 'EHLO ' . $hostname, [250]);
            self::command($socket, 'AUTH LOGIN', [334]);
            self::command($socket, base64_encode(self::SMTP_USER), [334]);
            self::command($socket, base64_encode(self::SMTP_PASSWORD), [235]);
            self::command($socket, 'MAIL FROM:<' . self::SMTP_USER . '>', [250]);
            self::command($socket, 'RCPT TO:<' . $recipient . '>', [250, 251]);
            self::command($socket, 'DATA', [354]);

            $boundary = '=_jocarsa_' . bin2hex(random_bytes(12));
            $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
            $headers = [
                'Date: ' . date(DATE_RFC2822),
                'From: jocarsa | asistencias <' . self::SMTP_USER . '>',
                'To: <' . $recipient . '>',
                'Subject: ' . $encodedSubject,
                'Message-ID: <' . bin2hex(random_bytes(12)) . '@jocarsa.com>',
                'MIME-Version: 1.0',
                'Content-Type: multipart/alternative; boundary="' . $boundary . '"',
            ];
            $body = implode("\r\n", $headers) . "\r\n\r\n"
                . '--' . $boundary . "\r\n"
                . "Content-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n"
                . rtrim(chunk_split(base64_encode($plain), 76, "\r\n")) . "\r\n"
                . '--' . $boundary . "\r\n"
                . "Content-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n"
                . rtrim(chunk_split(base64_encode($html), 76, "\r\n")) . "\r\n"
                . '--' . $boundary . "--\r\n";
            $body = preg_replace('/(?m)^\./', '..', $body) ?? $body;
            self::write($socket, $body . ".\r\n");
            self::expect($socket, [250]);
            self::command($socket, 'QUIT', [221]);
        } finally {
            fclose($socket);
        }
    }

    /** @param resource $socket */
    private static function command($socket, string $command, array $expectedCodes): void
    {
        self::write($socket, $command . "\r\n");
        self::expect($socket, $expectedCodes);
    }

    /** @param resource $socket */
    private static function write($socket, string $data): void
    {
        $length = strlen($data);
        $written = 0;
        while ($written < $length) {
            $bytes = fwrite($socket, substr($data, $written));
            if ($bytes === false || $bytes === 0) {
                throw new RuntimeException('El servidor SMTP interrumpió el envío.');
            }
            $written += $bytes;
        }
    }

    /** @param resource $socket @param list<int> $expectedCodes */
    private static function expect($socket, array $expectedCodes): void
    {
        $response = '';
        do {
            $line = fgets($socket, 1024);
            if ($line === false) {
                $meta = stream_get_meta_data($socket);
                throw new RuntimeException(!empty($meta['timed_out']) ? 'El servidor SMTP agotó el tiempo de espera.' : 'Respuesta SMTP incompleta.');
            }
            $response .= $line;
        } while (strlen($line) >= 4 && $line[3] === '-');

        $code = (int)substr($response, 0, 3);
        if (!in_array($code, $expectedCodes, true)) {
            throw new RuntimeException('El servidor SMTP rechazó la operación (código ' . $code . ').');
        }
    }
}
```

### src/Env.php

```php
<?php
declare(strict_types=1);

namespace JA;

final class Env
{
    public static function load(string $path): void
    {
        if (!is_file($path) || !is_readable($path)) {
            return;
        }

        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }
            [$key, $value] = array_map('trim', explode('=', $line, 2));
            if ($key === '' || getenv($key) !== false) {
                continue;
            }
            if (strlen($value) >= 2 && (($value[0] === '"' && str_ends_with($value, '"')) || ($value[0] === "'" && str_ends_with($value, "'")))) {
                $value = substr($value, 1, -1);
            }
            putenv($key . '=' . $value);
            $_ENV[$key] = $value;
        }
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $value = getenv($key);
        return $value === false ? $default : $value;
    }

    public static function bool(string $key, bool $default = false): bool
    {
        $value = self::get($key);
        if ($value === null) {
            return $default;
        }
        return filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? $default;
    }
}



```

### src/helpers.php

```php
<?php
declare(strict_types=1);

function ja_h(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function ja_redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

function ja_json(array $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function ja_post(string $key, string $default = ''): string
{
    return trim((string)($_POST[$key] ?? $default));
}



```

### src/ScheduleCompliance.php

```php
<?php
declare(strict_types=1);

namespace JA;

use DateTimeImmutable;
use PDO;

final class ScheduleCompliance
{
    /** @return array{outside:bool,schedule_label:string} */
    public static function evaluate(PDO $pdo, int $tenantId, int $employeeId, string $date, string $time): array
    {
        $current = new DateTimeImmutable($date);
        $previous = $current->modify('-1 day');
        $statement = $pdo->prepare(
            'SELECT weekday,start_time,end_time,valid_from,valid_until
             FROM schedules
             WHERE tenant_id=:tenant AND employee_id=:employee
               AND weekday IN (:current_weekday,:previous_weekday)
             ORDER BY weekday,start_time'
        );
        $statement->execute([
            'tenant' => $tenantId,
            'employee' => $employeeId,
            'current_weekday' => (int)$current->format('N'),
            'previous_weekday' => (int)$previous->format('N'),
        ]);

        $labels = [];
        $inside = false;
        foreach ($statement->fetchAll() as $slot) {
            $slotDate = (int)$slot['weekday'] === (int)$current->format('N') ? $current : $previous;
            $slotDateString = $slotDate->format('Y-m-d');
            if (($slot['valid_from'] && $slotDateString < $slot['valid_from'])
                || ($slot['valid_until'] && $slotDateString > $slot['valid_until'])) {
                continue;
            }
            $start = substr((string)$slot['start_time'], 0, 5);
            $end = substr((string)$slot['end_time'], 0, 5);
            $overnight = $end < $start;
            if ($slotDateString === $date) {
                $labels[] = $start . '–' . $end;
                $inside = $inside || (!$overnight && $time >= $start && $time <= $end)
                    || ($overnight && $time >= $start);
            } elseif ($overnight && $time <= $end) {
                $labels[] = $start . '–' . $end . ' (día anterior)';
                $inside = true;
            }
        }

        return [
            'outside' => !$inside,
            'schedule_label' => $labels ? implode(', ', array_unique($labels)) : 'Sin horario establecido para este momento',
        ];
    }
}

```

### src/Session.php

```php
<?php
declare(strict_types=1);

namespace JA;

final class Session
{
    public static function start(string $scope): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        $safeScope = preg_replace('/[^a-z0-9_]/i', '', $scope) ?: 'app';
        session_name('jav2' . $safeScope);
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }

    public static function destroy(): void
    {
        ApplicationAudit::preserveActor();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'] ?? '', (bool)$params['secure'], (bool)$params['httponly']);
        }
        session_destroy();
    }
}


```

### src/Settings.php

```php
<?php
declare(strict_types=1);

namespace JA;

use PDO;
use RuntimeException;

final class Settings
{
    public const WORKER_ATTENDANCE_EDIT = 'worker_attendance_edit_enabled';
    public const INTERNAL_TOAST_SECONDS = 'internal_toast_seconds';
    public const INTERNAL_TOAST_MODE = 'internal_toast_mode';
    public const COMPANY_LOCATION_ENABLED = 'company_location_enabled';
    public const COMPANY_IPV4 = 'company_ipv4';
    public const COMPANY_IPV6 = 'company_ipv6';
    public const EMAIL_NOTIFICATIONS_ENABLED = 'email_notifications_enabled';
    public const NOTIFICATION_EMAIL = 'notification_email';
    public const NOTIFY_ABSENCE_REQUEST = 'notify_absence_request';
    public const NOTIFY_OUT_OF_SCHEDULE_CLOCKING = 'notify_out_of_schedule_clocking';

    /** @var array<string,string> */
    private const DEFAULTS = [
        self::WORKER_ATTENDANCE_EDIT => '1',
        self::INTERNAL_TOAST_SECONDS => '8',
        self::INTERNAL_TOAST_MODE => 'stack',
        self::COMPANY_LOCATION_ENABLED => '0',
        self::COMPANY_IPV4 => '',
        self::COMPANY_IPV6 => '',
        self::EMAIL_NOTIFICATIONS_ENABLED => '0',
        self::NOTIFICATION_EMAIL => '',
        self::NOTIFY_ABSENCE_REQUEST => '1',
        self::NOTIFY_OUT_OF_SCHEDULE_CLOCKING => '1',
    ];

    public static function get(PDO $pdo, int $tenantId, string $key): string
    {
        $statement = $pdo->prepare(
            'SELECT setting_value FROM tenant_settings WHERE tenant_id=:tenant AND setting_key=:key LIMIT 1'
        );
        $statement->execute(['tenant' => $tenantId, 'key' => $key]);
        $value = $statement->fetchColumn();
        return is_string($value) ? $value : (self::DEFAULTS[$key] ?? '');
    }

    public static function bool(PDO $pdo, int $tenantId, string $key): bool
    {
        return in_array(strtolower(trim(self::get($pdo, $tenantId, $key))), ['1', 'true', 'yes', 'on'], true);
    }

    public static function set(PDO $pdo, int $tenantId, string $key, string $value, int $adminId): void
    {
        if (!array_key_exists($key, self::DEFAULTS)) {
            throw new RuntimeException('La opción de configuración no existe.');
        }
        $statement = $pdo->prepare(
            'INSERT INTO tenant_settings (tenant_id,setting_key,setting_value,updated_by_admin_id,updated_at)
             VALUES (:tenant,:key,:value,:admin,CURRENT_TIMESTAMP)
             ON CONFLICT(tenant_id,setting_key) DO UPDATE SET
               setting_value=excluded.setting_value,
               updated_by_admin_id=excluded.updated_by_admin_id,
               updated_at=CURRENT_TIMESTAMP'
        );
        $statement->execute([
            'tenant' => $tenantId,
            'key' => $key,
            'value' => $value,
            'admin' => $adminId,
        ]);
    }

    public static function seedTenant(PDO $pdo, int $tenantId): void
    {
        $insert = $pdo->prepare(
            'INSERT OR IGNORE INTO tenant_settings (tenant_id,setting_key,setting_value)
             VALUES (:tenant,:key,:value)'
        );
        foreach (self::DEFAULTS as $key => $value) {
            $insert->execute(['tenant' => $tenantId, 'key' => $key, 'value' => $value]);
        }
    }

    public static function seedAllTenants(PDO $pdo): void
    {
        $tenants = $pdo->query('SELECT id FROM tenants ORDER BY id')->fetchAll();
        foreach ($tenants as $tenant) {
            self::seedTenant($pdo, (int)$tenant['id']);
        }
    }
}

```

### src/TenantContext.php

```php
<?php
declare(strict_types=1);

namespace JA;

use PDO;
use RuntimeException;

final class TenantContext
{
    private static ?array $tenant = null;

    public static function current(): array
    {
        if (self::$tenant !== null) {
            return self::$tenant;
        }

        $host = self::requestHost();
        $pdo = Database::pdo();
        $statement = $pdo->prepare(
            "SELECT t.*, d.domain, d.is_primary
             FROM tenant_domains d
             INNER JOIN tenants t ON t.id = d.tenant_id
             WHERE d.domain = :domain AND t.status = 'active'
             LIMIT 1"
        );
        $statement->execute(['domain' => $host]);
        $tenant = $statement->fetch();

        if (!$tenant && self::isLocalHost($host)) {
            $slug = trim((string)Env::get('LOCALHOST_TENANT_SLUG', ''));
            if ($slug !== '') {
                $statement = $pdo->prepare("SELECT t.*, :domain AS domain, 0 AS is_primary FROM tenants t WHERE t.slug = :slug AND t.status = 'active' LIMIT 1");
                $statement->execute(['domain' => $host, 'slug' => $slug]);
                $tenant = $statement->fetch();
            }
        }

        if (!$tenant) {
            throw new RuntimeException('Este dominio no está asignado a ninguna organización activa.');
        }

        self::$tenant = $tenant;
        date_default_timezone_set((string)($tenant['timezone'] ?: Env::get('APP_TIMEZONE', 'Europe/Madrid')));
        Database::setTimezone(date_default_timezone_get());
        return self::$tenant;
    }

    public static function id(): int
    {
        return (int)self::current()['id'];
    }

    public static function requestHost(): string
    {
        $raw = (string)($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost');
        if (Env::bool('APP_TRUST_PROXY', false) && !empty($_SERVER['HTTP_X_FORWARDED_HOST'])) {
            $raw = trim(explode(',', (string)$_SERVER['HTTP_X_FORWARDED_HOST'])[0]);
        }

        $raw = strtolower(trim($raw));
        if (filter_var($raw, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $host = $raw;
        } elseif (str_starts_with($raw, '[')) {
            $end = strpos($raw, ']');
            $host = $end === false ? $raw : substr($raw, 1, $end - 1);
        } else {
            $host = explode(':', $raw, 2)[0];
        }
        $host = rtrim($host, '.');
        if (function_exists('idn_to_ascii') && !self::isLocalHost($host)) {
            $ascii = idn_to_ascii($host, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);
            if ($ascii !== false) {
                $host = strtolower($ascii);
            }
        }
        if ($host === '' || strlen($host) > 253 || (!filter_var($host, FILTER_VALIDATE_IP) && !preg_match('/^[a-z0-9._-]+$/', $host))) {
            throw new RuntimeException('El dominio de la petición no es válido.');
        }
        return $host;
    }

    public static function normalizeDomain(string $domain): string
    {
        $domain = strtolower(trim($domain));
        $domain = preg_replace('#^https?://#', '', $domain) ?? $domain;
        $domain = explode('/', $domain, 2)[0];
        if (filter_var($domain, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            // Keep the unbracketed IPv6 literal.
        } elseif (str_starts_with($domain, '[')) {
            $end = strpos($domain, ']');
            $domain = $end === false ? $domain : substr($domain, 1, $end - 1);
        } else {
            $domain = explode(':', $domain, 2)[0];
        }
        $domain = rtrim($domain, '.');
        if (function_exists('idn_to_ascii') && !self::isLocalHost($domain)) {
            $ascii = idn_to_ascii($domain, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);
            if ($ascii !== false) {
                $domain = strtolower($ascii);
            }
        }
        if ($domain === '' || strlen($domain) > 253 || (!filter_var($domain, FILTER_VALIDATE_IP) && !preg_match('/^[a-z0-9._-]+$/', $domain))) {
            throw new RuntimeException('Dominio no válido.');
        }
        return $domain;
    }

    public static function isLocalHost(string $host): bool
    {
        return in_array(strtolower($host), ['localhost', '127.0.0.1', '::1'], true) || str_ends_with(strtolower($host), '.localhost');
    }
}


```

### src/V1DumpImporter.php

```php
<?php
declare(strict_types=1);

namespace JA;

use PDO;
use RuntimeException;
use Throwable;

final class V1DumpImporter
{
    /**
     * Only short, clearly reversed vacation ranges are swapped automatically.
     * Longer ranges remain blocked because they may instead contain a wrong
     * month or year that the importer cannot infer safely.
     */
    private const MAX_AUTOCORRECT_VACATION_DAYS = 62;

    private const TABLES = [
        'admin', 'asistencia', 'ausencias', 'centros_trabajo', 'contratos',
        'empleados', 'festivos', 'horarios', 'incidencias', 'tipos_contrato',
        'usuarios', 'vacaciones',
    ];

    /** @var array<string,\PDOStatement> */
    private array $upsertStatements = [];

    /** @var array<string,\PDOStatement> */
    private array $idStatements = [];
    private string $activeSourceName = '';

    public function __construct(private readonly PDO $pdo)
    {
    }

    public function import(int $tenantId, string $file, ?string $sourceName = null): array
    {
        if (!is_file($file) || !is_readable($file)) {
            throw new RuntimeException('El volcado SQL no existe o no se puede leer.');
        }
        $tenant = $this->pdo->prepare('SELECT id FROM tenants WHERE id = :id LIMIT 1');
        $tenant->execute(['id' => $tenantId]);
        if (!$tenant->fetchColumn()) {
            throw new RuntimeException('El tenant de destino no existe.');
        }

        $run = $this->pdo->prepare("INSERT INTO import_runs (tenant_id, source_name, status) VALUES (:tenant, :source, 'running')");
        $safeSourceName = basename($sourceName ?: $file);
        $this->activeSourceName = $safeSourceName;
        $run->execute(['tenant' => $tenantId, 'source' => $safeSourceName]);
        $runId = (int)$this->pdo->lastInsertId();

        try {
            AttendanceAudit::bootstrapTenant($this->pdo, $tenantId);
            $integrity = AttendanceAudit::verifyTenant($this->pdo, $tenantId);
            if (!$integrity['valid']) {
                throw new RuntimeException('La importación se ha bloqueado porque la cadena de asistencia no es íntegra.');
            }
            $rows = $this->parseFile($file);
            $summary = $this->persist($tenantId, $rows);
            $summary['source'] = $safeSourceName;
            $summary['run_id'] = $runId;
            $done = $this->pdo->prepare("UPDATE import_runs SET status = 'completed', summary = :summary, finished_at = CURRENT_TIMESTAMP WHERE id = :id");
            $done->execute(['summary' => json_encode($summary, JSON_UNESCAPED_UNICODE), 'id' => $runId]);
            return $summary;
        } catch (Throwable $exception) {
            $failed = $this->pdo->prepare("UPDATE import_runs SET status = 'failed', summary = :summary, finished_at = CURRENT_TIMESTAMP WHERE id = :id");
            $failed->execute(['summary' => json_encode(['error' => $exception->getMessage()], JSON_UNESCAPED_UNICODE), 'id' => $runId]);
            throw $exception;
        }
    }

    /** @return array<string,array<int,array<string,mixed>>> */
    public function parseFile(string $file): array
    {
        $sql = file_get_contents($file);
        if ($sql === false) {
            throw new RuntimeException('No se pudo leer el volcado SQL.');
        }

        $result = array_fill_keys(self::TABLES, []);
        foreach ($this->statements($sql) as $statement) {
            if (!preg_match('/\bINSERT\s+INTO\s+`?([A-Za-z0-9_]+)`?\s*\((.*?)\)\s*VALUES\s*(.*)\s*;?\s*$/is', $statement, $match)) {
                continue;
            }
            $table = strtolower($match[1]);
            if (!array_key_exists($table, $result)) {
                continue;
            }
            $columns = array_map(static fn(string $column): string => trim($column, " \t\n\r\0\x0B`"), explode(',', $match[2]));
            foreach ($this->tuples($match[3]) as $values) {
                if (count($columns) !== count($values)) {
                    throw new RuntimeException('Número de columnas y valores distinto en la tabla V1 ' . $table . '.');
                }
                $result[$table][] = array_combine($columns, $values);
            }
        }
        if (array_sum(array_map('count', $result)) === 0) {
            throw new RuntimeException('El archivo no contiene INSERT compatibles con el formato V1.');
        }
        return $result;
    }

    /** @return iterable<int,string> */
    private function statements(string $sql): iterable
    {
        $buffer = '';
        $quoted = false;
        $escaped = false;
        $length = strlen($sql);
        for ($i = 0; $i < $length; $i++) {
            $char = $sql[$i];
            $buffer .= $char;
            if ($quoted) {
                if ($escaped) {
                    $escaped = false;
                } elseif ($char === '\\') {
                    $escaped = true;
                } elseif ($char === "'") {
                    if ($i + 1 < $length && $sql[$i + 1] === "'") {
                        $buffer .= $sql[++$i];
                    } else {
                        $quoted = false;
                    }
                }
                continue;
            }
            if ($char === "'") {
                $quoted = true;
            } elseif ($char === ';') {
                yield $buffer;
                $buffer = '';
            }
        }
        if (trim($buffer) !== '') {
            yield $buffer;
        }
    }

    /** @return array<int,array<int,mixed>> */
    private function tuples(string $valuesSql): array
    {
        $tuples = [];
        $tuple = [];
        $token = '';
        $depth = 0;
        $quoted = false;
        $escaped = false;
        $length = strlen($valuesSql);

        for ($i = 0; $i < $length; $i++) {
            $char = $valuesSql[$i];
            if ($quoted) {
                $token .= $char;
                if ($escaped) {
                    $escaped = false;
                } elseif ($char === '\\') {
                    $escaped = true;
                } elseif ($char === "'") {
                    if ($i + 1 < $length && $valuesSql[$i + 1] === "'") {
                        $token .= $valuesSql[++$i];
                    } else {
                        $quoted = false;
                    }
                }
                continue;
            }
            if ($char === "'") {
                $quoted = true;
                $token .= $char;
                continue;
            }
            if ($char === '(') {
                if ($depth > 0) {
                    $token .= $char;
                }
                $depth++;
                continue;
            }
            if ($char === ')' && $depth > 0) {
                $depth--;
                if ($depth === 0) {
                    $tuple[] = $this->decodeToken($token);
                    $tuples[] = $tuple;
                    $tuple = [];
                    $token = '';
                } else {
                    $token .= $char;
                }
                continue;
            }
            if ($char === ',' && $depth === 1) {
                $tuple[] = $this->decodeToken($token);
                $token = '';
                continue;
            }
            if ($depth > 0) {
                $token .= $char;
            }
        }
        return $tuples;
    }

    private function decodeToken(string $token): mixed
    {
        $token = trim($token);
        if (strcasecmp($token, 'NULL') === 0) {
            return null;
        }
        if (strlen($token) >= 2 && $token[0] === "'" && str_ends_with($token, "'")) {
            $raw = substr($token, 1, -1);
            $decoded = '';
            $length = strlen($raw);
            $map = ['0' => "\0", 'n' => "\n", 'r' => "\r", 't' => "\t", 'Z' => "\x1a", '\\' => '\\', "'" => "'", '"' => '"'];
            for ($i = 0; $i < $length; $i++) {
                if ($raw[$i] === '\\' && $i + 1 < $length) {
                    $next = $raw[++$i];
                    $decoded .= $map[$next] ?? $next;
                } elseif ($raw[$i] === "'" && $i + 1 < $length && $raw[$i + 1] === "'") {
                    $decoded .= "'";
                    $i++;
                } else {
                    $decoded .= $raw[$i];
                }
            }
            return $decoded;
        }
        return $token;
    }

    /** @param array<string,array<int,array<string,mixed>>> $rows */
    private function persist(int $tenantId, array $rows): array
    {
        $summary = [
            'imported' => [],
            'reused_accounts' => [],
            'preserved_identities' => [],
            'corrected' => [],
            'corrections' => [],
            'skipped' => 0,
        ];
        $employeeMap = [];
        $contractTypeMap = [];
        $centerMap = [];

        $this->pdo->beginTransaction();
        try {
            foreach ($rows['tipos_contrato'] as $row) {
                $id = $this->upsert('contract_types', $tenantId, (int)$row['id'], [
                    'name' => (string)$row['name'],
                ]);
                $contractTypeMap[(int)$row['id']] = $id;
                $this->count($summary, 'contract_types');
            }

            foreach ($rows['centros_trabajo'] as $row) {
                $id = $this->upsert('work_centers', $tenantId, (int)$row['id'], [
                    'name' => (string)$row['name'],
                    'address' => $row['address'] ?? null,
                    'latitude' => $row['latitude'] ?? null,
                    'longitude' => $row['longitude'] ?? null,
                ]);
                $centerMap[(int)$row['id']] = $id;
                $this->count($summary, 'work_centers');
            }

            foreach ($rows['empleados'] as $row) {
                $legacyId = (int)$row['id'];
                $id = $this->upsert('employees', $tenantId, $legacyId, [
                    'photo' => $row['fotografia'] ?? null,
                    'nombre' => (string)($row['nombre'] ?? ''),
                    'apellido' => $row['apellido'] ?? null,
                    'departamento' => $row['departamento'] ?? null,
                    'contract_type_id' => $this->mappedNullable($row['tipo_contrato_id'] ?? null, $contractTypeMap),
                    'work_center_id' => $this->mappedNullable($row['centro_trabajo_id'] ?? null, $centerMap),
                    'action_radius' => $row['radio_accion'] ?? null,
                    'role' => $row['rol'] ?? null,
                    'active' => 1,
                ]);
                $employeeMap[$legacyId] = $id;
                $this->count($summary, 'employees');
            }

            foreach ($rows['admin'] as $row) {
                $result = $this->upsertAccount('tenant_admins', $tenantId, (int)$row['id'], [
                    'full_name' => (string)$row['full_name'],
                    'email' => $this->nullableString($row['email'] ?? null),
                    'username' => (string)$row['username'],
                    'password_hash' => Auth::passwordForStorage((string)$row['password']),
                    'active' => 1,
                ]);
                $this->count($summary, 'tenant_admins');
                $this->countAccountResolution($summary, 'tenant_admins', $result);
            }

            foreach ($rows['usuarios'] as $row) {
                $employeeId = $this->mappedNullable($row['empleado_id'] ?? null, $employeeMap);
                $result = $this->upsertAccount('users', $tenantId, (int)$row['id'], [
                    'employee_id' => $employeeId,
                    'name' => (string)$row['nombre'],
                    'email' => $this->nullableString($row['email'] ?? null),
                    'username' => (string)$row['username'],
                    'password_hash' => Auth::passwordForStorage((string)$row['password']),
                    'role' => (string)($row['rol'] ?: 'worker'),
                    'active' => 1,
                ]);
                $this->count($summary, 'users');
                $this->countAccountResolution($summary, 'users', $result);
            }

            $this->importDependentRows($rows, $tenantId, $employeeMap, $summary);
            $this->pdo->commit();
            return $summary;
        } catch (Throwable $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }
    }

    /**
     * Accounts have tenant-scoped unique usernames and emails in addition to
     * legacy_id. An administrator or user may already have been created in 2.x
     * before importing V1, so an upsert based only on legacy_id is insufficient.
     *
     * When an existing identity is reused, its current password is deliberately
     * preserved. This prevents an import (or a repeated import) from resetting a
     * password that has already been configured in 2.x.
     *
     * @param array<string,mixed> $data
     * @return array{reused:bool,identity_preserved:bool}
     */
    private function upsertAccount(string $table, int $tenantId, int $legacyId, array $data): array
    {
        if (!in_array($table, ['tenant_admins', 'users'], true)) {
            throw new RuntimeException('Tabla de cuenta de importación no permitida.');
        }

        $username = trim((string)($data['username'] ?? ''));
        $email = $this->nullableString($data['email'] ?? null);
        if ($username === '') {
            throw new RuntimeException('El volcado contiene una cuenta sin nombre de usuario.');
        }

        $quotedTable = $this->quoteIdentifier($table);
        $findLegacy = $this->pdo->prepare(
            'SELECT id FROM ' . $quotedTable .
            ' WHERE tenant_id = :tenant AND legacy_id = :legacy LIMIT 1'
        );
        $findLegacy->execute(['tenant' => $tenantId, 'legacy' => $legacyId]);
        $id = (int)$findLegacy->fetchColumn();
        $reused = false;

        if ($id < 1) {
            $findIdentity = $this->pdo->prepare(
                'SELECT id FROM ' . $quotedTable . '
                 WHERE tenant_id = :tenant
                   AND (username = :username OR (:email IS NOT NULL AND email = :email))
                 ORDER BY CASE WHEN username = :username THEN 0 ELSE 1 END, id
                 LIMIT 1'
            );
            $findIdentity->execute([
                'tenant' => $tenantId,
                'username' => $username,
                'email' => $email,
            ]);
            $id = (int)$findIdentity->fetchColumn();
            $reused = $id > 0;
        }

        if ($id < 1) {
            $this->upsert($table, $tenantId, $legacyId, $data);
            return ['reused' => false, 'identity_preserved' => false];
        }

        $updates = $data;
        unset($updates['username'], $updates['email'], $updates['password_hash']);
        $assignments = [
            'legacy_id = CASE WHEN legacy_id IS NULL THEN :legacy_id ELSE legacy_id END',
        ];
        $parameters = [
            'id' => $id,
            'tenant_id' => $tenantId,
            'legacy_id' => $legacyId,
        ];
        foreach ($updates as $column => $value) {
            if (!preg_match('/^[a-z_]+$/', $column)) {
                throw new RuntimeException('Columna de cuenta de importación no permitida.');
            }
            $assignments[] = $this->quoteIdentifier($column) . ' = :' . $column;
            $parameters[$column] = $value;
        }
        $assignments[] = 'updated_at = CURRENT_TIMESTAMP';
        $update = $this->pdo->prepare(
            'UPDATE ' . $quotedTable . ' SET ' . implode(', ', $assignments) .
            ' WHERE id = :id AND tenant_id = :tenant_id'
        );
        $update->execute($parameters);

        $identityPreserved = false;
        if (!$this->identityBelongsToAnotherAccount($table, $tenantId, $id, 'username', $username)) {
            $updateUsername = $this->pdo->prepare(
                'UPDATE ' . $quotedTable .
                ' SET username = :value, updated_at = CURRENT_TIMESTAMP' .
                ' WHERE id = :id AND tenant_id = :tenant'
            );
            $updateUsername->execute(['value' => $username, 'id' => $id, 'tenant' => $tenantId]);
        } else {
            $identityPreserved = true;
        }

        if ($email !== null) {
            if (!$this->identityBelongsToAnotherAccount($table, $tenantId, $id, 'email', $email)) {
                $updateEmail = $this->pdo->prepare(
                    'UPDATE ' . $quotedTable .
                    ' SET email = :value, updated_at = CURRENT_TIMESTAMP' .
                    ' WHERE id = :id AND tenant_id = :tenant'
                );
                $updateEmail->execute(['value' => $email, 'id' => $id, 'tenant' => $tenantId]);
            } else {
                $identityPreserved = true;
            }
        }

        return ['reused' => $reused, 'identity_preserved' => $identityPreserved];
    }

    private function identityBelongsToAnotherAccount(
        string $table,
        int $tenantId,
        int $accountId,
        string $column,
        string $value
    ): bool {
        if (!in_array($table, ['tenant_admins', 'users'], true) || !in_array($column, ['username', 'email'], true)) {
            throw new RuntimeException('Identidad de cuenta de importación no permitida.');
        }
        $statement = $this->pdo->prepare(
            'SELECT 1 FROM ' . $this->quoteIdentifier($table) .
            ' WHERE tenant_id = :tenant AND ' . $this->quoteIdentifier($column) . ' = :value AND id <> :id LIMIT 1'
        );
        $statement->execute(['tenant' => $tenantId, 'value' => $value, 'id' => $accountId]);
        return (bool)$statement->fetchColumn();
    }

    private function importDependentRows(array $rows, int $tenantId, array $employeeMap, array &$summary): void
    {
        $specs = [
            'horarios' => ['schedules', 'empleado', static fn(array $r, int $employee): array => ['employee_id' => $employee, 'weekday' => (int)$r['dia_de_la_semana'], 'valid_from' => $r['fecha_inicio'], 'valid_until' => $r['fecha_final'], 'start_time' => $r['hora_de_entrada'], 'end_time' => $r['hora_de_salida']]],
            'incidencias' => ['incidents', 'empleado_id', static fn(array $r, int $employee): array => ['employee_id' => $employee, 'type' => $r['tipo'], 'incident_date' => $r['fecha'], 'notes' => null]],
            'ausencias' => ['absences', 'empleado_id', static function (array $r, int $employee): array {
                $approved = (int)($r['aprobado'] ?? 0);
                $rawStatus = strtolower((string)($r['estado'] ?? ''));
                $status = in_array($rawStatus, ['approved', 'aprobada', 'aprobado'], true) || $approved ? 'approved' : (in_array($rawStatus, ['rejected', 'rechazada', 'rechazado'], true) ? 'rejected' : 'pending');
                return ['employee_id' => $employee, 'start_date' => $r['fecha_inicio'], 'end_date' => $r['fecha_fin'], 'absence_type' => $r['tipo_ausencia'], 'reason' => $r['motivo'], 'attachment' => $r['documento_justificante'], 'status' => $status, 'requested_at' => $r['fecha_solicitud'] ?: date('Y-m-d H:i:s'), 'approved' => $approved, 'source' => 'import'];
            }],
            'vacaciones' => ['vacations', 'empleado_id', function (array $r, int $employee) use (&$summary): array {
                $range = $this->normalizeVacationRange($r, $summary);
                return [
                    'employee_id' => $employee,
                    'start_date' => $range['start_date'],
                    'end_date' => $range['end_date'],
                    'approved' => (int)$r['aprobado'],
                ];
            }],
            'contratos' => ['contracts', 'empleado_id', fn(array $r, int $employee): array => [
                'employee_id' => $employee,
                'contract_type_id' => ContractCatalog::resolveTypeId($this->pdo, $tenantId, (string)$r['tipo_contrato']),
                'contract_type' => $r['tipo_contrato'],
                'signed_date' => $r['fecha_firma'],
                'end_date' => $r['fecha_finalizacion'],
                'annual_salary' => $r['salario_anual'],
            ]],
        ];

        foreach ($rows['asistencia'] as $row) {
            $legacyEmployee = (int)($row['empleado_id'] ?? 0);
            if (!$legacyEmployee || !isset($employeeMap[$legacyEmployee])) {
                $summary['skipped']++;
                continue;
            }
            $legacyId = (int)$row['id'];
            $beforeStatement = $this->pdo->prepare('SELECT * FROM attendance WHERE tenant_id=:tenant AND legacy_id=:legacy LIMIT 1');
            $beforeStatement->execute(['tenant' => $tenantId, 'legacy' => $legacyId]);
            $before = $beforeStatement->fetch() ?: null;
            $attendanceId = $this->upsert('attendance', $tenantId, $legacyId, [
                'employee_id' => $employeeMap[$legacyEmployee],
                'work_date' => $row['fecha'],
                'start_time' => $row['hora_entrada'],
                'end_time' => $row['hora_salida'],
            ]);
            $afterStatement = $this->pdo->prepare('SELECT * FROM attendance WHERE id=:id AND tenant_id=:tenant LIMIT 1');
            $afterStatement->execute(['id' => $attendanceId, 'tenant' => $tenantId]);
            $after = $afterStatement->fetch();
            if (!$after) {
                throw new RuntimeException('No se pudo auditar una asistencia importada.');
            }
            $beforeState = $before ? $this->attendanceState($before) : null;
            $afterState = $this->attendanceState($after);
            if ($beforeState !== null && json_encode($beforeState) !== json_encode($afterState)) {
                throw new RuntimeException(
                    'El volcado intenta cambiar la asistencia V1 #' . $legacyId .
                    '. Una jornada ya autenticada solo puede modificarse mediante una corrección aceptada por el trabajador.'
                );
            }
            if ($beforeState === null) {
                AttendanceAudit::recordStateEvent(
                    $this->pdo,
                    $tenantId,
                    $after,
                    'imported',
                    'system',
                    null,
                    'Importador V1',
                    [
                        'state_before' => $beforeState,
                        'source' => $this->activeSourceName,
                        'legacy_id' => $legacyId,
                    ]
                );
            }
            $this->count($summary, 'attendance');
        }

        foreach ($specs as $source => [$target, $employeeColumn, $transform]) {
            foreach ($rows[$source] as $row) {
                $legacyEmployee = (int)($row[$employeeColumn] ?? 0);
                if (!$legacyEmployee || !isset($employeeMap[$legacyEmployee])) {
                    $summary['skipped']++;
                    continue;
                }
                $this->upsert($target, $tenantId, (int)$row['id'], $transform($row, $employeeMap[$legacyEmployee]));
                $this->count($summary, $target);
            }
        }

        foreach ($rows['festivos'] as $row) {
            $this->upsert('holidays', $tenantId, (int)$row['id'], [
                'holiday_date' => $row['fecha'],
                'name' => $row['nombre'],
                'description' => $row['descripcion'],
            ]);
            $this->count($summary, 'holidays');
        }
    }

    private function upsert(string $table, int $tenantId, int $legacyId, array $data): int
    {
        $allowed = ['contract_types', 'work_centers', 'employees', 'tenant_admins', 'users', 'attendance', 'schedules', 'incidents', 'absences', 'vacations', 'contracts', 'holidays'];
        if (!in_array($table, $allowed, true)) {
            throw new RuntimeException('Tabla de importación no permitida.');
        }
        $columns = array_keys($data);
        foreach ($columns as $column) {
            if (!preg_match('/^[a-z_]+$/', $column)) {
                throw new RuntimeException('Columna de importación no permitida.');
            }
        }
        $allColumns = array_merge(['tenant_id', 'legacy_id'], $columns);
        $placeholders = array_map(static fn(string $column): string => ':' . $column, $allColumns);
        $quotedColumns = array_map([$this, 'quoteIdentifier'], $allColumns);
        $updates = array_map(
            fn(string $column): string => $this->quoteIdentifier($column) . ' = excluded.' . $this->quoteIdentifier($column),
            $columns
        );
        $cacheKey = $table . ':' . implode(',', $columns);
        if (!isset($this->upsertStatements[$cacheKey])) {
            $sql = 'INSERT INTO ' . $this->quoteIdentifier($table) .
                ' (' . implode(',', $quotedColumns) . ') VALUES (' . implode(',', $placeholders) . ')' .
                ' ON CONFLICT(tenant_id, legacy_id) DO UPDATE SET ' . implode(',', $updates);
            $this->upsertStatements[$cacheKey] = $this->pdo->prepare($sql);
        }
        $statement = $this->upsertStatements[$cacheKey];
        $statement->execute(array_merge(['tenant_id' => $tenantId, 'legacy_id' => $legacyId], $data));

        if (!isset($this->idStatements[$table])) {
            $this->idStatements[$table] = $this->pdo->prepare(
                'SELECT id FROM ' . $this->quoteIdentifier($table) .
                ' WHERE tenant_id = :tenant AND legacy_id = :legacy LIMIT 1'
            );
        }
        $idStatement = $this->idStatements[$table];
        $idStatement->execute(['tenant' => $tenantId, 'legacy' => $legacyId]);
        $id = (int)$idStatement->fetchColumn();
        if ($id < 1) {
            throw new RuntimeException('No se pudo recuperar el registro importado de ' . $table . '.');
        }
        return $id;
    }

    private function quoteIdentifier(string $identifier): string
    {
        if (!preg_match('/^[a-z_]+$/', $identifier)) {
            throw new RuntimeException('Identificador de importación no permitido.');
        }
        return '"' . $identifier . '"';
    }

    private function mappedNullable(mixed $legacyId, array $map): ?int
    {
        if ($legacyId === null || $legacyId === '' || !isset($map[(int)$legacyId])) {
            return null;
        }
        return (int)$map[(int)$legacyId];
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string)$value);
        return $value === '' ? null : $value;
    }

    /**
     * Repairs only vacation ranges whose intended value can be inferred from
     * the two dates themselves:
     *
     * 1. A short reversed range is swapped.
     *    2025-10-02 -> 2025-09-18 becomes 2025-09-18 -> 2025-10-02.
     * 2. If the end date is exactly one year behind and moving only its year
     *    forward creates a short valid range, the end year is aligned.
     *    2026-08-01 -> 2025-08-31 becomes 2026-08-01 -> 2026-08-31.
     *
     * Anything else is rejected so that the importer never invents an
     * ambiguous vacation period merely to satisfy the SQLite CHECK.
     *
     * @param array<string,mixed> $row
     * @param array<string,mixed> $summary
     * @return array{start_date:string,end_date:string}
     */
    private function normalizeVacationRange(array $row, array &$summary): array
    {
        $legacyId = (int)($row['id'] ?? 0);
        $employeeLegacyId = (int)($row['empleado_id'] ?? 0);
        $originalStart = $this->validatedIsoDate($row['fecha_inicio'] ?? null, $legacyId, 'fecha_inicio');
        $originalEnd = $this->validatedIsoDate($row['fecha_fin'] ?? null, $legacyId, 'fecha_fin');

        if ($originalEnd >= $originalStart) {
            return ['start_date' => $originalStart, 'end_date' => $originalEnd];
        }

        $start = new \DateTimeImmutable($originalStart);
        $end = new \DateTimeImmutable($originalEnd);
        $startYear = (int)$start->format('Y');
        $endYear = (int)$end->format('Y');

        // Same-year ranges such as 2 October -> 18 September are clear
        // start/end inversions when the resulting period remains short.
        if ($startYear === $endYear && $this->daysBetween($end, $start) <= self::MAX_AUTOCORRECT_VACATION_DAYS) {
            return $this->recordVacationCorrection(
                $summary,
                $legacyId,
                $employeeLegacyId,
                $originalStart,
                $originalEnd,
                $originalEnd,
                $originalStart,
                'swapped_reversed_range'
            );
        }

        if ($startYear === $endYear + 1) {
            // A genuinely reversed range spanning New Year can also be safely
            // swapped when both dates are close together.
            if ($this->daysBetween($end, $start) <= self::MAX_AUTOCORRECT_VACATION_DAYS) {
                return $this->recordVacationCorrection(
                    $summary,
                    $legacyId,
                    $employeeLegacyId,
                    $originalStart,
                    $originalEnd,
                    $originalEnd,
                    $originalStart,
                    'swapped_reversed_range'
                );
            }

            // Otherwise try the common typo where only fecha_fin retained the
            // previous year (for example, August 2025 instead of August 2026).
            $alignedEnd = $end->setDate($startYear, (int)$end->format('m'), (int)$end->format('d'));
            if ($alignedEnd >= $start && $this->daysBetween($start, $alignedEnd) <= self::MAX_AUTOCORRECT_VACATION_DAYS) {
                return $this->recordVacationCorrection(
                    $summary,
                    $legacyId,
                    $employeeLegacyId,
                    $originalStart,
                    $originalEnd,
                    $originalStart,
                    $alignedEnd->format('Y-m-d'),
                    'end_year_aligned_to_start'
                );
            }
        }

        throw new RuntimeException(
            'Vacaciones V1 #' . $legacyId . ' contiene un intervalo ambiguo (' .
            $originalStart . ' -> ' . $originalEnd . '). Corrige el volcado o añade una regla explícita antes de importarlo.'
        );
    }

    private function validatedIsoDate(mixed $value, int $legacyId, string $column): string
    {
        $date = trim((string)$value);
        $parsed = \DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        $errors = \DateTimeImmutable::getLastErrors();
        $hasErrors = is_array($errors) && ($errors['warning_count'] > 0 || $errors['error_count'] > 0);
        if ($parsed === false || $hasErrors || $parsed->format('Y-m-d') !== $date) {
            throw new RuntimeException(
                'Vacaciones V1 #' . $legacyId . ' contiene una fecha inválida en ' . $column . ': ' . $date . '.'
            );
        }
        return $date;
    }

    private function daysBetween(\DateTimeImmutable $first, \DateTimeImmutable $second): int
    {
        return (int)$first->diff($second)->format('%a');
    }

    /**
     * @param array<string,mixed> $summary
     * @return array{start_date:string,end_date:string}
     */
    private function recordVacationCorrection(
        array &$summary,
        int $legacyId,
        int $employeeLegacyId,
        string $originalStart,
        string $originalEnd,
        string $correctedStart,
        string $correctedEnd,
        string $rule
    ): array {
        $summary['corrected']['vacations'] = ($summary['corrected']['vacations'] ?? 0) + 1;
        $summary['corrections'][] = [
            'table' => 'vacations',
            'legacy_id' => $legacyId,
            'employee_legacy_id' => $employeeLegacyId,
            'original' => ['start_date' => $originalStart, 'end_date' => $originalEnd],
            'corrected' => ['start_date' => $correctedStart, 'end_date' => $correctedEnd],
            'rule' => $rule,
        ];

        return ['start_date' => $correctedStart, 'end_date' => $correctedEnd];
    }

    private function attendanceState(array $row): array
    {
        return [
            'attendance_id' => (int)$row['id'],
            'employee_id' => (int)$row['employee_id'],
            'work_date' => (string)$row['work_date'],
            'start_time' => $row['start_time'] !== null ? (string)$row['start_time'] : null,
            'end_time' => $row['end_time'] !== null ? (string)$row['end_time'] : null,
        ];
    }

    private function count(array &$summary, string $table): void
    {
        $summary['imported'][$table] = ($summary['imported'][$table] ?? 0) + 1;
    }

    /** @param array{reused:bool,identity_preserved:bool} $resolution */
    private function countAccountResolution(array &$summary, string $table, array $resolution): void
    {
        if ($resolution['reused']) {
            $summary['reused_accounts'][$table] = ($summary['reused_accounts'][$table] ?? 0) + 1;
        }
        if ($resolution['identity_preserved']) {
            $summary['preserved_identities'][$table] = ($summary['preserved_identities'][$table] ?? 0) + 1;
        }
    }
}
```

### src/View.php

```php
<?php
declare(strict_types=1);

namespace JA;

final class View
{
    public static function head(string $title, string $area = ''): void
    {
        $safeTitle = ja_h($title);
        $safeArea = ja_h($area);
        $assets = str_contains($_SERVER['SCRIPT_NAME'] ?? '', '/admin/') || str_contains($_SERVER['SCRIPT_NAME'] ?? '', '/front/') || str_contains($_SERVER['SCRIPT_NAME'] ?? '', '/superadmin/') ? '../assets' : 'assets';
        $logoUrl = '/assets/jocarsa-darksalmon.svg?v=2.1.8';
        echo <<<HTML
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no,viewport-fit=cover">
  <meta name="color-scheme" content="light">
  <title>{$safeTitle}</title>
  <link rel="stylesheet" href="{$assets}/jocarsa-iu.css?v=2.2.4">
  <link rel="icon" href="{$logoUrl}" type="image/svg+xml">
  <link rel="shortcut icon" href="{$logoUrl}" type="image/svg+xml">
  <link rel="icon" type="image/png" sizes="32x32" href="/assets/favicon-32x32.png?v=2.1.8">
  <link rel="icon" type="image/png" sizes="16x16" href="/assets/favicon-16x16.png?v=2.1.8">

<!-- iPhone and iPad home-screen icons: regenerated from the exact uploaded darksalmon SVG -->
<link rel="apple-touch-icon" href="/assets/apple-touch-icon-180x180.png?v=2.1.8" sizes="180x180">
<link rel="apple-touch-icon" href="/assets/apple-touch-icon-167x167.png?v=2.1.8" sizes="167x167">
<link rel="apple-touch-icon" href="/assets/apple-touch-icon-152x152.png?v=2.1.8" sizes="152x152">
<link rel="apple-touch-icon" href="/assets/apple-touch-icon-120x120.png?v=2.1.8" sizes="120x120">
<!-- Android / iOS / installable web app -->
<link rel="manifest" href="/site.webmanifest?v=2.1.8">

<!-- Mobile application appearance -->
<meta name="theme-color" content="#e9967a">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="apple-mobile-web-app-title" content="jocarsa | asistencias">
<meta name="application-name" content="jocarsa | asistencias">
<script>
// iOS: suppress accidental double-tap / gesture zoom while keeping normal controls usable.
(function () {
  var lastTouchEnd = 0;
  document.addEventListener('touchend', function (event) {
    var now = Date.now();
    var target = event.target instanceof Element ? event.target : null;
    var editing = target && target.closest('input, textarea, select, [contenteditable="true"]');
    if (!editing && now - lastTouchEnd <= 320) {
      event.preventDefault();
    }
    lastTouchEnd = now;
  }, { passive: false });

  document.addEventListener('gesturestart', function (event) {
    event.preventDefault();
  }, { passive: false });
})();
</script>
</head>
<body data-area="{$safeArea}">
HTML;
    }

    public static function login(string $title, string $subtitle, string $error = '', string $csrf = ''): void
    {
        self::head($title, 'login');
        $safeTitle = ja_h($title);
        $safeSubtitle = ja_h($subtitle);
        $safeCsrf = ja_h($csrf);
        $logoUrl = '/assets/jocarsa-darksalmon.svg?v=2.1.8';
        $errorHtml = $error === '' ? '' : '<div class="notice danger" role="alert">' . ja_h($error) . '</div>';
        echo <<<HTML
<main class="ju-login-screen">
  <section class="ju-login-layout" aria-label="Acceso a jocarsa asistencias">
    <article class="ju-login-side chaflan">
      <div class="ju-login-brand">
        <div class="ju-login-logo-frame"><img src="{$logoUrl}" alt="Jocarsa"></div>
        <div><p class="ju-login-overline">jocarsa</p><h2>jocarsa | asistencias</h2><p>Control horario y organización laboral.</p></div>
      </div>
      <div class="ju-login-side-copy">
        <p class="ju-login-overline">Espacio de trabajo</p>
        <h3>Asistencia clara, segura y verificable</h3>
        <p>Gestiona la jornada laboral con una visión común para la organización y cada persona trabajadora.</p>
        <ul class="ju-login-feature-list">
          <li>Estado diario de toda la plantilla</li>
          <li>Correcciones y confirmaciones trazables</li>
          <li>Datos aislados por organización</li>
        </ul>
      </div>
      <div class="ju-login-context"><span>Área de acceso</span><strong>{$safeSubtitle}</strong></div>
    </article>
    <article class="ju-login-card chaflan">
      <div class="ju-login-card-head"><p class="eyebrow">Acceso seguro</p><h1>{$safeTitle}</h1><p>Introduce tus credenciales para acceder a {$safeSubtitle}.</p></div>
      {$errorHtml}
      <form method="post" class="ju-login-form">
        <input type="hidden" name="csrf" value="{$safeCsrf}">
        <label><span>Usuario</span><input name="username" autocomplete="username" required autofocus></label>
        <label><span>Contraseña</span><input type="password" name="password" autocomplete="current-password" required></label>
        <button class="button primary ju-login-submit" type="submit">Entrar</button>
      </form>
      <div class="ju-login-footer"><span>jocarsa | asistencias</span><strong>versión 2.1</strong></div>
    </article>
  </section>
</main>
</body>
</html>
HTML;
    }
}
```

### superadmin/import.php

```php
<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

use JA\Auth;
use JA\Csrf;
use JA\Database;
use JA\Session;
use JA\V1DumpImporter;

Session::start('superadmin');
Auth::requireSuperadmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ja_redirect('index.php?section=imports');
}

try {
    Csrf::check($_POST['csrf'] ?? null);
    $tenantId = filter_input(INPUT_POST, 'tenant_id', FILTER_VALIDATE_INT);
    if (!$tenantId) {
        throw new RuntimeException('Selecciona un tenant de destino.');
    }
    if (!isset($_FILES['dump']) || !is_array($_FILES['dump']) || (int)$_FILES['dump']['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('No se ha recibido correctamente el volcado SQL.');
    }
    if ((int)$_FILES['dump']['size'] > 50 * 1024 * 1024) {
        throw new RuntimeException('El volcado supera el límite web de 50 MB. Usa el importador de terminal.');
    }
    $originalName = basename((string)$_FILES['dump']['name']);
    if (strtolower(pathinfo($originalName, PATHINFO_EXTENSION)) !== 'sql') {
        throw new RuntimeException('El archivo debe tener extensión .sql.');
    }

    $summary = (new V1DumpImporter(Database::pdo()))->import((int)$tenantId, (string)$_FILES['dump']['tmp_name'], $originalName);
    $_SESSION['flash'] = ['type' => 'ok', 'message' => 'Importación V1 completada.', 'summary' => $summary];
} catch (Throwable $exception) {
    $_SESSION['flash'] = ['type' => 'danger', 'message' => $exception->getMessage()];
}

ja_redirect('index.php?section=imports');


```

### superadmin/index.php

```php
<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

use JA\Auth;
use JA\Csrf;
use JA\ContractCatalog;
use JA\Database;
use JA\Session;
use JA\Settings;
use JA\TenantContext;
use JA\View;

Session::start('superadmin');
$pdo = Database::pdo();
$loginError = '';

if (empty($_SESSION['superadmin'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        try {
            Csrf::check($_POST['csrf'] ?? null);
            if (Auth::loginSuperadmin(ja_post('username'), (string)($_POST['password'] ?? ''))) {
                ja_redirect('index.php');
            }
            $loginError = 'Usuario o contraseña incorrectos.';
        } catch (Throwable $exception) {
            $loginError = $exception->getMessage();
        }
    }
    View::login('Superadministración', 'Tenants, dominios e importaciones', $loginError, Csrf::token());
    exit;
}

$superadmin = Auth::requireSuperadmin();
$section = (string)($_GET['section'] ?? 'tenants');
if (!in_array($section, ['tenants', 'domains', 'admins', 'imports'], true)) {
    $section = 'tenants';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        Csrf::check($_POST['csrf'] ?? null);
        $action = ja_post('action');

        if ($action === 'create_tenant') {
            $name = ja_post('name');
            $slug = strtolower(ja_post('slug'));
            if ($name === '' || !preg_match('/^[a-z0-9][a-z0-9-]{1,98}[a-z0-9]$/', $slug)) {
                throw new RuntimeException('Indica un nombre y un slug válido de 3 a 100 caracteres.');
            }
            $pdo->beginTransaction();
            $statement = $pdo->prepare('INSERT INTO tenants (name, slug, status, timezone) VALUES (:name, :slug, :status, :timezone)');
            $statement->execute(['name' => $name, 'slug' => $slug, 'status' => ja_post('status', 'active'), 'timezone' => ja_post('timezone', 'Europe/Madrid')]);
            $createdTenantId = (int)$pdo->lastInsertId();
            ContractCatalog::seedTenant($pdo, $createdTenantId);
            Settings::seedTenant($pdo, $createdTenantId);
        } elseif ($action === 'update_tenant') {
            $tenantId = (int)($_POST['tenant_id'] ?? 0);
            $statement = $pdo->prepare('UPDATE tenants SET name=:name, status=:status, timezone=:timezone, updated_at=CURRENT_TIMESTAMP WHERE id=:id');
            $statement->execute(['name' => ja_post('name'), 'status' => ja_post('status'), 'timezone' => ja_post('timezone'), 'id' => $tenantId]);
        } elseif ($action === 'add_domain') {
            $tenantId = (int)($_POST['tenant_id'] ?? 0);
            $domain = TenantContext::normalizeDomain(ja_post('domain'));
            $isPrimary = isset($_POST['is_primary']) ? 1 : 0;
            $isLocal = TenantContext::isLocalHost($domain) ? 1 : 0;
            $pdo->beginTransaction();
            if ($isPrimary) {
                $clear = $pdo->prepare('UPDATE tenant_domains SET is_primary = 0 WHERE tenant_id = :tenant');
                $clear->execute(['tenant' => $tenantId]);
            }
            $statement = $pdo->prepare('INSERT INTO tenant_domains (tenant_id, domain, is_primary, is_localhost) VALUES (:tenant, :domain, :primary, :local)');
            $statement->execute(['tenant' => $tenantId, 'domain' => $domain, 'primary' => $isPrimary, 'local' => $isLocal]);
            $pdo->commit();
        } elseif ($action === 'delete_domain') {
            $domainId = (int)($_POST['domain_id'] ?? 0);
            $statement = $pdo->prepare('SELECT tenant_id FROM tenant_domains WHERE id = :id');
            $statement->execute(['id' => $domainId]);
            $tenantId = (int)$statement->fetchColumn();
            $count = $pdo->prepare('SELECT COUNT(*) FROM tenant_domains WHERE tenant_id = :tenant');
            $count->execute(['tenant' => $tenantId]);
            if ((int)$count->fetchColumn() <= 1) {
                throw new RuntimeException('No se puede eliminar el último dominio de un tenant.');
            }
            $delete = $pdo->prepare('DELETE FROM tenant_domains WHERE id = :id');
            $delete->execute(['id' => $domainId]);
        } elseif ($action === 'create_admin') {
            $password = (string)($_POST['password'] ?? '');
            if (strlen($password) < 10) {
                throw new RuntimeException('La contraseña del administrador debe tener al menos 10 caracteres.');
            }
            $statement = $pdo->prepare('INSERT INTO tenant_admins (tenant_id, full_name, email, username, password_hash, active) VALUES (:tenant, :name, :email, :username, :password, 1)');
            $statement->execute([
                'tenant' => (int)($_POST['tenant_id'] ?? 0),
                'name' => ja_post('full_name'),
                'email' => ja_post('email') ?: null,
                'username' => ja_post('username'),
                'password' => password_hash($password, PASSWORD_DEFAULT),
            ]);
        } elseif ($action === 'toggle_admin') {
            $statement = $pdo->prepare('UPDATE tenant_admins SET active=CASE active WHEN 1 THEN 0 ELSE 1 END, updated_at=CURRENT_TIMESTAMP WHERE id=:id');
            $statement->execute(['id' => (int)($_POST['admin_id'] ?? 0)]);
        } else {
            throw new RuntimeException('Operación no reconocida.');
        }

        if ($pdo->inTransaction()) {
            $pdo->commit();
        }
        $_SESSION['flash'] = ['type' => 'ok', 'message' => 'Cambios guardados correctamente.'];
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $_SESSION['flash'] = ['type' => 'danger', 'message' => $exception->getMessage()];
    }
    ja_redirect('index.php?section=' . urlencode($section));
}

$tenants = $pdo->query(
    'SELECT t.*,
      (SELECT COUNT(*) FROM tenant_domains d WHERE d.tenant_id = t.id) AS domain_count,
      (SELECT COUNT(*) FROM employees e WHERE e.tenant_id = t.id) AS employee_count,
      (SELECT COUNT(*) FROM tenant_admins a WHERE a.tenant_id = t.id) AS admin_count
     FROM tenants t ORDER BY t.name'
)->fetchAll();
$domains = $pdo->query('SELECT d.*, t.name AS tenant_name, t.slug FROM tenant_domains d JOIN tenants t ON t.id = d.tenant_id ORDER BY t.name, d.is_primary DESC, d.domain')->fetchAll();
$admins = $pdo->query('SELECT a.*, t.name AS tenant_name FROM tenant_admins a JOIN tenants t ON t.id = a.tenant_id ORDER BY t.name, a.full_name')->fetchAll();
$imports = $pdo->query('SELECT i.*, t.name AS tenant_name FROM import_runs i JOIN tenants t ON t.id = i.tenant_id ORDER BY i.id DESC LIMIT 50')->fetchAll();
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
$csrf = Csrf::token();

$labels = ['tenants' => 'Organizaciones', 'domains' => 'Dominios', 'admins' => 'Administradores', 'imports' => 'Importaciones V1'];
View::head('Superadmin · jocarsa | asistencias', 'superadmin');
?>
<header class="topbar">
  <div class="brand"><div class="brand-mark"><img src="https://static.jocarsa.com/logos/darksalmon.svg" alt="Jocarsa"></div><div><h1>jocarsa | asistencias</h1><p>control global multi-tenant</p></div></div>
  <div class="topbar-spacer"></div>
  <div class="topbar-user"><strong><?= ja_h($superadmin['name']) ?></strong><small>superadministración</small></div>
  <a class="button" href="logout.php">Salir</a>
</header>
<main class="app-shell">
  <aside class="sidebar">
    <p class="nav-title">Control global</p>
    <nav class="nav-list">
      <?php foreach ($labels as $key => $label): ?>
        <a class="<?= $section === $key ? 'active' : '' ?>" href="?section=<?= ja_h($key) ?>"><span class="nav-icon"><?= strtoupper($key[0]) ?></span><span><?= ja_h($label) ?></span></a>
      <?php endforeach; ?>
    </nav>
  </aside>
  <aside class="rail">
    <p class="rail-title">Tenants registrados</p>
    <ul class="item-list">
      <?php foreach ($tenants as $tenant): ?>
        <li><a href="?section=tenants#tenant-<?= (int)$tenant['id'] ?>"><span class="nav-icon"><?= strtoupper(substr((string)$tenant['name'], 0, 1)) ?></span><span><strong><?= ja_h($tenant['name']) ?></strong><small><?= ja_h($tenant['slug']) ?> · <?= (int)$tenant['employee_count'] ?> personas</small></span></a></li>
      <?php endforeach; ?>
    </ul>
  </aside>
  <section class="workspace">
    <div class="page-head"><div><p class="eyebrow">Superadmin</p><h2><?= ja_h($labels[$section]) ?></h2><p>Configuración global sin acceso a datos de otros tenants desde sus paneles.</p></div></div>

    <?php if (is_array($flash)): ?>
      <div class="notice <?= ja_h($flash['type'] ?? '') ?>"><?= ja_h($flash['message'] ?? '') ?></div>
      <?php if (!empty($flash['summary'])): ?><pre class="import-summary"><?= ja_h(json_encode($flash['summary'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre><?php endif; ?>
    <?php endif; ?>

    <?php if ($section === 'tenants'): ?>
      <div class="metrics">
        <div class="metric"><span>Tenants</span><strong><?= count($tenants) ?></strong></div>
        <div class="metric"><span>Activos</span><strong><?= count(array_filter($tenants, fn($t) => $t['status'] === 'active')) ?></strong></div>
        <div class="metric"><span>Dominios</span><strong><?= count($domains) ?></strong></div>
        <div class="metric"><span>Personas</span><strong><?= array_sum(array_column($tenants, 'employee_count')) ?></strong></div>
      </div>
      <article class="card"><h3>Crear organización</h3>
        <form method="post" class="grid-2"><input type="hidden" name="csrf" value="<?= ja_h($csrf) ?>"><input type="hidden" name="action" value="create_tenant">
          <label>Nombre<input name="name" required></label><label>Slug<input name="slug" pattern="[a-z0-9][a-z0-9-]{1,98}[a-z0-9]" required></label>
          <label>Zona horaria<input name="timezone" value="Europe/Madrid" required></label><label>Estado<select name="status"><option value="active">Activo</option><option value="suspended">Suspendido</option></select></label>
          <div><button class="button primary" type="submit">Crear tenant</button></div>
        </form>
      </article>
      <?php foreach ($tenants as $tenant): ?>
        <article class="card" id="tenant-<?= (int)$tenant['id'] ?>"><h3><?= ja_h($tenant['name']) ?> <span class="badge <?= $tenant['status'] === 'active' ? 'ok' : 'warning' ?>"><?= ja_h($tenant['status']) ?></span></h3>
          <form method="post" class="grid-2"><input type="hidden" name="csrf" value="<?= ja_h($csrf) ?>"><input type="hidden" name="action" value="update_tenant"><input type="hidden" name="tenant_id" value="<?= (int)$tenant['id'] ?>">
            <label>Nombre<input name="name" value="<?= ja_h($tenant['name']) ?>" required></label><label>Slug<input value="<?= ja_h($tenant['slug']) ?>" disabled></label>
            <label>Zona horaria<input name="timezone" value="<?= ja_h($tenant['timezone']) ?>" required></label><label>Estado<select name="status"><option value="active" <?= $tenant['status'] === 'active' ? 'selected' : '' ?>>Activo</option><option value="suspended" <?= $tenant['status'] === 'suspended' ? 'selected' : '' ?>>Suspendido</option></select></label>
            <div><button class="button" type="submit">Guardar</button></div>
          </form>
        </article>
      <?php endforeach; ?>
    <?php elseif ($section === 'domains'): ?>
      <article class="card"><h3>Asignar dominio</h3>
        <form method="post" class="inline-form"><input type="hidden" name="csrf" value="<?= ja_h($csrf) ?>"><input type="hidden" name="action" value="add_domain">
          <label>Tenant<select name="tenant_id" required><?php foreach ($tenants as $tenant): ?><option value="<?= (int)$tenant['id'] ?>"><?= ja_h($tenant['name']) ?></option><?php endforeach; ?></select></label>
          <label>Dominio<input name="domain" placeholder="empresa.example.com" required></label><label class="check"><input type="checkbox" name="is_primary" value="1"> Principal</label>
          <button class="button primary" type="submit">Añadir</button>
        </form>
        <p class="muted">Se aceptan `localhost`, `127.0.0.1`, `::1` y subdominios `.localhost` para desarrollo.</p>
      </article>
      <div class="table-wrap"><table><thead><tr><th>Tenant</th><th>Dominio</th><th>Tipo</th><th></th></tr></thead><tbody>
        <?php foreach ($domains as $domain): ?><tr><td><?= ja_h($domain['tenant_name']) ?></td><td><strong><?= ja_h($domain['domain']) ?></strong></td><td><?= $domain['is_primary'] ? '<span class="badge ok">Principal</span>' : '<span class="badge">Alias</span>' ?> <?= $domain['is_localhost'] ? '<span class="badge warning">Local</span>' : '' ?></td><td><form method="post"><input type="hidden" name="csrf" value="<?= ja_h($csrf) ?>"><input type="hidden" name="action" value="delete_domain"><input type="hidden" name="domain_id" value="<?= (int)$domain['id'] ?>"><button class="button danger small" data-confirm="¿Eliminar este dominio?" type="submit">Eliminar</button></form></td></tr><?php endforeach; ?>
      </tbody></table></div>
    <?php elseif ($section === 'admins'): ?>
      <article class="card"><h3>Crear administrador de tenant</h3>
        <form method="post" class="grid-2"><input type="hidden" name="csrf" value="<?= ja_h($csrf) ?>"><input type="hidden" name="action" value="create_admin">
          <label>Tenant<select name="tenant_id" required><?php foreach ($tenants as $tenant): ?><option value="<?= (int)$tenant['id'] ?>"><?= ja_h($tenant['name']) ?></option><?php endforeach; ?></select></label><label>Nombre completo<input name="full_name" required></label>
          <label>Usuario<input name="username" required></label><label>Email<input type="email" name="email"></label><label>Contraseña inicial<input type="password" name="password" minlength="10" required></label>
          <div><button class="button primary" type="submit">Crear administrador</button></div>
        </form>
      </article>
      <div class="table-wrap"><table><thead><tr><th>Tenant</th><th>Administrador</th><th>Usuario</th><th>Estado</th><th></th></tr></thead><tbody>
        <?php foreach ($admins as $admin): ?><tr><td><?= ja_h($admin['tenant_name']) ?></td><td><?= ja_h($admin['full_name']) ?><br><small class="muted"><?= ja_h($admin['email']) ?></small></td><td><?= ja_h($admin['username']) ?></td><td><span class="badge <?= $admin['active'] ? 'ok' : 'warning' ?>"><?= $admin['active'] ? 'Activo' : 'Inactivo' ?></span></td><td><form method="post"><input type="hidden" name="csrf" value="<?= ja_h($csrf) ?>"><input type="hidden" name="action" value="toggle_admin"><input type="hidden" name="admin_id" value="<?= (int)$admin['id'] ?>"><button class="button small" type="submit"><?= $admin['active'] ? 'Desactivar' : 'Activar' ?></button></form></td></tr><?php endforeach; ?>
      </tbody></table></div>
    <?php else: ?>
      <article class="card"><h3>Importar volcado de la versión 1</h3><p>El importador extrae únicamente datos compatibles, convierte contraseñas antiguas a hashes y no ejecuta procedimientos, vistas, `DEFINER` ni DDL del volcado.</p>
        <form action="import.php" method="post" enctype="multipart/form-data" class="grid-2"><input type="hidden" name="csrf" value="<?= ja_h($csrf) ?>">
          <label>Tenant de destino<select name="tenant_id" required><?php foreach ($tenants as $tenant): ?><option value="<?= (int)$tenant['id'] ?>"><?= ja_h($tenant['name']) ?></option><?php endforeach; ?></select></label><label>Volcado SQL<input type="file" name="dump" accept=".sql,text/plain,application/sql" required></label>
          <div><button class="button primary" type="submit">Importar V1</button></div>
        </form>
      </article>
      <div class="table-wrap"><table><thead><tr><th>Fecha</th><th>Tenant</th><th>Origen</th><th>Estado</th><th>Resumen</th></tr></thead><tbody>
        <?php foreach ($imports as $import): ?><tr><td><?= ja_h($import['started_at']) ?></td><td><?= ja_h($import['tenant_name']) ?></td><td><?= ja_h($import['source_name']) ?></td><td><span class="badge <?= $import['status'] === 'completed' ? 'ok' : ($import['status'] === 'failed' ? 'danger' : 'warning') ?>"><?= ja_h($import['status']) ?></span></td><td class="wrap"><small><?= ja_h($import['summary']) ?></small></td></tr><?php endforeach; ?>
      </tbody></table></div>
    <?php endif; ?>
  </section>
</main>
<script src="../assets/app.js"></script>
</body></html>

```

### superadmin/logout.php

```php
<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

JA\Session::start('superadmin');
JA\Session::destroy();
ja_redirect('index.php');



```

### tools/import_v1.php

```php
<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Este importador solo se ejecuta desde terminal.');
}

require_once dirname(__DIR__) . '/bootstrap.php';

use JA\Database;
use JA\V1DumpImporter;

$options = getopt('', ['tenant:', 'file:']);
$tenantRef = trim((string)($options['tenant'] ?? ''));
$file = (string)($options['file'] ?? '');
if ($tenantRef === '' || $file === '') {
    fwrite(STDERR, "Uso: php tools/import_v1.php --tenant=slug-o-dominio --file=/ruta/volcado.sql\n");
    exit(2);
}

$pdo = Database::pdo();
$statement = $pdo->prepare(
    'SELECT DISTINCT t.id, t.name FROM tenants t
     LEFT JOIN tenant_domains d ON d.tenant_id = t.id
     WHERE t.slug = :slug OR d.domain = :domain
     LIMIT 1'
);
$statement->execute(['slug' => strtolower($tenantRef), 'domain' => strtolower($tenantRef)]);
$tenant = $statement->fetch();
if (!$tenant) {
    fwrite(STDERR, "Tenant no encontrado.\n");
    exit(3);
}

try {
    $summary = (new V1DumpImporter($pdo))->import((int)$tenant['id'], $file);
    echo json_encode(['tenant' => $tenant['name'], 'summary' => $summary], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
} catch (Throwable $exception) {
    fwrite(STDERR, "Importación fallida: " . $exception->getMessage() . "\n");
    exit(1);
}


```

### tools/seed_demo2.php

```php
<?php
declare(strict_types=1);

/**
 * Demo tenant seeder for jocarsa | asistencias 2.1+
 * Run from the project root:
 *   php tools/seed_demo2.php
 *
 * Creates tenant demo2, admin demo2/demo2 and a substantial coherent dataset.
 * It intentionally refuses to overwrite an existing demo2 tenant.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("CLI only.\n");
}

require_once dirname(__DIR__) . '/bootstrap.php';

use JA\AttendanceAudit;
use JA\ContractCatalog;
use JA\Database;
use JA\Settings;

$pdo = Database::pdo();
$slug = 'demo2';

$exists = $pdo->prepare('SELECT id FROM tenants WHERE slug=:slug LIMIT 1');
$exists->execute(['slug' => $slug]);
if ($exists->fetchColumn()) {
    fwrite(STDERR, "Tenant 'demo2' already exists. Nothing changed.\n");
    fwrite(STDERR, "Delete/rename that tenant first if you really want to seed it again.\n");
    exit(2);
}

function ins(PDO $pdo, string $sql, array $params = []): int {
    $st = $pdo->prepare($sql);
    $st->execute($params);
    return (int)$pdo->lastInsertId();
}
function hm(int $minutes): string {
    $minutes = max(0, min(1439, $minutes));
    return sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
}
function weekdayDates(DateTimeImmutable $from, DateTimeImmutable $to): array {
    $out = [];
    for ($d = $from; $d <= $to; $d = $d->modify('+1 day')) {
        if ((int)$d->format('N') <= 5) $out[] = $d;
    }
    return $out;
}

$pdo->beginTransaction();
try {
    $tenantId = ins($pdo,
        "INSERT INTO tenants (name,slug,status,timezone) VALUES (:name,:slug,'active','Europe/Madrid')",
        ['name' => 'Demo2 · Empresa de demostración', 'slug' => $slug]
    );

    ContractCatalog::seedTenant($pdo, $tenantId);
    Settings::seedTenant($pdo, $tenantId);

    // Use demo2.localhost for a local demo without stealing the existing localhost tenant.
    ins($pdo, "INSERT INTO tenant_domains (tenant_id,domain,is_primary,is_localhost) VALUES (:t,'demo2.localhost',1,1)", ['t'=>$tenantId]);

    $adminId = ins($pdo,
        "INSERT INTO tenant_admins (tenant_id,full_name,email,username,password_hash,active) VALUES (:t,:n,:e,'demo2',:p,1)",
        ['t'=>$tenantId,'n'=>'Administrador Demo2','e'=>'admin.demo2@example.test','p'=>password_hash('demo2', PASSWORD_DEFAULT)]
    );
    $admin = ['id'=>$adminId,'full_name'=>'Administrador Demo2','username'=>'demo2'];

    // Work centres.
    $centers = [];
    foreach ([
        ['Central Valencia','C/ Colón 40, València',39.4699,-0.3763],
        ['Centro Paterna','Parc Tecnològic, Paterna',39.5486,-0.4611],
        ['Centro Torrent','Av. al Vedat, Torrent',39.4371,-0.4655],
    ] as $c) {
        $centers[] = ins($pdo, 'INSERT INTO work_centers (tenant_id,name,address,latitude,longitude) VALUES (?,?,?,?,?)', [$tenantId,...$c]);
    }

    // Attendance types by role.
    $typeIds = [];
    $types = [
        'ADMINISTRACION'=>[['Oficina',1],['Teletrabajo',0]],
        'DESARROLLO'=>[['Desarrollo',1],['Teletrabajo',0],['Reunión cliente',0]],
        'SOPORTE'=>[['Soporte',1],['Guardia',0],['Teletrabajo',0]],
        'COMERCIAL'=>[['Oficina',1],['Visita comercial',0],['Teletrabajo',0]],
    ];
    foreach ($types as $role=>$rows) {
        foreach ($rows as [$name,$default]) {
            $id = ins($pdo, 'INSERT INTO attendance_types (tenant_id,role,name,is_default,active) VALUES (?,?,?,?,1)', [$tenantId,$role,$name,$default]);
            $typeIds[$role][$name] = $id;
            if ($default) $typeIds[$role]['_default'] = $id;
        }
    }

    // Employees and front credentials. All passwords are deliberately easy demo credentials.
    $people = [
        ['Ana','Martínez','Administración','ADMINISTRACION',0,'ana.martinez','ana2026'],
        ['Carlos','Soler','Administración','ADMINISTRACION',0,'carlos.soler','carlos2026'],
        ['Laura','García','Desarrollo','DESARROLLO',0,'laura.garcia','laura2026'],
        ['David','Navarro','Desarrollo','DESARROLLO',1,'david.navarro','david2026'],
        ['Marta','Ferrer','Desarrollo','DESARROLLO',1,'marta.ferrer','marta2026'],
        ['Pablo','Ruiz','Desarrollo','DESARROLLO',0,'pablo.ruiz','pablo2026'],
        ['Lucía','Torres','Soporte','SOPORTE',1,'lucia.torres','lucia2026'],
        ['Javier','Gil','Soporte','SOPORTE',2,'javier.gil','javier2026'],
        ['Elena','Vidal','Soporte','SOPORTE',0,'elena.vidal','elena2026'],
        ['Sergio','Molina','Comercial','COMERCIAL',0,'sergio.molina','sergio2026'],
        ['Paula','Ortega','Comercial','COMERCIAL',2,'paula.ortega','paula2026'],
        ['Miguel','Blasco','Comercial','COMERCIAL',1,'miguel.blasco','miguel2026'],
    ];

    $employees = [];
    foreach ($people as $i=>$p) {
        [$first,$last,$dept,$role,$centerIndex,$username,$password] = $p;
        $employeeId = ins($pdo,
            'INSERT INTO employees (tenant_id,nombre,apellido,departamento,work_center_id,action_radius,role,active) VALUES (?,?,?,?,?,250,?,1)',
            [$tenantId,$first,$last,$dept,$centers[$centerIndex],$role]
        );
        $userId = ins($pdo,
            "INSERT INTO users (tenant_id,employee_id,name,email,username,password_hash,role,active,conciliacion) VALUES (?,?,?,?,?,?,'worker',1,?)",
            [$tenantId,$employeeId,"$first $last",$username.'@demo2.example.test',$username,password_hash($password,PASSWORD_DEFAULT),($i%3===0?1:0)]
        );
        $employees[] = ['id'=>$employeeId,'user_id'=>$userId,'first'=>$first,'last'=>$last,'dept'=>$dept,'role'=>$role,'username'=>$username,'password'=>$password];
    }

    // Attach a varied contract to every employee using the system catalogue already seeded.
    $catalog = $pdo->prepare('SELECT id,name FROM contract_types WHERE tenant_id=:t AND active=1 ORDER BY is_system DESC,id');
    $catalog->execute(['t'=>$tenantId]);
    $contractTypes = $catalog->fetchAll();
    if (!$contractTypes) throw new RuntimeException('No contract types were seeded.');
    foreach ($employees as $i=>&$e) {
        $ct = $contractTypes[$i % count($contractTypes)];
        $salary = 22000 + (($i % 6) * 2500);
        $workday = in_array($i,[1,7,10],true) ? 'part_time' : 'full_time';
        $arrangement = ['on_site','hybrid','remote'][$i%3];
        $signed = (new DateTimeImmutable('2026-01-01'))->modify('-'.($i*37).' days')->format('Y-m-d');
        $contractId = ins($pdo,
            'INSERT INTO contracts (tenant_id,employee_id,contract_type_id,contract_type,signed_date,end_date,annual_salary,workday_type,work_arrangement,notes) VALUES (?,?,?,?,?,NULL,?,?,?,?)',
            [$tenantId,$e['id'],(int)$ct['id'],(string)$ct['name'],$signed,$salary,$workday,$arrangement,'Contrato de demostración con datos ficticios.']
        );
        $pdo->prepare('UPDATE employees SET contract_type_id=:ct WHERE id=:id AND tenant_id=:t')->execute(['ct'=>(int)$ct['id'],'id'=>$e['id'],'t'=>$tenantId]);
        $e['contract_id']=$contractId;
    }
    unset($e);

    // Weekly schedules. Weekday follows ISO 1=Mon ... 7=Sun.
    foreach ($employees as $i=>$e) {
        $partTime = in_array($i,[1,7,10],true);
        for ($wd=1; $wd<=5; $wd++) {
            if ($partTime && $wd===5) continue;
            $start = $partTime ? '09:00' : (($i%4===0)?'08:00':'08:30');
            $end   = $partTime ? '14:00' : (($i%4===0)?'16:00':'16:30');
            ins($pdo, 'INSERT INTO schedules (tenant_id,employee_id,weekday,valid_from,valid_until,start_time,end_time,attendance_type_id) VALUES (?,?,?,\'2026-01-01\',NULL,?,?,?)',
                [$tenantId,$e['id'],$wd,$start,$end,$typeIds[$e['role']]['_default']]);
        }
    }

    // Public holidays useful in 2026 demo views.
    foreach ([
        ['2026-01-01','Año Nuevo','Festivo nacional'],['2026-01-06','Epifanía del Señor','Festivo nacional'],
        ['2026-03-19','San José','Festivo autonómico/local de demostración'],['2026-04-03','Viernes Santo','Festivo'],
        ['2026-05-01','Fiesta del Trabajo','Festivo nacional'],['2026-08-15','Asunción','Festivo nacional'],
        ['2026-10-09','Día de la Comunitat Valenciana','Festivo autonómico'],['2026-10-12','Fiesta Nacional de España','Festivo nacional'],
        ['2026-11-01','Todos los Santos','Festivo nacional'],['2026-12-06','Día de la Constitución','Festivo nacional'],
        ['2026-12-08','Inmaculada Concepción','Festivo nacional'],['2026-12-25','Navidad','Festivo nacional'],
    ] as $h) ins($pdo,'INSERT INTO holidays (tenant_id,holiday_date,name,description) VALUES (?,?,?,?)',[$tenantId,...$h]);

    // Attendance history: ~3 months, with realistic punctuality variation and some missed days.
    $holidaySet = array_flip(['2026-08-15']);
    $dates = weekdayDates(new DateTimeImmutable('2026-06-01'), new DateTimeImmutable('2026-09-01'));
    foreach ($employees as $i=>$e) {
        foreach ($dates as $d) {
            $date = $d->format('Y-m-d');
            if (isset($holidaySet[$date])) continue;
            // Sparse deterministic absences so charts are not perfectly uniform.
            if ((crc32($date.$e['id']) % 29) === 0) continue;
            $base = in_array($i,[1,7,10],true) ? 9*60 : (($i%4===0)?8*60:8*60+30);
            $duration = in_array($i,[1,7,10],true) ? 5*60 : 8*60;
            $jitter = (int)(crc32($e['username'].$date) % 23) - 8; // -8..+14 min
            $startMin = $base + $jitter;
            $endMin = $startMin + $duration + ((int)(crc32($date.'end'.$i)%21)-10);
            $type = $typeIds[$e['role']]['_default'];
            // Occasional telework/field work where available.
            if (($d->format('N')==='3') && isset($typeIds[$e['role']]['Teletrabajo']) && ($i%2===0)) $type=$typeIds[$e['role']]['Teletrabajo'];
            if ($e['role']==='COMERCIAL' && $d->format('N')==='4') $type=$typeIds[$e['role']]['Visita comercial'];
            AttendanceAudit::createManualAttendance($pdo,$tenantId,$e['id'],$date,hm($startMin),hm($endMin),$type,$admin,'Carga inicial de datos de demostración');
        }
    }

    // Vacations: approved, pending, past and future.
    $vac = [
        [0,'2026-07-13','2026-07-24',1],[1,'2026-08-03','2026-08-14',1],[2,'2026-08-17','2026-08-28',1],
        [3,'2026-09-14','2026-09-18',1],[4,'2026-10-05','2026-10-09',0],[5,'2026-12-21','2026-12-31',1],
        [6,'2026-06-22','2026-06-26',1],[7,'2026-09-21','2026-09-25',0],[8,'2026-11-02','2026-11-06',1],
        [9,'2026-07-27','2026-08-07',1],[10,'2026-10-19','2026-10-23',1],[11,'2026-12-07','2026-12-11',0],
    ];
    foreach($vac as [$idx,$from,$to,$approved]) ins($pdo,'INSERT INTO vacations (tenant_id,employee_id,start_date,end_date,approved) VALUES (?,?,?,?,?)',[$tenantId,$employees[$idx]['id'],$from,$to,$approved]);

    // Absences in different states. Worker-origin rows can be approved/rejected directly.
    $abs = [
        [0,'2026-06-11','09:00','2026-06-11','11:30','Médica','Consulta médica','approved',1],
        [2,'2026-07-02',null,'2026-07-02',null,'Asuntos propios','Gestión personal','approved',1],
        [4,'2026-08-26','12:00','2026-08-26','16:30','Médica','Prueba médica','approved',1],
        [6,'2026-09-03','10:00','2026-09-03','12:00','Asuntos propios','Trámite administrativo','pending',0],
        [8,'2026-08-10',null,'2026-08-12',null,'Enfermedad','Proceso leve','approved',1],
        [10,'2026-07-16','08:30','2026-07-16','10:30','Médica','Consulta','rejected',0],
    ];
    foreach($abs as [$idx,$sd,$st,$ed,$et,$type,$reason,$status,$approved])
        ins($pdo,"INSERT INTO absences (tenant_id,employee_id,start_date,start_time,end_date,end_time,absence_type,reason,status,approved,source,employee_confirmation_status) VALUES (?,?,?,?,?,?,?,?,?,?,'worker','not_required')",[$tenantId,$employees[$idx]['id'],$sd,$st,$ed,$et,$type,$reason,$status,$approved]);

    // Incidents.
    foreach ([
        [1,'Retraso','2026-06-18','Entrada 18 minutos después del horario previsto.'],
        [3,'Olvido de fichaje','2026-07-08','Se regularizó mediante revisión administrativa.'],
        [5,'Salida anticipada','2026-08-04','Salida anticipada comunicada al responsable.'],
        [7,'Incidencia técnica','2026-08-19','El terminal de fichaje no tenía conexión.'],
        [9,'Desplazamiento','2026-08-27','Jornada iniciada durante visita comercial.'],
    ] as [$idx,$type,$date,$notes]) ins($pdo,'INSERT INTO incidents (tenant_id,employee_id,type,incident_date,notes) VALUES (?,?,?,?,?)',[$tenantId,$employees[$idx]['id'],$type,$date,$notes]);

    // Internal communications.
    $msgTypes=[];
    foreach ([['Información','#e9967a','📢'],['Urgente','#b22222','⚠️'],['RRHH','#4682b4','👥'],['Celebración','#6b8e23','🎉']] as $m)
        $msgTypes[$m[0]]=ins($pdo,'INSERT INTO internal_message_types (tenant_id,name,color,emoji,active) VALUES (?,?,?,?,1)',[$tenantId,...$m]);
    foreach ([
        ['Información','2026-09-01',7,'Bienvenida al nuevo periodo. Recordad revisar horarios y solicitudes pendientes.'],
        ['RRHH','2026-09-02',10,'Ya está disponible la planificación de vacaciones del último trimestre.'],
        ['Urgente','2026-09-04',2,'Mantenimiento programado del sistema interno el viernes a partir de las 18:00.'],
        ['Celebración','2026-09-07',3,'Desayuno de equipo el lunes a las 10:30 en la sede central.'],
    ] as [$type,$date,$days,$message]) ins($pdo,'INSERT INTO internal_messages (tenant_id,message_type_id,start_date,highlight_days,message,active,created_by_admin_id) VALUES (?,?,?,?,?,1,?)',[$tenantId,$msgTypes[$type],$date,$days,$message,$adminId]);

    $pdo->commit();

    echo "\nDemo2 created successfully.\n";
    echo "Tenant: demo2 · Demo2 · Empresa de demostración\n";
    echo "Local host/domain: demo2.localhost\n";
    echo "Admin: demo2 / demo2\n\n";
    echo "Employee credentials:\n";
    foreach ($employees as $e) printf("  %-22s  %-18s / %s\n", $e['first'].' '.$e['last'], $e['username'], $e['password']);
    echo "\nNote: all emails and business data are fictitious demo data.\n";
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    fwrite(STDERR, "ERROR: {$e->getMessage()}\n");
    exit(1);
}
```

### .gitignore

```text
/.env
/database/*.sqlite
/database/*.sqlite-shm
/database/*.sqlite-wal
/database/*.sqlite-journal
/database/*.key
```

### .htaccess

```text
Options -Indexes
DirectoryIndex index.php

<FilesMatch "^(\.env|\.env\.example)$">
    Require all denied
</FilesMatch>

RewriteEngine On
RewriteRule ^(?:src|database|tools)(?:/|$) - [F,L]

```

### bootstrap.php

```php
<?php
declare(strict_types=1);

define('JA_ROOT', __DIR__);

require_once JA_ROOT . '/src/Env.php';
JA\Env::load(JA_ROOT . '/.env');

date_default_timezone_set((string)JA\Env::get('APP_TIMEZONE', 'Europe/Madrid'));

spl_autoload_register(static function (string $class): void {
    $prefix = 'JA\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $file = JA_ROOT . '/src/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($file)) {
        require_once $file;
    }
});

require_once JA_ROOT . '/src/helpers.php';

if (PHP_SAPI !== 'cli') {
    JA\ApplicationAudit::registerRequest();
}

set_exception_handler(static function (Throwable $exception): void {
    $debug = JA\Env::bool('APP_DEBUG', false);
    error_log('[jocarsa-asistencias-v2] ' . $exception);

    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, '[jocarsa-asistencias-v2] ' . $exception->getMessage() . PHP_EOL);
        exit(1);
    }

    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/html; charset=utf-8');
    }

    $message = $debug ? $exception->getMessage() : 'Se ha producido un error interno.';
    echo '<!doctype html><html lang="es"><meta charset="utf-8"><title>Error</title>';
    echo '<style>body{font-family:system-ui;margin:0;background:#2c1d1b;color:#fff;display:grid;place-items:center;min-height:100vh}main{max-width:42rem;padding:2rem;border-left:8px solid darksalmon;background:#fff;color:#2c1d1b}a{color:#a35149}</style>';
    echo '<main><h1>No se pudo completar la operación</h1><p>' . ja_h($message) . '</p><p><a href="javascript:history.back()">Volver</a></p></main></html>';
});


```

### CHANGELOG.md

```markdown
# Changelog

Todos los cambios relevantes de `jocarsa | asistencias` se documentan desde ahora en este único archivo.

## [2.1] - 2026-08-28

### Añadido

- Menú inferior desplegable en el front móvil, con la sección activa visible, apertura hacia arriba, cierre al seleccionar, al tocar fuera o mediante Escape.
- Logotipo corporativo servido desde `https://static.jocarsa.com/logos/darksalmon.svg` en el acceso y las cabeceras de administración, personal y superadministración.
- Favicon SVG común basado en el mismo recurso corporativo.
- Pantalla de acceso inspirada en `jocarsa-iu`, con composición dividida, tarjeta de credenciales, panel informativo, chaflanes y respuesta adaptable a móvil.
- Saludo al administrador conectado y acceso directo para cerrar sesión en la parte derecha del encabezado.
- Conteo inclusivo de días en cada barra de la aplicación **Temporalización vacaciones**.
- Desplazamiento horizontal automático del calendario anual para centrar el día actual al cargar la página.
- Interruptor para mostrar únicamente empleados con vacaciones y control deslizante de zoom horizontal en **Temporalización vacaciones**.
- Aplicación **Hoy**, establecida como pantalla inicial de administración, con empleados previstos, fichajes cerrados, jornadas en curso, personas sin iniciar, próximas entradas y ausencias justificadas.
- Vacaciones, festivos y ausencias, además de los fichajes, en la vista mensual de **Mis jornadas** del portal del empleado.
- Evento configurable **Fichaje fuera del horario establecido** dentro de **Configuración → Eventos que generan una notificación**.
- Avisos por email a la dirección designada cuando una persona inicia o finaliza una jornada fuera de todas sus franjas horarias vigentes.
- Horas y minutos opcionales para el inicio y el final de una ausencia. Si no se indican horas, el periodo se interpreta como día completo.
- Ausencias sin fecha final para bajas o periodos cuya finalización todavía se desconoce.
- Creación de ausencias desde administración mediante acuerdo bilateral: la persona trabajadora debe aceptarlas o rechazarlas con su contraseña.
- Adjuntos PDF, JPG o JPEG de hasta 10 MB en las solicitudes de ausencia del empleado y en las propuestas administrativas.
- Descarga autenticada de adjuntos, con comprobación de usuario, empleado, organización y pertenencia del registro.
- Almacenamiento de adjuntos fuera del acceso web directo, dentro de la zona protegida de datos.

### Cambiado

- La navegación inferior conserva la barra completa en escritorio y se reduce a un control compacto en pantallas de hasta 720 px.
- Tarjetas, acciones de fichaje, selector de vista y espaciados del front se adaptan mejor a pantallas estrechas y a la zona segura inferior del dispositivo.
- El acceso administrativo y el del personal recuperan `jocarsa-asistencias.png` como única imagen de fondo, sin degradados superpuestos.
- Los dos paneles de acceso quedan alineados con la misma altura en escritorio.
- Las aplicaciones administrativas se distribuyen en su ruta real `admin/applications/`, evitando que una instalación conserve módulos antiguos.
- El conteo de días de vacaciones aparece al principio de cada barra para permanecer visible aunque el final del periodo quede fuera de la ventana.
- El centrado del día actual y el zoom de vacaciones se ejecutan desde el JavaScript global después de calcular el ancho real de las columnas.
- La tabla `absences` incorpora horas opcionales, final abierto, nombre del adjunto, origen de la solicitud y datos de confirmación del empleado.
- Las cuadrículas, resúmenes, estadísticas y correos interpretan correctamente las ausencias sin fecha final.
- Las ausencias propuestas por administración solo pueden resolverse mediante la decisión del empleado; administración no puede sustituir posteriormente esa decisión.
- Las solicitudes con archivos usan formularios `multipart/form-data`, manteniendo la protección CSRF.
- La migración automática conserva todas las ausencias existentes y actualiza internamente SQLite al nivel de esquema `11`.

### Seguridad

- Validación real del tipo MIME de los adjuntos mediante `finfo`.
- Nombres internos aleatorios para impedir colisiones y adivinación de rutas.
- Cabeceras de descarga privada y `X-Content-Type-Options: nosniff`.
- Reautenticación con contraseña antes de aceptar o rechazar una ausencia creada por administración.

## [2.0] - Base anterior consolidada

### Arquitectura y administración

- Aplicación PHP 8.2+ con SQLite, modo WAL y aislamiento multi-tenant mediante `tenant_id` y dominio.
- Ámbitos separados para empleados, administración de cada organización y superadministración.
- Gestión de organizaciones, dominios, administradores, empleados, usuarios, centros de trabajo, contratos, festivos, incidencias, horarios, ausencias y vacaciones.
- Interfaz administrativa basada en `jocarsa-iu`, búsqueda contextual, favoritos y tablas con filtrado y paginación en servidor.
- Importación repetible desde la versión heredada, manteniendo `legacy_id`, identidades existentes y contraseñas ya configuradas.

### Jornadas y trazabilidad

- Inicio y finalización de jornadas desde el portal del personal.
- Tipos de fichaje configurables por rol, con valores activos y predeterminados.
- Registro manual de jornadas por administración.
- Correcciones bilaterales: administración propone y el trabajador decide, o el trabajador solicita y administración decide.
- Cadena de integridad HMAC-SHA-256 por organización, eventos append-only y verificación del estado actual.
- Auditoría global inmutable de peticiones, acciones, actores, resultados, IP, navegador, duración y memoria.
- Histórico de jornadas en tabla y calendario mensual.

### Horarios y planificación

- Editor visual de horarios semanales en bloques de treinta minutos.
- Segmentos con vigencia por fecha y asociación a tipos de fichaje.
- Cuadrícula mensual de asistencia, calendario semanal, resumen de sesiones y estadísticas por empleado.
- Calendario anual de vacaciones por persona, con filtros, navegación por años, barras aprobadas o pendientes y edición directa.
- Catálogo de contratos y gestión de llamamientos para contratos fijo-discontinuos.

### Comunicación y configuración

- Comunicaciones internas laborales con tipos, colores, fechas y avisos emergentes.
- Indicador informativo de fichaje dentro o fuera de la empresa mediante IPv4/IPv6 configurables.
- Configuración independiente por organización mediante `tenant_settings`.
- Notificaciones por email mediante SMTP autenticado y STARTTLS.
- Aviso configurable para nuevas solicitudes de ausencia, sin revertir la solicitud cuando falla el correo.
- Opciones de conciliación y permisos para que el personal solicite cambios en sus jornadas.
```

### index.php

```php
<?php
declare(strict_types=1);

header('Location: front/');
exit;



```

### README-BAJAS.md

```markdown
# Patch: entidad Bajas

Copiar el contenido de este directorio sobre la raíz de `jocarsa-asistencias`, conservando las rutas.

## Cambios

- Nueva tabla SQLite `sick_leaves` / entidad administrativa **Bajas**.
- Alta, edición y eliminación de bajas desde Administración > Tablas > Bajas.
- La baja entra en vigor inmediatamente al guardarla: no existe aceptación/rechazo del trabajador.
- Periodo con horas opcionales y fecha final opcional, para bajas todavía abiertas.
- Motivo/observaciones y adjunto PDF/JPG opcional de hasta 10 MB.
- Nueva sección de solo lectura **Mis bajas** en el portal del empleado.
- Las bajas aparecen también en el calendario mensual de **Mis jornadas**, junto a fichajes, vacaciones, festivos y ausencias.
- Descarga de adjuntos aislada por tenant y empleado.
- Migración automática de SQLite a `PRAGMA user_version = 12`; no borra las ausencias existentes.

## Despliegue

No es necesario ejecutar SQL manualmente. Al cargar la aplicación, `src/Database.php` ejecuta la migración automática y `database/schema.sql` crea la tabla `sick_leaves` si todavía no existe.

Para una instalación nueva puede seguir utilizándose `php database/install.php`.

## 2.2.1 - corrección de layout administrativo
Se corrige el desbordamiento horizontal de la columna central al crecer el listado de tablas. El panel administrativo vuelve a ocupar todo el ancho disponible y la columna de contenido no queda comprimida.
```

### README-FIX.md

```markdown
# Fix 2.2.4

Corrige únicamente la unión visual entre la tarjeta seleccionada de la
segunda columna y el panel derecho.

La regla 2.2.3 daba a la tarjeta activa `width: calc(100% + 12px)`, pero otra
regla posterior/general la limitaba con `max-width:100%`. Por eso seguían
quedando visibles los 12 px de fondo de la segunda columna.

Ahora la tarjeta activa usa `max-width:none`, de modo que ocupa también el
padding derecho de `#mensajes` y termina exactamente contra `#contenido`.

No modifica Bajas, base de datos ni lógica PHP.
```

### README-LAYOUT-FIX.md

```markdown
# Layout fix 2.2.2

Este parche parte exactamente del estado de proyecto adjuntado el 03/09/2026.

Cambios:
- fuerza la administración a ocupar el viewport completo;
- fija `#topbar` a todo el ancho;
- fija `#app.iu-admin-shell` entre la cabecera y el borde inferior;
- conserva las tres columnas sin que `#mensajes` pueda ensanchar el grid;
- elimina el crecimiento físico de los elementos activos;
- mantiene scroll vertical independiente;
- conserva el comportamiento responsive por debajo de 900 px;
- incrementa el cache-buster del CSS a `2.2.2`.

Copiar sobre la raíz del proyecto conservando las rutas.
```

### README-MOBILE-UPDATE.md

```markdown
# jocarsa | asistencias — mobile/iOS icon update 2.1.8

This patch uses the user-supplied `darksalmon.svg` as the only source for every application logo/icon.

Replace these project files/folders:

- `src/View.php`
- `site.webmanifest`
- all files included under `assets/`

Regenerated from the exact SVG:

- `android-chrome-192x192.png`
- `android-chrome-512x512.png`
- `android-maskable-192x192.png`
- `android-maskable-512x512.png`
- `apple-touch-icon-120x120.png`
- `apple-touch-icon-152x152.png`
- `apple-touch-icon-167x167.png`
- `apple-touch-icon-180x180.png`
- `favicon-16x16.png`
- `favicon-32x32.png`
- `blanco.png`
- `jocarsa-darksalmon.svg`
- `jocarsa_darksalmon.svg`
- `jocarsa _ white.svg`

`jocarsa-asistencias.png` remains untouched because it is the login background.

After deploying on iOS, delete the existing Home Screen app and add it again from Safari because iOS aggressively caches touch icons.
```

### README-PATCH.md

```markdown
# Patch reuniones + estadísticas + etiquetas de horario

Copiar el contenido sobre la raíz de `jocarsa-asistencias`. No borra ni modifica tablas existentes. La nueva tabla `meetings` se crea de forma aditiva con `CREATE TABLE IF NOT EXISTS`.

Cambios: etiquetas de tipo en bloques de horario; total de horas por tipo; filtros manual/semana/mes; nueva aplicación Reuniones con semanas reales y cómputo estadístico como trabajo fuera del horario establecido.
```

### README.md

````markdown
# jocarsa | asistencias · versión 2.1

## Novedades 2.1

Las ausencias pueden ser de día completo o incluir hora y minuto, tener una fecha final todavía desconocida y adjuntar un justificante PDF/JPG. Administración también puede proponer ausencias, que solo se aprueban cuando el empleado las confirma desde su portal. El sistema de notificaciones añade avisos configurables para los fichajes realizados fuera de las franjas horarias vigentes. El calendario mensual del empleado integra fichajes, vacaciones, festivos y ausencias, y la temporalización anual incorpora filtro de empleados con vacaciones, zoom horizontal y centrado automático en el día actual.

Aplicación PHP 8.2+ y SQLite multi-tenant por dominio. No requiere servidor MySQL, usuario de base de datos ni creación manual de una base. Mantiene los tres ámbitos consolidados en la versión 2.0:

- `front/`: portal móvil de la persona trabajadora (fichaje por tipo y rol, jornadas, correcciones pactadas, ausencias y llamamientos notificados).
- `admin/`: administración aislada de la organización resuelta por el dominio, con la estructura completa de `jocarsa-iu` (grupos, elementos y panel de trabajo).
- `superadmin/`: gestión global de organizaciones, dominios, administradores e importaciones V1.

El diseño usa `jocarsa-iu`, con estructura de navegación/panel, cortes en chaflán y una paleta construida con `darksalmon` y sus sombras. El acceso emplea una composición dividida y alineada de panel informativo y tarjeta de credenciales sobre la ilustración `assets/jocarsa-asistencias.png`, sin degradados CSS superpuestos. El logotipo y el favicon comunes se sirven desde `https://static.jocarsa.com/logos/darksalmon.svg`.

En el portal del personal, la navegación inferior se presenta como barra completa en escritorio. En pantallas de hasta 720 px se convierte en un menú compacto desplegable hacia arriba, mostrando siempre la sección actual y respetando la zona segura inferior del dispositivo.

### Aplicaciones de la versión 2.0

El grupo **Aplicaciones** conserva las herramientas de la versión original y añade una cuadrícula diaria inspirada en `jocarsa-asistencia`, adaptadas a SQLite y con todas sus consultas aisladas mediante `tenant_id`:

- `hoy`: pantalla inicial de administración con una perspectiva operativa del día: personas que debían fichar, jornadas cerradas, fichajes en curso, empleados sin iniciar, entradas posteriores y situaciones justificadas.
- `cuadricula_asistencia`: matriz mensual de personas y días, con búsqueda por teclado, filtros, preferencias visuales y accesos seguros al alta o corrección auditada.
- `calendario_semanal`: horarios activos de toda la plantilla por semana.
- `estadisticas_empleados`: filtros, KPIs, evolución, puntualidad, incidencias, ausencias y fichajes por persona.
- `horario_semanal`: editor gráfico en bloques de 30 minutos con vigencia por fechas.
- `resumen_sesiones`: matriz semanal con asistencias, jornadas abiertas, errores, ausencias, festivos y días sin horario.
- `temporalizacion_vacaciones`: calendario anual horizontal con una fila por empleado, columnas diarias y barras para cada periodo vacacional.

La interfaz mantiene búsqueda contextual y favoritos locales. Las vistas y los procedimientos almacenados de V1 no se incluyen porque SQLite no los necesita para estas funciones.

### Contratos y llamamientos

La versión 2.0 incorporó un catálogo estructurado con las cuatro grandes modalidades vigentes y sus principales submodalidades: indefinido ordinario, fijo-discontinuo, adscrito a obra, incentivado, las variantes de circunstancias de la producción y sustitución, los dos contratos formativos y los supuestos especiales indicados por el SEPE. Cada tenant recibe automáticamente 19 modalidades de partida y puede añadir modalidades internas sin alterar los códigos oficiales.

La jornada (`tiempo completo` o `tiempo parcial`) y la prestación (`presencial`, `a distancia` o `híbrida`) se guardan como condiciones del contrato y no como modalidades contractuales independientes. Los contratos importados de la aplicación heredada conservan su texto histórico; `INDEFINIDO`, `Fijo discontinuo` y `Prácticas` se enlazan automáticamente con el catálogo de la versión 2.0.

La tabla **Llamamientos** solo admite contratos marcados como fijo-discontinuos. Cada registro incluye periodo de actividad, fecha y medio de aviso, estado y condiciones. SQLite bloquea asociaciones con otro tipo de contrato, cruces entre tenants y periodos activos solapados. Las previsiones todavía no notificadas pueden eliminarse; después de la notificación deben conservarse y, si procede, marcarse como canceladas. La persona trabajadora ve en `front/` sus llamamientos ya notificados.

Referencias de diseño: [modalidades y modelos del SEPE](https://www.sepe.es/HomeSepe/empresas/Contratos-de-trabajo/modelos-contrato.html) y artículos 11, 12, 15 y 16 del [Estatuto de los Trabajadores](https://www.boe.es/buscar/act.php?id=BOE-A-2015-11430).

### Tipos de fichaje por rol

La versión 2.0 añadió la entidad **Tipos de fichaje**. Cada organización define sus propios tipos y los vincula a un rol de empleado. Cuando una persona entra en el front, solo ve los tipos activos de su rol como botones grandes adaptados a móvil. Si el administrador marca uno como predeterminado, ese botón aparece seleccionado; si no hay predeterminado, la persona debe elegir antes de iniciar la jornada.

La organización local inicial recibe `Docencia` y `Complementarias` para el rol `DOCENTES`. Los demás tenants parten sin tipos para que cada empresa construya su catálogo. Si un rol no tiene tipos activos, se mantiene el fichaje clásico sin categoría para conservar compatibilidad con V1.

El tipo elegido se guarda al iniciar la jornada, se muestra en el estado abierto, el histórico, administración y estadísticas, y se incorpora al estado autenticado de la cadena HMAC. Una corrección administrativa también puede proponer cambiar el tipo, sujeto a aceptación del trabajador. Una vez utilizado, el nombre y el rol del tipo quedan bloqueados para que no cambie retrospectivamente el significado de jornadas históricas; todavía puede activarse, desactivarse o dejar de ser predeterminado.

### Solicitudes de corrección iniciadas por el trabajador

La versión 2.0 también incorporó el flujo inverso. Desde la sección **Solicitar cambios** del front, una persona puede proponer fecha, entrada, salida o tipo nuevos para una de sus jornadas cerradas y confirmar la solicitud con su contraseña actual. El registro vigente no cambia en ese momento.

Administración recibe la propuesta en la aplicación **Solicitudes del personal** y puede aprobarla o rechazarla. Solo la aprobación actualiza la jornada. Propuesta, decisión, valores anterior/nuevo, motivo, comentarios, actores, fechas y huellas se incorporan a la misma cadena HMAC de asistencia. No puede coexistir más de una corrección pendiente sobre una jornada, con independencia de quién la haya iniciado.

La capacidad se activa o desactiva por tenant desde **Configuración → Configuración general**. Se guarda en la tabla extensible `tenant_settings`; desactivarla impide solicitudes nuevas, pero no oculta ni bloquea la resolución de las ya existentes.

### Auditoría global de la aplicación

La versión 2.0 registra automáticamente cada petición PHP en `application_audit_log`: fecha, tenant, área, actor, método, ruta, acción, código HTTP, resultado, IP, navegador, duración y memoria máxima. El registro no incluye contraseñas ni cuerpos de formularios o JSON. Triggers SQLite impiden actualizar o borrar sus filas, y administración puede consultarlo en **Configuración → Registro de actividad**.

Esta auditoría global complementa la cadena HMAC de jornadas: la primera cubre el uso completo de la aplicación; la segunda autentica específicamente los estados y acuerdos que alteran asistencias.

### Ubicación de fichaje por IP

La versión 2.0 permite que cada organización configure una dirección IPv4, una IPv6 o ambas desde **Administración → Configuración → Configuración general**. Cuando la opción está activa, el portal de personal muestra si la conexión coincide exactamente con una de las direcciones públicas configuradas mediante los mensajes **Fichaje desde la empresa** o **Fichaje fuera de la empresa**.

La comprobación se realiza en servidor y tiene carácter informativo: no impide iniciar o finalizar una jornada. Si la aplicación está detrás de un proxy inverso, `APP_TRUST_PROXY=1` permite utilizar las cabeceras de dirección de cliente proporcionadas por ese proxy; solo debe activarse cuando el acceso directo al servidor de origen esté protegido.

### Notificaciones y calendarios de la versión 2.0

La versión 2.0 incorpora una configuración de notificaciones independiente para cada empresa. Administración puede activar o detener el sistema, indicar el email receptor y seleccionar los eventos desde una matriz ampliable. La primera automatización disponible es **Nueva solicitud de ausencia**: la petición se guarda primero y, después, se envía un aviso mediante el servidor SMTP de IONOS. Una incidencia de correo nunca elimina ni revierte la solicitud ya registrada.

En el portal del personal, **Mis jornadas** permite alternar entre la tabla histórica y un calendario mensual. Cada mes se consulta al servidor de forma independiente y muestra fichajes, vacaciones, festivos y ausencias con estilos y estados diferenciados.

Administración añade la aplicación **Temporalización vacaciones**, con navegación anual, filtro por departamento y por empleados con vacaciones, zoom horizontal, centrado automático del día actual, cabecera diaria, nombres fijos durante el desplazamiento y barras con el conteo de días para los periodos aprobados o pendientes. Desde cada barra se abre directamente la edición de vacaciones.

### Tablas de negocio recuperadas y ampliadas

El grupo **Tablas** conserva las trece entidades funcionales de la aplicación heredada: `admin`, `Asistencia`, `ausencias`, `centros_trabajo`, `Contratos`, `dias_de_la_semana`, `empleados`, `Festivos`, `horarios`, `Incidencias`, `tipos_contrato`, `usuarios` y `Vacaciones`. La versión 2.0 añadió `Llamamientos` y `Tipos de fichaje`. En SQLite usan nombres internos normalizados y siempre incorporan el aislamiento por `tenant_id`.

`dias_de_la_semana` se presenta como catálogo de solo lectura: desde la versión 2.0 sus valores están normalizados mediante una restricción de `schedules.weekday`, por lo que no se mantiene una tabla redundante. Las vistas auxiliares `resumen_semanal` y `vw_empleado_detalle` tampoco aparecen como tablas.

## Correcciones pactadas y registro de integridad

Una jornada existente nunca se edita directamente desde administración:

1. La empresa propone fecha, entrada, salida o tipo de fichaje nuevos e indica un motivo.
2. La jornada conserva sus valores vigentes y aparece como **pendiente**.
3. La persona trabajadora ve los valores anterior/propuesto en `front/`, y acepta o rechaza con su sesión personal volviendo a confirmar su contraseña actual. En sentido inverso, el trabajador propone y administración decide.
4. Solo una aceptación actualiza la jornada. Un rechazo conserva el original.
5. Propuesta, respuesta, actores, fechas, estados y valores quedan en un log append-only.

Los eventos forman una cadena HMAC-SHA-256 por tenant. Cada evento incorpora la huella anterior y una cabecera autenticada permite detectar también la eliminación del final de la cadena. El verificador contrasta además el estado actual de todas las jornadas y solicitudes contra sus últimos eventos autenticados. SQLite bloquea `UPDATE` y `DELETE` sobre el log mediante triggers.

La clave HMAC se guarda fuera de SQLite. Si `AUDIT_HMAC_KEY` está vacío, se genera automáticamente en la ruta de `AUDIT_HMAC_KEY_FILE` (`database/audit-hmac.key` por defecto). En producción es recomendable apuntar esa ruta fuera de la raíz web, limitar sus permisos al usuario de PHP y guardar una copia de seguridad separada. **Sin esa clave no es posible verificar la cadena histórica.**

La huella es evidencia técnica de integridad, no una firma electrónica cualificada ni una certificación jurídica automática. La organización debe documentar el sistema conforme al convenio/acuerdo o consulta aplicable, informar a la plantilla, restringir accesos y conservar los registros al menos cuatro años.

Referencias oficiales utilizadas para este diseño:

- Artículo 34.9 del Estatuto de los Trabajadores: https://www.boe.es/buscar/act.php?id=BOE-A-2019-3481
- Guía sobre el registro de jornada del Ministerio de Trabajo: https://www.mites.gob.es/ficheros/ministerio/GuiaRegistroJornada.pdf
- Guía de protección de datos en las relaciones laborales de la AEPD: https://www.aepd.es/guias/la-proteccion-de-datos-en-las-relaciones-laborales.pdf

## Requisitos

- PHP 8.2 o posterior.
- Extensión PDO SQLite. En Ubuntu: `sudo apt install php-sqlite3`.
- Extensión OpenSSL de PHP para las conexiones SMTP mediante STARTTLS.
- Permiso de escritura para el usuario de PHP sobre `database/`.

No se crean vistas, procedimientos almacenados ni eventos. Los resúmenes semanales y los datos combinados se calculan mediante consultas normales y PHP.

## Instalación

Desde la raíz del proyecto:

```bash
cp .env.example .env
nano .env
php database/install.php
```

También puedes proporcionar la contraseña inicial sin editar esa variable:

```bash
php database/install.php --password="una-clave-segura"
```

El instalador es repetible: crea o actualiza el tenant local, sus dominios, el catálogo contractual, los tipos iniciales `Docencia`/`Complementarias` y el superadministrador sin borrar datos existentes. La base se guarda por defecto en `database/jocarsa-asistencias.sqlite`.

En Apache/Ubuntu, el proceso web debe poder escribir tanto el archivo como su directorio, porque SQLite crea archivos temporales `-wal` y `-shm`:

```bash
sudo chgrp -R www-data database
sudo chmod 775 database
sudo chmod 660 database/jocarsa-asistencias.sqlite
sudo chmod 640 database/audit-hmac.key
```

Configura Apache o Nginx para que todos los dominios de cliente apunten a la raíz del proyecto. Después accede a:

- `http://localhost/superadmin/` para configurar tenants y dominios.
- `http://dominio-del-tenant/admin/` para el panel del tenant.
- `http://dominio-del-tenant/front/` para las personas trabajadoras.

## Modelo multi-tenant

Todos los datos funcionales comparten un único archivo SQLite y llevan `tenant_id`. Las claves foráneas compuestas `(id, tenant_id)` impiden asociar registros entre organizaciones. Cada petición normal resuelve el tenant desde `HTTP_HOST`; las sesiones de admin y front se invalidan si el dominio deja de corresponder al mismo tenant.

No se acepta un `tenant_id` enviado por el navegador. El tenant siempre procede del dominio. `X-Forwarded-Host` solo se considera con `APP_TRUST_PROXY=1`.

SQLite se configura con claves foráneas activas, espera ante bloqueos, caché de lectura y modo WAL. Las operaciones de auditoría usan `SAVEPOINT`, por lo que pueden ejecutarse de forma segura tanto dentro como fuera de una transacción ya abierta sin provocar transacciones anidadas. El inicio de jornada usa una transacción de escritura inmediata para impedir dobles aperturas simultáneas sin modificar los registros históricos incompletos de V1.

## Importación desde V1

El importador lee únicamente los `INSERT` compatibles del volcado de phpMyAdmin. No ejecuta el DDL de MySQL ni importa las vistas `resumen_semanal` y `vw_empleado_detalle`, el procedimiento `sp_search_asistencias_by_worker`, `DEFINER`, triggers o eventos. Las contraseñas V1 en texto se convierten a hashes.

Desde terminal, recomendado para volcados grandes:

```bash
php tools/import_v1.php --tenant=local --file=/ruta/jocarsapersonastame2.sql
```

También puede ejecutarse desde `superadmin/`, seleccionando el tenant y subiendo el `.sql`. La importación es repetible: cada tabla conserva `legacy_id` y reutiliza el mismo registro en sucesivas ejecuciones. Si ya existe en la versión 2.x un administrador o usuario con el mismo nombre de usuario o correo, el importador reutiliza esa cuenta, le vincula el identificador antiguo y conserva su contraseña actual para no bloquear el acceso. Las nuevas asistencias importadas entran en la cadena de auditoría; si una importación posterior intentara alterar una asistencia ya autenticada, se bloquea y obliga a utilizar el flujo bilateral de corrección.

## Copias de seguridad

Con la aplicación detenida o usando la utilidad SQLite:

```bash
sqlite3 database/jocarsa-asistencias.sqlite ".backup 'asistencias-backup.sqlite'"
```

## Seguridad y despliegue

- Usa HTTPS en producción.
- Mantén `.env`, `database/`, `src/` y `tools/` fuera del acceso público; el `.htaccess` incluido los bloquea en Apache.
- Conserva conjuntamente la base SQLite y la clave de auditoría, pero en copias de seguridad con controles de acceso separados.
- Mantén los registros disponibles para la persona trabajadora, sus representantes y la Inspección durante un mínimo de cuatro años.
- En Nginx crea reglas equivalentes.
- Cambia la contraseña inicial antes de ejecutar el instalador.
- Después de importar, revisa las cuentas y desactiva las que ya no deban acceder.
````

