<?php
    require_once("../config/conexion.php");
    require_once("../model/Ventas.php");
    $ventas = new Ventas();
    
    switch($_GET["op"]){

        case "updatetransmisionauto":
            $datos=$ventas->update_transmision_auto($_POST["txtNuevoValor"]);
        break;

        case "updatenotacredito":
            $datos=$ventas->update_nota_credito($_POST["txtUUID"],$_POST["txtTipoDeDTE"],$_POST["txtNumeroDeControl"],$_POST["txtDocumentoRelacionado"]);
        break;

        case "updatedtetransmitido":
            $datos=$ventas->update_dte_transmitido($_POST["txtUUID"],$_POST["txtSello"]);
            $output["resultado"] = $datos;
            echo json_encode($output);
        break;

        case "consultardtependiente":
            $datos=$ventas->consultar_dt_pendiente();  
            if(is_array($datos)==true and count($datos)>0){
                foreach($datos as $row)
                {
                    $output["resultado"] = "Ok";
                    $output["UUIDVenta"] = $row["UUIDVenta"];
                    $output["CodDocumento"] = $row["CodDocumento"];
                    $output["UUIDSucursal"] = $row["UUIDSucursal"];
                    $output["UUIDCaja"] = $row["UUIDCaja"];
                }
                echo json_encode($output);
            } else {
                $output["resultado"] = "Not";
                echo json_encode($output);
            }

        break;

        case "habilitardte":
            $datos=$ventas->habilitar_dte($_POST["txtUUIDVenta"]);
            $output["resultado"] = $datos;
            echo json_encode($output);
        break;

        case "recalculardte":
            $datos=$ventas->recalcular_dte($_POST["txtUUIDVenta"],$_POST["txtNoItem"]);
            $output["resultado"] = $datos;
            echo json_encode($output);
        break;

        case "generararchivosjson":
            $datos=$ventas->generar_archivos_json($_POST["fechainicio"],$_POST["fechafin"]);
            $output["resultado"] = $datos;
            echo json_encode($output);
        break;

        case "generararchivospdf":
            $datos = $ventas->generar_archivos_pdf($_POST["fechainicio"], $_POST["fechafin"]);
            $output["resultado"] = $datos;
            echo json_encode($output);
            break;

        case "verificarcorrelativos":
            $datos=$ventas->verificar_correlativos($_POST["UUIDSucursal"],$_POST["fechainicio"],$_POST["fechafin"]);
            $output["resultado"] = $datos;
            echo json_encode($output);
        break;

        case "listarventasfefiltro":
            $datos=$ventas->listar_ventasfe_filtro($_POST["estado"],$_POST["filtro"],$_POST["fechainicio"],$_POST["fechafin"]);
            $data= Array();
            foreach($datos as $row){
                
                $sub_array = array();
                $sub_array[] = date("d/m/Y h:i a", strtotime($row["FechaRegistro"]));
                $sub_array[] = $row["NoAnualCorrelativo"];
                $sub_array[] = '<span style="display: inline-block; text-align: left;">' .$row["UUIDCaja"] . '</span>';
                $sub_array[] = !empty($row["FechaHoraGeneracion"]) ? date("d/m/Y h:i a", strtotime($row["FechaHoraGeneracion"])) : " ";
                $sub_array[] = $row["NumeroDeControl"];
                $sub_array[] = tipoDOCUMENTO($row["CodDocumento"]);
                $sub_array[] = '<span style="display: inline-block; text-align: left;">' .$row["NombreDeCliente"] . '</span>';
                if ($row["Estado"]==1){
                    $sub_array[] = '<span class="label label-pill label-warning">Pendiente</span>';
                }else{
                    $sub_array[] = condicionDOCUMENTO($row["Condicion"]);                    
                }
                if ($row["Estado"]==1){
                    $sub_array[] = '<span style="display: inline-block; width: 100%; text-align: right;">' . number_format($row["TotalImporte"],2) . '</span>';
                }else{
                    $sub_array[] = '<span style="display: inline-block; width: 100%; text-align: right;">' . number_format($row["TotalPagar"],2) . '</span>';                   
                }
                
                
                if ($row["Estado"]==1){
                    $sub_array[] = '<span class="label label-pill label-warning">Pedido</span>';
                }else if($row["Estado"]==2){
                    $sub_array[] = '<span class="label label-pill label-primary">Generada</span>';
                }else if($row["Estado"]==3){
                    $sub_array[] = '<span class="label label-pill label-success">Transmitida</span>';
                }else if($row["Estado"]==4){
                    $sub_array[] = '<span class="label label-pill label-danger">Rechazada</span>';
                }else if($row["Estado"]==5){
                    $sub_array[] = '<span class="label label-pill label-danger">Anulada</span>';
                }
                
                if (!empty($row["CorreoElectronico"]) || $row["Entregado"] > 0){
                    if ($row["Entregado"]==0){
                        $sub_array[] = '<span class="label label-pill label-warning">No</span>';
                    }else if($row["Entregado"]==1){
                        $sub_array[] = '<span class="label label-pill label-success">Si</span>';
                    }else if($row["Entregado"]==2){
                        $sub_array[] = '<span class="label label-pill label-danger">Error</span>';
                    }
                }else{
                    $sub_array[] = $row["CorreoElectronico"];
                }

                $urlConsulta = '';
                if (!empty($row["CodigoDeGeneracion"]) && !empty($row["FechaHoraGeneracion"])) {
                    $urlConsulta = 'https://admin.factura.gob.sv/consultaPublica?ambiente=01'
                                . '&codGen=' . urlencode($row["CodigoDeGeneracion"])
                                . '&fechaEmi=' . urlencode(substr($row["FechaHoraGeneracion"], 0, 10));
                }

                if ($_POST["tipousuario"] == 1){
                    $sub_array[] = '<ul class="icons-list" style="text-align:center;">
                                        <li class="dropdown">
                                            <a href="#" class="dropdown-toggle" data-toggle="dropdown" >
                                                <i class="icon-menu9"></i>
                                            </a>
                                            <ul class="dropdown-menu dropdown-menu-right">
                                            <li><a href="javascript:;" onclick="transmitirDTE(\''.$row["UUIDVenta"].'\', \''.$row["CodDocumento"].'\', \''.$row["UUIDSucursal"].'\', \''.$row["UUIDCaja"].'\', \''.$row["Estado"].'\')">
                                                <i class="icon-file-upload"></i> Transmitir DTE</a></li>
                                            <li><a href="'.$urlConsulta.'" target="_blank">
                                                <i class="icon-eye8"></i> Consulta MH</a></li>
                                            <li><a href="javascript:;" onclick="enviarDTECORREO(\''.$row["UUIDVenta"].'\', \''.$row["Estado"].'\', \''.$row["CorreoElectronico"].'\', \''.$row["CodigoCLI"].'\')">
                                                <i class="icon-envelop4"></i> Eviar DTE a Correo</a></li>
                                            <li><a href="javascript:;" onclick="imprimirDTETicket(\''.$row["UUIDVenta"].'\', \''.$row["CodDocumento"].'\', \''.$row["Estado"].'\')">
                                                <i class="icon-printer4"></i> Imprimir Ticket</a></li>
                                            <li><a href="javascript:;" onclick="imprimirDTEDocumento(\''.$row["UUIDVenta"].'\', \''.$row["CodDocumento"].'\', \''.$row["Estado"].'\')">
                                                <i class="icon-printer4"></i> Imprimir Documento</a></li>
                                            <li><a href="javascript:;" onclick="detalleDTE(\''.$row["UUIDVenta"].'\')">
                                                <i class="icon-file-eye"></i> Detalle DTE</a></li>
                                            <li><a href="javascript:;" onclick="recalcularDTE(\''.$row["UUIDVenta"].'\')">
                                                <i class="icon-calculator2"></i> Recalcular DTE</a></li>
                                            <li><a href="javascript:;" onclick="actualizarDTE(\''.$row["UUIDVenta"].'\')">
                                                <i class="icon-file-check"></i> Actualizar DTE</a></li>
                                            
                                            <li><a href="javascript:;" onclick="notaCREDITO(\''.$row["UUIDVenta"].'\')">
                                                <i class="icon-file-text2"></i> Nota de Credito</a></li>
                                            <li><a href="javascript:;" onclick="anularDTE(\''.$row["UUIDVenta"].'\', \''.$row["Estado"].'\')">
                                                <i class="icon-cancel-square"></i> Anular DTE</a></li>
                                            <li><a href="javascript:;" onclick="eliminarDTE(\''.$row["UUIDVenta"].'\', \''.$row["Estado"].'\')">
                                                <i class="icon-trash"></i> Eliminar</a></li>
                                            <li><a href="javascript:;" onclick="infoDTE(\''.$row["UUIDVenta"].'\',
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
                                                <li><a href="javascript:;" onclick="imprimirDTETicket(\''.$row["UUIDVenta"].'\', \''.$row["Estado"].'\')">
                                                    <i class="icon-printer4"></i> Imprimir Ticket</a></li>
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

        case "listarfacturasdtefiltro":
            $datos=$ventas->listar_facturasdte_filtro($_POST["estado"],$_POST["filtro"],$_POST["fechainicio"],$_POST["fechafin"]);
            $data= Array();
            foreach($datos as $row){
                
                $sub_array = array();
                $sub_array[] = date("d/m/Y h:i a", strtotime($row["FechaRegistro"]));
                $sub_array[] = $row["NoAnualCorrelativo"];
                $sub_array[] = '<span style="display: inline-block; text-align: left;">' .$row["UUIDCaja"] . '</span>';
                $sub_array[] = !empty($row["FechaHoraGeneracion"]) ? date("d/m/Y h:i a", strtotime($row["FechaHoraGeneracion"])) : " ";
                $sub_array[] = $row["NumeroDeControl"];
                $sub_array[] = tipoDOCUMENTO($row["CodDocumento"]);
                $sub_array[] = '<span style="display: inline-block; text-align: left;">' .$row["NombreDeCliente"] . '</span>';
                if ($row["Estado"]==1){
                    $sub_array[] = '<span class="label label-pill label-warning">Pendiente</span>';
                }else{
                    $sub_array[] = condicionDOCUMENTO($row["Condicion"]);                    
                }
                if ($row["Estado"]==1){
                    $sub_array[] = '<span style="display: inline-block; width: 100%; text-align: right;">' . number_format($row["TotalImporte"],2) . '</span>';
                }else{
                    $sub_array[] = '<span style="display: inline-block; width: 100%; text-align: right;">' . number_format($row["TotalPagar"],2) . '</span>';                   
                }
                
                
                if ($row["Estado"]==1){
                    $sub_array[] = '<span class="label label-pill label-warning">Pedido</span>';
                }else if($row["Estado"]==2){
                    $sub_array[] = '<span class="label label-pill label-primary">Generada</span>';
                }else if($row["Estado"]==3){
                    $sub_array[] = '<span class="label label-pill label-success">Transmitida</span>';
                }else if($row["Estado"]==4){
                    $sub_array[] = '<span class="label label-pill label-danger">Rechazada</span>';
                }else if($row["Estado"]==5){
                    $sub_array[] = '<span class="label label-pill label-danger">Anulada</span>';
                }
                
                if (!empty($row["CorreoElectronico"]) || $row["Entregado"] > 0){
                    if ($row["Entregado"]==0){
                        $sub_array[] = '<span class="label label-pill label-warning">No</span>';
                    }else if($row["Entregado"]==1){
                        $sub_array[] = '<span class="label label-pill label-success">Si</span>';
                    }else if($row["Entregado"]==2){
                        $sub_array[] = '<span class="label label-pill label-danger">Error</span>';
                    }
                }else{
                    $sub_array[] = $row["CorreoElectronico"];
                }

                $urlConsulta = '';
                if (!empty($row["CodigoDeGeneracion"]) && !empty($row["FechaHoraGeneracion"])) {
                    $urlConsulta = 'https://admin.factura.gob.sv/consultaPublica?ambiente=01'
                                . '&codGen=' . urlencode($row["CodigoDeGeneracion"])
                                . '&fechaEmi=' . urlencode(substr($row["FechaHoraGeneracion"], 0, 10));
                }

                if ($_POST["tipousuario"] == 1){
                    $sub_array[] = '<ul class="icons-list" style="text-align:center;">
                                        <li class="dropdown">
                                            <a href="#" class="dropdown-toggle" data-toggle="dropdown" >
                                                <i class="icon-menu9"></i>
                                            </a>
                                            <ul class="dropdown-menu dropdown-menu-right">
                                            <li><a href="javascript:;" onclick="transmitirDTE(\''.$row["UUIDVenta"].'\', \''.$row["CodDocumento"].'\', \''.$row["UUIDSucursal"].'\', \''.$row["UUIDCaja"].'\', \''.$row["Estado"].'\')">
                                                <i class="icon-file-upload"></i> Transmitir DTE</a></li>
                                            <li><a href="'.$urlConsulta.'" target="_blank">
                                                <i class="icon-eye8"></i> Consulta MH</a></li>
                                            <li><a href="javascript:;" onclick="enviarDTECORREO(\''.$row["UUIDVenta"].'\', \''.$row["Estado"].'\', \''.$row["CorreoElectronico"].'\', \''.$row["CodigoCLI"].'\')">
                                                <i class="icon-envelop4"></i> Eviar DTE a Correo</a></li>
                                            <li><a href="javascript:;" onclick="imprimirDTETicket(\''.$row["UUIDVenta"].'\', \''.$row["CodDocumento"].'\', \''.$row["Estado"].'\')">
                                                <i class="icon-printer4"></i> Imprimir Ticket</a></li>
                                            <li><a href="javascript:;" onclick="imprimirDTEDocumento(\''.$row["UUIDVenta"].'\', \''.$row["CodDocumento"].'\', \''.$row["Estado"].'\')">
                                                <i class="icon-printer4"></i> Imprimir Documento</a></li>
                                            <li><a href="javascript:;" onclick="detalleDTE(\''.$row["UUIDVenta"].'\')">
                                                <i class="icon-file-eye"></i> Detalle DTE</a></li>
                                            <li><a href="javascript:;" onclick="recalcularDTE(\''.$row["UUIDVenta"].'\')">
                                                <i class="icon-calculator2"></i> Recalcular DTE</a></li>
                                            <li><a href="javascript:;" onclick="actualizarDTE(\''.$row["UUIDVenta"].'\')">
                                                <i class="icon-file-check"></i> Actualizar DTE</a></li>
                                            
                                            <li><a href="javascript:;" onclick="notaCREDITO(\''.$row["UUIDVenta"].'\')">
                                                <i class="icon-file-text2"></i> Nota de Credito</a></li>
                                            <li><a href="javascript:;" onclick="anularDTE(\''.$row["UUIDVenta"].'\', \''.$row["Estado"].'\')">
                                                <i class="icon-cancel-square"></i> Anular DTE</a></li>
                                            <li><a href="javascript:;" onclick="eliminarDTE(\''.$row["UUIDVenta"].'\', \''.$row["Estado"].'\')">
                                                <i class="icon-trash"></i> Eliminar</a></li>
                                            <li><a href="javascript:;" onclick="infoDTE(\''.$row["UUIDVenta"].'\',
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
                                                <li><a href="javascript:;" onclick="imprimirDTETicket(\''.$row["UUIDVenta"].'\', \''.$row["Estado"].'\')">
                                                    <i class="icon-printer4"></i> Imprimir Ticket</a></li>
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
    
    
        
    
    }
    function tipoDOCUMENTO($opcion) 
    {        
        switch($opcion)
        {
            case "01": 
                return "FACTURA";
            case "03": 
                return "CREDITO FISCAL";
            case "05": 
                return "NOTA DE CREDITO";
            case "14": 
                return "SUJETO EXCLUIDO";
            default:
                return "Sin Dato";
        }  
    }

    function tipoDESPACHO($opcion) 
    {        
        switch($opcion)
        {
            case "1": 
                return "Sucursal";
            case "2": 
                return "Domicilio";
            case "3": 
                return "Ruteo";
            default:
                return "S/D";
        }  
    }

    function condicionDOCUMENTO($opcion) 
    {        
        switch($opcion)
        {
            case 1: 
                return '<span class="label label-pill label-success">Contado</span>';
            case 2: 
                return '<span class="label label-pill label-warning">Credito</span>';
            case 3: 
                return '<span class="label label-pill label-primary">Multiple</span>';
            default:
                return 'Sin Dato';
        }   
    }
    
    function estadoDOCUMENTO($opcion) 
    {        
        switch($opcion)
        {
            case 1: 
                return '<span class="label label-pill label-danger">PENDIENTE</span>';
            case 2: 
                return '<span class="label label-pill label-primary">GENERADA</span>';
            case 3: 
                return '<span class="label label-pill label-success">TRANSMITIDA</span>';
            case 4: 
                return '<span class="label label-pill label-warning">RECHAZADA</span>';
            case 5: 
                return '<span class="label label-pill label-warning">ANULADA</span>';
            default:
                return "Sin Dato";
        }  
    }
    
    
?>