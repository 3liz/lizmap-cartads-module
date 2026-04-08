<?php

namespace cartADS\Commands;

use Jelix\Scripts\ModuleCommandAbstract;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use cartADS\dbClient as cartAdsDbClient;
use cartADS\Util as cartAdsUtil;
use cartADS\AdsCsApiClient as cartAdsApiClient;
use carAds\ResultUpdateDossiers;
use cartADS\UpdateDossierAbstract;

class UpdateAllDossiers extends UpdateDossierAbstract
{
    protected function configure()
    {
        $this->setName('cartads:dossiers:update:all')
            ->setDescription('Mise à jour des dossiers Cart@DS pour tous les projets')
            ->setHelp('')
            ->addArgument('dateModification', InputArgument::OPTIONAL, 'Recherche par date de dernière modification');
    }


    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $dateModification = $input->getArgument('dateModification');
        if (is_null($dateModification)) {
            $dateModification = date('Y-m-d', strtotime("-1 days"));
        } else {
            if (!preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $dateModification)) {
                $output->writeln('`'.$dateModification.'` is not a date');
                return 1;
            }
        }

        $repoList = \lizmap::getRepositoryList();

        foreach ($repoList as  $repo) {
            $projectList = \lizmap::getRepository($repo)->getProjects();
            $output->writeln('searching projects on repo '.$repo);
            foreach ($projectList as $proj) {
                $projectName = $proj->getKey();
                $testCartADSProject = cartAdsUtil::projectIsCartADS($repo, $projectName);
                if ($testCartADSProject == cartAdsUtil::PROJECT_OK) {
                    $output->writeln('cartAds project '.$repo.'/'.$projectName.' found, updating dossiers');
                    $this->updateProjectDossier($repo, $projectName, $dateModification, $output);
                }
            }
        }

        return 0;
    }
}
