<?php
    date_default_timezone_set('America/Guayaquil');
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization");
    header("Access-Control-Allow-Methods: POST, OPTIONS, PUT");
    header('Content-Type: application/json');
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
        $query = "SELECT * FROM ge_usuarios WHERE usuario = '{$data->user}' AND clave = '{$data->pass}' AND empresa = '{$empresa}'";
        $res = odbc_exec($connect,$query);
        if(odbc_num_rows($res)==0){
            echo json_encode(['success'=>false,'msg'=>'Usuario no encontrado/registrado']);
            return;
        }else{
            $dt = [];
            while($row = odbc_fetch_array($res)){
                $row = codificar($row);
                $dt = $row;
            }
            //print_r($row);return;
            if($dt['usuario']==$data->user && $dt['clave']==$data->pass){
                $getToken = jwt($dt['usuario'],$dt['email']);
                $generateToken = jwt_encode($getToken,getenv('API_KEY'));
                $query = "UPDATE ge_usuarios SET token_user = '{$generateToken}',token_exp='{$getToken['exp']}' 
                WHERE empresa = '{$empresa}' AND usuario = '{$dt['usuario']}'";
                $res = odbc_exec($connect,$query);
                if(!$res || odbc_error()){
                    echo json_encode(['success'=>false,'msg'=>'No se ha podido actualizar informacion correctamente']);
                    return;
                }else{
                    odbc_commit($connect);
                    echo json_encode(['success'=>true,'token'=>$generateToken]);
                }
            } else {
                echo json_encode(['success'=>false,'msg'=>'Usuario o clave incorrectos']);
                return;
            }
        }
        odbc_close($connect);
    }