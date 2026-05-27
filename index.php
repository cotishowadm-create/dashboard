<?php
session_start();
require_once 'conexion.php';

// El Guardián
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}
// Tu dashboard sigue aquí abajo...
?>
<?php

// Inicialización de variables de contingencia
$puntos_ventas = array(0);
$labels_puntos = array('--:--');
$total_ventas_count = 0;
$max_venta = 1;
$efectivo = $debito = $credito = $qr = $total_gral = 0;
$db_status = "OFFLINE";

error_reporting(E_ERROR | E_PARSE);

// MODIFICACIÓN: Inclusión del archivo de conexión centralizado
require_once 'conexion.php'; 

if ($conn) {
    $db_status = "ONLINE";
    mysqli_set_charset($conn, "utf8mb4");
    
    // Totales por método de pago
    $sql_totales = "SELECT Tarjeta, SUM(subtotal) as total_dia FROM factura WHERE fecha = CURDATE() GROUP BY Tarjeta";
    $res_totales = mysqli_query($conn, $sql_totales);
    if ($res_totales) {
        while ($row = mysqli_fetch_assoc($res_totales)) {
            switch ($row['Tarjeta']) {
                case 1: $credito  = (float)$row['total_dia']; break;
                case 2: $debito   = (float)$row['total_dia']; break;
                case 3: $efectivo = (float)$row['total_dia']; break;
                case 4: $qr       = (float)$row['total_dia']; break;
            }
        }
    }
    $total_gral = $efectivo + $debito + $credito + $qr;

    // Conteo total de tickets
    $res_count = mysqli_query($conn, "SELECT COUNT(*) as total FROM factura WHERE fecha = CURDATE()");
    if ($res_count) {
        $row_count = mysqli_fetch_assoc($res_count);
        $total_ventas_count = $row_count['total'];
    }

    // Gráfico: Venta a Venta individual
    $sql_puntos = "SELECT subtotal, DATE_FORMAT(hora, '%H:%i') as corte_hora 
                   FROM factura 
                   WHERE fecha = CURDATE() 
                   ORDER BY hora ASC";
    $res_puntos = mysqli_query($conn, $sql_puntos);

    if ($res_puntos && mysqli_num_rows($res_puntos) > 0) {
        $puntos_ventas = array(); 
        $labels_puntos = array();
        while ($row = mysqli_fetch_assoc($res_puntos)) {
            $puntos_ventas[] = (float)$row['subtotal'];
            $labels_puntos[] = $row['corte_hora'];
        }
        $max_venta = max($puntos_ventas) > 0 ? max($puntos_ventas) : 1;
    }

    // Flujo de Artículos
    $res_articulos = mysqli_query($conn, "
        SELECT descripcion, Prec_venta, COUNT(CodBarras_subProd) AS cantidad
        FROM detallefactura
        WHERE fecha = CURDATE()
        GROUP BY CodBarras_subProd, descripcion, Prec_venta
        ORDER BY cantidad DESC
        LIMIT 50
    ");

    // Staff Performance
    $res_vendedores = mysqli_query($conn, "
        SELECT encargado, SUM(subtotal) as total 
        FROM factura 
        WHERE fecha = CURDATE() 
        GROUP BY encargado 
        ORDER BY total DESC
    ");
    
    // Últimas Facturas
    $res_arqueo = mysqli_query($conn, "
        SELECT hora, subtotal 
        FROM factura 
        WHERE fecha = CURDATE() 
        ORDER BY hora DESC 
        LIMIT 30
    ");
}

// LÓGICA GRÁFICA SVG
$puntos_svg = ""; 
$ancho_total = 1000; 
$alto_total = 80;
$total_puntos = count($puntos_ventas);
$paso = ($total_puntos > 1) ? $ancho_total / ($total_puntos - 1) : 0;

foreach ($puntos_ventas as $idx => $monto) {
    $x = $idx * $paso;
    $y = $alto_total - (($monto / $max_venta) * $alto_total);
    $x_fmt = number_format($x, 2, '.', '');
    $y_fmt = number_format($y, 2, '.', '');
    $puntos_svg .= "$x_fmt,$y_fmt ";
}
$puntos_svg = trim($puntos_svg);

if ($total_puntos > 1 && !empty($puntos_svg)) {
    $pts_arr = explode(' ', $puntos_svg);
    $last_pt = end($pts_arr);
    $coords = explode(',', $last_pt);
    $lx = $coords[0];
    $ly = $coords[1];
} else {
    $lx = 1000; 
    $ly = 80;
}

$promedio_y = 40;
if ($total_puntos > 0) {
    $promedio = array_sum($puntos_ventas) / $total_puntos;
    $promedio_y = $alto_total - (($promedio / $max_venta) * $alto_total);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chuck OS v10.5 Stable - Closable Panels</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght=500;800&family=Inter:wght@400;700;900&display=swap');
        :root { --bg: #050505; --panel: #0d1117; --text: #c9d1d9; --border: rgba(255,255,255,0.05); --accent: #2f81f7; --select-bg: #161b22; }
        body.theme-light { --bg: #f0f2f5; --panel: #ffffff; --text: #1a1d23; --border: rgba(0,0,0,0.1); --accent: #0969da; --select-bg: #f6f8fa; }
        
        body { background-color: var(--bg); color: var(--text); font-family: 'Inter', sans-serif; font-size: 13px; transition: background 0.3s ease; }
        
        .panel { 
            background: var(--panel); 
            border: 1px solid var(--border); 
            border-radius: 12px; 
            transition: transform 0.2s cubic-bezier(0.16, 1, 0.3, 1), border-color 0.2s ease, box-shadow 0.2s ease;
        }
        .panel:hover { 
            border-color: rgba(47, 129, 247, 0.35); 
            box-shadow: 0 6px 24px rgba(47, 129, 247, 0.08);
            transform: translateY(-1px);
        }
        
        .mono { font-family: 'JetBrains Mono', monospace; }
        .scroll-custom::-webkit-scrollbar { width: 4px; }
        .scroll-custom::-webkit-scrollbar-thumb { background: var(--accent); border-radius: 10px; }
        
        .draggable-col { cursor: grab; }
        .draggable-col:active { cursor: grabbing; }
        .dragging { opacity: 0.4; border: 1px dashed var(--accent) !important; transform: scale(0.98); }

        .switch { position: relative; display: inline-block; width: 36px; height: 18px; }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #30363d; transition: .4s; border-radius: 20px; }
        .slider:before { position: absolute; content: ""; height: 12px; width: 12px; left: 3px; bottom: 3px; background-color: white; transition: .4s; border-radius: 50%; }
        input:checked + .slider { background-color: var(--accent); }
        input:checked + .slider:before { transform: translateX(18px); }
        
        .hover-row { transition: transform 0.15s ease, box-shadow 0.15s ease, background-color 0.15s ease; }
        .hover-row:hover { transform: scale(1.015); box-shadow: 0 4px 15px rgba(47,129,247,0.12); z-index: 10; position: relative; }
        #chartTooltip { pointer-events: none; }
        
        .branch-select { background-color: var(--select-bg); color: var(--text); border: 1px solid var(--border); }
    </style>
</head>
<body class="h-screen flex flex-col p-4 gap-3 overflow-hidden" id="mainBody">

    <!-- PANEL DEL GRÁFICO (Flujo Venta a Venta) -->
    <div class="panel w-full flex flex-col p-3 relative shrink-0 transition-all group" style="height: 140px;">
         
        <div class="flex justify-between items-center mb-2 shrink-0">
            <div class="flex items-center gap-2">
                <span class="text-[12px] font-black mono text-white tracking-tighter">CHUCK OS <span class="text-blue-500 font-normal">v10.5</span></span>
                <span class="text-[9px] opacity-25 uppercase tracking-wider font-bold hidden sm:inline">| Retail Intelligence System</span>
                
                <div class="flex items-center border-l border-white/10 pl-2 ml-1 h-3 gap-2">
                    <!-- Switch de Tema -->
                    <label class="switch flex items-center">
                        <input type="checkbox" id="themeToggle" checked onchange="toggleTheme(event)">
                        <span class="slider"></span>
                    </label>

                    <!-- NUEVO BOTÓN: Ubicado estratégicamente a la derecha del switch -->
                    <button onclick="window.location.href='graficos.php?sucursal=<?php echo $sucursal_actual; ?>'" 
                            class="text-[9px] mono font-bold bg-blue-500/20 text-blue-400 border border-blue-500/30 px-2 py-0.5 rounded hover:bg-blue-500 hover:text-white transition-all">
                        [ANALYTICS]
                    </button>

                    <!-- Botón para restaurar paneles ocultos -->
                    <button id="resetLayoutBtn" onclick="resetPanels(event)" class="hidden text-[9px] mono font-bold bg-yellow-500/20 text-yellow-400 border border-yellow-500/30 px-1.5 py-0.5 rounded animate-pulse">
                        [RESTORE PANELS]
                    </button>
                                    
                </div>
            </div>
            
            <div class="flex items-center gap-4">
                <div class="flex items-center gap-3">
                    <span class="text-[9px] mono opacity-40 text-yellow-400 font-bold">— prom ticket</span>
                    <span class="text-[11px] mono font-bold text-blue-500 uppercase tracking-tight"><?php echo $total_ventas_count; ?> TICKETS</span>
                        <a href="admin_usuarios.php" class="text-[9px] mono font-bold bg-purple-500/20 text-purple-400 border border-purple-500/30 px-2 py-0.5 rounded hover:bg-purple-500 hover:text-white transition-all">
                            [CREAR USUARIO]
                        </a>
                        <span class="flex items-center gap-1 text-[11px] mono text-green-400 font-bold">
                        <span class="w-1.5 h-1.5 rounded-full bg-green-400 animate-pulse inline-block"></span> LIVE FLOW
                    </span>
                </div>
            </div>
        </div>

        <!-- El contenedor del gráfico mantiene redirección nativa al hacer clic sobre el área visual -->
        <div class="flex-1 w-full relative min-h-0 cursor-pointer" id="chartContainer" onclick="window.location.href='graficos.php?sucursal=<?php echo $sucursal_actual; ?>'">
            <svg viewBox="0 0 1000 100" preserveAspectRatio="none" class="w-full h-full overflow-visible">
                <defs>
                    <linearGradient id="grad" x1="0%" y1="0%" x2="0%" y2="100%">
                        <stop offset="0%" style="stop-color:#2f81f7;stop-opacity:0.3"/>
                        <stop offset="100%" style="stop-color:#2f81f7;stop-opacity:0"/>
                    </linearGradient>
                </defs>

                <line x1="0" y1="0"   x2="1000" y2="0"   stroke="white" stroke-width="0.5" opacity="0.05"/>
                <line x1="0" y1="40"  x2="1000" y2="40"  stroke="white" stroke-width="0.5" opacity="0.05"/>
                <line x1="0" y1="80"  x2="1000" y2="80"  stroke="white" stroke-width="0.5" opacity="0.05"/>

                <line x1="0" y1="<?php echo $promedio_y; ?>" x2="1000" y2="<?php echo $promedio_y; ?>"
                    stroke="#f59e0b" stroke-width="1" stroke-dasharray="5,4" opacity="0.4"/>

                <?php if ($total_puntos > 0 && $puntos_svg !== ""): ?>
                    <polyline points="0,80 <?php echo $puntos_svg; ?> <?php echo $lx; ?>,80" fill="url(#grad)"/>
                    <polyline points="<?php echo $puntos_svg; ?>" fill="none" stroke="#2f81f7" stroke-width="2.2" stroke-linejoin="round" stroke-linecap="round"/>
                    <circle cx="<?php echo $lx; ?>" cy="<?php echo $ly; ?>" r="3.5" fill="#2f81f7">
                        <animate attributeName="r" values="2.5;6;2.5" dur="1.5s" repeatCount="indefinite"/>
                        <animate attributeName="opacity" values="1;0.2;1" dur="1.5s" repeatCount="indefinite"/>
                    </circle>
                <?php endif; ?>

                <text x="4" y="9"    fill="#c9d1d9" font-size="9" opacity="0.3" font-family="monospace">Max: $<?php echo number_format($max_venta, 0, ',', '.'); ?></text>
                <text x="4" y="45"   fill="#c9d1d9" font-size="9" opacity="0.3" font-family="monospace">$<?php echo number_format($max_venta/2, 0, ',', '.'); ?></text>
                <text x="4" y="79"   fill="#c9d1d9" font-size="9" opacity="0.3" font-family="monospace">$0</text>

                <?php
                    $n = count($labels_puntos);
                    $step_label = max(1, (int)($n / 8));
                    foreach ($labels_puntos as $i => $label) {
                        if ($i % $step_label === 0 || $i === $n - 1) {
                            $lx_label = ($n > 1) ? ($i / ($n - 1)) * 1000 : 0;
                            $anchor = ($i === 0) ? 'start' : (($i === $n-1) ? 'end' : 'middle');
                            echo "<text x=\"$lx_label\" y=\"96\" fill=\"#c9d1d9\" font-size=\"9\" opacity=\"0.3\" font-family=\"monospace\" text-anchor=\"$anchor\">$label</text>";
                        }
                    }
                ?>

                <?php
                    if ($puntos_svg !== "") {
                        foreach ($puntos_ventas as $i => $monto) {
                            $px = ($n > 1) ? ($i / ($n - 1)) * 1000 : 500;
                            $py = $alto_total - (($monto / $max_venta) * $alto_total);
                            $hora = isset($labels_puntos[$i]) ? $labels_puntos[$i] : '';
                            $monto_fmt = '$' . number_format($monto, 0, ',', '.');
                            // Inyectamos stopPropagation para no disparar redirección doble al interactuar con las burbujas
                            echo "<circle class=\"chart-dot\" cx=\"$px\" cy=\"$py\" r=\"16\" fill=\"transparent\" data-hora=\"Ticket — $hora hs\" data-monto=\"$monto_fmt\" style=\"cursor:crosshair\" onclick=\"event.stopPropagation();\"/>";
                        }
                    }
                ?>
            </svg>

            <div id="chartTooltip" class="absolute hidden bg-gray-900 border border-blue-500/40 rounded-lg px-2 py-1 z-50 shadow-xl">
                <div class="text-[10px] mono text-blue-400" id="tooltipHora"></div>
                <div class="text-[13px] mono font-black text-white" id="tooltipMonto"></div>
            </div>
        </div>
    </div>    

    <!-- METODOS DE PAGO + SELECTOR SUCURSAL -->
    <div class="grid grid-cols-5 gap-3 shrink-0">
        <div class="panel p-2 border-l-4 border-blue-500 flex flex-col justify-between">
            <div class="flex justify-between items-center">
                <span class="text-[8px] font-black text-blue-500 uppercase tracking-tight">Sucursal</span>
                <span class="text-[10px] font-mono font-black <?php echo $db_status === 'ONLINE' ? 'text-green-400' : 'text-red-400'; ?>">
                    <?php echo $db_status; ?>
                </span>
            </div>
            <select onchange="window.location.href='?sucursal=' + this.value" class="branch-select mt-1 w-full text-[11px] font-bold py-0.5 px-1 rounded outline-none cursor-pointer mono transition-all">
                <?php foreach ($sucursales as $id => $suc): ?>
                    <option value="<?php echo $id; ?>" <?php echo $id === $sucursal_actual ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($suc['nombre']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="panel p-2 text-center flex flex-col justify-center">
            <p class="text-[8px] text-gray-500 uppercase font-bold">Efectivo</p>
            <p class="text-sm sm:text-base font-black mono text-green-500">$<?php echo number_format($efectivo, 0, ',', '.'); ?></p>
        </div>
        <div class="panel p-2 text-center flex flex-col justify-center">
            <p class="text-[8px] text-gray-500 uppercase font-bold">Crédito</p>
            <p class="text-sm sm:text-base font-black mono text-white">$<?php echo number_format($credito, 0, ',', '.'); ?></p>
        </div>
        <div class="panel p-2 text-center flex flex-col justify-center">
            <p class="text-[8px] text-gray-500 uppercase font-bold">Débito</p>
            <p class="text-sm sm:text-base font-black mono text-white">$<?php echo number_format($debito, 0, ',', '.'); ?></p>
        </div>
        <div class="panel p-2 text-center flex flex-col justify-center">
            <p class="text-[8px] text-gray-500 uppercase font-bold">QR</p>
            <p class="text-sm sm:text-base font-black mono text-purple-500">$<?php echo number_format($qr, 0, ',', '.'); ?></p>
        </div>
    </div>

    <!-- KPIs -->
    <div class="grid grid-cols-3 gap-3 shrink-0">
        <div class="panel p-2 flex flex-col items-center"><p class="text-[10px] font-black text-yellow-500 uppercase">Conversión</p><p class="text-2xl font-black mono italic text-white">32.4%</p></div>
        <div class="panel p-2 flex flex-col items-center"><p class="text-[10px] font-black text-purple-500 uppercase">UPT</p><p class="text-2xl font-black mono italic text-white">2.8</p></div>
        <div class="panel p-2 flex flex-col items-center bg-blue-500/5"><p class="text-[10px] font-black text-blue-500 uppercase">Total Bruto</p><p class="text-2xl font-black mono text-blue-400">$<?php echo number_format($total_gral, 0, ',', '.'); ?></p></div>
    </div>

    <!-- TABLAS OPERATIVAS CON DRAG & DROP + BOTÓN DE CIERRE -->
    <div class="flex-1 flex gap-3 min-h-0 w-full" id="tablesContainer">
        
        <!-- COLUMNA 1: PRODUCTOS -->
        <div data-id="productos" class="flex-1 panel flex flex-col overflow-hidden draggable-col" draggable="true">
            <div class="p-2.5 px-3 bg-white/5 border-b border-white/5 text-[10px] font-black text-blue-400 uppercase tracking-tighter shrink-0 flex justify-between items-center">
                <div class="flex items-center gap-1.5">
                    <span class="opacity-20 text-[8px]">⋮⋮</span>
                    <span>Productos Más Vendidos</span>
                </div>
                <button onclick="closePanel(this, event)" class="text-white/30 hover:text-red-400 font-bold font-mono text-sm px-1 transition-colors select-none">×</button>
            </div>
            <div class="flex-1 overflow-y-auto p-2 space-y-1 scroll-custom">
                <?php if (isset($res_articulos) && $res_articulos): while ($a = mysqli_fetch_assoc($res_articulos)): ?>
                    <div class="hover-row flex justify-between items-center p-2 rounded-lg hover:bg-blue-500/5 border-b border-white/5">
                        <div class="flex items-center gap-2 min-w-0">
                            <span class="text-[10px] font-black mono bg-yellow-500/20 text-yellow-400 px-1.5 py-0.5 rounded-md shrink-0"><?php echo $a['cantidad']; ?>x</span>
                            <span class="text-[10px] uppercase opacity-70 truncate text-white"><?php echo htmlspecialchars($a['descripcion']); ?></span>
                        </div>
                        <span class="text-[10px] font-black mono text-blue-500 shrink-0 ml-2">$<?php echo number_format((float)$a['Prec_venta'], 0, ',', '.'); ?></span>
                    </div>
                <?php endwhile; endif; ?>
            </div>
        </div>

        <!-- COLUMNA 2: RANKING -->
        <div data-id="ranking" class="flex-1 panel flex flex-col overflow-hidden draggable-col" draggable="true">
            <div class="p-2.5 px-3 bg-white/5 border-b border-white/5 text-[10px] font-black text-yellow-500 uppercase tracking-tighter shrink-0 flex justify-between items-center">
                <div class="flex items-center gap-1.5">
                    <span class="opacity-20 text-[8px]">⋮⋮</span>
                    <span>Staff Ranking</span>
                </div>
                <button onclick="closePanel(this, event)" class="text-white/30 hover:text-red-400 font-bold font-mono text-sm px-1 transition-colors select-none">×</button>
            </div>
            <div class="flex-1 overflow-y-auto p-3 space-y-2 scroll-custom">
                <?php if (isset($res_vendedores) && $res_vendedores): while ($v = mysqli_fetch_assoc($res_vendedores)): ?>
                    <div class="hover-row p-2 px-3 rounded-xl bg-blue-500/5 border border-white/5 flex justify-between items-center hover:bg-white/5">
                        <span class="text-[10px] font-black uppercase text-white"><?php echo htmlspecialchars($v['encargado'] ?: 'ADMIN'); ?></span>
                        <span class="text-lg font-black mono text-yellow-500">$<?php echo number_format($v['total'], 0, ',', '.'); ?></span>
                    </div>
                <?php endwhile; endif; ?>
            </div>
        </div>

        <!-- COLUMNA 3: FACTURAS -->
        <div data-id="facturas" class="flex-1 panel flex flex-col overflow-hidden border-t-2 border-green-500 draggable-col" draggable="true">
            <div class="p-2.5 px-3 bg-green-500/5 border-b border-white/5 text-[10px] font-black text-green-500 uppercase tracking-tighter shrink-0 flex justify-between items-center">
                <div class="flex items-center gap-1.5">
                    <span class="opacity-20 text-[8px]">⋮⋮</span>
                    <span>Últimas Facturas</span>
                </div>
                <button onclick="closePanel(this, event)" class="text-white/30 hover:text-red-400 font-bold font-mono text-sm px-1 transition-colors select-none">×</button>
            </div>
            <div class="flex-1 overflow-y-auto p-2 space-y-2 scroll-custom">
                <?php if (isset($res_arqueo) && $res_arqueo): while ($f = mysqli_fetch_assoc($res_arqueo)): ?>
                    <div class="hover-row flex justify-between items-center p-3 rounded-lg bg-white/5 border-r-4 border-green-500/30 hover:bg-green-500/5">
                        <div class="flex flex-col">
                            <span class="text-[12px] font-black text-white/90 italic">TICKET</span>
                            <span class="text-[10px] mono opacity-40 text-white">Hora: <?php echo substr($f['hora'], 0, 5); ?></span>
                        </div>
                        <div class="text-right">
                            <span class="text-lg font-black mono text-green-400">$<?php echo number_format($f['subtotal'], 0, ',', '.'); ?></span>
                        </div>
                    </div>
                <?php endwhile; endif; ?>
            </div>
        </div>
    </div>

    <!-- SCRIPTS CORE ENGINE -->
    <script>
        // 1. SELECTORES DE ENTORNO
        const tablesContainer = document.getElementById('tablesContainer');
        const container = document.getElementById('chartContainer');
        const tooltip   = document.getElementById('chartTooltip');
        let draggedNode = null;

        // 2. MOTOR DE PERSISTENCIA DE LAYOUT (LOCALSTORAGE)
        function saveLayoutState() {
            const panels = [...tablesContainer.querySelectorAll('.draggable-col')];
            const state = panels.map(panel => ({
                id: panel.dataset.id,
                hidden: panel.classList.contains('hidden')
            }));
            localStorage.setItem('chuck_os_layout', JSON.stringify(state));
        }

        function loadLayoutState() {
            const savedState = localStorage.getItem('chuck_os_layout');
            if (!savedState) return;

            const state = JSON.parse(savedState);
            
            state.forEach(panelState => {
                const panel = tablesContainer.querySelector(`[data-id="${panelState.id}"]`);
                if (panel) {
                    if (panelState.hidden) {
                        panel.classList.add('hidden');
                    } else {
                        panel.classList.remove('hidden');
                    }
                    tablesContainer.appendChild(panel);
                }
            });
            
            evalResetButton();
        }

        // 3. GESTIÓN DE TEMAS (MIDNIGHT / LIGHT)
        function toggleTheme(event) {
            if (event) event.stopPropagation();
            
            const body = document.getElementById('mainBody');
            const checkbox = document.getElementById('themeToggle');
            if (checkbox.checked) { 
                body.classList.remove('theme-light'); 
                localStorage.setItem('theme', 'midnight'); 
            } else { 
                body.classList.add('theme-light'); 
                localStorage.setItem('theme', 'light'); 
            }
        }
        
        window.onload = () => {
            if (localStorage.getItem('theme') === 'light') {
                document.getElementById('themeToggle').checked = false;
                document.getElementById('mainBody').classList.add('theme-light');
            }
            loadLayoutState();
        };

        // 4. GESTIÓN DINÁMICA DE PANELES (CERRAR / RESTAURAR)
        function closePanel(button, event) {
            event.stopPropagation(); 
            const panel = button.closest('.draggable-col');
            if (panel) {
                panel.classList.add('hidden');
                saveLayoutState();
                evalResetButton();
            }
        }

        // Restaurar estado de todos los paneles
        function resetPanels(event) {
            if (event) event.stopPropagation();
            
            document.querySelectorAll('.draggable-col').forEach(panel => {
                panel.classList.remove('hidden');
            });
            
            saveLayoutState();
            evalResetButton();
        }

        function evalResetButton() {
            const hiddenPanels = document.querySelectorAll('.draggable-col.hidden').length;
            const resetBtn = document.getElementById('resetLayoutBtn');
            if (resetBtn) {
                if (hiddenPanels > 0) {
                    resetBtn.classList.remove('hidden');
                } else {
                    resetBtn.classList.add('hidden');
                }
            }
        }

        // 5. TOOLTIP DEL GRÁFICO SVG
        document.querySelectorAll('.chart-dot').forEach(dot => {
            dot.addEventListener('mouseenter', function() {
                document.getElementById('tooltipHora').textContent  = this.dataset.hora;
                document.getElementById('tooltipMonto').textContent = this.dataset.monto;
                tooltip.classList.remove('hidden');
            });
            dot.addEventListener('mousemove', function(e) {
                const rect = container.getBoundingClientRect();
                let tx = e.clientX - rect.left + 14;
                let ty = e.clientY - rect.top - 40;
                if (tx + 120 > rect.width) tx -= 130;
                if (ty < 0) ty = 5;
                tooltip.style.left = tx + 'px';
                tooltip.style.top  = ty + 'px';
            });
            dot.addEventListener('mouseleave', function() {
                tooltip.classList.add('hidden');
            });
        });

        // 6. ENGINE DRAG & DROP NATURALEZA FLUIDA
        tablesContainer.addEventListener('dragstart', (e) => {
            draggedNode = e.target.closest('.draggable-col');
            if (draggedNode) {
                draggedNode.classList.add('dragging');
            }
        });

        tablesContainer.addEventListener('dragend', (e) => {
            if (draggedNode) {
                draggedNode.classList.remove('dragging');
                draggedNode = null;
                saveLayoutState();
            }
        });

        tablesContainer.addEventListener('dragover', (e) => {
            e.preventDefault();
            const nextSibling = getDragAfterElement(tablesContainer, e.clientX);
            if (nextSibling == null) {
                tablesContainer.appendChild(draggedNode);
            } else {
                tablesContainer.insertBefore(draggedNode, nextSibling);
            }
        });

        function getDragAfterElement(container, x) {
            const draggableElements = [...container.querySelectorAll('.draggable-col:not(.dragging):not(.hidden)')];
            return draggableElements.reduce((closest, child) => {
                const box = child.getBoundingClientRect();
                const offset = x - box.left - box.width / 2;
                if (offset < 0 && offset > closest.offset) {
                    return { offset: offset, element: child };
                } else {
                    return { closest };
                }
            }, { offset: Number.NEGATIVE_INFINITY }).element;
        }

        // 7. Auto-refresh de 30s respetuoso con el layout
        setInterval(() => { 
            location.reload(); 
        }, 30000);
    </script>
</body>
</html>