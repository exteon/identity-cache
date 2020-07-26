<?php
    /**
     * @package Exteon\IdentityCache
     * @author Dinu Marina
     * @link https://github.com/exteon/identity-cache
     * @license https://www.apache.org/licenses/LICENSE-2.0
     */

    namespace NativeArray;

    use AbstractIdentityMapTest;
    use Exteon\IdentityCache\NativeArray\IdentityMap;

    class IdentityMapTest extends AbstractIdentityMapTest {
        protected function getTestedClass(): string {
            return IdentityMap::class;
        }

        protected function setUp(): void {
            parent::setUp();
            $this->identity=$this->getIdentity();
        }
    }
