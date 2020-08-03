<?php

namespace App\Http\Traits\Metable;

use App\Models\MetaData;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as BaseCollection;

trait Metable
{
    static $groupingEnabled = true; // Constants for traits

    public $grouping    = null;
    public $duplication = null;

    /**
     * Meta scope for easier join
     * -------------------------
     */
    public function scopeMeta($query)
    {
        return $query->join($this->getMetaTable(), $this->getTable() . '.id', '=', $this->getMetaTable() . '.' . $this->getMetaKeyName());
    }

    /**
     * Set Meta Data functions
     * -------------------------.
     */
    public function setMeta($key, $value = null, $grouping = null, $duplicate = null)
    {
        $setMeta = 'setMeta' . ucfirst(gettype($key));

        if (strtolower(gettype($key)) === 'array') {
            $grouping = $value;

            if (self::$groupingEnabled && null === $grouping) {
                $grouping = 'profile';
            }
        }

        return $this->setGrouping($grouping)->setDuplicate($duplicate)->$setMeta($key, $value);
    }

    protected function setMetaString($key, $value)
    {
        $key = strtolower($key);

        // For multi values (grouping)
        if ( $this->isGrouping() ) {

            if ( null === $this->metaDataMulti) {
                $this->metaLoaded = null;

                $this->getMetaDataMulti();
            }

            if (!$this->metaDataMulti->has($key)) {
                $this->metaDataMulti[$key] = new BaseCollection();
            }

            $meta = $this->metaDataMulti[$key]->where('grouping', $this->getGrouping())->first();

            if ( !$meta || null !== $this->getDuplicate() ) {
                return $this->metaDataMulti[$key]->put(null, $this->getModelStub([
                    'key'   => $key,
                    'value' => $value,
                    'grouping' => $this->getGrouping()
                ]));
            }

            // Make sure deletion marker is not set
            $meta->markForDeletion(false);

            $meta->value = $value;

            return $meta;

        } else {

            if ( null === $this->metaData) {
                $this->metaLoaded = null;

                $this->getMetaData();
            }

            if ($this->metaData->has($key) && null === $this->getDuplicate()) {

                // Make sure deletion marker is not set
                $this->metaData[$key]->markForDeletion(false);

                $this->metaData[$key]->value    = $value;

                return $this->metaData[$key];
            }

            return $this->metaData[$key] = $this->getModelStub(
                array_merge([
                    'key'   => $key,
                    'value' => $value,
                ], array_filter([
                    'grouping' => $this->getGrouping()
                ]))
            );

        }
    }

    protected function setMetaArray()
    {
        list($metas) = func_get_args();

        foreach ($metas as $key => $value) {
            $this->setMetaString($key, $value);
        }

        return $this->isGrouping() ?
            $this->metaDataMulti :
            $this->metaData;
    }

    /**
     * Unset Meta Data functions
     * -------------------------.
     */
    public function unsetMeta($key)
    {
        $unsetMeta = 'unsetMeta' . ucfirst(gettype($key));

        return $this->$unsetMeta($key);
    }

    protected function unsetMetaString($key)
    {
        $key = strtolower($key);
        if ($this->metaData->has($key)) {
            $this->metaData[$key]->markForDeletion();
        }
    }

    protected function unsetMetaArray()
    {
        list($keys) = func_get_args();

        foreach ($keys as $key) {
            $key = strtolower($key);
            $this->unsetMetaString($key);
        }
    }

    /**
     * Get Meta Data functions
     * -------------------------.
     */
    public function getMeta($key = null, $raw = false)
    {
        if (is_string($key) && preg_match('/[,|]/is', $key, $m)) {
            $key = preg_split('/ ?[,|] ?/', $key);
        }

        $getMeta = 'getMeta' . ucfirst(strtolower(gettype($key)));

        return $this->$getMeta($key, $raw);
    }

    protected function getMetaString($key, $raw = false)
    {
        if ( null === $this->metaData ) {
            $this->metaLoaded = null;
            $this->getMetaData();
        }

        $meta = $this->metaData->get($key, null);

        if (is_null($meta) || $meta->isMarkedForDeletion()) {
            return;
        }

        return ($raw) ? $meta : $meta->value;
    }

    protected function getMetaArray($keys, $raw = false)
    {
        $collection = new BaseCollection();

        if ( null === $this->metaData ) {
            $this->metaLoaded = null;
            $this->getMetaData();
        }

        foreach ($this->metaData as $meta) {
            if (!$meta->isMarkedForDeletion() && in_array($meta->key, $keys)) {
                $collection->put($meta->key, $raw ? $meta : $meta->value);
            }
        }

        return $collection;
    }

    protected function getMetaNull()
    {
        list($keys, $raw) = func_get_args();

        $collection = new BaseCollection();

        if ( null === $this->metaData ) {
            $this->metaLoaded = null;
            $this->getMetaData();
        }

        foreach ($this->metaData as $meta) {
            if (!$meta->isMarkedForDeletion()) {
                $collection->put($meta->key, $raw ? $meta : $meta->value);
            }
        }

        return $collection;
    }

    /**
     * Get Meta Data functions
     * -------------------------.
     */
    public function getMetaMulti($grouping, $key = null, $raw = false)
    {
        if (is_string($key) && preg_match('/[,|]/is', $key, $m)) {
            $key = preg_split('/ ?[,|] ?/', $key);
        }

        $getMeta = 'getMetaMulti'.ucfirst(strtolower(gettype($key)));

        return $this->$getMeta($grouping, $key, $raw);
    }

    protected function getMetaMultiString($grouping, $key, $raw = false)
    {
        if (null === $this->metaDataMulti) {
            $this->metaLoaded = null;
            $this->getMetaDataMulti();
        }

        $meta = $this->metaDataMulti->get($key, null);

        if (null === $meta) {
            return null;
        }

        $meta = $meta->where('grouping', $grouping)->first();

        if (is_null($meta) || $meta->isMarkedForDeletion()) {
            return;
        }

        return ($raw) ? $meta : $meta->value;
    }

    protected function getMetaMultiArray($grouping, $keys, $raw = false)
    {
        $collection = new BaseCollection();

        if (null === $this->metaDataMulti) {
            $this->metaLoaded = null;
            $this->getMetaDataMulti();
        }

        foreach ($this->metaDataMulti as $key => $meta) {

            if (!in_array($key, $keys))
                continue;

            $meta = $meta->where('grouping', $grouping)->first();

            if ( $meta && !$meta->isMarkedForDeletion()) {
                $collection->put($meta->key, $raw ? $meta : $meta->value);
            }
        }

        return $collection;
    }

    protected function getMetaMultiNull()
    {
        list($grouping, $keys, $raw) = func_get_args();

        $collection = new BaseCollection();

        if (null === $this->metaDataMulti) {
            $this->metaLoaded = null;
            $this->getMetaDataMulti();
        }

        foreach ($this->metaDataMulti as $meta) {

            if (!is_null($grouping)) {
                $meta = $meta->where('grouping', $grouping)->first();
            }

            if ( $meta && !$meta->isMarkedForDeletion()) {
                $collection->put($meta->key, $raw ? $meta : $meta->value);
            }
        }

        return $collection;
    }

    /**
     * Query Meta Table functions
     * -------------------------.
     */
    public function whereMeta($key, $value)
    {
        return $this->getModelStub()
            ->whereKey(strtolower($key))
            ->whereValue($value)
            ->get();
    }

    public function whereMetaArray($values = [], $allRecords = false)
    {
        if ($allRecords) {
            return $this->getModelStub()->where($values)->get();
        } else {
            return $this->getModelStub()->where($values)->first();
        }
    }

    /**
     * Trait specific functions
     * -------------------------.
     */
    protected function setObserver()
    {
        $this->saved(function ($model) {
            $model->saveMeta();
        });
    }

    protected function getModelStub()
    {
        // get new meta model instance
        $model = $this->getMetaModel();
        $model = new $model;
        $model->setTable($this->getMetaTable());

        // model fill with attributes.
        if (func_num_args() > 0) {
            array_filter(func_get_args(), [$model, 'fill']);
        }

        return $model;
    }

    protected function saveMeta()
    {
        if ( $this->isGrouping() ) {
            if ($this->metaDataMulti) {
                foreach ($this->metaDataMulti as $meta) {
                    foreach ($meta as $key => $nestedMeta) {
                        $this->doProcessingOnMeta($nestedMeta);
                    }
                }
            }
        } else {
            if ($this->metaData) {
                foreach ($this->metaData as $meta) {
                    $this->doProcessingOnMeta($meta);
                }
            }
        }

        $this->resetFlagsOnSave();
    }

    private function doProcessingOnMeta($meta)
    {
        $meta->setTable($this->getMetaTable());

        if ($meta->isMarkedForDeletion()) {
            $meta->delete();
            return;
        }

        if ($meta->isDirty()) {
            // set meta and model relation id's into meta table.
            $meta->setAttribute($this->metaKeyName, $this->modelKey);
            $meta->save();
        }
    }

    protected function getMetaData()
    {
        if (!isset($this->metaLoaded)) {
            $this->setObserver();

            if ($this->exists) {
                $objects = $this->getModelStub()
                    ->where($this->metaKeyName, $this->modelKey)
                    ->get();

                if (!is_null($objects)) {
                    $this->metaLoaded = true;

                    return $this->metaData = $objects->keyBy('key');
                }
            }
            $this->metaLoaded = true;

            return $this->metaData = new Collection();
        }
    }

    protected function getMetaDataMulti()
    {
        if (!isset($this->metaLoaded)) {
            $this->setObserver();

            if ($this->exists) {
                $objects = $this->getModelStub()
                    ->where($this->metaKeyName, $this->modelKey)
                    ->get();

                if (!is_null($objects)) {
                    $this->metaLoaded = true;

                    return $this->metaDataMulti = $objects->groupBy('key');
                }
            }
            $this->metaLoaded = true;

            return $this->metaDataMulti = new Collection();
        }
    }

    /**
     * Reset grouping.
     *
     * @return Metable
     */
    protected function resetGrouping()
    {
        return $this->setGrouping(null);
    }

    /**
     * Is grouping set?
     *
     * @return string
     */
    protected function isGrouping()
    {
        return null !== $this->grouping;
    }

    /**
     * Retreive set grouping value.
     *
     * @return string
     */
    protected function getGrouping()
    {
        return $this->grouping;
    }

    /**
     * Update grouping value
     *
     * @return Metable
     */
    protected function setGrouping($value)
    {
        $this->grouping = $value;

        return $this;
    }

    /**
     * Reset duplication.
     *
     * @return Metable
     */
    protected function resetDuplicate()
    {
        return $this->setDuplicate(null);
    }

    /**
     * Retreive duplication value.
     *
     * @return string
     */
    protected function getDuplicate()
    {
        return $this->duplication;
    }

    /**
     * Update duplication value
     *
     * @return Metable
     */
    protected function setDuplicate($value)
    {
        $this->duplication = $value;

        return $this;
    }

    /**
     * Reset all flags set for meta.
     *
     * @return Metable
     */
    protected function resetFlagsOnSave()
    {
        $this->metaDataMulti = null;
        $this->metaData = null;
        $this->metaLoaded = null;

        return $this
            ->resetGrouping()
            ->resetDuplicate();
    }

    /**
     * Return the key for the model.
     *
     * @return string
     */
    protected function getModelKey()
    {
        return $this->getKey();
    }

    /**
     * Return the foreign key name for the meta table.
     *
     * @return string
     */
    protected function getMetaKeyName()
    {
        return isset($this->metaKeyName) ? $this->metaKeyName : $this->getForeignKey();
    }

    /**
     * Return the table name.
     *
     * @return null
     */
    protected function getMetaTable()
    {
        return isset($this->metaTable) ? $this->metaTable : $this->getTable() . '_meta';
    }

    /**
     * Return fully qualified class name of meta data.
     *
     * @return null
     */
    protected function getMetaModel()
    {
        return isset($this->metaModel) ? $this->metaModel : 'App\Models\MetaData';
    }

    /**
     * Extended meta function for default value support.
     *
     * @param  string  $key
     * @param  boolean  $default
     * @param  boolean $raw
     *
     * @return mixed
     */
    public function getMetaDefault($key, $default = null)
    {
        $value = self::getMeta($key, false);

        return null === $value ? (null === $default ? $value : $default) : $value;
    }

    /**
     * Extended meta function for default value support.
     *
     * @param  string  $key
     * @param  boolean  $default
     * @param  boolean $raw
     *
     * @return mixed
     */
    public function getMetaMultiDefault($grouping, $key, $default = null)
    {
        $value = self::getMetaMulti($grouping, $key, false);

        return null === $value ? (null === $default ? $value : $default) : $value;
    }

    /**
     * Always return object wether key exist or not.
     *
     * @param  string $key
     * @return MetaData
     */
    public function getMetaObject($key)
    {
        return self::getMeta($key, true) ?: app($this->getMetaModel());
    }

    /**
     * Convert the model instance to an array.
     *
     * @return array
     */
    public function toArray()
    {
        return array_merge(parent::toArray(), [
            'meta_data' => $this->getMeta()->toArray(),
        ]);
    }

    /**
     * Model Override functions
     * -------------------------.
     */

    /**
     * Get an attribute from the model.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function getAttribute($key)
    {
        // parent call first.
        if (($attr = parent::getAttribute($key)) !== null) {
            return $attr;
        }

        // there was no attribute on the model
        // retrieve the data from meta relationship
        return $this->getMeta($key);
    }

    public function __unset($key)
    {
        // unset attributes and relations
        parent::__unset($key);

        // delete meta
        $this->unsetMeta($key);
    }

    public function __get($attr)
    {
        // Check for meta accessor
        $accessor = camel_case('get_' . $attr . '_meta');

        if (method_exists($this, $accessor)) {
            return $this->{$accessor}();
        }

        // Check for legacy getter
        $getter = 'get' . ucfirst($attr);

        // leave model relation methods for parent::
        $isRelationship = method_exists($this, $attr);

        if (method_exists($this, $getter) && !$isRelationship) {
            return $this->{$getter}();
        }

        return parent::__get($attr);
    }

    public function __set($key, $value)
    {
        // ignore the trait properties being set.
        if (starts_with($key, 'meta') || $key == 'query') {
            $this->$key = $value;

            return;
        }

        // if key is a model attribute, set as is
        if (array_key_exists($key, parent::getAttributes())) {
            parent::setAttribute($key, $value);

            return;
        }

        // if the key has a mutator execute it
        $mutator = camel_case('set_' . $key . '_meta');

        if (method_exists($this, $mutator)) {
            $this->{$mutator}($value);

            return;
        }

        // if key belongs to meta data, append its value.
        if ($this->metaData->has($key)) {
            /*if ( is_null($value) ) {
            $this->metaData[$key]->markForDeletion();
            return;
            }*/
            $this->metaData[$key]->value = $value;

            return;
        }

        // if model table has the column named to the key
        if (\Schema::hasColumn($this->getTable(), $key)) {
            parent::setAttribute($key, $value);

            return;
        }

        // key doesn't belong to model, lets create a new meta relationship
        //if ( ! is_null($value) ) {
        $this->setMetaString($key, $value);
        //}
    }

    public function __isset($key)
    {
        // trait properties.
        if (starts_with($key, 'meta') || $key == 'query') {
            return isset($this->{$key});
        }

        // check parent first.
        if (parent::__isset($key) === true) {
            return true;
        }

        // lets check meta data.
        return isset($this->getMetaData()[$key]);
    }
}
