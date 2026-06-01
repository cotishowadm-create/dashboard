<?php
/**
 * CHUCK RETAIL INTELLIGENCE - CREAR USUARIO
 */
session_start();
require_once 'conexion.php';

// Verificación de acceso
'if (!isset($_SESSION['usuario_id'])) {
 '   header("Location: login.php");
  '  exit();
'}

// LÓGICA DE CREACIÓN
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    if (!empty($username) && !empty($password)) {
        // Encriptamos la contraseña por seguridad
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        // Insertamos en cotishowadm.usuarios
        $stmt = mysqli_prepare($conn, "INSERT INTO cotishowadm.usuarios (username, password) VALUES (?, ?)");
        mysqli_stmt_bind_param($stmt, "ss", $username, $password_hash);
        
        if (mysqli_stmt_execute($stmt)) {
            // Mensaje de éxito para mostrar en la siguiente página
            $_SESSION['mensaje'] = "Usuario '{$username}' creado correctamente.";
            header("Location: admin_usuarios.php");
            exit();
        } else {
            $error = "Error al crear el usuario: " . mysqli_error($conn);
        }
        mysqli_stmt_close($stmt);
    } else {
        $error = "Todos los campos son obligatorios.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Crear Usuario | Chuck OS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@500;800&family=Inter:wght@400;700&display=swap');
        body { background-color: #050505; color: #c9d1d9; font-family: 'Inter', sans-serif; }
        .panel { background: #0d1117; border: 1px solid rgba(255,255,255,0.05); border-radius: 12px; }
    </style>
</head>
<body class="p-8">

    <div class="max-w-md mx-auto">
        <h1 class="text-xl font-black text-white mb-6 tracking-tighter">NUEVO_USUARIO</h1>

        <?php if (isset($error)): ?>
            <div class="bg-red-500/10 text-red-400 p-3 mb-4 rounded border border-red-500/20 text-[12px]"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" class="panel p-6 space-y-4">
            <div>
                <label class="block text-[10px] uppercase text-gray-500 font-bold mb-1">Nombre de Usuario</label>
                <input type="text" name="username" required class="w-full bg-black/50 border border-white/10 rounded p-2 text-white outline-none focus:border-blue-500 transition-all">
            </div>
            <div>
                <label class="block text-[10px] uppercase text-gray-500 font-bold mb-1">Contraseña</label>
                <input type="password" name="password" required class="w-full bg-black/50 border border-white/10 rounded p-2 text-white outline-none focus:border-blue-500 transition-all">
            </div>
            
            <div class="flex gap-2 pt-4">
                <a href="admin_usuarios.php" class="flex-1 text-center bg-gray-800 text-gray-400 py-2 rounded text-[12px] font-bold hover:bg-gray-700 transition-all">CANCELAR</a>
                <button type="submit" class="flex-1 bg-blue-600 text-white py-2 rounded text-[12px] font-bold hover:bg-blue-500 transition-all">GUARDAR USUARIO</button>
            </div>
        </form>
    </div>

</body>
</html>
