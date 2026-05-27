<?php require_once "../config.php"; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles.css">
    
    <title>Resumen de Ventas</title>

</head>

    <div class="container">
        <h1>Resumen de Ventas (Últimos 30 Días)</h1>
        <table>
            <thead>
                <tr>
                    <th class="checkbox-column">Seleccionar</th>
                    <th>Fecha</th>
                    <th>Efectivo</th>
                    <th>Crédito</th>
                    <th>Débito</th>
                    <th>MercadoP</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $mysqli = new mysqli(DB_HOST1, DB_USER1, DB_PASS1, DB_NAME1);
                if ($mysqli->connect_error) {
                    die("Error de conexión: " . $mysqli->connect_error);
                }

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
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td class='checkbox-column'><input type='checkbox' class='check' data-efectivo='" . $row['Efectivo'] . "' data-credito='" . $row['Credito'] . "' data-debito='" . $row['Debito'] . "' data-mercadop='" . $row['MercadoP'] . "'/></td>";
                        echo "<td>" . $row['fecha'] . "</td>";
                        echo "<td>$" . number_format($row['Efectivo'], 2, '.', ',') . "</td>";
                        echo "<td>$" . number_format($row['Credito'], 2, '.', ',') . "</td>";
                        echo "<td>$" . number_format($row['Debito'], 2, '.', ',') . "</td>";
                        echo "<td>$" . number_format($row['MercadoP'], 2, '.', ',') . "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='6'>No se encontraron resultados</td></tr>";
                }
                $mysqli->close();
                ?>
            </tbody>
        </table>
    </div>

    <footer class="footer">
    <div class="calculator">
        <h2>Calculadora</h2>
        <div class="calculator-controls">
            <!-- Cambiar input type a text para permitir formateo -->
            <label for="nombre">Nombre:</label>
            <input type="text" id="calc-efectivo" placeholder="Efectivo" />
           
            <input type="text" id="calc-credito" placeholder="Crédito" />
            
            <input type="text" id="calc-debito" placeholder="Débito" />
           
            <input type="text" id="calc-mercadop" placeholder="MercadoP"  />
            <button id="calculate">Calcular</button>
        </div>
        <div class="calculator-result" id="result">Total: $0.00</div>
    </div>
    </footer>
</body>

    <script>    

        document.addEventListener('DOMContentLoaded', function() {
        const checkboxes = document.querySelectorAll('.check');
        const efectivoInput = document.getElementById('calc-efectivo');
        const creditoInput = document.getElementById('calc-credito');
        const debitoInput = document.getElementById('calc-debito');
        const mercadopInput = document.getElementById('calc-mercadop');
        const resultDiv = document.getElementById('result');

        function formatCurrency(number) {
            return '$' + number.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
        }

        function parseNumber(value) {
            return parseFloat(value.replace(/[^0-9.-]+/g, '')) || 0;
        }

        function updateTotals() {
            let efectivoTotal = 0;
            let creditoTotal = 0;
            let debitoTotal = 0;
            let mercadopTotal = 0;

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
            resultDiv.textContent = 'Total: ' + formatCurrency(total);
        }

        checkboxes.forEach(checkbox => {
            checkbox.addEventListener('change', updateTotals);
        });

        document.getElementById('calculate').addEventListener('click', updateTotals);
    });
    </script>
</html>
