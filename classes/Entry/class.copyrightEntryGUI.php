<?php

require_once("./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/CopyRight/classes/class.ilCopyRightPlugin.php");
require_once("class.copyrightEntry.php");
require_once("./Services/UIComponent/AdvancedSelectionList/classes/class.ilAdvancedSelectionListGUI.php");
require_once("./Services/Form/classes/class.ilMultiSelectInputGUI.php");

class copyrightEntryGUI
{

    /**
     * @var ilTemplate
     */
    protected $html;

    /**
     * @var copyrightEntry
     */
    protected $entry;

    /**
     * @var ilPropertyFormGUI
     */
    public $form;

    /**
     * @var ilTemplate
     */
    protected $tpl;

    /**
     * @param copyrightEntry $entry
     * @param null $parent_gui
     */
    public function __construct(copyrightEntry $entry, $parent_gui = null)
    {
        global $ilCtrl, $tpl;

        /**
         * @var $ilCtrl ilCtrl
         */
        $this->ctrl = $ilCtrl;
        $this->tpl = $tpl;
        $this->pl = new ilCopyRightPlugin();
        $this->entry = $entry;
        $this->parent_gui = $parent_gui;
    }

    /**
     * @param string $mode
     */
    public function initForm($mode = "create")
    {
        global $lng;

        require_once("./Services/Form/classes/class.ilPropertyFormGUI.php");

        /**
         * @var $lng ilLanguage
         */
        $lng->loadLanguageModule("meta");
        $this->form = new ilPropertyFormGUI();
        $this->form->setTitle($this->pl->txt("conf_form_title"));
        $this->form->setFormAction($this->ctrl->getFormAction($this->parent_gui));

        foreach (copyrightEntry::getAllLanguageIds() as $language) {
            $te = new ilTextInputGUI($lng->txt("meta_l_" . $language), "title_" . $language);
            $te->setRequired(copyrightEntry::isDefaultLanguage($language));
            $this->form->addItem($te);
            $tea = new ilTextAreaInputGUI($this->pl->txt("info_text") . " " . $lng->txt("meta_l_" . $language), "info_text_" . $language);
            $tea->setRows(5);
            $tea->setUseRte(true);
            $tea->setRequired(copyrightEntry::isDefaultLanguage($language));
            $tea->addPlugin("latex");
            $tea->addButton("latex");
            $tea->addButton("pastelatex");
            $this->form->addItem($tea);
        }

        $cb = new ilCheckboxInputGUI($this->pl->txt("option_active"), "option_active");
        $this->form->addItem($cb);

        $this->form->addCommandButton($mode . "Object", $this->pl->txt("common_create"));
        $this->form->addCommandButton("configure", $this->pl->txt("common_cancel"));
    }

    /**
     * @return array
     */
    public function setFormValuesByArray()
    {
        $values = [];

        foreach ($this->entry->getTranslations() as $k => $v) {
            $values["title_" . $k] = $v["title"];
            $values["info_text_" . $k] = $v["info_text"];
        }

        $values["option_active"] = $this->entry->getActive();
        $this->form->setValuesByArray($values);

        return $values;
    }

    public function createEntry()
    {
        $lngs = [];

        foreach (copyrightEntry::getAllLanguageIds() as $lng) {
            if ($this->form->getInput("title_" . $lng)) {
                $lngs[$lng]["title"] = $this->form->getInput("title_" . $lng);
            }
            if ($this->form->getInput("title_" . $lng)) {
                $lngs[$lng]["info_text"] = $this->form->getInput("info_text_" . $lng);
            }
        }

        $this->entry->setTranslations($lngs);
        $this->entry->setActive($this->form->getInput("option_active"));

        if ($this->entry->getId()) {
            $this->entry->setDefaultOption($this->entry->getDefaultOption());
        } else {
            $this->entry->setDefaultOption(false);
        }

        $this->entry->create();
    }

    /**
     * @return copyrightEntry
     */
    public function getEntry()
    {
        return $this->entry;
    }
}