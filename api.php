<?php
/**
 * API REST de Libros y Autores.
 *
 * Punto de entrada para las peticiones AJAX del buscador. Expone un
 * conjunto de acciones en formato JSON para listar autores y libros,
 * obtener sus detalles y realizar búsquedas por texto.
 *
 * @author Pablo
 * @version 1.0
 */

require_once __DIR__ . '/clases/Libros.php';

/**
 * Instancia principal del modelo de datos.
 *
 * @var Libros $libros Gestiona el acceso a autores y libros.
 */
$libros = new Libros();

/**
 * Conexión activa a la base de datos empleada por la API.
 *
 * @var PDO|null $conexion Objeto PDO si la conexión es correcta, null en caso de error.
 */
$conexion = $libros->conexion("localhost", "libros", "root", "");

header("Content-Type: application/json; charset=utf-8");

/**
 * Verifica que la conexión a la base de datos se ha establecido.
 *
 * Si no hay conexión disponible se devuelve un mensaje de error en JSON y
 * se detiene la ejecución del script.
 *
 * @return void
 */
if ($conexion === null) {
    echo json_encode(["error" => "Error de conexión"]);
    exit;
}

/**
 * Acción solicitada por la persona usuaria.
 *
 * Se recibe por parámetro GET y determina qué operación realiza la API.
 *
 * @var string $accion Nombre de la acción (por ejemplo, "buscar").
 */
$accion = $_GET["action"] ?? "";

/**
 * Enrutador principal de la API.
 *
 * Según el valor de la acción delega en los distintos métodos de la clase
 * Libros y construye la respuesta en formato JSON para el cliente.
 *
 * @return void
 */
switch ($accion) {

    case "get_listado_autores":
        echo json_encode($libros->consultarAutores($conexion));
        break;

    case "get_datos_autor":
        $id = $_GET["id"] ?? null;
        echo json_encode([
            "autor" => $libros->consultarAutores($conexion, $id),
            "libros" => $libros->consultarLibros($conexion, $id)
        ]);
        break;

    case "get_listado_libros":
        echo json_encode($libros->consultarLibros($conexion));
        break;

    case "get_datos_libro":
        $id = $_GET["id"] ?? null;
        $libro = $libros->consultarDatosLibro($conexion, $id);
        $autor = $libros->consultarAutores($conexion, $libro["id_autor"]);
        echo json_encode([
            "libro" => $libro,
            "autor" => $autor
        ]);
        break;

    case "buscar":
        $texto = $_GET["q"] ?? "";
        echo json_encode($libros->buscar($conexion, $texto));
        break;

    default:
        echo json_encode(["error" => "Acción no válida"]);
}
