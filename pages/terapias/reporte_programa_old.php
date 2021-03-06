<?php
require('../../vendor/fpdf/invoice.php');
require_once '../../assets/class/terapias.php';
require_once '../../assets/class/calendario.php';

$numero_invoice             =   1;
$fecha                      =   date("d/m/Y");
$id_paciente;
$id_programa;
if (isset($_GET["id_paciente"])){
    $id_paciente                =   $_GET["id_paciente"];   
    $id_programa = terapias::obtener_id_programa_paciente($id_paciente);
    $condicion = "WHERE programa_tiene_terapia.programa_terapeutico_id_programa_terapeutico =".$id_programa;
}
else if (isset ($_GET["individual"])){
    $id_reserva = $_GET["reserva"];
    $condicion = "WHERE rm.id_rm =".$id_reserva;
}
else if (isset ($_GET["reserva"])){
    $id_reserva = $_GET["reserva"];
    $condicion = "WHERE programa_tiene_terapia.id_programa_tiene_terapia =".$id_reserva;
}


$modo_pago                  =   "MODO DE PAGO";
$fecha_vencimiento          =   "02/01/2018";
$nombre_paciente            =   "Nombre_paciente";


$sql = "SELECT paciente.celular as celular_p, paciente.email as email_p, 
    rm.fecha_inicio as fecha_c, paciente.apellidop as apellido_p, 
    paciente.RUT as rut, paciente.nombre as nombre_p, 
    paciente.direccion as direccion_p,
    terapia.id_terapia as id_terapia, programa_tiene_terapia.estado as estado_t, 
    terapia.nombre_terapia as nombre_t, terapia.precio_terapia as precio_t, 
    terapia.id_terapia as id_t, programa_terapeutico.descripcion_programa_terapeutico as desc_p,
    programa_terapeutico.descuento as descuento_p, ep.nombre as nombre_ep
    FROM terapia 
    INNER JOIN programa_tiene_terapia ON terapia.id_terapia=programa_tiene_terapia.terapia_id_terapia 
    INNER JOIN programa_terapeutico ON programa_tiene_terapia.programa_terapeutico_id_programa_terapeutico = programa_terapeutico.id_programa_terapeutico 
    INNER JOIN paciente ON programa_terapeutico.paciente_id_paciente = paciente.id_paciente 
    INNER JOIN estatus_pago ep ON ep.id_ep=programa_terapeutico.estatus_pago_id_ep
    LEFT JOIN reserva_medica rm ON rm.id_rm=programa_tiene_terapia.reserva_medica_id_rm\n"
    .$condicion ;
$bd = connection::getInstance()->getDb();
//echo $sql;
$pdo = $bd->prepare($sql);
$pdo->execute();
$resultado = $pdo->fetchAll(PDO::FETCH_ASSOC);   

//echo "<br> paciente: $id_paciente, programa: $id_programa";

//FPDF
$pdf = new PDF_Invoice( 'P', 'mm', 'A4' );
//$pdf->
$pdf->AddPage();
$pdf->addSociete( utf8_decode("SALUDINTEGRAL"),
 utf8_decode("Nuestra dirección\n" .
                  "Calle\n".
                  "Otra dirección\n"));
$pdf->fact_dev( "SALUDINTEGRAL", "$numero_invoice" );
//$pdf->temporaire( "Devis temporaire" );
$pdf->addDate( "$fecha");
//$pdf->addClient($resultado[0]["rut"]);
$pdf->addPageNumber("1");
//$pdf->addClientAdresse(utf8_decode($resultado[0]["direccion_p"]));
//$pdf->addReglement("$modo_pago");
//$pdf->addEcheance("$fecha_vencimiento");
//$pdf->addNumTVA("FR888777666");
$pdf->addReference(utf8_decode("
NOMBRE: ".$resultado[0]["nombre_p"]." ".$resultado[0]["apellido_p"]."\n".
    "R.U.T: asd                     Teléfono: ".$resultado[0]["celular_p"].
        "\nEMAIL: ".$resultado[0]["email_p"].
        "\nDIRECCIÓN: ".$resultado[0]["direccion_p"].""));
$y = 90;
$pdf->SetXY(10, $y);
$pdf->Cell(0,10,'PAGO: '.$resultado[0]["nombre_ep"],0,0,'R');

$cols=array( "FECHA TERAPIA"    => 45,
             "DESCRIPCION"  => 88,
             "P. UNITARIO"=> 26,
             "SUB TOTAL" => 31);
$pdf->addCols( $cols);
$cols=array( "FECHA TERAPIA"    => "L",
             "DESCRIPCION"  => "L",
             "P. UNITARIO"      => "R",
             "SUB TOTAL" => "R");
$pdf->addLineFormat( $cols);
//$pdf->addLineFormat($cols);

$y    = 109;    
if ($resultado){
    $longitud = count($resultado);
    //echo $longitud;
    //$json[0]["estado"] = 1;
    $str="";
    if ($longitud<1){
        $line = array( "FECHA TERAPIA"    => "-",
                   "DESCRIPCION"  => "-",
                   "P. UNITARIO"      => "-",
                   "SUB TOTAL" => "-");
        $size = $pdf->addLine( $y, $line );
        $y   += $size + 3;
    }
    $subtotal = 0;
    for ($i = 0; $i<$longitud; $i++){
        $nombre_programa        = utf8_decode($resultado[$i]["desc_p"]);
        $descripcion_terapia    = utf8_decode($resultado[$i]["nombre_t"]);
        $precio_terapia         = utf8_decode($resultado[$i]["precio_t"]);
        $fecha_t                = $resultado[$i]["fecha_c"];
        if ($fecha_t==null){
            $fecha_t = " ";
        }
        else{
            $fecha_t = calendario::formatear_fecha(1, $fecha_t);
        }
        $subtotal += $precio_terapia;        
            $line = array( "FECHA TERAPIA"    => $fecha_t,
                   "DESCRIPCION"  => "$descripcion_terapia",
                   "P. UNITARIO"      => "$".number_format($precio_terapia,2)."",
                   "SUB TOTAL" => "$".number_format($subtotal,2)."");
        
        
        $size = $pdf->addLine( $y, $line );
        $y   += $size + 3;
    }
    $descuento = ($resultado[0]["descuento_p"]/100)*$subtotal;
    $max_altura = $pdf->GetPageHeight();
    $y = $max_altura - 67;
    $line = array( "FECHA TERAPIA"    => " ",
                   "DESCRIPCION"  => "DESCUENTO APLICADO AL PAGO DE CONTADO",
                   "P. UNITARIO"  =>number_format($resultado[0]["descuento_p"],2)."%",
                   "SUB TOTAL" =>" - $".number_format(($descuento),2));
    $size = $pdf->addLine( $y, $line );
    $y   += $size + 3;
    
    
    $line = array( "FECHA TERAPIA"    => " ",
                   "DESCRIPCION"  => " ",
                   "P. UNITARIO"      => " ",
                   "SUB TOTAL" => "TOTAL: $".number_format($subtotal-$descuento,2)."");
    $size = $pdf->addLine( $y, $line );
        
}

$pdf->SetFont('Arial','',10);
// Número de página
$pdf->SetXY(10, $y+10);
$pdf->Cell(0,10,'CENTRO DE TERAPIAS ALTERNATIVAS SALUD INTEGRAL',0,0,'L');

$pdf->SetXY(10, $y+14);
$pdf->Cell(0,10,'Santa Lucia 118 - Santiago',0,0,'L');

$pdf->SetXY(10, $y+18);
$pdf->Cell(0,10,'http://www.saludintegralcentro.cl',0,0,'L');

$pdf->SetXY($pdf->GetPageWidth()-25, $y+10);
$pdf->Cell(0,10,'Fono: 226328948',0,0,'R');

$pdf->SetXY($pdf->GetPageWidth()-25, $y+14);
$pdf->Cell(0,10,'Call Center:226328960',0,0,'R');

$pdf->SetXY($pdf->GetPageWidth()-25, $y+18);
$pdf->Cell(0,10,'saludintegralcentro@gmail.com',0,0,'R');

$y+=26;        
$pdf->Line(10, $y, $pdf->GetPageWidth()-10, $y);
$pdf->Line(10, $pdf->GetPageHeight()-10, $pdf->GetPageWidth()-10, $pdf->GetPageHeight()-10);
$y+=4;
$pdf->SetXY(15,$y);
$pdf->SetFont('Arial','',9);
$pdf->SetFillColor(150, 0, 20); 
$pdf->SetTextColor(255, 255, 255); //Color del texto: Negro
$pdf->MultiCell($pdf->GetPageWidth()-30,3,  utf8_decode("AVISO: Aquellas horas previamente agendadas y pagadas serán consideradas como realizadas en el caso de no asistir\n sin dar previo aviso, por lo cual recomendamos en el caso de no poder asistir, reagendar su hora al fono: 226328948 al menos con 12 horas de anticipación."),0,'L',true);



$pdf->Output();
?>

=======
<?php
// (c) Xavier Nicolay
// Exemple de génération de devis/facture PDF

require('../../vendor/fpdf/invoice.php');
require_once '../../assets/class/terapias.php';
require_once '../../assets/class/calendario.php';

$numero_invoice             =   1;
$fecha                      =   date("d/m/Y");
$id_paciente;
$id_programa;
if (isset($_GET["id_paciente"])){
    $id_paciente                =   $_GET["id_paciente"];   
    $id_programa = terapias::obtener_id_programa_paciente($id_paciente);
    $condicion = "WHERE programa_tiene_terapia.programa_terapeutico_id_programa_terapeutico =".$id_programa;
}
else if (isset ($_GET["individual"])){
    $id_reserva = $_GET["reserva"];
    $condicion = "WHERE rm.id_rm =".$id_reserva;
}
else if (isset ($_GET["reserva"])){
    $id_reserva = $_GET["reserva"];
    $condicion = "WHERE programa_tiene_terapia.id_programa_tiene_terapia =".$id_reserva;
}


$modo_pago                  =   "MODO DE PAGO";
$fecha_vencimiento          =   "02/01/2018";
$nombre_paciente            =   "Nombre_paciente";


$sql = "SELECT paciente.celular as celular_p, paciente.email as email_p, 
    rm.fecha_inicio as fecha_c, paciente.apellidop as apellido_p, 
    paciente.RUT as rut, paciente.nombre as nombre_p, 
    paciente.direccion as direccion_p,
    terapia.id_terapia as id_terapia, programa_tiene_terapia.estado as estado_t, 
    terapia.nombre_terapia as nombre_t, terapia.precio_terapia as precio_t, 
    terapia.id_terapia as id_t, programa_terapeutico.descripcion_programa_terapeutico as desc_p,
    programa_terapeutico.descuento as descuento_p, ep.nombre as nombre_ep
    FROM terapia 
    INNER JOIN programa_tiene_terapia ON terapia.id_terapia=programa_tiene_terapia.terapia_id_terapia 
    INNER JOIN programa_terapeutico ON programa_tiene_terapia.programa_terapeutico_id_programa_terapeutico = programa_terapeutico.id_programa_terapeutico 
    INNER JOIN paciente ON programa_terapeutico.paciente_id_paciente = paciente.id_paciente 
    INNER JOIN estatus_pago ep ON ep.id_ep=programa_terapeutico.estatus_pago_id_ep
    LEFT JOIN reserva_medica rm ON rm.id_rm=programa_tiene_terapia.reserva_medica_id_rm\n"
    .$condicion ;
$bd = connection::getInstance()->getDb();
//echo $sql;
$pdo = $bd->prepare($sql);
$pdo->execute();
$resultado = $pdo->fetchAll(PDO::FETCH_ASSOC);   

//echo "<br> paciente: $id_paciente, programa: $id_programa";

//FPDF
$pdf = new PDF_Invoice( 'P', 'mm', 'A4' );
//$pdf->
$pdf->AddPage();
$pdf->addSociete( utf8_decode("SALUDINTEGRAL"),
 utf8_decode("Nuestra dirección\n" .
                  "Calle\n".
                  "Otra dirección\n"));
$pdf->fact_dev( "SALUDINTEGRAL", "$numero_invoice" );
//$pdf->temporaire( "Devis temporaire" );
$pdf->addDate( "$fecha");
//$pdf->addClient($resultado[0]["rut"]);
$pdf->addPageNumber("1");
//$pdf->addClientAdresse(utf8_decode($resultado[0]["direccion_p"]));
//$pdf->addReglement("$modo_pago");
//$pdf->addEcheance("$fecha_vencimiento");
//$pdf->addNumTVA("FR888777666");
$pdf->addReference(utf8_decode("
NOMBRE: ".$resultado[0]["nombre_p"]." ".$resultado[0]["apellido_p"]."\n".
    "R.U.T: asd                     Teléfono: ".$resultado[0]["celular_p"].
        "\nEMAIL: ".$resultado[0]["email_p"].
        "\nDIRECCIÓN: ".$resultado[0]["direccion_p"].""));
$y = 90;
$pdf->SetXY(10, $y);
$pdf->Cell(0,10,'PAGO: '.$resultado[0]["nombre_ep"],0,0,'R');

$cols=array( "FECHA TERAPIA"    => 45,
             "DESCRIPCION"  => 88,
             "P. UNITARIO"=> 26,
             "SUB TOTAL" => 31);
$pdf->addCols( $cols);
$cols=array( "FECHA TERAPIA"    => "L",
             "DESCRIPCION"  => "L",
             "P. UNITARIO"      => "R",
             "SUB TOTAL" => "R");
$pdf->addLineFormat( $cols);
//$pdf->addLineFormat($cols);

$y    = 109;    
if ($resultado){
    $longitud = count($resultado);
    //echo $longitud;
    //$json[0]["estado"] = 1;
    $str="";
    if ($longitud<1){
        $line = array( "FECHA TERAPIA"    => "-",
                   "DESCRIPCION"  => "-",
                   "P. UNITARIO"      => "-",
                   "SUB TOTAL" => "-");
        $size = $pdf->addLine( $y, $line );
        $y   += $size + 3;
    }
    $subtotal = 0;
    for ($i = 0; $i<$longitud; $i++){
        $nombre_programa        = utf8_decode($resultado[$i]["desc_p"]);
        $descripcion_terapia    = utf8_decode($resultado[$i]["nombre_t"]);
        $precio_terapia         = utf8_decode($resultado[$i]["precio_t"]);
        $fecha_t                = $resultado[$i]["fecha_c"];
        if ($fecha_t==null){
            $fecha_t = " ";
        }
        else{
            $fecha_t = calendario::formatear_fecha(1, $fecha_t);
        }
        $subtotal += $precio_terapia;        
            $line = array( "FECHA TERAPIA"    => $fecha_t,
                   "DESCRIPCION"  => "$descripcion_terapia",
                   "P. UNITARIO"      => "$".number_format($precio_terapia,2)."",
                   "SUB TOTAL" => "$".number_format($subtotal,2)."");
        
        
        $size = $pdf->addLine( $y, $line );
        $y   += $size + 3;
    }
    $descuento = ($resultado[0]["descuento_p"]/100)*$subtotal;
    $max_altura = $pdf->GetPageHeight();
    $y = $max_altura - 67;
    $line = array( "FECHA TERAPIA"    => " ",
                   "DESCRIPCION"  => "DESCUENTO APLICADO AL PAGO DE CONTADO",
                   "P. UNITARIO"  =>number_format($resultado[0]["descuento_p"],2)."%",
                   "SUB TOTAL" =>" - $".number_format(($descuento),2));
    $size = $pdf->addLine( $y, $line );
    $y   += $size + 3;
    
    
    $line = array( "FECHA TERAPIA"    => " ",
                   "DESCRIPCION"  => " ",
                   "P. UNITARIO"      => " ",
                   "SUB TOTAL" => "TOTAL: $".number_format($subtotal-$descuento,2)."");
    $size = $pdf->addLine( $y, $line );
        
}

$pdf->SetFont('Arial','',10);
// Número de página
$pdf->SetXY(10, $y+10);
$pdf->Cell(0,10,'CENTRO DE TERAPIAS ALTERNATIVAS SALUD INTEGRAL',0,0,'L');

$pdf->SetXY(10, $y+14);
$pdf->Cell(0,10,'Santa Lucia 118 - Santiago',0,0,'L');

$pdf->SetXY(10, $y+18);
$pdf->Cell(0,10,'http://www.saludintegralcentro.cl',0,0,'L');

$pdf->SetXY($pdf->GetPageWidth()-25, $y+10);
$pdf->Cell(0,10,'Fono: 226328948',0,0,'R');

$pdf->SetXY($pdf->GetPageWidth()-25, $y+14);
$pdf->Cell(0,10,'Call Center:226328960',0,0,'R');

$pdf->SetXY($pdf->GetPageWidth()-25, $y+18);
$pdf->Cell(0,10,'saludintegralcentro@gmail.com',0,0,'R');

$y+=26;        
$pdf->Line(10, $y, $pdf->GetPageWidth()-10, $y);
$pdf->Line(10, $pdf->GetPageHeight()-10, $pdf->GetPageWidth()-10, $pdf->GetPageHeight()-10);
$y+=4;
$pdf->SetXY(15,$y);
$pdf->SetFont('Arial','',9);
$pdf->SetFillColor(150, 0, 20); 
$pdf->SetTextColor(255, 255, 255); //Color del texto: Negro
$pdf->MultiCell($pdf->GetPageWidth()-30,3,  utf8_decode("AVISO: Aquellas horas previamente agendadas y pagadas serán consideradas como realizadas en el caso de no asistir\n sin dar previo aviso, por lo cual recomendamos en el caso de no poder asistir, reagendar su hora al fono: 226328948 al menos con 12 horas de anticipación."),0,'L',true);



$pdf->Output();
