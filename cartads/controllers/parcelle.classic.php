<?php

use \cartADS\dbClient as cartAdsDbClient;
use \cartADS\Util as cartAdsUtil;
use \cartADS\AdsCsApiClient as cartAdsApiClient;

class parcelleCtrl extends jController {

    public function dossiers() {
        $resp = $this->getResponse('json');

        $repo = $this->param('repository');
        $projectName = $this->param('project');
        $parcelleId = $this->param('parcelle_id');
        if (is_null($repo) || is_null($projectName) || is_null($parcelleId)) {
            $resp->setHttpStatus('400', 'Bad Request');
            $resp->data = array(
                'code' => '400',
                'message' => 'Bad Request',
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
        $apiClient = new cartAdsApiClient($repo, $projectName);
        $dossiers = $apiClient->recherche(array(
            'parcelle' => $parcelleId,
        ));
        if (!$dossiers) {
            $resp->setHttpStatus('404', 'Not Found');
            $resp->data = array(
                'code' => '404',
                'message' => 'Not Found',
                'details' => 'La recherche par parcelle n\'a retourné aucun dossier',
            );
            return $resp;
        }
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
            $resp->setHttpStatus('400', 'Bad Request');
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
        $resp->contentTpl = 'cartads~charge';
        $resp->content->assign('parcelles', $parcellesCharge);

        return $resp;
    }

    public function carte() {
        $repo = $this->param('repository');
        $projectName = $this->param('project');
        // parcelles=31 AB 31;31 AB 32; 31 AC 27
        $parcelles = $this->param('parcelles');
        // taille de l'image requêtée
        $width = $this->param('width');
        $height = $this->param('height');

        // default response for errors
        $resp = $this->getResponse('json');

        if (is_null($repo) ||
            is_null($projectName) ||
            is_null($parcelles) ||
            is_null($width) ||
            is_null($height)
        ) {
            $resp->setHttpStatus('400', 'Bad Request');
            $resp->data = array(
                'code' => '400',
                'message' => 'Bad Request',
                'details' => 'parcelles, width, height are mandatory',
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

        $project = \lizmap::getProject($repo . '~' . $projectName);
        $getCapabilitiesRequest = \Lizmap\Request\Proxy::build($project, array(
            'service' => 'WMS',
            'version' => '1.3.0',
            'request' => 'GetCapabilities',
        ));
        $getCapabilitiesResponse = $getCapabilitiesRequest->process();
        if ($getCapabilitiesResponse->getCode() != 200) {
            $resp->setHttpStatus('500', 'Internal Server Error');
            $resp->data = array(
                'code' => '500',
                'message' => 'Internal Server Error',
                'details' => 'Error while getting GetCapabilities',
            );
            return $resp;
        }

        $getCapabilities = \Lizmap\App\XmlTools::xmlFromString($getCapabilitiesResponse->getBodyAsString());
        $layerName = (string) $getCapabilities->Capability->Layer->Name;
        $crs = $project->getProj();
        $parcelles = array_map('trim', explode(';', $parcelles));

        $bbox = cartAdsDbClient::carteEmprise($repo, $projectName, $parcelles, $crs);
        $bbox = cartAdsUtil::adaptBbox($bbox, $width, $height);
        $parcelleIds = cartAdsDbClient::parcelleIds($repo, $projectName, $parcelles);

        $getMapParams = array(
            'service' => 'WMS',
            'version' => '1.3.0',
            'request' => 'GetMap',
            'layers' => 'parcelles,'.$layerName,
            'styles' => '',
            'crs' => $crs,
            'bbox' => implode(',', $bbox),
            'width' => $width,
            'height' => $height,
            'format' => 'image/jpeg',
            'dpi' => 96,
            //'transparent' => 'true',
            'exceptions' => 'application/vnd.ogc.se_inimage',
            'selection' => 'parcelles:' . implode(', ', $parcelleIds),
        );
        $getMapRequest = \Lizmap\Request\Proxy::build(
            $project,
            $getMapParams,
        );

        $result = $getMapRequest->process();
        if ($result->data == 'error') {
            $resp->setHttpStatus('500', 'Internal Server Error');
            $resp->data = array(
                'code' => '500',
                'message' => 'Internal Server Error',
                'details' => 'Error while getting GetMap request with ',
            );
            return $resp;
        }

        /** @var jResponseBinary $rep */
        $resp = $this->getResponse('binary');
        $resp->mimeType = $result->mime;
        if (is_string($result->data) || is_callable($result->data)) {
            $resp->content = $result->data;
        }
        $resp->doDownload = false;
        $resp->outputFileName = 'carte_parcelles_' . implode('_', $parcelles) . '.jpg';
        return $resp;
        // faire une requête GetMap avec sélection sur la parcelles
    }

}
