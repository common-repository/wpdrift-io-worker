<?php
/**
 * EDD_GetTerm_Taxonomy_Endpoint class
 *
 * @category Edd
 * @package  Edd
 * @author   Rajendra Banker <bankerrajendra@upnrunn.com>
 * @license  GNU
 * @link     NA
 */

defined('ABSPATH') || exit;

/**
 * EDD Term Taxonomy endpoints.
 *
 * @category Edd
 * @package  Edd
 * @author   Rajendra Banker <bankerrajendra@upnrunn.com>
 * @license  GNU
 * @link     NA
 * @since    1.0.0
 */
class EDD_GetTerm_Taxonomy_Endpoint extends WP_REST_Controller
{
    /**
     * Constructor.
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        $this->namespace = 'wpdriftio/v1';
        $this->rest_base = 'getterm-taxonomy';
    }

    /**
     * Register the component routes.
     *
     * @since  1.0.0
     * @return return
     */
    public function registerRoutes()
    {
        register_rest_route(
            $this->namespace, 
            '/' . $this->rest_base, 
            array(
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array( $this, 'getItems' ),
                    'permission_callback' => array( 
                                                $this, 
                                                'getItemsPermissionsCheck' 
                                            ),
                    'args'                => array(),
                )
            )
        );
    }

    /**
     * Get a collection of items
     *
     * @param WP_REST_Request $request Full data about the request.
     *
     * @return WP_Error|WP_REST_Response
     */
    public function getItems($request)
    {
        $parameters = $request->get_params();
        $items = array();
        $items['edd_term_taxonomy'] = $this->retrieveEddTermTaxonomy($parameters);
        $data = array();
        foreach ($items as $key => $item) {
            $itemdata = $this->prepareItemForResponse($item, $request);
            $data[$key] = $this->prepare_response_for_collection($itemdata);
        }

        return rest_ensure_response($data);
    }

    /**
     * Prepare the item for the REST response
     *
     * @param mixed           $item    WordPress representation of the item.
     * @param WP_REST_Request $request Request object.
     *
     * @return mixed
     */
    public function prepareItemForResponse($item, $request)
    {
        return $item;
    }

    /**
     * Check if a given request has access to get items
     *
     * @param WP_REST_Request $request Full data about the request.
     *
     * @return WP_Error|bool
     */
    public function getItemsPermissionsCheck($request)
    {
        return current_user_can('list_users');
    }

    /**
     * Retrieve EDD Term Taxonomy
     *
     * @param string $parameters params
     *
     * @return Term Taxonpmy
     */
    public function retrieveEddTermTaxonomy($parameters)
    {
        global $wpdb;
        $task = (isset($parameters['task']) && trim($parameters['task']) != "") ? trim($parameters['task']) : "";
        $term_id = (isset($parameters['term_id']) && trim($parameters['term_id']) != "") ? trim($parameters['term_id']) : "";
        $term_taxonomy = (isset($parameters['taxonomy']) && trim($parameters['taxonomy']) != "") ? trim($parameters['taxonomy']) : "";

        if($task == "get_single" && $term_id != "" && $term_taxonomy != "") {
            $edd_term_taxonomy = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM 
                    ".$wpdb->prefix."term_taxonomy 
                    WHERE taxonomy LIKE %s AND 
                    term_id = %s", $term_taxonomy, $term_id
                )
            );
            // get term name and slug //
            $term_details = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT `name`, `slug` 
                    FROM ".$wpdb->prefix."terms 
                    WHERE term_id = %d", $term_id
                )
            );

            $terms_tax_arry['term_taxonomy_id'] 
                = $edd_term_taxonomy[0]->term_taxonomy_id;
            $terms_tax_arry['term_id'] = $edd_term_taxonomy[0]->term_id;
            $terms_tax_arry['name'] = $term_details[0]->name;
            $terms_tax_arry['slug'] = $term_details[0]->slug;
            $terms_tax_arry['taxonomy'] = $edd_term_taxonomy[0]->taxonomy;
            $terms_tax_arry['description'] = $edd_term_taxonomy[0]->description;
            $terms_tax_arry['parent'] = $edd_term_taxonomy[0]->parent;
            $terms_tax_arry['count'] = $edd_term_taxonomy[0]->count;

            return $terms_tax_arry;

        } else {
            $edd_term_taxonomy = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM 
                    ".$wpdb->prefix."term_taxonomy 
                    WHERE taxonomy LIKE %s 
                        OR taxonomy LIKE %s", 'download_category', 'download_tag'
                )
            );
            $terms_tax_arry = array();
            $i = 0;
            foreach ($edd_term_taxonomy as $term_tax_value) {
                // get term name and slug //
                $term_details = $wpdb->get_results(
                    $wpdb->prepare(
                        "SELECT `name`, `slug` 
                        FROM ".$wpdb->prefix."terms 
                        WHERE term_id = %d", $term_tax_value->term_id
                    )
                );

                $terms_tax_arry[$i]['term_taxonomy_id'] 
                    = $term_tax_value->term_taxonomy_id;
                $terms_tax_arry[$i]['term_id'] = $term_tax_value->term_id;
                $terms_tax_arry[$i]['name'] = $term_details[0]->name;
                $terms_tax_arry[$i]['slug'] = $term_details[0]->slug;
                $terms_tax_arry[$i]['taxonomy'] = $term_tax_value->taxonomy;
                $terms_tax_arry[$i]['description'] = $term_tax_value->description;
                $terms_tax_arry[$i]['parent'] = $term_tax_value->parent;
                $terms_tax_arry[$i]['count'] = $term_tax_value->count;

                $i++;
            }
            return $terms_tax_arry;
        }
    }
}
