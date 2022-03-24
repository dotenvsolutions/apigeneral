<?php
    require_once('../../helpers/index.php');

    $op = $_GET['op'];
    switch($op){
        case "checkUser":checkUser();break;
        default: "No ha especificado una ruta";break;
    }

    function checkUser(){
        require_once '../../../connectdb.php';
        odbc_autocommit($connect, FALSE); 
        $empresa = $_GET["e"];
        $data =  json_decode(file_get_contents("php://input"));
        $query = "SELECT * usuario FROM ge_usuarios WHERE usuario = '{$data->usuario}' AND clave = '{$data->pass}' AND empresa = '{$empresa}'";
        $res = odbc_exec($connect,$query);
        if(odbc_num_rows($res)==0){
            echo json_encode(['success'=>false,'msg'=>'Usuario no encontrado/registrado']);
        }else{
            $dt = [];
            while($row = odbc_fetch_array($res)){
                $row = codificar($row);
                $dt = $row;
            }
            if($row['usuario']==$data->usuario && $row['clave']==$data->pass){
                $getToken = jwt($row['usuario'],$row['email']);
                $generateToken = jwt_encode($getToken,getenv('API_KEY'));
                echo json_encode(['success'=>true,'data'=>$generateToken]);
            } else {
                echo json_encode(['success'=>false,'msg'=>'Usuario o clave incorrectos']);
            }
        }
    }