<?php
    /**
     * @package Exteon\IdentityCache
     * @author Dinu Marina
     * @link https://github.com/exteon/identity-cache
     * @license https://www.apache.org/licenses/LICENSE-2.0
     */

    namespace Test\Exteon\IdentityCache;

    use Exteon\IdentityCache\WeakRef\IdentityMap;
    use PHPUnit\Framework\TestCase;
    use \Exteon\IdentityCache\WeakRef\IdentityCache;
    use stdClass;

    class documentationTest extends TestCase {

        protected function setUp(): void {
            if (!class_exists('Weakref')) {
                $this->markTestSkipped('Extension Weakref is not present');
            }
            parent::setUp();
        }

        public function testSnippet1():void {
            $map = new IdentityMap();

            $instance = new stdClass();
            $map[1] = $instance;

            // While object is in use via $instance, the map will provide a reference to it
            // via its id.

            self::assertTrue($map[1] === $instance);

            // Put object out of use by releasing its reference

            unset($instance);

            // Object has been freed and its reference unset, because all references to it
            // were released

            self::assertTrue(isset($map[1]) === false);
        }

        public function testSnippet2(): void {
            $cache = new IdentityCache([
                'trigger' => 'maxRetainedObjects',
                'maxRetainedObjects' => 1,
                'purgeStrategy' => 'popularity',
                'purgePressure' => 50
            ]);

            // Create 2 object instances

            $instance1 = new stdClass();
            $instance2 = new stdClass();

            $cache[1] = $instance1;
            $cache[2] = $instance2;

            // While objects are in use via $instance1, $instance2, the map holds references
            // to them via their ids

            self::assertTrue($cache[1] === $instance1);
            self::assertTrue($cache[1] === $instance1);

            // Increase instance 1's popularity by accessing it repeatedly

            $cache[1];
            $cache[1];
            $cache[1];

            // Put objects out of use by releasing their references

            unset($instance1);
            unset($instance2);

            // maxRetainedObjects was configured to 1; instances will be purged

            $cache->gc();

            // instance 1's popularity is greater so it will be preserved in the cache

            self::assertTrue(is_object($cache[1]));

            // instance 2 was of a lesser popularity so it was freed

            self::assertTrue(isset($cache[2]) === false);
        }

        /**
         * @doesNotPerformAssertions
         */
        public function testSnippet3(): void {
            $cache = new IdentityCache([
                //  Specifies the condition that triggers the cache to purge unused
                //  references.
                //  Possible values:
                //      'maxRetainedObjects'    :   purging will be initiated when we are
                //                                  caching a number of references greater
                //                                  than the maxRetainedObjects parameter
                //      'maxScriptMemory'       :   purging will be initiated when the total
                //                                  memory consumed by the running script is
                //                                  greater than the value specified in the
                //                                  maxScriptMemory parameter.
                //      'none'                  :   purging will not be done. This can be
                //                                  changed later by using the setSettings()
                //                                  method.
                'trigger' => 'maxRetainedObjects',

                //  Number of objects that can be retained before purging if trigger is
                //  set to 'maxRetainedObjects'
                'maxRetainedObjects' => 1000,

                //  Maximum total memory consumed by the running script before purging if
                //  trigger is 'maxScriptMemory'. Format is the same as php.ini's sizes//
                //  format.
                'maxScriptMemory' => '64M',

                //  When purging, which percent of the cached objects to purge?
                'purgePressure' => 10,

                //  When purging, how to select which instances to keep?
                //  Possible values:
                //      'popularity'    :   Keep the most popular objects. See the following
                //                          section for more details on how popularity-based
                //                          caching works.
                //      'random'        :   Randomly purge the number of objects dictated
                //                          by purgePressure.
                'purgeStrategy' => 'popularity',

                //  See the Popularity-based caching section for an explanation of this//
                //  parameter
                'popularityDecay' => IdentityCache::getPopularityDecay(1000, 10000, 2)
            ]);
        }

        /**
         * @doesNotPerformAssertions
         */
        public function testSnippet4(): void {
            $popularityDecay = IdentityCache::getPopularityDecay(
                1000,   //  initialPopularity
                10000,  //  rounds
                2       //  targetPopularity
            );
        }

        public function testSnippet5(): void {
            $cache = new IdentityCache([
                'trigger' => 'maxRetainedObjects',
                'maxRetainedObjects' => 0
            ]);

            $instance = new stdClass();
            $cache[1] = $instance;

            $instance->foo = 'bar';
            $cache->acquire(1);

            //  The acquired identity with id 1 will never be purged, even if the cache's
            //  purge strategy is triggered by number of objects or memory trigger

            unset($instance);
            $cache->gc();
            self::assertTrue(
                is_object($cache[1]) &&
                $cache[1]->foo === 'bar'
            );

            //  Acquired identities can be released and then they can be purged if no longer
            //  in use:

            $cache->release(1);
            $cache->gc();
            self::assertTrue(isset($cache[1]) === false);
        }
    }