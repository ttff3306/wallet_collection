<?php

use think\facade\Route;
use app\api\validate\user\SignValidate;
use \app\api\validate\user\SignUpValidate;
use \app\api\middleware\AuthMiddleware;
use \app\api\middleware\DelAppLockMiddleware;
use \app\api\validate\user\BackUpMnemonicValidate;
use app\api\validate\user\UpdateNicknameValidate;
use app\api\validate\user\UpdatePasswordValidate;
use app\api\validate\user\UpdatePayPasswordValidate;
use app\api\validate\user\DelRelationUserValidate;
use app\api\validate\user\FeedbackValidate;
use app\api\validate\account\WithdrawValidate;
use app\api\middleware\IsBackUpMnemonicMiddleware;
use app\api\middleware\CheckPayPwdMiddleware;
use app\api\middleware\CheckMnemonicMiddleware;
use app\api\validate\market\ReleaseValidate;

/*******************授权接口*************************/
Route::group(function (){
    /************助记词模块*************/
    Route::group('mnemonic', function (){
        Route::get('$', 'api/mnemonic/getMnemonic')->name('获取助记词');
        Route::post('backup$', 'api/mnemonic/backUpMnemonic')->validate(BackUpMnemonicValidate::class)->name('备份助记词');
        Route::post('check$', 'api/mnemonic/checkMnemonic')->validate(BackUpMnemonicValidate::class)->middleware(CheckMnemonicMiddleware::class)->name('检测助记词是否正确');
    });
    /************用户模块*************/
    Route::group('user', function (){
        Route::get('info$', 'api/user/getUserInfo')->name('获取用户信息');
        Route::get('logout$', 'api/user/logout')->name('退出登陆');
        Route::get('team/data$', 'api/user/getTeamData')->name('获取团队数据');
        Route::post('sign$', 'api/user/sign')->middleware(IsBackUpMnemonicMiddleware::class)->name('用户签到');
        Route::get('invite/friends$', 'api/user/inviteFriends')->name('推广码');


        /************更新用户信息*************/
        Route::group('update', function (){
            Route::post('avatar$', 'api/user/uploadAvatar')->name('上传用户头像');
            Route::post('nickname$', 'api/user/updateNickname')->validate(UpdateNicknameValidate::class)->name('修改昵称');
            Route::post('login/password$', 'api/user/updateLoginPassword')->validate(UpdatePasswordValidate::class)->middleware(CheckMnemonicMiddleware::class)->name('修改登录密码');
            Route::post('pay/password$', 'api/user/updatePayPassword')->validate(UpdatePayPasswordValidate::class)->middleware(CheckMnemonicMiddleware::class)->name('修改二级密码');
        });

        /************用户关联*************/
        Route::group('relation', function (){
            Route::get('$', 'api/user/relationList')->name('获取关联账号列表');
            Route::post('add$', 'api/user/addRelationUser')->validate(SignValidate::class)->middleware(IsBackUpMnemonicMiddleware::class)->name('添加关联账号');
            Route::post('del$', 'api/user/delRelationUser')->validate(DelRelationUserValidate::class)->middleware(IsBackUpMnemonicMiddleware::class)->name('解除关联账号');
        });

        /**************意见反馈*****************/
        Route::post('feedback$', 'api/user/feedback')->validate(FeedbackValidate::class)->middleware(IsBackUpMnemonicMiddleware::class)->name('意见反馈');
    });

    /**************账户*****************/
    Route::group('account', function (){
        Route::get('usdt$', 'api/account/usdt')->name('获取USDT账户数据');
        Route::get('usdk$', 'api/account/usdk')->name('获取USDK账户数据');
        Route::get('usdk/profit$', 'api/account/getUsdkProfitList')->name('获取USDK收益明细');
        Route::get('wallet$', 'api/account/getWallet')->name('获取用户钱包');
        Route::get('withdraw/config$', 'api/account/getWithdrawConfig')->name('获取提现配置数据');
        Route::post('apply/withdraw$', 'api/account/applyWithdraw')->validate(WithdrawValidate::class)->middleware(CheckPayPwdMiddleware::class)->name('申请提现');
        Route::get('withdraw/order$', 'api/account/listWithdrawOrder')->name('提现订单');
    });

    /**************公告*****************/
    Route::group('notice', function (){
        Route::get('popup$', 'api/notice/getPopupNotice')->name('获取公告弹窗');
        Route::get('list$', 'api/notice/getNoticeList')->name('获取公告列表');
        Route::get('detail$', 'api/notice/getNoticeDetail')->name('获取公告详情');
    });

    /**************市场*****************/
    Route::group('market', function (){
        Route::get('$', 'api/market/index')->name('市场数据');
        Route::get('list/order$', 'api/market/listOrder')->name('质押订单列表');
        Route::get('order$', 'api/market/getOrderDetail')->name('订单详情');
        Route::post('release$', 'api/market/release')->validate(ReleaseValidate::class)->middleware(IsBackUpMnemonicMiddleware::class)->name('投入');
        Route::post('close/order$', 'api/market/closeOrder')->middleware(CheckPayPwdMiddleware::class)->name('解压订单');
        Route::get('exchange$', 'api/market/getExchangeIndex')->name('获取闪兑数据');
        Route::post('exchange$', 'api/market/exchange')->middleware(CheckPayPwdMiddleware::class)->name('闪兑提交');
    });

    /**************资讯*****************/
    Route::group('information', function (){
        Route::get('$', "api/information/index")->name("资讯首页");
        Route::get('list$', "api/information/listInformation")->name("资讯列表");
        Route::get('detail$', "api/information/detailInformation")->name("资讯列表");
    });

    /**************排行榜*****************/
    Route::get('ranking', 'api/information/listRanking');

})->middleware(AuthMiddleware::class)->middleware(DelAppLockMiddleware::class);

/*******************非授权接口*************************/
Route::group(function (){
    Route::post('sign$', 'api/user/login')->validate(SignValidate::class)->name('登陆接口');
    Route::post('sign/up$','api/user/register')->validate(SignUpValidate::class)->name('注册接口');
});

/*******************路由不存在*************************/
Route::miss(function() {
    throw new \think\exception\RouteNotFoundException();
});