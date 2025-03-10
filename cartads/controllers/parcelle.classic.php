<?php

use \cartADS\dbClient as cartAdsDbClient;
use \cartADS\Util as cartAdsUtil;
use \cartADS\StatADSAPIClient;

class parcelleCtrl extends jController {

    public function dossiers() {
        $resp = $this->getResponse('json');

        $repo = $this->param('repository');
        $projectName = $this->param('project');
        $parcelleId = $this->param('parcelle_id');
        if (is_null($repo) || is_null($projectName) || is_null($parcelleId)) {
            $resp->setHttpStatus('404');
            $resp->data = array(
                'code' => '404',
                'message' => 'Missing parameters',
                'details' => 'repository, project, parcelle_id are mandatory',
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
        $dossiers = $apiClient->getDossiers($parcelleId);
        $resp->data = $dossiers;
        return $resp;
    }

    public function charge() {
        $repo = $this->param('repository');
        $projectName = $this->param('project');
        // parcelles[]=31 AB 4&parcelles[]=31 AB 5
        $parcelles = $this->param('parcelles');

        $resp = $this->getResponse('text');
        if (is_null($repo) || is_null($projectName) || is_null($parcelles)) {
            $resp->setHttpStatus('404', 'Missing parameters');
            $resp->data = 'repository, project, parcelles[] are mandatory';
            return $resp;
        }
        $testCartADSProject = cartAdsUtil::projectIsCartADS($repo, $projectName);
        if ($testCartADSProject != cartAdsUtil::PROJECT_OK) {
            if ($testCartADSProject == cartAdsUtil::ERR_CODE_PROJECT_VARIABLE) {
                $message = 'Missing project variable';
            } else {
                $message = 'Project name must be "cartads"';
            }
            $resp->setHttpStatus('500', 'Internal Server Error');
            $resp->data = $message;
            return $resp;
        }

        $parcellesCharge = cartAdsDbClient::charge($repo, $projectName, $parcelles);

        $resp = $this->getResponse('xml');
        $resp->dataTpl = 'cartads~charge';
        $resp->data->assign('parcelles', $parcellesCharge);

        return $resp;
    }

}
