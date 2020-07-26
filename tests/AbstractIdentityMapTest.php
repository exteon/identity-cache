<?php
    /**
     * @package Exteon\IdentityCache
     * @author Dinu Marina
     * @link https://github.com/exteon/identity-cache
     * @license https://www.apache.org/licenses/LICENSE-2.0
     */

    use Exteon\IdentityCache\IIdentityMap;
    use PHPUnit\Framework\TestCase;

    abstract class AbstractIdentityMapTest extends TestCase {
        const TRY_KEY = 'foo';

        /** @var IIdentityMap */
        protected $identity;

        public function testSet(): void {
            $object = new stdClass();
            $this->identity[static::TRY_KEY] = $object;
            self::assertSame($object, $this->identity[static::TRY_KEY]);
        }

        public function testUnset(): void {
            $object = new stdClass();
            $this->identity[static::TRY_KEY] = $object;
            unset($this->identity[self::TRY_KEY]);
            self::assertFalse(isset($this->identity[self::TRY_KEY]));
        }

        protected abstract function getTestedClass(): string;

        /**
         * @param mixed ...$args
         * @return IIdentityMap
         */
        protected function getIdentity(...$args): object {
            $className = $this->getTestedClass();

            return new $className(...$args);
        }
    }
