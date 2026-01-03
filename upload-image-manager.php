<?php
/**
 * Plugin Name: Upload Image Manager
 * Description: Upload images with trade date, view as table, and delete. Uses remote storage via API.
 * Version: 1.1
 * Author: RD Algo
 */

if (!defined('ABSPATH')) exit;

// 🔹 Shortcode to render upload + gallery
add_shortcode('upload_image_manager', 'render_upload_image_manager');

function render_upload_image_manager() {
    ob_start();
    include plugin_dir_path(__FILE__) . 'templates/form-view.php';
    include plugin_dir_path(__FILE__) . 'templates/gallery-view.php';
    return ob_get_clean();
}

// 🔹 Optional CSS (create if needed)
add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style('uim-style', plugin_dir_url(__FILE__) . 'assets/uim-style.css');
});

// 🔹 AJAX to fetch uploaded images
add_action('wp_ajax_fetch_uploaded_images', 'uim_fetch_uploaded_images');
add_action('wp_ajax_nopriv_fetch_uploaded_images', 'uim_fetch_uploaded_images');

function uim_fetch_uploaded_images() {
    $date = sanitize_text_field($_GET['date'] ?? '');
    $url = 'https://image.rdalgo.in/wp-json/rdalgo/v1/images?date=' . urlencode($date);
    $response = wp_remote_get($url);
    $body = wp_remote_retrieve_body($response);
    wp_send_json(json_decode($body));
}

// 🔹 AJAX to delete image from remote server
add_action('wp_ajax_delete_uploaded_image', 'uim_delete_uploaded_image');

function uim_delete_uploaded_image() {
    $id = intval($_POST['id'] ?? 0);
    if (!$id) {
        wp_send_json(['success' => false, 'message' => 'Invalid ID']);
    }

    $response = wp_remote_post('https://image.rdalgo.in/wp-json/rdalgo/v1/delete', [
        'body' => ['id' => $id],
    ]);

    $body = wp_remote_retrieve_body($response);
    wp_send_json(json_decode($body));
}
