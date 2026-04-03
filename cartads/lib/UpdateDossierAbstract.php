<?php

namespace cartADS;

use Jelix\Scripts\ModuleCommandAbstract;

use Symfony\Component\Console\Output\OutputInterface;
use cartADS\dbClient as cartAdsDbClient;
use cartADS\AdsCsApiClient as cartAdsApiClient;

abstract class UpdateDossierAbstract extends ModuleCommandAbstract
{
    public function updateProjectDossier($repo, $projectName, $dateModification, OutputInterface $output)
    {
        $output->writeln('Recherche de dossiers modifiés depuis le '.$dateModification);
        $apiClient = new cartAdsApiClient($repo, $projectName);
        $limit = 1000;
        $offset = 0;
        $dossiers = array();
        $output->writeln('Récupération des dossiers');
        while ($limit) {
            $data = $apiClient->recherche(array(
                'dateModification' => $dateModification,
                'limit' => $limit,
                'offset' => $offset,
            ));
            $dossiers = array_merge($dossiers, $data);
            $output->writeln(count($dossiers).' dossiers récupérés');
            if (count($data) < $limit) {
                $limit = 0;
                $output->writeln('Tous les dossiers ont été récupérées');
                break;
            }
            $offset += $limit;
        }

        $result = cartAdsDbClient::updateDossiers($repo, $projectName, $dossiers);
        if ($result->getNbTotal() == 0) {
            $output->writeln('Aucun dossier mis à jour');
            return 0;
        }
        $nb_total = $result->getNbTotal();
        $nb_new = $result->getNbNew();
        $nb_update_parcelles = $result->getNbUpdateParcelles();
        $message = '';
        if ($nb_total == 1) {
            $message = '1 dossier mis à jour';
        } else {
            $message = $nb_total.' dossiers mis à jour';
        }
        if ($nb_new > 0) {
            if ($nb_new == 1) {
                $message .= ' dont 1 nouveau';
            } else {
                $message .= ' dont '.$nb_new.' nouveaux';
            }
        }
        if ($nb_update_parcelles > 0) {
            if ($nb_update_parcelles == 1) {
                $message .= ' dont 1 dossier';
            } else {
                $message .= ' dont '.$nb_update_parcelles.' dossiers';
            }
            $message .= ' avec une nouvelle liste de parcelles';
        }
        $output->writeln($message);
    }
}
