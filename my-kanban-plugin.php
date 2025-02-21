<?php
/*
Plugin Name: My Kanban Plugin
Description: A simple Kanban board plugin.
Version: 1.0
Author: Your Name
*/

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * 1. Enqueue scripts and styles + localize admin-ajax URL
 */
function mkp_enqueue_scripts() {
    wp_enqueue_style(
        'mkp-styles',
        plugins_url('styles.css', __FILE__),
        array(), 
        null
    );

    wp_enqueue_script(
        'mkp-drag',
        plugins_url('drag.js', __FILE__),
        array(), 
        null,
        true
    );

    wp_enqueue_script(
        'mkp-todo',
        plugins_url('todo.js', __FILE__),
        array('mkp-drag'), 
        null,
        true
    );

    // Pass the AJAX endpoint to todo.js
    wp_localize_script('mkp-todo', 'mkpAjax', [
        'ajax_url' => admin_url('admin-ajax.php')
    ]);
}
add_action('wp_enqueue_scripts', 'mkp_enqueue_scripts');

/**
 * 2. Shortcode to display the Kanban board
 */
function mkp_render_kanban_board() {
    ob_start();
    ?>
    <div class="board">
      <form id="todo-form">
        <input type="text" placeholder="New TODO..." id="todo-input" />
        <button type="submit">Add +</button>
      </form>

      <div class="lanes">
        <div class="swim-lane" id="todo-lane">
          <h3 class="heading">TODO</h3>
        </div>
        <div class="swim-lane" id="doing-lane">
          <h3 class="heading">Doing</h3>
        </div>
        <div class="swim-lane" id="done-lane">
          <h3 class="heading">Done</h3>
        </div>
      </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('my_kanban_board', 'mkp_render_kanban_board');

/**
 * 3. Create custom table upon plugin activation
 */
register_activation_hook(__FILE__, 'mkp_install_table');
function mkp_install_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'kanban_tasks';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        title text NOT NULL,
        lane varchar(50) NOT NULL DEFAULT 'todo',
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

/**
 * 4. AJAX handler: Add a new task
 */
add_action('wp_ajax_mkp_add_task', 'mkp_add_task');
add_action('wp_ajax_nopriv_mkp_add_task', 'mkp_add_task');
function mkp_add_task() {
    if (!isset($_POST['title']) || !$_POST['title']) {
        wp_send_json_error(['message' => 'No title provided']);
    }

    $title = sanitize_text_field($_POST['title']);
    $lane  = sanitize_text_field($_POST['lane'] ?? 'todo');

    global $wpdb;
    $table_name = $wpdb->prefix . 'kanban_tasks';
    $success = $wpdb->insert(
        $table_name,
        [ 'title' => $title, 'lane' => $lane ],
        [ '%s', '%s' ]
    );

    if (false === $success) {
        wp_send_json_error(['message' => 'DB insert failed']);
    } else {
        wp_send_json_success([
            'message' => 'Task added',
            'task_id' => $wpdb->insert_id
        ]);
    }
}

/**
 * 5. AJAX handler: Get all tasks
 */
add_action('wp_ajax_mkp_get_tasks', 'mkp_get_tasks');
add_action('wp_ajax_nopriv_mkp_get_tasks', 'mkp_get_tasks');
function mkp_get_tasks() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'kanban_tasks';
    $results = $wpdb->get_results("SELECT id, title, lane FROM $table_name");

    if (!is_array($results)) {
        wp_send_json_error(['message' => 'Failed to retrieve tasks']);
    } else {
        wp_send_json_success([
            'message' => 'Tasks retrieved',
            'tasks'   => $results
        ]);
    }
}
