<?php
$pageTitle = 'Cotizador DTF - Playeras';
$pageActions = '<button type="button" class="btn btn-success" onclick="calcularCotizacion()">
    <i class="fas fa-calculator me-2"></i>Calcular Cotización
</button>';
require_once 'includes/header.php';
?>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-calculator me-2"></i>Cotizador de Playeras DTF
                </h5>
                <small class="text-muted">Herramienta interna para calcular precios de playeras con estampado DTF</small>
            </div>
            <div class="card-body">
                <form id="cotizadorForm">
                    <!-- Información del Diseño -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h6 class="text-primary mb-3">
                                <i class="fas fa-paint-brush me-2"></i>Información del Diseño
                            </h6>
                        </div>
                        <div class="col-md-6">
                            <label for="ancho_diseno" class="form-label">Ancho del Diseño (cm)</label>
                            <input type="number" class="form-control" id="ancho_diseno" name="ancho_diseno" 
                                   step="0.1" min="1" max="60" value="20" required>
                            <div class="form-text">Máximo 60 cm (ancho del film)</div>
                        </div>
                        <div class="col-md-6">
                            <label for="alto_diseno" class="form-label">Alto del Diseño (cm)</label>
                            <input type="number" class="form-control" id="alto_diseno" name="alto_diseno" 
                                   step="0.1" min="1" max="100" value="25" required>
                            <div class="form-text">Máximo 100 cm (largo del film)</div>
                        </div>
                    </div>

                    <!-- Configuración DTF -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h6 class="text-primary mb-3">
                                <i class="fas fa-print me-2"></i>Configuración DTF
                            </h6>
                        </div>
                        <div class="col-md-6">
                            <label for="costo_metro_lineal" class="form-label">Costo por Metro Lineal ($)</label>
                            <input type="number" class="form-control" id="costo_metro_lineal" name="costo_metro_lineal" 
                                   step="0.01" min="0" value="500" required>
                            <div class="form-text">Costo del proveedor por metro lineal</div>
                        </div>
                        <div class="col-md-6">
                            <label for="ancho_film" class="form-label">Ancho del Film (cm)</label>
                            <input type="number" class="form-control" id="ancho_film" name="ancho_film" 
                                   step="0.1" min="1" value="60" required>
                            <div class="form-text">Ancho estándar del film DTF</div>
                        </div>
                    </div>

                    <!-- Información de la Playera -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h6 class="text-primary mb-3">
                                <i class="fas fa-tshirt me-2"></i>Información de la Playera
                            </h6>
                        </div>
                        <div class="col-md-6">
                            <label for="tipo_playera" class="form-label">Tipo de Playera</label>
                            <select class="form-select" id="tipo_playera" name="tipo_playera" required>
                                <option value="algodon_blanca">Algodón Blanca</option>
                                <option value="algodon_color">Algodón Color</option>
                                <option value="dry_fit_blanca">Dry Fit Blanca</option>
                                <option value="dry_fit_color">Dry Fit Color</option>
                                <option value="polo_blanca">Polo Blanca</option>
                                <option value="polo_color">Polo Color</option>
                                <option value="sublimable">Sublimable</option>
                                <option value="premium">Premium</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="costo_playera" class="form-label">Costo de la Playera ($)</label>
                            <input type="number" class="form-control" id="costo_playera" name="costo_playera" 
                                   step="0.01" min="0" value="50" required>
                            <div class="form-text">Costo base de la playera</div>
                        </div>
                    </div>

                    <!-- Costos Adicionales -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h6 class="text-primary mb-3">
                                <i class="fas fa-plus-circle me-2"></i>Costos Adicionales
                            </h6>
                        </div>
                        <div class="col-md-4">
                            <label for="mano_obra" class="form-label">Mano de Obra ($)</label>
                            <input type="number" class="form-control" id="mano_obra" name="mano_obra" 
                                   step="0.01" min="0" value="5">
                        </div>
                        <div class="col-md-4">
                            <label for="margen_ganancia" class="form-label">Margen de Ganancia (%)</label>
                            <input type="number" class="form-control" id="margen_ganancia" name="margen_ganancia" 
                                   step="0.1" min="0" max="200" value="40">
                        </div>
                        <div class="col-md-4">
                            <label for="cantidad" class="form-label">Cantidad de Playeras</label>
                            <input type="number" class="form-control" id="cantidad" name="cantidad" 
                                   min="1" value="1">
                        </div>
                    </div>

                    <!-- Botones -->
                    <div class="row">
                        <div class="col-12">
                            <button type="button" class="btn btn-primary me-2" onclick="calcularCotizacion()">
                                <i class="fas fa-calculator me-2"></i>Calcular
                            </button>
                            <button type="button" class="btn btn-secondary me-2" onclick="limpiarFormulario()">
                                <i class="fas fa-eraser me-2"></i>Limpiar
                            </button>
                            <button type="button" class="btn btn-info" onclick="cargarPresets()">
                                <i class="fas fa-bookmark me-2"></i>Presets
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Panel de Resultados -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-chart-line me-2"></i>Resultados de la Cotización
                </h5>
            </div>
            <div class="card-body">
                <div id="resultados" class="d-none">
                    <!-- Resultados se mostrarán aquí -->
                </div>
                <div id="mensaje_inicial" class="text-center text-muted">
                    <i class="fas fa-calculator fa-3x mb-3"></i>
                    <p>Ingresa los datos del diseño y presiona "Calcular" para ver los resultados</p>
                </div>
            </div>
        </div>

        <!-- Presets Rápidos -->
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="fas fa-bookmark me-2"></i>Presets Rápidos
                </h6>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <button class="btn btn-outline-primary btn-sm" onclick="aplicarPreset('pequeno')">
                        <i class="fas fa-tag me-1"></i>Diseño Pequeño (10x10 cm)
                    </button>
                    <button class="btn btn-outline-primary btn-sm" onclick="aplicarPreset('mediano')">
                        <i class="fas fa-tag me-1"></i>Diseño Mediano (20x25 cm)
                    </button>
                    <button class="btn btn-outline-primary btn-sm" onclick="aplicarPreset('grande')">
                        <i class="fas fa-tag me-1"></i>Diseño Grande (30x40 cm)
                    </button>
                    <button class="btn btn-outline-primary btn-sm" onclick="aplicarPreset('playera_basica')">
                        <i class="fas fa-tshirt me-1"></i>Playera Básica
                    </button>
                    <button class="btn btn-outline-primary btn-sm" onclick="aplicarPreset('playera_premium')">
                        <i class="fas fa-star me-1"></i>Playera Premium
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Presets de configuración
const presets = {
    pequeno: {
        ancho_diseno: 10,
        alto_diseno: 10,
        tipo_playera: 'algodon_blanca',
        costo_playera: 45,
        margen_ganancia: 35
    },
    mediano: {
        ancho_diseno: 20,
        alto_diseno: 25,
        tipo_playera: 'algodon_blanca',
        costo_playera: 50,
        margen_ganancia: 40
    },
    grande: {
        ancho_diseno: 30,
        alto_diseno: 40,
        tipo_playera: 'algodon_color',
        costo_playera: 55,
        margen_ganancia: 45
    },
    playera_basica: {
        tipo_playera: 'algodon_blanca',
        costo_playera: 45,
        margen_ganancia: 35,
        mano_obra: 3
    },
    playera_premium: {
        tipo_playera: 'premium',
        costo_playera: 80,
        margen_ganancia: 50,
        mano_obra: 8
    }
};

// Aplicar preset
function aplicarPreset(presetName) {
    const preset = presets[presetName];
    if (preset) {
        Object.keys(preset).forEach(key => {
            const element = document.getElementById(key);
            if (element) {
                element.value = preset[key];
            }
        });
        calcularCotizacion();
    }
}

// Cargar presets en el botón
function cargarPresets() {
    // Esta función se puede expandir para mostrar un modal con más opciones
    showAlert('Selecciona un preset de los botones de la derecha', 'info');
}

// Limpiar formulario
function limpiarFormulario() {
    document.getElementById('cotizadorForm').reset();
    document.getElementById('ancho_diseno').value = 20;
    document.getElementById('alto_diseno').value = 25;
    document.getElementById('costo_metro_lineal').value = 500;
    document.getElementById('ancho_film').value = 60;
    document.getElementById('costo_playera').value = 50;
    document.getElementById('mano_obra').value = 5;
    document.getElementById('margen_ganancia').value = 40;
    document.getElementById('cantidad').value = 1;
    
    document.getElementById('resultados').classList.add('d-none');
    document.getElementById('mensaje_inicial').classList.remove('d-none');
}

// Calcular cotización
function calcularCotizacion() {
    // Obtener valores del formulario
    const anchoDiseno = parseFloat(document.getElementById('ancho_diseno').value) || 0;
    const altoDiseno = parseFloat(document.getElementById('alto_diseno').value) || 0;
    const costoMetroLineal = parseFloat(document.getElementById('costo_metro_lineal').value) || 0;
    const anchoFilm = parseFloat(document.getElementById('ancho_film').value) || 0;
    const costoPlayera = parseFloat(document.getElementById('costo_playera').value) || 0;
    const manoObra = parseFloat(document.getElementById('mano_obra').value) || 0;
    const margenGanancia = parseFloat(document.getElementById('margen_ganancia').value) || 0;
    const cantidad = parseInt(document.getElementById('cantidad').value) || 1;

    // Validaciones
    if (anchoDiseno <= 0 || altoDiseno <= 0) {
        showAlert('Por favor ingresa dimensiones válidas para el diseño', 'warning');
        return;
    }

    if (costoMetroLineal <= 0 || anchoFilm <= 0) {
        showAlert('Por favor ingresa valores válidos para la configuración DTF', 'warning');
        return;
    }

    if (costoPlayera <= 0) {
        showAlert('Por favor ingresa un costo válido para la playera', 'warning');
        return;
    }

    // Cálculos
    const areaDiseno = anchoDiseno * altoDiseno; // cm²
    const areaFilmMetro = anchoFilm * 100; // cm² por metro lineal
    const costoPorCm2 = costoMetroLineal / areaFilmMetro;
    const costoDTF = areaDiseno * costoPorCm2;
    
    const subtotal = costoPlayera + costoDTF + manoObra;
    const margenDecimal = margenGanancia / 100;
    const precioUnitario = subtotal * (1 + margenDecimal);
    const precioTotal = precioUnitario * cantidad;

    // Mostrar resultados
    mostrarResultados({
        areaDiseno,
        costoPorCm2,
        costoDTF,
        costoPlayera,
        manoObra,
        subtotal,
        margenGanancia,
        precioUnitario,
        cantidad,
        precioTotal
    });
}

// Mostrar resultados
function mostrarResultados(datos) {
    const resultadosDiv = document.getElementById('resultados');
    const mensajeInicial = document.getElementById('mensaje_inicial');
    
    resultadosDiv.innerHTML = `
        <div class="row">
            <div class="col-12">
                <h6 class="text-success mb-3">
                    <i class="fas fa-check-circle me-2"></i>Cotización Calculada
                </h6>
            </div>
        </div>
        
        <!-- Resumen de Costos -->
        <div class="mb-3">
            <h6 class="text-primary">Resumen de Costos (por unidad)</h6>
            <div class="table-responsive">
                <table class="table table-sm">
                    <tbody>
                        <tr>
                            <td>Área del Diseño:</td>
                            <td class="text-end"><strong>${datos.areaDiseno.toFixed(1)} cm²</strong></td>
                        </tr>
                        <tr>
                            <td>Costo por cm²:</td>
                            <td class="text-end">$${datos.costoPorCm2.toFixed(4)}</td>
                        </tr>
                        <tr>
                            <td>Costo DTF:</td>
                            <td class="text-end">$${datos.costoDTF.toFixed(2)}</td>
                        </tr>
                        <tr>
                            <td>Costo Playera:</td>
                            <td class="text-end">$${datos.costoPlayera.toFixed(2)}</td>
                        </tr>
                        <tr>
                            <td>Mano de Obra:</td>
                            <td class="text-end">$${datos.manoObra.toFixed(2)}</td>
                        </tr>
                        <tr class="table-light">
                            <td><strong>Subtotal:</strong></td>
                            <td class="text-end"><strong>$${datos.subtotal.toFixed(2)}</strong></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Precio Final -->
        <div class="mb-3">
            <h6 class="text-success">Precio Final</h6>
            <div class="alert alert-success">
                <div class="row">
                    <div class="col-6">
                        <small>Precio Unitario:</small><br>
                        <strong class="h5">$${datos.precioUnitario.toFixed(2)}</strong>
                    </div>
                    <div class="col-6">
                        <small>Total (${datos.cantidad} unidades):</small><br>
                        <strong class="h5">$${datos.precioTotal.toFixed(2)}</strong>
                    </div>
                </div>
                <hr>
                <div class="text-center">
                    <small class="text-muted">
                        Margen de ganancia: ${datos.margenGanancia}% | 
                        Ganancia por unidad: $${(datos.precioUnitario - datos.subtotal).toFixed(2)}
                    </small>
                </div>
            </div>
        </div>

        <!-- Botones de Acción -->
        <div class="d-grid gap-2">
            <button class="btn btn-success" onclick="copiarCotizacion()">
                <i class="fas fa-copy me-2"></i>Copiar Cotización
            </button>
            <button class="btn btn-info" onclick="exportarCotizacion()">
                <i class="fas fa-download me-2"></i>Exportar PDF
            </button>
        </div>
    `;
    
    resultadosDiv.classList.remove('d-none');
    mensajeInicial.classList.add('d-none');
}

// Copiar cotización al portapapeles
function copiarCotizacion() {
    const resultados = document.getElementById('resultados').innerText;
    navigator.clipboard.writeText(resultados).then(() => {
        showAlert('Cotización copiada al portapapeles', 'success');
    }).catch(() => {
        showAlert('Error al copiar la cotización', 'danger');
    });
}

// Exportar cotización (placeholder)
function exportarCotizacion() {
    showAlert('Función de exportación en desarrollo', 'info');
}

// Cálculo automático al cambiar valores
document.addEventListener('DOMContentLoaded', function() {
    const inputs = document.querySelectorAll('#cotizadorForm input, #cotizadorForm select');
    inputs.forEach(input => {
        input.addEventListener('input', function() {
            // Calcular automáticamente después de un pequeño delay
            clearTimeout(window.calculoTimeout);
            window.calculoTimeout = setTimeout(calcularCotizacion, 500);
        });
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
