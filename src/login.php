<?php

/**********************************************************************************************************************
 * Este programa, a través del formulario que tienes que hacer debajo, en el área de la vista, realiza el inicio de
 * sesión del usuario verificando que ese usuario con esa contraseña existe en la base de datos.
 * 
 * Para mantener iniciada la sesión dentrás que usar la $_SESSION de PHP.
 * 
 * En el formulario se deben indicar los errores ("Usuario y/o contraseña no válido") cuando corresponda.
 * 
 * Dicho formulario enviará los datos por POST.
 * 
 * Cuando el usuario se haya logeado correctamente y hayas iniciado la sesión, redirige al usuario a la
 * página principal.
 * 
 * UN USUARIO LOGEADO NO PUEDE ACCEDER A ESTE SCRIPT.
 */

/**********************************************************************************************************************
 * Lógica del programa
 * 
 * Tareas a realizar:
 * - TODO: tienes que realizar toda la lógica de este script
 */

session_start();
if (isset($_SESSION['usuario'])) {
    header('location: index.php');
}

//funcion que imprime el formulario
function imprimirFormulario($error)
{
    echo <<<END
        <form action="#" method="POST" id="lang">
        <p>Usuario: <input type='text' name='usuario'/></p>
        <p>Contraseña: <input type='password' name='clave'/></p>
        <p><input type='submit' name='enviar'/></p>
        <span>$error</span>
        </form>
    END;
}

function loguearUsuario()
{
    if (!$_POST) {
        imprimirFormulario("");
        return;
    }

    //se recogen los datos del POST y se sanean
    $usuario = isset($_POST['usuario']) ? htmlentities(trim($_POST['usuario'])) : null;
    $clave = isset($_POST['clave']) ? htmlentities(trim($_POST['clave'])) : null;
    
    //si algunos de los campos esta vacio
    if ($usuario == null || $clave == null) {
        imprimirFormulario("Contraseña o usuario incorrectos");
        return;
    }

    //conectarse con mariadb
    $mysqli = new mysqli("db", "dwes", "dwes", "dwes", 3306);
    if ($mysqli->errno) {
        echo "Error al conectarse a la base de datos";
        return;
    }

    //preparamos la consulta
    $sentencia = $mysqli->prepare(
        "select nombre, clave from usuario as u where u.nombre=?"
    );
    if (!$sentencia) {
        echo "Error: " . $mysqli->error;
        $mysqli->close();
        return;
    }

    //preparamos la vinculacion
    $valor = $usuario;
    $vinculacion = $sentencia->bind_param("s", $valor);
    if (!$vinculacion) {
        echo "Error al vincular: " . $mysqli->error;
        $sentencia->close();
        $mysqli->close();
        return;
    }

    //ejecutamos
    $ejecucion = $sentencia->execute();
    if (!$ejecucion) {
        echo "Error al ejecutar la sentencia " . $mysqli->error;
        $sentencia->close();
        $mysqli->close();
        return;
    }
    //recuperamos las filas obtenidas como resultado
    $resultado = $sentencia->get_result();
    if (!$resultado) {
        echo "Error al obtener los resultados. " . $mysqli->error;
        $sentencia->close();
        $mysqli->close();
        return;
    }

    //si el usuario y la clave descifrada obtenida de la consulta son iguales a los puestos en el post, el usuario se ha logueado.
    if (($fila = $resultado->fetch_assoc()) != null && $fila['nombre'] == $usuario && password_verify($clave, $fila['clave'])) {
        echo "Te has logueado $usuario!</br>";
        echo "<a href='index.php'>Volver a inicio</a>";
        $_SESSION['usuario'] = $usuario;
    } else {
        imprimirFormulario("Contraseña o usuario incorrectos");
    }


    $sentencia->close();
    $resultado->free();
    $mysqli->close();
}




function imprimirNav()
{
    if (!$_SESSION) {
        echo <<<END
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="filter.php">Filtrar imágenes</a></li>
                <li><a href="login.php">Login</a></li>
                <li><a href="signup.php">Regístrate</a></li>
            </ul>
        END;
    } else {
        echo <<<END
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="add.php">Añadir imagen</a></li>
                <li><a href="filter.php">Filtrar imágenes</a></li>
                <li><a href="logout.php">Cerrar sesión ({$_SESSION['usuario']})</a></li>
            </ul>
        END;
    }
}
?>

<?php
/*********************************************************************************************************************
 * Salida HTML
 * 
 * Tareas a realizar en la vista:
 * - TODO: añadir el menú.
 * - TODO: formulario con nombre de usuario y contraseña.
 */
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>

<body>
    <h1>Galería de imágenes</h1>
    <?php imprimirNav(); ?>
    <h1>Inicia sesión</h1>
    <?php loguearUsuario(); ?>
</body>

</html>