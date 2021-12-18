<?php
    /**
     * @package Exteon\IdentityCache
     * @author Dinu Marina
     * @link https://github.com/exteon/identity-cache
     * @license https://www.apache.org/licenses/LICENSE-2.0
     */

    namespace Exteon\IdentityCache;

    use ArrayAccess;
    use Iterator;
    use ReturnTypeWillChange;

    interface IIdentityMap extends ArrayAccess, Iterator {
        /**
         * Prevents the key from being garbage collected
         *
         * @param bool|float|int|string $offset
         * @return mixed
         */
        public function acquire($offset): void;

        /**
         * Releases a key previously acquired via {@see acquire()} back to the
         * garbage collector
         *
         * @param bool|float|int|string $offset
         * @return mixed
         */
        public function release($offset): void;

        /**
         * @return array<bool|float|int|string>
         */
        public function getKeys(): array;
    }