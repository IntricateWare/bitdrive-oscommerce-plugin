<?php

/*
 * Copyright (c) 2015 IntricateWare Inc.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

class bitdrive_standard {
    /**
     * The BitDrive Standard Checkout URL.
     * @type string
     */
    private $_checkout_url = 'https://www.bitdrive.io/pay';
    
    /**
     * THe payment module configuration key prefix.
     * @type string
     */
    private $_prefix = 'MODULE_PAYMENT_BITDRIVE_STANDARD_';
    
    /**
     * The payment module code.
     * @type string
     */
    public $code;
    
    /**
     * The payment module description.
     * @type string
     */
    public $description;
    
    /**
     * Flag which indicates whether or not the payment module is enabled.
     * @type boolean
     */
    public $enabled;
    
    /**
     * The payment module title.
     * @type string
     */
    public $title;
    
        
    /**
     * The payment module constructor for initialisation.
     */
    public function __construct() {
        $this->code = 'bitdrive_standard';
        $this->signature = 'bitdrive|bitdrive_standard|1.015.0418|2.3';
        
        $this->title = MODULE_PAYMENT_BITDRIVE_STANDARD_TEXT_TITLE;
        $this->public_title = MODULE_PAYMENT_BITDRIVE_STANDARD_TEXT_PUBLIC_TITLE;
        $this->description = MODULE_PAYMENT_BITDRIVE_STANDARD_TEXT_DESCRIPTION;
        
        $this->enabled = (defined($this->_prefix . 'STATUS') && (MODULE_PAYMENT_BITDRIVE_STANDARD_STATUS == 'True'))
            ? true : false;
        $this->order_status =
            (defined($this->_prefix. 'STATUS_ID') && ((int)MODULE_PAYMENT_BITDRIVE_STANDARD_ORDER_STATUS_ID > 0))
            ? (int)MODULE_PAYMENT_BITDRIVE_STANDARD_ORDER_STATUS_ID : 0;
        $this->sort_order = defined($this->_prefix . 'SORT_ORDER') ? MODULE_PAYMENT_BITDRIVE_STANDARD_SORT_ORDER : 0;
        
        $this->form_action_url = $this->_checkout_url;
    }
    
    /**
     * Output the payment method title and text for checkout.
     *
     * @return array
     */
    public function selection() {
        return array(
            'id'        => $this->code,
            'module'    => $this->public_title
        );
    }
    
    /**
     * Update status.
     */
    public function update_status() {
        return;
    }
    
    /**
     * Javascript validation.
     */
    public function javascript_validation() {
        return;
    }
    
    /**
     * Pre-confirmation check.
     */
    public function pre_confirmation_check() {
        global $cartID, $cart, $order;

        if (empty($cart->cartID)) {
            $cartID = $cart->cartID = $cart->generate_cart_id();
        }

        if (!tep_session_is_registered('cartID')) {
            tep_session_register('cartID');
        }

        $order->info['payment_method_raw'] = $order->info['payment_method'];
        $order->info['payment_method'] = 'BitDrive Standard Checkout';
    }
    
    /**
     * Order confirmation.
     */
    public function confirmation() {
        global $cartID, $cart_BitDrive_Standard_ID, $customer_id, $languages_id, $order, $order_total_modules;
        
        if (tep_session_is_registered('cartID')) {
            $insert_order = false;

            if (tep_session_is_registered('cart_BitDrive_Standard_ID')) {
                $order_id = substr($cart_BitDrive_Standard_ID, strpos($cart_BitDrive_Standard_ID, '-') + 1);
                $sql = sprintf("SELECT currency FROM %s WHERE orders_id = %d", TABLE_ORDERS, (int)$order_id);
                $curr_check = tep_db_query($sql);
                $curr = tep_db_fetch_array($curr_check);

                if ( ($curr['currency'] != $order->info['currency'])
                    || ($cartID != substr($cart_BitDrive_Standard_ID, 0, strlen($cartID))) ) {
                    
                    $sql = sprintf("SELECT orders_id FROM %s WHERE orders_id = %d LIMIT 1",
                                   TABLE_ORDERS_STATUS_HISTORY, (int)$order_id);
                    $check_query = tep_db_query($sql);

                    $del_tables = array(
                        TABLE_ORDERS, TABLE_ORDERS_TOTAL, TABLE_ORDERS_STATUS_HISTORY, TABLE_ORDERS_PRODUCTS,
                        TABLE_ORDERS_PRODUCTS_ATTRIBUTES, TABLE_ORDERS_PRODUCTS_DOWNLOAD
                    );
                    if (tep_db_num_rows($check_query) < 1) {
                        foreach ($del_tables as $table) {
                            $sql = sprintf("DELETE FROM %s WHERE orders_id = %d", $table, (int)$order_id);
                            tep_db_query($sql);
                        }
                    }

                    $insert_order = true;
                }
            } else {
                $insert_order = true;
            }

            if ($insert_order == true) {
                $order_totals = array();
                
                if (is_array($order_total_modules->modules)) {
                    foreach ($order_total_modules->modules as $value) {
                        $class = substr($value, 0, strrpos($value, '.'));
                        if ($GLOBALS[$class]->enabled) {
                            for ($i = 0, $n = sizeof($GLOBALS[$class]->output); $i < $n; $i++) {
                                if (tep_not_null($GLOBALS[$class]->output[$i]['title'])
                                    && tep_not_null($GLOBALS[$class]->output[$i]['text'])) {
                                        $order_totals[] = array(
                                            'code'          => $GLOBALS[$class]->code,
                                            'title'         => $GLOBALS[$class]->output[$i]['title'],
                                            'text'          => $GLOBALS[$class]->output[$i]['text'],
                                            'value'         => $GLOBALS[$class]->output[$i]['value'],
                                            'sort_order'    => $GLOBALS[$class]->sort_order
                                        );
                                }
                            }
                        }
                    }
                }

                if ( isset($order->info['payment_method_raw']) ) {
                    $order->info['payment_method'] = $order->info['payment_method_raw'];
                    unset($order->info['payment_method_raw']);
                }

                $data = array(
                    'customers_id' => $customer_id,
                    'customers_name' => $order->customer['firstname'] . ' ' . $order->customer['lastname'],
                    'customers_company' => $order->customer['company'],
                    'customers_street_address' => $order->customer['street_address'],
                    'customers_suburb' => $order->customer['suburb'],
                    'customers_city' => $order->customer['city'],
                    'customers_postcode' => $order->customer['postcode'],
                    'customers_state' => $order->customer['state'],
                    'customers_country' => $order->customer['country']['title'],
                    'customers_telephone' => $order->customer['telephone'],
                    'customers_email_address' => $order->customer['email_address'],
                    'customers_address_format_id' => $order->customer['format_id'],
                    'delivery_name' => $order->delivery['firstname'] . ' ' . $order->delivery['lastname'],
                    'delivery_company' => $order->delivery['company'],
                    'delivery_street_address' => $order->delivery['street_address'],
                    'delivery_suburb' => $order->delivery['suburb'],
                    'delivery_city' => $order->delivery['city'],
                    'delivery_postcode' => $order->delivery['postcode'],
                    'delivery_state' => $order->delivery['state'],
                    'delivery_country' => $order->delivery['country']['title'],
                    'delivery_address_format_id' => $order->delivery['format_id'],
                    'billing_name' => $order->billing['firstname'] . ' ' . $order->billing['lastname'],
                    'billing_company' => $order->billing['company'],
                    'billing_street_address' => $order->billing['street_address'],
                    'billing_suburb' => $order->billing['suburb'],
                    'billing_city' => $order->billing['city'],
                    'billing_postcode' => $order->billing['postcode'],
                    'billing_state' => $order->billing['state'],
                    'billing_country' => $order->billing['country']['title'],
                    'billing_address_format_id' => $order->billing['format_id'],
                    'payment_method' => $order->info['payment_method'],
                    'date_purchased' => 'now()',
                    'orders_status' => $order->info['order_status'],
                    'currency' => $order->info['currency'],
                    'currency_value' => $order->info['currency_value']
                );

                tep_db_perform(TABLE_ORDERS, $data);
                $insert_id = tep_db_insert_id();

                for ($i = 0, $n = sizeof($order_totals); $i < $n; $i++) {
                    $data = array(
                        'orders_id'     => $insert_id,
                        'title'         => $order_totals[$i]['title'],
                        'text'          => $order_totals[$i]['text'],
                        'value'         => $order_totals[$i]['value'],
                        'class'         => $order_totals[$i]['code'],
                        'sort_order'    => $order_totals[$i]['sort_order']
                    );

                    tep_db_perform(TABLE_ORDERS_TOTAL, $data);
                }

                for ($i = 0, $n = sizeof($order->products); $i < $n; $i++) {
                    $data = array(
                        'orders_id' => $insert_id,
                        'products_id' => tep_get_prid($order->products[$i]['id']),
                        'products_model' => $order->products[$i]['model'],
                        'products_name' => $order->products[$i]['name'],
                        'products_price' => $order->products[$i]['price'],
                        'final_price' => $order->products[$i]['final_price'],
                        'products_tax' => $order->products[$i]['tax'],
                        'products_quantity' => $order->products[$i]['qty']
                    );
    
                    tep_db_perform(TABLE_ORDERS_PRODUCTS, $data);
                    $order_products_id = tep_db_insert_id();
    
                    $attributes_exist = '0';
                        
                    if (isset($order->products[$i]['attributes'])) {
                        $attributes_exist = '1';
                        for ($j = 0, $n2 = sizeof($order->products[$i]['attributes']); $j < $n2; $j++) {
                            if (DOWNLOAD_ENABLED == 'true') {
                                $sql = sprintf(  "SELECT popt.products_options_name "
                                               . "       , poval.products_options_values_name "
                                               . "       , pa.options_values_price "
                                               . "       , pa.price_prefix "
                                               . "       , pad.products_attributes_maxdays "
                                               . "       , pad.products_attributes_maxcount "
                                               . "       , pad.products_attributes_filename "
                                               . "FROM %s popt, %s poval, %s pa "
                                               . "LEFT JOIN %s pad "
                                               . "       ON pa.products_attributes_id=pad.products_attributes_id "
                                               . "WHERE pa.products_id = '%s' "
                                               . "  AND pa.options_id = '%s' "
                                               . "  AND pa.options_id = popt.products_options_id "
                                               . "  AND pa.options_values_id = '%s' "
                                               . "  AND pa.options_values_id = poval.products_options_values_id "
                                               . "  AND popt.language_id = '%s' "
                                               . "  AND poval.language_id = '%s'",
                                               TABLE_PRODUCTS_OPTIONS,
                                               TABLE_PRODUCTS_OPTIONS_VALUES,
                                               TABLE_PRODUCTS_ATTRIBUTES,
                                               TABLE_PRODUCTS_ATTRIBUTES_DOWNLOAD,
                                               $order->products[$i]['id'],
                                               $order->products[$i]['attributes'][$j]['option_id'],
                                               $order->products[$i]['attributes'][$j]['value_id'],
                                               $languages_id,
                                               $languages_id);
                                $attributes = tep_db_query($sql);
                            } else {
                                $sql = sprintf(  "SELECT popt.products_options_name "
                                               . "     , poval.products_options_values_name"
                                               . "     , pa.options_values_price"
                                               . "     , pa.price_prefix "
                                               . "FROM %s popt, %s poval, %s pa "
                                               . "WHERE pa.products_id = '%s' "
                                               . "  AND pa.options_id = '%s' "
                                               . "  AND pa.options_id = popt.products_options_id "
                                               . "  AND pa.options_values_id = '%s' "
                                               . "  AND pa.options_values_id = poval.products_options_values_id "
                                               . "  AND popt.language_id = '%s' "
                                               . "  AND poval.language_id = '%s'",
                                               TABLE_PRODUCTS_OPTIONS,
                                               TABLE_PRODUCTS_OPTIONS_VALUES,
                                               TABLE_PRODUCTS_ATTRIBUTES,
                                               $order->products[$i]['id'],
                                               $order->products[$i]['attributes'][$j]['option_id'],
                                               $order->products[$i]['attributes'][$j]['value_id'],
                                               $languages_id,
                                               $languages_id);
                                
                                $attributes = tep_db_query($sql);
                            }
                            
                            $attributes_values = tep_db_fetch_array($attributes);
                            $data = array(
                                'orders_id'                 => $insert_id,
                                'orders_products_id'        => $order_products_id,
                                'products_options'          => $attributes_values['products_options_name'],
                                'products_options_values'   => $attributes_values['products_options_values_name'],
                                'options_values_price'      => $attributes_values['options_values_price'],
                                'price_prefix'              => $attributes_values['price_prefix']
                            );
    
                            tep_db_perform(TABLE_ORDERS_PRODUCTS_ATTRIBUTES, $data);
    
                            if ((DOWNLOAD_ENABLED == 'true') &&
                                isset($attributes_values['products_attributes_filename']) &&
                                tep_not_null($attributes_values['products_attributes_filename'])) {
                                    $data = array(
                                        'orders_id'                 => $insert_id,
                                        'orders_products_id'        => $order_products_id,
                                        'orders_products_filename'  => $attributes_values['products_attributes_filename'],
                                        'download_maxdays'          => $attributes_values['products_attributes_maxdays'],
                                        'download_count'            => $attributes_values['products_attributes_maxcount']
                                    );
    
                                tep_db_perform(TABLE_ORDERS_PRODUCTS_DOWNLOAD, $data);
                            }
                        }
                    }
                }

                $cart_BitDrive_Standard_ID = $cartID . '-' . $insert_id;
                tep_session_register('cart_BitDrive_Standard_ID');
            }
        }
        
        return false;
    }
    
    /**
     * Build the form fields for BitDrive Standard Checkout.
     * 
     * @return string
     */
    public function process_button() {
        global $order, $currency, $cart_BitDrive_Standard_ID;
        
        $order_id = substr($cart_BitDrive_Standard_ID, strpos($cart_BitDrive_Standard_ID, '-') + 1);
        
        $process_button_string = '';
        
        $data = array(
            'bd-cmd'            => 'pay',
            'bd-merchant'       => MODULE_PAYMENT_BITDRIVE_STANDARD_MERCHANT_ID,
            'bd-currency'       => $currency,
            'bd-amount'         => $this->format_raw($order->info['total']),
            'bd-memo'           => sprintf('Payment for order #%s', $order_id),
            'bd-invoice'        => $order_id,
            'bd-success-url'    => tep_href_link(FILENAME_CHECKOUT_PROCESS, '', 'SSL'),
            'bd-error-url'      => tep_href_link(FILENAME_CHECKOUT_PAYMENT, '', 'SSL')
        );
        
        foreach ($data as $name => $value) {
            $process_button_string .= tep_draw_hidden_field($name, $value);
        }
        
        return $process_button_string;
    }
    
    /**
     * Before order processing after checkout is complete.
     */
    public function before_process() {
        global $cart;
        
        $this->after_process();

        $cart->reset(true);

        tep_session_unregister('sendto');
        tep_session_unregister('billto');
        tep_session_unregister('shipping');
        tep_session_unregister('payment');
        tep_session_unregister('comments');
        tep_session_unregister('cart_BitDrive_Standard_ID');

        tep_redirect(tep_href_link(FILENAME_CHECKOUT_SUCCESS, '', 'SSL'));
    }
    
    /**
     * After order processing after checkout is complete.
     */
    public function after_process() {
        return;
    }
    
    /**
     * Get error.
     */
    public function get_error() {
        return;
    }
    
    /**
     * Format the specified number without currency formatting.
     *
     * @param double $number
     * @param string $currency_code
     * @param string $currency_value
     */
    function format_raw($number, $currency_code = '', $currency_value = '') {
        global $currencies, $currency;

        if (empty($currency_code) || !$this->is_set($currency_code)) {
            $currency_code = $currency;
        }

        if (empty($currency_value) || !is_numeric($currency_value)) {
            $currency_value = $currencies->currencies[$currency_code]['value'];
        }

        return number_format(
            tep_round($number * $currency_value, $currencies->currencies[$currency_code]['decimal_places']),
            $currencies->currencies[$currency_code]['decimal_places'], '.', '');
    }
    
    /**
     * Check if the module is enabled.
     *
     * @return boolean
     */
    public function check() {
        if (!isset($this->_check)) {
            $check_query = tep_db_query(sprintf("SELECT configuration_value FROM %s WHERE configuration_key = '%sSTATUS'",
                TABLE_CONFIGURATION, $this->_prefix));
            $this->_check = tep_db_num_rows($check_query);
        }
      
        return $this->_check;
    }
    
    /**
     * Install the payment module.
     *
     * @param string $parameter
     */
    public function install($parameter = null) {
        $params = $this->getParams();
        if (isset($parameter)) {
            if (isset($params[$parameter])) {
                $params = array($parameter => $params[$parameter]);
            } else {
                $params = array();
            }
        }
        
        foreach ($params as $key => $value) {
            $data = array(
                'configuration_title'       => $value['title'],
                'configuration_key'         => $key,
                'configuration_value'       => (isset($value['value']) ? $value['value'] : ''),
                'configuration_description' => $value['desc'],
                'configuration_group_id'    => '6',
                'sort_order'                => '0',
                'date_added'                => 'now()'
            );

            if (isset($value['set_func'])) {
                $data['set_function'] = $value['set_func'];
            }
            
            if (isset($value['use_func'])) {
                $data['use_function'] = $value['use_func'];
            }

            tep_db_perform(TABLE_CONFIGURATION, $data);
        }
    }
    
    /**
     * Uninstall the payment module.
     */
    public function remove() {
        tep_db_query(sprintf(
            "DELETE FROM %s WHERE configuration_key IN ('%s')", TABLE_CONFIGURATION, implode("', '", $this->keys())));
    }
    
    /**
     * Get the configuration keys for the payment module.
     *
     * @return array
     */
    public function keys() {
        $keys = array_keys($this->getParams());

        if ($this->check()) {
            foreach ($keys as $key) {
                if (!defined($key)) {
                    $this->install($key);
                }
            }
        }

        return $keys;
    }
    
    /**
     * Get the configuration parameters for the payment module.
     *
     * @return array
     */
    public function getParams() {
        $params = array(
            
            $this->_prefix . 'STATUS' => array(
                'title'     => 'Enable BitDrive Standard Checkout',
                'desc'      => 'Do you want to accept bitcoin using BitDrive Standard Checkout?',
                'value'     => 'True',
                'set_func'  => 'tep_cfg_select_option(array(\'True\', \'False\'), '
            ),
            
            $this->_prefix . 'MERCHANT_ID' => array(
                'title'     => 'Merchant ID',
                'desc'      => 'Your BitDrive merchant ID'
            ),
            
            $this->_prefix . 'IPN_SECRET' => array(
                'title'     => 'IPN Secret',
                'desc'      => 'The IPN secret for verfiying BitDrive notification messages'
            ),
            
            $this->_prefix . '_ORDER_STATUS_ID' => array(
                'title' => 'Set Payment Order Status',
                'desc' => 'Set the status of orders made with this payment module to this value',
                'value' => '0',
                'set_func' => 'tep_cfg_pull_down_order_statuses(',
                'use_func' => 'tep_get_order_status_name'
            ),
            
            $this->_prefix . '_SORT_ORDER' => array(
                'title' => 'Sort order of display',
                'desc' => 'Sort order of display. Lowest is displayed first.',
                'value' => '0'
            )
        
        );
        
        return $params;
    }
    
    /**
     * Process the BitDrive IPN message.
     *
     * @param string $data
     */
    public function processIpn($data) {
        global $currencies;
        
        // Check the IPN data
        $json = json_decode($data);
        if (!$json) {
            exit;
        }
        
        // Check for the IPN parameters that are required
        $requiredIpnParams = array(
            'notification_type',
            'sale_id',
            'merchant_invoice',
            'amount',
            'bitcoin_amount'
        );
        foreach ($requiredIpnParams as $param) {
            if (!isset($json->$param) || strlen(trim($json->$param)) == 0) {
                exit;
            }
        }
        
        // Verify the SHA 256 hash
        $merchant_id = MODULE_PAYMENT_BITDRIVE_STANDARD_MERCHANT_ID;
        $ipn_secret = MODULE_PAYMENT_BITDRIVE_STANDARD_IPN_SECRET;
        $hash_string = strtoupper(hash('sha256', $json->sale_id . $merchant_id . $json->merchant_invoice . $ipn_secret));
        if ($hash_string != $json->hash) {
            exit;
        }
        
        // Get the order
        $order_id = (int)$json->merchant_invoice;
        $transaction_id = $json->sale_id;
        $sql = sprintf("SELECT orders_id, orders_status, currency, currency_value FROM %s WHERE orders_id = '%s'",
                       TABLE_ORDERS, $order_id);
        $order_query = tep_db_query($sql);

        if (tep_db_num_rows($order_query) == 1) {
            $order = tep_db_fetch_array($order_query);
            $new_order_status = DEFAULT_ORDERS_STATUS_ID;

            switch ($json->notification_type) {
                // Order created
                case 'ORDER_CREATED':
                    $new_order_status = 1; // Pending
                    break;
                
                // Payment completed
                case 'PAYMENT_COMPLETED':
                    $new_order_status = 2; // Processing
                    break;
                
                // Transaction cancelled/expired
                case 'TRANSACTION_CANCELLED':
                case 'TRANSACTION_EXPIRED':
                    $new_order_status = 1; // Pending
                    break;
            }
            
            $comment_status = sprintf('Transaction ID: %s; %s; %s; %s',
                                      $transaction_id,
                                      $json->notification_type,
                                      $currencies->format($json->amount, false, $json->currency),
                                      $json->notification_description);
          
            $sql = sprintf("UPDATE %s SET orders_status = %d, last_modified = NOW() WHERE orders_id = %d",
                           TABLE_ORDERS, $new_order_status, $order_id);
            tep_db_query($sql);

            $history_data = array(
                'orders_id'         => $order_id,
                'orders_status_id'  => $new_order_status,
                'date_added'        => 'now()',
                'customer_notified' => '0',
                'comments'          => sprintf('BitDrive IPN [%s]', $comment_status)
            );
            tep_db_perform(TABLE_ORDERS_STATUS_HISTORY, $history_data);
        }
    }
}

?>