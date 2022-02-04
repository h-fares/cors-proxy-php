<?php

// Configs
$enable_jsonp = false;
$return_as_json = false;
$enable_native = false;
$valid_url_regex = '/.*/';

$url = $_GET['url'];

if(!$url) {
    // URL not found
    $contents = 'Url not specified';
    $status = array('http_code' => 'ERROR');
}else {
    if(!preg_match($valid_url_regex, $url)) {
        // URL not valid
        $contents = 'Invalid url';
        $status = array('http_code' => 'ERROR');
    }else {
        $ch = curl_init($url);

        // Save domain
        $parts = parse_url($url);
        $domain = $parts['scheme']."://".$parts['host'];

        if(strtolower($_SERVER['REQUEST_METHOD']) == 'post') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $_POST);
        }

        if(isset($_GET['send_cookies']) && $_GET['send_cookies']) {
            $cookie = array();
            foreach($_COOKIE as $key => $value) {
                $cookie[] = $key.'='.$value;
            }
            if($_GET['send_session']) {
                $cookie[] = SID;
            }
            $cookie = implode('; ', $cookie);
            curl_setopt($ch, CURLOPT_COOKIE, $cookie);
        }

        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_ENCODING, ""); // @lf guess encoding automagically

        if(isset($_GET['user_agent'])) {
            curl_setopt($ch, CURLOPT_USERAGENT,
                $_GET['user_agent'] ? $_GET['user_agent'] : $_SERVER['HTTP_USER_AGENT']);
        }

        list($header, $contents) = preg_split('/([\r\n][\r\n])\\1/', curl_exec($ch), 2);

        // Filter all URLs
        $rep['/href="(?!https?:\/\/)(?!data:)(?!#)/'] = 'href="'.$domain;
        $rep['/src="(?!https?:\/\/)(?!data:)(?!#)/'] = 'src="'.$domain;
        $rep['/href=\'(?!https?:\/\/)(?!data:)(?!#)/'] = 'href="'.$domain;
        $rep['/src=\'(?!https?:\/\/)(?!data:)(?!#)/'] = 'src="'.$domain;
        $rep['/@import[\n+\s+]"\//'] = '@import "'.$domain;
        $rep['/@import[\n+\s+]"\./'] = '@import "'.$domain;

        $contents = preg_replace(array_keys($rep), array_values($rep), $contents);
        $status = curl_getinfo($ch);
        curl_close($ch);
    }
}

// Split header text into an array.
$header_text = preg_split('/[\r\n]+/', $header);

if(isset($_GET['mode']) && $_GET['mode'] == 'native') {
    if(!$enable_native) {
        $contents = 'ERROR: invalid mode';
        $status = array('http_code' => 'ERROR');
    }
    // Propagate headers to response.
    foreach($header_text as $header) {
        if(preg_match('/^(?:Content-Type|Content-Language|Set-Cookie):/i', $header)) {
            header($header);
        }
    }
    print $contents;
}else {
    $data = array();

    // Propagate all HTTP headers into the JSON data object.
    if(isset($_GET['full_headers']) && $_GET['full_headers']) {
        $data['headers'] = array();
        foreach($header_text as $header) {
            preg_match('/^(.+?):\s+(.*)$/', $header, $matches);
            if($matches) {
                $data['headers'][$matches[1]] = $matches[2];
            }
        }
    }

    // Propagate all cURL request / response info to the JSON data object.
    if(isset($_GET['full_status']) && $_GET['full_status']) {
        $data['status'] = $status;
    }else {
        $data['status'] = array();
        $data['status']['http_code'] = $status['http_code'];
    }

    // Set the JSON data object contents, decoding it from JSON if possible.
    $decoded_json = json_decode($contents);
    $data['contents'] = $decoded_json ? $decoded_json : $contents;

    // Generate appropriate content-type header.
    $is_xhr = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    header('Content-type: application/'.($is_xhr ? 'json' : 'x-javascript'));
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
    header('Access-Control-Allow-Header: Content-Type');

    if($return_as_json) {
        $jsonp_callback = $enable_jsonp && isset($_GET['callback']) ? $_GET['callback'] : null;
        // Generate JSON/JSONP string
        $json = json_encode($data);
        print $jsonp_callback ? "$jsonp_callback($json)" : $json;
    }else {
        print $data['contents'];
    }
}
