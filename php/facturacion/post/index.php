<?php

    date_default_timezone_set('America/Guayaquil');
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization");
    header("Access-Control-Allow-Methods: POST, OPTIONS");
    header('Content-Type: application/json');
    require_once('../../helpers/index.php');

    $op = $_GET['op'];
    switch($op){
        case "createDocument":createDocument();break;
        default: "No ha especificado una ruta";break;
    }

    function createDocument(){
        require_once '../../../connectdb.php';
        odbc_autocommit($connect, FALSE); 
        $empresa = $_GET["e"];
        $data =  json_decode(file_get_contents("php://input"));
        $headers = apache_request_headers();
        $token = str_replace('Bearer ', '', $headers['Authorization']);
        $decode = jwt_decode($token,getenv('API_KEY'));
        print_r($decode);
    }
