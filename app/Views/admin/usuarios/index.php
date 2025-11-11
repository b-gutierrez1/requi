<?php
use App\Helpers\View;
use App\Middlewares\CsrfMiddleware;

View::startSection('content');
?>

<style>
    .user-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        border: none;
        transition: all 0.3s ease;
        overflow: hidden;
    }
    
    .user-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 35px rgba(0,0,0,0.15);
    }
    
    .user-avatar {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        font-weight: bold;
        color: white;
        margin: 0 auto 1rem;
    }
    
    .user-avatar.admin { background: linear-gradient(45deg, #2c3e50, #34495e); }
    .user-avatar.revisor { background: linear-gradient(45deg, #f39c12, #e67e22); }
    .user-avatar.user { background: linear-gradient(45deg, #3498db, #2980b9); }
    
    .role-badge {
        border-radius: 20px;
        padding: 0.5rem 1rem;
        font-weight: 600;
        font-size: 0.8rem;
    }
    
    .role-badge.admin {
        background: linear-gradient(45deg, #2c3e50, #34495e);
        color: white;
    }
    
    .role-badge.revisor {
        background: linear-gradient(45deg, #f39c12, #e67e22);
        color: white;
    }
    
    .role-badge.user {
        background: linear-gradient(45deg, #3498db, #2980b9);
        color: white;
    }
    
    .status-indicator {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        display: inline-block;
        margin-right: 0.5rem;
    }
    
    .status-indicator.active {
        background: #28a745;
        box-shadow: 0 0 10px rgba(40, 167, 69, 0.5);
    }
    
    .status-indicator.inactive {
        background: #6c757d;
    }
    
    .action-buttons {
        display: flex;
        gap: 0.5rem;
        justify-content: center;
    }
    
    .btn-action {
        border-radius: 20px;
        padding: 0.4rem 1rem;
        font-size: 0.8rem;
        font-weight: 600;
        border: none;
        transition: all 0.3s ease;
    }
    
    .btn-action:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    }
    
    .btn-edit {
        background: linear-gradient(45deg, #27ae60, #229954);
        color: white;
    }
    
    .btn-delete {
        background: linear-gradient(45deg, #e74c3c, #c0392b);
        color: white;
    }
    
    .btn-toggle {
        background: linear-gradient(45deg, #f39c12, #e67e22);
        color: white;
    }
    
    .search-section {
        background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
        color: white;
        border-radius: 15px;
        padding: 2rem;
        margin-bottom: 2rem;
    }
    
    .filter-tabs {
        display: flex;
        gap: 1rem;
        margin-bottom: 1rem;
    }
    
    .filter-tab {
        padding: 0.5rem 1.5rem;
        border-radius: 25px;
        background: rgba(255,255,255,0.2);
        color: white;
        text-decoration: none;
        transition: all 0.3s ease;
        border: 2px solid transparent;
    }
    
    .filter-tab:hover,
    .filter-tab.active {
        background: rgba(255,255,255,0.3);
        border-color: rgba(255,255,255,0.5);
        color: white;
        text-decoration: none;
    }
    
    .stats-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        margin-bottom: 2rem;
    }
    
    .stat-card {
        background: white;
        border-radius: 10px;
        padding: 1.5rem;
        text-align: center;
        box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
    }
    
    .stat-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    }
    
    .stat-number {
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
    }
    
    .stat-label {
        color: #6c757d;
        font-size: 0.9rem;
    }
</style>

<div class="container py-4" style="max-width: 1200px;">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="search-section">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h1 class="h3 mb-2">
                            <i class="fas fa-users me-2"></i>
                            Gestión de Usuarios
                        </h1>
                        <p class="mb-0">Administra usuarios, roles y permisos del sistema</p>
                    </div>
                    <div class="col-md-4 text-end">
                        <button class="btn btn-light btn-lg" data-bs-toggle="modal" data-bs-target="#modalCrearUsuario">
                            <i class="fas fa-user-plus me-2"></i>
                            Nuevo Usuario
                        </button>
                    </div>
                </div>
                
                <!-- Filtros -->
                <div class="filter-tabs">
                    <a href="?filtro=todos" class="filter-tab <?php echo ($filtro ?? 'todos') === 'todos' ? 'active' : ''; ?>">
                        <i class="fas fa-users me-1"></i>Todos
                    </a>
                    <a href="?filtro=activos" class="filter-tab <?php echo ($filtro ?? '') === 'activos' ? 'active' : ''; ?>">
                        <i class="fas fa-check-circle me-1"></i>Activos
                    </a>
                    <a href="?filtro=admins" class="filter-tab <?php echo ($filtro ?? '') === 'admins' ? 'active' : ''; ?>">
                        <i class="fas fa-crown me-1"></i>Administradores
                    </a>
                    <a href="?filtro=revisores" class="filter-tab <?php echo ($filtro ?? '') === 'revisores' ? 'active' : ''; ?>">
                        <i class="fas fa-eye me-1"></i>Revisores
                    </a>
                </div>
                
                <!-- Búsqueda -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="input-group">
                            <span class="input-group-text bg-white">
                                <i class="fas fa-search text-muted"></i>
                            </span>
                            <input type="text" class="form-control" id="buscarUsuario" placeholder="Buscar por nombre o email...">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="input-group">
                            <span class="input-group-text bg-white">
                                <i class="fas fa-filter text-muted"></i>
                            </span>
                            <select class="form-select" id="filtroRol">
                                <option value="">Todos los roles</option>
                                <option value="admin">Administrador</option>
                                <option value="revisor">Revisor</option>
                                <option value="usuario">Usuario</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Estadísticas -->
    <div class="stats-row">
        <div class="stat-card">
            <div class="stat-number text-primary"><?php echo $stats['total'] ?? 0; ?></div>
            <div class="stat-label">Total Usuarios</div>
        </div>
        <div class="stat-card">
            <div class="stat-number text-success"><?php echo $stats['activos'] ?? 0; ?></div>
            <div class="stat-label">Usuarios Activos</div>
        </div>
        <div class="stat-card">
            <div class="stat-number text-danger"><?php echo $stats['admins'] ?? 0; ?></div>
            <div class="stat-label">Administradores</div>
        </div>
        <div class="stat-card">
            <div class="stat-number text-warning"><?php echo $stats['revisores'] ?? 0; ?></div>
            <div class="stat-label">Revisores</div>
        </div>
    </div>

    <!-- Lista de Usuarios -->
    <div class="row" id="listaUsuarios">
        <?php if (!empty($usuarios)): ?>
                            <?php foreach ($usuarios as $usuario): ?>
            <div class="col-md-6 col-lg-4 mb-4 usuario-item" 
                 data-nombre="<?php echo strtolower($usuario->nombre ?? ''); ?>" 
                 data-email="<?php echo strtolower($usuario->email); ?>"
                 data-rol="<?php echo $usuario->is_admin ? 'admin' : ($usuario->is_revisor ? 'revisor' : 'usuario'); ?>"
                 data-activo="<?php echo $usuario->activo ? 'si' : 'no'; ?>">
                <div class="user-card">
                    <div class="card-body text-center">
                        <div class="user-avatar <?php echo $usuario->is_admin ? 'admin' : ($usuario->is_revisor ? 'revisor' : 'user'); ?>">
                            <?php echo strtoupper(substr($usuario->nombre ?? 'U', 0, 1)); ?>
                        </div>
                        
                        <h5 class="card-title mb-1"><?php echo View::e($usuario->nombre ?? 'Sin nombre'); ?></h5>
                        <p class="text-muted mb-2"><?php echo View::e($usuario->email); ?></p>
                        
                        <div class="mb-3">
                            <span class="role-badge <?php echo $usuario->is_admin ? 'admin' : ($usuario->is_revisor ? 'revisor' : 'user'); ?>">
                                <?php if ($usuario->is_admin): ?>
                                    <i class="fas fa-crown me-1"></i>Administrador
                                <?php elseif ($usuario->is_revisor): ?>
                                    <i class="fas fa-eye me-1"></i>Revisor
                                        <?php else: ?>
                                    <i class="fas fa-user me-1"></i>Usuario
                                        <?php endif; ?>
                            </span>
                        </div>
                        
                        <div class="mb-3">
                            <span class="status-indicator <?php echo $usuario->activo ? 'active' : 'inactive'; ?>"></span>
                            <span class="text-muted">
                                <?php echo $usuario->activo ? 'Activo' : 'Inactivo'; ?>
                            </span>
                        </div>
                        
                        <div class="action-buttons">
                            <button class="btn btn-action btn-edit" onclick="editarUsuario(<?php echo $usuario->id; ?>)">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-action btn-toggle" onclick="toggleUsuario(<?php echo $usuario->id; ?>, <?php echo $usuario->activo ? 'false' : 'true'; ?>)">
                                <i class="fas fa-<?php echo $usuario->activo ? 'ban' : 'check'; ?>"></i>
                            </button>
                            <button class="btn btn-action btn-delete" onclick="eliminarUsuario(<?php echo $usuario->id; ?>)">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php else: ?>
            <div class="col-md-12">
                <div class="text-center py-5">
                    <i class="fas fa-users fs-1 text-muted mb-3"></i>
                    <h4 class="text-muted">No hay usuarios registrados</h4>
                    <p class="text-muted">Comienza creando el primer usuario del sistema</p>
                    <button class="btn btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#modalCrearUsuario">
                        <i class="fas fa-user-plus me-2"></i>Crear Usuario
                    </button>
                </div>
                </div>
            <?php endif; ?>
    </div>
</div>

<!-- Modal Crear Usuario -->
<div class="modal fade" id="modalCrearUsuario" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="fas fa-user-plus me-2"></i>
                    Crear Nuevo Usuario
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formCrearUsuario">
                <?php echo CsrfMiddleware::field(); ?>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Nombre Completo *</label>
                                <input type="text" class="form-control" name="nombre" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Email *</label>
                                <input type="email" class="form-control" name="email" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Rol *</label>
                                <select class="form-select" name="rol" required>
                                    <option value="">Seleccionar rol</option>
                                    <option value="usuario">Usuario</option>
                                    <option value="revisor">Revisor</option>
                                    <option value="admin">Administrador</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Estado</label>
                                <select class="form-select" name="activo">
                                    <option value="1">Activo</option>
                                    <option value="0">Inactivo</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Crear Usuario
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php View::endSection(); ?>

<?php View::startSection('scripts'); ?>
<script>
// Búsqueda en tiempo real
document.getElementById('buscarUsuario').addEventListener('input', function() {
    filtrarUsuarios();
});

document.getElementById('filtroRol').addEventListener('change', function() {
    filtrarUsuarios();
});

function filtrarUsuarios() {
    const busqueda = document.getElementById('buscarUsuario').value.toLowerCase();
    const filtroRol = document.getElementById('filtroRol').value;
    const usuarios = document.querySelectorAll('.usuario-item');
    
    usuarios.forEach(usuario => {
        const nombre = usuario.dataset.nombre;
        const email = usuario.dataset.email;
        const rol = usuario.dataset.rol;
        
        const coincideBusqueda = nombre.includes(busqueda) || email.includes(busqueda);
        const coincideRol = !filtroRol || rol === filtroRol;
        
        if (coincideBusqueda && coincideRol) {
            usuario.style.display = 'block';
        } else {
            usuario.style.display = 'none';
        }
    });
}

// Crear usuario
document.getElementById('formCrearUsuario').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('/admin/usuarios/crear', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Usuario creado exitosamente');
            location.reload();
        } else {
            alert('Error: ' + data.error);
        }
    });
});

// Editar usuario
function editarUsuario(id) {
    window.location.href = `/admin/usuarios/${id}/edit`;
}

// Toggle estado
function toggleUsuario(id, nuevoEstado) {
    const accion = nuevoEstado === 'true' ? 'activar' : 'desactivar';
    if (!confirm(`¿Estás seguro de ${accion} este usuario?`)) {
        return;
    }
    
    fetch(`/admin/usuarios/${id}/toggle`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            activo: nuevoEstado === 'true'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(`Usuario ${accion}do exitosamente`);
            location.reload();
        } else {
            alert('Error: ' + data.error);
        }
    });
}

// Eliminar usuario
function eliminarUsuario(id) {
    if (!confirm('¿Estás seguro de eliminar este usuario? Esta acción no se puede deshacer.')) {
        return;
    }
    
    fetch(`/admin/usuarios/${id}/eliminar`, {
        method: 'DELETE',
        headers: {
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Usuario eliminado exitosamente');
            location.reload();
        } else {
            alert('Error: ' + data.error);
        }
    });
}
</script>
<?php View::endSection(); ?>