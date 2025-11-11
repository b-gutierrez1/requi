<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Sistema de Requisiciones</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .login-container {
            max-width: 450px;
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
            background: linear-gradient(135deg, #0066cc 0%, #0052a3 100%);
            color: white;
            padding: 2.5rem 2rem;
            text-align: center;
        }
        
        .login-header i {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        
        .login-header h2 {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .login-header p {
            margin: 0;
            opacity: 0.9;
        }
        
        .login-body {
            padding: 2.5rem 2rem;
        }
        
        .btn-microsoft {
            background-color: #0078d4;
            border-color: #0078d4;
            color: white;
            padding: 0.75rem 1.5rem;
            font-size: 1.1rem;
            font-weight: 500;
            border-radius: 0.5rem;
            transition: all 0.3s ease;
            width: 100%;
        }
        
        .btn-microsoft:hover {
            background-color: #006abc;
            border-color: #006abc;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,120,212,0.3);
        }
        
        .btn-microsoft i {
            font-size: 1.3rem;
            margin-right: 0.75rem;
        }
        
        .login-features {
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid #e9ecef;
        }
        
        .feature-item {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
            color: #6c757d;
        }
        
        .feature-item i {
            color: #28a745;
            margin-right: 0.75rem;
            font-size: 1.2rem;
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
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <!-- Header -->
            <div class="login-header">
                <i class="fas fa-file-invoice"></i>
                <h2>Sistema de Requisiciones</h2>
                <p>Gestión de Compras y Autorizaciones</p>
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
                
                <h3 class="text-center mb-4" style="color: #333; font-size: 1.3rem;">
                    Iniciar Sesión
                </h3>
                
                <!-- Botón de Login con Microsoft -->
                <a href="/auth/azure" class="btn btn-microsoft">
                    <i class="fab fa-microsoft"></i>
                    Continuar con Microsoft
                </a>
                
                <!-- Características -->
                <div class="login-features">
                    <div class="feature-item">
                        <i class="fas fa-shield-alt"></i>
                        <span>Autenticación segura con Azure AD</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-check-circle"></i>
                        <span>Flujo de autorización multi-nivel</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-bell"></i>
                        <span>Notificaciones automáticas</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="footer-text">
            <p class="mb-0">
                &copy; <?php echo date('Y'); ?> Sistema de Requisiciones v2.0
            </p>
            <p class="mb-0" style="font-size: 0.85rem; margin-top: 0.5rem;">
                ¿Necesitas ayuda? <a href="mailto:soporte@empresa.com">Contacta a soporte</a>
            </p>
        </div>
    </div>

    <!-- Bootstrap Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
