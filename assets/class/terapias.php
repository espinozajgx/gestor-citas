<?php
//include_once '../citas.php';
//include_once '../class/calendario.php';
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of terapias
 *
 * @author RM
 */
class terapias {
    //put your code here
    
    public static function agregar_terapia($nombre, $precio, $descripcion){
        
        $bd = connection::getInstance()->getDb();
        
        $consulta = "INSERT INTO terapia (nombre_terapia, descripcion_terapia, precio_terapia)
            VALUES (?,?,?)";
       // echo $consulta;
        $comando = $bd->prepare($consulta);
        $resultado = $comando->execute(array($nombre, $descripcion, $precio));
        
        if ($resultado){
            return "1";
        }
        else{
            return "2";
        }
    }
    
    public static function consulta_terapia($col, $valor, $tipo_dato="varchar"){
        $operador;
        if ($tipo_dato != "varchar"){
            $operador = "=";
        }
        else{
            $operador = "LIKE";
        }
        $consulta = "SELECT $col FROM terapia WHERE $col $operador \"$valor\"";
        //echo $consulta;
        $bd = connection::getInstance()->getDb();
        $pdo = $bd->prepare($consulta);
        $pdo->execute();
        $resultado = $pdo->fetchAll(PDO::FETCH_ASSOC);        
        if ($resultado){
            $longitud = count($resultado);
            //echo $longitud;
            if ($longitud>0){
                return 1;
            }
            else return 0;
            
        }
        else return false;
    }            
    
    public static function consulta_info_terapia($id_terapia){
        $json;
        $consulta = "SELECT DISTINCT nombre_terapia, precio_terapia, descripcion_terapia
            FROM terapia 
            WHERE id_terapia = $id_terapia";
        //echo $consulta;
        $bd = connection::getInstance()->getDb();
        $pdo = $bd->prepare($consulta);
        $pdo->execute();
        $resultado = $pdo->fetchAll(PDO::FETCH_ASSOC);        
        if ($resultado){
            $longitud = count($resultado);
            //echo $longitud;
            $json[0]["estado"] = 1;
            for ($i=0; $i<$longitud; $i++){
                $json[$i+1]['nombre_terapia']  = $resultado[$i]["nombre_terapia"];
                $json[$i+1]['precio_terapia']  = $resultado[$i]["precio_terapia"];
                $json[$i+1]['descripcion_terapia']  = $resultado[$i]["descripcion_terapia"];
            }     
            return $json;
        }
        else{
            $json[0]["estado"] = 1;
            return $json;
        }
    }
    
    public static function actualizar_terapia($id_terapia, $nombre, $precio, $descripcion){
        $bd = connection::getInstance()->getDb();
        $sql = "UPDATE terapia
        SET nombre_terapia=?, descripcion_terapia=?, precio_terapia=?
            WHERE id_terapia = ".$id_terapia;
        $pdo = $bd->prepare($sql);        
        return $pdo->execute(array($nombre, $descripcion, $precio));
    }
    
    public static function actualizar_programa_terapeutico_basico($id_programa, $descripcion, $descuento, $tipo_pago){
        $bd = connection::getInstance()->getDb();
        $sql = "UPDATE programa_terapeutico
        SET descripcion_programa_terapeutico=?, descuento = ?, estatus_pago_id_ep = ?
            WHERE id_programa_terapeutico = ".$id_programa;
        $pdo = $bd->prepare($sql);        
        return $pdo->execute(array($descripcion, $descuento, $tipo_pago));
    }
    
    public static function cancelar_programa_terapeutico($id_programa){
        $bd = connection::getInstance()->getDb();
        $sql = "UPDATE programa_terapeutico
        SET estado=?
            WHERE id_programa_terapeutico = ".$id_programa;
        $pdo = $bd->prepare($sql);        
        return $pdo->execute(array("anulado"));
    }
    
    public static function cancelar_terapias_programa($id_programa){
        $bd = connection::getInstance()->getDb();
        $sql = "UPDATE programa_tiene_terapia
        SET estado=?
            WHERE programa_terapeutico_id_programa_terapeutico = ".$id_programa."
                AND estado NOT LIKE \"pagado\" AND estado NOT LIKE \"atendido\"";
        $pdo = $bd->prepare($sql);        
        return $pdo->execute(array("anulado"));
    }
    
    public static function cancelar_citas_programa ($id_programa){
        $sql = "SELECT ptt.terapia_id_terapia as id_t, ptt.reserva_medica_id_rm as id_r 
            FROM programa_tiene_terapia ptt\n"
        . "INNER JOIN reserva_medica rm ON ptt.reserva_medica_id_rm = rm.id_rm\n"
        . "WHERE ptt.programa_terapeutico_id_programa_terapeutico = $id_programa";
        //echo $consulta;
        $bd = connection::getInstance()->getDb();
        $pdo = $bd->prepare($sql);
        $pdo->execute();
        $resultado = $pdo->fetchAll(PDO::FETCH_ASSOC);        
        if ($resultado){
            $longitud = count($resultado);           
            for ($i=0; $i<$longitud; $i++){
                $id_cita = $resultado[$i]["id_r"];                
                $sql = "UPDATE reserva_medica
                    SET estado=?
                    WHERE id_rm = ".$id_cita." AND estado <> 2 AND estado <> 6";
                $pdo_ = $bd->prepare($sql);
                
                //echo $sql;
                $res_aux = $pdo_->execute(array("5"));                              
            }                 
        }        
        return true;
    }
    
    public static function eliminar_terapias_programa($id_programa, $solo_activas = false){
        $bd = connection::getInstance()->getDb();        
        $sql = "DELETE FROM programa_tiene_terapia
            WHERE programa_terapeutico_id_programa_terapeutico=".$id_programa;
        if ($solo_activas){
            $sql.= " AND estado LIKE \"pendiente\"";
        }
        $pdo = $bd->prepare($sql);
        //echo $sql;
        return $pdo->execute();
    }
    
    
    public static function eliminar_terapia_individual($id_programa, $id_terapia){
        $bd = connection::getInstance()->getDb();        
        $sql = "DELETE FROM programa_tiene_terapia
            WHERE id_programa_tiene_terapia=".$id_programa."
                AND terapia_id_terapia=".$id_terapia;
        
        $pdo = $bd->prepare($sql);
        //echo $sql;
        return $pdo->execute();
    }
    
    public static function eliminar_pago_parcial($id_programa){
        $bd = connection::getInstance()->getDb();        
        $sql = "DELETE FROM pagos_parciales
            WHERE programa_terapeutico_id_programa_terapeutico=".$id_programa;
        
        $pdo = $bd->prepare($sql);
        //echo $sql;
        return $pdo->execute();
    }
    
    public static function establecer_modo_pago ($id_programa, $modo_pago){
        $bd = connection::getInstance()->getDb();
        $sql = "UPDATE programa_terapeutico
        SET estatus_pago_id_ep=?
            WHERE id_programa_terapeutico = ".$id_programa;
        $pdo = $bd->prepare($sql);       
        //echo $sql;
        return $pdo->execute(array("$modo_pago"));
    }
    
    public static function establecer_descuento_programa_terapeutico ($id_programa, $descuento){
        $bd = connection::getInstance()->getDb();
        $sql = "UPDATE programa_terapeutico
        SET descuento=?
            WHERE id_programa_terapeutico = ".$id_programa;
        $pdo = $bd->prepare($sql);       
        //echo $sql;
        return $pdo->execute(array("$descuento"));
    }
    
    public static function obtener_id_programa_paciente ($id_paciente, $especial = false){
        $sql = "SELECT * FROM `programa_terapeutico` 
            WHERE `paciente_id_paciente`=".$id_paciente."
                 AND (estado NOT LIKE \"anulado\" AND estado NOT LIKE \"culminado\" AND estado NOT LIKE \"deshabilitado\" AND estado NOT LIKE \"eliminado\")";
        if (!$especial){
            $sql.=" AND especial <> true";
        }
        $bd = connection::getInstance()->getDb();
        $pdo = $bd->prepare($sql);
        //echo $sql;
        $pdo->execute();
        $resultado = $pdo->fetchAll(PDO::FETCH_ASSOC);        
        if ($resultado){
            return $resultado[0]["id_programa_terapeutico"];
        }
        else{            
            return false;
        }
    }
    
    public static function obtener_id_cita_programa_t_id ($id_ptt){
        $sql = "SELECT * FROM `programa_tiene_terapia` 
            WHERE `id_programa_tiene_terapia`=".$id_cita."";
        $bd = connection::getInstance()->getDb();
        $pdo = $bd->prepare($sql);
        //echo $sql;
        $pdo->execute();
        $resultado = $pdo->fetchAll(PDO::FETCH_ASSOC);        
        if ($resultado){
            return $resultado[0]["reserva_medica_id_rm"];
        }
        else{            
            return false;
        }
    }
            
    
    public static function obtener_nombre_terapia ($id_terapia){
        $sql = "SELECT DISTINCT * 
            FROM `terapia` 
            WHERE `id_terapia`=$id_terapia";
        $bd = connection::getInstance()->getDb();
        $pdo = $bd->prepare($sql);
        $pdo->execute();
        $resultado = $pdo->fetchAll(PDO::FETCH_ASSOC);        
        if ($resultado){
            return $resultado[0]["nombre_terapia"];
        }
        else{            
            return false;
        }
    }
    
    public static function obtener_id_terapia_cita($id_programa_t){
        $sql = "SELECT t.id_terapia as id_t, ptt.id_programa_tiene_terapia
                FROM terapia t
                INNER JOIN programa_tiene_terapia ptt ON ptt.terapia_id_terapia=t.id_terapia
                WHERE ptt.id_programa_tiene_terapia = $id_programa_t";
        $bd = connection::getInstance()->getDb();
        $pdo = $bd->prepare($sql);
        $pdo->execute();
        $resultado = $pdo->fetchAll(PDO::FETCH_ASSOC);        
        //echo $sql;
        if ($resultado){
            return $resultado[0]["id_t"];
        }
        else{            
            return false;
        }
    }
    
    public static function obtener_nombre_terapia_cita($id_programa_t){
        $sql = "SELECT t.nombre_terapia as nombre_t, ptt.id_programa_tiene_terapia
                FROM terapia t
                INNER JOIN programa_tiene_terapia ptt ON ptt.terapia_id_terapia=t.id_terapia
                WHERE ptt.id_programa_tiene_terapia = $id_programa_t";
        $bd = connection::getInstance()->getDb();
        $pdo = $bd->prepare($sql);
        $pdo->execute();
        $resultado = $pdo->fetchAll(PDO::FETCH_ASSOC);        
        if ($resultado){
            return $resultado[0]["nombre_t"];
        }
        else{            
            return false;
        }
    }
    
    public static function obtener_id_tipo_pago ($id_programa){
        $sql = "SELECT * FROM `programa_terapeutico` 
            WHERE `id_programa_terapeutico`=$id_programa";
        $bd = connection::getInstance()->getDb();
        $pdo = $bd->prepare($sql);
        //echo $sql;
        $pdo->execute();
        $resultado = $pdo->fetchAll(PDO::FETCH_ASSOC);        
        if ($resultado){
            return $resultado[0]["estatus_pago_id_ep"];
        }
        else{            
            return false;
        }
    }
    public static function obtener_id_metodo_pago ($id_programa){
        $sql = "SELECT * FROM `programa_terapeutico` 
            WHERE `id_programa_terapeutico`=$id_programa";
        $bd = connection::getInstance()->getDb();
        $pdo = $bd->prepare($sql);
        //echo $sql;
        $pdo->execute();
        $resultado = $pdo->fetchAll(PDO::FETCH_ASSOC);        
        if ($resultado){
            return $resultado[0]["metodos_pago_id_mp"];
        }
        else{            
            return false;
        }
    }
    public static function obtener_referencia_pago ($id_programa){
        $sql = "SELECT * FROM `programa_terapeutico` 
            WHERE `id_programa_terapeutico`=$id_programa";
        $bd = connection::getInstance()->getDb();
        $pdo = $bd->prepare($sql);
        //echo $sql;
        $pdo->execute();
        $resultado = $pdo->fetchAll(PDO::FETCH_ASSOC);        
        if ($resultado){
            return $resultado[0]["referencia"];
        }
        else{            
            return false;
        }
    }
    
    public static function obtener_referencia_pago_parcial ($id_programa){
        $sql = "SELECT * FROM `pagos_parciales` 
            WHERE `programa_terapeutico_id_programa_terapeutico`=$id_programa";
        $bd = connection::getInstance()->getDb();
        $pdo = $bd->prepare($sql);
        //echo $sql;
        $pdo->execute();
        $resultado = $pdo->fetchAll(PDO::FETCH_ASSOC);        
        if ($resultado){
            return $resultado[0]["referencia"];
        }
        else{            
            return false;
        }
    }
    
    public static function obtener_nombre_mp ($id_mp){
        $sql = "SELECT * FROM `metodos_pago` 
            WHERE `id_mp`=$id_mp";
        $bd = connection::getInstance()->getDb();
        $pdo = $bd->prepare($sql);
        //echo $sql;
        $pdo->execute();
        $resultado = $pdo->fetchAll(PDO::FETCH_ASSOC);        
        if ($resultado){
            return $resultado[0]["nombre"];
        }
        else{            
            return false;
        }
    }
    
    public static function obtener_estado_programa ($id_programa){
        $sql = "SELECT * FROM `programa_terapeutico` 
            WHERE `id_programa_terapeutico`=$id_programa";
        $bd = connection::getInstance()->getDb();
        $pdo = $bd->prepare($sql);
        //echo $sql;
        $pdo->execute();
        $resultado = $pdo->fetchAll(PDO::FETCH_ASSOC);        
        if ($resultado){
            return $resultado[0]["estado"];
        }
        else{            
            return false;
        }
    }
    
        public static function obtener_nombre_programa ($id_programa){
        $sql = "SELECT * FROM `programa_terapeutico` 
            WHERE `id_programa_terapeutico`=$id_programa";
        $bd = connection::getInstance()->getDb();
        $pdo = $bd->prepare($sql);
        //echo $sql;
        $pdo->execute();
        $resultado = $pdo->fetchAll(PDO::FETCH_ASSOC);        
        if ($resultado){
            return $resultado[0]["descripcion_programa_terapeutico"];
        }
        else{            
            return false;
        }
    }

    public static function obtener_nombre_ep ($id_ep){
        $sql = "SELECT * FROM `estatus_pago` 
            WHERE `id_ep`=$id_ep";
        $bd = connection::getInstance()->getDb();
        $pdo = $bd->prepare($sql);
        //echo $sql;
        $pdo->execute();
        $resultado = $pdo->fetchAll(PDO::FETCH_ASSOC);        
        if ($resultado){
            return $resultado[0]["nombre"];
        }
        else{            
            return false;
        }
    }

    public static function obtener_metodo_pago_parcial ($id_programa){
        $sql = "SELECT * FROM `pagos_parciales` 
            WHERE `programa_terapeutico_id_programa_terapeutico`=$id_programa";
        $bd = connection::getInstance()->getDb();
        $pdo = $bd->prepare($sql);
        //echo $sql;
        $pdo->execute();
        $resultado = $pdo->fetchAll(PDO::FETCH_ASSOC);        
        if ($resultado){
            return $resultado[0]["metodos_pago_id_mp"];
        }
        else{            
            return false;
        }
    }
    
    public static function obtener_descuento_programa($id_programa){
        $sql = "SELECT * FROM `programa_terapeutico` 
            WHERE `id_programa_terapeutico`=$id_programa";
        $bd = connection::getInstance()->getDb();
        $pdo = $bd->prepare($sql);
        //echo $sql;
        $pdo->execute();
        $resultado = $pdo->fetchAll(PDO::FETCH_ASSOC);        
        if ($resultado){
            return $resultado[0]["descuento"];
        }
        else{            
            return false;
        }
    }    
    
    public static function crear_programa_terapeutico($id_paciente, $nombre_programa, $descuento, $retornar_id=false, $especial = false, $tipo_pago = 7){
        $bd = connection::getInstance()->getDb();
        $array;
        if ($tipo_pago == ""){
            $tipo_pago = 7;
        }
        if ($especial){
            $consulta = "INSERT INTO programa_terapeutico (paciente_id_paciente, descripcion_programa_terapeutico, descuento, especial, estatus_pago_id_ep)
            VALUES (?,?,?,?,?)";
            $array = array ($id_paciente, $nombre_programa,$descuento, $especial, $tipo_pago);
        }
        else{
            $consulta = "INSERT INTO programa_terapeutico (paciente_id_paciente, descripcion_programa_terapeutico, descuento, estatus_pago_id_ep)
            VALUES (?,?,?,?)";
            $array = array ($id_paciente, $nombre_programa,$descuento,$tipo_pago);
        }
        
        //echo $consulta;
        $comando = $bd->prepare($consulta);
        $resultado = $comando->execute($array);
        if ($resultado){
            
            if ($retornar_id){
                return $bd->lastInsertId();
            }
            else{
                return "0";
            }
        }
        else{
            return "2";
        }
    }
    
    public static function asignar_terapias_programa($array_terapias, $id_programa){
        $bd = connection::getInstance()->getDb();                
        $numero_terapias = count($array_terapias);
        $bandera = 1;                
        
        for ($i=0; $i<$numero_terapias;$i++){
            $sql = "INSERT INTO programa_tiene_terapia
            (programa_terapeutico_id_programa_terapeutico, terapia_id_terapia)
            VALUES (?, ?)";
            $pdo = $bd->prepare($sql);
            $id_terapia= $array_terapias[$i];    
            //echo $id_terapia." - ".$id_programa;
            if (!$pdo->execute(array($id_programa,$id_terapia))){
                $bandera = 0;
            }
            else{
                $bandera = $bd->lastInsertId();
            }
        }
        return $bandera;
    }
    
    public static function tabla_programas(){
         //Establecer la conexion con la base de datos
        $bd = connection::getInstance()->getDb();
        //Consulta para obtener los dias feriados
        $sql = "SELECT 
            pt.estado,
            p.id_paciente               as id_p,
            pt.id_programa_terapeutico  as programa,
            p.nombre                    as nombre, 
            p.apellidop, 
            p.apellidom,
            pt.id_programa_terapeutico  as id_pt,
            COUNT(t.id_terapia) Terapias 
            FROM paciente p 
            INNER JOIN programa_terapeutico pt ON pt.paciente_id_paciente=p.id_paciente 
            LEFT JOIN programa_tiene_terapia ptt ON ptt.programa_terapeutico_id_programa_terapeutico=pt.id_programa_terapeutico 
            LEFT  JOIN terapia t ON ptt.terapia_id_terapia=t.id_terapia 
            WHERE pt.especial <> true AND pt.estado NOT LIKE \"%eliminado%\"
            GROUP BY pt.id_programa_terapeutico";
        $pdo = $bd->prepare($sql);
        //echo $sql;

        //pt.estado NOT LIKE '%culminado%' 
        
        $pdo->execute();
        //Creamos el arreglo asociativo con el cual trabajaremos
        $resultados = $pdo->fetchAll(PDO::FETCH_ASSOC);
        //Nos paseamos por la lista de fechas para constuir la estructura de JSON necesaria
        //para el calendario de FULLCALENDAR
        //print_r($resultados);
        //echo "<br>";
        //print_r($resultados);
        $longitud = count($resultados);
        //echo $longitud;   
        if ($resultados){
            if ($longitud<1){
                $json[0]['N'] = "No hay información que mostrar";
                $json[0]['Paciente'] = "";
                $json[0]['Terapias'] = "";
                $json[0]['Estado'] = "";
                $json[0]['Acciones'] = "";

            }
            for ($i=0; $i<$longitud; $i++){
                $json[$i]['N'] = "<a href=\"terapias.php?opcion=1&terapia=".$resultados[$i]["id_p"]."\">".($i+1)."</a>";
                $json[$i]['Paciente'] = strtoupper( $resultados[$i]["nombre"] . " " . $resultados[$i]["apellidop"] . " " . $resultados[$i]["apellidom"]);
                $json[$i]['Terapias'] = $resultados[$i]["Terapias"];
                if ($resultados[$i]["estado"] == "deshabilitado" || $resultados[$i]["estado"] == "cancelado"){
                    $estado =  "ANULADO";
                }
                else{
                    $estado = $resultados[$i]["estado"];
                }
                $json[$i]['Estado'] = strtoupper($estado);
                $json[$i]['Acciones'] = "
                        <a title=\"Ver Reporte\" id=\"btn_reserva\" 
                            class=\"btn btn-info\"  
                            onclick = \"generar_invoice_programa(".$resultados[$i]["programa"].")\">
                            <i class=\"fa fa-file-text-o\"></i>
                        </a>
                        <a title=\"Detalle\" 
                            class=\"btn btn-info\"  
                            href = \"terapias.php?opcion=6&id_paciente=".$resultados[$i]["id_p"]."&id_programa=".$resultados[$i]["programa"]."\">
                            <i class=\"fa fa-eye\"></i>
                        </a>
                        <a title=\"Eliminar\" 
                            class=\"btn btn-danger\"  
                            onclick =\"modal_cancelar_programa(".$resultados[$i]["id_pt"].", true)\">
                            <i class=\"fa fa-trash\"></i>
                        </a>
                        ";

            }        //FORMATO de json
        }
        else{
            $json[0]['N'] = "No hay información que mostrar";
            $json[0]['Paciente'] = "";
            $json[0]['Terapias'] = "";
            $json[0]['Acciones'] = "";
            $json[0]['Estado'] = "";
        }
        
        //descripcion, fecha inicio, fecha fin
        $json = json_encode($json);
        return $json;
    }
    
    public static function lista_programa_paciente($id_paciente){
        $sql = "SELECT paciente.id_paciente, programa_terapeutico.id_programa_terapeutico as id_pt, terapia.id_terapia as id_t, paciente.nombre, GROUP_CONCAT(terapia.nombre_terapia) as nombre_t, SUM(terapia.precio_terapia) as precio_t FROM paciente\n"
    . " INNER JOIN programa_terapeutico ON programa_terapeutico.paciente_id_paciente=paciente.id_paciente\n"
    . " INNER JOIN programa_tiene_terapia ON programa_tiene_terapia.programa_terapeutico_id_programa_terapeutico=programa_terapeutico.id_programa_terapeutico\n"
    . " INNER JOIN terapia ON programa_tiene_terapia.terapia_id_terapia=terapia.id_terapia\n"
    . " GROUP BY id_pt\n"
    . " HAVING id_paciente = ".$id_paciente;
        $bd = connection::getInstance()->getDb();
        //echo $sql;
        $pdo = $bd->prepare($sql);
        $pdo->execute();
        $resultado = $pdo->fetchAll(PDO::FETCH_ASSOC);        
        if ($resultado){
            $longitud = count($resultado);
            //echo $longitud;
            $json[0]["estado"] = 1;
            $str="";
            for ($i=0; $i<$longitud; $i++){
                $id             = $resultado[$i]["id_pt"];
                $nombre         = $resultado[$i]["nombre_t"];
                $precio         = $resultado[$i]["precio_t"];
                $str.="<option value=".$id.">Terapias: ".$nombre."</option>";
                
            }   
            $json[1]['html'] = $str;
            return $json;
        }
        else{
            $json[0]["estado"] = 1;
            return $json;
        }
        
    }
    
    public static function lista_terapias_programa($id_programa, $id_referer = false){
        $sql = "SELECT programa_tiene_terapia.id_programa_tiene_terapia as ptt_id,
            rm.id_rm                                        as id_rm, 
            rm.fecha_inicio                                 as fecha_t,
            terapia.id_terapia                              as id_terapia, 
            programa_tiene_terapia.estado                   as estado_t, 
            terapia.nombre_terapia                          as nombre_t, 
            terapia.precio_terapia                          as precio_t,
            terapia.id_terapia                              as id_t,
            prt.descripcion_programa_terapeutico            as desc_prt, 
            prt.id_programa_terapeutico                     as prt_id,
            prt.estatus_pago_id_ep                          as tipo_pago,
            ep.nombre                                       as nombre_pago,
            mp.nombre                                       as nombre_mp,
            prt.referencia                                  as referencia_pt,
            pp.metodos_pago_id_mp                           as id_mp_pp,
            pp.referencia                                   as referencia_pp,
            prt.estado                                      as estado_pr
            FROM terapia            
            INNER JOIN programa_tiene_terapia       ON terapia.id_terapia=programa_tiene_terapia.terapia_id_terapia
            INNER JOIN programa_terapeutico     prt ON prt.id_programa_terapeutico = programa_tiene_terapia.programa_terapeutico_id_programa_terapeutico
            LEFT JOIN reserva_medica            rm  ON programa_tiene_terapia.reserva_medica_id_rm = rm.id_rm            
            LEFT JOIN estatus_pago              ep  ON prt.estatus_pago_id_ep = ep.id_ep
            LEFT JOIN pagos_parciales           pp  ON pp.programa_terapeutico_id_programa_terapeutico = prt.id_programa_terapeutico
            LEFT JOIN metodos_pago              mp  ON mp.id_mp = prt.metodos_pago_id_mp
            WHERE programa_tiene_terapia.programa_terapeutico_id_programa_terapeutico =$id_programa
            ORDER BY programa_tiene_terapia.id_programa_tiene_terapia";
        
        $bd = connection::getInstance()->getDb();
        //echo $sql;
        $pdo = $bd->prepare($sql);
        $pdo->execute();
        //echo $sql;
        $resultado = $pdo->fetchAll(PDO::FETCH_ASSOC);        
        if ($resultado){
            $longitud = count($resultado);
            $bandera_validar_programa = true;
            $bandera_programa_bloqueado = false;
            //echo $longitud;
            $json[0]["estado"] = 1;
            //Nombre del programa
            $json[0]["desc_prt"] = $resultado[0]["desc_prt"];
            //Estatus del programa
            $json[0]["tipo_pago"] = $resultado[0]["nombre_pago"];
            $str_btn="";
            //Detalles de pago
            $str_metodos_pago="";
            $metodo_1 = $resultado[0]["nombre_mp"];
            if ($resultado[0]["tipo_pago"]==7){
                $str_metodos_pago.= "PAGO POR CITA";
            }
            else{                
                $metodo_1 = $resultado[0]["nombre_mp"];                
                $referencia_1 = $resultado[0]["referencia_pt"];
                if ($metodo_1 == "" || $metodo_1 == null){
                    $str_metodo = "PAGO #1 NO DEFINIDO";
                }
                else{
                    $str_metodo = "METODO: $metodo_1, REFERENCIA: $referencia_1";
                    $bandera_programa_bloqueado = true;
                }
                $str_metodos_pago.= $str_metodo;
                $metodo_2 = terapias::obtener_metodo_pago_parcial($resultado[0]["prt_id"]);
                $referencia_2 = $resultado[0]["referencia_pp"];
                if ($metodo_2 == "" || $metodo_2 == null || $metodo_2 == false){
                    $str_metodo_2 = "PAGO #2 NO DEFINIDO";
                }
                else{
                    $str_metodo_2 = "METODO: $metodo_2, REFERENCIA: $referencia_2";
                    $bandera_programa_bloqueado = true;
                }
                $str_metodos_pago.=", ".$str_metodo_2;
            }
            
            
            $json[0]["detalle_pago"] = $str_metodos_pago;
            
            if ($longitud<1){
                $json[0]['N'] = "No hay información que mostrar";
                $json[0]['Terapias'] = "";
                $json[0]['Fecha'] = "";
                $json[0]['Precio'] = "";
                $json[0]['Estado'] = "";
                $json[0]['Acciones']   = "";
            }
            for ($i=0; $i<$longitud; $i++){
                $str_a                  = "href=\"terapias.php?opcion=2&terapia=".$resultado[$i]["id_terapia"]."";
                $id_terapia = $resultado[$i]["id_terapia"];                
                $id_ptt     = $resultado[$i]["ptt_id"];
                //echo $resultado[$i]["estado_t"]." - estado";
                if ($resultado[$i]["estado_t"]=="pendiente"){//Se puede reservar la cita                    
                    $str_btn = "
                    <button title=\"Reservar\" 
                        class=\"btn btn-info\"  
                        onclick = \"seleccionar_terapia($id_ptt, 2)\"";                    
                    if($resultado[$i]["tipo_pago"]==3){//Si el tipo de pago es parcial
                        //Si el pago uno no se ha establecido                        
                        if ($i<$longitud/2){
                            if ($metodo_1 == "" || $metodo_1 == null || $metodo_1 == false){
                                $str_btn.=" disabled ";
                            }
                        }
                        else{
                            if ($metodo_2 == "" || $metodo_2 == null || $metodo_2 == false){
                               $str_btn.=" disabled ";
                            }
                        }                        
                    }
                    else if ($resultado[$i]["tipo_pago"]==4){
                        if ($metodo_1 == "" || $metodo_1 == null || $metodo_1 == false){
                            $str_btn.=" disabled ";
                        }
                    }
                    $str_btn.=">
                        <i class=\"fa fa-calendar\"               
                    ></i>
                    </button>";
                    if ($id_referer==1){//Agregar boton de eliminar terapia
                        $str_btn .= "
                        <button title=\"Eliminar terapia\" 
                            class=\"btn btn-danger\"
                            onclick=\"eliminar_terapia_modal(".$resultado[$i]["ptt_id"].",".$resultado[$i]["id_terapia"].")\"
                                ";
                        if ($bandera_programa_bloqueado){
                            $str_btn.=" disabled ";
                        }
                        $str_btn.=">
                            <i class=\"fa fa-times-circle\"></i>
                        </button>";
                    }
                    $bandera_validar_programa = false;
                }
                else if($resultado[$i]["estado_t"]=="pagado"){
                    $id_cita = citas::obtener_id_cita_de_terapia($id_terapia, $id_programa);
                    //Se puede modificar la cita que se habia reservado
                    $id_ptt =$resultado[$i]["ptt_id"];
                    $str_btn = "
                    <a title=\"Editar\" 
                        class=\"btn btn-info\"  
                        onclick = \"seleccionar_terapia($id_cita, 2, true, $id_ptt)\">
                        <i class=\"fa fa-edit\"></i>
                    </a>
                    
                    <a title=\"Eliminar\" 
                        class=\"btn btn-danger\"
                        onclick=\"modal_cita_terapia(".$resultado[$i]["id_rm"].",".$resultado[$i]["ptt_id"].")\">
                        <i class=\"fa fa-trash\"></i>
                    </a>

                    <a title=\"Ver Reporte\" 
                        class=\"btn btn-info\"
                        onclick=\"generar_invoice_individual(".$resultado[$i]["ptt_id"].")\">
                        <i class=\"fa fa-file\"></i>
                    </a>
                    
                            <a title=\"Validar terapia\" 
                        class=\"btn btn-success\"
                        onclick=\"validar_terapia(".$resultado[$i]["ptt_id"].", ".$resultado[$i]["id_terapia"].", ".$resultado[$i]["id_rm"].")\">
                        <i class=\"fa fa-check\"></i>
                    </a>";
                    //$bandera_validar_programa = false;
                }                
                else if ($resultado[$i]["estado_t"]=="anulado") {
                    $str_btn = "
                    <a title=\"CANCELADA, NO SE PUEDE MODIFICAR\" 
                        class=\"btn btn-danger\">
                        <i class=\"fa fa-edit\"></i>
                    </a>";
                    //$bandera_validar_programa = false;
                }
                else if ($resultado[$i]["estado_t"]=="atendida" || $resultado[$i]["estado_t"]==6) {
                    $str_btn = "
                    <a title=\"Ver Reporte\" 
                        class=\"btn btn-success\"
                        onclick=\"generar_invoice_individual(".$resultado[$i]["ptt_id"].")\">
                        <i class=\"fa fa-file\"></i>
                    </a>";
                }
                
                
                $str_btn.="
                    ";
                $json[$i]['N']          = ($i+1);
                $json[$i]['Terapias']   = strtoupper($resultado[$i]["nombre_t"]);
                $json[$i]['Precio']     = number_format($resultado[$i]["precio_t"],"0",",",".");
                $json[$i]['Estado']     = strtoupper($resultado[$i]["estado_t"]);                
                if ($resultado[$i]['fecha_t']== ""){
                    $json[$i]['Fecha']     = strtoupper("PENDIENTE");                    
                }
                else{
                    $json[$i]['Fecha']     = strtoupper(calendario::formatear_fecha(1,$resultado[$i]["fecha_t"]));  
                }                
                if ($resultado[0]["estado_pr"] == "deshabilitado"){
                    $json[$i]['Acciones']   = "No disponible";    
                }
                else{
                    $json[$i]['Acciones']   = $str_btn;    
                    if ($bandera_validar_programa ){//Creamos un boton para validar el programa completo
                    $str_btn_validar ="<a title=\"Validar programa completo\" 
                            class=\"btn btn-success\"
                            onclick=\"validar_programa(".$resultado[0]["prt_id"].")\">
                            <i class=\"fa fa-check\"></i>
                        </a>";
                    $json[0]["btn_validar_prg"]=$str_btn_validar;
                }                        
                }                

            }   
            
            //$json[1]['html'] = $str;
            return $json;
        }
        else{
            //$json[0]["estado"] = 1;
            $json[0]['N']           = "No se han agregado citas";
            $json[0]['Terapias']    = " ";
            $json[0]['Fecha']       = " ";
            $json[0]['Precio']      = " ";
            $json[0]['Estado']      = " ";
            $json[0]['Acciones']    = " ";
            return $json;
        }
    }
    public static function validar_terapia($id_programa){
        $bd = connection::getInstance()->getDb();
        $sql = "UPDATE programa_tiene_terapia
        SET estado=?
            WHERE id_programa_tiene_terapia = ".$id_programa;
        $pdo = $bd->prepare($sql);       
        //echo $sql;
        return $pdo->execute(array("6"));
    }
    
    public static function validar_programa($id_programa){
        $bd = connection::getInstance()->getDb();
        $sql = "UPDATE programa_terapeutico
        SET estado=?
            WHERE id_programa_terapeutico = ".$id_programa;
        $pdo = $bd->prepare($sql);        
        //echo "<br>".$sql;
        return $pdo->execute(array("culminado"));
    }
    
    public static function terapias_paciente ($id_paciente, $format = 'JSON', $solo_activas = false, $especial = false){
        $bd = connection::getInstance()->getDb();
        
        $sql = "SELECT 
            pt.id_programa_terapeutico              as id_programa, 
            pt.descripcion_programa_terapeutico     as desc_pt, 
            t.id_terapia                            as id_t, 
            t.nombre_terapia                        as nombre_t, 
            pt.estado                               as estado_pt
            FROM paciente p
                INNER JOIN programa_terapeutico pt ON pt.paciente_id_paciente=p.id_paciente
                LEFT  JOIN programa_tiene_terapia ptt ON ptt.programa_terapeutico_id_programa_terapeutico=pt.id_programa_terapeutico
                LEFT  JOIN terapia t ON ptt.terapia_id_terapia=t.id_terapia
                WHERE (pt.estado LIKE \"%activo%\" OR pt.estado)
                        AND p.id_paciente = ".$id_paciente." 
                        AND pt.especial <> true ";
        if ($solo_activas){
            $sql.= "    AND ptt.estado NOT LIKE \"anulado\" AND ptt.estado NOT LIKE \"atendida\"";
        }
        $pdo = $bd->prepare($sql);        
        //echo $sql;
        
        $pdo->execute();    
        $resultados = $pdo->fetchAll(PDO::FETCH_ASSOC);    
        $longitud = count($resultados);
        $json_retorno;
        $json_retorno[0]['estado']=0;
        $json_retorno[0]['sql']=$sql;
        $json_retorno[0]['filas']=$longitud;
        
        if ($longitud>0) 
        {
            $json_retorno[0]['estado_programa'] = $resultados[0]["estado_pt"] == "deshabilitado" ? 0 : 1 ;
            $json_retorno[0]['estado']          =   1;
            $json_retorno[0]['cantidad']        =   $longitud;
            $json_retorno[0]['desc_pt']         =   $resultados[0]["desc_pt"];
            $json_retorno[0]['id_programa']     =   $resultados[0]["id_programa"];
            
            for ($i=0; $i<$longitud; $i++){
                $json_retorno[$i+1]['id']   =   $resultados[$i]['id_t'];
                $json_retorno[$i+1]['text'] =   $resultados[$i]["nombre_t"];

            }         
            if ($format = 'array'){
                return $json_retorno;
            }
            else{
                $json_retorno[0]["estado"] = 1;                
            }
        }
        else{
            $json_retorno[0]['estado']=0;
        }
        return $json_retorno;
    }
    
    public static function lista_terapias_configurar(){
        $sql = "SELECT * FROM `terapia`";        
        $bd = connection::getInstance()->getDb();
        $pdo = $bd->prepare($sql);
        $pdo->execute();
        $json;
        $resultado = $pdo->fetchAll(PDO::FETCH_ASSOC);        
        if ($resultado){
            $longitud = count($resultado);
            //echo $longitud;
            $json[0]["estado"] = 1;
            $str="";
            if ($longitud<1){
                $json[0]['N'] = "No hay información que mostrar";
                $json[0]['Nombre'] = "";
                $json[0]['Descripcion'] = "";
                $json[0]['Precio'] = "";
                $json[0]['Acciones'] = "";

            }
            for ($i=0; $i<$longitud; $i++){
                //echo $resultado[$i]["id_terapia"]." - terapia";
                //if ($resultado[$i]["estado_terapia"] == "activa"){
                    $str_btn = "<a title=\"Eliminar\" onclick=\"modificar_terapia(".$resultado[$i]["id_terapia"].",1)\" class=\"btn btn-danger\" href=#><i class=\"fa fa-trash\"></i></a>";
                //}
                //else{
                //    $str_btn = "<a title=\"Habilitar\" onclick=\"modificar_terapia(".$resultado[$i]["id_terapia"].",2)\" class=\"btn btn-success\" href=#><i class=\"fa fa-edit\"></i></a>";
                //}
                $json[$i]['N'] = $i+1;
                $json[$i]['Nombre'] = $resultado[$i]["nombre_terapia"];
                $json[$i]['Descripcion'] = $resultado[$i]["descripcion_terapia"];
                $json[$i]['Precio'] = number_format($resultado[$i]["precio_terapia"],"0",",",".");
                //$json[$i]['Estado'] = $resultado[$i]["estado_terapia"];
                $json[$i]['Acciones'] = "
                    <a title=\"Editar\" class=\"btn btn-info\" href=\"terapias.php?opcion=2&terapia=".$resultado[$i]["id_terapia"]."\"><i class=\"fa fa-edit\"></i></a>
                    
                    ".$str_btn."
                    ";

            }   
           // $json[1]['html'] = $str;
            return $json;
        }
        else{
            $json[0]["estado"] = 1;
            return $json;
        }
    }
    
    public static function desvincular_cita($id_ptt){
        $bd = connection::getInstance()->getDb();
        $sql = "UPDATE programa_tiene_terapia
        SET estado=?, reserva_medica_id_rm=?
            WHERE id_programa_tiene_terapia = ".$id_ptt;
        $pdo = $bd->prepare($sql);        
        return $pdo->execute(array("pendiente",NULL));
    }
    
    public static function dehabilitar_programa($id_pt){
        $bd = connection::getInstance()->getDb();
        $sql = "UPDATE programa_terapeutico
        SET estado=?
            WHERE id_programa_terapeutico = ".$id_pt;
        $pdo = $bd->prepare($sql);        
        return $pdo->execute(array("deshabilitado"));
    }
    
    public static function eliminar_programa($id_pt){
        $bd = connection::getInstance()->getDb();
        $sql = "UPDATE programa_terapeutico
        SET estado=?
            WHERE id_programa_terapeutico = ".$id_pt;
        $pdo = $bd->prepare($sql);        
        return $pdo->execute(array("eliminado"));
    }
    
    public static function cancelar_terapia($id_terapia){
        $bd = connection::getInstance()->getDb();
        $sql = "UPDATE terapia
        SET estado_terapia=?
            WHERE id_terapia = ".$id_terapia;
        $pdo = $bd->prepare($sql);        
        return $pdo->execute(array("anulado"));
    }
    
    public static function cancelar_cita ($id_programa, $id_cita){
        $bd = connection::getInstance()->getDb();
        $sql = "UPDATE programa_tiene_terapia
        SET estado=?
            WHERE reserva_medica_id_rm = ".$id_cita." AND programa_terapeutico_id_programa_terapeutico = ".$id_programa;
        $pdo = $bd->prepare($sql);        
        return $pdo->execute(array("5"));
    }
    
    public static function habilitar_terapia($id_terapia){
        $bd = connection::getInstance()->getDb();
        $sql = "UPDATE terapia
        SET estado_terapia=?
            WHERE id_terapia = ".$id_terapia;
        $pdo = $bd->prepare($sql);        
        return $pdo->execute(array("activa"));
    }
    
    public static function habilitar_programa($id_programa){
        $bd = connection::getInstance()->getDb();
        $sql = "UPDATE programa_terapeutico
        SET estado=?
            WHERE id_programa_terapeutico= ".$id_programa;
        $pdo = $bd->prepare($sql);        
        return $pdo->execute(array("activo"));
    }
    
    public static function establecer_metodo_pago ($metodo, $referencia, $id_programa){
        $bd = connection::getInstance()->getDb();
        $sql = "UPDATE programa_terapeutico
        SET metodos_pago_id_mp=?, referencia=?
            WHERE id_programa_terapeutico = ".$id_programa;
        $pdo = $bd->prepare($sql);        
        return $pdo->execute(array($metodo, $referencia));
    }
    
    public static function agregar_pago_parcial($id_programa, $referencia, $metodo){
        $bd = connection::getInstance()->getDb();
        
        $consulta = "INSERT INTO pagos_parciales 
            (metodos_pago_id_mp, programa_terapeutico_id_programa_terapeutico, referencia)
            VALUES (?,?,?)";
       // echo $consulta;
        $comando = $bd->prepare($consulta);
        $resultado = $comando->execute(array($metodo, $id_programa, $referencia));
        
        if ($resultado){
            return true;
        }
        else{
            return false;
        }
    }
    
    
}
