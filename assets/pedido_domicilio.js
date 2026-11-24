
document.addEventListener('DOMContentLoaded', () => {

    const tarjetas = document.querySelectorAll('.producto');
    const contenedor = document.getElementById('carrito-items');
    const totalElemento = document.getElementById('carrito-total');
    const formulario = document.getElementById('form-pedido');
    const carritoInput = document.getElementById('carrito-json');
    const errorElemento = document.getElementById('error-carrito');
    const botonConfirmar = document.getElementById('btn-confirmar');

    // Productos actuales obtenidos del HTML generado por PHP
    const productos = {};

    tarjetas.forEach(tarjeta => {
        const id = Number(tarjeta.dataset.id);

        productos[id] = {
            id_producto: id,
            nombre: tarjeta.dataset.nombre,
            precio: Number(tarjeta.dataset.precio)
        };
    });

    // Carrito en memoria
    const carrito = {};

    const formatoPrecio = valor => {
        return new Intl.NumberFormat('es-MX', {
            style: 'currency',
            currency: 'MXN'
        }).format(valor);
    };

    function actualizarCarrito() {

        contenedor.replaceChildren();

        let total = 0;
        const items = Object.values(carrito);

        if (items.length === 0) {
            const vacio = document.createElement('p');
            vacio.className = 'vacio';
            vacio.textContent = 'Todavía no agregas productos.';
            contenedor.appendChild(vacio);
        }

        items.forEach(item => {

            const subtotal = item.precio * item.cantidad;
            total += subtotal;

            const fila = document.createElement('div');
            fila.className = 'item-carrito';

            const informacion = document.createElement('div');

            const nombre = document.createElement('strong');
            nombre.textContent = item.nombre;

            const precio = document.createElement('small');
            precio.textContent = formatoPrecio(subtotal);

            informacion.append(nombre, precio);

            const controles = document.createElement('div');
            controles.className = 'cantidad-controles';

            const menos = document.createElement('button');
            menos.type = 'button';
            menos.textContent = '−';
            menos.setAttribute(
                'aria-label',
                'Quitar uno de ' + item.nombre
            );

            const cantidad = document.createElement('span');
            cantidad.textContent = item.cantidad;

            const mas = document.createElement('button');
            mas.type = 'button';
            mas.textContent = '+';
            mas.setAttribute(
                'aria-label',
                'Agregar uno de ' + item.nombre
            );

            menos.addEventListener('click', () => {
                cambiarCantidad(item.id_producto, -1);
            });

            mas.addEventListener('click', () => {
                cambiarCantidad(item.id_producto, 1);
            });

            controles.append(menos, cantidad, mas);
            fila.append(informacion, controles);
            contenedor.appendChild(fila);
        });

        totalElemento.textContent = formatoPrecio(total);

        // Enviar únicamente ID y cantidad al servidor
        carritoInput.value = JSON.stringify(
            items.map(item => ({
                id_producto: item.id_producto,
                cantidad: item.cantidad
            }))
        );

        errorElemento.textContent = '';
    }

    function cambiarCantidad(id, cambio) {

        const producto = productos[id];

        if (!producto) return;

        const cantidadActual = carrito[id]?.cantidad || 0;
        const nuevaCantidad = cantidadActual + cambio;

        if (nuevaCantidad <= 0) {
            delete carrito[id];
        } else if (nuevaCantidad <= 50) {

            // Máximo 20 productos distintos
            if (
                !carrito[id] &&
                Object.keys(carrito).length >= 20
            ) {
                errorElemento.textContent =
                    'El carrito admite hasta 20 productos diferentes.';
                return;
            }

            carrito[id] = {
                ...producto,
                cantidad: nuevaCantidad
            };
        }

        actualizarCarrito();
    }

    // Agregar productos desde las tarjetas
    tarjetas.forEach(tarjeta => {

        const boton = tarjeta.querySelector('.agregar');

        boton.addEventListener('click', () => {
            cambiarCantidad(Number(tarjeta.dataset.id), 1);
        });
    });

    // Validar antes de enviar
    formulario.addEventListener('submit', event => {

        if (Object.keys(carrito).length === 0) {

            event.preventDefault();

            errorElemento.textContent =
                'Agrega al menos un producto antes de continuar.';

            return;
        }

        // Evitar envíos repetidos
        botonConfirmar.disabled = true;
        botonConfirmar.textContent = 'Enviando pedido...';
    });

    // Render inicial
    actualizarCarrito();
});
