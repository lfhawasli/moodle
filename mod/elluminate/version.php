<?php
$module->requires = 2010112400;
// START UCLA MOD: CCLE-4401 - Blackboard collaborate cron failing
//$module->version  = 2013080201;  // The current module version (Date: YYYYMMDDxx)
//$module->cron     = 600;         // Period for cron to check this module (secs)
$module->version  = 2013080202;  // The current module version (Date: YYYYMMDDxx)
$module->cron     = 1800;         // Period for cron to check this module (secs)
// END UCLA MOD: CCLE-4401
$module->release  = '3.1.0-7';	 // Human Readable version number
$module->maturity = MATURITY_STABLE;
$module->component = 'mod_elluminate';
