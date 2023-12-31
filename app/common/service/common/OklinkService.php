<?php

namespace app\common\service\common;

use app\common\facade\Redis;
use app\common\facade\ReportData;
use app\common\model\ApiKeyModel;
use GuzzleHttp\Client;

class OklinkService
{
    //请求地址
    private $url = 'https://www.oklink.com';

    /**
     * 获取api key
     * @return mixed|string
     * @author Bin
     * @time 2023/7/26
     */
    public function getApiKey()
    {
        $list_api_key = $this->listApiKey();
        if (empty($list_api_key)) return '';
        if (count($list_api_key) > 1) {
            $key = 'oklink:api:key:date:' . getDateDay(4, 11);
            if (!Redis::has($key)) Redis::setString($key, 0, 24 * 3600);
            $num = Redis::incString($key) % count($list_api_key);
            //获取apikey
            $api_key = $list_api_key[$num] ?? $list_api_key[0];
        }else{
            $api_key = $list_api_key[0] ?? '';
        }
        //限流处理
        $limit_key = 'oklink:api:key:' . $api_key . ':limit:time:' . time();
        if (!Redis::has($limit_key)) Redis::setString($limit_key, 0, 10);
        if (Redis::incString($limit_key) > 30) sleep(1);
        return $api_key;
    }

    /**
     * 获取api key
     * @param string $name
     * @param bool $is_update
     * @return mixed|string
     * @author Bin
     * @time 2023/7/26
     */
    public function listApiKey(string $name = 'oklink', bool $is_update = false)
    {
        //缓存key
        $key = 'api:key:list:name:' . $name . ':date:' . getDateDay(1, 50);
        //检测缓存
        if ($is_update || !Redis::has($key))
        {
            $key_list = ApiKeyModel::new()->where(['name' => $name, 'status' => 1])->column('api_key');
            Redis::setString($key, $key_list, 24 * 3600);
        }
        //返回结果
        return $key_list ?? Redis::getString($key);
    }

    /**
     * 根据地址获取余额明细列表
     * @param string $chain 公链缩写符号
     * @param string $address 地址
     * @param string $protocol_type 合约协议类型 20代币：token_20 721代币：token_721 1155代币：token_1155 10代币：token_10
     * @param int $page
     * @param int $limit
     * @return array|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @author Bin
     * @time 2023/7/26
     */
    public function listAddressBalance(string $chain, string $address, string $protocol_type = 'token_20', string $token_contract_address = '', int $page = 1, int $limit = 20)
    {
        $url = $this->url . '/api/v5/explorer/address/address-balance-fills?chainShortName=' . $chain . '&protocolType=' . $protocol_type
            . '&address=' . $address . '&page=' . $page . '&limit=' . $limit;
        if (!empty($token_contract_address)) $url .= '&tokenContractAddress=' . $token_contract_address;
        try {
            $options = [
                'headers'   => [
                    'Ok-Access-Key' => $this->getApiKey()
                ]
            ];
            $client = new Client();
            $response = $client->get($url, $options);
            // 获取响应内容
            $result = $response->getBody()->getContents();
            //返回结果
            return json_decode($result, true);
        } catch (\Exception $e) {
            ReportData::recordErrorLog('listAddressBalance', $e->getMessage());
            return [];
        }
    }

    /**
     * 根据地址获取余额
     * @param string $chain
     * @param string $address
     * @return array|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @author Bin
     * @time 2023/7/26
     */
    public function getAddressBalance(string $chain, string $address)
    {
        $url = $this->url . '/api/v5/explorer/address/address-summary?chainShortName=' . $chain . '&address=' . $address;
        try {
            $options = [
                'headers'   => [
                    'Ok-Access-Key' => $this->getApiKey()
                ]
            ];
            $client = new Client();
            $response = $client->get($url, $options);
            // 获取响应内容
            $result = $response->getBody()->getContents();
            //返回结果
            return json_decode($result, true);
        } catch (\Exception $e) {
            ReportData::recordErrorLog('getAddressBalance', $e->getMessage());
            return [];
        }
    }

    /**
     * 根据地址获取交易列表
     * @param string $chain
     * @param string $address
     * @param string $protocol_type
     * @param string $token_contract_address 不同类型的交易 交易：transaction 内部交易：internal 20代币：token_20 721代币：token_721 1155代币：token_1155 10代币：token_10 默认是transaction
     * @param int $page
     * @param int $limit
     * @return array|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @author Bin
     * @time 2023/7/26
     */
    public function listAddressTransaction(string $chain, string $address, string $token_contract_address = '', string $protocol_type = '', int $page = 1, int $limit = 100)
    {
        $url = $this->url . '/api/v5/explorer/address/transaction-list?chainShortName=' . $chain . '&address=' . $address . '&page=' . $page . '&limit=' . $limit;
        if (!empty($token_contract_address)) $url .= '&tokenContractAddress=' . $token_contract_address;
        if (!empty($protocol_type)) $url .= '&protocolType=' . $protocol_type;
        try {
            $options = [
                'headers'   => [
                    'Ok-Access-Key' => $this->getApiKey()
                ]
            ];
            $client = new Client();
            $response = $client->get($url, $options);
            // 获取响应内容
            $result = $response->getBody()->getContents();
            //返回结果
            return json_decode($result, true);
        } catch (\Exception $e) {
            ReportData::recordErrorLog('listAddressTransaction', $e->getMessage());
            return [];
        }
    }

    /**
     * 获取公链列表
     * @return array|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @author Bin
     * @time 2023/7/26
     */
    public function listChain()
    {
        $url = $this->url . '/api/v5/explorer/blockchain/summary';
        try {
            $options = [
                'headers'   => [
                    'Ok-Access-Key' => $this->getApiKey()
                ]
            ];
            $client = new Client();
            $response = $client->get($url, $options);
            // 获取响应内容
            $result = $response->getBody()->getContents();
            //返回结果
            return json_decode($result, true);
        } catch (\Exception $e) {
            ReportData::recordErrorLog('listChain', $e->getMessage());
            return [];
        }
    }

    /**
     * 获取公链下代币详情
     * @param string $chain
     * @param string $protocol_type
     * @param string $token_contract_address
     * @param int $page
     * @param int $limit
     * @return array|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @author Bin
     * @time 2023/7/30
     */
    public function listToken(string $chain, string $protocol_type = 'token_20', string $token_contract_address = '', int $page = 1, int $limit = 50)
    {
        $url = $this->url . '/api/v5/explorer/token/token-list?chainShortName=' . $chain . '&protocolType=' . $protocol_type . '&page=' . $page . '&limit=' . $limit;
        if (!empty($token_contract_address)) $url .= '&tokenContractAddress=' . $token_contract_address;
        try {
            $options = [
                'headers'   => [
                    'Ok-Access-Key' => $this->getApiKey()
                ]
            ];
            $client = new Client();
            $response = $client->get($url, $options);
            // 获取响应内容
            $result = $response->getBody()->getContents();
            //返回结果
            return json_decode($result, true);
        } catch (\Exception $e) {
            ReportData::recordErrorLog('listToken', $e->getMessage());
            return [];
        }
    }

    /**
     * 检测代币风险
     * @param string $chain
     * @param string $token_contract_address
     * @return array|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @author Bin
     * @time 2023/8/1
     */
    public function checkToken(string $chain, string $token_contract_address)
    {
        $url = $this->url . '/api/v5/tracker/tokenscanner/token-risk-scanning?chainShortName=' . $chain . '&tokenContractAddress=' . $token_contract_address;
        try {
            $options = [
                'headers'   => [
                    'Ok-Access-Key' => $this->getApiKey()
                ]
            ];
            $client = new Client();
            $response = $client->get($url, $options);
            // 获取响应内容
            $result = $response->getBody()->getContents();
            //返回结果
            return json_decode($result, true);
        } catch (\Exception $e) {
            ReportData::recordErrorLog('checkToken', $e->getMessage());
            return [];
        }
    }

    /**
     * 代币市场数据
     * @param string $token_contract_address
     * @return array|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @author Bin
     * @time 2023/8/1
     */
    public function marketData(int $chain_id = 0, string $token_contract_address = '')
    {
        $url = $this->url . '/api/v5/explorer/tokenprice/market-data?chainId=' . $chain_id . '&tokenContractAddress=' . $token_contract_address;
        try {
            $options = [
                'headers'   => [
                    'Ok-Access-Key' => $this->getApiKey()
                ]
            ];
            $client = new Client();
            $response = $client->get($url, $options);
            // 获取响应内容
            $result = $response->getBody()->getContents();
            //返回结果
            return json_decode($result, true);
        } catch (\Exception $e) {
            ReportData::recordErrorLog('marketData', $e->getMessage());
            return [];
        }
    }

    /**
     * 获取币种列表
     * @return array|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @author Bin
     * @time 2023/8/1
     */
    public function listTokenPrice(string $token = '', int $page = 1, int $limit = 50, string $token_unique_id = '')
    {
        $url = $this->url . "/api/v5/explorer/tokenprice/token-list?page={$page}&limit={$limit}";
        if (!empty($token)) $url .= "&token={$token}";
        if (!empty($token_unique_id)) $url .= "&tokenUniqueId={$token_unique_id}";
        try {
            $options = [
                'headers'   => [
                    'Ok-Access-Key' => $this->getApiKey()
                ]
            ];
            $client = new Client();
            $response = $client->get($url, $options);
            // 获取响应内容
            $result = $response->getBody()->getContents();
            //返回结果
            return json_decode($result, true);
        } catch (\Exception $e) {
            ReportData::recordErrorLog('listTokenPrice', $e->getMessage());
            return [];
        }
    }

    /**
     * 查询代币持仓地址列表
     * @param string $chain 公链
     * @param string $token_contract_address 合约地址
     * @param string|null $holder_address 持仓地址
     * @param int $page
     * @param int $limit
     * @return array|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @author Bin
     * @time 2023/8/12
     */
    public function listPosition(string $chain, string $token_contract_address, string $holder_address = null, int $page = 1, int $limit = 100)
    {
        $url = $this->url . '/api/v5/explorer/token/position-list?chainShortName=' . $chain . '&tokenContractAddress=' .
            $token_contract_address . '&page=' . $page . '&limit=' . $limit;
        if (!is_null($holder_address)) $url .= '&holderAddress=' . $holder_address;
        try {
            $options = [
                'headers'   => [
                    'Ok-Access-Key' => $this->getApiKey()
                ]
            ];
            $client = new Client();
            $response = $client->get($url, $options);
            // 获取响应内容
            $result = $response->getBody()->getContents();
            //返回结果
            return json_decode($result, true);
        } catch (\Exception $e) {
            ReportData::recordErrorLog('marketData', $e->getMessage());
            return [];
        }
    }

    /**
     * 获取区块列表
     * @param string $chain
     * @param int|null $height
     * @param int $page
     * @param int $limit
     * @return array|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @author Bin
     * @time 2023/8/22
     */
    public function listBlock(string $chain, int $height = null, int $page = 1, int $limit = 100)
    {
        $url = $this->url . "/api/v5/explorer/block/block-list?chainShortName={$chain}&page={$page}&limit={$limit}";
        if (!is_null($height)) $url .= "&height={$height}";
        try {
            $options = [
                'headers'   => [
                    'Ok-Access-Key' => $this->getApiKey()
                ]
            ];
            $client = new Client();
            $response = $client->get($url, $options);
            // 获取响应内容
            $result = $response->getBody()->getContents();
            //返回结果
            return json_decode($result, true);
        } catch (\Exception $e) {
            ReportData::recordErrorLog('listBlock', $e->getMessage());
            return [];
        }
    }

    /**
     * 获取交易列表
     * @param string $chain
     * @param int|null $height
     * @param int $page
     * @param int $limit
     * @return array|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @author Bin
     * @time 2023/8/22
     */
    public function listTransaction(string $chain, int $height = null, int $page = 1, int $limit = 100, string $protocol_type = 'transaction')
    {
        $url = $this->url . "/api/v5/explorer/block/transaction-list?chainShortName={$chain}&page={$page}&limit={$limit}&protocolType={$protocol_type}";
        if (!is_null($height)) $url .= "&height={$height}";
        try {
            $options = [
                'headers'   => [
                    'Ok-Access-Key' => $this->getApiKey()
                ]
            ];
            $client = new Client();
            $response = $client->get($url, $options);
            // 获取响应内容
            $result = $response->getBody()->getContents();
            //返回结果
            return json_decode($result, true);
        } catch (\Exception $e) {
            ReportData::recordErrorLog('listTransaction', $e->getMessage());
            return [];
        }
    }

    /**
     * 获取交易列表
     * @param string $chain
     * @param string $txid
     * @return array|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @author Bin
     * @time 2023/8/25
     */
    public function listTransactionFills(string $chain, string $txid)
    {
        $url = $this->url . "/api/v5/explorer/transaction/transaction-fills?chainShortName={$chain}&txid={$txid}";
        try {
            $options = [
                'headers'   => [
                    'Ok-Access-Key' => $this->getApiKey()
                ]
            ];
            $client = new Client();
            $response = $client->get($url, $options);
            // 获取响应内容
            $result = $response->getBody()->getContents();
            //返回结果
            return json_decode($result, true);
        } catch (\Exception $e) {
            ReportData::recordErrorLog('listTransactionFills', $e->getMessage());
            return [];
        }
    }
}