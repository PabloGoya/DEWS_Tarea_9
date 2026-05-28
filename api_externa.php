<?php
/**
 * Página de consulta a la API externa de Open Library.
 *
 * Realiza peticiones HTTP a la API pública de Open Library
 * (https://openlibrary.org/developers/api) usando cURL para buscar
 * libros por título y mostrar los resultados en formato tabla.
 *
 * No requiere registro ni API key.
 *
 * @author Pablo
 * @version 1.0
 */

/**
 * Realiza una petición cURL a la API de Open Library y devuelve los datos JSON.
 *
 * @param string $query Término de búsqueda introducido por el usuario.
 * @param int    $limit Número máximo de resultados a obtener (por defecto 10).
 * @return array Array asociativo con los datos devueltos por la API, o array con clave 'error'.
 */
function buscarLibrosOpenLibrary(string $query, int $limit = 10): array {
    $url = "https://openlibrary.org/search.json?title=" . urlencode($query) . "&limit=" . $limit . "&lang=es";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_USERAGENT, "BuscadorLibrosDWES/1.0");
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $respuesta = curl_exec($ch);
    $errorCurl  = curl_error($ch);
    curl_close($ch);

    if ($errorCurl) {
        return ["error" => "Error de conexión: " . $errorCurl];
    }

    $datos = json_decode($respuesta, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        return ["error" => "Error al procesar la respuesta de la API"];
    }

    return $datos;
}

/**
 * Formatea el año de publicación de un libro para mostrarlo en la tabla.
 *
 * Si el libro tiene varios años, devuelve el primero disponible.
 * Si no tiene ninguno, devuelve un guión.
 *
 * @param array $libro Array con los datos del libro devueltos por la API.
 * @return string Año de primera publicación o '-' si no está disponible.
 */
function obtenerAnioPublicacion(array $libro): string {
    if (!empty($libro['first_publish_year'])) {
        return (string) $libro['first_publish_year'];
    }
    return '-';
}

/**
 * Extrae el primer autor de un libro o devuelve 'Desconocido'.
 *
 * @param array $libro Array con los datos del libro devueltos por la API.
 * @return string Nombre del autor principal o 'Desconocido'.
 */
function obtenerAutor(array $libro): string {
    if (!empty($libro['author_name']) && is_array($libro['author_name'])) {
        return htmlspecialchars($libro['author_name'][0]);
    }
    return 'Desconocido';
}

/**
 * Extrae el número de ediciones disponibles de un libro.
 *
 * @param array $libro Array con los datos del libro devueltos por la API.
 * @return int Número de ediciones o 0 si no está disponible.
 */
function obtenerEdiciones(array $libro): int {
    return isset($libro['edition_count']) ? (int) $libro['edition_count'] : 0;
}

// ── Procesamiento del formulario ──────────────────────────────────────────────

$query     = trim($_GET['q'] ?? '');
$resultados = [];
$total      = 0;
$error      = '';

if ($query !== '') {
    if (!preg_match('/^[A-Za-záéíóúÁÉÍÓÚñÑüÜ\s]+$/u', $query)) {
        $error = 'Solo se permiten letras y espacios en la búsqueda.';
    } else {
        $datos = buscarLibrosOpenLibrary($query);

        if (isset($datos['error'])) {
            $error = $datos['error'];
        } else {
            $resultados = $datos['docs'] ?? [];
            $total      = $datos['numFound'] ?? 0;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Externa – Open Library</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Estilos adicionales específicos de esta página */
        .api-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255,255,255,0.2);
            border: 1px solid rgba(255,255,255,0.4);
            border-radius: 30px;
            padding: 6px 16px;
            font-size: 0.85rem;
            margin-top: 12px;
        }
        .api-badge a {
            color: white;
            text-decoration: none;
            font-weight: 600;
        }
        .api-badge a:hover { text-decoration: underline; }

        .search-form {
            display: flex;
            gap: 12px;
            align-items: flex-start;
        }
        .search-form .input-wrapper {
            flex: 1;
        }
        .btn-buscar {
            padding: 18px 28px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border: none;
            border-radius: var(--radius-md);
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            white-space: nowrap;
            box-shadow: var(--shadow-md);
        }
        .btn-buscar:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        .btn-buscar i { margin-right: 8px; }

        .editions-badge {
            display: inline-block;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border-radius: 20px;
            padding: 3px 12px;
            font-size: 0.82rem;
            font-weight: 600;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            margin-bottom: 20px;
            padding: 10px 18px;
            background: white;
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-sm);
            transition: all 0.3s ease;
        }
        .back-link:hover {
            box-shadow: var(--shadow-md);
            transform: translateX(-3px);
        }

        .info-api {
            background: linear-gradient(135deg, #f0f4ff, #e8f0fe);
            border-left: 4px solid var(--primary-color);
            border-radius: var(--radius-sm);
            padding: 14px 18px;
            font-size: 0.9rem;
            color: var(--text-light);
            margin-bottom: 20px;
        }
        .info-api strong { color: var(--primary-color); }
        .info-api code {
            background: rgba(102,126,234,0.12);
            padding: 2px 7px;
            border-radius: 4px;
            font-size: 0.85rem;
            color: var(--primary-dark);
        }

        @media (max-width: 600px) {
            .search-form { flex-direction: column; }
            .btn-buscar { width: 100%; }
        }
    </style>
</head>
<body>

<header>
    <div class="header-content">
        <i class="fas fa-globe"></i>
        <h1>API Externa – Open Library</h1>
    </div>
    <p class="subtitle">Búsqueda de libros mediante la API pública de Open Library</p>
    <div class="api-badge">
        <i class="fas fa-plug"></i>
        Fuente: <a href="https://openlibrary.org/developers/api" target="_blank">openlibrary.org/developers/api</a>
    </div>
</header>

<main>
    <a href="index.php" class="back-link">
        <i class="fas fa-arrow-left"></i> Volver al buscador principal
    </a>

    <!-- Información técnica de la API -->
    <div class="info-api">
        <i class="fas fa-info-circle"></i>
        Esta página consume la API REST de <strong>Open Library</strong> usando <code>cURL</code>.
        Endpoint: <code>https://openlibrary.org/search.json?title={query}</code> — Sin registro ni API key.
    </div>

    <!-- Formulario de búsqueda -->
    <div class="search-container">
        <form method="GET" action="api_externa.php" id="formBusqueda">
            <div class="search-form">
                <div class="input-wrapper">
                    <i class="fas fa-search"></i>
                    <input
                        type="text"
                        name="q"
                        id="texto"
                        placeholder="Busca un libro por título, ej: Don Quijote..."
                        value="<?= htmlspecialchars($query) ?>"
                        pattern="[A-Za-záéíóúÁÉÍÓÚñÑüÜ\s]+"
                        title="Solo se permiten letras y espacios"
                        autocomplete="off"
                    >
                </div>
                <button type="submit" class="btn-buscar">
                    <i class="fas fa-search"></i> Buscar
                </button>
            </div>

            <?php if ($error): ?>
                <div class="error-message visible">
                    <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
        </form>
    </div>

    <!-- Resultados -->
    <div class="results-container">

        <?php if ($query !== '' && !$error): ?>
            <div class="contador">
                <i class="fas fa-book"></i>
                Se encontraron <strong><?= number_format($total) ?></strong> resultados para
                "<strong><?= htmlspecialchars($query) ?></strong>" — mostrando los primeros <strong><?= count($resultados) ?></strong>
            </div>
        <?php endif; ?>

        <?php if (!empty($resultados)): ?>
            <table class="results-table fade-in">
                <thead>
                    <tr>
                        <th><i class="fas fa-book"></i> Título</th>
                        <th><i class="fas fa-user"></i> Autor</th>
                        <th><i class="fas fa-calendar"></i> Año</th>
                        <th><i class="fas fa-layer-group"></i> Ediciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($resultados as $libro): ?>
                        <tr>
                            <td>
                                <i class="fas fa-book icon-book"></i>
                                <?php
                                $titulo = htmlspecialchars($libro['title'] ?? 'Sin título');
                                $key    = $libro['key'] ?? '';
                                if ($key) {
                                    echo '<a href="https://openlibrary.org' . htmlspecialchars($key) . '" target="_blank" style="color:var(--primary-color);text-decoration:none;font-weight:500;">' . $titulo . ' <i class="fas fa-external-link-alt" style="font-size:0.75rem;"></i></a>';
                                } else {
                                    echo $titulo;
                                }
                                ?>
                            </td>
                            <td>
                                <i class="fas fa-pen-nib icon-author"></i>
                                <?= obtenerAutor($libro) ?>
                            </td>
                            <td>
                                <i class="fas fa-calendar-alt icon-date"></i>
                                <?= obtenerAnioPublicacion($libro) ?>
                            </td>
                            <td>
                                <span class="editions-badge"><?= obtenerEdiciones($libro) ?></span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

        <?php elseif ($query !== '' && !$error): ?>
            <div class="no-results">
                <i class="fas fa-search"></i>
                <p>No se encontraron libros para "<strong><?= htmlspecialchars($query) ?></strong>"</p>
            </div>

        <?php else: ?>
            <div class="no-results">
                <i class="fas fa-book-open"></i>
                <p>Introduce un título para buscar libros en Open Library</p>
            </div>
        <?php endif; ?>

    </div>
</main>

<footer>
    <p>
        <i class="fas fa-code"></i> Aplicación DWES –
        <i class="fas fa-database"></i> Datos: <a href="https://openlibrary.org" target="_blank" style="color:var(--primary-color);">Open Library API</a>
    </p>
</footer>

</body>
</html>