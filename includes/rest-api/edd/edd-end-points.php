<?php
/**
 * For adding all edd rest apis end points to setup the edd on app site.
 *
 * @category Edd
 * @package  Edd
 * @author   Rajendra Banker <bankerrajendra@upnrunn.com>
 * @license  GNU
 * @link     NA
 */
/**
 * Register get edd downloads end points
 *
 * @var [type]
 */

require_once dirname(WPDRIFT_WORKER_FILE) . 
'/includes/rest-api/edd/class-wpdrift-get-downloads-endpoint.php';
$recent_edd_downloads_controller = new EDD_GetDownloads_Endpoint();
$recent_edd_downloads_controller->registerRoutes();

/**
* Get last daily downloads
*/
require_once dirname(WPDRIFT_WORKER_FILE) . 
'/includes/rest-api/edd/class-wpdrift-get-daily-downloads-endpoint.php';
$recent_edd_daily_downloads_controller = new EDD_GetDayDownloads_Endpoint();
$recent_edd_daily_downloads_controller->registerRoutes();

/**
* Get last daily discounts
*/
require_once dirname(WPDRIFT_WORKER_FILE) . 
'/includes/rest-api/edd/class-wpdrift-get-daily-discounts-endpoint.php';
$recent_edd_daily_discounts_controller = new EDD_GetDayDiscounts_Endpoint();
$recent_edd_daily_discounts_controller->registerRoutes();

/**
* Get last daily payments
*/
require_once dirname(WPDRIFT_WORKER_FILE) . 
'/includes/rest-api/edd/class-wpdrift-get-daily-payments-endpoint.php';
$recent_edd_daily_payments_controller = new EDD_GetDayPayments_Endpoint();
$recent_edd_daily_payments_controller->registerRoutes();
/**
* Get last daily customers
*/
require_once dirname(WPDRIFT_WORKER_FILE) . 
'/includes/rest-api/edd/class-wpdrift-get-daily-customers-endpoint.php';
$recent_edd_daily_customers_controller = new EDD_GetDayCustomers_Endpoint();
$recent_edd_daily_customers_controller->registerRoutes();

/**
 * Register get edd downloads metas end points
 *
 * @var [type]
 */
require_once dirname(WPDRIFT_WORKER_FILE) . 
'/includes/rest-api/edd/class-wpdrift-get-downloads-metas-endpoint.php';
$recent_edd_downloads_metas_controller = new EDD_GetDownloads_Metas_Endpoint();
$recent_edd_downloads_metas_controller->registerRoutes();

/**
 * Register get edd customers end points
 *
 * @var [type]
 */
require_once dirname(WPDRIFT_WORKER_FILE) . 
'/includes/rest-api/edd/class-wpdrift-get-customers-endpoint.php';
$recent_edd_customers_controller = new EDD_GetCustomers_Endpoint();
$recent_edd_customers_controller->registerRoutes();

/**
 * Register get edd customers metas end points
 *
 * @var [type]
 */
require_once dirname(WPDRIFT_WORKER_FILE) . 
'/includes/rest-api/edd/class-wpdrift-get-customers-metas-endpoint.php';
$recent_edd_customers_metas_controller = new EDD_GetCustomers_Metas_Endpoint();
$recent_edd_customers_metas_controller->registerRoutes();

/**
 * Register get edd payments end points
 *
 * @var [type]
 */
require_once dirname(WPDRIFT_WORKER_FILE) . 
'/includes/rest-api/edd/class-wpdrift-get-payments-endpoint.php';
$recent_edd_payments_controller = new EDD_GetPayments_Endpoint();
$recent_edd_payments_controller->registerRoutes();

/**
 * Register get edd payments metas end points
 *
 * @var [type]
 */
require_once dirname(WPDRIFT_WORKER_FILE) . 
'/includes/rest-api/edd/class-wpdrift-get-payments-metas-endpoint.php';
$recent_edd_payments_metas_controller = new EDD_GetPayments_Metas_Endpoint();
$recent_edd_payments_metas_controller->registerRoutes();

/**
 * Register get edd discounts end points
 *
 * @var [type]
 */
require_once dirname(WPDRIFT_WORKER_FILE) . 
'/includes/rest-api/edd/class-wpdrift-get-discounts-endpoint.php';
$recent_edd_discounts_controller = new EDD_GetDiscounts_Endpoint();
$recent_edd_discounts_controller->registerRoutes();

/**
 * Register get edd discounts metas end points
 *
 * @var [type]
 */
require_once dirname(WPDRIFT_WORKER_FILE) . 
'/includes/rest-api/edd/class-wpdrift-get-discounts-metas-endpoint.php';
$recent_edd_discounts_metas_controller = new EDD_GetDiscounts_Metas_Endpoint();
$recent_edd_discounts_metas_controller->registerRoutes();

/**
 * Register get edd logs end points
 *
 * @var [type]
 */
require_once dirname(WPDRIFT_WORKER_FILE) . 
'/includes/rest-api/edd/class-wpdrift-get-edd-logs-endpoint.php';
$recent_edd_logs_controller = new EDD_GetLogs_Endpoint();
$recent_edd_logs_controller->registerRoutes();

/**
 * Register get edd logs metas end points
 *
 * @var [type]
 */
require_once dirname(WPDRIFT_WORKER_FILE) . 
'/includes/rest-api/edd/class-wpdrift-get-edd-logs-metas-endpoint.php';
$recent_edd_logs_metas_controller = new EDD_GetLogs_Metas_Endpoint();
$recent_edd_logs_metas_controller->registerRoutes();

/**
 * Register get downloads logs metas end points
 *
 * @var [type]
 */
require_once dirname(WPDRIFT_WORKER_FILE) . 
'/includes/rest-api/edd/class-wpdrift-get-downloads-logs-endpoint.php';
$recent_edd_dwnlds_logs_controller = new EDD_GetDownloads_Logs_Endpoint();
$recent_edd_dwnlds_logs_controller->registerRoutes();

/**
 * Register get term taxonomy end points
 *
 * @var [type]
 */
require_once dirname(WPDRIFT_WORKER_FILE) . 
'/includes/rest-api/edd/class-wpdrift-get-term-taxonomy-endpoint.php';
$recent_edd_term_taxonomy_controller = new EDD_GetTerm_Taxonomy_Endpoint();
$recent_edd_term_taxonomy_controller->registerRoutes();

/**
 * Register get term assigned end points
 *
 * @var [type]
 */
require_once dirname(WPDRIFT_WORKER_FILE) . 
'/includes/rest-api/edd/class-wpdrift-get-term-assigned-endpoint.php';
$recent_edd_term_assigned_controller = new EDD_GetTerm_Assigned_Endpoint();
$recent_edd_term_assigned_controller->registerRoutes();

/**
 * Get Users end points
 *
 * @var [type]
 */
require_once dirname(WPDRIFT_WORKER_FILE) . 
'/includes/rest-api/edd/class-wpdrift-users-endpoint.php';
$recent_edd_users_controller = new EDD_Users_Endpoint();
$recent_edd_users_controller->registerRoutes();

/**
 * Get Users Metas end points
 *
 * @var [type]
 */
require_once dirname(WPDRIFT_WORKER_FILE) . 
'/includes/rest-api/edd/class-wpdrift-users-metas-endpoint.php';
$recent_edd_users_metas_controller = new EDD_GetUsers_Metas_Endpoint();
$recent_edd_users_metas_controller->registerRoutes();

?>