<?php
/**
 * Prismatic Links plugin for Craft CMS 3.x
 *
 * test
 *
 * @link      https://prismaticbytes.com
 * @copyright Copyright (c) 2021 Prismatic Bytes
 */

namespace prismaticbytes\prismaticlinks\variables;

use craft\web\View;
use prismaticbytes\prismaticlinks\fields\PrismaticLinksField;
use \prismaticbytes\prismaticlinks\PrismaticLinks;

use Craft;

/**
 * Prismatic Links Variable
 *
 * Craft allows plugins to provide their own template variables, accessible from
 * the {{ craft }} global variable (e.g. {{ craft.prismaticLinks }}).
 *
 * https://craftcms.com/docs/plugins/variables
 *
 * @author    Prismatic Bytes
 * @package   PrismaticLinks
 * @since     1.0.0
 */
class PrismaticLinksVariable
{
    // Public Methods
    // =========================================================================

    /**
     * Whatever you want to output to a Twig template can go into a Variable method.
     * You can have as many variable functions as you want.  From any Twig template,
     * call it like this:
     *
     *     {{ craft.prismaticLinks.exampleVariable }}
     *
     * Or, if your variable requires parameters from Twig:
     *
     *     {{ craft.prismaticLinks.exampleVariable(twigValue) }}
     *
     * @param null $optional
     * @return string
     */
    public function fetch($url, $template = null, $cacheDuration = null)
    {
        // $cacheDuration
        $cacheKey = 'prismaticlinks-' . PrismaticLinksField::slugify($url);

        if (Craft::$app->cache->exists($cacheKey) && $cacheDuration !== -1) {
            $data = Craft::$app->cache->get($cacheKey);
        } else {
            try {
                $data = PrismaticLinks::getInstance()->prismaticLinksService->fetchURL($url);
                Craft::$app->cache->set($cacheKey, $data, $cacheDuration);
            } catch (\Exception $e) {
                return;
            }
        }

        return \Craft::$app->view->renderTemplate(
            $template ?? 'prismatic-links/prismatic-link-template.twig',
            $data,
            View::TEMPLATE_MODE_SITE
        );
    }
}
