/**
 * Scripts para admin/cotizaciones_view.php
 * Configuración en #cotizacion-view-config, datos PDF en #cotizacion-pdf-data
 */
function getCotizacionConfig() {
    const el = document.getElementById('cotizacion-view-config');
    if (!el) {
        throw new Error('Configuración de cotización no encontrada');
    }
    return {
        id: parseInt(el.dataset.id, 10),
        estado: el.dataset.estado || '',
        total: parseFloat(el.dataset.total) || 0,
        cargarHistorial: el.dataset.cargarHistorial === '1'
    };
}

function getCotizacionPdfData() {
    const el = document.getElementById('cotizacion-pdf-data');
    if (!el) {
        throw new Error('Datos PDF de cotización no encontrados');
    }
    return JSON.parse(el.textContent);
}

function cambiarEstado(estado) {
    const config = getCotizacionConfig();
    const estados = {
        enviada: 'enviada',
        aceptada: 'aceptada',
        rechazada: 'rechazada',
        pagada: 'pagada',
        entregada: 'entregada'
    };
    const estadoTexto = estados[estado] || estado;

    if (!confirm('¿Estás seguro de marcar esta cotización como ' + estadoTexto + '?')) {
        return;
    }

    fetch('../ajax/cotizaciones.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=change_status&id=' + config.id + '&estado=' + encodeURIComponent(estado)
    })
        .then(function (response) {
            if (!response.ok) {
                throw new Error('HTTP error! status: ' + response.status);
            }
            return response.text();
        })
        .then(function (text) {
            const data = JSON.parse(text);
            if (data.success) {
                showAlert(data.message, 'success');
                location.reload();
            } else {
                showAlert(data.message, 'danger');
            }
        })
        .catch(function (error) {
            console.error('Error:', error);
            showAlert('Error al cambiar el estado de la cotización: ' + error.message, 'danger');
        });
}

function imprimirCotizacion() {
    window.print();
}

function exportarPDF() {
    const btn = event.target;
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Generando PDF...';
    btn.disabled = true;

    let cotizacionData;
    try {
        cotizacionData = getCotizacionPdfData();
    } catch (e) {
        showAlert('Error al leer datos de la cotización', 'danger');
        btn.innerHTML = originalText;
        btn.disabled = false;
        return;
    }

    fetch('../ajax/generate_pdf.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            action: 'generate_cotizacion_pdf',
            data: cotizacionData
        })
    })
        .then(function (response) {
            if (response.ok) {
                return response.blob();
            }
            return response.text().then(function (text) {
                throw new Error('Error del servidor: ' + response.status + ' - ' + text);
            });
        })
        .then(function (blob) {
            if (blob.type === 'application/pdf' || blob.size > 0) {
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = 'Cotizacion_' + cotizacionData.numero + '.pdf';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                window.URL.revokeObjectURL(url);
            } else {
                const reader = new FileReader();
                reader.onload = function (e) {
                    const newWindow = window.open('', '_blank');
                    newWindow.document.write(e.target.result);
                    newWindow.document.close();
                };
                reader.readAsText(blob);
            }
            btn.innerHTML = originalText;
            btn.disabled = false;
        })
        .catch(function (error) {
            console.error('Error generando PDF:', error);
            showAlert('Error al generar el PDF: ' + error.message, 'danger');
            btn.innerHTML = originalText;
            btn.disabled = false;
        });
}

function deleteCotizacion() {
    const config = getCotizacionConfig();
    if (!confirm('¿Estás seguro de eliminar esta cotización? Esta acción no se puede deshacer.')) {
        return;
    }

    fetch('../ajax/cotizaciones.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=delete&id=' + config.id
    })
        .then(function (response) { return response.json(); })
        .then(function (data) {
            if (data.success) {
                showAlert(data.message, 'success');
                setTimeout(function () {
                    window.location.href = 'cotizaciones.php';
                }, 1500);
            } else {
                showAlert(data.message, 'danger');
            }
        })
        .catch(function (error) {
            console.error('Error:', error);
            showAlert('Error al eliminar la cotización', 'danger');
        });
}

function cargarSaldoPendiente() {
    const config = getCotizacionConfig();
    const formData = new FormData();
    formData.append('action', 'obtener_pagos');
    formData.append('cotizacion_id', config.id);

    fetch('../ajax/pagos.php', { method: 'POST', body: formData })
        .then(function (response) { return response.json(); })
        .then(function (data) {
            if (data.success) {
                let totalPagado = 0;
                if (data.pagos && data.pagos.length > 0) {
                    totalPagado = data.pagos.reduce(function (sum, pago) {
                        return sum + parseFloat(pago.monto);
                    }, 0);
                }
                const saldoPendiente = config.total - totalPagado;
                const montoInput = document.getElementById('monto_pago');
                const infoSaldo = document.getElementById('info_saldo_pendiente');

                infoSaldo.innerHTML =
                    '<strong>Total de la cotización:</strong> $' + config.total.toFixed(2) + '<br>' +
                    '<strong>Total pagado:</strong> $' + totalPagado.toFixed(2) + '<br>' +
                    '<strong class="text-warning">Saldo pendiente:</strong> $' + saldoPendiente.toFixed(2);

                montoInput.max = saldoPendiente;
                montoInput.value = saldoPendiente;

                montoInput.addEventListener('input', function () {
                    const montoIngresado = parseFloat(this.value);
                    if (montoIngresado > saldoPendiente) {
                        this.setCustomValidity('El monto no puede exceder el saldo pendiente de $' + saldoPendiente.toFixed(2));
                        this.classList.add('is-invalid');
                    } else if (montoIngresado <= 0) {
                        this.setCustomValidity('El monto debe ser mayor a 0');
                        this.classList.add('is-invalid');
                    } else {
                        this.setCustomValidity('');
                        this.classList.remove('is-invalid');
                    }
                });
            } else {
                document.getElementById('info_saldo_pendiente').innerHTML =
                    '<span class="text-danger">Error al cargar la información de pagos</span>';
            }
        })
        .catch(function (error) {
            console.error('Error:', error);
            document.getElementById('info_saldo_pendiente').innerHTML =
                '<span class="text-danger">Error al cargar la información de pagos</span>';
        });
}

function registrarPago() {
    const cotizacionId = document.getElementById('cotizacion_id_pago').value;
    const monto = parseFloat(document.getElementById('monto_pago').value);
    const metodoPago = document.getElementById('metodo_pago').value;
    const referencia = document.getElementById('referencia_pago').value;
    const observaciones = document.getElementById('observaciones_pago').value;

    if (monto <= 0) {
        showAlert('El monto debe ser mayor a 0', 'danger');
        return;
    }

    const montoInput = document.getElementById('monto_pago');
    if (montoInput.checkValidity() === false) {
        showAlert(montoInput.validationMessage, 'danger');
        return;
    }

    const btn = event.target;
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Registrando...';
    btn.disabled = true;

    const formData = new FormData();
    formData.append('action', 'registrar_pago');
    formData.append('cotizacion_id', cotizacionId);
    formData.append('monto', monto);
    formData.append('metodo_pago', metodoPago);
    formData.append('referencia', referencia);
    formData.append('observaciones', observaciones);

    fetch('../ajax/pagos.php', { method: 'POST', body: formData })
        .then(function (response) { return response.json(); })
        .then(function (data) {
            if (data.success) {
                showAlert(data.message, 'success');
                const modal = bootstrap.Modal.getInstance(document.getElementById('modalRegistrarPago'));
                modal.hide();
                setTimeout(function () { location.reload(); }, 1500);
            } else {
                showAlert(data.message, 'danger');
            }
        })
        .catch(function (error) {
            console.error('Error:', error);
            showAlert('Error al registrar el pago', 'danger');
        })
        .finally(function () {
            btn.innerHTML = originalText;
            btn.disabled = false;
        });
}

function cargarHistorialPagos() {
    const config = getCotizacionConfig();
    const formData = new FormData();
    formData.append('action', 'obtener_pagos');
    formData.append('cotizacion_id', config.id);

    fetch('../ajax/pagos.php', { method: 'POST', body: formData })
        .then(function (response) { return response.json(); })
        .then(function (data) {
            if (data.success) {
                mostrarHistorialPagos(data.pagos);
            } else {
                document.getElementById('historial-pagos').innerHTML =
                    '<div class="text-center text-muted">No se pudo cargar el historial de pagos</div>';
            }
        })
        .catch(function (error) {
            console.error('Error:', error);
            document.getElementById('historial-pagos').innerHTML =
                '<div class="text-center text-danger">Error al cargar el historial de pagos</div>';
        });
}

function mostrarHistorialPagos(pagos) {
    const container = document.getElementById('historial-pagos');
    const config = getCotizacionConfig();

    if (pagos.length === 0) {
        container.innerHTML = '<div class="text-center text-muted">No hay pagos registrados</div>';
        return;
    }

    let html = '<div class="table-responsive"><table class="table table-striped">';
    html += '<thead><tr><th>Fecha</th><th>Monto</th><th>Método</th><th>Referencia</th><th>Registrado por</th><th>Observaciones</th></tr></thead><tbody>';

    let totalPagado = 0;
    pagos.forEach(function (pago) {
        totalPagado += parseFloat(pago.monto);
        html += '<tr>';
        html += '<td>' + new Date(pago.fecha_pago).toLocaleDateString('es-ES') + '</td>';
        html += '<td class="text-end"><strong>$' + parseFloat(pago.monto).toFixed(2) + '</strong></td>';
        html += '<td><span class="badge bg-info">' + pago.metodo_pago + '</span></td>';
        html += '<td>' + (pago.referencia || '-') + '</td>';
        html += '<td>' + (pago.usuario_nombre || 'Sistema') + '</td>';
        html += '<td>' + (pago.observaciones || '-') + '</td>';
        html += '</tr>';
    });

    html += '</tbody></table></div>';
    const pendiente = config.total - totalPagado;

    html += '<div class="row mt-3">';
    html += '<div class="col-md-4"><div class="card bg-light"><div class="card-body text-center">';
    html += '<h6 class="card-title">Total Cotización</h6><h4 class="text-primary">$' + config.total.toFixed(2) + '</h4>';
    html += '</div></div></div>';
    html += '<div class="col-md-4"><div class="card bg-success text-white"><div class="card-body text-center">';
    html += '<h6 class="card-title">Total Pagado</h6><h4>$' + totalPagado.toFixed(2) + '</h4>';
    html += '</div></div></div>';
    html += '<div class="col-md-4"><div class="card bg-warning text-dark"><div class="card-body text-center">';
    html += '<h6 class="card-title">Pendiente</h6><h4>$' + pendiente.toFixed(2) + '</h4>';
    html += '</div></div></div></div>';

    container.innerHTML = html;
}

function cargarModuloPagos() {
    const config = getCotizacionConfig();
    const formData = new FormData();
    formData.append('action', 'obtener_pagos');
    formData.append('cotizacion_id', config.id);

    fetch('../ajax/pagos.php', { method: 'POST', body: formData })
        .then(function (response) { return response.json(); })
        .then(function (data) {
            if (data.success) {
                mostrarModuloPagos(data.pagos);
            } else {
                document.getElementById('modulo-pagos').innerHTML =
                    '<div class="text-center text-muted">No se pudo cargar la información de pagos: ' + data.message + '</div>';
            }
        })
        .catch(function (error) {
            console.error('Error:', error);
            document.getElementById('modulo-pagos').innerHTML =
                '<div class="text-center text-danger">Error al cargar la información de pagos</div>';
        });
}

function mostrarModuloPagos(pagos) {
    const container = document.getElementById('modulo-pagos');
    const config = getCotizacionConfig();
    const totalCotizacion = config.total;

    let totalPagado = 0;
    if (pagos && pagos.length > 0) {
        totalPagado = pagos.reduce(function (sum, pago) {
            return sum + parseFloat(pago.monto);
        }, 0);
    }

    const pendiente = totalCotizacion - totalPagado;
    const porcentajePagado = totalCotizacion > 0 ? (totalPagado / totalCotizacion) * 100 : 0;

    let html = '<div class="row mb-4">';
    html += '<div class="col-md-4"><div class="card bg-light h-100"><div class="card-body text-center">';
    html += '<i class="fas fa-file-invoice fa-2x text-primary mb-2"></i><h6 class="card-title">Total Cotización</h6>';
    html += '<h4 class="text-primary">$' + totalCotizacion.toFixed(2) + '</h4></div></div></div>';
    html += '<div class="col-md-4"><div class="card bg-success text-white h-100"><div class="card-body text-center">';
    html += '<i class="fas fa-check-circle fa-2x mb-2"></i><h6 class="card-title">Total Pagado</h6>';
    html += '<h4>$' + totalPagado.toFixed(2) + '</h4><small>' + porcentajePagado.toFixed(1) + '% completado</small>';
    html += '</div></div></div>';

    const pendienteClass = pendiente > 0 ? 'bg-warning text-dark' : 'bg-success text-white';
    const pendienteIcon = pendiente > 0 ? 'fas fa-clock' : 'fas fa-check-double';
    html += '<div class="col-md-4"><div class="card ' + pendienteClass + ' h-100"><div class="card-body text-center">';
    html += '<i class="' + pendienteIcon + ' fa-2x mb-2"></i><h6 class="card-title">Saldo Pendiente</h6>';
    html += '<h4>$' + pendiente.toFixed(2) + '</h4>';
    html += pendiente <= 0 ? '<small>¡Completamente pagado!</small>' : '<small>' + (100 - porcentajePagado).toFixed(1) + '% pendiente</small>';
    html += '</div></div></div></div>';

    html += '<div class="mb-4"><div class="d-flex justify-content-between mb-1">';
    html += '<span>Progreso de Pago</span><span>' + porcentajePagado.toFixed(1) + '%</span></div>';
    html += '<div class="progress" style="height: 20px;">';
    html += '<div class="progress-bar bg-success" role="progressbar" style="width: ' + porcentajePagado + '%">';
    html += porcentajePagado.toFixed(1) + '%</div></div></div>';

    html += '<h6 class="mb-3"><i class="fas fa-list me-2"></i>Pagos Registrados</h6>';
    html += '<div class="table-responsive"><table class="table table-sm table-striped">';
    html += '<thead style="background-color: #f8f9fa;"><tr>';
    html += '<th style="color: #000000; font-weight: 600;">Fecha</th>';
    html += '<th style="color: #000000; font-weight: 600;">Monto</th>';
    html += '<th style="color: #000000; font-weight: 600;">Método</th>';
    html += '<th style="color: #000000; font-weight: 600;">Referencia</th>';
    html += '<th style="color: #000000; font-weight: 600;">Usuario</th></tr></thead><tbody>';

    if (pagos && pagos.length > 0) {
        pagos.forEach(function (pago) {
            html += '<tr>';
            html += '<td>' + new Date(pago.fecha_pago).toLocaleDateString('es-ES') + '</td>';
            html += '<td class="text-end"><strong>$' + parseFloat(pago.monto).toFixed(2) + '</strong></td>';
            html += '<td><span class="badge bg-info">' + pago.metodo_pago + '</span></td>';
            html += '<td>' + (pago.referencia || '-') + '</td>';
            html += '<td>' + (pago.usuario_nombre || 'Sistema') + '</td>';
            html += '</tr>';
        });
    } else {
        html += '<tr><td colspan="5" class="text-center text-muted py-3">';
        html += '<i class="fas fa-credit-card fa-2x mb-2 d-block"></i>No hay pagos registrados</td></tr>';
    }

    html += '</tbody></table></div><div class="text-center mt-3">';

    if (pendiente <= 0) {
        html += '<button type="button" class="btn btn-primary" onclick="cambiarEstado(\'entregada\')">';
        html += '<i class="fas fa-truck me-2"></i>Marcar como Entregada</button>';
    } else if (totalPagado > 0) {
        html += '<button type="button" class="btn btn-success me-2" data-bs-toggle="modal" data-bs-target="#modalRegistrarPago">';
        html += '<i class="fas fa-plus me-2"></i>Registrar Pago</button>';
        html += '<button type="button" class="btn btn-primary" onclick="cambiarEstado(\'entregada\')">';
        html += '<i class="fas fa-truck me-2"></i>Marcar como Entregada</button>';
    } else {
        html += '<button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalRegistrarPago">';
        html += '<i class="fas fa-plus me-2"></i>Registrar Pago</button>';
    }

    html += '</div>';
    container.innerHTML = html;
    actualizarBotonesAcciones(pendiente, totalPagado);

    if (pendiente <= 0 && config.estado === 'en_espera_deposito') {
        cambiarEstadoAutomatico('pagada');
    }
}

function cambiarEstadoAutomatico(estado) {
    const config = getCotizacionConfig();

    fetch('../ajax/cotizaciones.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=change_status&id=' + config.id + '&estado=' + encodeURIComponent(estado)
    })
        .then(function (response) {
            if (!response.ok) {
                throw new Error('HTTP error! status: ' + response.status);
            }
            return response.text();
        })
        .then(function (text) {
            const data = JSON.parse(text);
            if (data.success) {
                setTimeout(function () { location.reload(); }, 1000);
            }
        })
        .catch(function (error) {
            console.error('Error en cambio automático:', error);
        });
}

function actualizarBotonesAcciones(pendiente, totalPagado) {
    const accionesContainer = document.querySelector('.card-body .d-grid');
    if (!accionesContainer) return;

    const botonRegistrarPago = accionesContainer.querySelector('[data-bs-target="#modalRegistrarPago"]');
    const botonEntregada = accionesContainer.querySelector('[onclick="cambiarEstado(\'entregada\')"]');

    if (pendiente <= 0) {
        if (botonRegistrarPago) botonRegistrarPago.style.display = 'none';
        if (botonEntregada) botonEntregada.style.display = 'block';
    } else if (totalPagado > 0) {
        if (botonRegistrarPago) botonRegistrarPago.style.display = 'block';
        if (botonEntregada) botonEntregada.style.display = 'block';
    } else {
        if (botonRegistrarPago) botonRegistrarPago.style.display = 'block';
        if (botonEntregada) botonEntregada.style.display = 'none';
    }
}

function initCotizacionViewPage() {
    cargarModuloPagos();

    const config = getCotizacionConfig();
    if (config.cargarHistorial) {
        cargarHistorialPagos();
    }

    const modalRegistrarPago = document.getElementById('modalRegistrarPago');
    if (modalRegistrarPago) {
        modalRegistrarPago.addEventListener('show.bs.modal', function () {
            cargarSaldoPendiente();
        });
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initCotizacionViewPage);
} else {
    initCotizacionViewPage();
}
