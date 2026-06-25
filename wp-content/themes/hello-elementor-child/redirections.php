<?php
function redirecciones_masivas() {
    $json_file = get_template_directory() . '/redirections.json';

    if (!file_exists($json_file)) {
        return;
    }

    $json_data = file_get_contents($json_file);
    $redirecciones = json_decode($json_data, true);

    if (!is_array($redirecciones)) {
        return;
    }

    $request_uri = $_SERVER['REQUEST_URI'];

    if (array_key_exists($request_uri, $redirecciones)) {
        wp_redirect($redirecciones[$request_uri], 301);
        exit();
    }
}
add_action('template_redirect', 'redirecciones_masivas');
