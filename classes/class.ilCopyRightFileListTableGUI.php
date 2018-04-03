<?php
require_once("Services/Table/classes/class.ilTable2GUI.php");

class ilCopyRightFileListTableGUI extends ilTable2GUI
{
    protected $pl;
    protected $ctrl;
    protected $tpl;
    protected $parent_obj;
    private $options = [];
    //protected $filter; // [array]

    /**
     * Constructor
     * @param   ilCopyRightPageGUI $a_parent_obj
     * @param   string $a_parent_cmd
     * @param   array $a_data
     */
    public function __construct($a_parent_obj, $a_parent_cmd, array $a_data = null)
    {
        /**
         * @var $tpl       ilTemplate
         * @var $ilCtrl    ilCtrl
         */
        global $ilCtrl, $tpl, $lng;

        $this->pl = new ilCopyRightPlugin();
        $this->ctrl = $ilCtrl;
        $this->tpl = $tpl;
        $this->parent_obj = $a_parent_obj;

        $this->setId("filelist");

        parent::__construct($a_parent_obj, $a_parent_cmd);

        $this->setTitle($this->pl->txt("list_files_filter"));

        $this->addColumn("", "", 1);
        $this->setSelectAllCheckbox("select_file_indexes");
        $this->addMultiCommand("downloadFile", $this->pl->txt("common_download"));
        $this->addMultiCommand("filesOptionChange", $this->pl->txt("common_files_option_change"));

        $this->addColumn($lng->txt("title"), "title");
        $this->addColumn($this->pl->txt("used_in"), "used_in");
        $this->addColumn($this->pl->txt("select_list_title"), "copy_right");
        $this->addColumn($lng->txt("action"), "");

        $this->setFormAction($ilCtrl->getFormAction($a_parent_obj, $a_parent_cmd));
        $this->setRowTemplate("tpl.file_list_row.html", $this->pl->getDirectory());

        $this->setPreventDoubleSubmission(false);
        $this->setDefaultOrderField("title");
        $this->setDefaultOrderDirection("asc");
        $this->setShowRowsSelector(true);

        if (sizeof($a_data)) {
            foreach ($a_data as $key => $value) {
                foreach ($value["parent"] as $keyp => $valuep) {
                    foreach (array_keys($valuep) as $type) {
                        if ($type !== "type_title") {
                            $this->options[$type] = $valuep["type_title"];
                        }
                    }
                }
            }

            $this->options[""] = $this->pl->txt("select_all");
            asort($this->options);
        }

        $this->initFilter();
        $this->initItems($a_data);
    }

    public function initFilter()
    {
        require_once("./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/CopyRight/classes/EntryInstanceFactory/class.copyrightEntryInstanceFactory.php");

        $item = $this->addFilterItemByMetaType("parent_type", self::FILTER_SELECT, false, $this->pl->txt("used_in"));
        $item->setOptions($this->options);
        $this->filter["parent_type"] = $item->getValue();
        $item = $this->addFilterItemByMetaType("copy_right_option", self::FILTER_SELECT, false, $this->pl->txt("select_list_title"));
        $copyRightOptions = copyrightEntryInstanceFactory::getAll();
        $copyRightOptionsArray = ["" => $this->pl->txt("select_all"), "-1" => $this->pl->txt("select_without_option")];

        foreach ($copyRightOptions as $copyRightOption) {
            $copyRightOptionsArray[$copyRightOption["id"]] = $copyRightOption["title"];
        }

        $item->setOptions($copyRightOptionsArray);
        $this->filter["copy_right_option"] = $item->getValue();
    }

    protected function initItems($a_data)
    {
        require_once("./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/CopyRight/classes/EntryInstanceFactory/class.copyrightEntryInstanceFactory.php");

        $data = [];

        if (sizeof($a_data)) {
            foreach ($a_data as $index => $file_info) {
                $copyRightOption = copyrightEntryInstanceFactory::getTitleByOptionId($file_info["option_id"]);
                $copy_right_option = $this->filter["copy_right_option"];
                $parent_type = $this->filter["parent_type"];

                if ($parent_type) {
                    $flag = true;
                    foreach ($file_info["parent"] as $keyp => $valuep) {
                        foreach (array_keys($valuep) as $type) {
                            if ($type === $parent_type) {
                                $flag = false;
                            }
                        }
                    }
                    if ($flag) {
                        continue;
                    }
                }

                if ($copy_right_option) {
                    if ($copy_right_option === "-1") {
                        if ($file_info["option_id"]) {
                            continue;
                        }
                    } else if ($copy_right_option !== $file_info["option_id"]) {
                        continue;
                    }
                }

                $data[$index] = ["file_index" => $index,
                    "title" => $file_info["title"],
                    "used_in" => $file_info["used_in"],
                    "parent" => $file_info["parent"],
                    "copy_right" => $copyRightOption["title"]];
            }
        }

        $this->setData($data);
    }

    /**
     * fill row
     * @param array $data
     */
    public function fillRow($data)
    {
        $this->tpl->setVariable("VAL_ID", $data["file_index"]);
        $this->tpl->setVariable("TITLE", $data["title"]);
        $this->tpl->setVariable("USED_IN", $data["used_in"]);
        $this->tpl->setVariable("COPY_RIGHT_OPTION", $data["copy_right"]);
        $this->tpl->setCurrentBlock("actions");
        $this->tpl->setVariable("ACTIONS", $this->buildActions($data));
        $this->tpl->parseCurrentBlock();
    }

    protected function buildActions($data)
    {
        include_once "./Services/UIComponent/AdvancedSelectionList/classes/class.ilAdvancedSelectionListGUI.php";
        include_once "./Services/Link/classes/class.ilLink.php";

        global $lng, $ilCtrl;

        $ilCtrl->setParameterByClass("ilcopyrightpagegui", "file_index", $data["file_index"]);
        $actions = new ilAdvancedSelectionListGUI();
        $actions->setId($this->id . "-" . $data["file_index"]);
        $actions->setListTitle($lng->txt("actions"));
        $actions->addItem($this->pl->txt("common_edit_file_copy_right_option"), "edit", $this->ctrl->getLinkTarget($this->parent_obj, "editFileCopyRightOption"));

        foreach ($data["parent"] as $key => $value) {
            foreach ($value as $key1 => $value1) {
                if ($key1 !== "type_title") {
                    switch ($key1) {
                        case "wps":
                            $ilCtrl->setParameterByClass("ilobjworkspacefoldergui", "wsp_id", $key);
                            $actions->addItem($lng->txt("show") . " " . $value1, "",
                                "ilias.php?wsp_id=" . $key . "&amp;baseClass=ilPersonalDesktopGUI&amp;cmd=jumpToWorkspace",
                                "", "", "_blank");
                            break;
                        case "prof":
                            $actions->addItem($lng->txt("show") . " " . $value1, "",
                                "ilias.php?" . "baseClass=ilPersonalDesktopGUI&amp;cmd=jumpToProfile",
                                "", "", "_blank");
                            break;
                        case "auth":
                        case "stys":
                        case "shop":
                        case "impr":
                            if ($key !== -1) {
                                $actions->addItem($lng->txt("show") . " " . $value1, "",
                                    "ilias.php?baseClass=ilAdministrationGUI&amp;ref_id=" .
                                    $key . "&amp;cmd=jump", "", "", "_blank");
                            } else {
                                $actions->addItem($lng->txt("show") . " " . $value1, "",
                                    "ilias.php?baseClass=ilShopController&amp;cmd=firstpage",
                                    "", "", "_blank");
                            }
                            break;
                        case "att":
                            $actions->addItem($lng->txt("show") . " " . $value1, "",
                                "ilias.php?baseClass=ilMailGUI",
                                "", "", "_blank");
                            break;
                        case "root":
                            if ($key > 1) {
                                $actions->addItem($lng->txt("show") . " " . $value1, "",
                                    ilLink::_getLink($key, "file"),
                                    "", "", "_blank");
                            } else {
                                $actions->addItem($lng->txt("show") . " " . $value1, "",
                                    ilLink::_getLink($key, $key1),
                                    "", "", "_blank");
                            }
                            break;
                        default:
                            $actions->addItem($lng->txt("show") . " " . $value1, "",
                                ilLink::_getLink($key, $key1),
                                "", "", "_blank");
                            break;
                    }
                }
            }
        }

        return $actions->getHTML();
    }
}