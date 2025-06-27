<?php
namespace cartADS;

class AdsCsApiDossier {

    protected $data;

    public function __construct($data) {
        $this->data = $data;
    }

    /**
     * @return int
     */
    public function getIdDossier() {
        return $this->data->IdDossier;
    }

    /**
     * @return string
     */
    public function getNomDossier() {
        return $this->data->NomDossier;
    }

    /**
     * @return string
     */
    public function getCommune() {
        return $this->data->CoCommune;
    }

    /**
     * @return int
     */
    public function getNCommune() {
        return $this->data->NCommune;
    }

    /**
     * @return string
     */
    public function getAdresse() {
        return trim(($this->data->NVoirieTerrain ?? '').' '.($this->data->AdresseTerrain ?? ''));
    }

    /**
     * @return string
     */
    public function getListeParcelles() {
        return $this->data->Parcelles;
    }

    /**
     * @return string
     */
    public function getTypeDossier() {
        return $this->data->CoTypeDossier   ;
    }

    /**
     * @return int
     */
    public function getAnnee() {
        return $this->data->Annee;
    }

    /**
     * @return string
     */
    public function getDateDepot() {
        return $this->data->DateDepot;
    }

    /**
     * @return null|string
     */
    public function getDateLimiteInstruction() {
        return $this->data->DateLimiteInstruction ?? null;
    }

    /**
     * @return string
     */
    public function getDateModificationDossier() {
        return $this->data->DateModificationDossier;
    }

    /**
     * @return null|string
     */
    public function getDateAvisInstructeur() {
        return $this->data->DateAvisInstructeur ?? null;
    }

    /**
     * @return null|string
     */
    public function getDateDecisionSignataire() {
        return $this->data->DateDecisionSignataire ?? null;
    }

    /**
     * @return null|string
     */
    public function getDateNotificationDecisionSignataire() {
        return $this->data->DateNotificationDecisionSignataire ?? null;
    }

    /**
     * @return string
     */
    public function getStade() {
        return $this->data->Stade;
    }

    /**
     * @return null|string
     */
    public function getAutoriteCompetente() {
        return $this->data->AutoriteCompetente ?? null;
    }

    /**
     * @return null|string
     */
    public function getInstructeur() {
        return $this->data->Instructeur ?? null;
    }

    /**
     * @return null|string
     */
    public function getAvisInstructeur() {
        return $this->data->AvisInstructeur ?? null;
    }

    /**
     * @return null|string
     */
    public function getSignataire() {
        return $this->data->Signataire ?? null;
    }

    /**
     * @return null|string
     */
    public function getDecisionSignataire() {
        return $this->data->DecisionSignataire ?? null;
    }

    /**
     * @return string
     */
    public function getDemandeurPrincipal() {
        return trim($this->data->PrenomDemandeur ?? ''.' '.$this->data->NomDemandeur);
    }

    /**
     * @return string
     */
    public function getUrlDossier() {
        return $this->data->UrlDossier;
    }

    /**
     * @return string
     */
    public function getSqlValues() {
        return '('.implode(
            ',',
            array(
                $this->getIdDossier(),
                "'".$this->getNomDossier()."'",
                "'".$this->getCommune()."'",
                $this->getNCommune(),
                "'".str_replace("'", "''", $this->getAdresse())."'",
                "'".$this->getListeParcelles()."'",
                "'".$this->getTypeDossier()."'",
                $this->getAnnee(),
                "'".$this->getDateDepot()."'",
                $this->getDateLimiteInstruction() ? "'".$this->getDateLimiteInstruction()."'" : 'NULL',
                "'".$this->getDateModificationDossier()."'",
                $this->getDateAvisInstructeur() ? "'".$this->getDateAvisInstructeur()."'" : 'NULL',
                $this->getDateDecisionSignataire() ? "'".$this->getDateDecisionSignataire()."'" : 'NULL',
                $this->getDateNotificationDecisionSignataire() ? "'".$this->getDateNotificationDecisionSignataire()."'" : 'NULL',
                "'".str_replace("'", "''", $this->getStade())."'",
                $this->getAutoriteCompetente() ? "'".str_replace("'", "''", $this->getAutoriteCompetente())."'" : 'NULL',
                $this->getInstructeur() ? "'".str_replace("'", "''", $this->getInstructeur())."'" : 'NULL',
                $this->getAvisInstructeur() ? "'".str_replace("'", "''", $this->getAvisInstructeur())."'" : 'NULL',
                $this->getSignataire() ? "'".str_replace("'", "''", $this->getSignataire())."'" : 'NULL',
                $this->getDecisionSignataire() ? "'".str_replace("'", "''", $this->getDecisionSignataire())."'" : 'NULL',
                "'".str_replace("'", "''", $this->getDemandeurPrincipal())."'",
                "'".$this->getUrlDossier()."'",
            )
        ).')';
    }
}
