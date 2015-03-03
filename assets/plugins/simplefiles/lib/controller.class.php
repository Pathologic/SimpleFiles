<?php namespace SimpleFiles;

require_once(MODX_BASE_PATH . 'assets/lib/SimpleTab/controller.abstract.php');
require_once(MODX_BASE_PATH . 'assets/plugins/simplefiles/lib/table.class.php');

class sfController extends \SimpleTab\AbstractController {
    public function __construct(\DocumentParser $modx)
    {
        parent::__construct($modx);
        $this->data = new \SimpleFiles\sfData($modx);
        $this->ridField = 'sf_rid';
        $this->rid = isset($_REQUEST[$this->ridField]) ? (int)$_REQUEST[$this->ridField] : 0;
        $defaults = array(
            'folder' => 'assets/files/storage/',
            'iconsFolder' => 'assets/snippets/simplefiles/icons/',
            'allowedFiles' => $modx->config['upload_files']
        );
        foreach ($defaults as $key => $value) if (!isset($this->params[$key])) $this->params[$key] = $value;
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
        $id = isset($_REQUEST['sf_id']) ? (int)$_REQUEST['sf_id'] : 0;
        if ($id) {
            if ($this->FS->checkFile($_REQUEST['sf_file']) && in_array($this->FS->takeFileExt($_REQUEST['sf_file']), explode(',',$this->params['allowedFiles']))) {
                $out = $this->data->edit($id)->toArray();
                $dest = $this->params['folder'] . $this->rid . "/";
                $name = $this->FS->takeFileBasename($_REQUEST['sf_file']);
                $name = $this->FS->getInexistantFilename($dest . $name, true);
                if ($this->FS->copyFile($_REQUEST['sf_file'],$dest.$name)) {
                    $out['sf_file'] = $dest.$name;
                    //TODO: icon refactor
                    $icon = $this->params['iconsFolder'].strtolower($this->FS->takeFileExt($out['sf_file'])).'.png';
                    $out['sf_icon'] = $this->modx->config['site_url'].($this->FS->checkFile($icon) ? $icon : $this->params['iconsFolder'].'file.png');
                }
            }
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
        foreach ($data['rows'] as &$row) {
            $icon = $this->params['iconsFolder'].strtolower($this->FS->takeFileExt($row['sf_file'])).'.png';
            $row['sf_icon'] = $this->modx->config['site_url'].($this->FS->checkFile($icon) ? $icon : $this->params['iconsFolder'].'file.png');
        }
        return json_encode($data);
    }
}