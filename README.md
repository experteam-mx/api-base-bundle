API Redis Bundle
=

Base Services for APIs in Symfony 5.1+ <br>
It includes:
- <b>Params Client:</b> Service to obtain the value of parameters defined in api-companies.
- <b>JSend Response:</b> Sets the format of the JSON response according to the JSEND specification (https://github.com/omniti-labs/jsend).
- <b>Request Util:</b> Request validation service.

### Install

1. Configure the repository in the `composer.json` file: <br>
```
...

"repositories": [
   {
      "type": "vcs",
      "url":  "https://github.com/experteam-mx/api-base-bundle.git"
   }
]  
```

2. Configure the required package in the `composer.json` file: <br>
```
"require": {
   "experteam/api-base-bundle": "dev-master"
}
```

3. Execute the following command: <br>
```
composer update experteam/api-base-bundle
```

### License
[MIT license](https://opensource.org/licenses/MIT).
