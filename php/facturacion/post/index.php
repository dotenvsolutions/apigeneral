<?php

    date_default_timezone_set('America/Guayaquil');
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization");
    header("Access-Control-Allow-Methods: POST, OPTIONS");
    header('Content-Type: application/json');
    require_once('../../helpers/index.php');
    require_once('../../helpers/facturacion.php');

    $op = $_GET['op'];
    switch($op){
        case "createDocument":createDocument();break;
        default: "No ha especificado una ruta";break;
    }

    function createDocument(){
        require_once '../../../connectdb.php';
        odbc_autocommit($connect, FALSE); 
        $empresa = $_GET["e"];
        $proveedor = [];
        $param = params($empresa,[228],$connect);
        $codigo = NuevoCodigoDecimal($connect,'in_proveedor','codigo',NULL,NULL,$empresa);
        $putFacturacion = new putFacturacion($connect);
        $data =  json_decode(file_get_contents("php://input"));
        $headers = apache_request_headers();
        $token = str_replace('Bearer ', '', $headers['Authorization']);
        $decode = jwt_decode($token,getenv('API_KEY'));
        $sql = "SELECT TOP 1 * FROM in_proveedor WHERE empresa = '{$empresa}' AND cedula = '{$data->ruc}'";
        $res = odbc_exec($connect, $sql);
        if(odbc_num_rows($res)==0){
            $sql = "SELECT TOP 1 * FROM in_cliente WHERE empresa = '{$empresa}' AND cedula_ruc = '{$data->ruc}';";
            $res_1 = odbc_exec($connect, $sql);
            if(odbc_num_rows($res_1)==0){
                $proveedor = [];
            }else{
                while($row = odbc_fetch_array($res_1)){
                    $row = codificar($row);
                    $proveedor = $row;
                }
            }
            $codigo = NuevoCodigoDecimal($connect,'in_proveedor','codigo',NULL,NULL,$empresa);
            $direccion = is_null($proveedor['direccion1']) || empty($proveedor['direccion1']) ? 'NULL' : $proveedor['direccion1'];
            $telefono = is_null($proveedor['telefono']) || empty($proveedor['telefono']) ? 'NULL' : $proveedor['telefono'];
            $e_mail = is_null($proveedor['e_mail']) || empty($proveedor['e_mail']) ? 'NULL' : $proveedor['e_mail'];
            $insert = "INSERT INTO in_proveedor (codigo, nombre, cedula, direccion1, empresa, telefono, e_mail) 
            VALUES ('{$codigo}','{$proveedor['nombre']}','{$proveedor['cedula_ruc']}',{$direccion},{$empresa},{$telefono},{$e_mail});";
            $exec = odbc_exec($connect, $insert);
            if($exec || odbc_error()){
                odbc_rollback($connect);
                echo json_encode(['success'=>false,'msg'=>'Error: '.substr(odbc_errormsg(), 35)]);
            } 
            $sql2 = "SELECT TOP 1 * FROM in_proveedor WHERE empresa = '{$empresa}' AND cedula = '{$data->ruc}'";
            $res2 = odbc_exec($connect, $sql2);
            $dt = odbc_fetch_array($res2);
            $proveedor = $dt;
        }else{
            $dt = odbc_fetch_array($res);
            $proveedor = $dt;
        }
        
        $factura = [
            'cabecera'=> [
                'referencia' => $data->cabecera->referencia,
                'estacion' => $data->cabecera->estacion,
                'punto' => $data->cabecera->punto,
                'proveedor' => $proveedor,
                'accion_usuario' => isset($data->cabecera->autorizacion) && !empty($data->cabecera->autorizacion) ? $data->cabecera->autorizacion : $data->cabecera->ce_clave_acceso,
                'retencion_iva' => 'N',
                'retencion_fuente' => 'N',
                'caja'=>['codigo'=>$data->cabecera->caja->codigo],
                'comentario' => $data->cabecera->comentario,
                'sustento_tributario' => ['codigo' => '01'],
                'impuesto' => 0,
                'fob' => 0,
                'seguro' => 0,
                'flete' => 0,
                'otros' => 0,
                'orden' => '',
                'fecha' => $data->cabecera->fecha,                
                'fechav' => $data->cabecera->fechav
            ],
            'movimiento'=> [],
            'pago' => []
        ];

        if(count($data->movimiento)>0){
            foreach($data->movimiento as $k){
                $factura['movimiento'][] = [
                    'producto' => [
                        'codigo' => $k->producto->codigo
                    ],
                    'cantidad' => 1,
                    'valor' => $k->valor,
                    'descuento' => 0,
                    'impuesto' => 0,
                    'ubicacion' => array(
                        'codigo' => '1'
                    ),
                    'cod_rf' => array(),
                    'cod_ri' => array(),
                    'codigo_concepto_retencion' => array(),
                    'proyecto' => array(),
                    'codrubro' => array(),
                    'rubro' => array(),
                    'clase' => array(),
                    'componente' => array(),
                    'capitulo' => array(),
                    'serie' => '',
                ];
            }
        }
        $factura = json_decode(json_encode($factura), FALSE);
        $res = $putFacturacion->factura_compra($empresa, $factura);
        if(!$res['success'])
            odbc_rollback($connect);
        else
            odbc_commit($connect);
        //print_r($factura);return;
    }
