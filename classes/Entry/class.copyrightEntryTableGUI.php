<?php
require_once("./Services/Table/classes/class.ilTable2GUI.php");
require_once("./Services/UIComponent/AdvancedSelectionList/classes/class.ilAdvancedSelectionListGUI.php");
require_once("./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/CopyRight/classes/EntryInstanceFactory/class.copyrightEntryInstanceFactory.php");

class copyrightEntryTableGUI extends ilTable2GUI
{
    protected $pl;
    protected $ctrl;
    protected $tpl;
    protected $parent_obj;

    /**
     * @param ilCopyRightConfigGUI $a_parent_obj
     */
    public function __construct(ilCopyRightConfigGUI $a_parent_obj)
    {
        /**
         * @var $tpl       ilTemplate
         * @var $ilCtrl    ilCtrl
         * @var $ilToolbar ilToolbarGUI
         */
        global $ilCtrl, $ilToolbar, $tpl;

        $this->pl = new ilCopyRightPlugin();
        $this->ctrl = $ilCtrl;
        $this->tpl = $tpl;
        $this->parent_obj = $a_parent_obj;
        $this->setId("copy_right_option_list");
        parent::__construct($a_parent_obj);
        $this->setRowTemplate("tpl.copy_right_option_list.html", $this->pl->getDirectory());
        $this->setTitle($this->pl->txt("list_title"));
        //
        // Columns
        $this->addColumn($this->pl->txt("option_title"), "title", "auto");
        $this->addColumn($this->pl->txt("option_default"), "default", "auto");
        $this->addColumn($this->pl->txt("option_active"), "active", "auto");
        // ...
        // Header
        $ilToolbar->addButton($this->pl->txt("add_new"), $this->ctrl->getLinkTarget($a_parent_obj, "addOption"));
        $this->setFormAction($ilCtrl->getFormAction($a_parent_obj));
        $this->setDefaultOrderField("default");
        $this->setDefaultOrderDirection("desc");
        $this->setData(copyrightEntryInstanceFactory::getAll());
    }

    /**
     * Prepare output
     */
    function prepareOutput()
    {
        global $lng;

        if ($this->dataExists()) {
            $this->addCommandButton("saveDefaultOption", $lng->txt("save"));
        }
    }

    /**
     * @param array $a_set
     */
    public function fillRow($a_set)
    {
        $this->tpl->setVariable("TITLE", (empty($a_set["title"]) ? "(null)" : $a_set["title"]));
        $this->tpl->setVariable("ACTIVE", $a_set["active"] ? "checked" : "");
        $this->ctrl->setParameter($this->getParentObject(), "option_id", $a_set["id"]);
        $this->tpl->setVariable("NR", $a_set["id"]);
        $this->tpl->setVariable("DEF_CHECKED", $a_set["default_option"] ? "checked=\"checked\"" : "");
        $this->tpl->setVariable("EDIT_LINK", $this->ctrl->getLinkTarget($this->parent_obj, "editEntry"));
    }
}