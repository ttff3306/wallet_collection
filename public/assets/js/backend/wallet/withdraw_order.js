define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'wallet.withdraw_order/index' + location.search,
                    // add_url: 'wallet.withdraw_order/add',
                    // edit_url: 'wallet.withdraw_order/edit',
                    // del_url: 'wallet.withdraw_order/del',
                    // multi_url: 'wallet.withdraw_order/multi',
                    table: 'withdraw_order',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                search: false,
                columns: [
                    [
                        // {checkbox: true},
                        // {field: 'id', title: __('Id')},
                        {field: 'hash', title: __('Hash'),formatter: function (value,row,index){
                                return '<textarea cols="60" rows="2" disabled> '+(value == null ? '' : value)+' </textarea>';
                            },operate: 'LIKE'},
                        {field: 'is_internal', title: __('是否内部提现'), searchList:{'1':"是", '0':'否'}, formatter: Table.api.formatter.status},
                        {field: 'block_id', title: __('Block_id')},
                        {field: 'from_address', title: __('发送地址')},
                        {field: 'to_address', title: __('收款地址')},
                        // {field: 'uid', title: __('Uid')},
                        {field: 'trade_num', title: __('提币数量'), operate:'BETWEEN'},
                        {field: 'status', title: __('交易状态'), searchList: {'success':"交易成功",'fail':"交易失败",'pending':'等待确认'}, formatter: Table.api.formatter.status},
                        {field: 'trade_time', title: __('Withdraw_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'token_name', title: __('提币名称')},
                        {field: 'chain', title: __('所属公链'), searchList: $.getJSON('chain.chain/listChain'), formatter: Table.api.formatter.status},
                        {field: 'admin_id', title: __('所属商户'), searchList: {'1':'A商户', '2':'B商户'}, formatter: Table.api.formatter.status},
                        // {field: 'token_contract_address', title: __('Token_contract_address')},
                        // {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
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
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});