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
 * Extrae el nombre y apellido del autor desde el string completo.
 *
 * @param string $nombreCompleto Nombre completo del autor (ej: "J.R.R. Tolkien").
 * @return array Array con 'nombre' y 'apellidos'.
 */
function separarNombreApellido(string $nombreCompleto): array {
    $nombreCompleto = trim($nombreCompleto);
    
    if (empty($nombreCompleto)) {
        return ['nombre' => 'Desconocido', 'apellidos' => ''];
    }
    
    // Dividir por espacios
    $partes = explode(' ', $nombreCompleto);
    
    if (count($partes) === 1) {
        // Si solo hay una palabra, es el nombre
        return ['nombre' => $partes[0], 'apellidos' => ''];
    }
    
    // El último elemento es el apellido, el resto es el nombre
    $apellidos = array_pop($partes);
    $nombre = implode(' ', $partes);
    
    return ['nombre' => $nombre, 'apellidos' => $apellidos];
}

/**
 * Valida si un string parece ser un nombre de autor válido.
 *
 * Evita editoriales, compiladores, etc. que no sean autores reales.
 *
 * @param string $autor Nombre a validar.
 * @return bool true si parece un autor válido.
 */
function esAutorValido(string $autor): bool {
    $autor = strtolower(trim($autor));
    
    // Palabras que indican que no es un autor real
    $palabrasExcluidas = ['editor', 'compiler', 'contributor', 'translator', 
                          'illustrated by', 'ilustrado por', 'editorial', 
                          'publisher', 'editor in chief', 'general editor'];
    
    foreach ($palabrasExcluidas as $palabra) {
        if (stripos($autor, $palabra) !== false) {
            return false;
        }
    }
    
    // Excluir si es muy corto o parece un nombre de editorial
    if (strlen($autor) < 3 || preg_match('/^[A-Z\s&]+$/', $autor)) {
        return false;
    }
    
    return true;
}

/**
 * Realiza una búsqueda en la API de Open Library y formatea los resultados.
 *
 * Busca libros por título, extrae el autor principal y evita duplicados
 * o datos incorrectos. Los resultados se formatean para compatibilidad
 * con la interfaz existente.
 *
 * @param string $query Término de búsqueda.
 * @param int $limit Número máximo de resultados a procesar.
 * @return array Resultados formateados o array con clave 'error'.
 */
function buscarEnOpenLibrary(string $query, int $limit = 30): array {
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

    // Formatear y filtrar los resultados
    $resultados = [];
    $titulosVistos = [];
    
    if (!empty($datos['docs'])) {
        foreach ($datos['docs'] as $libro) {
            $titulo = trim($libro['title'] ?? 'Sin título');
            
            // Evitar duplicados de títulos
            if (isset($titulosVistos[$titulo])) {
                continue;
            }
            $titulosVistos[$titulo] = true;
            
            // Extraer autor válido
            $autor = 'Desconocido';
            $nombre = 'Desconocido';
            $apellidos = '';
            
            if (!empty($libro['author_name']) && is_array($libro['author_name'])) {
                // Buscar el primer autor válido
                foreach ($libro['author_name'] as $autorName) {
                    if (esAutorValido($autorName)) {
                        $autor = $autorName;
                        $partes = separarNombreApellido($autor);
                        $nombre = $partes['nombre'];
                        $apellidos = $partes['apellidos'];
                        break;
                    }
                }
            }
            
            // Obtener año de publicación
            $anio = !empty($libro['first_publish_year']) 
                    ? $libro['first_publish_year'] . "-01-01" 
                    : null;
            
            $resultados[] = [
                "titulo" => $titulo,
                "nombre" => $nombre,
                "apellidos" => $apellidos,
                "nacionalidad" => "Internacional",
                "f_publicacion" => $anio
            ];
            
            // Limitar a 10 resultados válidos
            if (count($resultados) >= 10) {
                break;
            }
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
