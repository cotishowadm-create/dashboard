<?php
/**
 * CHUCK OS - CENTRAL CONNECTION ENGINE (Railway Compatible)
 */

$sucursales = array(
    1 => array('nombre' => 'Cotishow 1', 'host' => 'cotishow.duckdns.org', 'db' => 'cotishowcash'),
    2 => array('nombre' => 'VicenteLopezMg 2', 'host' => 'vicentelopezmg.duckdns.org', 'db' => 'cotishowcash'),
    3 => array('nombre' => 'Lomas 3', 'host' => 'cotishowlomas1.duckdns.org', 'db' => 'cotishowcash'),
    4 => array('nombre' => 'Ezeiza 4', 'host' => 'cotishowezeiza.duckdns.org', 'db' => 'cotishowcash'),
    5 => array('nombre' => 'Sucursal 5', 'host' => 'cotishowcanuelas.duckdns.org', 'db' => 'cotishowcash')
);

$sucursal_actual = isset($_GET['sucursal']) ? (int)$_GET['sucursal'] : 1;
if (!array_key_exists($sucursal_actual, $sucursales)) { 
    $sucursal_actual = 1; 
}

// DETECTAR ENTORNO: Si existe la variable de Railway, la usa. Si no, usa tu configuración original.
if (getenv('MYSQLHOST')) {
    $host = getenv('MYSQLHOST');
    $user = getenv('MYSQLUSER');
    $pass = getenv('MYSQLPASSWORD');
    $db   = getenv('MYSQLDATABASE'); // Base retail por defecto
    $db_auth = "cotishowadm";        // Tu base de usuarios
} else {
    // Tu configuración local antigua
    $host = $sucursales[$sucursal_actual]['host'];
    $db   = $sucursales[$sucursal_actual]['db'];
    $user = "cotishow";
    $pass = "1234";
    $db_auth = "cotishowadm";
}

error_reporting(E_ERROR | E_PARSE);

// 1. CONEXIÓN RETAIL
$conn = mysqli_connect($host, $user, $pass, $db);
if ($conn) {
    mysqli_set_charset($conn, "utf8mb4");
    mysqli_query($conn, "SET lc_time_names = 'es_AR'");
}

// 2. CONEXIÓN AUTH
$conn_auth = mysqli_connect($host, $user, $pass, $db_auth);
if ($conn_auth) {
    mysqli_set_charset($conn_auth, "utf8mb4");
}
?>