<?php

use cartADS\Commands\UpdateDossiers;


if (isset($application)) {
    $application->add(new UpdateDossiers());
}
