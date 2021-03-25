<?php

namespace prismaticbytes\prismaticlinks\jobs;

use Craft;
use craft\helpers\FileHelper;
use craft\mail\Message;
use prismaticbytes\prismaticlinks\fields\PrismaticLinksField;

class StoreImage extends \craft\queue\BaseJob
{
    protected $url;

    /**
     * @inheritdoc
     */
    public function execute($queue)
    {
        if (!PrismaticLinksField::cacheFileExists($this->url)) {
            $data = file_get_contents($this->url);

            file_put_contents(PrismaticLinksField::getCachePath($this->url), $data);
        }
    }

    public function setUrl($value)
    {
        $this->url = $value;
    }


    /**
     * @inheritdoc
     */
    protected function defaultDescription()
    {
        return \Craft::t('app', 'Storing link image');
    }
}