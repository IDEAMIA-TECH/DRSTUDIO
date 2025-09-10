<?php
// Test to simulate web access to cotizaciones_view.php
echo "Testing web access simulation...\n";

// Set up the environment
$_GET['id'] = 15;
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REQUEST_URI'] = '/admin/cotizaciones_view.php?id=15';

// Start output buffering
ob_start();

// Include the file
include 'admin/cotizaciones_view.php';

// Get the output
$output = ob_get_clean();

// Search for variant displays in the HTML
echo "Searching for variant displays in HTML output...\n\n";

// Look for the specific pattern
if (preg_match_all('/<td>.*?<span class="badge bg-light text-dark">(.*?)<\/span>.*?<\/td>/s', $output, $matches)) {
    echo "Found " . count($matches[1]) . " variant displays:\n";
    foreach ($matches[1] as $i => $display) {
        echo "  " . ($i + 1) . ": " . strip_tags($display) . "\n";
    }
} else {
    echo "No variant displays found in the expected format.\n";
}

// Also search for any mention of materials
echo "\nSearching for material mentions...\n";
if (preg_match_all('/(Algodon|DRY FIY|algodón|dry fit)/i', $output, $material_matches)) {
    echo "Found materials: " . implode(', ', array_unique($material_matches[0])) . "\n";
} else {
    echo "No material mentions found.\n";
}

// Check if the issue might be in the table structure
echo "\nChecking table structure...\n";
if (strpos($output, '<th>Variante</th>') !== false) {
    echo "✓ Variant column header found\n";
} else {
    echo "✗ Variant column header NOT found\n";
}

if (strpos($output, 'S,M,L,XL - AZUL - Algodon') !== false) {
    echo "✓ First variant (Algodon) found in output\n";
} else {
    echo "✗ First variant (Algodon) NOT found in output\n";
}

if (strpos($output, 'S,M,L,XL - AZUL - DRY FIY') !== false) {
    echo "✓ Second variant (DRY FIY) found in output\n";
} else {
    echo "✗ Second variant (DRY FIY) NOT found in output\n";
}

echo "\nTest completed.\n";
?>
