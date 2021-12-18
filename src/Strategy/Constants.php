<?php

    namespace Exteon\IdentityCache\Strategy;

    class Constants
    {
        public const TRIGGER_NONE = 0;
        public const TRIGGER_RETAINED_OBJECTS = 1;
        public const TRIGGER_SCRIPT_MEMORY = 2;

        public const PURGE_STRATEGY_RANDOM = 0;
        public const PURGE_STRATEGY_POPULARITY = 1;
    }