<?php
// ================================================================
// MIKROTIK API — ROS 6 & 7 compatible (Standalone)
// For IP Hotspot IP-Binding (MAC bypass)
// ================================================================

class MikrotikAPI {
    private $sock = null;
    public $error = '';
    private $host, $user, $pass;
    private $port;

    public function __construct(string $host, int $port, string $user, string $pass) {
        $this->host=$host; $this->port=$port; $this->user=$user; $this->pass=$pass;
    }

    public function connect(): bool {
        $this->sock = @fsockopen($this->host, $this->port, $en, $es, 10);
        if (!$this->sock) { $this->error="Gagal konek {$this->host}:{$this->port} — $es"; return false; }
        stream_set_timeout($this->sock, 30);
        return $this->login();
    }

    private function login(): bool {
        $r=$this->talk(['/login','=name='.$this->user,'=password='.$this->pass]);
        if(in_array('!done',$r)) return true;
        foreach($r as $l) {
            if(preg_match('/=ret=([a-f0-9]+)/',$l,$m)) {
                $r2=$this->talk(['/login','=name='.$this->user,'=response=00'.md5("\x00".$this->pass.pack('H*',$m[1]))]);
                if(in_array('!done',$r2)) return true;
            }
        }
        $this->error='Login Mikrotik gagal'; return false;
    }

    public function talk(array $cmds): array {
        if(!$this->sock) return [];
        foreach($cmds as $w) $this->writeWord($w);
        $this->writeWord('');
        return $this->readAll();
    }

    private function writeWord(string $w): void {
        $l=strlen($w);
        if($l<0x80)       fwrite($this->sock,chr($l));
        elseif($l<0x4000) fwrite($this->sock,chr(($l>>8)|0x80).chr($l&0xFF));
        elseif($l<0x200000) fwrite($this->sock,chr(($l>>16)|0xC0).chr(($l>>8)&0xFF).chr($l&0xFF));
        else fwrite($this->sock,chr(($l>>24)|0xE0).chr(($l>>16)&0xFF).chr(($l>>8)&0xFF).chr($l&0xFF));
        if($l>0) fwrite($this->sock,$w);
    }

    private function readAll(): array {
        $out=[];$done=false;$maxLoops=100000;$loops=0;$emptyRetries=0;$maxEmptyRetries=100;
        while($loops++<$maxLoops){
            $byte=@fread($this->sock,1);
            if($byte===false||$byte===''){
                if($done) break;
                $meta=@stream_get_meta_data($this->sock);
                if($meta===false) break;
                if($meta['timed_out']??false) break;
                if(++$emptyRetries>$maxEmptyRetries) break;
                usleep(5000); continue;
            }
            $emptyRetries=0;
            $b=ord($byte);
            if($b&0x80){
                if(($b&0xC0)===0x80){
                    $len=(($b&0x3F)<<8)+ord(@fread($this->sock,1));
                } elseif(($b&0xE0)===0xC0){
                    $len=(($b&0x1F)<<8)+ord(@fread($this->sock,1));
                    $len=($len<<8)+ord(@fread($this->sock,1));
                } elseif(($b&0xF0)===0xE0){
                    $len=(($b&0x0F)<<8)+ord(@fread($this->sock,1));
                    $len=($len<<8)+ord(@fread($this->sock,1));
                    $len=($len<<8)+ord(@fread($this->sock,1));
                } else {
                    $len=ord(@fread($this->sock,1));
                    $len=($len<<8)+ord(@fread($this->sock,1));
                    $len=($len<<8)+ord(@fread($this->sock,1));
                    $len=($len<<8)+ord(@fread($this->sock,1));
                }
            } else {
                $len=$b;
            }
            if($len===0){
                $st=@stream_get_meta_data($this->sock);
                if($done&&($st===false||($st['unread_bytes']??0)===0)) break;
                continue;
            }
            $word='';$rem=$len;
            while($rem>0){$chunk=@fread($this->sock,$rem);if($chunk===false||$chunk==='')break;$word.=$chunk;$rem-=strlen($chunk);}
            $out[]=$word;
            if($word==='!done') $done=true;
            if($word==='!trap'||$word==='!fatal'){
                while(true){
                    $b2=@fread($this->sock,1);if($b2===false||$b2==='')break;$l2=ord($b2);if($l2===0)break;
                    $w2='';$r2=$l2;while($r2>0){$c2=@fread($this->sock,$r2);if(!$c2)break;$w2.=$c2;$r2-=strlen($c2);}
                    $out[]=$w2;
                }
                break;
            }
        }
        return $out;
    }

    public function parse(array $resp): array {
        $out=[];$cur=[];
        foreach($resp as $l){
            if($l==='!done'){if($cur)$out[]=$cur;break;}
            if($l==='!re'){if($cur)$out[]=$cur;$cur=[];continue;}
            if(str_starts_with($l,'=')){list($k,$v)=array_pad(explode('=',substr($l,1),2),2,'');$cur[$k]=$v;}
        }
        if($cur&&!in_array('!done',$resp))$out[]=$cur;
        return $out;
    }

    public function parseOne(array $resp): array {
        $out=[];
        foreach($resp as $l){
            if(in_array($l,['!done','!re','!trap','!fatal']))continue;
            if(str_starts_with($l,'=')){list($k,$v)=array_pad(explode('=',substr($l,1),2),2,'');$out[$k]=$v;}
        }
        return $out;
    }

    public function hasError(array $resp): ?string {
        foreach($resp as $l) {
            if(str_starts_with($l, '=message=')) return substr($l, 9);
        }
        return null;
    }

    public function close(): void { if($this->sock){fclose($this->sock);$this->sock=null;} }

    // ============================================================
    // IP BINDING (Hotspot IP-Binding) — untuk MAC bypass
    // /ip/hotspot/ip-binding
    // type=bypassed → MAC langsung bypass hotspot tanpa perlu login
    // ============================================================

    /**
     * Get all IP bindings
     */
    public function getIpBindings(): array {
        return $this->parse($this->talk(['/ip/hotspot/ip-binding/print',
            '=.proplist=.id,mac-address,type,comment,disabled,address,to-address,server']));
    }

    /**
     * Add IP binding with type=bypassed
     * @param string $mac MAC address
     * @param string $comment Nama/komentar
     * @return string|null ID atau null jika gagal
     */
    public function addIpBinding(string $mac, string $comment = ''): ?string {
        $cmd = ['/ip/hotspot/ip-binding/add',
            "=mac-address=$mac",
            '=type=bypassed'
        ];
        if ($comment) $cmd[] = "=comment=$comment";
        $r = $this->talk($cmd);

        $err = $this->hasError($r);
        if ($err) { $this->error = $err; return null; }

        foreach($r as $l) if(preg_match('/=ret=(.+)/',$l,$m)) return $m[1];
        return in_array('!done',$r) ? 'ok' : null;
    }

    /**
     * Update IP binding
     */
    public function updateIpBinding(string $id, ?string $mac = null, ?string $comment = null): bool {
        $cmd = ['/ip/hotspot/ip-binding/set', "=.id=$id"];
        if ($mac !== null) $cmd[] = "=mac-address=$mac";
        if ($comment !== null) $cmd[] = "=comment=$comment";
        $r = $this->talk($cmd);

        $err = $this->hasError($r);
        if ($err) { $this->error = $err; return false; }

        return in_array('!done', $r);
    }

    /**
     * Remove IP binding
     */
    public function removeIpBinding(string $id): bool {
        $r = $this->talk(['/ip/hotspot/ip-binding/remove', "=.id=$id"]);
        $err = $this->hasError($r);
        if ($err) { $this->error = $err; return false; }
        return in_array('!done', $r);
    }

    /**
     * Get system identity
     */
    public function getIdentity(): string {
        $r = $this->parseOne($this->talk(['/system/identity/print']));
        return $r['name'] ?? '-';
    }

    /**
     * Get system resource usage
     */
    public function getResourceUsage(): array {
        return $this->parseOne($this->talk(['/system/resource/print']));
    }
}
