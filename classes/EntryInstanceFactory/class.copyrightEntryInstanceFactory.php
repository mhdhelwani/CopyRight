<?php

require_once("./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/CopyRight/classes/Entry/class.copyrightEntry.php");

class copyrightEntryInstanceFactory
{
    /**
     * @return copyrightEntry[]
     */
    public static function getAll()
    {
        $options = [];
        $sets = copyrightEntry::where([])->orderBy("id");

        foreach ($sets->get() as $set) {
            $copyrightEntry = new copyrightEntry($set->id);
            $copyrightEntryArray = [
                "id" => $copyrightEntry->getId(),
                "active" => $copyrightEntry->getActive(),
                "translations" => $copyrightEntry->getTranslations(),
                "title" => $copyrightEntry->getTitle(),
                "info_text" => $copyrightEntry->getInfoText(),
                "default_option" => $copyrightEntry->getDefaultOption(),
            ];
            $options[] = $copyrightEntryArray;
        }

        return $options;
    }

    /**
     * @return copyrightEntry[]
     */
    public static function getActiveOption()
    {
        $options = [];
        $sets = copyrightEntry::where(["active" => true])->orderBy("id");

        foreach ($sets->get() as $set) {
            $copyrightEntry = new copyrightEntry($set->id);
            $copyrightEntryArray = [
                "id" => $copyrightEntry->getId(),
                "active" => $copyrightEntry->getActive(),
                "translations" => $copyrightEntry->getTranslations(),
                "title" => $copyrightEntry->getTitle(),
                "info_text" => $copyrightEntry->getInfoText(),
                "default_option" => $copyrightEntry->getDefaultOption(),
            ];
            $options[] = $copyrightEntryArray;
        }

        return $options;
    }

    /**
     * @return int
     */
    public static function getDefaultOption()
    {
        $sets = copyrightEntry::where(["default_option" => true]);
        $copyRightOptionDefault = 0;

        foreach ($sets->get() as $set) {
            $copyRightOptionDefault = $set->id;
        }

        return $copyRightOptionDefault;
    }

    /**
     * @var $option_id
     *
     * @return array
     */
    public static function getTitleByOptionId($option_id)
    {
        $copyrightEntryArray = [];
        $sets = copyrightEntry::where(["id" => $option_id]);

        foreach ($sets->get() as $set) {
            $copyrightEntry = new copyrightEntry($set->id);
            $copyrightEntryArray = [
                "id" => $copyrightEntry->getId(),
                "active" => $copyrightEntry->getActive(),
                "translations" => $copyrightEntry->getTranslations(),
                "title" => $copyrightEntry->getTitle(),
                "info_text" => $copyrightEntry->getInfoText(),
                "default_option" => $copyrightEntry->getDefaultOption(),
            ];
        }

        return $copyrightEntryArray;
    }
}
