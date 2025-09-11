<?php
// Test simple de formulario
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'test_form_simple.log');

echo "=== TEST FORM SIMPLE ===\n";
echo "Timestamp: " . date('Y-m-d H:i:s') . "\n";
echo "REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD'] . "\n";
echo "POST data: " . print_r($_POST, true) . "\n";
echo "GET data: " . print_r($_GET, true) . "\n";

if ($_POST) {
    echo "=== FORMULARIO ENVIADO ===\n";
    echo "Nombre: " . ($_POST['nombre'] ?? 'NO ENVIADO') . "\n";
    echo "Email: " . ($_POST['email'] ?? 'NO ENVIADO') . "\n";
    echo "Mensaje: " . ($_POST['mensaje'] ?? 'NO ENVIADO') . "\n";
} else {
    echo "=== MOSTRANDO FORMULARIO ===\n";
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Test Form Simple</title>
    </head>
    <body>
        <h1>Test Form Simple</h1>
        <form method="POST">
            <p>
                <label>Nombre:</label><br>
                <input type="text" name="nombre" required>
            </p>
            <p>
                <label>Email:</label><br>
                <input type="email" name="email" required>
            </p>
            <p>
                <label>Mensaje:</label><br>
                <textarea name="mensaje" required></textarea>
            </p>
            <p>
                <button type="submit">Enviar</button>
            </p>
        </form>
    </body>
    </html>
    <?php
}
?>
