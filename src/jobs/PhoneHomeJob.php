<?php


namespace sitemill\powertools\jobs;


use craft\queue\BaseJob;
use sitemill\powertools\services\PhoneHome;

class PhoneHomeJob extends BaseJob
{
    /**
     * @inheritdoc
     */
    public function execute($queue)
    {
        PhoneHome::makeRequest();
    }

    /**
     * @inheritdoc
     */
    protected function defaultDescription()
    {
        return 'Phoning home';
    }
}