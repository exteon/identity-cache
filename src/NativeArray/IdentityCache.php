<?php
    /**
     * @package Exteon\IdentityCache
     * @author Dinu Marina
     * @link https://github.com/exteon/identity-cache
     * @license https://www.apache.org/licenses/LICENSE-2.0
     */

    namespace Exteon\IdentityCache\NativeArray;

    use ArrayIterator;
    use Exteon\IdentityCache\IIdentityCache;

    class IdentityCache extends ArrayIterator implements IIdentityCache
    {

        /**
         * IdentityCache constructor.
         * @param array $config
         * @noinspection PhpUnusedParameterInspection
         */
        public function __construct(array $config = [])
        {
            parent::__construct();
        }

        function gc(): void
        {
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

        public static function canUse(): bool
        {
            return true;
        }
    }