<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title ?? 'Sistema de Requisiciones'; ?></title>
    
    <!-- CSRF Token Meta -->
    <?php 
    use App\Middlewares\CsrfMiddleware;
    echo CsrfMiddleware::metaTag(); 
    ?>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Estilos personalizados locales -->
    <link rel="stylesheet" href="/css/app.css?v=<?php echo time(); ?>">
    
    <!-- Estilos personalizados -->
    <style>
        body {
            font-family: 'Inter', 'Segoe UI', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: var(--bg-primary);
        }
        
        .main-content {
            min-height: calc(100vh - 120px);
            padding: 2rem 0;
        }
        
        .footer {
            background-color: var(--bg-secondary);
            border-top: 1px solid var(--neutral-200);
            padding: 1.5rem 0;
            margin-top: 2rem;
        }
    </style>
    
    <?php App\Helpers\View::section('styles'); ?>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="/dashboard">
                <i class="fas fa-file-invoice me-2"></i>
                Sistema de Requisiciones
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/dashboard">
                            <i class="fas fa-home me-1"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/requisiciones">
                            <i class="fas fa-file-alt me-1"></i> Requisiciones
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/autorizaciones">
                            <i class="fas fa-check-circle me-1"></i> Autorizaciones
                        </a>
                    </li>
                    <?php if (isset($usuario) && (($usuario['is_admin'] ?? $usuario['es_admin'] ?? 0) == 1)): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="/admin">
                            <i class="fas fa-cog me-1"></i> Administración
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
                
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-1"></i>
                            <?php echo App\Helpers\View::e($usuario['name'] ?? 'Usuario'); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="/perfil"><i class="fas fa-user me-2"></i> Mi Perfil</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="/logout"><i class="fas fa-sign-out-alt me-2"></i> Cerrar Sesión</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container-fluid main-content">
        <!-- Flash Messages -->
        <?php
        use App\Helpers\Session;
        if (Session::hasFlash()):
            $flash = Session::getFlash();
            $alertClass = match($flash['type']) {
                'success' => 'alert-success',
                'error' => 'alert-danger',
                'warning' => 'alert-warning',
                'info' => 'alert-info',
                default => 'alert-info'
            };
        ?>
        <div class="alert <?php echo $alertClass; ?> alert-dismissible fade show" role="alert">
            <i class="fas fa-<?php echo match($flash['type']) {
                'success' => 'check-circle',
                'error' => 'exclamation-circle',
                'warning' => 'exclamation-triangle',
                'info' => 'info-circle',
                default => 'info-circle'
            }; ?> me-2"></i>
            <?php echo App\Helpers\View::e($flash['message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <!-- Page Content -->
        <?php if (App\Helpers\View::hasSection('content')) { App\Helpers\View::section('content'); } else { echo $content ?? ''; } ?>

    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-6">
                    <p class="mb-0 text-muted">
                        &copy; <?php echo date('Y'); ?> Sistema de Requisiciones v2.0
                    </p>
                </div>
                <div class="col-md-6 text-end">
                    <p class="mb-0 text-muted">
                        <i class="fas fa-code me-1"></i> Desarrollado con <i class="fas fa-heart text-danger"></i>
                    </p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- JavaScript personalizado -->
    <script src="/js/app.js?v=<?php echo time(); ?>"></script>
    
    <!-- CSRF Token for AJAX -->
    <script>
        // Configurar token CSRF para todas las peticiones AJAX
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        
        // Interceptar fetch para agregar CSRF token
        const originalFetch = window.fetch;
        window.fetch = function(url, options = {}) {
            if (options.method && ['POST', 'PUT', 'DELETE', 'PATCH'].includes(options.method.toUpperCase())) {
                options.headers = options.headers || {};
                options.headers['X-CSRF-TOKEN'] = csrfToken;
                if (!options.headers['X-Requested-With']) {
                    options.headers['X-Requested-With'] = 'XMLHttpRequest';
                }
                if (!options.headers['Accept']) {
                    options.headers['Accept'] = 'application/json, text/plain, */*';
                }
            }
            return originalFetch(url, options);
        };
    </script>
    
    <?php App\Helpers\View::section('scripts'); ?>
</body>
</html>

