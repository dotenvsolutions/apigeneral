<?php 
     require_once('index.php');

    class putFacturacion {	
        private $connect;
   
        public function __construct($connect) 
        {
            $this->connect=$connect;
        }

        public function factura_compra($empresa, $data) {
			error_reporting(0);
	    	//$getOpciones=new getOpciones($this->connect);
	    	//$svcsFacturacion=new svcsFacturacion($this->connect,$empresa);
	    	//$webparams = webparams($empresa, [125,143],$this->connect);
	    	$documento = NuevoCodigoDecimal($this->connect, 'in_cabecera', 'documento', 'tipo', 'CP', $empresa);
		    $codigos_nuevos_productos = [];

		    //Verificar si ya existe una compra con ese proveedor y esa referencia
		    /*if (!empty($data->cabecera->referencia)){
		        $referencia = intval($data->cabecera->referencia);
		        $query="SELECT COUNT(*) n FROM in_cabecera WHERE empresa = '$empresa' AND tipo = 'CP' AND estacion = '{$data->cabecera->estacion}' AND punto = '{$data->cabecera->punto}' AND CAST(referencia AS NUMERIC) = $referencia AND pro_cli = '{$data->cabecera->proveedor->codigo}' AND  ISNUMERIC(referencia) = 1 ";
		        $res = odbc_exec($this->connect, $query);
		        $res = odbc_fetch_object($res);


		        if (is_null($res->n) || $res->n > 0 ){
		            return array(
		                'success'=> false, 
		                'msg'=> 'No se pudo guardar la Factura de Compra: Ya existe una en el sistema con el mismo proveedor, estación, punto y referencia'
		            );
		        }     
		    }*/
		    
            //print_r($data);return;
		    //Actualizar proveedor
		    $cod_rf = isset($data->movimiento[0]->cod_rf->codigo) ? $data->movimiento[0]->cod_rf->codigo : '';
		    $cod_ri = isset($data->movimiento[0]->cod_ri->codigo) ? $data->movimiento[0]->cod_ri->codigo : '';
		    $data->cabecera->proveedor->direccion1 = isset($data->cabecera->proveedor->direccion1) ? $data->cabecera->proveedor->direccion1 : '';
		    $data->cabecera->proveedor->telefono = isset($data->cabecera->proveedor->telefono) ? $data->cabecera->proveedor->telefono : '';
		    $query="UPDATE in_proveedor SET
		    nombre  = '{$data->cabecera->proveedor->nombre}',
		    direccion1 = '{$data->cabecera->proveedor->direccion1}',
		    telefono = '{$data->cabecera->proveedor->telefono}',
		    e_mail = '{$data->cabecera->proveedor->e_mail}'
		    WHERE codigo = '{$data->cabecera->proveedor->codigo}' AND empresa = '$empresa'";

		    odbc_exec($this->connect, utf8_decode($query));

		    if (odbc_error()){
		        return array(
		            'success'=> false, 
		            'msg'=> 'Ocurrió un error al actualizar el proveedor: '.substr(odbc_errormsg($this->connect),35)
		        );
		    } 



		    //Parametrizaciones
		    /*$query = "SELECT 
		    (SELECT parametro FROM web_ge_parametros WHERE empresa = '$empresa' AND codigo = 19) campo_retencion,
		    (SELECT parametro FROM web_ge_parametros WHERE empresa = '$empresa' AND codigo = 22) campo_tipo_comprobante,
		    (SELECT parametro FROM web_ge_parametros WHERE empresa = '$empresa' AND codigo = 23) campo_sustento_tributario,
		    (SELECT parametro FROM web_ge_parametros WHERE empresa = '$empresa' AND codigo = 64) actualizar_costo_con_descuento";
		    $res = odbc_exec($this->connect, $query);
		    $res = odbc_fetch_object($res);
		    $params = $res;
		    $campo_retencion = $res->campo_retencion;
		    $campo_tipo_comprobante = $res->campo_tipo_comprobante;
		    $campo_sustento_tributario = $res->campo_sustento_tributario;

		    if ($campo_retencion == 'S'){
		        $campo_retencion_fuente = 'contabiliza_tran';
		        $campo_retencion_iva = 'contabilidad';
		    }
		    else{
		        $campo_retencion_fuente = 'contabilidad';
		        $campo_retencion_iva = 'contabiliza_tran';
		    }*/
		    //Fin

		    $data->cabecera->caja = isset($data->cabecera->caja->codigo) ? $data->cabecera->caja->codigo : '';
		    //print_r($data->cabecera->caja);return;
			$data->cabecera->destino = isset($data->cabecera->destino->codigo) && !empty($data->cabecera->destino->codigo) ? "'" . $data->cabecera->destino->codigo . "'" : 'NULL';
		    $data->cabecera->sustento_tributario = isset($data->cabecera->sustento_tributario->codigo) && !empty($data->cabecera->sustento_tributario->codigo) ? "'".$data->cabecera->sustento_tributario->codigo."'" : "NULL";
		    $data->cabecera->tipo_comprobante = isset($data->cabecera->tipo_comprobante->codigo) && !empty($data->cabecera->tipo_comprobante->codigo) ? "'".$data->cabecera->tipo_comprobante->codigo."'" : "NULL";
		    $data->cabecera->compra_importada = isset($data->cabecera->compra_importada) && !empty($data->cabecera->compra_importada) ? "'{$data->cabecera->compra_importada}'" : "NULL";
		    $data->cabecera->orden_tipo = isset($data->cabecera->orden_tipo) && !empty($data->cabecera->orden_tipo) ? "'{$data->cabecera->orden_tipo}'" : "NULL";


		    $data->cabecera->fecha = isset($data->cabecera->fecha) ? "'".$data->cabecera->fecha."'" : "TODAY()";
		    $data->cabecera->fechav = isset($data->cabecera->fechav) ? "'".$data->cabecera->fechav."'" : "TODAY()";
		    $data->cabecera->stado = nullifempty($data->cabecera, 'stado');

		    $carga_compra = $data->cabecera->stado == "'X'" || false;
		    $stado = isset($data->cabecera->stado) ? "'L'" : "NULL";


		    //Verificar si la compra viene de una mercadería en tránsito para eliminarla
		    /*if ($data->cabecera->orden_tipo == "'CP'"){
		    	$query="DELETE FROM in_movimiento_proforma WHERE empresa = '$empresa' AND tipo = 'CP' AND documento = '{$data->cabecera->orden}';
		    	DELETE FROM cxc_auxiliar_proforma WHERE empresa = '$empresa' AND tipo = 'CP' AND documento = '{$data->cabecera->orden}';
		    	DELETE FROM in_cabecera_proforma WHERE empresa = '$empresa' AND tipo = 'CP' AND documento = '{$data->cabecera->orden}'
		    	";
		    	odbc_exec($this->connect, $query);
		    	if (odbc_error()){
			        return array(
			            'success'=> false, 
			            'msg'=> 'Ocurrió un error al dar de baja la mercadería en tránsito: '.substr(odbc_errormsg($this->connect),35)
			        );
			    }

			    $data->cabecera->compra_importada = "'S'";
		    }*/
		    //Fin
			$documento = NuevoCodigoDecimal($this->connect, 'in_cabecera', 'documento', 'tipo', 'CP', $empresa);
		    $query="INSERT INTO in_cabecera (tipo,documento,empresa,fecha,fechav,fecha_usuario,pro_cli,referencia,accion_usuario,
			estacion,punto,comentario,caja,retencion_iva,retencion_fuente,sustento_tributario,impuesto,seguro,vendedor,transporte,
			fecha_retencion,documento2) VALUES ('CP','{$documento}', '{$empresa}', {$data->cabecera->fecha},{$data->cabecera->fechav},
			{$data->cabecera->fecha},'{$data->cabecera->proveedor->codigo}', '{$data->cabecera->referencia}','{$data->cabecera->accion_usuario}',
			'{$data->cabecera->estacion}','{$data->cabecera->punto}','{$data->cabecera->comentario}','{$data->cabecera->caja}',
			NULL,NULL,{$data->cabecera->sustento_tributario},'{$data->cabecera->impuesto}',{$data->cabecera->seguro},NULL,0,NULL,
			{$data->cabecera->tipo_comprobante});";
            //print_r($query);return;
            odbc_exec($this->connect, utf8_decode($query));

		    if (odbc_error()){
		        return array(
		            'success'=> false, 
		            'msg'=> 'Ocurrió un error al guardar la cabecera de la factura: '.substr(odbc_errormsg($this->connect),35)
		        );
		    }   

		    $query="";
		    //$codigo_imei = NuevoCodigoDecimal($this->connect, 'in_movimiento_series', 'codigo',NULL, NULL, $empresa);



		    foreach ($data->movimiento as $i){
		    	//Conversion de medida
		    	/*$res = $svcsFacturacion->movimiento_conversion_medida($i);
		    	if (!$res['success'])
		    		return $res;
		    	else
		    		$i=$res['data'];*/
		    	//Fin

		    	/*if (isset($i->medida) && isset($i->medida->codigo) && !empty(trim($i->medida->codigo)))
		    		$i->medida = "'".$i->medida->codigo."'";
		    	else if (isset($i->producto->medida) && isset($i->producto->medida->codigo) && !empty(trim($i->producto->medida->codigo)))
		    		$i->medida = "'".$i->producto->medida->codigo."'";
		    	else
		    		$i->medida = "NULL";

		    	$i->imei = isset($i->imei) && $i->imei ? 'S' : 'N';
		        //Si la factura viene de una importacion de un archivo local, recorrer si existen productos que no existen para ser creados automaticos
		        if (isset($data->cabecera->stado) && $data->cabecera->stado == "'I'"){
		            $query_producto = "SELECT * FROM in_item WHERE empresa = '$empresa' AND codigo = '{$i->producto->codigo}'";
		            $res = odbc_exec($this->connect, $query_producto);
		            if (odbc_num_rows($res)==0){
		                $codigos_nuevos_productos[] = $i->producto->codigo;
		                $i->producto->descripcion1 = utf8_decode($i->producto->descripcion1);
		                $query_producto_nuevo="INSERT INTO in_item (codigo, descripcion1, empresa, grupo, medida, stock, costo, iva, itemb, calidad,imei)
		                VALUES ('{$i->producto->codigo}', '{$i->producto->descripcion1}', '$empresa', 1, 1, 'S', '$i->valor', '$i->impuesto', 'S', 'G',{$i->imei})";
		                odbc_exec($this->connect, $query_producto_nuevo);
		                if (odbc_error()){
		                    return array(
		                        'success'=>false, 
		                        'msg'=>'Ocurrió un error al generar el nuevo producto: '.substr(odbc_errormsg($this->connect),35)
		                    );
		                }
		            }
		        }*/
		        //Fin



		        //Si la factura viene de una carga de compra, recorrer si existen productos no asignados para ingresarlos automaticamente al catalogo de productos
		        /*if ($carga_compra){
		            if (!isset($i->producto->codigo) || empty($i->producto->codigo)){
		                $i->producto->codigo = NuevoCodigoDecimal($this->connect, 'in_item', 'codigo',NULL, NULL, $empresa);
		                $codigos_nuevos_productos[] = $i->producto->codigo;
		                $query_producto_nuevo="INSERT INTO in_item (codigo, descripcion1, empresa, grupo, medida, stock, costo, iva, itemb, calidad)
		                VALUES ('{$i->producto->codigo}', '{$i->producto->descripcion_xml}', '$empresa', 1, 1, 'S', '$i->valor', '$i->impuesto', 'S', 'G')";
		                odbc_exec($this->connect, utf8_encode($query_producto_nuevo));
		                if (odbc_error()){
		                   return array(
		                        'success'=>false, 
		                        'msg'=>'Ocurrió un error al generar el nuevo producto: '.substr(odbc_errormsg($this->connect),35)
		                    );
		                }
		            }

		            if (isset($i->producto->codigo_xml) && !empty($i->producto->codigo_xml)){
		                $query_producto_nuevo="DELETE FROM ce_item_proveedor WHERE empresa = '$empresa' AND proveedor = '{$data->cabecera->proveedor->codigo}' AND item_xml = '{$i->producto->codigo_xml}';
		                INSERT INTO ce_item_proveedor (item, proveedor, empresa, item_xml) VALUES ('{$i->producto->codigo}', '{$data->cabecera->proveedor->codigo}', '$empresa', '{$i->producto->codigo_xml}')";
		                odbc_exec($this->connect, $query_producto_nuevo);
		                if (odbc_error()){
		                    return array(
		                        'success'=>false, 
		                        'msg'=>'Ocurrió un error al asignar el producto al proveedor: '.substr(odbc_errormsg($this->connect),35)
		                    );
		                }
		            }
		        }*/
		        //Fin
		        $i->cod_rf = isset($i->cod_rf->codigo) ? $i->cod_rf->codigo : '';
		        $i->cod_ri = isset($i->cod_ri->codigo) ? $i->cod_ri->codigo : '';
		        $i->codigo_concepto_retencion = isset($i->codigo_concepto_retencion->codigo) ? $i->codigo_concepto_retencion->codigo : '';
		        $i->proyecto = isset($i->proyecto->codigo) && !empty($i->proyecto->codigo) ? "'" . $i->proyecto->codigo . "'" : 'NULL';
		        $i->codrubro = isset($i->rubro->cod_presupuesto) && !empty($i->rubro->cod_presupuesto) ? "'" . $i->rubro->cod_presupuesto . "'" : 'NULL';
		        $i->rubro = isset($i->rubro->cod_formato) && !empty($i->rubro->cod_formato) ? "'" . $i->rubro->cod_formato . "'" : 'NULL';
		        $i->clase = isset($i->clase->codigo) && !empty($i->clase->codigo) ? "'" . $i->clase->codigo . "'" : 'NULL';		        
		        $i->componente = isset($i->componente->cod_presupuesto) && !empty($i->componente->cod_presupuesto) ? "'" . $i->componente->cod_presupuesto . "'" : 'NULL';
		        $i->capitulo = isset($i->capitulo->cod_presupuesto) && !empty($i->capitulo->cod_presupuesto) ? "'" . $i->capitulo->cod_presupuesto . "'" : 'NULL';

		        //Campos de exportacion
		        $i->cif = isset($i->cif) ? $i->cif : 0;
		        $i->arancel  = isset($i->arancel ) ? $i->arancel  : 0;
		        $i->fodinfa  = isset($i->fodinfa ) ? $i->fodinfa  : 0;
		        $i->otros  = isset($i->otros ) ? $i->otros  : 0;
		        //Fin

		        
		        /*if($webparams['data']['p125']=='S' && !empty($i->serie)){
		        	$sql = "SELECT * FROM in_movimiento WHERE empresa = '{$empresa}' AND serie = '{$i->serie}' AND tipo = 'FC';";
		        	$res_serie = odbc_exec($this->connect, $sql);
		        	if(odbc_num_rows($res_serie)>0){
		        		return array(
		        			'success'=>false,
		        			'msg'=>'Numero de Serie ya previamente Registrado'
		        		);
		        	}

		        }*/


		        $movimiento = "INSERT INTO in_movimiento (empresa, tipo, documento, cantidad, valor, descuento, impuesto, producto,
				costo, ubicacion, cod_rf, cod_ri, codigo_concepto_retencion, bonificacion)
		        VALUES ('{$empresa}', 'CP','{$documento}', '$i->cantidad','$i->valor','$i->descuento', '$i->impuesto', 
				'{$i->producto->codigo}', '$i->valor', '{$i->ubicacion->codigo}', '{$i->cod_rf}', '{$i->cod_ri}', 
				'{$i->codigo_concepto_retencion}', 0);
		        /*UPDATE in_item SET proveedor = '{$data->cabecera->proveedor->codigo}' WHERE empresa = '{$empresa}'  AND codigo = '{$i->producto->codigo}';*/";
		        //print_r($movimiento);return;
				odbc_exec($this->connect, $movimiento);	
		        if(odbc_error()){
			       	return array(
			            'success'=>false, 
			            'msg'=>'Ocurrió un error al guardar los productos de la factura: '.substr(odbc_errormsg($this->connect),35)
			        );
			    }
		        /*if($webparams['data']['p143'] == 'S')
				{
					if(isset($i->series))
					{
						if(count($i->series)>0){
							$mv = "SELECT @@IDENTITY AS movimiento;";
							$result = odbc_exec($this->connect, $mv);
							$movimiento = odbc_fetch_array($result)['movimiento'];

							foreach($i->series as $serie)
							{
								$numeral = numeracionSeries($this->connect,'in_movimiento','in_movimiento_series','numeral','CP',$empresa);
								
								$select = "SELECT count(serie) veces FROM in_movimiento_series 
								WHERE empresa = '{$empresa}' AND serie = '{$serie->serie}';";
								
								$resp = odbc_exec($this->connect, $select);
								$veces = intval(odbc_fetch_array($resp)['veces']);

								if($veces > 0)
								{
									return [
										'success'=>false,
										'msg' => 'Número de serie previamente registrado '.$serie->serie
									];
								}

								$sql = "INSERT INTO in_movimiento_series (empresa,codigo,movimiento,serie,numeral,created_at) VALUES (?,?,?,?,?,?);";
								$options = [
									'empresa'=>$empresa,
									'codigo'=>$codigo_imei,
									'movimiento'=> $movimiento,
									'serie' => $serie->serie,
									'numeral' => $numeral,
									'created_at' => date('Y-m-d H:m:s') 
								];
								$prepare = odbc_prepare($this->connect, $sql);
								$execute = odbc_execute($prepare,$options);
								if(!$execute || odbc_error())
								{
									return array(
										'success'=>false, 
										'msg'=>'No se pudieron añadir las series deseadas: '.substr(odbc_errormsg($this->connect),35)
									);
								}
								$codigo_imei++;

							}
						}else{
							continue;
						}
					}else{
						continue;
					}
				}*/

				/*$query = "";
		        /*if (isset($data->options->actualizar_costo) && $data->options->actualizar_costo){
		        	$valor = $i->impuesto > 0 ? ($i->total / $i->cantidad) / 1.12 : ($i->total / $i->cantidad);
		            $query.= "UPDATE in_item SET costo = '$valor' WHERE codigo = '{$i->producto->codigo}' AND empresa = '$empresa';";
		        }*/
		        /*if (isset($data->options->actualizar_precio) && $data->options->actualizar_precio)
		            $query .= "UPDATE in_item SET 
		            pvp1 = '{$i->producto->pvp1}',
		            pvp2 = '{$i->producto->pvp2}', 
		            pvp3 = '{$i->producto->pvp3}', 
		            pvp4 = '{$i->producto->pvp4}', 
		            pvp5 = '{$i->producto->pvp5}', 
		            pvp6 = '{$i->producto->pvp6}', 
		            por1 = '{$i->producto->por1}', 
		            por2 = '{$i->producto->por2}', 
		            por3 = '{$i->producto->por3}', 
		            por4 = '{$i->producto->por4}', 
		            por5 = '{$i->producto->por5}', 
		            por6 = '{$i->producto->por6}'
		            WHERE codigo = '{$i->producto->codigo}' AND empresa = '$empresa';";	*/	

		       	if(!empty($query)) odbc_exec($this->connect, utf8_decode($query));				
		    }

		    

		    /*$query ="";
		    foreach ($data->pago as $i){
		        $i->banco = (isset($i->banco->secuencia)) ? $i->banco->secuencia : '';
		        $query .="INSERT INTO cxc_auxiliar (documento,tipo,forma_pago,fechae,fechav,valor,empresa,entidad,banco,cuenta,numero,observacion, retencion, ret_iva, descuento) 
		        VALUES ('$documento', 'CP','{$i->forma_pago->secuencia}', '$i->fechae', '$i->fechav', $i->valor, '$empresa', '$i->entidad', '{$i->banco}','$i->cuenta','{$i->numero}', '$i->observacion', $i->retencion, $i->ret_iva, $i->descuento);";
		        odbc_exec($this->connect, $query);		
			    if(odbc_error()){
			        return array(
			            'success'=>false, 
			            'msg'=>'Ocurrió un error al guardar los pagos de la factura: '.substr(odbc_errormsg($this->connect),35)
			        );
			    }
		    }*/
	   	


		    return array(
		        'success'=>true, 
		        'msg'=>'Factura de compra registrada exitosamente!',
		        'data' => array(
		            'documento' => $documento,
		            //'codigos_nuevos_productos' => $codigos_nuevos_productos
		        )
		    ); 

	    }
    }