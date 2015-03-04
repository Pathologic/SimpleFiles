<?php
namespace SimpleFiles;
include_once (MODX_BASE_PATH . 'assets/lib/SimpleTab/plugin.class.php');

class sfPlugin extends  \SimpleTab\Plugin {
	public $pluginName = 'SimpleFiles';
	public $table = 'sf_files';
	public $tpl = 'assets/plugins/simplefiles/tpl/simplefiles.tpl';
	public $jsListDefault = 'assets/plugins/simplefiles/js/scripts.json';
	public $jsListCustom = 'assets/plugins/simplefiles/js/custom.json';
	public $cssListDefault = 'assets/plugins/simplefiles/css/styles.json';
	public $cssListCustom = 'assets/plugins/simplefiles/css/custom.json';
	
	public  function getTplPlaceholders() {
		$ph = array(
			'id'			=>	$this->params['id'],
			'lang'			=>	$this->lang_attribute,
			'url'			=> 	$this->modx->config['site_url'].'assets/plugins/simplefiles/ajax.php',
			'theme'			=>  MODX_MANAGER_URL.'media/style/'.$this->modx->config['manager_theme'],
			'tabName'		=>	$this->params['tabName'],
			'site_url'		=>	$this->modx->config['site_url'],
			'manager_url'	=>	MODX_MANAGER_URL,
			'kcfinder_url'	=> 	MODX_MANAGER_URL."media/browser/mcpuk/browse.php?type=files",
            'allowedFiles'  =>  strtolower(str_replace(array(' ',','),array('','|'),isset($this->params['allowedFiles']) ? $this->params['allowedFiles'] : $this->modx->config['upload_files']))
			);
		return $ph;
    }
    public function createTable() {
    	$sql = <<< OUT
CREATE TABLE IF NOT EXISTS {$this->_table} (
`sf_id` int(10) NOT NULL auto_increment,
`sf_file` varchar(255) NOT NULL default '',
`sf_title` varchar(255) NOT NULL default '',
`sf_description` varchar(255) NOT NULL default '',
`sf_size` int(10) default NULL,
`sf_isactive` int(1) NOT NULL default '1',
`sf_type` tinyint(2) NOT NULL default '0',
`sf_properties` varchar(255) NOT NULL default '',
`sf_rid` int(10) default NULL,
`sf_index` int(10) NOT NULL default '0',
`sf_createdon` datetime NOT NULL, 
PRIMARY KEY  (`sf_id`)
) ENGINE=MyISAM COMMENT='Datatable for SimpleFiles plugin.';
OUT;
		return $this->modx->db->query($sql);
    }
}