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

// 1. Enqueue scripts and styles
function mkp_enqueue_scripts() {
    // Use plugins_url() to reference files in this same folder
    wp_enqueue_style(
        'mkp-styles',
        plugins_url('styles.css', __FILE__),
        array(), // no dependencies
        null     // no version
    );

    // Enqueue drag.js first
    wp_enqueue_script(
        'mkp-drag',
        plugins_url('drag.js', __FILE__),
        array(), // no dependencies
        null,
        true     // load in footer
    );

    // Enqueue todo.js second
    wp_enqueue_script(
        'mkp-todo',
        plugins_url('todo.js', __FILE__),
        array('mkp-drag'), // depends on drag.js
        null,
        true
    );
}
add_action('wp_enqueue_scripts', 'mkp_enqueue_scripts');

// 2. Shortcode to display the Kanban board
function mkp_render_kanban_board() {
    // Instead of index.html, we define the board’s HTML here
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
          <!-- In the original index.html, there were sample tasks like:
               <p class="task" draggable="true">Get groceries</p> 
               You can add them here or let the user create tasks themselves. -->
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
// Trigger this function when the plugin is activated
register_activation_hook(__FILE__, 'mkp_install_table');

/**
 * Creates custom table for the Kanban tasks upon plugin activation.
 */
function mkp_install_table() {
    global $wpdb;

    // Name of the table (wp_ is the prefix, $wpdb->prefix detects what your site is using).
    $table_name = $wpdb->prefix . 'kanban_tasks';

    // This gets the database character set/collation info
    $charset_collate = $wpdb->get_charset_collate();

    // Define the SQL for creating the table
    // mediumint(9) is just an example data type for the ID. Adjust as needed.
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        title text NOT NULL,
        lane varchar(50) NOT NULL DEFAULT 'todo',
        PRIMARY KEY  (id)
    ) $charset_collate;";

    // dbDelta is a WordPress function that safely runs queries, creating or updating tables
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
}
