<?php

use \cartADS\Util as cartAdsUtil;

class cartadsListener extends \jEventListener
{
    public function ongetMapAdditions($event)
    {
        $repository = $event->repository;
        $project = $event->project;

        $projectCartADSCheck = cartAdsUtil::projectIsCartADS($repository, $project);
        switch ($projectCartADSCheck) {
            case cartAdsUtil::PROJECT_OK:
                $jsvars = array(
                    'cartAdsConfig' => array(
                        'dossierUrl' => jUrl::get('cartads~dossier:index', array('repository' => $repository, 'project' => $project)),
                        'dossiersRechercheUrl' => jUrl::get('cartads~dossier:recherche', array('repository' => $repository, 'project' => $project)),
                    ),
                );
                $js = array(jUrl::get('jelix~www:getfile', array('targetmodule' => 'cartads', 'file' => 'cartads.js')));
                $event->add(
                    array(
                        'js' => $js,
                        'jsvars' => $jsvars,
                    )
                );
                break;
        }
    }

    public function ongetRedirectKeyParams($event)
    {
        $repository = $event->repository;
        $project = $event->project;

        $projectNetADSCheck = cartAdsUtil::projectIsCartADS($repository, $project);
        switch ($projectNetADSCheck) {
            case cartAdsUtil::PROJECT_OK:
                // Ouverture et Centrage de la carte sur les parcelles du terrain
                $event->add('parcelles');
                // Ouverture et Centrage de la carte sur le dossier
                $event->add('dossiers');
                // Ouverture et Centrage de la carte sur le dossier avec outil de saisie du terrain activé
                $event->add('dossier');
                $event->add('x');
                $event->add('y');
                break;
        }
    }
}
