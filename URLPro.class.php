<?php
class URLPro{
  private $dealer_code = '';
  private $year_month = '';
  private $page = '';
  private $download_flag = '';

 /**
  * Accept variable page prefixed and dealer affixes based on hidden items as a constructor
  */
  public function __construct($url_page_prefix, $page_prefix, $dlr_affix, $ym_affix){
    try{

      $this->url_page_prefix = $url_page_prefix;
      $this->dlr_affix = $dlr_affix;
      $this->ym_affix = $ym_affix;
      $this->page_prefix = $page_prefix;

    }catch(Exception $e){
      echo json_encode(Array('error' => $e->getMessage()));
    }
  }

  public function getServerInfo($args){
    if(isset($_SERVER[$args])){
      return $_SERVER[$args];
    }
  }

  /**
   * Generate a P2 parameter to be used in the newly generated URL
   * @params: page number, dealer code and month-year
   * @return: P2
   */
  private function buildP2($pg, $dlr, $ym){
    $p = $this->page_prefix;
    $d = $this->dlr_affix;
    $purl = $this->url_page_prefix;
    $ym_aff = $this->ym_affix;
    $cc = '|';
    $pid = $p.$pg;

    $P2_YM = (isset($ym) ? $pid.$ym_aff.$ym : '');
    $P2_PAGE = $purl.$pg;
    $P2_DEALER = $pid.$d.$dlr;

    return $P2_PAGE.$cc.$P2_DEALER.$cc.$P2_YM;
  }

  /**
   * Generates a curated URI compliant with P1, P2 and P3 formats
   * @params: server name from the inbound uri, the directory, (P1,P2 and P3 parameters)
   */
  public function genURI($server, $urldir, $params){
    $query =  urldecode(http_build_query($params));
    $link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ?
                    "https" : "http") . "://" .$server.'/?'.$query;
    return $link;
  }

  /**
   * Defines the username and database based on the following
   * @params: server name and mapping file directory
   */
  private function serverMapping($server,$file_dir){
    $iterator = $this->readJSON($file_dir);
    foreach ($iterator as $key => $val) {
      if($key == $server){
        if(is_array($val)){
          return $val;
        }
      }
    }
  }

  private function readJSON($file){
    $json = file_get_contents($file);
    $jsonIterator = new RecursiveIteratorIterator(
                    new RecursiveArrayIterator(json_decode($json, TRUE)),
                        RecursiveIteratorIterator::SELF_FIRST);
    return $jsonIterator;
  }

 /**
  * Curate the inbound url from server information and mapping json files
  * @return: curated URI with P1, P2 AND P3 formats
  */
  public function processURL($jsonfile){
    try{
      $app_file = $jsonfile;
      $query = $this->getServerInfo('QUERY_STRING');
      $self = strtok($this->getServerInfo('REQUEST_URI'),'?');
      $server_name = $this->getServerInfo('HTTP_HOST');

      $app_info = $this->serverMapping($server_name,$app_file);
      $app_user = $app_info['app_usr'];
      $app_db = $app_info['app_db'];

      if($query){
        foreach(explode('&', $query) as $chunk) {

            $param = explode("=", $chunk);
            if ($param) {
               $param_enc = urldecode($param[0]);
               if(urldecode($param[1])){
                 $param_val = urldecode($param[1]);
                  switch($param_enc){
                    case 'i':
                         $this->dealer_code = $param_val;
                         break;
                    case 'm':
                         $this->year_month = ucfirst(strtolower($param_val));
                         break;
                    case 'p':
                         $this->page = $param_val;
                         break;
                    case 'd':
                         $this->download_flag = $param_val;
                         break;
                  }
               }
            }
        }

      }else{
        echo 'URI query violation';
      }

        $P1 = $app_user;
        $P2 = $this->buildP2($this->page, $this->dealer_code, $this->year_month);
        $P3 = $app_db;

        $parameters = Array(
          'P1' => $P1,
          'P2' => $P2,
          'P3' => $P3
        );

        return $this->genURI($server_name, $self, $parameters);

    }catch(Exception $e){
      echo json_encode(Array('error' => $e->getMessage()));
    }
  }
}
?>
