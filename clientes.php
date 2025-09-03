<?php
/**
 * Módulo de Gestión de Clientes
 * Sistema Zyra - Gestión empresarial
 */

// Incluir configuración común
require_once 'includes/config.php';
require_once 'Config/Conexion.php';

// No se requiere permiso específico para visualizar clientes
// Los permisos se verifican por acción específica (crear, editar, eliminar)

// Obtener actividades económicas
$actividades = [];
try {
    $conexion = Conexion::obtenerConexion()->getPdo();
    $stmt = $conexion->prepare("SELECT IDActividad, CodigoActividad, DescripcionActividad FROM tblcatalogodeactividades ORDER BY DescripcionActividad");
    $stmt->execute();
    $actividades = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Error al cargar actividades: " . $e->getMessage());
}

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
    
    <!-- Estilos para autocompletado -->
    <style>
        .autocomplete-container {
            position: relative;
        }
        
        .autocomplete-suggestions {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #ddd;
            border-top: none;
            border-radius: 0 0 4px 4px;
            max-height: 200px;
            overflow-y: auto;
            z-index: 1000;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .suggestion-item {
            padding: 10px 12px;
            cursor: pointer;
            border-bottom: 1px solid #f0f0f0;
            font-size: 14px;
        }
        
        .suggestion-item:hover,
        .suggestion-item.selected {
            background-color: #f8f9fa;
        }
        
        .suggestion-item:last-child {
            border-bottom: none;
        }
        
        .suggestion-code {
            font-weight: 600;
            color: #007bff;
        }
        
        .suggestion-description {
            color: #666;
            margin-left: 8px;
        }
    </style>
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
                <!-- Pestañas de navegación -->
                <div class="tab-navigation">
                    <button type="button" class="tab-button active" data-tab="datos-generales">Datos Generales</button>
                    <button type="button" class="tab-button" data-tab="documentos">Documentos</button>
                    <button type="button" class="tab-button" data-tab="datos-fiscales">Datos Fiscales</button>
                    <button type="button" class="tab-button" data-tab="ubicacion-gps">Ubicación GPS</button>
                </div>
                
                <div class="form-sections">
                    <!-- Pestaña: Datos Generales -->
                    <div class="tab-content active" id="datos-generales">
                        <div class="form-section">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="editClientName" class="nunito-sans-medium">Nombre de Cliente / Razón Social *</label>
                                    <input type="text" id="editClientName" name="nombreDeCliente" class="form-input nunito-sans-regular" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="editClientCommercialName" class="nunito-sans-medium">Nombre Comercial / Negocio</label>
                                    <input type="text" id="editClientCommercialName" name="nombreComercial" class="form-input nunito-sans-regular">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="editClientAddress" class="nunito-sans-medium">Dirección</label>
                                    <textarea id="editClientAddress" name="direccion" class="form-input nunito-sans-regular" rows="2"></textarea>
                                </div>
                                

                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="editClientPhone" class="nunito-sans-medium">Teléfono / WhatsApp</label>
                                    <input type="tel" id="editClientPhone" name="telefono" class="form-input nunito-sans-regular" maxlength="20">
                                </div>
                                
                                <div class="form-group">
                                    <label for="editClientEmail" class="nunito-sans-medium">Correo Electrónico</label>
                                    <input type="email" id="editClientEmail" name="correoElectronico" class="form-input nunito-sans-regular">
                                </div>
                                
                                <div class="form-group">
                                    <label for="editClientType" class="nunito-sans-medium">Tipo de Persona *</label>
                                    <select id="editClientType" name="tipoCliente" class="form-input nunito-sans-regular" required>
                                        <option value="1">Persona Natural</option>
                                        <option value="2">Persona Jurídica</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="editClientObservations" class="nunito-sans-medium">Observaciones</label>
                                <textarea id="editClientObservations" name="observaciones" class="form-input nunito-sans-regular" rows="3"></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Pestaña: Documentos -->
                    <div class="tab-content" id="documentos">
                        <div class="form-section">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="editClientFacturarCon" class="nunito-sans-medium">Facturar con *</label>
                                    <select id="editClientFacturarCon" name="facturarCon" class="form-input nunito-sans-regular" required>
                                        <option value="">Seleccionar documento</option>
                                        <option value="DUI">DUI - Documento Único de Identidad</option>
                                        <option value="Carnet de Residente">Carnet de Residente</option>
                                        <option value="Pasaporte">Pasaporte</option>
                                        <option value="NIT">NIT - Número de Identificación Tributaria</option>
                                        <option value="Otro">Otro</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="editClientDUI" class="nunito-sans-medium">DUI</label>
                                    <input type="text" id="editClientDUI" name="dui" class="form-input nunito-sans-regular" maxlength="50" placeholder="00000000-0">
                                </div>
                                
                                <div class="form-group">
                                    <label for="editClientNIT" class="nunito-sans-medium">NIT</label>
                                    <input type="text" id="editClientNIT" name="nit" class="form-input nunito-sans-regular" maxlength="50" placeholder="0000-000000-000-0">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="editClientOtherDoc" class="nunito-sans-medium">Otro Documento</label>
                                <input type="text" id="editClientOtherDoc" name="otroDocumento" class="form-input nunito-sans-regular" maxlength="100" placeholder="Carnet/Pasaporte/Otro">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Pestaña: Datos Fiscales -->
                    <div class="tab-content" id="datos-fiscales">
                        <div class="form-section">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="editClientContribuyente" class="nunito-sans-medium">Contribuyente</label>
                                    <select id="editClientContribuyente" name="contribuyente" class="form-input nunito-sans-regular">
                                        <option value="0">No</option>
                                        <option value="1">Sí</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="editClientNRC" class="nunito-sans-medium">NRC</label>
                                    <input type="text" id="editClientNRC" name="nrc" class="form-input nunito-sans-regular" maxlength="50">
                                </div>
                                
                                <div class="form-group">
                                    <label for="editClientDocumentEstablished" class="nunito-sans-medium">Documento Establecido</label>
                                    <select id="editClientDocumentEstablished" name="documentoEstablecido" class="form-input nunito-sans-regular">
                                        <option value="Factura Electrónica">Factura Electrónica</option>
                                        <option value="Crédito Fiscal Electrónico">Crédito Fiscal Electrónico</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-group autocomplete-container">
                                <label for="editClientActivity" class="nunito-sans-medium">Actividad Económica</label>
                                <input type="text" id="editClientActivity" name="codActividad" class="form-input nunito-sans-regular" placeholder="Escriba para buscar actividad económica..." autocomplete="off">
                                <input type="hidden" id="editClientActivityCode" name="codActividadValue">
                                <div id="activitySuggestions" class="autocomplete-suggestions" style="display: none;"></div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="editClientConditionEstablished" class="nunito-sans-medium">Condición Establecida</label>
                                    <select id="editClientConditionEstablished" name="condicionEstablecida" class="form-input nunito-sans-regular">
                                        <option value="">Seleccionar condición</option>
                                        <option value="Contado">Contado</option>
                                        <option value="Crédito">Crédito</option>
                                        <option value="Mixto">Mixto</option>
                                    </select>
                                </div>
                                

                                <div class="form-group">
                                    <label for="editClientTermEstablished" class="nunito-sans-medium">Plazo Establecido</label>
                                    <select id="editClientTermEstablished" name="plazoEstablecido" class="form-input nunito-sans-regular">
                                        <option value="">Seleccionar plazo</option>
                                        <option value="7 días">7 días</option>
                                        <option value="15 días">15 días</option>
                                        <option value="30 días">30 días</option>
                                        <option value="45 días">45 días</option>
                                        <option value="60 días">60 días</option>
                                        <option value="90 días">90 días</option>
                                    </select>
                                </div>
                            </div>
                            
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
                                
                                <div class="form-group">
                                    <label class="checkbox-label nunito-sans-medium">
                                        <input type="checkbox" id="editClientRetainRenta" name="retenerRenta" value="1">
                                        Retener Renta
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Pestaña: Ubicación GPS -->
                    <div class="tab-content" id="ubicacion-gps">
                        <div class="form-section">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="editClientDepartment" class="nunito-sans-medium">Departamento</label>
                                    <select id="editClientDepartment" name="departamento" class="form-input nunito-sans-regular">
                                        <option value="">Seleccionar departamento</option>
                                        <?php
                                        try {
                                            require_once 'Config/Conexion.php';
                                            $conexion = new Conexion();
                                            $pdo = $conexion->getPdo();
                                            
                                            $stmt = $pdo->prepare("SELECT UUIDDepartamento, Departamento FROM tbldepartamentos WHERE UUIDDepartamento > 0 ORDER BY Departamento");
                                            $stmt->execute();
                                            $departamentos = $stmt->fetchAll();
                                            
                                            foreach ($departamentos as $depto) {
                                                echo '<option value="' . htmlspecialchars($depto['UUIDDepartamento']) . '">' . htmlspecialchars($depto['Departamento']) . '</option>';
                                            }
                                        } catch (Exception $e) {
                                            error_log("Error al cargar departamentos: " . $e->getMessage());
                                        }
                                        ?>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="editClientMunicipality" class="nunito-sans-medium">Municipio</label>
                                    <select id="editClientMunicipality" name="municipio" class="form-input nunito-sans-regular" disabled>
                                        <option value="">Seleccionar municipio</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="editClientDistrict" class="nunito-sans-medium">Distrito</label>
                                    <select id="editClientDistrict" name="distrito" class="form-input nunito-sans-regular" disabled>
                                        <option value="">Seleccionar distrito</option>
                                    </select>
                                </div>
                            </div>
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
        
        // Actividades económicas para autocompletado
        window.actividades = <?php echo json_encode($actividades); ?>;
        
        // Funcionalidad de autocompletado para actividades económicas
        document.addEventListener('DOMContentLoaded', function() {
            const activityInput = document.getElementById('editClientActivity');
            const activityCodeInput = document.getElementById('editClientActivityCode');
            const suggestionsDiv = document.getElementById('activitySuggestions');
            let selectedIndex = -1;
            
            if (activityInput && suggestionsDiv) {
                activityInput.addEventListener('input', function() {
                    const query = this.value.toLowerCase().trim();
                    selectedIndex = -1;
                    
                    if (query.length < 2) {
                        suggestionsDiv.style.display = 'none';
                        activityCodeInput.value = '';
                        return;
                    }
                    
                    const filteredActivities = window.actividades.filter(activity => {
                        const code = activity.CodigoActividad.toLowerCase();
                        const description = activity.DescripcionActividad.toLowerCase();
                        return code.includes(query) || description.includes(query);
                    }).slice(0, 5); // Máximo 5 sugerencias
                    
                    if (filteredActivities.length > 0) {
                        suggestionsDiv.innerHTML = filteredActivities.map((activity, index) => 
                            `<div class="suggestion-item" data-code="${activity.CodigoActividad}" data-index="${index}">
                                <span class="suggestion-code">${activity.CodigoActividad}</span>
                                <span class="suggestion-description">- ${activity.DescripcionActividad}</span>
                            </div>`
                        ).join('');
                        suggestionsDiv.style.display = 'block';
                    } else {
                        suggestionsDiv.style.display = 'none';
                    }
                });
                
                // Manejar clics en sugerencias
                suggestionsDiv.addEventListener('click', function(e) {
                    const suggestionItem = e.target.closest('.suggestion-item');
                    if (suggestionItem) {
                        const code = suggestionItem.getAttribute('data-code');
                        const activity = window.actividades.find(a => a.CodigoActividad === code);
                        if (activity) {
                            activityInput.value = `${activity.CodigoActividad} - ${activity.DescripcionActividad}`;
                            activityCodeInput.value = activity.CodigoActividad;
                            suggestionsDiv.style.display = 'none';
                        }
                    }
                });
                
                // Manejar navegación con teclado
                activityInput.addEventListener('keydown', function(e) {
                    const suggestions = suggestionsDiv.querySelectorAll('.suggestion-item');
                    
                    if (e.key === 'ArrowDown') {
                        e.preventDefault();
                        selectedIndex = Math.min(selectedIndex + 1, suggestions.length - 1);
                        updateSelection(suggestions);
                    } else if (e.key === 'ArrowUp') {
                        e.preventDefault();
                        selectedIndex = Math.max(selectedIndex - 1, -1);
                        updateSelection(suggestions);
                    } else if (e.key === 'Enter') {
                        e.preventDefault();
                        if (selectedIndex >= 0 && suggestions[selectedIndex]) {
                            suggestions[selectedIndex].click();
                        }
                    } else if (e.key === 'Escape') {
                        suggestionsDiv.style.display = 'none';
                        selectedIndex = -1;
                    }
                });
                
                function updateSelection(suggestions) {
                    suggestions.forEach((item, index) => {
                        item.classList.toggle('selected', index === selectedIndex);
                    });
                }
                
                // Ocultar sugerencias al hacer clic fuera
                document.addEventListener('click', function(e) {
                    if (!activityInput.contains(e.target) && !suggestionsDiv.contains(e.target)) {
                        suggestionsDiv.style.display = 'none';
                        selectedIndex = -1;
                    }
                });
            }
        });
    </script>
</body>
</html>