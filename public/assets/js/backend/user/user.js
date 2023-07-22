define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'user.user/index',
                    add_url: 'user.user/add',
                    edit_url: 'user.user/edit',
                    table: 'user',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id'), sortable: true},
                        {field: 'uuid', title: __('UUID'), sortable: true},
                        {
                            field: 'avatar',
                            title: __('Avatar'),
                            events: Table.api.events.image,
                            formatter: Table.api.formatter.image,
                            operate: false
                        },
                        {field: 'username', title: __('Username'), operate: 'LIKE'},
                        {field: 'nickname', title: __('Nickname'), operate: 'LIKE'},
                        {field: 'usdt', title: __('USDT余额'), operate: 'LIKE', sortable: true},
                        {field: 'usdk', title: __('USDK余额'), operate: 'LIKE', sortable: true},
                        {field: 'level', title: __('Level'),visible:false,searchList: $.getJSON("config.levelConfig/listLevel")},
                        {field: 'level_name', title: __('Level'),operate: false},
                        {field: 'p_level', title: __('后台设置等级'),visible:false,searchList: $.getJSON("config.levelConfig/listLevel")},
                        {field: 'p_level_name', title: __('后台设置等级'),operate: false},
                        {field: 'p_uid', title: __('直推人id')},
                        {field: 'p_uid2', title: __('间推人id')},
                        {field: 'invite_code', title: __('推广码')},
                        {field: 'common.is_backup_mnemonic', title: __('是否备份助记词'), searchList: {'1':'已备份','0':'未备份'}, operate: false
                        ,formatter: Table.api.formatter.normal},
                        {field: 'common.is_effective_member', title: __('是否有效会员'), searchList: {'1':'有效','0':'无效'}, operate: false
                            ,formatter: Table.api.formatter.status},
                        {field: 'common.total_user_performance', title: __('用户累计投入量'), operate: false},
                        {field: 'common.direct_effective_num', title: __('直推有效人数'), operate: false},
                        {field: 'common.team_effective_num', title: __('团队有效人数'), operate: false},
                        {field: 'common.total_recharge_usdt', title: __('用户累计充值'), operate: false},
                        {field: 'common.total_withdraw_usdt', title: __('用户累计提现'), operate: false},
                        {field: 'common.team_total_recharge_usdt', title: __('团队累计充值'), operate: false},
                        {field: 'common.team_total_withdraw_usdt', title: __('团队累计提现'), operate: false},
                        {
                        field: 'status',
                        title: __('Status'),
                        formatter: Table.api.formatter.status,
                        searchList: {1: __('正常'), 0: __('禁用')}
                        },
                        {
                            field: 'createtime',
                            title: __('注册时间'),
                            formatter: Table.api.formatter.datetime,
                            operate: 'RANGE',
                            addclass: 'datetimerange',
                            sortable: true
                        },
                        {
                            field: 'logintime',
                            title: __('Logintime'),
                            formatter: Table.api.formatter.datetime,
                            operate: 'RANGE',
                            addclass: 'datetimerange',
                            sortable: true
                        },
                        {field: 'loginip', title: __('Loginip'), formatter: Table.api.formatter.search},
                        {
                            field: 'jointime',
                            title: __('Jointime'),
                            formatter: Table.api.formatter.datetime,
                            operate: 'RANGE',
                            addclass: 'datetimerange',
                            sortable: true
                        },
                        {field: 'joinip', title: __('Joinip'), formatter: Table.api.formatter.search},
                        {
                            field: 'operate',
                            title: __('Operate'),
                            table: table,
                            events: Table.api.events.operate,
                            formatter: Table.api.formatter.operate,
                            buttons: [
                                {
                                    name: 'edit',
                                    text: '编辑',
                                    title: '编辑',
                                    icon: 'fa fa-edit',
                                    classname: 'btn btn-xs btn-success btn-editone btn-dialog',
                                    url: 'user.user/edit',
                                },
                                {
                                    name: 'editusdt',
                                    text: 'USDT调整',
                                    icon: 'fa fa-pencil',
                                    classname: 'btn btn-success btn-xs btn-dialog',
                                    url: 'user.user/editUsdt'
                                },
                                {
                                    name: 'editusdk',
                                    text: 'USDK调整',
                                    icon: 'fa fa-pencil',
                                    classname: 'btn btn-success btn-xs btn-dialog',
                                    url: 'user.user/editUsdk'
                                },
                            ]
                        }
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        editusdt: function () {
            Controller.api.bindevent();
        },
        editusdk: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});