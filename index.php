<?php
    require "./config/db.php";

    $query = "SELECT * FROM usuario";
    $resultado = $db->query($query);
    
    //header('Content-type: application/json');
    //echo json_encode($resultado);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PHP - MySQL</title>
</head>
<body>
    nothing to see here...
</body>
</html>