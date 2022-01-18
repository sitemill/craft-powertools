<?php
/**
 * powertools plugin for Craft CMS 3.x
 *
 * A set of tools used internally by Sitemill
 *
 * @link      sitemill.co
 * @copyright Copyright (c) 2022 sitemill
 */

namespace sitemill\powertools;

use Craft;
use sitemill\powertools\services\PhoneHome;

/**
 * Class Powertools
 *
 * @author    sitemill
 * @package   Powertools
 * @since     1.0.0
 *
 */
class Powertools extends \craft\base\Plugin
{
    public function init()
    {
        parent::init();

        if (Craft::$app->request->getIsCpRequest()) {
            if (!Craft::$app->request->getIsAjax() && Craft::$app->isInstalled && Craft::$app->env == 'production') {
                PhoneHome::makeCall();
            }
        }
    }


}
