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

    interface IIdentityMap extends ArrayAccess, Iterator {
        /**
         * Prevents the key from being garbage collected
         *
         * @param bool|float|int|string $offset
         * @return mixed
         */
        public function acquire(string $offset): void;

        /**
         * Releases a key previously acquired via {@see acquire()} back to the
         * garbage collector
         *
         * @param bool|float|int|string $offset
         * @return mixed
         */
        public function release(string $offset): void;
    }