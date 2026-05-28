<?php
/**
 * Página principal del buscador de libros.
 *
 * Carga la interfaz HTML de la aplicación: el campo de búsqueda, el área
 * donde se mostrarán los resultados y el script JavaScript que se encarga
 * de hablar con la API y pintar los libros encontrados.
 *
 * @author Pablo
 * @version 1.0
 */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buscador de Libros</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<header>
    <div class="header-content">
        <i class="fas fa-book-open"></i>
        <h1>Buscador de Libros</h1>
    </div>
    <p class="subtitle">Encuentra tus libros favoritos por título, autor o nacionalidad</p>
</header>

<main>
    <div class="search-container">
        <form id="formBusqueda">
            <div class="input-wrapper">
                <i class="fas fa-search"></i>
                <input 
                    type="text" 
                    id="texto" 
                    placeholder="Escribe para buscar..." 
                    autocomplete="off"
                >
            </div>
            <div id="error" class="error-message"></div>
        </form>
    </div>

    <div class="results-container">
        <div id="contador" class="contador"></div>
        <div id="resultados"></div>
    </div>
</main>

<footer>
    <p><i class="fas fa-code"></i> Aplicación AJAX – DWES</p>
</footer>

<script src="js/buscador.js"></script>
</body>
</html>
