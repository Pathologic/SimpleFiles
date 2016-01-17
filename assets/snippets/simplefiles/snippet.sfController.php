<?php
include_once(MODX_BASE_PATH . 'assets/lib/APIHelpers.class.php');

$_prepare = explode(",", $prepare);
$prepare = array();
$prepare[] = \APIhelpers::getkey($modx->event->params, 'BeforePrepare', '');
$prepare = array_merge($prepare,$_prepare);
$prepare[] = 'DLsfController::prepare';
$prepare[] = \APIhelpers::getkey($modx->event->params, 'AfterPrepare', '');
$modx->event->params['prepare'] = trim(implode(",", $prepare), ',');

$params = array_merge(array(
    "controller"    =>  "sf_site_content",
    "dir"        =>  "assets/snippets/simplefiles/controller/"
), $modx->event->params);

if(!class_exists("DLsfController", false)){
    class DLsfController{
        public static function prepare(array $data = array(), DocumentParser $modx, $_DL, prepare_DL_Extender $_extDocLister){
            $iconsFolder = $_DL->getCfgDef('iconsFolder','assets/snippets/simplefiles/icons/');
            $wrapper='';
            if (isset($data['files'])) {
                foreach ($data['files'] as $file) {
                    $ph = $file;
                    $ph['fSize'] = $_DL->FS->fileSize($file['sf_file'],true);
                    $ph['mime'] = $_DL->FS->takeFileMIME($file['sf_file']);
                    $ph['ext'] = $_DL->FS->takeFileExt($file['sf_file']);
                    $icon = $iconsFolder.strtolower($ph['ext']).'.png';
                    $ph['icon'] = $_DL->FS->checkFile($icon) ? $icon : $iconsFolder.'file.png';
                    $ph['filename'] = $_DL->FS->takeFileName($file['sf_file']);
                    $ph['basename'] = $_DL->FS->takeFileBasename($file['sf_file']);
                    $ph['e.sf_title'] = htmlentities($file['sf_title'], ENT_COMPAT, 'UTF-8', false);
                    $ph['e.sf_description'] = htmlentities($file['sf_description'], ENT_COMPAT, 'UTF-8', false);
                    $wrapper .= $_DL->parseChunk($_DL->getCfgDef('sfRowTpl'), $ph);
                }
                $data['files'] = $_DL->parseChunk($_DL->getCfgDef('sfOuterTpl'),array('wrapper'=>$wrapper));
            }
            return $data;
        }            
    }
}

return $modx->runSnippet("DocLister", $params);
