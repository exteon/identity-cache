<?php
    /**
     * @package Exteon\IdentityCache
     * @author Dinu Marina
     * @link https://github.com/exteon/identity-cache
     * @license https://www.apache.org/licenses/LICENSE-2.0
     */

    namespace Exteon\IdentityCache;

    interface IIdentityCache extends IIdentityMap {
        /**
         * Garbage collects non-acquired entities
         */
        public function gc(): void;
    }