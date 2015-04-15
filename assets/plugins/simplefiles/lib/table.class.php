<?php
namespace SimpleFiles;
require_once (MODX_BASE_PATH . 'assets/lib/SimpleTab/table.abstract.php');

class sfData extends \SimpleTab\dataTable {
	/* @var autoTable $table */
	protected $table = 'sf_files';
	protected $pkName = 'sf_id';
    protected $indexName = 'sf_index';
    protected $rfName = 'sf_rid';
    protected $jsonFields = array(
        'sf_properties'
    );

    protected $default_field = array(
		'sf_file' => '',
		'sf_title' => '',
		'sf_description' => '',
		'sf_size' => 0,
		'sf_createdon' => '',
		'sf_index' => 0,
		'sf_isactive' => '1',
        'sf_type' => 'file',
        'sf_properties' => '',
		'sf_rid'=>0
		);

	/**
     * @param $ids
     * @param null $fire_events
     * @return mixed
     */
    public function deleteAll($ids, $rid, $fire_events = NULL) {
		$ids = $this->cleanIDs($ids, ',', array(0));
		if(empty($ids) || is_scalar($ids)) return false;
        $files = $this->query('SELECT `sf_file` FROM '.$this->makeTable($this->table).' WHERE `sf_id` IN ('.$this->sanitarIn($ids).')');
        $out = parent::deleteAll($ids, $rid, $fire_events);
        while ($row = $this->modx->db->getRow($files)) {
            $url = $this->fs->relativePath($row['sf_file']);
            if ($this->fs->checkFile($url)) {
                @unlink(MODX_BASE_PATH . $url);
                $dir = $this->fs->takeFileDir($url);
                $iterator = new \FilesystemIterator($dir);
                if (!$iterator->valid()) $this->fs->rmDir($dir);
            }
        }
		return $out;
	}

	public function save($fire_events = null, $clearCache = false) {
		if ($this->newDoc) {
			$rows = $this->modx->db->select('`sf_id`', $this->makeTable($this->table), '`sf_rid`='.$this->field['sf_rid']);
			$this->field['sf_index'] = $this->modx->db->getRecordCount($rows);
			$this->touch('sf_createdon');
		}
		return parent::save();
	}

    public function getFileIcon($file, $relativeUrl = false) {
        $folder = $this->params['iconsFolder'];
        $icon = $folder . strtolower($this->fs->takeFileExt($file)) . '.png';
        $icon = $this->fs->checkFile($icon) ? $icon : $folder . 'file.png';
        return $relativeUrl ? $icon : $this->modx->config['site_url'].$icon;
    }
}