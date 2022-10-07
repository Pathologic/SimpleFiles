<?php
/**
 * sfLister
 * 
 * DocLister wrapper for SimpleFiles table
 *
 * @category    snippet
 * @version     1.0.0
 * @license     http://www.gnu.org/copyleft/gpl.html GNU Public License (GPL)
 * @internal    @properties 
 * @internal    @modx_category Content
 * @author      Pathologic (m@xim.name)
 */

include_once(MODX_BASE_PATH . 'assets/lib/APIHelpers.class.php');

$prepare = \APIhelpers::getkey($modx->event->params, 'BeforePrepare', '');
$prepare = explode(",", $prepare);
$prepare[] = 'DLsfLister::prepare';
$prepare[] = \APIhelpers::getkey($modx->event->params, 'AfterPrepare', '');
$modx->event->params['prepare'] = trim(implode(",", $prepare), ',');

$params = array_merge(array(
    "controller"    =>  "onetable",
    "config"        =>  "sfLister:assets/snippets/simplefiles/config/"
), $modx->event->params, array(
    'depth' => '0',
    'showParent' => '-1'
));

if(!class_exists("DLsfLister", false)){
    class DLsfLister{
        public static function prepare(array $data, DocumentParser $modx, $_DL, prepare_DL_Extender $_extDocLister){
            $iconsFolder = $_DL->getCfgDef('iconsFolder','assets/snippets/simplefiles/icons');
            $data['fSize'] = $_DL->FS->fileSize($data['sf_file'],true);
            $data['mime'] = $_DL->FS->takeFileMIME($data['sf_file']);
            $data['ext'] = $_DL->FS->takeFileExt($data['sf_file']);
            $icon = $iconsFolder.strtolower($data['ext']).'.png';
            $data['icon'] = $_DL->FS->checkFile($icon) ? $icon : $iconsFolder.'file.png';
            $data['filename'] = $_DL->FS->takeFileName($data['sf_file']);
            $data['basename'] = $_DL->FS->takeFileBasename($data['sf_file']);
            $data['e.sf_title'] = htmlentities($data['sf_title'], ENT_COMPAT, 'UTF-8', false);
            $data['e.sf_description'] = htmlentities($data['sf_description'], ENT_COMPAT, 'UTF-8', false);
            return $data;
        }            
    }
}
return $modx->runSnippet('DocLister', $params);
?>
