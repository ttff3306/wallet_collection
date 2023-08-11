<?php

namespace app\common\service\chain;

use BitWasp\Bitcoin\Key\Factory\HierarchicalKeyFactory;
use BitWasp\Bitcoin\Key\Factory\PrivateKeyFactory;
use BitWasp\Bitcoin\Mnemonic\Bip39\Bip39SeedGenerator;
use BitWasp\Bitcoin\Script\ScriptType;
use Btccom\BitcoinCash\Address\CashAddress;

/**
 * Bch基础服务
 * @time 2023/6/29
 */
class BchService
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
            $path = '44\'/145\'/0\'/0/0';
            $generator = new Bip39SeedGenerator();
            $seed = $generator->getSeed($mnemonic);
            $hdFactory = new HierarchicalKeyFactory();
            $master = $hdFactory->fromEntropy($seed);
            $hardened = $master->derivePath($path);
            $network = \Btccom\BitcoinCash\Network\NetworkFactory::bitcoinCash();

            $address = new CashAddress(ScriptType::P2PKH, $hardened->getPublicKey()->getPubKeyHash());

            return [
                [
                    'public_key' => $hardened->getPublicKey()->getHex(),
                    'address' => $address->getAddress($network),
                    'private_key' => $hardened->getPrivateKey()->toWif($network),
                    'mnemonic' => $mnemonic
                ],
                [
                    'public_key' => $hardened->getPublicKey()->getHex(),
                    'address' => $address->getLegacyAddress()->getAddress(),
                    'private_key' => $hardened->getPrivateKey()->toWif($network),
                    'mnemonic' => $mnemonic
                ]
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
            $network = \Btccom\BitcoinCash\Network\NetworkFactory::bitcoinCash();
            $hardened = (new PrivateKeyFactory())->fromWif($private_key, $network);
            $address = new CashAddress(ScriptType::P2PKH, $hardened->getPublicKey()->getPubKeyHash());
            return [
                [
                    'public_key' => $hardened->getPublicKey()->getHex(),
                    'address' => $address->getAddress($network),
                    'private_key' => $private_key,
                    'mnemonic' => ''
                ],
                [
                    'public_key' => $hardened->getPublicKey()->getHex(),
                    'address' => $address->getLegacyAddress()->getAddress(),
                    'private_key' => $private_key,
                    'mnemonic' => ''
                ]
            ];
        }catch (\Exception $e){
            $result = [];
        }
        //返回结果
        return $result;
    }
}




