<?php

class EmailController extends Controller
{
    public function enviar(Request $request)
    {
        $this->requireProfessor();

        $to = $request->input('para', '');
        $subject = $request->input('assunto', '');
        $message = $request->input('mensagem', '');

        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $this->json(array(
                'success' => false,
                'message' => 'Informe um e-mail de destino válido.'
            ), 422);
        }

        if ($subject === '' || $message === '') {
            $this->json(array(
                'success' => false,
                'message' => 'Assunto e mensagem são obrigatórios.'
            ), 422);
        }

        if (strlen($subject) > 180 || strlen($message) > 10000) {
            $this->json(array(
                'success' => false,
                'message' => 'Assunto ou mensagem excede o tamanho permitido.'
            ), 422);
        }

        $html = EmailTemplate::render(array(
            'title' => $subject,
            'preheader' => $subject,
            'heading' => $subject,
            'content' => '<p>' . nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8')) . '</p>'
        ));

        try {
            $mailer = new Mailer();
            $mailer->send($to, $subject, $html, $message);
        } catch (Exception $exception) {
            error_log('Portal e-mail: ' . $exception->getMessage());
            $this->json(array(
                'success' => false,
                'message' => 'Não foi possível enviar o e-mail.'
            ), 500);
        }

        $this->json(array(
            'success' => true,
            'message' => 'E-mail enviado.'
        ), 200);
    }
}
