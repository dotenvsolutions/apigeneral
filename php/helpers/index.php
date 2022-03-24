<?php

    require_once('../../../vendor/autoload.php');
    use Firebase\JWT\JWT as JWT;

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
                "email"=> $email
            ]
        ];
        $jwt = JWT::encode($token,'mEicK{O{kIuNdv{I)h;(.~l|&.o)FG');
        return $jwt;
    }