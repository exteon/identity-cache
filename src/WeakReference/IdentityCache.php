<?php

    namespace Exteon\IdentityCache\WeakReference;

    use Exteon\IdentityCache\IIdentityCache;
    use Exteon\IdentityCache\Strategy\CachingStrategy;

    class IdentityCache extends AIdentityMap implements IIdentityCache {
        use CachingStrategy;
    }