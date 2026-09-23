# FlickPick

FlickPick will be a web application users can access to browse movies or TV shows as if it were a dating app. By using the “swipe left” or “swipe right” concept, which many users are already accustomed to, we will provide an entertaining method for users to choose a movie. The primary goal of FlickPick is to reduce the decision fatigue many users experience and decrease selection time when choosing what to stream.


## To get started developing in this project 

navigate to your C: drive  
find the "MAMP" folder  
Create a folder called "Config"  
Create a file within that folder called "tmdb.php"  
Inside this file paste the following contents and replace 'PASTE_YOUR_TMDB_TOKEN_HERE' with the tmdb api token  

```php
<?php

return [
    'token' => 'PASTE_YOUR_TMDB_TOKEN_HERE',
];
```

Now you must download a bundle of trusted certificate authorities and add them to the php.ini file.  

download the CA bundle from:

https://curl.sw/ca/cacert.pem  

save this to the same config folder (C:\MAMP\Config)  

Navigate to:  
C:\MAMP\conf\php8.3.1\php.ini

add the following lines to the bottom of the file:

```ini
curl.cainfo = "C:\MAMP\Config\cacert.pem"
openssl.cafile = "C:\MAMP\Config\cacert.pem"
```