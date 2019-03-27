<?php

namespace aemaddin\mt5webapi;
//+------------------------------------------------------------------+
//|                                             MetaTrader 5 Web API |
//|                   Copyright 2001-2015, MetaQuotes Software Corp. |
//|                                        http://www.metaquotes.net |
//+------------------------------------------------------------------+

/**
 * Send common_get to MetaTrader 5 Server
 */
class MTCommonProtocol
{
    private $m_connect = null;

    public function __construct($connect)
    {
        $this->m_connect = $connect;
    }

    /**
     * send request common_get
     * @param MTConCommon $common - config from MT5 server
     * @return MTRetCode
     */
    public function CommonGet(&$common)
    {
        //--- send request
        if (!$this->m_connect->Send(MTProtocolConsts::WEB_CMD_COMMON_GET, "")) {
            if (MTLogger::getIsWriteLog()) MTLogger::write(MTLoggerType::ERROR, 'send common get failed');
            return MTRetCode::MT_RET_ERR_NETWORK;
        }
        //--- get answer
        if (($answer = $this->m_connect->Read()) == null) {
            if (MTLogger::getIsWriteLog()) MTLogger::write(MTLoggerType::ERROR, 'answer common get is empty');
            return MTRetCode::MT_RET_ERR_NETWORK;
        }
        //--- parse answer
        if (($error_code = $this->ParseCommonGet($answer, $common_answer)) != MTRetCode::MT_RET_OK) {
            if (MTLogger::getIsWriteLog()) MTLogger::write(MTLoggerType::ERROR, 'parse common get failed: [' . $error_code . ']' . MTRetCode::GetError($error_code));
            return $error_code;
        }
        //---
        $common = $common_answer->GetFromJson();
        //---
        return MTRetCode::MT_RET_OK;
    }

    /**
     * check answer from MetaTrader 5 server
     * @param  string $answer
     * @param  MTCommonGetAnswer $common_answer
     * @return MTRetCode
     */
    private function ParseCommonGet(&$answer, &$common_answer)
    {
        $pos = 0;
        //--- get command answer
        $command = $this->m_connect->GetCommand($answer, $pos);
        if ($command != MTProtocolConsts::WEB_CMD_COMMON_GET) return MTRetCode::MT_RET_ERR_DATA;
        //---
        $common_answer = new MTCommonGetAnswer();
        //--- get param
        $pos_end = -1;
        while (($param = $this->m_connect->GetNextParam($answer, $pos, $pos_end)) != null) {
            switch ($param['name']) {
                case MTProtocolConsts::WEB_PARAM_RETCODE:
                    $common_answer->RetCode = $param['value'];
                    break;
            }
        }
        //--- check ret code
        if (($ret_code = MTConnect::GetRetCode($common_answer->RetCode)) != MTRetCode::MT_RET_OK) return $ret_code;
        //--- get json
        if (($common_answer->ConfigJson = $this->m_connect->GetJson($answer, $pos_end)) == null) return MTRetCode::MT_RET_REPORT_NODATA;
        //---
        return MTRetCode::MT_RET_OK;
    }
}

/**
 * answer on request common_get
 */
class MTCommonGetAnswer
{
    public $RetCode = '-1';
    public $ConfigJson = '';

    /**
     * From json get class MTConCommon
     * @return MTConTime
     */
    public function GetFromJson()
    {
        $obj = MTJson::Decode($this->ConfigJson);
        if ($obj == null) return null;
        //---
        $result = new MTConCommon();
        //---
        $result->Name = (string)$obj->Name;
        $result->Owner = (string)$obj->Owner;
        $result->OwnerID = (string)$obj->OwnerID;
        $result->OwnerHost = (string)$obj->OwnerHost;
        $result->OwnerEmail = (string)$obj->OwnerEmail;
        $result->Product = (string)$obj->Product;
        $result->ExpirationLicense = (int)$obj->ExpirationLicense;
        $result->ExpirationSupport = (int)$obj->ExpirationSupport;
        $result->LimitTradeServers = (int)$obj->LimitTradeServers;
        $result->LimitWebServers = (int)$obj->LimitWebServers;
        $result->LimitAccounts = (int)$obj->LimitAccounts;
        $result->LimitDeals = (int)$obj->LimitDeals;
        $result->LimitGroups = (int)$obj->LimitGroups;
        $result->LiveUpdateMode = (int)$obj->LiveUpdateMode;
        //---
        $obj = null;
        return $result;
    }
}

/**
 * Common MetaTrader 5 Platform config
 */
class MTConCommon
{
    //--- server name
    public $Name;
    //--- owner full name (from license)
    public $Owner;
    //--- owner short name (from license)
    public $OwnerID;
    //--- owner host (from license)
    public $OwnerHost;
    //--- owner email (from license)
    public $OwnerEmail;
    //--- product full name (from license)
    public $Product;
    //--- license expiration date
    public $ExpirationLicense;
    //--- license support date
    public $ExpirationSupport;
    //--- max. trade servers count (from license)
    public $LimitTradeServers;
    //--- max. web servers count (from license)
    public $LimitWebServers;
    //--- max. real accounts count (from license)
    public $LimitAccounts;
    //--- max. trade deals count (from license)
    public $LimitDeals;
    //--- max. client groups count (from license)
    public $LimitGroups;
    //--- LiveUpdate mode
    public $LiveUpdateMode;
}