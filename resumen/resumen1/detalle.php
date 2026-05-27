<?php
require_once "../../config.php";

// Obtener la fecha del parámetro en la URL
$fecha = isset($_GET['dia']) ? $_GET['dia'] : '';

if (!$fecha) {
    die("Fecha no especificada.");
}

// Conectar a la base de datos cotishowcash
$mysqli = new mysqli(DB_HOST1, DB_USER1, DB_PASS1, DB_NAME1);

if ($mysqli->connect_error) {
    die("Error de conexión: " . $mysqli->connect_error);
}

// Consultar los productos vendidos en la fecha específica
$sql = "SELECT * FROM detallefactura WHERE fecha = ? AND CodBarras_subProd NOT IN ('art. Multiplicacion', '999')";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param('s', $fecha);
$stmt->execute();
$result = $stmt->get_result();

// Agrupar resultados por idfactura
$facturas = [];
while ($row = $result->fetch_assoc()) {
    $facturas[$row['idfactura']][] = $row; // Agrupamos por idfactura
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles.css">
    <title>Detalle de Ventas - <?php echo htmlspecialchars($fecha); ?></title>
    <style>
        .hidden { display: none; }
        .toggle { cursor: pointer; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Detalle de Ventas para el <?php echo htmlspecialchars($fecha); ?></h1>
        <table>
            <thead>
                <tr>
                    <th>Acción</th>
                    <th>ID Factura</th>
                    <th>Descripción</th>
                    <th>Precio</th>
                    <th>Fecha</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($facturas as $idfactura => $rows): ?>
                    <tr class="toggle" onclick="toggleDetails('<?php echo $idfactura; ?>')">
                        <td class="toggle"><span id="toggle-<?php echo $idfactura; ?>">+</span></td>
                        <td><?php echo htmlspecialchars($idfactura); ?></td>
                        <td colspan="3"><?php echo htmlspecialchars($rows[0]['descripcion']); ?></td>
                    </tr>
                    <tr id="details-<?php echo $idfactura; ?>" class="hidden">
                        <td colspan="5">
                            <table>
                                <tr>
                                    <th>ID Factura</th>
                                    <th>Precio</th>
                                    <th>Fecha</th>
                                    <th>Hora</th>
                                </tr>
                                <?php foreach ($rows as $detail): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($detail['idfactura']); ?></td>
                                        <td>$<?php echo number_format($detail['Prec_venta'], 2, '.', ','); ?></td>
                                        <td><?php echo htmlspecialchars($detail['fecha']); ?></td>
                                        <td><?php echo htmlspecialchars($detail['hora']); ?></td> <!-- Asegúrate de que la columna hora existe -->
                                    </tr>
                                <?php endforeach; ?>
                            </table>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($facturas)): ?>
                    <tr><td colspan='5'>No se encontraron resultados</td></tr>
                <?php endif; ?>
                <?php
                $stmt->close();
                $mysqli->close();
                ?>
            </tbody>
        </table>
    </div>

    <script>
        function toggleDetails(id) {
            const detailsRow = document.getElementById('details-' + id);
            const toggleSymbol = document.getElementById('toggle-' + id);
            
            if (detailsRow.classList.contains('hidden')) {
                detailsRow.classList.remove('hidden');
                toggleSymbol.innerText = '-'; // Cambiar a símbolo -
            } else {
                detailsRow.classList.add('hidden');
                toggleSymbol.innerText = '+'; // Cambiar a símbolo +
            }
        }
    </script>
</body>
</html>
