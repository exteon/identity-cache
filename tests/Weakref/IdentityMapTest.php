<?php

    namespace Weakref;

    use AbstractIdentityMapTest;
    use Exteon\IdentityCache\WeakRef\IdentityMap;
    use stdClass;

    class IdentityMapTest extends AbstractIdentityMapTest {
        protected function getTestedClass(): string {
            return IdentityMap::class;
        }

        protected function setUp(): void {
            if (!class_exists('Weakref')) {
                $this->markTestSkipped('Extension Weakref is not present');
            }
            parent::setUp();
            $this->identity = $this->getIdentity();
        }

        public function testCanUse() {
            self::assertTrue($this->identity::canUse());
        }

        public function testWeakref(): void {
            $object = new stdClass();
            $this->identity[self::TRY_KEY] = $object;
            unset($object);
            self::assertFalse(isset($this->identity[self::TRY_KEY]));
        }
    }
