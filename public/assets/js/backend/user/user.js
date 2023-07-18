define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'user.user/index',
                    add_url: 'user.user/add',
                    edit_url: 'user.user/edit',
                    multi_url: 'user.user/multi',
                    table: 'user',
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
                        {field: 'id', title: __('Id'), sortable: true},
                        {field: 'uuid', title: __('UUID'), sortable: true},
                        {
                            field: 'avatar',
                            title: __('Avatar'),
                            events: Table.api.events.image,
                            formatter: Table.api.formatter.image,
                            operate: false
                        },
                        {field: 'username', title: __('Username'), operate: 'LIKE'},
                        {field: 'nickname', title: __('Nickname'), operate: 'LIKE'},
                        {field: 'usdt', title: __('USDT余额'), operate: 'LIKE', sortable: true},
                        {field: 'usdk', title: __('USDK余额'), operate: 'LIKE', sortable: true},
                        {field: 'level', title: __('Level'), operate: 'BETWEEN', sortable: true},
                        {field: 'p_uid', title: __('直推人id')},
                        {field: 'p_uid2', title: __('间推人id')},
                        {field: 'invite_code', title: __('推广码')},
                        {
                            field: 'createtime',
                            title: __('注册时间'),
                            formatter: Table.api.formatter.datetime,
                            operate: 'RANGE',
                            addclass: 'datetimerange',
                            sortable: true
                        },
                        {
                            field: 'logintime',
                            title: __('Logintime'),
                            formatter: Table.api.formatter.datetime,
                            operate: 'RANGE',
                            addclass: 'datetimerange',
                            sortable: true
                        },
                        {field: 'loginip', title: __('Loginip'), formatter: Table.api.formatter.search},
                        {
                            field: 'jointime',
                            title: __('Jointime'),
                            formatter: Table.api.formatter.datetime,
                            operate: 'RANGE',
                            addclass: 'datetimerange',
                            sortable: true
                        },
                        {field: 'joinip', title: __('Joinip'), formatter: Table.api.formatter.search},
                        {
                            field: 'status',
                            title: __('Status'),
                            formatter: Table.api.formatter.status,
                            searchList: {normal: __('Normal'), hidden: __('Hidden')}
                        },
                        {
                            field: 'operate',
                            title: __('Operate'),
                            table: table,
                            events: Table.api.events.operate,
                            formatter: Table.api.formatter.operate
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
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});