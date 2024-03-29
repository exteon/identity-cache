<?php

    namespace Test\Exteon\IdentityCache\Weakref;

    use Exteon\IdentityCache\WeakRef\IdentityMap;
    use Test\Exteon\IdentityCache\AbstractWeakrefIdentityMapTest;

    class IdentityMapTest extends AbstractWeakrefIdentityMapTest {
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
    }
