<?php

namespace app\common\service\chain;

use BitWasp\Bitcoin\Key\Factory\HierarchicalKeyFactory;
use BitWasp\Bitcoin\Key\Factory\PrivateKeyFactory;
use BitWasp\Bitcoin\Mnemonic\Bip39\Bip39SeedGenerator;
use BitWasp\Bitcoin\Script\ScriptType;
use Btccom\BitcoinCash\Address\CashAddress;
use Web3p\EthereumUtil\Util;
use Web3p\EthereumWallet\Wallet;

/**
 * Ftm基础服务
 * @time 2023/6/29
 */
class FtmService
{
    /**
     * 解析助记词
     * @param string $mnemonic
     * @return array
     * @author Bin
     * @time 2023/7/27
     */
    public function fromMnemonic(string $mnemonic)
    {
        $path = '44\'/60\'/0\'/0/0';
        try {
            $wallet = new Wallet();
            $result = $wallet->fromMnemonic($mnemonic, $path);
            return [
                'public_key' => $result->getPublicKey(),
                'address' => $result->getAddress(),
                'private_key' => $result->getPrivateKey(),
                'mnemonic' => $result->getMnemonic()
            ];
        }catch (\Exception $e){
            $result = [];
        }
        //返回结果
        return $result;
    }

    /**
     * 通过私钥解析钱包
     * @param string $private_key
     * @return array
     * @author Bin
     * @time 2023/7/30
     */
    public function fromPrivateKey(string $private_key)
    {
        try {
            $util = new Util();
            $publicKey = $util->privateKeyToPublicKey($private_key);
            $address = $util->publicKeyToAddress($publicKey);
            return [
                'public_key' => $publicKey,
                'address' => $address,
                'private_key' => $private_key,
                'mnemonic' => ''
            ];
        }catch (\Exception $e){
            $result = [];
        }
        //返回结果
        return $result;
    }
}




