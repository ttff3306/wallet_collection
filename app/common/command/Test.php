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

use app\api\facade\Account;
use app\api\facade\Notice;
use app\api\facade\User;
use app\common\facade\Rabbitmq;
use app\common\facade\Redis;
use app\common\model\NoticeModel;
use app\common\model\ProfitConfigModel;
use app\common\service\common\BscService;
use app\common\service\common\TronService;
use app\common\service\mq\ConsumerService;
use fast\Random;
use fast\Rsa;
use kornrunner\Secp256k1;
use kornrunner\Serializer\HexPrivateKeySerializer;
use think\Exception;
use think\console\Input;
use think\console\Output;
use think\console\Command;
use think\console\input\Option;
use think\facade\Config;
use think\facade\Lang;
use Web3\Personal;
use Web3\Utils;
use Web3\Web3;
use Web3\Providers\HttpProvider;
use Web3\RequestManagers\HttpRequestManager;
use Web3\Methods\Personal\UnlockAccount;

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
//        dd(file_get_contents('127.0.0.1:10010'));
//        dd( / pow(10, 18));
//        $result = (new BscService())->getGasPrice();
//        $res= (new BscService())->getBalance('0x8EBb38012199918c1d6639539d6C801619960c15');
        $data = [
            'command' => "transfer_raw",
            'from' => '0xaB19b9C93e020ED5Cc699C2e66DbC17F03D3025A',
            'to' => '0x9f8a0a0E56c37240EC5cECC4a3a66391A66A50A5',
            'value' => 1,
            'privateKey' => '0x93cf39f9eb6c8df64370512f325a899dc1b27d33a2eee55854b3b2c495ad824a',
            'contract' => '0xb7A6cC4a27c92acd394d1f6Cf409FfdBB23280FE'
        ];
//        $data = [
//            'from' => '0xaB19b9C93e020ED5Cc699C2e66DbC17F03D3025A',
//            'to' => '0x8EBb38012199918c1d6639539d6C801619960c15',
//            'value' => 0.02,
//            'privateKey' => '0x93cf39f9eb6c8df64370512f325a899dc1b27d33a2eee55854b3b2c495ad824a',
//            'contract' => '0xd66c6B4F0be8CE5b39D52E0Fd1344c389929B378'
//        ];
//        $data = [
//            'from' => '0x7D06602C13bddC0d12979a34d3aacAD755BeAa24',
//            'to' => '0x8EBb38012199918c1d6639539d6C801619960c15',
//            'value' => 0.1,
//            'privateKey' => '0x90ff3134dd935505190397196dcdff5b9d420422a0d7211acd810db7347ade3f',
//            'contract' => '0x337610d27c682E347C9cD60BD4b3b107C9d34dDd'
////            'contract' => ''
//        ];
//        $data = [
//            'from' => '0x8EBb38012199918c1d6639539d6C801619960c15',
//            'to' => '0xb0d579b447efe2ee886e2e95218231ea1d263455',
//            'value' => 0.03,
//            'privateKey' => 'aaf87fbbc553523fa3f6b154b7fe3070111861285b10aa3134d5b3c1dd97e94f',
//            'contract' => ''
//        ];
//        $result  = (new BscService())->transferRaw($data['from'], $data['to'], $data['value'], $data['privateKey'], $data['contract']);
        $result = (new Rsa('', env('system_config.private_key')))->privEncrypt('65456315476846546546564');
        $result = (new Rsa(env('system_config.public_key')))->pubDecrypt($result);

        dd($result);
        dd((new BscService())->personalUnlockAccount('0x8EBb38012199918c1d6639539d6C801619960c15'));
    }

}
