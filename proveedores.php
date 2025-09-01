<?php
/**
 * Módulo de Gestión de Proveedores
 * Sistema Zyra - Gestión empresarial
 */

// Incluir configuración común
require_once 'includes/config.php';

// Definir título de página específico
$pageTitle = 'Proveedores';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proveedores - Sistema Zyra</title>
    
    <!-- Fuentes -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:ital,opsz,wght@0,6..12,200..1000;1,6..12,200..1000&display=swap" rel="stylesheet">
    
    <!-- Estilos -->
    <link rel="stylesheet" href="styles/dashboard/dashboard.css">
    <link rel="stylesheet" href="styles/dashboard/proveedores.css">
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <div class="dashboard-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <!-- Contenido principal -->
        <main class="main-content">
            <?php include 'includes/header.php'; ?>
        <div class="suppliers-section">
            <!-- Controles -->
            <div class="suppliers-controls">
                <div class="search-container">
                    <input type="text" id="suppliersSearch" class="search-input nunito-sans-regular" placeholder="Buscar proveedores...">
                </div>
            </div>
            
            <!-- Tabla de proveedores -->
            <div class="suppliers-table-container">
                <table class="suppliers-table">
                    <thead>
                        <tr>
                            <th class="nunito-sans-semibold">Nombre</th>
                            <th class="nunito-sans-semibold">Celular</th>
                            <th class="nunito-sans-semibold">Documento</th>
                            <th class="nunito-sans-semibold">Correo</th>
                            <th class="nunito-sans-semibold">Estado</th>
                            <th class="nunito-sans-semibold">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="suppliersTableBody">
                        <!-- Los datos se cargarán dinámicamente -->
                    </tbody>
                </table>
            </div>
        </div>
    </main>
    
    <!-- Modal para crear/editar proveedor -->
    <div id="editSupplierModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="nunito-sans-semibold" id="modalTitle">Crear Proveedor</h3>
                <button class="close-modal">&times;</button>
            </div>
            
            <form id="editSupplierForm" class="modal-form">
                <div class="form-sections">
                    <!-- Datos generales -->
                    <div class="form-section">
                        <h4 class="nunito-sans-medium">Información General</h4>
                        
                        <div class="form-group">
                            <label for="editSupplierName" class="nunito-sans-medium">Razón Social *</label>
                            <input type="text" id="editSupplierName" name="razonSocial" class="form-input nunito-sans-regular" required>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="editSupplierPhone" class="nunito-sans-medium">Celular</label>
                                <input type="tel" id="editSupplierPhone" name="celular" class="form-input nunito-sans-regular">
                            </div>
                            
                            <div class="form-group">
                                <label for="editSupplierDocument" class="nunito-sans-medium">Documento</label>
                                <input type="text" id="editSupplierDocument" name="documento" class="form-input nunito-sans-regular">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="editSupplierEmail" class="nunito-sans-medium">Correo Electrónico *</label>
                            <input type="email" id="editSupplierEmail" name="correoElectronico" class="form-input nunito-sans-regular" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="editSupplierAddress" class="nunito-sans-medium">Dirección *</label>
                            <textarea id="editSupplierAddress" name="direccion" class="form-input nunito-sans-regular" rows="3" required></textarea>
                        </div>
                    </div>
                </div>
                
                <div class="modal-actions">
                    <button type="submit" class="btn btn-save nunito-sans-medium">Guardar Proveedor</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Scripts -->
    <script src="js/dashboard/proveedores.js"></script>
    
    <!-- Variables globales para JavaScript -->
    <script>
        window.currencySymbol = '<?php echo $currencySymbol; ?>';
        window.uuidContribuyente = '<?php echo $_SESSION['uuid_contribuyente']; ?>';
        window.canEdit = <?php echo (isset($_SESSION['permisos']['Perm_EditarEliminarClientesProveedores']) && $_SESSION['permisos']['Perm_EditarEliminarClientesProveedores'] == 1) ? 'true' : 'false'; ?>;
    </script>
        </main>
    </div>
</body>
</html>