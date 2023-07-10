<?php

namespace app\api\controller;

use app\api\logic\MnemonicLogic;
use app\common\controller\Api;

/**
 * 助记词
 * @author Bin
 * @time 2023/7/3
 */
class Mnemonic extends Api
{
    /**
     * 获取助记词
     * @param MnemonicLogic $logic
     * @return void
     * @author Bin
     * @time 2023/7/3
     */
    public function getMnemonic(MnemonicLogic $logic)
    {
        $result = $logic->getMnemonic();
        $this->success($result);
    }

    /**
     * 备份助记词
     * @param MnemonicLogic $logic
     * @return void
     * @throws \app\api\exception\ApiException
     * @author Bin
     * @time 2023/7/3
     */
    public function backUpMnemonic(MnemonicLogic $logic)
    {
        $result = $logic->backUpMnemonic();
        $this->success($result);
    }

    /**
     * 检测助记词
     * @param MnemonicLogic $logic
     * @return void
     * @throws \app\api\exception\ApiException
     * @author Bin
     * @time 2023/7/3
     */
    public function checkMnemonic(MnemonicLogic $logic)
    {
        $result = $logic->checkMnemonic();
        $this->success($result);
    }
}
