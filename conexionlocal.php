<?php
/**
 * CHUCK OS - CENTRAL CONNECTION ENGINE
 * Maneja dinámicamente la sucursal y la base de datos global de usuarios.
 */
// 1. CONFIGURACIÓN DE TUS SUCURSALES
$sucursales = array(
    1 => array('nombre' => 'Cotishow 1', 'host' => 'cotishow.duckdns.org', 'db' => 'cotishowcash'),
    2 => array('nombre' => 'VicenteLopezMg 2', 'host' => 'vicentelopezmg.duckdns.org', 'db' => 'cotishowcash'),
    3 => array('nombre' => 'Lomas 3', 'host' => 'cotishowlomas1.duckdns.org', 'db' => 'cotishowcash'),
    4 => array('nombre' => 'Ezeiza 4', 'host' => 'cotishowezeiza.duckdns.org', 'db' => 'cotishowcash'),
    5 => array('nombre' => 'Sucursal 5', 'host' => 'cotishowcanuelas.duckdns.org', 'db' => 'cotishowcash')
);

// Validación de sucursal activa
$sucursal_actual = isset($_GET['sucursal']) ? (int)$_GET['sucursal'] : 1;
if (!array_key_exists($sucursal_actual, $sucursales)) { 
    $sucursal_actual = 1; 
}

$host = $sucursales[$sucursal_actual]['host'];
$db   = $sucursales[$sucursal_actual]['db'];
$user = "cotishow";
$pass = "1234";

error_reporting(E_ERROR | E_PARSE);

// 1. CONEXIÓN RETAIL (Tu lógica original)
$conn = mysqli_connect($host, $user, $pass, $db);
if ($conn) {
    mysqli_set_charset($conn, "utf8mb4");
    mysqli_query($conn, "SET lc_time_names = 'es_AR'");
}

// 2. CONEXIÓN AUTH (Nueva: cotishowadm)
// Usamos las mismas credenciales pero apuntando a la base de usuarios
$db_auth = "cotishowadm";
$conn_auth = mysqli_connect($host, $user, $pass, $db_auth);

if ($conn_auth) {
    mysqli_set_charset($conn_auth, "utf8mb4");
}
?>