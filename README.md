# Laravel Encrypted Attributes
This package adds a secure-by-default encrypted attributes, to any Laravel Model,
on demand.

By default, all serializations (`toArray`, `toJson`) and the default getter for any attribute
will always choose the encrypted value over the decrypted one. 

To get the decrypted value you have to simply add the `_decrypted` suffix:
```php
echo $tenant->access_token_decrypted;
```

# Installation
```
composer require mathiasgrimm/laravel-encrypted-attributes ^1.0
```

# Background
I was thinking in a way to improve the existing Eloquent cast `encrypted`
[Encrypted Casting](https://laravel.com/docs/9.x/eloquent-mutators#encrypted-casting).

Laravel Documentation:
> The encrypted cast will encrypt a model's attribute value using Laravel's built-in encryption features. In addition, the encrypted:array, encrypted:collection, encrypted:object, AsEncryptedArrayObject, and AsEncryptedCollection casts work like their unencrypted counterparts; however, as you might expect, the underlying value is encrypted when stored in your database.

Laravel's default behavior is to keep it encrypted only in the database, and plain text for the rest.
I think the default behaviour should be to always keep it encrypted unless you specifically ask for it decrypted.

> For the rest of this reading assume the following:
> - `local.encrypted-string` is an encrypted string, prepended with the environment
    >   it was encrypted in.
> - `plain-text-string` is just an unencrypted plain text string.

# Laravel Default Behavior
The examples bellow will all output the encrypted cast attributes in plain text:

```php
logger($tenant, ['tenant' => $tenant]);
// Will output:
// [2022-06-23 12:10:32] local.DEBUG: {"access_token_github":"plain-text-string"} {"tenant":{"App\\Models\\Tenant":{"access_token_github":"plain-text-string"}}} 

print_r($tenant->toArray());
// Will print:
// Array
// (
//     [access_token_github] => plain-text-string
// )

echo  $tenant->toJson();
// Will print: {"access_token_github":"plain-text-string"}

echo $tenant->access_token_github;
// Will print: plain-text-string
```


# Configuration
To use the Encrypted attributes all you need to do is to use the trait `HasEncryptedAttributes` and 
add the attributes you want to be encrypted to the `protected $encrypted = []` model property.

## Examples
```php
// app/Models/Tenants.php
use MathiasGrimm\EncryptedAttributes\HasEncryptedAttributes;

class Tenants extends Model
{
    use HasEncryptedAttributes;
    
    protected $encrypted = [
        'access_token_github',
        'access_token_facebook',
    ];
}

// other.php
$tenant = Tenant::find(1);
```

```php
// -------------------------------------------------------------------
// Example 1 - New Default Getter Behaviour
// -------------------------------------------------------------------
echo $tenant->access_token_github;
// Will print "local.encrypted-string".
// When using the accessor we always get the raw database value.
// Therefore, by default it's encrypted.
```

```php
// -------------------------------------------------------------------
// Example 2 - (Same) Default Setter Behaviour
$tenant->access_token_github = 'plain-text-string';
// Will encrypt the value to "local.encrypted-string".
```

```php
// -------------------------------------------------------------------
// Example 3 - Getting The Decrypted Value
// -------------------------------------------------------------------
echo $tenant->access_token_github_decrypted;
// Will print "plain-text-string".
```

```php
// -------------------------------------------------------------------
// Example 4 - Setting a Raw Value 
// -------------------------------------------------------------------
$tenant->access_token_github_raw = 'local.encrypted-string';
// Will store the raw value

echo $tenant->access_token_github;
// Will print "local.encrypted-string".

echo $tenant->access_token_github_decrypted;
// Will print "plain-text-string".
```

```php
// -------------------------------------------------------------------
// Example 5 - Update
// -------------------------------------------------------------------
$tenant->update([
    'access_token_github' => 'plain-text-string'
]);
// Will store/set the value "local.encrypted-string"
```

```php
// -------------------------------------------------------------------
// Example 5.1 - Update With Raw
// -------------------------------------------------------------------
$tenant->update([
    'access_token_github_raw' => 'local.encrypted-string'
]);
// Will store/set the value of access_token_github to "local.encrypted-string"
```

```php
// -------------------------------------------------------------------
// Example 6 - Query 
// -------------------------------------------------------------------
echo Tenant::where('access_token_github', 'plain-text-string')->count();
// Will print "0"
     
echo Tenant::where('access_token_github', 'local.encrypted-string')->count();
// Will print "1"
```

```php
// -------------------------------------------------------------------
// Example 7 - toArray()
// -------------------------------------------------------------------
print_r($tenant->toArray());
// Will print:
// array [
//     'access_token_github' => 'local.encrypted-string'
// ]
```

```php
// -------------------------------------------------------------------
// Example 8 - toJson()
// -------------------------------------------------------------------
print_r($tenant->toJson());
// Will print: "{"access_token_github": "local.encrypted-string"}"
```

```php
// -------------------------------------------------------------------
// Example 9 - Collection ->keyBy('access_token_github')
// -------------------------------------------------------------------
$tenantsByAccessTokenGithub = collect([$tenant])->keyBy('access_token_github');

// works
$tenantsByAccessTokenGithub['local.encrypted-string'];

// undefined index
$tenantsByAccessTokenGithub['plain-text-string'];
```

```php
// -------------------------------------------------------------------
// Example 10 - Collection ->keyBy('access_token_github_decrypted')
// -------------------------------------------------------------------
$tenantsByAccessTokenGithub = collect([$tenant])->keyBy('access_token_github_decrypted');

// undefined index
$tenantsByAccessTokenGithub['local.encrypted-string'];

// works
$tenantsByAccessTokenGithub['plain-text-string'];
```

```php
// -------------------------------------------------------------------
// Example 11 - Getting Encrypted Attribute Environment
// -------------------------------------------------------------------
echo $tenant->access_token_github_environment;
// Will print: local
```

## Environment Scope
When using the encrypted attributes, the environment in which the value was encrypted in is prepended
to the encrypted string.

Examples:
```
local.encrypted-string
testing.encrypted-string
staging.encrypted-string
production.encrypted-string
other.encrypted-string
```

By doing that we can add extra checks based on the prefix.

With that we could do things like:
```php
// MyApiClient.php

$writeMethods = ['post', 'put', 'patch', 'delete'];

if (in_array($method, $writeMethods) && $this->access_token_github_environment != app()->environment()) {
    throw Exception("API is currently in read-only mode");
}   
```

[Original Idea](https://github.com/mathiasgrimm/blog/blob/master/articles/laravel-encrypted-attributes-idea/)