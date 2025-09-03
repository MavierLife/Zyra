<?php
    date_default_timezone_set('America/El_Salvador');

    error_reporting(E_ALL);
    ini_set('display_errors', 1);

    class Facturacion extends Conectar{

        public function insertar_venta_auto($usuarioactual,$sucursalid,$sucursalidcaja){
        
            $conectar= parent::conexion();
            parent::set_names();
                try 
                {

                // Consulta para obtener datos de la tabla tblsucursales
                $sql = "SELECT * FROM tblsucursales WHERE UUIDSucursal = ?";
                $stmt = $conectar->prepare($sql);
                $stmt->bindValue(1, $sucursalid);
                $stmt->execute();
                $sucursal = $stmt->fetch(PDO::FETCH_ASSOC);

                $tipomoneda = $sucursal['TipoMoneda'];

                $uuidnegocio = $sucursal["UUIDNegocio"];

                // Consulta para obtener datos de la tabla tblnegocios
                $sql = "SELECT Ambiente FROM tblnegocios WHERE UUIDNegocio = ?";
                $stmt = $conectar->prepare($sql);
                $stmt->bindValue(1, $uuidnegocio);
                $stmt->execute();
                $negocio = $stmt->fetch(PDO::FETCH_ASSOC);

                $AmbienteTransmision = $negocio["Ambiente"];

                // Generar UUID 
                $UUID = $this->generateUUIDv4(); 

                $fechaactual = date("Y-m-d"); // Obtener la fecha actual en el formato de tu base de datos
                $sucursal = $sucursalid; // Código de la sucursal
                $fecha = date('YmdHis'); // Obtiene la fecha en formato YYYYMMDDHHMMSS
                $codigoVenta = $sucursal . $fecha; // Concatenación
                $sql = "INSERT INTO tblnotasdeentrega 
                    (UUIDVenta, CodigoVEN, UsuarioRegistro, UUIDSucursal, 
                    UUIDCaja, TipoMoneda, TipoDespacho, CodigoCLI, NombreDeCliente, Ambiente) 
                    VALUES 
                    (?,?,?,?,?,?,?,?,?,?)";

                    $sql = $conectar->prepare($sql);
                    $sql->bindValue(1, $UUID);
                    $sql->bindValue(2, $codigoVenta);
                    $sql->bindValue(3, $usuarioactual);
                    $sql->bindValue(4, $sucursalid);
                    $sql->bindValue(5, $sucursalidcaja);
                    $sql->bindValue(6, $tipomoneda);
                    $sql->bindValue(7, "1"); 
                    $sql->bindValue(8, "0000"); 
                    $sql->bindValue(9, "VENTA POR DESPACHO");
                    $sql->bindValue(10, $AmbienteTransmision);
                    
                    if($sql->execute())
                    {
                        // Obtener el UUID insertado
                        $lastInsertUUID = $UUID;
                        return $lastInsertUUID;

                    } else {
                        return "Error";
                    }
                } catch (Exception $e) {
                    return "Error";		
                }
                    
        }

        public function agregar_actualizar_detalle_venta($usuarioactual,$txtuuidventa,$txtcodigoprod,$txtcodigobarra,$txttipoventa,$txtcantidad,$txtconcepto){
            $conectar= parent::conexion();
            parent::set_names();

            $sql = "SELECT UUIDDetalleVenta, Cantidad FROM tblnotasdeentregadetalle WHERE UUIDVenta = ? AND TV = ? AND CodigoPROD = ? ";
            $sql = $conectar->prepare($sql);
            $sql->bindValue(1, $txtuuidventa);
            $sql->bindValue(2, $txttipoventa);
            $sql->bindValue(3, $txtcodigoprod);
            $sql->execute();
            $resultado = $sql->fetchAll(PDO::FETCH_ASSOC);
            $Encontrado = 0;
            $UnidadesEnVenta = 0;
            
            // Verificar si se encontró algún resultado
            if (count($resultado) > 0) {
                // Almacenar los valores en variables individuales
                $Encontrado = 1;
                $UUIDDetalleVenta = $resultado[0]['UUIDDetalleVenta'];
                $UnidadesEnVenta = $resultado[0]['Cantidad'];
            } else {
                $Encontrado = 0;
                $UnidadesEnVenta = 0;
            }
            
            $sql = "SELECT * FROM tblcatalogodeproductos WHERE CodigoPROD = ?";
            $sql = $conectar->prepare($sql);
            $sql->bindValue(1, $txtcodigoprod);
            $sql->execute();
            $resultado = $sql->fetchAll(PDO::FETCH_ASSOC);
            $preciopublico = 0.00;
            $unidades = 0;
            // Verificar si se encontró algún resultado
            if (count($resultado) > 0) {
                
                $PagaImpuesto = $resultado[0]['PagaImpuesto'];
                $PorcentajeImpuesto = $resultado[0]['PorcentajeImpuesto'];

                $PrecioPublico = isset($resultado[0]['PrecioPublico']) ? floatval($resultado[0]['PrecioPublico']) : 0.00;
                $PrecioDetalle = isset($resultado[0]['PrecioDetalle']) ? floatval($resultado[0]['PrecioDetalle']) : 0.00;
                $PrecioMayoreo = isset($resultado[0]['PrecioMayoreo']) ? floatval($resultado[0]['PrecioMayoreo']) : 0.00;
                $PrecioCosto = isset($resultado[0]['PrecioCosto']) ? floatval($resultado[0]['PrecioCosto']) : 0.00;

                $UMinimaMayoreo = $resultado[0]['UMinimaMayoreo'];
                $UMaximaMayoreo = $resultado[0]['UMaximaMayoreo'];
                $UMinimaDetalle = $resultado[0]['UMinimaDetalle'];
                $UMaximaDetalle = $resultado[0]['UMaximaDetalle'];
                $UMinimaPublico = $resultado[0]['UMinimaPublico'];
                $UMaximaPublico = $resultado[0]['UMaximaPublico'];

                $Unidades = $resultado[0]['Unidades'];
            } else {
                $PrecioPublico = 0;
                $unidades = 0;
            }

            $TipoVenta = $txttipoventa;
            $UnidadDeMedida = 99;
            $TotalVendidas = 0;

            // Establece las unidades a registrar si encuenta el producto en detalle suma las unidades a la existen y sino unidades vendidas son iguales a cantidad
            if ($Encontrado == 1 ){ $TotalVendidas = ($UnidadesEnVenta + $txtcantidad); } else { $TotalVendidas = $txtcantidad;}
            
            $Cantidad = $TotalVendidas;

            // Consutla oferta del producto
            $EstaOfertado = 0;

            if ($EstaOfertado > 0) {
                // Funcion para aplicar la oferta del producto
                
            } else {

                $PrecioBase = 0.00;
                if ($TipoVenta == 1) {
                    $PrecioCosto = ($Unidades > 0) ? ($PrecioCosto / $Unidades) : 0;
                    if ($TotalVendidas >= $UMinimaPublico && $TotalVendidas <= $UMaximaPublico) {
                        $PrecioBase = $PrecioPublico;
                    } elseif ($TotalVendidas >= $UMinimaDetalle && $TotalVendidas <= $UMaximaDetalle) {
                        $PrecioBase = $PrecioDetalle;
                    } elseif ($TotalVendidas >= $UMinimaMayoreo) {
                        $PrecioBase = $PrecioMayoreo;
                    }
                } else {
                    $PrecioCosto = $PrecioCosto;
                    $PrecioBase = $PrecioMayoreo;
                }
                
                $PrecioVentaSinImpuesto = 0.00;
                $CodigoTributo = null;
                $Tributo = null;
                //$PorcentajeImpuesto = 13.00;
                $UnidadesVendidas = 0;

                if (isset($PrecioBase)) {
                    if ($TipoVenta == 1) {
                        $PrecioVenta = ($PrecioBase / $Unidades);
                        $PrecioNormal =  round(($PrecioPublico / $Unidades), 4);
                        $UnidadesVendidas = $Cantidad;
                    } else {
                        $PrecioVenta = round($PrecioBase, 4);
                        $PrecioNormal = round($PrecioPublico, 4);
                        $UnidadesVendidas = ($Cantidad * $Unidades);
                    }
                    $PrecioVentaSinImpuesto = round(($PrecioVenta / (1 + ($PorcentajeImpuesto / 100))), 4);
                }
                
                $Descuento = 0.00;
                $NoSujetas = 0.00;
                $Excento = 0.00;
                $IVAItem = 0.00;
                $Gravado = 0.00; 
                $GravadoSinImpuesto = 0.00;

                $TotalImporte = ($Cantidad * $PrecioVenta);
                $TotalCosto = ($Cantidad * $PrecioCosto);
                $TotalImporteSinImpuesto = round($TotalImporte / (1 +  ($PorcentajeImpuesto / 100)), 4);
                
                if ($PagaImpuesto == 1) {
                    $Tributo = "20";
                    $Gravado = $TotalImporte;
                    $GravadoSinImpuesto = $TotalImporteSinImpuesto;
                    $Excento = 0.00;
                    $IVAItem = ($TotalImporteSinImpuesto * ($PorcentajeImpuesto /100));
                } else {
                    $Tributo = null;
                    $Gravado = 0.00;
                    $GravadoSinImpuesto = 0.00;
                    $Excento = $TotalImporte;
                    $IVAItem = 0.00;
                }
            }

            if ($Encontrado == 1 ){
                $sql="UPDATE tblnotasdeentregadetalle
                SET
                Cantidad = ?,
                UnidadesVendidas = ?,
                PrecioVenta = ?,
                PrecioVentaSinImpuesto = ?,
                PrecioNormal = ?,
                Descuento = ?,
                VentaNoSujeta = ?,
                VentaExenta = ?,
                VentaGravada = ?,
                VentaGravadaSinImpuesto = ?,
                IVAItem = ?,
                TotalImporte = ?,
                PrecioCosto = ?,
                TotalCosto = ?
                WHERE
                UUIDDetalleVenta = ?";
                $sql=$conectar->prepare($sql);
                $sql->bindValue(1, $Cantidad);
                $sql->bindValue(2, $UnidadesVendidas);
                $sql->bindValue(3, $PrecioVenta);
                $sql->bindValue(4, $PrecioVentaSinImpuesto);
                $sql->bindValue(5, $PrecioNormal);
                $sql->bindValue(6, $Descuento);
                $sql->bindValue(7, $NoSujetas);
                $sql->bindValue(8, $Excento);
                $sql->bindValue(9, $Gravado);
                $sql->bindValue(10, $GravadoSinImpuesto);
                $sql->bindValue(11, $IVAItem);
                $sql->bindValue(12, $TotalImporte);
                $sql->bindValue(13, $PrecioCosto);
                $sql->bindValue(14, $TotalCosto);
                $sql->bindValue(15, $UUIDDetalleVenta);
      
                if($sql->execute())
                {
                    $count = $sql->rowCount();
                    if($count == 0){
                        $data = "Error";
                    } else {
                        $data = "Actualizado";
                    }
                    return $data;
                } else {
                    $data = "Error";
                    return $data;
                }

            } else {
                $sql="INSERT INTO tblnotasdeentregadetalle 
                        (UUIDDetalleVenta,UUIDVenta,UsuarioRegistro,CodigoPROD,CodigoBarra,Concepto,TV,UnidadDeMedida,
                        Cantidad,UnidadesVendidas,PrecioVenta,PrecioVentaSinImpuesto,PrecioNormal,Descuento,VentaNoSujeta,VentaExenta,
                        VentaGravada,VentaGravadaSinImpuesto,IVAItem,TotalImporte,PrecioCosto,TotalCosto,PagaImpuesto,
                        CodigoTributo,Tributo,PorcentajeImpuesto) 
                        VALUES 
                        (UUID(),?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?);";
                $sql=$conectar->prepare($sql);
                $sql->bindValue(1, $txtuuidventa);
                $sql->bindValue(2, $usuarioactual);
                $sql->bindValue(3, $txtcodigoprod);
                $sql->bindValue(4, $txtcodigobarra);
                $sql->bindValue(5, $txtconcepto);
                $sql->bindValue(6, $TipoVenta);
                $sql->bindValue(7, $UnidadDeMedida);
                $sql->bindValue(8, $Cantidad);
                $sql->bindValue(9, $UnidadesVendidas);
                $sql->bindValue(10, $PrecioVenta);
                $sql->bindValue(11, $PrecioVentaSinImpuesto);
                $sql->bindValue(12, $PrecioNormal);
                $sql->bindValue(13, $Descuento);
                $sql->bindValue(14, $NoSujetas);
                $sql->bindValue(15, $Excento);
                $sql->bindValue(16, $Gravado);
                $sql->bindValue(17, $GravadoSinImpuesto);
                $sql->bindValue(18, $IVAItem);
                $sql->bindValue(19, $TotalImporte);
                $sql->bindValue(20, $PrecioCosto);
                $sql->bindValue(21, $TotalCosto);
                $sql->bindValue(22, $PagaImpuesto);
                $sql->bindValue(23, $CodigoTributo);
                $sql->bindValue(24, $Tributo);
                $sql->bindValue(25, $PorcentajeImpuesto);
          
                if($sql->execute())
                {
                    $count = $sql->rowCount();
                    if($count == 0){
                        $data = "Error";
                    } else {
                        $data = "Actualizado";
                    }
                    return $data;
                } else {
                    $data = "Error";
                    return $data;
                }

            }
            
        }

        public function consultar_venta_uuid($uuidventa){
            $conectar= parent::conexion();
            parent::set_names();
            $sql="SELECT * FROM tblnotasdeentrega
                    WHERE
                    UUIDVenta = ?";
            $sql=$conectar->prepare($sql);
            $sql->bindValue(1, $uuidventa);
            $sql->execute();
            return $resultado=$sql->fetchAll();

        }

        public function consultar_flotantes($uuidterminal) {
            $conectar = parent::conexion();
            parent::set_names();

            $sql = "SELECT COUNT(*) AS total FROM tblnotasdeentrega
                    WHERE Estado = 1
                    AND UUIDCaja = ?
                    AND DATE(FechaRegistro) = CURDATE()";

            $stmt = $conectar->prepare($sql);
            $stmt->bindValue(1, $uuidterminal);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            return $row ? intval($row["total"]) : 0;
        }

        public function consultar_membresia_id($codigomembresia){
            $conectar = parent::conexion();
            parent::set_names();

            // LIMPIAR Y NORMALIZAR EL CÓDIGO ESCANEADO
            $codigoLimpio = $this->limpiarTexto($codigomembresia);
            $codigoLimpio = str_replace('ÇÇ', ']]', $codigoLimpio); // Corregir separación
            $clave = mb_substr($codigoLimpio, 0, 40, 'UTF-8'); // Corte seguro de caracteres multibyte

            // BUSCAR
            $sql = "SELECT * FROM tblcatalogodeclientes WHERE CodigoTarjeta64 = ?";
            $sql = $conectar->prepare($sql);
            $sql->bindValue(1, $clave);
            $sql->execute();

            return $sql->fetchAll();
        }

        function limpiarTexto($texto) {
            $texto = trim($texto); // Quitar espacios en los extremos
            $texto = str_replace(["\r", "\n", "\t"], '', $texto); // Quitar saltos y tabs
            $texto = preg_replace('/\s+/', '', $texto); // Eliminar todos los espacios
            $texto = preg_replace('/[\x00-\x1F\x7F]/u', '', $texto); // Quitar caracteres invisibles
            return mb_convert_encoding($texto, 'UTF-8', 'UTF-8'); // Asegurar codificación UTF-8 válida
        }

        public function consultar_si_transmite($terminalid){
            $conectar= parent::conexion();
            parent::set_names();
            $sql="SELECT TransmitirDTE FROM tblterminales
                    WHERE
                    UUIDTerminal = ?";
            $sql=$conectar->prepare($sql);
            $sql->bindValue(1, $terminalid);
            $sql->execute();
            return $resultado=$sql->fetchAll();

        }

        public function vender_por_domicilio($uuidsucursal){
            $conectar= parent::conexion();
            parent::set_names();
            $sql="SELECT VenderPorDomicilio, UUIDSucursal FROM tblsucursales
                    WHERE
                    VenderPorDomicilio = 1 AND 
                    UUIDSucursal = ?";
            $sql=$conectar->prepare($sql);
            $sql->bindValue(1, $uuidsucursal);
            $sql->execute();
            return $resultado=$sql->fetchAll();

        }

        public function transmitir_Contingencia_DTE($URLContingencia,$nit,$documento,$token){
    
            // Construir los datos para enviar
            $data = [
                "nit" => $nit,//test 00, produccion 01
                "documento" => $documento
            ];

            // Convertir los datos a JSON
            $jsonData = json_encode($data);

            $urlContingencia = $URLContingencia;
            
            // Inicializar cURL
            $ch = curl_init($urlContingencia);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            
            $headers = [
                "Authorization:".$token,
                "content-Type: application/json",
                "User-Agent: Mozilla/5.0 (Windows NT 6.2; WOW64; rv:17.0) Gecko/20100101 Firefox/17.0"
            ];
            curl_setopt($ch, CURLOPT_HTTPHEADER,$headers);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);

            // Deshabilitar la verificación SSL (solo para pruebas, no recomendado en producción)
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

            // Ejecutar la solicitud y obtener la respuesta
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            // Manejo de errores
            if ($response === false) {
                die("Error en la solicitud: " . curl_error($ch));
            }

            $responseArray = json_decode($response, true);
            
            curl_close($ch);
        
            return $responseArray;

        }

        public function transmitir_Anulacion_DTE($URLAnulacion,$Ambiente,$Correlativo,$documento,$token,$versionDte){
    
            // Construir los datos para enviar
            $data = [
                "ambiente" => $Ambiente,//test 00, produccion 01
                "idEnvio" => $Correlativo,//correlativo a dicresion
                "version" => $versionDte,//Debe coincidir con la versió del de la sección de Identificación del DTE
                "documento" => $documento
            ];

            // Convertir los datos a JSON
            $jsonData = json_encode($data);
    
            $urlAnulacion = $URLAnulacion;
            
            // Inicializar cURL
            $ch = curl_init($urlAnulacion);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            
            $headers = [
                "Authorization:".$token,
                "content-Type: application/json",
                "User-Agent: Mozilla/5.0 (Windows NT 6.2; WOW64; rv:17.0) Gecko/20100101 Firefox/17.0"
            ];
            curl_setopt($ch, CURLOPT_HTTPHEADER,$headers);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);

            // Deshabilitar la verificación SSL (solo para pruebas, no recomendado en producción)
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

            // Ejecutar la solicitud y obtener la respuesta
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            // Manejo de errores
            if ($response === false) {
                die("Error en la solicitud: " . curl_error($ch));
            }

            $responseArray = json_decode($response, true);
            
            curl_close($ch);
        
            return $responseArray;

        }

        public function transmitir_DTE($URLRecepcion,$Ambiente,$Correlativo,$documento,$codigodegeneracion, $token, $tipoDte, $versionDte){
    
            // Construir los datos para enviar
            $data = [
                "ambiente" => $Ambiente,//test 00, produccion 01
                "idEnvio" => $Correlativo,//correlativo a dicresion
                "version" => $versionDte,//Debe coincidir con la versió del de la sección de Identificación del DTE
                "tipoDte" => $tipoDte,//Debe coincidir con el tipo de DTE de la sección de Identificación del DTE. 
                "documento" => $documento,
                "codigoGeneracion" => $codigodegeneracion
            ];

            // Convertir los datos a JSON
            $jsonData = json_encode($data);

            $urlRecepcion = $URLRecepcion;
            
            // Inicializar cURL
            $ch = curl_init($urlRecepcion);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            
            $headers = [
                "Authorization:".$token,
                "content-Type: application/json",
                "User-Agent: Mozilla/5.0 (Windows NT 6.2; WOW64; rv:17.0) Gecko/20100101 Firefox/17.0"
            ];
            curl_setopt($ch, CURLOPT_HTTPHEADER,$headers);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);

            // Deshabilitar la verificación SSL (solo para pruebas, no recomendado en producción)
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

            // Ejecutar la solicitud y obtener la respuesta
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            // Manejo de errores
            if ($response === false) {
                die("Error en la solicitud: " . curl_error($ch));
            }

            $responseArray = json_decode($response, true);
            
            curl_close($ch);
        
            return $responseArray;

        }

        public function getToken($URLAutenticacion,$user, $pass){

            $conectar = parent::conexion();
            parent::set_names();
    
            // Consulta para obtener datos de la tabla tblsucursales
            $sql = "SELECT * FROM tblconfiguraciones";
            $sql = $conectar->prepare($sql);
            $sql->execute();
            $configuracion = $sql->fetch(PDO::FETCH_ASSOC);

            $TokenTransmision = $configuracion["TokenTransmision"];
            $FechaUpdateToken = $configuracion["FechaUpdateToken"];

            // Obtener la fecha de hoy
            $hoy = date("Y-m-d"); // Solo la parte de la fecha (no la hora)
            $validado = 0;

            // Evaluar si la fecha está vacía primero
            if (!empty($FechaUpdateToken)) {
                // Obtener solo la parte de la fecha del campo
                $fechaToken = date("Y-m-d", strtotime($FechaUpdateToken));
                if ($fechaToken === $hoy && !empty($TokenTransmision)) {
                    $validado = 1;
                } else {
                    $validado = 0;
                }
            } else {
                $validado = 0;
            }

            if($validado == 1){
                // Obteniendo token almacenado
                return $TokenTransmision;

            } else {

                // Obteniendo token nuevo
                // Construir los datos para enviar
                $data = [
                    "user" => $user,
                    "pwd" => $pass
                ];

                // Convertir los datos a JSON
                $jsonData = json_encode($data);

                $urlToken = $URLAutenticacion; 

                // Convertir los datos a formato form-data (application/x-www-form-urlencoded)
                $postData = http_build_query([
                    'user' => $user,
                    'pwd'  => $pass
                ]);

                // Inicializar cURL
                $ch = curl_init($urlToken);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    "Content-Type: application/x-www-form-urlencoded", // Form-data
                    "Accept: application/json",
                    "User-Agent: Mozilla/5.0 (Windows NT 6.2; WOW64; rv:17.0) Gecko/20100101 Firefox/17.0"
                ]);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);

                // Deshabilitar la verificación SSL (solo para pruebas, no recomendado en producción)
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

                // Ejecutar la solicitud y obtener la respuesta
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

                // Manejo de errores
                if ($response === false) {
                    die("Error en la solicitud: " . curl_error($ch));
                }

                // Convertir la respuesta en un array asociativo
                $responseArray = json_decode($response, true);

                // Cerrar cURL
                curl_close($ch);

                // Verificar si se recibió el token
                if (isset($responseArray['body']['token'])) {
                    $token = $responseArray['body']['token'];
                    // Actualizar JSON Sello Recibiod en tblnotasdeentrega
                    $sql = "UPDATE tblconfiguraciones 
                            SET TokenTransmision = ?, 
                            FechaUpdateToken = NOW()";
                    $sql = $conectar->prepare($sql);
                    $sql->bindValue(1, $token);
                    $sql->execute();
                    return $token;
                } else {
                    echo "Error en la autenticación. Código HTTP: " . $httpCode . " - Respuesta: " . $response;
                    return "Error";
                }
            }
        }

        public function generar_DTE_Factura($tipotransmision, $uuidventa, $sucursalid, $terminalid) {
            
                $conectar = parent::conexion();
                parent::set_names();
        
                // Consulta para obtener datos de la tabla tblsucursales
                $sql = "SELECT s.*, n.* 
                        FROM tblsucursales s 
                        INNER JOIN tblnegocios n ON s.UUIDNegocio = n.UUIDNegocio 
                        WHERE s.UUIDSucursal = ?";
                $stmt = $conectar->prepare($sql);
                $stmt->bindValue(1, $sucursalid);
                $stmt->execute();
                $sucursal = $stmt->fetch(PDO::FETCH_ASSOC);

                $uuidnegocio = $sucursal["UUIDNegocio"];

                // Consulta para obtener datos de la tabla tblnegocios
                $sql = "SELECT * FROM tblnegocios WHERE UUIDNegocio = ?";
                $stmt = $conectar->prepare($sql);
                $stmt->bindValue(1, $uuidnegocio);
                $stmt->execute();
                $negocio = $stmt->fetch(PDO::FETCH_ASSOC);

                $NITNegocio = $sucursal["NIT"];
                $ClavePrivada = $negocio["ClavePrivada"];
                $ClavePublica = $negocio["ClavePublica"];
                $ClaveAPI = $negocio["ClaveAPI"];
                $AmbienteTransmision = $negocio["Ambiente"];

                $URLFirmador = $negocio["URLFirmador"];
                $URLAutenticacion = $negocio["URLAutenticacion"];
                $URLRecepcion = $negocio["URLRecepcion"];
                $URLRecepcionLOTE = $negocio["URLRecepcionLOTE"];
                $URLConsultarDTE = $negocio["URLConsultarDTE"];
                $URLContingencia = $negocio["URLContingencia"];
                $URLAnularDTE = $negocio["URLAnularDTE"];
        
                // Consulta para obtener datos de la tabla tblterminales
                $sql = "SELECT * FROM tblterminales WHERE UUIDTerminal = ?";
                $stmt = $conectar->prepare($sql);
                $stmt->bindValue(1, $terminalid);
                $stmt->execute();
                $terminal = $stmt->fetch(PDO::FETCH_ASSOC);
        
                // Consulta para obtener datos de la tabla tblnotasdeentrega
                $sql = "SELECT * FROM tblnotasdeentrega WHERE UUIDVenta = ?";
                $stmt = $conectar->prepare($sql);
                $stmt->bindValue(1, $uuidventa);
                $stmt->execute();
                $nota = $stmt->fetch(PDO::FETCH_ASSOC);
        
                // Consulta para obtener datos de la tabla tblnotasdeentregadetalle
                $sql = "SELECT * FROM tblnotasdeentregadetalle WHERE UUIDVenta = ? ORDER BY NoItem";
                $stmt = $conectar->prepare($sql);
                $stmt->bindValue(1, $uuidventa);
                $stmt->execute();
                $detalle_notas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
                // Extraer solo la fecha y la hora
                $fecEmi = date('Y-m-d', strtotime($nota["FechaHoraGeneracion"]));
                $horEmi = date('H:i:s', strtotime($nota["FechaHoraGeneracion"]));
                $FechaHoraGeneracion = $nota['FechaHoraGeneracion'];
        
                $documentoRelacionado = null;

                $codigodegeneracion = $nota["CodigoDeGeneracion"];

                if (!empty($nota["DUI"])){
                    $tipoDocumento = "13";
                    $numDocumento = $nota["DUI"];//!empty($nota["DUI"]) ? str_replace("-", "", $nota["DUI"]) : NULL;
                } else {
                    $tipoDocumento = null;
                    $numDocumento = null;
                }
                // Formateando datos emison
            
                // buscando Codigo Departamento MH 
                $departamentoemisor = $this->buscarDEPARTAMENTOMH($sucursal["CodDepartamento"]); 
                
                // buscando Codigo Municipio MH 
                $municipioemisor = $this->buscarMUNICIPIOMH($sucursal["CodMunicipio"]); 
                
                // Formateando datos receptor
                $nrc = !empty($nota["NRC"]) ? str_replace("-", "", $nota["NRC"]) : null;
                $nombre = !empty($nota["NombreDeCliente"]) ? $nota["NombreDeCliente"] : null;                
                $codActividad = !empty($nota["CodigoActividad"]) ? $nota["CodigoActividad"] : null;
                $descActividad = !empty($nota["Actividad"]) ? $nota["Actividad"] : null;
                
                $complemento = !empty($nota["Direccion"]) ? $nota["Direccion"] : null;
                
                if(!empty($complemento)){ 
                    if(!empty($nota["IDDepartamento"])){ 
                        // buscando Codigo Departamento MH 
                        $departamento = $this->buscarDEPARTAMENTOMH($nota["IDDepartamento"]*1);
                    } else {
                        $departamento = null;
                    }
                    if(!empty($nota["IDMunicipio"])){ 
                        // buscando Codigo Municipio MH 
                        $municipio = $this->buscarMUNICIPIOMH($nota["IDMunicipio"]*1);
                    } else {
                        $municipio = null;
                    }
                } else {
                    $departamento = null;
                    $municipio = null;
                }

                $telefono = !empty($nota["TelMovilWhatsApp"]) ? $nota["TelMovilWhatsApp"] : null;
                $correo = !empty($nota["CorreoElectronico"]) ? $nota["CorreoElectronico"] : null;
    
                $json_data = [
                    "identificacion" => [
                        "ambiente" => $sucursal["AmbienteDTE"],
                        "codigoGeneracion" => $nota["CodigoDeGeneracion"],
                        "fecEmi" => $fecEmi,
                        "horEmi" => $horEmi,
                        "numeroControl" => $nota["NumeroDeControl"],
                        "tipoDte" => $nota["CodDocumento"],
                        "tipoModelo" => $nota["ModeloDeFacturacion"],
                        "tipoOperacion" => $nota["TipoDeTransmision"],
                        "tipoContingencia" => $nota["TipoContingencia"],
                        "motivoContin" => $nota["MotivoContingencia"],
                        "tipoMoneda" => $nota["TipoMoneda"],
                        "version" => $nota["VersionDTE"]
                    ],
                    "documentoRelacionado" => $documentoRelacionado,
                    "emisor" => [
                        "codActividad" => $sucursal["CodActividad"],
                        "descActividad" => $sucursal["DescripcionActividad"],
                        "codEstable" => $sucursal["CodEstablecimiento"],
                        "codEstableMH" => $nota["CodEstablecimientoMH"],
                        "codPuntoVenta" => $nota["CodPuntoVenta"],
                        "codPuntoVentaMH" => $nota["CodPuntoVentaMH"],
                        "correo" => $sucursal["CorreoElectronico"],
                        "nit" => $sucursal["NIT"],
                        "nombre" => $sucursal["RazonSocial"],
                        "nombreComercial" => $sucursal["NombreNegocio"],
                        "nrc" => $sucursal["NRC"],
                        "telefono" => $sucursal["Telefono"],
                        "tipoEstablecimiento" => $sucursal["TipoEstablecimiento"],
                        "direccion" => [
                            "complemento" => $sucursal["Direccion"],
                            "departamento" => $departamentoemisor,
                            "municipio" => $municipioemisor
                        ]
                    ],
                    "receptor" => [
                        "tipoDocumento" => $tipoDocumento,
                        "numDocumento" => $numDocumento,
                        "nrc" => null, //$nrc,
                        "nombre" => $nombre,
                        "codActividad" => $codActividad,
                        "descActividad" => $descActividad,
                        "telefono" => $telefono,
                        "correo" => $correo,
                        "direccion" => !empty($complemento) ? [
                            "complemento" => $complemento,
                            "departamento" => $departamento,
                            "municipio" => $municipio
                        ] : null
                    ],
                    "otrosDocumentos" => null,
                    "ventaTercero" => null,
                    "cuerpoDocumento" => array_map(function($detalle) {
                        return [
                            "numItem" => $detalle["NoItem"],
                            "tipoItem" => $detalle["TipoDeItem"],
                            "uniMedida" => floatval($detalle["UnidadDeMedida"]),
                            "cantidad" => floatval($detalle["Cantidad"]),
                            "codigo" => $detalle["CodigoPROD"],
                            "descripcion" => $detalle["Concepto"],
                            "ivaItem" => floatval($detalle["IVAItem"]),
                            "noGravado" => floatval($detalle["VentaNoSujeta"]),
                            "precioUni" => floatval($detalle["PrecioVenta"]),
                            "psv" => floatval($detalle["PrecioSugeridoVenta"]),
                            "montoDescu" => floatval($detalle["Descuento"]),
                            "ventaExenta" => floatval($detalle["VentaExenta"]),
                            "ventaGravada" => floatval($detalle["VentaGravada"]),
                            "ventaNoSuj" => floatval($detalle["VentaNoSujeta"]),
                            "numeroDocumento" => null,
                            "codTributo" => null, //$detalle["CodigoTributo"],
                            "tributos" => null
                        ];
                    }, $detalle_notas),
                    "resumen" => [
                        "condicionOperacion" => $nota["Condicion"],
                        "descuNoSuj" => floatval($nota["DescuentosNoSujetas"]),
                        "descuExenta" => floatval($nota["DescuentosExentas"]),
                        "descuGravada" => floatval($nota["DescuentosGravadas"]),
                        "numPagoElectronico" => null,//$nota["NumPagoElectronico"],
                        "porcentajeDescuento" => floatval($nota["PorcentajeDescuento"]),
                        "reteRenta" => floatval($nota["RetencionRenta"]),
                        "ivaRete1" => floatval($nota["IVARetencion"]),
                        "saldoFavor" => floatval($nota["SaldoFavor"]),
                        "totalDescu" => floatval($nota["TotalDescuentos"]),
                        "totalNoSuj" => floatval($nota["TotalNoSujetas"]),
                        "totalNoGravado" => floatval($nota["TotalNoGravado"]),
                        "totalExenta" => floatval($nota["TotalExentas"]),
                        "totalGravada" => floatval($nota["TotalGravadas"]),
                        "subTotal" => floatval($nota["SubTotal"]),
                        "totalIva" => floatval($nota["TotalIVA"]),
                        "subTotalVentas" => floatval($nota["SubTotalVentas"]),
                        "montoTotalOperacion" => floatval($nota["TotalOperacion"]),
                        "totalPagar" => floatval($nota["TotalPagar"]),
                        "totalLetras" => $nota["TotalLetras"],
                        "pagos" => null,
                        "tributos" => null
                    ],
                    "extension" => null,
                    "apendice" => null
                ];

                $json = json_encode($json_data, JSON_PRETTY_PRINT);
               
                // Actualizar JSON generado en tblnotasdeentrega
                $sql = "UPDATE tblnotasdeentrega SET JSONGenerado = ? WHERE UUIDVenta = ?";
                $stmt = $conectar->prepare($sql);
                $stmt->bindValue(1, $json);
                $stmt->bindValue(2, $uuidventa);
                $stmt->execute();

                // Estableciendo valores para el firmado
                $nit = $NITNegocio;
                $passwordPri = $ClavePrivada;

                $dteJson = json_encode($json_data);

                // URL del firmador
                $url = $URLFirmador;

                // Convertir el JSON recibido a un array
                $dteJsonDecoded = json_decode($dteJson, true);
                if ($dteJsonDecoded === null) {
                    die(json_encode(["error" => "El JSON del documento es inválido"]));
                }

                // Construir los datos para enviar
                $data = [
                    "nit" => $nit,
                    "activo" => true, // Se asume que siempre está activo
                    "passwordPri" => $passwordPri,
                    "dteJson" => $dteJsonDecoded
                ];

                // Convertir los datos a JSON
                $jsonData = json_encode($data);

                // Inicializar cURL
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    "Content-Type: application/json"
                ]);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);

                // Ejecutar la solicitud y obtener la respuesta
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                $responseArray = json_decode($response, true);

                $JSONfirmado = json_encode($responseArray, JSON_PRETTY_PRINT);

                // Actualizar JSON generado en tblnotasdeentrega
                $sql = "UPDATE tblnotasdeentrega SET JSONFirmado = ? WHERE UUIDVenta = ?";
                $stmt = $conectar->prepare($sql);
                $stmt->bindValue(1, $JSONfirmado);
                $stmt->bindValue(2, $uuidventa);
                $stmt->execute();

                // Estableciendo valores para generar el token
                $user = $nit; 
                $pass = $ClaveAPI;

                $token = $this->getToken($URLAutenticacion,$user, $pass);

                // Actualizar JSON Token en tblnotasdeentrega
                $sql = "UPDATE tblnotasdeentrega SET Token = ? WHERE UUIDVenta = ?";
                $stmt = $conectar->prepare($sql);
                $stmt->bindValue(1, $token);
                $stmt->bindValue(2, $uuidventa);
                $stmt->execute();

                $respuesta = $this->transmitir_DTE($URLRecepcion,$AmbienteTransmision,1,$responseArray['body'],$codigodegeneracion, $token, $nota["CodDocumento"], $nota["VersionDTE"]);

                $respuestaMH = json_encode($respuesta, JSON_PRETTY_PRINT);
                
                // Actualizar JSON ResustaMH en tblnotasdeentrega
                $sql = "UPDATE tblnotasdeentrega SET RespuestaMH = ? WHERE UUIDVenta = ?";
                $stmt = $conectar->prepare($sql);
                $stmt->bindValue(1, $respuestaMH);
                $stmt->bindValue(2, $uuidventa);
                $stmt->execute();

                $estado = $respuesta['estado'];
                
                if ($estado == "PROCESADO") {
                    $selloRecibido = $respuesta['selloRecibido'];
                    // Actualizar JSON Sello Recibido en tblnotasdeentrega
                    $sql = "UPDATE tblnotasdeentrega 
                            SET SelloDeRecepcion = ?, 
                            Estado = 3 
                            WHERE UUIDVenta = ?";
                    $stmt = $conectar->prepare($sql);
                    $stmt->bindValue(1, $selloRecibido);
                    $stmt->bindValue(2, $uuidventa);
                    $stmt->execute();

                    // Creando JSON en carpeta
                    $fechaCarpeta = date("Y-m-d", strtotime($FechaHoraGeneracion));
                    $mesCarpeta = date("mY", strtotime($FechaHoraGeneracion));

                    $rutaBase = '../../documentos/contabilidad/DTEjson';
                    $rutaSucursal = $sucursalid;
                    $rutaMes = $mesCarpeta;
                    $rutaFinal = $rutaBase . '/' .$rutaMes . '/' . $rutaSucursal . '-' . $rutaMes . '/' . $fechaCarpeta;

                    // Crear carpetas si no existen
                    if (!file_exists($rutaFinal)) {
                        mkdir($rutaFinal, 0777, true);
                    }

                    // Decodificar los datos como array
                    $jsonGeneradoDecoded = $json_data; // ← ya debe ser array
                    $jsonFirmadoDecoded = json_decode($JSONfirmado, true);
                    $firmadoBody = isset($jsonFirmadoDecoded['body']) ? $jsonFirmadoDecoded['body'] : null;

                    // Armar la estructura final fusionando todo
                    $jsonFinal = $jsonGeneradoDecoded;
                    $jsonFinal['selloRecibido'] = $selloRecibido;
                    $jsonFinal['firmaElectronica'] = $firmadoBody;

                    // Convertir a JSON string
                    $jsonFinalString = json_encode($jsonFinal, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

                    // Guardar también en la base de datos
                    $sql = "UPDATE tblnotasdeentrega SET JSONGenerado = ? WHERE UUIDVenta = ?";
                    $stmt = $conectar->prepare($sql);
                    $stmt->bindValue(1, $jsonFinalString);
                    $stmt->bindValue(2, $uuidventa);
                    $stmt->execute();

                    // Guardar en archivo
                    $ruta = $rutaFinal . '/' . $codigodegeneracion . ".json";
                    file_put_contents($ruta, $jsonFinalString);
                

                    $data = "Procesado";
                } else {
                    // Actualizar JSON Estado Rechazado en tblnotasdeentrega
                    $sql = "UPDATE tblnotasdeentrega 
                            SET Estado = 4 
                            WHERE UUIDVenta = ?";
                    $stmt = $conectar->prepare($sql);
                    $stmt->bindValue(1, $uuidventa);
                    $stmt->execute();
                    $data = "Rechazado";
                }
                return $data;
                
        }

        public function generar_DTE_CreditoFiscal($tipotransmision, $uuidventa, $sucursalid, $terminalid) {
            $conectar = parent::conexion();
            parent::set_names();
        
                // Consulta para obtener datos de la tabla tblsucursales
                $sql = "SELECT s.*, n.* 
                        FROM tblsucursales s 
                        INNER JOIN tblnegocios n ON s.UUIDNegocio = n.UUIDNegocio 
                        WHERE s.UUIDSucursal = ?";
                $stmt = $conectar->prepare($sql);
                $stmt->bindValue(1, $sucursalid);
                $stmt->execute();
                $sucursal = $stmt->fetch(PDO::FETCH_ASSOC);

                $uuidnegocio = $sucursal["UUIDNegocio"];

                // Consulta para obtener datos de la tabla tblnegocios
                $sql = "SELECT * FROM tblnegocios WHERE UUIDNegocio = ?";
                $stmt = $conectar->prepare($sql);
                $stmt->bindValue(1, $uuidnegocio);
                $stmt->execute();
                $negocio = $stmt->fetch(PDO::FETCH_ASSOC);

                $NITNegocio = $sucursal["NIT"];
                $ClavePrivada = $negocio["ClavePrivada"];
                $ClavePublica = $negocio["ClavePublica"];
                $ClaveAPI = $negocio["ClaveAPI"];
                $AmbienteTransmision = $negocio["Ambiente"];

                $URLFirmador = $negocio["URLFirmador"];
                $URLAutenticacion = $negocio["URLAutenticacion"];
                $URLRecepcion = $negocio["URLRecepcion"];
                $URLRecepcionLOTE = $negocio["URLRecepcionLOTE"];
                $URLConsultarDTE = $negocio["URLConsultarDTE"];
                $URLContingencia = $negocio["URLContingencia"];
                $URLAnularDTE = $negocio["URLAnularDTE"];
        
                // Consulta para obtener datos de la tabla tblterminales
                $sql = "SELECT * FROM tblterminales WHERE UUIDTerminal = ?";
                $stmt = $conectar->prepare($sql);
                $stmt->bindValue(1, $terminalid);
                $stmt->execute();
                $terminal = $stmt->fetch(PDO::FETCH_ASSOC);
        
                // Consulta para obtener datos de la tabla tblnotasdeentrega
                $sql = "SELECT * FROM tblnotasdeentrega WHERE UUIDVenta = ?";
                $stmt = $conectar->prepare($sql);
                $stmt->bindValue(1, $uuidventa);
                $stmt->execute();
                $nota = $stmt->fetch(PDO::FETCH_ASSOC);
        
                // Consulta para obtener datos de la tabla tblnotasdeentregadetalle
                $sql = "SELECT * FROM tblnotasdeentregadetalle WHERE UUIDVenta = ?";
                $stmt = $conectar->prepare($sql);
                $stmt->bindValue(1, $uuidventa);
                $stmt->execute();
                $detalle_notas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
                // Extraer solo la fecha y la hora
                $fecEmi = date('Y-m-d', strtotime($nota["FechaRegistro"]));
                $horEmi = date('H:i:s', strtotime($nota["FechaRegistro"]));
                $FechaHoraGeneracion = $nota['FechaHoraGeneracion'];

                $documentoRelacionado = null;

                $codigodegeneracion = $nota["CodigoDeGeneracion"];

                // Formateando datos emison            
                
                // buscando Codigo Departamento MH 
                $departamentoemisor = $this->buscarDEPARTAMENTOMH($sucursal["CodDepartamento"]); 
                
                // buscando Codigo Municipio MH 
                $municipioemisor = $this->buscarMUNICIPIOMH($sucursal["CodMunicipio"]); 
                
                // Formateando datos receptor
                
                $nrc = !empty($nota["NRC"]) ? str_replace("-", "", $nota["NRC"]): null;
                $nit = !empty($nota["NIT"]) ? str_replace("-", "", $nota["NIT"]) : null;
                $nombre = !empty($nota["NombreDeCliente"]) ? $nota["NombreDeCliente"] : null;
                $nombreComercial = !empty($nota["NombreComercial"]) ? $nota["NombreComercial"] : null;
                $codActividad = !empty($nota["CodigoActividad"]) ? $nota["CodigoActividad"] : null;
                $descActividad = !empty($nota["Actividad"]) ? $nota["Actividad"] : null;
                $complemento = !empty($nota["Direccion"]) ? $nota["Direccion"] : null;
                
                if(!empty($complemento)){ 
                    if(!empty($nota["IDDepartamento"])){ 
                        // buscando Codigo Departamento MH 
                        $departamento = $this->buscarDEPARTAMENTOMH($nota["IDDepartamento"]*1);
                    } else {
                        $departamento = null;
                    }
                    if(!empty($nota["IDMunicipio"])){ 
                        // buscando Codigo Municipio MH 
                        $municipio = $this->buscarMUNICIPIOMH($nota["IDMunicipio"]*1);
                    } else {
                        $municipio = null;
                    }
                } else {
                    $departamento = null;
                    $municipio = null;
                }

                $telefono = !empty($nota["TelMovilWhatsApp"]) ? $nota["TelMovilWhatsApp"] : null;
                $correo = !empty($nota["CorreoElectronico"]) ? $nota["CorreoElectronico"] : null;
                
                $json_data = [
                    "identificacion" => [
                        "ambiente" => $sucursal["AmbienteDTE"],
                        "codigoGeneracion" => $nota["CodigoDeGeneracion"],
                        "fecEmi" => $fecEmi,
                        "horEmi" => $horEmi,
                        "numeroControl" => $nota["NumeroDeControl"],
                        "tipoDte" => $nota["CodDocumento"],
                        "tipoModelo" => $nota["ModeloDeFacturacion"],
                        "tipoOperacion" => $nota["TipoDeTransmision"],
                        "tipoContingencia" => $nota["TipoContingencia"],
                        "motivoContin" => $nota["MotivoContingencia"],
                        "tipoMoneda" => $nota["TipoMoneda"],
                        "version" => $nota["VersionDTE"]
                    ],
                    "documentoRelacionado" => $documentoRelacionado,
                    "emisor" => [
                        "codActividad" => $sucursal["CodActividad"],
                        "codEstable" => $sucursal["CodEstablecimiento"],
                        "codEstableMH" => $nota["CodEstablecimientoMH"],
                        "codPuntoVenta" => $nota["CodPuntoVenta"],
                        "codPuntoVentaMH" => $nota["CodPuntoVentaMH"],
                        "correo" => $sucursal["CorreoElectronico"],
                        "descActividad" => $sucursal["DescripcionActividad"],
                        "nit" => $sucursal["NIT"],
                        "nombre" => $sucursal["RazonSocial"],
                        "nombreComercial" => $sucursal["NombreNegocio"],
                        "nrc" => $sucursal["NRC"],
                        "telefono" => $sucursal["Telefono"],
                        "tipoEstablecimiento" => $sucursal["TipoEstablecimiento"],
                        "direccion" => [
                            "complemento" => $sucursal["Direccion"],
                            "departamento" => $departamentoemisor,
                            "municipio" => $municipioemisor
                        ]
                    ],
                    "receptor" => [
                        "nrc" => $nrc,
                        "nit" => $nit,
                        "nombre" => $nombre,
                        "nombreComercial" => $nombreComercial,
                        "codActividad" => $codActividad,
                        "descActividad" => $descActividad,
                        "telefono" => $telefono,
                        "correo" => $correo,
                        "direccion" => !empty($complemento) ? [
                            "complemento" => $complemento,
                            "departamento" => $departamento,
                            "municipio" => $municipio
                        ] : null
                    ],
                    "otrosDocumentos" => null,
                    "ventaTercero" => null,
                    "cuerpoDocumento" => array_map(function($detalle) {
                        return [
                            "numItem" => $detalle["NoItem"],
                            "tipoItem" => $detalle["TipoDeItem"],
                            "numeroDocumento" => null,
                            "codigo" => $detalle["CodigoPROD"],
                            "descripcion" => $detalle["Concepto"],
                            "uniMedida" => floatval($detalle["UnidadDeMedida"]),
                            "cantidad" => floatval($detalle["Cantidad"]),
                            "precioUni" => floatval($detalle["PrecioVentaSinImpuesto"]),
                            "montoDescu" => floatval($detalle["Descuento"]),
                            "ventaNoSuj" => floatval($detalle["VentaNoSujeta"]),
                            "ventaExenta" => floatval($detalle["VentaExenta"]),
                            "ventaGravada" => floatval($detalle["VentaGravadaSinImpuesto"]),
                            "noGravado" => floatval($detalle["VentaNoSujeta"]),
                            "psv" => floatval($detalle["PrecioSugeridoVenta"]),
                            "codTributo" => null,
                            "tributos" => [$detalle["Tributo"]]
                        ];
                    }, $detalle_notas),
                    "resumen" => [
                        "totalNoSuj" => floatval($nota["TotalNoSujetas"]),
                        "totalExenta" => floatval($nota["TotalExentas"]),
                        "totalNoGravado" => floatval($nota["TotalNoGravado"]),
                        "totalGravada" => floatval($nota["TotalGravadas"]),
                        "subTotal" => floatval($nota["SubTotal"]),
                        "descuNoSuj" => floatval($nota["DescuentosNoSujetas"]),
                        "descuExenta" => floatval($nota["DescuentosExentas"]),
                        "descuGravada" => floatval($nota["DescuentosGravadas"]),
                        "subTotalVentas" => floatval($nota["SubTotalVentas"]),
                        "porcentajeDescuento" => floatval($nota["PorcentajeDescuento"]),
                        "ivaRete1" => floatval($nota["IVARetencion"]),
                        "ivaPerci1" => floatval($nota["IVAPercibido"]),
                        "reteRenta" => floatval($nota["RetencionRenta"]),
                        "montoTotalOperacion" => floatval($nota["TotalOperacion"]),
                        "totalDescu" => floatval($nota["TotalDescuentos"]),
                        "totalPagar" => floatval($nota["TotalPagar"]),
                        "totalLetras" => $nota["TotalLetras"],
                        "saldoFavor" => floatval($nota["SaldoFavor"]),
                        "condicionOperacion" => $nota["Condicion"],
                        "pagos" => null,
                        "numPagoElectronico" => null,//$nota["NumPagoElectronico"],                    
                        "tributos" => [json_decode($nota["Tributos"], true)]
                    ],
                    "extension" => null,
                    "apendice" => null
                ];

                $json = json_encode($json_data, JSON_PRETTY_PRINT);
               
                // Actualizar JSON generado en tblnotasdeentrega
                $sql = "UPDATE tblnotasdeentrega SET JSONGenerado = ? WHERE UUIDVenta = ?";
                $stmt = $conectar->prepare($sql);
                $stmt->bindValue(1, $json);
                $stmt->bindValue(2, $uuidventa);
                $stmt->execute();

                // Estableciendo valores para el firmado
                $nit = $NITNegocio;
                $passwordPri = $ClavePrivada;

                $dteJson = json_encode($json_data);

                // URL del firmador
                $url = $URLFirmador;

                // Convertir el JSON recibido a un array
                $dteJsonDecoded = json_decode($dteJson, true);
                if ($dteJsonDecoded === null) {
                    die(json_encode(["error" => "El JSON del documento es inválido"]));
                }

                // Construir los datos para enviar
                $data = [
                    "nit" => $nit,
                    "activo" => true, // Se asume que siempre está activo
                    "passwordPri" => $passwordPri,
                    "dteJson" => $dteJsonDecoded
                ];

                // Convertir los datos a JSON
                $jsonData = json_encode($data);

                // Inicializar cURL
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    "Content-Type: application/json"
                ]);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);

                // Ejecutar la solicitud y obtener la respuesta
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                $responseArray = json_decode($response, true);

                $JSONfirmado = json_encode($responseArray, JSON_PRETTY_PRINT);

                // Actualizar JSON generado en tblnotasdeentrega
                $sql = "UPDATE tblnotasdeentrega SET JSONFirmado = ? WHERE UUIDVenta = ?";
                $stmt = $conectar->prepare($sql);
                $stmt->bindValue(1, $JSONfirmado);
                $stmt->bindValue(2, $uuidventa);
                $stmt->execute();

                // Estableciendo valores para generar el token
                $user = $nit; 
                $pass = $ClaveAPI;

                $token = $this->getToken($URLAutenticacion,$user, $pass);

                // Actualizar JSON Token en tblnotasdeentrega
                $sql = "UPDATE tblnotasdeentrega SET Token = ? WHERE UUIDVenta = ?";
                $stmt = $conectar->prepare($sql);
                $stmt->bindValue(1, $token);
                $stmt->bindValue(2, $uuidventa);
                $stmt->execute();

                $respuesta = $this->transmitir_DTE($URLRecepcion,$AmbienteTransmision,1,$responseArray['body'],$codigodegeneracion, $token, $nota["CodDocumento"], $nota["VersionDTE"]);

                $respuestaMH = json_encode($respuesta, JSON_PRETTY_PRINT);
                
                // Actualizar JSON ResustaMH en tblnotasdeentrega
                $sql = "UPDATE tblnotasdeentrega SET RespuestaMH = ? WHERE UUIDVenta = ?";
                $stmt = $conectar->prepare($sql);
                $stmt->bindValue(1, $respuestaMH);
                $stmt->bindValue(2, $uuidventa);
                $stmt->execute();

                $estado = $respuesta['estado'];
                
                if ($estado == "PROCESADO") {
                    $selloRecibido = $respuesta['selloRecibido'];
                    // Actualizar JSON Sello Recibido en tblnotasdeentrega
                    $sql = "UPDATE tblnotasdeentrega 
                            SET SelloDeRecepcion = ?, 
                            Estado = 3 
                            WHERE UUIDVenta = ?";
                    $stmt = $conectar->prepare($sql);
                    $stmt->bindValue(1, $selloRecibido);
                    $stmt->bindValue(2, $uuidventa);
                    $stmt->execute();

                    // Creando JSON en carpeta
                    $fechaCarpeta = date("Y-m-d", strtotime($FechaHoraGeneracion));
                    $mesCarpeta = date("mY", strtotime($FechaHoraGeneracion));

                    $rutaBase = '../../documentos/contabilidad/DTEjson';
                    $rutaSucursal = $sucursalid;
                    $rutaMes = $mesCarpeta;
                    $rutaFinal = $rutaBase . '/' .$rutaMes . '/' . $rutaSucursal . '-' . $rutaMes . '/' . $fechaCarpeta;

                    // Crear carpetas si no existen
                    if (!file_exists($rutaFinal)) {
                        mkdir($rutaFinal, 0777, true);
                    }

                    // Decodificar los datos como array
                    $jsonGeneradoDecoded = $json_data; // ← ya debe ser array
                    $jsonFirmadoDecoded = json_decode($JSONfirmado, true);
                    $firmadoBody = isset($jsonFirmadoDecoded['body']) ? $jsonFirmadoDecoded['body'] : null;

                    // Armar la estructura final fusionando todo
                    $jsonFinal = $jsonGeneradoDecoded;
                    $jsonFinal['selloRecibido'] = $selloRecibido;
                    $jsonFinal['firmaElectronica'] = $firmadoBody;

                    // Convertir a JSON string
                    $jsonFinalString = json_encode($jsonFinal, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

                    // Actualizar en base de datos
                    $sql = "UPDATE tblnotasdeentrega SET JSONGenerado = ? WHERE UUIDVenta = ?";
                    $stmt = $conectar->prepare($sql);
                    $stmt->bindValue(1, $jsonFinalString);
                    $stmt->bindValue(2, $uuidventa);
                    $stmt->execute();

                    // Guardar en archivo
                    $ruta = $rutaFinal . '/' . $codigodegeneracion . ".json";
                    file_put_contents($ruta, $jsonFinalString);

                    $data = "Procesado";
                } else {
                    // Actualizar JSON Estado Rechazado en tblnotasdeentrega
                    $sql = "UPDATE tblnotasdeentrega 
                            SET Estado = 4 
                            WHERE UUIDVenta = ?";
                    $stmt = $conectar->prepare($sql);
                    $stmt->bindValue(1, $uuidventa);
                    $stmt->execute();
                    $data = "Rechazado";
                }
                return $data;
        }

        public function generar_DTE_NotaDeCredito($tipotransmision, $uuidventa, $sucursalid, $terminalid) {
            $conectar = parent::conexion();
            parent::set_names();
        
                // Consulta para obtener datos de la tabla tblsucursales
                $sql = "SELECT s.*, n.* 
                        FROM tblsucursales s 
                        INNER JOIN tblnegocios n ON s.UUIDNegocio = n.UUIDNegocio 
                        WHERE s.UUIDSucursal = ?";
                $stmt = $conectar->prepare($sql);
                $stmt->bindValue(1, $sucursalid);
                $stmt->execute();
                $sucursal = $stmt->fetch(PDO::FETCH_ASSOC);

                $uuidnegocio = $sucursal["UUIDNegocio"];

                // Consulta para obtener datos de la tabla tblnegocios
                $sql = "SELECT * FROM tblnegocios WHERE UUIDNegocio = ?";
                $stmt = $conectar->prepare($sql);
                $stmt->bindValue(1, $uuidnegocio);
                $stmt->execute();
                $negocio = $stmt->fetch(PDO::FETCH_ASSOC);

                $NITNegocio = $sucursal["NIT"];
                $ClavePrivada = $negocio["ClavePrivada"];
                $ClavePublica = $negocio["ClavePublica"];
                $ClaveAPI = $negocio["ClaveAPI"];
                $AmbienteTransmision = $negocio["Ambiente"];

                $URLFirmador = $negocio["URLFirmador"];
                $URLAutenticacion = $negocio["URLAutenticacion"];
                $URLRecepcion = $negocio["URLRecepcion"];
                $URLRecepcionLOTE = $negocio["URLRecepcionLOTE"];
                $URLConsultarDTE = $negocio["URLConsultarDTE"];
                $URLContingencia = $negocio["URLContingencia"];
                $URLAnularDTE = $negocio["URLAnularDTE"];
        
                // Consulta para obtener datos de la tabla tblterminales
                $sql = "SELECT * FROM tblterminales WHERE UUIDTerminal = ?";
                $stmt = $conectar->prepare($sql);
                $stmt->bindValue(1, $terminalid);
                $stmt->execute();
                $terminal = $stmt->fetch(PDO::FETCH_ASSOC);
        
                // Consulta para obtener datos de la tabla tblnotasdeentrega
                $sql = "SELECT * FROM tblnotasdeentrega WHERE UUIDVenta = ?";
                $stmt = $conectar->prepare($sql);
                $stmt->bindValue(1, $uuidventa);
                $stmt->execute();
                $nota = $stmt->fetch(PDO::FETCH_ASSOC);
        
                // Consulta para obtener datos de la tabla tblnotasdeentregadetalle
                $sql = "SELECT * FROM tblnotasdeentregadetalle WHERE UUIDVenta = ?";
                $stmt = $conectar->prepare($sql);
                $stmt->bindValue(1, $uuidventa);
                $stmt->execute();
                $detalle_notas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
                // Extraer solo la fecha y la hora
                $fecEmi = date('Y-m-d', strtotime($nota["FechaRegistro"]));
                $horEmi = date('H:i:s', strtotime($nota["FechaRegistro"]));
                $FechaHoraGeneracion = $nota['FechaHoraGeneracion'];

                $codigodegeneracion = $nota["CodigoDeGeneracion"];

                // Formateando datos emison            
                
                // buscando Codigo Departamento MH 
                $departamentoemisor = $this->buscarDEPARTAMENTOMH($sucursal["CodDepartamento"]); 
                
                // buscando Codigo Municipio MH 
                $municipioemisor = $this->buscarMUNICIPIOMH($sucursal["CodMunicipio"]); 
                
                // Formateando datos receptor
                
                $nrc = !empty($nota["NRC"]) ? str_replace("-", "", $nota["NRC"]): null;
                $nit = !empty($nota["NIT"]) ? str_replace("-", "", $nota["NIT"]) : null;
                $nombre = !empty($nota["NombreDeCliente"]) ? $nota["NombreDeCliente"] : null;
                $nombreComercial = !empty($nota["NombreComercial"]) ? $nota["NombreComercial"] : null;
                $codActividad = !empty($nota["CodigoActividad"]) ? $nota["CodigoActividad"] : null;
                $descActividad = !empty($nota["Actividad"]) ? $nota["Actividad"] : null;
                $complemento = !empty($nota["Direccion"]) ? $nota["Direccion"] : null;
                
                if(!empty($complemento)){ 
                    if(!empty($nota["IDDepartamento"])){ 
                        // buscando Codigo Departamento MH 
                        $departamento = $this->buscarDEPARTAMENTOMH($nota["IDDepartamento"]*1);
                    } else {
                        $departamento = null;
                    }
                    if(!empty($nota["IDMunicipio"])){ 
                        // buscando Codigo Municipio MH 
                        $municipio = $this->buscarMUNICIPIOMH($nota["IDMunicipio"]*1);
                    } else {
                        $municipio = null;
                    }
                } else {
                    $departamento = null;
                    $municipio = null;
                }

                $telefono = !empty($nota["TelMovilWhatsApp"]) ? $nota["TelMovilWhatsApp"] : null;
                $correo = !empty($nota["CorreoElectronico"]) ? $nota["CorreoElectronico"] : null;
                
                /*$documentos =[
                    "tipoDocumento" => "03",
                    "tipoGeneracion"=> 2,
                    "numeroDocumento"=> "2775DF22-7BE8-42DB-A7BE-255DFF441BB8",
                    "fechaEmision" => "2025-03-25"
                ];
                $documentosRelacionados = json_encode($documentos, JSON_PRETTY_PRINT);*/

                $json_data = [
                    "identificacion" => [
                        "ambiente" => $sucursal["AmbienteDTE"],
                        "codigoGeneracion" => $nota["CodigoDeGeneracion"],
                        "fecEmi" => $fecEmi,
                        "horEmi" => $horEmi,
                        "numeroControl" => $nota["NumeroDeControl"],
                        "tipoDte" => $nota["CodDocumento"],
                        "tipoModelo" => $nota["ModeloDeFacturacion"],
                        "tipoOperacion" => $nota["TipoDeTransmision"],
                        "tipoContingencia" => $nota["TipoContingencia"],
                        "motivoContin" => $nota["MotivoContingencia"],
                        "tipoMoneda" => $nota["TipoMoneda"],
                        "version" => $nota["VersionDTE"]
                    ],
                    "emisor" => [
                        "codActividad" => $sucursal["CodActividad"],
                        "descActividad" => $sucursal["DescripcionActividad"],
                        "nit" => $sucursal["NIT"],
                        "nombre" => $sucursal["RazonSocial"],
                        "nombreComercial" => $sucursal["NombreNegocio"],
                        "nrc" => $sucursal["NRC"],
                        "telefono" => $sucursal["Telefono"],
                        "correo" => $sucursal["CorreoElectronico"],
                        "tipoEstablecimiento" => $sucursal["TipoEstablecimiento"],
                        "direccion" => [
                            "complemento" => $sucursal["Direccion"],
                            "departamento" => $departamentoemisor,
                            "municipio" => $municipioemisor
                        ]
                    ],
                    "receptor" => [
                        "nrc" => $nrc,
                        "nit" => $nit,
                        "nombre" => $nombre,
                        "nombreComercial" => $nombreComercial,
                        "codActividad" => $codActividad,
                        "descActividad" => $descActividad,
                        "telefono" => $telefono,
                        "correo" => $correo,
                        "direccion" => !empty($complemento) ? [
                            "complemento" => $complemento,
                            "departamento" => $departamento,
                            "municipio" => $municipio
                        ] : null
                    ],
                    "ventaTercero" => null,
                    "documentoRelacionado" => [json_decode($nota["DocumentoRelacionado"], true)],
                    "cuerpoDocumento" => array_map(function($detalle) {
                        return [
                            "numItem" => $detalle["NoItem"],
                            "tipoItem" => $detalle["TipoDeItem"],
                            "numeroDocumento" => $detalle["NumeroDocumento"],
                            "codigo" => $detalle["CodigoPROD"],
                            "descripcion" => $detalle["Concepto"],
                            "uniMedida" => floatval($detalle["UnidadDeMedida"]),
                            "cantidad" => floatval($detalle["Cantidad"]),
                            "precioUni" => floatval($detalle["PrecioVentaSinImpuesto"]),
                            "montoDescu" => floatval($detalle["Descuento"]),
                            "ventaNoSuj" => floatval($detalle["VentaNoSujeta"]),
                            "ventaExenta" => floatval($detalle["VentaExenta"]),
                            "ventaGravada" => floatval($detalle["VentaGravadaSinImpuesto"]),
                            "codTributo" => null,
                            "tributos" => [$detalle["Tributo"]]
                        ];
                    }, $detalle_notas),
                    "resumen" => [
                        "totalNoSuj" => floatval($nota["TotalNoSujetas"]),
                        "totalExenta" => floatval($nota["TotalExentas"]),
                        "totalGravada" => floatval($nota["TotalGravadas"]),
                        "subTotal" => floatval($nota["SubTotal"]),
                        "descuNoSuj" => floatval($nota["DescuentosNoSujetas"]),
                        "descuExenta" => floatval($nota["DescuentosExentas"]),
                        "descuGravada" => floatval($nota["DescuentosGravadas"]),
                        "subTotalVentas" => floatval($nota["SubTotalVentas"]),
                        "totalDescu" => floatval($nota["TotalDescuentos"]), 
                        "ivaRete1" => floatval($nota["IVARetencion"]),
                        "ivaPerci1" => floatval($nota["IVAPercibido"]),
                        "reteRenta" => floatval($nota["RetencionRenta"]),
                        "montoTotalOperacion" => floatval($nota["TotalOperacion"]),
                        "totalLetras" => $nota["TotalLetras"],
                        "condicionOperacion" => $nota["Condicion"],                  
                        "tributos" => [json_decode($nota["Tributos"], true)]
                    ],
                    "extension" => null,
                    "apendice" => null
                ];

                $json = json_encode($json_data, JSON_PRETTY_PRINT);
               
                // Actualizar JSON generado en tblnotasdeentrega
                $sql = "UPDATE tblnotasdeentrega SET JSONGenerado = ? WHERE UUIDVenta = ?";
                $stmt = $conectar->prepare($sql);
                $stmt->bindValue(1, $json);
                $stmt->bindValue(2, $uuidventa);
                $stmt->execute();

                // Estableciendo valores para el firmado
                $nit = $NITNegocio;
                $passwordPri = $ClavePrivada;

                $dteJson = json_encode($json_data);

                // URL del firmador
                $url = $URLFirmador;

                // Convertir el JSON recibido a un array
                $dteJsonDecoded = json_decode($dteJson, true);
                if ($dteJsonDecoded === null) {
                    die(json_encode(["error" => "El JSON del documento es inválido"]));
                }

                // Construir los datos para enviar
                $data = [
                    "nit" => $nit,
                    "activo" => true, // Se asume que siempre está activo
                    "passwordPri" => $passwordPri,
                    "dteJson" => $dteJsonDecoded
                ];

                // Convertir los datos a JSON
                $jsonData = json_encode($data);

                // Inicializar cURL
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    "Content-Type: application/json"
                ]);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);

                // Ejecutar la solicitud y obtener la respuesta
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                $responseArray = json_decode($response, true);

                $JSONfirmado = json_encode($responseArray, JSON_PRETTY_PRINT);

                // Actualizar JSON generado en tblnotasdeentrega
                $sql = "UPDATE tblnotasdeentrega SET JSONFirmado = ? WHERE UUIDVenta = ?";
                $stmt = $conectar->prepare($sql);
                $stmt->bindValue(1, $JSONfirmado);
                $stmt->bindValue(2, $uuidventa);
                $stmt->execute();

                // Estableciendo valores para generar el token
                $user = $nit; 
                $pass = $ClaveAPI;

                $token = $this->getToken($URLAutenticacion,$user, $pass);

                // Actualizar JSON Token en tblnotasdeentrega
                $sql = "UPDATE tblnotasdeentrega SET Token = ? WHERE UUIDVenta = ?";
                $stmt = $conectar->prepare($sql);
                $stmt->bindValue(1, $token);
                $stmt->bindValue(2, $uuidventa);
                $stmt->execute();

                $respuesta = $this->transmitir_DTE($URLRecepcion,$AmbienteTransmision,1,$responseArray['body'],$codigodegeneracion, $token, $nota["CodDocumento"], $nota["VersionDTE"]);

                $respuestaMH = json_encode($respuesta, JSON_PRETTY_PRINT);
                
                // Actualizar JSON ResustaMH en tblnotasdeentrega
                $sql = "UPDATE tblnotasdeentrega SET RespuestaMH = ? WHERE UUIDVenta = ?";
                $stmt = $conectar->prepare($sql);
                $stmt->bindValue(1, $respuestaMH);
                $stmt->bindValue(2, $uuidventa);
                $stmt->execute();

                $estado = $respuesta['estado'];
                
                if ($estado == "PROCESADO") {
                    $selloRecibido = $respuesta['selloRecibido'];
                    // Actualizar JSON Sello Recibido en tblnotasdeentrega
                    $sql = "UPDATE tblnotasdeentrega 
                            SET SelloDeRecepcion = ?, 
                            Estado = 3 
                            WHERE UUIDVenta = ?";
                    $stmt = $conectar->prepare($sql);
                    $stmt->bindValue(1, $selloRecibido);
                    $stmt->bindValue(2, $uuidventa);
                    $stmt->execute();

                    // Creando JSON en carpeta
                    $fechaCarpeta = date("Y-m-d", strtotime($FechaHoraGeneracion));
                    $mesCarpeta = date("mY", strtotime($FechaHoraGeneracion));

                    $rutaBase = '../../documentos/contabilidad/DTEjson';
                    $rutaSucursal = $sucursalid;
                    $rutaMes = $mesCarpeta;
                    $rutaFinal = $rutaBase . '/' .$rutaMes . '/' . $rutaSucursal . '-' . $rutaMes . '/' . $fechaCarpeta;

                    // Crear carpetas si no existen
                    if (!file_exists($rutaFinal)) {
                        mkdir($rutaFinal, 0777, true);
                    }

                    // Decodificar los datos como array
                    $jsonGeneradoDecoded = $json_data; // ← ya debe ser array
                    $jsonFirmadoDecoded = json_decode($JSONfirmado, true);
                    $firmadoBody = isset($jsonFirmadoDecoded['body']) ? $jsonFirmadoDecoded['body'] : null;

                    // Armar la estructura final fusionando todo
                    $jsonFinal = $jsonGeneradoDecoded;
                    $jsonFinal['selloRecibido'] = $selloRecibido;
                    $jsonFinal['firmaElectronica'] = $firmadoBody;

                    // Convertir a JSON string
                    $jsonFinalString = json_encode($jsonFinal, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

                    // Actualizar en la base de datos
                    $sql = "UPDATE tblnotasdeentrega SET JSONGenerado = ? WHERE UUIDVenta = ?";
                    $stmt = $conectar->prepare($sql);
                    $stmt->bindValue(1, $jsonFinalString);
                    $stmt->bindValue(2, $uuidventa);
                    $stmt->execute();

                    // Guardar en archivo
                    $ruta = $rutaFinal . '/' . $codigodegeneracion . ".json";
                    file_put_contents($ruta, $jsonFinalString);

                    $data = "Procesado";
                } else {
                    // Actualizar JSON Estado Rechazado en tblnotasdeentrega
                    $sql = "UPDATE tblnotasdeentrega 
                            SET Estado = 4 
                            WHERE UUIDVenta = ?";
                    $stmt = $conectar->prepare($sql);
                    $stmt->bindValue(1, $uuidventa);
                    $stmt->execute();
                    $data = "Rechazado";
                }
                return $data;
        }

        public function generar_DTE_SujetoExcluido($tipotransmision, $uuidventa, $sucursalid, $terminalid) {
            
            $conectar = parent::conexion();
            parent::set_names();
    
            // Consulta para obtener datos de la tabla tblsucursales
            $sql = "SELECT s.*, n.* 
                    FROM tblsucursales s 
                    INNER JOIN tblnegocios n ON s.UUIDNegocio = n.UUIDNegocio 
                    WHERE s.UUIDSucursal = ?";
            $stmt = $conectar->prepare($sql);
            $stmt->bindValue(1, $sucursalid);
            $stmt->execute();
            $sucursal = $stmt->fetch(PDO::FETCH_ASSOC);

            $uuidnegocio = $sucursal["UUIDNegocio"];

            // Consulta para obtener datos de la tabla tblnegocios
            $sql = "SELECT * FROM tblnegocios WHERE UUIDNegocio = ?";
            $stmt = $conectar->prepare($sql);
            $stmt->bindValue(1, $uuidnegocio);
            $stmt->execute();
            $negocio = $stmt->fetch(PDO::FETCH_ASSOC);

            $NITNegocio = $sucursal["NIT"];
            $ClavePrivada = $negocio["ClavePrivada"];
            $ClavePublica = $negocio["ClavePublica"];
            $ClaveAPI = $negocio["ClaveAPI"];
            $AmbienteTransmision = $negocio["Ambiente"];

            $URLFirmador = $negocio["URLFirmador"];
            $URLAutenticacion = $negocio["URLAutenticacion"];
            $URLRecepcion = $negocio["URLRecepcion"];
            $URLRecepcionLOTE = $negocio["URLRecepcionLOTE"];
            $URLConsultarDTE = $negocio["URLConsultarDTE"];
            $URLContingencia = $negocio["URLContingencia"];
            $URLAnularDTE = $negocio["URLAnularDTE"];
    
            // Consulta para obtener datos de la tabla tblterminales
            $sql = "SELECT * FROM tblterminales WHERE UUIDTerminal = ?";
            $stmt = $conectar->prepare($sql);
            $stmt->bindValue(1, $terminalid);
            $stmt->execute();
            $terminal = $stmt->fetch(PDO::FETCH_ASSOC);
    
            // Consulta para obtener datos de la tabla tblnotasdeentrega
            $sql = "SELECT * FROM tblnotasdeentrega WHERE UUIDVenta = ?";
            $stmt = $conectar->prepare($sql);
            $stmt->bindValue(1, $uuidventa);
            $stmt->execute();
            $nota = $stmt->fetch(PDO::FETCH_ASSOC);
    
            // Consulta para obtener datos de la tabla tblnotasdeentregadetalle
            $sql = "SELECT * FROM tblnotasdeentregadetalle WHERE UUIDVenta = ? ORDER BY NoItem";
            $stmt = $conectar->prepare($sql);
            $stmt->bindValue(1, $uuidventa);
            $stmt->execute();
            $detalle_notas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
            // Extraer solo la fecha y la hora
            $fecEmi = date('Y-m-d', strtotime($nota["FechaHoraGeneracion"]));
            $horEmi = date('H:i:s', strtotime($nota["FechaHoraGeneracion"]));
            $FechaHoraGeneracion = $nota['FechaHoraGeneracion'];
    
            $documentoRelacionado = null;

            $codigodegeneracion = $nota["CodigoDeGeneracion"];

            if (!empty($nota["DUI"])){
                $tipoDocumento = "13";
                $numDocumento = !empty($nota["DUI"]) ? str_replace("-", "", $nota["DUI"]) : NULL;
            } else {
                $tipoDocumento = null;
                $numDocumento = null;
            }
            // Formateando datos emison
        
            // buscando Codigo Departamento MH 
            $departamentoemisor = $this->buscarDEPARTAMENTOMH($sucursal["CodDepartamento"]); 
            
            // buscando Codigo Municipio MH 
            $municipioemisor = $this->buscarMUNICIPIOMH($sucursal["CodMunicipio"]); 
            
            // Formateando datos receptor
            $nrc = !empty($nota["NRC"]) ? str_replace("-", "", $nota["NRC"]) : null;
            $nombre = !empty($nota["NombreDeCliente"]) ? $nota["NombreDeCliente"] : null;
            $codActividad = !empty($nota["CodigoActividad"]) ? $nota["CodigoActividad"] : null;
            $descActividad = !empty($nota["Actividad"]) ? $nota["Actividad"] : null;
            $complemento = !empty($nota["Direccion"]) ? $nota["Direccion"] : null;
            
            if(!empty($complemento)){ 
                if(!empty($nota["IDDepartamento"])){ 
                    // buscando Codigo Departamento MH 
                    $departamento = $this->buscarDEPARTAMENTOMH($nota["IDDepartamento"]*1);
                } else {
                    $departamento = null;
                }
                if(!empty($nota["IDMunicipio"])){ 
                    // buscando Codigo Municipio MH 
                    $municipio = $this->buscarMUNICIPIOMH($nota["IDMunicipio"]*1);
                } else {
                    $municipio = null;
                }
            } else {
                $departamento = null;
                $municipio = null;
            }

            $telefono = !empty($nota["TelMovilWhatsApp"]) ? $nota["TelMovilWhatsApp"] : null;
            $correo = !empty($nota["CorreoElectronico"]) ? $nota["CorreoElectronico"] : null;

            $json_data = [
                "identificacion" => [
                    "ambiente" => $sucursal["AmbienteDTE"], 
                    "codigoGeneracion" => $nota["CodigoDeGeneracion"],
                    "fecEmi" => $fecEmi,
                    "horEmi" => $horEmi,
                    "numeroControl" => $nota["NumeroDeControl"],
                    "tipoDte" => $nota["CodDocumento"], 
                    "tipoModelo" => $nota["ModeloDeFacturacion"],
                    "tipoOperacion" => $nota["TipoDeTransmision"],
                    "tipoContingencia" => $nota["TipoContingencia"],
                    "motivoContin" => $nota["MotivoContingencia"],
                    "tipoMoneda" => $nota["TipoMoneda"],
                    "version" => $nota["VersionDTE"] 
                ],
                "emisor" => [
                    "codActividad" => $sucursal["CodActividad"],
                    "descActividad" => $sucursal["DescripcionActividad"],
                    "codEstable" => $sucursal["CodEstablecimiento"],
                    "codEstableMH" => $nota["CodEstablecimientoMH"],
                    "codPuntoVenta" => $nota["CodPuntoVenta"],
                    "codPuntoVentaMH" => $nota["CodPuntoVentaMH"],
                    "correo" => $sucursal["CorreoElectronico"],
                    "nit" => $sucursal["NIT"],
                    "nombre" => $sucursal["RazonSocial"],
                    "nrc" => $sucursal["NRC"],
                    "telefono" => $sucursal["Telefono"],
                    "direccion" => [
                        "complemento" => $sucursal["Direccion"],
                        "departamento" => $departamentoemisor,
                        "municipio" => $municipioemisor
                    ]
                ],
                "sujetoExcluido" => [
                    "tipoDocumento" => $tipoDocumento,
                    "numDocumento" => $numDocumento,
                    "nombre" => $nombre,
                    "codActividad" => $codActividad,
                    "descActividad" => $descActividad,
                    "telefono" => $telefono,
                    "correo" => $correo,
                    "direccion" => !empty($complemento) ? [
                        "complemento" => $complemento,
                        "departamento" => $departamento,
                        "municipio" => $municipio
                    ] : null
                ],
                "cuerpoDocumento" => array_map(function($detalle) {
                    return [
                        "numItem" => $detalle["NoItem"],
                        "codigo" => null,
                        "tipoItem" => $detalle["TipoDeItem"],
                        "uniMedida" => floatval($detalle["UnidadDeMedida"]),
                        "cantidad" => floatval($detalle["Cantidad"]),
                        "descripcion" => $detalle["Concepto"],
                        "precioUni" => floatval($detalle["PrecioVenta"]),
                        "montoDescu" => floatval($detalle["Descuento"]),
                        "compra" => floatval($detalle["VentaGravada"])
                    ];
                }, $detalle_notas),
                "resumen" => [
                    "condicionOperacion" => $nota["Condicion"],
                    "ivaRete1" => floatval($nota["IVARetencion"]),
                    "reteRenta" => floatval($nota["RetencionRenta"]),
                    "subTotal" => floatval($nota["SubTotal"]),
                    "totalDescu" => floatval($nota["TotalDescuentos"]),
                    "descu" => floatval($nota["descuSE"]),
                    "totalCompra" => floatval($nota["TotalGravadas"]),
                    "totalPagar" => floatval($nota["TotalPagar"]),
                    "totalLetras" => $nota["TotalLetras"],
                    "observaciones" => null,
                    "pagos" => null
                ],
                "apendice" => null
            ];

            $json = json_encode($json_data, JSON_PRETTY_PRINT);
               
            // Actualizar JSON generado en tblnotasdeentrega
            $sql = "UPDATE tblnotasdeentrega SET JSONGenerado = ? WHERE UUIDVenta = ?";
            $stmt = $conectar->prepare($sql);
            $stmt->bindValue(1, $json);
            $stmt->bindValue(2, $uuidventa);
            $stmt->execute();

            // Estableciendo valores para el firmado
            $nit = $NITNegocio;
            $passwordPri = $ClavePrivada;

            $dteJson = json_encode($json_data);

            // URL del firmador
            $url = $URLFirmador;

            // Convertir el JSON recibido a un array
            $dteJsonDecoded = json_decode($dteJson, true);
            if ($dteJsonDecoded === null) {
                die(json_encode(["error" => "El JSON del documento es inválido"]));
            }

            // Construir los datos para enviar
            $data = [
                "nit" => $nit,
                "activo" => true, // Se asume que siempre está activo
                "passwordPri" => $passwordPri,
                "dteJson" => $dteJsonDecoded
            ];

            // Convertir los datos a JSON
            $jsonData = json_encode($data);

            // Inicializar cURL
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Content-Type: application/json"
            ]);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);

            // Ejecutar la solicitud y obtener la respuesta
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $responseArray = json_decode($response, true);

            $JSONfirmado = json_encode($responseArray, JSON_PRETTY_PRINT);

            // Actualizar JSON generado en tblnotasdeentrega
            $sql = "UPDATE tblnotasdeentrega SET JSONFirmado = ? WHERE UUIDVenta = ?";
            $stmt = $conectar->prepare($sql);
            $stmt->bindValue(1, $JSONfirmado);
            $stmt->bindValue(2, $uuidventa);
            $stmt->execute();

            // Estableciendo valores para generar el token
            $user = $nit; 
            $pass = $ClaveAPI;

            $token = $this->getToken($URLAutenticacion,$user, $pass);

            // Actualizar JSON Token en tblnotasdeentrega
            $sql = "UPDATE tblnotasdeentrega SET Token = ? WHERE UUIDVenta = ?";
            $stmt = $conectar->prepare($sql);
            $stmt->bindValue(1, $token);
            $stmt->bindValue(2, $uuidventa);
            $stmt->execute();

            $respuesta = $this->transmitir_DTE($URLRecepcion,$AmbienteTransmision,1,$responseArray['body'],$codigodegeneracion, $token, $nota["CodDocumento"], $nota["VersionDTE"]);

            $respuestaMH = json_encode($respuesta, JSON_PRETTY_PRINT);
            
            // Actualizar JSON ResustaMH en tblnotasdeentrega
            $sql = "UPDATE tblnotasdeentrega SET RespuestaMH = ? WHERE UUIDVenta = ?";
            $stmt = $conectar->prepare($sql);
            $stmt->bindValue(1, $respuestaMH);
            $stmt->bindValue(2, $uuidventa);
            $stmt->execute();

            $estado = $respuesta['estado'];
            
            if ($estado == "PROCESADO") {
                $selloRecibido = $respuesta['selloRecibido'];
                // Actualizar JSON Sello Recibido en tblnotasdeentrega
                $sql = "UPDATE tblnotasdeentrega 
                        SET SelloDeRecepcion = ?, 
                        Estado = 3 
                        WHERE UUIDVenta = ?";
                $stmt = $conectar->prepare($sql);
                $stmt->bindValue(1, $selloRecibido);
                $stmt->bindValue(2, $uuidventa);
                $stmt->execute();

                // Creando JSON en carpeta
                $fechaCarpeta = date("Y-m-d", strtotime($FechaHoraGeneracion));
                $mesCarpeta = date("mY", strtotime($FechaHoraGeneracion));

                $rutaBase = '../../documentos/contabilidad/DTEjson';
                $rutaSucursal = $sucursalid;
                $rutaMes = $mesCarpeta;
                $rutaFinal = $rutaBase . '/' .$rutaMes . '/' . $rutaSucursal . '-' . $rutaMes . '/' . $fechaCarpeta;

                // Crear carpetas si no existen
                if (!file_exists($rutaFinal)) {
                    mkdir($rutaFinal, 0777, true);
                }

                // Decodificar los datos como array
                $jsonGeneradoDecoded = $json_data; // ← ya debe ser array
                $jsonFirmadoDecoded = json_decode($JSONfirmado, true);
                $firmadoBody = isset($jsonFirmadoDecoded['body']) ? $jsonFirmadoDecoded['body'] : null;

                // Armar la estructura final fusionando todo
                $jsonFinal = $jsonGeneradoDecoded;
                $jsonFinal['selloRecibido'] = $selloRecibido;
                $jsonFinal['firmaElectronica'] = $firmadoBody;

                // Convertir a JSON string
                $jsonFinalString = json_encode($jsonFinal, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

                // Actualizar en base de datos
                $sql = "UPDATE tblnotasdeentrega SET JSONGenerado = ? WHERE UUIDVenta = ?";
                $stmt = $conectar->prepare($sql);
                $stmt->bindValue(1, $jsonFinalString);
                $stmt->bindValue(2, $uuidventa);
                $stmt->execute();

                // Guardar en archivo
                $ruta = $rutaFinal . '/' . $codigodegeneracion . ".json";
                file_put_contents($ruta, $jsonFinalString);

                $data = "Procesado";
            } else {
                // Actualizar JSON Estado Rechazado en tblnotasdeentrega
                $sql = "UPDATE tblnotasdeentrega 
                        SET Estado = 4 
                        WHERE UUIDVenta = ?";
                $stmt = $conectar->prepare($sql);
                $stmt->bindValue(1, $uuidventa);
                $stmt->execute();
                $data = "Rechazado";
            }
            return $data;
            
        }

        public function generar_CONTINGENCIA($uuidevento, $uuidnegocio, $uuidsucursal) {
            
            $conectar = parent::conexion();
            parent::set_names();

            // Consulta para obtener datos de la tabla tblsucursales
            $sql = "SELECT s.*, n.* 
            FROM tblsucursales s 
            INNER JOIN tblnegocios n ON s.UUIDNegocio = n.UUIDNegocio 
            WHERE s.UUIDSucursal = ?";
            $stmt = $conectar->prepare($sql);
            $stmt->bindValue(1, $uuidsucursal);
            $stmt->execute();
            $sucursal = $stmt->fetch(PDO::FETCH_ASSOC);

            // Consulta para obtener datos de la tabla tblnegocios
            $sql = "SELECT * FROM tblnegocios WHERE UUIDNegocio = ?";
            $stmt = $conectar->prepare($sql);
            $stmt->bindValue(1, $uuidnegocio);
            $stmt->execute();
            $negocio = $stmt->fetch(PDO::FETCH_ASSOC);

            $NITNegocio = $sucursal["NIT"];
            $ClavePrivada = $negocio["ClavePrivada"];
            $ClavePublica = $negocio["ClavePublica"];
            $ClaveAPI = $negocio["ClaveAPI"];
            
            $URLFirmador = $negocio["URLFirmador"];
            $URLAutenticacion = $negocio["URLAutenticacion"];
            $URLContingencia = $negocio["URLContingencia"];
            
            // Consulta para obtener datos de la tabla tblnotasdeentrega
            $sql = "SELECT * FROM tbleventocontingencia WHERE UUIDEventoCON = ?";
            $stmt = $conectar->prepare($sql);
            $stmt->bindValue(1, $uuidevento);
            $stmt->execute();
            $evento = $stmt->fetch(PDO::FETCH_ASSOC);
    
            // Consulta para obtener datos de la tabla tblnotasdeentregadetalle
            $sql = "SELECT * FROM tbleventocontingenciadetalle WHERE UUIDEventoCON = ? ORDER BY NoItem";
            $stmt = $conectar->prepare($sql);
            $stmt->bindValue(1, $uuidevento);
            $stmt->execute();
            $evento_detalle = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($evento["TipoContingencia"] == 5){
                $motivocontingencia = $evento["MotivoContingencia"]; 
            } else{
                $motivocontingencia = null;
            }

            //$codigodegeneracion = $evento["CodigoDeGeneracion"];
            $CodigoGeneracion = $this->CodigoGeneracionUUIDv4();

            $json_data = [
                "identificacion" => [
                    "version" => $evento["Version"],
                    "ambiente" => $evento["Ambiente"],
                    "codigoGeneracion" => $CodigoGeneracion,
                    "fTransmision" => $evento["FechaDeTransmision"],
                    "hTransmision" => $evento["HoraTransmision"]
                ],
                "emisor" => [
                    "nit" => $sucursal["NIT"],
                    "nombre" => $sucursal["RazonSocial"],
                    "nombreResponsable" => $evento["NombreResponsable"],
                    "tipoDocResponsable" => $evento["TipoDocResponsable"],
                    "numeroDocResponsable" => $evento["NumeroDocResponsable"],
                    "tipoEstablecimiento" => $sucursal["TipoEstablecimiento"],
                    "codEstableMH" => $sucursal["CodEstablecimientoMH"],
                    "codPuntoVenta" => null,
                    "telefono" => $sucursal["Telefono"],
                    "correo" => $sucursal["CorreoElectronico"]
                ],
                "detalleDTE" => array_map(function($detalle) {
                    return [
                        "noItem" => $detalle["NoItem"],
                        "tipoDoc" => $detalle["TipoDTE"],
                        "codigoGeneracion" => $detalle["CodigoDeGeneracion"]
                    ];
                }, $evento_detalle),
                "motivo" => [
                    "fInicio" => $evento["FechaInicio"],
                    "fFin" => $evento["FechaFin"],
                    "hInicio" => $evento["HoraInicio"],
                    "hFin" => $evento["HoraFin"],
                    "tipoContingencia" => $evento["TipoContingencia"],
                    "motivoContingencia" => $motivocontingencia,
                ]
            ];

            $json = json_encode($json_data, JSON_PRETTY_PRINT);
           
            // Actualizar JSON generado en tblnotasdeentrega
            $sql = "UPDATE tbleventocontingencia SET JSONGenerado = ? WHERE UUIDEventoCON = ?";
            $stmt = $conectar->prepare($sql);
            $stmt->bindValue(1, $json);
            $stmt->bindValue(2, $uuidevento);
            $stmt->execute();

            // Estableciendo valores para el firmado
            $nit = $NITNegocio;
            $passwordPri = $ClavePrivada;

            $dteJson = json_encode($json_data);

            // URL del firmador
            $url = $URLFirmador;

            // Convertir el JSON recibido a un array
            $dteJsonDecoded = json_decode($dteJson, true);
            if ($dteJsonDecoded === null) {
                die(json_encode(["error" => "El JSON del documento es inválido"]));
            }

            // Construir los datos para enviar
            $data = [
                "nit" => $nit,
                "activo" => true, // Se asume que siempre está activo
                "passwordPri" => $passwordPri,
                "dteJson" => $dteJsonDecoded
            ];

            // Convertir los datos a JSON
            $jsonData = json_encode($data);

            // Inicializar cURL
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Content-Type: application/json"
            ]);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);

            // Ejecutar la solicitud y obtener la respuesta
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $responseArray = json_decode($response, true);

            $JSONfirmado = json_encode($responseArray, JSON_PRETTY_PRINT);

            // Actualizar JSON generado en tblnotasdeentrega
            $sql = "UPDATE tbleventocontingencia SET JSONFirmado = ? WHERE UUIDEventoCON = ?";
            $stmt = $conectar->prepare($sql);
            $stmt->bindValue(1, $JSONfirmado);
            $stmt->bindValue(2, $uuidevento);
            $stmt->execute();

            // Estableciendo valores para generar el token
            $user = $nit; 
            $pass = $ClaveAPI;

            $token = $this->getToken($URLAutenticacion,$user, $pass);

            // Actualizar JSON Token en tbleventocontingencia
            $sql = "UPDATE tbleventocontingencia SET Token = ? WHERE UUIDEventoCON = ?";
            $stmt = $conectar->prepare($sql);
            $stmt->bindValue(1, $token);
            $stmt->bindValue(2, $uuidevento);
            $stmt->execute();

            $respuesta = $this->transmitir_Contingencia_DTE($URLContingencia,$nit,$responseArray['body'],$token);

            $respuestaMH = json_encode($respuesta, JSON_PRETTY_PRINT);
            
            // Actualizar JSON ResustaMH en tbleventocontingencia
            $sql = "UPDATE tbleventocontingencia SET RespuestaMH = ? WHERE UUIDEventoCON = ?";
            $stmt = $conectar->prepare($sql);
            $stmt->bindValue(1, $respuestaMH);
            $stmt->bindValue(2, $uuidevento);
            $stmt->execute();

            $estado = $respuesta['estado'];
            
            if ($estado == "RECIBIDO") {
                $selloRecibido = $respuesta['selloRecibido'];
                // Actualizar JSON Sello Recibido en tbleventocontingencia
                $sql = "UPDATE tbleventocontingencia 
                        SET SelloDeRecepcion = ?, 
                        Estado = 2 
                        WHERE UUIDEventoCON = ?";
                $stmt = $conectar->prepare($sql);
                $stmt->bindValue(1, $selloRecibido);
                $stmt->bindValue(2, $uuidevento);
                $stmt->execute();

                $data = "Procesado";
            } else {
                // Actualizar JSON Estado Rechazado en tbleventocontingencia
                $sql = "UPDATE tbleventocontingencia 
                        SET Estado = 3 
                        WHERE UUIDEventoCON = ?";
                $stmt = $conectar->prepare($sql);
                $stmt->bindValue(1, $uuidevento);
                $stmt->execute();
                $data = "Rechazado";
            }
            return $data;
            
        }


        public function generar_ANULACION($uuidevento) {
            
            $conectar = parent::conexion();
            parent::set_names();

            // Consulta para obtener datos de la tabla tbleventoanulacion
            $sql = "SELECT * FROM tbleventoanulacion WHERE UUIDEventoANU = ?";
            $stmt = $conectar->prepare($sql);
            $stmt->bindValue(1, $uuidevento);
            $stmt->execute();
            $evento = $stmt->fetch(PDO::FETCH_ASSOC);
            $sucursalid = $evento["UUIDSucursal"];

            // Consulta para obtener datos de la tabla tblsucursales
            $sql = "SELECT s.*, n.* 
                    FROM tblsucursales s 
                    INNER JOIN tblnegocios n ON s.UUIDNegocio = n.UUIDNegocio 
                    WHERE s.UUIDSucursal = ?";
            $stmt = $conectar->prepare($sql);
            $stmt->bindValue(1, $sucursalid);
            $stmt->execute();
            $sucursal = $stmt->fetch(PDO::FETCH_ASSOC);

            $uuidnegocio = $sucursal["UUIDNegocio"];

            // Consulta para obtener datos de la tabla tblnegocios
            $sql = "SELECT * FROM tblnegocios WHERE UUIDNegocio = ?";
            $stmt = $conectar->prepare($sql);
            $stmt->bindValue(1, $uuidnegocio);
            $stmt->execute();
            $negocio = $stmt->fetch(PDO::FETCH_ASSOC);

            $NITNegocio = $sucursal["NIT"];
            $ClavePrivada = $negocio["ClavePrivada"];
            $ClavePublica = $negocio["ClavePublica"];
            $ClaveAPI = $negocio["ClaveAPI"];
            $AmbienteTransmision = $negocio["Ambiente"];

            $URLFirmador = $negocio["URLFirmador"];
            $URLAutenticacion = $negocio["URLAutenticacion"];
            $URLAnularDTE = $negocio["URLAnularDTE"];

            $MotivoAnulacion = !empty($evento["MotivoAnulacion"]) ? $evento["MotivoAnulacion"] : null; 
            $CodigoDeGeneracionR = !empty($evento["CodigoDeGeneracionR"]) ? $evento["CodigoDeGeneracionR"] : null; 

            $TipoDocReceptor = !empty($evento["TipoDocReceptor"]) ? $evento["TipoDocReceptor"] : null;
            $NumeroDocReceptor = !empty($evento["NumeroDocReceptor"]) ? $evento["NumeroDocReceptor"] : null;
            $NombreReceptor = !empty($evento["NombreReceptor"]) ? $evento["NombreReceptor"] : null;
            
            $json_data = [
                "identificacion" => [
                    "version"=> $evento["VersionJSON"],
                    "ambiente" => $evento["Ambiente"],
                    "codigoGeneracion" => $evento["CodigoDeGeneracion"],
                    "fecAnula" => $evento["FechaAnulacion"],
                    "horAnula" => $evento["HoraAnulacion"],
                ],
                "emisor" => [
                    "nit" => $sucursal["NIT"],
                    "nombre" => $sucursal["RazonSocial"],
                    "tipoEstablecimiento" => "02",
                    "nomEstablecimiento" => $sucursal["NombreNegocio"],
                    "telefono" => $sucursal["Telefono"],
                    "correo" => $sucursal["CorreoElectronico"],
                    "codEstable"=> "N1S009",
                    "codPuntoVenta"=> "P018"
                ],
                "documento" => [
                    "tipoDte"=>$evento["TipoDTE"],
                    "codigoGeneracion"=>$evento["CodigoDeGeneracionEmitido"],
                    "selloRecibido"=>$evento["SelloDeRecepcionEmitido"],
                    "numeroControl"=>$evento["NumeroDeControlEmitido"],
                    "fecEmi"=>$evento["FechaEmision"],
                    "montoIva"=>floatval($evento["MontoIVA"]),
                    "codigoGeneracionR" => $CodigoDeGeneracionR,
                    "tipoDocumento" => $TipoDocReceptor,
                    "numDocumento" => $NumeroDocReceptor,
                    "nombre" => $NombreReceptor,
                ],
                "motivo" => [
                    "tipoAnulacion"=>$evento["TipoDeAnulacion"],
                    "motivoAnulacion"=>$MotivoAnulacion,
                    "nombreResponsable"=>$evento["NombreResponsable"],
                    "tipDocResponsable"=>$evento["TipoDocResponsable"],
                    "numDocResponsable"=>$evento["NumeroDocResponsable"],
                    "nombreSolicita"=>$evento["NombreSolicitante"],
                    "tipDocSolicita"=>$evento["TipoDocSolicitante"],
                    "numDocSolicita"=>$evento["NumeroDocSolicitante"],
                ]
            ];

            $codigodegeneracionemitido = $evento["CodigoDeGeneracionEmitido"];

            $json = json_encode($json_data, JSON_PRETTY_PRINT);
               
            // Actualizar JSON generado en tbleventoanulacion
            $sql = "UPDATE tbleventoanulacion SET JSONGenerado = ? WHERE UUIDEventoANU = ?";
            $stmt = $conectar->prepare($sql);
            $stmt->bindValue(1, $json);
            $stmt->bindValue(2, $uuidevento);
            $stmt->execute();

            // Estableciendo valores para el firmado
            $nit = $NITNegocio;
            $passwordPri = $ClavePrivada;

            $dteJson = json_encode($json_data);

            // URL del firmador
            $url = $URLFirmador;

            // Convertir el JSON recibido a un array
            $dteJsonDecoded = json_decode($dteJson, true);
            if ($dteJsonDecoded === null) {
                die(json_encode(["error" => "El JSON del documento es inválido"]));
            }

            // Construir los datos para enviar
            $data = [
                "nit" => $nit,
                "activo" => true, // Se asume que siempre está activo
                "passwordPri" => $passwordPri,
                "dteJson" => $dteJsonDecoded
            ];

            // Convertir los datos a JSON
            $jsonData = json_encode($data);

            // Inicializar cURL
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Content-Type: application/json"
            ]);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);

            // Ejecutar la solicitud y obtener la respuesta
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $responseArray = json_decode($response, true);

            $JSONfirmado = json_encode($responseArray, JSON_PRETTY_PRINT);

            // Actualizar JSON generado en tbleventoanulacion
            $sql = "UPDATE tbleventoanulacion SET JSONFirmado = ? WHERE UUIDEventoANU = ?";
            $stmt = $conectar->prepare($sql);
            $stmt->bindValue(1, $JSONfirmado);
            $stmt->bindValue(2, $uuidevento);
            $stmt->execute();

            // Estableciendo valores para generar el token
            $user = $nit; 
            $pass = $ClaveAPI;

            $token = $this->getToken($URLAutenticacion,$user, $pass);

            $respuesta = $this->transmitir_Anulacion_DTE($URLAnularDTE,$AmbienteTransmision,1,$responseArray['body'],$token,$evento["VersionJSON"]);

            $respuestaMH = json_encode($respuesta, JSON_PRETTY_PRINT);
            
            // Actualizar JSON ResultadoMH en tbleventoanulacion
            $sql = "UPDATE tbleventoanulacion SET RespuestaMH = ? WHERE UUIDEventoANU = ?";
            $stmt = $conectar->prepare($sql);
            $stmt->bindValue(1, $respuestaMH);
            $stmt->bindValue(2, $uuidevento);
            $stmt->execute();

            $estado = $respuesta['estado'];
            
            if ($estado == "PROCESADO") {
                $selloRecibido = $respuesta['selloRecibido'];
                // Actualizar JSON Sello Recibido en tbleventoanulacion
                $sql = "UPDATE tbleventoanulacion 
                        SET SelloDeRecepcion = ?, 
                        Estado = 2 
                        WHERE UUIDEventoANU = ?";
                $stmt = $conectar->prepare($sql);
                $stmt->bindValue(1, $selloRecibido);
                $stmt->bindValue(2, $uuidevento);
                $stmt->execute();

                // Actualizar JSON Estado en tblnotasdeentgrega
                $sql = "UPDATE tblnotasdeentrega
                        SET Estado = 5,
                        Anulada = 1 
                        WHERE CodigoDeGeneracion = ?";
                $stmt = $conectar->prepare($sql);
                $stmt->bindValue(1, $codigodegeneracionemitido);
                $stmt->execute();
                $data = "Procesado";
            } else {
                // Actualizar JSON Estado Rechazado en tbleventoanulacion
                $sql = "UPDATE tbleventoanulacion 
                        SET Estado = 3 
                        WHERE UUIDEventoANU = ?";
                $stmt = $conectar->prepare($sql);
                $stmt->bindValue(1, $uuidevento);
                $stmt->execute();
                $data = "Rechazado";
            }
            return $data;
            
        }

        public function aplicar_membresia($uuidventa, $precio) {
            $conectar = parent::conexion();
            parent::set_names();

            try {
                // 1. Obtener todos los detalles de productos de esa venta
                $stmt = $conectar->prepare("SELECT * FROM tblnotasdeentregadetalle WHERE UUIDVenta = ?");
                $stmt->execute([$uuidventa]);
                $detalles = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // 2. Recorrer cada producto
                foreach ($detalles as $detalle) {
                    $Codigo = $detalle['CodigoPROD'];
                    $CantidadVenta = $detalle['Cantidad'];
                    $PrecioActual = $detalle['PrecioVenta'];
                    $PagaImpuesto = $detalle['PagaImpuesto'];
                    $Impuesto = $detalle['PorcentajeImpuesto'];

                    // 3. Obtener precios del producto desde el catálogo
                    $stmtProd = $conectar->prepare("SELECT TipoDeItem, Unidades, PrecioCosto, PrecioPublico, PrecioDetalle, PrecioDetalleDespacho, PrecioMayoreo, PrecioEspecial, UMinimaMayoreoDespacho FROM tblcatalogodeproductos WHERE CodigoPROD = ?");
                    $stmtProd->execute([$Codigo]);
                    $producto = $stmtProd->fetch(PDO::FETCH_ASSOC);

                    $tipoItem = $producto['TipoDeItem'];
                    if ($tipoItem == 1) {
                        $Unidades = $producto['Unidades'];
                        $Costo = $producto['PrecioCosto'];
                        $UMinimaMayoreoDespacho = $producto['UMinimaMayoreoDespacho'];
                        $tipoVenta = $detalle['TV'];
                        $tipoPrecio = $precio;
                        $UnidadesVenta = 0;
                        if ($tipoVenta == 1) {
                            $UnidadesVenta = $CantidadVenta;
                        } else {
                            $UnidadesVenta = ($CantidadVenta * $Unidades);
                        }

                        // 4. Calcular nuevo precio según tipo de precio
                        switch ($tipoPrecio) {
                            case 0: 
                                $nuevoPrecio = $producto['PrecioCosto']; 
                                break;
                            case 2:
                                if ($UnidadesVenta < $UMinimaMayoreoDespacho) 
                                {
                                    $nuevoPrecio = $producto['PrecioDetalleDespacho'];
                                } else {
                                    $nuevoPrecio = $producto['PrecioMayoreo'];
                                }
                                break;
                            case 3: 
                                $nuevoPrecio = $producto['PrecioMayoreo']; 
                                break;
                            case 4: 
                                $nuevoPrecio = $producto['PrecioEspecial']; 
                                break;
                            default: 
                                $nuevoPrecio = $producto['PrecioPublico'];
                        }

                        $PrecioPublico = $producto['PrecioPublico'];

                        // 5. Establecemos precio venta según tipo de venta
                        if ($tipoVenta == 1) {
                            $precioVenta = $nuevoPrecio / max(1, $Unidades);
                            $precioCosto = $Costo / max(1, $Unidades);
                            $precioNormal = $PrecioPublico / max(1, $Unidades);
                        } else {
                            $precioVenta = $nuevoPrecio;
                            $precioCosto = $Costo;
                            $precioNormal = $PrecioPublico;
                        }

                        // 6. Cálculos de impuestos
                        $precioVentaSinImpuesto = ($precioVenta / (1 + ($Impuesto / 100)));
                        $TotalImporte = ($precioVenta * $CantidadVenta);
                        $TotalImporteSinImpuesto = ($TotalImporte / (1 + ($Impuesto / 100)));
                        $IVAItem = ($TotalImporteSinImpuesto * ($Impuesto / 100));
                        $TotalCosto = ($precioCosto * $CantidadVenta);

                        if ($PagaImpuesto == 1) {
                            $VentaGravada = $TotalImporte;
                            $VentaGravadaSinImpuesto = $TotalImporteSinImpuesto;
                            $VentaExenta = 0.00;
                        } else {
                            $VentaGravada = 0.00;
                            $VentaGravadaSinImpuesto = 0.00;
                            $VentaExenta = $TotalImporte;
                        }

                        $TotalCosto = $precioCosto * $CantidadVenta;
                       
                        if ($PrecioActual > $precioVenta) {
                            // 7. Actualizar el detalle de la venta
                            $stmtUpdate = $conectar->prepare("
                                UPDATE tblnotasdeentregadetalle 
                                SET 
                                    PrecioNormal = ?, 
                                    PrecioVenta = ?, 
                                    PrecioVentaSinImpuesto = ?,
                                    VentaGravada = ?,
                                    VentaGravadaSinImpuesto = ?,
                                    VentaExenta = ?,
                                    TotalImporte = ?,
                                    IVAItem = ?,
                                    PrecioCosto = ?,
                                    TotalCosto = ?
                                WHERE UUIDVenta = ? AND CodigoPROD = ?
                            ");

                            $stmtUpdate->bindValue(1, $precioNormal);
                            $stmtUpdate->bindValue(2, $precioVenta);
                            $stmtUpdate->bindValue(3, $precioVentaSinImpuesto);
                            $stmtUpdate->bindValue(4, $VentaGravada);
                            $stmtUpdate->bindValue(5, $VentaGravadaSinImpuesto);
                            $stmtUpdate->bindValue(6, $VentaExenta);
                            $stmtUpdate->bindValue(7, $TotalImporte);
                            $stmtUpdate->bindValue(8, $IVAItem);
                            $stmtUpdate->bindValue(9, $precioCosto);
                            $stmtUpdate->bindValue(10, $TotalCosto);
                            $stmtUpdate->bindValue(11, $uuidventa);
                            $stmtUpdate->bindValue(12, $Codigo);
                            $stmtUpdate->execute();
                        }
                    }
                }

                return "Ok";

            } catch (Exception $e) {
                return "Error: " . $e->getMessage();
            }
        }

        
        public function mostrar_ultima_venta($uuidcaja){
            $conectar= parent::conexion();
            parent::set_names();
            $sql = "SELECT *, NoCorrelativo 
                    FROM tblnotasdeentrega 
                    WHERE UUIDCaja = ? AND DATE(FechaEntrega) = CURDATE() 
                    ORDER BY NoCorrelativo DESC 
                    LIMIT 1";
            $sql=$conectar->prepare($sql);
            $sql->bindValue(1, $uuidcaja);
            $sql->execute();
            return $resultado=$sql->fetchAll();

        }

        public function consultar_codigo_barra($txtcodigobarra){
            $conectar= parent::conexion();
            parent::set_names();
            $sql="SELECT * FROM tblcodigosdebarras
                    WHERE
                    CodigoDeBarras LIKE ?";
            $sql=$conectar->prepare($sql);
            $sql->bindValue(1, $txtcodigobarra);
            $sql->execute();
            return $resultado=$sql->fetchAll();

        }

        public function esta_ofertado($txtUUIDSucursal){
            $conectar= parent::conexion();
            parent::set_names();
            $sql="SELECT * FROM tblcontroldeofertas
                    WHERE
                    Estado = 1 AND EstaOfertado > 0 AND Para = '0000' OR Para = ?";
            $sql=$conectar->prepare($sql);
            $sql->bindValue(1, $txtUUIDSucursal);
            $sql->execute();
            return $resultado=$sql->fetchAll();

        }

        public function listar_detalle_venta($uuidventa){
            $conectar= parent::conexion();
            parent::set_names();
            $sql="SELECT UUIDDetalleVenta, FechaRegistro, TV, Cantidad, PrecioVenta, Descuento, Concepto, TotalImporte  FROM tblnotasdeentregadetalle
                    WHERE
                    tblnotasdeentregadetalle.UUIDVenta = ?";
            $sql=$conectar->prepare($sql);
            $sql->bindValue(1, $uuidventa);
            $sql->execute();
            return $resultado=$sql->fetchAll();

        }

        public function consultar_detalle_venta_id($uuiddetalleventa){
            $conectar= parent::conexion();
            parent::set_names();
            $sql="SELECT * FROM tblnotasdeentregadetalle
                    WHERE
                    tblnotasdeentregadetalle.UUIDDetalleVenta = ?";
            $sql=$conectar->prepare($sql);
            $sql->bindValue(1, $uuiddetalleventa);
            $sql->execute();
            return $resultado=$sql->fetchAll();

        }

        public function calcular_total_venta($uuidventa){
            $conectar= parent::conexion();
            parent::set_names();
            $sql="SELECT SUM(TotalImporte) as SumaTotalImporte
                    FROM tblnotasdeentregadetalle
                    WHERE tblnotasdeentregadetalle.UUIDVenta = ?";
            $sql=$conectar->prepare($sql);
            $sql->bindValue(1, $uuidventa);
            $sql->execute();
            return $resultado=$sql->fetchAll();

        }

        public function guardar_total_venta($uuidventa,$txtSubTotal,$txtIVAPercibido,$txtIVARetenido,$txtRetencionRenta,$txttotalimporte){
            $conectar= parent::conexion();
            parent::set_names();
            $sql="UPDATE tblnotasdeentrega 
                  SET
                  SubTotalVentas = ?,
                  IVAPercibido = ?,
                  IVARetencion = ?,
                  RetencionRenta = ?,
                  TotalImporte = ?
                WHERE
                  UUIDVenta = ?";
            $sql=$conectar->prepare($sql);
            $sql->bindValue(1, $txtSubTotal);
            $sql->bindValue(2, $txtIVAPercibido);
            $sql->bindValue(3, $txtIVARetenido);
            $sql->bindValue(4, $txtRetencionRenta);
            $sql->bindValue(5, $txttotalimporte);
            $sql->bindValue(6, $uuidventa);
            if($sql->execute())
			{
                $count = $sql->rowCount();
                if($count == 0){
                    $data = "SinRegistro";
                } else {
                    $data = "Ok";
                }
			} else {
				$data = "Error";
			}
            return $data;
        }

        public function actualizar_pagos($UUIDVenta,$txtUsuarioActual,$txtPagoTarjeta,$txtPagoCheque,$txtPagoElectronico,$txtPagoEfectivo,$txtPagoVale,$txtPagoGiftCard,$txtPagoRecibido){
            $conectar= parent::conexion();
            parent::set_names();
            $sql="UPDATE tblnotasdeentrega 
                  SET
                  PagoTarjeta = ?,
                  PagoCheque = ?,
                  PagoElectronico = ?,
                  PagoEfectivo = ?,
                  PagoVale = ?,
                  PagoGiftCard = ?,
                  PagoRecibido = ?,
                  UsuarioUpdate = ?,
                  FechaUpdate = now()
                WHERE
                  UUIDVenta = ?";
            $sql=$conectar->prepare($sql);
            $sql->bindValue(1, $txtPagoTarjeta);
            $sql->bindValue(2, $txtPagoCheque);
            $sql->bindValue(3, $txtPagoElectronico);
            $sql->bindValue(4, $txtPagoEfectivo);
            $sql->bindValue(5, $txtPagoVale);
            $sql->bindValue(6, $txtPagoGiftCard);
            $sql->bindValue(7, $txtPagoRecibido);
            $sql->bindValue(8, $txtUsuarioActual);
            $sql->bindValue(9, $UUIDVenta);
            if($sql->execute())
			{
               $data = "Ok";
			} else {
				$data = "Error";
			}
            return $data;
        }

        public function agregar_servicio_detalle($txtUUIDDetalleVenta, $txtDescripcion, $txtTipoItem, $txtTipoVenta, $txtPrecioVenta, 
        $txtPrecioVentaSinImpuesto, $txtPrecioNormal, $txtCantidad, $txtUnidadesVendidas, $txtDescuento, $txtTotalImporte, $txtExentas,
        $txtGravadas, $txtGravadasSinImpuesto, $txtIVAItem, $txtPrecioCosto, $txtTotalCosto, $txtUsuarioActual){
        $conectar= parent::conexion();
            parent::set_names();
            $txtCodigoProd = "000000";
            $txtCodigoBarra = "";
            $txtUnidadDeMedida = 99;
            $txtNoSujetas = 0;
            $txtExcento = 0;
            $txtPagaImpuesto = 1;
            $txtCodigoTributo = null;
            $txtTributo = 20;
            $txtPorcentajeImpuesto = 13;
            $sql="INSERT INTO tblnotasdeentregadetalle 
                        (UUIDDetalleVenta,UUIDVenta,UsuarioRegistro,TipoDeItem,CodigoPROD,CodigoBarra,Concepto,TV,UnidadDeMedida,
                        Cantidad,PrecioVenta,PrecioVentaSinImpuesto,PrecioNormal,Descuento,VentaNoSujeta,VentaExenta,
                        VentaGravada,VentaGravadaSinImpuesto,IVAItem,TotalImporte,PrecioCosto,TotalCosto,PagaImpuesto,
                        CodigoTributo,Tributo,PorcentajeImpuesto) 
                        VALUES 
                        (UUID(),?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?);";
                $sql=$conectar->prepare($sql);
                $sql->bindValue(1, $txtUUIDDetalleVenta);
                $sql->bindValue(2, $txtUsuarioActual);
                $sql->bindValue(3, $txtTipoItem);
                $sql->bindValue(4, $txtCodigoProd);
                $sql->bindValue(5, $txtCodigoBarra);
                $sql->bindValue(6, $txtDescripcion);
                $sql->bindValue(7, $txtTipoVenta);
                $sql->bindValue(8, $txtUnidadDeMedida);
                $sql->bindValue(9, $txtCantidad);
                $sql->bindValue(10, $txtPrecioVenta);
                $sql->bindValue(11, $txtPrecioVentaSinImpuesto);
                $sql->bindValue(12, $txtPrecioNormal);
                $sql->bindValue(13, $txtDescuento);
                $sql->bindValue(14, $txtNoSujetas);
                $sql->bindValue(15, $txtExcento);
                $sql->bindValue(16, $txtGravadas);
                $sql->bindValue(17, $txtGravadasSinImpuesto);
                $sql->bindValue(18, $txtIVAItem);
                $sql->bindValue(19, $txtTotalImporte);
                $sql->bindValue(20, $txtPrecioCosto);
                $sql->bindValue(21, $txtTotalCosto);
                $sql->bindValue(22, $txtPagaImpuesto);
                $sql->bindValue(23, $txtCodigoTributo);
                $sql->bindValue(24, $txtTributo);
                $sql->bindValue(25, $txtPorcentajeImpuesto);
          
                if($sql->execute())
                {
                    $count = $sql->rowCount();
                    if($count == 0){
                        $data = "Error";
                    } else {
                        $data = "Procesado";
                    }
                    return $data;
                } else {
                    $data = "Error";
                    return $data;
                }
        }

        public function update_detalle_venta($txtUUIDDetalleVenta, $txtDescripcion, $txtTipoItem, $txtTipoVenta, $txtPrecioVenta, $txtPrecioVentaSinImpuesto, $txtPrecioNormal, $txtCantidad, $txtUnidadesVendidas, $txtDescuento, $txtTotalImporte, $txtExentas, $txtGravadas, $txtGravadasSinImpuesto, $txtIVAItem, $txtPrecioCosto, $txtTotalCosto, $txtPagaImpuesto, $txtPorcentajeImpuesto, $txtUsuarioActual){
        $conectar= parent::conexion();
            parent::set_names();
            $sql="UPDATE tblnotasdeentregadetalle 
                  SET
                  Concepto = ?,
                  TV = ?,
                  PrecioVenta = ?,
                  PrecioVentaSinImpuesto = ?,
                  PrecioNormal = ?,
                  Cantidad = ?,
                  UnidadesVendidas = ?,
                  Descuento = ?,
                  TotalImporte = ?,
                  VentaExenta = ?,
                  VentaGravada = ?,
                  VentaGravadaSinImpuesto = ?,
                  IVAItem = ?,
                  PrecioCosto = ?,
                  TotalCosto = ?,
                  TipoDeItem = ?,
                  PagaImpuesto = ?,
                  PorcentajeImpuesto = ?,
                  UsuarioUpdate = ?,
                  FechaUpdate = now()
                WHERE
                  UUIDDetalleVenta = ?";
            $sql=$conectar->prepare($sql);
            $sql->bindValue(1, $txtDescripcion);
            $sql->bindValue(2, $txtTipoVenta);
            $sql->bindValue(3, $txtPrecioVenta);
            $sql->bindValue(4, $txtPrecioVentaSinImpuesto);
            $sql->bindValue(5, $txtPrecioNormal);
            $sql->bindValue(6, $txtCantidad);
            $sql->bindValue(7, $txtUnidadesVendidas);
            $sql->bindValue(8, $txtDescuento);
            $sql->bindValue(9, $txtTotalImporte);
            $sql->bindValue(10, $txtExentas);
            $sql->bindValue(11, $txtGravadas);
            $sql->bindValue(12, $txtGravadasSinImpuesto);
            $sql->bindValue(13, $txtIVAItem);
            $sql->bindValue(14, $txtPrecioCosto);
            $sql->bindValue(15, $txtTotalCosto);
            $sql->bindValue(16, $txtTipoItem);
            $sql->bindValue(17, $txtPagaImpuesto);
            $sql->bindValue(18, $txtPorcentajeImpuesto);
            $sql->bindValue(19, $txtUsuarioActual);
            $sql->bindValue(20, $txtUUIDDetalleVenta);
            
            if($sql->execute())
			{
			    $count = $sql->rowCount();
				if($count == 0){
					$data = "Error";
				} else {
					$data = "Edicion";
				}
			} else {
				$data = "Error";
			}
            return $data;
        }

        public function procesar_venta(
            $txtUUIDVenta,
            $txtUUIDSucursal,
            $txtUUIDTerminal,
            $txtUsuarioActual,
            $txtTipoDespacho,  
            $cmbDocumento,
            $txtCondicion,
            $txtTotalImporteCobro,
            $txtEfectivoRecibido, 
            $txtCambio, 
            $cmbPlazo,
            $txtVencimiento,
            $txtPagoTarjeta,
            $txtPagoCheque,
            $txtPagoElectronico,
            $txtPagoEfectivo,
            $txtPagoVale,
            $txtPagoGiftCard,
            $txtPagoRecibido,
            $txtPagoContado,
            $txtUUIDOperador,
            $txtOperador,
            $txtUUIDVendedor,
            $txtCodigoCLI,
            $txtNombreClienteCobro,
            $txtNombreComercialCobro,
            $txtCodigoActividadCobro,
            $txtActividadCobro,
            $txtDireccionCobro,
            $txtCodDepartamentoCobro,
            $txtDepartamentoCobro,
            $txtCodMunicipioCobro,
            $txtMunicipioCobro,
            $txtCodDistritoCobro,
            $txtDistritoCobro,
            $txtDUICobro,
            $txtNRCCobro,
            $txtNITCobro,
            $txtCorreoElectronico, 
            $txtTelWhatsApp
        ){
            $conectar= parent::conexion();
            $conectar->beginTransaction(); // 🟢 Inicia transacción
            parent::set_names();

            //Contolando errores
            try
            {
                //Procesando codigo 
                if($cmbDocumento =="01"){
                    $sql = "SELECT
                                SUM(Descuento) AS TotalDescuento,
                                SUM(VentaNoSujeta) AS TotalVentaNoSujeta,
                                SUM(VentaExenta) AS TotalVentaExenta,
                                SUM(VentaGravada) AS TotalVentaGravada,
                                SUM(IVAItem) AS TotalIVA,
                                SUM(TotalOperacion) AS TotalOperacion,
                                SUM(TotalImporte) AS TotalImporte,
                                SUM(TotalCosto) AS TotalCosto
                            FROM tblnotasdeentregadetalle
                            WHERE UUIDVenta = ?
                            GROUP BY UUIDVenta";
                } else if($cmbDocumento == "03") {
                    $sql = "SELECT
                        SUM(Descuento) AS TotalDescuento,
                        SUM(VentaNoSujeta) AS TotalVentaNoSujeta,
                        SUM(VentaExenta) AS TotalVentaExenta,
                        SUM(VentaGravadaSinImpuesto) AS TotalVentaGravada,
                        SUM(IVAItem) AS TotalIVA,
                        SUM(TotalOperacion) AS TotalOperacion,
                        SUM(TotalImporte) AS TotalImporte,
                        SUM(TotalCosto) AS TotalCosto
                    FROM tblnotasdeentregadetalle
                    WHERE UUIDVenta = ?
                    GROUP BY UUIDVenta";
                }

                $sql = $conectar->prepare($sql);
                $sql->bindValue(1, $txtUUIDVenta);
                $sql->execute();
                $resumen = $sql->fetch();

                $totalDescu = $resumen['TotalDescuento']; 
                $totalNoSuj= $resumen['TotalVentaNoSujeta']; 
                $totalExenta= $resumen['TotalVentaExenta']; 
                $totalGravada= $resumen['TotalVentaGravada']; 
                $totalIva= $resumen['TotalIVA']; 
                $subTotal= $resumen['TotalVentaNoSujeta'] + $resumen['TotalVentaExenta']+ $resumen['TotalVentaGravada']; 
                $subTotalVentas= $resumen['TotalVentaNoSujeta'] + $resumen['TotalVentaExenta']+ $resumen['TotalVentaGravada'];
                $montoTotalOperacion= $resumen['TotalImporte']; 
                $totalPagar= $resumen['TotalImporte']; 
                $tributos = null;
                if($cmbDocumento == "01"){
                    $tributos = null;
                } else if($cmbDocumento == "03") {
                    $trib =[
                        "codigo" => "20",
                        "descripcion"=> "Impuesto al Valor Agregado 13%",
                        "valor"=> round(floatval($totalIva), 2)
                    ];
                    $tributos = json_encode($trib, JSON_PRETTY_PRINT);
                }
                // Consulta para obtener datos de la tabla tblnegocios
                $sql = "SELECT VersionDTE FROM tbltipodedocumentos WHERE Codigo = ?";
                $stmt = $conectar->prepare($sql);
                $stmt->bindValue(1, $cmbDocumento);
                $stmt->execute();
                $documentos = $stmt->fetch(PDO::FETCH_ASSOC);
                $txtVersionDTE = $documentos['VersionDTE']; 

                // Consulta para obtener datos de la tabla tblterminales
                $sql = "SELECT CodPuntoVenta, CodPuntoVentaMH FROM tblterminales WHERE UUIDTerminal = ?";
                $stmt = $conectar->prepare($sql);
                $stmt->bindValue(1, $txtUUIDTerminal);
                $stmt->execute();
                $terminal = $stmt->fetch(PDO::FETCH_ASSOC);
                $txtCodPuntoVenta = $terminal['CodPuntoVenta']; 
                $txtCodPuntoVentaMH = $terminal['CodPuntoVentaMH']; 
            
                // Generando Ultimo Numero Correlativo Anual
                // Paso 1: Obtener el último número desde tblsucursales
                
                if($cmbDocumento == "01"){
                    $sql1 = "SELECT CodEstablecimiento, CodEstablecimientoMH, UltimoNumFactura FROM tblsucursales WHERE UUIDSucursal = ? FOR UPDATE";
                } else if($cmbDocumento == "03"){
                    $sql1 = "SELECT CodEstablecimiento, CodEstablecimientoMH, UltimoNumCreditoFiscal FROM tblsucursales WHERE UUIDSucursal = ? FOR UPDATE";
                } 
                
                $stmt1 = $conectar->prepare($sql1);
                $stmt1->bindValue(1, $txtUUIDSucursal); // Este es el UUIDSucursal que ya usabas
                $stmt1->execute();
                $row1 = $stmt1->fetch(PDO::FETCH_ASSOC);

                $txtCodEstablecimiento = $row1['CodEstablecimiento']; 
                $txtCodEstablecimientoMH = $row1['CodEstablecimientoMH']; 

                // Paso 2: Calcular el nuevo número
                if($cmbDocumento == "01"){
                    $ultimoNumero = $row1['UltimoNumFactura'];
                    if($ultimoNumero == 999999999999999){
                        $ultimoNoAnualCorrelativo = 1;
                    } else {
                        $ultimoNoAnualCorrelativo = $row1['UltimoNumFactura'] + 1;
                    }
                } else if($cmbDocumento == "03"){
                    $ultimoNumero = $row1['UltimoNumCreditoFiscal'];
                    if($ultimoNumero == 999999999999999){
                        $ultimoNoAnualCorrelativo = 1;
                    } else {
                        $ultimoNoAnualCorrelativo = $row1['UltimoNumCreditoFiscal'] + 1;
                    }
                } 

                $correlativoanual = str_pad($ultimoNoAnualCorrelativo, 15, "0", STR_PAD_LEFT);
                $numerocontrol = "DTE-" . $cmbDocumento . "-" . $txtCodEstablecimiento . $txtCodPuntoVenta . "-" . $correlativoanual; 

                //Generando Ultimo Numero Correlativo Diario
                $sql2 = "SELECT NoCorrelativo FROM tblnotasdeentrega 
                        WHERE UUIDSucursal = ? AND CodDocumento = ? AND DATE(FechaRegistro) = CURDATE()  
                        ORDER BY NoCorrelativo DESC
                        LIMIT 1";
                $sql2 = $conectar->prepare($sql2);
                $sql2->bindValue(1, $txtUUIDSucursal);
                $sql2->bindValue(2, $cmbDocumento);
                $sql2->execute();
                $resultado2 = $sql2->fetch(PDO::FETCH_ASSOC);
                $ultimoNoCorrelativo = 0;
                
                // Verificar si se encontró algún resultado
                if ($resultado2) {
                    $ultimoNoCorrelativo = $resultado2['NoCorrelativo'] + 1;
                } else {
                    $ultimoNoCorrelativo = 1;
                }

                if ($txtCondicion == 1) {
                    $txtPagoRecibido = $txtEfectivoRecibido;
                } else if ($txtCondicion == 2 ) {
                    $txtPagoRecibido = 0;
                } else if ($txtCondicion == 3 ) {
                    $txtPagoRecibido = $txtPagoRecibido;
                }
                $FechaVencimiento = $txtVencimiento ? $txtVencimiento : null;

                $monto = str_replace(",", "", $txtTotalImporteCobro); // quita las comas
                $monto = floatval($monto); // lo convierte a número real

                $txtTotalEnLetras = $this->convertirNumeroALetras($monto); 

                $CodigoGeneracion = $this->CodigoGeneracionUUIDv4();

                // Actualizar venta
                $sql="UPDATE tblnotasdeentrega 
                    SET
                    FechaFacturacion = now(),
                    FechaEnvio = now(),
                    FechaEntrega = now(),
                    FechaHoraGeneracion = now(),
                    NoAnualCorrelativo = ?,
                    NoCorrelativo = ?,
                    NumeroDeControl = ?,
                    CodigoDeGeneracion = ?,
                    TipoDespacho = ?,
                    CodDocumento = ?,
                    Condicion = ?,
                    TotalImporte = ?,
                    PagoRecibido = ?,
                    PagoContado = ?,
                    Cambio = ?,
                    Plazo = ?,
                    FechaVencimiento = ?,
                    PagoTarjeta = ?,
                    PagoCheque = ?,
                    PagoElectronico = ?,
                    PagoEfectivo = ?,
                    PagoVale = ?,
                    PagoGiftCard = ?,
                    UUIDOperador = ?,
                    Operador = ?,
                    UUIDVendedor = ?,
                    CodigoCLI = ?,
                    NombreDeCliente = ?,
                    NombreComercial = ?,
                    CodigoActividad = ?,
                    Actividad = ?,
                    Direccion = ?,
                    IDDepartamento = ?,
                    Departamento = ?,
                    IDMunicipio = ?,
                    Municipio = ?,
                    IDDistrito = ?,
                    Distrito = ?,
                    DUI = ?,
                    NRC = ?,
                    NIT = ?,
                    CorreoElectronico = ?,
                    TelMovilWhatsApp = ?,
                    TotalDescuentos = ?,
                    TotalNoSujetas = ?,
                    TotalExentas = ?,
                    TotalGravadas = ?,
                    TotalIVA = ?,
                    SubTotal = ?,
                    SubTotalVentas = ?,
                    TotalOperacion = ?,
                    TotalPagar = ?,
                    TotalLetras = ?,
                    VersionDTE = ?,
                    CodEstablecimiento = ?,
                    CodEstablecimientoMH = ?,
                    CodPuntoVenta = ?,
                    CodPuntoVentaMH = ?,
                    Tributos = ?,
                    UsuarioUpdate = ?,
                    Estado = 2,
                    FechaUpdate = now()
                    WHERE
                    UUIDVenta = ?";
                $sql=$conectar->prepare($sql);
                $sql->bindValue(1, $ultimoNoAnualCorrelativo);
                $sql->bindValue(2, $ultimoNoCorrelativo);
                $sql->bindValue(3, $numerocontrol);
                $sql->bindValue(4, $CodigoGeneracion);
                $sql->bindValue(5, $txtTipoDespacho);
                $sql->bindValue(6, $cmbDocumento);
                $sql->bindValue(7, $txtCondicion);
                $sql->bindValue(8, $txtTotalImporteCobro);
                $sql->bindValue(9, $txtPagoRecibido);
                $sql->bindValue(10, $txtPagoContado);
                $sql->bindValue(11, $txtCambio);
                $sql->bindValue(12, $cmbPlazo);
                $sql->bindValue(13, $FechaVencimiento);
                $sql->bindValue(14, $txtPagoTarjeta);
                $sql->bindValue(15, $txtPagoCheque);
                $sql->bindValue(16, $txtPagoElectronico);
                $sql->bindValue(17, $txtPagoEfectivo);
                $sql->bindValue(18, $txtPagoVale);
                $sql->bindValue(19, $txtPagoGiftCard);
                $sql->bindValue(20, $txtUUIDOperador);
                $sql->bindValue(21, $txtOperador);
                $sql->bindValue(22, $txtUUIDVendedor);
                $sql->bindValue(23, $txtCodigoCLI);
                $sql->bindValue(24, $txtNombreClienteCobro);
                $sql->bindValue(25, $txtNombreComercialCobro);
                $sql->bindValue(26, $txtCodigoActividadCobro);
                $sql->bindValue(27, $txtActividadCobro);
                $sql->bindValue(28, $txtDireccionCobro);
                $sql->bindValue(29, $txtCodDepartamentoCobro);
                $sql->bindValue(30, $txtDepartamentoCobro);
                $sql->bindValue(31, $txtCodMunicipioCobro);
                $sql->bindValue(32, $txtMunicipioCobro);
                $sql->bindValue(33, $txtCodDistritoCobro);
                $sql->bindValue(34, $txtDistritoCobro);
                $sql->bindValue(35, $txtDUICobro);
                $sql->bindValue(36, $txtNRCCobro);
                $sql->bindValue(37, $txtNITCobro);
                $sql->bindValue(38, $txtCorreoElectronico);
                $sql->bindValue(39, $txtTelWhatsApp);
                $sql->bindValue(40, $totalDescu);
                $sql->bindValue(41, $totalNoSuj);
                $sql->bindValue(42, $totalExenta);
                $sql->bindValue(43, $totalGravada);
                $sql->bindValue(44, $totalIva);
                $sql->bindValue(45, $subTotal);
                $sql->bindValue(46, $subTotalVentas);
                $sql->bindValue(47, $montoTotalOperacion);
                $sql->bindValue(48, $totalPagar);
                $sql->bindValue(49, $txtTotalEnLetras);
                $sql->bindValue(50, $txtVersionDTE);
                $sql->bindValue(51, $txtCodEstablecimiento);
                $sql->bindValue(52, $txtCodEstablecimientoMH);
                $sql->bindValue(53, $txtCodPuntoVenta);
                $sql->bindValue(54, $txtCodPuntoVentaMH);
                $sql->bindValue(55, $tributos);
                $sql->bindValue(56, $txtUsuarioActual);
                $sql->bindValue(57, $txtUUIDVenta);
                if($sql->execute())
                {
                    // Paso 3: Actualizar el nuevo número en tblsucursales
                    if($cmbDocumento == "01"){
                        $sql2 = "UPDATE tblsucursales SET UltimoNumFactura = ? WHERE UUIDSucursal = ?";
                    } else if($cmbDocumento == "03"){
                        $sql2 = "UPDATE tblsucursales SET UltimoNumCreditoFiscal = ? WHERE UUIDSucursal = ?";
                    } 
                    $stmt2 = $conectar->prepare($sql2);
                    $stmt2->bindValue(1, $ultimoNoAnualCorrelativo);
                    $stmt2->bindValue(2, $txtUUIDSucursal);
                    $stmt2->execute();

                    $conectar->commit(); // ✅ Confirma si todo salió bien
                    $data = "Procesado";
                } else {
                    $data = "Error";
                }

            }  catch (Exception $e) {
                // Aquí se muestra el error si algo falla
                //echo "Error: " . $e->getMessage();
                
                $conectar->rollBack(); // ❌ Reversión si algo falló
                //Retornando por error 
                $data = "Error";
            }
            return $data;
    
        }

        private function CodigoGeneracionUUIDv4() {
            $data = random_bytes(16);
            $data[6] = chr(ord($data[6]) & 0x0f | 0x40); 
            $data[8] = chr(ord($data[8]) & 0x3f | 0x80); 
            return strtoupper(vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4)));
        }

        public function actualizar_inventario($txtUUIDVenta,$txtUUIDSucursal,$txtUsuarioActual){
            $conectar= parent::conexion();
            parent::set_names();

            // 1. Obtener productos y cantidades vendidos en esta nota de entrega
            $sql = "SELECT UUIDDetalleVenta, CodigoPROD, UnidadesVendidas FROM tblnotasdeentregadetalle WHERE UUIDVenta = ?";
            $stmt = $conectar->prepare($sql);
            $stmt->execute([$txtUUIDVenta]);
            $detalle = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Ahora recorrer y actualizar NoItem uno por uno
            $NoItem = 1;
            foreach ($detalle as $row) {
                $codigo = $row['UUIDDetalleVenta'];

                $sqlUpdate = "UPDATE tblnotasdeentregadetalle 
                            SET NoItem = ? 
                            WHERE UUIDVenta = ? AND UUIDDetalleVenta = ?";
                $stmtUpdate = $conectar->prepare($sqlUpdate);
                $stmtUpdate->execute([$NoItem, $txtUUIDVenta, $codigo]);

                $NoItem++;
            }

            // 2. Recorrer y actualizar existencias
            foreach ($detalle as $row) {
                $codigo = $row['CodigoPROD'];
                $cantidad = floatval($row['UnidadesVendidas']);
                // Restar cantidad directamente sin validación
                $sqlUpdate = "UPDATE tblproductossucursal 
                            SET Existencia = Existencia - ?, 
                                FechaUpdate = NOW(), 
                                UsuarioUpdate = ?
                            WHERE UUIDSucursal = ? AND CodigoPROD = ?";
                $stmtUpdate = $conectar->prepare($sqlUpdate);
                $stmtUpdate->execute([$cantidad, $txtUsuarioActual, $txtUUIDSucursal, $codigo]);
            }

        }


        public function consultar_producto($codigoproducto){
            $conectar= parent::conexion();
            parent::set_names();
            $sql="SELECT * FROM tblcatalogodeproductos
                    WHERE
                    tblcatalogodeproductos.CodigoPROD = ?";
            $sql=$conectar->prepare($sql);
            $sql->bindValue(1, $codigoproducto);
            $sql->execute();
            return $resultado=$sql->fetchAll();
            
        }

        // Función para generar UUID v4 en PHP
        private function generateUUIDv4() {
            $data = random_bytes(16);
            $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
            $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
            return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
        }

        public function guardar_direccion_envio($txtUUIDVenta,$txtPara,$txtContacto,$txtDireccionDeEnvio){
        
        $conectar= parent::conexion();
        parent::set_names();
            $sql="UPDATE tblnotasdeentrega
                  SET
                    Para = ?,
                    Contacto = ?,
                    DireccionEnvio = ?
                WHERE
                    UUIDVenta = ?";
            $sql=$conectar->prepare($sql);
            $sql->bindValue(1, $txtPara);
            $sql->bindValue(2, $txtContacto);
            $sql->bindValue(3, $txtDireccionDeEnvio);
            $sql->bindValue(4, $txtUUIDVenta);

            if($sql->execute())
			{
				$data = "Edicion";
			} else {
				$data = "Error";
			}
            return $data;
        }

        public function update_tipo_despacho($txtUUIDVenta,$txtTipoDespacho){
        
            $conectar= parent::conexion();
            parent::set_names();
                $sql="UPDATE tblnotasdeentrega
                      SET
                        TipoDespacho = ?
                    WHERE
                        UUIDVenta = ?";
                $sql=$conectar->prepare($sql);
                $sql->bindValue(1, $txtTipoDespacho);
                $sql->bindValue(2, $txtUUIDVenta);
    
                if($sql->execute())
                {
                    $data = "Edicion";
                } else {
                    $data = "Error";
                }
                return $data;
        }

        public function update_correo_cliente($txtUUIDCliente,$txtUsuarioActual,$txtCorreoNuevo,$txtTelefonoNuevo){
        
            $conectar= parent::conexion();
            parent::set_names();
                $sql="UPDATE tblcatalogodeclientes
                      SET
                        CorreoElectronico = ?,
                        TelWhatsApp = ?,
                        UsuarioUpdate = ?,
                        FechaUpdate = now()
                    WHERE
                        UUIDCliente = ?";
                $sql=$conectar->prepare($sql);
                $sql->bindValue(1, $txtCorreoNuevo);
                $sql->bindValue(2, $txtTelefonoNuevo);
                $sql->bindValue(3, $txtUsuarioActual);
                $sql->bindValue(4, $txtUUIDCliente);
    
                if($sql->execute())
                {
                    $data = "Ok";
                } else {
                    $data = "Error";
                }
                return $data;
        }

        public function mover_venta($txtUUIDVenta,$txtUsuarioActual,$txtTerminalDestino){
        
            $conectar= parent::conexion();
            parent::set_names();
                $sql="UPDATE tblnotasdeentrega
                      SET
                        UUIDCaja = ?,
                        UsuarioUpdate = ?,
                        FechaUpdate = now()
                    WHERE
                        UUIDVenta = ?";
                $sql=$conectar->prepare($sql);
                $sql->bindValue(1, $txtTerminalDestino);
                $sql->bindValue(2, $txtUsuarioActual);
                $sql->bindValue(3, $txtUUIDVenta);
    
                if($sql->execute())
                {
                    $data = "Ok";
                } else {
                    $data = "Error";
                }
                return $data;
        }

        public function delete_detalle_venta($uuid){
            $conectar= parent::conexion();
            parent::set_names();
            $sql="DELETE FROM tblnotasdeentregadetalle WHERE tblnotasdeentregadetalle.UUIDDetalleVenta = ?";
            $sql=$conectar->prepare($sql);
            $sql->bindValue(1, $uuid);
            
            if($sql->execute())
			{
				$data = "Eliminado";
			} else {
				$data = "Error";
			}
            return $data;
        }

        public function delete_venta($uuid){
            $conectar= parent::conexion();
            parent::set_names();
            $sql="DELETE FROM tblnotasdeentrega WHERE tblnotasdeentrega.UUIDVenta = ?";
            $sql=$conectar->prepare($sql);
            $sql->bindValue(1, $uuid);
            
            if($sql->execute())
			{
				$data = "Eliminado";
			} else {
				$data = "Error";
			}
            return $data;
        }

        public function delete_detalles_venta($uuid){
            $conectar= parent::conexion();
            parent::set_names();
            $sql="DELETE FROM tblnotasdeentregadetalle WHERE tblnotasdeentregadetalle.UUIDVenta = ?";
            $sql=$conectar->prepare($sql);
            $sql->bindValue(1, $uuid);
            
            if($sql->execute())
			{
				$data = "Eliminado";
			} else {
				$data = "Error";
			}
            return $data;
        }

        public function listar_ventas_mostrador_flotantes($nocaja){
            $conectar= parent::conexion();
            parent::set_names();
            $sql = "SELECT UUIDVenta, CodigoVEN, NombreDeCliente, TotalImporte FROM tblnotasdeentrega
                        WHERE TipoDespacho = 1 AND Estado = 1 AND UUIDCaja = ?
                        ORDER BY NoCorrelativo";
            $sql=$conectar->prepare($sql);
            $sql->bindValue(1, $nocaja);

            if($sql->execute())
			{
				return $resultado=$sql->fetchAll();
			} else {
				return "Error";
			}
        }

        public function listar_ventas_domicilio_flotantes($nocaja){
            $conectar= parent::conexion();
            parent::set_names();
            
            $sql = "SELECT UUIDVenta, CodigoVEN, NombreDeCliente, TotalImporte FROM tblnotasdeentrega
                        WHERE TipoDespacho = 2 AND Estado = 1 AND UUIDCaja = ?
                        ORDER BY NoCorrelativo";
            $sql=$conectar->prepare($sql);
            $sql->bindValue(1, $nocaja);

            if($sql->execute())
			{
				return $resultado=$sql->fetchAll();
			} else {
				return "Error";
			}
            
        }

        public function listar_ventas_despacho_flotantes($nocaja){
            $conectar= parent::conexion();
            parent::set_names();
            
            $sql = "SELECT UUIDVenta, CodigoVEN, NombreDeCliente, TotalImporte FROM tblnotasdeentrega
                        WHERE TipoDespacho = 3 AND Estado = 1 AND UUIDCaja = ?
                        ORDER BY NoCorrelativo";
            $sql=$conectar->prepare($sql);
            $sql->bindValue(1, $nocaja);

            if($sql->execute())
			{
				return $resultado=$sql->fetchAll();
			} else {
				return "Error";
			}
            
        }

        public function listar_ventas_procesadas($nocaja){
            $conectar= parent::conexion();
            parent::set_names();
            
            $sql = "SELECT * FROM tblnotasdeentrega
                        WHERE DATE(FechaEntrega) = CURDATE() AND (CodDocumento = '01' OR CodDocumento = '03') AND Estado >= 2 AND UUIDCaja = ?
                        ORDER BY NoCorrelativo";
            $sql=$conectar->prepare($sql);
            $sql->bindValue(1, $nocaja);

            if($sql->execute())
			{
				return $resultado=$sql->fetchAll();
			} else {
				return "Error";
			}
            
        }

        public function consultar_ventas_flotantes($nocaja){
            $conectar= parent::conexion();
            parent::set_names();
            $sql = "SELECT 
                        SUM(CASE WHEN TipoDespacho = 1 THEN 1 ELSE 0 END) AS cantmostrador,
                        SUM(CASE WHEN TipoDespacho = 2 THEN 1 ELSE 0 END) AS cantdomicilio,
                        SUM(CASE WHEN TipoDespacho = 3 THEN 1 ELSE 0 END) AS cantdespacho
                    FROM tblnotasdeentrega
                    WHERE Estado = 1 AND UUIDCaja = ?;";
            $sql=$conectar->prepare($sql);
            $sql->bindValue(1, $nocaja);

            if($sql->execute())
			{
				return $resultado=$sql->fetchAll();
			}
        }


        public function listar_clientes_filtro($buscarpor,$txtcriterio){
            $conectar= parent::conexion();
            parent::set_names();
           
                if($buscarpor == "nombre"){
                    $txtcriterio = "%".$txtcriterio."%";
                    $sql = "SELECT *, NombreDeCliente AS Cliente 
                            FROM tblcatalogodeclientes 
                            WHERE NombreDeCliente LIKE ? 
                            ORDER BY NombreDeCliente";
                }
                if($buscarpor == "codigo"){
                    $txtcriterio = "%".$txtcriterio."%";
                    $sql = "SELECT *, NombreDeCliente AS Cliente
                            FROM tblcatalogodeclientes WHERE tblcatalogodeclientes.CodigoCLI = ?  
                            ORDER BY tblcatalogodeclientes.NombreDeCliente";
                }
                if($buscarpor == "dui"){
                    $txtcriterio = "%".$txtcriterio."%";
                    $sql = "SELECT *, NombreDeCliente AS Cliente
                            FROM tblcatalogodeclientes WHERE tblcatalogodeclientes.DUI = ?  
                            ORDER BY tblcatalogodeclientes.NombreDeCliente";
                }
                if($buscarpor == "nrc"){
                    $txtcriterio = "%".$txtcriterio."%";
                    $sql = "SELECT *, NombreDeCliente AS Cliente
                            FROM tblcatalogodeclientes WHERE tblcatalogodeclientes.NRC = ?  
                            ORDER BY tblcatalogodeclientes.NombreDeCliente";
                }
                
                $sql=$conectar->prepare($sql);
                $sql->bindValue(1, $txtcriterio);

                $sql->execute();
                return $resultado=$sql->fetchAll();
        }

        public function listar_productos_filtro($sucursalid,$buscarproductopor,$txtcriterioproducto){
            $conectar= parent::conexion();
            parent::set_names();
           
            if ($buscarproductopor == "nombre") {
                $txtcriterioproducto = "%" . $txtcriterioproducto . "%";
                $sql = "SELECT p.*, ps.*, CONCAT(p.Descripcion, ' ', p.Contenido1) AS Producto 
                        FROM tblproductossucursal ps 
                        INNER JOIN  tblcatalogodeproductos p ON ps.CodigoPROD = p.CodigoPROD
                        WHERE ps.Estado = 1 AND ps.UUIDSucursal = ? AND CONCAT(p.Descripcion, ' ', p.Contenido1) LIKE ? 
                        ORDER BY CONCAT(p.Descripcion, ' ', p.Contenido1)";

            } else if ($buscarproductopor == "codigo") {
                $txtcriterioproducto = "%" . $txtcriterioproducto . "%";
                $sql = "SELECT p.*, ps.*, CONCAT(p.Descripcion, ' ', p.Contenido1) AS Producto
                        FROM tblproductossucursal ps 
                        INNER JOIN tblcatalogodeproductos ps ON ps.CodigoPROD = p.CodigoPROD
                        WHERE ps.Estado = 1 AND ps.UUIDSucursal = ? AND ps.CodigoPROD LIKE ?  
                        ORDER BY CONCAT(p.Descripcion, ' ', p.Contenido1)";

            }
                
            $sql=$conectar->prepare($sql);
            $sql->bindValue(1, $sucursalid);
            $sql->bindValue(2, $txtcriterioproducto);
            $sql->execute();
            return $resultado=$sql->fetchAll();
        }

        public function buscarDEPARTAMENTOMH($iddepartamento){ 
            $conectar = parent::conexion();
            parent::set_names();
            $sql = "SELECT CodigoMH FROM tbldepartamentos WHERE UUIDDepartamento = ?;";
            $sql = $conectar->prepare($sql);
            $sql->bindValue(1, $iddepartamento);
            $sql->execute();
            $resultado = $sql->fetch(); // Esto trae solo una fila
        
            // Ahora accedemos al campo directamente
            return $resultado['CodigoMH'];
        }

        public function buscarMUNICIPIOMH($idmunicipio){ 
            $conectar = parent::conexion();
            parent::set_names();
            $sql = "SELECT CodigoMH FROM tblmunicipios WHERE UUIDMunicipio = ?;";
            $sql = $conectar->prepare($sql);
            $sql->bindValue(1, $idmunicipio);
            $sql->execute();
            $resultado = $sql->fetch(); // Esto trae solo una fila
        
            // Ahora accedemos al campo directamente
            return $resultado['CodigoMH'];
        }

        function convertirNumeroALetras($numero)
        {
            $unidad = [
                '', 'UNO', 'DOS', 'TRES', 'CUATRO', 'CINCO', 'SEIS',
                'SIETE', 'OCHO', 'NUEVE', 'DIEZ', 'ONCE', 'DOCE',
                'TRECE', 'CATORCE', 'QUINCE', 'DIECISÉIS', 'DIECISIETE',
                'DIECIOCHO', 'DIECINUEVE', 'VEINTE'
            ];

            $decenas = [
                '', '', 'VEINTI', 'TREINTA', 'CUARENTA', 'CINCUENTA',
                'SESENTA', 'SETENTA', 'OCHENTA', 'NOVENTA'
            ];

            $centenas = [
                '', 'CIENTO', 'DOSCIENTOS', 'TRESCIENTOS', 'CUATROCIENTOS',
                'QUINIENTOS', 'SEISCIENTOS', 'SETECIENTOS', 'OCHOCIENTOS', 'NOVECIENTOS'
            ];

            $numero = number_format($numero, 2, '.', '');
            list($entero, $decimal) = explode('.', $numero);
            $entero = (int)$entero;
            $decimal = (int)$decimal;

            if ($entero > 100000) {
                return "ERROR: Número mayor a cien mil.";
            }

            if ($entero == 0) {
                $letras = 'CERO';
            } elseif ($entero == 100000) {
                $letras = 'CIEN MIL';
            } else {
                $letras = '';

                // Miles
                if ($entero >= 1000) {
                    $miles = floor($entero / 1000);
                    if ($miles == 1) {
                        $letras .= 'MIL ';
                    } else {
                        $letras .= convertirNumeroALetras($miles);
                        $letras .= ' MIL ';
                    }
                    $entero = $entero % 1000;
                }

                // Centenas
                if ($entero == 100) {
                    $letras .= 'CIEN';
                } elseif ($entero > 0) {
                    $cent = floor($entero / 100);
                    $dec = floor(($entero % 100) / 10);
                    $uni = $entero % 10;
                    $resto = $entero % 100;

                    $letras .= $centenas[$cent];

                    if ($resto <= 20) {
                        $letras .= ($cent ? ' ' : '') . $unidad[$resto];
                    } elseif ($resto < 30) {
                        $letras .= ($cent ? ' ' : '') . $decenas[$dec] . $unidad[$uni];
                    } else {
                        $letras .= ($cent ? ' ' : '') . $decenas[$dec];
                        if ($uni > 0) {
                            $letras .= ' Y ' . $unidad[$uni];
                        }
                    }
                }
            }

            return trim($letras) . ' ' . str_pad($decimal, 2, '0', STR_PAD_RIGHT) . '/100 DÓLARES';
        }

        

        public function Imprimir_Ticket_Venta($uuidventa){
            $conectar= parent::conexion();
            parent::set_names();
            $sql="SELECT * FROM tblnotasdeentrega
                    WHERE
                    tblnotasdeentrega.UUIDVenta = ?";
            $sql=$conectar->prepare($sql);
            $sql->bindValue(1, $uuidventa);
            $sql->execute();
            return $resultado=$sql->fetchAll();
        }
        public function Imprimir_Ticket_Venta_Detalle($uuidventa){
            $conectar= parent::conexion();
            parent::set_names();
            $sql="SELECT * FROM tblnotasdeentregadetalle
                    WHERE tblnotasdeentregadetalle.UUIDVenta = ?";
            $sql=$conectar->prepare($sql);
            $sql->bindValue(1, $uuidventa);
            $sql->execute();
            return $sql;
        }
        


    }
    
?>