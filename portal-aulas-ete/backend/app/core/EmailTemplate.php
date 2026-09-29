<?php

class EmailTemplate
{
    public static function render($data)
    {
        if (!is_array($data)) {
            $data = array();
        }

        $title = isset($data['title']) ? $data['title'] : 'Portal de Aulas ETE';
        $preheader = isset($data['preheader']) ? $data['preheader'] : '';
        $heading = isset($data['heading']) ? $data['heading'] : '';
        $content = isset($data['content']) ? $data['content'] : '';
        $footer = isset($data['footer']) ? $data['footer'] : 'Escola Técnica Estadual — Portal de Aulas';

        ob_start();
        include dirname(__DIR__) . '/views/email/layout.php';
        return ob_get_clean();
    }
}
