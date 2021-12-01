<?php
    require "./config/db.php";

    $query = "SELECT * FROM usuario";
    $resultado = $db->query($query);
    
    $data = array();

    //while($row = $resultado->fetch_assoc()){
    //    $data[] = $row;
    //}

    //header('Content-type: application/json');
    //echo json_encode($data);
    
    $failed = array('Error' => 'Failed');
    $consultaGet = isset($_GET['consulta']);
    $insertPost = isset($_POST['agregar']);
    $entradaPost = isset($_POST['entrada']);
    $salidaPost = isset($_POST['salida']);

    if($consultaGet && $_GET['consulta'] == 'busqueda')
    {
        if(isset($_GET['placa']))
        {
            $plate = $_GET['placa'];
            $stmt = $db->query("SELECT * FROM vehiculo WHERE placa = \"".$plate."\"");
            if($stmt){
                if($stmt -> num_rows > 0){
                    $data = array();
                    while($row = $stmt->fetch_assoc()){
                        $data[] = $row;
                    }
                    header('Content-type: application/json');
                    echo json_encode($data);
                }else{
                    header('Content-type: application/json');
                    $failed = array('Error' => 'No se encontro la placa.');
                    echo json_encode($failed);
                }
            }else{
                echo json_encode($failed);
            }
            
        }
    }elseif($insertPost && $_POST['agregar'] == 'agregarVehiculo'){
        $nombreUser = $_POST['nombreUser'];
        $stmt = $db->query("SELECT idusuario FROM usuario WHERE nombre = \"".$nombreUser."\"");
        $userFound = false;
        $idUsuario = 0;
        if($stmt){
            if($stmt -> num_rows > 0){
                $idUsuario = $stmt->fetch_assoc()['idusuario'];
                $userFound = true;
            }else{
                $failed = array('Error' => 'No se encontro el usuario');
                header('Content-type: application/json');
                echo json_encode($failed);
            }
        }else{
            header('Content-type: application/json');
            echo json_encode($failed);
        }

        if($userFound){
            $stmt = $db->prepare("INSERT INTO vehiculo (placa, idtipo, idusuario, color, modelo, estado) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("siissi", $_POST['placa'], $_POST['idtipo'], $idUsuario, $_POST['color'], $_POST['modelo'], $_POST['estado']);
            if($stmt->execute()){
                $success = array('status' => 'exito');
                header('Content-type: application/json');
                echo json_encode($success);
            }else{
                header('Content-type: application/json');
                $failed = array('error' => $stmt->error);
                echo json_encode($failed);
            }
            
        }
    }elseif($entradaPost && $_POST['entrada'] == 'registrarentrada'){
        $placa = $_POST['placa'];
        $stmt = $db->prepare("UPDATE vehiculo SET estado = 1 WHERE placa = \"".$placa."\"");
        if($stmt->execute()){
            $stmt = $db->query("SELECT idVehiculo, idUsuario FROM vehiculo WHERE placa = \"".$placa."\"");
            $array = array();
            $array[] = $stmt->fetch_assoc();
            $idVehiculo = $array[0]['idVehiculo'];
            $idUsuario = $array[0]['idUsuario'];
            $stmt = $db->query("SELECT MAX(idTicket) FROM ticket");
            if($stmt){
                if($stmt->num_rows > 0){
                    $idTicket = $stmt->fetch_assoc()['MAX(idTicket)'];
                    $idTicket = $idTicket+1;
                }else{
                    $idTicket = 0;
                }
                $stmt = $db->prepare("INSERT INTO ticket (idTicket, idVehiculo, idUsuario) VALUES (?, ?, ?)");
                $stmt->bind_param("iii", $idTicket, $idVehiculo, $idUsuario);
                if($stmt->execute()){
                    $stmt = $db->prepare("INSERT INTO detalleTicket (idticket, entrada, salida) VALUES (?, now(), now())");
                    $stmt->bind_param("i", $idTicket);
                    if($stmt->execute()){
                        header('Content-type: application/json');
                        $status = array('status' => 'Exito');
                        echo json_encode($status);
                    }else{
                        header('Content-type: application/json');
                        $failed = array('error' => $stmt->error);
                        echo json_encode($failed);
                    }
                }else{
                    header('Content-type: application/json');
                    $failed = array('error' => $stmt->error);
                    echo json_encode($failed);
                }
            }
        }else{
            header('Content-type: application/json');
            $failed = array('error' => 'No se encontro el vehiculo');
            echo json_encode($failed);
        }
    }elseif($salidaPost && $_POST['salida'] == 'registrarsalida'){
        $placa = $_POST['placa'];
        $stmt = $db->prepare("UPDATE vehiculo SET estado = 0 WHERE placa = \"".$placa."\"");
        if($stmt->execute()){
            $stmt = $db->query("SELECT idVehiculo FROM vehiculo WHERE placa = \"".$placa."\"");
            $idVehiculo = $stmt->fetch_assoc()['idVehiculo'];
            $stmt = $db->query("SELECT MAX(idTicket) FROM ticket WHERE idVehiculo = ".$idVehiculo);
            if($stmt){
                if($stmt->num_rows > 0){
                    $idTicket = $stmt->fetch_assoc()['MAX(idTicket)'];
                    $stmt = $db->prepare("UPDATE detalleticket SET salida = now() WHERE idTicket = ".$idTicket);
                    $stmt->execute();
                    $stmt = $db->query("SELECT entrada, salida FROM detalleticket WHERE idTicket = ".$idTicket);
                    $array = array();
                    $array[] = $stmt->fetch_assoc();
                    $fechaEntrada = strtotime($array[0]['entrada']);
                    $fechaSalida = strtotime($array[0]['salida']);
                    $horaEntrada = idate('H', $fechaEntrada);
                    $horaSalida = idate('H', $fechaSalida);
                    $minutoEntrada = idate('i', $fechaEntrada);
                    $minutoSalida = idate('i', $fechaSalida);
                    $horasTotal = $horaSalida - $horaEntrada;
                    if($minutoEntrada > $minutoSalida){
                        $horasTotal = $horasTotal - 1;
                    }
                    $total = $horasTotal * 15;
                    $stmt = $db->prepare("UPDATE detalleticket SET total = ".$total." WHERE idTicket = ".$idTicket);
                    if($stmt->execute()){
                        header('Content-type: application/json');
                        $failed = array('status' => "Exito");
                        echo json_encode($failed);
                    }
                }else{
                    header('Content-type: application/json');
                    $failed = array('error' => "No se encontro ticket para este Vehiculo");
                    echo json_encode($failed);
                }
            }else{
                header('Content-type: application/json');
                $failed = array('error' => $stmt->error);
                echo json_encode($failed);
            }
        }else{
            header('Content-type: application/json');
            $failed = array('error' => "No se encontro el vehiculo");
            echo json_encode($failed);
        }
    }
?>
