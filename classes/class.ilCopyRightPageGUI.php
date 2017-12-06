<?php
require_once("./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/CopyRight/classes/class.ilCopyRightFileListTableGUI.php");
require_once("./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/CopyRight/classes/Helper/class.copyrightHelper.php");

/**
 * @ilCtrl_IsCalledBy ilCopyRightPageGUI: ilUIPluginRouterGUI
 * @ilCtrl_Calls ilCopyRightPageGUI: ilObjWorkspaceFolderGUI
 */
class ilCopyRightPageGUI
{
    /** @var ilCtrl $ctrl */
    protected $ctrl;

    /** @var ilTemplate $tpl */
    protected $tpl;

    /** @var ilCopyRightPlugin $plugin */
    protected $plugin;

    /** @var  ilTabsGUI $tabs */
    protected $tabs;

    /** @var  ilTabsGUI $tabs */
    protected $lng;

    /**
     * @var ilPropertyFormGUI
     */
    public $form;

    protected $user_id;

    public function __construct()
    {
        /**
         * @var ilCtrl $ilCtrl
         * @var ilTemplate $tpl
         * @var  ilTabsGUI $ilTabs
         */
        global $ilCtrl, $tpl, $ilTabs, $ilUser, $ilias, $lng;

        $this->ctrl = $ilCtrl;
        $this->tpl = $tpl;
        $this->tab = $ilTabs;
        $this->plugin = new ilCopyRightPlugin();
        $this->user_id = $ilUser->getId();
        $this->lng = $lng;

        // catch hack attempts
        if ($ilUser->isAnonymous()) {
            $ilias->raiseError($this->lng->txt("msg_not_available_for_anon"), $ilias->error_obj->MESSAGE);
        }

        $this->tab->activateTab("showFiles");
    }

    /**
     * Handles all commands, default is "showFile"
     */
    public function executeCommand()
    {
        $cmd = $this->ctrl->getCmd("showFiles");

        switch ($cmd) {
            default:
                if ($this->prepareOutput()) {
                    $this->$cmd();
                }
        }
    }

    /**
     * Show the file of the user
     */
    protected function showFiles()
    {
        global $ilToolbar;

        $files = copyrightHelper::_getAllOwnedRepositoryFiles($this->user_id);

        if (sizeof($files)) {
            include_once "Services/Form/classes/class.ilSelectInputGUI.php";
            include_once "Services/UIComponent/Button/classes/class.ilSubmitButton.php";

            $ilToolbar->setFormAction($this->ctrl->getFormAction($this, "showFiles"));
            $selCopyRightOptionsChangeFrom = copyrightHelper::_getCopyRightAllOptionSelectListFilter(
                "select_list_change_from",
                "copy_right_list_change_from",
                "select_without_option",
                true);
            $selCopyRightOptionsChangeTo = copyrightHelper::_getCopyRightActiveOptionSelectListFilter(
                "select_list_change_to",
                "copy_right_list_change_to");
            $buttonChange = ilSubmitButton::getInstance();
            $buttonChange->setCaption($this->plugin->txt("common_change"), false);
            $buttonChange->setCommand("copyRightChange");
            $selCopyRightChangeFrom = (string)$_REQUEST["copy_right_list_change_from"];
            $selCopyRightChangeTo = (string)$_REQUEST["copy_right_list_change_to"];
            $this->ctrl->setParameter($this, "copy_right_list_change_from", $selCopyRightChangeFrom);
            $this->ctrl->setParameter($this, "copy_right_list_change_to", $selCopyRightChangeTo);
            $ilToolbar->addInputItem($selCopyRightOptionsChangeFrom, true);
            $ilToolbar->addInputItem($selCopyRightOptionsChangeTo, true);
            $ilToolbar->addFormButton($this->plugin->txt("common_change"), "copyRightChange");
        }

        $tbl = new ilCopyRightFileListTableGUI($this, "showFiles", $files);

        $this->tpl->setContent($tbl->getHTML());
        $this->tpl->show();
    }

    function applyFilter()
    {
        $tbl = new ilCopyRightFileListTableGUI($this, "showFiles");
        $tbl->writeFilterToSession();
        $tbl->resetOffset();
        $this->showFiles();
    }


    function resetFilter()
    {
        $tbl = new ilCopyRightFileListTableGUI($this, "showFiles");
        $tbl->resetFilter();
        $tbl->resetOffset();
        $this->showFiles();
    }

    /**
     * Prepare the page header, tabs etc.
     */
    protected function prepareOutput()
    {
        /** @var ilLocatorGUI $ilLocator */
        global $ilLocator;

        $ilLocator->addItem($this->plugin->txt("text_file_tab"), $this->ctrl->getLinkTarget($this, "showFiles"));
        $this->tpl->getStandardTemplate();
        $this->tpl->setLocator();
        $this->tpl->setTitle($this->plugin->txt("text_file_tab"));
        $this->tpl->setDescription($this->plugin->txt("text_file_desc"));
        $this->tpl->setTitleIcon(ilObject::_getIcon("", "big", "file"));

        return true;
    }

    function copyRightChange()
    {
        $selCopyRightChangeFrom = (string)$_REQUEST["copy_right_list_change_from"];
        $selCopyRightChangeTo = (string)$_REQUEST["copy_right_list_change_to"];
        copyrightHelper::_changeCopyRightOption($selCopyRightChangeFrom, $selCopyRightChangeTo, $this->user_id);
        $this->showFiles();
    }

    function downloadFile()
    {
        if (!$_POST["select_file_indexes"]) {
            ilUtil::sendFailure($this->lng->txt("no_checkbox"), true);
            $this->showFiles();
            return;
        }

        copyrightHelper::_downloadFiles($_POST["select_file_indexes"]);
        $this->showFiles();
    }

    function filesOptionChange()
    {
        if (!$_POST["select_file_indexes"]) {
            ilUtil::sendFailure($this->lng->txt("no_checkbox"), true);
            $this->showFiles();
            return;
        }

        $this->ctrl->saveParameter($this, "copy_right_option");
        $this->initFormMultiFilesOptionChange();
        $this->tpl->setContent($this->form->getHTML());
        $this->tpl->show();
    }

    public function editFileCopyRightOption()
    {
        $this->ctrl->saveParameter($this, "file_index");
        $this->ctrl->saveParameter($this, "copy_right_option");
        $this->initForm();
        $this->setFormValuesByArray();
        $this->tpl->setContent($this->form->getHTML());
        $this->tpl->show();
    }

    public function initForm()
    {
        require_once("./Services/Form/classes/class.ilPropertyFormGUI.php");

        $this->form = new ilPropertyFormGUI();
        $this->form->setTitle($this->plugin->txt("edit_form_title"));
        $this->form->setFormAction($this->ctrl->getFormAction($this));
        $fileTitle = new ilNonEditableValueGUI($this->lng->txt("title"), "title");
        $this->form->addItem($fileTitle);
        $copyRightOptionRadio = copyrightHelper::_getCopyRightActiveOptionRadio("select_list_title", "copy_right_option_update");
        $copyRightOptionRadio->setRequired(true);
        $this->form->addItem($copyRightOptionRadio);
        $this->form->addCommandButton("update", $this->plugin->txt("common_create"));
        $this->form->addCommandButton("showFiles", $this->plugin->txt("common_cancel"));
    }

    public function initFormMultiFilesOptionChange()
    {
        require_once("./Services/Form/classes/class.ilPropertyFormGUI.php");

        $this->form = new ilPropertyFormGUI();
        $this->form->setTitle($this->plugin->txt("edit_form_title"));
        $this->form->setFormAction($this->ctrl->getFormAction($this));
        $copyRightActiveOptionRadio = copyrightHelper::_getCopyRightActiveOptionRadio(
            "select_list_title",
            "copy_right_option_update");
        $copyRightActiveOptionRadio->setRequired(true);
        $this->form->addItem($copyRightActiveOptionRadio);

        foreach ($_POST["select_file_indexes"] as $select_file_index) {
            $hf = new ilHiddenInputGUI("select_file_indexes[" . $select_file_index . "]");
            $hf->setValue($select_file_index);
            $this->form->addItem($hf);
        }

        $this->form->addCommandButton("filesOptionUpdate", $this->plugin->txt("common_create"));
        $this->form->addCommandButton("showFiles", $this->plugin->txt("common_cancel"));
    }

    /**
     * @return array
     */
    public function setFormValuesByArray()
    {
        $values = [];

        $file_info_array = explode(":", $_GET["file_index"]);
        $file_detail_array = explode("|", $file_info_array[2]);
        $object_type = copyrightHelper::_getTypeByObjId($file_info_array[0]);
        if ($object_type) {
            $object = copyrightHelper::_getObjectByObjIdObjType($file_info_array[0], $object_type);
        }

        switch ($file_detail_array[0]) {
            case "email_attachment":
                require_once "./Services/Mail/classes/class.ilFileDataMail.php";

                $fileMailData = new ilFileDataMail($this->user_id);

                if (!$fileMailData->checkPath($fileMailData->getAbsolutePath($file_detail_array[1]))) {
                    ilUtil::sendFailure($this->plugin->txt("msg_no_permission"));
                    return $this->showFiles();
                }

                $values["title"] = $file_detail_array[1];
                break;
            case "profile_picture":
                $values["title"] = $this->lng->txt("personal_picture");
                break;
            case "portfolio_banner":
            case "blog_banner":
            case "poll_image":
            case "mob_preview_pic":
            case "peer_feedback":
            case "ass_feedback":
            case "exc_return":
            case "exc_instruction_files":
            case "forum":
            case "tst_solutions":
            case "lms_html_file":
            case "interactive_overlay_image":
            case "mob":
            case "book_info_file":
            case "book_post_file":
            case "bibl":
            case "exc_global_feedback_file":
            case "crs":
                if ($object->getOwner() != $this->user_id) {
                    ilUtil::sendFailure($this->plugin->txt("msg_no_permission"));
                    return $this->showFiles();
                }

                switch ($file_detail_array[0]) {
                    case "portfolio_banner":
                    case "blog_banner":
                    case "poll_image":
                        $values["title"] = $object->getImage();
                        break;
                    case "book_info_file":
                        $values["title"] = $object->getFile();
                        break;
                    case "book_post_file":
                        $values["title"] = $object->getPostFile();
                        break;
                    case "bibl":
                        $values["title"] = $object->getFilename();
                        break;
                    case "mob_preview_pic":
                        $values["title"] = $object->getVideoPreviewPic(true);
                        break;
                    case "exc_global_feedback_file":
                        require_once "./Modules/Exercise/classes/class.ilExAssignment.php";

                        $exAssignment = new ilExAssignment($file_info_array[1]);
                        $values["title"] = $exAssignment->getFeedbackFile();
                        break;
                    case "peer_feedback":
                    case "ass_feedback":
                    case "exc_return":
                    case "exc_instruction_files":
                    case "forum":
                    case "tst_solutions":
                    case "lms_html_file":
                    case "interactive_overlay_image":
                    case "mob":
                        $values["title"] = $file_detail_array[1];
                        break;
                    case "crs":
                        require_once "./Modules/Course/classes/class.ilCourseFile.php";

                        $courseFile = new ilCourseFile($file_info_array[1]);
                        $values["title"] = $courseFile->getFileName();
                        break;
                }
                break;
            case "java_applet":
            case "image_map":
            case "flash":
            case "qpl_recapitulation":
                require_once "./Modules/TestQuestionPool/classes/class.assQuestion.php";

                $question = assQuestion::_instantiateQuestion($file_info_array[1]);
                $question->loadFromDb($file_info_array[1]);

                if ($question->getOwner() != $this->user_id) {
                    ilUtil::sendFailure($this->plugin->txt("msg_no_permission"));
                    return $this->showFiles();
                }

                switch ($file_detail_array[0]) {
                    case "java_applet":
                        $values["title"] = $question->getJavaAppletFilename();
                        break;
                    case "image_map":
                        $values["title"] = $question->getImageFilename();
                        break;
                    case "flash":
                        $values["title"] = $question->getApplet();
                        break;
                    case "qpl_recapitulation":
                        $questionSuggestion = $question->getSuggestedSolution();
                        $values["title"] = strlen($questionSuggestion["value"]["filename"]) ?
                            $questionSuggestion["value"]["filename"] : $questionSuggestion["value"]["name"];
                        break;
                }

                break;
            default:
                if (strpos($file_detail_array[0], "mob_") === 0) {
                    $mediaItem = new ilMediaItem($file_info_array[1]);
                    $values["title"] = $mediaItem->getLocation();
                } else {
                    $values["title"] = $object->getTitle();
                }
                break;
        }

        $values["copy_right_option_update"] = copyrightHelper::_getCopyRightValue($file_info_array[0], $file_info_array[1], $file_info_array[2]);
        $this->form->setValuesByArray($values);

        return $values;
    }

    /**
     * @param bool $redirect
     */
    public function update($redirect = true)
    {
        $this->initForm();

        if ($this->form->checkInput()) {
            $file_info_array = explode(":", $_GET["file_index"]);
            $file_index = $file_info_array[0];
            $sub_id = $file_info_array[1];
            $file_location = $file_info_array[2];

            copyrightHelper::_updateCopyRightOptionByObjectId($file_index, $_POST["copy_right_option_update"], $sub_id, $file_location);
            ilUtil::sendSuccess($this->plugin->txt("option_updated"), $redirect);

            if ($redirect) {
                $this->ctrl->saveParameter($this, "copy_right_option");
                $this->ctrl->redirect($this);
            }
        }

        $this->form->setValuesByPost();
        $this->tpl->setContent($this->form->getHTML());
        $this->tpl->show();
    }

    /**
     * @param bool $redirect
     */
    public function filesOptionUpdate($redirect = true)
    {
        $this->initFormMultiFilesOptionChange();

        if ($this->form->checkInput()) {
            copyrightHelper::_updateCopyRightOptionByObjectIds($_POST["select_file_indexes"], $_POST["copy_right_option_update"]);
            ilUtil::sendSuccess($this->plugin->txt("option_updated"), $redirect);

            if ($redirect) {
                $this->ctrl->saveParameter($this, "copy_right_option");
                $this->ctrl->redirect($this);
            }
        }

        $this->tpl->setContent($this->form->getHTML());
        $this->tpl->show();
    }
}