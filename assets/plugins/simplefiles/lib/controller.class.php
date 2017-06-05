<?php namespace SimpleFiles;

require_once(MODX_BASE_PATH . 'assets/lib/SimpleTab/controller.abstract.php');
require_once(MODX_BASE_PATH . 'assets/plugins/simplefiles/lib/table.class.php');

class sfController extends \SimpleTab\AbstractController {
    public $rfName='sf_rid';

    public function __construct(\DocumentParser $modx)
    {
        parent::__construct($modx);
        $defaults = array(
            'folder' => 'assets/storage/',
            'iconsFolder' => 'assets/snippets/simplefiles/icons/',
            'allowedFiles' => $modx->config['upload_files']
        );
        foreach ($defaults as $key => $value) if (!isset($this->params[$key]) || empty($this->params[$key])) $this->params[$key] = $value;
        $this->modx->event->params = $this->params;
        $this->data = new \SimpleFiles\sfData($modx);
        $this->dlInit();
        $this->dlParams['dateSource'] = 'date';
    }

    /**
     * @return array
     */
    public function upload()
    {
        $errorCode = 0;
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
            $dir = $this->params['folder'] . $this->rid . "/";
            $flag = $this->FS->makeDir($dir, $this->modx->config['new_folder_permissions']);
            if (!$flag) {
                $this->modx->logEvent(0, 3, "Cannot create {$dir} .", 'SimpleFiles');
                die();
            }

            $uploadDir = $this->params['folder'].'upload/'.$this->rid .'/';
            $this->FS->makeDir($uploadDir, $this->modx->config['new_folder_permissions']);
            $filename = end(explode('filename=',$_SERVER['HTTP_CONTENT_DISPOSITION']));
            $content_range_header = $_SERVER['HTTP_CONTENT_RANGE'];
            $content_range = $content_range_header ? preg_split('/[^0-9]+/', $content_range_header) : null;
            $size =  $content_range ? $content_range[3] : null;
            $partfile = MODX_BASE_PATH . $uploadDir.$filename.'.part';
            $in = @fopen("php://input", "rb");
            if (!$content_range[1] && $this->FS->fileSize($partfile) > $content_range[2]) {
                $out = @fopen($partfile,"wb");
            } else {
                $out = fopen($partfile, "ab");
            }
            while ($buff = fread($in, 4096)) {
                @fwrite($out, $buff);
            }
            @fclose($out);
            @fclose($in);
            if ($size && $size == $this->FS->fileSize($partfile)) {
                $name = $this->data->stripName(urldecode($filename));
                $name = $this->FS->getInexistantFilename($dir . $name, true);
                $ext = end(explode('.',$name));
                if (in_array($ext, explode(',',$this->params['allowedFiles']))) {
                    if ($this->FS->moveFile($partfile, $name)) {
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
                            'sf_title' => preg_replace('/\\.[^.\\s]{2,4}$/', '', urldecode($filename)),
                            'sf_size' => $size
                        ))->save();
                    } else {
                        $errorCode = 101;
                    }
                } else {
                    $errorCode = 7;
                }
                $this->FS->rmDir($this->params['folder'].'upload/'.$this->rid);
            }

            // Server response: "HTTP/1.1 200 OK"
            $this->isExit = true;
            $this->output = json_encode(array(
                'success' => !$errorCode,
                'message' => $errorCode
            ));
            return;
        }
    }

    public function edit() {
		$out = array();
        $id = isset($_POST['sf_id']) ? (int)$_POST['sf_id'] : 0;
        if ($id) {
            if ($this->FS->checkFile($_POST['sf_file']) && in_array($this->FS->takeFileExt($_POST['sf_file']), explode(',',$this->params['allowedFiles']))) {
                $out = $this->data->edit($id)->toArray();
                if ($out['sf_file'] !== $_POST['sf_file']) {
                    $dest = $this->params['folder'] . $this->rid . "/";
                    $name = $this->FS->takeFileBasename($_POST['sf_file']);
                    $name = $this->FS->relativePath($this->FS->getInexistantFilename($dest . $name, true));
                    if ($this->FS->copyFile($_POST['sf_file'], $name)) {
                        @unlink(MODX_BASE_PATH.$out['sf_file']);
                        $out['sf_file'] = $name;
                        $out['sf_size'] = $this->FS->fileSize($out['sf_file']);
                        $out['sf_icon'] = $this->data->getFileIcon($out['sf_file']);
                    }
                }
            }
			$out['sf_title'] = isset($_POST['sf_title']) ? $_POST['sf_title'] : $out['sf_title'];
			$out['sf_description'] = isset($_POST['sf_description']) ? $_POST['sf_description'] : $out['sf_description'];
			$out['sf_isactive'] = isset($_POST['sf_isactive']) ? $_POST['sf_isactive'] : $out['sf_isactive'];
        } else {
            die();
        }
        $this->data->fromArray($out)->save();
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
