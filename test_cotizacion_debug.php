<?php
// Test script to access cotizaciones_view.php and capture debug output
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', '/Users/gorila/Desktop/CLONE/GIT/DRSTUDIO/debug.log');

echo "Testing cotizaciones_view.php with debug output...\n";

// Simulate the GET parameter
$_GET['id'] = 15;

// Capture output
ob_start();

// Include the file
include 'admin/cotizaciones_view.php';

$output = ob_get_clean();

echo "Output captured. Check debug.log for detailed logs.\n";

// Show a summary of what was displayed
if (strpos($output, 'DRY FIY') !== false) {
    echo "✓ DRY FIY material found in output\n";
} else {
    echo "✗ DRY FIY material NOT found in output\n";
}

if (strpos($output, 'Algodon') !== false) {
    echo "✓ Algodon material found in output\n";
} else {
    echo "✗ Algodon material NOT found in output\n";
}

// Show the variant display section
if (preg_match('/<td>.*?<span class="badge bg-light text-dark">(.*?)<\/span>.*?<\/td>/s', $output, $matches)) {
    echo "Variant displays found:\n";
    foreach ($matches as $i => $match) {
        if ($i > 0) { // Skip the full match
            echo "  " . strip_tags($match) . "\n";
        }
    }
}
?>
