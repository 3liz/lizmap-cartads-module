<?php
namespace cartADS;

class ResultUpdateDossiers {

    protected int $nb_total = 0;

    protected int $nb_new = 0;

    protected int $nb_update_parcelles = 0;

    public function __construct(int $nb_total = 0, int $nb_new = 0, int $nb_update_parcelles = 0)
    {
        $this->nb_total = $nb_total;
        $this->nb_new = $nb_new;
        $this->nb_update_parcelles = $nb_update_parcelles;
    }

    public function getNbTotal(): int
    {
        return $this->nb_total;
    }

    public function getNbNew(): int
    {
        return $this->nb_new;
    }

    public function getNbUpdateParcelles(): int
    {
        return $this->nb_update_parcelles;
    }
}
