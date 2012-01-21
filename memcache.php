<?php

class Cache extends Memcache {
    static private $m_objMem = NULL;
    static function getMem() {
        if (self::$m_objMem == NULL) {
            self::$m_objMem = new Memcache;
            self::$m_objMem->connect(MEMCACHE_SERVER, MEMCACHE_PORT) 
            or die ("ERROR CONNECTING TO MEMCACHE SERVER");
        }

        return self::$m_objMem;
    }
}

?>
