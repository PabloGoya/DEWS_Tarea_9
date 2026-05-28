/**
 * Buscador de libros con AJAX.
 *
 * Escucha el campo de texto, valida lo que escribe la persona usuaria
 * y muestra de forma amigable los resultados devueltos por la API.
 *
 * @author Pablo
 * @version 1.0
 */

const campoTexto = document.getElementById("texto");
const tipoBusqueda = document.getElementById("tipoBusqueda");
const contenedorResultados = document.getElementById("resultados");
const mensajeError = document.getElementById("error");
const contadorResultados = document.getElementById("contador");

let temporizador = null;

function validarTexto(texto, tipo) {
    if (tipo === "year") {
        return /^\d+$/.test(texto);
    }
    return /^[A-Za-záéíóúÁÉÍÓÚñÑüÜ\s]*$/.test(texto);
}

function filtrarTexto(e) {
    const valor = e.target.value;
    const tipo = tipoBusqueda.value;
    let valorFiltrado;
    
    if (tipo === "year") {
        valorFiltrado = valor.replace(/[^\d]/g, '');
    } else {
        valorFiltrado = valor.replace(/[^A-Za-záéíóúÁÉÍÓÚñÑüÜ\s]/g, '');
    }
    
    if (valor !== valorFiltrado) {
        e.target.value = valorFiltrado;
        mensajeError.textContent = tipo === "year" ? "Solo números" : "Solo letras y espacios";
        mensajeError.classList.add("visible");
        setTimeout(() => {
            mensajeError.classList.remove("visible");
            mensajeError.textContent = "";
        }, 2000);
    }
}

campoTexto.addEventListener("input", filtrarTexto);

campoTexto.addEventListener("keyup", () => {
    clearTimeout(temporizador);

    temporizador = setTimeout(() => {
        const texto = campoTexto.value.trim();
        const tipo = tipoBusqueda.value;

        if (!validarTexto(texto, tipo)) {
            mensajeError.textContent = tipo === "year" ? "Solo se permiten números" : "Solo se permiten letras y espacios";
            mensajeError.classList.add("visible");
            return;
        }

        if (texto.length === 0) {
            mensajeError.textContent = "";
            mensajeError.classList.remove("visible");
            contenedorResultados.innerHTML = "";
            contadorResultados.textContent = "";
            return;
        }

        if (texto.length < 2) {
            mensajeError.textContent = "Introduce al menos 2 caracteres";
            mensajeError.classList.add("visible");
            contenedorResultados.innerHTML = "";
            contadorResultados.textContent = "";
            return;
        }

        mensajeError.textContent = "";
        mensajeError.classList.remove("visible");

        contenedorResultados.innerHTML = '<div class="loading"><i class="fas fa-spinner fa-spin"></i> Buscando...</div>';

        fetch(`api.php?action=buscar&q=${encodeURIComponent(texto)}&tipo=${tipo}`)
            .then(res => res.json())
            .then(datos => {
                if (datos && datos.error) {
                    contenedorResultados.innerHTML = `<div class="error-api">${escaparHtml(datos.error)}</div>`;

                    contadorResultados.textContent = "";
                    return;
                }

                mostrarResultadosLibros(datos);
            })
            .catch(error => {
                contenedorResultados.innerHTML = '<div class="error-api">Error al conectar con el servidor</div>';
                contadorResultados.textContent = "";
            });
    }, 400);
});

/**
 * Muestra en pantalla los resultados de la búsqueda.
 *
 * Genera una tabla HTML con título, autor, nacionalidad y año de publicación
 * para cada libro devuelto por la API.
 *
 * @param array $datos Lista de libros devuelta por la API.
 * @return void
 */
function mostrarResultadosLibros(datos) {
    contenedorResultados.innerHTML = "";

    if (!datos || datos.length === 0) {
        contenedorResultados.innerHTML = `
            <div class="no-results">
                <i class="fas fa-search"></i>
                <p>No se encontraron resultados</p>
            </div>
        `;
        contadorResultados.textContent = "";
        return;
    }

    // Mostrar contador de resultados
    contadorResultados.innerHTML = `<i class="fas fa-book"></i> Se encontraron <strong>${datos.length}</strong> resultado(s)`;

    const table = document.createElement("table");
    table.className = "results-table";

    const thead = document.createElement("thead");
    const trHead = document.createElement("tr");
    ["Título", "Autor", "Nacionalidad", "Año"].forEach(t => {
        const th = document.createElement("th");
        th.textContent = t;
        trHead.appendChild(th);
    });
    thead.appendChild(trHead);
    table.appendChild(thead);

    const tbody = document.createElement("tbody");

    datos.forEach((item, index) => {
        const tr = document.createElement("tr");
        tr.style.animationDelay = `${index * 0.05}s`;
        tr.className = "fade-in";

        // Formatear fecha
        const fecha = item.f_publicacion ? new Date(item.f_publicacion).getFullYear() : 'N/A';

        tr.innerHTML = `
            <td>
                <i class="fas fa-book-open icon-book"></i>
                ${escaparHtml(item.titulo)}
            </td>
            <td>
                <i class="fas fa-user icon-author"></i>
                ${escaparHtml(item.nombre)} ${escaparHtml(item.apellidos)}
            </td>
            <td>
                <i class="fas fa-globe icon-flag"></i>
                ${escaparHtml(item.nacionalidad)}
            </td>
            <td>
                <i class="fas fa-calendar icon-date"></i>
                ${fecha}
            </td>
        `;

        tbody.appendChild(tr);
    });

    table.appendChild(tbody);
    contenedorResultados.appendChild(table);
}

/**
 * Escapa caracteres especiales para que el texto sea seguro en HTML.
 *
 * Se utiliza antes de pintar en la página contenido que viene de la base
 * de datos, evitando posibles inyecciones de código.
 *
 * @param string $texto Texto original recibido de la base de datos.
 * @return string Texto seguro listo para mostrar en la vista.
 */
function escaparHtml(texto) {
    if (!texto) return '';
    const div = document.createElement('div');
    div.textContent = texto;
    return div.innerHTML;
}
