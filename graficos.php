<?php
/**
 * CHUCK RETAIL INTELLIGENCE - DEEP ANALYTICS MODULE v2.5
 * Integración Total: Lógica v1.4 + Layout Flexbox (Simetría Perfecta)
 */

require_once 'conexion.php';

// CONTROL: Captura segura
$sucursal_actual = isset($_GET['sucursal']) ? intval($_GET['sucursal']) : 1;
$periodo_seleccionado = isset($_GET['periodo']) ? $_GET['periodo'] : null;

// Estructura de respaldo
if (!isset($sucursales)) {
    $sucursales = [
        1 => ['nombre' => 'Sucursal Central'],
        2 => ['nombre' => 'Sucursal 2'],
        3 => ['nombre' => 'Sucursal 3'],
        4 => ['nombre' => 'Sucursal 4'],
        5 => ['nombre' => 'Sucursal 5']
    ];
}

$puntos_ventas = array();
$labels_puntos = array();
$ventas_mensuales = array();
$ventas_diarias_mes = array();
$total_hoy = 0; $tickets_hoy = 0; $promedio_ticket = 0;

if ($conn) {
    mysqli_query($conn, "SET lc_time_names = 'es_AR'");

    // CÁLCULO DE MÉTRICAS RÁPIDAS
    $res_resumen = mysqli_query($conn, "SELECT SUM(subtotal) as total, COUNT(*) as cuenta FROM factura WHERE fecha = CURDATE()");
    if($res_resumen) {
        $row_res = mysqli_fetch_assoc($res_resumen);
        $total_hoy = $row_res['total'] ?: 0;
        $tickets_hoy = $row_res['cuenta'] ?: 0;
        $promedio_ticket = ($tickets_hoy > 0) ? ($total_hoy / $tickets_hoy) : 0;
    }

    // LÓGICA V1.4
    $sql_puntos = "SELECT subtotal, DATE_FORMAT(hora, '%H:%i') as corte_hora FROM factura WHERE fecha = CURDATE() ORDER BY hora ASC";
    $res_puntos = mysqli_query($conn, $sql_puntos);
    if ($res_puntos) {
        while ($row = mysqli_fetch_assoc($res_puntos)) {
            $puntos_ventas[] = (float)$row['subtotal'];
            $labels_puntos[] = $row['corte_hora'];
        }
    }

    if ($periodo_seleccionado) {
        $sql_dias = "SELECT fecha, DAY(fecha) as dia_numero, DATE_FORMAT(fecha, '%W') as dia_nombre, SUM(subtotal) as total_dia, COUNT(*) as total_tickets, AVG(subtotal) as ticket_promedio FROM factura WHERE DATE_FORMAT(fecha, '%Y-%m') = ? GROUP BY fecha ORDER BY fecha DESC";
        $stmt = mysqli_prepare($conn, $sql_dias);
        mysqli_stmt_bind_param($stmt, "s", $periodo_seleccionado);
        mysqli_stmt_execute($stmt);
        $res_dias = mysqli_stmt_get_result($stmt);
        if ($res_dias) { while ($row = mysqli_fetch_assoc($res_dias)) { $ventas_diarias_mes[] = $row; } }
        mysqli_stmt_close($stmt);
    }

    $sql_meses = "SELECT DATE_FORMAT(fecha, '%Y-%m') as mes_id, YEAR(fecha) as anio, MONTHNAME(fecha) as mes_nombre, SUM(subtotal) as total_mes, COUNT(*) as total_tickets, AVG(subtotal) as ticket_promedio FROM factura GROUP BY YEAR(fecha), MONTH(fecha) ORDER BY mes_id DESC";
    $res_meses = mysqli_query($conn, $sql_meses);
    if ($res_meses) { while ($row = mysqli_fetch_assoc($res_meses)) { $ventas_mensuales[] = $row; } }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chuck OS - Advanced Analytics</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght=500;800&family=Inter:wght=400;700;900&display=swap');
        :root { --bg: #050505; --panel: #0d1117; --text: #c9d1d9; --border: rgba(255,255,255,0.05); --accent: #2f81f7; }
        body { background-color: var(--bg); color: var(--text); font-family: 'Inter', sans-serif; font-size: 13px; }
        .panel { background: var(--panel); border: 1px solid var(--border); border-radius: 12px; }
        .mono { font-family: 'JetBrains Mono', monospace; }
        .scroll-custom::-webkit-scrollbar { width: 4px; }
        .scroll-custom::-webkit-scrollbar-thumb { background: var(--accent); border-radius: 10px; }
    </style>
</head>
<body class="h-screen flex flex-col p-4 gap-4 overflow-hidden">

    <!-- CABECERA -->
    <div class="panel p-3 flex justify-between items-center shrink-0">
        <div class="flex items-center gap-3">
            <button onclick="window.location.href='index.php?sucursal=<?php echo $sucursal_actual; ?>'" class="text-[11px] mono font-bold bg-white/5 border border-white/10 px-3 py-1 rounded-lg hover:bg-white/10 transition-colors">← ESCAPE DASHBOARD</button>
            <div class="h-4 border-r border-white/10"></div>
            <h1 class="text-xs font-black mono text-white uppercase tracking-wider">
                Módulo Analítico v2.5 — <span class="text-blue-500"><?php echo htmlspecialchars(isset($sucursales[$sucursal_actual]['nombre']) ? $sucursales[$sucursal_actual]['nombre'] : 'Sucursal'); ?></span>
            </h1>
        </div>
        <div class="flex bg-white/5 p-0.5 rounded-lg border border-white/5 mono text-[10px] font-bold">
            <button onclick="switchChartType('line')" id="btn-line" class="px-3 py-1 rounded-md bg-blue-500 text-white transition-all">LINEAL</button>
            <button onclick="switchChartType('area')" id="btn-area" class="px-3 py-1 rounded-md text-gray-400 hover:text-white transition-all">ÁREA</button>
            <button onclick="switchChartType('bar')" id="btn-bar" class="px-3 py-1 rounded-md text-gray-400 hover:text-white transition-all">BARRAS</button>
        </div>
    </div>

    <!-- SECCIÓN CENTRAL: Gráfico + Métricas (Flexbox Simétrico) -->
    <div class="flex-1 flex gap-4 min-h-0">
        <!-- Gráfico (Ocupa el espacio restante) -->
        <div class="panel flex-1 p-4 flex flex-col">
            <div class="flex justify-between items-center mb-2 shrink-0">
                <span class="text-[10px] uppercase font-bold text-gray-400 tracking-wider">Distribución Operativa (Flujo del Día)</span>
                <span class="text-[10px] mono text-yellow-500 bg-yellow-500/10 px-2 py-0.5 rounded-md border border-yellow-500/20">Muestras: <?php echo count($puntos_ventas); ?> tkt</span>
            </div>
            <div class="flex-1 min-h-0 relative">
                <canvas id="analyticsChart" class="w-full h-full"></canvas>
            </div>
        </div>

        <!-- Métricas (Columna lateral fija de 250px) -->
        <div class="w-64 flex flex-col gap-4 shrink-0">
            <div class="panel p-4 flex-1 flex flex-col justify-center border-l-4 border-green-500">
                <span class="text-[9px] uppercase text-gray-500 font-bold">Total Hoy</span>
                <span class="text-xl font-black text-white mono">$<?php echo number_format($total_hoy, 0, ',', '.'); ?></span>
            </div>
            <div class="panel p-4 flex-1 flex flex-col justify-center border-l-4 border-blue-500">
                <span class="text-[9px] uppercase text-gray-500 font-bold">Tickets Emitidos</span>
                <span class="text-xl font-black text-white mono"><?php echo number_format($tickets_hoy, 0); ?></span>
            </div>
            <div class="panel p-4 flex-1 flex flex-col justify-center border-l-4 border-yellow-500">
                <span class="text-[9px] uppercase text-gray-500 font-bold">Ticket Promedio</span>
                <span class="text-xl font-black text-white mono">$<?php echo number_format($promedio_ticket, 2, ',', '.'); ?></span>
            </div>
        </div>
    </div>

    <!-- HISTÓRICO (Copia exacta v1.4) -->
    <div class="panel h-[280px] shrink-0 flex flex-col overflow-hidden">
        <?php if ($periodo_seleccionado): ?>
            <div class="p-3 bg-white/5 border-b border-white/5 text-[10px] font-black uppercase tracking-wider shrink-0 flex justify-between items-center">
                <span class="text-yellow-500">Desglose Diario: <span class="text-white mono"><?php echo htmlspecialchars($periodo_seleccionado); ?></span></span>
                <button onclick="window.location.href='?sucursal=<?php echo $sucursal_actual; ?>'" class="bg-blue-500/10 border border-blue-500/20 text-blue-400 font-mono font-bold px-2 py-0.5 rounded text-[9px] hover:bg-blue-500 hover:text-white transition-all">Volver ↑</button>
            </div>
            <div class="flex-1 overflow-y-auto p-2 space-y-1 scroll-custom">
                <?php if (!empty($ventas_diarias_mes)): foreach ($ventas_diarias_mes as $vd): ?>
                    <div onclick="window.location.href='reporte_factura.php?dia=<?php echo $vd['fecha']; ?>&sucursal=<?php echo $sucursal_actual; ?>'" class="grid grid-cols-4 items-center p-2.5 px-3 rounded-lg hover:bg-yellow-500/5 border-b border-white/5 cursor-pointer group transition-all">
                        <div class="flex flex-col"><span class="text-[11px] font-black uppercase text-white"><?php echo $vd['dia_numero']; ?> - <?php echo $vd['dia_nombre']; ?></span><span class="text-[9px] mono opacity-40"><?php echo $vd['fecha']; ?></span></div>
                        <div class="text-right mono font-bold text-gray-300"><?php echo number_format($vd['total_tickets'], 0, ',', '.'); ?> u.</div>
                        <div class="text-right mono text-xs font-bold text-blue-400">$<?php echo number_format($vd['ticket_promedio'], 2, ',', '.'); ?></div>
                        <div class="text-right mono text-sm font-black text-yellow-500">$<?php echo number_format($vd['total_dia'], 2, ',', '.'); ?></div>
                    </div>
                <?php endforeach; else: ?><div class="p-8 text-center text-gray-500 mono">Sin registros.</div><?php endif; ?>
            </div>
        <?php else: ?>
            <div class="p-3 bg-white/5 border-b border-white/5 text-[10px] font-black text-purple-400 uppercase tracking-wider shrink-0">Histórico Mensual</div>
            <div class="flex-1 overflow-y-auto p-2 space-y-1 scroll-custom">
                <?php if (!empty($ventas_mensuales)): foreach ($ventas_mensuales as $vm): ?>
                    <div onclick="window.location.href='?sucursal=<?php echo $sucursal_actual; ?>&periodo=<?php echo $vm['mes_id']; ?>'" class="grid grid-cols-4 items-center p-2.5 px-3 rounded-lg hover:bg-purple-500/10 border-b border-white/5 cursor-pointer group transition-all">
                        <div class="flex flex-col"><span class="text-[11px] font-black uppercase text-white"><?php echo htmlspecialchars($vm['mes_nombre']); ?></span><span class="text-[9px] mono opacity-40"><?php echo $vm['anio']; ?></span></div>
                        <div class="text-right mono font-bold text-gray-300"><?php echo number_format($vm['total_tickets'], 0, ',', '.'); ?> u.</div>
                        <div class="text-right mono text-xs font-bold text-blue-400">$<?php echo number_format($vm['ticket_promedio'], 2, ',', '.'); ?></div>
                        <div class="text-right mono text-sm font-black text-purple-400">$<?php echo number_format($vm['total_mes'], 2, ',', '.'); ?></div>
                    </div>
                <?php endforeach; else: ?><div class="p-8 text-center text-gray-500 mono">Sin registros.</div><?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        const chartLabels = <?php echo json_encode($labels_puntos); ?>;
        const chartData   = <?php echo json_encode($puntos_ventas); ?>;
        let currentType = 'line'; 
        let chartInstance = null;
        const ctx = document.getElementById('analyticsChart').getContext('2d');

        function buildChartConfig(type) {
            let datasetsConfig = { label: 'Monto ($)', data: chartData, borderColor: '#2f81f7', borderWidth: 2, pointRadius: chartData.length > 50 ? 0 : 2, tension: 0.2 };
            if (type === 'area') { datasetsConfig.fill = true; datasetsConfig.backgroundColor = 'rgba(47, 129, 247, 0.2)'; type = 'line'; } 
            else if (type === 'bar') { datasetsConfig.backgroundColor = 'rgba(47, 129, 247, 0.7)'; datasetsConfig.borderRadius = 4; }
            return { type: type, data: { labels: chartLabels, datasets: [datasetsConfig] }, options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { ticks: { color: '#6e7681', font: { size: 9 } } }, y: { ticks: { color: '#6e7681', font: { size: 9 } } } } } };
        }

        function initChart(type) { if (chartInstance) { chartInstance.destroy(); } chartInstance = new Chart(ctx, buildChartConfig(type)); }
        function switchChartType(type) { currentType = type; initChart(type); }
        initChart(currentType);
    </script>
</body>
</html>
