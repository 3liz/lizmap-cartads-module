<?php

use \cartADS\dbClient as cartAdsDbClient;
use \cartADS\Util as cartAdsUtil;
use \cartADS\AdsCsApiClient as cartAdsApiClient;

class dossierCtrl extends jController {

    public function index() {
        $resp = $this->getResponse('json');

        $repo = $this->param('repository');
        $projectName = $this->param('project');
        $nomDossier = $this->param('nom');
        if (is_null($repo) || is_null($projectName) || is_null($dossierId)) {
            $resp->setHttpStatus('400', 'Bad Request');
            $resp->data = array(
                'code' => '400',
                'message' => 'Bad Request',
                'details' => 'repository, project, nom are mandatory',
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
        $apiClient = new cartAdsApiClient($repo, $projectName);
        $dossier = $apiClient->getDossier($nomDossier);
        if (!$dossier) {
            $resp->setHttpStatus('404', 'Not Found');
            $resp->data = array(
                'code' => '404',
                'message' => 'Not Found',
                'details' => 'Dossier `'.$nomDossier.'`non trouvé',
            );
            return $resp;
        }
        $result = cartAdsDbClient::updateDossiers($repo, $projectName, array($dossier));
        // dossier found
        $resp->data = $dossier;
        return $resp;
    }

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
            $resp->setHttpStatus('400', 'Bad Request');
            $resp->data = array(
                'code' => '400',
                'message' => 'Bad Request',
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
        $apiClient = new cartAdsApiClient($repo, $projectName);
        $dossiers = $apiClient->recherche($params);
        if (!$dossiers) {
            $resp->setHttpStatus('404', 'Not Found');
            $resp->data = array(
                'code' => '404',
                'message' => 'Not Found',
                'details' => 'La recherche n\'a retourné aucun dossier',
            );
            return $resp;
        }
        $result = cartAdsDbClient::updateDossiers($repo, $projectName, $dossiers);
        $resp->data = $dossiers;
        return $resp;
    }
}
