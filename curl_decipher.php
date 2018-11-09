<?php

namespace Home\Controller;
use Think\Controller;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Exception\RequestException;
class IndexController extends Controller
{
    protected $client;
    protected $JSESSIONID;
    protected $number;
    function _initialize()
    {
        Vendor('autoload');
        $this->client = new Client([
            'base_uri' => 'https://segmentfault.com',
            'headers' => [
                'User-Agent'      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/68.0.3440.75 Safari/537.36',
                'Host'            => 'www.biaojiquxiao.com',
            ],
            'cookies' => true,
            'http_errors' => true,
        ]);
    }

    function index(){
        $this->number = I('get.number');

        $cookie = $this->checkCode();  //访问www.**.com/checkCode获取cookie
        $this->JSESSIONID =$cookie['Value'];

        while (true){
            $img = $this->getImg();         //www.**.com/code 获取图片
            $data = $this->getPoint($img);  //获取坐标
            if($data){                      //获取成功跳出
                break;
            }
        }
        $data['number'] = $this->number;

        if($this->checkCodeExc($cookie,$data)=='1'){    //图片验证成功 1  失败 0
            if($this->query($cookie)){
                $res= $this->status($cookie);
                $code = 0;
                while (true){
                    if(json_decode($res)->status != '0'){   //返回结果为空 重新获取
                        $code += 1;
                        break;
                    }else{
                        $res= $this->status($cookie);
                        continue;
                    }
                }
            }
        } else{
            $res = "图片验证失败";
            $code = 0;
        }

        $message = '发送的数据x，y，number 分别是'.implode(',',$data);
        $result = array(       //最终获取的结果
            'code'    => $code ,
            'message' => $message ,
            'data' => $res
        );
        exit(json_encode($result, JSON_UNESCAPED_UNICODE));

    }

    function query($cookie){
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://www.biaojiquxiao.com/query/".$this->number,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                "Content-Type: application/x-www-form-urlencoded",
                "Cookie: JSESSIONID=".$cookie,
                "cache-control: no-cache"
            ),
        ));
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        if ($err) {
            return "cURL Error #:" . $err;
        } else {
            //echo $response;
            return true;
        }
    }
    function status($cookie){
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://www.biaojiquxiao.com/status/".$this->number,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                "Content-Type: application/x-www-form-urlencoded",
                "Cookie: JSESSIONID=".$cookie,
                "cache-control: no-cache"
            ),
        ));
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        if ($err) {
            return "cURL Error #:" . $err;
        } else {
            return (string) $response;
        }
    }
    function checkCodeExc($cookie,$data){
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://www.biaojiquxiao.com/checkCodeExc",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => "x=".$data['x']."&y=".$data['y']."&number=".$data['number'],
            CURLOPT_HTTPHEADER => array(
                "Content-Type: application/x-www-form-urlencoded",
                "Cookie: JSESSIONID=".$cookie,
                "Host: www.biaojiquxiao.com",
                "Origin: https://www.biaojiquxiao.com",
                "Referer: https://www.biaojiquxiao.com/checkCode/17633465362",
                "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/68.0.3440.75 Safari/537.36",
                "cache-control: no-cache"
            ),
        ));
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        if ($err) {
            return "cURL Error #:" . $err;
        } else {
            // var_dump($response);
            return $response;
        }
    }

    function checkCode(){
        $url ='https://www.biaojiquxiao.com/checkCode/'.$this->number;
        $res = $this->client->request('GET',$url);
        $this->connTime = time();
        $config = $this->client->getConfig();
        $cookie = $config['cookies']->toArray()[0];
        return $cookie['Value'];
    }
    public function getImg()
    {
        $url = "https://www.biaojiquxiao.com/code";
        $path = constant('PUBLIC') . 'images/tempImg';
        if (!is_dir($path)) {
            mkdir('./' . $path, 0777, true);
        }
        $imgName = substr(str_shuffle('abcdefghijkmnpqrstwxyz23456789'), rand(0,15), rand(3,10)) . '.jpg';
        $path = $path . '/' . $imgName;
        $response = $this->client->get($url,
            array(
                'headers' => [
                    'Referer' => 'https://www.biaojiquxiao.com/checkCode/'.$this->number,
                    'Host'    => 'www.biaojiquxiao.com',
                    'Accept'  => 'image/webp,image/apng,image/*,*/*;q=0.8',
                    'Accept-Encoding' => ' gzip, deflate, br',
                    'Cookie'          => "JSESSIONID=".$this->JSESSIONID,
                ],
            )
        );
        $content = $response->getBody().stream;
        $myfile = fopen($path, "w");
        fwrite($myfile, $content);
        fclose($myfile);
        if (file_exists($path)) {
            return $path;
        } else {
            return false;
        }
    }



    function getPoint($path){
        //$path = constant('PUBLIC').'images/test/code.png';
        $img = base64_encode(file_get_contents($path));
        //sleep(1);
        $data = array(
            'appid' => "1252193721",
            'bucket' => 'tencentyun',
            'image' => $img
        );
        $content = json_encode($data);
        $url = 'http://recognition.image.myqcloud.com/ocr/handwriting';
        $authorization = $this->getAuthorization();
        $response = $this->posturl($url, $content, $authorization);
        if ($response['code'] != 0) {    //腾讯api请求失败
            // var_dump($response['message']);
            return $response['message'];
        } else {
            $result = array();
            if($response['data']['items'][0]['words'][4]['confidence'] < 0.5){
                $result = null;
            }else{
                $aimWord = $response['data']['items'][0]['words'][4]['character'];
                for ($i = 1; $i <= 4; $i++) {
                    if ($response['data']['items'][$i]['itemstring'] == $aimWord) {
                        $result['x'] = $response['data']['items'][$i]['itemcoord']['x']+15;
                        $result['y'] = $response['data']['items'][$i]['itemcoord']['y']+15;
                        unset($response);
                        break;
                    }
                }
            }
            if (file_exists($path)){
                unlink($path);        //删除临时图片
            }
            if(empty($result)){
                return false;
            }else{
                return $result;
            }
        }
    }



    //有效签名串
    function getAuthorization()
    {
        $path = constant('PUBLIC') . 'temp/authorization.xml';
        $xml = simplexml_load_file($path);
        //如果签名没有过期直接返回
        if ($xml->overtime < time()) {
            return $xml->content;
        }
        $appid = "保密";
        $bucket = "保密";
        $secret_id = "保密";
        $secret_key = "保密";
        $expired = time() + 2592000;
        $current = time();
        $rdm = rand();
        $srcStr = 'a=' . $appid . '&b=' . $bucket . '&k=' . $secret_id . '&e=' . $expired . '&t=' . $current . '&r=' . $rdm . '&f=';
        $signStr = base64_encode(hash_hmac('SHA1', $srcStr, $secret_key, true) . $srcStr);
        //保存签名
        $overtime = $expired - 2000;
        $str = "<?xml version=\"1.0\" encoding=\"utf8\"?><authorization><overtime>$overtime</overtime><content>$signStr</content></authorization>";
        file_put_contents($path, $str);
        return $signStr;
    }

    //调用腾讯接口 返回数组 $data json
    function posturl($url, $data, $authorization)
    {
        $headerArray = array("Content-type:application/json;charset='utf-8'", "Accept:application/json", " Host:recognition.image.myqcloud.com", "Authorization:" . $authorization);
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headerArray);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($curl);
        curl_close($curl);
        return json_decode($output, true);
    }
}
