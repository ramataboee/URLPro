<?php
require_once 'URLPro.class.php';

try {
    //variable prefix and affix standard based on oracle apex session items
    $page_prefix = 'P';
    $url_page_prefix = 'PAGE_';
    $dlr_affix = '_DEALER_CODE_';
    $ym_affix = '_MONTH_YR_';

    $URL = new URLPro($url_page_prefix, $page_prefix, $dlr_affix, $ym_affix);
    $query = $URL->getServerInfo('QUERY_STRING');
    $arg = substr($query,0,2);
    if($arg == 'i='){ // prevent infinite redirect loop by checking if the link has be tranformed or not
       $location = $URL->processURL('app.json');
       if(isset($location)){
         header('Location: '.$location);
         exit;
       }
    }
} catch (Exception $e) {
    echo json_encode(Array('error' => $e->getMessage()));
}
?>
