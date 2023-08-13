<?php
/**
 * *
 *  * ============================================================================
 *  * Created by PhpStorm.
 *  * User: Ice
 *  * 邮箱: ice@sbing.vip
 *  * 网址: https://sbing.vip
 *  * Date: 2019/9/19 下午3:33
 *  * ============================================================================.
 */

namespace app\admin\controller;

use app\admin\model\Admin;
use app\admin\model\User;
use app\common\facade\Chain;
use app\common\model\Attachment;
use app\common\model\ChainModel;
use app\common\model\CollectionBalanceModel;
use app\common\model\ImportMnemonicModel;
use app\common\model\WalletBalanceModel;
use fast\Date;
use think\facade\Config;
use app\common\controller\Backend;
use think\facade\Db;

/**
 * 控制台.
 *
 * @icon fa fa-dashboard
 * @remark 用于展示当前系统中的统计数据、统计报表及重要实时数据
 */
class Dashboard extends Backend
{
    /**
     * 查看
     */
    public function index()
    {
        //获取总资产(usdt)
        $total_usdt = WalletBalanceModel::new()->sum('value_usd');
        //总导入助记词数量
        $total_mnemonic = ImportMnemonicModel::new()->count();
        //今日新增
        $today_mnemonic = ImportMnemonicModel::new()->where(['date_day' => date('Ymd')])->count();
        //总提现
        $total_withdraw_usdt = WalletBalanceModel::new()->sum('withdraw_value_usd');
        //今日提现
        $today_withdraw_usdt = CollectionBalanceModel::new()->getValuesSum(['date_day' => date('Ymd')], 'actual_receipt_amount_usd');
        //获取公链列表
        $chain_list = (new \app\admin\model\chain\Chain())->where(['status' => 1])->select();

        $this->view->assign([
            'total_usdt'                => $total_usdt,
            'total_mnemonic'            => $total_mnemonic,
            'today_mnemonic'            => $today_mnemonic,
            'total_withdraw_usdt'       => $total_withdraw_usdt,
            'today_withdraw_usdt'       => $today_withdraw_usdt,
            'total_chain'               => count($chain_list),
            'chain_list'                => $chain_list
        ]);

        return $this->view->fetch();
    }
}
