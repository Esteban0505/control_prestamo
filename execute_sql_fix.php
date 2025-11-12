<?php
// Script para ejecutar la corrección del campo status en loan_items
// Conecta a la base de datos y ejecuta el ALTER TABLE

$servername = "localhost";
$username = "root";
$password = ""; // Cambia esto si tienes contraseña
$dbname = "prestamobd";

try {
    // Crear conexión
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Verificar conexión
    if ($conn->connect_error) {
        throw new Exception("Conexión fallida: " . $conn->connect_error);
    }

    echo "Conectado exitosamente a la base de datos.\n";

    // Verificar el estado actual del campo status
    echo "\nEstado actual del campo status en loan_items:\n";
    $result = $conn->query("DESCRIBE loan_items");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            if ($row['Field'] == 'status') {
                echo "Campo: " . $row['Field'] . "\n";
                echo "Tipo: " . $row['Type'] . "\n";
                echo "Null: " . $row['Null'] . "\n";
                echo "Default: " . $row['Default'] . "\n";
                break;
            }
        }
    }

    // Ejecutar el ALTER TABLE
    echo "\nEjecutando ALTER TABLE...\n";
    $sql = "ALTER TABLE loan_items MODIFY status TINYINT(1) NOT NULL DEFAULT 1";

    if ($conn->query($sql) === TRUE) {
        echo "Campo status modificado exitosamente de bit(1) a tinyint(1).\n";
    } else {
        throw new Exception("Error al modificar el campo: " . $conn->error);
    }

    // Verificar el cambio
    echo "\nVerificando el cambio:\n";
    $result = $conn->query("DESCRIBE loan_items");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            if ($row['Field'] == 'status') {
                echo "Campo: " . $row['Field'] . "\n";
                echo "Tipo: " . $row['Type'] . "\n";
                echo "Null: " . $row['Null'] . "\n";
                echo "Default: " . $row['Default'] . "\n";
                break;
            }
        }
    }

    echo "\n¡Corrección completada exitosamente!\n";
    echo "Ahora el campo status puede almacenar valores como 0, 1, 3, 4.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>