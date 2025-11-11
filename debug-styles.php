<?php
/**
 * DEBUG - Página de prueba para verificar carga de estilos
 */
require_once 'app/Helpers/functions.php';
require_once 'app/Helpers/Config.php';
require_once 'app/Helpers/View.php';

// Simular variables de sesión básicas
session_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DEBUG - Prueba de Estilos</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css">
    
    <!-- DEBUG: Mostrar las URLs de CSS -->
    <?php 
    echo "<!-- DEBUG URL CONFIG -->\n";
    echo "<!-- Base Path: " . \App\Helpers\Config::get('app.base_path') . " -->\n";
    echo "<!-- Public Path: " . \App\Helpers\Config::get('app.public_path') . " -->\n";
    echo "<!-- Current SERVER info: " . print_r($_SERVER['HTTP_HOST'] ?? 'N/A', true) . " -->\n";
    
    $cssUrl = \App\Helpers\View::asset('css/app.css');
    $animationsUrl = \App\Helpers\View::asset('css/animations.css');
    $jsUrl = \App\Helpers\View::asset('js/app.js');
    
    echo "<!-- CSS URL: " . $cssUrl . " -->\n";
    echo "<!-- Animations URL: " . $animationsUrl . " -->\n";
    echo "<!-- JS URL: " . $jsUrl . " -->\n";
    ?>
    
    <!-- Estilos locales -->
    <link rel="stylesheet" href="<?php echo $cssUrl; ?>?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="<?php echo $animationsUrl; ?>?v=<?php echo time(); ?>">
    
    <style>
        .debug-info {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 1rem;
            margin: 1rem 0;
            font-family: monospace;
            font-size: 0.9rem;
        }
        
        .test-card {
            margin: 1rem 0;
        }
        
        .success { color: #28a745; }
        .error { color: #dc3545; }
    </style>
</head>
<body>
    <div class="container mt-4">
        <h1 class="text-gradient mb-4">
            <i class="fas fa-bug me-2"></i>
            DEBUG - Prueba de Estilos y Animaciones
        </h1>
        
        <!-- Información de DEBUG -->
        <div class="debug-info">
            <h5>Información de URLs:</h5>
            <p><strong>CSS URL:</strong> <?php echo $cssUrl; ?></p>
            <p><strong>Animations URL:</strong> <?php echo $animationsUrl; ?></p>
            <p><strong>JS URL:</strong> <?php echo $jsUrl; ?></p>
            <p><strong>Base Path:</strong> <?php echo \App\Helpers\Config::get('app.base_path', 'NOT SET'); ?></p>
            <p><strong>Current Host:</strong> <?php echo $_SERVER['HTTP_HOST'] ?? 'NOT SET'; ?></p>
            <p><strong>Current Port:</strong> <?php echo $_SERVER['SERVER_PORT'] ?? 'NOT SET'; ?></p>
        </div>
        
        <!-- Test de existencia de archivos -->
        <div class="debug-info">
            <h5>Verificación de archivos:</h5>
            <?php
            $files = [
                'public/css/app.css',
                'public/css/animations.css',
                'public/js/app.js'
            ];
            
            foreach ($files as $file) {
                $fullPath = __DIR__ . '/' . $file;
                $exists = file_exists($fullPath);
                $class = $exists ? 'success' : 'error';
                $icon = $exists ? 'fa-check' : 'fa-times';
                echo "<p class='{$class}'><i class='fas {$icon}'></i> {$file}: " . ($exists ? 'EXISTS' : 'NOT FOUND') . "</p>";
            }
            ?>
        </div>
        
        <!-- Test de Bootstrap -->
        <div class="alert alert-info animate-fadeIn">
            <i class="fas fa-info-circle me-2"></i>
            Si ves este mensaje con el ícono y color correcto, Bootstrap y Font Awesome funcionan.
        </div>
        
        <!-- Test de Cards animadas -->
        <div class="row">
            <div class="col-md-4">
                <div class="card test-card smooth-hover animate-slide-in-up">
                    <div class="card-header">
                        <i class="fas fa-heart me-2"></i>
                        Card de Prueba 1
                    </div>
                    <div class="card-body">
                        <p>Si esta card tiene efectos hover y animaciones suaves, los estilos funcionan.</p>
                        <button class="btn btn-primary btn-hover-effect animate-zoom-in">
                            <i class="fas fa-magic me-2"></i>
                            Botón Animado
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card test-card card-animated pendiente animate-strong-pulse" style="animation-delay: 0.2s;">
                    <div class="card-header">
                        <i class="fas fa-clock me-2"></i>
                        Estado Pendiente
                    </div>
                    <div class="card-body">
                        <span class="badge badge-animated pendiente animate-strong-glow">
                            <span class="status-indicator pendiente"></span>
                            Pendiente
                        </span>
                        <p class="mt-2">Esta card debería tener una animación pulsante muy visible.</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card test-card card-animated autorizado animate-super-bounce" style="animation-delay: 0.4s;">
                    <div class="card-header">
                        <i class="fas fa-check me-2"></i>
                        Estado Autorizado
                    </div>
                    <div class="card-body">
                        <span class="badge badge-animated autorizado animate-flip-in-x">
                            <span class="status-indicator autorizado"></span>
                            Autorizado
                        </span>
                        <p class="mt-2">Esta card debería aparecer con animación bounce fuerte.</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Test de botones -->
        <div class="row mt-4">
            <div class="col-12">
                <h4>Test de Botones Animados:</h4>
                <div class="btn-group" role="group">
                    <button class="btn btn-primary btn-hover-effect">
                        <i class="fas fa-save me-2"></i>Guardar
                    </button>
                    <button class="btn btn-success btn-hover-effect">
                        <i class="fas fa-check me-2"></i>Aprobar
                    </button>
                    <button class="btn btn-danger btn-hover-effect">
                        <i class="fas fa-times me-2"></i>Rechazar
                    </button>
                    <button class="btn btn-warning btn-hover-effect">
                        <i class="fas fa-exclamation me-2"></i>Advertencia
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Test de tabla -->
        <div class="mt-4">
            <h4>Test de Tabla Animada:</h4>
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>1</td>
                        <td>Requisición de Prueba</td>
                        <td><span class="badge bg-success">Aprobada</span></td>
                        <td>
                            <button class="btn btn-sm btn-primary btn-hover-effect">
                                <i class="fas fa-eye"></i>
                            </button>
                        </td>
                    </tr>
                    <tr>
                        <td>2</td>
                        <td>Otra Requisición</td>
                        <td><span class="badge bg-warning">Pendiente</span></td>
                        <td>
                            <button class="btn btn-sm btn-info btn-hover-effect">
                                <i class="fas fa-edit"></i>
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>
    <script src="<?php echo $jsUrl; ?>?v=<?php echo time(); ?>"></script>
    
    <script>
        // Test de JavaScript
        console.log('=== DEBUG SCRIPT TEST ===');
        console.log('CSS URL:', '<?php echo $cssUrl; ?>');
        console.log('JS URL:', '<?php echo $jsUrl; ?>');
        
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM loaded, testing animations...');
            
            // Test de SweetAlert2
            setTimeout(() => {
                if (typeof Swal !== 'undefined') {
                    console.log('✅ SweetAlert2 loaded correctly');
                } else {
                    console.log('❌ SweetAlert2 NOT loaded');
                }
                
                // Test de la app de requisiciones
                if (typeof window.app !== 'undefined') {
                    console.log('✅ RequisicionesApp loaded correctly');
                    window.app.showSuccess('¡Las animaciones funcionan correctamente!');
                } else {
                    console.log('❌ RequisicionesApp NOT loaded');
                }
            }, 1000);
        });
    </script>
</body>
</html>