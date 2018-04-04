<#1>
    <?php
    require_once('./Services/ActiveRecord/class.ActiveRecord.php');

    require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/CopyRight/classes/Entry/class.copyrightEntry.php');
    copyrightEntry::installDB();

    require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/CopyRight/classes/class.copyrightTranslation.php');
    copyrightTranslation::installDB();

?>
<#2>
<?php
if (!$ilDB->tableExists('copy_right_data')) {
    $fields = array(
        'obj_id' => array(
            'type' => 'integer',
            'length' => 8,
            'notnull' => true
        ),
        'option_id' => array(
            'type' => 'integer',
            'length' => 8,
            'notnull' => true
        ),
        'sub_id' => array(
            'type' => 'integer',
            'length' => 8,
            'notnull' => false
        ),
        'file_location' => array(
            'type' => 'text',
            'length' => 255,
            'notnull' => false
        )
    );
    $ilDB->createTable("copy_right_data", $fields);
    $ilDB->addUniqueConstraint("copy_right_data", array("obj_id", "sub_id", "file_location"));
}
?>