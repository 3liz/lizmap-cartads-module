<?php

class admincartadsListener extends \jEventListener
{
    public function onmasteradminGetMenuContent($event)
    {
        if (jAcl2::check('cartads.admin.access')) {
            // new section
            $sectionAuth = new masterAdminMenuItem('cartads', "Cart@DS", '', 120);
            // add config page
            $sectionAuth->childItems[] = new masterAdminMenuItem('cartads', "Configuration", jUrl::get('cartads~admin:show'), 150, 'cartads_conf');

            $event->add($sectionAuth);
        }
    }
}
