<?php namespace Milkyway\SS\ExternalNewsletter\Forms;
/**
 * Milkyway Multimedia
 * SubscribeFormField.php
 *
 * @package relatewell.org.au
 * @author Mellisa Hankins <mell@milkywaymultimedia.com.au>
 */

class SubscribeFormField extends \CompositeField {
    private static $allowed_actions = [
        'subscribe',
        'SubscribeForm',
    ];

    public $lists;

    public $subscribeFormFields;

    public $subscribeFormCallback;

    public $doNotOverrideChildren = false;

    public $allowMultiple = true;
    public $allowSelectable = false;

    public $hidden = true;
    public $includeHiddenField = false;

    public $listClass = 'ExtList';

    public function __construct($name, $title = '', $lists = null, $value = null, $subscribeFormFields = null) {
        \FormField::__construct($name, $title, $value);
        $this->lists = $lists;
        $this->useProperChildren();
    }

    public function setLists($lists) {
        $this->lists = $lists;
        $this->useProperChildren();
        return $this;
    }

    public function FieldList() {
        $this->useProperChildren();
        return parent::FieldList();
    }

    public function getLists() {
        return $this->lists;
    }

    public function setSubscribeFormFields($subscribeFormFields) {
        $this->subscribeFormFields = $subscribeFormFields;
        return $this;
    }

    public function getSubscribeFormFields() {
        return $this->subscribeFormFields;
    }

    public function setSubscribeFormCallback($subscribeFormCallback) {
        $this->subscribeFormCallback = $subscribeFormCallback;
        return $this;
    }

    public function getSubscribeFormCallback() {
        return $this->subscribeFormCallback;
    }

    public function hasData() {
        return $this->useListsAsValue();
    }

    public function dataValue() {
        $this->convertListsToArray();

        if($this->useListsAsValue())
            return \ArrayLib::is_associative((array)$this->lists) ? array_keys($this->lists) : (array)$this->lists;

        return parent::dataValue();
    }

    public function saveInto(DataObjectInterface $record) {
        $fieldname = $this->name;
        $relation = ($fieldname && $record && $record->hasMethod($fieldname)) ? $record->$fieldname() : null;
        if($fieldname && $record && $relation &&
           ($relation instanceof RelationList || $relation instanceof UnsavedRelationList)) {
            $idList = array();
            if($this->value) foreach($this->value as $id => $bool) {
                if($bool) {
                    $idList[] = $id;
                }
            }
            $relation->setByIDList($idList);
        } elseif($fieldname && $record) {
            if($this->value) {
                $this->value = str_replace(',', '{comma}', $this->value);
                $record->$fieldname = implode(',', (array) $this->value);
            } else {
                $record->$fieldname = '';
            }
        }
    }

    public function Type() {
        return basename(str_replace('\\', DIRECTORY_SEPARATOR, parent::Type()));
    }

    protected function convertListsToArray() {
        if($this->lists && !is_array($this->lists)) {
            if (is_array($this->lists) && !\ArrayLib::is_associative($this->lists)) {
                $this->lists = \DataList::create($this->listClass)->filter('ID', $this->lists);
            }

            if ($this->lists instanceof \SS_List) {
                $this->lists = $this->lists->map()->toArray();
            }
        }
    }

    protected function useProperChildren() {
        $this->convertListsToArray();

        if(!$this->doNotOverrideChildren && $this->lists) {
            if($this->hidden && $this->includeHiddenField) {
                $lists = \ArrayLib::is_associative((array)$this->lists) ? array_keys($this->lists) : (array)$this->lists;
                $this->children = \FieldList::create(\HiddenField::create($this->name, '', implode(',', $lists))->setForm($this->form));
            }
            elseif(!$this->hidden && is_array($this->lists)) {
                if ($this->allowMultiple) {
                    $this->children = \FieldList::create(
                        \CheckboxSetField::create(
                            $this->name,
                            $this->title,
                            $this->lists,
                            $this->value
                        )->setForm($this->form)
                    );
                }
                elseif($this->allowSelectable) {
                    $this->children = \FieldList::create(
                        \OptionsetField::create(
                            $this->name,
                            $this->title,
                            $this->lists,
                            $this->value
                        )->setForm($this->form)
                    );
                }
                else {
                    $this->children = \FieldList::create(
                        \CheckboxField::create(
                            $this->name,
                            $this->title,
                            $this->value
                        )->setForm($this->form)
                    );
                }
            }
            elseif(!$this->hidden && $this->lists) {
                $this->children = \FieldList::create(
                    \CheckboxField::create(
                        $this->name,
                        $this->title,
                        $this->value
                    )->setForm($this->form)
                );
            }
        }

        if(!$this->children)
            $this->children = \FieldList::create();

        return $this;
    }

    protected function useListsAsValue() {
        return $this->hidden || (!$this->hidden && ((is_array($this->lists) && !$this->allowMultiple && !$this->allowSelectable) || !is_array($this->lists)));
    }
} 