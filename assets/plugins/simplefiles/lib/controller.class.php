<?php namespace SimpleFiles;

require_once(MODX_BASE_PATH . 'assets/lib/SimpleTab/controller.abstract.php');
require_once(MODX_BASE_PATH . 'assets/plugins/simplefiles/lib/table.class.php');

class sfController extends \SimpleTab\AbstractController {
    public $rfName='sf_rid';

    public function __construct(\DocumentParser $modx)
    {
        parent::__construct($modx);
        $this->rid = isset($_REQUEST[$this->rfName]) ? (int)$_REQUEST[$this->rfName] : 0;
        $defaults = array(
            'folder' => 'assets/storage/',
            'iconsFolder' => 'assets/snippets/simplefiles/icons/',
            'allowedFiles' => $modx->config['upload_files']
        );
        foreach ($defaults as $key => $value) if (!isset($this->params[$key])) $this->params[$key] = $value;
        $this->modx->event->params = $this->params;
        $this->data = new \SimpleFiles\sfData($modx);
    }

    /**
     * @return array
     */
    public function upload()
    {
        $out = array();
        include_once MODX_BASE_PATH . 'assets/plugins/simplefiles/lib/FileAPI.class.php';

        if (!empty($_SERVER['HTTP_ORIGIN'])) {
            // Enable CORS
            header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
            header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
            header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Range, Content-Disposition, Content-Type');
        }

        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            $this->isExit = true;
            return;
        }

        if (strtoupper($_SERVER['REQUEST_METHOD']) == 'POST') {
            $files = \FileAPI::getFiles(); // Retrieve File List
            $dir = $this->params['folder'] . $this->rid . "/";
            $flag = $this->FS->makeDir($dir, $this->modx->config['new_folder_permissions']);
            if (!$flag) {
                $this->modx->logEvent(0, 3, "Cannot create {$dir} .", 'SimpleFiles');
                die();
            }
            if ($files['sf_files']['error'] == UPLOAD_ERR_OK) {
                $tmp_name = $files["sf_files"]["tmp_name"];
                $name = $this->data->stripName($_FILES["sf_files"]["name"]);
                $name = $this->FS->getInexistantFilename($dir . $name, true);
                $ext = $this->FS->takeFileExt($name);
                if (in_array($ext, explode(',',$this->params['allowedFiles']))) {
                    if (@move_uploaded_file($tmp_name, $name)) {
                        $this->data->create(array(
                            'sf_file' => $this->FS->relativePath($name),
                            'sf_rid' => $this->rid,
                            'sf_type' => 'file',
                            'sf_properties' => json_encode(array(
                                'filename' => $this->FS->takeFileName($name),
                                'basename' => $this->FS->takeFileBasename($name),
                                'mime' => $this->FS->takeFileMIME($name),
                                'ext' => $this->FS->takeFileExt($name)
                            )),
                            'sf_title' => preg_replace('/\\.[^.\\s]{2,4}$/', '', $_FILES["sf_files"]["name"]),
                            'sf_size' => $this->FS->fileSize($name)
                            ))->save();
                    }
                } else {
                    $files['sf_files']['error'] = 101;
                }
            }

            //fetchImages($files, $images);
            $json = array(
                'data' => array('_REQUEST' => $_REQUEST, '_FILES' => $files)
            );

            // JSONP callback name
            $jsonp = isset($_REQUEST['callback']) ? trim($_REQUEST['callback']) : null;

            // Server response: "HTTP/1.1 200 OK"
            $this->isExit = true;
            $this->output = \FileAPI::makeResponse(array(
              'status' => \FileAPI::OK
            , 'statusText' => 'OK'
            , 'body' => $json
            ), $jsonp);
            return $out;
        }
    }

    public function remove()
    {
        $out = array();
        $ids = isset($_REQUEST['ids']) ? (string)$_REQUEST['ids'] : '';
        $ids = isset($_REQUEST['id']) ? (string)$_REQUEST['id'] : $ids;
        $out['success'] = false;
        if (!empty($ids)) {
            if ($this->data->deleteAll($ids, $this->rid)) {
                $out['success'] = true;
            }
        }
        return $out;
    }
    public function edit() {
		$out = array();
        $id = isset($_REQUEST['sf_id']) ? (int)$_REQUEST['sf_id'] : 0;
        if ($id) {
            if ($this->FS->checkFile($_REQUEST['sf_file']) && in_array($this->FS->takeFileExt($_REQUEST['sf_file']), explode(',',$this->params['allowedFiles']))) {
                $out = $this->data->edit($id)->toArray();
                if ($out['sf_file'] !== $_REQUEST['sf_file']) {
                    $dest = $this->params['folder'] . $this->rid . "/";
                    $name = $this->FS->takeFileBasename($_REQUEST['sf_file']);
                    $name = $this->FS->relativePath($this->FS->getInexistantFilename($dest . $name, true));
                    if ($this->FS->copyFile($_REQUEST['sf_file'], $name)) {
                        @unlink(MODX_BASE_PATH.$out['sf_file']);
                        $out['sf_file'] = $name;
                        $out['sf_size'] = $this->FS->fileSize($out['sf_file']);
                        $out['sf_icon'] = $this->data->getFileIcon($out['sf_file']);
                    }
                }
            }
			$out['sf_title'] = isset($_REQUEST['sf_title']) ? $_REQUEST['sf_title'] : $out['sf_title'];
			$out['sf_description'] = isset($_REQUEST['sf_description']) ? $_REQUEST['sf_description'] : $out['sf_description'];
        } else {
            die();
        }
        $this->data->fromArray($out)->save();
        return $out;
    }

    public function reorder()
    {
        $out = array();
        $source = $_REQUEST['source'];
        $target = $_REQUEST['target'];
        $point = $_REQUEST['point'];
        $orderDir = $_REQUEST['orderDir'];
        $rows = $this->data->reorder($source, $target, $point, $this->rid, $orderDir);

        if ($rows) {
            $out['success'] = true;
        } else {
            $out['success'] = false;
        }
        return $out;
    }

    public function listing()
    {
        $out = parent::listing();
        $data = json_decode($out,true);
        foreach ($data['rows'] as &$row)
            $row['sf_icon'] = $this->data->getFileIcon($row['sf_file']);
        return json_encode($data);
    }
}