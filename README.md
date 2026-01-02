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
  * URL de récupération du token d’authentification `auth_url`
  * Identifiant du client pour accès à l’API SIG Rest Cart@DS `clientId`
  * Utilisateur Cart@DS avec droits sur les communes concernées `login`
  * Mot de passe de l'utilisateur Cart@DS permettant d'obtenir le token nécessaire à l'accès aux données des dossiers fournis par l'API SIG Rest Cart@DS `password`
  * URL de recherche de dossiers Cart@DS par exemple : https://[nom de domaine]/cartads/api/Sig/Dossiers `search_url`
  * URL de données d'un dossier Cart@DS par exemple : https://[nom de domaine]/cartads/api/Sig/Dossier `dossier_url`

## Configuration du projet QGIS

Pour que votre projet QGIS/Lizmap utilise les fonctionnalités du module, il faut :

* que le nom du fichier projet commence ou finisse par le mot clé `cartads` (cartads.qgs ou narbonne_cartads.qgs ou cartads_ladomitienne.qgs)
* que le projet ait une variable de projet `cartads_login` contenant votre identifiant client Cart@DS

Si vous avez plusieurs projets QGIS avec différents comptes pour Cart@DS, vous pouvez surcharger les paramètres de configuration du module pour chaque projet en ajoutant des variables de projet :

* `cartads_auth_url`
* `cartads_clientId`
* `cartads_password`
* `cartads_search_url`
* `cartads_dossier_url`

## Fonctionnalités ajoutées par le module

### Service de la charge de la parcelle

Pour chaque projet QGIS/Lizmap configuré pour Cart@DS, le module ajoute une URL de service `cartads~dossier:chargeParcelle` qui permet de récupérer la charge de la parcelle en fonction de son identifiant. Cette URL est accessible en GET, elle est de la forme `/index.php/cartads/${repository}/${project}/parcelle/charge` avec un paramètre multiple `parcelles[]`.

Par exemple, pour récupérer la charge des parcelles `106 AP 2015` et `106 AP 2016` via le projet `cartads` du répertoire `adsgn` du serveur Lizmap, l'URL sera : `/index.php/cartads/adsgn/cartads/parcelle/charge?parcelles[]=106%20AP%2015&parcelles[]=106%20AP%2016`

### Paramètres de la carte Lizmap supplémentaires

Les paramètres `parcelles` et `dossiers` peuvent être utiliser sur une carte Lizmap configuré pour Cart@DS pour centrer sur une ou plusieurs parcelles ou dossiers. Le séprateur de valeurs pour ces paramètres est le `;`.

Par exemples :

* pour centrer sur la parcelle `258 CA 28`, il faut ajouter `parcelles=258%20CA%2028` à l'URL de la carte Lizmap.
* pour centrer sur les parcelles `258 CA 46`, `258 CA 45` et `258 CA 55`, il faut ajouter `parcelles=258%20CA%2046;%20258%20CA%2045;%20258%20CA%2055` à l'URL de la carte Lizmap.
* pour centrer sur le dossier `CU 011 258 20 L0020`, il faut ajouter `dossiers=CU%20011%20258%2020%20L0020` à l'URL de la carte Lizmap.
* pour centrer sur les dossiers `CU 011 258 20 L0020` et `CU 011 258 20 L0020`, il faut ajouter `dossiers=CU%20011%20258%2020%20L0020;CU%20011%20258%2020%20L0020` à l'URL de la carte Lizmap.
