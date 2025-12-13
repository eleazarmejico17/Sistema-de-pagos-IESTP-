<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Nuevo Registro de Solicitud</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
</head>
<body class="min-h-screen bg-gray-100 font-sans">

  <main class="w-full max-w-5xl mx-auto p-6">
    <div class="bg-white rounded-2xl shadow-xl border border-gray-200 overflow-hidden">

      <!-- Header -->
      <div class="bg-gradient-to-r from-blue-700 to-blue-500 text-white px-6 py-4 flex items-center gap-3 rounded-t-2xl shadow-md">
        <i class="fas fa-file-alt text-2xl"></i>
        <h2 class="text-xl font-semibold tracking-wide">Nuevo Registro de Solicitud</h2>
      </div>

      <!-- FORMULARIO -->
      <form id="formSolicitud" enctype="multipart/form-data">
        
        <!-- Contenido -->
        <div class="p-8 space-y-6">

          <!-- Mensaje de éxito (se mostrará arriba de todo) -->
          <div id="mensajeExito" class="hidden p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg mb-6">
            <div class="flex items-center">
              <i class="fas fa-check-circle text-green-500 text-xl mr-3"></i>
              <div>
                <h3 class="font-semibold">¡Solicitud Enviada!</h3>
                <p id="textoExito" class="text-sm mt-1">Tu solicitud ha sido registrada exitosamente.</p>
              </div>
              <button type="button" onclick="ocultarMensaje()" class="ml-auto text-green-500 hover:text-green-700">
                <i class="fas fa-times"></i>
              </button>
            </div>
          </div>

          <!-- Mensaje de error -->
          <div id="mensajeError" class="hidden p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg mb-6">
            <div class="flex items-center">
              <i class="fas fa-exclamation-triangle text-red-500 text-xl mr-3"></i>
              <div>
                <h3 class="font-semibold">Error</h3>
                <p id="textoError" class="text-sm mt-1"></p>
              </div>
              <button type="button" onclick="ocultarMensaje()" class="ml-auto text-red-500 hover:text-red-700">
                <i class="fas fa-times"></i>
              </button>
            </div>
          </div>

          <!-- Datos del usuario -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="flex flex-col gap-2">
              <label class="font-medium text-gray-700 flex items-center gap-2">
                    <i class="fas fa-id-card text-purple-500"></i> DNI del estudiante
                </label>
                <div class="relative">
                  <input 
                    type="text"
                    name="dni"
                    id="dni"
                    maxlength="8"
                    placeholder="Ingrese DNI (8 dígitos)"
                    class="border border-gray-300 rounded-lg px-4 py-2 w-full focus:ring-2 focus:ring-purple-400 focus:outline-none transition pr-10"
                    required>
                  <span id="dniLoader" class="hidden absolute right-3 top-1/2 transform -translate-y-1/2">
                    <i class="fas fa-spinner fa-spin text-purple-500"></i>
                  </span>
                  <span id="dniCheck" class="hidden absolute right-3 top-1/2 transform -translate-y-1/2">
                    <i class="fas fa-check-circle text-green-500"></i>
                  </span>
                </div>
            </div>
            <div class="flex flex-col gap-2">
              <label class="font-medium text-gray-700 flex items-center gap-2">
                <i class="fas fa-user text-blue-500"></i> Nombre completo
              </label>
              <input 
                type="text" 
                name="nombre"
                id="nombre"
                placeholder="Nombre del estudiante" 
                class="border border-gray-300 rounded-lg px-4 py-2 w-full focus:ring-2 focus:ring-blue-400 focus:outline-none transition"
                required>
            </div>

            <div class="flex flex-col gap-2">
              <label class="font-medium text-gray-700 flex items-center gap-2">
                <i class="fas fa-phone text-green-500"></i> Teléfono
              </label>
              <input 
                type="tel"
                name="telefono"
                id="telefono"
                placeholder="999-999-999" 
                class="border border-gray-300 rounded-lg px-4 py-2 w-full focus:ring-2 focus:ring-green-400 focus:outline-none transition"
                required>
            </div>

            <div class="flex flex-col gap-2">
              <label class="font-medium text-gray-700 flex items-center gap-2">
                <i class="fas fa-file-contract text-yellow-500"></i> Resolución
              </label>
              <select 
                name="tipo"
                id="tipo"
                class="border border-gray-300 rounded-lg px-4 py-2 w-full focus:ring-2 focus:ring-yellow-400 focus:outline-none transition"
                required>
                <option value="">Seleccionar resolución</option>
              </select>
              <p class="text-xs text-gray-500">Selecciona la resolución institucional a la que se asocia tu solicitud.</p>
            </div>

            <div class="flex flex-col gap-2">
              <label class="font-medium text-gray-700 flex items-center gap-2">
                <i class="fas fa-calendar-alt text-red-500"></i> Fecha de solicitud
              </label>
              <input 
                type="date"
                name="fecha"
                id="fecha"
                class="border border-gray-300 rounded-lg px-4 py-2 w-full focus:ring-2 focus:ring-red-400 focus:outline-none transition"
                required>
            </div>

          </div>

          <!-- Redactar solicitud -->
          <div class="flex flex-col gap-2">
            <label class="font-medium text-gray-700 flex items-center gap-2">
              <i class="fas fa-comment-alt text-blue-500"></i> Descripción de la solicitud
            </label>
            <textarea 
              name="descripcion"
              id="descripcion"
              rows="4" 
              placeholder="Describe tu solicitud..." 
              class="w-full border border-gray-300 rounded-xl p-4 focus:ring-2 focus:ring-blue-400 focus:outline-none transition resize-none"
              required></textarea>
          </div>

          <!-- Archivos -->
          <div class="flex flex-col gap-2">
            <label class="font-medium text-gray-700 flex items-center gap-2">
              <i class="fas fa-paperclip text-blue-500"></i> Evidencias
            </label>
            <p class="text-gray-500 text-sm mb-2">Agrega imágenes o documentos (máximo 5 archivos, 5MB cada uno)</p>
            <input 
              type="file" 
              name="archivo[]" 
              id="archivo"
              multiple 
              accept=".jpg,.jpeg,.png,.pdf,.doc,.docx"
              class="border border-dashed border-gray-300 rounded-xl p-4 cursor-pointer hover:border-blue-400 transition file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
              onchange="validarArchivos(this)">
            <p id="errorArchivos" class="text-red-500 text-sm hidden"></p>
          </div>

          <!-- Botón enviar -->
          <div class="flex justify-start pt-4">
            <button 
              type="submit"
              id="btnEnviar"
              class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-8 py-3 rounded-xl shadow-md transition transform hover:-translate-y-1 hover:shadow-xl flex items-center gap-2">
              <i class="fas fa-paper-plane"></i> Enviar Solicitud
            </button>
            
            <button 
              type="button"
              id="btnLoading"
              class="hidden bg-blue-400 text-white font-semibold px-8 py-3 rounded-xl shadow-md flex items-center gap-2 cursor-not-allowed">
              <i class="fas fa-spinner fa-spin"></i> Enviando...
            </button>
          </div>

        </div>

      </form>
    </div>
  </main>

  <script>
function ocultarMensaje() {
  document.getElementById('mensajeExito').classList.add('hidden');
  document.getElementById('mensajeError').classList.add('hidden');
}

// SOLO NÚMEROS EN DNI Y TELÉFONO
const dniInput = document.getElementById("dni");
const nombreInput = document.getElementById("nombre");
const telefonoInput = document.getElementById("telefono");

dniInput.addEventListener("input", e => {
  e.target.value = e.target.value.replace(/\D/g, "");
  
  // Búsqueda automática cuando se ingresen 8 dígitos
  const dni = e.target.value.trim();
  if (dni.length === 8) {
    buscarEstudiantePorDNI(dni);
  } else if (dni.length < 8) {
    // Limpiar campos si se borra el DNI
    nombreInput.value = "";
    telefonoInput.value = "";
    // Ocultar íconos de estado
    document.getElementById("dniLoader").classList.add("hidden");
    document.getElementById("dniCheck").classList.add("hidden");
    dniInput.style.borderColor = "";
  }
});

document.getElementById("telefono").addEventListener("input", e => {
  e.target.value = e.target.value.replace(/\D/g, "");
});

// VALIDACIÓN DE ARCHIVOS
function validarArchivos(input) {
  const errorElement = document.getElementById('errorArchivos');
  const archivos = input.files;
  let errores = [];

  if (archivos.length > 5) errores.push("Máximo 5 archivos");

  const tiposPermitidos = ['image/jpeg','image/png','application/pdf','application/msword','application/vnd.openxmlformats-officedocument.wordprocessingml.document'];

  for (let archivo of archivos) {
    if (archivo.size > 5 * 1024 * 1024) errores.push(`${archivo.name} excede 5MB`);
    if (!tiposPermitidos.includes(archivo.type)) errores.push(`${archivo.name} tipo no permitido`);
  }

  if (errores.length) {
    errorElement.textContent = errores.join(" | ");
    errorElement.classList.remove("hidden");
    input.value = "";
    return false;
  } else {
    errorElement.classList.add("hidden");
    return true;
  }
}

// VALIDACIÓN COMPLETA DEL FORMULARIO
document.getElementById("formSolicitud").addEventListener("submit", function(e) {
  e.preventDefault();
  ocultarMensaje();

  const dni = document.getElementById("dni").value.trim();
  const nombre = document.getElementById("nombre").value.trim();
  const telefono = document.getElementById("telefono").value.trim();
  const tipo = document.getElementById("tipo").value;
  const fecha = document.getElementById("fecha").value;
  const descripcion = document.getElementById("descripcion").value.trim();
  const archivoInput = document.getElementById("archivo");
  const mensajeError = document.getElementById("textoError");

  // VALIDACIONES
  let errores = [];

  if (!/^\d{8}$/.test(dni)) errores.push("DNI inválido (8 dígitos)");

  if (!/^[a-zA-ZÁÉÍÓÚáéíóúñÑ\s]{6,}$/.test(nombre)) errores.push("Nombre inválido");

  if (!/^9\d{8}$/.test(telefono)) errores.push("Teléfono inválido");

  if (!tipo) errores.push("Seleccione una resolución");

  const hoy = new Date().toISOString().split("T")[0];
  if (fecha > hoy) errores.push("Fecha no válida");

  if (descripcion.length < 10) errores.push("Descripción muy corta (mínimo 10 caracteres)");

  if (archivoInput.files.length > 0 && !validarArchivos(archivoInput)) return;

  if (errores.length > 0) {
    document.getElementById("mensajeError").classList.remove("hidden");
    mensajeError.textContent = errores.join(" | ");
    return;
  }

  // UI LOADING
  document.getElementById("btnEnviar").classList.add("hidden");
  document.getElementById("btnLoading").classList.remove("hidden");

  // ENVÍO AJAX
  const formData = new FormData(this);

  fetch('../controller/guardarSolicitudController.php', {
    method: "POST",
    body: formData
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      document.getElementById("mensajeExito").classList.remove("hidden");
      document.getElementById("textoExito").textContent = data.message;
      document.getElementById("formSolicitud").reset();
      document.getElementById("fecha").value = hoy;
    } else {
      document.getElementById("mensajeError").classList.remove("hidden");
      mensajeError.textContent = data.error;
    }
  })
  .catch(() => {
    document.getElementById("mensajeError").classList.remove("hidden");
    mensajeError.textContent = "Error de conexión con el servidor";
  })
  .finally(() => {
    document.getElementById("btnEnviar").classList.remove("hidden");
    document.getElementById("btnLoading").classList.add("hidden");
  });
});

// FUNCIÓN PARA BUSCAR ESTUDIANTE POR DNI
function buscarEstudiantePorDNI(dni) {
  const dniLoader = document.getElementById("dniLoader");
  const dniCheck = document.getElementById("dniCheck");
  
  // Mostrar indicador de carga
  dniInput.disabled = true;
  dniInput.style.backgroundColor = "#f3f4f6";
  dniLoader.classList.remove("hidden");
  dniCheck.classList.add("hidden");
  
  // Limpiar mensajes previos
  ocultarMensaje();
  
  // Calcular ruta correcta según la ubicación actual
  const currentPath = window.location.pathname;
  let apiPath;
  
  if (currentPath.includes('/views/')) {
    // Desde dashboard-usuario.php en views/
    apiPath = '../controller/buscarEstudianteSolicitud.php';
  } else {
    // Ruta alternativa
    apiPath = '../../controller/buscarEstudianteSolicitud.php';
  }
  
  fetch(`${apiPath}?dni=${dni}`)
    .then(response => response.json())
    .then(data => {
      if (data.success && data.estudiante) {
        // Autocompletar campos
        nombreInput.value = data.estudiante.nombre_completo || "";
        telefonoInput.value = data.estudiante.telefono || "";
        
        // Mostrar check de éxito
        dniLoader.classList.add("hidden");
        dniCheck.classList.remove("hidden");
        
        // Estilo visual de éxito
        nombreInput.style.borderColor = "#10b981";
        telefonoInput.style.borderColor = "#10b981";
        dniInput.style.borderColor = "#10b981";
        
        // Remover estilo después de 2 segundos
        setTimeout(() => {
          nombreInput.style.borderColor = "";
          telefonoInput.style.borderColor = "";
          dniInput.style.borderColor = "";
          dniCheck.classList.add("hidden");
        }, 2000);
      } else {
        // Limpiar campos si no se encontró
        nombreInput.value = "";
        telefonoInput.value = "";
        dniLoader.classList.add("hidden");
        dniCheck.classList.add("hidden");
        
        // Mostrar mensaje de advertencia
        document.getElementById("mensajeError").classList.remove("hidden");
        document.getElementById("textoError").textContent = data.message || "No se encontró un estudiante con ese DNI";
        
        // Auto-ocultar mensaje después de 3 segundos
        setTimeout(() => {
          ocultarMensaje();
        }, 3000);
      }
    })
    .catch(error => {
      console.error("Error al buscar estudiante:", error);
      nombreInput.value = "";
      telefonoInput.value = "";
      dniLoader.classList.add("hidden");
      dniCheck.classList.add("hidden");
      
      // Mostrar mensaje de error
      document.getElementById("mensajeError").classList.remove("hidden");
      document.getElementById("textoError").textContent = "Error al conectar con el servidor. Intente nuevamente.";
      
      setTimeout(() => {
        ocultarMensaje();
      }, 3000);
    })
    .finally(() => {
      // Rehabilitar campo DNI
      dniInput.disabled = false;
      dniInput.style.backgroundColor = "";
    });
}

// FECHA HOY AUTOMÁTICA
document.addEventListener("DOMContentLoaded", () => {
  document.getElementById("fecha").value = new Date().toISOString().split("T")[0];

  // Cargar resoluciones en el select de tipo (Resolución)
  const selectResolucion = document.getElementById("tipo");
  if (selectResolucion) {
    const currentPath = window.location.pathname;
    let apiPath;

    if (currentPath.includes('/views/')) {
      // Estamos dentro de views/ (por ejemplo, dashboard-usuario)
      apiPath = '../controller/listarResolucionesPublic.php';
    } else {
      // Ruta alternativa
      apiPath = '../../controller/listarResolucionesPublic.php';
    }

    fetch(apiPath)
      .then(r => r.json())
      .then(data => {
        if (!data.success || !Array.isArray(data.data)) return;

        // Limpiar excepto la primera opción
        const firstOption = selectResolucion.querySelector('option');
        selectResolucion.innerHTML = '';
        if (firstOption) {
          selectResolucion.appendChild(firstOption);
        } else {
          const opt = document.createElement('option');
          opt.value = '';
          opt.textContent = 'Seleccionar resolución';
          selectResolucion.appendChild(opt);
        }

        data.data.forEach(res => {
          const opt = document.createElement('option');
          opt.value = res.id; // enviamos el id de la resolución
          opt.textContent = `${res.numero_resolucion} - ${res.titulo}`;
          selectResolucion.appendChild(opt);
        });
      })
      .catch(err => {
        console.error('Error cargando resoluciones:', err);
      });
  }
});
</script>


</body>
</html>