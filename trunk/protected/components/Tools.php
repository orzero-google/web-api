<?php
class Tools
{
	public static function OZCurl($src, $expire=60, $show=false)
	{
		
		$expire = intval($expire)>20 ? intval($expire) : 20;
		$src = trim($src);
		if(empty($src)) return false;

        if(!self::is_url($src)) return false;
		
		$c = null;
		$key = md5($src);
		$c=Yii::app()->cache->get($key);
		
		if(empty($c)){
			//Run curl
            Yii::app()->CURL->run(array(CURLOPT_REFERER => $src));
            Yii::app()->CURL->setUrl($src);
            Yii::app()->CURL->exec();
			
			if(Yii::app()->CURL->isError()) {
				// Error
//				var_dump(Yii::app()->CURL->getErrNo());
//				var_dump(Yii::app()->CURL->getError());
                return false;
			}else{
				// More info about the transfer
				$c=array(
					'ErrNo'=>Yii::app()->CURL->getErrNo(),
					'Error'=>Yii::app()->CURL->getError(),
					'Header'=>Yii::app()->CURL->getHeader(),
					'Info'=>Yii::app()->CURL->getInfo(),
					'Result'=>Yii::app()->CURL->getResult(),
				);
			}
            //小于3M
            if(sizeof($c)<1024*1024*3){
                Yii::app()->cache->set($key, $c, $expire);
            }
		}
		
		if($show==true){
			if(!empty($c['Info']['content_type']))
				header('Content-Type: '.$c['Info']['content_type']);
			if($c['Info']['http_code']==200)
				echo $c['Result'];
		}
		
		return $c;
	}
	
	public static function is_url($url){
		$validate=new CUrlValidator();
		if(empty($url)){
			return false;
		}
		if($validate->validateValue($url)===false){
			return false;
		}
	    return true;
	}

    //取得字段间字符
	public static function cutContent($content='', $start='', $end='', $reg=false)
	{
        //是否启用正则
        if($reg){
            $e_start=preg_split($start, $content, 2, PREG_SPLIT_OFFSET_CAPTURE);
            if(empty($e_start[1][0]) || empty($e_start[1][1])){
                return false;
            }

            $e_end=preg_split($end, $e_start[1][0], 2, PREG_SPLIT_OFFSET_CAPTURE);
            if(empty($e_end[1][0]) || empty($e_end[1][1])){
                return false;
            }

            return $e_end[0][0];
        }else{
            $e_start=explode($start, $content);
            if(!isset($e_start[1]))
                return false;
            $e_end=explode($end, $e_start[1]);
            if(!isset($e_end[1]))
                return false;
            return $e_end[0];
        }

	}

    public static function get_content_type($html){
        if(empty($html)){
            return false;
        }
        self::cutContent($html, '');
    }

    public static function FContent($in){
        if(empty($in)){
            return '';
        }
        //过滤flash
        $in=preg_replace('/<(object|style|script).*?<\/(?(1)\\1)>/isx', '', $in);
//        pr($in);
//        $in=self::insert_more_mark($in);
//        pd($in);
        /**/
        $out=preg_replace_callback('/(<img\s+.*?src=\s*)([\"\']?)([^\'^\"]*?)((?(2)\\2))([^>]*?>)/isx',array('self','mk_img'),$in);
        /*
        $out=preg_replace_callback('/(<img\s+.*?src=\s*)([\"\']?)([^\'^\"]*?)((?(2)\\2))([^>^\/]*?>)([^<^\/]*?)(<\/img>?)/isx',array('self','mk_img'),$in);
        */
        return preg_replace_callback('/(<a\s+.*?href=\s*)([\"\']?)([^\'^\"]*?)((?(2)\\2))([^>^\/]*?>)(.*?)(<\/a>)/isx',array('self','mk_href'),$out);
    }

    public static function insert_more_mark($in){
        /*
        //截断大于500字符的文章，截断标识符 '<br>', '<br />', '</p>', '</div>', '</span>'

        $i=1;
        $m=array();
        foreach($k as $v){
            $h='';
            $f='';
            $c=0;
            $t=false;
            do{
                if(empty($f)){
                    if(($t=stristr($in, $v))==false){
                        break;
                    }
                    $h=stristr($in, $v, true);
                    $f=$t;
                    $c=strlen($h);
                    if($c==0){
                        break;
                    }
                }else{
                    if(($t=stristr($f, $v))==false){
                        break;
                    }
                    $h.=stristr($f, $v, true);
                    $f=$t;
                    if($c==strlen($h)){
                        break;
                    }
                }
            }while(strlen($h)<2000);
            $m[$i++]=strlen($h);
        }
        */
        if(empty($in)){
            return '';
        }
        //定义截断区间
        $start=2000;
        $cut=2500;
        $end=5000; 
        
        $k=array('<br>', '<BR>', '<br />', '<BR />', '<br/>', '<BR/>', '</p>', '</P>', '</div>', '</DIV>', '</span>', "</SPAN>");
        
        $t=array(); //存放分割后的文章
        $m=array(); //存放记录
        $j=0;
        foreach($k as $v){
            $t=explode($v, $in);
            $l=0;

            if(($c=count($t))>1){
                for($i=0;$i<$c;$i++){
                    $l+=strlen($t[$i]);
                    if($l>$start&&isset($t[$i+1])){
                        $t[$i+1]='<!--more-->'.$t[$i+1];
                        if($l+strlen($t[$i+1])<$cut){
                            return implode($v, $t);
                        }else{
                            $m[$j]=array();
                            $m[$j]['t']=$t;
                            $m[$j]['l']=$l;
                            $m[$j]['v']=$v;
                            break;
                        }
                    }
                }
            }
            $j++;
        }

        $tt=$end;
        $the_k='';
        foreach($m as $k => $v){
            if(empty($tt)){
                $tt=$v['l'];
            }else{
                if($v['l']<$tt){
                    $the_k=$k;
                }
            }
        }

//        pr($m);
//        pd($the_k);
        if(empty($the_k)){
            return $in;
        }else{
            return implode($m[$the_k]['v'], $m[$the_k]['t']);
        }
    }

    public static function mk_href($matches)
	{
//        pd($matches);
		if(substr($matches[3],0,7)!=='http://'){
			return $matches[0];
		}
		return $matches[1].$matches[2].'http://'.Yii::app()->params['host'].'/api/href?to='.base64_encode($matches[3]).$matches[4].$matches[5].$matches[6].$matches[7];
	}

    public static function mk_img($matches)
	{
//        pd($matches);
        if(!isset($matches[6])){
            $matches[6]='';
        }
        if(!isset($matches[7])){
            $matches[7]='';
        }
        return $matches[1].$matches[2].'http://'.Yii::app()->params['host'].'/api/img?src='.urlencode($matches[3]).$matches[4].$matches[5].$matches[6].$matches[7];
	}

    public static function getImg($src){
        if(empty($src)){
            return false;
        }
        $o = Tools::OZCurl($src, 3600*24*7, false);
        if($o!=false && isset($o['Result']) && !empty($o['Result'])){
            return $o;
        }
        return false;
    }

    public static function formatHtml($html){
        if(empty($html)){
            return false;
        }

        include_once(
            Yii::getPathOfAlias(
                'application.extensions.simple_html_dom'
            ).DIRECTORY_SEPARATOR.'simple_html_dom.php'
        );
        $html_obj = str_get_html($html);

        //格式化图片地址
        $count=0;
        $count=count($html_obj->find('img'));
        for($i=0;$i<$count;$i++){
            $html_obj->find('img',$i)->src='http://'.Yii::app()->params['host'].YII::app()->createUrl('api/img',array(
                'src'=>rawurlencode(MCrypy::encrypt(trim($html_obj->find('img',$i)->src), Yii::app()->params['mcpass'], 128))
            ));
            $html_obj->find('img',$i)->alt=strtoupper($_SERVER['SERVER_NAME']).'整理';
        }

        //格式化链接地址
        $count=0;
        $count=count($html_obj->find('a'));
        for($i=0;$i<$count;$i++){
            $html_obj->find('a',$i)->href='http://'.Yii::app()->params['host'].YII::app()->createUrl('api/a',array(
                'href'=>rawurlencode(MCrypy::encrypt(trim($html_obj->find('a',$i)->href), Yii::app()->params['mcpass'], 128))
            ));
            $html_obj->find('a',$i)->title=$html_obj->find('a',$i)->plaintext;
        }

        return $html_obj->save();
    }

    public static function getLink($href){
        $c=Tools::OZCurl($href, $expire=3600);

        if(!empty($c['Info']['content_type']))
            header('Content-Type: '.$c['Info']['content_type']);
        if($c['Info']['http_code']==200){
            $search = array (
                "'<script[^>]*?>.*?</script>'si",  // 去掉 javascript
                "'([\r\n])[\s]+'"                   // 去掉空白字符
            );
            $replace = array ("", "\\1");
            $html = preg_replace($search, $replace, $c['Result']);

            include_once(
                Yii::getPathOfAlias(
                    'application.extensions.simple_html_dom'
                ).DIRECTORY_SEPARATOR.'simple_html_dom.php'
            );
            $ad1=Yii::app()->params['ad1'];
            $ad2=Yii::app()->params['ad2'];
            $ad3=Yii::app()->params['ad3'];
            $html_obj = str_get_html($html);
            $html_obj->find('body', 0)->innertext= $ad1.'<div style="float:right;">'.$ad3.'</div>'.
                $html_obj->find('body', 0)->innertext.$ad1.$ad2.$ad2;
            return $html_obj->save();
        }else{
            return false;
        }
    }

    public static function createWords($words = 128)
    {
        $seperate = array("，", "。", "！", "？", "；");
        $strings = '';
        for ($i=0; $i<$words; $i++)
        {
            $strings .= iconv('UTF-16', 'UTF-8', chr(mt_rand(0x00, 0xA5)).chr(mt_rand(0x4E, 0x9F)));
            if (fmod($i, 18) > mt_rand(10, 20))
            {
                $strings .= $seperate[mt_rand(0, 4)];
            }
        }
        return $strings;
    }

    public static function subString_UTF8($str, $start, $lenth)
    {
        $len = strlen($str);
        $r = array();
        $n = 0;
        $m = 0;
        for($i = 0; $i < $lenth; $i++) {
            $x = substr($str, $i, 1);
            $a  = base_convert(ord($x), 10, 2);
            $a = substr('00000000'.$a, -8);
            if ($n < $start){
                if (substr($a, 0, 1) == 0) {
                }elseif (substr($a, 0, 3) == 110) {
                    $i += 1;
                }elseif (substr($a, 0, 4) == 1110) {
                    $i += 2;
                }
                $n++;
            }else{
                if (substr($a, 0, 1) == 0) {
                    $r[ ] = substr($str, $i, 1);
                }elseif (substr($a, 0, 3) == 110) {
                    $r[ ] = substr($str, $i, 2);
                    $i += 1;
                }elseif (substr($a, 0, 4) == 1110) {
                    $r[ ] = substr($str, $i, 3);
                    $i += 2;
                }else{
                    $r[ ] = '';
                }
                if (++$m >= $lenth){
                    break;
                }
            }
        }
        //return $r;
        $o=join('', $r);
        if($lenth<$len){
        	$o .= '…';
        }
        return $o;
    } // End subString_UTF8;

    public static function Pinyin($_String, $_Code='gb2312')
    {
        $_DataKey = "a|ai|an|ang|ao|ba|bai|ban|bang|bao|bei|ben|beng|bi|bian|biao|bie|bin|bing|bo|bu|ca|cai|can|cang|cao|ce|ceng|cha".
        "|chai|chan|chang|chao|che|chen|cheng|chi|chong|chou|chu|chuai|chuan|chuang|chui|chun|chuo|ci|cong|cou|cu|".
        "cuan|cui|cun|cuo|da|dai|dan|dang|dao|de|deng|di|dian|diao|die|ding|diu|dong|dou|du|duan|dui|dun|duo|e|en|er".
        "|fa|fan|fang|fei|fen|feng|fo|fou|fu|ga|gai|gan|gang|gao|ge|gei|gen|geng|gong|gou|gu|gua|guai|guan|guang|gui".
        "|gun|guo|ha|hai|han|hang|hao|he|hei|hen|heng|hong|hou|hu|hua|huai|huan|huang|hui|hun|huo|ji|jia|jian|jiang".
        "|jiao|jie|jin|jing|jiong|jiu|ju|juan|jue|jun|ka|kai|kan|kang|kao|ke|ken|keng|kong|kou|ku|kua|kuai|kuan|kuang".
        "|kui|kun|kuo|la|lai|lan|lang|lao|le|lei|leng|li|lia|lian|liang|liao|lie|lin|ling|liu|long|lou|lu|lv|luan|lue".
        "|lun|luo|ma|mai|man|mang|mao|me|mei|men|meng|mi|mian|miao|mie|min|ming|miu|mo|mou|mu|na|nai|nan|nang|nao|ne".
        "|nei|nen|neng|ni|nian|niang|niao|nie|nin|ning|niu|nong|nu|nv|nuan|nue|nuo|o|ou|pa|pai|pan|pang|pao|pei|pen".
        "|peng|pi|pian|piao|pie|pin|ping|po|pu|qi|qia|qian|qiang|qiao|qie|qin|qing|qiong|qiu|qu|quan|que|qun|ran|rang".
        "|rao|re|ren|reng|ri|rong|rou|ru|ruan|rui|run|ruo|sa|sai|san|sang|sao|se|sen|seng|sha|shai|shan|shang|shao|".
        "she|shen|sheng|shi|shou|shu|shua|shuai|shuan|shuang|shui|shun|shuo|si|song|sou|su|suan|sui|sun|suo|ta|tai|".
        "tan|tang|tao|te|teng|ti|tian|tiao|tie|ting|tong|tou|tu|tuan|tui|tun|tuo|wa|wai|wan|wang|wei|wen|weng|wo|wu".
        "|xi|xia|xian|xiang|xiao|xie|xin|xing|xiong|xiu|xu|xuan|xue|xun|ya|yan|yang|yao|ye|yi|yin|ying|yo|yong|you".
        "|yu|yuan|yue|yun|za|zai|zan|zang|zao|ze|zei|zen|zeng|zha|zhai|zhan|zhang|zhao|zhe|zhen|zheng|zhi|zhong|".
        "zhou|zhu|zhua|zhuai|zhuan|zhuang|zhui|zhun|zhuo|zi|zong|zou|zu|zuan|zui|zun|zuo";

        $_DataValue = "-20319|-20317|-20304|-20295|-20292|-20283|-20265|-20257|-20242|-20230|-20051|-20036|-20032|-20026|-20002|-19990".
        "|-19986|-19982|-19976|-19805|-19784|-19775|-19774|-19763|-19756|-19751|-19746|-19741|-19739|-19728|-19725".
        "|-19715|-19540|-19531|-19525|-19515|-19500|-19484|-19479|-19467|-19289|-19288|-19281|-19275|-19270|-19263".
        "|-19261|-19249|-19243|-19242|-19238|-19235|-19227|-19224|-19218|-19212|-19038|-19023|-19018|-19006|-19003".
        "|-18996|-18977|-18961|-18952|-18783|-18774|-18773|-18763|-18756|-18741|-18735|-18731|-18722|-18710|-18697".
        "|-18696|-18526|-18518|-18501|-18490|-18478|-18463|-18448|-18447|-18446|-18239|-18237|-18231|-18220|-18211".
        "|-18201|-18184|-18183|-18181|-18012|-17997|-17988|-17970|-17964|-17961|-17950|-17947|-17931|-17928|-17922".
        "|-17759|-17752|-17733|-17730|-17721|-17703|-17701|-17697|-17692|-17683|-17676|-17496|-17487|-17482|-17468".
        "|-17454|-17433|-17427|-17417|-17202|-17185|-16983|-16970|-16942|-16915|-16733|-16708|-16706|-16689|-16664".
        "|-16657|-16647|-16474|-16470|-16465|-16459|-16452|-16448|-16433|-16429|-16427|-16423|-16419|-16412|-16407".
        "|-16403|-16401|-16393|-16220|-16216|-16212|-16205|-16202|-16187|-16180|-16171|-16169|-16158|-16155|-15959".
        "|-15958|-15944|-15933|-15920|-15915|-15903|-15889|-15878|-15707|-15701|-15681|-15667|-15661|-15659|-15652".
        "|-15640|-15631|-15625|-15454|-15448|-15436|-15435|-15419|-15416|-15408|-15394|-15385|-15377|-15375|-15369".
        "|-15363|-15362|-15183|-15180|-15165|-15158|-15153|-15150|-15149|-15144|-15143|-15141|-15140|-15139|-15128".
        "|-15121|-15119|-15117|-15110|-15109|-14941|-14937|-14933|-14930|-14929|-14928|-14926|-14922|-14921|-14914".
        "|-14908|-14902|-14894|-14889|-14882|-14873|-14871|-14857|-14678|-14674|-14670|-14668|-14663|-14654|-14645".
        "|-14630|-14594|-14429|-14407|-14399|-14384|-14379|-14368|-14355|-14353|-14345|-14170|-14159|-14151|-14149".
        "|-14145|-14140|-14137|-14135|-14125|-14123|-14122|-14112|-14109|-14099|-14097|-14094|-14092|-14090|-14087".
        "|-14083|-13917|-13914|-13910|-13907|-13906|-13905|-13896|-13894|-13878|-13870|-13859|-13847|-13831|-13658".
        "|-13611|-13601|-13406|-13404|-13400|-13398|-13395|-13391|-13387|-13383|-13367|-13359|-13356|-13343|-13340".
        "|-13329|-13326|-13318|-13147|-13138|-13120|-13107|-13096|-13095|-13091|-13076|-13068|-13063|-13060|-12888".
        "|-12875|-12871|-12860|-12858|-12852|-12849|-12838|-12831|-12829|-12812|-12802|-12607|-12597|-12594|-12585".
        "|-12556|-12359|-12346|-12320|-12300|-12120|-12099|-12089|-12074|-12067|-12058|-12039|-11867|-11861|-11847".
        "|-11831|-11798|-11781|-11604|-11589|-11536|-11358|-11340|-11339|-11324|-11303|-11097|-11077|-11067|-11055".
        "|-11052|-11045|-11041|-11038|-11024|-11020|-11019|-11018|-11014|-10838|-10832|-10815|-10800|-10790|-10780".
        "|-10764|-10587|-10544|-10533|-10519|-10331|-10329|-10328|-10322|-10315|-10309|-10307|-10296|-10281|-10274".
        "|-10270|-10262|-10260|-10256|-10254";
        $_TDataKey = explode('|', $_DataKey);
        $_TDataValue = explode('|', $_DataValue);

        $_Data = (PHP_VERSION>='5.0') ? array_combine($_TDataKey, $_TDataValue) : self::_Array_Combine($_TDataKey, $_TDataValue);
        arsort($_Data);
        reset($_Data);

        if($_Code != 'gb2312') $_String = self::_U2_Utf8_Gb($_String);
        $_Res = '';
        for($i=0; $i<strlen($_String); $i++)
        {
            $_P = ord(substr($_String, $i, 1));
            if($_P>160) { $_Q = ord(substr($_String, ++$i, 1)); $_P = $_P*256 + $_Q - 65536; }
            $_Res .= self::_Pinyin($_P, $_Data);
        }
        return preg_replace("/[^a-z0-9]*/", '', $_Res);
    }

    public static function _Pinyin($_Num, $_Data)
    {
        if ($_Num>0 && $_Num<160 ) return chr($_Num);
        elseif($_Num<-20319 || $_Num>-10247)
            return '';
        else {
            foreach($_Data as $k=>$v){
                if($v<=$_Num) break;
            }
            return $k;
        }
    }

    public static function _U2_Utf8_Gb($_C)
    {
        $_String = '';
        if($_C < 0x80) $_String .= $_C;
        elseif($_C < 0x800)
        {
            $_String .= chr(0xC0 | $_C>>6);
            $_String .= chr(0x80 | $_C & 0x3F);
        }elseif($_C < 0x10000){
            $_String .= chr(0xE0 | $_C>>12);
            $_String .= chr(0x80 | $_C>>6 & 0x3F);
            $_String .= chr(0x80 | $_C & 0x3F);
        } elseif($_C < 0x200000) {
            $_String .= chr(0xF0 | $_C>>18);
            $_String .= chr(0x80 | $_C>>12 & 0x3F);
            $_String .= chr(0x80 | $_C>>6 & 0x3F);
            $_String .= chr(0x80 | $_C & 0x3F);
        }
        return iconv('UTF-8', 'GB2312', $_String);
    }

    function _Array_Combine($_Arr1, $_Arr2)
    {
        for($i=0; $i<count($_Arr1); $i++) $_Res[$_Arr1[$i]] = $_Arr2[$i];
        return $_Res;
    }

    public static function utf8ToUnicode( &$str )
    {
        $unicode = array();
        $values = array();
        $lookingFor = 1;

        for ($i = 0; $i < strlen( $str ); $i++ )
        {
            $thisValue = ord( $str[ $i ] );
            if ( $thisValue < 128 )
                $unicode[] = $thisValue;
            else
            {
                if ( count( $values ) == 0 )
                    $lookingFor = ( $thisValue < 224 ) ? 2 : 3;
                $values[] = $thisValue;
                if ( count( $values ) == $lookingFor )
                {
                    $number = ( $lookingFor == 3 ) ?
                        ( ( $values[0] % 16 ) * 4096 ) + ( ( $values[1] % 64 ) * 64 ) + ( $values[2] % 64 ):
                        ( ( $values[0] % 32 ) * 64 ) + ( $values[1] % 64 );
                    $unicode[] = $number;
                    $values = array();
                    $lookingFor = 1;
                }
            }
        }
        return $unicode;
    }

}