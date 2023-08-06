<?php

namespace app\common\service\chain;

use app\common\facade\Redis;
use BitWasp\Bitcoin\Address\PayToPubKeyHashAddress;
use BitWasp\Bitcoin\Address\SegwitAddress;
use BitWasp\Bitcoin\Key\Factory\HierarchicalKeyFactory;
use BitWasp\Bitcoin\Key\Factory\PrivateKeyFactory;
use BitWasp\Bitcoin\Mnemonic\Bip39\Bip39SeedGenerator;
use BitWasp\Bitcoin\Network\NetworkFactory;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Script\WitnessProgram;
use BitWasp\Buffertools\Buffer;
use EthereumRPC\EthereumRPC;
use Exception;
use Web3\Utils;
use Web3p\EthereumTx\Transaction;
use Web3p\EthereumWallet\Wallet;

/**
 * Ltc基础服务
 * @time 2023/6/29
 */
class LtcService
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
            $path = '44\'/2\'/0\'/0/0';
            $generator = new Bip39SeedGenerator();
            $seed = $generator->getSeed($mnemonic);
            $hdFactory = new HierarchicalKeyFactory();
            $master = $hdFactory->fromEntropy($seed);
            $hardened = $master->derivePath($path);
            $address = new PayToPubKeyHashAddress($hardened->getPublicKey()->getPubKeyHash());
            $network = NetworkFactory::litecoin();
            return [
                'public_key' => $hardened->getPublicKey()->getHex(),
                'address' => $address->getAddress($network),
                'private_key' => $hardened->getPrivateKey()->toWif($network),
                'mnemonic' => $mnemonic
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
            $network = NetworkFactory::litecoin();
            $hardened = (new PrivateKeyFactory())->fromWif($private_key, $network);
            $address = new PayToPubKeyHashAddress($hardened->getPublicKey()->getPubKeyHash());
            return [
                'public_key' => $hardened->getPublicKey()->getHex(),
                'address' => $address->getAddress($network),
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




