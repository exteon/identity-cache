# IdentityCache

IdentityCache is a drop-in array replacement object cache designed to work with 
ORM and Identity Map patterns, providing Weakref-based unique 
identity-to-object resolution and smart caching of unused objects based on 
objects popularity  

## Abstract

The [Identity Map](https://en.wikipedia.org/wiki/Identity_map_pattern) pattern
is very common in ORM implementations. It consists of an intermediate cache
between the persistence and application layer, which is able to retrieve
object instances by an unique object id. It must satisfy two requirements:

* For as long as an Identity is in use (the application layer holds any 
  references to it), the Identity Map will retrieve the
  same instance when its id is queried. Otherwise, data consistency will be 
  compromised when the application layer mutates different instances of the same
  identity.
* For Identities not in use, the Cache provides performance benefits by keeping
  instances for eventual reuse. It should however have a mechanism to discard 
  cached instances so as not to consume excessive resources (i.e. memory).

Many implementations use a PHP native array to store an identity => instance
map; however this has the disadvantage of storing instances indefinitely,
ultimately filling up memory even though the references are not still in use.   
  
## Implementation

The IdentityCache implements Weakref-based caching to satisfy the requirements
and a popularity-based garbage collection algorithm to keep the size of the
cached unused instances into configurable limits. 

The `IdentityCache` and `IdentityMap` are designed as drop-in replacements for a
native PHP array. The `IdentityMap` does not provide caching functionality; as
soon as an object is not used anymore (its refcount drops to 0), the object is
freed.     

## Requirements

* PHP 7.2
* The [Weakref](https://pecl.php.net/package/Weakref) extension; support for 
  PHP 7.4's [Weakreference](https://www.php.net/manual/en/class.weakreference.php)
  is NOT yet implemented.
 
## Usage

### Installing with `composer`

```shell script
composer config repositories.exteon-identity-map vcs https://github.com/exteon/identity-cache
composer require exteon/identity-cache
```

### IdentityMap

```php
$map = new \Exteon\IdentityCache\WeakRef\IdentityMap();

$instance = new stdClass();
$map[1] = $instance;

// While object is in use via $instance, the map will provide a reference to it
// via its id.

assert($map[1] === $instance);

// Put object out of use by releasing its reference

unset($instance);

// Object has been freed and its reference unset, because all references to it
// were released

assert(isset($map[1]) === false);
```

### IdentityCache

```php
$cache = new \Exteon\IdentityCache\WeakRef\IdentityCache([
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

assert($cache[1] === $instance1);
assert($cache[1] === $instance1);

// Increase instance 1's popularity by accessing it repeatedly

$cache[1];
$cache[1];
$cache[1];

// Put objects out of use by releasing their references

unset($instance1);
unset($instance2);

// maxRetainedObjects was configured to 1; instances will be purged

$cache->gc();

// instance 1's popularity was greater so it was preserved in the cache

assert(is_object($cache[1]));

// instance 2 was of a lesser popularity so it was purged

assert(isset($cache[2]) === false);
```

#### Configuration

An associative array can be passed to the constructor of 
`\Exteon\IdentityCache\WeakRef\IdentityCache`. Example usage, default and 
possible values are shown below. If a configuration option is not passed to the 
constructor, its default value will be used:

```php
use \Exteon\IdentityCache\WeakRef\IdentityCache;

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
    //                                  changed later by using the setConfig()
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
```

##### Popularity-based caching

When the `purgeStrategy` config option is `popularity`, a popularity index will 
be kept for the cached objects. An object's popularity increases by 1 whenever 
it is accessed from the cache.

In order to not keep forever objects that were once very popular but have not
been used in a long time, an object's popularity also decays exponentially every
time another object is accessed from the cache.

The value of the decay parameter can be specified by the `popularityDecay`
config option. This is a very sensitive float value that can be computed using 
the `getPopularityDecay()` method, as such:

```php
$popularityDecay = \Exteon\IdentityCache\WeakRef\IdentityCache::getPopularityDecay(
    1000,   //  initialPopularity
    10000,  //  rounds
    2       //  targetPopularity
);
```

The meaning of the parameters above, shown with default values, is:
If an object has at the present moment a popularity of `initialPopularity`
(1000), then other objects are accessed from the cache a number of `rounds` 
(10000) times, the popularity of the initial object will drop to 
`targetPopularity` (2). By tweaking the value of `popularityDecay`, you can
balance between keeping in the cache more of formerly popular objects or more of
recent objects.   

#### Acquiring objects

Mapped objects can be explicitly 'acquired', meaning the map/cache will hold 
them indefinitely even if they are no longer in use (they won't be purged). This 
is needed for 'dirty' objects that have been mutated and will need to be 
persisted later in a write-behind strategy scenario. 

Example:
```php
$cache = new \Exteon\IdentityCache\WeakRef\IdentityCache([
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
assert(
    is_object($cache[1]) &&
    $cache[1]->foo === 'bar'
);

//  Acquired identities can be released and then they can be purged if no longer 
//  in use:

$cache->release(1);
$cache->gc();
assert(isset($cache[1]) === false);
```

#### Invoking the garbage collector explicitly

The garbage collector is normally invoked every time an identity is added to the
cache; it will then check if the trigger condition is satisfied and if so it
will proceed to purge excess objects from the cache. The gc can be also invoked
manually by calling the `gc()` method on `IdentityCache`.

### Native PHP array replacements

If the `Weakref` extension is not available, or for testing purposes, or for
an emergency in case the Weakref implementation is failing, we provide regular
array-based replacements for the Weakref `IdentityMap` and `IdentityCache`:

`\Exteon\IdentityCache\NativeArray\IdentityMap`  
`\Exteon\IdentityCache\NativeArray\IdentityCache`

These implement the same interfaces as their WeakRef counterparts, so they can
be dropped in their place, but they will not implement any reference releasing
functionality: objects added will remain in the `IdentityMap` or `IdentityCache`
until explicitly `unset()`.