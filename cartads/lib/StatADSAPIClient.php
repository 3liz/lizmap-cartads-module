<?php
namespace cartADS;

class StatADSAPIClient {

    public function __construct(string $repo, string $projectName) {

        $this->config = Util::projectCartADSConfig($repo, $projectName);

    }

    public function getToken() {
        // get authentication url and payload from config
        $authURL = $this->config['auth_url'];
        $authPayload = array(
            'Login' => $this->config['login'],
            'MotDePasse' => $this->config['password'],
        );

        // prepare request
        $options = array(
            'method' => 'post',
            'body' => json_encode($authPayload),
            'headers' => array(
                'Content-type' => 'application/json',
            ),
        );

        // send request and get response
        list($data, $mime, $code) = \Lizmap\Request\Proxy::getRemoteData($authURL, $options);

        if (floor($code / 100) >= 4) {
            \jLog::log('unable to query Stat\'ADS API (' . $authURL . ') HTTP code '.$code, 'error');

            return null;
        }

        if (strpos($mime, 'application/json') !== 0) {
            \jLog::log('unable to query Stat\'ADS API (' . $authURL . ') mime-type '.$mime, 'error');

            return null;
        }

        $resp = json_decode($data);

        if (!property_exists($resp, 'token')) {
            \jLog::log('unable to query Stat\'ADS API (' . $authURL . ')', 'error');

            return null;
        }

        return $resp->token;
    }

    public function getDossiers(string $parcelleId) {
        // get token
        $token = $this->getToken();
        if (!$token) {
            return null;
        }

        // build search URL
        $searchURL = $this->config['search_url'];
        $searchParams = http_build_query(array(
            'refresh' => 'false',
            'token' => $token,
            'liste_parcelles' => '[*'.$parcelleId.'*]',
        ));
        $searchURL .= '?' . $searchParams;

        // send request and get response
        $options = array();
        list($data, $mime, $code) = \Lizmap\Request\Proxy::getRemoteData($searchURL, $options);

        if (floor($code / 100) >= 4) {
            \jLog::log('unable to query Stat\'ADS API (' . $searchURL . ') HTTP code '.$code, 'error');

            return null;
        }

        if (strpos($mime, 'application/json') !== 0) {
            \jLog::log('unable to query Stat\'ADS API (' . $searchURL . ') mime-type '.$mime, 'error');

            return null;
        }

        $resp = json_decode($data);

        if (!property_exists($resp, 'data')) {
            \jLog::log('unable to query Stat\'ADS API (' . $searchURL . ')', 'error');

            return null;
        }

        $dossiers = $resp->data;
        // sort by date_depot descending
        usort($dossiers, fn($a, $b) => strcmp($b->date_depot, $a->date_depot));

        return $dossiers;
    }

    public function getDossier(string $dossierId) {
        // get token
        $token = $this->getToken();
        if (!$token) {
            return null;
        }

        // build search URL
        $searchURL = $this->config['search_url'];
        $searchParams = http_build_query(array(
            'refresh' => 'false',
            'token' => $token,
            'nom_dossier	' => '[*'.$dossierId.'*]',
        ));
        $searchURL .= '?' . $searchParams;

        // send request and get response
        $options = array();
        list($data, $mime, $code) = \Lizmap\Request\Proxy::getRemoteData($searchURL, $options);

        if (floor($code / 100) >= 4) {
            \jLog::log('unable to query Stat\'ADS API (' . $searchURL . ') HTTP code '.$code, 'error');

            return null;
        }

        if (strpos($mime, 'application/json') !== 0) {
            \jLog::log('unable to query Stat\'ADS API (' . $searchURL . ') mime-type '.$mime, 'error');

            return null;
        }

        $resp = json_decode($data);

        if (!property_exists($resp, 'data')) {
            \jLog::log('unable to query Stat\'ADS API (' . $searchURL . ')', 'error');

            return null;
        }

        $dossiers = $resp->data;
        // sort by date_depot descending
        usort($dossiers, fn($a, $b) => strcmp($b->date_depot, $a->date_depot));

        return $dossiers[0];
    }

    public function recherche(array $params) {
        // get token
        $token = $this->getToken();
        if (!$token) {
            return null;
        }

        // build search URL
        $searchURL = $this->config['search_url'];
        $searchParams = http_build_query(array_merge($params, array(
            'refresh' => 'false',
            'token' => $token,
        )));
        $searchURL .= '?' . $searchParams;

        // send request and get response
        $options = array();
        list($data, $mime, $code) = \Lizmap\Request\Proxy::getRemoteData($searchURL, $options);

        if (floor($code / 100) >= 4) {
            \jLog::log('unable to query Stat\'ADS API (' . $searchURL . ') HTTP code '.$code, 'error');

            return null;
        }

        if (strpos($mime, 'application/json') !== 0) {
            \jLog::log('unable to query Stat\'ADS API (' . $searchURL . ') mime-type '.$mime, 'error');

            return null;
        }

        $resp = json_decode($data);

        if (!property_exists($resp, 'data')) {
            \jLog::log('unable to query Stat\'ADS API (' . $searchURL . ')', 'error');

            return null;
        }

        $dossiers = $resp->data;
        // sort by date_depot descending
        usort($dossiers, fn($a, $b) => strcmp($b->date_depot, $a->date_depot));

        return $dossiers[0];
    }
}
