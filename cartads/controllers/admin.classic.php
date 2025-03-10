<?php

class adminCtrl extends jController {

    public $pluginParams = array(
        '*' => array('jacl2.right' => 'cartads.admin.access'),
    );

    private $iniFile;

    public function __construct($request) {
        parent::__construct($request);
        $file = jApp::varconfigPath('cartads.ini.php');
        $this->iniFile = new \Jelix\IniFile\IniModifier($file);
    }

    public function show() {
        /**
         * @var $resp jResponseHTML;
         */
        $resp = $this->getResponse('html');

        $form = jForms::create('cartads~cartadsadmin');
        $tpl = new jTpl();
        $this->initFormWithIni($form);
        $tpl->assign('form', $form);

        $resp->body->assign('MAIN', $tpl->fetch('show_config'));
        $resp->body->assign('selectedMenuItem', 'cartads');

        return $resp;
    }

    public function prepare() {
        $form = jForms::create('cartads~cartadsadmin');
        $this->initFormWithIni($form);
        return $this->redirect('cartads~admin:edit');
    }

    public function edit() {
        /**
         * @var $resp jResponseHTML;
         */
        $resp = $this->getResponse('html');

        $form = jForms::get('cartads~cartadsadmin');
        if ( is_null($form) ) {
            // redirect to default page
            return $this->redirect('cartads~admin:prepare');
        }
        $tpl = new jTpl();
        $tpl->assign('form', $form);

        $resp->body->assign('MAIN', $tpl->fetch('config'));
        $resp->body->assign('selectedMenuItem', 'cartads');

        return $resp;
    }

    public function save() {
        $form = jForms::fill('cartads~cartadsadmin');
        if ( is_null($form) ) {
            // redirect to default page
            return $this->redirect('cartads~admin:prepare');
        }

        if (!$form->check()) {
            return $this->redirect('cartads~admin:edit');
        }
        // Save the data
        foreach ($form->getControls() as $ctrl) {
            if ($ctrl->type != 'submit') {
                $this->iniFile->setValue($ctrl->ref, $form->getData($ctrl->ref));
            }
        }
        $this->iniFile->save();
        jForms::destroy('cartads~cartadsadmin');
        return $this->redirect('cartads~admin:show');
    }

    private function initFormWithIni($form) {
        // init form
        foreach ($form->getControls() as $ctrl) {
            if ($ctrl->type != 'submit') {
                $form->setData($ctrl->ref, $this->iniFile->getValue($ctrl->ref));
            }
        }
    }
}
