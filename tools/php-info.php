<?php/*Wave FrameworkPHP informationThis simple script is simply used to return current PHP information and call phpinfo() function. This function details a lot of important information about PHP installation and server setup.* It is recommended to remove all files from /tools/ subfolder prior to deploying project in liveAuthor and support: Kristo Vaher - kristo@waher.netLicense: GNU Lesser General Public License Version 3*/// This initializes tools and authenticationrequire('.'.DIRECTORY_SEPARATOR.'tools_autoload.php');// Simple phpinfo() is executed, returning all the relevant data about current installationphpinfo();	?>