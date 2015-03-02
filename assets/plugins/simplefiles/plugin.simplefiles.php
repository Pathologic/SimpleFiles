<?php
if (IN_MANAGER_MODE != 'true') die();
$e = &$modx->event;
if ($e->name == 'OnDocFormRender' && $id) {
    include_once(MODX_BASE_PATH . 'assets/plugins/simplefiles/lib/plugin.class.php');
    global $modx_lang_attribute;
    $plugin = new \SimpleFiles\sfPlugin($modx, $modx_lang_attribute);
    $output = $plugin->render();
    if ($output) $e->output($output);
}
if ($e->name == 'OnEmptyTrash') {
    if (empty($ids)) return;
    include_once(MODX_BASE_PATH . 'assets/plugins/simplefiles/lib/plugin.class.php');
    $plugin = new \SimpleFiles\sfPlugin($modx);
    $where = implode(',', $ids);
    $modx->db->delete($plugin->_table, "`sf_rid` IN ($where)");
    $plugin->clearFolders($ids, MODX_BASE_PATH . $e->params['folder']);
    $sql = "ALTER TABLE {$plugin->_table} AUTO_INCREMENT = 1";
    $rows = $modx->db->query($sql);
}
