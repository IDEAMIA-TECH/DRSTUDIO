Especificación en Markdown — Sitio Web con Panel de Administrador para Empresa de Promocionales

Objetivo

Desarrollar una página web con panel de administrador para una empresa que vende productos promocionales (playeras, vasos, tazas, gorras, lonas, etc.). El sistema debe ser sencillo de operar, rápido y mantenible.

⸻

Módulos del Sitio Web

1) Inicio
	•	Banner rotatorio de promociones del mes auto-administrable.
	•	Sección de productos destacados.
	•	Sección de clientes (logos o carrusel).
	•	Sección de recomendaciones/testimonios de clientes.
	•	Misión, Visión y Valores.

2) Contacto
	•	Formulario de contacto para solicitar información general (nombre, email, teléfono, mensaje).
	•	Envío de notificación al correo de administración y confirmación al usuario.

3) Productos
	•	Alta y visualización de productos con tallas, colores, variantes y descripción.
	•	El cliente podrá seleccionar todos los productos de interés y solicitar una cotización.
	•	El cliente enviará la cotización; esta será enviada a los administradores para su seguimiento.
	•	Filtros para buscar por tipo de producto y otros criterios relevantes (marca, color, material, precio, etc.).
	•	Paginación y buscador.

4) Galería
	•	Galería para mostrar los productos y proyectos más destacados (con título y breve descripción).

⸻

Panel de Administración
	•	Cotizador para que los administradores generen cotizaciones con base en los productos dados de alta, precios y tiempos de entrega.
	•	Alta/edición de productos (tiempos de entrega, fotos, precios, inventario, color, tallas, SKU, estado).
	•	Historial de cotizaciones guardadas y relacionadas con el cliente que las solicitó.
	•	Si una cotización es aceptada por el cliente, podrá aceptarse a través del sistema para llevar un registro; el cliente podrá subir su ficha de transferencia del anticipo.
	•	Reportes de ingresos y egresos con comparación visual (gráficas) para evaluar la salud del negocio.
	•	En el alta de productos se debe ingresar el costo de fabricación y el precio al cliente.
	•	Cotizador rápido: asistente para calcular precio al público y costos internos (ej. playera con DTF: tamaño del DTF, talla, costos variables y márgenes).
	•	Notificaciones por correo (y opcionalmente WhatsApp/SMS) a administradores y clientes cuando un pedido se inicie, se complete, se entregue o presente retraso.
	•	Gestión de banners, productos destacados y galería desde el panel.
	•	Control de usuarios y permisos (administrador, ventas, diseñador/producción, sólo lectura).

⸻

Tecnologías
	•	Backend: PHP
	•	Base de datos: MySQL
	•	Frontend: HTML, CSS, JavaScript, AJAX
	•	Buenas prácticas: arquitectura clara, validación en servidor y cliente, sanitización de datos, protección CSRF/XSS, paginación y cacheo donde aplique.

⸻

UX / Navegación
	•	Menú único en el panel de administración para cambios rápidos.
	•	Navegación clara y consistente en la página pública.
	•	Diseño responsivo (desktop, tablet, móvil).
	•	Cargas de imágenes optimizadas (WebP/JPG, compresión).

⸻

Base de Datos y Diseño
	•	Base de datos bien estructurada y relacionada (productos, variantes, inventarios, clientes, cotizaciones, pedidos, pagos, banners, galería, usuarios/roles).
	•	Diseño elegante y divertido, alineado a la imagen corporativa y a los colores del logo.
	•	Componentes reutilizables y estilos consistentes.

⸻

Flujo de Cotización (Resumen)
	1.	Cliente selecciona productos y envía solicitud de cotización.
	2.	Administrador revisa, calcula (con el cotizador) y envía propuesta.
	3.	Cliente acepta y sube la ficha de transferencia del anticipo.
	4.	Sistema actualiza estado, notifica y genera tareas internas (producción/entrega).
	5.	Cierre con entrega, facturación (si aplica) y registro en reportes.

⸻

Alcance y Principios
	•	Evitar complejidad innecesaria; simplicidad y orden en el desarrollo.
	•	Código modular, documentado y preparado para futuras integraciones (pasarelas de pago, facturación, etc.).
	•	Seguridad, rendimiento y SEO básico considerados desde el inicio.