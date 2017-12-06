<?php

require_once("./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/CopyRight/classes/class.copyrightTranslation.php");

class copyrightEntry extends ActiveRecord
{
    const TABLE_NAME = "ui_uihk_copyright_o_e";

    /**
     * @var int
     *
     * @con_is_primary true
     * @con_sequence   true
     * @con_has_field  true
     * @con_fieldtype  integer
     * @con_length     8
     */
    public $id;

    /**
     * @var boolean
     *
     * @db_has_field    true
     * @db_fieldtype    integer
     * @db_length       8
     * @db_is_notnull   true
     */
    protected $active = true;

    /**
     * @var boolean
     *
     * @db_has_field    true
     * @db_fieldtype    integer
     * @db_length       1
     * @db_is_notnull   true
     */
    protected $default_option = true;

    /**
     * @var array
     */
    protected $translations = [];

    /**
     *
     * @var string
     */
    protected $title = "";

    /**
     * @var string
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

    public function __construct($primary_key = 0)
    {
        parent::__construct($primary_key);

        if (isset($primary_key)) {
            $title_info_text = copyrightTranslation::_getTitleAndInfoTextForOptionId($this->getId());
            $this->setTitle($title_info_text["title"]);
            $this->setInfoText($title_info_text["info_text"]);
            $this->setTranslations(copyrightTranslation::_getAllTranslationsAsArray($this->getId()));
        }
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @param $lng
     *
     * @return bool
     */
    public static function isDefaultLanguage($lng)
    {
        $lngs = new ilLanguage("en");

        return $lngs->getDefaultLanguage() == $lng ? true : false;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
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

    /**
     * @return array
     */
    public static function getAllLanguageIds()
    {
        $lngs = new ilLanguage("en");

        return $lngs->getInstalledLanguages();
    }

    public function create()
    {
        if ($this->getId() != 0) {
            $this->update();
        } else {
            parent::create();
            $this->writeTranslations();
        }
    }

    public function update()
    {
        parent::update();

        $this->writeTranslations();
    }

    public function updateDefaultOption()
    {
        parent::update();
    }

    protected function writeTranslations()
    {
        $translations = $this->getTranslations();
        foreach (self::getAllLanguageIds() as $k) {
            if (!array_key_exists($k, $translations)) {
                $trans = copyrightTranslation::_getInstanceForLanguageKey($this->getId(), $k);
                $trans->delete();
            }
        }

        foreach ($this->getTranslations() as $k => $v) {
            $trans = copyrightTranslation::_getInstanceForLanguageKey($this->getId(), $k);
            $trans->setTitle($v["title"]);
            $trans->setInfoText($v["info_text"]);
            $trans->store();
        }
    }

    /**
     * @return array
     */
    public function getTranslations()
    {
        return $this->translations;
    }

    /**
     * @param array $translations
     */
    public function setTranslations($translations)
    {
        $this->translations = $translations;
    }

    /**
     * @param boolean $active
     */
    public function setActive($active)
    {
        $this->active = $active;
    }

    /**
     * @return boolean
     */
    public function getActive()
    {
        return (bool)$this->active;
    }

    /**
     * @param boolean $default_option
     */
    public function setDefaultOption($default_option)
    {
        $this->default_option = $default_option;
    }

    /**
     * @return boolean
     */
    public function getDefaultOption()
    {
        return (bool)$this->default_option;
    }
}