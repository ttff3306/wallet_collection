<?php

// 全局中间件定义文件
return [
    // 全局请求缓存
    // \think\middleware\CheckRequestCache::class,
    // 多语言加载
    \think\middleware\LoadLangPack::class,
    // 页面Trace调试
    //\think\middleware\TraceDebug::class,
    //跨域处理
    \app\api\middleware\AllowCrossDomain::class,
];
