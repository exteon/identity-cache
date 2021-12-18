<?php

    namespace Test\Exteon\IdentityCache;

    use Exception;
    use stdClass;

    abstract class AbstractWeakrefIdentityCacheTest extends AbstractIdentityCacheTest
    {
        public function testAcquire(): void {
            $object = new stdClass();
            $this->identity[self::TRY_KEY] = $object;
            $this->identity->acquire(self::TRY_KEY);
            unset($object);
            self::assertInstanceOf(
                stdClass::class,
                $this->identity[self::TRY_KEY]
            );
        }

        /**
         * @throws Exception
         */
        public function testGcSingleElement() {
            $this->identity->setConfig(
                [
                    'trigger' => 'maxRetainedObjects',
                    'maxRetainedObjects' => 0,
                    'purgePressure' => 100,
                    'purgeStrategy' => 'random',
                ]
            );
            $this->identity[self::TRY_KEY] = new stdClass();
            self::assertFalse(isset($this->identity[self::TRY_KEY]));
        }

        /**
         * @throws Exception
         */
        public function testGcPurgePressure() {
            $NUMBER = 10000;
            $PRESSURE = 10;

            $remaining = $NUMBER * (100 - $PRESSURE) / 100;
            self::assertIsInt($remaining);
            $this->identity->setConfig(
                [
                    'trigger' => 'maxRetainedObjects',
                    'maxRetainedObjects' => $NUMBER - 1,
                    'purgePressure' => 10,
                    'purgeStrategy' => 'random',
                ]
            );
            for ($i = 0; $i < $NUMBER; $i++) {
                $this->identity[$i] = new stdClass();
            }
            $actualRemaining = 0;
            for ($i = 0; $i < $NUMBER; $i++) {
                if (isset($this->identity[$i])) {
                    $actualRemaining++;
                }
            }
            self::assertEquals($remaining, $actualRemaining);
        }

        /**
         * @throws Exception
         */
        public function testGcPurgePopularityLower() {
            $this->identity->setConfig(
                [
                    'trigger' => 'maxRetainedObjects',
                    'maxRetainedObjects' => 3,
                    'purgePressure' => 50,
                    'purgeStrategy' => 'popularity',
                    'popularityDecay' => $this->identity->getPopularityDecay(
                        1000,
                        1001,
                        2
                    ),
                ]
            );
            $this->identity[1] = new stdClass();
            for ($i = 0; $i < 998; $i++) {
                $this->identity[1];
            }
            $this->identity[0] = new stdClass();
            for ($i = 0; $i < 998; $i++) {
                $this->identity[0];
            }
            $this->identity[2] = new stdClass();
            unset($this->identity[0]);
            $this->identity->setConfig(
                [
                    'maxRetainedObjects' => 1,
                ]
            );

            $this->identity->gc();
            self::assertFalse(isset($this->identity[1]));
            self::assertNotNull($this->identity[2]);
        }

        /**
         * @throws Exception
         */
        public function testGcPurgePopularityHigher() {
            $this->identity->setConfig(
                [
                    'trigger' => 'maxRetainedObjects',
                    'maxRetainedObjects' => 3,
                    'purgePressure' => 50,
                    'purgeStrategy' => 'popularity',
                    'popularityDecay' => $this->identity->getPopularityDecay(
                        1000,
                        1003,
                        2
                    ),
                ]
            );
            $this->identity[1] = new stdClass();
            for ($i = 0; $i < 998; $i++) {
                $this->identity[1];
            }
            $this->identity[0] = new stdClass();
            for ($i = 0; $i < 998; $i++) {
                $this->identity[0];
            }
            $this->identity[2] = new stdClass();
            unset($this->identity[0]);
            $this->identity->setConfig(
                [
                    'maxRetainedObjects' => 1,
                ]
            );

            $this->identity->gc();
            self::assertNotNull($this->identity[1]);
            self::assertFalse(isset($this->identity[2]));
        }

        /**
         * @throws Exception
         */
        public function testMemoryTrigger() {
            $this->identity[1] = new stdClass();
            $this->identity->setConfig(
                [
                    'trigger' => 'maxScriptMemory',
                    'maxScriptMemory' => 0,
                    'purgePressure' => 100,
                ]
            );
            $this->identity[2] = new stdClass();
            self::assertFalse(isset($this->identity[1]));
            self::assertFalse(isset($this->identity[2]));
        }

        /**
         * @depends testMemoryTrigger
         * @throws Exception
         */
        public function testPurgesExcessItems() {
            $this->identity[1] = new stdClass();
            $this->identity->setConfig(
                [
                    'trigger' => 'maxScriptMemory',
                    'maxScriptMemory' => 0,
                    'purgePressure' => 51,
                ]
            );
            $this->identity[2] = new stdClass();
            self::assertFalse(isset($this->identity[1]));
            self::assertFalse(isset($this->identity[2]));
        }
    }