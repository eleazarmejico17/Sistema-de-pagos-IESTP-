// js/simulador-solicitud.js

function guardarSolicitudSimulada(data) {
  const solicitudes = JSON.parse(localStorage.getItem("solicitudesSimuladas")) || [];
  solicitudes.push({
    ...data,
    id: Date.now(),
    fecha: new Date().toLocaleDateString("es-PE")
  });
  localStorage.setItem("solicitudesSimuladas", JSON.stringify(solicitudes));
}

function renderizarSolicitudes(selector, estadoFiltro = null) {
  const contenedor = document.querySelector(selector);
  if (!contenedor) return;

  const solicitudes = JSON.parse(localStorage.getItem("solicitudesSimuladas")) || [];

  contenedor.innerHTML = "";

  solicitudes
    .filter(s => !estadoFiltro || s.estado === estadoFiltro)
    .forEach(sol => {
      const card = document.createElement("div");
      card.className = "border-l-4 rounded-lg shadow-md card-hover border-yellow-400";
      card.innerHTML = `
        <div class="flex justify-between items-center p-5 cursor-pointer select-none bg-white" onclick="toggleMessage(this)">
          <div>
            <p class="font-semibold text-gray-800 text-sm">${sol.nombre}</p>
            <p class="text-gray-600 text-xs">Carrera: ${sol.tipo_solicitud}</p>
            <span class="text-xs font-medium text-yellow-600 mt-1">Pendiente</span>
          </div>
          <span class="text-black text-xl font-bold rotate-anim">▲</span>
        </div>
        <div class="transition-height bg-gray-50">
          <div class="px-5 pb-5">
            <p class="text-gray-700 text-sm mt-2">${sol.descripcion}</p>
            <div class="mt-4">
              <p class="font-semibold text-gray-700 text-sm">Fecha</p>
              <span class="text-xs text-gray-600">${sol.fecha_solicitud}</span>
            </div>
          </div>
        </div>
      `;
      contenedor.appendChild(card);
    });
}

window.toggleMessage = function (header) {
  const content = header.nextElementSibling;
  const arrow = header.querySelector(".rotate-anim");

  if (content.style.maxHeight && content.style.maxHeight !== "0px") {
    content.style.maxHeight = "0px";
    content.style.paddingTop = "0";
    content.style.paddingBottom = "0";
    arrow.style.transform = "rotate(0deg)";
  } else {
    content.style.paddingTop = "1.25rem";
    content.style.paddingBottom = "1.25rem";
    content.style.maxHeight = content.scrollHeight + "px";
    arrow.style.transform = "rotate(180deg)";
  }
};