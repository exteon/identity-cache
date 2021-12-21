<?php
    /**
     * @package Exteon\IdentityCache
     * @author Dinu Marina
     * @link https://github.com/exteon/identity-cache
     * @license https://www.apache.org/licenses/LICENSE-2.0
     */

    namespace Exteon\IdentityCache\WeakReference;

    use Exteon\IdentityCache\IIdentityMap;
    use WeakReference;

    abstract class AIdentityMap implements IIdentityMap
    {

        /** @var array<WeakReference|mixed> */
        private array $collection = [];

        /** @var bool */
        private bool $isStart = true;

        /** @var bool */
        private bool $isEnd = false;

        private array $acquired = [];

        /**
         * Checks if the Weakref map can be used. If the Weakref extension is
         * not present, you can fall back to the NativeArray equivalents.
         *
         * @return bool
         */
        public static function canUse(): bool
        {
            return class_exists(WeakReference::class);
        }

        /**
         * @param bool|float|int|string $offset
         * @return bool
         */
        public function offsetExists($offset): bool
        {
            return (
                array_key_exists($offset, $this->collection) &&
                $this->validateOffset($offset)
            );
        }

        /**
         * @param bool|float|int|string $offset
         * @return bool
         */
        protected function validateOffset($offset): bool
        {
            if (!array_key_exists($offset, $this->collection)) {
                return false;
            }
            $isValid = static::isValidItem($this->collection[$offset]);
            if (!$isValid) {
                $this->offsetUnset($offset);
            }

            return $isValid;
        }

        /**
         * @param mixed $item
         * @return bool
         */
        protected static function isValidItem($item): bool
        {
            if ($item instanceof WeakReference) {
                return ($item->get() !== null);
            }

            return true;
        }

        /**
         * @param bool|float|int|string $offset
         */
        public function offsetUnset($offset): void
        {
            unset($this->collection[$offset]);
        }

        /**
         * @param bool|float|int|string $offset
         * @return mixed|null
         */
        public function offsetGet($offset)
        {
            if (!array_key_exists($offset, $this->collection)) {
                static::raiseUndefinedIndexError($offset);

                return null;
            }
            $item = $this->collection[$offset];
            if (static::isValidItem($item)) {
                return static::formatItem($item);
            }
            $this->offsetUnset($offset);

            static::raiseUndefinedIndexError($offset);

            return null;
        }

        /**
         * @param bool|float|int|string $offset
         */
        protected static function raiseUndefinedIndexError($offset): void
        {
            $version = static::getPhpVersion();
            if ($version['major'] >= 8) {
                trigger_error('Undefined index: ' . $offset, E_USER_WARNING);
            } else {
                trigger_error('Undefined index: ' . $offset, E_USER_NOTICE);
            }
        }

        /**
         * @return array An array with the following keys:<br>
         * <ul>
         *      <li>'version' =><br>
         *          The PHP full verion number<br><br></li>
         *      <li>'major' =><br>
         *          The PHP major verion number<br><br></li>
         *      <li>'minor' =><br>
         *          The PHP minor verion number<br><br></li>
         *      <li>'patch' =><br>
         *          The PHP patch verion number<br><br></li>
         * </ul>
         */
        protected static function getPhpVersion(): array
        {
            $result['version'] = phpversion();
            [
                $result['major'],
                $result['minor'],
                $result['patch'],
            ] = explode('.', $result['version']);

            return $result;
        }

        /**
         * @param mixed $item
         * @return mixed
         */
        protected static function formatItem($item)
        {
            if ($item instanceof WeakReference) {
                return $item->get();
            }

            return $item;
        }

        /**
         * @param bool|float|int|string $offset
         * @param mixed $value
         */
        public function offsetSet($offset, $value): void
        {
            if (is_object($value)) {
                $this->collection[$offset] = WeakReference::create($value);
            } else {
                $this->collection[$offset] = $value;
            }
        }

        /**
         * @return float|int|string|null
         */
        public function key()
        {
            if ($this->isStart) {
                $this->rewind();
            }

            return key($this->collection);
        }

        public function rewind(): void
        {
            $this->isStart = false;
            reset($this->collection);
            $this->nextValid();
        }

        protected function nextValid(): void
        {
            $this->isEnd = true;
            do {
                $offset = key($this->collection);
                if ($offset === null) {
                    break;
                }
                if (static::validateOffset($offset)) {
                    $this->isEnd = false;
                    break;
                }
                next($this->collection);
            } while (true);
        }

        public function next(): void
        {
            if ($this->isStart) {
                $this->rewind();
            }
            next($this->collection);
            $this->nextValid();
        }

        /**
         * @return bool|mixed
         */
        public function current()
        {
            if ($this->isStart) {
                $this->rewind();
            }
            if ($this->isEnd) {
                return false;
            }

            return static::formatItem(current($this->collection));
        }

        /**
         * @return bool
         */
        public function valid(): bool
        {
            if ($this->isStart) {
                $this->rewind();
            }

            return !$this->isEnd;
        }

        /**
         * @param bool|float|int|string $offset
         */
        public function acquire($offset): void
        {
            if (array_key_exists($offset, $this->collection)) {
                $this->acquired[$offset] = $this->collection[$offset];
            }
        }

        /**
         * @param bool|float|int|string $offset
         */
        public function release($offset): void
        {
            if (array_key_exists($offset, $this->acquired)) {
                unset($this->acquired[$offset]);
            }
        }

        public function getKeys(): array
        {
            return array_keys($this->collection);
        }
    }