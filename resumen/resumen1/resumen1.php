<?php 
require_once "../../config.php"; 

// Mapeo dinámico a las constantes de tu config.php
$host = defined('DB_HOST1') ? DB_HOST1 : 'localhost';
$user = defined('DB_USER1') ? DB_USER1 : 'root';
$pass = defined('DB_PASS1') ? DB_PASS1 : '';
$db   = defined('DB_NAME1') ? DB_NAME1 : 'cotishowcash';

$db_status = "ONLINE";
error_reporting(E_ERROR | E_PARSE);
$mysqli = new mysqli($host, $user, $pass, $db);

if ($mysqli->connect_error) {
    $db_status = "OFFLINE";
} else {
    $mysqli->set_charset("utf8mb4");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chuck OS - Resumen de Ventas de 30 Días</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght=500;800&family=Inter:wght=400;700;900&display=swap');
        
        :root { 
            --bg: #050505; 
            --panel: #0d1117; 
            --text: #c9d1d9; 
            --border: rgba(255,255,255,0.05); 
            --accent: #2f81f7; 
            --input-bg: #161b22;
            --chart-grid: rgba(255, 255, 255, 0.05);
        }
        body.theme-light { 
            --bg: #f0f2f5; 
            --panel: #ffffff; 
            --text: #1a1d23; 
            --border: rgba(0,0,0,0.1); 
            --accent: #0969da; 
            --input-bg: #f6f8fa;
            --chart-grid: rgba(0, 0, 0, 0.05);
        }
        
        body { 
            background-color: var(--bg); 
            color: var(--text); 
            font-family: 'Inter', sans-serif; 
            font-size: 13px; 
            transition: background 0.3s ease; 
        }
        
        .panel { 
            background: var(--panel); 
            border: 1px solid var(--border); 
            border-radius: 12px; 
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }
        .panel:hover { 
            border-color: rgba(47, 129, 247, 0.25); 
            box-shadow: 0 6px 24px rgba(47, 129, 247, 0.04);
        }
        
        .mono { font-family: 'JetBrains Mono', monospace; }
        .scroll-custom::-webkit-scrollbar { width: 4px; height: 4px; }
        .scroll-custom::-webkit-scrollbar-thumb { background: var(--accent); border-radius: 10px; }
        
        .custom-input {
            background-color: var(--input-bg);
            color: var(--text);
            border: 1px solid var(--border);
        }
        .custom-input:focus {
            border-color: var(--accent);
            outline: none;
        }
        
        .hover-row { transition: background-color 0.15s ease; }
        .hover-row:hover { background-color: rgba(47, 129, 247, 0.05); }
    </style>
</head>
<body class="h-screen flex flex-col p-4 gap-3 overflow-hidden" id="mainBody">

    <!-- CABECERA DEL DASHBOARD -->
    <div class="panel w-full flex justify-between items-center p-3 shrink-0">
        <div class="flex items-center gap-2">
            <span class="text-[12px] font-black mono text-white tracking-tighter">CHUCK OS <span class="text-blue-500 font-normal">v10.5</span></span>
            <span class="text-[9px] opacity-25 uppercase tracking-wider font-bold hidden sm:inline">| History Analytics Matrix</span>
            
            <div class="flex items-center border-l border-white/10 pl-2 ml-1 h-3 gap-2">
                <!-- Switch de Tema -->
                <label class="relative inline-block w-9 h-4.5 cursor-pointer">
                    <input type="checkbox" id="themeToggle" checked onchange="toggleTheme(event)" class="sr-only peer">
                    <span class="absolute inset-0 bg-[#30363d] peer-checked:bg-[var(--accent)] rounded-full transition-colors duration-300
                                 before:absolute before:content-[''] before:h-3 before:width-3 before:left-0.5 before:bottom-0.5 before:bg-white before:rounded-full before:transition-transform before:duration-300 peer-checked:before:translate-x-4.5"></span>
                </label>
                
                <span class="text-[10px] font-mono font-black <?php echo $db_status === 'ONLINE' ? 'text-green-400' : 'text-red-400'; ?>">
                    <?php echo $db_status; ?>
                </span>
            </div>
        </div>
        
        <h1 class="text-xs font-bold uppercase tracking-tight text-right text-white opacity-90">Resumen de Ventas (Últimos 30 Días)</h1>
    </div>

    <!-- CUERPO PRINCIPAL DIVIDIDO EN DOS MÓDULOS -->
    <div class="flex-1 flex gap-3 min-h-0 w-full">
        
        <!-- SECCIÓN IZQUIERDA: LA TABLA DE DATOS HISTÓRICOS -->
        <div class="flex-1 flex flex-col panel overflow-hidden">
            <div class="p-2.5 px-3 bg-white/5 border-b border-white/5 text-[10px] font-black text-blue-400 uppercase tracking-tighter shrink-0">
                Auditoría de Cierres Diarios
            </div>
            
            <div class="flex-1 overflow-auto scroll-custom p-1">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-white/5 text-[9px] uppercase tracking-wider mono opacity-50 text-white">
                            <th class="p-2.5 text-center w-14">Sel.</th>
                            <th class="p-2.5">Fecha</th>
                            <th class="p-2.5 text-right text-green-400">Efectivo</th>
                            <th class="p-2.5 text-right text-blue-400">Crédito</th>
                            <th class="p-2.5 text-right text-yellow-500">Débito</th>
                            <th class="p-2.5 text-right text-purple-400">MercadoP</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        <?php
                        if ($db_status === "ONLINE") {
                            $sql = "SELECT fecha,
                                        SUM(CASE WHEN tarjeta = 3 THEN subtotal ELSE 0 END) AS Efectivo,
                                        SUM(CASE WHEN tarjeta = 2 THEN subtotal ELSE 0 END) AS Credito,
                                        SUM(CASE WHEN tarjeta = 1 THEN subtotal ELSE 0 END) AS Debito,
                                        SUM(CASE WHEN tarjeta = 4 THEN subtotal ELSE 0 END) AS MercadoP
                                    FROM factura
                                    WHERE fecha >= CURDATE() - INTERVAL 30 DAY
                                    GROUP BY fecha
                                    ORDER BY fecha DESC";

                            $result = $mysqli->query($sql);
                            if ($result && $result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    echo "<tr class='hover-row font-medium'>";
                                    echo "<td class='p-2 text-center'><input type='checkbox' class='check rounded border-gray-700 text-blue-500 focus:ring-0 bg-transparent cursor-pointer' data-efectivo='" . $row['Efectivo'] . "' data-credito='" . $row['Credito'] . "' data-debito='" . $row['Debito'] . "' data-mercadop='" . $row['MercadoP'] . "'/></td>";
                                    echo "<td class='p-2 mono font-bold text-white'><a href='factura.php?dia=" . $row['fecha'] . "' class='hover:underline text-blue-400'>" . $row['fecha'] . "</a></td>";
                                    echo "<td class='p-2 text-right mono text-green-500'>$" . number_format($row['Efectivo'], 2, '.', ',') . "</td>";
                                    echo "<td class='p-2 text-right mono text-white'>$" . number_format($row['Credito'], 2, '.', ',') . "</td>";
                                    echo "<td class='p-2 text-right mono text-white'>$" . number_format($row['Debito'], 2, '.', ',') . "</td>";
                                    echo "<td class='p-2 text-right mono text-purple-400'>$" . number_format($row['MercadoP'], 2, '.', ',') . "</td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='6' class='p-4 text-center mono opacity-50'>No se encontraron registros en el rango establecido.</td></tr>";
                            }
                            $mysqli->close();
                        } else {
                            echo "<tr><td colspan='6' class='p-4 text-center mono text-red-400 font-bold'>BASE DE DATOS OFFLINE - COMPROBÁ LA CONFIGURACIÓN</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- SECCIÓN DERECHA: CALCULADORA ACUMULATIVA Y ANÁLISIS CHART -->
        <div class="w-80 flex flex-col gap-3 shrink-0">
            
            <!-- CONTENEDOR CALCULADORA -->
            <div class="panel flex flex-col p-3">
                <div class="text-[10px] font-black text-purple-400 uppercase tracking-tighter mb-2.5">
                    Calculadora de Consolidación
                </div>
                <div class="space-y-1.5 flex-1">
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-[10px] font-bold mono uppercase opacity-50 w-20">Efectivo</span>
                        <input type="text" id="calc-efectivo" class="custom-input text-right text-xs mono font-bold py-1 px-2 rounded-md w-full" readonly placeholder="$0.00" />
                    </div>
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-[10px] font-bold mono uppercase opacity-50 w-20">Crédito</span>
                        <input type="text" id="calc-credito" class="custom-input text-right text-xs mono font-bold py-1 px-2 rounded-md w-full" readonly placeholder="$0.00" />
                    </div>
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-[10px] font-bold mono uppercase opacity-50 w-20">Débito</span>
                        <input type="text" id="calc-debito" class="custom-input text-right text-xs mono font-bold py-1 px-2 rounded-md w-full" readonly placeholder="$0.00" />
                    </div>
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-[10px] font-bold mono uppercase opacity-50 w-20">MercadoP</span>
                        <input type="text" id="calc-mercadop" class="custom-input text-right text-xs mono font-bold py-1 px-2 rounded-md w-full" readonly placeholder="$0.00" />
                    </div>
                </div>
                
                <div class="border-t border-white/5 mt-3 pt-3 flex justify-between items-center">
                    <button id="calculate" class="text-[10px] mono font-bold bg-blue-500/20 text-blue-400 border border-blue-500/30 px-3 py-1 rounded hover:bg-blue-500 hover:text-white transition-all">
                        FORZAR CÁLCULO
                    </button>
                    <div class="text-right">
                        <p class="text-[8px] opacity-40 uppercase font-bold tracking-wider">Total Selección</p>
                        <div class="text-base font-black mono text-green-400" id="result">$0.00</div>
                    </div>
                </div>
            </div>

            <!-- CONTENEDOR DEL GRÁFICO BAR CHART -->
            <div class="panel flex-1 flex flex-col p-3 min-h-0">
                <div class="text-[10px] font-black text-yellow-500 uppercase tracking-tighter mb-2">
                    Mapeo de Flujo Acumulado
                </div>
                <div class="flex-1 relative min-h-0 w-full flex items-center justify-center">
                    <canvas id="ventasChart" class="w-full h-full"></canvas>
                </div>
            </div>
            
        </div>
    </div>

    <!-- SCRIPTS CORE ENGINE -->
    <script>
        let ventasChart;
        const checkboxes = document.querySelectorAll('.check');
        const efectivoInput = document.getElementById('calc-efectivo');
        const creditoInput = document.getElementById('calc-credito');
        const debitoInput = document.getElementById('calc-debito');
        const mercadopInput = document.getElementById('calc-mercadop');
        const resultDiv = document.getElementById('result');

        function formatCurrency(number) {
            return '$' + number.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
        }

        function updateTotals() {
            let efectivoTotal = 0, creditoTotal = 0, debitoTotal = 0, mercadopTotal = 0;

            checkboxes.forEach(checkbox => {
                if (checkbox.checked) {
                    efectivoTotal += parseFloat(checkbox.dataset.efectivo) || 0;
                    creditoTotal += parseFloat(checkbox.dataset.credito) || 0;
                    debitoTotal += parseFloat(checkbox.dataset.debito) || 0;
                    mercadopTotal += parseFloat(checkbox.dataset.mercadop) || 0;
                }
            });

            efectivoInput.value = formatCurrency(efectivoTotal);
            creditoInput.value = formatCurrency(creditoTotal);
            debitoInput.value = formatCurrency(debitoTotal);
            mercadopInput.value = formatCurrency(mercadopTotal);

            const total = efectivoTotal + creditoTotal + debitoTotal + mercadopTotal;
            resultDiv.textContent = formatCurrency(total);

            updateChart([efectivoTotal, creditoTotal, debitoTotal, mercadopTotal]);
        }

        // CONTROL DE TEMA DINÁMICO + ACTUALIZACIÓN DE GRÁFICO SUTIL
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
            
            if(ventasChart) {
                const isLight = body.classList.contains('theme-light');
                ventasChart.options.scales.x.grid.color = isLight ? 'rgba(0,0,0,0.05)' : 'rgba(255,255,255,0.05)';
                ventasChart.options.scales.y.grid.color = isLight ? 'rgba(0,0,0,0.05)' : 'rgba(255,255,255,0.05)';
                ventasChart.options.scales.x.ticks.color = isLight ? '#1a1d23' : '#c9d1d9';
                ventasChart.options.scales.y.ticks.color = isLight ? '#1a1d23' : '#c9d1d9';
                ventasChart.update();
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Inicializar persistencia de tema
            if (localStorage.getItem('theme') === 'light') {
                document.getElementById('themeToggle').checked = false;
                document.getElementById('mainBody').classList.add('theme-light');
            }

            checkboxes.forEach(cb => cb.addEventListener('change', updateTotals));
            document.getElementById('calculate').addEventListener('click', updateTotals);

            // ENGINE INITIALIZATION CHART.JS
            const ctx = document.getElementById('ventasChart').getContext('2d');
            const isLightMode = document.getElementById('mainBody').classList.contains('theme-light');
            
            ventasChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['Efectivo', 'Crédito', 'Débito', 'MercadoP'],
                    datasets: [{
                        label: 'Monto ($)',
                        data: [0, 0, 0, 0],
                        backgroundColor: [
                            'rgba(34, 197, 94, 0.25)',  // Verde Efectivo
                            'rgba(255, 255, 255, 0.25)', // Blanco/Negro Crédito
                            'rgba(245, 158, 11, 0.25)',  // Amarillo Débito
                            'rgba(168, 85, 247, 0.25)'   // Púrpura MP
                        ],
                        borderColor: [
                            '#22c55e', '#3b82f6', '#f59e0b', '#a855f7'
                        ],
                        borderWidth: 1.5,
                        borderRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: isLightMode ? 'rgba(0,0,0,0.05)' : 'rgba(255,255,255,0.05)' },
                            ticks: { font: { family: 'JetBrains Mono', size: 9 }, color: isLightMode ? '#1a1d23' : '#c9d1d9' }
                        },
                        x: {
                            grid: { display: false },
                            ticks: { font: { family: 'Inter', size: 10, weight: 'bold' }, color: isLightMode ? '#1a1d23' : '#c9d1d9' }
                        }
                    }
                }
            });
        });

        function updateChart(data) {
            if(ventasChart) {
                ventasChart.data.datasets[0].data = data;
                ventasChart.update();
            }
        }
    </script>
</body>
</html>