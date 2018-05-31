# baas-sdk-php

# Introduction

This is a php library.
If you just enjoy the BaaS service. you can go to [BaaS API](https://doc.gxb.io/gxchain/api/baas-api.html).


### Requirements

PHP 7

PHP EXTENSION: [secp256k1](https://github.com/Bit-Wasp/secp256k1-php) 


# Storage Usage
```
composer require "gxchain/baas:dev-master"

touch test.php

vim test.php

```

```

require "vendor/autoload.php";

use Gxchain\BaaS\BaaSClient;

$obj = new BaaSClient();

//store
$res = $obj->store('hello gxs php ssdk');
echo $res;

//storeGet
//$cid = 'Qmchxhy52QswvcodjRWuNz83vUo1ZKZnUfGRaUaPYdVEKy';
//$res = $obj->storeGet($cid);
//echo $res;
```

```
cd vendor/gxchain/baas/src/BaaS
```

Edit Config.php

Back to the vendor directory

```
php test.php

```






