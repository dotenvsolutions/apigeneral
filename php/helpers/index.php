<?php

    require_once('../../../vendor/autoload.php');
    use Firebase\JWT\JWT as JWT;
    use Firebase\JWT\Key;

    function codificar($data){
        $fila = [];
        foreach ($data as $key => $value) {
            $fila [$key] = utf8_encode($value);
        }
        return $fila;
    }

    function jwt($usuario,$email){
        $time = time();
        $token = [
            "iat"=>$time,
            "exp" =>  $time + (60*60*24),
            "data" => [
                "id" => $usuario,
                "email"=> $email,
            ]
        ];
        
        return $token;
    }

    function jwt_encode($token,$key) {
        $jwt = JWT::encode($token,$key,'HS256');
        return $jwt;
    }

    function jwt_decode($token,$key){
        $jwt = JWT::decode($token, new Key($key,'HS256'));
        return $jwt;
    }

    function webparams($empresa, $params) {
        $query="SELECT ";
        $index=0;
        foreach ($params as $i) {
            if($index>0)
                $query.=",";
            $query.="(SELECT parametro FROM web_ge_parametros WHERE empresa = '$empresa' AND codigo = '{$i}') p$i";
            $index++;
        }
        $res = odbc_exec($this->connect, $query);

        if (odbc_error()){
            return array(
                'success' => false,
                'msg' => 'No se pudo obtener los parámetros de configuración: '.substr(odbc_errormsg(), 35)
            );
        }  
        $data = odbc_fetch_array($res);
        $data = codificar($data);
         
        return ['success' => true,'data' => $data];
    }

    function params($empresa, $params) {
        $query="SELECT ";
        $index=0;
        foreach ($params as $i) {
            if($index>0)
                $query.=",";
            $query.="(SELECT parametro FROM ge_parametros WHERE empresa = '$empresa' AND codigo = '{$i}') p$i";
            $index++;
        }
        $res = odbc_exec($this->connect, $query);

        if (odbc_error()){
            return array(
                'success' => false,
                'msg' => 'No se pudo obtener los parámetros de configuración: '.substr(odbc_errormsg(), 35)
            );
        }  
        $data = odbc_fetch_array($res);
        $data = codificar($data);
         
        return array(
            'success' => true,
            'data' => $data
        );
    }