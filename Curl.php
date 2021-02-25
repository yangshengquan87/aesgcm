<?php
class Curl
{
    protected 			$response = '';
    protected 			$session;
    protected 			$url;
    protected 			$options = array();
    protected 			$headers = array();
    public 				$error_code;
    public 				$error_string;
    public 				$info;
    
    
    public function __construct()
    {
    }
    
    public function __call($method, $arguments)
    {
        if (in_array($method, array('simple_get', 'simple_post', 'simple_put', 'simple_delete', 'simple_request')))
        {
            $verb = str_replace('simple_', '', $method);
            array_unshift($arguments, $verb);
            return call_user_func_array(array($this, '_simple_call'), $arguments);
        }
    }
    public function _simple_call($method, $url, $params = array(), $options = array())
    {
        if ($method === 'get')
        {
            $connect = (false === strpos($url, '?'))?'?':'&';
            $this->create($url.($params ? $connect.http_build_query($params, NULL, '&') : ''));
        }
        else
        {
            $this->create($url);
            
            $this->{$method}($params);
        }
        if( ! is_array($options) ) $options = [];
        $this->options($options);
        return $this->execute();
    }
    
    public function post($params = array(), $options = array())
    {
        if (is_array($params))
        {
            $params = http_build_query($params, NULL, '&');
        }
        
        $this->options($options);
        
        $this->http_method('post');
        
        $this->option(CURLOPT_POST, TRUE);
        $this->option(CURLOPT_POSTFIELDS, $params);
    }
    
    public function request($params = array(), $options = array())
    {
        if($params)
        {
            return $this->post($params, $options);
        }
    }
    
    public function put($params = array(), $options = array())
    {
        if (is_array($params))
        {
            $params = http_build_query($params, NULL, '&');
        }
        
        $this->options($options);
        
        $this->http_method('put');
        $this->option(CURLOPT_POSTFIELDS, $params);
        
        $this->option(CURLOPT_HTTPHEADER, array('X-HTTP-Method-Override: PUT'));
    }
    
    public function delete($params, $options = array())
    {
        if (is_array($params))
        {
            $params = http_build_query($params, NULL, '&');
        }
        
        $this->options($options);
        
        $this->http_method('delete');
        
        $this->option(CURLOPT_POSTFIELDS, $params);
    }
    
    public function http_header($header, $content = NULL)
    {
        $this->headers[] = $content ? $header . ': ' . $content : $header;
        return $this;
    }
    
    public function http_host($host)
    {
        if(!empty($host))
        {
            $this->http_header('Host',$host);
        }
        return $this;
    }
    
    public function verbose( $verbose = TRUE )
    {
        $this->option(CURLOPT_VERBOSE, $verbose);
        return $this;
    }
    
    public function http_method($method)
    {
        $this->options[CURLOPT_CUSTOMREQUEST] = strtoupper($method);
        return $this;
    }
    
    public function http_login($username = '', $password = '', $type = 'any')
    {
        $this->option(CURLOPT_HTTPAUTH, constant('CURLAUTH_' . strtoupper($type)));
        $this->option(CURLOPT_USERPWD, $username . ':' . $password);
        return $this;
    }
    
    public function proxy($url = '', $port = 80)
    {
        $this->option(CURLOPT_HTTPPROXYTUNNEL, TRUE);
        $this->option(CURLOPT_PROXY, $url . ':' . $port);
        return $this;
    }
    
    public function proxy_login($username = '', $password = '')
    {
        $this->option(CURLOPT_PROXYUSERPWD, $username . ':' . $password);
        return $this;
    }
    
    public function ssl($verify_peer = TRUE, $verify_host = 2, $path_to_cert = NULL)
    {
        if ($verify_peer)
        {
            $this->option(CURLOPT_SSL_VERIFYPEER, TRUE);
            $this->option(CURLOPT_SSL_VERIFYHOST, $verify_host);
            if (isset($path_to_cert)) {
                $path_to_cert = realpath($path_to_cert);
                $this->option(CURLOPT_CAINFO, $path_to_cert);
            }
        }
        else
        {
            $this->option(CURLOPT_SSL_VERIFYPEER, FALSE);
            $this->option(CURLOPT_SSL_VERIFYHOST, FALSE);
        }
        return $this;
    }
    
    public function options( array $options = [] )
    {
        foreach ($options as $option_code => $option_value)
        {
            $this->option($option_code, $option_value);
        }
        
        curl_setopt_array($this->session, $this->options);
        
        return $this;
    }
    
    public function option($code, $value)
    {
        if (is_string($code) && !is_numeric($code))
        {
            $code = constant('CURLOPT_' . strtoupper($code));
        }
        
        $this->options[$code] = $value;
        return $this;
    }
    
    public function create($url)
    {
        $this->url = $url;
        $this->session = curl_init($this->url);
        if(0===strpos($this->url, 'https://'))
        {
            $this->ssl(0);
        }
        return $this;
    }
    
    public function execute()
    {
        if ( ! isset($this->options[CURLOPT_TIMEOUT]))
        {
            $this->options[CURLOPT_TIMEOUT] = 30;
        }
        if ( ! isset($this->options[CURLOPT_RETURNTRANSFER]))
        {
            $this->options[CURLOPT_RETURNTRANSFER] = TRUE;
        }
        if ( ! isset($this->options[CURLOPT_FAILONERROR]))
        {
            $this->options[CURLOPT_FAILONERROR] = FALSE;
        }
        
        if ( ! ini_get('safe_mode') && ! ini_get('open_basedir'))
        {
            if ( ! isset($this->options[CURLOPT_FOLLOWLOCATION]))
            {
                $this->options[CURLOPT_FOLLOWLOCATION] = TRUE;
                $this->options[CURLOPT_MAXREDIRS] = 5;
            }
        }
        if ( ! empty($this->headers))
        {
            $this->option(CURLOPT_HTTPHEADER, $this->headers);
        }
        $this->options();
        $this->response = curl_exec($this->session);
        $this->info = curl_getinfo($this->session);
        if($this->verbose === TRUE)
        {
            $return = [
                'transfer_stats'=>$this->info,
                'curl'=>['error'=>curl_error($this->session),'errno'=>curl_errno($this->session)],
                'effective_url'=>$this->info['url'],
                'headers'=>[],
                'status'=>$this->info['http_code'],
                'body'=>$this->response,
            ];
            foreach ($this->headers as $text)
            {
                list($hk , $hv) = explode(":", $text);
                $return['headers'][trim($hk)][] = trim($hv);
            }
            curl_close($this->session);
            $this->set_defaults();
            return $return;
        }
        
        if ($this->response === FALSE)
        {
            $errno = curl_errno($this->session);
            $error = curl_error($this->session);
            
            curl_close($this->session);
            $this->set_defaults();
            
            $this->error_code = $errno;
            $this->error_string = $error;
            
            return FALSE;
        }
        
        else
        {
            curl_close($this->session);
            $this->last_response = $this->response;
            $this->set_defaults();
            return $this->last_response;
        }
    }
    
    public function is_enabled()
    {
        return function_exists('curl_init');
    }
    
    public function set_defaults()
    {
        $this->response = '';
        $this->headers = array();
        $this->options = array();
        $this->error_code = NULL;
        $this->error_string = '';
        $this->session = NULL;
    }
}


/* End of file Curl.php */
/* Location: ./application/libraries/Curl.php */