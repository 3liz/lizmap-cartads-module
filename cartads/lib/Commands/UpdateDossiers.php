<?php

namespace cartADS\Commands;

use Jelix\Scripts\ModuleCommandAbstract;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use \cartADS\dbClient as cartAdsDbClient;
use \cartADS\Util as cartAdsUtil;
use \cartADS\AdsCsApiClient as cartAdsApiClient;
use \cartADS\AdsCsApiDossier as cartAdsApiDossier;


class UpdateDossiers extends ModuleCommandAbstract
{
    protected function configure()
    {
        $this->setName('cartads:dossiers:update')
            ->setDescription('Mise à jour des dossiers Cart@DS pour un projet')
            ->setHelp('')
            ->addArgument('repository', InputArgument::REQUIRED, 'Repository name')
            ->addArgument('project', InputArgument::REQUIRED, 'Project name')
            ->addArgument('dateModification', InputArgument::OPTIONAL, 'Recherche par date de dernière modification');
    }


    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $repo = $input->getArgument('repository');
        $projectName = $input->getArgument('project');
        $testCartADSProject = cartAdsUtil::projectIsCartADS($repo, $projectName);
        if ($testCartADSProject != cartAdsUtil::PROJECT_OK) {
            if ($testCartADSProject == cartAdsUtil::ERR_CODE_PROJECT_VARIABLE) {
                $message = 'Missing project variable';
            } else {
                $message = 'Project is not a cartADS project';
            }
            $output->writeln($message);
            return 1;
        }

        $dateModification = $input->getArgument('dateModification');
        if (is_null($dateModification)) {
            $dateModification = date('Y-m-d', strtotime("-1 days"));
        } else {
            if (!preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $dateModification)) {
                $output->writeln('`'.$dateModification.'` is not a date');
                return 1;
            }
        }

        $apiClient = new cartAdsApiClient($repo, $projectName);
        $dossiers = $apiClient->getDossiers(array(
            'dateModification' => $dateModification,
            'limit' => 1000,
            'offset' => 0,
        ));

        $nb = cartAdsDbClient::updateDossiers($repo, $projectName, $dossiers);
        $output->writeln($nb.' dossiers updated');
        return 0;
    }
}
