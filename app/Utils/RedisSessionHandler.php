<?php namespace app\Utils;



use Redis;
use SessionHandlerInterface;
use SessionIdInterface;
/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

// require_once 'Encrypter.php';
use app\Utils\Encrypter;

// use SessionHandlerInterface;
// to delete all laravel redis session, use this in the terminal
// redis-cli -h 'db1.apexinnovations.com' KEYS 'laravel:*' | xargs redis-cli -h 'db1.apexinnovations.com' DEL
// require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/apx_User.php');
// require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/apx_Users.php');
// if(!class_exists('apx_DBAccessBaseClass'))
// {
//     function __autoload($class_name) { require_once $_SERVER['DOCUMENT_ROOT'] . "classes/" . $class_name . ".php"; }    
    
// }


class RedisSessionHandler implements SessionHandlerInterface, SessionIdInterface
// class RedisSessionHandler extends apx_DBAccessBaseClass implements SessionHandlerInterface, SessionIdInterface
{
    protected $client;
    protected $ttl;
    protected $prefix;
    protected $encrypter;
    static public $store = [];

    /**
     * @param ClientInterface $client  Fully initialized client instance.
     * @param array           $options Session handler options.
     */
    public function __construct(array $options = [])
    {
        $this->client = new Redis();
        $this->client->connect(getenv('REDIS_SERVER'), getenv('REDIS_PORT'));
        $this->client->auth(getenv('REDIS_AUTH'));
        $key = getenv('KEY');
        $this->encrypter = new Encrypter($key);

        if (isset($options['gc_maxlifetime'])) {
            $this->ttl = (int) $options['gc_maxlifetime'];
        } else {
            $this->ttl = ini_get('session.gc_maxlifetime');
        }

        if (isset($options['prefix'])) {
            $this->prefix = (int) $options['prefix'];
        } else {
            $this->prefix = 'laravel:';
            // $this->prefix = '';
        }
    }

    /**
     * Registers this instance as the current session handler.
     */
    public function register()
    {
        if (version_compare(PHP_VERSION, '5.4.0') >= 0) {
            session_set_save_handler($this, true);
        } else {
            session_set_save_handler(
                array($this, 'open'),
                array($this, 'close'),
                array($this, 'read'),
                array($this, 'write'),
                array($this, 'destroy'),
                array($this, 'gc'),
                array($this, 'create_sid')
            );
        }
    }

    public function create_sid() {
        $sid = bin2hex(openssl_random_pseudo_bytes(16));
        static::$store[$sid] = [];
        // return $sid;
        // original
        return $sid;
    }

    /**
     * {@inheritdoc}
     */
    public function open($save_path, $session_id)
    {
        // NOOP
        // error_log('open SESSION');
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        // NOOP
        // error_log('close SESSION');
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function gc($maxlifetime)
    {
        // NOOP
        // error_log('gc SESSION');
        return true;
    }

    public function getDecryptedId($encrypted)
    {
        try {
            $response = $this->encrypter->decrypt($encrypted);
        } catch(Exception $e) {
            // no biggy didn't work
            $response = $encrypted;
        }
        // error_log('READING[$encrypted, $response]: '.print_r([$encrypted, $response], true));
        return $this->prefix.$response;
    }

    /**
     * {@inheritdoc}
     */
    public function read($session_id)
    {
        $session_id = $this->getDecryptedId($session_id);
        // error_log($session_id);
        if ($data = $this->client->get($session_id)) {
            $unserialized_data = unserialize(unserialize($data));
            $serialized_data = self::serialize_php($unserialized_data);
            // error_log('READING[$session_id, $data]: '.print_r([$session_id, $data, $unserialized_data, $serialized_data], true));
            // $this->getConcurrentStatus();
            // $this->setBypassed($unserialized_data);
            if(isset($unserialized_data['userID']) && !$this->getBypassed($unserialized_data))
            {
                $userID = $unserialized_data['userID'];
                if('laravel:' . $this->client->get('User:' . $userID) !== $session_id)
                {
                    $this->destroy($session_id);
                }
                else
                {

                    return $serialized_data;
                }
            }
            else 
            {   //we don't care about concurrent logins on the admin panel or Trax
                return $serialized_data;
            }
        }

        return '';
    }



    /**
     * {@inheritdoc}
     */
    public function write($session_id, $session_data)
    {
        // $session_id = $this->getDecryptedId($session_id);
        // error_log('[unserialize_php]: '.print_r(self::unserialize_php($session_data), true));
        $serialized_data = serialize(serialize(self::unserialize_php($session_data)));
        //error_log('WRITTING [$session_id, $session_data, $serialized_data]: '.print_r([$session_id, $session_data, $serialized_data], true));
        $this->client->setex($session_id, $this->ttl, $serialized_data);
        return true;
    }

    private function getBypassed($unserialized_data = null)
    {
            //this disables concurrent logins. The admin panel is not affected by this. 
            $proxyLogon = isset($unserialized_data['ProxyLogon']) ? $unserialized_data['ProxyLogon'] : false;
            $Trax = isset($unserialized_data['Trax']) ? $unserialized_data['Trax'] : false;
            $LMS = isset($unserialized_data['LMS']) ? $unserialized_data['LMS'] : false;
			$FIRST = isset($unserialized_data['FIRST']) ? $unserialized_data['FIRST'] : false;
            $EHAC = isset($unserialized_data['EHAC']) ? $unserialized_data['EHAC'] : false;
            $AJAX = isset($unserialized_data['AJAX']) ? $unserialized_data['AJAX'] : false;
            $ApexStore = isset($unserialized_data['ApexStore']) ? $unserialized_data['ApexStore'] : false;
            if(isset($unserialized_data['userID']))
            {
                // $user = new apx_User($unserialized_data['userID']);
                // $LMSUser = $user->LMS;
            }
            if($proxyLogon || $Trax || $LMS || $EHAC || $FIRST || $ApexStore || $AJAX)
            {                
                return true;
            }
            else
            {
                return false;
            }
 
    }

    private static function unserialize_php($session_data) {
        $return_data = array();
        $offset = 0;
        while ($offset < strlen($session_data)) {
            if (!strstr(substr($session_data, $offset), "|")) {
                throw new Exception("invalid data, remaining: " . substr($session_data, $offset));
            }
            $pos = strpos($session_data, "|", $offset);
            $num = $pos - $offset;
            $varname = substr($session_data, $offset, $num);
            $offset += $num + 1;
            $data = unserialize(substr($session_data, $offset));
            $return_data[$varname] = $data;
            $offset += strlen(serialize($data));
        }
        // error_log('[unserialize_php]: '.print_r($return_data, true));
        return $return_data;
    }


    private static function serialize_php($session_data) {
        $return_str = '';
        foreach($session_data as $k => $v){
            $serializedValue = serialize($v);
            $endLn = (substr($serializedValue, -1) == ('}' || ';')) ? '' : ';';
            $return_str .= $k.'|'.$serializedValue.$endLn;
        }
        //error_log('[serialize_php]: '.print_r([$session_data, $return_str], true));
        return $return_str;
    }

    /**
     * {@inheritdoc}
     */
    /*public function read($session_id)
    {
        if ($data = $this->client->get($session_id)) {
            error_log('$data: '.print_r($data, true));
            return unserialize(unserialize($data));
        }

        return '';
    }*/
    /**
     * {@inheritdoc}
     */
    /*public function write($session_id, $session_data)
    {
        error_log('$session_data: '.print_r($session_data, true));
        $this->client->setex($session_id, $this->ttl, serialize(serialize($session_data)));

        return true;
    }*/

    /**
     * {@inheritdoc}
     */
    public function destroy($session_id)
    {
        $session_id = $this->getDecryptedId($session_id);
        
        $this->client->del($session_id);

        return true;
    }

    /**
     * Returns the underlying client instance.
     *
     * @return ClientInterface
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * Returns the session max lifetime value.
     *
     * @return int
     */
    public function getMaxLifeTime()
    {
        return $this->ttl;
    }
}
