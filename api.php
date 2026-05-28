<?php
/**
 * API REST de Libros y Autores.
 *
 * Punto de entrada para las peticiones AJAX del buscador. Expone un
 * conjunto de acciones en formato JSON para listar autores y libros,
 * obtener sus detalles y realizar búsquedas por texto.
 *
 * Utiliza la API externa de Open Library para las búsquedas,
 * eliminando la necesidad de una base de datos local.
 *
 * @author Pablo
 * @version 1.0
 */

require_once __DIR__ . '/clases/Libros.php';

header("Content-Type: application/json; charset=utf-8");

/**
 * Realiza una búsqueda en la API de Open Library y formatea los resultados.
 *
 * @param string $query Término de búsqueda.
 * @param int $limit Número máximo de resultados.
 * @return array Resultados formateados o array con clave 'error'.
 */
function buscarEnOpenLibrary(string $query, int $limit = 20): array {
    $url = "https://openlibrary.org/search.json?title=" . urlencode($query) . "&limit=" . $limit;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_USERAGENT, "BuscadorLibrosDWES/1.0");
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $respuesta = curl_exec($ch);
    $errorCurl = curl_error($ch);
    curl_close($ch);

    if ($errorCurl) {
        return ["error" => "Error de conexión: " . $errorCurl];
    }

    $datos = json_decode($respuesta, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        return ["error" => "Error al procesar los datos"];
    }

    // Formatear los resultados para que coincidan con la estructura de la BD local
    $resultados = [];
    if (!empty($datos['docs'])) {
        foreach ($datos['docs'] as $libro) {
            $resultados[] = [
                "titulo" => $libro['title'] ?? 'Sin título',
                "nombre" => !empty($libro['author_name']) ? $libro['author_name'][0] : 'Desconocido',
                "apellidos" => '',
                "nacionalidad" => 'Internacional',
                "f_publicacion" => !empty($libro['first_publish_year']) ? $libro['first_publish_year'] . "-01-01" : null
            ];
        }
    }

    return $resultados;
}

/**
 * Instancia principal del modelo de datos.
 *
 * @var Libros $libros Gestiona el acceso a autores y libros.
 */
$libros = new Libros();

/**
 * Intenta crear conexión a la base de datos local (opcional).
 *
 * @var PDO|null $conexion Objeto PDO si existe BD, null si no.
 */
$conexion = $libros->conexion("localhost", "libros", "root", "");
$tieneBD = ($conexion !== null);

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
 * La acción "buscar" utiliza Open Library en lugar de la base de datos local,
 * permitiendo búsquedas sin necesidad de servidor MySQL.
 *
 * @return void
 */
switch ($accion) {

    case "get_listado_autores":
        if ($tieneBD) {
            echo json_encode($libros->consultarAutores($conexion));
        } else {
            echo json_encode(["error" => "Base de datos no disponible"]);
        }
        break;

    case "get_datos_autor":
        if ($tieneBD) {
            $id = $_GET["id"] ?? null;
            echo json_encode([
                "autor" => $libros->consultarAutores($conexion, $id),
                "libros" => $libros->consultarLibros($conexion, $id)
            ]);
        } else {
            echo json_encode(["error" => "Base de datos no disponible"]);
        }
        break;

    case "get_listado_libros":
        if ($tieneBD) {
            echo json_encode($libros->consultarLibros($conexion));
        } else {
            echo json_encode(["error" => "Base de datos no disponible"]);
        }
        break;

    case "get_datos_libro":
        if ($tieneBD) {
            $id = $_GET["id"] ?? null;
            $libro = $libros->consultarDatosLibro($conexion, $id);
            $autor = $libros->consultarAutores($conexion, $libro["id_autor"]);
            echo json_encode([
                "libro" => $libro,
                "autor" => $autor
            ]);
        } else {
            echo json_encode(["error" => "Base de datos no disponible"]);
        }
        break;

    case "buscar":
        $texto = $_GET["q"] ?? "";
        // Usar Open Library en lugar de BD local
        echo json_encode(buscarEnOpenLibrary($texto));
        break;

    default:
        echo json_encode(["error" => "Acción no válida"]);
}
