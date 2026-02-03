<?php

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Helpers\View;
use App\Helpers\Redirect;
use App\Helpers\Session;
use App\Helpers\Config;
use App\Services\EmailService;

class EmailController extends Controller
{
    private $emailService;

    public function __construct()
    {
        parent::__construct();

        // Verificar que es administrador
        if (!Session::isAdmin()) {
            Redirect::to('/dashboard')
                ->withError('No tienes permisos de administrador')
                ->send();
        }

        $this->emailService = new EmailService();
    }

    /**
     * Muestra la página principal de configuración de correo
     */
    public function index()
    {
        $config = $this->getEmailConfig();
        $templates = $this->getEmailTemplates();

        View::render('admin/email/index', [
            'title' => 'Configuración de Correo',
            'config' => $config,
            'templates' => $templates
        ]);
    }

    /**
     * Muestra el formulario de configuración SMTP
     */
    public function config()
    {
        $config = $this->getEmailConfig();

        View::render('admin/email/config', [
            'title' => 'Configuración SMTP',
            'config' => $config
        ]);
    }

    /**
     * Guarda la configuración SMTP
     */
    public function saveConfig()
    {
        if (!$this->validateCSRF()) {
            Redirect::back()
                ->withError('Token de seguridad inválido')
                ->send();
        }

        $data = [
            'host' => $_POST['host'] ?? '',
            'port' => (int)($_POST['port'] ?? 587),
            'encryption' => $_POST['encryption'] ?? 'tls',
            'username' => $_POST['username'] ?? '',
            'password' => $_POST['password'] ?? '',
            'from_address' => $_POST['from_address'] ?? '',
            'from_name' => $_POST['from_name'] ?? '',
            'test_mode' => isset($_POST['test_mode']) ? 1 : 0,
            'test_recipient' => $_POST['test_recipient'] ?? '',
            'skip_sending' => isset($_POST['skip_sending']) ? 1 : 0
        ];

        // Validar datos requeridos
        if (empty($data['host']) || empty($data['from_address'])) {
            Redirect::back()
                ->withError('El servidor SMTP y la dirección de remitente son obligatorios')
                ->withInput()
                ->send();
        }

        // Guardar configuración
        $result = $this->saveEmailConfig($data);

        if ($result) {
            Redirect::to('/admin/email/config')
                ->withSuccess('Configuración de correo guardada correctamente')
                ->send();
        } else {
            Redirect::back()
                ->withError('Error al guardar la configuración')
                ->withInput()
                ->send();
        }
    }

    /**
     * Muestra la lista de plantillas de correo
     */
    public function templates()
    {
        $templates = $this->getEmailTemplates();

        View::render('admin/email/templates', [
            'title' => 'Plantillas de Correo',
            'templates' => $templates
        ]);
    }

    /**
     * Muestra el editor de una plantilla
     */
    public function editTemplate($template)
    {
        $templateContent = $this->getTemplateContent($template);

        if (!$templateContent) {
            Redirect::to('/admin/email/templates')
                ->withError('Plantilla no encontrada')
                ->send();
        }

        View::render('admin/email/edit_template', [
            'title' => 'Editar Plantilla: ' . $template,
            'template' => $template,
            'content' => $templateContent,
            'variables' => $this->getTemplateVariables($template)
        ]);
    }

    /**
     * Guarda una plantilla
     */
    public function saveTemplate($template)
    {
        if (!$this->validateCSRF()) {
            Redirect::back()
                ->withError('Token de seguridad inválido')
                ->send();
        }

        $content = $_POST['content'] ?? '';

        if (empty($content)) {
            Redirect::back()
                ->withError('El contenido de la plantilla no puede estar vacío')
                ->send();
        }

        $result = $this->saveTemplateContent($template, $content);

        if ($result) {
            Redirect::to('/admin/email/templates')
                ->withSuccess('Plantilla guardada correctamente')
                ->send();
        } else {
            Redirect::back()
                ->withError('Error al guardar la plantilla')
                ->send();
        }
    }

    /**
     * Prueba la conexión SMTP
     */
    public function testConnection()
    {
        // Siempre responder como JSON para peticiones AJAX
        $isAjax = $this->isAjaxRequest();
        
        // Función para responder con JSON de forma segura
        $respondJson = function($data, $statusCode = 200) use ($isAjax) {
            // Limpiar cualquier output previo
            while (ob_get_level()) {
                ob_end_clean();
            }
            
            http_response_code($statusCode);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        };
        
        // Configurar para capturar errores
        error_reporting(E_ALL);
        ini_set('display_errors', '0'); // No mostrar errores en HTML
        ini_set('log_errors', '1');
        
        // Registrar handler de errores para capturar errores fatales
        set_error_handler(function($errno, $errstr, $errfile, $errline) use ($respondJson, $isAjax) {
            if ($isAjax) {
                $respondJson([
                    'success' => false,
                    'error' => 'Error en el servidor',
                    'details' => 'Error: ' . $errstr . ' en ' . basename($errfile) . ':' . $errline
                ], 500);
            }
            return false; // Continuar con el handler por defecto
        });
        
        // Registrar handler de excepciones no capturadas
        set_exception_handler(function($exception) use ($respondJson, $isAjax) {
            if ($isAjax) {
                $respondJson([
                    'success' => false,
                    'error' => 'Error en el servidor',
                    'details' => 'Excepción: ' . $exception->getMessage()
                ], 500);
            }
        });
        
        try {
            if (!$this->validateCSRF()) {
                if ($isAjax) {
                    $respondJson([
                        'success' => false,
                        'error' => 'Token de seguridad inválido'
                    ], 403);
                }
                Redirect::back()
                    ->withError('Token de seguridad inválido')
                    ->send();
                return;
            }

            $host = $_POST['host'] ?? '';
            $port = (int)($_POST['port'] ?? 587);
            $encryption = $_POST['encryption'] ?? 'tls';
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';

            if (empty($host) || empty($port)) {
                if ($isAjax) {
                    $respondJson([
                        'success' => false,
                        'error' => 'El servidor SMTP y el puerto son obligatorios'
                    ], 400);
                } else {
                    Redirect::back()
                        ->withError('El servidor SMTP y el puerto son obligatorios')
                        ->send();
                }
                return;
            }

            // Intentar cargar PHPMailer
            if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
                $autoloadPath = __DIR__ . '/../../../vendor/autoload.php';
                if (file_exists($autoloadPath)) {
                    require_once $autoloadPath;
                }
            }

            if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
                throw new \Exception('PHPMailer no está disponible. Por favor, instálelo con Composer.');
            }

            $mailer = new \PHPMailer\PHPMailer\PHPMailer(true);
            
            // Configurar SMTP
            $mailer->isSMTP();
            $mailer->Host = $host;
            $mailer->Port = $port;
            $mailer->SMTPAuth = !empty($username);
            $mailer->Timeout = 10; // Timeout de 10 segundos
            
            if (!empty($username)) {
                $mailer->Username = $username;
                $mailer->Password = $password;
            }
            
            // Configurar cifrado
            if ($encryption === 'ssl') {
                $mailer->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
            } elseif ($encryption === 'tls') {
                $mailer->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            } else {
                $mailer->SMTPSecure = '';
            }
            
            // Opciones SSL más permisivas para pruebas
            $mailer->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true,
                    'crypto_method' => STREAM_CRYPTO_METHOD_TLS_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT
                ]
            ];
            
            // Intentar conectar
            // smtpConnect() devuelve true si la conexión fue exitosa, false en caso contrario
            $connected = $mailer->smtpConnect();
            
            if ($connected) {
                // Cerrar la conexión de prueba
                $mailer->smtpClose();
                
                if ($isAjax) {
                    $respondJson([
                        'success' => true,
                        'message' => 'Conexión establecida correctamente con el servidor SMTP'
                    ]);
                } else {
                    Redirect::back()
                        ->withSuccess('Conexión establecida correctamente')
                        ->send();
                }
                return;
            } else {
                throw new \Exception('No se pudo establecer la conexión con el servidor SMTP');
            }
        } catch (\PHPMailer\PHPMailer\Exception $e) {
            $errorMessage = $e->getMessage();
            $details = '';
            
            // Obtener variables del scope del try
            $host = $_POST['host'] ?? '';
            $port = (int)($_POST['port'] ?? 587);
            $encryption = $_POST['encryption'] ?? 'tls';
            
            // Mensajes más amigables basados en el error de PHPMailer
            if (strpos($errorMessage, 'SMTP connect() failed') !== false || strpos($errorMessage, 'SMTP Error') !== false) {
                $errorMessage = 'No se pudo conectar al servidor SMTP.';
                $details = 'Verifique que el servidor (' . $host . ') y el puerto (' . $port . ') sean correctos. ';
                if ($encryption === 'ssl' && $port != 465) {
                    $details .= 'Para SSL, el puerto típico es 465. ';
                } elseif ($encryption === 'tls' && $port != 587 && $port != 25) {
                    $details .= 'Para TLS, los puertos típicos son 587 o 25. ';
                }
                $details .= 'Verifique también que no haya un firewall bloqueando la conexión.';
            } elseif (strpos($errorMessage, 'Connection refused') !== false || strpos($errorMessage, 'Connection timed out') !== false || strpos($errorMessage, 'timed out') !== false) {
                $errorMessage = 'El servidor rechazó la conexión o no respondió.';
                $details = 'Verifique que el servidor esté disponible y que el puerto no esté bloqueado por un firewall.';
            } elseif (strpos($errorMessage, 'Authentication failed') !== false || strpos($errorMessage, 'Invalid credentials') !== false || strpos($errorMessage, '535') !== false || strpos($errorMessage, '534') !== false) {
                $errorMessage = 'Error de autenticación.';
                $details = 'Las credenciales (usuario/contraseña) proporcionadas no son válidas.';
            } elseif (strpos($errorMessage, 'SSL') !== false || strpos($errorMessage, 'TLS') !== false || strpos($errorMessage, 'certificate') !== false) {
                $errorMessage = 'Error en el cifrado SSL/TLS.';
                $details = 'Verifique que el tipo de cifrado (' . $encryption . ') sea correcto para este servidor. ';
                if ($encryption === 'ssl') {
                    $details .= 'Algunos servidores requieren SSL en el puerto 465.';
                } elseif ($encryption === 'tls') {
                    $details .= 'Algunos servidores requieren TLS en el puerto 587.';
                }
            } else {
                $details = 'Error técnico: ' . substr($errorMessage, 0, 150);
            }
            
            if ($isAjax) {
                $respondJson([
                    'success' => false,
                    'error' => $errorMessage,
                    'details' => $details
                ], 500);
            } else {
                Redirect::back()
                    ->withError($errorMessage . ($details ? ' ' . $details : ''))
                    ->send();
            }
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            $details = '';
            
            // Mensajes más amigables
            if (strpos($errorMessage, 'Connection refused') !== false) {
                $errorMessage = 'No se pudo conectar al servidor. Verifique que el servidor y el puerto sean correctos.';
                $details = 'El servidor rechazó la conexión. Puede que el puerto esté bloqueado o el servidor no esté disponible.';
            } elseif (strpos($errorMessage, 'Connection timed out') !== false || strpos($errorMessage, 'timed out') !== false) {
                $errorMessage = 'Tiempo de espera agotado.';
                $details = 'El servidor no respondió en el tiempo esperado. Verifique la conectividad de red.';
            } elseif (strpos($errorMessage, 'Authentication failed') !== false || strpos($errorMessage, 'Invalid credentials') !== false) {
                $errorMessage = 'Error de autenticación. Verifique el usuario y la contraseña.';
                $details = 'Las credenciales proporcionadas no son válidas.';
            } elseif (strpos($errorMessage, 'Could not connect') !== false) {
                $errorMessage = 'No se pudo conectar al servidor SMTP.';
                $details = 'Verifique que el servidor y el puerto sean correctos, y que no haya un firewall bloqueando la conexión.';
            } else {
                $details = 'Error: ' . substr($errorMessage, 0, 150);
            }
            
            if ($isAjax) {
                $respondJson([
                    'success' => false,
                    'error' => $errorMessage ?: 'Error desconocido al conectar con el servidor SMTP',
                    'details' => $details
                ], 500);
            } else {
                Redirect::back()
                    ->withError($errorMessage . ($details ? ' ' . $details : ''))
                    ->send();
            }
        } finally {
            // Restaurar handlers
            restore_error_handler();
            restore_exception_handler();
        }
    }

    /**
     * Prueba el envío de correo
     */
    public function testEmail()
    {
        if (!$this->validateCSRF()) {
            if ($this->isAjaxRequest()) {
                $this->jsonResponse([
                    'success' => false,
                    'error' => 'Token de seguridad inválido'
                ], 403);
                return;
            }
            Redirect::back()
                ->withError('Token de seguridad inválido')
                ->send();
        }

        $to = $_POST['email'] ?? '';
        $subject = $_POST['subject'] ?? 'Prueba de correo';
        $template = $_POST['template'] ?? 'base';

        if (empty($to)) {
            if ($this->isAjaxRequest()) {
                $this->jsonResponse([
                    'success' => false,
                    'error' => 'Debe especificar un email de destino'
                ], 400);
                return;
            }
            Redirect::back()
                ->withError('Debe especificar un email de destino')
                ->send();
        }

        // Limpiar cualquier output previo para respuestas AJAX
        $isAjax = $this->isAjaxRequest();
        if ($isAjax) {
            while (ob_get_level()) {
                ob_end_clean();
            }
        }

        // Enviar correo de prueba
        $result = $this->emailService->sendWithTemplate(
            $to,
            $subject,
            $template,
            [
                'app_name' => Config::get('app.name', 'Sistema de Requisiciones'),
                'year' => date('Y'),
                'titulo' => 'Correo de Prueba',
                'content' => '<p>Este es un correo de prueba del sistema de configuración de correo.</p>'
            ]
        );

        if ($isAjax) {
            // Asegurar que el resultado sea un array válido
            if (!is_array($result)) {
                $result = [
                    'success' => false,
                    'error' => 'Error desconocido al enviar el correo'
                ];
            }
            
            // Limpiar output nuevamente antes de enviar JSON
            while (ob_get_level()) {
                ob_end_clean();
            }
            
            $this->jsonResponse($result);
        } else {
            if ($result['success'] ?? false) {
                Redirect::back()
                    ->withSuccess('Correo de prueba enviado correctamente')
                    ->send();
            } else {
                Redirect::back()
                    ->withError('Error al enviar correo: ' . ($result['error'] ?? 'Error desconocido'))
                    ->send();
            }
        }
    }

    /**
     * Obtiene la configuración de correo actual
     */
    private function getEmailConfig()
    {
        $mailConfig = Config::load('mail');
        
        return [
            'host' => $mailConfig['mailers']['smtp']['host'] ?? '',
            'port' => $mailConfig['mailers']['smtp']['port'] ?? 587,
            'encryption' => $mailConfig['mailers']['smtp']['encryption'] ?? 'tls',
            'username' => $mailConfig['mailers']['smtp']['username'] ?? '',
            'password' => $mailConfig['mailers']['smtp']['password'] ?? '',
            'from_address' => $mailConfig['from']['address'] ?? '',
            'from_name' => $mailConfig['from']['name'] ?? '',
            'test_mode' => $mailConfig['test_mode'] ?? false,
            'test_recipient' => $mailConfig['test_email'] ?? $mailConfig['test_recipient'] ?? '',
            'skip_sending' => $mailConfig['skip_sending'] ?? false
        ];
    }

    /**
     * Guarda la configuración de correo
     */
    private function saveEmailConfig($data)
    {
        $configFile = __DIR__ . '/../../../config/mail.php';
        
        // Leer el archivo actual
        $currentConfig = file_exists($configFile) ? require $configFile : [];
        
        // Inicializar estructura si no existe
        if (!isset($currentConfig['mailers'])) {
            $currentConfig['mailers'] = [];
        }
        if (!isset($currentConfig['mailers']['smtp'])) {
            $currentConfig['mailers']['smtp'] = [];
        }
        if (!isset($currentConfig['from'])) {
            $currentConfig['from'] = [];
        }
        
        // Actualizar configuración
        $currentConfig['mailers']['smtp']['host'] = $data['host'];
        $currentConfig['mailers']['smtp']['port'] = $data['port'];
        $currentConfig['mailers']['smtp']['encryption'] = $data['encryption'];
        $currentConfig['mailers']['smtp']['username'] = $data['username'];
        $currentConfig['mailers']['smtp']['password'] = $data['password'];
        $currentConfig['from']['address'] = $data['from_address'];
        $currentConfig['from']['name'] = $data['from_name'];
        $currentConfig['test_mode'] = (bool)$data['test_mode'];
        $currentConfig['test_recipient'] = $data['test_recipient'];
        $currentConfig['skip_sending'] = (bool)$data['skip_sending'];

        // Generar contenido PHP con formato legible
        $content = "<?php\n\nreturn [\n";
        $content .= "    'default' => 'smtp',\n\n";
        $content .= "    'mailers' => [\n";
        $content .= "        'smtp' => [\n";
        $content .= "            'transport' => 'smtp',\n";
        $content .= "            'host' => " . var_export($data['host'], true) . ",\n";
        $content .= "            'port' => " . $data['port'] . ",\n";
        $content .= "            'encryption' => " . var_export($data['encryption'], true) . ",\n";
        $content .= "            'username' => " . var_export($data['username'], true) . ",\n";
        $content .= "            'password' => " . var_export($data['password'], true) . ",\n";
        $content .= "            'timeout' => null,\n";
        $content .= "        ],\n";
        $content .= "    ],\n\n";
        $content .= "    'from' => [\n";
        $content .= "        'address' => " . var_export($data['from_address'], true) . ",\n";
        $content .= "        'name' => " . var_export($data['from_name'], true) . ",\n";
        $content .= "    ],\n\n";
        $content .= "    'test_mode' => " . ($data['test_mode'] ? 'true' : 'false') . ",\n";
        $content .= "    'test_recipient' => " . var_export($data['test_recipient'], true) . ",\n";
        $content .= "    'skip_sending' => " . ($data['skip_sending'] ? 'true' : 'false') . ",\n";
        $content .= "];\n";

        return file_put_contents($configFile, $content) !== false;
    }

    /**
     * Obtiene la lista de plantillas disponibles
     */
    private function getEmailTemplates()
    {
        $templatesDir = __DIR__ . '/../../Views/emails/';
        $templates = [];

        if (is_dir($templatesDir)) {
            $files = glob($templatesDir . '*.html');
            foreach ($files as $file) {
                $name = basename($file, '.html');
                $templates[$name] = [
                    'name' => $name,
                    'path' => $file,
                    'size' => filesize($file),
                    'modified' => filemtime($file)
                ];
            }
        }

        return $templates;
    }

    /**
     * Obtiene el contenido de una plantilla
     */
    private function getTemplateContent($template)
    {
        $templateFile = __DIR__ . '/../../Views/emails/' . $template . '.html';
        
        if (file_exists($templateFile)) {
            return file_get_contents($templateFile);
        }

        return null;
    }

    /**
     * Guarda el contenido de una plantilla
     */
    private function saveTemplateContent($template, $content)
    {
        $templateFile = __DIR__ . '/../../Views/emails/' . $template . '.html';
        
        // Crear directorio si no existe
        $dir = dirname($templateFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        return file_put_contents($templateFile, $content) !== false;
    }

    /**
     * Obtiene las variables disponibles para una plantilla
     */
    private function getTemplateVariables($template)
    {
        $variables = [
            'base' => ['app_name', 'year', 'titulo', 'content'],
            'nueva_requisicion' => ['app_name', 'year', 'titulo', 'requisicion_id', 'usuario_nombre', 'monto_total', 'fecha'],
            'aprobacion' => ['app_name', 'year', 'titulo', 'requisicion_id', 'autorizador_nombre', 'comentario'],
            'rechazo' => ['app_name', 'year', 'titulo', 'requisicion_id', 'motivo_rechazo'],
            'completada' => ['app_name', 'year', 'titulo', 'requisicion_id', 'fecha_completada'],
            'recordatorio' => ['app_name', 'year', 'titulo', 'requisicion_id', 'dias_pendiente'],
            'urgente_autorizacion' => ['app_name', 'year', 'titulo', 'requisicion_id', 'dias_pendiente']
        ];

        return $variables[$template] ?? $variables['base'];
    }
}

