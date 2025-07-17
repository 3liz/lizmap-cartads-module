const cartAds = function() {
    const NOM_COUCHE_PARCELLES = 'parcelles';

    function goToParcelles(parcelles) {
        const layer = lizMap.mainLizmap.state.layersAndGroupsCollection.getLayerByName('parcelles');
        const filter = `"cartads_parcelle" IN ( ${parcelles.map(p => `'${p}'`).join(',')} )`;
        const options = {
            'TYPENAME': NOM_COUCHE_PARCELLES,
            'EXP_FILTER': filter,
        };
        lizMap.mainLizmap.wfs.getFeature(options).then((featureCollection) => {
            const format = new lizMap.ol.format.GeoJSON();
            const features = format.readFeatures(featureCollection, {
                featureProjection: lizMap.mainLizmap.projection,
            });
            let extent = null;
            let featureIds = [];
            features.forEach((feature) => {
                featureIds.push(feature.getProperties().cartads_parcelle_id);
                if (extent == null) {
                    extent = feature.getGeometry().getExtent();
                } else {
                    lizMap.ol.extent.extend(extent, feature.getGeometry().getExtent());
                }
            });

            layer.selectedFeatures = featureIds;
            lizMap.config.layers[NOM_COUCHE_PARCELLES]['selectedFeatures'] = featureIds;
            lizMap.events.triggerEvent('layerSelectionChanged',
                {
                    'featureType': NOM_COUCHE_PARCELLES,
                    'featureIds': lizMap.config.layers[NOM_COUCHE_PARCELLES]['selectedFeatures'],
                    'updateDrawing': true
                }
            );
            lizMap.mainLizmap.map.getView().fit(extent, {duration: 1000});
            const wmsParams = {
                QUERY_LAYERS: NOM_COUCHE_PARCELLES,
                LAYERS: NOM_COUCHE_PARCELLES,
                FEATURE_COUNT: 50, // TODO: get this value from config after it has been loaded?
                FILTER: `${NOM_COUCHE_PARCELLES}:${filter}`,
            }
            lizMap.mainLizmap.wms.getFeatureInfo(wmsParams).then((getFeatureInfo) => {
                lizMap.displayGetFeatureInfo(
                    getFeatureInfo,
                    { // center in pixel
                        x: lizMap.mainLizmap.state.map.size[0] / 2,
                        y: lizMap.mainLizmap.state.map.size[1] / 2,
                    }
                );
            });
        });
    }

    function goToDossiers(dossiers) {
        const url = cartAdsConfig.dossierUrl;
        const requests = dossiers.map((dossier) => {
            return fetch(`${url}?${new URLSearchParams({nom: dossier})}`);
        });
        Promise.all(requests).then((responses) => {
            return Promise.all(responses.map((response) => {
                return response.json();
            }));
        }).then((data) => {
            const parcelles =
                data.filter((d) => d !== null)
                    .map((d) => d.Parcelles) // get all lists of parcels
                    .join(',') // join all lists into a single string
                    .split(',').map((p) => p.trim()) // split by comma and trim
                    .filter((value, index, array) => array.indexOf(value) === index) // remove duplicates
            goToParcelles(parcelles);
        });
    }

    lizMap.events.on({
        uicreated: () => {
            const urlSearchParams = new URLSearchParams(window.location.search);
            const params = Object.fromEntries(urlSearchParams.entries());

            if (params) {
                if (params.parcelles) {
                    goToParcelles(params.parcelles.split(';').map((p) => p.trim()));
                }
                else if (params.dossiers) {
                    goToDossiers(params.dossiers.split(';').map((p) => p.trim()));
                }
            }

        }
    });
    return {

    };
}();
