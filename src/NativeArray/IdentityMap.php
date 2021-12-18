<?php
    /**
     * @package Exteon\IdentityCache
     * @author Dinu Marina
     * @link https://github.com/exteon/identity-cache
     * @license https://www.apache.org/licenses/LICENSE-2.0
     */

    namespace Exteon\IdentityCache\NativeArray;

    use ArrayIterator;
    use Exteon\IdentityCache\IIdentityMap;

    class IdentityMap extends ArrayIterator implements IIdentityMap
    {

        /**
         * IdentityMap constructor.
         */
        public function __construct()
        {
            parent::__construct();
        }

        function acquire($offset): void
        {
        }

        function release($offset): void
        {
        }

        public function getKeys(): array
        {
            return array_keys((array)$this);
        }
    }