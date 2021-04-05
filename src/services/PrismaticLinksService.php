<?php
/**
 * Prismatic Links plugin for Craft CMS 3.x
 *
 * Link previews
 *
 * @link      https://prismaticbytes.com
 * @copyright Copyright (c) 2021 Stephen Frank
 */

namespace prismaticbytes\prismaticlinks\services;

use prismaticbytes\prismaticlinks\PrismaticLinks;

use Craft;
use craft\base\Component;

/**
 * PrismaticLinksService Service
 *
 * All of your plugin’s business logic should go in services, including saving data,
 * retrieving data, etc. They provide APIs that your controllers, template variables,
 * and other plugins can interact with.
 *
 * https://craftcms.com/docs/plugins/services
 *
 * @author    Stephen Frank
 * @package   PrismaticLinks
 * @since     1.0.0
 */
class PrismaticLinksService extends Component
{
    // Public Methods
    // =========================================================================

    /**
     *
     * @return mixed
     */
    public function fetchURL($url)
    {
        $urlParts = parse_url($url);

        try {
            $previewClient = new \Dusterio\LinkPreview\Client($url);
            $preview = $previewClient->getPreview('general');
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

        // Convert output to array
        $preview = $preview->toArray();

        if (strlen($preview['description']) > 200) {
            $preview['description'] = substr(
                    $preview['description'],
                    0,
                    strrpos(substr($preview['description'], 0, 200), ' '))
                . '...';
        }

        $preview['description'] = $preview['description'] ?? null;
        $preview['description'] = htmlspecialchars($preview['description']);

        $preview['title'] = $preview['title'] ?? null;
        $preview['title'] = htmlspecialchars($preview['title']);

        array_unshift($preview['images'], $preview['cover']);

        return [
            'url'         => $url,
            'image'       => $preview['cover'] ?? null,
            'title'       => $preview['title'] ?? null,
            'description' => $preview['description'] ?? null,
            'domain'      => $urlParts['host'],
            'images'      => $preview['images'] ?? [],
            'valid'      => true,
        ];
    }
}
