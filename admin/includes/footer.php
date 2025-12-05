            </main>
        </div>
    </div>

    <!-- jQuery (debe cargarse antes que Bootstrap) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Custom JS -->
    <script src="js/admin.js"></script>
    
    <!-- Alert Messages -->
    <div id="alertContainer"></div>
    
    <script>
        // Inicializar DataTables solo para tablas con clase 'data-table' que no tengan DataTable ya inicializado
        $(document).ready(function() {
            $('.data-table').each(function() {
                if (!$.fn.DataTable.isDataTable(this)) {
                    var tableId = $(this).attr('id');
                    var config = {
                        language: {
                            url: 'https://cdn.datatables.net/plug-ins/1.11.5/i18n/es-ES.json'
                        },
                        responsive: true,
                        pageLength: 25,
                        order: [[0, 'desc']]
                    };
                    
                    // Configuración específica para tabla de productos
                    if (tableId === 'productosTable') {
                        config.columnDefs = [
                            { orderable: false, targets: [0, 8] } // Deshabilitar ordenamiento en imagen y acciones
                        ];
                    }
                    
                    // Configuración específica para tabla de cotizaciones
                    if (tableId === 'cotizacionesTable') {
                        config.columnDefs = [
                            { orderable: false, targets: [8] } // Deshabilitar ordenamiento en acciones
                        ];
                    }
                    
                    $(this).DataTable(config);
                }
            });
        });
    </script>
</body>
</html>
