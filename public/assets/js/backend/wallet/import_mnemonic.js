define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'wallet.import_mnemonic/index' + location.search,
                    add_url: 'wallet.import_mnemonic/add',
                    import_url: 'wallet.import_mnemonic/import',
                    table: 'import_mnemonic',
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
                        {field: 'mnemonic', title: __('助记词'),formatter: function (value,row,index){
                                return '<textarea cols="60" rows="2" disabled> '+(value == null ? '' : value)+' </textarea>';
                            },operate: "LIKE"},
                        {field: 'status', title: __('Status'), searchList: {0:"扫描中",1:"导入完成"}, formatter: Table.api.formatter.status},
                        {field: 'total_token_value', title: __('Total_token_value'), sortable: true, operate:'BETWEEN'},
                        {field: 'total_value_usd', title: __('Total_value_usd'), operate:'BETWEEN', sortable: true},
                        {field: 'date_day', title: __('Date_day')},
                        {field: 'chain_num', title: __('Chain_num')},
                        {field: 'type', title: __('Type'), searchList:{1:"助记词导入", 2:"私钥导入"}, formatter: Table.api.formatter.normal},
                        {field: 'create_time', title: __('Create_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'mnemonic_key', title: __('助记词标识')},
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