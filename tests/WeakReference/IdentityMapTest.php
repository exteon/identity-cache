<?php

    namespace Test\Exteon\IdentityCache\WeakReference;

    use Exteon\IdentityCache\WeakReference\IdentityMap;
    use Test\Exteon\IdentityCache\AbstractWeakrefIdentityMapTest;
    use WeakReference;

    class IdentityMapTest extends AbstractWeakrefIdentityMapTest {
        protected function getTestedClass(): string {
            return IdentityMap::class;
        }

        protected function setUp(): void {
            if (!class_exists(WeakReference::class)) {
                $this->markTestSkipped('Extension Weakreference is not present');
            }
            parent::setUp();
            $this->identity = $this->getIdentity();
        }
    }
