<?php
namespace cartADS;

class Util {

    const ERR_CODE_PROJECT_NAME = 1;
    const ERR_CODE_PROJECT_VARIABLE = 2;
    const PROJECT_OK = 0;

    public static function projectIsCartADS(string $repo, string $projectName) {
        $project = \lizmap::getProject($repo . '~' . $projectName);

        if (!$project || $projectName !== 'cartads') {
            return self::ERR_CODE_PROJECT_NAME;
        }

        // le projet doit contenir une variable custom cartads_login
        $customProjectVariables = $project->getCustomProjectVariables();
        if ($customProjectVariables && array_key_exists('cartads_login', $customProjectVariables)) {
            return self::PROJECT_OK;
        }

        return self::ERR_CODE_PROJECT_VARIABLE;
    }

    public static function projectCartADSConfig(string $repo, string $projectName) {
        $config = array();
        if (self::projectIsCartADS($repo, $projectName) != self::PROJECT_OK) {
            return $config;
        }

        // get config from inifile
        $file = \jApp::varconfigPath('cartads.ini.php');
        $iniFile = new \Jelix\IniFile\IniModifier($file);
        $form = \jForms::create('cartads~cartadsadmin');
        foreach ($form->getControls() as $ctrl) {
            if ($ctrl->type != 'submit') {
                $config[$ctrl->ref] = $iniFile->getValue($ctrl->ref);
            }
        }

        $project = \lizmap::getProject($repo . '~' . $projectName);
        $config['project'] = $project;
        // le projet contient des variables custom
        $customProjectVariables = $project->getCustomProjectVariables();
        if ($customProjectVariables) {
            foreach ($form->getControls() as $ctrl) {
                if ($ctrl->type != 'submit' && array_key_exists('cartads_'.$ctrl->ref, $customProjectVariables)) {
                    $config[$ctrl->ref] = $customProjectVariables['cartads_'.$ctrl->ref];
                }
            }
        }

        \jForms::destroy('cartads~cartadsadmin');
        return $config;
    }

    public static function getConnection(string $repo, string $projectName) {
        // Check project is a CartADS one
        if (self::projectIsCartADS($repo, $projectName) != self::PROJECT_OK) {
            return null;
        }

        // Get project
        $project = \lizmap::getProject($repo . '~' . $projectName);
        $layerParcelle = 'parcelles';

        // Get parcelles layer
        $layer = $project->findLayerByName($layerParcelle);
        if (!$layer) {
            return \jDb::getConnection('cartads');
        }

        // Get parcelles QGIS layer
        $layerId = $layer->id;
        $qgisLayer = $project->getLayer($layerId);
        if (!$qgisLayer) {
            return \jDb::getConnection('cartads');
        }

        // Get profile
        $profile = $qgisLayer->getDatasourceProfile(34);
        if (!$profile) {
            return \jDb::getConnection('cartads');
        }
        return \jDb::getConnection($profile);
    }

    public static function getParcellesLayer(string $repo, string $projectName): \qgisVectorLayer {
        // Check project is a CartADS one
        if (self::projectIsCartADS($repo, $projectName) != self::PROJECT_OK) {
            return null;
        }
        // Get project
        $project = \lizmap::getProject($repo . '~' . $projectName);
        $layerParcelle = 'parcelles';
        // Get parcelles layer
        $layer = $project->findLayerByName($layerParcelle);
        if (!$layer) {
            return null;
        }
        // Get parcelles QGIS layer
        $layerId = $layer->id;
        $qgisLayer = $project->getLayer($layerId);
        if (!$qgisLayer) {
            return null;
        }
        return $qgisLayer;
    }

    public static function adaptBbox(array $bbox, float $width, float $height): array {
        $bboxWidth = $bbox[2] - $bbox[0];
        $bboxHeight = $bbox[3] - $bbox[1];
        $ratio = $bboxWidth / $bboxHeight;
        $imageRatio = $width / $height;
        if ($ratio > $imageRatio) {
            $newWidth = $bboxWidth;
            $newHeight = $bboxWidth / $imageRatio;
        } else {
            $newHeight = $bboxHeight;
            $newWidth = $bboxHeight * $imageRatio;
        }
        $bbox[0] -= ($newWidth - $bboxWidth) / 2;
        $bbox[2] += ($newWidth - $bboxWidth) / 2;
        $bbox[1] -= ($newHeight - $bboxHeight) / 2;
        $bbox[3] += ($newHeight - $bboxHeight) / 2;
        return $bbox;
    }
}
