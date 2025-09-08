const cartAds = function() {
    const NOM_COUCHE_PARCELLES = 'parcelles';
    const NOM_DOSSIER_CARTADS = 'cartads_dossier_geo';
    const NOM_COUCHE_PARCELLES_HISTORIQUE = 'cartads_parcelle_historique';

    function goToParcelles(parcelles) {
        const layer = lizMap.mainLizmap.state.layersAndGroupsCollection.getLayerByName(NOM_COUCHE_PARCELLES);
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

            if (extent == null) {
                return;
            }

            // Zoom to extent
            lizMap.mainLizmap.map.getView().fit(extent, {duration: 1000});

            // Get feature info
            let wmsParams = {
                QUERY_LAYERS: NOM_COUCHE_PARCELLES,
                LAYERS: NOM_COUCHE_PARCELLES,
                CRS: lizMap.mainLizmap.state.map.projection,
                FEATURE_COUNT: 50, // TODO: get this value from config after it has been loaded?
                FILTER: `${NOM_COUCHE_PARCELLES}:${filter}`,
            }

            // using cadastre
            if (globalThis.cadastreConfig && globalThis.cadastreConfig.parcelle) {
                const wmsName = globalThis.cadastreConfig.parcelle.shortName ?? globalThis.cadastreConfig.parcelle.name;
                wmsFilter = `"geo_parcelle" IN ( ${features.map(p => `'${p.getProperties().geo_parcelle}'`).join(',')} )`;
                wmsParams = {
                    QUERY_LAYERS: wmsName,
                    LAYERS: wmsName,
                    CRS: lizMap.mainLizmap.state.map.projection,
                    FEATURE_COUNT: 50, // TODO: get this value from config after it has been loaded?
                    FILTER: `${wmsName}:${wmsFilter}`,
                }
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
            const parcelles = data
                .filter((d) => d !== null)
                .map((d) => d.Parcelles) // get all lists of parcels
                .join(',') // join all lists into a single string
                .split(',').map((p) => p.trim()) // split by comma and trim
                .filter((value, index, array) => array.indexOf(value) === index) // remove duplicates

            const coords = data
                .filter((d) => d !== null && !isNaN(d.X) && !isNaN(d.Y))
                .map((d) => [d.X, d.Y]);

            const dossiers = data
                .filter((d) => d !== null)
                .map((d) => d.NomDossier);

            if (coords.length > 0) {
                // Calculate minResolution for 5000 scale denominator
                const ADJUSTED_DPI = 95.999808;
                const inchesPerMeter = 1000 / 25.4;
                const minResolution = 5000 / (ADJUSTED_DPI * inchesPerMeter);
                // Extra min and max coordinates
                const minX = Math.min(...coords.map((c) => c[0]));
                const minY = Math.min(...coords.map((c) => c[1]));
                const maxX = Math.max(...coords.map((c) => c[0]));
                const maxY = Math.max(...coords.map((c) => c[1]));
                // Zoom
                lizMap.mainLizmap.map.getView().fit(
                    [minX, minY, maxX, maxY],
                    {duration: 1000, minResolution: minResolution}
                );
            }

            const dlayer = lizMap.mainLizmap.state.layersAndGroupsCollection.getLayerByName(NOM_DOSSIER_CARTADS);
            dlayer.checked = true;

            const filter = `"nom_dossier" IN ( ${dossiers.map(d => `'${d}'`).join(',')} )`;
            // Get feature info
            const wmsParams = {
                QUERY_LAYERS: NOM_DOSSIER_CARTADS,
                LAYERS: NOM_DOSSIER_CARTADS,
                CRS: lizMap.mainLizmap.state.map.projection,
                FEATURE_COUNT: 50, // TODO: get this value from config after it has been loaded?
                FILTER: `${NOM_DOSSIER_CARTADS}:${filter}`,
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

            // Get Feature
            const wfsParams = {
                'TYPENAME': NOM_DOSSIER_CARTADS,
                'EXP_FILTER': filter,
            }
            lizMap.mainLizmap.wfs.getFeature(wfsParams).then((featureCollection) => {
                const format = new lizMap.ol.format.GeoJSON();
                const features = format.readFeatures(featureCollection, {
                    featureProjection: lizMap.mainLizmap.projection,
                });
                let extent = null;
                let featureIds = [];
                features.forEach((feature) => {
                    featureIds.push(feature.getProperties().id_dossier);
                    if (extent == null) {
                        extent = feature.getGeometry().getExtent();
                    } else {
                        lizMap.ol.extent.extend(extent, feature.getGeometry().getExtent());
                    }
                });

                if (extent === null && parcelles.length > 0) {
                    let parcelleIds = [];
                    // Filter
                    const parcelleFilter = `"cartads_parcelle" IN ( ${parcelles.map(p => `'${p}'`).join(',')} )`;
                    const parcelleOptions = {
                        'TYPENAME': NOM_COUCHE_PARCELLES_HISTORIQUE,
                        'EXP_FILTER': parcelleFilter,
                    };

                    lizMap.mainLizmap.wfs.getFeature(parcelleOptions).then((featureCollection) => {
                        const features = format.readFeatures(featureCollection, {
                            featureProjection: lizMap.mainLizmap.projection,
                        });
                        features.forEach((feature) => {
                            parcelleIds.push(feature.getProperties().cartads_parcelle_id);
                            if (extent == null) {
                                extent = feature.getGeometry().getExtent();
                            } else {
                                lizMap.ol.extent.extend(extent, feature.getGeometry().getExtent());
                            }
                        });
                    });

                    if (parcelleIds.length > 0) {
                        // Zoom
                        if (extent !== null) {
                            lizMap.mainLizmap.map.getView().fit(extent, {duration: 1000});
                        }
                    }
                }

                if (featureIds.length > 0) {
                    // Zoom
                    if (extent !== null) {
                        lizMap.mainLizmap.map.getView().fit(extent, {duration: 1000});
                    }
                } else {
                    // Launch edition
                    $(document.getElementById('edition-layer')).val(dlayer.id);

                    const eventsObj = {
                        'lizmapeditionformdisplayed': function(e) {
                            if (e.layerId == dlayer.id) {
                                const dossier = dossiers[0];
                                const field = $(document.getElementById('jforms_view_edition_nom_dossier'));
                                field.val(dossier);
                                field.change();
                                field.find('~ span > input').val(dossier);
                                field.change();
                                // PC 011 258 15 L0016
                            }
                        },
                        'lizmapeditionformclosed': function(e) {
                            if (e.layerId == dlayer.id) {
                                lizMap.events.un(eventsObj);
                            }
                        },
                    }
                    lizMap.events.on(eventsObj);

                    $(document.getElementById('edition-draw')).click();
                }
            });
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
