<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test AJAX Access</title>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
    <div class="container mt-5">
        <h1>Test de Acceso AJAX</h1>
        <button id="testBtn" class="btn btn-primary">Probar AJAX</button>
        <div id="result" class="mt-3"></div>
    </div>

    <script>
    document.getElementById('testBtn').addEventListener('click', function() {
        console.log('Probando acceso AJAX...');
        
        // Probar acceso a test_ajax_simple.php
        fetch('../test_ajax_simple.php')
        .then(response => {
            console.log('Respuesta test_ajax_simple.php:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('Datos recibidos:', data);
            document.getElementById('result').innerHTML = `
                <div class="alert alert-success">
                    <h5>Test AJAX Simple - ✅ Exitoso</h5>
                    <p><strong>Mensaje:</strong> ${data.message}</p>
                    <p><strong>Servidor:</strong> ${data.server}</p>
                    <p><strong>Timestamp:</strong> ${data.timestamp}</p>
                </div>
            `;
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('result').innerHTML = `
                <div class="alert alert-danger">
                    <h5>Test AJAX Simple - ❌ Error</h5>
                    <p>Error: ${error.message}</p>
                </div>
            `;
        });
        
        // Probar acceso a generate_pdf.php
        setTimeout(() => {
            console.log('Probando acceso a generate_pdf.php...');
            
            fetch('../ajax/generate_pdf_debug.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'test',
                    data: {}
                })
            })
            .then(response => {
                console.log('Respuesta generate_pdf_debug.php:', response.status);
                return response.json().then(data => {
                    console.log('Datos recibidos:', data);
                    document.getElementById('result').innerHTML += `
                        <div class="alert alert-info">
                            <h5>Test generate_pdf_debug.php</h5>
                            <p><strong>Status:</strong> ${response.status}</p>
                            <p><strong>Mensaje:</strong> ${data.message}</p>
                            <p><strong>Timestamp:</strong> ${data.timestamp}</p>
                        </div>
                    `;
                });
            })
            .catch(error => {
                console.error('Error generate_pdf_debug.php:', error);
                document.getElementById('result').innerHTML += `
                    <div class="alert alert-warning">
                        <h5>Test generate_pdf_debug.php - ❌ Error</h5>
                        <p>Error: ${error.message}</p>
                    </div>
                `;
            });
        }, 1000);
    });
    </script>
</body>
</html>
