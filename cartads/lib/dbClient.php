<?php
namespace cartADS;

class dbClient {

    public static function charge(string $repo, string $projectName, array $parcelles) {
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
}
