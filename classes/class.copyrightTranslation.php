<?php

class copyrightTranslation extends ActiveRecord
{
    const TABLE_NAME = "ui_uihk_copyright_o_t";

    /**
     * @var int
     *
     * @con_is_primary true
     * @con_sequence   true
     * @con_has_field  true
     * @con_fieldtype  integer
     * @con_is_notnull true
     * @con_length     8
     */
    protected $id = 0;

    /**
     * @var int
     *
     * @con_has_field  true
     * @con_fieldtype  integer
     * @con_is_notnull true
     * @con_length     8
     */
    protected $option_id = 0;

    /**
     * @var string
     *
     * @con_has_field  true
     * @con_fieldtype  text
     * @con_is_notnull true
     * @con_length     255
     */
    protected $language_key = "";

    /**
     * @var string
     *
     * @con_has_field  true
     * @con_fieldtype  text
     * @con_length     255
     */
    protected $title = "";


    /**
     * @var string
     *
     * @con_has_field  true
     * @con_fieldtype  clob
     */
    protected $info_text = "";

    /**
     * @return string
     * @description Return the Name of your Database Table
     * @deprecated
     */
    static function returnDbTableName()
    {
        return self::TABLE_NAME;
    }

    /**
     * @param $option_id
     * @param $language_key
     *
     * @return copyrightTranslation
     */
    public static function _getInstanceForLanguageKey($option_id, $language_key)
    {
        $result = self::where(["option_id" => $option_id, "language_key" => $language_key]);

        if ($result->hasSets()) {
            $instance = $result->first();
        } else {
            $instance = new self();
            $instance->setLanguageKey($language_key);
            $instance->setOptionId($option_id);
        }

        return $instance;
    }

    /**
     * @param $option_id
     *
     * @return mixed
     */
    public static function _getAllTranslationsAsArray($option_id)
    {
        $query = self::where(["option_id" => $option_id]);
        $entries = $query->getArray();
        $return = [];

        foreach ($entries as $set) {
            $return[$set["language_key"]]["title"] = $set["title"];
            $return[$set["language_key"]]["info_text"] = $set["info_text"];
        }

        return $return;
    }

    /**
     * @param $option_id
     *
     * @return bool|array
     */
    public static function _getTitleAndInfoTextForOptionId($option_id)
    {
        global $ilUser;

        $obj = self::_getInstanceForLanguageKey($option_id, $ilUser->getLanguage());

        if (!isset($obj)) {
            require_once("./Services/Language/classes/class.ilLanguage.php");
            $lngs = new ilLanguage("en");
            $obj = self::_getInstanceForLanguageKey($option_id, $lngs->getDefaultLanguage());

            if ($obj->getId() == 0) {
                return false;
            }
        } else {
            if (!$obj->getTitle()) {
                require_once("./Services/Language/classes/class.ilLanguage.php");
                $lngs = new ilLanguage("en");
                $obj = self::_getInstanceForLanguageKey($option_id, $lngs->getDefaultLanguage());

                if ($obj->getId() == 0) {
                    return false;
                }
            }
        }

        return ["title" => $obj->getTitle(), "info_text" => $obj->getInfoText()];
    }

    /**
     * @param $option_id
     *
     * @return copyrightTranslation[]
     */
    public function _getAllInstancesForOptionId($option_id)
    {
        $result = self::where(["option_id" => $option_id]);

        return $result->get();
    }

    public static function _deleteAllInstancesForOptionId($option_id)
    {
        foreach (self::_getAllInstancesForOptionId($option_id) as $tr) {
            $tr->delete();
        }
    }

    //
    // Setter & Getter
    //
    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $option_id
     */
    public function setOptionId($option_id)
    {
        $this->option_id = $option_id;
    }

    /**
     * @return int
     */
    public function getOptionId()
    {
        return $this->option_id;
    }

    /**
     * @param string $language_key
     */
    public function setLanguageKey($language_key)
    {
        $this->language_key = $language_key;
    }

    /**
     * @return string
     */
    public function getLanguageKey()
    {
        return $this->language_key;
    }

    /**
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @return string
     */
    public function getInfoText()
    {
        return $this->info_text;
    }

    /**
     * @param string $info_text
     */
    public function setInfoText($info_text)
    {
        $this->info_text = $info_text;
    }
}