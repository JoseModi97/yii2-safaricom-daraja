<?php

namespace Safaricom\Daraja;

class EndpointCatalog
{
    const OAUTH_TOKEN = 'oauth.token';
    const RATIBA_CREATE_PAYBILL = 'ratiba.create_paybill';
    const RATIBA_CREATE_BUY_GOODS = 'ratiba.create_buy_goods';
    const B2B_PAYMENT = 'b2b.payment_request';
    const B2C_PAYMENT = 'b2c.payment_request';
    const B2POCHI_PAYMENT = 'b2pochi.payment_request';
    const C2B_REGISTER_URL = 'c2b.register_url';
    const C2B_SIMULATE = 'c2b.simulate';
    const STK_PUSH = 'stk.push';
    const STK_QUERY = 'stk.query';
    const REVERSAL = 'reversal.request';
    const TRANSACTION_STATUS = 'transaction_status.query';
    const ACCOUNT_BALANCE = 'account_balance.query';
    const LIPA_NA_BONGA_REDEEM_PAYBILL = 'lipa_na_bonga.redeem_paybill';
    const LIPA_NA_BONGA_CALCULATE_POINTS = 'lipa_na_bonga.calculate_points';
    const IMSI_CHECK_ATI = 'imsi.check_ati';
    const SWAP_CHECK_ATI = 'swap.check_ati';
    const PULL_REGISTER = 'pull_transactions.register';
    const PULL_QUERY = 'pull_transactions.query';
    const IOT_SEARCH_MESSAGES = 'iot.search_messages';
    const IOT_FILTER_MESSAGES = 'iot.filter_messages';
    const IOT_DELETE_MESSAGE_THREAD = 'iot.delete_message_thread';
    const IOT_GET_ALL_MESSAGES = 'iot.get_all_messages';
    const IOT_SEND_SINGLE_MESSAGE = 'iot.send_single_message';
    const IOT_DELETE_MESSAGE = 'iot.delete_message';
    const IOT_ALL_SIMS = 'iot.all_sims';
    const IOT_QUERY_LIFECYCLE_STATUS = 'iot.query_lifecycle_status';
    const IOT_QUERY_CUSTOMER_INFO = 'iot.query_customer_info';
    const IOT_SIM_ACTIVATION = 'iot.sim_activation';
    const IOT_GET_ACTIVATION_TRENDS = 'iot.get_activation_trends';
    const IOT_RENAME_ASSET = 'iot.rename_asset';
    const IOT_GET_LOCATION_INFO = 'iot.get_location_info';
    const IOT_SUSPEND_UNSUSPEND_SUB = 'iot.suspend_unsuspend_sub';

    public static function all()
    {
        return array(
            self::OAUTH_TOKEN => array('method' => 'GET', 'path' => '/oauth/v1/generate', 'query' => array('grant_type' => 'client_credentials'), 'auth' => 'basic'),
            self::RATIBA_CREATE_PAYBILL => array('method' => 'POST', 'path' => '/standingorder/v1/createStandingOrderExternal'),
            self::RATIBA_CREATE_BUY_GOODS => array('method' => 'POST', 'path' => '/standingorder/v1/createStandingOrderExternal'),
            self::B2B_PAYMENT => array('method' => 'POST', 'path' => '/mpesa/b2b/v1/paymentrequest'),
            self::B2C_PAYMENT => array('method' => 'POST', 'path' => '/mpesa/b2c/v1/paymentrequest'),
            self::B2POCHI_PAYMENT => array('method' => 'POST', 'path' => '/mpesa/b2c/v1/paymentrequest'),
            self::C2B_REGISTER_URL => array('method' => 'POST', 'path' => '/mpesa/c2b/v1/registerurl'),
            self::C2B_SIMULATE => array('method' => 'POST', 'path' => '/mpesa/c2b/v1/simulate'),
            self::STK_PUSH => array('method' => 'POST', 'path' => '/mpesa/stkpush/v1/processrequest'),
            self::STK_QUERY => array('method' => 'POST', 'path' => '/mpesa/stkpushquery/v1/query'),
            self::REVERSAL => array('method' => 'POST', 'path' => '/mpesa/reversal/v1/request'),
            self::TRANSACTION_STATUS => array('method' => 'POST', 'path' => '/mpesa/transactionstatus/v1/query'),
            self::ACCOUNT_BALANCE => array('method' => 'POST', 'path' => '/mpesa/accountbalance/v1/query'),
            self::LIPA_NA_BONGA_REDEEM_PAYBILL => array('method' => 'POST', 'path' => '/v1/lipa/na/bonga/redeem-paybill'),
            self::LIPA_NA_BONGA_CALCULATE_POINTS => array('method' => 'POST', 'path' => '/v1/lipa/na/bonga/calculator-points'),
            self::IMSI_CHECK_ATI => array('method' => 'POST', 'path' => '/imsi/v1/checkATI'),
            self::SWAP_CHECK_ATI => array('method' => 'POST', 'path' => '/imsi/v2/checkATI'),
            self::PULL_REGISTER => array('method' => 'POST', 'path' => '/pulltransactions/v1/register'),
            self::PULL_QUERY => array('method' => 'POST', 'path' => '/pulltransactions/v1/query'),
            self::IOT_SEARCH_MESSAGES => array('method' => 'POST', 'path' => '/simportal/v1/searchmessages', 'query' => array('pageNo' => 1, 'pageSize' => 5)),
            self::IOT_FILTER_MESSAGES => array('method' => 'POST', 'path' => '/simportal/v1/filtermessages', 'query' => array('pageNo' => 1, 'pageSize' => 10)),
            self::IOT_DELETE_MESSAGE_THREAD => array('method' => 'POST', 'path' => '/simportal/v1/deleteMessageThread'),
            self::IOT_GET_ALL_MESSAGES => array('method' => 'POST', 'path' => '/simportal/v1/getallmessages', 'query' => array('pageNo' => 1, 'pageSize' => 10)),
            self::IOT_SEND_SINGLE_MESSAGE => array('method' => 'POST', 'path' => '/simportal/v1/sendsinglemessage'),
            self::IOT_DELETE_MESSAGE => array('method' => 'POST', 'path' => '/simportal/v1/deletemessage'),
            self::IOT_ALL_SIMS => array('method' => 'POST', 'path' => '/simportal/v1/allsims', 'query' => array('pageNo' => 1, 'pageSize' => 10)),
            self::IOT_QUERY_LIFECYCLE_STATUS => array('method' => 'POST', 'path' => '/simportal/v1/queryLifeCycleStatus'),
            self::IOT_QUERY_CUSTOMER_INFO => array('method' => 'POST', 'path' => '/simportal/v1/querycustomerinfo'),
            self::IOT_SIM_ACTIVATION => array('method' => 'POST', 'path' => '/simportal/v1/simactivation'),
            self::IOT_GET_ACTIVATION_TRENDS => array('method' => 'POST', 'path' => '/simportal/v1/getactivationtrends'),
            self::IOT_RENAME_ASSET => array('method' => 'POST', 'path' => '/simportal/v1/renameasset'),
            self::IOT_GET_LOCATION_INFO => array('method' => 'POST', 'path' => '/simportal/v1/getlocationinfo'),
            self::IOT_SUSPEND_UNSUSPEND_SUB => array('method' => 'POST', 'path' => '/simportal/v1/suspend_unsuspend_sub'),
        );
    }

    public static function get($key)
    {
        $endpoints = self::all();
        return isset($endpoints[$key]) ? $endpoints[$key] : null;
    }

    public static function keys()
    {
        return array_keys(self::all());
    }

    public static function has($key)
    {
        return self::get($key) !== null;
    }
}
