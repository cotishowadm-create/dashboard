<?php require_once "../../config.php"; ?>
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
    </style>
    <script>
        function toggleDetails(id) {
            var detailsRow = document.getElementById('details-' + id);
            if (detailsRow.style.display === 'none') {
                detailsRow.style.display = 'table-row'; // Mostrar detalles
            } else {
                detailsRow.style.display = 'none'; // Ocultar detalles
            }
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
                    <th>ID Factura</th>
                    <th>Fecha</th>
                    <th>Hora</th>
                    <th>ID Vendedor</th>
                    <th>Subtotal</th>
                    <th>Tipo de Pago</th>
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
                
                // Consulta SQL para obtener los datos de la factura
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
                        ORDER BY Id DESC";

                // Preparar y ejecutar la consulta
                $stmt = $mysqli->prepare($sql);
                $stmt->bind_param("s", $fechaConsulta);
                $stmt->execute();
                $result = $stmt->get_result();

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

                        // Detalles de la factura (ocultos por defecto)
                        echo "<tr id='details-$idFactura' class='details'>";
                        echo "<td colspan='7'>"; // Abarca todas las columnas
                        echo "Detalles de la factura: "; // Aquí puedes incluir más detalles o un nuevo fetch a la tabla detallefactura.
                        // Agrega aquí la lógica para obtener y mostrar los detalles de la factura usando $idFactura
                        echo "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='7'>No se encontraron resultados para esta fecha.</td></tr>";
                }

                // Cerrar conexión
                $stmt->close();
                $mysqli->close();
            ?>
            </tbody>
        </table>
    </div>
</body>
</html>
