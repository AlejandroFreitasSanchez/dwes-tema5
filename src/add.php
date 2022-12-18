<?php

/**********************************************************************************************************************
 * Este es el script que añade imágenes en la base de datos. En la tabla "imagen" de la base de datos hay que guardar
 * el nombre que viene vía POST, la ruta de la imagen como se indica más abajo, la fecha de la inserción (función
 * UNIX_TIMESTAMP()) y el identificador del usuario que inserta la imagen (el usuario que está logeado en estos
 * momentos).
 * 
 * ¿Cuál es la ruta de la imagen? ¿De dónde sacamos esta ruta? Te lo explico a continuación:
 * - Busca una forma de asignar un nombre que sea único.
 * - La extensión será la de la imagen original, que viene en $_FILES['imagne']['name'].
 * - Las imágenes se subirán a la carpeta llamada "imagenes/" que ves en el proyecto.
 * - En la base de datos guardaremos la ruta relativa en el campo "ruta" de la tabla "imagen".
 * 
 * Así, si llega por POST una imagen PNG y le asignamosel nombre "imagen1", entonces en el campo "ruta" de la tabla
 * "imagen" de la base de datos se guardará el valor "imagenes/imagen1.png".
 * 
 * Como siempre:
 * 
 * - Si no hay POST, entonces tan solo se muestra el formulario.
 * - Si hay POST con errores se muestra el formulario con los errores y manteniendo el nombre en el campo nombre.
 * - Si hay POST y todo es correcto entonces se guarda la imagen en la base de datos para el usuario logeado.
 * 
 * Esta son las validaciones que hay que hacer sobre los datos POST y FILES que llega por el formulario:
 * - En el nombre debe tener algo (mb_strlen > 0).
 * - La imagen tiene que ser o PNG o JPEG (JPG). Usa FileInfo para verificarlo.
 * 
 * NO VAMOS A CONTROLAR SI YA EXISTE UNA IMAGEN CON ESE NOMBRE. SI EXISTE, SE SOBREESCRIBIRÁ Y YA ESTÁ.
 * 
 * A ESTE SCRIPT SOLO SE PUEDE ACCEDER SI HAY UN USARIO LOGEADO.
 */

/**********************************************************************************************************************
 * Lógica del programa
 * 
 * Tareas a realizar:
 * - TODO: tienes que desarrollar toda la lógica de este script.
 */
session_start();
//funcion que valida si un nombre es valido
function validarNombre($nombre)
{
    if (mb_strlen($nombre) > 0) {
        return $nombre;
    } else {
        return false;
    }
}



function imprimirFormulario($nombre, $error1, $error2)
{
    echo <<<END
        <form method="post" enctype="multipart/form-data">
        <p>
        <label for="nombre">Nombre</label>
        <input type="text" name="nombre" id="nombre" value = $nombre><br/>
        <span>$error1</span>
        </p>

        <p>
        <label for="imagen">Imagen</label>
        <input type="file" name="imagen" id="imagen"><br/>
        <span>$error2</span>
        </p>

        <p>
        <input type="submit" value="Añadir">
        </p>
        </form>
    END;
}


function añadirImagen()
{

    if ($_POST) {

        //
        //// Saneamiento y validacion de datos ->
        //

        //filtros de datos
        $filtros = array(
            'nombre' => array(
                'filter' => FILTER_CALLBACK,
                'options' =>  'validarNombre'
            )
        );
        //se recoge el nombre del post y se sanea
        $nombreSaneado = htmlspecialchars(trim($_POST['nombre']));
        //los datos saneados se pasan a un array
        $datosSaneados = [
            'nombre' => $nombreSaneado,
        ];
        //se hace un filter validate de los datos con los filtros creados
        $validacion = filter_var_array($datosSaneados, $filtros);

        //string del fichero
        $fichero = $_FILES['imagen']['name'];
        //fichero que le pasamos al formulario
        $ficheroFileInfo = $_FILES['imagen'];
        //extension del fichero
        $extension = pathinfo($fichero, PATHINFO_EXTENSION);
        //ruta en la que se va a guardar el fichero ya con el nombre que le hemos pasado
        $rutaFicheroDestino = 'imagenes/' . basename(time() . "." . pathinfo($fichero, PATHINFO_EXTENSION));
        //extensiones permitidas
        $permitido = array('image/png', 'image/jpeg');

        //Errores posibles: ->
        $valido = true;
        $error1 = "";
        $error2 = "";


        //si el nombre no cumple la validacion
        if ($validacion['nombre'] != true) {
            $error1 = "El campo nombre no debe estar vacio";
            $valido = false;
        }

        //si no hay imagen seleccionada
        if (!$_FILES || !isset($_FILES['imagen']) || !$_FILES['imagen']['error'] === UPLOAD_ERR_OK || !$_FILES['imagen']['size'] > 0) {
            $error2 = "No has seleccionado ninguna imagen";
            $valido = false;
        } else {
            //si la extension no esta permitida
            if (!in_array(finfo_file(finfo_open(FILEINFO_MIME_TYPE), $ficheroFileInfo['tmp_name']), $permitido)) {
                $error2 = "La extension " . pathinfo($fichero, PATHINFO_EXTENSION) . " no esta permitida";
                $valido = false;
            }
        }


        //si no se cumplen las validaciones:
        if ($valido == false) {
            imprimirFormulario($nombreSaneado, $error1, $error2);
            return;
        }

        //
        //// Consultas a la base de datos ->
        //

        //conectarse con mariadb
        $mysqli = new mysqli("db", "dwes", "dwes", "dwes", 3306);
        if ($mysqli->errno) {
            echo "Error al conectarse a la base de datos";
            return;
        }

        //preparamos la primera sentencia para obtener el id del usuario
        $sentencia1 = $mysqli->query(
            "select id from usuario u where u.nombre = '{$_SESSION['usuario']}'"
        );

        if (!$sentencia1) {
            echo "Error: " . $mysqli->error;
            $mysqli->close();
            return;
        } else {
            while (($fila = $sentencia1->fetch_assoc()) != null) {
                $id = $fila['id'];
            }

            $sentencia1->free();
        }

        //preparamos la segunda sentencia que insertara el usuario a la base de datos
        $sentencia2 = $mysqli->prepare(
            "insert into imagen (nombre, ruta, subido, usuario) values (?, ?, ?, ?) "
        );

        if (!$sentencia2) {
            echo "Error: " . $mysqli->error;
            $mysqli->close();
            return;
        }

        //preparamos la vinculacion
        $valorNombre = $nombreSaneado;
        $valorRuta = $rutaFicheroDestino;
        $valorSubido = time();
        $valorUsuario = $id;

        $vinculacion = $sentencia2->bind_param("ssis", $valorNombre, $valorRuta, $valorSubido, $valorUsuario);

        if (!$vinculacion) {
            echo "Error al vincular: " . $mysqli->error;
            $sentencia2->close();
            $mysqli->close();
            return;
        }

        //ejecutamos la sentencia
        $ejecucion = $sentencia2->execute();
        if (!$ejecucion) {
            echo "Error al ejecutar la sentencia " . $mysqli->error;
            $sentencia2->close();
            $mysqli->close();
            return;
        }

        //si la funcion llega hasta aquí, se sube la imagen
        $seHaSubido = move_uploaded_file($_FILES['imagen']['tmp_name'], $rutaFicheroDestino);
        echo "<p>Tu imagen se ha subido correctamente</p><br>";
    } else {
        imprimirFormulario("", "", "");
    }
}
//funcion que imprime la barra de Navegacion
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




/*********************************************************************************************************************
 * Salida HTML
 * 
 * Tareas a realizar:
 * - TODO: añadir el menú de navegación.
 * - TODO: añadir en el campo del nombre el valor del mismo cuando haya errores en el envío para mantener el nombre
 *         que el usuario introdujo.
 * - TODO: añadir los errores que se produzcan cuando se envíe el formulario debajo de los campos.
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

    <h2>Añade una nueva imagen</h2>
    <?php añadirImagen(); ?>

</body>

</html>