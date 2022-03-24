<?php
    require_once('../../helpers/index.php');

    $op = $_GET['op'];
    switch($op){
        case "getUser":getuser();break;
        default: "No ha especificado una ruta";break;
    }

    function getuser(){
        require_once '../../../connectdb.php';
        $sql = "SELECT * FROM ge_usuarios WHERE empresa = '001';";
        $res = odbc_exec($connect, $sql);
        if(odbc_num_rows($res)==0){
            echo json_encode(['success'=>false,'msg'=>'no obtuvo nada']);
        }else{
            $data = [];
            $res_jwt = jwt('perdoalex0121','perdoalex0121@gmail.com');
            
            echo json_encode(['success'=>true,'data'=>$res_jwt]);
        }
        odbc_close($connect);
    }

    /*function token($user,$email){
        
    }*/