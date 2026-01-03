<?php
/**
 * Plugin Name: Upload Image Manager
 * Description: Upload images with trade date, view as table, and delete. Uses remote storage via API.
 * Version: 1.3
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

// 🔹 Optional CSS
add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style('uim-style', plugin_dir_url(__FILE__) . 'assets/uim-style.css');
});

// 🔹 AJAX to fetch uploaded images (from Remote)
add_action('wp_ajax_fetch_uploaded_images', 'uim_fetch_uploaded_images');
add_action('wp_ajax_nopriv_fetch_uploaded_images', 'uim_fetch_uploaded_images');

function uim_fetch_uploaded_images() {
    $date = sanitize_text_field($_GET['date'] ?? '');
    $url = 'https://image.rdalgo.in/wp-json/rdalgo/v1/images?date=' . urlencode($date);
    $response = wp_remote_get($url);
    $body = wp_remote_retrieve_body($response);
    wp_send_json(json_decode($body));
}

// 🔹 AJAX to delete image (from Remote)
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

// 🔹 AJAX to fetch Pending Names for Dropdown (Local DB - Remote Check)
add_action('wp_ajax_fetch_pending_names', 'uim_fetch_pending_names');
add_action('wp_ajax_nopriv_fetch_pending_names', 'uim_fetch_pending_names');

function uim_fetch_pending_names() {
    global $wpdb;

    $date = sanitize_text_field($_GET['date'] ?? date('Y-m-d'));

    // 1. Fetch Local "Approved" records from wp_taa_staging
    // Using simple concatenation of chart_name + strike + dir
    $table_name = 'wp_taa_staging'; 
    
    // Check if table exists to prevent errors
    if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
         wp_send_json(['success' => false, 'message' => 'DB Table not found']);
    }

    // Filter by Date(created_at) and Status='Approved'
    // Added 'dir' to the SELECT statement
    $query = $wpdb->prepare("
        SELECT chart_name, strike, dir
        FROM $table_name
        WHERE DATE(created_at) = %s
        AND status = 'Approved'
    ", $date);

    $results = $wpdb->get_results($query);

    // 2. Fetch already uploaded images from Remote API to exclude them
    $url = 'https://image.rdalgo.in/wp-json/rdalgo/v1/images?date=' . urlencode($date);
    $response = wp_remote_get($url);
    $remote_names = [];

    if (!is_wp_error($response)) {
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        if (is_array($data)) {
            // Collect names of images already uploaded
            $remote_names = array_column($data, 'name');
        }
    }

    // 3. Filter the list
    $final_list = [];
    foreach ($results as $row) {
        // Name Format: chart_name + " " + strike + " " + dir
        // Using trim to handle cases where a column might be empty/null
        $part1 = $row->chart_name ?? '';
        $part2 = $row->strike ?? '';
        $part3 = $row->dir ?? '';
        
        $combined_name = trim("$part1 $part2 $part3");

        // Only add if NOT in remote list
        if (!empty($combined_name) && !in_array($combined_name, $remote_names)) {
            $final_list[] = $combined_name;
        }
    }

    // Remove duplicates just in case
    $final_list = array_values(array_unique($final_list));

    wp_send_json(['success' => true, 'data' => $final_list]);
}