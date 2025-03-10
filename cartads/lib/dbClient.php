<?php
namespace cartADS;

class dbClient {

    public static function charge(string $repo, string $projectName, array $parcelles) {
        $cnx = Util::getConnection($repo, $projectName);
        if (!$cnx) {
            return null;
        }
        $quote = function($value) use ($cnx) {
            return $cnx->quote($value);
        };
        $sql = 'SELECT cartads_parcelle_id AS id,
            p.cartads_parcelle AS nom,
            round(ST_Area(p.geom)::numeric) AS surface,
            round(ST_Area(ST_Intersection(p.geom, ST_UNION(gz.geom)))::numeric) AS contenance,
            round((ST_Area(ST_Intersection(p.geom, ST_UNION(gz.geom))) / ST_Area(p.geom) * 100)::numeric, 2) AS occupation
            FROM cartads.cartads_parcelle p
            JOIN cartads.geo_zones gz ON (ST_INTERSECTS(p.geom, gz.geom) AND NOT ST_Touches(p.geom, gz.geom))
            JOIN cartads.communes com ON (com.codeinsee=gz.codeinsee AND ST_CENTROID(p.geom) && com.geom AND ST_CONTAINS(com.geom, ST_CENTROID(p.geom)))
            WHERE cartads_parcelle IN (' . implode(', ', array_map($quote, $parcelles)) . ')
            GROUP BY cartads_parcelle_id, p.cartads_parcelle, p.geom
        ';
        $result = $cnx->query($sql);
        $parcellesData = $result->fetchAll();

        $sql = 'SELECT cartads_parcelle_id AS parcelle_id,
            z.zones_nom AS nom,
            round(ST_Area(ST_Intersection(p.geom, gz.geom))::numeric) AS surface,
            round((ST_Area(ST_Intersection(p.geom, gz.geom)) / ST_Area(p.geom) * 100)::numeric, 2) AS pourcentage,
            zones_type AS type_nom,
            zones_type_code AS type_code,
            zones_observation AS observation
            FROM cartads.zones z
            JOIN cartads.geo_zones gz ON z.zones_id=gz.zones_id
            JOIN cartads.cartads_parcelle p ON (ST_INTERSECTS(p.geom, gz.geom) AND NOT ST_Touches(p.geom, gz.geom))
            JOIN cartads.communes com ON (com.codeinsee=gz.codeinsee AND ST_CENTROID(p.geom) && com.geom AND ST_CONTAINS(com.geom, ST_CENTROID(p.geom)))
            WHERE p.cartads_parcelle IN (' . implode(', ', array_map($quote, $parcelles)) . ')
            ORDER BY pourcentage DESC
        ';
        $result = $cnx->query($sql);
        $zonesData = $result->fetchAll();

        foreach ($parcellesData as &$parcelle) {
            $parcelle->zones = array();
            foreach ($zonesData as $zone) {
                if ($zone->parcelle_id == $parcelle->id) {
                    $parcelle->zones[] = $zone;
                }
            }
        }

        return $parcellesData;
    }
}
