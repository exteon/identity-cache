<?php
    /**
     * @package Exteon\IdentityCache
     * @author Dinu Marina
     * @link https://github.com/exteon/identity-cache
     * @license https://www.apache.org/licenses/LICENSE-2.0
     */

    use Exteon\IdentityCache\IIdentityCache;

    abstract class AbstractIdentityCacheTest extends AbstractIdentityMapTest {

        /** @var IIdentityCache */
        protected $identity;

        /**
         * @param mixed ...$args
         * @return IIdentityCache
         */
        protected function getIdentity(...$args): object {
            $className = $this->getTestedClass();

            return new $className(...$args);
        }
    }