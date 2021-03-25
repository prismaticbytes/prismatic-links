<?php
/**
 * Prismatic Links plugin for Craft CMS 3.x
 *
 * Link previews
 *
 * @link      https://prismaticbytes.com
 * @copyright Copyright (c) 2021 Prismatic Bytes
 */

namespace prismaticbytes\prismaticlinks\fields;

use craft\helpers\FileHelper;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use prismaticbytes\prismaticlinks\jobs\StoreImage;
use prismaticbytes\prismaticlinks\PrismaticLinks;
use prismaticbytes\prismaticlinks\assetbundles\prismaticlinks\PrismaticLinksAsset;

use Craft;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\helpers\Db;
use yii\db\Schema;
use craft\helpers\Json;

/**
 * PrismaticLinksField Field
 *
 * Whenever someone creates a new field in Craft, they must specify what
 * type of field it is. The system comes with a handful of field types baked in,
 * and we’ve made it extremely easy for plugins to add new ones.
 *
 * https://craftcms.com/docs/plugins/field-types
 *
 * @author    Prismatic Bytes
 * @package   PrismaticLinks
 * @since     1.0.0
 */
class PrismaticLinksField extends Field
{
    // Public Properties
    // =========================================================================

    /**
     * Some attribute
     *
     * @var string
     */
    public $someAttribute = 'Some Default';

    // Static Methods
    // =========================================================================

    /**
     * Returns the display name of this class.
     *
     * @return string The display name of this class.
     */
    public static function displayName(): string
    {
        return Craft::t('prismatic-links', 'Prismatic Link');
    }

    // Public Methods
    // =========================================================================

    /**
     * Returns the validation rules for attributes.
     *
     * Validation rules are used by [[validate()]] to check if attribute values are valid.
     * Child classes may override this method to declare different validation rules.
     *
     * More info: http://www.yiiframework.com/doc-2.0/guide-input-validation.html
     *
     * @return array
     */
    public function rules()
    {
        $rules = parent::rules();
        $rules = array_merge($rules, [
            ['someAttribute', 'string'],
            ['someAttribute', 'default', 'value' => 'Some Default'],
        ]);
        return $rules;
    }

    /**
     * Returns the column type that this field should get within the content table.
     *
     * This method will only be called if [[hasContentColumn()]] returns true.
     *
     * @return string The column type. [[\yii\db\QueryBuilder::getColumnType()]] will be called
     * to convert the give column type to the physical one. For example, `string` will be converted
     * as `varchar(255)` and `string(100)` becomes `varchar(100)`. `not null` will automatically be
     * appended as well.
     * @see \yii\db\QueryBuilder::getColumnType()
     */
    public function getContentColumnType(): string
    {
        return Schema::TYPE_TEXT;
    }

    /**
     * Normalizes the field’s value for use.
     *
     * This method is called when the field’s value is first accessed from the element. For example, the first time
     * `entry.myFieldHandle` is called from a template, or right before [[getInputHtml()]] is called. Whatever
     * this method returns is what `entry.myFieldHandle` will likewise return, and what [[getInputHtml()]]’s and
     * [[serializeValue()]]’s $value arguments will be set to.
     *
     * @param mixed $value The raw field value
     * @param ElementInterface|null $element The element the field is associated with, if there is one
     *
     * @return mixed The prepared field value
     */
    public function normalizeValue($value, ElementInterface $element = null)
    {
        $data = json_decode($value, true) ?? [];

        $data['valid'] = $data['valid'] ?? null;
        $data['image'] = $data['image'] ?? null;
        $data['image_cached'] = null;
        $data['title'] = $data['title'] ?? null;
        $data['domain'] = $data['domain'] ?? null;
        $data['description'] = $data['description'] ?? null;
        $data['images'] = $data['images'] ?? [];
        $data['url'] = $data['url'] ?? null;


        if (isset($data['image'])) {
            if (static::cacheFileExists($data['image'])) {
                $data['image_cached'] = '/actions/prismatic-links/default/cache?file=' . static::slugify($data['image']);
            }
        }

        return $data;
    }

    /**
     * Prepares the field’s value to be stored somewhere, like the content table or JSON-encoded in an entry revision table.
     *
     * Data types that are JSON-encodable are safe (arrays, integers, strings, booleans, etc).
     * Whatever this returns should be something [[normalizeValue()]] can handle.
     *
     * @param mixed $value The raw field value
     * @param ElementInterface|null $element The element the field is associated with, if there is one
     * @return mixed The serialized field value
     */
    public function serializeValue($value, ElementInterface $element = null)
    {
        if (isset($value['image'])) {
            \craft\helpers\Queue::push(new StoreImage([
                'url'    => $value['image'],
            ]));
        }

        return parent::serializeValue($value, $element);
    }

    public static function slugify($url)
    {
        return substr(preg_replace('/\W/', '-', $url), 0, 255);
    }

    public static function cacheFileExists($imageUrl)
    {
        return file_exists(static::getCachePath($imageUrl));
    }

    public static function getCachePath($imageUrl)
    {
        $saveAs = static::slugify($imageUrl);

        $path = Craft::$app->path->getStoragePath() . DIRECTORY_SEPARATOR . 'prismaticlinks';

        if (!is_dir($path)) {
            FileHelper::createDirectory($path);
            FileHelper::writeGitignoreFile($path);
        }

        return $path . DIRECTORY_SEPARATOR . '/' . $saveAs;
    }

    // /**
    //  * Returns the component’s settings HTML.
    //  *
    //  * @return string|null
    //  */
    // public function getSettingsHtml()
    // {
    //     // Render the settings template
    //     return Craft::$app->getView()->renderTemplate(
    //         'prismatic-links/_components/fields/PrismaticLinksField_settings',
    //         [
    //             'field' => $this,
    //         ]
    //     );
    // }

    /**
     * Returns the field’s input HTML.
     *
     * @param mixed $value The field’s value. This will either be the [[normalizeValue() normalized value]],
     *                                               raw POST data (i.e. if there was a validation error), or null
     * @param ElementInterface|null $element The element the field is associated with, if there is one
     *
     * @return string The input HTML.
     */
    public function getInputHtml($value, ElementInterface $element = null): string
    {
        // Register our asset bundle
        Craft::$app->getView()->registerAssetBundle(PrismaticLinksAsset::class);

        // Get our id and namespace
        $id           = Craft::$app->getView()->formatInputId($this->handle);
        $namespacedId = Craft::$app->getView()->namespaceInputId($id);

        // Variables to pass down to our field JavaScript to let it namespace properly
        $jsonVars = [
            'id'        => $id,
            'name'      => $this->handle,
            'namespace' => $namespacedId,
            'prefix'    => Craft::$app->getView()->namespaceInputId(''),
        ];
        $jsonVars = Json::encode($jsonVars);

        Craft::$app->getView()->registerJs("$('#{$namespacedId}-field').PrismaticLinks(" . $jsonVars . ");");

        // Render the input template
        return Craft::$app->getView()->renderTemplate(
            'prismatic-links/_components/fields/PrismaticLinksField_input',
            [
                'name'         => $this->handle,
                'value'        => $value,
                'field'        => $this,
                'id'           => $id,
                'namespacedId' => $namespacedId,
            ]
        );
    }


    /**
     * @inheritdoc
     */
    public function getContentGqlType()
    {
        static $count = 0;
        $count++;
        return new ObjectType([
            'name'   => 'PrismaticLink_' . $count,
            'fields' => [
                'url' => Type::string(),
            ],
        ]);
    }

}
