<?php

class copyrightHelper
{
    static function _downloadFiles($fileIds)
    {
        include_once "./Services/Utilities/classes/class.ilUtil.php";
        include_once "./Modules/File/classes/class.ilObjFile.php";
        include_once "./Modules/File/classes/class.ilFileException.php";
        include_once "./Services/MediaObjects/classes/class.ilObjMediaObject.php";

        global $ilUser;

        // create temporary file to download
        $tmpdir = ilUtil::ilTempnam();
        ilUtil::makeDir($tmpdir);

        try {
            if (!$fileIds)
                return;
            // copy each selected object
            foreach ($fileIds as $fileId) {
                // get object
                $file_info_array = explode(":", $fileId);
                $file_detail_array = explode("|", $file_info_array[2]);
                $object_type = copyrightHelper::_getTypeByObjId($file_info_array[0]);

                if ($object_type) {
                    $object = copyrightHelper::_getObjectByObjIdObjType($file_info_array[0], $object_type);
                }

                $file_name_prefix = $file_detail_array[0] . "_" . $file_info_array[1] . "_" .
                    $file_info_array[0];
                $file_name = $file_detail_array[1];

                switch ($file_detail_array[0]) {
                    case "email_attachment":
                        require_once "./Services/Mail/classes/class.ilFileDataMail.php";

                        $fileMailData = new ilFileDataMail($ilUser->getId());
                        $file_name = "";
                        $file_path = $fileMailData->getAbsolutePath($file_detail_array[1]);
                        $file_name_prefix = $file_info_array[2];
                        break;
                    case "profile_picture":
                        $file_name = $object->getTitle() . ".jpg";
                        $file_path = ILIAS_ABSOLUTE_PATH . "/" . ilUtil::getWebspaceDir() .
                            "/usr_images/upload_" . $ilUser->getId() . "pic";
                        break;
                    case "forum":
                        require_once "./Modules/Forum/classes/class.ilFileDataForum.php";

                        $file_obj = new ilFileDataForum();
                        $file_name = $file_detail_array[1];
                        $file_path = $file_obj->getForumPath() . "/" . $file_info_array[0] . "_" .
                            $file_info_array[1] . "_" . $file_detail_array[1];
                        $file_name_prefix .= "_" . $file_detail_array[2];
                        break;
                    case "bibl":
                        $file_name = $object->getFilename();
                        $file_path = $object->getFileAbsolutePath();
                        break;
                    case "mob_preview_pic":
                        $file_name = $object->getVideoPreviewPic(true);
                        $file_path = $object->getVideoPreviewPic();
                        break;
                    case "lms_html_file":
                        $file_name = $file_detail_array[1];
                        $file_path = ILIAS_ABSOLUTE_PATH . $object->getDataDirectory() .
                            trim($file_detail_array[2]) . "/" . $file_detail_array[1];
                        $file_name_prefix .= "_" . $file_detail_array[2];
                        break;
                    case "interactive_overlay_image":
                        $file_name = $file_detail_array[1];
                        $file_path = $object->getDataDirectory() . "/" . "overlays/" . $file_detail_array[1];
                        break;
                    case "crs":
                        require_once "./Modules/Course/classes/class.ilCourseFile.php";

                        $courseFile = new ilCourseFile($file_info_array[1]);
                        $file_name = $courseFile->getFileName();
                        $file_path = $courseFile->getAbsolutePath();
                        break;
                    case "mob":
                        $file_name = $file_detail_array[1];
                        $file_path = $object->getDataDirectory() . trim($file_detail_array[2]) . "/" .
                            $file_detail_array[1];
                        $file_name_prefix .= "_" . $file_detail_array[2];
                        break;
                    case "ass_feedback":
                    case "peer_feedback":
                    case "exc_return":
                    case "exc_global_feedback_file":
                    case "exc_instruction_files":
                        require_once "./Modules/Exercise/classes/class.ilExAssignment.php";

                        $exAssignment = new ilExAssignment($file_info_array[1]);

                        switch ($file_detail_array[0]) {
                            case "ass_feedback":
                                require_once "./Modules/Exercise/classes/class.ilFSStorageExercise.php";

                                $storage = new ilFSStorageExercise($exAssignment->getExerciseId(), $exAssignment->getId());
                                $file_path = $storage->getFeedbackFilePath($file_detail_array[2], $file_detail_array[1]);
                                $file_name_prefix .= "_" . $file_detail_array[2];

                                break;
                            case "peer_feedback":
                                require_once "./Modules/Exercise/classes/class.ilExcCriteria.php";

                                $item = ilExcCriteria::getInstanceByType("file");
                                $item->setPeerReviewContext(
                                    $exAssignment,
                                    $ilUser->getId(),
                                    $file_detail_array[3]
                                );
                                $files = $item->getFiles();

                                foreach ($files as $file) {
                                    $file_name = basename($file);
                                    if ($file_name === $file_detail_array[1]) {
                                        $file_path = $file;
                                        break;
                                    }
                                }

                                $file_name = $file_detail_array[1];
                                $file_name_prefix = $file_detail_array[0] . "_" . $file_detail_array[2] . "_" .
                                    $file_detail_array[3] . "_" . $file_info_array[2] . "_" . $file_info_array[0];
                                break;
                            case "exc_return":
                                require_once "./Modules/Exercise/classes/class.ilExSubmission.php";

                                $exSubmission = new ilExSubmission($exAssignment, $ilUser->getId());
                                $file = $exSubmission->getFiles([$file_detail_array[2]]);
                                $file_name = $file_detail_array[1];
                                $file_path = $file[0]["filename"];
                                $file_name_prefix .= "_" . $file_detail_array[2];

                                break;
                            case "exc_global_feedback_file":
                                $file_name = $exAssignment->getFeedbackFile();
                                $file_path = $exAssignment->getGlobalFeedbackFilePath();
                                break;
                            case "exc_instruction_files":
                                $files = $exAssignment->getFiles();
                                $file_index = -1;
                                $i = 0;

                                foreach ($files as $file) {
                                    if ($file["name"] == $file_detail_array[1]) {
                                        $file_index = $i;
                                    }
                                    $i++;
                                }

                                $file_name = $file_detail_array[1];
                                $file_path = $files[$file_index]["fullpath"];
                                break;
                        }
                        break;
                    case "portfolio_banner":
                    case "blog_banner":
                    case "poll_image":
                        $file_name = $object->getImage();
                        $file_path = ILIAS_ABSOLUTE_PATH . $object->getImageFullPath();
                        break;
                    case "book_info_file":
                        $file_name = $object->getFile();
                        $file_path = $object->getFileFullPath();
                        break;
                    case "book_post_file":
                        $file_name = $object->getPostFile();
                        $file_path = $object->getPostFileFullPath();
                        break;
                    case "java_applet":
                    case "image_map":
                    case "flash":
                    case "qpl_recapitulation":
                    case "tst_solutions":
                        require_once "./Modules/TestQuestionPool/classes/class.assQuestion.php";

                        $question = assQuestion::_instantiateQuestion($file_info_array[1]);
                        $question->loadFromDb($file_info_array[1]);

                        switch ($file_detail_array[0]) {
                            case "java_applet":
                                $file_name = $question->getJavaAppletFilename();
                                $file_path = $question->getJavaPath() . $question->getJavaAppletFilename();
                                break;
                            case "image_map":
                                $file_name = $question->getImageFilename();
                                $file_path = $question->getImagePath() . $question->getImageFilename();
                                break;
                            case "flash":
                                $file_name = $question->getApplet();
                                $file_path = $question->getFlashPath() . $question->getApplet();
                                break;
                            case "qpl_recapitulation":
                                $questionSuggestion = $question->getSuggestedSolution();
                                $file_name = $questionSuggestion["value"]["name"];
                                $file_path = $question->getSuggestedSolutionPath() . $questionSuggestion["value"]["name"];
                                break;
                            case "tst_solutions":
                                $file_name = $file_detail_array[1];
                                $file_path = $question->getFileUploadPath(
                                        $file_detail_array[3],
                                        $file_info_array[1],
                                        $file_detail_array[5]
                                    ) . $file_detail_array[1];
                                $file_name_prefix .= "_" . $file_detail_array[3] . "_" . $file_detail_array[2] . "_";
                                break;
                        }
                        break;
                    default:
                        if (strpos($file_detail_array[0], "mob_") === 0) {
                            $mediaItem = new ilMediaItem($file_info_array[1]);
                            $file_name = $mediaItem->getLocation();
                            $file_path = $object::_getDirectory($object->getId()) . DIRECTORY_SEPARATOR .
                                $mediaItem->getLocation();
                        } else {
                            $file_name = $object->getFileName();
                            $file_path = $object->getDirectory($object->getVersion()) . "/" . $object->getFileName();
                        }
                        break;
                }

                self::_copyFile(
                    $file_name,
                    $file_path,
                    $file_name_prefix,
                    $tmpdir);
            }

            // compress the folder
            $deliverFilename = ilUtil::getAsciiFilename("ilias files") . ".zip";
            $tmpzipfile = ilUtil::ilTempnam() . ".zip";
            ilUtil::zip($tmpdir, $tmpzipfile, true);
            ilUtil::delDir($tmpdir);
            ilUtil::deliverFile(
                $tmpzipfile,
                $deliverFilename,
                "",
                false,
                true,
                true);
        } catch (ilFileException $e) {
            ilUtil::sendInfo($e->getMessage(), true);
        }
    }

    public static function _getCopyRightActiveOptionSelectList($selectListTitleKey, $postVar, $selectedValue = "")
    {
        require_once("./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/CopyRight/classes/class.ilCopyRightPlugin.php");
        require_once("./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/CopyRight/classes/EntryInstanceFactory/class.copyrightEntryInstanceFactory.php");
        require_once("./Services/Form/classes/class.ilSelectInputGUI.php");

        $copyRightPlugin = new ilCopyRightPlugin();
        $copyRightOptions = copyrightEntryInstanceFactory::getActiveOption();
        $copyRightOptionsArray = [];

        foreach ($copyRightOptions as $copyRightOption) {
            $copyRightOptionsArray[$copyRightOption["id"]] = $copyRightOption["title"];
            if (!$selectedValue) {
                if ($copyRightOption["default_option"]) {
                    $selectedValue = $copyRightOption["id"];
                }
            }
        }

        $copy_right_option = new ilSelectInputGUI($copyRightPlugin->txt($selectListTitleKey), $postVar);
        $copy_right_option->setOptions($copyRightOptionsArray);

        if ($selectedValue) {
            $copy_right_option->setValue($selectedValue);
        }

        return $copy_right_option;
    }

    public static function _getCopyRightActiveOptionRadio($selectListTitleKey, $postVar, $selectedValue = "")
    {
        require_once("./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/CopyRight/classes/class.ilCopyRightPlugin.php");
        require_once("./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/CopyRight/classes/EntryInstanceFactory/class.copyrightEntryInstanceFactory.php");
        require_once("./Services/Form/classes/class.ilRadioGroupInputGUI.php");
        require_once("./Services/Form/classes/class.ilCustomInputGUI.php");
        require_once("./Services/Form/classes/class.ilNonEditableValueGUI.php");

        $copyRightPlugin = new ilCopyRightPlugin();
        $copy_right_option = new ilRadioGroupInputGUI($copyRightPlugin->txt($selectListTitleKey), $postVar);
        $copyRightOptions = copyrightEntryInstanceFactory::getActiveOption();

        foreach ($copyRightOptions as $copyRightOption) {
            $opt = new ilRadioOption($copyRightOption["title"], $copyRightOption["id"]);
            $infoText = new ilCustomInputGUI("", "");
            $infoText->setHtml($copyRightOption["info_text"]);
            $opt->addSubItem($infoText);
            $copy_right_option->addOption($opt);

            if (!$selectedValue) {
                if ($copyRightOption["default_option"]) {
                    $selectedValue = $copyRightOption["id"];
                }
            }
        }

        if ($selectedValue) {
            $copy_right_option->setValue($selectedValue);
        } else {
            $copy_right_option->setValue($copyRightOptions[0]["id"]);
        }

        $copy_right_option->setRequired(true);

        return $copy_right_option;
    }

    public static function _getCopyRightActiveOptionSelectListFilter($selectListTitleKey, $postVar, $selectedValue = "")
    {
        require_once("./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/CopyRight/classes/class.ilCopyRightPlugin.php");
        require_once("./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/CopyRight/classes/EntryInstanceFactory/class.copyrightEntryInstanceFactory.php");

        $copyRightPlugin = new ilCopyRightPlugin();
        $copyRightOptions = copyrightEntryInstanceFactory::getActiveOption();
        $copyRightOptionsArray = [];

        foreach ($copyRightOptions as $copyRightOption) {
            $copyRightOptionsArray[$copyRightOption["id"]] = $copyRightOption["title"];
        }

        $copy_right_option = new ilSelectInputGUI($copyRightPlugin->txt($selectListTitleKey), $postVar);
        $copy_right_option->setOptions($copyRightOptionsArray);

        if ($selectedValue) {
            $copy_right_option->setValue($selectedValue);
        }

        return $copy_right_option;
    }

    public static function _getCopyRightAllOptionSelectListFilter($selectListTitleKey, $postVar, $extraValueTitleKey = "", $withExtraValue = false, $selectedValue = "")
    {
        require_once("./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/CopyRight/classes/class.ilCopyRightPlugin.php");
        require_once("./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/CopyRight/classes/EntryInstanceFactory/class.copyrightEntryInstanceFactory.php");

        $copyRightPlugin = new ilCopyRightPlugin();
        $copyRightOptions = copyrightEntryInstanceFactory::getAll();
        $copyRightOptionsArray = [];

        if ($withExtraValue) {
            $copyRightOptionsArray = ["" => $copyRightPlugin->txt($extraValueTitleKey)];
        }

        foreach ($copyRightOptions as $copyRightOption) {
            $copyRightOptionsArray[$copyRightOption["id"]] = $copyRightOption["title"];
        }

        $copy_right_option = new ilSelectInputGUI($copyRightPlugin->txt($selectListTitleKey), $postVar);
        $copy_right_option->setOptions($copyRightOptionsArray);

        if ($selectedValue) {
            $copy_right_option->setValue($selectedValue);
        }

        return $copy_right_option;
    }

    private static function _copyFile($file_name, $file_path, $file_name_prefix, $tmpdir)
    {
        // copy to temporary directory
        $newFilename = $tmpdir . DIRECTORY_SEPARATOR .
            str_replace(
                ["_ _", "__"],
                "_",
                ilUtil::getASCIIFilename($file_name_prefix . "_" . $file_name)
            );

        if (!copy($file_path, trim($newFilename, "_")))
            throw new ilFileException("Could not copy " . $file_path . " to " . $newFilename);

        touch(trim($newFilename, "_"), filectime($file_path));
    }

    /**
     * get object type by object id
     *
     * @param    int $a_obj_id object id
     * @return    string    object type
     */
    static function _getTypeByObjId($a_obj_id)
    {
        global $ilias, $ilDB;

        // read object data
        $q = "SELECT * FROM object_data " .
            "WHERE obj_id=" . $ilDB->quote($a_obj_id, "integer");
        $object_set = $ilias->db->query($q);

        if ($object_set->numRows() == 0) {
            return false;
        }

        $object_rec = $object_set->fetchRow(DB_FETCHMODE_ASSOC);

        return $object_rec["type"];
    }

    /**
     * get object by object id, object type
     *
     * @param    int $a_obj_id object id
     * @return    object
     */
    static function _getObjectByObjIdObjType($a_obj_id, $a_obj_type)
    {
        global $ilias;

        if ($a_obj_type === "mob") {
            include_once "./Services/MediaObjects/classes/class.ilObjMediaObject.php";

            $object =& new ilObjMediaObject($a_obj_id);
        } else { //if (in_array($a_obj_type, ["file", "book", "crs", "bibl"])) {
            $object =& $ilias->obj_factory->getInstanceByObjId($a_obj_id);
        }

        return $object;
    }

    /**
     * Get all ids of objects user owns
     *
     * @param int $a_user_id
     * @return array
     */
    static function _getAllOwnedRepositoryFiles($a_user_id)
    {
        global $ilDB, $lng, $tree, $ilPluginAdmin, $rbacreview, $ilUser;

        require_once("./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/CopyRight/classes/class.ilCopyRightPlugin.php");

        $copyRightPlugin = new ilCopyRightPlugin();

        $file_list = [];

        // restrict to file and media pool object
        $types = ["file", "mep"];

        $sql = "SELECT od.obj_id,od.type,od.title,oref.ref_id, od.owner FROM object_data od" .
            " JOIN object_reference oref ON(oref.obj_id = od.obj_id)" .
            " JOIN tree ON (tree.child = oref.ref_id)";

        if (!$a_user_id) {
            $sql .= " LEFT JOIN usr_data ud ON (ud.usr_id = od.owner)" .
                " WHERE (od.owner < " . $ilDB->quote(1, "integer") .
                " OR od.owner IS NULL OR ud.login IS NULL)";
        }

        $sql .= " AND tree.tree > " . $ilDB->quote(0, "integer");

        $res = $ilDB->query($sql);
        while ($row = $ilDB->fetchAssoc($res)) {
            $obj_type = $lng->txt("obj_" . $row["type"]);

            switch ($row["type"]) {
                case "file":
                    if ($row["owner"] == $a_user_id) {
                        $parent_node_data = $tree->getParentNodeData($row["ref_id"]);
                        $parent_type_title = $lng->txt("obj_" . $parent_node_data["type"]);

                        if ($parent_node_data["type"] === "root") {
                            $parent_type_title_with_path = $parent_type_title;
                        } else {
                            $parent_type_title_with_path = $parent_type_title . " (" .
                                self::_buildPath(
                                    $tree,
                                    $row["ref_id"],
                                    "ref_id",
                                    true
                                ) . ")";
                        }

                        $array_index = $row["obj_id"] . ":0:file";
                        $file_list[$array_index]["title"] = $row["title"];
                        $file_list[$array_index]["option_id"] = self::_getCopyRightValue(
                            $row["obj_id"],
                            null,
                            "file"
                        );
                        $file_list[$array_index]["used_in"] .= "<li>" . $parent_type_title_with_path . "</li>";
                        $file_list[$array_index]["parent"][$row["ref_id"]][$parent_node_data["type"]] = $obj_type .
                            " (" . ($parent_node_data["type"] === "root" ? $parent_type_title : $parent_node_data["title"]) . ")";
                        $file_list[$array_index]["parent"][$row["ref_id"]]["type_title"] = $parent_type_title;
                    }
                    break;
                case "mep":
                    include_once "./Modules/MediaPool/classes/class.ilObjMediaPool.php";
                    include_once "./Services/MediaObjects/classes/class.ilObjMediaObject.php";

                    $objMediaPool = new ilObjMediaPool($row["ref_id"]);
                    $mediaPoolTree = $objMediaPool->getTree();
                    $sql_mep = "SELECT DISTINCT mep_tree.*, object_data.*, mep_item.obj_id mep_obj_id " .
                        "FROM mep_tree JOIN mep_item ON (mep_tree.child = mep_item.obj_id) " .
                        " JOIN object_data ON (mep_item.foreign_id = object_data.obj_id) " .
                        " WHERE mep_tree.mep_id = " . $ilDB->quote($row["obj_id"], "integer") .
                        " AND object_data.type = " . $ilDB->quote("mob", "text");
                    $res_mep = $ilDB->query($sql_mep);

                    while ($row_mep = $ilDB->fetchAssoc($res_mep)) {
                        $mediaObject = new ilObjMediaObject($row_mep["obj_id"]);

                        if ($mediaObject->getOwner() == $a_user_id) {
                            $mediaItems = $mediaObject->getMediaItems();
                            $mediaItemsFileNames = [];

                            foreach ($mediaItems as $mediaItem) {
                                if ($mediaItem->getLocationType() === "LocalFile") {
                                    $mediaItemsFileNames[] = $mediaItem->getLocation();
                                    $array_index = $mediaObject->getId() . ":" . $mediaItem->getId() . ":" .
                                        "mob_" . $mediaItem->getPurpose();
                                    $file_list[$array_index]["title"] = $mediaItem->getlocation();
                                    $file_list[$array_index]["option_id"] = self::_getCopyRightValue(
                                        $mediaObject->getId(),
                                        $mediaItem->getId(),
                                        "mob_" . $mediaItem->getPurpose());
                                    $file_list[$array_index]["used_in"] .= "<li>" . $obj_type . " (" .
                                        self::_buildPath(
                                            $tree,
                                            $row["ref_id"],
                                            "ref_id",
                                            true) .
                                        " &raquo; " . $row["title"] . " &raquo; " .
                                        self::_buildPath(
                                            $mediaPoolTree,
                                            $row_mep["mep_obj_id"],
                                            "mep_obj_id",
                                            false) .
                                        " &raquo; " . $mediaItem->getPurpose() . ")</li>";
                                    $file_list[$array_index]["parent"][$row["ref_id"]][$row["type"]] = $obj_type .
                                        " (" . $row["title"] . ")";
                                    $file_list[$array_index]["parent"][$row["ref_id"]]["type_title"] = $obj_type;
                                }
                            }

                            $dir_files = iterator_to_array(new RecursiveIteratorIterator(
                                new RecursiveDirectoryIterator($mediaObject->getDataDirectory())));

                            foreach ($dir_files as $f => $d) {
                                $pi = pathinfo($f);

                                if (!is_dir($f)) {
                                    $sub_dir = str_replace(
                                        "\\",
                                        "/",
                                        substr($pi["dirname"], strlen($mediaObject->getDataDirectory())));
                                    $sub_dir = ($sub_dir ? $sub_dir : " ");
                                    $array_index = $mediaObject->getId() . ": :" . "mob|" .
                                        $pi["basename"] . "|" . $sub_dir;
                                    $file_list[$array_index]["title"] = $pi["basename"];
                                    $file_list[$array_index]["option_id"] = self::_getCopyRightValue(
                                        $mediaObject->getId(),
                                        0,
                                        "mob|" . $pi["basename"] . "|" . $sub_dir);
                                    $file_list[$array_index]["used_in"] .= "<li>" . $obj_type . " (" .
                                        self::_buildPath(
                                            $tree,
                                            $row["ref_id"],
                                            "",
                                            true) .
                                        " &raquo; " . $row["title"] . " &raquo; " .
                                        self::_buildPath(
                                            $mediaPoolTree,
                                            $row_mep["mep_obj_id"],
                                            "mep_obj_id",
                                            false) .
                                        str_replace("/", " &raquo; ", trim($sub_dir)) . ")</li>";
                                    $file_list[$array_index]["parent"][$row["ref_id"]][$row["type"]] = $obj_type .
                                        " (" . $row["title"] . ")";
                                    $file_list[$array_index]["parent"][$row["ref_id"]]["type_title"] = $obj_type;
                                }
                            }
                        }
                    }

                    include_once("./Modules/MediaPool/classes/class.ilMediaPoolPage.php");

                    $pages = ilMediaPoolPage::getAllPages("mep", $row["obj_id"]);

                    foreach ($pages as $page) {
                        if (ilMediaPoolPage::_exists($page["id"])) {
                            $mepPage = new ilMediaPoolPage($page["id"]);
                            self::_fillFileArrayFromPageContent(
                                $file_list,
                                $mepPage->getXMLContent(),
                                $obj_type,
                                $row["type"],
                                $row["ref_id"],
                                $row["title"],
                                self::_buildPath(
                                    $mediaPoolTree,
                                    $page["id"],
                                    "",
                                    false),
                                $a_user_id,
                                $lng);
                        }
                    }
                    break;
                case "sahs":
                    include_once "./Modules/Scorm2004/classes/class.ilObjSCORM2004LearningModule.php";
                    include_once("./Modules/Scorm2004/classes/class.ilSCORM2004Page.php");

                    $objSCORM = new ilObjSCORM2004LearningModule($row["ref_id"]);
                    $sCORMPoolTree = $objSCORM->getTree();
                    $pages = ilSCORM2004Page::getAllPages("sahs", $row["obj_id"]);

                    foreach ($pages as $page) {
                        if (ilSCORM2004Page::_exists("sahs", $page["id"])) {
                            $sCORMPage = new ilSCORM2004Page($page["id"]);
                            self::_fillFileArrayFromPageContent(
                                $file_list,
                                $sCORMPage->getXMLContent(),
                                $obj_type,
                                $row["type"],
                                $row["ref_id"],
                                $row["title"],
                                self::_buildPath(
                                    $sCORMPoolTree,
                                    $page["id"],
                                    "",
                                    false),
                                $a_user_id,
                                $lng);
                        }
                    }
                    break;
                case "blog":
                    include_once "./Modules/Blog/classes/class.ilBlogPosting.php";
                    include_once "./Modules/Blog/classes/class.ilObjBlog.php";

                    $blog = new ilObjBlog($row["ref_id"]);
                    $posts = ilBlogPosting::getAllPostings($row["obj_id"]);

                    foreach ($posts as $post) {
                        if (ilBlogPosting::_exists("blp", $post["id"])) {
                            $blogPosting = new ilBlogPosting($post["id"]);
                            self::_fillFileArrayFromPageContent(
                                $file_list,
                                $blogPosting->getXMLContent(),
                                $obj_type,
                                $row["type"],
                                $row["ref_id"],
                                $row["title"],
                                $blogPosting->getTitle(),
                                $a_user_id,
                                $lng);
                        }
                    }

                    if ($row["owner"] == $a_user_id) {
                        $lng->loadLanguageModule("blog");
                        if ($blog->getImageFullPath(true)) {
                            $array_index = $row["obj_id"] . ":0:blog_banner";
                            $file_list[$array_index]["title"] = $blog->getImage();
                            $file_list[$array_index]["option_id"] = self::_getCopyRightValue(
                                $row["obj_id"],
                                "0",
                                "blog_banner");
                            $file_list[$array_index]["used_in"] .= "<li>" . $obj_type . " (" .
                                self::_buildPath($tree, $row["ref_id"], "", true) .
                                " &raquo; " . $lng->txt("blog_banner") . ")</li>";
                            $file_list[$array_index]["parent"][$row["ref_id"]][$row["type"]] = $obj_type .
                                " (" . $row["title"] . ")";
                            $file_list[$array_index]["parent"][$row["ref_id"]]["type_title"] = $obj_type;
                        }
                    }
                    break;
                case "poll":
                    include_once "./Modules/Poll/classes/class.ilObjPoll.php";

                    $poll = new ilObjPoll($row["ref_id"]);

                    if ($row["owner"] == $a_user_id) {
                        $lng->loadLanguageModule("poll");
                        if ($poll->getImageFullPath(true)) {
                            $array_index = $row["obj_id"] . ":0:poll_image";
                            $file_list[$array_index]["title"] = $poll->getImage();
                            $file_list[$array_index]["option_id"] = self::_getCopyRightValue(
                                $row["obj_id"],
                                "0",
                                "poll_image");
                            $file_list[$array_index]["used_in"] .= "<li>" . $obj_type . " (" .
                                self::_buildPath($tree, $row["ref_id"], "", true) .
                                " &raquo; " . $lng->txt("poll_image") . ")</li>";
                            $file_list[$array_index]["parent"][$row["ref_id"]][$row["type"]] = $obj_type .
                                " (" . $row["title"] . ")";
                            $file_list[$array_index]["parent"][$row["ref_id"]]["type_title"] = $obj_type;
                        }
                    }
                    break;
                case "crs":
                    include_once "./Modules/Course/classes/class.ilCourseObjective.php";
                    include_once "./Modules/Course/classes/Objectives/class.ilLOPage.php";
                    include_once "./Modules/Course/classes/class.ilCourseFile.php";

                    $objectiveIds = ilCourseObjective::_getObjectiveIds($row["obj_id"]);

                    foreach ($objectiveIds as $objective_id) {
                        if (ilLOPage::_exists("lobj", $objective_id)) {
                            $loPage = new ilLOPage($objective_id);
                            $loTitle = ilCourseObjective::lookupObjectiveTitle($objective_id);
                            self::_fillFileArrayFromPageContent(
                                $file_list,
                                $loPage->getXMLContent(),
                                $obj_type,
                                $row["type"],
                                $row["ref_id"],
                                $row["title"],
                                $loTitle,
                                $a_user_id,
                                $lng);
                        }
                    }

                    if ($row["owner"] == $a_user_id) {
                        $courseInfoFiles = ilCourseFile::_readFilesByCourse($row["obj_id"]);
                        $lng->loadLanguageModule("crs");

                        foreach ($courseInfoFiles as $courseInfoFile) {
                            $array_index = $row["obj_id"] . ":" . $courseInfoFile->getFileId() . ":" . "crs";
                            $file_list[$array_index]["title"] = $courseInfoFile->getFileName();
                            $file_list[$array_index]["option_id"] = self::_getCopyRightValue(
                                $row["obj_id"],
                                $courseInfoFile->getFileId(),
                                "crs");
                            $file_list[$array_index]["used_in"] .= "<li>" . $obj_type . " (" . self::_buildPath(
                                    $tree, $row["ref_id"],
                                    "",
                                    true) .
                                " &raquo; " . $lng->txt("crs_info_settings") . ")</li>";
                            $file_list[$array_index]["parent"][$row["ref_id"]][$row["type"]] = $obj_type .
                                " (" . $row["title"] . ")";
                            $file_list[$array_index]["parent"][$row["ref_id"]]["type_title"] = $obj_type;
                        }
                    }

                    // get course content page
                    include_once "./Services/Container/classes/class.ilContainerPage.php";

                    if (ilContainerPage::_exists("cont", $row["obj_id"])) {
                        $copTitle = $copyRightPlugin->txt("content_page");
                        $copPage = new ilContainerPage($row["obj_id"]);

                        self::_fillFileArrayFromPageContent(
                            $file_list,
                            $copPage->getXMLContent(),
                            $obj_type,
                            $row["type"],
                            $row["ref_id"],
                            $row["title"],
                            $copTitle,
                            $a_user_id,
                            $lng);
                    }

                    // get course content start page
                    include_once "./Services/Container/classes/class.ilContainerStartObjectsPage.php";

                    if (ilContainerPage::_exists("cstr", $row["obj_id"])) {
                        $copTitle = $copyRightPlugin->txt("content_start_page");
                        $copPage = new ilContainerStartObjectsPage($row["obj_id"]);

                        self::_fillFileArrayFromPageContent(
                            $file_list,
                            $copPage->getXMLContent(),
                            $obj_type,
                            $row["type"],
                            $row["ref_id"],
                            $row["title"],
                            $copTitle,
                            $a_user_id,
                            $lng);
                    }
                    break;
                case "fold":
                    // get folder content page
                    include_once "./Services/Container/classes/class.ilContainerPage.php";

                    if (ilContainerPage::_exists("cont", $row["obj_id"])) {
                        $copTitle = $copyRightPlugin->txt("content_page");
                        $copPage = new ilContainerPage($row["obj_id"]);

                        self::_fillFileArrayFromPageContent(
                            $file_list,
                            $copPage->getXMLContent(),
                            $obj_type,
                            $row["type"],
                            $row["ref_id"],
                            $row["title"],
                            $copTitle,
                            $a_user_id,
                            $lng);
                    }
                    break;
                case "cat":
                    // get category content page
                    include_once "./Services/Container/classes/class.ilContainerPage.php";

                    if (ilContainerPage::_exists("cont", $row["obj_id"])) {
                        $copTitle = $copyRightPlugin->txt("content_page");
                        $copPage = new ilContainerPage($row["obj_id"]);

                        self::_fillFileArrayFromPageContent(
                            $file_list,
                            $copPage->getXMLContent(),
                            $obj_type,
                            $row["type"],
                            $row["ref_id"],
                            $row["title"],
                            $copTitle,
                            $a_user_id,
                            $lng);
                    }
                    break;
                case "htlm":
                    require_once "./Modules/HTMLLearningModule/classes/class.ilObjFileBasedLM.php";

                    $lmsObj = new ilObjFileBasedLM($row["ref_id"]);

                    if ($lmsObj->getOwner() == $a_user_id) {
                        $dir_files = iterator_to_array(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($lmsObj->getDataDirectory())));

                        foreach ($dir_files as $f => $d) {
                            $pi = pathinfo($f);

                            if (!is_dir($f)) {
                                $sub_dir = str_replace(
                                    "\\",
                                    "/",
                                    substr($pi["dirname"], strlen($lmsObj->getDataDirectory())));
                                $sub_dir = ($sub_dir ? $sub_dir : " ");
                                $array_index = $row["obj_id"] . ": :" . "lms_html_file|" . $pi["basename"] . "|" . $sub_dir;
                                $file_list[$array_index]["title"] = $pi["basename"];
                                $file_list[$array_index]["option_id"] = self::_getCopyRightValue(
                                    $row["obj_id"],
                                    0,
                                    "lms_html_file|" . $pi["basename"] . "|" . $sub_dir);
                                $file_list[$array_index]["used_in"] .= "<li>" . $obj_type . " (" . self::_buildPath(
                                        $tree,
                                        $row["ref_id"],
                                        "",
                                        true) .
                                    str_replace("/", " &raquo; ", trim($sub_dir)) . ")</li>";
                                $file_list[$array_index]["parent"][$row["ref_id"]][$row["type"]] = $obj_type .
                                    " (" . $row["title"] . ")";
                                $file_list[$array_index]["parent"][$row["ref_id"]]["type_title"] = $obj_type;
                            }
                        }
                    }
                    break;
                case "grp":
                    // get group content page
                    include_once "./Services/Container/classes/class.ilContainerPage.php";

                    if (ilContainerPage::_exists("cont", $row["obj_id"])) {
                        $copTitle = $copyRightPlugin->txt("content_page");
                        $copPage = new ilContainerPage($row["obj_id"]);

                        self::_fillFileArrayFromPageContent(
                            $file_list,
                            $copPage->getXMLContent(),
                            $obj_type,
                            $row["type"],
                            $row["ref_id"],
                            $row["title"],
                            $copTitle,
                            $a_user_id,
                            $lng);
                    }
                    break;
                case "glo":
                    include_once "./Modules/Glossary/classes/class.ilGlossaryTerm.php";
                    include_once "./Modules/Glossary/classes/class.ilGlossaryDefinition.php";

                    $glossaryTerms = ilGlossaryTerm::getTermList($row["obj_id"]);

                    foreach ($glossaryTerms as $glossaryTerm) {
                        $defs = ilGlossaryDefinition::getDefinitionList($glossaryTerm["id"]);
                        foreach ($defs as $def) {
                            if (ilGlossaryDefPage::_exists("gdf", $def["id"])) {
                                $glossaryDefPage = new ilGlossaryDefPage($def["id"]);
                                self::_fillFileArrayFromPageContent(
                                    $file_list,
                                    $glossaryDefPage->getXMLContent(),
                                    $obj_type,
                                    $row["type"],
                                    $row["ref_id"],
                                    $row["title"],
                                    $glossaryTerm["term"],
                                    $a_user_id,
                                    $lng);
                            }
                        }
                    }
                    break;
                case "dcl":
                    include_once "./Modules/DataCollection/classes/class.ilObjDataCollection.php";
                    include_once "./Modules/DataCollection/classes/class.ilDataCollectionRecordViewViewdefinition.php";

                    $dclObj = new ilObjDataCollection($row["ref_id"]);
                    $tables = $dclObj->getTables();

                    foreach ($tables as $table) {
                        if (ilDataCollectionRecordViewViewdefinition::_exists("dclf", $table->getId())) {
                            $dclRecode = ilDataCollectionRecordViewViewdefinition::getInstanceByTableId($table->getId());
                            self::_fillFileArrayFromPageContent(
                                $file_list,
                                $dclRecode->getXMLContent(),
                                $obj_type,
                                $row["type"],
                                $row["ref_id"],
                                $row["title"],
                                $table->getTitle(),
                                $a_user_id,
                                $lng);

                            $fileFieldFound = false;
                            $tableFields = $table->getFields();
                            foreach ($tableFields as $tableField) {
                                if (in_array($tableField->getDatatypeId(), [ilDataCollectionDatatype::INPUTFORMAT_FILE,
                                    ilDataCollectionDatatype::INPUTFORMAT_MOB])) {
                                    $fileFieldFound = true;
                                    break;
                                }
                            }

                            if ($fileFieldFound) {
                                foreach ($table->getRecordsByFilter() as $record) {
                                    foreach ($table->getVisibleFields() as $field) {
                                        if (in_array($field->getDatatypeId(), [ilDataCollectionDatatype::INPUTFORMAT_FILE,
                                            ilDataCollectionDatatype::INPUTFORMAT_MOB])) {
                                            $sqlFile = "SELECT od.obj_id,od.type,od.title FROM object_data od";

                                            if ($a_user_id) {
                                                $sqlFile .= " WHERE od.owner = " . $ilDB->quote($a_user_id, "integer");
                                            } else {
                                                $sqlFile .= " LEFT JOIN usr_data ud ON (ud.usr_id = od.owner)" .
                                                    " WHERE (od.owner < " . $ilDB->quote(1, "integer") .
                                                    " OR od.owner IS NULL OR ud.login IS NULL)" .
                                                    " AND od.owner <> " . $ilDB->quote(-1, "integer");
                                            }

                                            $sqlFile .= " AND od.obj_id = " .
                                                $ilDB->quote($record->getRecordFieldValue($field->getId()), "integer");
                                            $resFile = $ilDB->query($sqlFile);

                                            while ($rowFile = $ilDB->fetchAssoc($resFile)) {
                                                $array_index = $rowFile["obj_id"] . ":0:file";
                                                $file_list[$array_index]["title"] = $rowFile["title"];
                                                $file_list[$array_index]["option_id"] = self::_getCopyRightValue(
                                                    $rowFile["obj_id"],
                                                    null,
                                                    "file");
                                                $file_list[$array_index]["used_in"] .=
                                                    "<li>" . $obj_type . " (" . self::_buildPath(
                                                        $tree,
                                                        $row["ref_id"],
                                                        "",
                                                        true) .
                                                    " &raquo; " . $table->getTitle() . ")</li>";
                                                $file_list[$array_index]["parent"][$row["ref_id"]][$row["type"]] = $obj_type;
                                                $file_list[$array_index]["parent"][$row["ref_id"]]["type_title"] = $obj_type;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                    break;
                case "lm":
                    include_once("./Modules/LearningModule/classes/class.ilLMPage.php");
                    include_once("./Modules/LearningModule/classes/class.ilObjLearningModule.php");

                    $pages = ilLMPage::getAllPages("lm", $row["obj_id"]);
                    $lmModule = new  ilObjLearningModule($row["ref_id"]);

                    foreach ($pages as $page) {
                        if (ilLMPage::_exists("lm", $page["id"])) {
                            $lmPage = new ilLMPage($page["id"]);
                            $title = self::_buildPath(
                                $lmModule->getTree(),
                                $page["id"],
                                "",
                                false);

                            self::_fillFileArrayFromPageContent(
                                $file_list,
                                $lmPage->getXMLContent(),
                                $obj_type,
                                $row["type"],
                                $row["ref_id"],
                                $row["title"],
                                $title,
                                $a_user_id,
                                $lng);
                        }
                    }
                    break;
                case "qpl":
                    require_once "./Modules/TestQuestionPool/classes/class.ilAssQuestionList.php";

                    $questionList = new ilAssQuestionList($ilDB, $lng, $ilPluginAdmin);
                    $questionList->setParentObjId($row["obj_id"]);
                    $questionList->load();
                    $questions = $questionList->getQuestionDataArray();

                    foreach ($questions as $question) {
                        self::_getQuestionFile(
                            $file_list,
                            $question,
                            $obj_type,
                            $row,
                            $lng,
                            $copyRightPlugin,
                            $tree,
                            $a_user_id);
                    }
                    break;
                case "tst":
                    require_once "./Modules/Test/classes/class.ilObjTest.php";

                    $objTest = new ilObjTest($row["ref_id"]);
                    $questions = $objTest->getTestQuestions();

                    foreach ($questions as $question) {
                        self::_getQuestionFile(
                            $file_list,
                            $question,
                            $obj_type,
                            $row,
                            $lng,
                            $copyRightPlugin,
                            $tree,
                            $a_user_id,
                            $objTest);
                    }
                    break;
                case "wiki":
                    require_once "./Modules/Wiki/classes/class.ilWikiPage.php";

                    $wikiPages = ilWikiPage::getAllPages($row["obj_id"]);

                    foreach ($wikiPages as $wikiPage) {
                        if (ilWikiPage::_exists("wpg", $wikiPage["id"])) {
                            $page = new ilWikiPage($wikiPage["id"]);

                            self::_fillFileArrayFromPageContent(
                                $file_list,
                                $page->getXMLContent(),
                                $obj_type,
                                $row["type"],
                                $row["ref_id"],
                                $row["title"],
                                $page->getTitle(),
                                $a_user_id,
                                $lng);
                        }
                    }
                    break;
                case "bibl":
                    require_once "./Modules/Bibliographic/classes/class.ilObjBibliographic.php";

                    $biblObj = new ilObjBibliographic($row["obj_id"]);
                    $array_index = $row["obj_id"] . ": :" . "bibl";
                    $file_list[$array_index]["title"] = $biblObj->getFilename();
                    $file_list[$array_index]["option_id"] = self::_getCopyRightValue(
                        $row["obj_id"],
                        null,
                        "bibl");
                    $file_list[$array_index]["used_in"] .= "<li>" . $obj_type . " (" .
                        self::_buildPath(
                            $tree,
                            $row["ref_id"],
                            "",
                            true) . ")</li>";
                    $file_list[$array_index]["parent"][$row["ref_id"]][$row["type"]] = $obj_type .
                        " (" . $row["title"] . ")";
                    $file_list[$array_index]["parent"][$row["ref_id"]]["type_title"] = $obj_type;
                    break;
                case "book":
                    require_once "./Modules/BookingManager/classes/class.ilBookingObject.php";

                    $bookObjs = ilBookingObject::getList($row["obj_id"]);

                    if ($row["owner"] == $a_user_id) {
                        foreach ($bookObjs as $bookObj) {
                            $array_index = $row["obj_id"] . ":" . $bookObj["booking_object_id"] . ":" . "book_info_file";
                            $file_list[$array_index]["title"] = $bookObj["info_file"];
                            $file_list[$array_index]["option_id"] = self::_getCopyRightValue(
                                $row["obj_id"],
                                $bookObj["booking_object_id"],
                                "book_info_file");
                            $file_list[$array_index]["used_in"] .= "<li>" . $obj_type . " (" . self::_buildPath(
                                    $tree,
                                    $row["ref_id"],
                                    "",
                                    true) .
                                " &raquo; " . $bookObj["title"] . " &raquo; " .
                                $copyRightPlugin->txt("information_file") . ")</li>";
                            $file_list[$array_index]["parent"][$row["ref_id"]][$row["type"]] = $obj_type .
                                " (" . $row["title"] . ")";
                            $file_list[$array_index]["parent"][$row["ref_id"]]["type_title"] = $obj_type;

                            $array_index = $row["obj_id"] . ":" . $bookObj["booking_object_id"] . ":" . "book_post_file";
                            $file_list[$array_index]["title"] = $bookObj["post_file"];
                            $file_list[$array_index]["option_id"] = self::_getCopyRightValue(
                                $row["obj_id"],
                                $bookObj["booking_object_id"],
                                "book_post_file");
                            $file_list[$array_index]["used_in"] .= "<li>" . $obj_type . " (" . self::_buildPath(
                                    $tree,
                                    $row["ref_id"],
                                    "",
                                    true) .
                                " &raquo; " . $bookObj["title"] . " &raquo; " .
                                $copyRightPlugin->txt("post_file") . ")</li>";
                            $file_list[$array_index]["parent"][$row["ref_id"]][$row["type"]] = $obj_type .
                                " (" . $row["title"] . ")";
                            $file_list[$array_index]["parent"][$row["ref_id"]]["type_title"] = $obj_type;
                        }
                    }
                    break;
                case "exc":
                    require_once "./Modules/Exercise/classes/class.ilExAssignment.php";
                    require_once "./Modules/Exercise/classes/class.ilExSubmission.php";
                    require_once "./Modules/Exercise/classes/class.ilObjExercise.php";

                    $exAssignments = ilExAssignment::getAssignmentDataOfExercise($row["obj_id"]);
                    $exerciseObj = new ilObjExercise($row["ref_id"]);
                    $lng->loadLanguageModule("exc");

                    foreach ($exAssignments as $exAssignment) {
                        $exAssignmentObj = new ilExAssignment($exAssignment["id"]);

                        if ($exerciseObj->getOwner() == $a_user_id) {
                            if ($exAssignment["fb_file"]) {
                                $array_index = $row["obj_id"] . ":" . $exAssignment["id"] . ":" .
                                    "exc_global_feedback_file";
                                $file_list[$array_index]["title"] = $exAssignment["fb_file"];
                                $file_list[$array_index]["option_id"] = self::_getCopyRightValue(
                                    $row["obj_id"],
                                    $exAssignment["id"],
                                    "exc_global_feedback_file");
                                $file_list[$array_index]["used_in"] .= "<li>" . $obj_type . " (" . self::_buildPath(
                                        $tree,
                                        $row["ref_id"],
                                        "",
                                        true) .
                                    " &raquo; " . $exAssignment["title"] . " &raquo; " .
                                    $lng->txt("exc_global_feedback_file") . ")</li>";
                                $file_list[$array_index]["parent"][$row["ref_id"]][$row["type"]] = $obj_type .
                                    " (" . $row["title"] . ")";
                                $file_list[$array_index]["parent"][$row["ref_id"]]["type_title"] = $obj_type;
                            }

                            $files = $exAssignmentObj->getFiles();

                            if (count($files) > 0) {
                                foreach ($files as $file) {
                                    $array_index = $row["obj_id"] . ":" . $exAssignment["id"] .
                                        ":" . "exc_instruction_files|" . $file["name"];
                                    $file_list[$array_index]["title"] = $file["name"];
                                    $file_list[$array_index]["option_id"] = self::_getCopyRightValue(
                                        $row["obj_id"],
                                        $exAssignment["id"],
                                        "exc_instruction_files|" . $file["name"]
                                    );
                                    $file_list[$array_index]["used_in"] .= "<li>" . $obj_type . " (" . self::_buildPath(
                                            $tree,
                                            $row["ref_id"],
                                            "",
                                            true) .
                                        " &raquo; " . $exAssignment["title"] . " &raquo; " .
                                        $lng->txt("exc_instruction_files") . ")</li>";
                                    $file_list[$array_index]["parent"][$row["ref_id"]][$row["type"]] = $obj_type .
                                        " (" . $row["title"] . ")";
                                    $file_list[$array_index]["parent"][$row["ref_id"]]["type_title"] = $obj_type;
                                }
                            }
                        }

                        $exSubmission = new ilExSubmission($exAssignmentObj, $a_user_id);

                        if ($exSubmission->getPeerReview()) {
                            $criteriaCatalogueItems = $exAssignmentObj->getPeerReviewCriteriaCatalogueItems();
                            $peerReviews = $exSubmission->getPeerReview()->getPeerReviewsByGiver($a_user_id);

                            foreach ($criteriaCatalogueItems as $item) {
                                if (is_a($item, "ilExcCriteriaFile")) {
                                    foreach ($peerReviews as $peerReview) {
                                        $item->setPeerReviewContext(
                                            $exAssignmentObj,
                                            $peerReview["giver_id"],
                                            $peerReview["peer_id"]);
                                        $files = $item->getFiles();

                                        foreach ($files as $file) {
                                            $file_name = basename($file);
                                            $array_index = $row["obj_id"] . ":" . $exAssignment["id"] .
                                                ":" . "peer_feedback|" . $file_name . "|" . $item->getId() . "|" .
                                                $peerReview["peer_id"];
                                            $file_list[$array_index]["title"] = $file_name;
                                            $file_list[$array_index]["option_id"] = self::_getCopyRightValue(
                                                $row["obj_id"],
                                                $exAssignment["id"],
                                                "peer_feedback|" . $file_name . "|" .
                                                $item->getId() . "|" . $peerReview["peer_id"]);
                                            $file_list[$array_index]["used_in"] .= "<li>" . $obj_type . " (" . self::_buildPath(
                                                    $tree,
                                                    $row["ref_id"],
                                                    "",
                                                    true) .
                                                " &raquo; " . $exAssignment["title"] . " &raquo; " .
                                                $copyRightPlugin->txt("peer_feedback") . ")</li>";
                                            $file_list[$array_index]["parent"][$row["ref_id"]][$row["type"]] = $obj_type .
                                                " (" . $row["title"] . ")";
                                            $file_list[$array_index]["parent"][$row["ref_id"]]["type_title"] = $obj_type;
                                        }
                                    }
                                }
                            }
                        }

                        $submissionFiles = $exSubmission->getFiles();

                        foreach ($submissionFiles as $submissionFile) {
                            $array_index = $row["obj_id"] . ":" . $exAssignment["id"] . ":" .
                                "exc_return|" . $submissionFile["filetitle"] . "|" . $submissionFile["returned_id"];
                            $file_list[$array_index]["title"] = $submissionFile["filetitle"];
                            $file_list[$array_index]["option_id"] = self::_getCopyRightValue(
                                $row["obj_id"],
                                $exAssignment["id"],
                                "exc_return|" . $submissionFile["filetitle"] .
                                "|" . $submissionFile["returned_id"]);
                            $file_list[$array_index]["used_in"] .= "<li>" . $obj_type . " (" . self::_buildPath(
                                    $tree,
                                    $row["ref_id"],
                                    "",
                                    true) .
                                " &raquo; " . $exAssignment["title"] . " &raquo; " . $lng->txt("exc_submission") . ")</li>";
                            $file_list[$array_index]["parent"][$row["ref_id"]][$row["type"]] = $obj_type .
                                " (" . $row["title"] . ")";
                            $file_list[$array_index]["parent"][$row["ref_id"]]["type_title"] = $obj_type;
                        }

                        $membersList = $exAssignmentObj->getMemberListData();

                        foreach ($membersList as $member) {
                            require_once "./Modules/Exercise/classes/class.ilFSStorageExercise.php";

                            $storage = new ilFSStorageExercise($exAssignmentObj->getExerciseId(), $exAssignmentObj->getId());
                            $feed_back_files = $storage->getFeedbackFiles($member["usr_id"]);

                            foreach ($feed_back_files as $file) {
                                $array_index = $row["obj_id"] . ":" . $exAssignment["id"] .
                                    ":" . "ass_feedback|" . $file . "|" . $member["usr_id"];
                                $file_list[$array_index]["title"] = $file;
                                $file_list[$array_index]["option_id"] = self::_getCopyRightValue(
                                    $row["obj_id"],
                                    $exAssignment["id"],
                                    "ass_feedback|" . $file . "|" . $member["usr_id"]);
                                $file_list[$array_index]["used_in"] .= "<li>" . $obj_type . " (" . self::_buildPath(
                                        $tree,
                                        $row["ref_id"],
                                        "",
                                        true) .
                                    " &raquo; " . $exAssignment["title"] . " &raquo; " .
                                    $copyRightPlugin->txt("exc_fb_file") . " &raquo; " .
                                    $member["name"] . "[" . $member["firstname"] . "]" . ")</li>";
                                $file_list[$array_index]["parent"][$row["ref_id"]][$row["type"]] = $obj_type .
                                    " (" . $row["title"] . ")";
                                $file_list[$array_index]["parent"][$row["ref_id"]]["type_title"] = $obj_type;
                            }
                        }
                    }
                    break;
                case "mcst":
                    require_once "./Modules/MediaCast/classes/class.ilObjMediaCast.php";
                    require_once "./Services/News/classes/class.ilNewsItem.php";
                    require_once "./Services/MediaObjects/classes/class.ilObjMediaObject.php";

                    $mediaCastObj = new ilObjMediaCast($row["ref_id"]);
                    $mediaCastItems = $mediaCastObj->getItemsArray();

                    foreach ($mediaCastItems as $mediaCastItem) {
                        $mcst_item = new ilNewsItem($mediaCastItem["id"]);
                        $mob = new ilObjMediaObject($mcst_item->getMobId());
                        $mediaItems = $mob->getMediaItems();

                        foreach ($mediaItems as $mediaItem) {
                            if ($mediaItem->getLocationType() !== "Reference") {
                                $array_index = $mob->getId() . ":" . $mediaItem->getId() . ":" . "mob_" . $mediaItem->getPurpose();
                                $file_list[$array_index]["title"] = $mediaItem->getlocation();
                                $file_list[$array_index]["option_id"] = self::_getCopyRightValue(
                                    $mob->getId(),
                                    $mediaItem->getId(),
                                    "mob_" . $mediaItem->getPurpose());
                                $file_list[$array_index]["used_in"] .= "<li>" . $obj_type . " (" .
                                    self::_buildPath(
                                        $tree,
                                        $row["ref_id"],
                                        "",
                                        true) .
                                    " &raquo; " . $mediaCastItem["title"] . " &raquo; " .
                                    $mediaItem->getPurpose() . ")</li>";
                                $file_list[$array_index]["parent"][$row["ref_id"]][$row["type"]] = $obj_type .
                                    " (" . $row["title"] . ")";
                                $file_list[$array_index]["parent"][$row["ref_id"]]["type_title"] = $obj_type;
                            }
                        }

                        $lng->loadLanguageModule("mcst");

                        if ($mob->getVideoPreviewPic()) {
                            $array_index = $mob->getId() . ":0:" . "mob_preview_pic";
                            $file_list[$array_index]["title"] = $mob->getVideoPreviewPic(true);
                            $file_list[$array_index]["option_id"] = self::_getCopyRightValue(
                                $mob->getId(),
                                0,
                                "mob_preview_pic");
                            $file_list[$array_index]["used_in"] .= "<li>" . $obj_type . " (" .
                                self::_buildPath(
                                    $tree,
                                    $row["ref_id"],
                                    "",
                                    true) .
                                " &raquo; " . $mediaCastItem["title"] . " &raquo; " .
                                $lng->txt("mcst_preview_picture") . ")</li>";
                            $file_list[$array_index]["parent"][$row["ref_id"]][$row["type"]] = $obj_type .
                                " (" . $row["title"] . ")";
                            $file_list[$array_index]["parent"][$row["ref_id"]]["type_title"] = $obj_type;
                        }
                    }
                    break;
                case "frm":
                    require_once "./Modules/Forum/classes/class.ilObjForum.php";
                    require_once "./Modules/Forum/classes/class.ilForumTopic.php";
                    require_once "./Modules/Forum/classes/class.ilForumPost.php";
                    require_once "./Modules/Forum/classes/class.ilFileDataForum.php";

                    $forumObj = new ilObjForum($row["ref_id"]);
                    $frm = $forumObj->Forum;
                    $frm->setForumId($forumObj->getId());
                    $frm->setForumRefId($forumObj->getRefId());
                    $frm->setMDB2Wherecondition("top_frm_fk = %s ", ["integer"], [$frm->getForumId()]);
                    $topicData = $frm->getOneTopic();
                    $threads = $frm->getAllThreads($topicData["top_pk"]);

                    foreach ($threads["items"] as $thread) {
                        /**
                         * @var $thread ilForumTopic
                         */
                        $posts = $thread->getAllPosts();

                        foreach ($posts as $key => $p) {
                            $file_obj = new ilFileDataForum($forumObj->getId(), $key);
                            $files = $file_obj->getFilesOfPost();

                            if (is_array($files) && count($files)) {
                                $post = new ilForumPost($key);
                                $parentPost = new ilForumPost($post->getParentId());
                                $postPath = "";

                                if ($parentPost->getSubject()) {
                                    $postPath = $parentPost->getSubject();
                                }

                                while ($parentPost->getParentId() != 0) {
                                    $parentPost = new ilForumPost($parentPost->getParentId());

                                    if ($parentPost->getSubject()) {
                                        $postPath = $parentPost->getSubject() . " &raquo; " . $postPath;
                                    }
                                }

                                foreach ($files as $file) {
                                    $array_index = $row["obj_id"] . ":" . $key . ":" . "forum|" . $file["name"];
                                    $file_list[$array_index]["title"] = $file["name"];
                                    $file_list[$array_index]["option_id"] = self::_getCopyRightValue(
                                        $row["obj_id"],
                                        $key,
                                        "forum|" . $file["name"]);
                                    $file_list[$array_index]["used_in"] .= "<li>" . $obj_type . " (" .
                                        self::_buildPath(
                                            $tree,
                                            $row["ref_id"],
                                            "",
                                            true) .
                                        " &raquo; " . $thread->getSubject() . $postPath . " &raquo; " .
                                        $post->getSubject() . ")</li>";
                                    $file_list[$array_index]["parent"][$row["ref_id"]][$row["type"]] = $obj_type .
                                        " (" . $row["title"] . ")";
                                    $file_list[$array_index]["parent"][$row["ref_id"]]["type_title"] = $obj_type;
                                }
                            }
                        }
                    }
                    break;
                default:
                    break;
            }
        }

        // Workspace Files
        include_once "./Services/PersonalWorkspace/classes/class.ilWorkspaceTree.php";

        $sql = "SELECT od.obj_id,od.type,od.title,tws.parent,tws.child FROM object_data od" .
            " JOIN object_reference_ws orefws ON(orefws.obj_id = od.obj_id)" .
            " JOIN tree_workspace tws ON (tws.child = orefws.wsp_id)";

        if ($a_user_id) {
            $sql .= " WHERE od.owner = " . $ilDB->quote($a_user_id, "integer");
        } else {
            $sql .= " LEFT JOIN usr_data ud ON (ud.usr_id = od.owner)" .
                " WHERE (od.owner < " . $ilDB->quote(1, "integer") .
                " OR od.owner IS NULL OR ud.login IS NULL)" .
                " AND od.owner <> " . $ilDB->quote(-1, "integer");
        }

        $sql .= " AND " . $ilDB->in("od.type", $types, "", "text") .
            " AND tws.tree = " . $ilDB->quote($a_user_id, "integer");
        $res = $ilDB->query($sql);
        $wsp_tree = new ilWorkspaceTree($a_user_id);

        while ($row = $ilDB->fetchAssoc($res)) {
            $file_path = self::_buildPath($wsp_tree, $row["child"], "child", true);
            $array_index = $row["obj_id"] . ":0:file";
            $file_list[$array_index]["title"] = $row["title"];
            $file_list[$array_index]["option_id"] = self::_getCopyRightValue(
                $row["obj_id"],
                null,
                "file");
            $file_list[$array_index]["used_in"] .= "<li>Workspace" .
                ($file_path ? " &raquo; " . $file_path : "") . "</li>";
            $file_list[$array_index]["parent"][$row["parent"]]["wps"] = "Workspace";
            $file_list[$array_index]["parent"][$row["parent"]]["type_title"] = "Workspace";
        }

        // Portfolio Files
        include_once "./Modules/Portfolio/classes/class.ilPortfolioPage.php";
        include_once "./Modules/Portfolio/classes/class.ilObjPortfolio.php";

        $portfolios = ilObjPortfolio::getPortfoliosOfUser($a_user_id);
        $lng->loadLanguageModule("prtf");

        foreach ($portfolios as $portfolio) {
            $portfolioPages = ilPortfolioPage::getAllPages($portfolio["id"]);

            foreach ($portfolioPages as $portfolioPage) {
                if (ilPortfolioPage::_exists("prtf", $portfolioPage["id"])) {
                    $poPage = new ilPortfolioPage($portfolioPage["id"]);

                    self::_fillFileArrayFromPageContent(
                        $file_list,
                        $poPage->getXMLContent(),
                        "Portfolio",
                        "prtf",
                        $portfolio["id"],
                        $portfolio["title"],
                        "PortFolio &raquo; " . $portfolio["title"] . " &raquo; " . $portfolioPage["title"],
                        $a_user_id,
                        $lng,
                        false);
                }
            }

            if ($portfolio["img"]) {
                $array_index = $portfolio["id"] . ":0:portfolio_banner";
                $file_list[$array_index]["title"] = $portfolio["img"];
                $file_list[$array_index]["option_id"] = self::_getCopyRightValue(
                    $portfolio["id"],
                    null,
                    "portfolio_banner");
                $file_list[$array_index]["used_in"] .= "<li>PortFolio &raquo; " . $portfolio["title"] . " &raquo; " .
                    $lng->txt("prtf_banner") . "</li>";
                $file_list[$array_index]["parent"][$portfolio["id"]]["prtf"] = "PortFolio (" . $portfolio["title"] . ")";
                $file_list[$array_index]["parent"][$portfolio["id"]]["type_title"] = "PortFolio";
            }
        }

        // user profile image
        if ($ilUser->getPref("profile_image")) {
            $array_index = $a_user_id . ":0:profile_picture";
            $file_list[$array_index]["title"] = $lng->txt("personal_picture");
            $file_list[$array_index]["option_id"] = self::_getCopyRightValue(
                $a_user_id,
                0,
                "profile_picture");
            $file_list[$array_index]["used_in"] .= "<li>" . $lng->txt("personal_data") . "</li>";
            $file_list[$array_index]["parent"][0]["prof"] = $lng->txt("personal_data");
            $file_list[$array_index]["parent"][0]["type_title"] = $lng->txt("personal_data");
        }

        // mail attachment
        require_once "./Services/Mail/classes/class.ilFileDataMail.php";
        /*require_once "./Services/Mail/classes/class.ilMailBoxQuery.php";
        ilMailBoxQuery::$userId = $a_user_id;
        ilMailBoxQuery::$folderId = 6;
        var_dump(ilMailBoxQuery::_getMailBoxListData());
        die();*/

        $fileMailData = new ilFileDataMail($a_user_id);
        $emailFiles = $fileMailData->getUserFilesData();

        foreach ($emailFiles as $emailFile) {
            $array_index = "0:" . $a_user_id . ":email_attachment|" . $emailFile["name"];
            $file_list[$array_index]["title"] = $emailFile["name"];
            $file_list[$array_index]["option_id"] = self::_getCopyRightValue(
                "0",
                $a_user_id,
                "email_attachment|" . $emailFile["name"]);
            $file_list[$array_index]["used_in"] .= "<li>" . $copyRightPlugin->txt("email_attachment") . "</li>";
            $file_list[$array_index]["parent"][0]["att"] = $copyRightPlugin->txt("email_attachment");
            $file_list[$array_index]["parent"][0]["type_title"] = $copyRightPlugin->txt("email_attachment");
        }

        if (in_array(SYSTEM_ROLE_ID, $rbacreview->assignedRoles($a_user_id))) {
            // get login pages
            include_once "./Services/Authentication/classes/class.ilLoginPage.php";

            $installed = $lng->getInstalledLanguages();
            $lng->loadLanguageModule("meta");
            $authTitle = $lng->txt("obj_auth");

            foreach ($installed as $key => $langkey) {
                if (ilLoginPage::_exists("auth", ilLanguage::lookupId($langkey))) {
                    $loginPage = new ilLoginPage(ilLanguage::lookupId($langkey));

                    self::_fillFileArrayFromPageContent(
                        $file_list,
                        $loginPage->getXMLContent(),
                        $authTitle,
                        "auth",
                        18,
                        $authTitle,
                        $authTitle . " &raquo; " . $lng->txt("meta_l_" . $langkey),
                        $a_user_id,
                        $lng,
                        false);
                }
            }

            // get legal notice page
            include_once "./Services/Imprint/classes/class.ilImprint.php";

            if (ilImprint::_exists("impr", 1)) {
                $lng->loadLanguageModule("administration");
                $lgTitle = $lng->txt("adm_imprint");
                $lgPage = new ilImprint(1);

                self::_fillFileArrayFromPageContent(
                    $file_list,
                    $lgPage->getXMLContent(),
                    $lgTitle,
                    "impr",
                    9,
                    $lgTitle,
                    $lgTitle,
                    $a_user_id,
                    $lng,
                    false);
            }

            // get repository content page
            include_once "./Services/Container/classes/class.ilContainerPage.php";

            if (ilContainerPage::_exists("cont", 1)) {
                $copTitle = $copyRightPlugin->txt("content_page");
                $copPage = new ilContainerPage(1);
                $repositoryTitle = $lng->txt("obj_root");

                self::_fillFileArrayFromPageContent(
                    $file_list,
                    $copPage->getXMLContent(),
                    $repositoryTitle,
                    "root",
                    1,
                    $copTitle,
                    $repositoryTitle . " &raquo; " . $copTitle,
                    $a_user_id,
                    $lng,
                    false);
            }

            // shop page
            include_once "./Services/Payment/classes/class.ilShopPage.php";
            include_once "./Services/Payment/classes/class.ilObjPaymentSettingsGUI.php";
            include_once "./Services/Payment/classes/class.ilShopInfoGUI.php";

            $pages = ilPageObject::getAllPages("shop", 0);
            $lng->loadLanguageModule("payment");
            $shopTitle = $lng->txt("pay_header");

            foreach ($pages as $page) {
                if (ilShopPage::_exists("shop", $page["id"])) {
                    $shopPage = new ilShopPage($page["id"]);

                    if ($page["id"] === (string)ilObjPaymentSettingsGUI::CONDITIONS_EDITOR_PAGE_ID) {
                        $shopPath = $shopTitle . " &raquo; " . $lng->txt("documents");
                        $ref_id = 19;
                    } else if ($page["id"] === (string)ilShopInfoGUI::SHOP_PAGE_EDITOR_PAGE_ID) {
                        $shopPath = $shopTitle . " &raquo; " . $lng->txt("shop_info");
                        $ref_id = -1;
                    } else {
                        $shopPath = $shopTitle . " &raquo; Content";
                        $ref_id = -1;
                    }

                    self::_fillFileArrayFromPageContent(
                        $file_list,
                        $shopPage->getXMLContent(),
                        $shopTitle,
                        "shop",
                        $ref_id,
                        $shopTitle,
                        $shopPath,
                        $a_user_id,
                        $lng,
                        false);
                }
            }

            // layout page
            include_once "./Services/Style/classes/class.ilPageLayoutPage.php";
            include_once "./Services/Style/classes/class.ilPageLayout.php";

            $pages = ilPageLayout::getLayoutsAsArray();
            $stysTitle = $lng->txt("obj_stys");

            foreach ($pages as $page) {
                if (ilPageLayoutPage::_exists("stys", $page["id"])) {
                    $stysPage = new ilPageLayoutPage($page["layout_id"]);

                    self::_fillFileArrayFromPageContent(
                        $file_list,
                        $stysPage->getXMLContent(),
                        $stysTitle,
                        "stys",
                        21,
                        $stysTitle,
                        $stysTitle . " &raquo; " . $page["title"],
                        $a_user_id,
                        $lng,
                        false);
                }
            }
        }

        return $file_list;
    }

    /**
     * Get all file items that are used within the all page and uploaded by specific user
     * @var $a_content
     *
     * @return array
     */
    private static function _collectFileItemsFromPageContent($a_content)
    {
        if ($a_content) {
            $file_ids = [];
            $doc = new DOMDocument();
            $doc->loadXML($a_content);
            $xpath = new DOMXPath($doc);
            // file items in file list
            $nodes = $xpath->query("//FileItem/Identifier");

            foreach ($nodes as $node) {
                $id_arr = explode("_", $node->getAttribute("Entry"));
                $file_id = $id_arr[count($id_arr) - 1];

                if ($file_id > 0 && ($id_arr[1] == "" || $id_arr[1] == IL_INST_ID || $id_arr[1] == 0)) {
                    $file_ids[$file_id] = $file_id;
                }
            }

            // file items in download links
            $xpath = new DOMXPath($doc);
            $nodes = $xpath->query("//IntLink[@Type='File']");

            foreach ($nodes as $node) {
                $t = $node->getAttribute("Target");

                if (substr($t, 0, 9) == "il__dfile") {
                    $id_arr = explode("_", $t);
                    $file_id = $id_arr[count($id_arr) - 1];
                    $file_ids[$file_id] = $file_id;
                }
            }

            $xpath = new DOMXPath($doc);
            $nodes = $xpath->query("//IntLink[@Type='MediaObject']");

            foreach ($nodes as $node) {
                $t = $node->getAttribute("Target");

                if (substr($t, 0, 8) == "il__mob_") {
                    $id_arr = explode("_", $t);
                    $file_id = $id_arr[count($id_arr) - 1];
                    $file_ids[$file_id] = $file_id;
                }
            }

            $xpath = new DOMXPath($doc);
            $nodes = $xpath->query("//IntLink[@Type='RepositoryItem']");

            foreach ($nodes as $node) {
                $t = $node->getAttribute("Target");

                if (substr($t, 0, 8) == "il__obj_") {
                    $id_arr = explode("_", $t);
                    $file_id = $id_arr[count($id_arr) - 1];
                    $file_id = ilObject::_lookupObjectId($file_id);
                    $file_ids[$file_id] = $file_id;
                }
            }

            $xpath = new DOMXPath($doc);
            // media objects and interactive images
            $nodes = $xpath->query("//MediaObject/MediaAlias");

            foreach ($nodes as $node) {
                $id_arr = explode("_", $node->getAttribute("OriginId"));
                $file_id = $id_arr[count($id_arr) - 1];

                if ($file_id > 0 && ($id_arr[1] == "" || $id_arr[1] == IL_INST_ID || $id_arr[1] == 0)) {
                    $file_ids[$file_id] = $file_id;
                }
            }

            // media objects and interactive images
            $nodes = $xpath->query("//InteractiveImage/MediaAlias");

            foreach ($nodes as $node) {
                $id_arr = explode("_", $node->getAttribute("OriginId"));
                $file_id = $id_arr[count($id_arr) - 1];

                if ($file_id > 0 && ($id_arr[1] == "" || $id_arr[1] == IL_INST_ID || $id_arr[1] == 0)) {
                    $file_ids[$file_id] = $file_id;
                }
            }

            return $file_ids;
        } else {
            return [];
        }
    }

    private static function _buildPath($a_tree, $a_ref_id, $a_ref_node_name, $a_with_root)
    {
        $path = "";
        $path_full = $a_tree->getPathFull($a_ref_id);

        if ($path_full) {
            foreach ($path_full as $data) {
                if ($data["parent"] === "0" && !$a_with_root) {
                    continue;
                }
                if ($a_ref_id != $data[$a_ref_node_name]) {
                    $path .= ($path ? " &raquo; " : "") . ($data["title"]);
                }
            }
        }

        return $path;
    }

    /**
     * get copy right option for file
     *
     * @param    int $a_id
     * @param    int $a_sub_id
     * @param    string $a_file_location
     *
     * @return int
     */
    public static function _getCopyRightValue($a_id, $a_sub_id, $a_file_location)
    {
        global $ilDB;

        $query = "SELECT * FROM copy_right_data WHERE obj_id = " .
            $ilDB->quote($a_id, "integer");

        if ($a_file_location) {
            $query .= " AND file_location = " . $ilDB->quote($a_file_location, "text");
        }

        if (trim($a_sub_id)) {
            $query .= " AND sub_id = " . $ilDB->quote($a_sub_id, "integer");
        }

        $res = $ilDB->query($query);

        while ($obj_rec = $ilDB->fetchAssoc($res)) {
            $option_id = $obj_rec["option_id"];
        }

        return $option_id;
    }

    public static function _changeCopyRightOption($oldOption, $newOption, $user_id)
    {
        $files = self::_getAllOwnedRepositoryFiles($user_id);

        foreach ($files as $key => $item) {
            if ((string)$item["option_id"] === (string)$oldOption) {
                $file_info_array = explode(":", $key);
                copyrightHelper::_updateCopyRightOptionByObjectId($file_info_array[0], $newOption, $file_info_array[1], $file_info_array[2]);
            }
        }
    }

    public static function _updateCopyRightOptionByObjectId($a_object_id, $a_copy_right_option_id, $a_sub_id, $a_file_location)
    {
        global $ilDB, $ilias;

        if ($a_copy_right_option_id) {
            // Update copy_right_option
            $query = "SELECT * FROM copy_right_data WHERE obj_id = " .
                $ilDB->quote($a_object_id, "integer");

            if (trim($a_file_location)) {
                $query .= " AND file_location = " . $ilDB->quote($a_file_location, "text");
            }

            if (trim($a_sub_id)) {
                $query .= " AND sub_id = " . $ilDB->quote($a_sub_id, "integer");
            }

            $res = $ilias->db->query($query);

            if ($res->numRows()) {
                $values = [
                    "option_id" => ["integer", $a_copy_right_option_id]
                ];
                $a_where = ["obj_id" => ["integer", $a_object_id]];

                if (trim($a_file_location)) {
                    $a_where["file_location"] = ["text", $a_file_location];
                }

                if (trim($a_sub_id)) {
                    $a_where["sub_id"] = ["integer", $a_sub_id];
                }

                $ilDB->update("copy_right_data",
                    $values,
                    $a_where);
            } else {
                $values = [
                    "obj_id" => ["integer", $a_object_id],
                    "option_id" => ["integer", $a_copy_right_option_id],
                    "sub_id" => ["integer", $a_sub_id],
                    "file_location" => ["text", $a_file_location]];
                $ilDB->insert("copy_right_data", $values);
            }
        }
    }

    public static function _deleteCopyRightOptionByObjectId($a_object_id, $a_sub_id, $a_file_location)
    {
        global $ilDB;

        $query = "DELETE FROM copy_right_data WHERE obj_id = " .
            $ilDB->quote($a_object_id, "integer");

        if ($a_file_location) {
            $query .= " AND file_location = " . $ilDB->quote($a_file_location, "text");
        }

        if ($a_sub_id) {
            $query .= " AND sub_id = " . $ilDB->quote($a_sub_id, "integer");
        }

        $ilDB->manipulate($query);
    }

    public static function _updateCopyRightOptionByObjectIds($a_object_ids, $a_copy_right_option_id)
    {
        // Update copyright option
        foreach ($a_object_ids as $key => $a_object_id) {
            $file_info_array = explode(":", $key);

            self::_updateCopyRightOptionByObjectId(
                $file_info_array[0],
                $a_copy_right_option_id,
                $file_info_array[1],
                $file_info_array[2]);
        }
    }

    /**
     * @param $a_file_list
     * @param $a_page_content
     * @param $obj_type
     * @param $row_type
     * @param $row_ref_id
     * @param $row_title
     * @param $pageTitle
     * @param $user_id
     * @param $lng
     * @param $build_path
     */
    private static function _fillFileArrayFromPageContent(&$a_file_list,
                                                          $a_page_content,
                                                          $obj_type,
                                                          $row_type,
                                                          $row_ref_id,
                                                          $row_title,
                                                          $pageTitle,
                                                          $user_id,
                                                          $lng,
                                                          $build_path = true)
    {
        global $ilDB, $tree;

        $res_file = self::_getResultFile($a_page_content);

        while ($row_file = $ilDB->fetchAssoc($res_file)) {
            if ($row_file["owner"] == $user_id) {
                if ($row_file["type"] === "mob") {
                    require_once "./Services/MediaObjects/classes/class.ilObjMediaObject.php";

                    $mediaObject = new ilObjMediaObject($row_file["obj_id"]);
                    $mediaItems = $mediaObject->getMediaItems();
                    $overlayImages = $mediaObject->getFilesOfDirectory("overlays");
                    $lng->loadLanguageModule("content");

                    foreach ($overlayImages as $overlayImage) {
                        if ($build_path) {
                            $path = "<li>" . $obj_type . " (" . self::_buildPath(
                                    $tree,
                                    $row_ref_id,
                                    "ref_id",
                                    true
                                ) . " &raquo; " . $row_title . " &raquo; " . $pageTitle .
                                " &raquo; " . $mediaObject->getTitle() . " &raquo; " .
                                $lng->txt("cont_overlay_images") . ")</li>";
                        } else {
                            $path = "<li>" . $pageTitle . " &raquo; " .
                                $mediaObject->getTitle() . " &raquo; " .
                                $lng->txt("cont_overlay_image") . "</li>";
                        }

                        $array_index = $mediaObject->getId() . ":0:" . "interactive_overlay_image|" . $overlayImage;
                        $a_file_list[$array_index]["title"] = $overlayImage;
                        $a_file_list[$array_index]["option_id"] = self::_getCopyRightValue(
                            $mediaObject->getId(),
                            0,
                            "interactive_overlay_image|" . $overlayImage);
                        $a_file_list[$array_index]["used_in"] .= $path;
                        $a_file_list[$array_index]["parent"][$row_ref_id][$row_type] = $obj_type . " (" . $row_title . ")";
                        $a_file_list[$array_index]["parent"][$row_ref_id]["type_title"] = $obj_type;
                    }

                    $used_file_names = [];

                    foreach ($mediaItems as $mediaItem) {
                        if ($mediaItem->getLocationType() === "LocalFile") {
                            if ($build_path) {
                                $path = "<li>" . $obj_type . " (" . self::_buildPath(
                                        $tree,
                                        $row_ref_id,
                                        "ref_id",
                                        true
                                    ) . " &raquo; " . $row_title . " &raquo; " . $pageTitle .
                                    " &raquo; " . $mediaObject->getTitle() .
                                    " &raquo; " . $mediaItem->getPurpose() . ")</li>";
                            } else {
                                $path = "<li>" . $pageTitle .
                                    " &raquo; " . $mediaObject->getTitle() .
                                    " &raquo; " . $mediaItem->getPurpose() . "</li>";
                            }

                            $array_index = $mediaObject->getId() . ":" . $mediaItem->getId() . ":" . "mob_" . $mediaItem->getPurpose();
                            $a_file_list[$array_index]["title"] = $mediaItem->getlocation();
                            $a_file_list[$array_index]["option_id"] = self::_getCopyRightValue(
                                $mediaObject->getId(),
                                $mediaItem->getId(),
                                "mob_" . $mediaItem->getPurpose());
                            $a_file_list[$array_index]["used_in"] .= $path;
                            $a_file_list[$array_index]["parent"][$row_ref_id][$row_type] = $obj_type . " (" . $row_title . ")";
                            $a_file_list[$array_index]["parent"][$row_ref_id]["type_title"] = $obj_type;
                        }
                        $used_file_names[] = $mediaItem->getLocation();
                    }

                    $dir_files = iterator_to_array(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($mediaObject->getDataDirectory())));

                    foreach ($dir_files as $f => $d) {
                        $pi = pathinfo($f);

                        if (!is_dir($f)) {
                            $sub_dir = str_replace(
                                "\\",
                                "/",
                                substr($pi["dirname"], strlen($mediaObject->getDataDirectory())));

                            if ($sub_dir != "/overlays" &&
                                !in_array(
                                    trim(($sub_dir ? $sub_dir . "/" : "") . $pi["basename"], "/"),
                                    $used_file_names)
                            ) {
                                $sub_dir = ($sub_dir ? $sub_dir : " ");

                                if ($build_path) {
                                    $path = "<li>" . $obj_type . " (" . self::_buildPath(
                                            $tree,
                                            $row_ref_id,
                                            "ref_id",
                                            true) .
                                        " &raquo; " . $row_title . " &raquo; " . $pageTitle .
                                        " &raquo; " . $mediaObject->getTitle() .
                                        str_replace("/", " &raquo; ", trim($sub_dir)) . ")</li>";
                                } else {
                                    $path = "<li>" . $pageTitle . " &raquo; " .
                                        $mediaObject->getTitle() .
                                        str_replace("/", " &raquo; ", trim($sub_dir)) . "</li>";
                                }

                                $array_index = $mediaObject->getId() . ": :" . "mob|" . $pi["basename"] .
                                    "|" . $sub_dir;
                                $a_file_list[$array_index]["title"] = $pi["basename"];
                                $a_file_list[$array_index]["option_id"] = self::_getCopyRightValue(
                                    $mediaObject->getId(),
                                    0,
                                    "mob|" . $pi["basename"] . "|" . $sub_dir);
                                $a_file_list[$array_index]["used_in"] .= $path;
                                $a_file_list[$array_index]["parent"][$row_ref_id][$row_type] = $obj_type .
                                    " (" . $row_title . ")";
                                $a_file_list[$array_index]["parent"][$row_ref_id]["type_title"] = $obj_type;
                            }
                        }
                    }
                } else {
                    $array_index = $row_file["obj_id"] . ":0:file";

                    if ($build_path) {
                        $path = "<li>" . $obj_type . " (" . self::_buildPath(
                                $tree,
                                $row_ref_id,
                                "ref_id",
                                true) .
                            " &raquo; " . $row_title . " &raquo; " . $pageTitle . ")</li>";
                    } else {
                        $path = "<li>" . $pageTitle . "</li>";
                    }

                    $a_file_list[$array_index]["title"] = $row_file["title"];
                    $a_file_list[$array_index]["option_id"] = self::_getCopyRightValue(
                        $row_file["obj_id"],
                        null,
                        "file");
                    $a_file_list[$array_index]["used_in"] .= $path;
                    $a_file_list[$array_index]["parent"][$row_ref_id][$row_type] = $obj_type . " (" . $row_title . ")";
                    $a_file_list[$array_index]["parent"][$row_ref_id]["type_title"] = $obj_type;
                }
            }
        }
    }

    /**
     * @param $a_page_content
     * @return mixed
     */
    private static function _getResultFile($a_page_content)
    {
        global $ilDB;

        $file_ids = self::_collectFileItemsFromPageContent($a_page_content);
        $sql_file = "SELECT od.obj_id,od.type,od.title, od.type, od.owner FROM object_data od" .
            " WHERE " . $ilDB->in("od.obj_id", $file_ids, "", "integer");
        $res_file = $ilDB->query($sql_file);

        return $res_file;
    }

    /**
     * @param $a_file_list
     * @param $question
     * @param $obj_type
     * @param $row
     * @param $lng
     * @param $copyRightPlugin
     * @param $tree
     * @param $user_id
     * @param $tstObj
     */
    public static function _getQuestionFile(&$a_file_list,
                                            $question,
                                            $obj_type,
                                            $row,
                                            $lng,
                                            $copyRightPlugin,
                                            $tree,
                                            $user_id,
                                            $tstObj = null)
    {
        require_once "./Modules/TestQuestionPool/classes/class.ilAssHintPage.php";
        require_once "./Modules/TestQuestionPool/classes/feedback/class.ilAssGenFeedbackPage.php";
        require_once "./Modules/TestQuestionPool/classes/feedback/class.ilAssSpecFeedbackPage.php";
        require_once "./Modules/TestQuestionPool/classes/class.ilAssQuestionPage.php";

        if (ilAssQuestionPage::_exists("qpl", $question["id"])) {
            $questionPage = new ilAssQuestionPage($question["question_id"]);

            self::_fillFileArrayFromPageContent(
                $a_file_list,
                $questionPage->getXMLContent(),
                $obj_type,
                $row["type"],
                $row["ref_id"],
                $row["title"],
                $question["title"] . " &raquo; " . $copyRightPlugin->txt("question_page"),
                $user_id,
                $lng);
        }

        $hintPages = ilAssHintPage::getAllPages("qht", $question["question_id"]);

        foreach ($hintPages as $hintPage) {
            if (ilAssHintPage::_exists("qht", $hintPage["id"])) {
                $page = new ilAssHintPage($hintPage["id"]);

                self::_fillFileArrayFromPageContent(
                    $a_file_list,
                    $page->getXMLContent(),
                    $obj_type,
                    $row["type"],
                    $row["ref_id"],
                    $row["title"],
                    $question["title"] . " " . " &raquo; " . $lng->txt("hint"),
                    $user_id,
                    $lng);
            }
        }

        $genFeedbackPages = ilAssGenFeedbackPage::getAllPages(
            "qfbg",
            $question["question_id"]);

        foreach ($genFeedbackPages as $genFeedbackPage) {
            if (ilAssGenFeedbackPage::_exists("qfbg", $genFeedbackPage["id"])) {
                $page = new ilAssGenFeedbackPage($genFeedbackPage["id"]);

                self::_fillFileArrayFromPageContent(
                    $a_file_list,
                    $page->getXMLContent(),
                    $obj_type,
                    $row["type"],
                    $row["ref_id"],
                    $row["title"],
                    $question["title"] . " " . " &raquo; " . $lng->txt("feedback_generic"),
                    $user_id,
                    $lng);
            }
        }

        $specFeedbackPages = ilAssSpecFeedbackPage::getAllPages(
            "qfbs",
            $question["question_id"]);

        foreach ($specFeedbackPages as $specFeedbackPage) {
            if (ilAssSpecFeedbackPage::_exists("qfbs", $specFeedbackPage["id"])) {
                $page = new ilAssSpecFeedbackPage($specFeedbackPage["id"]);

                self::_fillFileArrayFromPageContent(
                    $a_file_list,
                    $page->getXMLContent(),
                    $obj_type,
                    $row["type"],
                    $row["ref_id"],
                    $row["title"],
                    $question["title"] . " " . " &raquo; " . $lng->txt("feedback_generic"),
                    $user_id,
                    $lng);
            }
        }

        require_once "./Modules/TestQuestionPool/classes/class.assQuestion.php";

        assQuestion::_includeClass($question["type_tag"]);
        $questionObj = new $question["type_tag"]($question["question_id"]);
        $questionObj->loadFromDb($question["question_id"]);
        $questionSuggestion = $questionObj->getSuggestedSolution();

        if ($row["owner"] == $user_id) {
            if ($questionSuggestion["type"] === "file") {
                $array_index = $row["obj_id"] . ":" . $question["question_id"] . ":qpl_recapitulation";
                $a_file_list[$array_index]["title"] = strlen($questionSuggestion["value"]["filename"]) ?
                    $questionSuggestion["value"]["filename"] : $questionSuggestion["value"]["name"];
                $a_file_list[$array_index]["option_id"] = self::_getCopyRightValue(
                    $row["obj_id"],
                    $question["question_id"],
                    "qpl_recapitulation");
                $a_file_list[$array_index]["used_in"] .= "<li>" . $obj_type . " (" . self::_buildPath(
                        $tree,
                        $row["ref_id"],
                        "",
                        true) .
                    " &raquo; " . $question["title"] . " &raquo; " .
                    $copyRightPlugin->txt("suggested_solution") . ")</li>";
                $a_file_list[$array_index]["parent"][$row["ref_id"]][$row["type"]] = $obj_type . " (" .
                    $row["title"] . ")";
                $a_file_list[$array_index]["parent"][$row["ref_id"]]["type_title"] = $obj_type;
            }

            if ($question["type_tag"] === "assFileUpload" && $tstObj) {
                $data = $tstObj->getCompleteEvaluationData(FALSE);
                $max_pass = $tstObj->getMaxPassOfTest();

                foreach ($data->getParticipants() as $active_id => $participant) {
                    for ($pass = 0; $pass < $max_pass; $pass++) {
                        $testResultData = $tstObj->getTestResult($active_id, $pass);

                        foreach ($testResultData as $questionData) {
                            if (!isset($questionData["qid"]) || $questionData["qid"] != $question["question_id"]) {
                                continue;
                            }

                            $solutionFiles = $questionObj->getUploadedFiles($active_id, $pass);

                            foreach ($solutionFiles as $solutionFile) {
                                $array_index = $row["obj_id"] . ":" . $active_id . ":tst_solutions|" .
                                    $solutionFile["value1"] . "|" . $solutionFile["solution_id"] . "|" .
                                    $data->getTest()->getTestId() . "|" . $solutionFile["value2"] . "|" .
                                    $questionData["qid"];
                                $a_file_list[$array_index]["title"] = $solutionFile["value2"];
                                $a_file_list[$array_index]["option_id"] = self::_getCopyRightValue(
                                    $row["obj_id"],
                                    $active_id,
                                    "tst_solutions|" . $solutionFile["value1"] . "|" .
                                    $solutionFile["solution_id"] . "|" . $data->getTest()->getTestId() . "|" .
                                    $solutionFile["value2"] . "|" . $questionData["qid"]);
                                $a_file_list[$array_index]["used_in"] .= "<li>" . $obj_type . " (" . self::_buildPath(
                                        $tree,
                                        $row["ref_id"],
                                        "",
                                        true) .
                                    " &raquo; " . $question["title"] . " &raquo; " .
                                    $copyRightPlugin->txt("test_solution") . " &raquo; " .
                                    $lng->txt("toplist_col_participant") . "/" . $lng->txt("pass") . " [" .
                                    $participant->getName() . "/" . ($pass + 1) . "])</li>";
                                $a_file_list[$array_index]["parent"][$row["ref_id"]][$row["type"]] = $obj_type . " (" . $row["title"] . ")";
                                $a_file_list[$array_index]["parent"][$row["ref_id"]]["type_title"] = $obj_type;
                            }
                        }
                    }
                }
            }
        }

        if ($questionObj->getOwner() == $user_id) {
            if ($question["type_tag"] === "assJavaApplet") {
                if ($questionObj->getJavaAppletFilename()) {
                    $array_index = $row["obj_id"] . ":" . $question["question_id"] . ":java_applet";
                    $a_file_list[$array_index]["title"] = $questionObj->getJavaAppletFilename();
                    $a_file_list[$array_index]["option_id"] = self::_getCopyRightValue($row["obj_id"], $question["question_id"], "java_applet");
                    $a_file_list[$array_index]["used_in"] .= "<li>" . $obj_type . " (" . self::_buildPath(
                            $tree,
                            $row["ref_id"],
                            "",
                            true) .
                        " &raquo; " . $question["title"] . " &raquo; " .
                        $copyRightPlugin->txt("question_file") . ")</li>";
                    $a_file_list[$array_index]["parent"][$row["ref_id"]][$row["type"]] = $obj_type . " (" .
                        $row["title"] . ")";
                    $a_file_list[$array_index]["parent"][$row["ref_id"]]["type_title"] = $obj_type;
                }
            } else if ($question["type_tag"] === "assImagemapQuestion") {
                if ($questionObj->getImageFilename()) {
                    $array_index = $row["obj_id"] . ":" . $question["question_id"] . ":image_map";
                    $a_file_list[$array_index]["title"] = $questionObj->getImageFilename();
                    $a_file_list[$array_index]["option_id"] = self::_getCopyRightValue($row["obj_id"], $question["question_id"], "image_map");
                    $a_file_list[$array_index]["used_in"] .= "<li>" . $obj_type . " (" . self::_buildPath(
                            $tree,
                            $row["ref_id"],
                            "",
                            true) .
                        " &raquo; " . $question["title"] . " &raquo; " . $copyRightPlugin->txt("question_file") .
                        ")</li>";
                    $a_file_list[$array_index]["parent"][$row["ref_id"]][$row["type"]] = $obj_type . " (" .
                        $row["title"] . ")";
                    $a_file_list[$array_index]["parent"][$row["ref_id"]]["type_title"] = $obj_type;
                }
            } else if ($question["type_tag"] === "assFlashQuestion") {
                if ($questionObj->getApplet()) {
                    $array_index = $row["obj_id"] . ":" . $question["question_id"] . ":flash";
                    $a_file_list[$array_index]["title"] = $questionObj->getApplet();
                    $a_file_list[$array_index]["option_id"] = self::_getCopyRightValue($row["obj_id"], $question["question_id"], "flash");
                    $a_file_list[$array_index]["used_in"] .= "<li>" . $obj_type . " (" . self::_buildPath(
                            $tree,
                            $row["ref_id"],
                            "",
                            true) .
                        " &raquo; " . $question["title"] . " &raquo; " . $copyRightPlugin->txt("question_file") .
                        ")</li>";
                    $a_file_list[$array_index]["parent"][$row["ref_id"]][$row["type"]] = $obj_type . " (" .
                        $row["title"] . ")";
                    $a_file_list[$array_index]["parent"][$row["ref_id"]]["type_title"] = $obj_type;
                }
            }
        }
    }
}