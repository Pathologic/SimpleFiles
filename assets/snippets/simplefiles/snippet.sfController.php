<?php
$params = is_array($params) ? $params : array();

$params['dir'] = 'assets/snippets/simplefiles/controller/';
$params['controller'] = 'sf_site_content';

return $modx->runSnippet("DocLister", $params);