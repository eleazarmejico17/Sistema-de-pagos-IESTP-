
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Sistema de Descuentos - IESTP Andrés A. Cáceres</title>

  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />

  <style>
    /* Animaciones personalizadas */
    @keyframes fadeInUp { 
      0% { opacity: 0; transform: translateY(18px); } 
      100% { opacity: 1; transform: translateY(0); } 
    }
    .fade-in-up { animation: fadeInUp 0.8s ease forwards; }

    @keyframes float { 
      0% { transform: translateY(0); } 
      50% { transform: translateY(-6px); } 
      100% { transform: translateY(0); } 
    }
    .float { animation: float 4s ease-in-out infinite; }

    @keyframes pulseGlow { 
      0% { box-shadow: 0 0 0 rgba(204,168,49,0.0);} 
      50% { box-shadow: 0 0 18px rgba(204,168,49,0.14);} 
      100% { box-shadow: 0 0 0 rgba(204,168,49,0);} 
    }
    .glow:hover { animation: pulseGlow 1.2s ease-in-out; }

    /* Efectos adicionales */
    .hero-overlay { background: linear-gradient(180deg, rgba(0,43,119,0.45) 0%, rgba(0,0,0,0.65) 100%); }
    .accent { color: #CCA831; }
  </style>
</head>

<body class="bg-gray-100 text-gray-800 leading-relaxed">

  <!-- HEADER -->
  <header class="bg-white shadow sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-6 py-4 flex items-center justify-between">

      <div class="flex items-center gap-4">
        <img src="public/img/logo1.png" alt="Logo IESTP" class="w-16 h-16 rounded-md object-cover shadow-sm">
        <div>
          <h1 class="text-xl md:text-2xl font-extrabold text-[#002B77] uppercase">
            IESTP Andrés Avelino Cáceres
          </h1>
          <p class="text-sm text-gray-600">Comprometidos con la excelencia educativa</p>
        </div>
      </div>

      <div class="text-sm text-right">
        <p class="text-gray-600">📞 064-421149 | Lun a Vie: 8:00 a.m. - 4:30 p.m.</p>
        <p class="text-gray-600">📧 tramites@institutocajas.edu.pe</p>
      </div>

    </div>

    <nav class="bg-[#002B77] text-white">
      <div class="max-w-7xl mx-auto px-6">
        <ul class="flex flex-wrap justify-center gap-6 py-3 text-xs md:text-sm uppercase font-medium tracking-wide">
          <li><a href="#inicio" class="hover:underline hover:text-[#CCA831] transition">Inicio</a></li>
          <li><a href="#sobre" class="hover:underline hover:text-[#CCA831] transition">Sobre el sistema</a></li>
          <li><a href="#tipos" class="hover:underline hover:text-[#CCA831] transition">Tipos de descuentos</a></li>
          <li><a href="#requisitos" class="hover:underline hover:text-[#CCA831] transition">Requisitos</a></li>
          <li><a href="#faq" class="hover:underline hover:text-[#CCA831] transition">FAQ</a></li>
          <li><a href="#contacto" class="text-[#CCA831] font-semibold">Descuentos</a></li>
        </ul>
      </div>
    </nav>
  </header>

  <!-- HERO -->
  <section id="inicio" class="relative bg-cover bg-center h-[640px] md:h-[560px]" 
    style="background-image: url('https://images.unsplash.com/photo-1573497491208-6b1acb260507?auto=format&fit=crop&w=1400&q=60');">

    <div class="absolute inset-0 hero-overlay"></div>

    <div class="relative max-w-6xl mx-auto px-6 py-28 text-white">
      <div class="md:flex md:items-start md:gap-12">

        <div class="md:w-7/12">
          <h2 class="text-4xl md:text-5xl font-extrabold leading-tight drop-shadow-lg">
            Sistema de Pagos y Descuentos para Estudiantes
          </h2>

          <p class="mt-6 text-lg md:text-xl bg-[#CCA831] inline-block text-black px-4 py-3 rounded-2xl shadow-lg font-medium">
            Apoyo económico institucional para facilitar la continuidad académica
          </p>

          <div class="mt-6 text-sm md:text-base bg-white bg-opacity-90 text-gray-800 p-6 rounded-2xl shadow-lg">
            <p class="mb-3">
              El Instituto de Educación Superior Tecnológico Público Andrés Avelino Cáceres Dorregaray impulsa
              un sistema integral de descuentos dirigido a estudiantes, docentes y administrativos.
            </p>
            <p>
              Con este sistema, los beneficiarios podrán acceder a descuentos escalonados, seguir el estado
              de sus solicitudes digitalmente y recibir notificaciones oficiales en su correo institucional.
            </p>
          </div>

          <div class="mt-6 flex gap-4">
            <a href="public/login.html" class="inline-flex items-center gap-3 bg-[#002B77] text-white px-6 py-3 rounded-full shadow-lg hover:bg-blue-900 transition font-semibold">
              <i class="fa-solid fa-right-to-bracket"></i> Ingresar al sistema
            </a>
            <a href="#requisitos" class="inline-flex items-center gap-2 bg-white text-[#002B77] px-5 py-3 rounded-full shadow hover:underline transition">
              <i class="fa-solid fa-file-lines"></i> Requisitos
            </a>
          </div>
        </div>

        <div class="md:w-5/12 mt-10 md:mt-0">
          <div class="bg-white/90 p-6 rounded-2xl shadow-2xl text-gray-800">

            <h3 class="text-lg font-bold text-[#002B77] flex items-center gap-3">
              <i class="fa-solid fa-circle-info text-[#CCA831]"></i> Información rápida
            </h3>

            <ul class="mt-4 space-y-3 text-sm">
              <li class="flex items-start gap-3"><i class="fa-solid fa-check text-[#002B77] mt-1"></i> Acceso online 24/7</li>
              <li class="flex items-start gap-3"><i class="fa-solid fa-shield-halved text-[#002B77] mt-1"></i> Gestión transparente y segura</li>
              <li class="flex items-start gap-3"><i class="fa-solid fa-envelope text-[#002B77] mt-1"></i> Notificaciones al correo institucional</li>
            </ul>

            <div class="mt-6">
              <h4 class="text-sm font-semibold text-gray-600">Contacto rápido</h4>
              <p class="text-sm">tramites@institutocajas.edu.pe</p>
              <p class="text-sm">064-421149 (Lun a Vie: 8:00 - 16:30)</p>
            </div>

          </div>
        </div>

      </div>
    </div>
  </section>


  <!-- SOBRE EL SISTEMA -->
  <section id="sobre" class="max-w-6xl mx-auto px-6 py-16">
    <div class="grid md:grid-cols-2 gap-10 items-center mt-16">

  <div class="fade-in-up">
    <h3 class="text-3xl font-bold text-[#002B77]">¿Qué es el Sistema de Descuentos?</h3>

    <p class="mt-4 text-gray-700">
      Es una plataforma institucional que centraliza la gestión de todos los beneficios económicos otorgados por el instituto.
    </p>

    <ul class="mt-6 space-y-3 text-gray-700">
      <li class="flex items-start gap-3">
        <i class="fa-solid fa-gavel text-[#CCA831] mt-1"></i>
        <strong>Transparencia:</strong> registro claro de solicitudes y resoluciones.
      </li>

      <li class="flex items-start gap-3">
        <i class="fa-solid fa-clock text-[#CCA831] mt-1"></i>
        <strong>Rapidez:</strong> procesos digitales que reducen tiempos.
      </li>

      <li class="flex items-start gap-3">
        <i class="fa-solid fa-user-check text-[#CCA831] mt-1"></i>
        <strong>Accesibilidad:</strong> acceso desde cualquier dispositivo.
      </li>
    </ul>

    <p class="mt-6 text-gray-600">
      El sistema además permite generar reportes administrativos para la toma de decisiones.
    </p>
  </div>

</div>

    </div>
  </section>

  <!-- TIPOS DE DESCUENTOS -->
  <section id="tipos" class="bg-gray-50 py-16">
    <div class="max-w-6xl mx-auto px-6">

      <h3 class="text-3xl font-bold text-center text-[#002B77]">Tipos de descuentos disponibles</h3>

      <p class="mt-4 text-center text-gray-600 max-w-3xl mx-auto">
        A continuación se describen los tipos de descuentos que pueden solicitarse.
      </p>

      <div class="mt-10 grid md:grid-cols-3 gap-6">
        <div class="bg-white p-6 rounded-2xl shadow-lg border-t-4 border-[#CCA831]">
          <h4 class="text-xl font-bold text-[#002B77] flex items-center gap-3">
            <i class="fa-solid fa-user-graduate text-[#CCA831]"></i> Académico
          </h4>
          <p class="mt-3 text-gray-700">
            Para estudiantes con alto rendimiento académico.
          </p>
        </div>

        <div class="bg-white p-6 rounded-2xl shadow-lg border-t-4 border-[#CCA831]">
          <h4 class="text-xl font-bold text-[#002B77] flex items-center gap-3">
            <i class="fa-solid fa-hand-holding-heart text-[#CCA831]"></i> Social
          </h4>
          <p class="mt-3 text-gray-700">
            Para estudiantes en situación económica vulnerable.
          </p>
        </div>

        <div class="bg-white p-6 rounded-2xl shadow-lg border-t-4 border-[#CCA831]">
          <h4 class="text-xl font-bold text-[#002B77] flex items-center gap-3">
            <i class="fa-solid fa-chalkboard-user text-[#CCA831]"></i> Docente/Administrativo
          </h4>
          <p class="mt-3 text-gray-700">
            Beneficios aplicables al personal del instituto.
          </p>
        </div>
      </div>

      <div class="mt-8 grid md:grid-cols-2 gap-6">

        <div class="bg-white p-6 rounded-2xl shadow-lg">
          <h4 class="font-bold text-[#002B77]">Descuento por pronto pago</h4>
          <p class="mt-2 text-gray-700">
            Incentivo para quienes realizan el pago oportunamente.
          </p>
        </div>

        <div class="bg-white p-6 rounded-2xl shadow-lg">
          <h4 class="font-bold text-[#002B77]">Programas especiales</h4>
          <p class="mt-2 text-gray-700">
            Descuentos por convenios institucionales.
          </p>
        </div>

      </div>

    </div>
  </section>

  <!-- REQUISITOS -->
  <section id="requisitos" class="max-w-6xl mx-auto px-6 py-16">

    <h3 class="text-3xl font-bold text-center text-[#002B77]">Requisitos y documentos necesarios</h3>

    <p class="mt-4 text-center text-gray-600 max-w-3xl mx-auto">
      Guía completa según el tipo de descuento.
    </p>

    <div class="mt-10 grid md:grid-cols-2 gap-8">

      <div class="bg-white p-6 rounded-2xl shadow-lg">
        <h4 class="text-xl font-bold text-[#002B77]">Requisitos generales</h4>

        <ul class="mt-4 space-y-2 text-gray-700">
          <li class="flex items-start gap-3"><i class="fa-solid fa-id-card text-[#CCA831] mt-1"></i> Alumno matriculado.</li>
          <li class="flex items-start gap-3"><i class="fa-solid fa-envelope text-[#CCA831] mt-1"></i> Correo institucional activo.</li>
          <li class="flex items-start gap-3"><i class="fa-solid fa-clock text-[#CCA831] mt-1"></i> Cumplir con los plazos.</li>
        </ul>
      </div>

      <div class="bg-white p-6 rounded-2xl shadow-lg">
        <h4 class="text-xl font-bold text-[#002B77]">Documentos (ejemplos)</h4>

        <ul class="mt-4 space-y-2 text-gray-700">
          <li class="flex items-start gap-3"><i class="fa-solid fa-file-lines text-[#CCA831] mt-1"></i> Formulario completo.</li>
          <li class="flex items-start gap-3"><i class="fa-solid fa-file-invoice text-[#CCA831] mt-1"></i> Copia de DNI.</li>
          <li class="flex items-start gap-3"><i class="fa-solid fa-graduation-cap text-[#CCA831] mt-1"></i> Certificados académicos.</li>
          <li class="flex items-start gap-3"><i class="fa-solid fa-hand-holding-dollar text-[#CCA831] mt-1"></i> Constancia socioeconómica.</li>
        </ul>
      </div>

    </div>

    <div class="mt-8 text-sm text-gray-600">
      Nota: todos los documentos deben subirse en PDF/JPG.
    </div>

  </section>

  <!-- PROCESO DETALLADO -->
  <section class="bg-[#002B77] text-white py-16">
    <div class="max-w-6xl mx-auto px-6">

      <h3 class="text-3xl font-bold text-center">Proceso detallado para ser beneficiario</h3>

      <p class="mt-4 text-center max-w-3xl mx-auto text-[#f0f9ff]">
        Cada paso incluye instrucciones claras.
      </p>

      <div class="mt-10 grid md:grid-cols-3 gap-6">

        <!-- Paso 1 -->
        <div class="p-6 rounded-2xl bg-white text-gray-800 shadow-lg">
          <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-full bg-[#002B77] text-white flex items-center justify-center font-bold">1</div>
            <h4 class="font-bold">Regístrate</h4>
          </div>
          <p class="mt-4 text-sm">Tiempo estimado: 5 minutos.</p>
        </div>

        <!-- Paso 2 -->
        <div class="p-6 rounded-2xl bg-white text-gray-800 shadow-lg">
          <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-full bg-[#002B77] text-white flex items-center justify-center font-bold">2</div>
            <h4 class="font-bold">Envía una solicitud</h4>
          </div>
          <p class="mt-4 text-sm">Tiempo estimado: 10–20 minutos.</p>
        </div>

        <!-- Paso 3 -->
        <div class="p-6 rounded-2xl bg-white text-gray-800 shadow-lg">
          <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-full bg-[#002B77] text-white flex items-center justify-center font-bold">3</div>
            <h4 class="font-bold">Verificar y evaluar</h4>
          </div>
          <p class="mt-4 text-sm">Tiempo estimado: 5–15 días hábiles.</p>
        </div>

      </div>

      <div class="mt-6 grid md:grid-cols-3 gap-6">

        <!-- Paso 4 -->
        <div class="p-6 rounded-2xl bg-white text-gray-800 shadow-lg">
          <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-full bg-[#002B77] text-white flex items-center justify-center font-bold">4</div>
            <h4 class="font-bold">Notificación</h4>
          </div>
        </div>

        <!-- Paso 5 -->
        <div class="p-6 rounded-2xl bg-white text-gray-800 shadow-lg">
          <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-full bg-[#002B77] text-white flex items-center justify-center font-bold">5</div>
            <h4 class="font-bold">Aplicación del descuento</h4>
          </div>
        </div>

        <!-- Paso final -->
        <div class="p-6 rounded-2xl bg-white text-gray-800 shadow-lg">
          <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-full bg-[#002B77] text-white flex items-center justify-center font-bold">✓</div>
            <h4 class="font-bold">Seguimiento</h4>
          </div>
        </div>

      </div>

    </div>
  </section>

  <!-- TESTIMONIOS -->
  <section class="max-w-6xl mx-auto px-6 py-16">

    <h3 class="text-3xl font-bold text-center text-[#002B77]">Testimonios</h3>

    <p class="mt-4 text-center text-gray-600">
      Experiencias reales de beneficiarios.
    </p>

    <div class="mt-8 grid md:grid-cols-3 gap-6">

      <blockquote class="bg-white p-6 rounded-2xl shadow-lg">
        <p class="text-gray-700">
          "El descuento me permitió continuar mis estudios."
        </p>
        <footer class="mt-4 text-sm text-gray-600">— María P.</footer>
      </blockquote>

      <blockquote class="bg-white p-6 rounded-2xl shadow-lg">
        <p class="text-gray-700">
          "Proceso claro y notificación rápida."
        </p>
        <footer class="mt-4 text-sm text-gray-600">— Juan R.</footer>
      </blockquote>

      <blockquote class="bg-white p-6 rounded-2xl shadow-lg">
        <p class="text-gray-700">
          "La plataforma es muy intuitiva."
        </p>
        <footer class="mt-4 text-sm text-gray-600">— Ana M.</footer>
      </blockquote>

    </div>

  </section>

  <!-- FAQ -->
  <section id="faq" class="bg-gray-50 py-16">
    <div class="max-w-6xl mx-auto px-6">

      <h3 class="text-3xl font-bold text-center text-[#002B77]">Preguntas frecuentes (FAQ)</h3>

      <div class="mt-8 space-y-4">

        <details class="bg-white p-4 rounded-xl shadow">
          <summary class="cursor-pointer font-semibold">¿Quién puede solicitar un descuento?</summary>
          <div class="mt-2 text-gray-700">
            Estudiantes, docentes y administrativos.
          </div>
        </details>

        <details class="bg-white p-4 rounded-xl shadow">
          <summary class="cursor-pointer font-semibold">¿Cuánto demora la evaluación?</summary>
          <div class="mt-2 text-gray-700">
            Entre 5 y 15 días hábiles.
          </div>
        </details>

        <details class="bg-white p-4 rounded-xl shadow">
          <summary class="cursor-pointer font-semibold">¿Puedo apelar una decisión?</summary>
          <div class="mt-2 text-gray-700">
            Sí, dentro de los plazos establecidos.
          </div>
        </details>

        <details class="bg-white p-4 rounded-xl shadow">
          <summary class="cursor-pointer font-semibold">¿Cómo sabré si fui beneficiario?</summary>
          <div class="mt-2 text-gray-700">
            Recibirás una notificación a tu correo.
          </div>
        </details>

      </div>

    </div>
  </section>

  <!-- CONTACTO -->
  <section id="contacto" class="max-w-6xl mx-auto px-6 py-16">

    <div class="bg-white p-8 rounded-2xl shadow-lg md:flex md:items-center md:justify-between gap-6">

      <div>
        <h3 class="text-2xl font-bold text-[#002B77]">¿Listo para solicitar tu descuento?</h3>
        <p class="mt-2 text-gray-600">Accede al sistema o contáctanos.</p>
      </div>

      <div class="flex flex-col sm:flex-row gap-4 items-center">
        <a href="public/login.html" class="inline-flex items-center gap-3 bg-[#002B77] text-white px-6 py-3 rounded-full shadow-lg hover:bg-blue-900 transition font-semibold">
          <i class="fa-solid fa-right-to-bracket"></i> Ingresar al sistema
        </a>

        <a href="mailto:tramites@institutocajas.edu.pe" class="inline-flex items-center gap-3 bg-white text-[#002B77] px-5 py-3 rounded-full shadow hover:underline transition">
          <i class="fa-solid fa-envelope"></i> tramites@institutocajas.edu.pe
        </a>
      </div>

    </div>

    <div class="mt-8 grid md:grid-cols-3 gap-6 text-sm text-gray-600">

      <div class="bg-white p-4 rounded-xl shadow">
        <h4 class="font-semibold text-[#002B77]">Horario de atención</h4>
        <p>Lun a Vie: 8:00 a.m. - 4:30 p.m.</p>
        <p>Teléfono: 064-421149</p>
      </div>

      <div class="bg-white p-4 rounded-xl shadow">
        <h4 class="font-semibold text-[#002B77]">Dirección</h4>
        <p>Instituto de Educación Superior Tecnológico Público A. A. Cáceres</p>
      </div>

      <div class="bg-white p-4 rounded-xl shadow">
        <h4 class="font-semibold text-[#002B77]">Avisos</h4>
        <p>Las convocatorias se publicarán en el portal.</p>
      </div>

    </div>

  </section>

  <!-- FOOTER -->
  <footer class="bg-[#002B77] text-white py-6 mt-12">
    <div class="max-w-7xl mx-auto px-6 flex flex-col md:flex-row justify-between items-center gap-4">

      <p class="text-sm">© 2025 Instituto A. A. Cáceres - Todos los derechos reservados.</p>

      <div class="flex gap-4">
        <a href="#" aria-label="Facebook" class="text-white hover:accent"><i class="fa-brands fa-facebook fa-lg"></i></a>
        <a href="#" aria-label="Instagram" class="text-white hover:accent"><i class="fa-brands fa-instagram fa-lg"></i></a>
        <a href="#" aria-label="Twitter" class="text-white hover:accent"><i class="fa-brands fa-twitter fa-lg"></i></a>
      </div>

    </div>
  </footer>

</body>
</html>
