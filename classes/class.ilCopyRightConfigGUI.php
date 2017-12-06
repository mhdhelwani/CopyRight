<?php

include_once("./Services/Component/classes/class.ilPluginConfigGUI.php");
require_once("./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/CopyRight/classes/Entry/class.copyrightEntryTableGUI.php");
require_once("./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/CopyRight/classes/Entry/class.copyrightEntryGUI.php");

class ilCopyRightConfigGUI extends ilPluginConfigGUI
{
    public function __construct()
    {
        global $ilCtrl, $tpl, $ilTabs, $lng;
        /**
         * @var $ilCtrl ilCtrl
         * @var $tpl    ilTemplate
         * @var $ilTabs ilTabsGUI
         */
        $this->ctrl = $ilCtrl;
        $this->tpl = $tpl;
        $this->tabs = &$ilTabs;
        $this->pl = $this->getPluginObject();
        $this->plugin = new ilCopyRightPlugin();
        $this->lng = $lng;
    }

    /**
     * Handles all commmands, default is "configure"
     */
    function performCommand($cmd)
    {
        $this->tabs->addTab("cr_admin", $this->plugin->txt("text_tab"), $this->ctrl->getLinkTarget($this, "configure"));
        $this->tabs->activateTab("cr_admin");

        switch ($cmd) {
            case "configure":
            case "save":
            default:
                $this->$cmd();
                break;
        }
    }

    /**
     * Configure screen
     */
    function configure()
    {
        $table = new copyrightEntryTableGUI($this, "configure");
        $this->tpl->setContent($table->getHTML());
    }

    public function addOption()
    {
        /**
         * @var $entry_gui copyrightEntryGUI
         */
        $entry_gui = new copyrightEntryGUI(new copyrightEntry(), $this);
        $entry_gui->initForm();
        $entry_gui->setFormValuesByArray();
        $this->tpl->setContent($entry_gui->form->getHTML());
    }

    public function saveDefaultOption()
    {
        if (!isset($_POST["default"]))
        {
            ilUtil::sendFailure($this->plugin->txt("msg_no_default_option"));
            return $this->configure();
        }

        $copy_right_entry = new copyrightEntry($_POST["default"]);

        if (!$copy_right_entry->getActive()){
            ilUtil::sendFailure($this->plugin->txt("msg_wrong_default_option"));
            return $this->configure();
        }

        $copy_right_entry->setDefaultOption(true);
        $copy_right_entry->updateDefaultOption();
        $copy_right_entries = copyrightEntryInstanceFactory::getAll();

        foreach ($copy_right_entries as $cre){
            $copy_right_entry = new copyrightEntry($cre["id"]);
            if ($cre["id"] !== $_POST["default"] && $cre["default_option"]){
                $copy_right_entry->setDefaultOption(false);
                $copy_right_entry->updateDefaultOption();
                break;
            }
        }

        ilUtil::sendSuccess($this->lng->txt("msg_obj_modified"), true);
        $this->ctrl->redirect($this, "configure");
    }

    public function createObject($redirect = true)
    {
        $entry_gui = new copyrightEntryGUI(new copyrightEntry(), $this);
        $entry_gui->initForm();

        if ($entry_gui->form->checkInput()) {
            $entry_gui->createEntry();
            ilUtil::sendSuccess($this->plugin->txt("option_added"), $redirect);

            if ($redirect) {
                $this->ctrl->redirect($this);
            }
        }

        $entry_gui->form->setValuesByPost();
        $this->tpl->setContent($entry_gui->form->getHTML());
    }

    public function editEntry()
    {
        /**
         * @var $entry_gui     copyrightEntryGUI
         */
        $this->ctrl->saveParameter($this, "option_id");
        $entry_gui = new copyrightEntryGUI(new copyrightEntry($_GET["option_id"]), $this);
        $entry_gui->initForm("update");
        $entry_gui->setFormValuesByArray();
        $this->tpl->setContent($entry_gui->form->getHTML());
    }

    /**
     * @param bool $redirect
     */
    public function updateObject($redirect = true)
    {
        /**
         * @var $entry_gui copyrightEntryGUI
         */
        $entry_gui = new copyrightEntryGUI(new copyrightEntry($_GET["option_id"]), $this);
        $entry_gui->initForm("update");

        if ($entry_gui->form->checkInput()) {
            if ($entry_gui->getEntry()->getDefaultOption() && !$entry_gui->form->getInput("option_active")){
                ilUtil::sendFailure($this->plugin->txt("msg_set_inactive"));
                return $this->editEntry();
            }

            $entry_gui->createEntry();
            ilUtil::sendSuccess($this->plugin->txt("option_updated"), $redirect);

            if ($redirect) {
                $this->ctrl->redirect($this);
            }
        }

        $entry_gui->form->setValuesByPost();
        $this->tpl->setContent($entry_gui->form->getHTML());
    }

    public function getLink()
    {
        return $this->ctrl->getLinkTarget($this, "configure");
    }
}
