
document.addEventListener("DOMContentLoaded", () => {

    // =====================================
    // 1. MENÚ HAMBURGUESA
    // =====================================

    const navbar = document.getElementById("navbar");
    const botonMenu = document.getElementById("nav-toggle");
    const enlacesMenu = document.getElementById("nav-enlaces");

    if (navbar && botonMenu && enlacesMenu) {

        
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

        // Abrir y cerrar menú hamburguesa
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

        // Cerrar menú al seleccionar una opción
        enlacesMenu.querySelectorAll("a").forEach((enlace) => {

            enlace.addEventListener("click", () => {

                enlacesMenu.classList.remove("abierto");

                botonMenu.setAttribute("aria-expanded", "false");
                botonMenu.setAttribute("aria-label", "Abrir menú");

            });

        });

    }


    // =====================================
    // 2. ANIMACIONES AL HACER SCROLL
    // =====================================

    const reducirMovimiento = window.matchMedia(
        "(prefers-reduced-motion: reduce)"
    ).matches;

    if (
        !reducirMovimiento &&
        "IntersectionObserver" in window
    ) {

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


    // =====================================
    // 3. FORMULARIO DE RESERVACIONES
    // =====================================

    const formularioReserva = document.getElementById("form-reservacion");
    const fechaReserva = document.getElementById("fecha");
    const horaReserva = document.getElementById("hora");

    if (formularioReserva && fechaReserva && horaReserva) {

        // Obtener fecha actual en formato YYYY-MM-DD
        function fechaLocal() {

            const hoy = new Date();

            const anio = hoy.getFullYear();

            const mes = String(
                hoy.getMonth() + 1
            ).padStart(2, "0");

            const dia = String(
                hoy.getDate()
            ).padStart(2, "0");

            return `${anio}-${mes}-${dia}`;
        }


        // No permitir seleccionar fechas anteriores
        fechaReserva.min = fechaLocal();


        // Limpiar mensajes de validación
        function limpiarErrorFecha() {
            horaReserva.setCustomValidity("");
        }

        fechaReserva.addEventListener(
            "input",
            limpiarErrorFecha
        );

        horaReserva.addEventListener(
            "input",
            limpiarErrorFecha
        );


        // =====================================
        // 4. VALIDAR ANTES DE ENVIAR A PHP
        // =====================================

        formularioReserva.addEventListener("submit", (evento) => {

            // Limpiar errores anteriores
            horaReserva.setCustomValidity("");

            // Validar los campos obligatorios
            if (!formularioReserva.reportValidity()) {
                evento.preventDefault();
                return;
            }

            // Obtener la fecha y hora seleccionadas
            const fechaHora = new Date(
                `${fechaReserva.value}T${horaReserva.value}`
            );

            // Comprobar que sea una fecha futura
            if (
                Number.isNaN(fechaHora.getTime()) ||
                fechaHora <= new Date()
            ) {

                evento.preventDefault();

                horaReserva.setCustomValidity(
                    "Selecciona una fecha y hora futuras."
                );

                horaReserva.reportValidity();

                return;
            }

            // Todo correcto: permitir envío a PHP
            horaReserva.setCustomValidity("");

        });

    }

});
