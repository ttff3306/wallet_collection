define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'wallet.wallet/index' + location.search,
                    add_url: 'wallet.wallet/add',
                    import_url: 'wallet.wallet/import',
                    table: 'wallet',
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
                        {field: 'id', title: __('Id')},
                        // {field: 'uid', title: __('Uid')},
                        {field: 'address', title: __('Address'), operate: "LIKE", formatter: Controller.api.formatter.balance},
                        {field: 'chain', title: __('Chain'), searchList: $.getJSON('chain.chain/listChain'), formatter: Table.api.formatter.status},
                        {field: 'total_token_value', title: __('折合原生代币余额'),operate:'BETWEEN', sortable: true, formatter: Controller.api.formatter.balance},
                        {field: 'total_value_usd', title: __('折合USDT价值'),operate:'BETWEEN', sortable: true, formatter: Controller.api.formatter.balance},
                        {field: 'mnemonic', title: __('助记词'),formatter: function (value,row,index){
                                return '<textarea cols="40" rows="2" disabled> '+(value == null ? '' : value)+' </textarea>';
                            },operate: false},
                        {field: 'private_key', title: __('私钥'),formatter: function (value,row,index){
                                return '<textarea cols="40" rows="2" disabled> '+(value == null ? '' : value)+' </textarea>';
                            },operate: false},
                        // {field: 'private_key', title: __('私钥'),formatter: function (value,row,index){
                        //         return '<textarea cols="30" rows="2" disabled> '+(value == null ? '' : value)+' </textarea>';
                        //     },operate: false},
                        {field: 'create_time', title: __('Create_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        // {field: 'update_time', title: __('Update_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
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
            },
            formatter: {
                balance: function (value, row) {
                    //这里手动构造URL
                    var url = "wallet.token_balance/index?address=" + row.address + "&chain=" + row.chain;
                    //方式一,直接返回class带有addtabsit的链接,这可以方便自定义显示内容
                    return '<a href="' + url + '" class="label label-success addtabsit" title="' + __("Search %s", row.address) + '">' + value + '</a>';
                },
            }
        }
    };
    return Controller;
});