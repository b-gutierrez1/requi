<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error <?php echo $code ?? 500; ?> - Sistema de Requisiciones</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
        
        .error-container {
            text-align: center;
            color: white;
            max-width: 600px;
            padding: 0 15px;
        }
        
        .error-code {
            font-size: 8rem;
            font-weight: 700;
            line-height: 1;
            text-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }
        
        .error-icon {
            font-size: 5rem;
            margin-bottom: 1rem;
            opacity: 0.8;
        }
        
        .error-message {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        
        .error-description {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-bottom: 2rem;
        }
        
        .btn-home {
            background: white;
            color: #667eea;
            padding: 0.75rem 2rem;
            font-size: 1.1rem;
            font-weight: 500;
            border-radius: 0.5rem;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }
        
        .btn-home:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            color: #667eea;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-icon">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        
        <h1 class="error-code"><?php echo htmlspecialchars($code ?? 500); ?></h1>
        
        <h2 class="error-message">
            <?php 
            echo htmlspecialchars($message ?? match($code ?? 500) {
                404 => 'Página No Encontrada',
                403 => 'Acceso Prohibido',
                500 => 'Error Interno del Servidor',
                default => 'Ha Ocurrido un Error'
            });
            ?>
        </h2>
        
        <p class="error-description">
            <?php
            echo htmlspecialchars($description ?? match($code ?? 500) {
                404 => 'La página que buscas no existe o ha sido movida.',
                403 => 'No tienes permisos para acceder a este recurso.',
                500 => 'Estamos experimentando problemas técnicos. Por favor intenta nuevamente.',
                default => 'Algo salió mal. Por favor intenta nuevamente.'
            });
            ?>
        </p>
        
        <a href="/" class="btn-home">
            <i class="fas fa-home me-2"></i>
            Volver al Inicio
        </a>
    </div>
</body>
</html>
