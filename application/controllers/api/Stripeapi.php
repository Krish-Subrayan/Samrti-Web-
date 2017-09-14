<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * class for perfoming all Paypal ipn related functions
 *
 * @author   Nextloop.net
 * @access   public
 * @see      http://www.nextloop.net
 */
class Stripeapi extends MY_Controller
{

    /**
     * constructor method
     */
    public function __construct()
    {

        parent::__construct();

        //profiling::
        $this->data['controller_profiling'][] = __function__;

        //reduce error reporting to only critical
        @error_reporting(E_ALL);

        //turn off codeigniter profiler (which may be on in MY_Controller) during debug mode
        $this->output->enable_profiler(false);

        //template file
        $this->data['template_file'] = PATHS_CLIENT_THEME . '/pay.html';

        //css settings
        $this->data['vars']['css_menu_invoices'] = 'open'; //menu

        //include stripe api
        include_once (FCPATH . 'application/libraries/stripe/init.php');

    }

    /**
     * process paypal IPN calls
     *
     */
    function index()
    {

        //flow control
        $next = true;

        /*---------------------------------------------------------------------
        * get gateway settings
        *---------------------------------------------------------------------*/
        if ($next) {
            //get payment method settings
            $this->data['reg_fields'][] = 'gateway';
            $this->data['fields']['gateway'] = $this->settings_stripe_model->getSettings();
            
        }

        /*---------------------------------------------------------------------
        * some basic data validation
        *---------------------------------------------------------------------*/
        if ($next) {

            //get form data
            $unique_invoice_id = $this->input->post('charge_invoice_unique_id');
            $charge_currency = strtolower($this->input->post('charge_currency'));
            $charge_amount = $this->input->post('charge_amount');
            $stripe_token = $this->input->post('stripeToken');

            //do we have stripe token
            if ($next && $stripe_token == '') {
                $error_message = $this->data['lang']['lang_request_could_not_be_completed_not_charged'];
                $log_message = "stripe charge failed: no valid payment token ($stripe_token) - (invoice id - $unique_invoice_id)";
                $this->data['debug'][] = $log_message;
                $next = false;
            }

            //do we have valid charge amount
            if ($next && !is_numeric($charge_amount) || $charge_amount == 0) {
                $error_message = $this->data['lang']['lang_invalid_amount'];
                $log_message = "stripe charge failed: invalid or missing charge amount ($charge_amount) - (invoice id - $unique_invoice_id)";
                $this->data['debug'][] = $log_message;
                $next = false;
            }

            //do we have valid currency
            if ($next && $charge_currency == '') {
                $error_message = $this->data['lang']['lang_request_could_not_be_completed_not_charged'];
                $log_message = "stripe charge failed: invalid or missing currency ($charge_currency) - invoice id ($unique_invoice_id)";
                $this->data['debug'][] = $log_message;
                $next = false;
            }

            //get invoice id
            if ($next && (!$invoice_id = $this->invoices_model->getInvoiceID($unique_invoice_id))) {
                $error_message = $this->data['lang']['lang_request_could_not_be_completed_not_charged'];
                $log_message = "stripe charge failed: unable to retrieve invoice details (invoice id - $unique_invoice_id)";
                $this->data['debug'][] = $log_message;
                $next = false;
            }
            

            //get actual invoice details
            if ($next && (!$invoice = $this->invoices_model->getInvoice($invoice_id))) {
                $error_message = $this->data['lang']['lang_request_could_not_be_completed_not_charged'];
                $log_message = "stripe charge failed: unable to retrieve invoice details (invoice id - $unique_invoice_id)";
                $this->data['debug'][] = $log_message;
                $next = false;
            }
            

            //do we have stripe api keys
            if ($next) {
                //get and set the keys (live or testing mode)
                if ($this->config->item('payment_gateway_mode') == 1) {
                    $sk_key = $this->data['fields']['gateway']['stripe_live_secret_key'];
                    $pk_key = $this->data['fields']['gateway']['stripe_live_publishable_key'];
                } else {
                    $sk_key = $this->data['fields']['gateway']['stripe_test_secret_key'];
                    $pk_key = $this->data['fields']['gateway']['stripe_test_publishable_key'];
                }

                //validate
                if ($sk_key == '' || $pk_key == '') {
                    $error_message = $this->data['lang']['lang_request_could_not_be_completed_not_charged'];
                    $log_message = "stripe charge failed: invalid or missing stripe api keys (sk_key:$sk_key, pk_key:$pk_key) (invoice id - $unique_invoice_id)";
                    $this->data['debug'][] = $log_message;
                    $next = false;
                }
            }

            //show errors
            if (!$next) {
                //error message
                $this->session->set_flashdata('notice-error-html', $error_message);
                //log the error
                log_message('error', '[FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: $log_message]");
                //redirect
                redirect("/client/pay/$unique_invoice_id");
            }
        }

        /*---------------------------------------------------------------------
        * charge the users card
        *---------------------------------------------------------------------*/
        if ($next) {

            //assume failure
            $next = false;

            try {
                //connect to strupe
                $stripe = array('secret_key' => $sk_key, 'publishable_key' => $pk_key);
                \Stripe\Stripe::setApiKey($stripe['secret_key']);

                $charge = \Stripe\Charge::create(array(
                    'source' => $stripe_token,
                    'amount' => $charge_amount,
                    'currency' => $charge_currency));
            }

            catch (exception $e) {
                //stripe error
                $stripe_error = $e->getMessage();
                //default error message
                $error_message = $this->data['lang']['lang_request_could_not_be_completed_not_charged'];
                //log actual error message and dont disply it for security reasons
                log_message('error', '[FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: Stripe Error: $stripe_error (amount: $charge_amount, currency: $charge_currency, token: $stripe_token)]");
            }

            // Network problem
            catch (\Stripe\Error\ApiConnection $e) {
                $error_message = $this->data['lang']['lang_request_could_not_be_completed_not_charged'];
                //log this
                log_message('error', '[FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: Stripe unable to connect to api (amount: $charge_amount, currency: $charge_currency, token: $stripe_token)]");
            }

            // something wrong with our request
            catch (\Stripe\Error\InvalidRequest $e) {
                $error_message = $this->data['lang']['lang_request_could_not_be_completed_not_charged'];
                //log this
                log_message('error', '[FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: Stripe process failed, check code (amount: $charge_amount, currency: $charge_currency, token: $stripe_token)]");
            }

            // Stripe's servers are down!
            catch (\Stripe\Error\Api $e) {
                $error_message = $this->data['lang']['lang_request_could_not_be_completed_not_charged'];
                //log this
                log_message('error', '[FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: Stripe api server inreachable (amount: $charge_amount, currency: $charge_currency, token: $stripe_token)]");

            }

            // Card was declined - get error from stripe json - if none, use generic
            catch (\Stripe\Error\Card $e) {
                $e_json = $e->getJsonBody();
                $error = $e_json['error'];
                $error_message = ($error['message'] != '') ? $error['message'] : $this->data['lang']['lang_card_was_declined'];
            }

            //did the payment go through ok
            if ($charge->paid == true) {
                //success
                $next = true;
                //hide form
                $this->data['visible']['wi_gateway_stripe'] = 0;
                //success message
                $this->notifications('wi_notification', $this->data['lang']['lang_thank_you_for_payment']);

            } else {
                //there was an unknown error
                if ($error_message == '') {
                    $error_message = $this->data['lang']['lang_connection_error_not_charged'];
                    //log this
                    log_message('error', '[FILE: ' . __file__ . ']  [FUNCTION: ' . __function__ . ']  [LINE: ' . __line__ . "]  [MESSAGE: Stripe unknown processing error (amount: $charge_amount, currency: $charge_currency, token: $stripe_token)]");
                }
                //error message
                $this->session->set_flashdata('notice-error-html', $error_message);
                //redirect
                redirect("/client/pay/$unique_invoice_id");
            }

        }

        /* -----------------------------------------------------------------------------
        *  Update our database
        * ----------------------------------------------------------------------------*/
        if ($next) {

            //payment details array
            $payment['payments_invoice_id'] = $invoice['invoices_id'];
            $payment['payments_invoices_custom_id'] = $invoice['invoices_custom_id'];
            $payment['payments_project_id'] = $invoice['invoices_project_id'];
            $payment['payments_client_id'] = $invoice['invoices_clients_id'];
            $payment['payments_transaction_id'] = $charge->id;
            $payment['payments_amount'] = $charge_amount / 100; //convert from cents to dollars etc
            $payment['payments_currency_code'] = $charge->currency;
            $payment['payments_notes'] = '';
            $payment['payments_transaction_status'] = 'completed';
            $payment['payments_by_user_id'] = $this->data['vars']['my_id'];
            $payment['payments_method'] = 'stripe';
            //make globally available
            $this->data['vars']['payment'] = $payment;

            //check if payment has not already been recorded
            $paid = $this->payments_model->getByTransactionID($payment['payments_transaction_id']);
            

            //insert record
            if (!$paid) {
                $this->payments_model->addPayment($payment);
                

                //flow - send email
                $next_email = true;
            }

            //update record
            if ($paid) {
                $this->payments_model->updatePaymentStatus($payment['payments_transaction_id'], $payment['payments_transaction_status']);
                
            }
        }

        /* -----------------------------------------------------------------------------
        *  Send out emails & track event & update invoice with new payment (refresh)
        * ----------------------------------------------------------------------------*/
        if ($next && $next_email) {

            //---email admins--------------------------
            $email_vars['clients_company_name'] = $invoice['clients_company_name'];
            $email_vars['invoice_id'] = $payment['payments_invoice_id'];
            $email_vars['transaction_id'] = $payment['payments_transaction_id'];
            $email_vars['amount'] = $payment['payments_amount'];
            $email_vars['currency'] = $payment['payments_currency_code'];
            $this->data['vars']['emailvars'] = $email_vars; //debug
            $this->__emailer('new_payment', $email_vars);

            //---track event---------------------------
            $this->data['vars']['eventvars'] = $email_vars; //debug
            $this->__eventsTracker('invoice-payment', $event_vars);

            //---refresh invoice-----------------------
            $this->refresh->refreshSingleInvoice($payment['payments_invoice_id']);

            //redirect back to invoices
            $this->session->set_flashdata('notice-success-html', $this->data['lang']['lang_thank_you_for_payment']);
            redirect('/client/invoices');
        }

        //load view
        $this->__flmView('client/main');

    }

    /**
     * send out an email
     *
     * @param string $email email address
     */
    function __emailer($email = '', $vars = array())
    {

        //specific passed variables
        foreach ($vars as $key => $value) {
            $this->data['email_vars'][$key] = $value;
        }

        //-------------send out email-------------------------------
        if ($email == 'new_payment') {

            //get message template from database
            $template = $this->settings_emailtemplates_model->getEmailTemplate('new_payment_admin');
            

            //parse email
            $email_message = parse_email_template($template['message'], $this->data['email_vars']);
            //send email to multiple admins
            foreach ($this->data['vars']['mailinglist_admins'] as $email_address) {
                email_default_settings(); //defaults (from emailer helper)
                $this->email->to($email_address);
                $this->email->subject($this->data['lang']['lang_new_payment']);
                $this->email->message($email_message);
                $this->email->send();
                //log this
                $this->__emailLog($email_address, $template['subject'], $email_message);
            }
        }

    }

    /**
     * records new project events (timeline)
     *
     * @param	string   $type identify the loop to run in this function
     * @param   array    $vents_data an optional array that can be used to directly pass data]      
     */
    function __eventsTracker($type = '', $events_data = array())
    {
        //profiling
        $this->data['controller_profiling'][] = __function__;

        //--------------get any passed data-----------------------
        foreach ($event_data as $key => $value) {
            $$key = $value;
        }

        //flow control
        $next = true;

        //--------------record a new event-----------------------
        if ($type == 'invoice-payment') {

            //access payment data
            $payment = $this->data['vars']['payment'];

            //build data array
            $events = array();

            //invoice id
            $invoice_id = ($payment['payments_invoices_custom_id'] != '') ? $payment['payments_invoices_custom_id'] : $payment['payments_invoice_id'];

            $events['project_events_project_id'] = $payment['payments_project_id'];
            $events['project_events_type'] = 'payment';
            $events['project_events_details'] = $invoice_id;
            $events['project_events_action'] = 'lang_tl_paid_invoice';
            $events['project_events_target_id'] = $payment['payments_invoice_id'];
            $events['project_events_user_id'] = $payment['payments_by_user_id'];
            $events['project_events_user_type'] = 'client';
            $events['project_events_link'] = 'invoice_' . $payment['payments_invoice_id'] . '_' . $payment['payments_project_id'];

            //add data to database
            $this->project_events_model->addEvent($events);
            
        }
    }

    /**
     * loads the view
     *
     * @param string $view the view to load
     */
    function __flmView($view = '')
    {

        //profiling
        $this->data['controller_profiling'][] = __function__;

        //template::
        $this->data['template_file'] = help_verify_template($this->data['template_file']);

        //complete the view
        $this->__commonAll_View($view);
    }
}
