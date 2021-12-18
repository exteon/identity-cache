<?php
    namespace Test\Exteon\IdentityCache\WeakReference;

    use Exteon\IdentityCache\WeakReference\IdentityCache;
    use Test\Exteon\IdentityCache\AbstractWeakrefIdentityCacheTest;
    use WeakReference;

    class IdentityCacheTest extends AbstractWeakrefIdentityCacheTest {

        /** @var IdentityCache */
        protected $identity;

        protected function getTestedClass(): string {
            return IdentityCache::class;
        }

        protected function setUp(): void {
            if (!class_exists(WeakReference::class)) {
                $this->markTestSkipped('Extension Weakreference is not present');
            }
            parent::setUp();
            $this->identity = $this->getIdentity(
                [
                    'trigger' => 'none',
                ]
            );
        }
    }