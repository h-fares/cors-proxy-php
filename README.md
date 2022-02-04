# cors-proxy-php
A small description of the CORS issue, with simple solution written in PHP.


CROS policy "is a mechanism that uses additional HTTP header to inform a browser to allow a web application running at one origin (domain) have permission to access selected resources from a server at a different origin" [Cross-Origin Resource Sharing (CORS) Policies by IBM](https://www.ibm.com/docs/en/sva/10.0.1?topic=control-cross-origin-resource-sharing-cors-policies)

Thazt means when client trys to get content of another website (another URL) the Browser blocks this request if the reqest / response does not have special header:

Access-Control-Allow-Origin

With this header we can define all origin that can be allowed from the Browser. To allow all origin => Access-Control-Allow-Method: *


BASED ON: [simple cors](https://gist.github.com/dropmeaword/a050231a5767adc52b986faf587f64c9)
