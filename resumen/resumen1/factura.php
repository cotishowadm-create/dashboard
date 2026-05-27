<?php 
require_once "../../config.php"; 

// Variables para la ordenación
$orderColumn = isset($_GET['order']) ? $_GET['order'] : 'Id'; // Columna por defecto
$orderDirection = isset($_GET['direction']) ? $_GET['direction'] : 'ASC'; // Dirección por defecto

// Cambiar la dirección de orden si se hace clic en el mismo encabezado
if (isset($_GET['prev_order']) && $_GET['prev_order'] === $orderColumn) {
    $orderDirection = ($orderDirection === 'ASC') ? 'DESC' : 'ASC';
}

// Asegúrate de permitir solo ciertas columnas para evitar inyecciones SQL
$allowedColumns = ['Id', 'fecha', 'hora', 'idvendedor', 'subtotal', 'Tarjeta'];
if (!in_array($orderColumn, $allowedColumns)) {
    $orderColumn = 'Id'; // Si la columna no es válida, usar la por defecto
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles.css">
    <title>Reporte de Factura</title>
    <style>
        .details {
            display: none; /* Ocultar detalles por defecto */
        }
        #salesChart {
            width: 100%;
            height: 400px;
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        function toggleDetails(id) {
            var detailsRow = document.getElementById('details-' + id);
            if (detailsRow.style.display === 'none') {
                detailsRow.style.display = 'table-row'; // Mostrar detalles
            } else {
                detailsRow.style.display = 'none'; // Ocultar detalles
            }
        }

        function drawChart(data) {
            var ctx = document.getElementById('salesChart').getContext('2d');
            var salesChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.hours,
                    datasets: [{
                        label: 'Ventas (Subtotal)',
                        data: data.subtotals,
                        borderColor: 'rgba(75, 192, 192, 1)',
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        borderWidth: 1
                    }]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Subtotal ($)'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Hora'
                            }
                        }
                    }
                }
            });
        }
    </script>
</head>
<body>
    <div class="container">
        <h1>Reporte de Factura para el Día: <?php echo htmlspecialchars($_GET['dia']); ?></h1>
        <table>
            <thead>
                <tr>
                    <th></th> <!-- Columna para el símbolo de expansión -->
                    <th><a href="?dia=<?php echo urlencode($_GET['dia']); ?>&order=Id&direction=<?php echo ($orderColumn === 'Id' && $orderDirection === 'ASC') ? 'DESC' : 'ASC'; ?>">ID Factura</a></th>
                    <th><a href="?dia=<?php echo urlencode($_GET['dia']); ?>&order=fecha&direction=<?php echo ($orderColumn === 'fecha' && $orderDirection === 'ASC') ? 'DESC' : 'ASC'; ?>">Fecha</a></th>
                    <th><a href="?dia=<?php echo urlencode($_GET['dia']); ?>&order=hora&direction=<?php echo ($orderColumn === 'hora' && $orderDirection === 'ASC') ? 'DESC' : 'ASC'; ?>">Hora</a></th>
                    <th><a href="?dia=<?php echo urlencode($_GET['dia']); ?>&order=idvendedor&direction=<?php echo ($orderColumn === 'idvendedor' && $orderDirection === 'ASC') ? 'DESC' : 'ASC'; ?>">ID Vendedor</a></th>
                    <th><a href="?dia=<?php echo urlencode($_GET['dia']); ?>&order=subtotal&direction=<?php echo ($orderColumn === 'subtotal' && $orderDirection === 'ASC') ? 'DESC' : 'ASC'; ?>">Subtotal</a></th>
                    <th><a href="?dia=<?php echo urlencode($_GET['dia']); ?>&order=Tarjeta&direction=<?php echo ($orderColumn === 'Tarjeta' && $orderDirection === 'ASC') ? 'DESC' : 'ASC'; ?>">Tipo de Pago</a></th>
                </tr>
            </thead>
            <tbody>
            <?php
                $mysqli = new mysqli(DB_HOST1, DB_USER1, DB_PASS1, DB_NAME1);
                if ($mysqli->connect_error) {
                    die("Error de conexión: " . $mysqli->connect_error);
                }

                // Obtener la fecha desde el parámetro de URL
                $fechaConsulta = $_GET['dia'];
                
                // Consulta SQL para obtener los datos de la factura, utilizando el orden especificado
                $sql = "SELECT Id, fecha, hora, idvendedor, subtotal,
                        CASE
                            WHEN Tarjeta = 1 THEN 'Débito'
                            WHEN Tarjeta = 2 THEN 'Crédito'
                            WHEN Tarjeta = 3 THEN 'Efectivo'
                            WHEN Tarjeta = 4 THEN 'MercadoPago'
                            ELSE 'Otro'
                        END AS TipoPago
                        FROM factura 
                        WHERE fecha = ?
                        ORDER BY $orderColumn $orderDirection"; // Ordenar por la columna seleccionada

                // Preparar y ejecutar la consulta
                $stmt = $mysqli->prepare($sql);
                $stmt->bind_param("s", $fechaConsulta);
                $stmt->execute();
                $result = $stmt->get_result();

                // Datos para el gráfico
                $salesData = [];
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $idFactura = htmlspecialchars($row['Id']);
                        echo "<tr>";
                        echo "<td><a href='javascript:void(0);' onclick='toggleDetails($idFactura)'>+</a></td>"; // Símbolo de expansión
                        echo "<td>" . $idFactura . "</td>"; // ID Factura
                        echo "<td>" . htmlspecialchars($row['fecha']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['hora']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['idvendedor']) . "</td>";
                        echo "<td>$" . number_format($row['subtotal'], 2, '.', ',') . "</td>";
                        echo "<td>" . htmlspecialchars($row['TipoPago']) . "</td>"; // Tipo de Pago
                        echo "</tr>";

                        // Agregar los datos al gráfico
                        $hour = date('H', strtotime($row['hora'])); // Extraer la hora
                        if (!isset($salesData[$hour])) {
                            $salesData[$hour] = 0; // Inicializar si no existe
                        }
                        $salesData[$hour] += $row['subtotal']; // Acumular el subtotal

                        // Detalles de la factura (ocultos por defecto)
                        echo "<tr id='details-$idFactura' class='details'>";
                        echo "<td colspan='7'>"; // Abarca todas las columnas
                        echo "Detalles de la factura: "; // Aquí puedes incluir más detalles o un nuevo fetch a la tabla detallefactura.
                        // Consulta a la tabla detallefactura
                        $sqlDetalle = "SELECT CodBarras_subProd, descripcion, Prec_venta, Unidades_Sub FROM detallefactura WHERE idfactura = ?";
                        $stmtDetalle = $mysqli->prepare($sqlDetalle);
                        $stmtDetalle->bind_param("d", $idFactura); // Cambiar a 'd' para tipo double
                        $stmtDetalle->execute();
                        $resultDetalle = $stmtDetalle->get_result();

                        if ($resultDetalle->num_rows > 0) {
                            echo "<table><thead><tr><th>Producto</th><th>Cantidad</th><th>Precio</th></tr></thead><tbody>";
                            while ($detalle = $resultDetalle->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td>" . htmlspecialchars($detalle['descripcion']) . "</td>"; // Descripción del producto
                                echo "<td>" . htmlspecialchars($detalle['Unidades_Sub']) . "</td>"; // Cantidad
                                echo "<td>$" . number_format($detalle['Prec_venta'], 2, '.', ',') . "</td>"; // Precio
                                echo "</tr>";
                            }
                            echo "</tbody></table>";
                        } else {
                            echo "No hay detalles disponibles para esta factura.";
                        }

                        echo "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='7'>No se encontraron registros.</td></tr>";
                }

                // Preparar datos para el gráfico
                $hours = range(0, 23); // Rango de horas
                $subtotals = array_fill(0, 24, 0); // Inicializar los subtotales por hora

                foreach ($salesData as $hour => $subtotal) {
                    $subtotals[$hour] = $subtotal; // Asignar los subtotales correspondientes
                }

                $stmt->close();
                $mysqli->close();
            ?>
            </tbody>
        </table>
        <canvas id="salesChart"></canvas>
    </div>
    <script>
        // Llamar a la función para dibujar el gráfico con los datos preparados
        drawChart({ hours: <?php echo json_encode(array_map('strval', $hours)); ?>, subtotals: <?php echo json_encode($subtotals); ?> });
    </script>
</body>
</html>
