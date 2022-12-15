# Poner en marchar los contenedores
La **primera vez** tienes que ejecutar estos dos comandos en la raíz de este proyecto para crear los contenedores y levantarlos:
1. Ejecuta `docker build -t php-for-dwes .`
2. Ejecuta `docker compose up -d`

## Arrancar los contenedores
Una vez creado los contenedores, cuando quieras **arrancarlos** tan solo tienes que ejecutar el comando `docker compose start` en la raíz del proyecto.

## Parar lo contenedores
Si quieres **apagarlos** ejecuta `docker compose stop` en la raíz del proyecto.

# Poner en marcha la base de datos
Accede a MariaDB por medio de Adminer arrancando los contenedores y accediendo vía web a `localhost:8080`. Desde Adminer, crea las tablas que tienes en el fichero `./db/schema.sql`.

## Conectar a la base de datos
Para conectarte a la base de datos tienes que indicar:

- La dirección de la base de datos: `bd`
- El nombre de la base de datos, el usuario y la contraseña, que en todos los casos es `dwes`
- El puerto de la base de datos: `3306`

Ejemplo:

`$mysqli = new mysqli("db", "dwes", "dwes", "dwes", 3306);`

# Preparar la carpeta donde se subirán las imágenes
La carpeta donde se subirán las imágenes tiene que tener permisos de escritura. Te puedes asegurar que no tendrás problemas con un `chmod -R 0777 imagenes`.
