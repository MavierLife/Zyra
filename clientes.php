<?php
/**
 * Módulo de Gestión de Clientes
 * Sistema Zyra - Gestión empresarial
 */

// Incluir configuración común
require_once 'includes/config.php';

// No se requiere permiso específico para visualizar clientes
// Los permisos se verifican por acción específica (crear, editar, eliminar)

// Definir título de página específico
$pageTitle = 'Clientes';
$currentPage = 'clientes';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clientes - Sistema Zyra</title>
    
    <!-- Fuentes -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:ital,opsz,wght@0,6..12,200..1000;1,6..12,200..1000&display=swap" rel="stylesheet">
    
    <!-- Estilos -->
    <link rel="stylesheet" href="styles/dashboard/dashboard.css">
    <link rel="stylesheet" href="styles/dashboard/clientes.css">
    <link rel="icon" type="image/png" href="assets/logos/logo.png">
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <div class="dashboard-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <!-- Contenido principal -->
        <main class="main-content">
            <?php include 'includes/header.php'; ?>
            <div class="clients-section">
                <!-- Controles -->
                <div class="clients-controls">
                    <div class="search-container">
                        <input type="text" id="clientsSearch" class="search-input nunito-sans-regular" placeholder="Buscar clientes...">
                    </div>
                </div>
                
                <!-- Tabla de clientes -->
                <div class="clients-table-container">
                    <table class="clients-table">
                        <thead>
                            <tr>
                                <th class="nunito-sans-semibold">Nombre</th>
                                <th class="nunito-sans-semibold">Teléfono</th>
                                <th class="nunito-sans-semibold">Email</th>
                                <th class="nunito-sans-semibold">Tipo</th>
                                <th class="nunito-sans-semibold">Estado</th>
                                <th class="nunito-sans-semibold">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="clientsTableBody">
                            <!-- Los datos se cargarán dinámicamente -->
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Modal para crear/editar cliente -->
    <div id="editClientModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="nunito-sans-semibold" id="modalTitle">Crear Cliente</h3>
                <button class="close-modal">&times;</button>
            </div>
            
            <form id="editClientForm" class="modal-form">
                <div class="form-sections">
                    <!-- Datos generales -->
                    <div class="form-section">
                        <h4 class="nunito-sans-medium">Información General</h4>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="editClientType" class="nunito-sans-medium">Tipo de Cliente *</label>
                                <select id="editClientType" name="tipoCliente" class="form-input nunito-sans-regular" required>
                                    <option value="">Seleccionar tipo</option>
                                    <option value="0">Persona Natural</option>
                                    <option value="1">Persona Jurídica</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="editClientContribuyente" class="nunito-sans-medium">¿Es Contribuyente?</label>
                                <select id="editClientContribuyente" name="contribuyente" class="form-input nunito-sans-regular">
                                    <option value="0">No</option>
                                    <option value="1">Sí</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="editClientName" class="nunito-sans-medium">Nombre del Cliente *</label>
                            <input type="text" id="editClientName" name="nombreDeCliente" class="form-input nunito-sans-regular" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="editClientCommercialName" class="nunito-sans-medium">Nombre Comercial</label>
                            <input type="text" id="editClientCommercialName" name="nombreComercial" class="form-input nunito-sans-regular">
                        </div>
                    </div>
                    
                    <!-- Información de contacto -->
                    <div class="form-section">
                        <h4 class="nunito-sans-medium">Información de Contacto</h4>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="editClientPhone" class="nunito-sans-medium">Teléfono</label>
                                <input type="tel" id="editClientPhone" name="telefono" class="form-input nunito-sans-regular" maxlength="20">
                            </div>
                            
                            <div class="form-group">
                                <label for="editClientEmail" class="nunito-sans-medium">Correo Electrónico</label>
                                <input type="email" id="editClientEmail" name="correoElectronico" class="form-input nunito-sans-regular">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="editClientAddress" class="nunito-sans-medium">Dirección</label>
                            <textarea id="editClientAddress" name="direccion" class="form-input nunito-sans-regular" rows="3"></textarea>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="editClientDepartment" class="nunito-sans-medium">Departamento</label>
                                <input type="text" id="editClientDepartment" name="departamento" class="form-input nunito-sans-regular">
                            </div>
                            
                            <div class="form-group">
                                <label for="editClientMunicipality" class="nunito-sans-medium">Municipio</label>
                                <input type="text" id="editClientMunicipality" name="municipio" class="form-input nunito-sans-regular">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Documentos de identificación -->
                    <div class="form-section">
                        <h4 class="nunito-sans-medium">Documentos de Identificación</h4>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="editClientDUI" class="nunito-sans-medium">DUI</label>
                                <input type="text" id="editClientDUI" name="dui" class="form-input nunito-sans-regular" maxlength="50">
                            </div>
                            
                            <div class="form-group">
                                <label for="editClientNIT" class="nunito-sans-medium">NIT</label>
                                <input type="text" id="editClientNIT" name="nit" class="form-input nunito-sans-regular" maxlength="50">
                            </div>
                        </div>
                        
                        <div class="form-row" id="contributorFields" style="display: none;">
                            <div class="form-group">
                                <label for="editClientNRC" class="nunito-sans-medium">NRC</label>
                                <input type="text" id="editClientNRC" name="nrc" class="form-input nunito-sans-regular" maxlength="50">
                            </div>
                            
                            <div class="form-group">
                                <label for="editClientActivity" class="nunito-sans-medium">Código de Actividad</label>
                                <input type="text" id="editClientActivity" name="codActividad" class="form-input nunito-sans-regular" maxlength="20">
                            </div>
                        </div>
                        
                        <div class="form-group" id="commercialTurnField" style="display: none;">
                            <label for="editClientCommercialTurn" class="nunito-sans-medium">Giro Comercial</label>
                            <input type="text" id="editClientCommercialTurn" name="giroComercial" class="form-input nunito-sans-regular" maxlength="250">
                        </div>
                        
                        <div class="form-group">
                            <label for="editClientOtherDoc" class="nunito-sans-medium">Otro Documento</label>
                            <input type="text" id="editClientOtherDoc" name="otroDocumento" class="form-input nunito-sans-regular" maxlength="100">
                        </div>
                    </div>
                    
                    <!-- Configuración fiscal -->
                    <div class="form-section" id="fiscalSection" style="display: none;">
                        <h4 class="nunito-sans-medium">Configuración Fiscal</h4>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="checkbox-label nunito-sans-medium">
                                    <input type="checkbox" id="editClientPerceiveIVA" name="percibirIVA" value="1">
                                    Percibir IVA
                                </label>
                            </div>
                            
                            <div class="form-group">
                                <label class="checkbox-label nunito-sans-medium">
                                    <input type="checkbox" id="editClientRetainIVA" name="retenerIVA" value="1">
                                    Retener IVA
                                </label>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="checkbox-label nunito-sans-medium">
                                <input type="checkbox" id="editClientRetainRenta" name="retenerRenta" value="1">
                                Retener Renta
                            </label>
                        </div>
                    </div>
                    
                    <!-- Observaciones -->
                    <div class="form-section">
                        <h4 class="nunito-sans-medium">Observaciones</h4>
                        
                        <div class="form-group">
                            <label for="editClientObservations" class="nunito-sans-medium">Observaciones</label>
                            <textarea id="editClientObservations" name="observaciones" class="form-input nunito-sans-regular" rows="3"></textarea>
                        </div>
                    </div>
                </div>
                
                <div class="modal-actions">
                    <button type="submit" id="saveClientBtn" class="btn btn-save nunito-sans-medium">Guardar Cliente</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Scripts -->
    <script src="js/dashboard/clientes.js"></script>
    
    <!-- Variables globales para JavaScript -->
    <script>
        window.currencySymbol = '<?php echo $currencySymbol; ?>';
        window.uuidContribuyente = '<?php echo $_SESSION['uuid_contribuyente']; ?>';
        
        // Permisos específicos para clientes
        window.canCreateClients = <?php echo (function_exists('tienePermiso') && tienePermiso('Perm_CrearClientesProveedores')) ? 'true' : 'false'; ?>;
        window.canEditClients = <?php echo (function_exists('tienePermiso') && tienePermiso('Perm_EditarEliminarClientesProveedores')) ? 'true' : 'false'; ?>;
        window.canDeleteClients = <?php echo (function_exists('tienePermiso') && tienePermiso('Perm_EditarEliminarClientesProveedores')) ? 'true' : 'false'; ?>;
    </script>
</body>
</html>