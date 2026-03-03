<?php
/**
 * EmailService
 * 
 * Servicio para gestionar el envío de emails usando PHPMailer.
 * Soporta plantillas HTML, modo prueba, adjuntos y reintentos automáticos.
 * 
 * @package RequisicionesMVC\Services
 * @version 2.0
 */

namespace App\Services;

use App\Helpers\Config;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailService
{
    /**
     * Instancia de PHPMailer
     * 
     * @var PHPMailer
     */
    private $mailer;

    /**
     * Modo de prueba
     * 
     * @var bool
     */
    private $testMode;

    /**
     * Email de prueba
     * 
     * @var string
     */
    private $testRecipient;

    /**
     * Último error
     * 
     * @var string
     */
    private $lastError;

    /**
     * Modo skip (solo log, no envío)
     * 
     * @var bool
     */
    private $skipMode;

    /**
     * Constructor
     * Inicializa PHPMailer con la configuración del sistema
     */
    public function __construct()
    {
        try {
            // Intentar cargar PHPMailer
            if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
                // Intentar cargar el autoloader de Composer si no está cargado
                $autoloadPath = __DIR__ . '/../../vendor/autoload.php';
                if (file_exists($autoloadPath)) {
                    require_once $autoloadPath;
                }
            }
            
            if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
                $this->mailer = new PHPMailer(true);
                $this->configurarMailer();
            } else {
                // Modo fallback sin PHPMailer
                $this->mailer = null;
                error_log("PHPMailer no disponible - EmailService en modo fallback");
            }
        } catch (\Exception $e) {
            // Si hay error inicializando PHPMailer, usar modo fallback
            $this->mailer = null;
            error_log("Error inicializando PHPMailer: " . $e->getMessage());
        }
        
        $this->testMode = Config::get('mail.test_mode', false);
        $this->testRecipient = Config::get('mail.test_recipient') ?: Config::get('mail.test_email', '');
        $this->lastError = '';
        
        // Modo desarrollo: solo logear en lugar de enviar
        $this->skipMode = Config::get('mail.skip_sending', false);
    }

    /**
     * Configura PHPMailer con los parámetros del sistema
     * 
     * @return void
     */
    private function configurarMailer()
    {
        // Solo configurar si PHPMailer está disponible
        if (!$this->mailer) {
            return;
        }
        
        try {
            // Configuración del servidor
            $this->mailer->isSMTP();
            $this->mailer->Host = Config::get('mail.mailers.smtp.host') ?: Config::get('mail.host', '');
            $this->mailer->SMTPAuth = true;
            $this->mailer->Username = Config::get('mail.mailers.smtp.username') ?: Config::get('mail.username', '');
            $this->mailer->Password = Config::get('mail.mailers.smtp.password') ?: Config::get('mail.password', '');
            $encryption = Config::get('mail.mailers.smtp.encryption') ?: Config::get('mail.encryption', 'tls');
            $this->mailer->SMTPSecure = $encryption === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
            $this->mailer->Port = Config::get('mail.mailers.smtp.port') ?: Config::get('mail.port', 587);
            $this->mailer->CharSet = 'UTF-8';

            // Remitente por defecto
            $fromEmail = Config::get('mail.from.address', '');
            $fromName = Config::get('mail.from.name', '');
            $this->mailer->setFrom($fromEmail, $fromName);

            // Configuración adicional
            $this->mailer->isHTML(true);
            
            // Debug desactivado - el output de SMTPDebug rompe las redirecciones HTTP
            // Si necesitas debug, usa SMTPDebug = 2 y Debugoutput = 'error_log'
            $this->mailer->SMTPDebug = 0;
            // Para depurar en logs sin afectar la página, descomentar:
            // if (Config::get('app.debug', false)) {
            //     $this->mailer->SMTPDebug = 2;
            //     $this->mailer->Debugoutput = 'error_log'; // Enviar a logs, no a pantalla
            // }
        } catch (Exception $e) {
            error_log("Error configurando mailer: " . $e->getMessage());
            $this->lastError = $e->getMessage();
        }
    }

    /**
     * Envía un email simple (texto plano)
     * 
     * @param string $to Email destinatario
     * @param string $subject Asunto
     * @param string $body Cuerpo del mensaje
     * @param array $options Opciones adicionales (cc, bcc, reply_to)
     * @return array Resultado del envío
     */
    public function send($to, $subject, $body, $options = [])
    {
        // Modo skip: solo logear, no enviar
        if ($this->skipMode) {
            error_log("EmailService: SKIP MODE - Email no enviado");
            error_log("Para: $to | Asunto: $subject");
            return [
                'success' => true,
                'message' => 'Email no enviado (skip mode)',
                'code' => 'SKIPPED_SEND'
            ];
        }
        
        // Si PHPMailer no está disponible, simular envío exitoso
        if (!$this->mailer) {
            error_log("EmailService: Simulando envío de email (PHPMailer no disponible)");
            error_log("Para: $to | Asunto: $subject");
            return [
                'success' => true,
                'message' => 'Email simulado (PHPMailer no disponible)',
                'code' => 'SIMULATED_SEND'
            ];
        }
        
        try {
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();
            $this->mailer->clearAllRecipients();

            // Modo prueba: redirigir a email de prueba
            if ($this->testMode && $this->testRecipient) {
                $originalTo = $to;
                $to = $this->testRecipient;
                $body = "[MODO PRUEBA - Original: {$originalTo}]\n\n" . $body;
            }

            $this->mailer->addAddress($to);
            $this->mailer->Subject = $subject;
            $this->mailer->Body = $body;
            $this->mailer->isHTML(false);

            // Opciones adicionales
            $this->procesarOpciones($options);

            $this->mailer->send();

            $this->logEmail($to, $subject, 'success');

            return [
                'success' => true,
                'message' => 'Email enviado correctamente',
                'to' => $to
            ];
        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            error_log("Error enviando email a {$to}: " . $e->getMessage());
            
            $this->logEmail($to, $subject, 'error', $e->getMessage());

            return [
                'success' => false,
                'error' => 'Error al enviar email: ' . $e->getMessage(),
                'code' => 'EMAIL_SEND_ERROR'
            ];
        }
    }

    /**
     * Envía un email HTML
     * 
     * @param string $to Email destinatario
     * @param string $subject Asunto
     * @param string $htmlBody Cuerpo HTML
     * @param array $options Opciones adicionales
     * @return array Resultado del envío
     */
    public function sendHtml($to, $subject, $htmlBody, $options = [])
    {
        // Si PHPMailer no está disponible, simular envío exitoso
        if (!$this->mailer) {
            error_log("EmailService: Simulando envío de email HTML (PHPMailer no disponible)");
            error_log("Para: $to | Asunto: $subject");
            return [
                'success' => true,
                'message' => 'Email HTML simulado (PHPMailer no disponible)',
                'code' => 'SIMULATED_SEND'
            ];
        }
        
        try {
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();
            $this->mailer->clearAllRecipients();

            // Modo prueba
            if ($this->testMode && $this->testRecipient) {
                $originalTo = $to;
                $to = $this->testRecipient;
                $htmlBody = "<div style='background: #fff3cd; padding: 10px; border: 1px solid #ffc107; margin-bottom: 10px;'>"
                          . "<strong>MODO PRUEBA</strong> - Destinatario original: {$originalTo}"
                          . "</div>" . $htmlBody;
            }

            $this->mailer->addAddress($to);
            $this->mailer->Subject = $subject;
            $this->mailer->Body = $htmlBody;
            $this->mailer->isHTML(true);

            // Generar versión texto plano
            $this->mailer->AltBody = strip_tags($htmlBody);

            // Opciones adicionales
            $this->procesarOpciones($options);

            $this->mailer->send();

            $this->logEmail($to, $subject, 'success');

            return [
                'success' => true,
                'message' => 'Email HTML enviado correctamente',
                'to' => $to
            ];
        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            error_log("Error enviando email HTML a {$to}: " . $e->getMessage());
            
            $this->logEmail($to, $subject, 'error', $e->getMessage());

            return [
                'success' => false,
                'error' => 'Error al enviar email: ' . $e->getMessage(),
                'code' => 'EMAIL_SEND_ERROR'
            ];
        }
    }

    /**
     * Envía un email usando una plantilla HTML
     * 
     * @param string $to Email destinatario
     * @param string $subject Asunto
     * @param string $template Nombre de la plantilla
     * @param array $data Datos para reemplazar en la plantilla
     * @param array $options Opciones adicionales
     * @return array Resultado del envío
     */
    public function sendWithTemplate($to, $subject, $template, $data = [], $options = [])
    {
        try {
            // Cargar plantilla
            $templateContent = $this->loadTemplate($template);
            
            if (!$templateContent) {
                return [
                    'success' => false,
                    'error' => 'Plantilla no encontrada: ' . $template,
                    'code' => 'TEMPLATE_NOT_FOUND'
                ];
            }

            // Reemplazar variables
            $htmlBody = $this->replaceVariables($templateContent, $data);

            // Enviar email HTML
            return $this->sendHtml($to, $subject, $htmlBody, $options);
        } catch (\Exception $e) {
            error_log("Error enviando email con plantilla: " . $e->getMessage());
            
            return [
                'success' => false,
                'error' => 'Error al procesar plantilla: ' . $e->getMessage(),
                'code' => 'TEMPLATE_ERROR'
            ];
        }
    }

    /**
     * Envía email a múltiples destinatarios
     * 
     * @param array $recipients Array de emails
     * @param string $subject Asunto
     * @param string $body Cuerpo del mensaje
     * @param bool $isHtml Si el cuerpo es HTML
     * @param array $options Opciones adicionales
     * @return array Resultado del envío
     */
    public function sendToMultiple($recipients, $subject, $body, $isHtml = true, $options = [])
    {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();
            $this->mailer->clearAllRecipients();

            // Modo prueba: solo enviar al email de prueba
            if ($this->testMode && $this->testRecipient) {
                $recipients = [$this->testRecipient];
                $body = "[MODO PRUEBA - Múltiples destinatarios]\n\n" . $body;
            }

            // Agregar destinatarios
            foreach ($recipients as $email) {
                if ($this->validate($email)) {
                    $this->mailer->addAddress($email);
                }
            }

            $this->mailer->Subject = $subject;
            $this->mailer->Body = $body;
            $this->mailer->isHTML($isHtml);

            if ($isHtml) {
                $this->mailer->AltBody = strip_tags($body);
            }

            // Opciones adicionales
            $this->procesarOpciones($options);

            $this->mailer->send();

            $this->logEmail(implode(', ', $recipients), $subject, 'success');

            return [
                'success' => true,
                'message' => 'Emails enviados correctamente',
                'count' => count($recipients)
            ];
        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            error_log("Error enviando emails múltiples: " . $e->getMessage());
            
            return [
                'success' => false,
                'error' => 'Error al enviar emails: ' . $e->getMessage(),
                'code' => 'EMAIL_BATCH_ERROR'
            ];
        }
    }

    /**
     * Envía un lote de emails (cola)
     * 
     * @param array $emails Array de emails con formato: ['to', 'subject', 'body', 'template', 'data']
     * @return array Resultado con éxitos y fallos
     */
    public function sendBatch($emails)
    {
        $results = [
            'success' => true,
            'total' => count($emails),
            'sent' => 0,
            'failed' => 0,
            'errors' => []
        ];

        foreach ($emails as $index => $email) {
            try {
                $to = $email['to'] ?? '';
                $subject = $email['subject'] ?? '';
                $body = $email['body'] ?? '';
                $template = $email['template'] ?? null;
                $data = $email['data'] ?? [];
                $options = $email['options'] ?? [];

                if ($template) {
                    $result = $this->sendWithTemplate($to, $subject, $template, $data, $options);
                } else {
                    $result = $this->sendHtml($to, $subject, $body, $options);
                }

                if ($result['success']) {
                    $results['sent']++;
                } else {
                    $results['failed']++;
                    $results['errors'][] = [
                        'index' => $index,
                        'to' => $to,
                        'error' => $result['error'] ?? 'Error desconocido'
                    ];
                }

                // Pequeña pausa entre emails
                usleep(100000); // 0.1 segundos
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = [
                    'index' => $index,
                    'error' => $e->getMessage()
                ];
            }
        }

        $results['success'] = $results['failed'] === 0;

        return $results;
    }

    /**
     * Carga una plantilla HTML
     * 
     * @param string $templateName Nombre de la plantilla (sin extensión)
     * @return string|false Contenido de la plantilla o false
     */
    public function loadTemplate($templateName)
    {
        $templatePath = __DIR__ . '/../Views/emails/' . $templateName . '.html';

        if (!file_exists($templatePath)) {
            error_log("Plantilla no encontrada: {$templatePath}");
            return false;
        }

        return file_get_contents($templatePath);
    }

    /**
     * Reemplaza variables en una plantilla
     * Las variables deben estar en formato {{variable}}
     * 
     * @param string $template Contenido de la plantilla
     * @param array $data Array asociativo con las variables
     * @return string Plantilla con variables reemplazadas
     */
    public function replaceVariables($template, $data)
    {
        foreach ($data as $key => $value) {
            // Escapar HTML si el valor no es seguro
            $safeValue = is_string($value) ? htmlspecialchars($value, ENT_QUOTES, 'UTF-8') : $value;
            $template = str_replace('{{' . $key . '}}', $safeValue, $template);
        }

        // Limpiar variables no reemplazadas
        $template = preg_replace('/\{\{[^}]+\}\}/', '', $template);

        return $template;
    }

    /**
     * Agrega un archivo adjunto
     * 
     * @param string $filePath Ruta del archivo
     * @param string $name Nombre con el que aparecerá el archivo (opcional)
     * @return bool
     */
    public function addAttachment($filePath, $name = '')
    {
        try {
            if (!file_exists($filePath)) {
                throw new \Exception("Archivo no encontrado: {$filePath}");
            }

            $this->mailer->addAttachment($filePath, $name);
            return true;
        } catch (Exception $e) {
            error_log("Error agregando adjunto: " . $e->getMessage());
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * Valida formato de email
     * 
     * @param string $email Email a validar
     * @return bool
     */
    public function validate($email)
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Formatea una dirección de email con nombre
     * 
     * @param string $email Email
     * @param string $name Nombre
     * @return string Email formateado
     */
    public function formatAddress($email, $name = '')
    {
        if ($name) {
            return "{$name} <{$email}>";
        }
        return $email;
    }

    /**
     * Verifica si está en modo prueba
     * 
     * @return bool
     */
    public function isTestMode()
    {
        return $this->testMode;
    }

    /**
     * Envía un email al destinatario de prueba
     * 
     * @param string $subject Asunto
     * @param string $body Cuerpo
     * @return array Resultado
     */
    public function sendToTestRecipient($subject, $body)
    {
        if (!$this->testRecipient) {
            return [
                'success' => false,
                'error' => 'No hay email de prueba configurado',
                'code' => 'NO_TEST_RECIPIENT'
            ];
        }

        return $this->sendHtml($this->testRecipient, '[TEST] ' . $subject, $body);
    }

    /**
     * Registra el envío de un email
     * 
     * @param string $to Destinatario
     * @param string $subject Asunto
     * @param string $status Estado (success, error)
     * @param string $errorMessage Mensaje de error (opcional)
     * @return void
     */
    private function logEmail($to, $subject, $status, $errorMessage = '')
    {
        $logMessage = sprintf(
            "[EMAIL] To: %s | Subject: %s | Status: %s",
            $to,
            $subject,
            $status
        );

        if ($errorMessage) {
            $logMessage .= " | Error: {$errorMessage}";
        }

        error_log($logMessage);
    }

    /**
     * Obtiene el último error
     * 
     * @return string
     */
    public function getLastError()
    {
        return $this->lastError;
    }

    /**
     * Procesa opciones adicionales del email
     * 
     * @param array $options Opciones (cc, bcc, reply_to, attachments)
     * @return void
     */
    private function procesarOpciones($options)
    {
        try {
            // CC (Con Copia)
            if (isset($options['cc'])) {
                $ccList = is_array($options['cc']) ? $options['cc'] : [$options['cc']];
                foreach ($ccList as $cc) {
                    if ($this->validate($cc)) {
                        $this->mailer->addCC($cc);
                    }
                }
            }

            // BCC (Con Copia Oculta)
            if (isset($options['bcc'])) {
                $bccList = is_array($options['bcc']) ? $options['bcc'] : [$options['bcc']];
                foreach ($bccList as $bcc) {
                    if ($this->validate($bcc)) {
                        $this->mailer->addBCC($bcc);
                    }
                }
            }

            // Reply To
            if (isset($options['reply_to'])) {
                $replyTo = $options['reply_to'];
                $replyToName = $options['reply_to_name'] ?? '';
                if ($this->validate($replyTo)) {
                    $this->mailer->addReplyTo($replyTo, $replyToName);
                }
            }

            // Adjuntos
            if (isset($options['attachments']) && is_array($options['attachments'])) {
                foreach ($options['attachments'] as $attachment) {
                    if (is_string($attachment)) {
                        $this->addAttachment($attachment);
                    } elseif (is_array($attachment) && isset($attachment['path'])) {
                        $name = $attachment['name'] ?? '';
                        $this->addAttachment($attachment['path'], $name);
                    }
                }
            }
        } catch (Exception $e) {
            error_log("Error procesando opciones de email: " . $e->getMessage());
        }
    }

    /**
     * Reinicia el mailer para un nuevo envío
     * 
     * @return void
     */
    public function reset()
    {
        $this->mailer->clearAddresses();
        $this->mailer->clearAttachments();
        $this->mailer->clearAllRecipients();
        $this->mailer->clearCCs();
        $this->mailer->clearBCCs();
        $this->mailer->clearReplyTos();
        $this->lastError = '';
    }
}
