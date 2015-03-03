var sfHelper = {};
(function($){
	sfHelper = {
        sourceRow: {},
        targetRow: {},
        point: '',
        uploadErrors: 0,
        init: function() {
            Handlebars.registerHelper('stripText', function(str, len){
                return sfHelper.stripText(str, len);
            });
            Handlebars.registerHelper('bytesToSize', function(bytes){
                return sfHelper.bytesToSize(bytes);
            });
            Handlebars.registerHelper('ifCond', function(v1, v2, options) {
                if(v1 === v2) {
                    return options.fn(this);
                }
                return options.inverse(this);
            });
            var workspace = $('#SimpleFiles');
            workspace.append('<div class="js-fileapi-wrapper"><div class="btn"><div class="btn-text"><img src="'+sfConfig.theme+'/images/icons/folder_page_add.png">'+_sfLang['upload']+'</div><input id="sf_files" name="sf_files" class="btn-input" type="file" multiple /></div><table id="sfGrid" width="100%"></table></div>');
            workspace.fileapi({
                url: sfConfig.url+'?mode=upload',
                autoUpload: true,
                multiple: true,
                clearOnSelect: true,
                data: {
                    sf_rid:sfConfig.rid
                },
                filterFn: function (file) {
                    return sfConfig.allowedFiles.test(file.name.split('.').pop().toLowerCase());
                },
                onBeforeUpload: function(e,uiE) {
                    sfHelper.uploadErrors = 0;
                    var total = uiE.files.length;
                    var context = {
                        files: uiE.files,
                        sfLang: _sfLang,
                        modxTheme: sfConfig.theme
                    };
                    var uploadStateForm = $(Handlebars.templates.uploadForm(context));
                    uploadStateForm.window({
                        width:450,
                        modal:true,
                        title:_sfLang['files_upload'],
                        doSize:true,
                        collapsible:false,
                        minimizable:false,
                        maximizable:false,
                        resizable:false,
                        onOpen: function() {
                            $('body').css('overflow','hidden');
                            $('#sfProgress > span').html(_sfLang['uploaded']+' <span>'+sfConfig.sfFileId+'</span> '+_sfLang['from']+' '+total);
                            $('#sfUploadCancel').click(function(e){
                                uploadStateForm.window('close');
                            })
                        },
                        onClose: function() {
                            workspace.fileapi('abort');
                            sfHelper.destroyWindow(uploadStateForm);
                            $('#sfGrid').edatagrid('reload');
                        }
                    });
                },
                onProgress: function (e, uiE){
                    var part = uiE.loaded / uiE.total;
                    $('#sfProgress > div').css('width',100*part+'%');
                },
                onFilePrepare: function (e,uiE) {
                    sfConfig.sfFileId++;
                },
                onFileProgress: function (e,uiE) {
                    var part = uiE.loaded / uiE.total;
                    $('.progress','#sfFilesListRow'+(sfConfig.sfFileId-1)).text(Math.floor(100*part)+'%');
                },
                onFileComplete: function(e,uiE) {
                    var errorCode = 101;
                    if (uiE.result.data !== undefined) {
                        errorCode = parseInt(uiE.result.data._FILES.sf_files.error);
                    }
                    if (errorCode) {
                        $('.progress','#sfFilesListRow'+(sfConfig.sfFileId-1)).html('<img src="'+sfConfig.theme+'/images/icons/error.png'+'" title="'+_sfUploadResult[errorCode]+'">');
                        sfHelper.uploadErrors = 1;
                    }
                    $('#sfProgress > span > span').text(sfConfig.sfFileId);
                },
                onComplete: function(e,uiE) {
                    sfConfig.sfFileId = 0;
                    var btn = $('#sf_files');
                    btn.replaceWith(btn.val('').clone(true));
                    e.widget.files = [];
                    e.widget.uploaded = [];
                    $('#sfGrid').edatagrid('reload');
                    $('#sfUploadCancel span').text(_sfLang['close']);
                    if (!uiE.error && !sfHelper.uploadErrors) $('#sfUploadCancel').trigger('click');
                },
                elements: {
                    dnd: {
                        el: '.js-fileapi-wrapper',
                        hover: 'dnd_hover'
                    }
                }
            });
            $('#sfGrid').edatagrid({
                url: sfConfig.url+'',
                singleSelect: false,
                checkOnSelect:false,
                destroyUrl: sfConfig.url+'?mode=remove&sf_rid='+sfConfig.rid,
                updateUrl: sfConfig.url+'?mode=edit',
                destroyMsg: {
                    confirm: {   // when select a row
                        title: _sfLang['delete'],
                        msg: _sfLang['are_you_sure_to_delete']
                    }
                },
                pagination: true,
                pageList:[50,100,150,200],
                fitColumns: true,
                striped: true,
                idField: 'sf_id',
                scrollbarSize: 0,
                sortName: 'sf_index',
                sortOrder: 'DESC',
                queryParams: {sf_rid: sfConfig.rid},
                onBeforeLoad: function() {
                    $(this).edatagrid('clearChecked');
                    $('.btn-extra').parent().parent().hide();
                },
                onLoadSuccess: function () {
                    $(this).edatagrid('enableDnd');
                },
                onSortColumn: function (sort, order) {
                    sfConfig.sfOrderBy = sort;
                    sfConfig.sfOrderDir = order;
                },
                onDestroy: function () {
                    $(this).edatagrid('reload');
                },
                onBeforeDrag: function (row) {
                    if (sfConfig.sfOrderBy == 'sf_index' && !row.editing) {
                        $('body').css('overflow-x', 'hidden');
                        $('.datagrid-body').css('overflow-y', 'hidden');
                    } else {
                        return false;
                    }
                },
                onBeforeDrop: function (targetRow, sourceRow, point) {
                    $('body').css('overflow-x', 'auto');
                    $('.datagrid-body').css('overflow-y', 'auto');
                    this.targetRow = targetRow;
                    this.targetRow.index = tgt = $('#sfGrid').edatagrid('getRowIndex', targetRow);
                    this.sourceRow = sourceRow;
                    this.sourceRow.index = src = $('#sfGrid').edatagrid('getRowIndex', sourceRow);
                    this.point = point;
                    dif = tgt - src;
                    if ((point == 'bottom' && dif == -1) || (point == 'top' && dif == 1)) return false;
                },
                onDrop: function (targetRow, sourceRow, point) {
                    src = this.sourceRow.index;
                    tgt = this.targetRow.index;
                    $.ajax({
                        url: sfConfig.url+'?mode=reorder',
                        type: 'post',
                        data: {
                            'target': {
                                'sf_id': targetRow.sf_id,
                                'sf_index': targetRow.sf_index
                            },
                            'source': {
                                'sf_id': sourceRow.sf_id,
                                'sf_index': sourceRow.sf_index
                            },
                            'point': point,
                            'sf_rid': sfConfig.rid,
                            'orderDir': sfConfig.sfOrderDir
                        }
                    }).done(function (response) {
                        if (sfHelper.isValidJSON(response)) {
                            response = $.parseJSON(response);
                            if (!response.success) {
                                $.messager.alert(_sfLang['error'], _sfLang['save_fail']);
                                $('#sfGrid').edatagrid('reload');
                            } else {
                                rows = $('#sfGrid').edatagrid('getRows');
                                if (tgt < src) {
                                    rows[tgt].sf_index = targetRow.sf_index;
                                    for (var i = tgt; i <= src; i++) {
                                        rows[i].sf_index = rows[i - 1] != undefined ? rows[i - 1].sf_index - (sfConfig.sfOrderDir == 'desc' ? 1 : -1) : rows[i].sf_index;
                                        $('#sfGrid').edatagrid('refreshRow', i);
                                    }
                                } else {
                                    rows[tgt].sf_index = targetRow.sf_index;
                                    for (var i = tgt; i >= src; i--) {
                                        rows[i].sf_index = rows[i + 1] != undefined ? parseInt(rows[i + 1].sf_index) + (sfConfig.sfOrderDir == 'desc' ? 1 : -1) : rows[i].sf_index;
                                        $('#sfGrid').edatagrid('refreshRow', i);
                                    }
                                }
                            }
                        }
                    })
                },
                onBeforeEdit: function (index, row) {
                    row.editing = true;
                    sfHelper.updateActions(index);
                },
                onAfterEdit: function (index, row) {
                    row.editing = false;
                    sfHelper.updateActions(index);
                },
                onCancelEdit: function (index, row) {
                    row.editing = false;
                    sfHelper.updateActions(index);
                },
                onClickRow: function (row) {
                    row.editing = false;
                    $('#sfGrid').edatagrid('cancelEdit', row);
                },
                onSelect: function (rowIndex) {
                    $('#sfGrid').edatagrid('unselectRow', rowIndex);
                },
                onCheck: function(rowIndex) {
                    $('#sfGrid').edatagrid('unselectRow', rowIndex);
                    $('.btn-extra').parent().parent().show();
                },
                onUncheck: function() {
                    var rows = $('#sfGrid').edatagrid('getChecked');
                    if (!rows.length) $('.btn-extra').parent().parent().hide();
                },
                onCheckAll: function() {
                    $('#sfGrid').edatagrid('unselectAll');
                    $('.btn-extra').parent().parent().show();
                },
                onUncheckAll: function() {
                    $('.btn-extra').parent().parent().hide();
                },
                columns: sfGridColumns
            });
            var pager = $('#sfGrid').datagrid('getPager');    // get the pager of datagrid
            pager.pagination({
                buttons:[
                    {
                        iconCls:'btn-deleteAll btn-extra',
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
        getData: function(data) {
            if (sfHelper.isValidJSON(data)) {
                data = $.parseJSON(data);
                if (data.rows !== undefined) data.success = true;
            } else {
                data = {
                    success:false
                }
            }
            return data;
        },
		bytesToSize: function(bytes) {
		   if(bytes == 0) return '0 B';
		   var k = 1024;
		   var sizes = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
		   var i = Math.floor(Math.log(bytes) / Math.log(k));
		   return (bytes / Math.pow(k, i)).toFixed(2) + ' ' + sizes[i];
		},
		stripText: function(str,len) {
			str.replace(/<\/?[^>]+>/gi, '');
			if (str.length > len) str = str.slice(0,len) + '...';
			return str;
		},
		escape: function(str) {
			return str
			    .replace(/&/g, '&amp;')
			    .replace(/>/g, '&gt;')
			    .replace(/</g, '&lt;')
			    .replace(/"/g, '&quot;');
		},
		isValidJSON: function(src) {
		    var filtered = src;
		    filtered = filtered.replace(/\\["\\\/bfnrtu]/g, '@');
		    filtered = filtered.replace(/"[^"\\\n\r]*"|true|false|null|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?/g, ']');
		    filtered = filtered.replace(/(?:^|:|,)(?:\s*\[)+/g, '');
		    return (/^[\],:{}\s]*$/.test(filtered));
		},
        formatTime: function (seconds) {
            if (seconds == 0) return;
            time = new Date(0, 0, 0, 0, 0, seconds, 0);

            hh = time.getHours();
            mm = time.getMinutes();
            ss = time.getSeconds()

            output = '';
            if (hh != 0) {
                hh = ('0' + hh).slice(-2);
                output = hh + ':';
            }
            mm = ('0' + mm).slice(-2);
            output += mm + ':';
            output += ('0' + ss).slice(-2);
            return output;
        },
        updateActions: function (index) {
            $('#sfGrid').edatagrid('updateRow', {
                index: index,
                row: {}
            });
        },
        saverow: function (target) {
            $('#sfGrid').edatagrid('endEdit', this.getRowIndex(target));
        },
        cancelrow: function (target) {
            $('#sfGrid').edatagrid('cancelEdit', this.getRowIndex(target));
        },
        deleteRow: function (target) {
            $('#sfGrid').edatagrid('destroyRow', this.getRowIndex(target));
        },
        getRowIndex: function (target) {
            var tr = $(target).closest('tr.datagrid-row');
            return parseInt(tr.attr('datagrid-row-index'));
        },
        getSelected: function() {
            var ids = [];
            var rows = $('#sfGrid').edatagrid('getChecked');
            if (rows.length) {
                $.each(rows, function(i, row) {
                    ids.push(row.sf_id);
                });
            }
            ids = ids.join();
            return ids;
        },
        deleteAll: function() {
            var ids = this.getSelected();
            $.messager.confirm(_sfLang['delete'],_sfLang['are_you_sure_to_delete_many'],function(r){
                if (r){
                    $.post(
                        sfConfig.url+'?mode=remove', 
                        {
                            ids:ids,
                            sf_rid:sfConfig.rid
                        },
                        function(data) {
                            if (sfHelper.isValidJSON(data)) data=$.parseJSON(data);
                            if(data.success) {
                                $('#sfGrid').edatagrid('reload');
                            } else {
                                $.messager.alert(_sfLang['error'],_sfLang['cannot_delete']);
                            }
                        }
                    );
                }
            });
        }
	}
})(jQuery);