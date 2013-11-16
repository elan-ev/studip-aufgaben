<?php
/**
 * SimpleORMapCollection.class.php
 * simple object-relational mapping
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      André Noack <noack@data-quest.de>
 * @copyright   2012 Stud.IP Core-Group
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
*/
class EPP_SimpleORMapCollection extends EPP_SimpleCollection
{

    /**
     * the record object this collection belongs to
     *
     * @var SimpleORMap
     */
    protected $related_record;

    /**
     * relation options
     * @var array
     */
    protected $relation_options = array();

    /**
     * creates a collection from an array of objects
     * all objects should be of the same type
     *
     * @throws InvalidArgumentException if first entry is not SimpleOrMap
     * @param array $data array with SimpleORMap objects
     * @param bool $strict check every element for correct type and unique pk
     * @return SimpleORMapCollection
     */
    public static function createFromArray(Array $data, $strict = true)
    {
        $ret = new EPP_SimpleORMapCollection();
        if (count($data)) {
            $first = current($data);
            if ($first instanceof EPP_SimpleORMap) {
                $ret->setClassName(get_class($first));
                if ($strict) {
                    foreach ($data as $one) {
                        $ret[] = $one;
                    }
                } else {
                    $ret->exchangeArray($data);
                }
            } else {
                throw new InvalidArgumentException('This collection only accepts objects derived from SimpleORMap');
            }
        }
        return $ret;
    }

    /**
     * Constructor
     *
     * @param Closure $finder callable to fill collection
     * @param array $options relationship options
     * @param SimpleORMap $record related record
     */
    function __construct(Closure $finder = null, Array $options = null, EPP_SimpleORMap $record = null)
    {
        $this->relation_options = $options;
        $this->related_record = $record;
        parent::__construct($finder === null ? array() : $finder);
    }

    /**
     * Sets the value at the specified index
     * checks if the value is an object of specified class
     *
     * @see ArrayObject::offsetSet()
     * @throws InvalidArgumentException if the given model does not fit (wrong type or id)
     */
    function offsetSet($index, $newval)
    {
        if (!is_null($index)) {
            $index = (int)$index;
        }
        if (strtolower(get_class($newval)) !== $this->getClassName()) {
            throw new InvalidArgumentException('This collection only accepts objects of type: ' .  $this->getClassName());
        }
        if ($this->related_record && $this->relation_options['type'] === 'has_many') {
            $foreign_key_value = call_user_func($this->relation_options['assoc_func_params_func'], $this->related_record);
            call_user_func($this->relation_options['assoc_foreign_key_setter'], $newval, $foreign_key_value);
        }
        if ($newval->id !== null) {
            $exists = $this->find($newval->id);
            if ($exists) {
                throw new InvalidArgumentException('Element could not be appended, element with id: ' . $exists->id . ' is in the way');
            }
        }
        return parent::offsetSet($index, $newval);
    }

    /**
     * sets the allowed class name
     * @param string $class_name
     */
    function setClassName($class_name)
    {
        $this->relation_options['class_name'] = strtolower($class_name);
        $this->deleted->relation_options['class_name'] = strtolower($class_name);
    }

    /**
     * sets the related record
     *
     * @param SimpleORMap $record
     */
    function setRelatedRecord(EPP_SimpleORMap $record)
    {
        $this->related_record = $record;
    }

    /**
     * gets the allowed classname
     *
     * @return string
     */
    function getClassName()
    {
        return strtolower($this->relation_options['class_name']);
    }

    /**
     * reloads the elements of the collection
     * by calling the finder function
     *
     * @throws Exception
     * @return number of records after refresh
     */
    function refresh()
    {
        if (is_callable($this->finder)) {
            $data = call_user_func($this->finder, $this->related_record);
            foreach ($data as $one) {
                if (strtolower(get_class($one)) !== $this->getClassName()) {
                    throw new Exception('This collection only accepts objects of type: ' .  $this->getClassName());
                }
            }
            $this->exchangeArray($data);
            $this->deleted->exchangeArray(array());
            return $this->last_count = $this->count();
        }
    }

    /**
     * calls the given method on all elements
     * of the collection
     * @param string $method methodname to call
     * @param array $params parameters for methodcall
     * @return array of all return values
     */
    function sendMessage($method, $params = array()) {
        $results = array();
        foreach ($this as $record) {
            $results[] = call_user_func_array(array($record, $method), $params);
        }
        return $results;
    }

    /**
     * returns element with given primary key value
     *
     * @param string $value primary key value to search for
     * @return SimpleORMap
     */
    function find($value)
    {
        return $this->findOneBy('id', $value);
    }

    /**
     * returns the collection as grouped array
     * first param is the column to group by, it becomes the key in
     * the resulting array, default is pk. Limit returned fields with second param
     * The grouped entries can optoionally go through the given
     * callback. If no callback is provided, only the first grouped
     * entry is returned, suitable for grouping by unique column
     *
     * @param string $group_by the column to group by, pk if ommitted
     * @param mixed $only_these_fields limit returned fields
     * @param Closure $group_func closure to aggregate grouped entries
     * @return array assoc array
     */
    function toGroupedArray($group_by = 'id', $only_these_fields = null, Closure $group_func = null)
    {
        $result = array();
        foreach ($this as $record) {
            $key = $record->getValue($group_by);
            if (is_array($key)) {
                $key = join('_', $key);
            }
            $result[$key][] = $record->toArray($only_these_fields);
        }
        if ($group_func === null) {
            $group_func = 'current';
        }
        return array_map($group_func, $result);
    }

    /**
     * mark element(s) for deletion
     * element(s) with given primary key are moved to
     * internal deleted collection
     *
     * @param string $id primary key of element
     * @return  number of unsetted elements
     */
    function unsetByPk($id)
    {
        return $this->unsetBy('id', $id);
    }
}
