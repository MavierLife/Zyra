<?php
/**
 * Controlador para gestión de tokens de acceso y envío de códigos de verificación
 * Maneja la autenticación y verificación de vendedores
 */

require_once __DIR__ . '/../Config/Conexion.php';

class TokenController {
    private $conexion;
    private $pdo;
    
    // Configuración de WhatsApp API - Campos para completar
    private $whatsapp_token = 'EAATZAZCEYZAQE4BPZAW200pjqYgzqPUMTpTUmVZC6GVc4tMUZA03AY4xA2uTg74iNwmyRKgZAz2XXoJUBZACXlBlPKGzI1ZCivH1FJl03NOlHUcpBnhQsZBiBNiyFGrIiNZCdZBhBef0t12wepY8blkWbJ8z89OsnEAf2KK8Xel89WQtdJzC3APghnCxLUf4GBV9MmZCZBygZDZD';
    private $whatsapp_phone_id = '779107631951763';
    private $whatsapp_api_url = 'https://graph.facebook.com/v22.0/';
    
    public function __construct() {
        try {
            $this->conexion = new Conexion();
            $this->pdo = $this->conexion->getPdo();
        } catch (Exception $e) {
            error_log("Error en TokenController: " . $e->getMessage());
            throw new Exception("Error de conexión a la base de datos");
        }
    }
    
    /**
     * Generar token de acceso seguro de 6 dígitos
     * @return string
     */
    private function generarToken() {
        return str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
    }
    
    /**
     * Consultar datos del vendedor por número de teléfono
     * @param string $telefono
     * @return array|null
     */
    public function obtenerDatosVendedor($telefono) {
        try {
            // Limpiar y formatear número de teléfono para El Salvador
            $telefonoLimpio = $this->formatearTelefonoSalvador($telefono);
            error_log("[DEBUG] obtenerDatosVendedor - telefono original: $telefono, limpio: $telefonoLimpio");
            
            $sql = "
                SELECT 
                    v.UUIDVendedor,
                    v.NombreUsuario,
                    v.Rol,
                    v.Telefono,
                    v.UUIDContribuyente,
                    v.CodPuntoVenta,
                    c.RazonSocial,
                    c.NombreComercial
                FROM tblcontribuyentesvendedores v
                LEFT JOIN tblcontribuyentes c ON v.UUIDContribuyente = c.UUIDContribuyente
                WHERE v.Telefono = :telefono
            ";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':telefono', $telefonoLimpio, PDO::PARAM_STR);
            $stmt->execute();
            
            $vendedor = $stmt->fetch();
            error_log("[DEBUG] Consulta SQL ejecutada, resultado: " . ($vendedor ? 'ENCONTRADO' : 'NO ENCONTRADO'));
            
            if ($vendedor) {
                error_log("[DEBUG] Datos del vendedor encontrado: " . json_encode($vendedor));
                return [
                    'vendedor_id' => $vendedor['UUIDVendedor'],
                    'nombre_usuario' => $vendedor['NombreUsuario'],
                    'rol' => $vendedor['Rol'],
                    'telefono' => $vendedor['Telefono'],
                    'uuid_contribuyente' => $vendedor['UUIDContribuyente'],
                    'cod_punto_venta' => $vendedor['CodPuntoVenta'],
                    'razon_social' => $vendedor['RazonSocial'],
                    'nombre_comercial' => $vendedor['NombreComercial']
                ];
            }
            
            return null;
            
        } catch (PDOException $e) {
            error_log("[DEBUG] Error PDO en obtenerDatosVendedor: " . $e->getMessage());
            throw new Exception("Error al consultar datos del vendedor");
        }
    }
    
    /**
     * Formatear número de teléfono para El Salvador
     * @param string $telefono
     * @return string
     */
    private function formatearTelefonoSalvador($telefono) {
        // Remover todos los caracteres no numéricos
        $telefonoLimpio = preg_replace('/[^0-9]/', '', $telefono);
        
        // Si tiene código de país (503), removerlo
        if (strlen($telefonoLimpio) === 11 && substr($telefonoLimpio, 0, 3) === '503') {
            $telefonoLimpio = substr($telefonoLimpio, 3);
        }
        
        // Devolver solo los 8 dígitos
        return $telefonoLimpio;
    }
    
    /**
     * Validar número de teléfono salvadoreño
     * @param string $telefono
     * @return bool
     */
    private function validarTelefonoSalvador($telefono) {
        $telefonoLimpio = preg_replace('/[^0-9]/', '', $telefono);
        
        // Remover código de país si existe
        if (strlen($telefonoLimpio) === 11 && substr($telefonoLimpio, 0, 3) === '503') {
            $telefonoLimpio = substr($telefonoLimpio, 3);
        }
        
        // Debe tener exactamente 8 dígitos
        if (strlen($telefonoLimpio) !== 8) {
            return false;
        }
        
        // Debe comenzar con 2, 6 o 7
        $primerDigito = substr($telefonoLimpio, 0, 1);
        return in_array($primerDigito, ['2', '6', '7']);
    }
    
    /**
     * Guardar token de verificación en base de datos
     * @param int $id_vendedor
     * @param string $token
     * @return bool
     */
    private function guardarToken($id_vendedor, $token) {
        try {
            error_log("[DEBUG] guardarToken iniciado - id_vendedor: $id_vendedor, token: $token");
            
            // Limpiar tokens expirados del vendedor
            $sqlLimpiar = "DELETE FROM tbltokens_verificacion WHERE UUIDVendedor = :id_vendedor";
            $stmtLimpiar = $this->pdo->prepare($sqlLimpiar);
            $stmtLimpiar->bindParam(':id_vendedor', $id_vendedor, PDO::PARAM_STR);
            $stmtLimpiar->execute();
            
            // Insertar nuevo token
            $sql = "INSERT INTO tbltokens_verificacion (UUIDVendedor, Token, FechaCreacion, FechaExpiracion, Usado) 
                    VALUES (:id_vendedor, :token, NOW(), DATE_ADD(NOW(), INTERVAL 10 MINUTE), 0)";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':id_vendedor', $id_vendedor, PDO::PARAM_STR);
            $stmt->bindParam(':token', $token, PDO::PARAM_STR);
            
            $resultado = $stmt->execute();
            
            if ($resultado) {
                error_log("[DEBUG] Token guardado en base de datos correctamente");
                return true;
            } else {
                error_log("[DEBUG] Error al ejecutar INSERT del token");
                return false;
            }
            
        } catch (Exception $e) {
            error_log("[DEBUG] Error en guardarToken: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Enviar código de verificación por WhatsApp
     * @param string $telefono
     * @param string $token
     * @param string $nombreUsuario
     * @return array
     */
    private function enviarCodigoWhatsApp($telefono, $token, $nombreUsuario) {
        try {
            // Limpiar número de teléfono (remover espacios, guiones, etc.)
            $telefonoLimpio = preg_replace('/[^0-9]/', '', $telefono);
            
            // Asegurar que tenga el código de país (+503 para El Salvador)
            if (!str_starts_with($telefonoLimpio, '503')) {
                $telefonoLimpio = '503' . $telefonoLimpio;
            }
            
            // Agregar el signo + requerido por WhatsApp API
            if (!str_starts_with($telefonoLimpio, '+')) {
                $telefonoLimpio = '+' . $telefonoLimpio;
            }
            
            error_log("[DEBUG WHATSAPP] === INICIO ENVÍO WHATSAPP ===");
            error_log("[DEBUG WHATSAPP] Teléfono original: " . $telefono);
            error_log("[DEBUG WHATSAPP] Teléfono formateado: " . $telefonoLimpio);
            error_log("[DEBUG WHATSAPP] Token a enviar: " . $token);
            error_log("[DEBUG WHATSAPP] Nombre usuario: " . $nombreUsuario);
            
            $url = $this->whatsapp_api_url . $this->whatsapp_phone_id . '/messages';
            error_log("[DEBUG WHATSAPP] URL API: " . $url);
            error_log("[DEBUG WHATSAPP] Token API (primeros 20 chars): " . substr($this->whatsapp_token, 0, 20) . "...");
            
            $data = [
                'messaging_product' => 'whatsapp',
                'to' => $telefonoLimpio,
                'type' => 'template',
                'template' => [
                    'name' => 'codigoautorizacionzyra',
                    'language' => [
                        'code' => 'es'
                    ],
                    'components' => [
                        [
                            'type' => 'body',
                            'parameters' => [
                                [
                                    'type' => 'text',
                                    'text' => $token
                                ]
                            ]
                        ],
                        [
                            'type' => 'button',
                            'sub_type' => 'url',
                            'index' => '0',
                            'parameters' => [
                                [
                                    'type' => 'text',
                                    'text' => 'verificar'
                                ]
                            ]
                        ]
                    ]
                ]
            ];
            
            error_log("[DEBUG WHATSAPP] Datos a enviar: " . json_encode($data, JSON_PRETTY_PRINT));
            
            $headers = [
                'Authorization: Bearer ' . $this->whatsapp_token,
                'Content-Type: application/json'
            ];
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_VERBOSE, true);
            curl_setopt($ch, CURLOPT_STDERR, fopen('php://temp', 'w+'));
            
            error_log("[DEBUG WHATSAPP] Ejecutando petición cURL...");
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            $curlInfo = curl_getinfo($ch);
            
            error_log("[DEBUG WHATSAPP] Código HTTP: " . $httpCode);
            error_log("[DEBUG WHATSAPP] Respuesta cruda: " . $response);
            
            if ($curlError) {
                error_log("[DEBUG WHATSAPP] Error cURL: " . $curlError);
            }
            
            error_log("[DEBUG WHATSAPP] Info cURL: " . json_encode($curlInfo, JSON_PRETTY_PRINT));
            
            curl_close($ch);
            
            $responseData = json_decode($response, true);
            error_log("[DEBUG WHATSAPP] Respuesta decodificada: " . json_encode($responseData, JSON_PRETTY_PRINT));
            
            if ($httpCode === 200 && isset($responseData['messages'])) {
                error_log("[DEBUG WHATSAPP] ✅ ÉXITO - Message ID: " . ($responseData['messages'][0]['id'] ?? 'N/A'));
                return [
                    'success' => true,
                    'message' => 'Código enviado exitosamente',
                    'message_id' => $responseData['messages'][0]['id'] ?? null
                ];
            } else {
                error_log("[DEBUG WHATSAPP] ❌ ERROR - HTTP: $httpCode, Respuesta: " . $response);
                return [
                    'success' => false,
                    'message' => 'Error al enviar código por WhatsApp',
                    'error' => $responseData['error']['message'] ?? 'Error desconocido',
                    'http_code' => $httpCode
                ];
            }
            
        } catch (Exception $e) {
            error_log("[DEBUG WHATSAPP] ❌ EXCEPCIÓN: " . $e->getMessage());
            error_log("[DEBUG WHATSAPP] Stack trace: " . $e->getTraceAsString());
            return [
                'success' => false,
                'message' => 'Error interno al enviar código',
                'error' => $e->getMessage()
            ];
        } finally {
            error_log("[DEBUG WHATSAPP] === FIN ENVÍO WHATSAPP ===");
        }
    }
    
    /**
     * Solicitar token de verificación
     * @param string $telefono
     * @return array
     */
    public function solicitarToken($telefono) {
        try {
            // Validar formato del número de teléfono
            error_log("[DEBUG] Validando teléfono salvadoreño: " . $telefono);
            if (!$this->validarTelefonoSalvador($telefono)) {
                error_log("[DEBUG] Teléfono no válido para El Salvador: " . $telefono);
                return [
                    'success' => false,
                    'message' => 'Formato de número de teléfono inválido. Debe ser un número salvadoreño de 8 dígitos que comience con 2, 6 o 7'
                ];
            }
            
            // Obtener datos del vendedor
            error_log("[DEBUG] Obteniendo datos del vendedor para: " . $telefono);
            $vendedor = $this->obtenerDatosVendedor($telefono);
            
            if (!$vendedor) {
                error_log("[DEBUG] Vendedor no encontrado para teléfono: " . $telefono);
                return [
                    'success' => false,
                    'message' => 'Número de teléfono no registrado en el sistema'
                ];
            }
            
            error_log("[DEBUG] Vendedor encontrado: " . json_encode($vendedor));
            
            // Generar token
            $token = $this->generarToken();
            error_log("[DEBUG] Token generado: " . $token);
            
            // Guardar token en base de datos
            error_log("[DEBUG] Guardando token para vendedor_id: " . $vendedor['vendedor_id']);
            $tokenGuardado = $this->guardarToken($vendedor['vendedor_id'], $token);
            
            if (!$tokenGuardado) {
                error_log("[DEBUG] Error al guardar token en base de datos");
                return [
                    'success' => false,
                    'message' => 'Error al generar el código de verificación'
                ];
            }
            
            error_log("[DEBUG] Token guardado exitosamente");
            
            // Formatear teléfono para WhatsApp (sin +)
            $telefonoParaWhatsApp = $this->formatearTelefonoParaWhatsApp($telefono);
            error_log("[DEBUG] Teléfono formateado para WhatsApp: " . $telefonoParaWhatsApp);
            
            // Enviar código por WhatsApp
            error_log("[DEBUG] Enviando código por WhatsApp a: " . $vendedor['telefono']);
            $resultadoWhatsApp = $this->enviarCodigoWhatsApp(
                $vendedor['telefono'], 
                $token, 
                $vendedor['nombre_usuario']
            );
            
            error_log("[DEBUG] Resultado WhatsApp: " . json_encode($resultadoWhatsApp));
            
            if ($resultadoWhatsApp['success']) {
                error_log("[DEBUG] Proceso completado exitosamente");
                return [
                    'success' => true,
                    'message' => 'Código de verificación enviado exitosamente',
                    'vendedor' => [
                        'uuid' => $vendedor['vendedor_id'],
                        'nombre' => $vendedor['nombre_usuario'],
                        'rol' => $vendedor['rol']
                    ]
                ];
            } else {
                error_log("[DEBUG] Error al enviar WhatsApp: " . ($resultadoWhatsApp['message'] ?? 'Sin mensaje'));
                return [
                    'success' => false,
                    'message' => 'Error al enviar el código de verificación'
                ];
            }
            
        } catch (Exception $e) {
            error_log("[DEBUG] Exception en procesarSolicitudToken: " . $e->getMessage());
            error_log("[DEBUG] Stack trace: " . $e->getTraceAsString());
            return [
                'success' => false,
                'message' => 'Error interno del servidor: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Formatear teléfono para WhatsApp API (sin +)
     * @param string $telefono
     * @return string
     */
    private function formatearTelefonoParaWhatsApp($telefono) {
        $telefonoLimpio = preg_replace('/[^0-9]/', '', $telefono);
        
        // Si ya tiene código de país
        if (strlen($telefonoLimpio) === 11 && substr($telefonoLimpio, 0, 3) === '503') {
            return $telefonoLimpio;
        }
        
        // Si es número de 8 dígitos, agregar código de país
        if (strlen($telefonoLimpio) === 8) {
            return '503' . $telefonoLimpio;
        }
        
        return '503' . $telefonoLimpio;
    }
    
    /**
     * Procesar solicitud de token de acceso
     * @param string $telefono
     * @return array
     */
    public function procesarSolicitudToken($telefono) {
        try {
            error_log("[DEBUG] procesarSolicitudToken iniciado con telefono: " . $telefono);
            
            // Validar formato de teléfono
            if (empty($telefono) || !preg_match('/^[0-9+\-\s()]+$/', $telefono)) {
                error_log("[DEBUG] Teléfono inválido: " . $telefono);
                return [
                    'success' => false,
                    'message' => 'Número de teléfono inválido'
                ];
            }
            
            // Obtener datos del vendedor
            $vendedor = $this->obtenerDatosVendedor($telefono);
            
            if (!$vendedor) {
                return [
                    'success' => false,
                    'message' => 'Número de teléfono no registrado o vendedor inactivo'
                ];
            }
            
            // Generar token
            $token = $this->generarToken();
            
            // Guardar token en base de datos
            if (!$this->guardarToken($vendedor['vendedor_id'], $token)) {
                return [
                    'success' => false,
                    'message' => 'Error al generar token de verificación'
                ];
            }
            
            // Enviar código por WhatsApp
            $resultadoWhatsApp = $this->enviarCodigoWhatsApp(
                $vendedor['telefono'], 
                $token, 
                $vendedor['nombre_usuario']
            );
            
            if ($resultadoWhatsApp['success']) {
                return [
                    'success' => true,
                    'message' => 'Código de verificación enviado exitosamente',
                    'data' => [
                        'vendedor_id' => $vendedor['vendedor_id'],
                        'nombre_usuario' => $vendedor['nombre_usuario'],
                        'rol' => $vendedor['rol'],
                        'contribuyente' => $vendedor['uuid_contribuyente'],
                        'message_id' => $resultadoWhatsApp['message_id']
                    ]
                ];
            } else {
                return [
                    'success' => false,
                    'message' => $resultadoWhatsApp['message'],
                    'error' => $resultadoWhatsApp['error'] ?? null
                ];
            }
            
        } catch (Exception $e) {
            error_log("Error en procesarSolicitudToken: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error interno del servidor',
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Verificar token de acceso
     * @param string $telefono
     * @param string $token
     * @return array
     */
    public function verificarToken($telefono, $token) {
        try {
            error_log("[DEBUG] verificarToken iniciado - telefono: $telefono, token: $token");
            
            // Obtener datos del vendedor primero
            $vendedor = $this->obtenerDatosVendedor($telefono);
            if (!$vendedor) {
                error_log("[DEBUG] Vendedor no encontrado para verificación");
                return [
                    'success' => false,
                    'message' => 'Número de teléfono no registrado'
                ];
            }
            
            $idVendedor = $vendedor['vendedor_id'];
            error_log("[DEBUG] ID Vendedor para verificación: $idVendedor");
            
            // Buscar token en base de datos
            $sql = "SELECT * FROM tbltokens_verificacion 
                    WHERE UUIDVendedor = :id_vendedor 
                    AND Token = :token 
                    AND Usado = 0 
                    AND FechaExpiracion > NOW()";
            
            error_log("[DEBUG] SQL Query: $sql");
            error_log("[DEBUG] Parámetros - UUIDVendedor: $idVendedor, Token: $token");
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':id_vendedor', $idVendedor, PDO::PARAM_STR);
            $stmt->bindParam(':token', $token, PDO::PARAM_STR);
            $stmt->execute();
            
            $tokenData = $stmt->fetch();
            error_log("[DEBUG] Resultado de la consulta: " . ($tokenData ? json_encode($tokenData) : 'NULL'));
            
            // Verificar también sin restricción de tiempo para debug
            $sqlDebug = "SELECT *, NOW() as tiempo_actual FROM tbltokens_verificacion WHERE UUIDVendedor = :id_vendedor AND Token = :token";
            $stmtDebug = $this->pdo->prepare($sqlDebug);
            $stmtDebug->bindParam(':id_vendedor', $idVendedor, PDO::PARAM_STR);
            $stmtDebug->bindParam(':token', $token, PDO::PARAM_STR);
            $stmtDebug->execute();
            $debugData = $stmtDebug->fetch();
            error_log("[DEBUG] Consulta sin restricciones: " . ($debugData ? json_encode($debugData) : 'NULL'));
            
            if (!$tokenData) {
                error_log("[DEBUG] Token no encontrado, inválido o expirado en base de datos");
                return [
                    'success' => false,
                    'message' => 'Token no encontrado o expirado'
                ];
            }
            
            error_log("[DEBUG] Token encontrado en base de datos: " . json_encode($tokenData));
            
            // Marcar token como usado
            $sqlUpdate = "UPDATE tbltokens_verificacion SET Usado = 1 WHERE IDToken = :id_token";
            $stmtUpdate = $this->pdo->prepare($sqlUpdate);
            $stmtUpdate->bindParam(':id_token', $tokenData['IDToken'], PDO::PARAM_INT);
            $stmtUpdate->execute();
            
            error_log("[DEBUG] Token verificado exitosamente y marcado como usado");
            
            return [
                'success' => true,
                'message' => 'Token verificado exitosamente',
                'data' => [
                    'vendedor_id' => $vendedor['vendedor_id'],
                    'nombre_usuario' => $vendedor['nombre_usuario'],
                    'rol' => $vendedor['rol'],
                    'uuid_contribuyente' => $vendedor['uuid_contribuyente']
                ]
            ];
            
        } catch (Exception $e) {
            error_log("[DEBUG] Error en verificarToken: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al verificar token'
            ];
        }
    }
    
    /**
     * Destructor - Cerrar conexión
     */
    public function __destruct() {
        if ($this->conexion) {
            $this->conexion->cerrarConexion();
        }
    }
}

?>