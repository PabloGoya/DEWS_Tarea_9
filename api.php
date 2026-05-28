<?php
header("Content-Type: application/json; charset=utf-8");

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
