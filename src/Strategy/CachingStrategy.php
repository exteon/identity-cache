<?php
    /**
     * @package Exteon\IdentityCache
     * @author Dinu Marina
     * @link https://github.com/exteon/identity-cache
     * @license https://www.apache.org/licenses/LICENSE-2.0
     */

    namespace Exteon\IdentityCache\Strategy;

    use Exception;
    use Exteon\MemoryHelper;
    use InvalidArgumentException;

    /**
     * The WeakRef IdentityCache implements, in addition to {@see IdentityMap},
     * caching of unused instances, to optimize performance for subsequent
     * fetches of the same identity. The cache size can be limited via the
     * configuration options, and the caching strategy can include
     * popularity-based caching.
     *
     * @package Exteon\IdentityCache\WeakRef
     */
    trait CachingStrategy {

        /** @var array */
        private $collectionGcHold = [];

        /** @var array */
        private $config = [
            'trigger' => 'maxRetainedObjects',
            'maxRetainedObjects' => 1000,
            'maxScriptMemory' => '64M',
            'purgePressure' => 10,
            'purgeStrategy' => 'popularity',
        ];

        /** @var int */
        private $trigger;

        /** @var int */
        private $purgeStrategy;

        /**
         * For the garbage collector, this collection holds the objects
         * popularity (number of instantiations from the DB) for every object.
         * Object ids are keys.
         *
         * @var int[]
         */
        private $objectsPopularity = [];

        /** @var int[] */
        private $objectsPopularityDecayUpdated = [];

        /** @var int */
        private $objectsPopularityDecayCounter = 0;

        /**
         * IdentityCache constructor.
         *
         * @param array $config
         * The config parameter can have the following keys/values. If a setting
         * is not specified, its default will be used that is listed below.
         * <br />
         * <ul>
         *      <li>'trigger' => 'maxRetainedObjects'<br>
         *          Specifies the condition that triggers the cache to purge
         *          unused references.<br>
         *          <br>
         *          Possible values:<ul>
         *              <li>'maxRetainedObjects' :<br>
         *                  purging will be initiated when we are caching a
         *                  number of references greater than the
         *                  maxRetainedObjects parameter<br><br></li>
         *              <li>'maxScriptMemory' :<br>
         *                  purging will be initiated when the total memory
         *                  consumed by the running script is greater than the
         *                  value specified in the maxScriptMemory parameter.
         *                  <br><br></li>
         *              <li>'none' :<br>
         *                  purging will not be done. This can be changed later
         *                  by using the {@see setConfig()} method.
         *                  <br></li></ul></li>
         *      <li>'maxRetainedObjects' => 1000<br>
         *          Number of objects that can be retained before purging if
         *          trigger is set to 'maxRetainedObjects'<br><br></li>
         *      <li>'maxScriptMemory' => '64M'<br>
         *          Maximum total memory consumed by the running script before
         *          purging if trigger is 'maxScriptMemory'. Format is the same
         *          as php.ini's sizes// format.<br><br></li>
         *      <li>'purgePressure' => 10<br>
         *          When purging, which percent of the cached objects to purge?
         *          <br><br></li>
         *      <li>'purgeStrategy' => 'popularity'<br>
         *          When purging, how to select which instances to keep?<br>
         *          <br>
         *          Possible values:<ul>
         *              <li>'popularity' :<br>
         *                  Keep the most popular objects. See the following
         *                  section for more details on how popularity-based
         *                  caching works.<br><br></li>
         *              <li>'random' :<br>
         *                  Randomly purge the number of objects dictated by
         *                  purgePressure.<br></li></ul></li>
         *      <li>'popularityDecay' => self::getPopularityDecay(1000, 10000, 2)
         *          <br>
         *          See {@see getPopularityDecay()} for an explanation of this
         *          value
         *          <br><br></li></ul>
         *
         * @throws Exception
         */
        public function __construct(array $config = []) {
            $this->setConfig($config);
        }

        /**
         * @param bool|float|int|string $offset
         * @return mixed|null
         * @throws Exception
         */
        public function offsetGet($offset) {
            $result = parent::offsetGet($offset);
            if ($result !== null) {
                if (
                    !array_key_exists(
                        $offset,
                        $this->collectionGcHold
                    ) &&
                    !$this->checkGcTrigger()
                ) {
                    $this->collectionGcHold[$offset] = $result;
                }
                if ($this->purgeStrategy === Constants::PURGE_STRATEGY_POPULARITY) {
                    $this->increasePopularity($offset);
                }
            }

            return $result;
        }

        /**
         * @param bool|float|int|string $offset
         * @param mixed $value
         * @throws Exception
         */
        public function offsetSet($offset, $value): void {
            parent::offsetSet($offset, $value);
            $this->collectionGcHold[$offset] = $value;
            if ($this->purgeStrategy === Constants::PURGE_STRATEGY_POPULARITY) {
                $this->increasePopularity($offset);
                $this->increasePopularity($offset);
            }
            $this->gc();
        }

        /**
         * @param bool|float|int|string $offset
         */
        public function offsetUnset($offset): void {
            parent::offsetUnset($offset);
            $this->releaseHold($offset);
            $this->release($offset);
            unset($this->objectsPopularity[$offset]);
            unset($this->objectsPopularityDecayUpdated[$offset]);
        }

        /**
         * @param bool|float|int|string $offset
         */
        protected function releaseHold($offset): void {
            unset($this->collectionGcHold[$offset]);
        }

        /**
         * @return bool
         * @throws Exception
         */
        protected function checkGcTrigger(): bool {
            switch ($this->trigger) {
                case Constants::TRIGGER_RETAINED_OBJECTS:
                    return (
                        count($this->collectionGcHold)
                        >
                        $this->config['maxRetainedObjects']
                    );
                case Constants::TRIGGER_SCRIPT_MEMORY:
                    return (
                        memory_get_usage()
                        >
                        MemoryHelper::bytesFromPhpIniString(
                            $this->config['maxScriptMemory']
                        )
                    );
                case Constants::TRIGGER_NONE:
                    return false;
                default:
                    throw new Exception('Invalid trigger value');
            }
        }

        /**
         * @throws Exception
         */
        public function gc(): void {
            if ($this->checkGcTrigger()) {
                $this->purge();
            }
        }

        protected function purge(): void {
            switch ($this->purgeStrategy) {
                case Constants::PURGE_STRATEGY_POPULARITY:
                    foreach ($this->collectionGcHold as $offset => $value) {
                        $this->decay($offset);
                    }
                    uksort(
                        $this->collectionGcHold,
                        function ($offset1, $offset2) {
                            $pop1 = $this->objectsPopularity[$offset1];
                            $pop2 = $this->objectsPopularity[$offset2];
                            if ($pop1 == $pop2) {
                                return 0;
                            }
                            if ($pop1 < $pop2) {
                                return -1;
                            }

                            return 1;
                        }
                    );
                    $num = ceil(
                        count($this->collectionGcHold)
                        * $this->config['purgePressure'] / 100
                    );
                    reset($this->collectionGcHold);
                    for ($i = 0; $i < $num; $i++) {
                        $this->releaseHold(key($this->collectionGcHold));
                    }
                    break;
                case Constants::PURGE_STRATEGY_RANDOM:
                    $offsets = array_keys($this->collectionGcHold);
                    shuffle($offsets);
                    $num = ceil (
                        count($this->collectionGcHold)
                        * $this->config['purgePressure'] / 100
                    );
                    $offset = reset($offsets);
                    for ($i = 0; $i < $num; $i++) {
                        $this->releaseHold($offset);
                        $offset = next($offsets);
                    }
                    break;
            }
        }

        /**
         * Replace config options with new values specified in the $config
         * array. For a description of the available config options, see
         * {@see __construct()}
         *
         * @param array $config
         * @throws Exception
         */
        public function setConfig(array $config): void {
            $this->config = array_merge(
                $this->config,
                $config
            );
            switch ($this->config['trigger']) {
                case 'maxRetainedObjects':
                    $this->trigger = Constants::TRIGGER_RETAINED_OBJECTS;
                    break;
                case 'maxScriptMemory':
                    $this->trigger = Constants::TRIGGER_SCRIPT_MEMORY;
                    break;
                case 'none':
                    $this->trigger = Constants::TRIGGER_NONE;
                    break;
                default:
                    throw new Exception('Unknown gc trigger');
            }
            switch ($this->config['purgeStrategy']) {
                case 'popularity':
                    $this->purgeStrategy = Constants::PURGE_STRATEGY_POPULARITY;
                    if (!($this->config['popularityDecay'] ?? null)) {
                        $this->config['popularityDecay'] =
                            $this->getPopularityDecay();
                    }
                    break;
                case 'random':
                    $this->purgeStrategy = Constants::PURGE_STRATEGY_RANDOM;
                    break;
                default:
                    throw new Exception('Unknown purge strategy');
            }
        }

        /**
         * @param bool|float|int|string $offset
         */
        protected function increasePopularity($offset): void {
            if (!array_key_exists($offset, $this->objectsPopularity)) {
                $this->objectsPopularity[$offset] = 0;
                $this->objectsPopularityDecayUpdated[$offset] =
                    $this->objectsPopularityDecayCounter;
            }
            $this->objectsPopularity[$offset]++;
            $this->objectsPopularityDecayUpdated[$offset]++;
            $this->objectsPopularityDecayCounter++;
        }

        /**
         * @param bool|float|int|string $offset
         */
        protected function decay($offset): void {
            $decaySpan =
                $this->objectsPopularityDecayCounter
                - $this->objectsPopularityDecayUpdated[$offset];
            $this->objectsPopularity[$offset] =
                $this->objectsPopularity[$offset] *
                exp(
                    $decaySpan
                    * log(1 - $this->config['popularityDecay'])
                );
            $this->objectsPopularityDecayUpdated[$offset] =
                $this->objectsPopularityDecayCounter;
        }

        /**
         * Gets the configuration options currently in effect.
         * @see __construct()
         *
         * @return array
         */
        public function getConfig(): array {
            return $this->config;
        }

        /**
         * When the 'purgeStrategy' config option is 'popularity', a popularity
         * index will be kept for the cached objects. An object's popularity
         * increases by 1 whenever it is accessed from the cache.<br>
         * <br>
         * In order to not keep forever objects that were once very popular but
         * have not been used in a long time, an object's popularity also decays
         * exponentially every time another object is accessed from the cache.
         * <br>
         * <br>
         * The value of the decay parameter can be specified by the
         * 'popularityDecay' config option. This is a very sensitive float value
         * that can be computed using this function.<br>
         * <br>
         * The meaning of the parameters is:<br>
         * <br>
         * If an object has at the present moment a popularity of
         * $initialPopularity, then other objects are accessed from the cache a
         * number of $rounds times, the popularity of the initial object will
         * drop to $targetPopularity. By tweaking the value of
         * 'popularityDecay', you can balance between keeping in the cache more
         * of formerly popular objects or more of recent objects.
         *
         * @see __construct()
         *
         * @param float $initialPopularity
         * @param int $rounds
         * @param float $targetPopularity
         * @return float
         */
        public static function getPopularityDecay(
            float $initialPopularity = 1000,
            int $rounds = 10000,
            float $targetPopularity = 2
        ): float {
            if($targetPopularity <= 1){
                throw new InvalidArgumentException('targetPopularity cannot be <= 1');
            }
            if($targetPopularity > $initialPopularity){
                throw new InvalidArgumentException('targetPopularity cannot be > initialPopularity');
            }
            if($rounds <= 0){
                throw new InvalidArgumentException('rounds must be > 0');
            }
            if($targetPopularity == $initialPopularity){
                return 0;
            }
            $dLow = 0;
            $dHigh = 1;
            $d = ($dLow + $dHigh) / 2;
            do {
                $lastD = $d;
                $pop =
                    $initialPopularity
                    * exp(
                        $rounds
                        * log(1 - $d)
                    );
                if ($pop < $targetPopularity) {
                    $dHigh = $d;
                    $d = ($dLow + $d) / 2;
                } else {
                    $dLow = $d;
                    $d = ($dHigh + $d) / 2;
                }
            } while (
                $d != $lastD &&
                $d != $dLow &&
                $d != $dHigh
            );

            return $d;
        }
    }