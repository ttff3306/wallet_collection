define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'wallet.recharge_order/index' + location.search,
                    // add_url: 'wallet.recharge_order/add',
                    // edit_url: 'wallet.recharge_order/edit',
                    // del_url: 'wallet.recharge_order/del',
                    // multi_url: 'wallet.recharge_order/multi',
                    table: 'recharge_order',
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
                        // {checkbox: true},
                        // {field: 'id', title: __('Id')},
                        // {field: 'uid', title: __('Uid')},
                        {field: 'hash', title: __('Hash'),formatter: function (value,row,index){
                                return '<textarea cols="60" rows="2" disabled> '+(value == null ? '' : value)+' </textarea>';
                            },operate: 'LIKE'},
                        {field: 'is_internal', title: __('是否内部交易'), searchList:{'1':"是", '0':'否'}, formatter: Table.api.formatter.status},
                        {field: 'block_id', title: __('Block_id')},
                        {field: 'from_address', title: __('From_address')},
                        {field: 'to_address', title: __('To_address')},
                        {field: 'trade_num', title: __('Trade_num'), operate:'BETWEEN'},
                        {field: 'status', title: __('交易状态'), searchList: {'success':"交易成功",'fail':"交易失败",'pending':'等待确认'}, formatter: Table.api.formatter.status},
                        {field: 'trade_time', title: __('Trade_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'chain', title: __('Chain')},
                        {field: 'token_name', title: __('Token_name')},
                        // {field: 'token_contract_address', title: __('Token_contract_address')},
                        {field: 'admin_id', title: __('所属商户'), searchList: {'1':'A商户', '2':'B商户'}, formatter: Table.api.formatter.status},
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