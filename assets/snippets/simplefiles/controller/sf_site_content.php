<?php
if (!defined('MODX_BASE_PATH')) {
    die('HACK???');
}

include_once(MODX_BASE_PATH . 'assets/snippets/DocLister/core/controller/site_content.php');
class sf_site_contentDocLister extends site_contentDocLister
{
    public function getDocs($tvlist = '')
    {
        $docs = parent::getDocs($tvlist);

        $table = $this->getTable('sf_files');
        $rid = $this->modx->db->escape(implode(',',array_keys($docs)));
        $sfOrderBy = $this->modx->db->escape($this->getCFGDef('sfOrderBy','sf_index ASC'));

        $sfDisplay = $this->getCFGDef('sfDisplay','all');
        $sfAddWhereList = $this->modx->db->escape($this->getCFGDef('sfAddWhereList',''));

        if (!empty($sfAddWhereList)) $sfAddWhereList = ' AND ('.$sfAddWhereList.')';
        if (!empty($rid) && ($sfDisplay == 'all' || is_numeric($sfDisplay))) {
            switch ($sfDisplay) {
                case 'all':
                    $sql = "SELECT * FROM {$table} WHERE `sf_rid` IN ({$rid}) {$sfAddWhereList} ORDER BY {$sfOrderBy}";
                    break;
                case '1':
                    $sql = "SELECT * FROM (SELECT * FROM {$table} WHERE `sf_rid` IN ({$rid}) {$sfAddWhereList} ORDER BY {$sfOrderBy}) st GROUP BY sf_rid";
                    break;
                default:
                    $sql = "SELECT * FROM (SELECT *, @rn := IF(@prev = `sf_rid`, @rn + 1, 1) AS rn, @prev := `sf_rid` FROM {$table} JOIN (SELECT @prev := NULL, @rn := 0) AS vars WHERE `sf_rid` IN ({$rid}) ORDER BY sf_rid, {$sfOrderBy}) AS sf WHERE rn <= {$sfDisplay}";
                    break;
            }
            $files = $this->dbQuery($sql);
            while ($file = $this->modx->db->getRow($files)) {
                $_rid = $file['sf_rid'];
                $docs[$_rid]['files'][] = $file;
            }
        }
        $this->_docs = $docs;
        return $docs;
    }
}
