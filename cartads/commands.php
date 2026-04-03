<?php

use cartADS\Commands\UpdateAllDossiers;
use cartADS\Commands\UpdateDossiers;


if (isset($application)) {
    $application->add(new UpdateDossiers());
    $application->add(new UpdateAllDossiers);
}
