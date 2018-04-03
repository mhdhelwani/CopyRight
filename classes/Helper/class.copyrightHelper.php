<?php

ilCopyRightPlugin::getInstance()->includeClass('class.ilCopyRightPlugin.php');
ilCopyRightPlugin::getInstance()->includeClass('EntryInstanceFactory/class.copyrightEntryInstanceFactory.php');
require_once("./Services/Form/classes/class.ilSelectInputGUI.php");

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
        try {
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
        } catch (Exception $e){}
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
     * @param     $a_obj_type object type
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

        $copyRightPlugin = new ilCopyRightPlugin();

        $file_list = [];

        $sql = "SELECT * FROM cron_crnhk_files_list od WHERE owner = " . $ilDB->quote($a_user_id, "integer");

        $res = $ilDB->query($sql);

        while ($row = $ilDB->fetchAssoc($res)) {
            $parent_type = json_decode($row["parent_type"], true);

            if (key($parent_type) === "lng") {
                $obj_type = $lng->txt($parent_type["lng"][0]);
                $obj_type_code = $parent_type["lng"][1];
            } else {
                $obj_type = $parent_type["txt"][0];
                $obj_type_code = $parent_type["txt"][1];
            }

            $lng_modules = json_decode($row["lng_modules"], true);

            if ($lng_modules) {
                foreach ($lng_modules as $lng_module) {
                    $lng->loadLanguageModule($lng_module);
                }
            }

            $array_index = $row["obj_id"] . ":" . $row["sub_id"] . ":" . $row["file_info"];
            $file_list[$array_index]["title"] = $row["file_title"];
            $file_list[$array_index]["option_id"] = self::_getCopyRightValue(
                $row["obj_id"],
                $row["sub_id"],
                $row["file_info"]);

            $path_parms = json_decode($row["path_parms"], true);
            $path_parm_array = [];
            foreach ($path_parms as $key => $path_parm) {
                if ($key === "lng") {
                    foreach ($path_parm as $key1 => $parm) {
                        $path_parm_array[trim($key1)] = $lng->txt($parm);
                    }
                } else if ($key === "copyright"){
                    foreach ($path_parm as $key1 => $parm) {
                        $path_parm_array[trim($key1)] = $copyRightPlugin->txt($parm);
                    }
                } else {
                    foreach ($path_parm as $key1 => $parm) {
                        $path_parm_array[trim($key1)] = $parm;
                    }
                }
            }

            $file_list[$array_index]["used_in"] .= "<li>" . vsprintf($row["path"], $path_parm_array) . "</li>";

            $parent_title_parms = json_decode($row["parent_title_parms"], true);
            $parent_title_parm_array = [];
            foreach ($parent_title_parms as $key => $parent_title_parm) {
                if ($key === "lng") {
                    foreach ($parent_title_parm as $key1 => $parm) {
                        $parent_title_parm_array[trim($key1)] = $lng->txt($parm);
                    }
                } else if ($key === "copyright"){
                    foreach ($parent_title_parm as $key1 => $parm) {
                        $parent_title_parm_array[trim($key1)] = $copyRightPlugin->txt($parm);
                    }
                }else {
                    foreach ($parent_title_parm as $key1 => $parm) {
                        $parent_title_parm_array[trim($key1)] = $parm;
                    }
                }
            }
            $file_list[$array_index]["parent"][$row["ref_id"]][$obj_type_code] = vsprintf($row["parent_title"], $parent_title_parm_array);
            $file_list[$array_index]["parent"][$row["ref_id"]]["type_title"] = $obj_type;
        }

        return $file_list;
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
}