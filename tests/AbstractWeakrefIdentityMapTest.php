<?php

    namespace Test\Exteon\IdentityCache;

    use stdClass;

    abstract class AbstractWeakrefIdentityMapTest extends AbstractIdentityMapTest
    {
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