<?php

namespace app\common\service\chain;

use app\common\facade\Redis;
use BitWasp\Bitcoin\Address\PayToPubKeyHashAddress;
use BitWasp\Bitcoin\Key\Factory\HierarchicalKeyFactory;
use BitWasp\Bitcoin\Mnemonic\Bip39\Bip39SeedGenerator;
use EthereumRPC\EthereumRPC;
use Exception;
use Web3\Utils;
use Web3p\EthereumTx\Transaction;
use Web3p\EthereumWallet\Wallet;

/**
 * Btc基础服务
 * @time 2023/6/29
 */
class BtcService
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
            $path = '44\'/0\'/0\'/0/0';
            $generator = new Bip39SeedGenerator();
            $seed = $generator->getSeed($mnemonic);
            $hdFactory = new HierarchicalKeyFactory();
            $master = $hdFactory->fromEntropy($seed);
            $hardened = $master->derivePath($path);
            $address = new PayToPubKeyHashAddress($hardened->getPublicKey()->getPubKeyHash());
            return [
                'public_key' => $hardened->getPublicKey()->getHex(),
                'address' => $address->getAddress(),
                'private_key' => $hardened->getPrivateKey()->toWif(),
                'mnemonic' => $mnemonic
            ];
        }catch (\Exception $e){
            $result = [];
        }
        //返回结果
        return $result;
    }

}




