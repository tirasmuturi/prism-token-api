PrismToken Thrift API for php (client & example)

FOLDER CONTENTS

- Prism/PrismToken1/
  PHP sources for the PrismToken API, generated from prismtoken1-TokenApi.thrift

- Thrift/
  Thrift libraries for PHP, required to use the above sources
  NOTE: TSocket.php has been modified to use fwrite/fread for SSL compatibility,
        see notes in PrismTokenClient.php

- PrismTokenClient.php 
  Minimal example of using the PrismToken Thrift API (over TLS)


PLATFORM

Tested on PHP 5.6.40 for Windows x86 (VC11 x86 Thread Safe from https://windows.php.net/download/)

** ENABLE OpenSSL in your PHP.INI

  If you get an error 'Unable to find the socket transport "tls"' then you don't have OpenSSL loaded.
  To enable OpenSSL:
    Copy 'php.ini-development' to 'php.ini' if you don't already have a php.ini
    Uncomment: extension_dir = "ext"
    Uncomment: extension=php_openssl.dll
  REF https://superuser.com/questions/1010080/the-openssl-extension-is-missing-on-windows-while-installing-composer
  
   
TO RUN THE EXAMPLE

php.exe PrismTokenClient.php
