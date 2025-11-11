<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login de Desarrollo - Sistema de Requisiciones</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body {
            background: linear-gradient(135deg, #ff6b6b 0%, #feca57 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .login-container {
            max-width: 500px;
            width: 100%;
            padding: 0 15px;
        }
        
        .login-card {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        
        .login-header {
            background: linear-gradient(135deg, #ff6b6b 0%, #feca57 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .login-header i {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }
        
        .login-header h2 {
            font-size: 1.4rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .login-header p {
            margin: 0;
            opacity: 0.9;
            font-size: 0.9rem;
        }
        
        .dev-badge {
            background: rgba(255,255,255,0.2);
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.8rem;
            font-weight: 500;
            margin-top: 0.5rem;
            display: inline-block;
        }
        
        .login-body {
            padding: 2rem;
        }
        
        .form-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 0.75rem;
        }
        
        .form-select {
            border-radius: 0.5rem;
            border: 2px solid #e9ecef;
            padding: 0.75rem 1rem;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .form-select:focus {
            border-color: #ff6b6b;
            box-shadow: 0 0 0 0.2rem rgba(255, 107, 107, 0.25);
        }
        
        .btn-dev {
            background: linear-gradient(135deg, #ff6b6b 0%, #feca57 100%);
            border: none;
            color: white;
            padding: 0.75rem 1.5rem;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 0.5rem;
            transition: all 0.3s ease;
            width: 100%;
            margin-top: 1rem;
        }
        
        .btn-dev:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 107, 107, 0.3);
            color: white;
        }
        
        .btn-dev i {
            margin-right: 0.5rem;
        }
        
        .user-info {
            background: #f8f9fa;
            border-radius: 0.5rem;
            padding: 1rem;
            margin-top: 1rem;
            border-left: 4px solid #ff6b6b;
        }
        
        .user-info h6 {
            color: #333;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .user-info small {
            color: #6c757d;
        }
        
        .alert {
            border-radius: 0.5rem;
            border: none;
        }
        
        .footer-text {
            text-align: center;
            color: white;
            margin-top: 2rem;
            font-size: 0.9rem;
        }
        
        .footer-text a {
            color: white;
            text-decoration: underline;
        }
        
        .warning-box {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .warning-box i {
            color: #856404;
            margin-right: 0.5rem;
        }
        
        .warning-box small {
            color: #856404;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <!-- Header -->
            <div class="login-header">
                <i class="fas fa-code"></i>
                <h2>Modo Desarrollo</h2>
                <p>Sistema de Requisiciones</p>
                <div class="dev-badge">
                    <i class="fas fa-tools"></i> Entorno de Desarrollo
                </div>
            </div>
            
            <!-- Body -->
            <div class="login-body">
                <?php
                use App\Helpers\Session;
                if (Session::hasFlash()):
                    $flash = Session::getFlash();
                    $alertClass = match($flash['type']) {
                        'success' => 'alert-success',
                        'error' => 'alert-danger',
                        'warning' => 'alert-warning',
                        default => 'alert-info'
                    };
                ?>
                <div class="alert <?php echo $alertClass; ?> alert-dismissible fade show mb-4" role="alert">
                    <?php echo htmlspecialchars($flash['message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>
                
                <!-- Advertencia de desarrollo -->
                <div class="warning-box">
                    <i class="fas fa-exclamation-triangle"></i>
                    <small>
                        <strong>Advertencia:</strong> Este es un entorno de desarrollo. 
                        El login directo está habilitado solo para pruebas.
                    </small>
                </div>
                
                <h3 class="text-center mb-4" style="color: #333; font-size: 1.3rem;">
                    Seleccionar Usuario
                </h3>
                
                <!-- Formulario de Login -->
                <form method="POST" action="/dev/login">
                    <div class="mb-3">
                        <label for="user_id" class="form-label">
                            <i class="fas fa-user"></i> Usuario de Desarrollo
                        </label>
                        <select class="form-select" id="user_id" name="user_id" required>
                            <option value="">Seleccione un usuario...</option>
                            <?php if (!empty($usuarios)): ?>
                                <?php foreach ($usuarios as $usuario): ?>
                                    <option value="<?php echo $usuario->id; ?>">
                                        <?php echo htmlspecialchars(trim(($usuario->nombre ?? '') . ' ' . ($usuario->apellido ?? ''))); ?>
                                        (<?php echo htmlspecialchars($usuario->email ?? ''); ?>)
                                        - <?php echo htmlspecialchars($usuario->rol ?? 'usuario'); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <option disabled>No hay usuarios disponibles</option>
                            <?php endif; ?>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-dev">
                        <i class="fas fa-sign-in-alt"></i>
                        Iniciar Sesión de Desarrollo
                    </button>
                </form>
                
                <!-- Información adicional -->
                <div class="user-info">
                    <h6><i class="fas fa-info-circle"></i> Información</h6>
                    <small>
                        Esta funcionalidad está disponible solo en modo desarrollo. 
                        En producción se utilizará Azure AD para la autenticación.
                    </small>
                </div>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="footer-text">
            <p class="mb-0">
                &copy; <?php echo date('Y'); ?> Sistema de Requisiciones v2.0
            </p>
            <p class="mb-0" style="font-size: 0.85rem; margin-top: 0.5rem;">
                <i class="fas fa-code"></i> Modo Desarrollo
            </p>
        </div>
    </div>

    <!-- Bootstrap Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Auto-seleccionar el primer usuario si solo hay uno
        document.addEventListener('DOMContentLoaded', function() {
            const select = document.getElementById('user_id');
            const options = select.querySelectorAll('option[value]');
            
            if (options.length === 1) {
                options[0].selected = true;
            }
        });
    </script>
</body>
</html>
