define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'chain.chain_token/index' + location.search,
                    // add_url: 'chain.chain_token/add',
                    // edit_url: 'chain.chain_token/edit',
                    // del_url: 'chain.chain_token/del',
                    table: 'chain_token',
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
                        {field: 'contract', title: __('Contract')},
                        {field: 'token', title: __('Token'), operate: "LIKE"},
                        // {field: 'token_name', title: __('Token_name')},
                        {field: 'chain', title: __('Chain'), searchList: $.getJSON('chain.chain/listChain'), formatter: Table.api.formatter.status},
                        // {field: 'status', title: __('Status')},
                        {field: 'create_time', title: __('Create_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        // {field: 'update_time', title: __('Update_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        // {field: 'last_block', title: __('Last_block')},
                        {field: 'total_token_value', title: __('Total_token_value'), operate:'BETWEEN'},
                        {field: 'total_value_usd', title: __('Total_value_usd'), operate:'BETWEEN'},
                        {field: 'total_withdraw_token_value', title: __('Total_withdraw_token_value'), operate:'BETWEEN'},
                        {field: 'total_withdraw_value_usd', title: __('Total_withdraw_value_usd'), operate:'BETWEEN'},
                        {field: 'is_mainstream_token', title: __('Is_mainstream_token')},
                        {field: 'price_usd', title: __('Price_usd'), operate:'BETWEEN'},
                        // {field: 'protocol_type', title: __('Protocol_type')},
                        {field: 'is_chain_token', title: __('Is_chain_token')},
                        // {field: 'token_unique_id', title: __('Token_unique_id')},
                        // {field: 'chain_id', title: __('Chain_id')},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
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