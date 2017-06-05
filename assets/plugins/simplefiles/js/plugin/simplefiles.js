var sfHelper = {};
(function($){
	sfHelper = {
        sourceRow: {},
        targetRow: {},
        point: '',
        init: function() {
            var workspace = $('#SimpleFiles');
            workspace.append('<div class="js-fileapi-wrapper"><div class="btn-left"><a href="javascript:void(0)" id="sfUploadBtn"></a></div><table id="sfGrid" width="100%"></table></div>');
            var uploaderOptions = {
                workspace:'#SimpleFiles',
                dndArea:'.js-fileapi-wrapper',
                uploadBtn:'#sfUploadBtn',
                url:sfConfig.url,
                imageAutoOrientation: false,
                data: {
                    mode:'upload',
                    sf_rid:sfConfig.rid
                },
                chunkSize: .5 * FileAPI.MB,
                chunkUploadRetry: 1,
                filterFn:function(file){
                    return sfConfig.allowedFiles.test(file.name.split('.').pop().toLowerCase());
                },
                completeCallback:function(){
                    $('#sfGrid').edatagrid('reload');
                }
            };
            var sfUploader = new EUIUploader(uploaderOptions);
            var sfGrid = new EUIGrid({
                url: sfConfig.url+'',
                destroyUrl: sfConfig.url+'?mode=remove&sf_rid='+sfConfig.rid,
                updateUrl: sfConfig.url+'?mode=edit',
                idField: 'sf_id',
                indexField: 'sf_index',
                sortName: 'sf_index',
                sortOrder: 'DESC',
                rid: sfConfig.rid,
                queryParams: {sf_rid: sfConfig.rid},
                columns: sfGridColumns
            }, '#sfGrid');
            var pager = sfGrid.datagrid('getPager');    // get the pager of datagrid
            pager.pagination({
                buttons:[
                    {
                        iconCls:'fa fa-trash fa-lg btn-extra',
                        handler:function(){sfHelper.deleteAll();}
                    }
                ]
            });
            $('.btn-extra').parent().parent().hide();
		},
        destroyWindow: function(wnd) {
            wnd.window('destroy',true);
            $('.window-shadow,.window-mask').remove();
            $('body').css('overflow','auto');
        },
		escape: function(str) {
			return str
			    .replace(/&/g, '&amp;')
			    .replace(/>/g, '&gt;')
			    .replace(/</g, '&lt;')
			    .replace(/"/g, '&quot;');
		},
        saverow: function (index) {
            $('#sfGrid').edatagrid('endEdit', index);
        },
        cancelrow: function (index) {
            $('#sfGrid').edatagrid('cancelEdit', index);
        },
        deleteRow: function (index) {
            $('#sfGrid').edatagrid('destroyRow', index);
        },
        getSelected: function() {
            var ids = [];
            var rows = $('#sfGrid').edatagrid('getChecked');
            if (rows.length) {
                $.each(rows, function(i, row) {
                    ids.push(row.sf_id);
                });
            }
            return ids;
        },
        deleteAll: function() {
            var ids = this.getSelected();
            $.messager.confirm(_sfLang['delete'],_sfLang['are_you_sure_to_delete_many'],function(r){
                if (r && ids.length > 0){
                    $.post(
                        sfConfig.url+'?mode=remove', 
                        {
                            ids:ids.join(),
                            sf_rid:sfConfig.rid
                        },
                        function(response) {
                            if(response.success) {
                                $('#sfGrid').edatagrid('reload');
                            } else {
                                $.messager.alert(_sfLang['error'],_sfLang['cannot_delete']);
                            }
                        },'json'
                    ).fail(function(xhr) {
                        $.messager.alert(_sfLang['error'],_sfLang['server_error']+xhr.status+' '+xhr.statusText,'error');
                    });
                }
            });
        }
	}
})(jQuery);
