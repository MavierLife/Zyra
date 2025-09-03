<?php
    require_once("../config/conexion.php");
    require_once("../model/Eventos.php");
    $eventos = new Eventos();
    
    switch($_GET["op"]){

        case "insertanulacion":
            $datos=$eventos->insert_anulacion($_POST["txtUsuarioActual"],$_POST["txtUUIDSucursal"],$_POST["txtCodigoDeGeneracion"],$_POST["txtNumeroDeControl"],
            $_POST["txtSelloDeRecepcion"],$_POST['cmbTipoDeDocumento'],$_POST["txtFechaDeEmision"],$_POST["txtTotalImporte"],$_POST["txtTotalIVA"],
            $_POST["cmbTipoDeDocumentoReceptor"],$_POST["txtNumeroDocumentoReceptor"],$_POST["txtNombreReceptor"], $_POST["cmbTipoDeDocumentoResponsable"],
            $_POST["txtNumeroDocumentoResponsable"],$_POST["txtNombreResponsable"],$_POST["cmbTipoDeDocumentoSolicitante"],$_POST["txtNumeroDocumentoSolicitante"],
            $_POST["txtNombreSolicitante"],$_POST["cmbTipoDeAnulacion"],$_POST["txtMotivoAnulacion"],$_POST["txtCodigoDeGeneracionReposicion"]);
            $output["resultado"] = $datos;
            echo json_encode($output);
        break;
        
        case "updateanulacion":
            $datos=$eventos->update_anulacion($_POST["txtUUIDEventoANU"],$_POST["txtUsuarioActual"],$_POST["txtCodigoDeGeneracion"],$_POST["txtNumeroDeControl"],
            $_POST["txtSelloDeRecepcion"],$_POST['cmbTipoDeDocumento'],$_POST["txtFechaDeEmision"],$_POST["txtTotalImporte"],$_POST["txtTotalIVA"],
            $_POST["cmbTipoDeDocumentoR"],$_POST["txtNumeroDocumentoReceptor"],$_POST["txtNombreReceptor"], $_POST["cmbTipoDeDocumentoResponsable"],
            $_POST["txtNumeroDocumentoResponsable"],$_POST["txtNombreResponsable"],$_POST["cmbTipoDeDocumentoSolicitante"],$_POST["txtNumeroDocumentoSolicitante"],
            $_POST["txtNombreSolicitante"],$_POST["cmbTipoDeAnulacion"],$_POST["txtMotivoAnulacion"],$_POST["txtCodigoDeGeneracionReposicion"]);
            $output["resultado"] = $datos;
            echo json_encode($output);
        break;

        case "eliminaranulacion":
            $datos=$eventos->eliminar_anulacion($_POST["txtUUIDEvento"]);
            $output["resultado"] = $datos;
            echo json_encode($output);
        break;

        case "listareventosanulacionfiltro":
            $datos=$eventos->listar_eventos_anulacion_filtro($_POST["sucursalid"],$_POST["filtro"],$_POST["fechainicio"],$_POST["fechafin"]);
            $data= Array();
            foreach($datos as $row){
                
                $sub_array = array();
                $sub_array[] = date("d/m/Y h:i a", strtotime($row["FechaRegistro"]));
                $sub_array[] = $row["CodigoEVEN"];
                //$sub_array[] = date("d/m/Y", strtotime($row["FechaAnulacion"]));
                $sub_array[] = tipoDOCUMENTO($row["TipoDTE"]);
                $sub_array[] = date("d/m/Y", strtotime($row["FechaEmision"]));
                $sub_array[] = $row["CodigoDeGeneracionEmitido"];

                if ($row["TipoDeAnulacion"] == 3){
                    $sub_array[] = tipoANULACION($row["TipoDeAnulacion"]) + ': ' + $row["MotivoAnulacion"];
                }else{
                    $sub_array[] = tipoANULACION($row["TipoDeAnulacion"]);
                }

                
                $sub_array[] = '<span style="display: inline-block; width: 100%; text-align: right;">' . number_format($row["MontoDTE"],2) . '</span>';

                $sub_array[] = estadoDOCUMENTO($row["Estado"]);

                if ($_POST["tipousuario"] == 1){
                    $sub_array[] = '<ul class="icons-list" style="text-align:center;">
                                        <li class="dropdown">
                                            <a href="#" class="dropdown-toggle" data-toggle="dropdown" >
                                                <i class="icon-menu9"></i>
                                            </a>
                                            <ul class="dropdown-menu dropdown-menu-right">
                                            <li><a href="javascript:;" onclick="transmitirANULACION(\''.$row["UUIDEventoANU"].'\', \''.$row["Estado"].'\')">
                                                <i class="icon-file-upload"></i> Transmitir</a></li>
                                            <li><a href="javascript:;" onclick="editarANULACION(\''.$row["UUIDEventoANU"].'\',\''.$row["Estado"].'\')">
                                                <i class="icon-pencil7"></i> Editar</a></li>
                                            <li><a href="javascript:;" onclick="respuestaMH(\''.$row["UUIDEventoANU"].'\')">
                                                <i class="icon-file-eye"></i> Detalle</a></li>
                                            <li><a href="javascript:;" onclick="eliminarANULACION(\''.$row["UUIDEventoANU"].'\', \''.$row["Estado"].'\')">
                                                <i class="icon-trash"></i> Eliminar</a></li>
                                            <li><a href="javascript:;" onclick="infoANULACION(\''.$row["UUIDEventoANU"].'\',
                                                \''.$row["FechaRegistro"].'\',
                                                \''.$row["UsuarioRegistro"].'\',
                                                \''.$row["FechaUpdate"].'\',
                                                \''.$row["UsuarioUpdate"].'\')">
                                                <i class="icon-info22"></i> Info</a></li>
                                            </ul>
                                        </li>
                                    </ul>';
                }else{
                    $sub_array[] = '<ul class="icons-list" style="text-align:center;">
                                        <li class="dropdown">
                                            <a href="#" class="dropdown-toggle" data-toggle="dropdown" >
                                                <i class="icon-menu9"></i>
                                            </a>
                                            <ul class="dropdown-menu dropdown-menu-right">
                                                <li><a href="javascript:;" onclick="infoDTE(\''.$row["UUIDVenta"].'\',
                                                \''.$row["FechaRegistro"].'\',
                                                \''.$row["UsuarioRegistro"].'\',
                                                \''.$row["FechaUpdate"].'\',
                                                \''.$row["UsuarioUpdate"].'\')">
                                                <i class="icon-info22"></i> Info</a></li>
                                            </ul>
                                        </li>
                                    </ul>';
                } 
                
                $data[] = $sub_array;
            }

            $results = array(
                "sEcho"=>1,
                "iTotalRecords"=>count($data),
                "iTotalDisplayRecords"=>count($data),
                "aaData"=>$data);
            echo json_encode($results);
        break;

        case "listareventoscontingenciafiltro":
            $datos=$eventos->listar_eventos_contingencia_filtro($_POST["sucursalid"],$_POST["filtro"],$_POST["fechainicio"],$_POST["fechafin"]);
            $data= Array();
            foreach($datos as $row){
                
                $sub_array = array();
                $sub_array[] = date("d/m/Y", strtotime($row["FechaRegistro"]));
                $sub_array[] = $row["CodigoEVEN"];

                if ($row["TipoContingencia"] == 5){
                    $sub_array[] = tipoCONTINGENCIA($row["TipoContingencia"]) + ': ' + $row["MotivoContingencia"];
                }else{
                    $sub_array[] = tipoCONTINGENCIA($row["TipoContingencia"]);
                }

                $sub_array[] = 0;

                $sub_array[] = date("d/m/Y", strtotime($row["FechaInicio"]));

                $sub_array[] = date("d/m/Y", strtotime($row["FechaFin"]));

                $sub_array[] = estadoDOCUMENTO($row["Estado"]);

                if ($_POST["tipousuario"] == 1){
                    $sub_array[] = '<ul class="icons-list" style="text-align:center;">
                                        <li class="dropdown">
                                            <a href="#" class="dropdown-toggle" data-toggle="dropdown" >
                                                <i class="icon-menu9"></i>
                                            </a>
                                            <ul class="dropdown-menu dropdown-menu-right">
                                            <li><a href="javascript:;" onclick="transmitirCONTINGENCIA(\''.$row["UUIDEventoCON"].'\', \''.$row["Estado"].'\')">
                                                <i class="icon-file-upload"></i> Transmitir</a></li>
                                            <li><a href="javascript:;" onclick="editarEVENTO(\''.$row["UUIDEventoCON"].'\',\''.$row["Estado"].'\')">
                                                <i class="icon-pencil7"></i> Editar</a></li>
                                            <li><a href="javascript:;" onclick="detalleCONTINGENCIA(\''.$row["UUIDEventoCON"].'\')">
                                                <i class="icon-file-eye"></i> Detalle</a></li>
                                            <li><a href="javascript:;" onclick="eliminarCONTINGENCIA(\''.$row["UUIDEventoCON"].'\', \''.$row["Estado"].'\')">
                                                <i class="icon-trash"></i> Eliminar</a></li>
                                            <li><a href="javascript:;" onclick="infoCON(\''.$row["UUIDEventoCON"].'\',
                                                \''.$row["FechaRegistro"].'\',
                                                \''.$row["UsuarioRegistro"].'\',
                                                \''.$row["FechaUpdate"].'\',
                                                \''.$row["UsuarioUpdate"].'\')">
                                                <i class="icon-info22"></i> Info</a></li>
                                            </ul>
                                        </li>
                                    </ul>';
                }else{
                    $sub_array[] = '<ul class="icons-list" style="text-align:center;">
                                        <li class="dropdown">
                                            <a href="#" class="dropdown-toggle" data-toggle="dropdown" >
                                                <i class="icon-menu9"></i>
                                            </a>
                                            <ul class="dropdown-menu dropdown-menu-right">
                                                <li><a href="javascript:;" onclick="infoDTE(\''.$row["UUIDVenta"].'\',
                                                \''.$row["FechaRegistro"].'\',
                                                \''.$row["UsuarioRegistro"].'\',
                                                \''.$row["FechaUpdate"].'\',
                                                \''.$row["UsuarioUpdate"].'\')">
                                                <i class="icon-info22"></i> Info</a></li>
                                            </ul>
                                        </li>
                                    </ul>';
                } 
                
                $data[] = $sub_array;
            }

            $results = array(
                "sEcho"=>1,
                "iTotalRecords"=>count($data),
                "iTotalDisplayRecords"=>count($data),
                "aaData"=>$data);
            echo json_encode($results);
        break;

        case "consultardtecodigo":
            $datos=$eventos->consultar_dte_codigo($_POST["txtCodigoDeGeneracion"]);  
            if(is_array($datos)==true and count($datos)>0){
                foreach($datos as $row)
                {
                    $output["resultado"] = "Ok";
                    $output["NumeroDeControl"] = $row["NumeroDeControl"];
                    $output["SelloDeRecepcion"] = $row["SelloDeRecepcion"];
                    $output["CodDocumento"] = $row["CodDocumento"];
                    $output["FechaHoraGeneracion"] = $row["FechaHoraGeneracion"];
                    $output["UUIDSucursal"] = $row["UUIDSucursal"];
                    $output["TotalPagar"] = $row["TotalPagar"];
                    $output["TotalIVA"] = $row["TotalIVA"];
                    $output["TipoDocumentoReceptor"] = $row["TipoDocumentoReceptor"];
                    $output["NumeroDocumentoReceptor"] = $row["NumeroDocumentoReceptor"];
                    $output["NombreDeCliente"] = $row["NombreDeCliente"];
                }
                echo json_encode($output);
            } else {
                $output["resultado"] = "Error";
                echo json_encode($output);
            }
        break;

        case "consultaranulacionuuid":
            $datos=$eventos->consultar_evento_anulacion_uuid($_POST["UUIDEvento"]);  
            if(is_array($datos)==true and count($datos)>0){
                foreach($datos as $row)
                {
                    $output["resultado"] = "Ok";
                    
                    $output["JSONGenerado"] = $row["JSONGenerado"];
                    $output["JSONFirmado"] = $row["JSONFirmado"];
                    $output["RespuestaMH"] = $row["RespuestaMH"];
                }
                echo json_encode($output);
            } else {
                $output["resultado"] = "Error";
                echo json_encode($output);
            }
        break;

        case "consultarcontingenciauuid":
            $datos=$eventos->consultar_evento_contingencia_uuid($_POST["UUIDEvento"]);  
            if(is_array($datos)==true and count($datos)>0){
                foreach($datos as $row)
                {
                    $output["resultado"] = "Ok";
                    $output["JSONGenerado"] = $row["JSONGenerado"];
                    $output["JSONFirmado"] = $row["JSONFirmado"];
                    $output["RespuestaMH"] = $row["RespuestaMH"];
                }
                echo json_encode($output);
            } else {
                $output["resultado"] = "Error";
                echo json_encode($output);
            }
        break;

    }

    function estadoDOCUMENTO($opcion) 
    {        
        switch($opcion)
        {
            case 1: 
                return '<span class="label label-pill label-primary">GENERADO</span>';
            case 2: 
                return '<span class="label label-pill label-success">TRANSMITIDO</span>';
            case 3: 
                return '<span class="label label-pill label-warning">RECHAZADO</span>';
            default:
                return "Sin Dato";
        }  
    }

    function tipoCONTINGENCIA($opcion) 
    {        
        switch($opcion)
        {
            case 1: 
                return 'No disponibilidad del sistema MH';
            case 2: 
                return 'No disponibilidad del sistema del emisor';
            case 3: 
                return 'Falla en el servicio de suministro de internet del emisor';
            case 4: 
                return 'Falla en el servicio de suministro de energia del emisor';
            case 5: 
                return 'Otros';
            default:
                return "Sin Dato";
        }  
    }

    function tipoDOCUMENTO($opcion) 
    {        
        switch($opcion)
        {
            case "01": 
                return '<span class="label label-pill label-warning">FACTURA</span>';
            case "03": 
                return '<span class="label label-pill label-warning">CREDITO FISCAL</span>';
            case "05": 
                return "NOTA DE CREDITO";
            case "14": 
                return "SUJETO EXCLUIDO";
            default:
                return "Sin Dato";
        }  
    }

    function tipoANULACION($opcion) 
    {        
        switch($opcion)
        {
            case 1: 
                return 'Error en la información del Documento Tributario Electrónico a invalidar';
            case 2: 
                return 'Rescindir de la operación realizada';
            case 3: 
                return 'Otro';
            default:
                return "Sin Dato";
        }  
    }
    
    
?>