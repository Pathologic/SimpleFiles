<?php
namespace SimpleFiles;
require_once (MODX_BASE_PATH . 'assets/lib/MODxAPI/autoTable.abstract.php');
require_once (MODX_BASE_PATH . 'assets/lib/Helpers/FS.php');

class sfData extends \autoTable {
	/* @var autoTable $table */
	protected $table = 'sf_files';
	protected $pkName = 'sf_id';
    protected $jsonFields = array(
        'sf_properties'
    );

	public $_table = '';

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
	protected $params = array();
	protected $fs = null;

	public function __construct($modx, $debug = false) {
		parent::__construct($modx, $debug);
		$this->modx = $modx;
		$this->params = (isset($modx->event->params) && is_array($modx->event->params)) ? $modx->event->params : array();
		$this->fs = \Helpers\FS::getInstance();
		$this->_table['sf_files'] = $this->makeTable($this->table);
	}

	/**
     * @param $ids
     * @param null $fire_events
     * @return mixed
     */
    public function deleteAll($ids, $rid, $fire_events = NULL) {
		$ids = $this->cleanIDs($ids, ',', array(0));
		if(empty($ids) || is_scalar($ids)) return false;
		$ids = implode(',',$ids);
        $files = $this->query('SELECT `sf_file` FROM '.$this->_table['sf_files'].' WHERE `sf_id` IN ('.$this->sanitarIn($ids).')');
        $this->clearIndexes($ids,$rid);
        $out = $this->delete($ids, $fire_events);
        $this->query("ALTER TABLE {$this->_table['sf_files']} AUTO_INCREMENT = 1");
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

    private function clearIndexes($ids, $rid) {
        $rows = $this->query("SELECT MIN(`sf_index`) FROM {$this->_table['sf_files']} WHERE `sf_id` IN ({$ids})");
        $index = $this->modx->db->getValue($rows);
        $index = $index - 1;
        $this->query("SET @index := ".$index);
        $this->query("UPDATE {$this->_table['sf_files']} SET `sf_index` = (@index := @index + 1) WHERE (`sf_index`>{$index} AND `sf_rid`={$rid} AND `sf_id` NOT IN ({$ids})) ORDER BY `sf_index` ASC");
        $out = $this->modx->db->getAffectedRows();
        return $out;
    }

	public function reorder($source, $target, $point, $rid, $orderDir) {
		$rid = (int)$rid;
		$point = strtolower($point);
		$orderDir = strtolower($orderDir);
		$sourceIndex = (int)$source['sf_index'];
		$targetIndex = (int)$target['sf_index'];
		$sourceId = (int)$source['sf_id'];
		$rows = 0;
		/* more refactoring  needed */
		if ($target['sf_index'] < $source['sf_index']) {
			if (($point == 'top' && $orderDir == 'asc') || ($point == 'bottom' && $orderDir == 'desc')) {
				$rows = $this->modx->db->update('`sf_index`=`sf_index`+1',$this->_table['sf_files'],'`sf_index`>='.$targetIndex.' AND `sf_index`<'.$sourceIndex.' AND `sf_rid`='.$rid);
				$rows = $this->modx->db->update('`sf_index`='.$targetIndex,$this->_table['sf_files'],'`sf_id`='.$sourceId);				
			} elseif (($point == 'bottom' && $orderDir == 'asc') || ($point == 'top' && $orderDir == 'desc')) {
				$rows = $this->modx->db->update('`sf_index`=`sf_index`+1',$this->_table['sf_files'],'`sf_index`>'.$targetIndex.' AND `sf_index`<'.$sourceIndex.' AND `sf_rid`='.$rid);
				$rows = $this->modx->db->update('`sf_index`='.(1+$targetIndex),$this->_table['sf_files'],'`sf_id`='.$sourceId);				
			}
		} else {
			if (($point == 'bottom' && $orderDir == 'asc') || ($point == 'top' && $orderDir == 'desc')) {
				$rows = $this->modx->db->update('`sf_index`=`sf_index`-1',$this->_table['sf_files'],'`sf_index`<='.$targetIndex.' AND `sf_index`>'.$sourceIndex.' AND `sf_rid`='.$rid);
				$rows = $this->modx->db->update('`sf_index`='.$targetIndex,$this->_table['sf_files'],'`sf_id`='.(int)$source['sf_id']);				
			} elseif (($point == 'top' && $orderDir == 'asc') || ($point == 'bottom' && $orderDir == 'desc')) {
				$rows = $this->modx->db->update('`sf_index`=`sf_index`-1',$this->_table['sf_files'],'`sf_index`<'.$targetIndex.' AND `sf_index`>'.$sourceIndex.' AND `sf_rid`='.$rid);
				$rows = $this->modx->db->update('`sf_index`='.(-1+$targetIndex),$this->_table['sf_files'],'`sf_id`='.$sourceId);				
			}
		}
		
		return $rows;
	}

	public function save($fire_events = null, $clearCache = false) {
		if ($this->newDoc) {
			$rows = $this->modx->db->select('`sf_id`', $this->_table['sf_files'], '`sf_rid`='.$this->field['sf_rid']);
			$this->field['sf_index'] = $this->modx->db->getRecordCount($rows);
			$this->field['sf_createdon'] = date('Y-m-d H:i:s');
		}
		return parent::save();
	}

    /**
     * @param  string $name
     * @return string
     */
    public function stripName($name) {
        return $this->modx->stripAlias($name);
    }
}