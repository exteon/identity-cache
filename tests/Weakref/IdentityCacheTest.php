<?php
    namespace Test\Exteon\IdentityCache\Weakref;

    use Exteon\IdentityCache\WeakRef\IdentityCache;
    use Test\Exteon\IdentityCache\AbstractWeakrefIdentityCacheTest;

    class IdentityCacheTest extends AbstractWeakrefIdentityCacheTest {

        /** @var IdentityCache */
        protected $identity;

        protected function getTestedClass(): string {
            return IdentityCache::class;
        }

        protected function setUp(): void {
            if (!class_exists('Weakref')) {
                $this->markTestSkipped('Extension Weakref is not present');
            }
            parent::setUp();
            $this->identity = $this->getIdentity(
                [
                    'trigger' => 'none',
                ]
            );
        }
    }