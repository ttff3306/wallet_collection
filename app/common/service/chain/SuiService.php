<?php

namespace app\common\service\chain;

use app\common\facade\Redis;
use BitWasp\Bitcoin\Address\PayToPubKeyHashAddress;
use BitWasp\Bitcoin\Key\Factory\HierarchicalKeyFactory;
use BitWasp\Bitcoin\Key\Factory\PrivateKeyFactory;
use BitWasp\Bitcoin\Mnemonic\Bip39\Bip39SeedGenerator;
use EthereumRPC\EthereumRPC;
use Exception;
use Web3\Utils;
use Web3p\EthereumTx\Transaction;
use Web3p\EthereumWallet\Wallet;

/**
 * Sui基础服务
 * @time 2023/6/29
 */
class SuiService
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
        try {
            $path = '44\'/784\'/0\'/0/0';
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
     * 通过私钥解析助记词
     * @param string $private_key
     * @return array
     * @author Bin
     * @time 2023/7/30
     */
    public function fromPrivateKey(string $private_key)
    {
        try {
            $hardened = (new PrivateKeyFactory())->fromWif($private_key);
            $address = new PayToPubKeyHashAddress($hardened->getPublicKey()->getPubKeyHash());
            return [
                'public_key' => $hardened->getPublicKey()->getHex(),
                'address' => $address->getAddress(),
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




