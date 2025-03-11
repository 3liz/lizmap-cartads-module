<?php

use \cartADS\Util as cartAdsUtil;

class cartadsListener extends \jEventListener
{
    public function ongetMapAdditions($event)
    {
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
