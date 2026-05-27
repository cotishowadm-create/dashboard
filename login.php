<?php
session_start();
require_once 'conexion.php'; // Trae $conn_auth automáticamente

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user = mysqli_real_escape_string($conn_auth, $_POST['username']);
    $pass = $_POST['password'];

    $stmt = mysqli_prepare($conn_auth, "SELECT id, password FROM usuarios WHERE username = ?");
    mysqli_stmt_bind_param($stmt, "s", $user);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        if (password_verify($pass, $row['password'])) {
            session_regenerate_id(true);
            $_SESSION['usuario_id'] = $row['id'];
            $_SESSION['usuario_name'] = $user;
            header("Location: index.php"); // Tu dashboard
            exit();
        }
    }
    $error = "Credenciales incorrectas.";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Chuck OS // AUTH</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@500;800&family=Inter:wght@400;700&display=swap');
        body { background-color: #050505; font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="h-screen flex items-center justify-center">
    <div class="w-80 p-6 bg-[#0d1117] border border-white/10 rounded-xl shadow-2xl">
        <h1 class="text-white font-black text-lg mb-6 mono tracking-widest text-center">CHUCK OS // ACCESS</h1>
        <form method="POST" class="flex flex-col gap-3">
            <input type="text" name="username" placeholder="USUARIO" required class="bg-black border border-white/10 p-2.5 rounded-lg text-white text-xs mono focus:outline-none focus:border-blue-500">
            <input type="password" name="password" placeholder="PASSWORD" required class="bg-black border border-white/10 p-2.5 rounded-lg text-white text-xs mono focus:outline-none focus:border-blue-500">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 rounded-lg text-[10px] tracking-widest uppercase transition-all mt-2">Ingresar</button>
        </form>
        <?php if ($error): ?><p class="text-red-500 text-[10px] mt-4 text-center mono font-bold"><?php echo $error; ?></p><?php endif; ?>
    </div>
</body>
</html>