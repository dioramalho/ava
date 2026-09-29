<?php

class Mailer
{
    private $config;

    public function __construct($config = null)
    {
        if (!is_array($config)) {
            $all = require dirname(dirname(__DIR__)) . '/config/config.php';
            $config = isset($all['mail']) && is_array($all['mail']) ? $all['mail'] : array();
        }

        $this->config = $config;
    }

    /**
     * Envia um e-mail via SMTP usando PHPMailer.
     *
     * @param string $to
     * @param string $subject
     * @param string $htmlBody
     * @param string $textBody
     * @return bool
     * @throws Exception
     */
    public function send($to, $subject, $htmlBody, $textBody = '')
    {
        if (!class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
            throw new Exception('PHPMailer não está instalado. Execute composer install no backend.');
        }

        $to = trim((string) $to);
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('E-mail de destino inválido.');
        }

        $host = isset($this->config['host']) ? trim((string) $this->config['host']) : '';
        if ($host === '') {
            throw new Exception('Servidor de e-mail não configurado.');
        }

        $from = isset($this->config['from_address']) ? trim((string) $this->config['from_address']) : '';
        if (!filter_var($from, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Remetente de e-mail não configurado.');
        }

        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = $host;
        $mail->Port = isset($this->config['port']) ? (int) $this->config['port'] : 587;
        $mail->CharSet = 'UTF-8';
        $mail->Timeout = 20;

        $caFile = dirname(dirname(__DIR__)) . '/config/cacert.pem';
        if (is_file($caFile)) {
            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => true,
                    'verify_peer_name' => true,
                    'allow_self_signed' => false,
                    'cafile' => $caFile
                )
            );
        }

        $username = isset($this->config['username']) ? (string) $this->config['username'] : '';
        $password = isset($this->config['password']) ? (string) $this->config['password'] : '';
        $mail->SMTPAuth = ($username !== '');
        if ($mail->SMTPAuth) {
            $mail->Username = $username;
            $mail->Password = $password;
        }

        $encryption = isset($this->config['encryption']) ? strtolower(trim((string) $this->config['encryption'])) : 'tls';
        if ($encryption === 'tls') {
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        } elseif ($encryption === 'ssl') {
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        } else {
            $mail->SMTPSecure = '';
            $mail->SMTPAutoTLS = false;
        }

        $fromName = isset($this->config['from_name']) ? (string) $this->config['from_name'] : 'Portal de Aulas ETE';
        $mail->setFrom($from, $fromName);
        $mail->addAddress($to);
        $mail->Subject = (string) $subject;
        $mail->isHTML(true);
        $mail->Body = (string) $htmlBody;
        $mail->AltBody = $textBody !== '' ? (string) $textBody : trim(strip_tags((string) $htmlBody));

        $mail->send();

        return true;
    }
}
