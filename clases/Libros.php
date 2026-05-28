<?php

/**
 * Clase Libros.
 *
 * Pequeña capa de acceso a datos para la aplicación de ejemplo. Centraliza
 * todas las operaciones relacionadas con autores y libros y oculta los
 * detalles de las consultas SQL al resto de la aplicación.
 *
 * @author Pablo
 * @version 1.0
 */
class Libros {

    /**
     * Establece una conexión con la base de datos.
     *
     * Crea un objeto PDO configurado para trabajar con la base de datos de
     * libros. En caso de producirse un error se devuelve null para que la
     * capa superior pueda gestionarlo.
     *
     * @param string $servidor Servidor MySQL.
     * @param string $bd       Nombre de la base de datos.
     * @param string $usuario  Usuario de la base de datos.
     * @param string $password Contraseña del usuario.
     *
     * @return PDO|null Objeto PDO listo para usarse o null si falla la conexión.
     */
    public function conexion($servidor, $bd, $usuario, $password) {
        try {
            $pdo = new PDO(
                "mysql:host=$servidor;dbname=$bd;charset=utf8",
                $usuario,
                $password
            );
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $pdo;
        } catch (PDOException $e) {
            return null;
        }
    }

    /**
     * Consulta autores.
     *
     * Si no se indica un identificador devuelve el listado completo de autores.
     * Cuando se facilita un id concreto, devuelve únicamente los datos de ese
     * autor.
     *
     * @param PDO      $conexion Conexión activa a la base de datos.
     * @param int|null $id       Id del autor (opcional).
     *
     * @return array|null Array asociativo con los datos del/los autor(es) o null en caso de error.
     */
    public function consultarAutores($conexion, $id = null) {
        if ($id === null) {
            // Tabla: autores
            $stmt = $conexion->query("SELECT * FROM autores");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            // Tabla: autores, clave primaria: id
            $stmt = $conexion->prepare("SELECT * FROM autores WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
    }

    /**
     * Consulta libros.
     *
     * Sin parámetros devuelve el catálogo completo de libros. Si se indica
     * el identificador de un autor solo se devuelven aquellos libros
     * asociados a dicho autor.
     *
     * @param PDO      $conexion Conexión a la base de datos.
     * @param int|null $idAutor  Id del autor cuyos libros se desean obtener (opcional).
     *
     * @return array|null Lista de libros encontrados o null en caso de error.
     */
    public function consultarLibros($conexion, $idAutor = null) {
        if ($idAutor === null) {
            // Tabla: libros
            $stmt = $conexion->query("SELECT * FROM libros");
        } else {
            $stmt = $conexion->prepare(
                // Campo de relación con autores: id_autor
                "SELECT * FROM libros WHERE id_autor = ?"
            );
            $stmt->execute([$idAutor]);
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene los datos de un libro concreto.
     *
     * @param PDO $conexion Conexión a la base de datos.
     * @param int $idLibro  Id del libro que se quiere consultar.
     *
     * @return array|null Datos del libro o null si no existe ningún registro.
     */
    public function consultarDatosLibro($conexion, $idLibro) {
        $stmt = $conexion->prepare(
            // Tabla: libros, clave primaria: id
            "SELECT * FROM libros WHERE id = ?"
        );
        $stmt->execute([$idLibro]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Realiza una búsqueda por texto.
     *
     * Localiza libros cuya información coincida parcialmente con el texto
     * indicado. Se buscan coincidencias en el título del libro, nombre y
     * apellidos del autor y en la nacionalidad.
     *
     * @param PDO    $conexion Conexión a la base de datos.
     * @param string $texto    Texto a buscar dentro de los diferentes campos.
     *
     * @return array Lista de resultados coincidentes, cada uno con datos de libro y autor.
     */
    public function buscar($conexion, $texto) {
        $sql = "
            SELECT 
                l.titulo,
                l.f_publicacion,
                a.id AS id_autor,
                a.nombre,
                a.apellidos,
                a.nacionalidad
            FROM libros l
            JOIN autores a ON l.id_autor = a.id
            WHERE 
                l.titulo LIKE ?
                OR a.nombre LIKE ?
                OR a.apellidos LIKE ?
                OR a.nacionalidad LIKE ?
        ";

        $like = "%$texto%";
        $stmt = $conexion->prepare($sql);
        $stmt->execute([$like, $like, $like, $like]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
