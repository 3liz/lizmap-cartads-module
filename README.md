# Cart@DS

[![Packagist](https://img.shields.io/packagist/v/lizmap/lizmap-cartads-module)](https://packagist.org/packages/lizmap/lizmap-cartads-module)

## Documentation

Présentation, guide, installation, Lizmap Web Client

Pour la configuration de l'extension lizmap : https://docs.3liz.org/qgis-cartads-plugin/

## Installation

Il est recommandé d'installer le module avec [Composer](https://getcomposer.org/), le gestionnaire de paquet pour PHP. Si vous ne pouvez pas
l'utiliser, utilisez la méthode manuelle indiquée plus bas.

NB : tous les chemins ci-dessous sont relatifs au dossier de Lizmap Web Client.

### Copie des fichiers automatique avec Composer

* Dans `lizmap/my-packages`, créer le fichier `composer.json` s'il n'existe pas déjà, en copiant le fichier `composer.json.dist`,
* puis installer le module avec Composer :

```bash
    cp -n lizmap/my-packages/composer.json.dist lizmap/my-packages/composer.json
    composer require --working-dir=lizmap/my-packages "lizmap/lizmap-cartads-module"
```

### Copie des fichiers manuelle (sans composer)


 * Téléchargez l'archive sur la page des [versions dans GitHub](https://github.com/3liz/lizmap-cartads-module/releases).

 * Extrayez les fichiers de l'archive et copier le répertoire `cartads` dans `lizmap/lizmap-modules/`.

### Installation du module

* Allez dans le répertoire `lizmap/install/` pour lancer la configuration de l'installateur

```bash
php configurator.php cartads
```
* Lancez enfin l'installation du module :

```bash
php installer.php
./clean_vartmp.sh
./set_rights.sh
```

### Configuration du module

 * Connectez-vous à lizmap en tant qu'administrateur, en configurer le module depuis la section Cart@DS > Configuration

 * Saisissez les différents champs nécessaires :
   * URL de connexion de l’API Rest de Stat’ADS
   * Login de connexion à l’API Rest de Stat’ADS
   * Mot de passe
   * URL de recherche de dossiers Cart@DS

## Configuration du projet QGIS

Pour que votre projet QGIS/Lizmap utilise les fonctionnalités du module, il faut :
 * que le nom du fichier projet soit `cartads` (cartads.qgs donc)
 * que le projet ait une variable de projet `cartads_login` contenant votre identifiant client NetADS
