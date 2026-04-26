<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Grupo Santiago</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <!-- Google Fonts: Merriweather for headings, Roboto for body -->
    <link href="https://fonts.googleapis.com/css2?family=Merriweather:wght@400;700&family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #f8f9fa;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }
        h1, h2, h3, h4, h5, h6 {
            font-family: 'Merriweather', serif;
        }
        .login-container {
            display: flex;
            width: 100%;
            max-width: 1200px;
            border-radius: 1rem;
            overflow: hidden;
            box-shadow: 0 1rem 3rem rgba(0, 0, 0, 0.175);
        }
        .left-section {
            flex: 1;
            background: linear-gradient(to bottom right, #212529, #343a40, #495057);
            color: white;
            padding: 3rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            position: relative;
        }
        .left-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: url("data:image/svg+xml,%3Csvg width='10' height='10' viewBox='0 0 10 10' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M 10 0 L 0 0 0 10' fill='none' stroke='%23ffffff' stroke-width='0.2'/%3E%3C/svg%3E");
            opacity: 0.05;
            z-index: 0;
        }
        .left-section .content {
            position: relative;
            z-index: 1;
        }
        .left-section .icon-box {
            width: 6rem;
            height: 6rem;
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 1rem;
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 2rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(5px);
        }
        .left-section .icon-box svg {
            width: 3rem;
            height: 3rem;
            color: white;
        }
        .left-section h1 {
            font-size: 2.8rem;
            font-weight: 700;
            margin-bottom: 1rem;
            line-height: 1.2;
        }
        .left-section .highlight {
            background: linear-gradient(to right, #ffc107, #fd7e14);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .left-section p {
            font-size: 1.15rem;
            color: #ced4da;
            margin-bottom: 2rem;
        }
        .left-section .feature-list .list-item {
            display: flex;
            align-items: center;
            margin-bottom: 0.75rem;
            color: #e9ecef;
        }
        .left-section .feature-list .list-item .dot {
            width: 0.5rem;
            height: 0.5rem;
            background-color: #ffc107;
            border-radius: 50%;
            margin-right: 0.75rem;
        }
        .right-section {
            flex: 1;
            background-color: #ffffff;
            padding: 3rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .right-section .form-header {
            text-align: center;
            margin-bottom: 2.5rem;
        }
        .right-section .form-header .icon-box {
            width: 4rem;
            height: 4rem;
            background: linear-gradient(to right, #495057, #212529);
            border-radius: 0.75rem;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0 auto 1rem auto;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
        .right-section .form-header .icon-box svg {
            width: 2rem;
            height: 2rem;
            color: white;
        }
        .right-section h2 {
            font-size: 2.2rem;
            font-weight: 700;
            color: #343a40;
            margin-bottom: 0.5rem;
        }
        .right-section .form-header p {
            color: #6c757d;
        }
        .form-control {
            border-radius: 0.5rem;
            padding: 0.75rem 1rem;
            border: 1px solid #dee2e6;
            transition: all 0.2s ease-in-out;
            background-color: #f8f9fa;
        }
        .form-control:focus {
            border-color: #6c757d;
            box-shadow: 0 0 0 0.25rem rgba(108, 117, 125, 0.25);
            background-color: #ffffff;
        }
        .input-group-text {
            background-color: #e9ecef;
            border: 1px solid #dee2e6;
            border-right: none;
            border-radius: 0.5rem 0 0 0.5rem;
        }
        .password-toggle-btn {
            background-color: transparent;
            border: none;
            cursor: pointer;
            padding: 0.375rem 0.75rem;
            border-radius: 0 0.5rem 0.5rem 0;
            transition: color 0.2s ease-in-out;
        }
        .password-toggle-btn:hover {
            color: #495057;
        }
        .password-toggle-btn svg {
            width: 1.25rem;
            height: 1.25rem;
            color: #6c757d;
        }
        .btn-primary {
            background: linear-gradient(to right, #495057, #212529);
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 600;
            transition: all 0.3s ease-in-out;
            position: relative;
            overflow: hidden;
        }
        .btn-primary:hover {
            background: linear-gradient(to right, #212529, #495057);
            transform: translateY(-2px);
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.2);
        }
        .btn-primary:active {
            transform: translateY(0);
            box-shadow: none;
        }
        .btn-primary .btn-icon {
            margin-right: 0.75rem;
        }
        .btn-primary .btn-icon svg {
            width: 1.25rem;
            height: 1.25rem;
            color: rgba(255, 255, 255, 0.7);
        }
        .form-check-input:checked {
            background-color: #495057;
            border-color: #495057;
        }
        .form-check-input:focus {
            box-shadow: 0 0 0 0.25rem rgba(73, 80, 87, 0.25);
        }
        .text-muted {
            color: #6c757d !important;
        }
        .alert {
            border-radius: 0.5rem;
        }

        /* Responsive adjustments */
        @media (max-width: 991.98px) {
            .login-container {
                flex-direction: column;
                max-width: 600px;
            }
            .left-section {
                display: none; /* Hide left section on smaller screens */
            }
            .right-section {
                padding: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <!-- Sección Izquierda: Branding y Bienvenida -->
        <div class="left-section d-none d-lg-flex">
            <div class="content">
                <div class="icon-box mx-auto mb-4">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                    </svg>
                </div>
                <h1 class="mb-3">
                    Bienvenido a<br>
                    <span class="highlight">Grupo Santiago</span>
                </h1>
                <p>
                    Plataforma exclusiva para la gestión avanzada de clientes y préstamos.
                    Optimiza tus operaciones con herramientas de primer nivel.
                </p>
                <div class="feature-list text-start mt-4">
                    <div class="list-item">
                        <div class="dot"></div>
                        <span>Gestión integral y segura</span>
                    </div>
                    <div class="list-item">
                        <div class="dot"></div>
                        <span>Análisis financiero detallado</span>
                    </div>
                    <div class="list-item">
                        <div class="dot"></div>
                        <span>Soporte prioritario 24/7</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sección Derecha: Formulario de Login -->
        <div class="right-section">
            <div class="form-header">
                <div class="icon-box">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                    </svg>
                </div>
                <h2 class="mb-2">Acceso al Sistema</h2>
                <p class="text-muted">Ingrese sus credenciales para continuar</p>
            </div>

            <!-- Mensajes de error y estado -->
            @if ($errors->any())
                <div class="alert alert-danger mb-4">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if (session('status'))
                <div class="alert alert-success mb-4">
                    {{ session('status') }}
                </div>
            @endif

            <!-- Paso 1: Credenciales -->
            <form id="credentialsForm" method="POST" action="/login/submit-credentials" style="display: block;">
                @csrf

                <!-- Campo Email -->
                <div class="mb-3">
                    <label for="email" class="form-label">Correo Electrónico o Código</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"></path>
                            </svg>
                        </span>
                        <input id="email" type="text" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" class="form-control" placeholder="correo@ejemplo.com o código">
                    </div>
                </div>

                <!-- Campo Password -->
                <div class="mb-3">
                    <label for="password" class="form-label">Contraseña</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                            </svg>
                        </span>
                        <input id="password" type="password" name="password" required autocomplete="current-password" class="form-control" placeholder="••••••••">
                        <button type="button" class="password-toggle-btn" onclick="togglePasswordVisibility()">
                            <svg id="eye-open" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                            <svg id="eye-closed" class="d-none" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a9.953 9.953 0 011.718-3.713M6.343 6.343A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.542 7a9.953 9.953 0 01-1.718 3.713M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3l18 18"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Botón para verificar credenciales -->
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">
                        <span class="btn-icon">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </span>
                        Verificar Credenciales
                    </button>
                </div>
            </form>

            <!-- Paso 2: Código de Acceso -->
            <form id="accessCodeForm" method="POST" action="{{ route('login') }}" style="display: none;">
                @csrf
                <input type="hidden" id="final_email" name="email">
                <input type="hidden" id="final_password" name="password">

                <!-- Campo Código de Acceso -->
                <div class="mb-3">
                    <label for="access_code" class="form-label">
                        Código de Acceso
                    </label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m0 0v6a2 2 0 01-2 2H9a2 2 0 01-2-2V9a2 2 0 012-2m6 0V7a2 2 0 00-2-2H9a2 2 0 00-2 2v0m6 0V7a2 2 0 00-2-2H9a2 2 0 00-2 2v0m6 0h.01M9 7h.01"></path>
                            </svg>
                        </span>
                        <input id="access_code" type="text" name="access_code" required class="form-control" placeholder="Ej: ABC123" style="font-family: monospace; letter-spacing: 1px; text-transform: uppercase;" maxlength="10">
                    </div>
                    <div class="mt-2">
                        <small class="form-text text-info d-block">
                            <i class="fas fa-info-circle mr-1"></i>
                            <span id="code-help-text">Solicita el código al administrador o usa un código de emergencia</span>
                        </small>
                        <button type="button" class="btn btn-sm btn-outline-secondary mt-2" onclick="showStep1()">
                            <i class="fas fa-arrow-left mr-1"></i>Cambiar usuario
                        </button>
                    </div>
                </div>

                <!-- Recordarme y Olvidé mi contraseña -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="remember" id="remember_me">
                        <label class="form-check-label" for="remember_me">
                            Recordarme
                        </label>
                    </div>

                    @if (Route::has('password.request'))
                        <a class="text-muted text-decoration-none" href="{{ route('password.request') }}">
                            ¿Olvidaste tu contraseña?
                        </a>
                    @endif
                </div>

                <!-- Botón de Login -->
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">
                        <span class="btn-icon">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                            </svg>
                        </span>
                        Iniciar Sesión
                    </button>
                </div>
            </form>

            <!-- Footer del formulario -->
            <div class="text-center mt-5">
                <p class="text-muted small">
                    © 2025 Grupo Santiago. Todos los derechos reservados.
                </p>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script>
        function togglePasswordVisibility() {
            const passwordInput = document.getElementById('password');
            const eyeOpen = document.getElementById('eye-open');
            const eyeClosed = document.getElementById('eye-closed');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeOpen.classList.add('d-none');
                eyeClosed.classList.remove('d-none');
            } else {
                passwordInput.type = 'password';
                eyeOpen.classList.remove('d-none');
                eyeClosed.classList.add('d-none');
            }
        }

        // Manejar envío de credenciales (Paso 1)
        document.getElementById('credentialsForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            const submitBtn = this.querySelector('button[type="submit"]');
            
            // Deshabilitar botón y mostrar loading
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Verificando...';
            
            fetch('/login/submit-credentials', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                },
                body: JSON.stringify({
                    email: email,
                    password: password
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.show_code_step) {
                    // Credenciales válidas - mostrar paso 2
                    showStep2(email, password);
                    
                    // Si hay código en la respuesta, mostrarlo automáticamente
                    if (data.access_code) {
                        document.getElementById('access_code').value = data.access_code;
                        
                        const helpText = document.getElementById('code-help-text');
                        helpText.innerHTML = `✅ Tu código es: <strong>${data.access_code}</strong> (válido 15 minutos)`;
                        helpText.className = 'form-text text-success';
                    } else {
                        // Mostrar mensaje informativo para aprobación manual
                        const helpText = document.getElementById('code-help-text');
                        helpText.innerHTML = '⏳ Se ha generado un código único. Solicítalo al administrador para completar el acceso.';
                        helpText.className = 'form-text text-warning';
                    }
                } else {
                    throw new Error(data.message || 'Error desconocido');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error: ' + (error.message || 'No se pudieron verificar las credenciales'));
                
                // Restaurar botón
                submitBtn.disabled = false;
                submitBtn.innerHTML = `
                    <span class="btn-icon">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </span>
                    Verificar Credenciales
                `;
            });
        });

        function showStep1() {
            document.getElementById('credentialsForm').style.display = 'block';
            document.getElementById('accessCodeForm').style.display = 'none';
            document.querySelector('#credentialsForm h2')?.remove();
        }

        function showStep2(email, password) {
            document.getElementById('credentialsForm').style.display = 'none';
            document.getElementById('accessCodeForm').style.display = 'block';
            
            // Transferir credenciales al formulario final
            document.getElementById('final_email').value = email;
            document.getElementById('final_password').value = password;
            
            // Enfocar el campo de código
            setTimeout(() => {
                document.getElementById('access_code').focus();
            }, 300);
        }

        // Animación de entrada suave para el formulario (opcional, si se desea)
        document.addEventListener('DOMContentLoaded', function() {
            // Agregar meta tag para CSRF si no existe
            if (!document.querySelector('meta[name="csrf-token"]')) {
                const meta = document.createElement('meta');
                meta.name = 'csrf-token';
                meta.content = '{{ csrf_token() }}';
                document.head.appendChild(meta);
            }

            const loginContainer = document.querySelector('.login-container');
            if (loginContainer) {
                loginContainer.style.opacity = '0';
                loginContainer.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    loginContainer.style.transition = 'all 0.8s cubic-bezier(0.25, 0.8, 0.25, 1)';
                    loginContainer.style.opacity = '1';
                    loginContainer.style.transform = 'translateY(0)';
                }, 100);
            }
        });
    </script>
</body>
</html>