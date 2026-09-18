<?php
/**
 * Plugin Name: Doctype Inserter
 * Description: Fügt einen benutzerdefinierten Text direkt nach <!DOCTYPE html> ein.
 * Version: 1.0
 * Author: Cem Firat
 */

if (!defined('ABSPATH')) exit;

// Admin-Menü und Einstellungsseite
add_action('admin_menu', function () {
    add_options_page(
        'Doctype Inserter',
        'Doctype Inserter',
        'manage_options',
        'doctype-inserter',
        'doctype_inserter_settings_page'
    );
});

// Einstellungsseite anzeigen
function doctype_inserter_settings_page() {
    ?>
    <div class="wrap">
        <h1>Doctype Inserter</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('doctype_inserter_options');
            do_settings_sections('doctype-inserter');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

// Registrierung der Einstellungen
add_action('admin_init', function () {
    register_setting('doctype_inserter_options', 'doctype_inserter_text');

    add_settings_section(
        'doctype_inserter_main',
        'Text direkt nach <!DOCTYPE html>',
        null,
        'doctype-inserter'
    );

    add_settings_field(
        'doctype_inserter_field',
        'Auszugebender Text (HTML, Kommentar, Skript, ...)',
        'doctype_inserter_field_render',
        'doctype-inserter',
        'doctype_inserter_main'
    );
});

// Eingabefeld im Backend
function doctype_inserter_field_render() {
    $value = get_option('doctype_inserter_text', '');
    echo '<textarea name="doctype_inserter_text" rows="6" cols="70" style="font-family: monospace;">' . esc_textarea($value) . '</textarea>';
}

// Ausgabe vor dem <html>-Tag einfügen
add_action('template_redirect', function () {
    ob_start('doctype_inserter_buffer_callback');
});

function doctype_inserter_buffer_callback($buffer) {
    $injection = get_option('doctype_inserter_text', '');
    if (!empty($injection)) {
        $buffer = preg_replace(
            '/(<!DOCTYPE html[^>]*>)/i',
            '$1' . PHP_EOL . $injection,
            $buffer,
            1
        );
    }
    return $buffer;
}
