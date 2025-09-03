<?php
    date_default_timezone_set('America/El_Salvador');

    class Eventos extends Conectar{

        public function insert_anulacion($txtUsuarioActual, $UUIDSucursal, $txtCodigoDeGeneracionE, $txtNumeroDeControlE, $txtSelloDeRecepcionE,$cmbTipoDeDocumento,   
        $txtFechaDeEmision, $txtTotalImporte, $txtTotalIVA, $cmbTipoDeDocumentoReceptor, $txtNumeroDocumentoReceptor, $txtNombreReceptor, $cmbTipoDeDocumentoResponsable, 
        $txtNumeroDocumentoResponsable, $txtNombreResponsable, $cmbTipoDeDocumentoSolicitante, $txtNumeroDocumentoSolicitante,
        $txtNombreSolicitante, $cmbTipoDeAnulacion, $txtMotivoAnulacion, $txtCodigoDeGeneracionReposicion){
            $conectar= parent::conexion();
            parent::set_names();

            $txtCodigoDeGeneracion = $this->CodigoGeneracionUUIDv4();
            $VersionJSON = 2;
            $FechaAnulacion = date("Y-m-d");        
            $HoraAnulacion = date("H:i:s");   

            // Consulta para obtener datos de la tabla tblsucursales
            $sql = "SELECT * FROM tblsucursales
                    WHERE UUIDSucursal = ?";
            $stmt = $conectar->prepare($sql);
            $stmt->bindValue(1, $UUIDSucursal);
            $stmt->execute();
            $sucursal = $stmt->fetch(PDO::FETCH_ASSOC);

            $Ambiente = $sucursal["AmbienteDTE"];

            $sql="INSERT INTO tbleventoanulacion 
                    (UUIDEventoANU,CodigoEVEN,UsuarioRegistro,UUIDSucursal,VersionJSON,Ambiente,CodigoDeGeneracion,FechaAnulacion,HoraAnulacion,
                    TipoDTE,CodigoDeGeneracionEmitido,NumeroDeControlEmitido,SelloDeRecepcionEmitido,FechaEmision,MontoDTE,MontoIVA,TipoDocReceptor,
                    NumeroDocReceptor,NombreReceptor,TipoDocResponsable,NumeroDocResponsable,NombreResponsable,TipoDocSolicitante,NumeroDocSolicitante,
                    NombreSolicitante,TipoDeAnulacion,MotivoAnulacion,CodigoDeGeneracionR) 
                    VALUES 
                    (UUID(),CONCAT(
                            YEAR(NOW()), 
                            LPAD(MONTH(NOW()), 2, '0'), 
                            LPAD(DAY(NOW()), 2, '0'), 
                            LPAD(HOUR(NOW()), 2, '0'), 
                            LPAD(MINUTE(NOW()), 2, '0'), 
                            LPAD(SECOND(NOW()), 2, '0')
                        ),?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?);";
            $sql=$conectar->prepare($sql);
            $sql->bindValue(1, $txtUsuarioActual);
            $sql->bindValue(2, $UUIDSucursal);
            $sql->bindValue(3, $VersionJSON);
            $sql->bindValue(4, $Ambiente);
            $sql->bindValue(5, $txtCodigoDeGeneracion);
            $sql->bindValue(6, $FechaAnulacion);
            $sql->bindValue(7, $HoraAnulacion);
            $sql->bindValue(8, $cmbTipoDeDocumento);
            $sql->bindValue(9, $txtCodigoDeGeneracionE);
            $sql->bindValue(10, $txtNumeroDeControlE);
            $sql->bindValue(11, $txtSelloDeRecepcionE);
            $sql->bindValue(12, $txtFechaDeEmision);
            $sql->bindValue(13, $txtTotalImporte);
            $sql->bindValue(14, $txtTotalIVA);
            $sql->bindValue(15, $cmbTipoDeDocumentoReceptor);
            $sql->bindValue(16, $txtNumeroDocumentoReceptor);
            $sql->bindValue(17, $txtNombreReceptor);
            $sql->bindValue(18, $cmbTipoDeDocumentoResponsable);
            $sql->bindValue(19, $txtNumeroDocumentoResponsable);
            $sql->bindValue(20, $txtNombreResponsable);
            $sql->bindValue(21, $cmbTipoDeDocumentoSolicitante);
            $sql->bindValue(22, $txtNumeroDocumentoSolicitante);
            $sql->bindValue(23, $txtNombreSolicitante);
            $sql->bindValue(24, $cmbTipoDeAnulacion);
            $sql->bindValue(25, $txtMotivoAnulacion);
            $sql->bindValue(26, $txtCodigoDeGeneracionReposicion);
            
            if($sql->execute())
			{
			    $count = $sql->rowCount();
				if($count == 0){
					$data = "SinRegistro";
				} else {
					$data = "Registro";
				}
			} else {
				$data = "Error";
			}
            return $data;
        }

        public function update_anualcion($txtUUIDEventoANU,$txtUsuarioActual, $txtCodigoDeGeneracion, $txtNumeroDeControl, $txtSelloDeRecepcion, $cmbTipoDeDocumento, 
        $txtFechaDeEmision, $txtTotalImporte, $txtTotalIVA, $cmbTipoDeDocumentoR, $txtNumeroDocumentoReceptor, $txtNombreReceptor, 
        $cmbTipoDeDocumentoResponsable, $txtNumeroDocumentoResponsable, $txtNombreResponsable, $cmbTipoDeDocumentoSolicitante, $txtNumeroDocumentoSolicitante,
        $txtNombreSolicitante, $cmbTipoDeAnulacion, $txtMotivoAnulacion, $txtCodigoDeGeneracionReposicion){
            $Estado = 1;
			if($txtDiferencia >= 0){
				$Estado = 2;
			} else {
				$Estado = 1;
			}
            $conectar= parent::conexion();
            parent::set_names();
            $sql="UPDATE tblcortesdecnb 
                  SET
                  FechaDeCorte = ?,
                  UUIDSucursal = ?,
                  SaldoCNBFedecredito = ?,
                  SaldoCNBPromerica = ?,
                  SaldoCNBBAC = ?,
                  CajaFuerte = ?,
                  OtroDinero = ?,
                  MontoTotal = ?,
                  Notas = ?,
                  MontoComparativo = ?,
                  Cien = ?,
                  Cincuenta = ?,
                  Veinte = ?,Diez = ?,
                  Cinco = ?,
                  Uno = ?,
                  UnDolar = ?,
                  CeroVeinticinco = ?,
                  CeroDiez = ?,
                  CeroCinco = ?,
                  CeroUno = ?,
                  TotalEfectivo = ?,
                  Calculado = ?,
                  Diferencia = ?,
                  UsuarioUpdate = ?,
                  FechaUpdate = now(),
                  Estado = ?
                WHERE
                  UUIDCorteCNB = ?";
            $sql=$conectar->prepare($sql);
            $sql->bindValue(1, $txtFechaCorte);
            $sql->bindValue(2, $txtsucursalcnbregistro);
            $sql->bindValue(3, $txtSaldoCNBFedecredito);
            $sql->bindValue(4, $txtSaldoCNBPromerica);
            $sql->bindValue(5, $txtSaldoCNBCredomatic);
            $sql->bindValue(6, $txtCajaFuerte);
            $sql->bindValue(7, $txtOtroDinero);
            $sql->bindValue(8, $txtMontoTotal);
            $sql->bindValue(9, $txtobservaciones);
            $sql->bindValue(10, $txtMontoComparativo);
            $sql->bindValue(11, $txt100);
            $sql->bindValue(12, $txt50);
            $sql->bindValue(13, $txt20);
            $sql->bindValue(14, $txt10);
            $sql->bindValue(15, $txt5);
            $sql->bindValue(16, $txt1b);
            $sql->bindValue(17, $txt1m);
            $sql->bindValue(18, $txt025);
            $sql->bindValue(19, $txt010);
            $sql->bindValue(20, $txt005);
            $sql->bindValue(21, $txt001);
            $sql->bindValue(22, $txtTotalEfectivo);
            $sql->bindValue(23, $txtCalculado);
            $sql->bindValue(24, $txtDiferencia);
            $sql->bindValue(25, $txtUsuarioActual);
            $sql->bindValue(26, $Estado);
            $sql->bindValue(27, $txtID);

            if($sql->execute())
			{
			    $count = $sql->rowCount();
				if($count == 0){
					$data = "SinRegistro";
				} else {
					$data = "Edicion";
				}
			} else {
				$data = "Error";
			}
            return $data;
        }

        public function listar_eventos_contingencia_filtro($negocio,$filtro,$fechainicio,$fechafin){
            $conectar= parent::conexion();
            parent::set_names();
            if($filtro == "0"){
                $sql = "SELECT *
                        FROM tbleventocontingencia
                        WHERE UUIDNegocio = ? AND DATE(FechaRegistro) BETWEEN ? AND ? 
                        ORDER BY Estado";
                $sql=$conectar->prepare($sql);
                $sql->bindValue(1, $negocio);
                $sql->bindValue(2, $fechainicio);
                $sql->bindValue(3, $fechafin);
            }else{
                $sql = "SELECT *
                        FROM tbleventocontingencia
                        WHERE UUIDNegocio = ? 
                        AND Estado = ? AND DATE(FechaRegistro) BETWEEN ? AND ? 
                        ORDER BY Estado";
                $sql = $conectar->prepare($sql);
                $sql->bindValue(1, $negocio);
                $sql->bindValue(2, $filtro);
                $sql->bindValue(3, $fechainicio);
                $sql->bindValue(4, $fechafin);
            }
            $sql->execute();
            return $resultado=$sql->fetchAll();
        }

        public function listar_eventos_anulacion_filtro($sucursal,$filtro,$fechainicio,$fechafin){
            $conectar= parent::conexion();
            parent::set_names();
            if($filtro == "0"){
                $sql = "SELECT *
                        FROM tbleventoanulacion
                        WHERE DATE(FechaRegistro) BETWEEN ? AND ? 
                        ORDER BY Estado";
                $sql=$conectar->prepare($sql);
                $sql->bindValue(1, $fechainicio);
                $sql->bindValue(2, $fechafin);
            }else{
                $sql = "SELECT *
                        FROM tbleventoanulacion
                        WHERE UUIDSucursal = ? 
                        AND Estado = ? AND DATE(FechaRegistro) BETWEEN ? AND ? 
                        ORDER BY Estado";
                $sql = $conectar->prepare($sql);
                $sql->bindValue(1, $sucursal);
                $sql->bindValue(2, $filtro);
                $sql->bindValue(3, $fechainicio);
                $sql->bindValue(4, $fechafin);
            }
            $sql->execute();
            return $resultado=$sql->fetchAll();
        }

        public function eliminar_anulacion($txtUUIDEvento){
            $conectar= parent::conexion();
            parent::set_names();
            $sql="DELETE FROM tbleventoanulacion WHERE UUIDEventoANU = ?";
            $sql=$conectar->prepare($sql);
            $sql->bindValue(1, $txtUUIDEvento);
            
            if($sql->execute())
			{
				$data = "Eliminado";
			} else {
				$data = "Error";
			}
            return $data;
        }

        public function consultar_dte_codigo($txtCodigoDeGeneracion){
            $conectar= parent::conexion();
            parent::set_names();
            $sql="SELECT * FROM tblnotasdeentrega
                    WHERE
                    CodigoDeGeneracion = ?";
            $sql=$conectar->prepare($sql);
            $sql->bindValue(1, $txtCodigoDeGeneracion);
            $sql->execute();
            return $resultado=$sql->fetchAll();
        }
        
        public function consultar_evento_contingencia_uuid($uuidevento){
            $conectar= parent::conexion();
            parent::set_names();
            $sql="SELECT * FROM tbleventocontingencia
                    WHERE
                    UUIDEventoCON = ?";
            $sql=$conectar->prepare($sql);
            $sql->bindValue(1, $uuidevento);
            $sql->execute();
            return $resultado=$sql->fetchAll();
        }

        public function consultar_evento_anulacion_uuid($uuidevento){
            $conectar= parent::conexion();
            parent::set_names();
            $sql="SELECT * FROM tbleventoanulacion
                    WHERE
                    UUIDEventoANU = ?";
            $sql=$conectar->prepare($sql);
            $sql->bindValue(1, $uuidevento);
            $sql->execute();
            return $resultado=$sql->fetchAll();
        }

        private function CodigoGeneracionUUIDv4() {
            $data = random_bytes(16);
            $data[6] = chr(ord($data[6]) & 0x0f | 0x40); 
            $data[8] = chr(ord($data[8]) & 0x3f | 0x80); 
            return strtoupper(vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4)));
        }

    }
?>