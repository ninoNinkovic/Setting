<?php namespace Philf\Setting;

/*
 * ---------------------------------------------
 * | Do not remove!!!!                         |
 * |                                           |
 * | @package   PhoenixCore                    |
 * | @version   2.0                            |
 * | @develper  Phil F (http://www.Weztec.com) |
 * | @author    Phoenix Development Team       |
 * | @license   Free to all                    |
 * | @copyright 2013 Phoenix Group             |
 * | @link      http://www.phoenix-core.com    |
 * ---------------------------------------------
 *
 * Example syntax:
 * use Setting (If you are using namespaces)
 *
 * Single dimension
 * set:     Setting::set(array('name' => 'Phil'))
 * put:     Setting::put(array('name' => 'Phil'))
 * get:     Setting::get('name')
 * forget:  Setting::forget('name')
 * has:     Setting::has('name')
 *
 * Multi dimensional
 * set:     Setting::set(array('names' => array('firstname' => 'Phil', 'surname' => 'F')))
 * put:     Setting::put(array('names' => array('firstname' => 'Phil', 'surname' => 'F')))
 * get:     Setting::get('names.firstname')
 * forget:  Setting::forget(array('names' => 'surname'))
 * has:     Setting::has('names.firstname')
 *
 * Using a different path (make sure the path exists and is writable) *
 * Setting::path('setting2.json')->set(array('names2' => array('firstname' => 'Phil', 'surname' => 'F')));
 *
 * Using a different filename
 * Setting::filename('setting2.json')->set(array('names2' => array('firstname' => 'Phil', 'surname' => 'F')));
 *
 * Using both a different path and filename (make sure the path exists and is writable)
 * Setting::path(app_path().'/storage/meta/sub')->filename('dummy.json')->set(array('names2' => array('firstname' => 'Phil', 'surname' => 'F')));
 */

class Setting {

    /**
     * The path to the file
     * @var string
     */
    protected $path;

    /**
     * The filename used to store the config
     * @var string
     */
    protected $filename;

    /**
     * The class working array
     * @var array
     */
    protected $settings;

    /**
     * Create the Setting instance
     * @param string $path      The path to the file
     * @param string $filename  The filename
     * @param interfaces\FallbackInterface $fallback
     */
    public function __construct($path, $filename, $fallback = null)
    {
        $this->path     = $path;
        $this->filename = $filename;
        $this->fallback = $fallback;

        // Load the file and store the contents in $this->settings
        $this->load($this->path, $this->filename);
    }

    /**
     * Set the path to the file to use
     * @param  string $path The path to the file
     * @return \Philf\Setting\Setting
     */
    public function path($path)
    {
        $this->path = $path;
        return $this;
    }

    /**
     * Set the filename to use
     * @param  string $filename The filename
     * @return \Philf\Setting\Setting
     */
    public function filename($filename)
    {
        $this->filename = $filename;
        return $this;
    }

    /**
     * Get a value and return it
     * @param  string $searchKey String using dot notation
     * @return Mixed             The value(s) found
     */
    public function get($searchKey)
    {
        if($this->settings != $this->array_get($this->settings, $searchKey))
            return $this->array_get($this->settings, $searchKey);
        if(!is_null($this->fallback) && $this->fallback->fallbackHas($searchKey))
            return $this->fallback->fallbackGet($searchKey);
        return null;
    }

    /**
     * An alias for put
     * @param mixed $value The value(s) to be stored
     * @return void
     */
    public function set($value)
    {
        $this->put($value);
    }

    /**
     * Store the passed value in to the json file
     * @param  array $value The value(s) to be stored
     * @return void
     */
    public function put($value)
    {
        $this->settings = $this->build($this->settings, $value);
        $this->save($this->path, $this->filename);
        $this->load($this->path, $this->filename);
    }

    /**
     * Store the passed value in to the json file
     * @param $key
     * @param  mixed $value The value(s) to be stored
     * @return void
     */
    public function store($key, $value)
    {
        array_set($this->settings,$key,$value);
        $this->save($this->path, $this->filename);
        $this->load($this->path, $this->filename);
    }

    /**
     * Forget the value(s) currently stored
     * @param  mixed $deleteKey The value(s) to be removed
     * @return void
     */
    public function forget($deleteKey)
    {
        if (is_array($deleteKey))
        {
            foreach($deleteKey as $key => $val)
            {
                unset($this->settings[$key][$val]);
            }
        }
        else
        {
            unset($this->settings[$deleteKey]);
        }

        $this->save($this->path, $this->filename);
        $this->load($this->path, $this->filename);
    }

    /**
     * Check to see if the value exists
     * @param  string  $searchKey The key to search for
     * @return boolean            True: found - False not found
     */
    public function has($searchKey)
    {
        if($this->settings == $this->array_get($this->settings, $searchKey) && !is_null($this->fallback))
            return $this->fallback->fallbackHas($searchKey);
        return $this->settings == $this->array_get($this->settings, $searchKey) ? false : true;
    }

    /**
     * Recursively build the $this->settings array
     * @param  mixed $value1 The current $this->settings array or the recusive value
     * @param  mixed $value2 The array of values to add or the recusive value
     * @return array         The new array with all keys and values merged or updated
     */
    public function build($value1, $value2)
    {
        foreach($value2 as $key => $val)
        {
            if (is_array($value1))
            {
                if(array_key_exists($key, $value1) and is_array($val))
                {
                    $value1[$key] = $this->build($value1[$key], $value2[$key]);
                }
                else
                {
                    $value1[$key] = $val;
                }
            }
            else
            {
                $value1 = array($key => $val);
            }
        }

        return $value1;
    }

    /**
     * Load the file in to $this->settings so values can be used immediately
     * @param  string $path     The path to be used
     * @param  string $filename The filename to be used
     * @return \Philf\Setting\Setting
     */
    public function load($path = null, $filename = null)
    {
        $this->path     = isset($path) ? $path : $this->path;
        $this->filename = isset($filename) ? $filename : $this->filename;

        if (is_file($this->path.'/'.$this->filename))
        {
            $this->settings = json_decode(file_get_contents($this->path.'/'.$this->filename), true);
        }
        else
        {
            $this->settings = array();
        }

        return $this;
    }

    /**
     * Save the file
     * @param  string $path     The path to be used
     * @param  string $filename The filename to be used
     * @return void
     */
    public function save($path = null, $filename = null)
    {
        $this->path     = isset($path) ? $path : $this->path;
        $this->filename = isset($filename) ? $filename : $this->filename;

        $fh = fopen($this->path.'/'.$this->filename, 'w+');
        fwrite($fh, json_encode($this->settings, JSON_UNESCAPED_UNICODE));
        fclose($fh);
    }

    /**
     * Get an item from an array using "dot" notation.
     * Stole it from Illuminate/Support/helpers.php
     *
     * @param  array $array
     * @param  string $key
     * @internal param mixed $default
     * @return mixed
     */
    function array_get($array, $key)
    {
        if (is_null($key) || empty($key)) return $array;

        if (isset($array[$key])) return $array[$key];

        $toWalk = explode('.',$key);
        $workArray = &$array;
        foreach ($toWalk as $segment) {
            if($segment === end($toWalk))
            {
                return $workArray[$segment];
            }
            if(!array_key_exists($segment,$workArray) || !is_array($workArray[$segment]))
                return $array;
            $workArray = &$workArray[$segment];
        }
        return $workArray;
    }

    /**
     * Set an item in an array using "dot" notation.
     * This method will manipulate the given array
     *
     * @param  array $array
     * @param  string $key
     * @param $value mixed The value to add
     * @internal param mixed $default
     * @return mixed
     */
    function array_set(&$array, $key, $value)
    {
        if (is_null($key) || is_null($value)) return $array;

        $toWalk = explode('.',$key);
        $workArray = &$array;
        foreach ($toWalk as $segment) {
            if($segment === end($toWalk))
            {
                $workArray[$segment] = $value;
                return $array;
            }
            if(array_key_exists($segment,$workArray) && !is_array($workArray[$segment]))
                $workArray[$segment] = array();
            $workArray = &$workArray[$segment];
        }
        return $array;
    }
}