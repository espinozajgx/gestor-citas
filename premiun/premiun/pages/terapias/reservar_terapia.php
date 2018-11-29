<?php
$etiqueta = "Reservar cita para terapia";
$id_terapia;
if (isset($_GET["terapia"])){//Si existe la variable cita, es porque vamos a modificar
    $etiqueta = "Moficar cita para terapia";
    $id_terapia = $_GET["terapia"];
}
?>
<link href="../vendor/select2/css/select2.min.css" rel="stylesheet" />
<script type="text/javascript">
    
    
    document.addEventListener('DOMContentLoaded', function() { // page is now ready...   
        
                
        
    });
</script>
<div class="row">
    <div class="col-lg-12">
        <h1 class="page-header"><?php echo $etiqueta; ?></h1>

    </div>
    <div class="col-lg-12">
       <a class="btn btn-sm btn-success shared" href="terapias.php?opcion=3" title="Regresar"><i class="fa fa-arrow-left fa-bg"></i></a>
    </div>
    <!-- /.col-lg-12 -->
</div>

<div class="row">
    <div class="form-group col-12 col-sm-12 col-md-12">
        <small><strong><label for=name_>RUT</label></strong></small>

        <div class="input-group">
            <input type="text" id="rut_paciente" class="form-control" placeholder="Ingresa el RUT del paciente" autocomplete="off">
          <span class="input-group-btn" >
              <button id="btn_buscar" class="btn btn-default" type="button" onclick="buscar_info_paciente()"><i class="fa fa-search"></i></button>
          </span>
        </div><!-- /input-group -->
        <div id="error_rut" class="text-danger" style="display:none">
            <i class="fa fa-exclamation"></i><small> Ingresa un RUT válido</small>
        </div>
        <input id="id_oculto" type="text" hidden="">
    </div>
    <div class="form-group col-6 col-sm-6 col-md-6">
        <small><strong><label for="name">Nombre</label></strong></small>
        <input type="text" class="form-control" id="name" placeholder="Nombre" value="<?php  //echo Usuarios::obtener_nombre($bd,$hash) ?>" readonly>
        <div id="error_name" class="text-danger" style="display:none">
            <i class="fa fa-exclamation"></i><small> Ingresa tu nombre</small>
        </div>
    </div>
    <div class="form-group col-6 col-sm-6 col-md-6">
        <small><strong><label for="last_name">Apellido</label></strong></small>
        <input type="text" class="form-control" id="last_name" placeholder="Apellido" value="<?php //echo Usuarios::obtener_apellido($bd,$hash); ?>" autocomplete="off" readonly>
        <div id="error_last_name" class="text-danger" style="display:none">
            <i class="fa fa-exclamation"></i><small> Ingresa tu apellido</small>
        </div>
    </div>    
   <div class="form-group col-6 col-sm-6 col-md-6">
        <?php $cond_iva = 1; //Usuarios::obtener_cond_iva($bd,$hash);                                             
        ?>
        <small><strong><label for="terapia">Programa terapeutico</label></strong></small>
        <select class="form-control" id="terapia" onchange="cargar_terapias()">
                <option value="-1">Seleccione un paciente</option>
            </select>
            <div id="error_iva" class="text-danger" style="display:none">
                <i class="fa fa-exclamation"></i><small> Campo Obligatorio</small>
            </div>
    </div>
    <div class="form-group col-6 col-sm-6 col-md-6">
        <?php $cond_iva = 1; //Usuarios::obtener_cond_iva($bd,$hash);                                             
        ?>
        <small><strong><label for="terapia">Terapia</label></strong></small>
            <select class="form-control" id="terapia_t">
                <option value="-1">Seleccione un programa</option>
            </select>
            <div id="error_iva" class="text-danger" style="display:none">
                <i class="fa fa-exclamation"></i><small> Campo Obligatorio</small>
            </div>
    </div>
    <div class="col-md-12 col-sm-12 col-xs-12 py-2 margin-bottom-20 pull-right text-right ">
        <button type="button" id="btnguardar" class="btn btn-info btn-cons" onclick="redirigir_terapia()">Proceder</button>
    </div>

</div>
            
<script type="text/javascript">
    
    function cargar_terapias(){
        $.post("terapias/terapias_controlador.php",
        {
            id_operacion: 9,
            id_pt: $("#terapia").val()
        },function (result){
            var respuesta = JSON.parse(result);
            if (respuesta[0].estado == 1){
                $("#terapia_t").html(respuesta[1].html); 
                
            }
        });
    }
    
    function obtener_terapias_paciente(){
        $.post("terapias/terapias_controlador.php",
        {
            id_operacion: 8,
            id_paciente: $("#id_oculto").val()
        }, function(result){
            var respuesta = JSON.parse(result);
            if (respuesta[0].estado == 1){
                $("#terapia").html(respuesta[1].html).trigger('change');                
            }
            else{
                alert ("ERROR FATAL");
            }
        });
    }
    
    function buscar_info_paciente(){
            if ($("#rut_paciente").val()==""){
                $("#error_rut").show(1500);
                $("#error_rut").hide(5000);
            }
            else{
                $.post("citas/citas_controlador.php",{
                    id_operacion: 1,
                    rut: $("#rut_paciente").val()},
                    function (result){
                        var json = JSON.parse(result);
                        
                        //alert (json[0].id_paciente);
                        if (json[0].estado == true){
                            $("#name").val(json[0].nombre);
                            $("#last_name").val(json[0].apellido);                            
                            $("#id_oculto").val(json[0].id_paciente);
                            obtener_terapias_paciente();
                        }
                        else{
                            $("#name").val("");
                            $("#last_name").val("");                            
                        }
                    }
                );
            }
            
        }
    function redirigir_terapia(){
        regex = /[a-zA-Z0-9]+/;
        bandera = true;
        if (!regex.test($("#name").val())){
            bandera = false;
            $("#error_rut").show(500);
            $("#error_rut").hide(5000);
            //alert ($("#name").val());
        }     
        if ($("#terapias").val()==""){
            bandera = false;
            alert ("Seleccione al menos un medico");
        }
        if (bandera){
           window.location = "agregar_citas.php?id_terapia="+$("#terapia_t").val()+"&programa="+$("#terapia").val()+"&rut="+$("#rut_paciente").val();
        }
    }
    
    
</script>
