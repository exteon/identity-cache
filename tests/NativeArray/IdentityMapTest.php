<?php
    /**
     * @package Exteon\IdentityCache
     * @author Dinu Marina
     * @link https://github.com/exteon/identity-cache
     * @license https://www.apache.org/licenses/LICENSE-2.0
     */

    namespace Test\Exteon\IdentityCache\NativeArray;

    use Exteon\IdentityCache\NativeArray\IdentityMap;
    use Test\Exteon\IdentityCache\AbstractIdentityMapTest;

    class IdentityMapTest extends AbstractIdentityMapTest {
        protected function getTestedClass(): string {
            return IdentityMap::class;
        }

        protected function setUp(): void {
            parent::setUp();
            $this->identity=$this->getIdentity();
        }
    }
