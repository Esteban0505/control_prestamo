<?php
// Test de conexión a la base de datos
define('BASEPATH', true);

// Configuración de la base de datos
$hostname = 'localhost';
$username = 'root';
$password = '';
$database = 'prestamobd';
$dbdriver = 'mysqli';

try {
    if ($dbdriver === 'mysqli') {
        $conn = new mysqli($hostname, $username, $password, $database);

        if ($conn->connect_error) {
            throw new Exception("Error de conexión: " . $conn->connect_error);
        }

        echo "Conexión exitosa a la base de datos '$database' usando mysqli.\n";

        // Verificar si podemos ejecutar una consulta simple
        $result = $conn->query("SELECT 1 as test");
        if ($result) {
            $row = $result->fetch_assoc();
            echo "Consulta de prueba exitosa: " . $row['test'] . "\n";
        } else {
            echo "Error en consulta de prueba: " . $conn->error . "\n";
        }

        $conn->close();
    } else {
        echo "Driver no soportado en esta prueba: $dbdriver\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>