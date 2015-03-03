<style type="text/css">
    .btn-deleteAll {
        background: url([+theme+]/images/icons/trash.png) -2px center no-repeat;
    }
</style>
<script type="text/javascript">
var sfConfig = {
    rid:[+id+],
    theme:'[+theme+]',
    sfGridLoaded:false,
    sfOrderBy:'sf_index',
    sfOrderDir:'desc',
    sfFileId:0,
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
        field:'sf_id',
        hidden:true
    },
    {
        field:'sf_icon',
        title:'',
        sortable:false,
        width:20,
        fixed:true,
        align:'center',
        formatter: function(value) {
            return '<img src="'+value+'" width="16" height="16">';
        }
    },
    {
        field:'sf_file',
        title:_sfLang['file'],
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
                icon: '[+theme+]/images/icons/folder_add.png'
            }
        }
    },
    {
        field:'sf_title',
        title:_sfLang['title'],
        width:200,
        sortable:true,
        formatter: function(value) {
            return value
                    .replace(/&/g, '&amp;')
                    .replace(/>/g, '&gt;')
                    .replace(/</g, '&lt;')
                    .replace(/"/g, '&quot;');
        },
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
            type:'textarea'
        }
    },
    {
        field:'sf_size',
        title:_sfLang['size'],
        align:'center',
        sortable:true,
        formatter:function(value,row,index){
            return sfHelper.bytesToSize(value);
        }
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
        formatter:function(value,row){
            if (row.editing){
                var save = '<a href="javascript:void(0)" onclick="sfHelper.saverow(this)"><img src="[+theme+]/images/icons/save.png"></a> ';
                var cancel = '<a href="javascript:void(0)" onclick="sfHelper.cancelrow(this)"><img src="[+theme+]/images/icons/delete.png"></a>';
                return save+cancel;
            } else {
                return '<a href="javascript:void(0)" onclick="sfHelper.deleteRow(this)" title="'+_sfLang['delete']+'"><img src="[+theme+]/images/icons/trash.png"></a>';
            }
        }
    }
] ];
(function($){
$(window).load(function(){
    if ($('#sf-tab')) {
    $('#sf-tab.selected').trigger('click');    
}
});
$(document).ready(function(){
$('#sf-tab').click(function(){
    if (sfConfig.sfGridLoaded) {
        $('#sfGrid').edatagrid('reload');
    } else {
        sfHelper.init();
        sfConfig.sfGridLoaded = true;
        $(window).trigger('resize'); //stupid hack
    }
})
})
})(jQuery)
</script>
<div id="SimpleFiles" class="tab-page" style="width:100%;-moz-box-sizing: border-box; box-sizing: border-box;">
<h2 class="tab" id="sf-tab">[+tabName+]</h2>
</div>