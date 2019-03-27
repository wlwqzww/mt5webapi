<?php

namespace aemaddin\mt5webapi;
//+------------------------------------------------------------------+
//|                                             MetaTrader 5 Web API |
//|                   Copyright 2001-2015, MetaQuotes Software Corp. |
//|                                        http://www.metaquotes.net |
//+------------------------------------------------------------------+

/**
 * Class get deals
 */
class MTDealProtocol
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
     * Get dael
     * @param int $ticket - ticket
     * @param MTDeal $deal
     * @return MTRetCode
     */
    public function DealGet($ticket, &$deal)
    {
        //--- send request
        $data = array(MTProtocolConsts::WEB_PARAM_TICKET => $ticket);
        //---
        if (!$this->m_connect->Send(MTProtocolConsts::WEB_CMD_DEAL_GET, $data)) {
            if (MTLogger::getIsWriteLog()) MTLogger::write(MTLoggerType::ERROR, 'send deal get failed');
            return MTRetCode::MT_RET_ERR_NETWORK;
        }
        //--- get answer
        if (($answer = $this->m_connect->Read()) == null) {
            if (MTLogger::getIsWriteLog()) MTLogger::write(MTLoggerType::ERROR, 'answer deal get is empty');
            return MTRetCode::MT_RET_ERR_NETWORK;
        }
        //--- parse answer
        if (($error_code = $this->ParseDeal(MTProtocolConsts::WEB_CMD_DEAL_GET, $answer, $deal_answer)) != MTRetCode::MT_RET_OK) {
            if (MTLogger::getIsWriteLog()) MTLogger::write(MTLoggerType::ERROR, 'parse deal get failed: [' . $error_code . ']' . MTRetCode::GetError($error_code));
            return $error_code;
        }
        //--- get object from json
        $deal = $deal_answer->GetFromJson();
        //---
        return MTRetCode::MT_RET_OK;
    }

    /**
     * check answer from MetaTrader 5 server
     * @param string $command command
     * @param  string $answer answer from server
     * @param  MTDealAnswer $deal_answer
     * @return MTRetCode
     */
    private function ParseDeal($command, &$answer, &$deal_answer)
    {
        $pos = 0;
        //--- get command answer
        $command_real = $this->m_connect->GetCommand($answer, $pos);
        if ($command_real != $command) return MTRetCode::MT_RET_ERR_DATA;
        //---
        $deal_answer = new MTDealAnswer();
        //--- get param
        $pos_end = -1;
        while (($param = $this->m_connect->GetNextParam($answer, $pos, $pos_end)) != null) {
            switch ($param['name']) {
                case MTProtocolConsts::WEB_PARAM_RETCODE:
                    $deal_answer->RetCode = $param['value'];
                    break;
            }
        }
        //--- check ret code
        if (($ret_code = MTConnect::GetRetCode($deal_answer->RetCode)) != MTRetCode::MT_RET_OK) return $ret_code;
        //--- get json
        if (($deal_answer->ConfigJson = $this->m_connect->GetJson($answer, $pos_end)) == null) return MTRetCode::MT_RET_REPORT_NODATA;
        //---
        return MTRetCode::MT_RET_OK;
    }

    /**
     * check answer from MetaTrader 5 server
     * @param  string $answer - answer from server
     * @param  MTDealPageAnswer $deal_answer
     * @return MTRetCode
     */
    private function ParseDealPage(&$answer, &$deal_answer)
    {
        $pos = 0;
        //--- get command answer
        $command_real = $this->m_connect->GetCommand($answer, $pos);
        if ($command_real != MTProtocolConsts::WEB_CMD_DEAL_GET_PAGE) return MTRetCode::MT_RET_ERR_DATA;
        //---
        $deal_answer = new MTDealPageAnswer();
        //--- get param
        $pos_end = -1;
        while (($param = $this->m_connect->GetNextParam($answer, $pos, $pos_end)) != null) {
            switch ($param['name']) {
                case MTProtocolConsts::WEB_PARAM_RETCODE:
                    $deal_answer->RetCode = $param['value'];
                    break;
            }
        }
        //--- check ret code
        if (($ret_code = MTConnect::GetRetCode($deal_answer->RetCode)) != MTRetCode::MT_RET_OK) return $ret_code;
        //--- get json
        if (($deal_answer->ConfigJson = $this->m_connect->GetJson($answer, $pos_end)) == null) return MTRetCode::MT_RET_REPORT_NODATA;
        //---
        return MTRetCode::MT_RET_OK;
    }

    /**
     * Get total deals for login
     * @param string $login - user login
     * @param int $from - date from
     * @param int $to - date to
     * @param int $total - count of users positions
     * @return MTRetCode
     */
    public function DealGetTotal($login, $from, $to, &$total)
    {
        //--- send request
        $data = array(MTProtocolConsts::WEB_PARAM_LOGIN => $login, MTProtocolConsts::WEB_PARAM_FROM => $from, MTProtocolConsts::WEB_PARAM_TO => $to);
        //---
        if (!$this->m_connect->Send(MTProtocolConsts::WEB_CMD_DEAL_GET_TOTAL, $data)) {
            if (MTLogger::getIsWriteLog()) MTLogger::write(MTLoggerType::ERROR, 'send deal get total failed');
            return MTRetCode::MT_RET_ERR_NETWORK;
        }
        //--- get answer
        if (($answer = $this->m_connect->Read()) == null) {
            if (MTLogger::getIsWriteLog()) MTLogger::write(MTLoggerType::ERROR, 'answer deal get total is empty');
            return MTRetCode::MT_RET_ERR_NETWORK;
        }
        //--- parse answer
        if (($error_code = $this->ParseDealTotal($answer, $deal_answer)) != MTRetCode::MT_RET_OK) {
            if (MTLogger::getIsWriteLog()) MTLogger::write(MTLoggerType::ERROR, 'parse deal get total failed: [' . $error_code . ']' . MTRetCode::GetError($error_code));
            return $error_code;
        }
        //--- get total
        $total = $deal_answer->Total;
        //---
        return MTRetCode::MT_RET_OK;
    }

    /**
     * Get deals
     * @param int $login - number of ticket
     * @param int $from - from date in unix time
     * @param int $to - to date in unix time
     * @param int $offset - begin records number
     * @param int $total - total records need
     * @param array(MTDeal) $deals
     * @return MTRetCode
     */
    public function DealGetPage($login, $from, $to, $offset, $total, &$deals)
    {
        //--- send request
        $data = array(MTProtocolConsts::WEB_PARAM_LOGIN => $login, MTProtocolConsts::WEB_PARAM_FROM => $from, MTProtocolConsts::WEB_PARAM_TO => $to, MTProtocolConsts::WEB_PARAM_OFFSET => $offset, MTProtocolConsts::WEB_PARAM_TOTAL => $total);
        //---
        if (!$this->m_connect->Send(MTProtocolConsts::WEB_CMD_DEAL_GET_PAGE, $data)) {
            if (MTLogger::getIsWriteLog()) MTLogger::write(MTLoggerType::ERROR, 'send deal get page failed');
            return MTRetCode::MT_RET_ERR_NETWORK;
        }
        //--- get answer
        if (($answer = $this->m_connect->Read()) == null) {
            if (MTLogger::getIsWriteLog()) MTLogger::write(MTLoggerType::ERROR, 'answer deal get page is empty');
            return MTRetCode::MT_RET_ERR_NETWORK;
        }
        //--- parse answer
        if (($error_code = $this->ParseDealPage($answer, $deal_answer)) != MTRetCode::MT_RET_OK) {
            if (MTLogger::getIsWriteLog()) MTLogger::write(MTLoggerType::ERROR, 'parse deal get page failed: [' . $error_code . ']' . MTRetCode::GetError($error_code));
            return $error_code;
        }
        //--- get object from json
        $deals = $deal_answer->GetArrayFromJson();
        //---
        return MTRetCode::MT_RET_OK;
    }

    /**
     * Check answer from MetaTrader 5 server
     * @param  $answer string server answer
     * @param  $deal_answer MTDealTotalAnswer
     * @return false
     */
    private function ParseDealTotal(&$answer, &$deal_answer)
    {
        $pos = 0;
        //--- get command answer
        $command = $this->m_connect->GetCommand($answer, $pos);
        if ($command != MTProtocolConsts::WEB_CMD_DEAL_GET_TOTAL) return MTRetCode::MT_RET_ERR_DATA;
        //---
        $deal_answer = new MTDealTotalAnswer();
        //--- get param
        $pos_end = -1;
        while (($param = $this->m_connect->GetNextParam($answer, $pos, $pos_end)) != null) {
            switch ($param['name']) {
                case MTProtocolConsts::WEB_PARAM_RETCODE:
                    $deal_answer->RetCode = $param['value'];
                    break;
                case MTProtocolConsts::WEB_PARAM_TOTAL:
                    $deal_answer->Total = (int)$param['value'];
                    break;
            }
        }
        //--- check ret code
        if (($ret_code = MTConnect::GetRetCode($deal_answer->RetCode)) != MTRetCode::MT_RET_OK) return $ret_code;
        //---
        return MTRetCode::MT_RET_OK;
    }
}

/**
 * Deal information
 */
class MTDeal
{
    public $Deal;
    public $ExternalID;
    public $Login;
    public $Dealer;
    public $Order;
    public $Action;
    public $Entry;
    public $Digits;
    public $DigitsCurrency;
    public $ContractSize;
    public $Time;
    public $Symbol;
    public $Price;
    public $Volume;
    public $Profit;
    public $Storage;
    public $Commission;
    public $CommissionAgent;
    public $RateProfit;
    public $RateMargin;
    public $ExpertID;
    public $ExpertPositionID;
    public $Comment;
    public $ProfitRaw;
    public $PricePosition;
}

/**
 * Answer on request deal_get_total
 */
class MTDealTotalAnswer
{
    public $RetCode = '-1';
    public $Total = 0;
}

/**
 * get deal page answer
 */
class MTDealPageAnswer
{
    public $RetCode = '-1';
    public $ConfigJson = '';

    /**
     * From json get class MTDeal
     * @return array(MTDeal)
     */
    public function GetArrayFromJson()
    {
        $objects = MTJson::Decode($this->ConfigJson);
        if ($objects == null) return null;
        $result = array();
        //---
        foreach ($objects as $obj) {
            $info = MTDealJson::GetFromJson($obj);
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
 * get deal page answer
 */
class MTDealAnswer
{
    public $RetCode = '-1';
    public $ConfigJson = '';

    /**
     * From json get class MTDeal
     * @return array(MTDeal)
     */
    public function GetFromJson()
    {
        $obj = MTJson::Decode($this->ConfigJson);
        if ($obj == null) return null;
        //---
        return MTDealJson::GetFromJson($obj);
    }
}

class MTDealJson
{
    /**
     * Get MTDeal from json object
     * @param object $obj
     * @return MTDeal
     */
    public static function GetFromJson($obj)
    {
        if ($obj == null) return null;
        $info = new MTDeal();
        //---
        $info->Deal = (float)$obj->Deal;
        $info->ExternalID = (string)$obj->ExternalID;
        $info->Login = (float)$obj->Login;
        $info->Dealer = (float)$obj->Dealer;
        $info->Order = (float)$obj->Order;
        $info->Action = (float)$obj->Action;
        $info->Entry = (float)$obj->Entry;
        $info->Digits = (float)$obj->Digits;
        $info->DigitsCurrency = (float)$obj->DigitsCurrency;
        $info->ContractSize = (float)$obj->ContractSize;
        $info->Time = (float)$obj->Time;
        $info->Symbol = (string)$obj->Symbol;
        $info->Price = (float)$obj->Price;
        $info->Volume = (float)$obj->Volume;
        $info->Profit = (float)$obj->Profit;
        $info->Storage = (float)$obj->Storage;
        $info->Commission = (float)$obj->Commission;
        $info->CommissionAgent = (float)$obj->CommissionAgent;
        $info->RateProfit = (float)$obj->RateProfit;
        $info->RateMargin = (float)$obj->RateMargin;
        $info->ExpertID = (float)$obj->ExpertID;
        $info->ExpertPositionID = (float)$obj->ExpertPositionID;
        $info->Comment = (string)$obj->Comment;
        $info->ProfitRaw = (float)$obj->ProfitRaw;
        $info->PricePosition = (float)$obj->PricePosition;
        //---
        return $info;
    }
}

/**
 * types of transactions
 */
class MTEnDealAction
{
    const DEAL_BUY = 0; // buy
    const DEAL_SELL = 1; // sell
    const DEAL_BALANCE = 2; // deposit operation
    const DEAL_CREDIT = 3; // credit operation
    const DEAL_CHARGE = 4; // additional charges
    const DEAL_CORRECTION = 5; // correction deals
    const DEAL_BONUS = 6; // bouns
    const DEAL_COMMISSION = 7; // commission
    const DEAL_COMMISSION_DAILY = 8; // daily commission
    const DEAL_COMMISSION_MONTHLY = 9; // monthly commission
    const DEAL_AGENT_DAILY = 10; // daily agent commission
    const DEAL_AGENT_MONTHLY = 11; // monthly agent commission
    const DEAL_INTERESTRATE = 12; // interest rate charges
    //--- enumeration borders
    const DEAL_FIRST = MTEnDealAction::DEAL_BUY;
    const DEAL_LAST = MTEnDealAction::DEAL_INTERESTRATE;
}