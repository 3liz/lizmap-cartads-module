<?php

use \cartADS\dbClient as cartAdsDbClient;
use \cartADS\Util as cartAdsUtil;
use \cartADS\StatADSAPIClient;

class dossierCtrl extends jController {

    public function recherche() {
        $resp = $this->getResponse('json');

        $repo = $this->param('repository');
        $projectName = $this->param('project');
        if (is_null($repo) || is_null($projectName)) {
            $resp->setHttpStatus('404', 'Not Found');
            $resp->data = array(
                'code' => '404',
                'message' => 'Missing parameters',
                'details' => 'repository, project are mandatory',
            );

            return $resp;
        }

        $params = array_merge(array(), $this->params());

        unset($params['repository']);
        unset($params['project']);
        unset($params['module']);
        unset($params['action']);
        unset($params['ctrl']);

        if (count($params) == 0) {
            $resp->setHttpStatus('404', 'Not Found');
            $resp->data = array(
                'code' => '404',
                'message' => 'Missing parameters',
                'details' => 'At least one parameter is mandatory',
            );
            return $resp;
        }

        $testCartADSProject = cartAdsUtil::projectIsCartADS($repo, $projectName);
        if ($testCartADSProject != cartAdsUtil::PROJECT_OK) {
            if ($testCartADSProject == cartAdsUtil::ERR_CODE_PROJECT_VARIABLE) {
                $message = 'Missing project variable';
            } else {
                $message = 'Project is not a cartADS project';
            }
            $resp->setHttpStatus('500', 'Internal Server Error');
            $resp->data = array(
                'code' => '500',
                'message' => 'Internal Server Error',
                'details' => $message,
            );
            return $resp;
        }
        $apiClient = new StatADSAPIClient($repo, $projectName);
        $dossiers = $apiClient->recherche($params);
        $resp->data = $dossiers;
        return $resp;
    }
}
