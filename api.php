<?php
/**
 * API simple para búsquedas de libros usando Open Library.
 *
 * Este archivo expone una acción `buscar` que acepta el parámetro `q`
 * y devuelve un listado JSON con resultados formateados. Se opta por
 * simplicidad: peticiones GET y `file_get_contents` para obtener datos
 * de la API pública.
 *
 * Hecho por: Pablo (ajustes ligeros de documentación)
 * @version 1.0
 */

header("Content-Type: application/json; charset=utf-8");

/**
 * Realiza la búsqueda en Open Library y devuelve resultados formateados.
 *
 * - Detecta automáticamente si la consulta es numérica (año) y usa
 *   el parámetro apropiado.
 * - Si no es numérica realiza una búsqueda general (`q`) que cubre
 *   título y autor.
 *
 * @param string $query Término de búsqueda ingresado por la persona usuaria.
 * @return array Lista de resultados o array con clave 'error' en caso de fallo.
 */
function buscar($query) {
    if (empty($query)) {
        return [];
    }

    // Detectar el tipo de búsqueda automáticamente
    $esNumero = is_numeric(trim($query));
    $tipo = $esNumero ? "first_publish_year" : "title";
    
    // Si no es número, intentar buscar por autor también
    $url = "https://openlibrary.org/search.json?";
    if ($esNumero) {
        $url .= "first_publish_year=" . urlencode($query);
    } else {
        // Combinar búsqueda por título y autor
        $url .= "q=" . urlencode($query);
    }
    $url .= "&limit=20";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0");
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $respuesta = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        return ["error" => "Error de conexión"];
    }
    
    $datos = json_decode($respuesta, true);
    if (!isset($datos['docs'])) {
        return [];
    }
    
    $resultados = [];
    foreach ($datos['docs'] as $libro) {
        $titulo = $libro['title'] ?? 'Sin título';
        $autor = isset($libro['author_name'][0]) ? $libro['author_name'][0] : 'Desconocido';
        $anio = $libro['first_publish_year'] ?? null;
        
        $partes = array_reverse(explode(' ', trim($autor)));
        $apellidos = array_shift($partes) ?? '';
        $nombre = implode(' ', array_reverse($partes)) ?: $autor;
        
        $resultados[] = [
            "titulo" => $titulo,
            "nombre" => $nombre,
            "apellidos" => $apellidos,
            "nacionalidad" => "Internacional",
            "f_publicacion" => $anio ? $anio . "-01-01" : null
        ];
    }
    
    return $resultados;
}

$accion = $_GET["action"] ?? "";

if ($accion === "buscar") {
    echo json_encode(buscar($_GET["q"] ?? ""));
} else {
    echo json_encode(["error" => "Acción no válida"]);
}
