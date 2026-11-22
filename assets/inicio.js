
document.addEventListener("DOMContentLoaded", () => {

    // ===== MENÚ HAMBURGUESA =====

    const navbar = document.getElementById("navbar");
    const botonMenu = document.getElementById("nav-toggle");
    const enlacesMenu = document.getElementById("nav-enlaces");

    if (navbar && botonMenu && enlacesMenu) {

        // Cambiar apariencia al hacer scroll
        function actualizarNavbar() {
            navbar.classList.toggle(
                "scrolled",
                window.scrollY > 50
            );
        }

        window.addEventListener("scroll", actualizarNavbar, {
            passive: true
        });

        actualizarNavbar();

        // Abrir o cerrar menú
        botonMenu.addEventListener("click", () => {

            const abierto = enlacesMenu.classList.toggle("abierto");

            botonMenu.setAttribute(
                "aria-expanded",
                String(abierto)
            );

            botonMenu.setAttribute(
                "aria-label",
                abierto ? "Cerrar menú" : "Abrir menú"
            );
        });

        // Cerrar cuando se selecciona una opción
        enlacesMenu.querySelectorAll("a").forEach((enlace) => {

            enlace.addEventListener("click", () => {

                enlacesMenu.classList.remove("abierto");

                botonMenu.setAttribute("aria-expanded", "false");
                botonMenu.setAttribute("aria-label", "Abrir menú");
            });

        });
    }

    // ===== ANIMACIONES AL HACER SCROLL =====

    const reducirMovimiento = window.matchMedia(
        "(prefers-reduced-motion: reduce)"
    ).matches;

    if (!reducirMovimiento && "IntersectionObserver" in window) {

        document.body.classList.add("js-activo");

        const elementos = document.querySelectorAll(".revelar");

        const observador = new IntersectionObserver((entradas) => {

            entradas.forEach((entrada) => {

                if (entrada.isIntersecting) {
                    entrada.target.classList.add("visible");
                    observador.unobserve(entrada.target);
                }

            });

        }, {
            threshold: 0.1
        });

        elementos.forEach((elemento) => {
            observador.observe(elemento);
        });
    }



// ===== FORMULARIO DE RESERVACIONES =====

const fechaReserva = document.getElementById("fecha");
const horaReserva = document.getElementById("hora");
const formularioReserva = document.getElementById("form-reservacion");
const botonReservar = document.querySelector(".boton-reservar");

if (fechaReserva && horaReserva && formularioReserva && botonReservar) {

    function fechaLocal() {
        const hoy = new Date();

        const anio = hoy.getFullYear();
        const mes = String(hoy.getMonth() + 1).padStart(2, "0");
        const dia = String(hoy.getDate()).padStart(2, "0");

        return `${anio}-${mes}-${dia}`;
    }

    // No permitir fechas anteriores a hoy
    fechaReserva.min = fechaLocal();

    botonReservar.addEventListener("click", () => {

        // Primero verificar los campos obligatorios
        if (!formularioReserva.reportValidity()) {
            return;
        }

        const fechaHora = new Date(
            `${fechaReserva.value}T${horaReserva.value}`
        );

        if (fechaHora <= new Date()) {
            horaReserva.setCustomValidity(
                "Selecciona una fecha y hora futuras."
            );
            horaReserva.reportValidity();
            return;
        }

        horaReserva.setCustomValidity("");

        // Todavía no guardamos en Supabase
        alert("Formulario válido. Próximamente podrás enviar tu reservación.");
    });

    // Limpiar advertencia al cambiar fecha u hora
    [fechaReserva, horaReserva].forEach((campo) => {
        campo.addEventListener("input", () => {
            horaReserva.setCustomValidity("");
        });
    });
}

});
