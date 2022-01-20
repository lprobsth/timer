<?php
  /**
  * Plugin Timer: Displays content at given time. (After next cache update)
  * Format: <TIMER [P ]starttime=[endtime|-]> Text to show </TIMER>
  * Time format must be parseable with 
  * {@LINK http://www.php.net/manual/en/function.strtotime.php php strtotime}
  * Valid timestamp formats are descibed {@LINK http://www.gnu.org/software/tar/manual/html_chapter/tar_7.html here}
  * 
  * Examples: <TIMER P 07:00:00=10:00:00>Good morning</TIMER> Shows debug information (P option)
  *           <TIMER 2005-09-24 00:00:00=2005-09-25 23:59:00> Have a nice weekend</TIMER>
  * 
  * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
  * @author     Otto Vainio <otto@valjakko.net>
  */
 
  if(!defined('DOKU_INC')) define('DOKU_INC',realpath(dirname(__FILE__).'/../../').'/');
  if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
  require_once(DOKU_PLUGIN.'syntax.php');
 
  /**
  * All DokuWiki plugins to extend the parser/rendering mechanism
  * need to inherit from this class
  */
  class syntax_plugin_timer extends DokuWiki_Syntax_Plugin {
 

    /**
    * What kind of syntax are we?
    */
    function getType(){
      return 'container';
    }
 
    function getSort(){ 
      return 358;
    }
    function connectTo($mode) { 
      $this->Lexer->addEntryPattern('<timer.*?>(?=.*?\x3C/timer\x3E)',$mode,'plugin_timer'); 
    }
    function postConnect() { 
      $this->Lexer->addExitPattern('</timer>','plugin_timer'); 
    }
 
 
    /**
    * Handle the match
    */
    function handle($match, $state, $pos, Doku_Handler $handler){
      global $conf;
      switch ($state) {
        case DOKU_LEXER_ENTER :
          $str = substr($match, 7, -1);
          //$conf['dformat']
          $prt=0;
          if (substr($str,0,1)=="P") {
            $prt=1;
          }
          $str=substr($str,1);
          list($starttime, $endtime) = preg_split("/=/u", $str, 2);
          return array($state, array($prt,$starttime,$endtime));
        case DOKU_LEXER_UNMATCHED :  return array($state, $match);
        case DOKU_LEXER_EXIT :       return array($state, '');
      }
      return array();
    }
 
    /**
    * Create output
    */
    function render($mode,Doku_Renderer $renderer, $data) {
      global $st;
      global $et;
      global $conf;
      global $prt;
      if($mode == 'xhtml'){
        list($state, $match) = $data;
        switch ($state) {
        case DOKU_LEXER_ENTER :      
          list($prts,$starttime,$endtime) = $match;
          $err = "";
          if (($timestamp = strtotime($starttime)) === -1) {
            // If time false do not show.
            $sts = mktime()+10000;
            $err= "Starttime ($starttime) is invalid";
          } else {
            $sts = $timestamp;
          }
          if ($endtime=="-") {
            $ets = strtotime("+1 days");
          } else {
            if (($timestamp = strtotime($endtime)) === -1) {
              // If time false do not show.
              $ets = mktime()-10000;
              $err .= " Endtime ($endtime) is invalid";
            } else {
              $ets = $timestamp;
            }
          }
          $prt = $prts;
          $st = $sts;
          $et = $ets;
          $renderer->doc .= $err;
          break;
        case DOKU_LEXER_UNMATCHED :
          $now = mktime();
          if (($st<$now) && ($et>$now)) {
            if ($prt>0) {
              $renderer->doc .= " From:" . date($conf['dformat'],$st);
              $renderer->doc .= " Now:<b>" . date($conf['dformat']) . "</b>";
              $renderer->doc .= " To:" . date($conf['dformat'],$et);
            }
              // get the standalone html code for this dokuwiki syntax section
              $str = p_render('xhtml',p_get_instructions($match),$info);
              // strip unnecessary paragraphs and new lines
              $str = preg_replace('/^.*<p>(.*?)<\/p>.*$/s', '$1', $str);
              $renderer->doc .= $str;
          } else {
            if ($prt>0) {
              if ($now<$st) {
                $renderer->doc .= " Now:<b>" . date($conf['dformat']) . "</b>";
                $renderer->doc .= " From:" . date($conf['dformat'],$st);
                $renderer->doc .= " To:" . date($conf['dformat'],$et);
              } else {
                $renderer->doc .= " From:" . date($conf['dformat'],$st);
                $renderer->doc .= " To:" . date($conf['dformat'],$et);
                $renderer->doc .= " Now:<b>" . date($conf['dformat']) . "</b>";
              }

            }
          }
          // Disable cache if start or end time within now and next cache refresh time
          if (($now>$st-$conf['cachetime'] && $now<$st) || ($now>$et-$conf['cachetime'] && $now<$et)) {
            $renderer->nocache();
          }
          $renderer->doc .= $cac;
          break;
        case DOKU_LEXER_EXIT :
          break;
        }
        return true;
      }
      return false;
    }
  }
?>
