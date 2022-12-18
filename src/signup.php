<?php

/*********************************************************************************************************************
 * Este script realiza el registro del usuario vía el POST del formulario que hay debajo, en la vista.
 * 
 * Cuando llegue POST hay que validarlo y si todo fue bien insertar en la base de datos el usuario.
 * 
 * Requisitos del POST:
 * - El nombre de usuario no tiene que estar vacío y NO PUEDE EXISTIR UN USUARIO CON ESE NOMBRE EN LA BASE DE DATOS.
 * - La contraseña tiene que ser, al menos, de 8 caracteres.
 * - Las contraseñas tiene que coincidir.
 * 
 * La contraseña la tienes que guardar en la base de datos cifrada mediante el algoritmo BCRYPT.
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
function imprimirFormulario($usuario, $clave, $repiteClave, $error1, $error2, $error3)
{
    echo <<<END
    <h1>Regístrate</h1>
    <form action="signup.php" method="post">
        <p>
            <label for="nombre">Nombre de usuario</label>
            <input type="text" name="nombre" id="nombre" value=$usuario></br>
            <span>$error1</span>
        </p>
        <p>
            <label for="clave">Contraseña</label>
            <input type="password" name="clave" id="clave" value=$clave></br>
            <span>$error2</span>
        </p>
        <p>
            <label for="repite_clave">Repite la contraseña</label>
            <input type="password" name="repite_clave" id="repite_clave" value=$repiteClave></br>
            <span>$error3</span>
        </p>
        <p>
            <input type="submit" value="Regístrate">
        </p>
    </form>
    END;
}

//funcion que valida si un usuario es valido
function validarUsuario($nombre)
{
    if (mb_strlen($nombre) > 0) {
        return $nombre;
    } else {
        return false;
    }
}

//funcion que valida si una clave es valida
function validarClave($clave)
{
    if (mb_strlen($clave) >= 8) {
        return $clave;
    } else {
        return false;
    }
}

function insertarUsuario()
{
    if (!$_POST) {
        imprimirFormulario("", "", "", "", "", "");
    } else {

        //
        //// Saneamiento y validacion de datos ->
        //

        //filtros de datos
        $filtros = array(
            'usuario' => array(
                'filter' => FILTER_CALLBACK,
                'options' =>  'validarUsuario'
            ),
            'clave' => array(
                'filter' => FILTER_CALLBACK,
                'options' => 'validarClave'
            )
        );

        //se recogen las variables del post y se sanean
        $usuarioSaneado = htmlentities(trim($_POST['nombre']));
        $claveSaneada = htmlentities(trim($_POST['clave']));
        $claveRepetidaSaneada =  htmlentities(trim($_POST['repite_clave']));

        //los datos saneados se pasan a un array
        $datosSaneados = [
            'usuario' => $usuarioSaneado,
            'clave' => $claveSaneada,
        ];

        //se hace un filter validate de los datos con los filtros creados
        $validacion = filter_var_array($datosSaneados, $filtros);

        //se abre una conexion con la base de datos
        $mysqli = new mysqli("db", "dwes", "dwes", "dwes", 3306);
        if ($mysqli->connect_errno) {
            echo "No ha sido posible conectarse a la base de datos";
            exit();
        }

        //preparamos una sentencia para saber si ya hay un usuario con el mismo userName
        $sentencia1 = $mysqli->query(
            "select * from usuario u where u.nombre='$usuarioSaneado'"
        );
        if (!$sentencia1) {
            echo "Error: " . $mysqli->error;
            $mysqli->close();
            return;
        }

        //posibles errores ->
        $error1 = "";
        $error2 = "";
        $error3 = "";
        $valido = true;

        if ($sentencia1->num_rows > 0) {
            $valido = false;
            $error1 = "El usuario ya existe";
        }
        $sentencia1->free();

        if ($validacion['usuario'] != true) {
            $valido = false;
            $error1 = "El campo nombre no debe estar vacio";
        }
        if ($validacion['clave'] != true) {
            $valido = false;
            $error2 = "La comtraseña debe contener al menos 8 caracteres";
        }
        if ($claveSaneada != $claveRepetidaSaneada) {
            $valido = false;
            $error3 = "Las contraseñas deben coincidir";
        }


        //si no se cumplen todas las validaciones :
        if ($valido == false) {
            ImprimirFormulario($usuarioSaneado, $claveSaneada, $claveRepetidaSaneada, $error1, $error2, $error3);
            return;
        }

        //preparamos la consulta
        $sentencia = $mysqli->prepare(
            "insert into usuario(nombre, clave ) VALUES (?, ?)"
        );
        if (!$sentencia) {
            echo "Error: " . $mysqli->error;
            $mysqli->close();
            return;
        }

        //preparamos la vinculacion
        $valorUsuario = $usuarioSaneado;
        $valorClave = password_hash($claveSaneada, PASSWORD_BCRYPT);

        $vinculacion = $sentencia->bind_param("ss", $valorUsuario, $valorClave);

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
        } else {
            echo "Has creado el usuario: $usuarioSaneado</br>";
            echo "<a href='index.php'>Volver a inicio</a>";
        }


        //se cierra la conexion con la base de datos
        $sentencia->close();
        $mysqli->close();
    }
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
 * - TODO: los errores que se produzcan tienen que aparecer debajo de los campos.
 * - TODO: cuando hay errores en el formulario se debe mantener el valor del nombre de usuario en el campo
 * correspondiente.
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
    <?php ImprimirNav(); ?>

    <?php insertarUsuario(); ?>
</body>

</html>