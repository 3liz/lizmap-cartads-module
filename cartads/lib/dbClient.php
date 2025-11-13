<?php
namespace cartADS;

use \cartADS\AdsCsApiDossier as cartAdsApiDossier;
use \cartADS\ResultUpdateDossiers;


class dbClient {

    public static function charge(string $repo, string $projectName, array $parcelles){
        // Get connection from project
        $cnx = Util::getConnection($repo, $projectName);
        if (!$cnx) {
            return null;
        }

        // Defined a quote function for array_map
        $quote = function($value) use ($cnx) {
            return $cnx->quote($value);
        };

        // Build SQL query to get parcelles information for charge
        $sql = 'SELECT cartads_parcelle_id AS id,
            p.cartads_parcelle AS nom,
            round(ST_Area(p.geom)::numeric) AS surface,
            round(ST_Area(ST_Intersection(p.geom, ST_UNION(gz.geom)))::numeric) AS contenance,
            round((ST_Area(ST_Intersection(p.geom, ST_UNION(gz.geom))) / ST_Area(p.geom) * 100)::numeric, 2) AS occupation
            FROM cartads_parcelle p
            JOIN geo_zones gz ON (ST_INTERSECTS(p.geom, gz.geom) AND NOT ST_Touches(p.geom, gz.geom))
            JOIN communes com ON (com.codeinsee=gz.codeinsee AND ST_CENTROID(p.geom) && com.geom AND ST_CONTAINS(com.geom, ST_CENTROID(p.geom)))
            WHERE cartads_parcelle IN (' . implode(', ', array_map($quote, $parcelles)) . ')
            GROUP BY cartads_parcelle_id, p.cartads_parcelle, p.geom
        ';
        // Execute query and get parcelles data
        $result = $cnx->query($sql);
        $parcellesData = $result->fetchAll();

        // Build SQL query to get zones information for parcelles
        $sql = 'SELECT cartads_parcelle_id AS parcelle_id,
            z.zones_nom AS nom,
            round(ST_Area(ST_Intersection(p.geom, gz.geom))::numeric) AS surface,
            round((ST_Area(ST_Intersection(p.geom, gz.geom)) / ST_Area(p.geom) * 100)::numeric, 2) AS pourcentage,
            zones_type AS type_nom,
            zones_type_code AS type_code,
            zones_observation AS observation
            FROM zones z
            JOIN geo_zones gz ON z.zones_id=gz.zones_id
            JOIN cartads_parcelle p ON (ST_INTERSECTS(p.geom, gz.geom) AND NOT ST_Touches(p.geom, gz.geom))
            JOIN communes com ON (com.codeinsee=gz.codeinsee AND ST_CENTROID(p.geom) && com.geom AND ST_CONTAINS(com.geom, ST_CENTROID(p.geom)))
            WHERE p.cartads_parcelle IN (' . implode(', ', array_map($quote, $parcelles)) . ')
            ORDER BY pourcentage DESC
        ';
        // Execute query and get zones data
        $result = $cnx->query($sql);
        $zonesData = $result->fetchAll();

        // Merge zones data with parcelles data
        foreach ($parcellesData as &$parcelle) {
            $parcelle->zones = array();
            foreach ($zonesData as $zone) {
                if ($zone->parcelle_id == $parcelle->id) {
                    $parcelle->zones[] = $zone;
                }
            }
        }

        // Return parcelles data with zones data
        return $parcellesData;
    }

    public static function parcelleIds(string $repo, string $projectName, array $parcelles) {
        // Get parcelles layer from project
        $layer = Util::getParcellesLayer($repo, $projectName);
        if (!$layer) {
            return null;
        }

        // Get connection from project
        $cnx = Util::getConnection($repo, $projectName);
        if (!$cnx) {
            return null;
        }

        // Get parcelles layer datasource
        $datasource = new \qgisVectorLayerDatasource(
            $layer->getProvider(),
            $layer->getDatasource(),
        );
        if (!$datasource) {
            return null;
        }
        $tablename = $datasource->getDatasourceParameter('tablename');
        $primaryKey = $datasource->getDatasourceParameter('key');

        // Defined a quote function for array_map
        $quote = function ($value) use ($cnx) {
            return $cnx->quote($value);
        };
        // Get parcelles id
        $sql = 'SELECT ' . $cnx->encloseName($primaryKey) . ' as id
            FROM ' . $cnx->encloseName($tablename) . '
            WHERE cartads_parcelle IN (' . implode(', ', array_map($quote, $parcelles)) . ')';
        $result = $cnx->query($sql);
        $parcellesId = $result->fetchAll();
        return array_map(function ($value) {
            return $value->id;
        }, $parcellesId);
    }

    public static function carteEmprise(string $repo, string $projectName, array $parcelles, string $projection) {
        // Get connection from project
        $cnx = Util::getConnection($repo, $projectName);
        if (!$cnx) {
            return null;
        }

        // Defined a quote function for array_map
        $quote = function ($value) use ($cnx) {
            return $cnx->quote($value);
        };

        $sql = 'SELECT ST_XMIN(ST_EXTENT(geom)) as xmin,
            ST_YMIN(ST_EXTENT(geom)) as ymin,
            ST_XMAX(ST_EXTENT(geom)) as xmax,
            ST_YMAX(ST_EXTENT(geom)) as ymax
        FROM (
            SELECT ST_UNION(ST_TRANSFORM(geom, ' . $cnx->quote($projection) . ')) AS geom
            FROM cartads_parcelle
            WHERE cartads_parcelle IN (' . implode(', ', array_map($quote, $parcelles)) . ')
        ) AS foo;
        ';
        // Execute query and get zones data
        $result = $cnx->query($sql);
        $extent = $result->fetchAllAssociative()[0];
        // Expand extent
        $coeff = 2;
        $width = $extent['xmax'] - $extent['xmin'];
        $height = $extent['ymax'] - $extent['ymin'];
        if ($width > 5000 || $height > 5000) {
            $coeff = 0;
        } else if ($width > 2500 || $height > 2500) {
            $coeff = 0.5;
        } else if ($width > 1000 || $height > 1000) {
            $coeff = 1;
        }
        $extent['xmin'] -= $width * $coeff;
        $extent['ymin'] -= $height * $coeff;
        $extent['xmax'] += $width * $coeff;
        $extent['ymax'] += $height * $coeff;
        // returns bbox
        return array(
            $extent['xmin'],
            $extent['ymin'],
            $extent['xmax'],
            $extent['ymax'],
        );
    }


    public static function updateDossiers(string $repo, string $projectName, array $dossiers): ResultUpdateDossiers
    {
        $nullResult = new ResultUpdateDossiers();
        if (count($dossiers) == 0) {
            return $nullResult; // no dossiers to insert and update
        }

        // Get connection from project
        $cnx = Util::getConnection($repo, $projectName);
        if (!$cnx) {
            return $nullResult; // no dossiers to insert and update
        }

        // Clear table new_cartads_dossier
        $sql = 'TRUNCATE TABLE new_cartads_dossier';
        $cnx->exec($sql);
        // Insert new dossiers
        $values = array();
        foreach ($dossiers as $d) {
            $dossier = new cartAdsApiDossier($d);
            $values[] = $dossier->getSqlValues();
        }

        if (count($values) == 0) {
            return $nullResult; // no dossiers to insert and update
        }

        // insert into temp table
        $sql = "
        INSERT INTO new_cartads_dossier (
        id_dossier, nom_dossier, commune, n_commune, adresse,
        liste_parcelles, type_dossier, annee, date_depot,
        date_limite_instruction, date_modification_dossier,
        date_avis_instructeur, date_decision, date_notification_decision,
        stade, autorite, instructeur, avis_instructeur, signataire,
        decision, demandeur_principal, url_dossier) VALUES
        ".join(",\n", $values);
        $cnx->exec($sql);

        // liste des dossiers dont la liste des parcelles a changé
        $sql = "
        SELECT d.id_dossier
        FROM cartads_dossier d
        JOIN new_cartads_dossier n ON n.id_dossier = d.id_dossier
        WHERE d.liste_parcelles != n.liste_parcelles
        AND d.date_modification_dossier < n.date_modification_dossier
        ";
        $stmt = $cnx->query($sql);
        $results = $stmt->fetchAll();
        $dossiersParcelles = array();
        foreach ($results as $result) {
            $dossiersParcelles[] = $result->id_dossier;
        }

        // liste des nouveaux dossiers
        $sql = "
        SELECT n.id_dossier
        FROM new_cartads_dossier n
        LEFT JOIN cartads_dossier d ON n.id_dossier = d.id_dossier
        WHERE d.id_dossier IS NULL
        ";
        $stmt = $cnx->query($sql);
        $results = $stmt->fetchAll();
        $nouveauxDossiers = array();
        foreach ($results as $result) {
            $nouveauxDossiers[] = $result->id_dossier;
        }

        // suppression des parcelles et des géométries des dossiers dont la liste des parcelles a changé
        if (count($dossiersParcelles) > 0) {
            $sql = "
            DELETE FROM cartads_dossier_parcelle WHERE id_dossier IN (".implode(',', $dossiersParcelles).")
            ";
            $cnx->exec($sql);
            $sql = "
            DELETE FROM cartads_dossier_geo WHERE id_dossier IN (".implode(',', $dossiersParcelles).")
            ";
            $cnx->exec($sql);
        }

        // ajout et mise à jour des dossiers
        $sql = "
        INSERT INTO cartads_dossier (
        id_dossier, nom_dossier, commune, n_commune, adresse,
        liste_parcelles, type_dossier, annee, date_depot,
        date_limite_instruction, date_modification_dossier,
        date_avis_instructeur, date_decision, date_notification_decision,
        stade, autorite, instructeur, avis_instructeur, signataire,
        decision, demandeur_principal, url_dossier)
        SELECT id_dossier, nom_dossier, commune, n_commune, adresse,
        liste_parcelles, type_dossier, annee, date_depot,
        date_limite_instruction, date_modification_dossier,
        date_avis_instructeur, date_decision, date_notification_decision,
        stade, autorite, instructeur, avis_instructeur, signataire,
        decision, demandeur_principal, url_dossier
        FROM new_cartads_dossier
        ORDER BY id_dossier ASC
        ON CONFLICT (id_dossier) DO UPDATE
        SET commune = EXCLUDED.commune,
            n_commune = EXCLUDED.n_commune,
            adresse = EXCLUDED.adresse,
            liste_parcelles = EXCLUDED.liste_parcelles,
            type_dossier = EXCLUDED.type_dossier,
            annee = EXCLUDED.annee,
            date_depot = EXCLUDED.date_depot,
            date_limite_instruction = EXCLUDED.date_limite_instruction,
            date_modification_dossier = EXCLUDED.date_modification_dossier,
            date_avis_instructeur = EXCLUDED.date_avis_instructeur,
            date_decision = EXCLUDED.date_decision,
            date_notification_decision = EXCLUDED.date_notification_decision,
            stade = EXCLUDED.stade,
            autorite = EXCLUDED.autorite,
            instructeur = EXCLUDED.instructeur,
            avis_instructeur = EXCLUDED.avis_instructeur,
            signataire = EXCLUDED.signataire,
            decision = EXCLUDED.decision,
            demandeur_principal = EXCLUDED.demandeur_principal,
            url_dossier = EXCLUDED.url_dossier
        RETURNING id_dossier
        ";
        $cnx->exec($sql);

        if (count($dossiersParcelles) > 0 || count($nouveauxDossiers) > 0) {
            // Mise à jour des parcelles des dossiers
            $sql = "
            INSERT INTO cartads_dossier_parcelle (id_dossier, nom_dossier, cartads_parcelle)
            SELECT d.id_dossier, d.nom_dossier, trim(unnest(string_to_array(d.liste_parcelles, ','))) cartads_parcelle
            FROM cartads_dossier d
            WHERE id_dossier IN (".implode(',', array_merge($dossiersParcelles, $nouveauxDossiers)).")
            ON CONFLICT (id_dossier, cartads_parcelle) DO NOTHING
            RETURNING id_dossier, cartads_parcelle
            ";
            $cnx->exec($sql);

            // Récupération du code geo_parcelle
            $sql = "
            UPDATE cartads_dossier_parcelle
            SET geo_parcelle = p.geo_parcelle
            FROM cartads_parcelle p
            WHERE cartads_dossier_parcelle.cartads_parcelle = p.cartads_parcelle
            AND admin_sol.cartads_dossier_parcelle.geo_parcelle IS NULL;
            ";
            $cnx->exec($sql);

            // Calcul des géométries des dossiers
            $sql = "
            INSERT INTO cartads_dossier_geo (id_dossier, nom_dossier, geom, complete_geom)
            SELECT id_dossier, nom_dossier, geom, complete_geom
            FROM (
                SELECT cdp.id_dossier, cdp.nom_dossier, ST_UNION(cph.geom) as geom,
                    COUNT(cdp.cartads_parcelle) AS defined_parcelle,
                    COUNT(cph.cartads_parcelle) AS found_parcelle,
                    COUNT(cdp.cartads_parcelle) = COUNT(cph.cartads_parcelle) AS complete_geom
                FROM admin_sol.cartads_dossier_parcelle cdp
                LEFT JOIN admin_sol.cartads_parcelle_historique cph ON cdp.cartads_parcelle = cph.cartads_parcelle
                WHERE cdp.id_dossier IN (".implode(',', array_merge($dossiersParcelles, $nouveauxDossiers)).")
                  AND ST_IsValid(cph.geom)
                GROUP BY cdp.id_dossier, cdp.nom_dossier
            ) AS calculate_cdg
            WHERE found_parcelle > 0
            ORDER BY id_dossier ASC
            ON CONFLICT (id_dossier) DO UPDATE
            SET geom = EXCLUDED.geom, complete_geom = EXCLUDED.complete_geom
            ";
            $cnx->exec($sql);
        }

        // Retourne le nombre de dossiers traités
        return new ResultUpdateDossiers(count($values), count($nouveauxDossiers), count($dossiersParcelles));
    }
}
