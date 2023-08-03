define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'chain.chain/index' + location.search,
                    // add_url: 'chain.chain/add',
                    // edit_url: 'chain.chain/edit',
                    table: 'chain',
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
                        {field: 'chain', title: __('Chain')},
                        // {field: 'name', title: __('Name')},
                        // {field: 'status', title: __('Status')},
                        // {field: 'create_time', title: __('Create_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        // {field: 'update_time', title: __('Update_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'chain_token', title: __('Chain_token')},
                        {field: 'total_token_value', title: __('折合原生代币数量'), operate: false},
                        {field: 'total_value_usd', title: __('折合USDT数量'), operate:false},
                        // {field: 'chain_token_price_usd', title: __('Chain_token_price_usd'), sortable: true},
                        {field: 'total_withdraw_token_value', title: __('Total_withdraw_token_value'), operate:'BETWEEN'},
                        {field: 'total_withdraw_value_usd', title: __('Total_withdraw_value_usd'), operate:'BETWEEN'},
                        {field: 'collection_address', title: __('Collection_address')},
                        {field: 'gas_wallet_address', title: __('Gas_wallet_address')},
                        // {field: 'gas_wallet_private_key', title: __('Gas_wallet_private_key')},
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