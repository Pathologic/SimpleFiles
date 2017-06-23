<script type="text/javascript">
var sfConfig = {
    rid:[+id+],
    sfGridLoaded:false,
    maxFileSize:[+maxFileSize+],
    url:'[+url+]',
    allowedFiles:/[+allowedFiles+]$/
};
var sfGridColumns = [ [
    {
        field: 'sf_select',
        checkbox:true
    },
    {
        field:'sf_index',
        title: '#',
        sortable:true
    },
    {
        field:'sf_icon',
        title:'',
        sortable:false,
        width:24,
        fixed:true,
        align:'center',
        formatter: function(value) {
            return '<img src="'+value+'" width="16" height="16">';
        }
    },
    {
        field:'sf_file',
        title:_sfLang['file'],
        width:100,
        sortable:true,
        formatter: function(value) {
            return value.split('/').pop();
        },
        editor: {
            type: 'fileBrowser',
            options: {
                browserUrl: '[+kcfinder_url+]',
                opener: 'sfGrid',
                css: 'width:16px;height:16px;',
                cls: 'fa fa-folder fa-lg'
            }
        }
    },
    {
        field:'sf_title',
        title:_sfLang['title'],
        width:100,
        sortable:true,
        formatter: sfHelper.escape,
        editor:{
            type:'text'
        }
    },
    {
        field:'sf_description',
        title:_sfLang['description'],
        width:150,
        sortable:true,
        editor:{
            type:'textarea',
            options:{
                height:'40px'
            }
        },
        formatter: sfHelper.escape
    },
    {
        field:'sf_size',
        title:_sfLang['size'],
        align:'center',
        sortable:true,
        formatter:Handlebars.helpers.bytesToSize
    },
    {
        field:'sf_createdon',
        title:_sfLang['createdon'],
        align:'center',
        sortable:true,
        formatter:function(value) {
            sql = value.split(/[- :]/);
            d = new Date(sql[0], sql[1]-1, sql[2], sql[3], sql[4], sql[5]);
            year = d.getFullYear();
            month = d.getMonth()+1;
            day = d.getDate();
            hour = d.getHours();
            min = d.getMinutes();
            return ('0'+day).slice(-2) + '.' + ('0'+month).slice(-2) + '.' + year + '<br>' + ('0'+hour).slice(-2) + ':' + ('0'+min).slice(-2);
        }
    },
    {
        field:'sf_isactive',
        title:_sfLang['active'],
        align:'center',
        sortable:true,
        width:50,
        fixed:true,
        formatter:function(value){
            if (value == 1) {
                return _sfLang['yes'];
            }
            else {
                return '<span style="color:red;">'+_sfLang['no']+'</span>'
            }
        },
        editor:{
            type:'checkbox',
            options:{
                on: 1,
                off: 0
            }
        }
    },
    {
        field:'action',
        width:40,
        title:'',
        align:'center',
        fixed:true,
        formatter:function(value,row,index){
            if (row.editing){
                var save = '<a class="action save" href="javascript:void(0)" onclick="sfHelper.saverow('+index+')"><i class="fa fa-save fa-lg"></i></a> ';
                var cancel = '<a class="action cancel" href="javascript:void(0)" onclick="sfHelper.cancelrow('+index+')"><i class="fa fa-ban fa-lg"></i></a>';
                return save+cancel;
            } else {
                return '<a class="action delete" href="javascript:void(0)" onclick="sfHelper.deleteRow('+index+')" title="'+_sfLang['delete']+'"><i class="fa fa-trash fa-lg"></i></a>';
            }
        }
    }
] ];

(function($){
    $('#documentPane').on('click','#sf-tab',function(){
        if (sfConfig.sfGridLoaded) {
            $('#sfGrid').edatagrid('reload');
            $(window).trigger('resize');
        } else {
            sfHelper.init();
            sfConfig.sfGridLoaded = true;
        }
    });
    $(window).on('load', function(){
        if ($('#sf-tab')) {
            $('#sf-tab.selected').trigger('click');
        }
    });
    $(window).on('resize',function(){
        if ($('#sf-tab').hasClass('selected')) {
            clearTimeout(this.timeout);
            this.timeout = setTimeout(function () {
                $('#SimpleFiles').width($('body').width() - 60);
                if (sfConfig.sfGridLoaded) {
                    $('#sfGrid').datagrid('getPanel').panel('resize');
                }
            }, 300);
        }
    })
})(jQuery)
</script>
<div id="SimpleFiles" class="tab-page">
<h2 class="tab" id="sf-tab">[+tabName+]</h2>
</div>
