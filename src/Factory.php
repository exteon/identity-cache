<?php

    namespace Exteon\IdentityCache;

    use WeakRef;
    use WeakReference;

    abstract class Factory
    {
        public static function getIdentityMap(): IIdentityMap
        {
            if (class_exists(WeakReference::class)) {
                return new \Exteon\IdentityCache\WeakReference\IdentityMap();
            }
            if (class_exists(WeakRef::class)) {
                return new \Exteon\IdentityCache\WeakRef\IdentityMap();
            }
            return new \Exteon\IdentityCache\NativeArray\IdentityMap();
        }

        public static function getIdentityCache(array $config = []): IIdentityCache
        {
            if (class_exists(WeakReference::class)) {
                return new \Exteon\IdentityCache\WeakReference\IdentityCache($config);
            }
            if (class_exists(WeakRef::class)) {
                return new \Exteon\IdentityCache\WeakRef\IdentityCache($config);
            }
            return new \Exteon\IdentityCache\NativeArray\IdentityCache($config);
        }
    }