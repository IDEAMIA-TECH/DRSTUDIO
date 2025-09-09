<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

header('Content-Type: application/json');

error_log('Test Email Sender - Iniciando prueba');

try {
    require_once 'includes/EmailSender.php';
    error_log('Test Email Sender - EmailSender incluido correctamente');
    
    $emailSender = new EmailSender();
    error_log('Test Email Sender - EmailSender instanciado correctamente');
    
    echo json_encode(['success' => true, 'message' => 'EmailSender funciona correctamente']);
    
} catch (Exception $e) {
    error_log('Test Email Sender - Error: ' . $e->getMessage());
    error_log('Test Email Sender - Stack trace: ' . $e->getTraceAsString());
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
