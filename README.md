API Redis Bundle
=

Base Services for APIs in Symfony 5.1+ <br>
It includes:
- <b>Params Client:</b> Service to obtain the value of parameters defined in api-companies.
- <b>JSend Response:</b> Sets the format of the JSON response according to the JSEND specification (https://github.com/omniti-labs/jsend).
- <b>Request Util:</b> Request validation service.

### Install

1. Run de following composer command: <br>
```
composer require experteam/api-base-bundle
```

2. Create the configuration file through any of the following options: <br><br>

   a. Manually copy the example file in the package root to the folder `config/packages/`. <br><br>
   b. Copy the `vendor_copy.php` file to the root of the project and configure the scripts in the `composer.json` file:
   ```
   "scripts": {
        "vendor-scripts": [
            "@php vendor_copy.php -s vendor/experteam/api-base-bundle/experteam_api_base.yaml.example -d config/packages/experteam_api_base.yaml --not-overwrite --ignore-no-source"
        ],
        "post-install-cmd": [
            "@vendor-scripts"
        ],
        "post-update-cmd": [
            "@vendor-scripts"
        ]
    },
   ```

3. Edit the bundle configuration file `config/packages/experteam_api_base.yaml`: <br>
```
experteam_api_base:
    params:
        remote_url: [Remote URL]
        defaults:
            [PARAM NAME]: [Value]
            [PARAM NAME]: [Value]
```

### Update

Run de following composer command: <br>
```
composer update experteam/api-base-bundle
```


### License
[MIT license](https://opensource.org/licenses/MIT).
