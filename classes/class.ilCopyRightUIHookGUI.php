<?php
include_once("./Services/UIComponent/classes/class.ilUIHookPluginGUI.php");

class ilCopyRightUIHookGUI extends ilUIHookPluginGUI
{
    function modifyGUI($a_comp, $a_part, $a_par = [])
    {
        /** @var ilCtrl $ilCtrl */
        /** @var ilTabsGUI $ilTabs */
        global $ilCtrl, $ilTabs;

        switch ($a_part) {
            case "tabs":
                if (in_array($ilCtrl->getCmdClass(), ["ilobjectownershipmanagementgui", "ilobjworkspacerootfoldergui", "ilobjworkspacefoldergui"])) {
                    $plugin = new ilCopyRightPlugin();
                    $ilTabs->addTab(
                        "showFiles",
                        $plugin->txt("text_file_tab"),
                        $ilCtrl->getLinkTargetByClass(["iluipluginroutergui", "ilcopyrightpagegui"], "showFiles") );

                    $_SESSION["CopyRight"]["TabTarget"] = $ilTabs->target;
                }

                if ($ilCtrl->getCmdClass() == "ilcopyrightpagegui") {
                    if (isset($_SESSION["CopyRight"]["TabTarget"])) {
                        $ilTabs->target = $_SESSION["CopyRight"]["TabTarget"];
                    }

                    $ilTabs->activateTab("showFiles");
                }
                break;

            default:
                break;
        }
    }
}
