<?php
include_once("./Services/UIComponent/classes/class.ilUserInterfaceHookPlugin.php");

class ilCopyRightPlugin extends ilUserInterfaceHookPlugin
{
	function getPluginName()
	{
		return "CopyRight";
	}
}
