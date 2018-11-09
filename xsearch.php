<?php
require_once('workflows.php');
require('HtmlDomParser.php');
require_once("./Requests-1.7.0/library/Requests.php");

use Sunra\PhpSimple\HtmlDomParser;
mb_internal_encoding("UTF-8");

class Xsearch{
    private function getplaintextintrofromhtml($html) {

        // Remove the HTML tags
        $html = strip_tags($html);
    
        // Convert HTML entities to single characters
        $html = html_entity_decode($html, ENT_QUOTES, 'UTF-8');
    
        return $html;
    }
    
    private function request_post($url, $post_data) {
        $headers = array(
            'cache-control' => 'no-cache',
            'X-Requested-With' => 'XMLHttpRequest',
            'Content-Type' => 'application/x-www-form-urlencoded',
            'content-type' => 'multipart/form-data; boundary=----WebKitFormBoundary7MA4YWxkTrZu0gW'
          );
        $response = Requests::post($url, $headers, $post_data);
        return $response->body;
    }
    
    private function addResult($wf, $data, $position, $icon='icon.png'){
        $data = $this->getplaintextintrofromhtml($data);
        $data = preg_replace('/\s+/', ' ', $data);
        
        $count = mb_strlen($data);
    
        if($count<=26){
            $d_title = $data;
            $d_desc = $data;
        }else{
            $d_title = mb_substr($data,0,26);
            $d_desc = mb_substr($data,26);
        }
        $wf->result( $i.time(), "$data", "$d_title", "$d_desc", $icon );
    }
    
    private function queryXiangxing($orig){
        $html = HtmlDomParser::file_get_html( "http://www.vividict.com/Word.aspx?ie=utf8&wd=".urlencode($orig));
    
        $data = $html->find('.neirong p', 0);
    
        return $data;
    }
    
    private function queryHanyu($orig){
        $post_data = array(
            'words[]' => $orig   
        );
        $data = $this->request_post('http://dict.iguci.cn/dictionary/dcontent/simple', $post_data);
        $json =  json_decode($data);
        return $json->{"\"$orig\""}->{'content'};
    }
    
    private function shuowen($orig){
        $url = "http://www.shuowen.org/?kaishu=".urlencode($orig)."&pinyin=&bushou=&jiegou=";
        $html = HtmlDomParser::file_get_html($url);
        $data = $html->find('.media-body', 0);
        return $data;
    }
    
    public function search($orig){
        Requests::register_autoloader();
        $wf = new Workflows();

        $this->addResult($wf, $this->queryXiangxing($orig), 1,'icon_xiang.png');
        $this->addResult($wf, $this->queryHanyu($orig), 2,'icon_han.png');
        $this->addResult($wf, $this->shuowen($orig), 3, 'icon_shuo.png');
        
        $results = $wf->results();
        if ( count( $results ) == 0 ):
            $wf->result( 'xsearch', $orig, 'No Found', 'Search 象形词典 but not found. Search 象形词典 for '.$orig, 'icon.png' );
        endif;
        
        echo $wf->toxml();
    }
    
}

