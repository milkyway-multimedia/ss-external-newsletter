<?php namespace Milkyway\SS\ExternalNewsletter\Extensions;

use ManyManyList as SilverStripeManyManyList;

class ManyManyList extends SilverStripeManyManyList
{
    /**
     * @inheritdoc
     */
    public function add($item, $extraFields = null)
    {
        parent::add($item, $extraFields);

        if ($item instanceof \Object) {
            $item->extend('onAfterManyManyRelationAdd', $this, $extraFields);
        }
    }

    /**
     * @inheritdoc
     */
    public function removeByID($itemID)
    {
        $result = parent::removeByID($itemID);

        $item = \DataList::create($this->dataClass)->byId($itemID);

        if ($item instanceof \Object) {
            $item->extend('onAfterManyManyRelationRemove', $this);
        }

        return $result;
    }
}