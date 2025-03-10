<?php
/**
 * @author    3liz
 * @copyright 2022 3liz
 *
 * @see      https://3liz.com
 *
 * @license    GPL 3
 */
class cartadsModuleInstaller extends \Jelix\Installer\Module\Installer {
    public function install(Jelix\Installer\Module\API\InstallHelpers $helpers) {
        $groupName = 'cartads.subject.group';
        // Add rights group
        jAcl2DbManager::createRightGroup($groupName, 'cartads~default.rights.group.name');

        // Add right subject
        jAcl2DbManager::createRight('cartads.admin.access', 'cartads~default.rights.admin.access', $groupName);

        // Add rights on group admins
        jAcl2DbManager::addRight('admins', 'cartads.admin.access');
    }
}
