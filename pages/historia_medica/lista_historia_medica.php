<div class="col-12 px-5">
	<table width="100%" class="table table-striped table-bordered table-hover" id="dataTables-example">
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Historial </th>
                <th>Indicaciones Generales</th>
                <th>Indicaciones</th>
                <th>Paciente</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tfoot>
            <tr>
                <th>Fecha</th>
                <th>Historial </th>
                <th>Indicaciones Generales</th>
                <th>Indicaciones</th>
                <th>Paciente</th>
                <th>Acciones</th>
            </tr>
        </tfoot>
        <tbody>


		<?php
            $datos = Pacientes::obtener_lista_historias($bd);
            $lista = "";

            if($datos)
            foreach ($datos as $historia) {
                $des = "";
                $hab = "";
                $indicaciones =  strtoupper(substr($historia["indicaciones"],0,50));
                $diagnostico =  strtoupper(substr($historia["diagnostico"],0,50));
                $diagnostico_general =  strtoupper(substr($historia["descripcion"],0,50));

                $nombre = $historia["nombre"];
                $nombre .= " " . $historia["apellidop"];
                $nombre .= " " . $historia["apellidom"];



                //$hash_usuario =  $historia["hash"];
                $cantidad = 1; //  Usuarios::obtener_cant_publicaciones_by_user($bd, $hash_usuario);

                $lista .= ' <tr id='.$historia['id_hm'].'>
                            <td class="center text-center pull-center" style="width: 15%">'. $historia["fecha"] .'</td>
                            <td>'. $diagnostico_general .'</td>
                            <td>'. $diagnostico .'</td>
                            <td>'. $indicaciones .'</td>
                            <td>'. strtoupper($nombre) .'</td>
                            <td class="center text-center pull-center" style="width: 20%">
                                
                                <a class="btn btn-sm btn-info shared" href="historia_medica/historia_paciente.php?id_hm='.  $historia['id_hm'] .'&tipo=1" target="_Blank" title="Descargar Historial"><i class="fa fa-download"></i></a>
                                <a class="btn btn-sm btn-warning shared" href="historia_medica/historia_paciente.php?id_hm='.  $historia['id_hm'] .'&tipo=2" target="_Blank" title="Descargar Indicaciones Generales"><i class="fa fa-download"></i></a>
                                <a class="btn btn-sm btn-success shared" href="historia_medica/historia_paciente.php?id_hm='.  $historia['id_hm'] .'&tipo=3" target="_Blank" title="Descargar Indicaciones"><i class="fa fa-download"></i></a>
                                <btn class="btn btn-sm btn-danger delete" cod="'.  $historia['id_hm'] .'" title="Eliminar"><i class="fa fa-trash"></i></btn>
                            </td>
                        </tr>';

            }
            echo $lista;/**/
        ?>

        </tbody>
     </table>
    <!-- /.table-responsive -->
</div>