<?php

namespace Perimeter\ArrayTransformer;

/*
    This class uses the concept of a "path" to index into multi-dimentional
    arrays.

    The wild-card "?" is used to iterate over the entire subarray.

    Examples:

    A       -->   $array['A']
    A/B     -->   $array['A']['B']
    A/?/B   -->   foreach($array['A'] as $k => $v) $array['A'][$k]['B']

*/

class ArrayTransformer
{
    const WILD_CARD = '?';
    const PATH_DELIMITER = '/';
    const SELECTOR_DELIMITER = ':';

    protected $data;

    public function __construct(array &$data)
    {
        $this->data = &$data;
    }

    public function rename($oldPath, $newPath)
    {
        $old = explode(self::PATH_DELIMITER, $oldPath);
        $new = explode(self::PATH_DELIMITER, $newPath);

        return $this->renameKeys($this->data, $old, $new);
    }

    public function replace($path, $valueMap)
    {
        $path = explode(self::PATH_DELIMITER, $path);

        return $this->replaceValues($this->data, $valueMap, $path);
    }

    public function translate($path, $valueMap)
    {
        $path = explode(self::PATH_DELIMITER, $path);

        return $this->translateValues($this->data, $valueMap, $path);
    }

    public function remove($path)
    {
        $path = explode(self::PATH_DELIMITER, $path);

        return $this->removeKeys($this->data, $path);
    }

    public function checkPath($path)
    {
        $path = explode(self::PATH_DELIMITER, $path);

        return $this->pathExists($this->data, $path);
    }

    /*
     * This works for ALL callables:
     *
     *   $callable = 'strtolower';
     *   $callable = MyClass::staticfunction;
     *   $callable = array($myobj, 'someFunction');
     *   $callable = function ($key) { return strtolower($key); };
     *
     */
    public function modify($path, $callable)
    {
        $function = function (&$data, $key, &$value) use ($callable) {
            $value = call_user_func_array($callable, array(&$value));
        };
        $path = explode(self::PATH_DELIMITER, $path);
        $this->arrayMap($this->data, $path, $function);
    }

    public function reorder($path, $position, $otherkey = null)
    {
        $function = function (&$data, $key, &$value) use ($position, $otherkey) {
            $newData = array();
            $i = 0;
            foreach ($data as $datakey => $datavalue) {
                if (is_null($otherkey)) {
                    if (0 == $i && 'first' == $position) {
                        $newData[$key] = $value;
                    }
                    if ($datakey != $key) {
                        $newData[$datakey] = $datavalue;
                    }
                    if ((count($data) - 1) == $i && 'last' == $position) {
                        $newData[$key] = $value;
                    }
                } elseif ($datakey === $otherkey) {
                    if ($position === 'before') {
                        $newData[$key] = $value;
                        $newData[$datakey] = $datavalue;
                    } elseif ($position === 'after') {
                        $newData[$datakey] = $datavalue;
                        $newData[$key] = $value;
                    }
                } elseif ($datakey !== $key) {
                    $newData[$datakey] = $datavalue;
                }
                $i++;
            }
            $data = $newData;
        };
        $path = explode(self::PATH_DELIMITER, $path);

        return $this->arrayMap($this->data, $path, $function);
    }

    public function each($path, $callable)
    {
        $path = explode(self::PATH_DELIMITER, $path);

        return $this->arrayMap($this->data, $path, $callable);
    }

    public function get($path)
    {
        $path = explode(self::PATH_DELIMITER, $path);

        return $this->getValue($this->data, $path);
    }

    public function set($path, $value)
    {
        $path = explode(self::PATH_DELIMITER, $path);

        return $this->setValue($this->data, $path, $value);
    }

    public function setDefaultValue($path, $default)
    {
        if (!$this->checkPath($path) || is_null($this->get($path))) {
            $this->set($path, $default);
        }
    }

    protected function arrayMap(&$data, $path, $callable, $depth = 0)
    {
        if (!is_array($data)) {
            return false;
        }

        if ($path[$depth] == self::WILD_CARD) {
            $return = false;
            foreach ($data as $key => $val) {
                if ($depth == count($path)-1) {
                    if (call_user_func_array($callable, array(&$data, $key, &$data[$key]))) {
                        $return = true;
                    }
                } elseif ($this->arrayMap($data[$key], $path, $callable, $depth+1)) {
                    $return = true;
                }
            }

            return $return;
        } elseif (array_key_exists($path[$depth], $data)) {
            if ($depth == count($path)-1) {
                if (call_user_func_array($callable, array(&$data, $path[$depth], &$data[$path[$depth]])) === false) {
                    return false;
                }

                return true;
            }

            return $this->arrayMap($data[$path[$depth]], $path, $callable, $depth+1);
        }

        return false;
    }

    /**
     * Determines if there is a value set at end of the given path
     */
    protected function pathExists($data, $path, $depth = 0)
    {
        if (!is_array($data)) {
            return false;
        }

        if ($path[$depth] == self::WILD_CARD) {
            $matches = array();
            foreach ($data as $key => $val) {
                $match = $this->pathExists($data[$key], $path, $depth+1);
                $matches[] = $match;
            }

            return $matches;
        } elseif (array_key_exists($path[$depth], $data)) {
            if ($depth == count($path)-1) {
                return true;
            }

            return $this->pathExists($data[$path[$depth]], $path, $depth+1);
        }

        return false;
    }

    protected function getValue($data, $path, $depth = 0)
    {
        if (!is_array($data)) {
            return null;
        }

        if ($path[$depth] == self::WILD_CARD) {
            $matches = array();
            foreach ($data as $key => $val) {
                $match = $this->getValue($data[$key], $path, $depth+1);
                $matches[] = $match;
            }

            return $matches;
        } elseif ($depth == count($path)-1) {
            return $data[$path[$depth]];
        } elseif (array_key_exists($path[$depth], $data)) {
            return $this->getValue($data[$path[$depth]], $path, $depth+1);
        }

        return null;
    }

    protected function setValue(&$data, $path, $value, $depth = 0)
    {
        if (!is_array($data)) {
            return false;
        }

        if ($path[$depth] == self::WILD_CARD) {
            $return = false;
            foreach ($data as $key => $val) {
                if ($this->setValue($data[$key], $path, $value, $depth+1)) {
                    $return = true;
                }
            }

            return $return;
        } elseif ($depth == count($path)-1) {
            $data[$path[$depth]] = $value;

            return true;
        } elseif (array_key_exists($path[$depth], $data)) {
            return $this->setValue($data[$path[$depth]], $path, $value, $depth+1);
        }

        return false;
    }

    protected function renameKeys(&$data, &$oldPath, &$newPath, $depth = 0)
    {
        if (!is_array($data)) {
            return false;
        }

        if ($depth == count($oldPath)-1 || $depth == count($newPath)-1) {
            if (!$this->pathExists($data, $oldPath, $depth)) {
                return false;
            }
            $value = $this->getValue($data, $oldPath, $depth);
            $this->removeKeys($data, $oldPath, $depth);

            return $this->setValue($data, $newPath, $value, $depth);
        } elseif ($oldPath[$depth] == self::WILD_CARD) {
            $return = false;
            foreach ($data as $key => $value) {
                if ($this->renameKeys($data[$key], $oldPath, $newPath, $depth+1)) {
                    $return = true;
                }
            }

            return $return;
        } elseif (array_key_exists($oldPath[$depth], $data)) {
            return $this->renameKeys($data[$oldPath[$depth]], $oldPath, $newPath, $depth+1);
        }

        return false;
    }

    /**
     * modifies the data at the end of the path with values from valueMap,
     * using the valueMap keys as search and the valueMap values as replace
     * e.g.
     *      old value: page_type34
     *      valueMap: [page_type => prop]
     *      new value: prop34
     */
    protected function replaceValues(&$data, &$valueMap, &$path, $depth = 0)
    {
        if ($depth == count($path)) {
            if (is_array($data)) {
                return false;
            }

            $search = array_keys($valueMap);
            $replace = array_values($valueMap);
            $data = str_replace($search, $replace, $data);

            return true;
        } elseif ($path[$depth] == self::WILD_CARD) {
            if (!is_array($data)) {
                return false;
            }

            $return = false;
            foreach ($data as $key => $value) {
                if ($this->replaceValues($data[$key], $valueMap, $path, $depth+1)) {
                    $return = true;
                }
            }

            return $return;
        } elseif (array_key_exists($path[$depth], $data)) {
            return $this->replaceValues($data[$path[$depth]], $valueMap, $path, $depth+1);
        }

        return false;
    }

    /**
     * replaces the data at the end of the path with a value from valueMap,
     * using the original value as the index in valueMap to determine the new value
     * e.g.
     *      old value: hello
     *      valueMap: [hello => hola]
     *      new value: hola
     */
    protected function translateValues(&$data, &$valueMap, &$path, $depth = 0)
    {
        if ($depth == count($path)) {
            if (is_array($data)) {
                return false;
            }

            if (is_bool($data)) {
                // array_key_exists does not accept boolean values
                $data = intval($data);
            }

            if (!array_key_exists($data, $valueMap)) {
                return false;
            }

            $data = $valueMap[$data];

            return true;
        } elseif ($path[$depth] == self::WILD_CARD) {
            if (!is_array($data)) {
                return false;
            }

            $return = false;
            foreach ($data as $key => $value) {
                if ($this->translateValues($data[$key], $valueMap, $path, $depth+1)) {
                    $return = true;
                }
            }

            return $return;
        } elseif (array_key_exists($path[$depth], $data)) {
            return $this->translateValues($data[$path[$depth]], $valueMap, $path, $depth+1);
        }

        return false;
    }

    protected function removeKeys(&$data, &$path, $depth = 0)
    {
        if (!is_array($data)) {
            return false;
        }

        if ($depth == count($path)-1) {
            if ($selector = $this->parseSelector($data, $path, $depth)) {
                if ($path[$depth] == self::WILD_CARD) {
                    $isNumeric = array_keys($data) === range(0, count($data) - 1);
                    foreach ($data as $key => $value) {
                        if ($this->matchesSelector($value, $selector['selector'], $selector['argument'])) {
                            unset($data[$key]);
                        }
                    }
                    if ($isNumeric) {
                        // reset array keys to sequential values
                        $data = array_values($data);
                    }
                } elseif ($this->matchesSelector($data[$path[$depth]], $selector['selector'], $selector['argument'])) {
                    $key = $path[$depth];
                    if (!array_key_exists($key, $data)) {
                        return false;
                    }
                    unset($data[$key]);
                }
            } else {
                $key = $path[$depth];
                if (!array_key_exists($key, $data)) {
                    return false;
                }
                unset($data[$key]);
            }

            return true;
        } elseif ($path[$depth] == self::WILD_CARD) {
            $return = false;
            foreach ($data as $key => $value) {
                if ($this->removeKeys($data[$key], $path, $depth+1)) {
                    $return = true;
                }
            }

            return $return;
        } elseif (array_key_exists($path[$depth], $data)) {
            return $this->removeKeys($data[$path[$depth]], $path, $depth+1);
        }

        return false;
    }

    protected function parseSelector($data, &$path, $depth)
    {
        if (1 !== substr_count($path[$depth], self::SELECTOR_DELIMITER)) {
            return array(); // no selector added
        }
        list($path[$depth], $selector) = explode(self::SELECTOR_DELIMITER, $path[$depth]);
        if (!preg_match('/(?<selector>\w+)\((?<argument>.*)\)/', $selector, $matches)) {
            throw new \Exception('Invalid selector');
        }

        return array('selector' => $matches['selector'], 'argument' => $matches['argument']);
    }

    protected function matchesSelector($data, $selector, $argument)
    {
        switch ($selector) {
            case 'has':
                if (!preg_match('/(?<path>\w+)(?<operand>=|!=)(?<value>.+)/', $argument, $matches)) {
                    throw new \Exception('Invalid selector argument');
                }
                $selectorPath = explode(self::PATH_DELIMITER, $matches['path']); // path is in array format
                $value = json_decode($matches['value']); // ensure we cast values properly
                switch ($matches['operand']) {
                    case '=':
                        return $this->getValue($data, $selectorPath) === $value;
                    case '!=':
                        return $this->getValue($data, $selectorPath) !== $value;
                }
                throw new \Exception(sprintf('Invalid operand: %s', $matches['operand']));

            case 'equals':
                return $data === json_decode($argument);

            default:
                throw new \Exception(sprintf('Invalid selector type: %s', $selector));
        }
    }

}
