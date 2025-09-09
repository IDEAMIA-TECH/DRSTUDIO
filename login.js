// Login JavaScript - DT Studio Admin
// Sistema de autenticación para el panel de administración

// Credenciales de administrador (en producción esto debería estar en el servidor)
const ADMIN_CREDENTIALS = {
    username: 'admin',
    password: 'admin123'
};

// Inicialización cuando se carga la página
document.addEventListener('DOMContentLoaded', function() {
    initializeLogin();
    checkExistingSession();
});

// Inicializar el sistema de login
function initializeLogin() {
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', handleLogin);
    }
    
    // Verificar si ya hay una sesión activa
    if (isLoggedIn()) {
        redirectToAdmin();
    }
}

// Verificar sesión existente
function checkExistingSession() {
    const rememberMe = localStorage.getItem('rememberMe') === 'true';
    if (rememberMe && isLoggedIn()) {
        redirectToAdmin();
    }
}

// Manejar el envío del formulario de login
function handleLogin(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const username = formData.get('username');
    const password = formData.get('password');
    const remember = formData.get('remember') === 'on';
    
    // Mostrar estado de carga
    const submitBtn = e.target.querySelector('.btn-login');
    submitBtn.classList.add('loading');
    submitBtn.disabled = true;
    
    // Simular delay de autenticación
    setTimeout(() => {
        if (authenticateUser(username, password)) {
            // Login exitoso
            saveSession(username, remember);
            showSuccessMessage();
            setTimeout(() => {
                redirectToAdmin();
            }, 1000);
        } else {
            // Login fallido
            showErrorMessage('Usuario o contraseña incorrectos');
            submitBtn.classList.remove('loading');
            submitBtn.disabled = false;
        }
    }, 1500);
}

// Autenticar usuario
function authenticateUser(username, password) {
    return username === ADMIN_CREDENTIALS.username && 
           password === ADMIN_CREDENTIALS.password;
}

// Guardar sesión
function saveSession(username, remember) {
    const sessionData = {
        username: username,
        loginTime: new Date().toISOString(),
        remember: remember
    };
    
    if (remember) {
        localStorage.setItem('adminSession', JSON.stringify(sessionData));
        localStorage.setItem('rememberMe', 'true');
    } else {
        sessionStorage.setItem('adminSession', JSON.stringify(sessionData));
    }
}

// Verificar si el usuario está logueado
function isLoggedIn() {
    console.log('Verificando estado de login...');
    
    const sessionData = localStorage.getItem('adminSession') || sessionStorage.getItem('adminSession');
    if (!sessionData) {
        console.log('No hay datos de sesión');
        return false;
    }
    
    try {
        const session = JSON.parse(sessionData);
        console.log('Datos de sesión:', session);
        
        const loginTime = new Date(session.loginTime);
        const now = new Date();
        const hoursDiff = (now - loginTime) / (1000 * 60 * 60);
        
        console.log('Horas desde login:', hoursDiff);
        
        // La sesión expira después de 8 horas
        if (hoursDiff > 8) {
            console.log('Sesión expirada, limpiando...');
            clearSession();
            return false;
        }
        
        console.log('Sesión válida');
        return true;
    } catch (error) {
        console.error('Error parseando sesión:', error);
        clearSession();
        return false;
    }
}

// Limpiar sesión
function clearSession() {
    localStorage.removeItem('adminSession');
    sessionStorage.removeItem('adminSession');
    localStorage.removeItem('rememberMe');
}

// Redirigir al panel de administración
function redirectToAdmin() {
    window.location.href = 'admin.html';
}

// Mostrar mensaje de error
function showErrorMessage(message) {
    const errorDiv = document.getElementById('loginError');
    const errorMessage = document.getElementById('errorMessage');
    
    if (errorDiv && errorMessage) {
        errorMessage.textContent = message;
        errorDiv.style.display = 'flex';
        
        // Ocultar el mensaje después de 5 segundos
        setTimeout(() => {
            errorDiv.style.display = 'none';
        }, 5000);
    }
}

// Mostrar mensaje de éxito
function showSuccessMessage() {
    const errorDiv = document.getElementById('loginError');
    const errorMessage = document.getElementById('errorMessage');
    
    if (errorDiv && errorMessage) {
        errorDiv.style.background = '#efe';
        errorDiv.style.color = '#3c3';
        errorDiv.style.borderColor = '#cfc';
        errorMessage.innerHTML = '<i class="fas fa-check-circle"></i> Login exitoso. Redirigiendo...';
        errorDiv.style.display = 'flex';
    }
}

// Toggle de visibilidad de contraseña
function togglePassword() {
    const passwordInput = document.getElementById('password');
    const toggleIcon = document.getElementById('toggleIcon');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleIcon.classList.remove('fa-eye');
        toggleIcon.classList.add('fa-eye-slash');
    } else {
        passwordInput.type = 'password';
        toggleIcon.classList.remove('fa-eye-slash');
        toggleIcon.classList.add('fa-eye');
    }
}

// Función para cerrar sesión (llamada desde admin.html)
function logout() {
    clearSession();
    window.location.href = 'login.html';
}

// Verificar sesión en cada carga de página del admin
function checkAdminSession() {
    console.log('Verificando sesión de administrador...');
    
    if (!isLoggedIn()) {
        console.log('Sesión no válida, redirigiendo a login...');
        window.location.href = 'login.html';
        return;
    }
    
    console.log('Sesión válida, continuando...');
}

// Función para obtener información de la sesión actual
function getCurrentSession() {
    const sessionData = localStorage.getItem('adminSession') || sessionStorage.getItem('adminSession');
    if (sessionData) {
        return JSON.parse(sessionData);
    }
    return null;
}

// Función para actualizar la sesión
function updateSession() {
    const session = getCurrentSession();
    if (session) {
        session.lastActivity = new Date().toISOString();
        if (session.remember) {
            localStorage.setItem('adminSession', JSON.stringify(session));
        } else {
            sessionStorage.setItem('adminSession', JSON.stringify(session));
        }
    }
}

// Actualizar sesión cada 5 minutos
setInterval(updateSession, 5 * 60 * 1000);

// Manejar cierre de ventana/tab
window.addEventListener('beforeunload', function() {
    updateSession();
});

// Función para cambiar contraseña (futura implementación)
function changePassword() {
    // Esta función se implementará en el panel de administración
    console.log('Función de cambio de contraseña - Próximamente');
}

// Función para recuperar contraseña (futura implementación)
function forgotPassword() {
    // Esta función se implementará para recuperación de contraseña
    console.log('Función de recuperación de contraseña - Próximamente');
}

// Exportar funciones para uso en admin.html
window.logout = logout;
window.checkAdminSession = checkAdminSession;
window.getCurrentSession = getCurrentSession;
