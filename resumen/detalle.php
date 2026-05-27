<?php
require_once "../config.php";

// Obtener la fecha del parámetro en la URL
$fecha = isset($_GET['dia']) ? $_GET['dia'] : '';

if (!$fecha) {
    die("Fecha no especificada.");
}

// Conectar a la base de datos cotishowcash
$mysqli = new mysqli(DB_HOST1, DB_USER1, DB_PASS1, DB_NAME1); // Asegúrate de que DB_HOST2, DB_USER2, DB_PASS2 y DB_NAME2 están correctamente definidos

if ($mysqli->connect_error) {
    die("Error de conexión: " . $mysqli->connect_error);
}

// Consultar los productos vendidos en la fecha específica
$sql = "SELECT * FROM detallefactura WHERE fecha = ? and CodBarras_subProd NOT IN ('art. Multiplicacion', '999')";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param('s', $fecha);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles.css">
    <title>Detalle de Ventas - <?php echo htmlspecialchars($fecha); ?></title>
</head>
<body>
    <div class="container">
        <h1>Detalle de Ventas para el <?php echo htmlspecialchars($fecha); ?></h1>
        <table>
            <thead>
                <tr>
                    <th>ID Producto</th>
                    <th>Descripción</th>
                    <th>Cantidad</th>
                    <th>Precio</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($row['CodBarras_subProd']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['descripcion']) . "</td>";
                        
                        echo "<td>$" . number_format($row['Prec_venta'], 2, '.', ',') . "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='5'>No se encontraron resultados</td></tr>";
                }
                $stmt->close();
                $mysqli->close();
                ?>
            </tbody>
        </table>
    </div>
</body>
</html>
