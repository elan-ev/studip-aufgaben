<?php
/**
 * SimpleORMap.class.php
 * simple object-relational mapping
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      André Noack <noack@data-quest.de>
 * @copyright   2010 Stud.IP Core-Group
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
*/

class EPP_SimpleORMap implements ArrayAccess, Countable, IteratorAggregate
{
    /**
     * table row data
     * @var array $content
     */
    protected $content = array();
    /**
     * table row data
     * @var array $content_db
     */
    protected $content_db = array();
    /**
     * new state of entry
     * @var boolean $is_new
     */
    protected $is_new = true;

    /**
     * name of db table
     * @var string $db_table
     */
    protected $db_table = '';
    /**
     * table columns
     * @var array $db_fields
     */
    protected $db_fields = null;
    /**
     * primary key columns
     * @var array $pk
     */
    protected $pk = null;

    /**
     * default values for columns
     * @var array $default_values
     */
    protected $default_values = array();

     /**
     * db table metadata
     * @var array $schemes;


     */
    protected static $schemes;

    /**
     * aliases for columns
     * alias => column
     * @var array $alias_fields
     */
    protected $alias_fields = array();

    /**
     * additional computed fields
     * name => callable
     * @var array $additional_fields
     */
    protected $additional_fields = array();

    /**
     * stores instantiated related objects
     * @var array $relations
     */
    protected $relations = array();

    /**
     * 1:n relation
     * @var array $has_many
     */
    protected $has_many = array();

    /**
     * 1:1 relation
     * @var array $has_one
     */
    protected $has_one = array();

    /**
     * n:1 relations
     * @var array $belongs_to
     */
    protected $belongs_to = array();

    /**
     * n:m relations
     * @var array $has_and_belongs_to_many
     */
    protected $has_and_belongs_to_many = array();

    /**
     * callbacks
     * @var array $registered_callbacks
     */
    protected $registered_callbacks = array('before_create' => array(),
                                              'before_update' => array(),
                                              'before_store' => array(),
                                              'before_delete' => array(),
                                              'after_create' => array(),
                                              'after_update' => array(),
                                              'after_store' => array(),
                                              'after_delete' => array(),
                                              'after_initialize' => array());

    /**
     * contains an array of all used identifiers for fields
     * (db columns + aliased columns + additional columns + relations)
     * @var array $known_slots
     */
    protected $known_slots = array();

    /**
     * reserved indentifiers, fields with those names must not have an explicit getXXX() method
     * @var array $reserved_slots
     */
    protected $reserved_slots = array('value','newid','iterator','tablemetadata', 'relationvalue','wherequery','relationoptions','data','new','id');

    /**
     * fetch table metadata from db or from local cache
     *
     * @param string $db_table
     * @return bool true if metadata could be fetched
     */
    protected static function tableScheme($db_table)
    {
        if (self::$schemes === null) {
            $cache = StudipCacheFactory::getCache();
            self::$schemes = unserialize($cache->read('DB_TABLE_SCHEMES'));
        }
        if (!isset(self::$schemes[$db_table])) {
            $db = DBManager::get()->query("SHOW COLUMNS FROM $db_table");
            while($rs = $db->fetch(PDO::FETCH_ASSOC)){
                $db_fields[strtolower($rs['Field'])] = array(
                                                            'name' => $rs['Field'],
                                                            'null' => $rs['Null'],
                                                            'default' => $rs['Default'],
                                                            'extra' => $rs['Extra']
                                                            );
                if ($rs['Key'] == 'PRI'){
                    $pk[] = strtolower($rs['Field']);
                }
            }
            self::$schemes[$db_table]['db_fields'] = $db_fields;
            self::$schemes[$db_table]['pk'] = $pk;
            $cache = StudipCacheFactory::getCache();
            $cache->write('DB_TABLE_SCHEMES', serialize(self::$schemes));
        }
        return isset(self::$schemes[$db_table]);
    }

    /**
     * force reload of cached table metadata
     */
    public static function expireTableScheme()
    {
        StudipCacheFactory::getCache()->expire('DB_TABLE_SCHEMES');
        self::$schemes = null;
    }

    /**
     * returns new instance for given key
     * when found in db, else null
     * @param string primary key
     * @return SimpleORMap|NULL
     */
    public static function find($id)
    {
        $class = get_called_class();
        $ref = new ReflectionClass($class);
        $record = $ref->newInstanceArgs(func_get_args());
        if (!$record->isNew()) {
            return $record;
        } else {
            return null;
        }
    }

    /**
     * returns true if given key
     * exists in db
     * @param string primary key
     * @return boolean
     */
    public static function exists($id)
    {
        $ret = false;
        $class = get_called_class();
        $record = new $class();
        $record->setId(func_get_args());
        $where_query = $record->getWhereQuery();
        if ($where_query) {
            $query = "SELECT 1 FROM `{$record->db_table}` WHERE "
                    . join(" AND ", $where_query);
            $ret = (bool)DBManager::get()->query($query)->fetchColumn();
        }
        return $ret;
    }

    /**
     * returns number of records
     *
     * @param string sql clause to use on the right side of WHERE
     * @param array params for query
     * @return number
     */
    public static function countBySql($where = 1, $params = array())
    {
        $class = get_called_class();
        $record = new $class();
        $db = DBManager::get();
        $sql = "SELECT count(*) FROM `" .  $record->db_table . "` WHERE " . $where;
        $st = $db->prepare($sql);
        $st->execute($params);
        return $st->fetchColumn();
    }

    /**
     * creates new record with given data in db
     * returns the new object or null
     * @param array assoc array of record
     * @return SimpleORMap
     */
    public static function create($data)
    {
        $class = get_called_class();
        $record = new $class();
        $record->setData($data, true);
        if ($record->store()) {
            return $record;
        } else {
            return null;
        }
    }

    /**
     * generate SimpleORMap object structure from assoc array
     * if given array contains data of related objects in sub-arrays
     * they are also generated. Existing records are updated, new records are created
     * (but changes are not yet stored)
     *
     * @param array $data
     * @return SimpleORMap
     */
    public static function import($data)
    {
        $class = get_called_class();
        $record_data = array();
        $relation_data = array();
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $relation_data[$key] = $value;
            } else {
                $record_data[$key] = $value;
            }
        }
        $record = static::toObject($record_data);
        if (!$record instanceof $class) {
            $record = new $class();
            $record->setData($record_data, true);
        } else {
            $record->setData($record_data);
        }
        if (is_array($relation_data)) {
            foreach ($relation_data as $relation => $data) {
                $options = $record->getRelationOptions($relation);
                if ($options['type'] == 'has_one') {
                    $record->{$relation} = call_user_func(array($options['class_name'], 'import'), $data);
                }
                if ($options['type'] == 'has_many' || $options['type'] == 'has_and_belongs_to_many') {
                    foreach ($data as $one) {
                        $current = call_user_func(array($options['class_name'], 'import'), $one);
                        if ($options['type'] == 'has_many') {
                            $foreign_key_value = call_user_func($options['assoc_func_params_func'], $record);
                            call_user_func($options['assoc_foreign_key_setter'], $current, $foreign_key_value);
                        }
                        if ($current->id !== null) {
                            $existing = $record->{$relation}->find($current->id);
                            if ($existing) {
                                $existing->setData($current);
                            } else {
                                $record->{$relation}->append($current);
                            }
                        } else {
                            $record->{$relation}->append($current);
                        }
                    }
                }
            }
        }
        return $record;
    }

    /**
     * returns array of instances of given class filtered by given sql
     * @param string sql clause to use on the right side of WHERE
     * @param array parameters for query
     * @return array array of "self" objects
     */
    public static function findBySQL($where, $params = array())
    {
        $class = get_called_class();
        $record = new $class();
        $db = DBManager::get();
        $sql = "SELECT * FROM `" .  $record->db_table . "` WHERE " . $where;
        $st = $db->prepare($sql);
        $st->execute($params);
        $ret = array();
        $c = 0;
        while($row = $st->fetch(PDO::FETCH_ASSOC)) {
            $ret[$c] = new $class();
            $ret[$c]->setData($row, true);
            $ret[$c]->setNew(false);
            ++$c;
        }
        return $ret;
    }

    /**
     * find related records for a n:m relation (has_many_and_belongs_to_many)
     * using a combination table holding the keys
     *
     * @param string value of foreign key to find related records
     * @param array relation options from other side of relation
     * @return array of "self" objects
     */
    public static function findThru($foreign_key_value, $options)
    {
        $thru_table = $options['thru_table'];
        $thru_key = $options['thru_key'];
        $thru_assoc_key = $options['thru_assoc_key'];
        $assoc_foreign_key = $options['assoc_foreign_key'];

        $class = get_called_class();
        $record = new $class();
        $sql = "SELECT `{$record->db_table}`.* FROM `$thru_table`
        INNER JOIN `{$record->db_table}` ON `$thru_table`.`$thru_assoc_key` = `{$record->db_table}`.`$assoc_foreign_key`
        WHERE `$thru_table`.`$thru_key` = ?";
        $db = DBManager::get();
        $st = $db->prepare($sql);
        $st->execute(array($foreign_key_value));
        $ret = array();
        $c = 0;
        while($row = $st->fetch(PDO::FETCH_ASSOC)) {
            $ret[$c] = new $class();
            $ret[$c]->setData($row, true);
            $ret[$c]->setNew(false);
            ++$c;
        }
        return $ret;
    }

    /**
     * passes objects for given sql through given callback
     *
     * @param callable $callable callback which gets the current record as param
     * @param string where clause of sql
     * @param array sql statement parameters
     * @return integer number of found records
     */
    public static function findEachBySQL($callable, $where, $params = array())
    {
        $class = get_called_class();
        $record = new $class();
        $db = DBManager::get();
        $sql = "SELECT * FROM `" .  $record->db_table . "` WHERE " . $where;
        $st = $db->prepare($sql);
        $st->execute($params);
        $ret = 0;
        while ($row = $st->fetch(PDO::FETCH_ASSOC)) {
            $current = new $class();
            $current->setData($row, true);
            $current->setNew(false);
            $callable($current);
            ++$ret;
        }
        return $ret;
    }

    /**
     * returns array of instances of given class for by given pks
     * @param array array og primary keys
     * @param string order by clause
     * @return array
     */
    public static function findMany($pks = array(), $order = '', $order_params = array())
    {
        $class = get_called_class();
        $record = new $class();
        $db = DBManager::get();
        if (count($record->pk) > 1) {
            throw new Exception('not implemented yet');
        }
        $where = "`{$record->db_table}`.`{$record->pk[0]}` IN ("  . $db->quote($pks) . ") ";
        return self::findBySQL($where . $order, $order_params);
    }

    /**
     * passes objects for by given pks through given callback
     *
     * @param callable $callable callback which gets the current record as param
     * @param array $pks array of primary keys of called class
     * @param string $order order by sql
     * @return integer number of found records
     */
    public static function findEachMany($callable, $pks = array(), $order = '', $order_params = array())
    {
        $class = get_called_class();
        $record = new $class();
        $db = DBManager::get();
        if (count($record->pk) > 1) {
            throw new Exception('not implemented yet');
        }
        $where = "`{$record->db_table}`.`{$record->pk[0]}` IN ("  . $db->quote($pks) . ") ";
        return self::findEachBySQL($callable, $where . $order, $order_params);
    }

    /**
     * passes objects for given sql through given callback
     * and returns an array of callback return values
     *
     * @param callable $callable callback which gets the current record as param
     * @param string where clause of sql
     * @param array sql statement parameters
     * @return array return values of callback
     */
    public static function findAndMapBySQL($callable, $where, $params = array())
    {
        $ret = array();
        $calleach = function($m) use (&$ret, $callable) {
            $ret[] = $callable($m);
        };
        self::findEachBySQL($calleach, $where, $params);
        return $ret;
    }

    /**
     * passes objects for by given pks through given callback
     * and returns an array of callback return values
     *
     * @param callable $callable callback which gets the current record as param
     * @param array $pks array of primary keys of called class
     * @param string $order order by sql
     * @return array return values of callback
     */
    public static function findAndMapMany($callable, $pks = array(), $order = '', $order_params = array())
    {
        $ret = array();
        $calleach = function($m) use (&$ret, $callable) {
            $ret[] = $callable($m);
        };
        self::findEachBySQL($calleach, $order, $params);
        return $ret;
    }

    /**
     * deletes table rows specified by given class and sql clause
     * @param string sql clause to use on the right side of WHERE
     * @param array parameters for query
     * @return number
     */
    public static function deleteBySQL($where, $params = array())
    {
        $class = get_called_class();
        $record = new $class();
        $db = DBManager::get();
        $sql = "DELETE FROM `" .  $record->db_table . "` WHERE " . $where;
        $st = $db->prepare($sql);
        $st->execute($params);
        return $st->rowCount();
    }

    /**
     * returns object of given class for given id or null
     * the param could be a string, an assoc array containing primary key field
     * or an already matching object. In all these cases an object is returned
     *
     * @param mixed id as string, object or assoc array
     * @return NULL|object
     */
    public static function toObject($id_or_object)
    {
        $class = get_called_class();
        if ($id_or_object instanceof $class) {
            return $id_or_object;
        }
        if (is_array($id_or_object)) {
            $object = new $class();
            list( ,$pk) = array_values($object->getTableMetadata());
            $key_values = array();
            foreach($pk as $key) {
                if (array_key_exists($key, $id_or_object)) {
                    $key_values[] = $id_or_object[$key];
                }
            }
            if (count($pk) === count($key_values)) {
                if (count($pk) === 1) {
                    $id = $key_values[0];
                } else {
                    $id = $key_values;
                }
            }
        } else {
            $id = $id_or_object;
        }
        return call_user_func(array($class, 'find'), $id);
    }

    /**
     * interceptor for static findByColumn / findEachByColumn
     * magic
     * @param string $name
     * @param array $arguments
     * @throws BadMethodCallException
     * @return mixed
     */
    public static function __callStatic($name, $arguments)
    {
        $name = strtolower($name);
        $class = get_called_class();
        $record = new $class();
        $param_arr = array();
        $where = '';
        if (strpos($name, 'findby') === 0) {
            $field = substr($name, 6);
            $order = $arguments[1];
            $param_arr[0] =& $where;
            $param_arr[1] = array($arguments[0]);
            $find = 'findbysql';
        }
        if (strpos($name, 'findeachby') === 0) {
            $field = substr($name, 10);
            $order = $arguments[2];
            $param_arr[0] = $arguments[0];
            $param_arr[1] =& $where;
            $param_arr[2] = array($arguments[1]);
            $find = 'findeachbysql';
        }
        if (isset($record->alias_fields[$field])) {
            $field = $record->alias_fields[$field];
        }
        if (isset($record->db_fields[$field])) {
            $where = "`{$record->db_table}`.`$field` = ? " . $order;
            return call_user_func_array(array($class, $find), $param_arr);
        }
        throw new BadMethodCallException("Method $class::$name not found");
    }

    /**
     * constructor, give primary key of record as param to fetch
     * corresponding record from db if available, if not preset primary key
     * with given value. Give null to create new record
     *
     * @param mixed $id primary key of table
     */
    function __construct($id = null)
    {
        if (!$this->db_table) {
            $this->db_table = strtolower(get_class($this));
        }
        if (!$this->db_fields) {
            $this->getTableScheme();
        }
        if (!isset($this->db_fields['id'])
            && !isset($this->alias_fields['id'])
            && !isset($this->additional_fields['id'])) {
            if (count($this->pk) === 1) {
                $this->alias_fields['id'] = $this->pk[0];
            } else {
                $this->additional_fields['id'] = array('get' => function($r,$f) {return is_null($r->getId()) ? null : join('_',$r->getId());},
                                                        'set' => function($r,$f,$v) {return $r->setId(explode('_', $v));});
            }
        }
        foreach(array('has_many', 'belongs_to', 'has_one', 'has_and_belongs_to_many') as $type) {
            foreach (array_keys($this->{$type}) as $one) {
                $this->relations[$one] = null;
            }
        }
        if ($this->hasAutoIncrementColumn()) {
            $this->registerCallback('before_store after_create', 'cbAutoIncrementColumn');
        } elseif (count($this->pk) === 1) {
            $this->registerCallback('before_store', 'cbAutoKeyCreation');
        }

        $this->known_slots = array_merge(array_keys($this->db_fields), array_keys($this->alias_fields), array_keys($this->additional_fields), array_keys($this->relations));

        if ($id) {
            $this->setId($id);
        }
        $this->restore();
    }

    /**
     * clean up references after cloning
     *
     *
     */
    function __clone()
    {
        //all references link still to old object => reset all aliases
        foreach ($this->alias_fields as $alias => $field) {
            if (isset($this->db_fields[$field])) {
                $content_value = $this->content[$field];
                $content_db_value = $this->content_db[$field];
                unset($this->content[$alias]);
                unset($this->content_db[$alias]);
                unset($this->content[$field]);
                unset($this->content_db[$field]);
                $this->content[$field] = $content_value;
                $this->content_db[$field] = $content_db_value;
                $this->content[$alias] =& $this->content[$field];
                $this->content_db[$alias] =& $this->content_db[$field];
            }
        }
        //unset all relations for now
        //TODO: maybe a deep copy of all belonging objects is more appropriate
        foreach(array('has_many', 'belongs_to', 'has_one', 'has_and_belongs_to_many') as $type) {
            foreach (array_keys($this->{$type}) as $one) {
                $this->relations[$one] = null;
            }
        }
        //begun the clone war has... hmpf
    }

    /**
     * try to determine all needed options for a relationship from
     * configured options
     *
     * @param string $type
     * @param string $name
     * @param array $options
     * @throws Exception if options for thru_table could not be determined
     * @return array
     */
    protected function parseRelationOptions($type, $name, $options) {
        if (!$options['class_name']) {
            throw new Exception('Option class_name not set for relation ' . $name);
        }
        if (!$options['assoc_foreign_key']) {
            if ($type === 'has_many' || $type === 'has_one') {
                $options['assoc_foreign_key'] = $this->pk[0];
            } else if ($type === 'belongs_to') {
                $options['assoc_foreign_key'] = 'id';
            }
        }
        if ($type === 'has_and_belongs_to_many') {
            $thru_table = $options['thru_table'];
            if (!$options['thru_key']) {
                $options['thru_key'] = $this->pk[0];
            }
            if (!$options['thru_assoc_key'] || !$options['assoc_foreign_key']) {
                $class = $options['class_name'];
                $record = new $class();
                $meta = $record->getTableMetadata();
                if (!$options['thru_assoc_key'] ) {
                    $options['thru_assoc_key'] = $meta['pk'][0];
                }
                if (!$options['assoc_foreign_key']) {
                    $options['assoc_foreign_key']= $meta['pk'][0];
                }
            }
            self::TableScheme($thru_table);
            if (is_array(self::$schemes[$thru_table])) {
                $thru_key_ok = isset(self::$schemes[$thru_table]['db_fields'][$options['thru_key']]);
                $thru_assoc_key_ok = isset(self::$schemes[$thru_table]['db_fields'][$options['thru_assoc_key']]);
            }
            if (!$thru_assoc_key_ok || !$thru_key_ok) {
                throw new Exception("Could not determine keys for relation " . $name . " through table " . $thru_table);
            }
            if ($options['assoc_foreign_key'] instanceof Closure) {
                throw new Exception("For relation " . $name . " assoc_foreign_key must be a name of a column");
            }
        }
        if (!$options['assoc_func']) {
            if ($type !== 'has_and_belongs_to_many') {
                $options['assoc_func'] = $options['assoc_foreign_key'] === 'id' ? 'find' : 'findBy' . $options['assoc_foreign_key'];
            } else {
                $options['assoc_func'] = 'findThru';
            }
        }
        if (!$options['foreign_key']) {
            $options['foreign_key'] = 'id';
        }
        if ($options['foreign_key'] instanceof Closure) {
            $options['assoc_func_params_func'] = function($record) use ($name, $options) { return call_user_func($options['foreign_key'], $record, $name, $options);};
        } else {
            $options['assoc_func_params_func'] = function($record) use ($name, $options) { return $options['foreign_key'] === 'id' ? $record->getId() : $record->getValue($options['foreign_key']);};
        }
        $that = $this;
        if ($options['assoc_foreign_key'] instanceof Closure) {
            if ($type === 'belongs_to') {
                $options['assoc_foreign_key_getter'] = function($record) use ($name, $options, $that) { return call_user_func($options['assoc_foreign_key'], $record, $name, $options, $that);};
            } else {
                $options['assoc_foreign_key_setter'] = function($record, $params) use ($name, $options) { return call_user_func($options['assoc_foreign_key'], $record, $params, $name, $options);};
            }
        } else {
            if ($type === 'belongs_to') {
                $options['assoc_foreign_key_getter'] = function($record) use ($name, $options, $that) { return $record->getValue($options['assoc_foreign_key']);};
            } else {
                $options['assoc_foreign_key_setter'] = function($record, $value) use ($name, $options) { return $record->setValue($options['assoc_foreign_key'], $value);};
            }
        }
        return $options;
    }

    /**
     * returns array with option for given relation
     * available options:
     * 'type':                   relation type, on of 'has_many', 'belongs_to', 'has_one', 'has_and_belongs_to_many'
     * 'class_name':             name of class for related records
     * 'foreign_key':            name of column with foreign key
     *                           or callback to retrieve foreign key value
     * 'assoc_foreign_key':      name of foreign key column in related class
     * 'assoc_func':             name of static method to call on related class to find related records
     * 'assoc_func_params_func': callback to retrieve params for assoc_func
     * 'thru_table':             name of relation table for n:m relation
     * 'thru_key':               name of column holding foreign key in relation table
     * 'thru_assoc_key':         name of column holding foreign key from related class in relation table
     * 'on_delete':              contains simply 'delete' to indicate that related records should be deleted
     *                           or callback to invoke before record gets deleted
     * 'on_store':               contains simply 'store' to indicate that related records should be stored
     *                           or callback to invoke after record gets stored
     *
     * @param string $relation name of relation
     * @return array assoc array containing options
     */
    function getRelationOptions($relation)
    {
        $options = array();
        foreach(array('has_many', 'belongs_to', 'has_one', 'has_and_belongs_to_many') as $type) {
            if (isset($this->{$type}[$relation])) {
                $options = $this->{$type}[$relation];
                if (!isset($options['type'])) {
                    $options = $this->parseRelationOptions($type, $relation, $options);
                    $options['type'] = $type;
                    $this->{$type}[$relation] = $options;
                }
                break;
            }
        }
        return $options;
    }

    /**
     * restore table metadata from db or cache
     */
    protected function getTableScheme()
    {
        if(self::TableScheme($this->db_table)) {
            $this->db_fields =& self::$schemes[$this->db_table]['db_fields'];
            $this->pk =& self::$schemes[$this->db_table]['pk'];
            foreach ($this->db_fields as $field => $meta) {
                if (!isset($this->default_values[$field])) {
                    $this->default_values[$field] = $meta['default'];
                }
            }
        }
    }

    /**
     * returns table and columns metadata
     *
     * @return array assoc array with columns, primary keys and name of table
     */
    function getTableMetadata()
    {
        return array('fields' => $this->db_fields,
                     'pk' => $this->pk,
                     'table' => $this->db_table,
                     'additional_fields' => $this->additional_fields,
                     'alias_fields' => $this->alias_fields,
                     'relations' => array_keys($this->relations));
    }

    /**
     * returns true, if table has an auto_increment column
     *
     * @return boolean
     */
    function hasAutoIncrementColumn()
    {
        return $this->db_fields[$this->pk[0]]['extra'] == 'auto_increment';
    }

    /**
     * set primary key for entry, combined keys must be passed as array
     * @param string|array primary key
     * @throws InvalidArgumentException if given key is not complete
     * @return boolean
     */
    function setId($id)
    {
        if (!is_array($id)){
            $id = array($id);
        }
        if (count($this->pk) != count($id)){
            throw new InvalidArgumentException("Invalid ID, Primary Key {$this->db_table} is " .join(",",$this->pk));
        } else {
            foreach ($this->pk as $count => $key){
                $this->content[$key] = $id[$count];
            }
            return true;
        }
        return false;
    }

    /**
     * returns primary key, multiple keys as array
     * @return string|array current primary key, null if not set
     */
    function getId()
    {
        if (count($this->pk) == 1) {
            return $this->content[$this->pk[0]];
        } else {
            foreach ($this->pk as $key) {
                if ($this->content[$key] !== null) {
                    $id[] = $this->content[$key];
                }
            }
            return (count($this->pk) == count($id) ? $id : null);
        }
    }

    /**
     * create new unique pk as md5 hash
     * if pk consists of multiple columns, false is returned
     * @return boolean|string
     */
    function getNewId()
    {
        $id = false;
        if (count($this->pk) == 1) {
            do {
                $id = md5(uniqid($this->db_table,1));
                $db = DBManager::get()->query("SELECT `{$this->pk[0]}` FROM `{$this->db_table}` "
                . "WHERE `{$this->pk[0]}` = '$id'");
            } while($db->fetch());
        }
        return $id;
    }

    /**
     * returns data of table row as assoc array
     * pass array of fieldnames or ws separated string to limit
     * fields
     *
     * @param mixed $only_these_fields limit returned fields
     * @return array
     */
    function toArray($only_these_fields = null)
    {
        $ret = array();
        if (is_string($only_these_fields)) {
            $only_these_fields = words($only_these_fields);
        }
        $fields = array_diff($this->known_slots, array_keys($this->relations));
        if (is_array($only_these_fields)) {
            $only_these_fields = array_filter(array_map(function($s) {
                return is_string($s) ? strtolower($s) : null;
            }, $only_these_fields));
            $fields = array_intersect($only_these_fields, $fields);
        }
        foreach($fields as $field) {
           $ret[$field] = $this->getValue($field);
        }
        return $ret;
    }

    /**
     * returns data of table row as assoc array
     * including related records with a 'has*' relationship
     * recurses one level without param
     *
     * $only_these_fields limits output for relationships in this way:
     * $only_these_fields = array('field_1',
     *                            'field_2',
     *                            'relation1',
     *                            'relation2' => array('rel2_f1',
     *                                                 'rel2_f2',
     *                                                 'rel2_rel11' => array(
     *                                                           rel2_rel1_f1)
     *                                                )
     *                           )
     * Here all fields of relation1 will be returned.
     *
     * @param mixed $only_these_fields limit returned fields
     * @return array
     */
    function toArrayRecursive($only_these_fields = null)
    {
        if (is_string($only_these_fields)) {
            $only_these_fields = words($only_these_fields);
        }
        if (is_null($only_these_fields)) {
            $only_these_fields = $this->known_slots;
        }
        $ret = $this->toArray($only_these_fields);
        $relations = array();
        if (is_array($only_these_fields)) {
            foreach ($only_these_fields as $key => $value) {
                if (!is_array($value) &&
                        array_key_exists(strtolower($value), $this->relations)) {
                    $relations[strtolower($value)] = 0; //not null|array|string to stop recursion
                }
                if (array_key_exists(strtolower($key), $this->relations)) {
                    $relations[strtolower($key)] = $value;
                }
            }
        }
        if (count($relations)) {
            foreach ($relations as $relation_name => $relation_only_these_fields) {
                $options = $this->getRelationOptions($relation_name);
                if ($options['type'] === 'has_one' ||
                        $options['type'] === 'belongs_to') {
                    $ret[$relation_name] =
                            $this->{$relation_name}->
                                            toArrayRecursive($relation_only_these_fields);
                }
                if ($options['type'] === 'has_many' ||
                    $options['type'] === 'has_and_belongs_to_many') {
                    $ret[$relation_name] =
                            $this->{$relation_name}->
                                            sendMessage('toArrayRecursive',
                                            array($relation_only_these_fields));
                }
            }
        }
        return $ret;
    }

    /**
     * returns value of a column
     *
     * @throws InvalidArgumentException if column could not be found
     * @throws BadMethodCallException if getter for additional field could not be found
     * @param string $field
     * @return null|string|SimpleORMapCollection
     */
    function getValue($field)
    {
        $field = strtolower($field);
        if (in_array($field, $this->known_slots)) {
            if (!in_array($field, $this->reserved_slots) && !$this->additional_fields[$field]['get'] && method_exists($this, 'get' . $field)) {
                return call_user_func(array($this, 'get' . $field));
            }
            if (array_key_exists($field, $this->content)) {
                return  $this->content[$field];
            } else if (array_key_exists($field, $this->relations)) {
                $this->initRelation($field);
                return $this->relations[$field];
            } elseif (isset($this->additional_fields[$field]['get'])) {
                if ($this->additional_fields[$field]['get'] instanceof Closure) {
                    return call_user_func_array($this->additional_fields[$field]['get'], array($this, $field));
                } elseif (method_exists($this, $this->additional_fields[$field]['get'])) {
                    return call_user_func(array($this, $this->additional_fields[$field]['get']), $field);
                } else {
                    throw new BadMethodCallException('Did not find getter for' . $field);
                }
            }
        } else {
            throw new InvalidArgumentException($field . ' not found.');
        }
    }

    /**
     * gets a value from a related object
     * only possible, if the relation has cardinality 1
     * e.g. 'has_one' or 'belongs_to'
     *
     * @param string $relation name of relation
     * @param string $field name of column
     * @throws InvalidArgumentException if no relation with given name is found
     * @return mixed the value from the related object
     */
    function getRelationValue($relation, $field)
    {
        $field = strtolower($field);
        $options = $this->getRelationOptions($relation);
        if ($options['type'] === 'has_one' || $options['type'] === 'belongs_to') {
            return $this->{$relation}->{$field};
        } else {
            throw new InvalidArgumentException('Relation ' . $relation . ' not found or not applicable.');
        }
    }

    /**
     * sets value of a column
     *
     * @throws InvalidArgumentException if column could not be found
     * @throws BadMethodCallException if setter for additional field could not be found
     * @param string $field
     * @param string $value
     * @return string
     */
     function setValue($field, $value)
     {
         $field = strtolower($field);
         $ret = false;
         if (in_array($field, $this->known_slots)) {
             if (!in_array($field, $this->reserved_slots) && !$this->additional_fields[$field]['set'] && method_exists($this, 'set' . $field)) {
                 return call_user_func(array($this, 'set' . $field), $value);
             }
             if (array_key_exists($field, $this->content)) {
                 $ret = ($this->content[$field] = $value);
             } elseif (isset($this->additional_fields[$field]['set'])) {
                 if ($this->additional_fields[$field]['set'] instanceof Closure) {
                     return call_user_func_array($this->additional_fields[$field]['set'], array($this, $field, $value));
                 } elseif (method_exists($this, $this->additional_fields[$field]['set'])) {
                     return call_user_func(array($this, $this->additional_fields[$field]['set']), $field, $value);
                 } else {
                     throw new BadMethodCallException('Did not find setter for' . $field);
                 }
             } elseif (array_key_exists($field, $this->relations)) {
                 $options = $this->getRelationOptions($field);
                 if ($options['type'] === 'has_one' || $options['type'] === 'belongs_to') {
                     if (strtolower(get_class($value) === $options['class_name'])) {
                         $this->relations[$field] = $value;
                         if ($options['type'] == 'has_one') {
                             $foreign_key_value = call_user_func($options['assoc_func_params_func'], $this);
                             call_user_func($options['assoc_foreign_key_setter'], $value, $foreign_key_value);
                         } else {
                             $assoc_foreign_key_value = call_user_func($options['assoc_foreign_key_getter'], $value);
                             if ($assoc_foreign_key_value === null) {
                                 throw new InvalidArgumentException(sprintf('trying to set belongs_to object of type: %s, but assoc_foreign_key: %s is null', get_class($value), $options['assoc_foreign_key']));
                             }
                             $this->setValue($options['foreign_key'], $assoc_foreign_key_value);
                         }
                     } else {
                         throw new InvalidArgumentException(sprintf('relation %s expects object of type: %s', $field, $options['class_name']));
                     }
                 }
                 if ($options['type'] == 'has_many' || $options['type'] == 'has_and_belongs_to_many') {
                     if (is_array($value) || $value instanceof Traversable) {
                         $new_ids = array();
                         $old_ids = $this->{$field}->pluck('id');
                         foreach ($value as $current) {
                             if (strtolower(get_class($current) !== $options['class_name'])) {
                                 throw new InvalidArgumentException(sprintf('relation %s expects object of type: %s', $field, $options['class_name']));
                             }
                             if ($options['type'] == 'has_many') {
                                 $foreign_key_value = call_user_func($options['assoc_func_params_func'], $this);
                                 call_user_func($options['assoc_foreign_key_setter'], $current, $foreign_key_value);
                             }
                             if ($current->id !== null) {
                                 $new_ids[] = $current->id;
                                 $existing = $this->{$field}->find($current->id);
                                 if ($existing) {
                                     $existing->setData($current);
                                 } else {
                                     $this->{$field}->append($current);
                                 }
                             } else {
                                 $this->{$field}->append($current);
                             }
                         }
                         foreach (array_diff($old_ids, $new_ids) as $to_delete) {
                             $this->{$field}->unsetByPK($to_delete);
                         }
                     } else {
                         throw new InvalidArgumentException(sprintf('relation %s expects collection or array of objects of type: %s', $field, $options['class_name']));
                     }
                 }
             }
         } else {
             throw new InvalidArgumentException($field . ' not found.');
         }
         return $ret;
     }

    /**
     * magic method for dynamic properties
     */
    function __get($field)
    {
        return $this->getValue($field);
    }
    /**
     * magic method for dynamic properties
     */
    function __set($field, $value)
    {
         return $this->setValue($field, $value);
    }
    /**
     * magic method for dynamic properties
     */
    function __isset($field)
    {
        $field = strtolower($field);
        return isset($this->content[$field]);
    }
    /**
     * ArrayAccess: Check whether the given offset exists.
     */
    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->content);
    }

    /**
     * ArrayAccess: Get the value at the given offset.
     */
    public function offsetGet($offset)
    {
        return $this->getValue($offset);
    }

    /**
     * ArrayAccess: Set the value at the given offset.
     */
    public function offsetSet($offset, $value)
    {
        $this->setValue($offset, $value);
    }
    /**
     * ArrayAccess: unset the value at the given offset (not applicable)
     */
    public function offsetUnset($offset)
    {

    }
    /**
     * IteratorAggregate
     */
    public function getIterator()
    {
        return new ArrayIterator($this->content);
    }
    /**
     * Countable
     */
    public function count()
    {
        return count($this->content);
    }

    /**
     * check if given column exists in table
     * @param string $field
     * @return boolean
     */
    function isField($field)
    {
        $field = strtolower($field);
        return isset($this->db_fields[$field]);
    }

    /**
     * check if given column is additional
     * @param string $field
     * @return boolean
     */
    function isAdditionalField($field)
    {
        $field = strtolower($field);
        return isset($this->additional_fields[$field]);
    }

    /**
     * check if given column is an alias
     * @param string $field
     * @return boolean
     */
    function isAliasField($field)
    {
        $field = strtolower($field);
        return isset($this->alias_fields[$field]);
    }

    /**
     * set multiple column values
     * if second param is set, existing data in object will be
     * discarded and dirty state is cleared,
     * else new data overrides old data
     *
     * @param array $data assoc array
     * @param boolean $reset existing data in object will be discarded
     * @return number of columns changed
     */
    function setData($data, $reset = false)
    {
        $count = 0;
        if ($reset) {
            $this->initializeContent();
        }
        if (is_array($data) || $data instanceof Traversable) {
            foreach($data as $key => $value) {
                $key = strtolower($key);
                if (isset($this->db_fields[$key])
                || isset($this->alias_fields[$key])
                || isset($this->additional_fields[$key]['set'])) {
                    $this->setValue($key, $value);
                    ++$count;
                }
            }
        }
        if ($reset) {
            $this->content_db = $this->content;
            foreach (array_keys($this->relations) as $one) {
                $this->relations[$one] = null;
            }
            $this->applyCallbacks('after_initialize');
        }
        return $count;
    }

    /**
     * check if object is empty
     * @return bool true if at least one field is not null
     */
    function haveData()
    {
        foreach ($this->content as $c) {
            if ($c !== null) return true;
        }
        return false;
    }

    /**
     * check if object exists in database
     * @return boolean
     */
    function isNew()
    {
        return $this->is_new;
    }

    /**
     * check if object was deleted
     *
     * @return boolean
     */
    function isDeleted()
    {
        return !$this->isNew() && !$this->haveData();
    }

    /**
     * set object to new state
     * @param boolean $is_new
     * @return boolean
     */
    function setNew($is_new)
    {
        return $this->is_new = $is_new;
    }

    /**
     * returns sql clause with current table and pk
     * @return boolean|string
     */
    function getWhereQuery()
    {
        $where_query = null;
        $pk_not_set = array();
        foreach ($this->pk as $key) {
            if (isset($this->content[$key])) {
                $where_query[] = "`{$this->db_table}`.`{$key}` = "  . DBManager::get()->quote($this->content[$key]);
            } else {
                $pk_not_set[] = $key;
            }
        }
        if (!$where_query || count($pk_not_set)){
            return false;
        }
        return $where_query;
    }

    /**
     * restore entry from database
     * @return boolean
     */
    function restore()
    {
        $where_query = $this->getWhereQuery();
        if ($where_query) {
            $query = "SELECT * FROM `{$this->db_table}` WHERE "
                    . join(" AND ", $where_query);
            $rs = DBManager::get()->query($query)->fetchAll(PDO::FETCH_ASSOC);
            if (isset($rs[0])) {
                if ($this->setData($rs[0], true)){
                    $this->setNew(false);
                    return true;
                }
            }
            $id = $this->getId();
        }
        $this->initializeContent();
        $this->setNew(true);
        if (isset($id)) {
            $this->setId($id);
        }
        return false;
    }

    /**
     * store entry in database
     *
     * @throws UnexpectedValueException if there are forbidden NULL values
     * @return number|boolean
     */
    function store()
    {
        if ($this->applyCallbacks('before_store') === false) {
            return false;
        }

        $where_query = $this->getWhereQuery();
        if ($where_query) {
            if ($this->isDirty() || $this->isNew()) {
                if ($this->isNew()) {
                    if ($this->applyCallbacks('before_create') === false) {
                        return false;
                    }
                } else {
                    if ($this->applyCallbacks('before_update') === false) {
                        return false;
                    }
                }
                foreach ($this->db_fields as $field => $meta) {
                    $value = $this->content[$field];
                    if ($field == 'chdate' && !$this->isFieldDirty($field) && $this->isDirty()) {
                        $value = time();
                    }
                    if ($field == 'mkdate') {
                        if ($this->isNew()) {
                            if (!$this->isFieldDirty($field)) {
                                $value = time();
                            }
                        } else {
                            continue;
                        }
                    }
                    if ($value === null && $meta['null'] == 'NO') {
                        $value = $this->default_values[$field];
                        if ($value === null) {
                            throw new UnexpectedValueException($this->db_table . '.' . $field . ' must not be null.');
                        }
                    }
                    if (is_float($value)) {
                        $value = str_replace(',','.', $value);
                    }
                    $query_part[] = "`$field` = " . DBManager::get()->quote($value) . " ";
                }
                if (!$this->isNew()) {
                    $query = "UPDATE `{$this->db_table}` SET "
                    . implode(',', $query_part);
                    $query .= " WHERE ". join(" AND ", $where_query);
                } else {
                    $query = "INSERT INTO `{$this->db_table}` SET "
                    . implode(',', $query_part);
                }
                $ret = DBManager::get()->exec($query);
                if ($this->isNew()) {
                    $this->applyCallbacks('after_create');
                } else {
                    $this->applyCallbacks('after_update');
                }
            }
            $rel_ret = $this->storeRelations();
            $this->applyCallbacks('after_store');
            if ($ret || $rel_ret) {
                $this->restore();
            }
            return $ret + $rel_ret;
        } else {
            return false;
        }
    }

    /**
     * sends a store message to all initialized related objects
     * if a relation has a callback for 'on_store' configured, the callback
     * is instead invoked
     *
     * @return number addition of all return values, false if none was called
     */
    protected function storeRelations()
    {
        $ret = false;
        foreach (array_keys($this->relations) as $relation) {
            $options = $this->getRelationOptions($relation);
            if (isset($options['on_store']) &&
            ($options['type'] === 'has_one' ||
            $options['type'] === 'has_many' ||
            $options['type'] === 'has_and_belongs_to_many')) {
                if ($options['on_store'] instanceof Closure) {
                    $ret += call_user_func($options['on_store'], $this, $relation);
                } elseif (isset($this->relations[$relation])) {
                    $foreign_key_value = call_user_func($options['assoc_func_params_func'], $this);
                    if ($options['type'] === 'has_one') {
                        call_user_func($options['assoc_foreign_key_setter'], $this->{$relation}, $foreign_key_value);
                        $ret = call_user_func(array($this->{$relation}, 'store'));
                    } elseif ($options['type'] === 'has_many') {
                        foreach ($this->{$relation} as $r) {
                            call_user_func($options['assoc_foreign_key_setter'], $r, $foreign_key_value);
                        }
                        $ret += array_sum(call_user_func(array($this->{$relation}, 'sendMessage'), 'store'));
                        $ret += array_sum(call_user_func(array($this->{$relation}->getDeleted(), 'sendMessage'), 'delete'));
                    } else {
                        call_user_func(array($this->{$relation}, 'sendMessage'), 'store');
                        $to_delete = array_filter($this->{$relation}->getDeleted()->pluck($options['assoc_foreign_key']));
                        $to_insert = array_filter($this->{$relation}->pluck($options['assoc_foreign_key']));
                        $sql = "DELETE FROM `" . $options['thru_table'] ."` WHERE `" . $options['thru_key'] ."` = ? AND `" . $options['thru_assoc_key'] . "` = ?";
                        $st = DBManager::get()->prepare($sql);
                        foreach ($to_delete as $one_value) {
                            $st->execute(array($foreign_key_value, $one_value));
                            $ret += $st->rowCount();
                        }
                        $sql = "INSERT IGNORE INTO `" . $options['thru_table'] ."` SET `" . $options['thru_key'] ."` = ?, `" . $options['thru_assoc_key'] . "` = ?";
                        $st = DBManager::get()->prepare($sql);
                        foreach ($to_insert as $one_value) {
                            $st->execute(array($foreign_key_value, $one_value));
                            $ret += $st->rowCount();
                        }
                    }
                }
            }
        }
        return $ret;
    }

    /**
     * set chdate column to current timestamp
     * @return boolean
     */
    function triggerChdate()
    {
        if ($this->db_fields['chdate']) {
            $this->content['chdate'] = time();
            if ($where_query = $this->getWhereQuery()) {
                DBManager::get()->exec("UPDATE `{$this->db_table}` SET chdate={$this->content['chdate']}
                            WHERE ". join(" AND ", $where_query));
                return true;
            }
        } else {
            return false;
        }
    }

    /**
     * delete entry from database
     * the object is cleared, but is not(!) turned to new state
     * @return int number of deleted rows
     */
    function delete()
    {
        $ret = false;
        if (!$this->isNew()) {
            if ($this->applyCallbacks('before_delete') === false) {
                return false;
            }
            $ret = $this->deleteRelations();
            $where_query = $this->getWhereQuery();
            if ($where_query) {
                $query = "DELETE FROM `{$this->db_table}` WHERE "
                        . join(" AND ", $where_query);
                $ret += DBManager::get()->exec($query);
            }
            $this->applyCallbacks('after_delete');
        }
        $this->setData(array(), true);
        return $ret;
    }

    /**
     * sends a delete message to all related objects
     * if a relation has a callback for 'on_delete' configured, the callback
     * is invoked instead
     *
     * @return number addition of all return values, false if none was called
     */
    protected function deleteRelations()
    {
        $ret = false;
        foreach (array_keys($this->relations) as $relation) {
            $options = $this->getRelationOptions($relation);
            if ($options['type'] === 'has_one' || $options['type'] === 'has_many') {
                $this->initRelation($relation);
            }
            if (isset($options['on_delete']) &&
            ($options['type'] === 'has_one' ||
            $options['type'] === 'has_many' ||
            $options['type'] === 'has_and_belongs_to_many')) {
                if ($options['on_delete'] instanceof Closure) {
                    $ret += call_user_func($options['on_delete'], $this, $relation);
                } elseif (isset($this->relations[$relation])) {
                    if ($options['type'] === 'has_one') {
                        $ret += call_user_func(array($this->{$relation}, 'delete'));
                    } elseif ($options['type'] === 'has_many') {
                        $ret += array_sum(call_user_func(array($this->{$relation}, 'sendMessage'), 'delete'));
                    } else {
                        $foreign_key_value = call_user_func($options['assoc_func_params_func'], $this);
                        $sql = "DELETE FROM `" . $options['thru_table'] ."` WHERE `" . $options['thru_key'] ."` = ?";
                        $st = DBManager::get()->prepare($sql);
                        $st->execute(array($foreign_key_value));
                        $ret += $st->rowCount();
                    }
                }
                $this->relations[$relation] = null;
            }
        }
        return $ret;
    }

    /**
     * init internal content arrays with nulls
     *
     * @throws UnexpectedValueException if there is an unmatched alias
     */
    protected function initializeContent()
    {
        $this->content = array();
        foreach (array_keys($this->db_fields) as $field) {
            $this->content[$field] = null;
            $this->content_db[$field] = null;
        }
        foreach ($this->alias_fields as $alias => $field) {
            if (isset($this->db_fields[$field])) {
                $this->content[$alias] =& $this->content[$field];
                $this->content_db[$alias] =& $this->content_db[$field];
            } else {
                throw new UnexpectedValueException(sprintf('Column %s not found for alias %s', $field, $alias));
            }
        }
    }

    /**
     * checks if at least one field was modified since last restore
     *
     * @return boolean
     */
    public function isDirty()
    {
        foreach (array_keys($this->db_fields) as $field) {
            if ($this->isFieldDirty($field)) {
                return true;
            }
        }
        return false;
    }

    /**
     * checks if given field was modified since last restore
     *
     * @param string $field
     * @return boolean
     */
    public function isFieldDirty($field)
    {
        $field = strtolower($field);
        if ($this->content[$field] === null || $this->content_db[$field] === null) {
            return $this->content[$field] !== $this->content_db[$field];
        } else {
            return (string)$this->content[$field] !== (string)$this->content_db[$field];
        }
    }

    /**
     * reverts value of given field to last restored value
     *
     * @param string $field
     * @return mixed the restored value
     */
    public function revertValue($field)
    {
        $field = strtolower($field);
        return ($this->content[$field] = $this->content_db[$field]);
    }

    /**
     * intitalize a relationship and get related record(s)
     *
     * @param string $relation name of relation
     * @throws InvalidArgumentException if the relation does not exists
     * @return void
     */
    public function initRelation($relation)
    {
        if (!array_key_exists($relation, $this->relations)) {
            throw new InvalidArgumentException('Unknown relation: ' . $relation);
        }
        if ($this->relations[$relation] === null) {
            $options = $this->getRelationOptions($relation);
            $to_call = array($options['class_name'], $options['assoc_func']);
            $params = $options['assoc_func_params_func'];
            if ($options['type'] === 'has_many') {
                $records = function($record) use ($to_call, $params) {$p = (array)$params($record); return call_user_func_array($to_call, count($p) ? $p : array(null));};
                $this->relations[$relation] = new EPP_SimpleORMapCollection($records, $options, $this);
            } elseif ($options['type'] === 'has_and_belongs_to_many') {
                $records = function($record) use ($to_call, $params, $options) {$p = (array)$params($record); return call_user_func_array($to_call, array_merge(count($p) ? $p : array(null), array($options)));};
                $this->relations[$relation] = new EPP_SimpleORMapCollection($records, $options, $this);
            } else {
                $p = (array)$params($this);
                $records = call_user_func_array($to_call, count($p) ? $p : array(null));
                $result = is_array($records) ? $records[0] : $records;
                if (!$result && $options['type'] === 'has_one') {
                    $result = new $options['class_name'];
                    $foreign_key_value = call_user_func($options['assoc_func_params_func'], $this);
                    call_user_func($options['assoc_foreign_key_setter'], $result, $foreign_key_value);
                }
                $this->relations[$relation] = $result;
            }
        }
    }

    /**
     * clear data for a relationship
     *
     * @param string $relation name of relation
     * @throws InvalidArgumentException if teh relation does not exists
     */
    public function resetRelation($relation)
    {
        if (!array_key_exists($relation, $this->relations)) {
            throw new InvalidArgumentException('Unknown relation: ' . $relation);
        }
        $this->relations[$relation] = null;
    }

    /**
     * invoke registered callbacks for given type
     * if one callback returns false the following will not
     * be invoked
     *
     * @param string $type type of callback
     * @return bool return value from last callback
     */
    protected function applyCallbacks($type)
    {
        $ok = true;
        foreach ($this->registered_callbacks[$type] as $cb) {
            if ($cb instanceof Closure) {
                $function =  $cb;
                $params = array($this, $type);
            } else {
                $function = array($this, $cb);
                $params = array($type);
            };
            $ok = call_user_func_array($function, $params);
            if ($ok === false) {
                break;
            }
        }
        return $ok;
    }

    /**
     * register given callback for one or many possible callback types
     * callback param could be a closure or method name of current class
     *
     * @param string|array $types types to register callback for
     * @param mixed $cb callback
     * @throws InvalidArgumentException if the callback type is not known
     * @return number of registered callbacks
     */
    protected function registerCallback($types, $cb)
    {
        $types = is_array($types) ?: words($types);
        foreach ($types as $type) {
            if (isset($this->registered_callbacks[$type])) {
                $this->registered_callbacks[$type][] = $cb;
                $reg++;
            } else {
                throw new InvalidArgumentException('Unknown callback type: ' . $type);
            }
        }
        return $reg;
    }

    /**
     * unregister given callback for one or many possible callback types
     *
     * @param string|array $types types to unregister callback for
     * @param mixed $cb
     * @throws InvalidArgumentException if the callback type is not known
     * @return number of unregistered callbacks
     */
    protected function unregisterCallback($types, $cb)
    {
        $types = is_array($types) ?: words($types);
        foreach ($types as $type) {
            if (isset($this->registered_callbacks[$type])) {
                $found = array_search($cb, $this->registered_callbacks[$type], true);
                if ($found !== false) {
                    $unreg++;
                    unset($this->registered_callbacks[$type][$found]);
                }
            } else {
                throw new InvalidArgumentException('Unknown callback type: ' . $type);
            }
        }
        return $unreg;
    }

    /**
     * default callback for tables with auto_increment primary key
     *
     * @param string $type callback type
     * @return boolean
     */
    protected function cbAutoIncrementColumn($type)
    {
        if ($type == 'after_create' && !$this->getId()) {
            $this->setId(DBManager::get()->lastInsertId());
        }
        if ($type == 'before_store' && $this->isNew() && $this->getId() === null) {
            $this->setId(0);
        }
        return true;
    }

    /**
     * default callback for tables without auto_increment
     */
    protected function cbAutoKeyCreation()
    {
        if ($this->isNew() && $this->getId() === null) {
            $this->setId($this->getNewId());
        }
    }
}
