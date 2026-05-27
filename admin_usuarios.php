<?php
/**
 * CHUCK RETAIL INTELLIGENCE - ADMIN USUARIOS
 * Archivo: admin_usuarios.php
 */
session_start();
require_once 'conexion.php';

// Verificación de acceso
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

// LÓGICA DE BORRADO
if (isset($_GET['borrar'])) {
    $id_a_borrar = (int)$_GET['borrar'];
    if ($id_a_borrar > 0) {
        // Usamos cotishowadm.usuarios para asegurar que borra en la tabla correcta
        $stmt = mysqli_prepare($conn, "DELETE FROM cotishowadm.usuarios WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $id_a_borrar);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
    header("Location: admin_usuarios.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Administrar Usuarios | Chuck OS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@500;800&family=Inter:wght@400;700&display=swap');
        body { background-color: #050505; color: #c9d1d9; font-family: 'Inter', sans-serif; }
        .panel { background: #0d1117; border: 1px solid rgba(255,255,255,0.05); border-radius: 12px; }
        .mono { font-family: 'JetBrains Mono', monospace; }
    </style>
</head>
<body class="p-8">

    <div class="max-w-4xl mx-auto">
        <div class="flex justify-between items-end mb-6">
            <div>
                <h1 class="text-2xl font-black text-white mono tracking-tighter">ADMIN_USUARIOS</h1>
                <p class="text-[12px] text-gray-500">Gestión del personal y accesos al sistema.</p>
            </div>
            <div class="flex gap-2">
                <a href="index.php" class="text-[10px] mono font-bold bg-gray-800 text-gray-400 px-3 py-2 rounded hover:bg-gray-700 transition-all">[ VOLVER AL DASHBOARD ]</a>
                <a href="crear_usuario.php" class="text-[10px] mono font-bold bg-blue-500 text-white px-3 py-2 rounded hover:bg-blue-600 transition-all">[ + NUEVO USUARIO ]</a>
            </div>
        </div>

        <div class="panel overflow-hidden">
            <?php
                if (isset($_SESSION['mensaje'])) {
                    echo '<div class="bg-green-500/10 text-green-400 p-3 mb-4 rounded border border-green-500/20 text-[12px]">' . $_SESSION['mensaje'] . '</div>';
                    unset($_SESSION['mensaje']); // Limpiamos el mensaje para que no aparezca al recargar
                }
            ?>
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="text-[10px] uppercase text-gray-500 border-b border-white/10">
                        <th class="p-4">ID</th>
                        <th class="p-4">Usuario</th>
                        <th class="p-4 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="text-[13px]">
                    <?php
                    // Usamos cotishowadm.usuarios para asegurar que consulta en la base de datos correcta
                    $query = "SELECT id, username FROM cotishowadm.usuarios";
                    $res = mysqli_query($conn, $query);
                    
                    if ($res && mysqli_num_rows($res) > 0) {
                        while ($row = mysqli_fetch_assoc($res)) {
                            echo '<tr class="border-b border-white/5 hover:bg-blue-900/5">';
                            echo '<td class="p-4 mono text-gray-400">#' . $row['id'] . '</td>';
                            echo '<td class="p-4 text-white font-bold">' . htmlspecialchars($row['username']) . '</td>';
                            echo '<td class="p-4 text-right flex justify-end gap-2">';
                            
                            // Botón Editar
                            echo '<a href="editar_usuario.php?id=' . $row['id'] . '" class="bg-blue-500/10 text-blue-400 text-[10px] px-3 py-1.5 rounded border border-blue-500/20 hover:bg-blue-500 hover:text-white transition-all">[EDITAR]</a>';
                            
                            // Botón Borrar
                            echo '<a href="?borrar=' . $row['id'] . '" onclick="return confirm(\'¿Eliminar usuario ' . htmlspecialchars($row['username']) . '?\');" class="bg-red-500/10 text-red-400 text-[10px] px-3 py-1.5 rounded border border-red-500/20 hover:bg-red-500 hover:text-white transition-all">[BORRAR]</a>';
                            
                            echo '</td>';
                            echo '</tr>';
                        }
                    } else {
                        echo '<tr><td colspan="3" class="p-8 text-center text-gray-500 italic">No hay usuarios registrados.</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

</body>
</html>