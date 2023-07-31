<?php

namespace app\common\service\common;

use app\common\facade\Redis;
use app\common\model\ApiKeyModel;
use app\common\model\ErrorLogModel;
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
        //返回结果
        $result = '';
        $list_api_key = $this->listApiKey();
        if (empty($list_api_key)) return $result;
        $key = 'oklink:api:key:date:' . getDateDay(4, 11);
        if (!Redis::has($key)) Redis::setString($key, 0, 24 * 3600);
        $num = Redis::incString($key) % count($list_api_key);
        return $list_api_key[$num] ?? $list_api_key[0];
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
    public function listAddressBalance(string $chain, string $address, string $protocol_type = 'token_20', int $page = 1, int $limit = 20)
    {
        $url = $this->url . '/api/v5/explorer/address/address-balance-fills?chainShortName=' . $chain . '&protocolType=' . $protocol_type
            . '&address=' . $address . '&page=' . $page . '&limit=' . $limit;
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
            ErrorLogModel::new()->createRow([
                'name' => 'listAddressBalance',
                'content' => trim($e->getMessage()),
                'memo' => '',
            ]);
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
            ErrorLogModel::new()->createRow([
                'name' => 'getAddressBalance',
                'content' => trim($e->getMessage()),
                'memo' => '',
            ]);
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
    public function listAddressTransaction(string $chain, string $address, string $protocol_type, string $token_contract_address = '', int $page = 1, int $limit = 100)
    {
        $url = $this->url . '/api/v5/explorer/address/transaction-list?chainShortName=' . $chain . '&address=' . $address . '&page=' . $page . '&limit=' . $limit;
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
            ErrorLogModel::new()->createRow([
                'name' => 'listAddressTransaction',
                'content' => trim($e->getMessage()),
                'memo' => '',
            ]);
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
            ErrorLogModel::new()->createRow([
                'name' => 'listToken',
                'content' => trim($e->getMessage()),
                'memo' => '',
            ]);
            return [];
        }
    }
}