<?php
    /**
     * @package Exteon\IdentityCache
     * @author Dinu Marina
     * @link https://github.com/exteon/identity-cache
     * @license https://www.apache.org/licenses/LICENSE-2.0
     */

    namespace Test\Exteon\IdentityCache\NativeArray;

    use Exteon\IdentityCache\NativeArray\IdentityCache;
    use stdClass;
    use Test\Exteon\IdentityCache\AbstractIdentityCacheTest;

    class IdentityCacheTest extends AbstractIdentityCacheTest {

        protected function getTestedClass(): string {
            return IdentityCache::class;
        }

        protected function setUp(): void {
            parent::setUp();
            $this->identity = $this->getIdentity();
        }

        public function testGcDoesNothing(): void {
            $object = new stdClass();
            $this->identity[self::TRY_KEY] = $object;
            $this->identity->gc();
            self::assertSame($object, $this->identity[self::TRY_KEY]);
        }
    }
