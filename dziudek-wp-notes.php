<?php

/**
 * @wordpress-plugin
 * Plugin Name:       Dziudek WP Notes
 * Plugin URI:        http://dziudek.pl
 * Description:       Plugin used to create CPT and endpoints for the WP Notes Electron App
 * Version:           1.0.0
 * Author:            Tomasz Dziuda
 * Author URI:        http://dziudek.pl
 * License:           GPL-3.0
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.txt
 * Text Domain:       dziudek-wp-notes
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

require plugin_dir_path(__FILE__) . 'class-rest-controller.php';

/**
 * Create Custom Post Type dedicated for application
 */
function dziudek_wp_notes_cpt() {
    $labels = array(
        'name'                  => _x( 'Notes', 'Post Type General Name', 'dziudek_wp_notes_cpt' ),
        'singular_name'         => _x( 'Note', 'Post Type Singular Name', 'dziudek_wp_notes_cpt' ),
        'menu_name'             => __( 'Notes', 'dziudek_wp_notes_cpt' ),
        'name_admin_bar'        => __( 'Notes', 'dziudek_wp_notes_cpt' ),
        'archives'              => __( 'Notes archive', 'dziudek_wp_notes_cpt' ),
        'attributes'            => __( 'Note attributes', 'dziudek_wp_notes_cpt' ),
        'parent_item_colon'     => __( 'Parent note:', 'dziudek_wp_notes_cpt' ),
        'all_items'             => __( 'All notes', 'dziudek_wp_notes_cpt' ),
        'add_new_item'          => __( 'Add New Note', 'dziudek_wp_notes_cpt' ),
        'add_new'               => __( 'Add New', 'dziudek_wp_notes_cpt' ),
        'new_item'              => __( 'New Note', 'dziudek_wp_notes_cpt' ),
        'edit_item'             => __( 'Edit Note', 'dziudek_wp_notes_cpt' ),
        'update_item'           => __( 'Update Note', 'dziudek_wp_notes_cpt' ),
        'view_item'             => __( 'View Note', 'dziudek_wp_notes_cpt' ),
        'view_items'            => __( 'View Notes', 'dziudek_wp_notes_cpt' ),
        'search_items'          => __( 'Search Notes', 'dziudek_wp_notes_cpt' ),
        'not_found'             => __( 'Not found', 'dziudek_wp_notes_cpt' ),
        'not_found_in_trash'    => __( 'Not found in Trash', 'dziudek_wp_notes_cpt' ),
        'featured_image'        => __( 'Featured Image', 'dziudek_wp_notes_cpt' ),
        'set_featured_image'    => __( 'Set featured image', 'dziudek_wp_notes_cpt' ),
        'remove_featured_image' => __( 'Remove featured image', 'dziudek_wp_notes_cpt' ),
        'use_featured_image'    => __( 'Use as featured image', 'dziudek_wp_notes_cpt' ),
        'insert_into_item'      => __( 'Insert into note', 'dziudek_wp_notes_cpt' ),
        'uploaded_to_this_item' => __( 'Uploaded to this note', 'dziudek_wp_notes_cpt' ),
        'items_list'            => __( 'Notes list', 'dziudek_wp_notes_cpt' ),
        'items_list_navigation' => __( 'Notes list navigation', 'dziudek_wp_notes_cpt' ),
        'filter_items_list'     => __( 'Filter notes list', 'dziudek_wp_notes_cpt' ),
    );

    $args = array(
        'label'                 => __( 'Note', 'dziudek_wp_notes_cpt' ),
        'description'           => __( 'For WP Notes Electron App', 'dziudek_wp_notes_cpt' ),
        'labels'                => $labels,
        'supports'              => array( 'title', 'editor', 'author', 'revisions', ),
        'taxonomies'            => array( 'category', 'post_tag' ),
        'hierarchical'          => false,
        'public'                => true,
        'show_ui'               => true,
        'show_in_menu'          => true,
        'menu_position'         => 5,
        'menu_icon'             => 'dashicons-media-default',
        'show_in_admin_bar'     => true,
        'show_in_nav_menus'     => true,
        'can_export'            => true,
        'has_archive'           => false,
        'exclude_from_search'   => true,
        'publicly_queryable'    => true,
        'capability_type'       => 'page',
        'show_in_rest'          => true,
        'rest_base'             => 'wp-notes',
    );

    register_post_type( 'wp_notes', $args );
}

add_action( 'init', 'dziudek_wp_notes_cpt', 0 );

/**
 * Prepare wp_notes endpoint
 */
function dziudek_wp_notes_use_raw_content( $data, $post, $request ) {
    // Remove unused data to decrease response size
    unset($data->data['content']['rendered']);
    unset($data->data['title']['rendered']);

    // Add field with plain text of the post content (Markdown does not like <p>, <br> tags)
    $data->data['content']['plaintext'] = $post->post_content;

    // Add field with plain text of the post title (without "Private: " prefix)
    $data->data['title']['plaintext'] = $post->post_title;

    // Change string with date into JS-compatible timestamp in UTC time
    $data->data['modified_gmt'] = strtotime($post->post_modified_gmt . ' UTC') * 1000;

    return $data;
}

add_filter( 'rest_prepare_wp_notes', 'dziudek_wp_notes_use_raw_content', 10, 3 );
