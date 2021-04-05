<?php
/**
 * Prismatic Links plugin for Craft CMS 3.x
 *
 * Link previews
 *
 * @link      https://prismaticbytes.com
 * @copyright Copyright (c) 2021 Prismatic Bytes
 */

namespace prismaticbytes\prismaticlinks\controllers;

use finfo;
use prismaticbytes\prismaticlinks\fields\PrismaticLinksField;
use prismaticbytes\prismaticlinks\PrismaticLinks;

use Craft;
use craft\web\Controller;
use craft\web\View;

/**
 * Default Controller
 *
 * Generally speaking, controllers are the middlemen between the front end of
 * the CP/website and your plugin’s services. They contain action methods which
 * handle individual tasks.
 *
 * A common pattern used throughout Craft involves a controller action gathering
 * post data, saving it on a model, passing the model off to a service, and then
 * responding to the request appropriately depending on the service method’s response.
 *
 * Action methods begin with the prefix “action”, followed by a description of what
 * the method does (for example, actionSaveIngredient()).
 *
 * https://craftcms.com/docs/plugins/controllers
 *
 * @author    Prismatic Bytes
 * @package   PrismaticLinks
 * @since     1.0.0
 */
class DefaultController extends Controller
{

    // Protected Properties
    // =========================================================================

    /**
     * @var    bool|array Allows anonymous access to this controller's actions.
     *         The actions must be in 'kebab-case'
     * @access protected
     */
    protected $allowAnonymous = ['cache'];

    // Public Methods
    // =========================================================================

    public function actionCache()
    {
        $slug = $this->request->get('file');
        if (PrismaticLinksField::cacheFileExists($slug)) {

            $finfo = new finfo();
            $fileinfo = $finfo->file(PrismaticLinksField::getCachePath($slug), FILEINFO_MIME);

            if ($fileinfo) {
                header('Content-Type: ' . $fileinfo);
            }

            readfile(PrismaticLinksField::getCachePath($slug));
        }
    }

    /**
     *
     * @return mixed
     */
    public function actionParse()
    {
        $url = $this->request->get('url');

        // Get previews from all available parsers
        try {
            $data = PrismaticLinks::getInstance()->prismaticLinksService->fetchURL($url);
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            if ($e->getCode() === 404) {
                return $this->asJson([
                    'error' => '404 Not Found'
                ])->setStatusCode(400);
            }
            return $this->asJson([
                'error' => $e->getMessage()
            ])->setStatusCode(400);
        } catch (\Dusterio\LinkPreview\Exceptions\MalformedUrlException $e) {
            return $this->asJson([
                'error' => 'Invalid URL'
            ])->setStatusCode(400);
        } catch (\Dusterio\LinkPreview\Exceptions\ConnectionErrorException $e) {
            return $this->asJson([
                'error' => 'Failed to load URL'
            ])->setStatusCode(400);
        } catch (\Exception $e) {
            return $this->asJson([
                'error' => 'An unknown error occurred'
            ])->setStatusCode(400);
        }

        return $this->asJson($data);
    }


    /**
     *
     * @return mixed
     */
    public function actionPreview()
    {
        $data = json_decode($this->request->get('data'), true);

        return $this->renderTemplate('prismatic-links/prismatic-link-template.twig', $data, View::TEMPLATE_MODE_CP);
    }

}
