<?php
/**
 * Preparse Field plugin for Craft CMS 3.x
 *
 * @link      https://www.vaersaagod.no
 * @copyright Copyright (c) 2017 André Elvan
 */

namespace aelvan\preparsefield\fields;

use Craft;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\base\PreviewableFieldInterface;
use craft\db\mysql\Schema;
use craft\helpers\DateTimeHelper;
use craft\helpers\Db;
use craft\i18n\Locale;

/**
 *  Preparse field type
 *
 * @author    André Elvan
 * @package   PreparseField
 * @since     1.0.0
 */
class PreparseFieldType extends Field implements PreviewableFieldInterface
{
    // Public Properties
    // =========================================================================

    /**
     * Some attribute
     *
     * @var string
     */
    public $fieldTwig = '';
    public $displayType = 'hidden';
    public $showField = false;
    public $columnType = Schema::TYPE_TEXT;
    public $decimals = 0;
    public $textareaRows = 5;
    public $parseBeforeSave = false;
    public $parseOnMove = false;
    public $allowSelect = false;

    // Static Methods
    // =========================================================================

    /**
     * Returns the display name of this class.
     *
     * @return string The display name of this class.
     */
    public static function displayName(): string
    {
        return Craft::t('preparse-field', 'Preparse Field');
    }

    // Public Methods
    // =========================================================================

    public function rules()
    {
        $rules = parent::rules();
        $rules = array_merge($rules, [
            ['fieldTwig', 'string'],
            ['fieldTwig', 'default', 'value' => ''],
            ['columnType', 'string'],
            ['columnType', 'default', 'value' => ''],
            ['decimals', 'number'],
            ['decimals', 'default', 'value' => 0],
            ['textareaRows', 'number'],
            ['textareaRows', 'default', 'value' => 5],
            ['parseBeforeSave', 'boolean'],
            ['parseBeforeSave', 'default', 'value' => false],
            ['parseOnMove', 'boolean'],
            ['parseOnMove', 'default', 'value' => false],
            ['displayType', 'string'],
            ['displayType', 'default', 'value' => 'hidden'],
            ['allowSelect', 'boolean'],
            ['allowSelect', 'default', 'value' => false],
        ]);

        return $rules;
    }

    /**
     * @return string
     * @throws \yii\base\Exception
     */
    public function getContentColumnType(): string
    {
        if ($this->columnType === Schema::TYPE_DECIMAL) {
            return Db::getNumericalColumnType(null, null, $this->decimals);
        }

        return $this->columnType;
    }

    /**
     * @param mixed $value
     * @param ElementInterface $element
     * @return string
     * @throws \yii\base\InvalidConfigException
     */
    public function getTableAttributeHtml($value, ElementInterface $element): string
    {
        if (!$value) {
            return '';
        }
        if ($this->columnType === Schema::TYPE_DATETIME) {
            $formatter = Craft::$app->getFormatter();
            /** @var DateTime $value */
            return '<span title="' . $formatter->asDatetime($value, Locale::LENGTH_SHORT) . '">' . $formatter->asTimestamp($value, Locale::LENGTH_SHORT) . '</span>';
        }
        return parent::getTableAttributeHtml($value, $element);
    }

    /**
     * @param $value
     * @param ElementInterface|null $element
     * @return \DateTime|false|mixed
     */
    public function normalizeValue($value, ElementInterface $element = null)
    {
        if ($this->columnType === Schema::TYPE_DATETIME) {
            if ($value && ($date = DateTimeHelper::toDateTime($value)) !== false) {
                return $date;
            }
            return null;
        }
        return parent::normalizeValue($value, $element);
    }

    /**
     * @return null|string
     * @throws \Twig_Error_Loader
     * @throws \yii\base\Exception
     */
    public function getSettingsHtml()
    {
        $columns = [
            Schema::TYPE_TEXT => Craft::t('preparse-field', 'Text (stores about 64K)'),
            Schema::TYPE_MEDIUMTEXT => Craft::t('preparse-field', 'Mediumtext (stores about 16MB)'),
            Schema::TYPE_INTEGER => Craft::t('preparse-field', 'Number (integer)'),
            Schema::TYPE_DECIMAL => Craft::t('preparse-field', 'Number (decimal)'),
            Schema::TYPE_FLOAT => Craft::t('preparse-field', 'Number (float)'),
            Schema::TYPE_DATETIME => Craft::t('preparse-field', 'Date (datetime)'),
        ];

        $displayTypes = [
            'hidden' => 'Hidden',
            'textinput' => 'Text input',
            'textarea' => 'Textarea',
        ];

        // Render the settings template
        return Craft::$app->getView()->renderTemplate(
            'preparse-field/_components/fields/_settings',
            [
                'field' => $this,
                'columns' => $columns,
                'displayTypes' => $displayTypes,
                'existing' => !empty($this->model->id),
            ]
        );
    }

    /**
     * @param mixed                 $value
     * @param ElementInterface|null $element
     *
     * @return string
     */
    public function getInputHtml($value, ElementInterface $element = null): string
    {
        // Get our id and namespace
        $id = Craft::$app->getView()->formatInputId($this->handle);
        $namespacedId = Craft::$app->getView()->namespaceInputId($id);

        // Render the input template
        $displayType = $this->displayType;
        if ($displayType !== 'hidden' && $this->columnType === Schema::TYPE_DATETIME) {
            $displayType = 'date';
        }
        return Craft::$app->getView()->renderTemplate(
            'preparse-field/_components/fields/_input',
            [
                'name' => $this->handle,
                'value' => $value,
                'field' => $this,
                'id' => $id,
                'namespacedId' => $namespacedId,
                'displayType' => $displayType,
            ]
        );
    }
}
