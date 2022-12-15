<?php

/*********************************************************************************************************************
 * Este script muestra un formulario a través del cual se pueden buscar imágenes por el nombre y mostrarlas. Utiliza
 * el operador LIKE de SQL para buscar en el nombre de la imagen lo que llegue por $_GET['nombre'].
 * 
 * Evidentemente, tienes que controlar si viene o no por GET el valor a buscar. Si no viene nada, muestra el formulario
 * de búsqueda. Si viene en el GET el valor a buscar (en $_GET['nombre']) entonces hay que preparar y ejecutar una 
 * sentencia SQL.
 * 
 * El valor a buscar se tiene que mantener en el formulario.
 */

/**********************************************************************************************************************
 * Lógica del programa
 * 
 * Tareas a realizar:
 * - TODO: tienes que realizar toda la lógica de este script
 */
session_start();


function filtra(string $texto): array
{
    //conectarse con mariadb
    $mysqli = new mysqli("db", "dwes", "dwes", "dwes", 3306);
    if ($mysqli->errno) {
        echo "Error al conectarse a la base de datos";
        return [];
    }

    //preparamos la consulta
    $sentencia = $mysqli->prepare(
        "select nombre, ruta from imagen i where i.nombre like ?"
    );
    if (!$sentencia) {
        echo "Error: " . $mysqli->error;
        $mysqli->close();
        return [];
    }

    //preparamos la vinculacion
    $valor = '%' . $texto . '%';
    $vinculacion = $sentencia->bind_param("s", $valor);

    if (!$vinculacion) {
        echo "Error al vincular: " . $mysqli->error;
        $sentencia->close();
        $mysqli->close();
        return [];
    }

    //ejecutamos
    $ejecucion = $sentencia->execute();
    if (!$ejecucion) {
        echo "Error al ejecutar la sentencia " . $mysqli->error;
        $sentencia->close();
        $mysqli->close();
        return [];
    }
    //recuperamos las filas obtenidas como resultado
    $resultado = $sentencia->get_result();
    if (!$resultado) {
        echo "Error al obtener los resultados. " . $mysqli->error;
        $sentencia->close();
        $mysqli->close();
        return [];
    }
    $resultadoBusqueda = [];
    while (($fila = $resultado->fetch_assoc()) != null) {
        $resultadoBusqueda[] = $fila;
    }
    return $resultadoBusqueda;
}

$posts = [];

$stringBuscador = $_GET && isset($_GET['texto']) ? htmlspecialchars(trim($_GET['texto'])) : "";

if (mb_strlen($stringBuscador) > 0) {
    $posts = filtra($stringBuscador);
}

//funcion que imprime la barra de navegacion
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
 * Tareas a realizar:
 * - TODO: completa el código de la vista añadiendo el menú de navegación.
 * - TODO: en el formulario falta añadir el nombre que se puso cuando se envió el formulario.
 * - TODO: debajo del formulario tienen que aparecer las imágenes que se han encontrado en la base de datos.
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


    <h2>Busca imágenes por filtro</h2>

    <form action="filter.php" method="get">
        <p>
            <label for="texto">Busca por</label>
            <input type="text" name="texto" id="texto" value="<?= $stringBuscador; ?>">
        </p>
        <p>
            <input type="submit" value="buscar">
        </p>

    </form>
    <?php
    foreach ($posts as $post) {
        echo <<<END
                <p>{$post['nombre']}</p>
                <img src={$post['ruta']} width=200 height=200></img>
                
            END;
    }
    ?>
</body>

</html>

