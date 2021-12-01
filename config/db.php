<?php

$db = new mysqli("localhost", "SumirekoDev", "pekora99", "estacionamiento");

if ($db->connect_errno){
    echo "Fallo la conexion a la base de datos.";
}