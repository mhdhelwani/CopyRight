<?php
include_once("./Services/UIComponent/classes/class.ilUserInterfaceHookPlugin.php");

class ilCopyRightPlugin extends ilUserInterfaceHookPlugin
{
    /**
     * @var ilCopyRightPlugin
     */
    protected static $instance;

	function getPluginName()
	{
		return "CopyRight";
	}

    public function isCopyRightCronIsActive() {
        global $ilPluginAdmin;
        /**
         * @var ilPluginAdmin $ilPluginAdmin
         */
        return in_array('CopyRightCron', $ilPluginAdmin->getActivePluginsForSlot('Services', 'Cron', 'crnhk'));
    }

    /**
     * @return bool
     */
    public function beforeActivation() {
        //if CtrlMainMenu Plugin is active and no Video-Manager entry exists, create one
        if (self::isCopyRightCronIsActive()) {
            return true;
        }
        ilUtil::sendFailure($this->txt('msg_no_copyright_cron'), true);
        return false;
    }

    /**
     * @return ilCopyRightPlugin
     */
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

}
