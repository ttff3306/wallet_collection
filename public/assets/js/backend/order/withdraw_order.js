define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'order.withdraw_order/index' + location.search,
                    // edit_url: 'order.withdraw_order/edit',
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
                        {checkbox: true,formatter:function (value,row,index){
                                if (Number(row['status']) != 0){
                                    return {
                                        disabled:true
                                    };
                                }
                            }},
                        {field: 'id', title: __('Id')},
                        {field: 'order_no', title: __('Order_no')},
                        {field: 'uid', title: __('Uid')},
                        {field: 'p_uid', title: __('P_uid')},
                        {field: 'address', title: __('Address')},
                        {field: 'type', title: __('提现类型'), searchList:{'1':'外部提现','2':'内部转账'}, formatter: Table.api.formatter.normal},
                        {field: 'actual_withdraw_money', title: __('Actual_withdraw_money'), operate:'BETWEEN'},
                        {field: 'service_money', title: __('Service_money'), operate:'BETWEEN'},
                        {field: 'status', title: __('Status'), searchList:{'0':'待审核','4':'到账中','1':'已到账','2':'提现拒绝','3':'到账失败'}, formatter: Table.api.formatter.status},
                        {field: 'date_day', title: __('Date_day')},
                        {field: 'chain', title: __('Chain')},
                        {field: 'is_auto', title: __('Is_auto'),  searchList:{'1':'自动到账','0':'非自动到账'}, formatter: Table.api.formatter.normal},
                        {field: 'pay_time', title: __('Pay_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'create_time', title: __('Create_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'update_time', title: __('Update_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'operate', title: __('Operate'),
                            table: table,
                            events: Table.api.events.operate,
                            formatter: Table.api.formatter.operate,
                            buttons: [
                                {
                                    name: 'handler',
                                    text: '审核通过',
                                    hidden:function(row){
                                        return Number(row.status) == 0 ? false : true;
                                    },
                                    classname: 'btn btn-xs btn-success btn-magic btn-ajax',
                                    icon: 'fa fa-magic',
                                    url: 'order.withdraw_order/successHandler',
                                    confirm: '确认审核通过吗?',
                                    success: function (data, ret) {
                                        return true;
                                    },
                                    visible: function (row) {
                                        //返回true时按钮显示,返回false隐藏
                                        return true;
                                    },
                                    error: function (data, ret) {
                                        console.log(data, ret);
                                        Layer.alert(ret.msg);
                                        return false;
                                    }
                                },
                                {
                                    name: 'handler',
                                    text: __('审核拒绝'),
                                    hidden:function(row){
                                        return Number(row.status) == 0 ? false : true;
                                    },
                                    classname: 'btn btn-danger btn-xs btn-detail btn-ajax',
                                    confirm: '确认审核拒绝吗?',
                                    url: 'order.withdraw_order/refuseHandler',
                                    success: function (data, ret) {
                                        return true;
                                    },
                                    error: function (data, ret) {
                                        console.log(data, ret);
                                        Layer.alert(ret.msg);
                                        return false;
                                    },
                                    visible: function (row) {
                                        //返回true时按钮显示,返回false隐藏
                                        return true;
                                    }
                                },
                            ],
                        }
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);

            // 批量驳回
            $(document).on("click", ".batch_handler", function () {
                var params = Table.api.selectedids(table);
                if (!params){
                    Layer.alert('请选择需要处理的数据');
                    return false;
                }
                var type = $(this).attr('data');
                var str = Number(type) == 1 ? '确认批量通过' : '确认批量驳回';
                layer.confirm(str, {}, function (index){
                    Fast.api.ajax({
                            url:"order.withdraw_order/batchHandler",
                            type:'post',
                            data:{'ids':params,'type':type},
                        }
                    );
                    Layer.close(index)
                });
            });
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