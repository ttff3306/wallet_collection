define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'order.withdraw_order/index' + location.search,
                    edit_url: 'order.withdraw_order/edit',
                    multi_url: 'order.withdraw_order/multi',
                    table: 'withdraw_order',
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
                        {field: 'order_no', title: __('Order_no')},
                        {field: 'uid', title: __('Uid')},
                        {field: 'address', title: __('Address')},
                        {field: 'withdraw_money', title: __('Withdraw_money'), operate:'BETWEEN'},
                        {field: 'actual_withdraw_money', title: __('Actual_withdraw_money'), operate:'BETWEEN'},
                        {field: 'service_money', title: __('Service_money'), operate:'BETWEEN'},
                        {field: 'status', title: __('Status')},
                        {field: 'hash', title: __('Hash')},
                        {field: 'date_day', title: __('Date_day')},
                        {field: 'p_uid', title: __('P_uid')},
                        {field: 'chain', title: __('Chain')},
                        {field: 'is_auto', title: __('Is_auto')},
                        {field: 'pay_time', title: __('Pay_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'create_time', title: __('Create_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'update_time', title: __('Update_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
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