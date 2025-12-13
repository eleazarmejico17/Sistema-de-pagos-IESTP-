// js/login.js
(() => {
  "use strict";

  /* ----------  UTILS  ---------- */
  function mostrarError(mensaje) {
    console.log("mostrarError llamado con:", mensaje);
    
    // Ocultar el toast si está visible
    const toast = document.getElementById("toast");
    if (toast) {
      toast.style.display = "none";
      toast.style.opacity = "0";
      toast.style.visibility = "hidden";
      if (toast.timeoutId) {
        clearTimeout(toast.timeoutId);
      }
    }
    
    const errorContainer = document.getElementById("error-message");
    const errorText = document.getElementById("error-text");
    
    console.log("errorContainer:", errorContainer);
    console.log("errorText:", errorText);
    
    if (!errorContainer || !errorText) {
      console.error("Error container not found");
      return;
    }
    
    // Limpiar cualquier timeout anterior
    if (errorContainer.timeoutId) {
      clearTimeout(errorContainer.timeoutId);
    }
    
    // Establecer el mensaje
    errorText.textContent = mensaje;
    
    // Asegurar que el contenedor esté visible
    errorContainer.removeAttribute("hidden");
    errorContainer.classList.remove("hidden");
    errorContainer.style.display = "block";
    errorContainer.style.visibility = "visible";
    
    // Resetear estilos de animación
    errorContainer.style.opacity = "0";
    errorContainer.style.transform = "translateY(-10px)";
    
    // Forzar reflow
    void errorContainer.offsetWidth;
    
    // Animar entrada
    requestAnimationFrame(() => {
      errorContainer.style.transition = "all 0.3s ease-out";
      errorContainer.style.opacity = "1";
      errorContainer.style.transform = "translateY(0)";
    });
    
    // Ocultar después de 5 segundos
    errorContainer.timeoutId = setTimeout(() => {
      errorContainer.style.opacity = "0";
      errorContainer.style.transform = "translateY(-10px)";
      setTimeout(() => {
        errorContainer.style.display = "none";
        errorContainer.style.visibility = "hidden";
        errorContainer.classList.add("hidden");
        errorContainer.setAttribute("hidden", "true");
        errorText.textContent = "";
      }, 300);
    }, 5000);
  }

  function ocultarError() {
    const errorContainer = document.getElementById("error-message");
    if (errorContainer) {
      if (errorContainer.timeoutId) {
        clearTimeout(errorContainer.timeoutId);
      }
      errorContainer.style.opacity = "0";
      errorContainer.style.transform = "translateY(-10px)";
      setTimeout(() => {
        errorContainer.style.display = "none";
        errorContainer.classList.add("hidden");
        errorContainer.setAttribute("hidden", "true");
        const errorText = document.getElementById("error-text");
        if (errorText) {
          errorText.textContent = "";
        }
      }, 300);
    }
  }

  function mostrarExito(mensaje) {
    console.log("mostrarExito llamado con:", mensaje);
    
    // Ocultar el toast si está visible
    const toast = document.getElementById("toast");
    if (toast) {
      toast.style.display = "none";
      toast.style.opacity = "0";
      toast.style.visibility = "hidden";
      if (toast.timeoutId) {
        clearTimeout(toast.timeoutId);
      }
    }
    
    // Ocultar el mensaje de error si está visible
    ocultarError();
    
    const successContainer = document.getElementById("success-message");
    const successText = document.getElementById("success-text");
    
    console.log("successContainer:", successContainer);
    console.log("successText:", successText);
    
    if (!successContainer || !successText) {
      console.error("Success container not found");
      return;
    }
    
    // Limpiar cualquier timeout anterior
    if (successContainer.timeoutId) {
      clearTimeout(successContainer.timeoutId);
    }
    
    // Establecer el mensaje
    successText.textContent = mensaje;
    
    // Asegurar que el contenedor esté visible
    successContainer.removeAttribute("hidden");
    successContainer.classList.remove("hidden");
    successContainer.style.display = "flex";
    successContainer.style.visibility = "visible";
    successContainer.style.zIndex = "10";
    
    // Resetear estilos de animación
    successContainer.style.opacity = "0";
    successContainer.style.transform = "translateY(-10px)";
    
    // Forzar reflow
    void successContainer.offsetWidth;
    
    // Animar entrada
    requestAnimationFrame(() => {
      successContainer.style.transition = "all 0.3s ease-out";
      successContainer.style.opacity = "1";
      successContainer.style.transform = "translateY(0)";
    });
  }

  function mostrarToast(mensaje, tipo = "error") {
    const toast = document.getElementById("toast");
    if (!toast) {
      console.error("Toast element not found");
      return;
    }
    
    // Limpiar cualquier timeout anterior
    if (toast.timeoutId) {
      clearTimeout(toast.timeoutId);
    }
    
    // Agregar icono según el tipo
    const icono = tipo === "error" ? '<i class="fas fa-exclamation-circle text-xl mr-2"></i>' : '<i class="fas fa-check-circle text-xl mr-2"></i>';
    
    // Establecer clases y estilos ANTES de cambiar display
    toast.className = `fixed top-6 right-6 px-6 py-4 rounded-xl text-white font-medium shadow-2xl flex items-center gap-3 ${tipo}`;
    toast.style.cssText = `
      position: fixed;
      top: 24px;
      right: 24px;
      z-index: 99999;
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 16px 24px;
      border-radius: 12px;
      color: white;
      font-weight: 500;
      box-shadow: 0 10px 25px rgba(0,0,0,0.3);
      backdrop-filter: blur(10px);
      border: 1px solid rgba(255,255,255,0.2);
      min-width: 300px;
      max-width: 400px;
      opacity: 0;
      transform: translateY(-20px);
      transition: all 0.3s ease-out;
      pointer-events: auto;
    `;
    
    // Establecer el contenido
    toast.innerHTML = `${icono}<span>${mensaje}</span>`;
    
    // Forzar reflow para que la animación funcione
    void toast.offsetWidth;
    
    // Mostrar con animación
    requestAnimationFrame(() => {
      requestAnimationFrame(() => {
        toast.style.opacity = "1";
        toast.style.transform = "translateY(0)";
      });
    });
    
    // Ocultar después de 5 segundos con animación
    toast.timeoutId = setTimeout(() => {
      toast.style.opacity = "0";
      toast.style.transform = "translateY(-20px)";
      setTimeout(() => {
        toast.style.display = "none";
        toast.className = "";
        toast.innerHTML = "";
        toast.style.cssText = "";
      }, 300);
    }, 5000);
  }

  function normalizar(txt) {
    return typeof txt === "string" ? txt.trim().toLowerCase() : "";
  }

  /* ----------  INIT  ---------- */
  document.addEventListener("DOMContentLoaded", () => {
    const form = document.getElementById("loginForm");
    if (!form) return;

    form.addEventListener("submit", async (e) => {
      e.preventDefault();

      try {
        const usuario  = normalizar(document.getElementById("usuario")?.value);
        const password = normalizar(document.getElementById("password")?.value);

        // Ocultar error anterior
        ocultarError();

        // Validar que los campos no estén vacíos
        if (!usuario || !password) {
          // Asegurar que el toast no se muestre
          const toast = document.getElementById("toast");
          if (toast) {
            toast.style.display = "none";
          }
          mostrarError("Por favor, completa todos los campos");
          return;
        }

        // Deshabilitar el botón mientras se procesa
        const loginBtn = document.getElementById("loginBtn");
        const originalBtnText = loginBtn.innerHTML;
        loginBtn.disabled = true;
        loginBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Verificando...';

        const res = await fetch("login.php", {
          method : "POST",
          headers: { "Content-Type": "application/json" },
          body   : JSON.stringify({ usuario, password })
        });

        let data;
        try {
          const text = await res.text();
          data = text ? JSON.parse(text) : {};
        } catch (jsonErr) {
          // Si no se puede parsear JSON, mostrar error genérico
          console.error("Error al parsear JSON:", jsonErr);
          mostrarError("Error al procesar la respuesta del servidor. Inténtalo de nuevo.");
          loginBtn.disabled = false;
          loginBtn.innerHTML = originalBtnText;
          return;
        }

        // Restaurar el botón
        loginBtn.disabled = false;
        loginBtn.innerHTML = originalBtnText;

        // Si la respuesta no es OK (401, 500, etc.)
        if (!res.ok) {
          const mensajeError = data?.error || data?.message || "Usuario o contraseña incorrectos";
          console.log("Error de login:", mensajeError, "Status:", res.status);
          // Asegurar que el toast no se muestre
          const toast = document.getElementById("toast");
          if (toast) {
            toast.style.display = "none";
          }
          mostrarError(mensajeError);
          return;
        }

        // Si todo está bien, redirigir
        if (data.redirect) {
          // Mostrar mensaje de éxito debajo del botón antes de redirigir
          mostrarExito("¡Inicio de sesión exitoso! Redirigiendo...");
          // Esperar un poco más para que el usuario vea el mensaje
          setTimeout(() => {
            window.location.href = data.redirect;
          }, 2000);
        } else {
          mostrarError("Error: No se recibió la ruta de redirección");
        }

      } catch (err) {
        console.error("Error en login:", err);
        mostrarError("Error inesperado. Inténtalo de nuevo.");
        const loginBtn = document.getElementById("loginBtn");
        if (loginBtn) {
          loginBtn.disabled = false;
          loginBtn.innerHTML = '<i class="fa-solid fa-right-to-bracket mr-2"></i> Iniciar Sesión';
        }
      }
    });
  });
})();