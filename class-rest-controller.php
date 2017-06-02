<?php

class WP_Notes_Controller {
    public function __construct() {
        $this->namespace     = '/wp-notes/v1';
        $this->resource_name = 'notes';
    }

    /**
     * Create custom endpoint for the app
     */
    public function register_routes() {
        register_rest_route( $this->namespace, '/' . $this->resource_name, array(
            array(
                'methods'   => 'GET',
                'callback'  => array( $this, 'get_items' ),
                'permission_callback' => array( $this, 'get_items_permissions_check' ),
                'args' => array(
                    'author' => array(
                        'validate_callback' => function($param, $request, $key) {
                            return is_numeric( $param );
                        }
                    )
                )
            ),
            'schema' => array( $this, 'get_item_schema' ),
        ));
    }

    /**
     * Check for the items permission
     *
     * @param request - request data
     *
     * @return bool - if user has a permission or not to get items
     */
    public function get_items_permissions_check( $request ) {
        if ( ! current_user_can( 'read_private_posts' ) ) {
            return new WP_Error( 'rest_forbidden', esc_html__( 'You cannot view the post resource.' ), array( 'status' => $this->authorization_status_code() ) );
        }

        return true;
    }

    /**
     * Function used to retrieve data of the endpoint data
     *
     * @param request - request data
     *
     * @return object - response data
     */
    public function get_items( $request ) {
        $args = array(
            'author' => $request->get_param( 'author' ),
            'posts_per_page' => -1,
            'post_type' => 'wp_notes',
            'post_status' => 'private'
        );
        $posts = get_posts( $args );

        $data = array();

        if ( empty( $posts ) ) {
            return rest_ensure_response( $data );
        }

        foreach ( $posts as $post ) {
            $response = $this->prepare_item_for_response( $post, $request );
            $data[] = $this->prepare_response_for_collection( $response );
        }

        return rest_ensure_response( $data );
    }

    /**
     * Prepare item before returning a response
     *
     * @param post - post data
     * @param request - request data
     *
     * @return object - response data
     */
    public function prepare_item_for_response( $post, $request ) {
        $post_data = array();

        $schema = $this->get_item_schema( $request );

        // We are also renaming the fields to more understandable names.
        if ( isset( $schema['properties']['id'] ) ) {
            $post_data['id'] = (int) $post->ID;
        }

        if ( isset( $schema['properties']['modificationDate'] ) ) {
            $post_data['modificationDate'] = strtotime($post->post_modified_gmt . ' UTC') * 1000;
        }

        return rest_ensure_response( $post_data );
    }

    /**
     * Prepare items before returning a response
     *
     * @param request - request data
     *
     * @return object - response data
     */
    public function prepare_response_for_collection( $response ) {
        if ( ! ( $response instanceof WP_REST_Response ) ) {
            return $response;
        }

        $data = (array) $response->get_data();
        $server = rest_get_server();

        if ( method_exists( $server, 'get_compact_response_links' ) ) {
            $links = call_user_func( array( $server, 'get_compact_response_links' ), $response );
        } else {
            $links = call_user_func( array( $server, 'get_response_links' ), $response );
        }

        if ( ! empty( $links ) ) {
            $data['_links'] = $links;
        }

        return $data;
    }

    /**
     * Define item schema
     *
     * @param request - request data
     *
     * @return object - schema data
     */
    public function get_item_schema( $request ) {
        $schema = array(
            '$schema'              => 'http://json-schema.org/draft-04/schema#',
            'title'                => 'note',
            'type'                 => 'object',
            'properties'           => array(
                'id' => array(
                    'description'  => esc_html__( 'Unique identifier for the object.', 'dziudek-wp-notes' ),
                    'type'         => 'integer',
                    'context'      => array( 'view', 'edit', 'embed' ),
                    'readonly'     => true
                ),
                'modificationDate' => array(
                    'description'  => esc_html__( 'The GMT time of the last post modification.', 'dziudek-wp-notes' ),
                    'type'         => 'integer'
                )
            ),
        );

        return $schema;
    }

    /**
     * Return authorization status code
     *
     * @return int - status code
     */
    public function authorization_status_code() {
        $status = 401;

        if ( is_user_logged_in() ) {
            $status = 403;
        }

        return $status;
    }
}

/**
 * Define custom REST routes
 */
function dziudek_wp_notes_register_my_rest_routes() {
    $controller = new WP_Notes_Controller();
    $controller->register_routes();
}

add_action( 'rest_api_init', 'dziudek_wp_notes_register_my_rest_routes' );
