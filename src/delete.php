<?php
session_start();
/**********************************************************************************************************************
 * Este script simplemente elimina la imagen de la base de datos y de la carpeta <imagen>
 *
 * La información de la imagen a eliminar viene vía GET. Por GET se tiene que indicar el id de la imagen a eliminar
 * de la base de datos.
 * 
 * Busca en la documentación de PHP cómo borrar un fichero.
 * 
 * Si no existe ninguna imagen con el id indicado en el GET o no se ha inicado GET, este script redirigirá al usuario
 * a la página principal.
 * 
 * En otro caso seguirá la ejecución del script y mostrará la vista de debajo en la que se indica al usuario que
 * la imagen ha sido eliminada.
 */

/**********************************************************************************************************************
 * Lógica del programa
 * 
 * Tareas a realizar:
 * - TODO: tienes que desarrollar toda la lógica de este script.
 */
if ($_SESSION && $_GET && isset($_GET['id'])) {
    $ruta = "";
    //se recoge el id del GET y se sanea
    $id = htmlspecialchars(trim($_GET['id']));

    //conexion con mariadb
    $mysqli = new mysqli("db", "dwes", "dwes", "dwes", 3306);
    if ($mysqli->errno) {
        echo "Error al conectarse a la base de datos";
        return;
    }

    //preparamos la primera sentencia para obtener la ruta de la imagen
    $sentencia1 = $mysqli->query(
        "select ruta from imagen where id = '$id'"
    );
    if (!$sentencia1) {
        echo "Error: " . $mysqli->error;
        $mysqli->close();
        return;
    }

    //se pasa la ruta a una variable
    while (($fila = $sentencia1->fetch_assoc()) != null) {
        $ruta = $fila['ruta'];
    }

    //si no hay ninguna ruta vinculado al id (no existe) rederidige al index.php
    if ($ruta == "") {
        header("location:index.php");
    }

    //se libera la sentencia 1
    $sentencia1->free();

    //preparamos la segunda sentencia para eliminar la imagen de la base de datos
    $sentencia2 = $mysqli->query(
        "delete from imagen where id = '$id'"
    );
    if (!$sentencia2) {
        echo "Error: " . $mysqli->error;
        $mysqli->close();
        return;
    }

    //se borra la imagen
    if (file_exists($ruta)) {
        unlink($ruta);
    }

    //se cierra la conexion con la base de datos
    $mysqli->close();
} else {
    header("location:index.php");
}
?>

<?php
/*********************************************************************************************************************
 * Salida HTML
 */
?>
<h1>Galería de imágenes</h1>

<p>Imagen eliminada correctamente</p>
<p>Vuelve a la <a href="index.php">página de inicio</a></p>