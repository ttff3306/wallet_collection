define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'wallet.token_balance/index' + location.search,
                    // add_url: 'wallet.token_balance/add',
                    // edit_url: 'wallet.token_balance/edit',
                    // del_url: 'wallet.token_balance/del',
                    // multi_url: 'wallet.token_balance/multi',
                    table: 'token_balance',
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
                        {field: 'id', title: __('Id')},
                        {field: 'address', title: __('Address')},
                        {field: 'chain', title: __('Chain'), searchList: $.getJSON('chain.chain/listChain'), formatter: Table.api.formatter.status},
                        {field: 'balance', title: __('Balance'), operate:'BETWEEN', sortable: true},
                        {field: 'token', title: __('Token')},
                        {field: 'total_token_value', title: __('折合公链原生代币数量'), operate:'BETWEEN', sortable: true},
                        {field: 'value_usd', title: __('折合USD数量'), operate:'BETWEEN', sortable: true},
                        {field: 'history_high_balance', title: __('历史最高余额'), operate:'BETWEEN', sortable: true},
                        {field: 'history_high_value_usd', title: __('折合历史最高USDT'), operate:'BETWEEN', sortable: true},
                        {field: 'token_contract_address', title: __('代币合约地址')},
                        {field: 'collection_type', title: __('归集状态'), searchList: {'0':"待归集",'1':"归集中",'2':"归集成功",'3':"归集失败",'-1':"暂不支持归集"}, formatter: Table.api.formatter.status},
                        {field: 'create_time', title: __('Create_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'mnemonic_key', title: __('助记词标识')},
                        // {field: 'update_time', title: __('Update_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate,
                            buttons: [
                                {
                                    name: 'withdraw',
                                    text: '一键归集',
                                    classname: 'btn btn-xs btn-success btn-danger btn-ajax',
                                    icon: 'fa fa-magic',
                                    url: 'wallet.token_balance/withdraw',
                                    confirm: '确认一键归集吗?',
                                    hidden:function (value,row) {
                                        if (Number(value.collection_type) < 0){
                                            return true;
                                        }
                                    },
                                    success: function (data, ret) {
                                        $(".btn-refresh").trigger("click");
                                        return true;
                                    },
                                    error: function (data, ret) {
                                        Layer.alert(ret.msg);
                                        return false;
                                    }
                                },
                                {
                                    name: 'addblack',
                                    text: '加入黑名单',
                                    classname: 'btn btn-xs btn-success btn-magic btn-ajax',
                                    icon: 'fa fa-magic',
                                    url: 'wallet.token_balance/addblack',
                                    confirm: '确认加入黑名单吗?',
                                    success: function (data, ret) {
                                        $(".btn-refresh").trigger("click");
                                        return true;
                                    },
                                    error: function (data, ret) {
                                        Layer.alert(ret.msg);
                                        return false;
                                    }
                                }
                            ],
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
        addblack: function () {
            Controller.api.bindevent();
        },
        withdraw: function () {
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