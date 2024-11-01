<?php
/**
 * EDD_GetPayments_Endpoint class
 *
 * @category Edd
 * @package  Edd
 * @author   Rajendra Banker <bankerrajendra@upnrunn.com>
 * @license  GNU
 * @link     NA
 */

defined('ABSPATH') || exit;

/**
 * Edd Webhooks
 *
 * @category EDD_WebHooks
 * @package  Edd
 * @author   Rajendra Banker <bankerrajendra@upnrunn.com>
 * @license  GNU
 * @link     NA
 * @since    1.0.0
 */
class EDD_WebHooks
{
    public $edd_site = "https://edd.wpdrift.io";
    /**
     * Send customer id to app.wpdrift site upon customer create action
     * 
     * @param int $post_ID - created customer id
     *
     * @return return
     */
    function sendCustomerCreated($post_ID)  
    {
        $this->sendWebhook(
            'handle_customer_create', 
            array( 
                'post_id' => $post_ID 
            )
        );
    }
    /**
     * Send customer id to app.wpdrift site upon customer update action
     * 
     * @param int $updated - updated
     * @param int $post_ID - updated customer id
     *
     * @return return
     */
    function sendCustomerUpdated($updated, $post_ID) 
    {
        $this->sendWebhook('handle_customer_update', array( 'post_id' => $post_ID ));
    }
    /**
     * Send customer id to app.wpdrift site upon customer create action
     * 
     * @param int $updated - updated
     * @param int $post_ID - updated customer id
     *
     * @return return
     */
    function sendCustomerDeleted($updated, $post_ID) 
    {
        $this->sendWebhook('handle_customer_delete', array( 'post_id' => $post_ID ));
    }
    /**
     * USER
     * 
     * @param int $user_id - customer id
     *
     * @return return
     */
    function sendUserCreated($user_id) 
    {
        $this->sendWebhook('handle_user_create', array( 'user_id' => $user_id ));
    }
    /**
     * Send updated user id upon update user action
     * 
     * @param int $user_id - user id
     *
     * @return return
     */
    function sendUserUpdated($user_id) 
    {
        $this->sendWebhook('handle_user_update', array( 'user_id' => $user_id ));
    }
    /**
     * User Delete action
     * 
     * @param int $user_id - user id
     *
     * @return return
     */
    function sendUserDeleted($user_id) 
    {
        $this->sendWebhook('handle_user_delete', array( 'user_id' => $user_id ));
    }
    /**
     * TERM ASSIGNED
     * 
     * @param int $object_id - term object id
     *
     * @return return
     */
    function sendTermAssigned($object_id) 
    {
        $this->sendWebhook('handle_term_assign', array( 'object_id' => $object_id ));
    }
    /**
     * TERM CREATE
     * 
     * @param int    $term_id  - term id
     * @param int    $tt_id    - term taxonomy id
     * @param string $taxonomy - term id
     *
     * @return return
     */
    function sendCreateTerm($term_id, $tt_id, $taxonomy)
    {
        if ($taxonomy != 'download_category' && $taxonomy != "download_tag") {
            return;
        }
        $this->sendWebhook('handle_term_create', array($term_id, $tt_id, $taxonomy));
    }
    /**
     * TERM EDIT
     * 
     * @param int    $term_id  - term id
     * @param int    $tt_id    - term taxonomy id
     * @param string $taxonomy - term id
     *
     * @return return
     */
    function sendEditTerm($term_id, $tt_id, $taxonomy)
    {
        if ($taxonomy != 'download_category' && $taxonomy != "download_tag") {
            return;
        }
        $this->sendWebhook('handle_term_update', array($term_id, $tt_id, $taxonomy));
    }
    /**
     * TERM DELETE
     * 
     * @param obj    $term         - object
     * @param int    $tt_id        - term tax. id
     * @param string $taxonomy     - taxonomy
     * @param int    $deleted_term - deleted term
     *
     * @return return
     */
    function sendDeleteTerm($term, $tt_id, $taxonomy, $deleted_term)
    {
        if ($taxonomy != 'download_category' && $taxonomy != "download_tag") {
            return;
        }
        $this->sendWebhook(
            'handle_term_delete', 
            array($term, $tt_id, $taxonomy, $deleted_term)
        );
    }
    /**
     * ALL POST TYPES INSERTS
     * 
     * @param int $post_id - created customer id
     *
     * @return return
     */
    function sendPostCreated($post_id) 
    {
        $post_type = get_post_type($post_id);
        $params = array( 'post_id' => $post_id );
        // if not any required post type return
        if (!in_array(
            $post_type, 
            array(
            'edd_discount', 
            'download', 
            'edd_log', 
            'edd_payment'
            )
        )
        ) {
            return;
        }
        switch ($post_type) {
        case 'edd_discount':
            $this->sendWebhook('handle_discount_create', $params);
            break;
        case 'download':
            $this->sendWebhook('handle_download_create', $params);
            break;
        case 'edd_log':
            $this->sendWebhook('handle_eddlog_create', $params);
            break;
        case 'edd_payment':
            $this->sendWebhook('handle_payment_create', $params);
            break;
        }
    }
    
    /**
     * ALL POST TYPES UPDATES
     * 
     * @param int $post_id - created customer id
     *
     * @return return
     */
    function sendPostUpdated($meta, $postObj) 
    {
        $post_type = $postObj->post_type;
        $params = array( 'post_id' => $postObj->ID );
        // if not any required post type return
        if (!in_array(
            $post_type, 
            array(
                'edd_discount', 
                'download', 
                'edd_log', 
                'edd_payment'
            )
        )
        ) {
            return;
        }
        switch ($post_type) {
        case 'edd_discount':
            $this->sendWebhook('handle_discount_update', $params);
            break;
        case 'download':
            $this->sendWebhook('handle_download_update', $params);
            break;
        case 'edd_log':
            $this->sendWebhook('handle_eddlog_update', $params);
            break;
        case 'edd_payment':
            $this->sendWebhook('handle_payment_update', $params);
            break;
        }
    }

    /**
     * ALL POST TYPES DELETES
     * 
     * @param int $post_id - created customer id
     * 
     * @return return
     */
    function sendPostDeleted($post_id) 
    {
        global $post_type;
        $params = array( 'post_id' => $post_id );
        // if not any required post type return
        if (!in_array(
            $post_type, 
            array(
                //'edd_discount', 
                'download', 
                'edd_log', 
                //'edd_payment'
            )
        ) 
        ) {
            return;
        }
        switch ($post_type) {
        case 'download':
            $this->sendWebhook('handle_download_delete', $params);
            break;
        case 'edd_log':
            $this->sendWebhook('handle_eddlog_delete', $params);
            break;
        }
    }

    /**
     * Delete discount hook
     * 
     * @param array $data
     * 
     * @return return
     */
    function eddDeleteDiscount($data) 
    {
        $params = array( 'post_id' => $data['discount'] );
        $this->sendWebhook('handle_discount_delete', $params);
    }

    /**
     * Delete Payment hook
     * 
     * @param int $payment_id
     * 
     * @return return
     */
    function eddPaymentDelete($payment_id) 
    {
        $params = array( 'post_id' => $payment_id );
        $this->sendWebhook('handle_payment_delete', $params);
    }

    /**
     * Send web hook to app site
     * 
     * @param string $end_point - api end point
     * @param string $params    - parameters
     *
     * @return return
     */
    protected function sendWebhook($end_point, $params)
    {
        $url = "{$this->edd_site}/{$end_point}";
        $response = wp_remote_post(
            $url, 
            array
            (
            'method' => 'POST',
            'timeout' => 45,
            'redirection' => 5,
            'httpversion' => '1.0',
            'blocking' => true,
            'headers' => array(),
            'body' => $params,
            'cookies' => array()
            )
        );

        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
        } else {
            // success
        }
    }
}
$edd_webhook_obj = new EDD_WebHooks();
// edd customer post create action
add_action(
    'edd_customer_post_create', 
    array($edd_webhook_obj, 'sendCustomerCreated', 10, 1)
);
// edd customer post update action
add_action(
    'edd_customer_post_update', 
    array($edd_webhook_obj, 'sendCustomerUpdated'), 10, 2
);
// edd customer post delete action
add_action(
    'edd_pre_delete_customer', 
    array($edd_webhook_obj, 'sendCustomerDeleted'), 10, 1
);
// new user add
add_action('user_register', array($edd_webhook_obj, 'sendUserCreated'), 10, 1);
// user profile update
add_action('profile_update', array($edd_webhook_obj, 'sendUserUpdated'), 10, 1);
// user delete
add_action('delete_user', array($edd_webhook_obj, 'sendUserDeleted'));
// post type created
add_action('wp_insert_post', array($edd_webhook_obj, 'sendPostCreated'), 10, 1);
// post type updated
add_action('post_updated', array($edd_webhook_obj, 'sendPostUpdated'), 10, 2);

// post type delete
add_action('before_delete_post', array($edd_webhook_obj, 'sendPostDeleted'));

// delete hook for discount
add_action( 'edd_delete_discount', array($edd_webhook_obj, 'eddDeleteDiscount') );
// delete hook for payment
add_action( 'edd_payment_deleted', array($edd_webhook_obj, 'eddPaymentDelete'), 100 );

// edd logs add
// add_action('edd_post_insert_log', array($edd_webhook_obj, 'sendEddlogDeleted'));

// taxonmy assigned
add_action('set_object_terms', array($edd_webhook_obj, 'sendTermAssigned'), 10, 1);

// term created
add_action("create_term",  array($edd_webhook_obj, 'sendCreateTerm'), 10, 3);
// term edit
add_action("edit_term", array($edd_webhook_obj, 'sendEditTerm'), 10, 3);
// term deleted
add_action('delete_term', array($edd_webhook_obj, 'sendDeleteTerm'), 10, 4);


?>