<?php
/**
 * *
 *  * ============================================================================
 *  * Created by PhpStorm.
 *  * User: Ice
 *  * 邮箱: ice@sbing.vip
 *  * 网址: https://sbing.vip
 *  * Date: 2019/9/19 下午5:05
 *  * ============================================================================.
 */

namespace app\common\command;

use app\common\model\ChainModel;
use app\common\service\chain\BscService;
use app\common\service\chain\TronService;
use app\common\service\common\OklinkService;
use BitWasp\Bitcoin\Address\PayToPubKeyHashAddress;
use BitWasp\Bitcoin\Key\Factory\HierarchicalKeyFactory;
use BitWasp\Bitcoin\Mnemonic\Bip39\Bip39SeedGenerator;
use BitWasp\Bitcoin\Mnemonic\Bip39\Wordlist\EnglishWordList;
use BitWasp\Bitcoin\Mnemonic\MnemonicFactory;
use BitWasp\Bitcoin\Network\NetworkFactory;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use Web3p\EthereumWallet\Wallet;

class Test extends Command
{

    protected function configure()
    {
        $this
            ->setName('test')
            ->setDescription('test');
    }

    protected function execute(Input $input, Output $output)
    {
        $wallet = new Wallet();
        $path = '44\'/0\'/0\'/0/0';
//        $path = '49\'/0\'/0\'/0/0';
//        $path = '84\'/0\'/0\'/0/0';
        $m = "muscle shallow broom away bunker tortoise around piece enjoy power suffer surround";
        $generator = new Bip39SeedGenerator();
        $seed = $generator->getSeed($m);
        echo "seed: " . $seed->getHex() . PHP_EOL;
        $hdFactory = new HierarchicalKeyFactory();
        $master = $hdFactory->fromEntropy($seed);

        $hardened = $master->derivePath($path);
        echo 'WIF: ' . $hardened->getPrivateKey()->toWif();
        echo PHP_EOL;
        echo 'PUB: ' . $hardened->getPublicKey()->getHex();
        echo PHP_EOL;
        $address = new PayToPubKeyHashAddress($hardened->getPublicKey()->getPubKeyHash());
        echo 'address: ' . $address->getAddress();
        echo PHP_EOL;
        dd();
        dd(132);
        $res = $wallet->fromMnemonic($m);
        dd($res);
        $res = \app\common\facade\Wallet::syncAddressBalance('TRON', 'TBhrbwxpZV4STXDG1LbhXsiFtxKJ68LdFU');
        dd($res);
        dd(OklinkService::instance()->getAddressBalance('BSC','0x7527472Cc74089F7d65468ce4468aA8827d27a80'));
//        $res = (new OklinkService())->listAddressBalance('BSC','0x7527472Cc74089F7d65468ce4468aA8827d27a80');
//        $token_list_trc20 = (new TronService())->wallet('TQFyaUmm8m45vJ9wYPgTthTn4HF2oiLLCz');
//        dd($token_list_trc20);
        $token_list_bep20 = (new BscService())->getWallet('0xa223c6f76bb35f8ba6ccbcd73626794f62d9dfe3');
        dd($token_list_bep20);
        $m = 'copper bundle retreat orange acquire casual escape globe rabbit sample one club';
        $result = (new Wallet())->fromMnemonic($m);
        dd($result);
    }

}
