<?php

$db = new mysqli("localhost", "root", "", "estacionamiento");

if ($db->connect_errno){
    echo "Fallo la conexion a la base de datos.";
}