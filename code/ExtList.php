<?php
use Milkyway\SS\ExternalNewsletter\Utilities;

/**
 * Milkyway Multimedia
 * ExtList.php
 *
 * @package reggardocolaianni.com
 * @author  Mellisa Hankins <mell@milkywaymultimedia.com.au>
 */

class ExtList extends DataObject
{
	private static $singular_name = 'Mailing List';

    private static $db = [
        'Title' => 'Varchar',
    ];

    private static $extensions = [
        "Milkyway\\SS\\ExternalNewsletter\\Extensions\\Lists",
    ];

	public static function find_or_make($filter = [], $data = [])
	{
		singleton(get_called_class())->findOrMake($filter, $data);
	}

	public function requireDefaultRecords()
	{
		parent::requireDefaultRecords();

//        if(!Utilities::env_value('NoListSync') && !isset($_GET['forceExternalSync']))
//		    $this->sync(!isset($_GET['keepExisting']));
	}

    /**
     * Scaffold a simple edit form for all properties on this dataobject,
     * based on default {@link FormField} mapping in {@link DBField::scaffoldFormField()}.
     * Field labels/titles will be auto generated from {@link DataObject::fieldLabels()}.
     *
     * @uses FormScaffolder
     *
     * @param array $_params Associative array passing through properties to {@link FormScaffolder}.
     * @return FieldList
     */
    public function scaffoldFormFields($_params = null) {
        $params = array_merge(
            array(
                'tabbed' => false,
                'includeRelations' => false,
                'restrictFields' => false,
                'fieldClasses' => false,
                'ajaxSafe' => false
            ),
            (array)$_params
        );

        $fs = new FormScaffolder($this);
        $fs->tabbed = $params['tabbed'];
        $fs->includeRelations = $params['includeRelations'];
        $fs->restrictFields = $params['restrictFields'];
        $fs->fieldClasses = $params['fieldClasses'];
        $fs->ajaxSafe = $params['ajaxSafe'];

        $includeRelations = $fs->includeRelations;

        if($fs->includeRelations && is_array($fs->includeRelations) && isset($fs->includeRelations['many_many'])) {
            unset($fs->includeRelations['many_many']);
        }
        elseif($fs->includeRelations) {
            $fs->includeRelations = [
                'has_many' => true,
            ];
        }

        $fields = $fs->getFieldList();

        if($this->many_many()
           && ($includeRelations === true || isset($includeRelations['many_many']))) {

            foreach($this->many_many() as $relationship => $component) {
	            $label = $relationship == 'ExtSubscriber' ? 'AllEmails' : $relationship;

                if($fs->tabbed) {
                    $relationTab = $fields->findOrMakeTab(
                        "Root.$label",
                        $this->fieldLabel($label)
                    );
                }

                $fieldClass = (isset($fs->fieldClasses[$relationship]))
                    ? $fs->fieldClasses[$relationship]
                    : 'GridField';

                $grid = Object::create($fieldClass,
                    $relationship,
	                $this->fieldLabel($label),
                    $this->getManyManyComponents($relationship),
                    GridFieldConfig_RelationEditor::create()
                );

                if($fs->tabbed) {
                    $fields->addFieldToTab("Root.$label", $grid);
                } else {
                    $fields->push($grid);
                }
            }
        }

        return $fields;
    }
} 