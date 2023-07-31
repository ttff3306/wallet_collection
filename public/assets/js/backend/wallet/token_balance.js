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
                        {field: 'chain', title: __('Chain')},
                        {field: 'balance', title: __('Balance'), operate:'BETWEEN', sortable: true},
                        {field: 'token', title: __('Token')},
                        {field: 'total_token_value', title: __('折合公链原生代币数量'), sortable: true},
                        {field: 'value_usd', title: __('折合USD数量'), sortable: true},
                        {field: 'token_contract_address', title: __('代币合约地址')},
                        {field: 'create_time', title: __('Create_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'update_time', title: __('Update_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
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