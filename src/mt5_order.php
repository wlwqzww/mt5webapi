<?php

namespace aemaddin\mt5webapi;
//+------------------------------------------------------------------+
//|                                             MetaTrader 5 Web API |
//|                   Copyright 2001-2015, MetaQuotes Software Corp. |
//|                                        http://www.metaquotes.net |
//+------------------------------------------------------------------+

/**
 * Class get order
 */
class MTOrderProtocol
{
    private $m_connect; // connection to MT5 server

    /**
     * @param MTConnect $connect - connect to MT5 server
     */
    public function __construct($connect)
    {
        $this->m_connect = $connect;
    }

    /**
     * Get order
     * @param string $ticket - number of ticket
     * @param MTOrder $order
     * @return MTRetCode
     */
    public function OrderGet($ticket, &$order)
    {
        //--- send request
        $data = array(MTProtocolConsts::WEB_PARAM_TICKET => $ticket);
        if (!$this->m_connect->Send(MTProtocolConsts::WEB_CMD_ORDER_GET, $data)) {
            if (MTLogger::getIsWriteLog()) MTLogger::write(MTLoggerType::ERROR, 'send order get failed');
            return MTRetCode::MT_RET_ERR_NETWORK;
        }
        //--- get answer
        if (($answer = $this->m_connect->Read()) == null) {
            if (MTLogger::getIsWriteLog()) MTLogger::write(MTLoggerType::ERROR, 'answer order get is empty');
            return MTRetCode::MT_RET_ERR_NETWORK;
        }
        //--- parse answer
        if (($error_code = $this->ParseOrder(MTProtocolConsts::WEB_CMD_ORDER_GET, $answer, $order_answer)) != MTRetCode::MT_RET_OK) {
            if (MTLogger::getIsWriteLog()) MTLogger::write(MTLoggerType::ERROR, 'parse order get failed: [' . $error_code . ']' . MTRetCode::GetError($error_code));
            return $error_code;
        }
        //--- get object from json
        $order = $order_answer->GetFromJson();
        //---
        return MTRetCode::MT_RET_OK;
    }

    /**
     * check answer from MetaTrader 5 server
     * @param string $command - command
     * @param string $answer - answer from server
     * @param  MTOrderAnswer $order_answer
     * @return MTRetCode
     */
    private function ParseOrder($command, &$answer, &$order_answer)
    {
        $pos = 0;
        //--- get command answer
        $command_real = $this->m_connect->GetCommand($answer, $pos);
        if ($command_real != $command) return MTRetCode::MT_RET_ERR_DATA;
        //---
        $order_answer = new MTOrderAnswer();
        //--- get param
        $pos_end = -1;
        while (($param = $this->m_connect->GetNextParam($answer, $pos, $pos_end)) != null) {
            switch ($param['name']) {
                case MTProtocolConsts::WEB_PARAM_RETCODE:
                    $order_answer->RetCode = $param['value'];
                    break;
            }
        }
        //--- check ret code
        if (($ret_code = MTConnect::GetRetCode($order_answer->RetCode)) != MTRetCode::MT_RET_OK) return $ret_code;
        //--- get json
        if (($order_answer->ConfigJson = $this->m_connect->GetJson($answer, $pos_end)) == null) return MTRetCode::MT_RET_REPORT_NODATA;
        //---
        return MTRetCode::MT_RET_OK;
    }

    /**
     * check answer from MetaTrader 5 server
     * @param  string $answer - answer from server
     * @param  MTOrderPageAnswer $order_answer
     * @return MTRetCode
     */
    private function ParseOrderPage(&$answer, &$order_answer)
    {
        $pos = 0;
        //--- get command answer
        $command_real = $this->m_connect->GetCommand($answer, $pos);
        if ($command_real != MTProtocolConsts::WEB_CMD_ORDER_GET_PAGE) return MTRetCode::MT_RET_ERR_DATA;
        //---
        $order_answer = new MTOrderPageAnswer();
        //--- get param
        $pos_end = -1;
        while (($param = $this->m_connect->GetNextParam($answer, $pos, $pos_end)) != null) {
            switch ($param['name']) {
                case MTProtocolConsts::WEB_PARAM_RETCODE:
                    $order_answer->RetCode = $param['value'];
                    break;
            }
        }
        //--- check ret code
        if (($ret_code = MTConnect::GetRetCode($order_answer->RetCode)) != MTRetCode::MT_RET_OK) return $ret_code;
        //--- get json
        if (($order_answer->ConfigJson = $this->m_connect->GetJson($answer, $pos_end)) == null) return MTRetCode::MT_RET_REPORT_NODATA;
        //---
        return MTRetCode::MT_RET_OK;
    }

    /**
     * Get total order for login
     * @param string $login - user login
     * @param int $total - count of users orders
     * @return MTRetCode
     */
    public function OrderGetTotal($login, &$total)
    {
        //--- send request
        $data = array(MTProtocolConsts::WEB_PARAM_LOGIN => $login);
        if (!$this->m_connect->Send(MTProtocolConsts::WEB_CMD_ORDER_GET_TOTAL, $data)) {
            if (MTLogger::getIsWriteLog()) MTLogger::write(MTLoggerType::ERROR, 'send order get total failed');
            return MTRetCode::MT_RET_ERR_NETWORK;
        }
        //--- get answer
        if (($answer = $this->m_connect->Read()) == null) {
            if (MTLogger::getIsWriteLog()) MTLogger::write(MTLoggerType::ERROR, 'answer order get total is empty');
            return MTRetCode::MT_RET_ERR_NETWORK;
        }
        //--- parse answer
        if (($error_code = $this->ParseOrderTotal($answer, $order_answer)) != MTRetCode::MT_RET_OK) {
            if (MTLogger::getIsWriteLog()) MTLogger::write(MTLoggerType::ERROR, 'parse order get total failed: [' . $error_code . ']' . MTRetCode::GetError($error_code));
            return $error_code;
        }
        //--- get total
        $total = $order_answer->Total;
        //---
        return MTRetCode::MT_RET_OK;
    }

    /**
     * Get order
     * @param int $login - number of ticket
     * @param int $offset - begin records number
     * @param int $total - total records need
     * @param array(MTOrder) $orders
     * @return MTRetCode
     */
    public function OrderGetPage($login, $offset, $total, &$orders)
    {
        //--- send request
        $data = array(MTProtocolConsts::WEB_PARAM_LOGIN => $login, MTProtocolConsts::WEB_PARAM_OFFSET => $offset, MTProtocolConsts::WEB_PARAM_TOTAL => $total);
        //---
        if (!$this->m_connect->Send(MTProtocolConsts::WEB_CMD_ORDER_GET_PAGE, $data)) {
            if (MTLogger::getIsWriteLog()) MTLogger::write(MTLoggerType::ERROR, 'send order get page failed');
            return MTRetCode::MT_RET_ERR_NETWORK;
        }
        //--- get answer
        if (($answer = $this->m_connect->Read()) == null) {
            if (MTLogger::getIsWriteLog()) MTLogger::write(MTLoggerType::ERROR, 'answer order get page is empty');
            return MTRetCode::MT_RET_ERR_NETWORK;
        }
        //--- parse answer
        if (($error_code = $this->ParseOrderPage($answer, $order_answer)) != MTRetCode::MT_RET_OK) {
            if (MTLogger::getIsWriteLog()) MTLogger::write(MTLoggerType::ERROR, 'parse order get page failed: [' . $error_code . ']' . MTRetCode::GetError($error_code));
            return $error_code;
        }
        //--- get object from json
        $orders = $order_answer->GetArrayFromJson();
        //---
        return MTRetCode::MT_RET_OK;
    }

    /**
     * Check answer from MetaTrader 5 server
     * @param  $answer string server answer
     * @param  $order_answer MTOrderTotalAnswer
     * @return false
     */
    private function ParseOrderTotal(&$answer, &$order_answer)
    {
        $pos = 0;
        //--- get command answer
        $command = $this->m_connect->GetCommand($answer, $pos);
        if ($command != MTProtocolConsts::WEB_CMD_ORDER_GET_TOTAL) return MTRetCode::MT_RET_ERR_DATA;
        //---
        $order_answer = new MTOrderTotalAnswer();
        //--- get param
        $pos_end = -1;
        while (($param = $this->m_connect->GetNextParam($answer, $pos, $pos_end)) != null) {
            switch ($param['name']) {
                case MTProtocolConsts::WEB_PARAM_RETCODE:
                    $order_answer->RetCode = $param['value'];
                    break;
                case MTProtocolConsts::WEB_PARAM_TOTAL:
                    $order_answer->Total = (int)$param['value'];
                    break;
            }
        }
        //--- check ret code
        if (($ret_code = MTConnect::GetRetCode($order_answer->RetCode)) != MTRetCode::MT_RET_OK) return $ret_code;
        //---
        return MTRetCode::MT_RET_OK;
    }
}

/**
 * Order information
 */
class MTOrder
{
    //--- order ticket
    public $Order;
    //--- order ticket in external system (exchange, ECN, etc)
    public $ExternalID;
    //--- client login
    public $Login;
    //--- processed dealer login (0-means auto)
    public $Dealer;
    //--- order symbol
    public $Symbol;
    //--- price digits
    public $Digits;
    //--- currency digits
    public $DigitsCurrency;
    //--- contract size
    public $ContractSize;
    //--- EnOrderState
    public $State;
    //--- EnOrderReason
    public $Reason;
    //--- order setup time
    public $TimeSetup;
    //--- order expiration
    public $TimeExpiration;
    //--- order filling/cancel time
    public $TimeDone;
    //--- EnOrderType
    public $Type;
    //--- EnOrderFilling
    public $TypeFill;
    //--- EnOrderTime
    public $TypeTime;
    //--- order price
    public $PriceOrder;
    //--- order trigger price (stop-limit price)
    public $PriceTrigger;
    //--- order current price
    public $PriceCurrent;
    //--- order SL
    public $PriceSL;
    //--- order TP
    public $PriceTP;
    //--- order initial volume
    public $VolumeInitial;
    //--- order current volume
    public $VolumeCurrent;
    //--- expert id (filled by expert advisor)
    public $ExpertID;
    //--- expert position id (filled by expert advisor)
    public $ExpertPositionID;
    //--- order comment
    public $Comment;
    //--- order activation state, time and price
    public $ActivationMode;
    public $ActivationTime;
    public $ActivationPrice;
    public $ActivationFlags;
}

/**
 * Answer on request order_get_total
 */
class MTOrderTotalAnswer
{
    public $RetCode = '-1';
    public $Total = 0;
}

/**
 * get order page answer
 */
class MTOrderPageAnswer
{
    public $RetCode = '-1';
    public $ConfigJson = '';

    /**
     * From json get class MTOrder
     * @return array(MTOrder)
     */
    public function GetArrayFromJson()
    {
        $objects = MTJson::Decode($this->ConfigJson);
        if ($objects == null) return null;
        $result = array();
        //---
        foreach ($objects as $obj) {
            $info = MTOrderJson::GetFromJson($obj);
            //---
            $result[] = $info;
        }
        //---
        $objects = null;
        //---
        return $result;
    }
}

/**
 * get order page answer
 */
class MTOrderAnswer
{
    public $RetCode = '-1';
    public $ConfigJson = '';

    /**
     * From json get class MTOrder
     * @return array(MTOrder)
     */
    public function GetFromJson()
    {
        $obj = MTJson::Decode($this->ConfigJson);
        if ($obj == null) return null;
        //---
        return MTOrderJson::GetFromJson($obj);
    }
}

class MTOrderJson
{
    /**
     * Get MTOrder from json object
     * @param object $obj
     * @return MTOrder
     */
    public static function GetFromJson($obj)
    {
        if ($obj == null) return null;
        $info = new MTOrder();
        //---
        $info->Order = (float)$obj->Order;
        $info->ExternalID = (string)$obj->ExternalID;
        $info->Login = (float)$obj->Login;
        $info->Dealer = (float)$obj->Dealer;
        $info->Symbol = (string)$obj->Symbol;
        $info->Digits = (int)$obj->Digits;
        $info->DigitsCurrency = (int)$obj->DigitsCurrency;
        $info->ContractSize = (float)$obj->ContractSize;
        $info->State = (int)$obj->State;
        $info->Reason = (int)$obj->Reason;
        $info->TimeSetup = (float)$obj->TimeSetup;
        $info->TimeExpiration = (float)$obj->TimeExpiration;
        $info->TimeDone = (float)$obj->TimeDone;
        $info->Type = (int)$obj->Type;
        $info->TypeFill = (int)$obj->TypeFill;
        $info->TypeTime = (int)$obj->TypeTime;
        $info->PriceOrder = (float)$obj->PriceOrder;
        $info->PriceTrigger = (float)$obj->PriceTrigger;
        $info->PriceCurrent = (float)$obj->PriceCurrent;
        $info->PriceSL = (float)$obj->PriceSL;
        $info->PriceTP = (float)$obj->PriceTP;
        $info->VolumeInitial = (float)$obj->VolumeInitial;
        $info->VolumeCurrent = (float)$obj->VolumeCurrent;
        $info->ExpertID = (float)$obj->ExpertID;
        $info->ExpertPositionID = (float)$obj->ExpertPositionID;
        $info->Comment = (string)$obj->Comment;
        $info->ActivationMode = (int)$obj->ActivationMode;
        $info->ActivationTime = (float)$obj->ActivationTime;
        $info->ActivationPrice = (float)$obj->ActivationPrice;
        $info->ActivationFlags = (int)$obj->ActivationFlags;
        //---
        return $info;
    }
}

/**
 * order types
 */
class MTEnOrderType
{
    const OP_BUY = 0; // buy order
    const OP_SELL = 1; // sell order
    const OP_BUY_LIMIT = 2; // buy limit order
    const OP_SELL_LIMIT = 3; // sell limit order
    const OP_BUY_STOP = 4; // buy stop order
    const OP_SELL_STOP = 5; // sell stop order
    const OP_BUY_STOP_LIMIT = 6; // buy stop limit order
    const OP_SELL_STOP_LIMIT = 7; // sell stop limit order
    //--- enumeration borders
    const OP_FIRST = 0;
    const OP_LAST = 7;
}

/**
 * order filling types
 */
class MTEnOrderFilling
{
    const ORDER_FILL_FOK = 0; // fill or kill
    const ORDER_FILL_IOC = 1; // immediate or cancel
    const ORDER_FILL_RETURN = 2; // return order in queue
    //--- enumeration borders
    const ORDER_FILL_FIRST = 0;
    const ORDER_FILL_LAST = 2;
}

/**
 * order expiration types
 */
class MTEnOrderTime
{
    const ORDER_TIME_GTC = 0; // good till cancel
    const ORDER_TIME_DAY = 1; // good till day
    const ORDER_TIME_SPECIFIED = 2; // good till specified
    //--- enumeration borders
    const ORDER_TIME_FIRST = 0;
    const ORDER_TIME_LAST = 2;
}

/**
 * order state
 */
class MTEnOrderState
{
    const ORDER_STATE_STARTED = 0; // order started
    const ORDER_STATE_PLACED = 1; // order placed in system
    const ORDER_STATE_CANCELED = 2; // order canceled by client
    const ORDER_STATE_PARTIAL = 3; // order partially filled
    const ORDER_STATE_FILLED = 4; // order filled
    const ORDER_STATE_REJECTED = 5; // order rejected
    const ORDER_STATE_EXPIRED = 6; // order expired
    const ORDER_STATE_REQUEST_ADD = 7;
    const ORDER_STATE_REQUEST_MODIFY = 8;
    const ORDER_STATE_REQUEST_CANCEL = 9;
    //--- enumeration borders
    const ORDER_STATE_FIRST = 0;
    const ORDER_STATE_LAST = 9;
}

/**
 * order creation reasons
 */
class MTEnOrderReason
{
    const ORDER_REASON_CLIENT = 0; // order placed manually
    const ORDER_REASON_EXPERT = 1; // order placed by expert
    const ORDER_REASON_DEALER = 2; // order placed by dealer
    const ORDER_REASON_SL = 3; // order placed due SL
    const ORDER_REASON_TP = 4; // order placed due TP
    const ORDER_REASON_SO = 5; // order placed due Stop-Out
    const ORDER_REASON_ROLLOVER = 6; // order placed due rollover
    //--- enumeration borders
    const ORDER_REASON_FIRST = 0;
    const ORDER_REASON_LAST = 6;
}