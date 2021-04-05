<?php

namespace prismaticbytes\prismaticlinks\jobs;

use Craft;
use craft\helpers\FileHelper;
use craft\mail\Message;
use finfo;
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

            $fh = tmpfile();
            $path = stream_get_meta_data($fh)['uri'];

            fwrite($fh, $data);

            $finfo = new finfo();
            $fileinfo = $finfo->file($path, FILEINFO_MIME);

            fclose($fh);

            if (preg_match('#^image/#', $fileinfo)) {

                file_put_contents(PrismaticLinksField::getCachePath($this->url), $data);

            } else {
                Craft::info("File is not an image");
                return;
            }

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