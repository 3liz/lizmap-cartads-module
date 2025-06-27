<?php
namespace cartADS;

class AdsCsApiClient {

    protected $config;

    public function __construct(string $repo, string $projectName) {

        $this->config = Util::projectCartADSConfig($repo, $projectName);

    }

    public function getToken() {
        // get authentication url and payload from config
        $authURL = $this->config['auth_url'];
        $authPayload = array(
            'clientId' => $this->config['clientId'], // 'clientId
            'login' => $this->config['login'],
            'password' => hash('sha256', $this->config['password']),
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
            \jLog::log(
                'unable to query ADS CS API ('.$authURL.') HTTP code '.$code.' for payload '.json_encode($authPayload),
                'error'
            );

            return null;
        }

        if (strpos($mime, 'application/json') !== 0) {
            \jLog::log('unable to query ADS CS API ('.$authURL.') mime-type '.$mime.' for payload '.json_encode($authPayload),
                'error'
            );

            return null;
        }

        $resp = json_decode($data);

        if (!property_exists($resp, 'Token')) {
            \jLog::log('Response from ADS CS API ('.$authURL.') does not contain Token: '.json_encode($resp), 'error');

            return null;
        }

        return $resp->Token;
    }

    public function getDossier(string $dossierId) {
        // get token
        $token = $this->getToken();
        if (!$token) {
            return null;
        }

        // build dossier URL
        $dossierURL = $this->config['dossier_url'];
        $dossierPayload = array(
            'idDossier' => $dossierId,
        );

        $url = $dossierURL.'?';
        $url .= http_build_query($dossierPayload);

        // prepare request
        $options = array(
            'method' => 'get',
            'headers' => array(
                'Authorization' => 'Bearer ' . $token,
            ),
        );

        // send request and get response
        list($data, $mime, $code) = \Lizmap\Request\Proxy::getRemoteData($url, $options);

        if (floor($code / 100) >= 4) {
            \jLog::log(
                'unable to query ADS CS API ('.$dossierURL.' with idDossier '.$dossierId.') HTTP code '.$code,
                'error'
            );

            return null;
        }

        if (strpos($mime, 'application/json') !== 0) {
            \jLog::log(
                'unable to query ADS CS API ('.$dossierURL.' with idDossier '.$dossierId.') mime-type '.$mime,
                'error'
            );

            return null;
        }

        $resp = json_decode($data);

        if (!property_exists($resp, 'IdDossier')) {
            \jLog::log(
                'unable to query ADS CS API ('.$dossierURL.' with idDossier '.$dossierId.')',
                'error'
            );

            return null;
        }

        return $resp;
    }

    public function recherche(array $params) {
        // get token
        $token = $this->getToken();
        if (!$token) {
            return null;
        }

        // build search URL
        $searchURL = $this->config['search_url'];
        $searchPayload = array_intersect_key(
            $params,
            array_flip(array(
                'Communes',
                'typesDossier',
                'dateDepotDebut',
                'dateDepotFin',
                'dateModification',
                'parcelle',
                'limit',
                'offset',
            ))
        );
        if (count($searchPayload) == 0) {
            return null;
        }

        $url = $searchURL.'?';
        $url .= http_build_query($searchPayload);

        // prepare request
        $options = array(
            'method' => 'get',
            'headers' => array(
                'Authorization' => 'Bearer ' . $token,
            ),
        );

        // send request and get response
        list($data, $mime, $code) = \Lizmap\Request\Proxy::getRemoteData($url, $options);

        if (floor($code / 100) >= 4) {
            \jLog::log(
                'unable to query ADS CS API ('.$searchURL.') HTTP code '.$code.' for payload '.json_encode($searchPayload),
                'error'
            );

            return null;
        }

        if (strpos($mime, 'application/json') !== 0) {
            \jLog::log(
                'unable to query ADS CS API ('.$searchURL.') mime-type '.$mime.' for payload '.json_encode($searchPayload),
                'error'
            );

            return null;
        }

        $resp = json_decode($data);

        if (!is_array($resp) && count($resp) == 0) {
            \jLog::log(
                'unable to query ADS CS API ('.$searchURL.') for payload '.json_encode($searchPayload),
                'error'
            );

            return null;
        }

        return $resp;
    }
}
